<?php

class ProveedoresEsquema extends DBSchema {

  public function tablasProveedoresErp() {
    return array_keys($this->contratosAuditoriaProveedoresErp());
  }

  public function auditarProveedoresErp() {
    $auditoria = array();
    $tienePendientes = false;
    $resumen = array(
      "tablas_faltantes" => 0,
      "columnas_faltantes" => 0,
      "indices_faltantes" => 0,
      "indices_con_columnas_distintas" => 0
    );
    $contratos = $this->contratosAuditoriaProveedoresErp();

    foreach ($contratos as $tabla => $reglas) {
      $existe = $this->tablaExiste($tabla);
      $auditoria[$tabla] = $this->auditarTablaContratoProveedor($tabla, $existe, $reglas);

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
      "mensaje" => $tienePendientes ? "Hay pendientes en el esquema planeado de Proveedores ERP" : "El esquema planeado de Proveedores ERP esta completo",
      "depurar" => array(
        "sin_escrituras" => true,
        "tiene_pendientes" => $tienePendientes,
        "resumen" => $resumen,
        "auditoria" => $auditoria,
        "nota" => "Contrato propuesto para auditoria. No ejecuta DDL ni decide migracion/extensiones finales."
      )
    );
  }

  public function planActualizarProveedoresErp($ejecutar = false) {
    $plan = array();
    $errores = 0;
    $ejecutados = 0;
    $generados = 0;

    foreach ($this->definicionesProveedoresErp() as $tabla => $definicion) {
      $resultadoCrear = $this->crearTablaSiNoExiste($tabla, $definicion["crear"], "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);
      $plan[] = array("tabla" => $tabla, "accion" => "crear_tabla", "resultado" => $resultadoCrear);
      if ($resultadoCrear["error"]) {
        $errores++;
      } elseif ($this->valorArreglo($resultadoCrear["depurar"], "ejecutado", false)) {
        $ejecutados++;
      } elseif ($resultadoCrear["tipo"] === "info") {
        $generados++;
      }

      if (!$this->tablaExiste($tabla) && !$ejecutar) {
        continue;
      }

      foreach ($this->valorArreglo($definicion, "columnas", array()) as $columna => $sql) {
        $resultadoColumna = $this->agregarColumnaSiNoExiste($tabla, $columna, $sql, $ejecutar);
        $plan[] = array("tabla" => $tabla, "accion" => "agregar_columna", "columna" => $columna, "resultado" => $resultadoColumna);
        if ($resultadoColumna["error"]) {
          $errores++;
        } elseif ($this->valorArreglo($resultadoColumna["depurar"], "ejecutado", false)) {
          $ejecutados++;
        } elseif ($resultadoColumna["tipo"] === "info") {
          $generados++;
        }
      }

      foreach ($this->valorArreglo($definicion, "indices", array()) as $indice => $sql) {
        if ($indice === "PRIMARY") {
          continue;
        }
        $resultadoIndice = $this->agregarIndiceSiNoExiste($tabla, $indice, $sql, $ejecutar);
        $plan[] = array("tabla" => $tabla, "accion" => "agregar_indice", "indice" => $indice, "resultado" => $resultadoIndice);
        if ($resultadoIndice["error"]) {
          $errores++;
        } elseif ($this->valorArreglo($resultadoIndice["depurar"], "ejecutado", false)) {
          $ejecutados++;
        } elseif ($resultadoIndice["tipo"] === "info") {
          $generados++;
        }
      }
    }

    return array(
      "error" => $errores > 0,
      "tipo" => $errores > 0 ? "danger" : ($ejecutar ? "success" : "info"),
      "mensaje" => $ejecutar ? "Plan de Proveedores ERP ejecutado" : "Plan de Proveedores ERP generado sin ejecutar",
      "depurar" => array(
        "ejecutar" => $ejecutar,
        "errores" => $errores,
        "ejecutados" => $ejecutados,
        "generados_o_existentes" => $generados,
        "total" => count($plan),
        "plan" => $plan
      )
    );
  }

  public function contratosAuditoriaProveedoresErp() {
    return array(
      "erp_proveedores" => array(
        "descripcion" => "Raiz existente del proveedor. Se conserva como identidad primaria y puente operativo durante la transicion.",
        "columnas" => array(
          "id_proveedor" => array("severidad" => "critica", "impacto" => "Identifica proveedor en Compras, Catalogo y legado."),
          "proveedor" => array("severidad" => "critica", "impacto" => "Nombre base del proveedor existente."),
          "cuota" => array("severidad" => "baja", "impacto" => "Dato legado pendiente de clasificar como condicion comercial o historico."),
          "estatus_erp" => array("severidad" => "critica", "impacto" => "Estado puente para bloquear o permitir operacion desde Compras/Catalogo sin depender de pantallas legacy."),
          "origen_erp" => array("severidad" => "media", "impacto" => "Distingue registros legacy, migrados o capturados en ERP nuevo."),
          "fecha_actualizacion" => array("severidad" => "media", "impacto" => "Fecha de ultimo cambio operativo del proveedor raiz.")
        ),
        "indices" => array(
          "PRIMARY" => array("columnas" => array("id_proveedor"), "severidad" => "critica", "impacto" => "Raiz de relaciones con listas, Compras y Catalogo."),
          "idx_proveedor_estatus_erp" => array("columnas" => array("estatus_erp"), "severidad" => "alta", "impacto" => "Permite filtrar proveedores operables sin romper IDs existentes.")
        )
      ),
      "erp_proveedores_perfil" => array(
        "descripcion" => "Perfil ERP del proveedor sin sobrecargar la tabla raiz.",
        "columnas" => array(
          "id_proveedor" => array("severidad" => "critica", "impacto" => "Vincula el perfil con el proveedor existente."),
          "nombre_comercial" => array("severidad" => "alta", "impacto" => "Nombre visible operativo."),
          "nombre_corto" => array("severidad" => "media", "impacto" => "Nombre abreviado para busquedas, tablas y pantallas compactas."),
          "codigo_proveedor_erp" => array("severidad" => "media", "impacto" => "Codigo interno opcional para identificar proveedor sin depender del nombre."),
          "tipo_proveedor" => array("severidad" => "media", "impacto" => "Permite clasificar proveedor sin depender de reglas legacy."),
          "clasificacion_operativa" => array("severidad" => "media", "impacto" => "Clasificacion interna para delegar seguimiento sin crear reglas fiscales."),
          "origen" => array("severidad" => "media", "impacto" => "Distingue captura manual, legado, migracion u otro origen autorizado."),
          "responsable_interno_id" => array("severidad" => "media", "impacto" => "Usuario responsable del seguimiento operativo del proveedor, si se autoriza."),
          "notas" => array("severidad" => "baja", "impacto" => "Contexto operativo no estructurado."),
          "creado_por" => array("severidad" => "media", "impacto" => "Trazabilidad de captura."),
          "revisado_por" => array("severidad" => "media", "impacto" => "Trazabilidad de revision."),
          "autorizado_por" => array("severidad" => "alta", "impacto" => "Trazabilidad de autorizacion operativa."),
          "fecha_revision" => array("severidad" => "media", "impacto" => "Fecha de revision del perfil."),
          "fecha_autorizacion" => array("severidad" => "alta", "impacto" => "Fecha de autorizacion operativa."),
          "fecha_registro" => array("severidad" => "media", "impacto" => "Fecha de alta del perfil ERP."),
          "fecha_actualizacion" => array("severidad" => "media", "impacto" => "Fecha de ultimo cambio.")
        ),
        "indices" => array(
          "PRIMARY" => array("columnas" => array("id_proveedor"), "severidad" => "critica", "impacto" => "Un perfil ERP por proveedor."),
          "idx_proveedor_perfil_codigo" => array("columnas" => array("codigo_proveedor_erp"), "severidad" => "media", "impacto" => "Permite buscar proveedor por codigo interno."),
          "idx_proveedor_perfil_tipo" => array("columnas" => array("tipo_proveedor"), "severidad" => "media", "impacto" => "Soporta segmentacion operativa."),
          "idx_proveedor_perfil_responsable" => array("columnas" => array("responsable_interno_id"), "severidad" => "media", "impacto" => "Permite filtrar proveedores por responsable interno.")
        )
      ),
      "erp_proveedores_fiscales" => array(
        "descripcion" => "Datos fiscales del proveedor y evidencia asociada.",
        "columnas" => array(
          "id_proveedor_fiscal" => array("severidad" => "critica", "impacto" => "Identificador del registro fiscal."),
          "id_proveedor" => array("severidad" => "critica", "impacto" => "Proveedor dueño del registro fiscal."),
          "rfc" => array("severidad" => "alta", "impacto" => "Identificacion fiscal para compras/facturacion."),
          "razon_social" => array("severidad" => "alta", "impacto" => "Razon social fiscal."),
          "regimen_fiscal" => array("severidad" => "media", "impacto" => "Regimen fiscal declarado."),
          "codigo_postal_fiscal" => array("severidad" => "alta", "impacto" => "Codigo postal fiscal requerido para validaciones CFDI/SAT."),
          "pais" => array("severidad" => "media", "impacto" => "Pais fiscal del proveedor."),
          "estado" => array("severidad" => "media", "impacto" => "Estado fiscal del proveedor."),
          "municipio" => array("severidad" => "media", "impacto" => "Municipio fiscal del proveedor."),
          "colonia" => array("severidad" => "baja", "impacto" => "Colonia fiscal si se captura."),
          "calle" => array("severidad" => "baja", "impacto" => "Calle fiscal si se captura."),
          "numero_exterior" => array("severidad" => "baja", "impacto" => "Numero exterior fiscal si se captura."),
          "numero_interior" => array("severidad" => "baja", "impacto" => "Numero interior fiscal si se captura."),
          "domicilio_fiscal" => array("severidad" => "media", "impacto" => "Domicilio fiscal completo como texto de respaldo."),
          "uso_cfdi_preferido" => array("severidad" => "baja", "impacto" => "Dato de apoyo si negocio lo autoriza."),
          "id_documento_constancia" => array("severidad" => "alta", "impacto" => "Liga a la constancia fiscal almacenada como evidencia."),
          "fecha_constancia" => array("severidad" => "media", "impacto" => "Fecha de emision o referencia de la constancia fiscal."),
          "validado_por" => array("severidad" => "alta", "impacto" => "Usuario que valido los datos fiscales."),
          "fecha_validacion" => array("severidad" => "alta", "impacto" => "Fecha de validacion fiscal."),
          "vigencia_desde" => array("severidad" => "media", "impacto" => "Inicio de vigencia fiscal si aplica."),
          "vigencia_hasta" => array("severidad" => "media", "impacto" => "Fin de vigencia fiscal si aplica."),
          "estatus" => array("severidad" => "alta", "impacto" => "Distingue fiscal vigente, pendiente o historico."),
          "fecha_registro" => array("severidad" => "media", "impacto" => "Fecha de captura."),
          "fecha_actualizacion" => array("severidad" => "media", "impacto" => "Fecha de ultimo cambio.")
        ),
        "indices" => array(
          "PRIMARY" => array("columnas" => array("id_proveedor_fiscal"), "severidad" => "critica", "impacto" => "Identificador fiscal."),
          "idx_proveedor_fiscal_proveedor" => array("columnas" => array("id_proveedor", "estatus"), "severidad" => "critica", "impacto" => "Consulta fiscales vigentes o historicos por proveedor."),
          "idx_proveedor_fiscal_rfc" => array("columnas" => array("rfc"), "severidad" => "alta", "impacto" => "Busqueda y posible control de duplicados por RFC."),
          "idx_proveedor_fiscal_cp" => array("columnas" => array("codigo_postal_fiscal"), "severidad" => "media", "impacto" => "Apoya validaciones fiscales por codigo postal."),
          "idx_proveedor_fiscal_vigencia" => array("columnas" => array("vigencia_desde", "vigencia_hasta"), "severidad" => "media", "impacto" => "Consulta registros fiscales por vigencia.")
        )
      ),
      "erp_proveedores_contactos" => array(
        "descripcion" => "Contactos por area funcional del proveedor.",
        "columnas" => array(
          "id_contacto_proveedor" => array("severidad" => "critica", "impacto" => "Identificador del contacto."),
          "id_proveedor" => array("severidad" => "critica", "impacto" => "Proveedor asociado."),
          "area" => array("severidad" => "alta", "impacto" => "Compras, ventas, cobranza, logistica, soporte u otra area autorizada."),
          "nombre" => array("severidad" => "alta", "impacto" => "Nombre del contacto."),
          "puesto" => array("severidad" => "baja", "impacto" => "Contexto del contacto."),
          "correo" => array("severidad" => "media", "impacto" => "Medio de comunicacion."),
          "telefono" => array("severidad" => "media", "impacto" => "Medio de comunicacion."),
          "extension" => array("severidad" => "baja", "impacto" => "Extension telefonica si aplica."),
          "celular" => array("severidad" => "media", "impacto" => "Contacto movil para operacion urgente si se autoriza."),
          "whatsapp" => array("severidad" => "baja", "impacto" => "Canal alterno, no obligatorio."),
          "recibe_ordenes_compra" => array("severidad" => "media", "impacto" => "Indica si puede recibir ordenes de compra."),
          "recibe_notificaciones" => array("severidad" => "media", "impacto" => "Indica si se le pueden enviar avisos operativos."),
          "es_principal" => array("severidad" => "media", "impacto" => "Contacto preferente por proveedor/area."),
          "prioridad" => array("severidad" => "baja", "impacto" => "Orden de preferencia dentro del area."),
          "observaciones" => array("severidad" => "baja", "impacto" => "Notas operativas del contacto."),
          "estatus" => array("severidad" => "media", "impacto" => "Activo, historico o descartado."),
          "creado_por" => array("severidad" => "media", "impacto" => "Usuario que capturo el contacto."),
          "fecha_registro" => array("severidad" => "media", "impacto" => "Fecha de alta."),
          "fecha_actualizacion" => array("severidad" => "media", "impacto" => "Fecha de cambio.")
        ),
        "indices" => array(
          "PRIMARY" => array("columnas" => array("id_contacto_proveedor"), "severidad" => "critica", "impacto" => "Identificador del contacto."),
          "idx_proveedor_contacto_proveedor" => array("columnas" => array("id_proveedor", "estatus"), "severidad" => "critica", "impacto" => "Lista contactos activos/historicos por proveedor."),
          "idx_proveedor_contacto_area" => array("columnas" => array("id_proveedor", "area", "estatus"), "severidad" => "media", "impacto" => "Filtra contacto por area."),
          "idx_proveedor_contacto_correo" => array("columnas" => array("correo"), "severidad" => "baja", "impacto" => "Permite ubicar contacto por correo.")
        )
      ),
      "erp_proveedores_condiciones" => array(
        "descripcion" => "Condiciones comerciales y logisticas vigentes o historicas.",
        "columnas" => array(
          "id_condicion_proveedor" => array("severidad" => "critica", "impacto" => "Identificador de condicion."),
          "id_proveedor" => array("severidad" => "critica", "impacto" => "Proveedor asociado."),
          "moneda_preferida" => array("severidad" => "alta", "impacto" => "Moneda operativa sugerida, si negocio la autoriza."),
          "requiere_orden_compra" => array("severidad" => "alta", "impacto" => "Indica si el proveedor requiere OC formal antes de surtir."),
          "forma_pago_preferida" => array("severidad" => "media", "impacto" => "Forma de pago de referencia; no sustituye autorizacion financiera."),
          "metodo_pago_preferido" => array("severidad" => "media", "impacto" => "Metodo de pago de referencia; no sustituye autorizacion financiera."),
          "dias_credito" => array("severidad" => "media", "impacto" => "Condicion comercial de pago."),
          "limite_credito" => array("severidad" => "media", "impacto" => "Referencia comercial si aplica."),
          "minimo_compra" => array("severidad" => "media", "impacto" => "Minimo de compra si aplica."),
          "minimo_unidades" => array("severidad" => "baja", "impacto" => "Minimo en unidades/piezas si el proveedor lo maneja."),
          "tiempo_entrega_dias" => array("severidad" => "media", "impacto" => "Apoyo a Compras y planeacion."),
          "dias_surtido" => array("severidad" => "baja", "impacto" => "Dias o ventana habitual de surtido si aplica."),
          "tipo_flete" => array("severidad" => "media", "impacto" => "Define si el flete es proveedor, cliente, mixto u otro esquema autorizado."),
          "cobertura_entrega" => array("severidad" => "media", "impacto" => "Cobertura geografica/logistica de entrega."),
          "condiciones_pago" => array("severidad" => "media", "impacto" => "Texto o resumen de condiciones de pago."),
          "condiciones_logisticas" => array("severidad" => "media", "impacto" => "Texto o resumen logistico."),
          "restricciones_operativas" => array("severidad" => "media", "impacto" => "Restricciones de pedido, recepcion, horarios o documentacion."),
          "observaciones" => array("severidad" => "baja", "impacto" => "Notas internas sobre condiciones."),
          "vigencia_desde" => array("severidad" => "alta", "impacto" => "Inicio de vigencia."),
          "vigencia_hasta" => array("severidad" => "media", "impacto" => "Fin de vigencia si aplica."),
          "autorizado_por" => array("severidad" => "alta", "impacto" => "Usuario que autoriza condiciones vigentes."),
          "fecha_autorizacion" => array("severidad" => "alta", "impacto" => "Fecha de autorizacion de condiciones."),
          "estatus" => array("severidad" => "alta", "impacto" => "Vigente, historica o pendiente."),
          "fecha_registro" => array("severidad" => "media", "impacto" => "Fecha de captura."),
          "fecha_actualizacion" => array("severidad" => "media", "impacto" => "Fecha de cambio.")
        ),
        "indices" => array(
          "PRIMARY" => array("columnas" => array("id_condicion_proveedor"), "severidad" => "critica", "impacto" => "Identificador de condicion."),
          "idx_proveedor_condicion_proveedor" => array("columnas" => array("id_proveedor", "estatus"), "severidad" => "alta", "impacto" => "Consulta condicion vigente del proveedor."),
          "idx_proveedor_condicion_moneda" => array("columnas" => array("moneda_preferida"), "severidad" => "media", "impacto" => "Filtra condiciones por moneda preferida."),
          "idx_proveedor_condicion_vigencia" => array("columnas" => array("vigencia_desde", "vigencia_hasta"), "severidad" => "media", "impacto" => "Soporta vigencias.")
        )
      ),
      "erp_proveedores_documentos" => array(
        "descripcion" => "Evidencias y documentos asociados al proveedor.",
        "columnas" => array(
          "id_documento_proveedor" => array("severidad" => "critica", "impacto" => "Identificador del documento."),
          "id_proveedor" => array("severidad" => "critica", "impacto" => "Proveedor asociado."),
          "tipo_documento" => array("severidad" => "alta", "impacto" => "Constancia, contrato, lista, evidencia u otro tipo autorizado."),
          "nivel_sensibilidad" => array("severidad" => "alta", "impacto" => "Clasifica evidencia publica interna, confidencial o financiera sensible."),
          "entidad_origen" => array("severidad" => "media", "impacto" => "Modulo/entidad que origina la evidencia, si aplica."),
          "id_referencia" => array("severidad" => "media", "impacto" => "Identificador relacionado: fiscal, condicion, lista, costo u otro origen."),
          "referencia_tipo" => array("severidad" => "media", "impacto" => "Tipo de referencia relacionada."),
          "referencia" => array("severidad" => "media", "impacto" => "Folio o descripcion corta."),
          "archivo_nombre" => array("severidad" => "alta", "impacto" => "Nombre de archivo original."),
          "archivo_ruta" => array("severidad" => "alta", "impacto" => "Ruta interna si se almacena archivo."),
          "archivo_tipo" => array("severidad" => "media", "impacto" => "Tipo MIME o extension."),
          "archivo_tamano" => array("severidad" => "media", "impacto" => "Tamaño de evidencia."),
          "archivo_hash" => array("severidad" => "alta", "impacto" => "Control de duplicados/evidencia."),
          "metadatos_json" => array("severidad" => "media", "impacto" => "Metadatos estructurados de la evidencia sin agregar columnas por cada caso."),
          "vigencia_desde" => array("severidad" => "media", "impacto" => "Inicio de vigencia si aplica."),
          "vigencia_hasta" => array("severidad" => "media", "impacto" => "Fin de vigencia si aplica."),
          "validado_por" => array("severidad" => "media", "impacto" => "Usuario que valida la evidencia, si aplica."),
          "fecha_validacion" => array("severidad" => "media", "impacto" => "Fecha de validacion de evidencia."),
          "estatus" => array("severidad" => "alta", "impacto" => "Activo, cancelado, vencido o historico."),
          "creado_por" => array("severidad" => "media", "impacto" => "Usuario que cargo la evidencia."),
          "cancelado_por" => array("severidad" => "media", "impacto" => "Usuario que cancelo la evidencia."),
          "fecha_cancelacion" => array("severidad" => "media", "impacto" => "Fecha de cancelacion logica."),
          "motivo_cancelacion" => array("severidad" => "media", "impacto" => "Motivo de cancelacion logica."),
          "fecha_registro" => array("severidad" => "media", "impacto" => "Fecha de carga."),
          "fecha_actualizacion" => array("severidad" => "media", "impacto" => "Fecha de ultimo cambio.")
        ),
        "indices" => array(
          "PRIMARY" => array("columnas" => array("id_documento_proveedor"), "severidad" => "critica", "impacto" => "Identificador de documento."),
          "idx_proveedor_documento_proveedor" => array("columnas" => array("id_proveedor", "estatus"), "severidad" => "alta", "impacto" => "Lista documentos activos/historicos por proveedor."),
          "idx_proveedor_documento_tipo" => array("columnas" => array("id_proveedor", "tipo_documento", "estatus"), "severidad" => "media", "impacto" => "Consulta evidencias por tipo."),
          "idx_proveedor_documento_hash" => array("columnas" => array("id_proveedor", "archivo_hash"), "severidad" => "media", "impacto" => "Evita evidencias duplicadas por proveedor."),
          "idx_proveedor_documento_referencia" => array("columnas" => array("referencia_tipo", "id_referencia"), "severidad" => "media", "impacto" => "Vincula evidencia con fiscal, condicion, lista, costo u otra entidad.")
        )
      ),
      "erp_proveedores_listas_erp" => array(
        "descripcion" => "Encabezado propuesto para listas versionadas de proveedor.",
        "columnas" => array(
          "id_lista_proveedor_erp" => array("severidad" => "critica", "impacto" => "Identificador de lista ERP versionada."),
          "id_proveedor" => array("severidad" => "critica", "impacto" => "Proveedor dueño de la lista."),
          "id_lista_legacy" => array("severidad" => "media", "impacto" => "Liga a lista legacy si viene de migracion/conciliacion."),
          "nombre_lista" => array("severidad" => "alta", "impacto" => "Nombre operativo de la lista."),
          "version_lista" => array("severidad" => "alta", "impacto" => "Version visible para no reemplazar evidencia."),
          "origen" => array("severidad" => "media", "impacto" => "Excel, portal, legado, manual u otro origen autorizado."),
          "moneda" => array("severidad" => "alta", "impacto" => "Moneda del costo capturado."),
          "vigencia_desde" => array("severidad" => "alta", "impacto" => "Inicio de vigencia de lista."),
          "vigencia_hasta" => array("severidad" => "media", "impacto" => "Fin de vigencia de lista."),
          "fecha_emision" => array("severidad" => "media", "impacto" => "Fecha del documento del proveedor."),
          "id_documento_proveedor" => array("severidad" => "alta", "impacto" => "Evidencia original de la lista."),
          "estatus" => array("severidad" => "alta", "impacto" => "Cargada, validacion, conciliacion, aplicada, historica, etc."),
          "observaciones" => array("severidad" => "baja", "impacto" => "Contexto de la lista."),
          "cargado_por" => array("severidad" => "media", "impacto" => "Usuario que cargo lista."),
          "validado_por" => array("severidad" => "media", "impacto" => "Usuario que valido lista."),
          "fecha_validacion" => array("severidad" => "media", "impacto" => "Fecha de validacion."),
          "fecha_registro" => array("severidad" => "media", "impacto" => "Fecha de carga."),
          "fecha_actualizacion" => array("severidad" => "media", "impacto" => "Fecha de cambio.")
        ),
        "indices" => array(
          "PRIMARY" => array("columnas" => array("id_lista_proveedor_erp"), "severidad" => "critica", "impacto" => "Identificador de lista ERP."),
          "idx_proveedor_lista_erp_proveedor" => array("columnas" => array("id_proveedor", "estatus"), "severidad" => "alta", "impacto" => "Consulta listas por proveedor/estado."),
          "idx_proveedor_lista_erp_vigencia" => array("columnas" => array("vigencia_desde", "vigencia_hasta"), "severidad" => "media", "impacto" => "Soporta listas vigentes.")
        )
      ),
      "erp_proveedores_listas_detalle_erp" => array(
        "descripcion" => "Detalle propuesto de lista ERP con match y evidencia por renglon.",
        "columnas" => array(
          "id_lista_detalle_erp" => array("severidad" => "critica", "impacto" => "Identificador de renglon."),
          "id_lista_proveedor_erp" => array("severidad" => "critica", "impacto" => "Encabezado de lista ERP."),
          "id_producto_legacy" => array("severidad" => "media", "impacto" => "Liga a renglon legacy si existe."),
          "id_sku" => array("severidad" => "alta", "impacto" => "SKU ERP relacionado si ya fue conciliado."),
          "id_sku_proveedor" => array("severidad" => "alta", "impacto" => "Relacion proveedor-SKU aprobada si existe."),
          "sku_proveedor" => array("severidad" => "alta", "impacto" => "SKU reportado por proveedor."),
          "codigo_barras" => array("severidad" => "media", "impacto" => "Codigo reportado por proveedor."),
          "codigo_interno" => array("severidad" => "media", "impacto" => "Codigo interno reportado por proveedor."),
          "marca_proveedor" => array("severidad" => "media", "impacto" => "Marca reportada por proveedor."),
          "descripcion_proveedor" => array("severidad" => "alta", "impacto" => "Nombre/descripcion del renglon."),
          "unidad_compra_texto" => array("severidad" => "media", "impacto" => "Unidad tal como viene de proveedor."),
          "id_unidad_compra" => array("severidad" => "alta", "impacto" => "Unidad normalizada cuando ya fue validada."),
          "factor_conversion" => array("severidad" => "alta", "impacto" => "Conversion a unidad base."),
          "cantidad_minima" => array("severidad" => "media", "impacto" => "Minimo por renglon si aplica."),
          "costo" => array("severidad" => "alta", "impacto" => "Costo reportado por proveedor."),
          "moneda" => array("severidad" => "alta", "impacto" => "Moneda del costo."),
          "costo_incluye_impuestos" => array("severidad" => "alta", "impacto" => "Evita ambiguedad neto/bruto."),
          "existencia_reportada" => array("severidad" => "baja", "impacto" => "Dato informativo del proveedor."),
          "estado_match" => array("severidad" => "alta", "impacto" => "Pendiente, exacto, posible, relacionado, rechazado, sin match."),
          "criterio_match" => array("severidad" => "media", "impacto" => "Explica motivo de propuesta o decision."),
          "observaciones" => array("severidad" => "baja", "impacto" => "Contexto del renglon."),
          "fecha_registro" => array("severidad" => "media", "impacto" => "Fecha de carga."),
          "fecha_actualizacion" => array("severidad" => "media", "impacto" => "Fecha de cambio.")
        ),
        "indices" => array(
          "PRIMARY" => array("columnas" => array("id_lista_detalle_erp"), "severidad" => "critica", "impacto" => "Identificador de renglon."),
          "idx_proveedor_lista_detalle_lista" => array("columnas" => array("id_lista_proveedor_erp"), "severidad" => "critica", "impacto" => "Consulta renglones por lista."),
          "idx_proveedor_lista_detalle_sku" => array("columnas" => array("id_sku"), "severidad" => "alta", "impacto" => "Consulta renglones conciliados por SKU ERP."),
          "idx_proveedor_lista_detalle_match" => array("columnas" => array("estado_match"), "severidad" => "alta", "impacto" => "Bandeja de conciliacion por estado.")
        )
      ),
      "erp_proveedores_sku_costos" => array(
        "descripcion" => "Historial propuesto de costo proveedor-SKU con evidencia.",
        "columnas" => array(
          "id_costo_proveedor_sku" => array("severidad" => "critica", "impacto" => "Identificador de costo historico."),
          "id_proveedor" => array("severidad" => "critica", "impacto" => "Proveedor asociado."),
          "id_sku" => array("severidad" => "critica", "impacto" => "SKU ERP asociado."),
          "id_sku_proveedor" => array("severidad" => "alta", "impacto" => "Relacion proveedor-SKU usada."),
          "id_lista_proveedor_erp" => array("severidad" => "media", "impacto" => "Lista origen si aplica."),
          "id_lista_detalle_erp" => array("severidad" => "media", "impacto" => "Renglon origen si aplica."),
          "costo" => array("severidad" => "alta", "impacto" => "Costo validado."),
          "moneda" => array("severidad" => "alta", "impacto" => "Moneda del costo."),
          "tipo_cambio_referencia" => array("severidad" => "media", "impacto" => "Referencia si negocio autoriza conversion."),
          "id_unidad_compra" => array("severidad" => "alta", "impacto" => "Unidad de compra validada."),
          "factor_conversion" => array("severidad" => "alta", "impacto" => "Conversion a base."),
          "costo_incluye_impuestos" => array("severidad" => "alta", "impacto" => "Indica si costo esta neto o bruto."),
          "vigencia_desde" => array("severidad" => "alta", "impacto" => "Inicio de vigencia del costo."),
          "vigencia_hasta" => array("severidad" => "media", "impacto" => "Fin de vigencia si aplica."),
          "origen" => array("severidad" => "alta", "impacto" => "Lista, orden, recepcion, XML, manual u origen autorizado."),
          "id_documento_proveedor" => array("severidad" => "media", "impacto" => "Evidencia documental si existe."),
          "estatus" => array("severidad" => "alta", "impacto" => "Propuesto, vigente, historico, descartado."),
          "autorizado_por" => array("severidad" => "alta", "impacto" => "Usuario que autoriza costo vigente."),
          "fecha_autorizacion" => array("severidad" => "alta", "impacto" => "Fecha de autorizacion."),
          "fecha_registro" => array("severidad" => "media", "impacto" => "Fecha de captura."),
          "fecha_actualizacion" => array("severidad" => "media", "impacto" => "Fecha de cambio.")
        ),
        "indices" => array(
          "PRIMARY" => array("columnas" => array("id_costo_proveedor_sku"), "severidad" => "critica", "impacto" => "Identificador de costo."),
          "idx_proveedor_sku_costo_relacion" => array("columnas" => array("id_proveedor", "id_sku", "estatus"), "severidad" => "critica", "impacto" => "Consulta costo vigente/historico por proveedor-SKU."),
          "idx_proveedor_sku_costo_vigencia" => array("columnas" => array("vigencia_desde", "vigencia_hasta"), "severidad" => "alta", "impacto" => "Consulta costos por vigencia."),
          "idx_proveedor_sku_costo_lista" => array("columnas" => array("id_lista_proveedor_erp", "id_lista_detalle_erp"), "severidad" => "media", "impacto" => "Traza costo hacia lista/renglon origen.")
        )
      ),
      "erp_proveedores_migracion_staging" => array(
        "descripcion" => "Staging planeado para reutilizar informacion productiva legacy sin convertirla automaticamente en datos oficiales.",
        "columnas" => array(
          "id_staging" => array("severidad" => "critica", "impacto" => "Identificador del registro staging."),
          "lote" => array("severidad" => "alta", "impacto" => "Agrupa una corrida de importacion/preview."),
          "fuente" => array("severidad" => "alta", "impacto" => "Archivo o fuente origen, por ejemplo db/productivo."),
          "tipo_registro" => array("severidad" => "alta", "impacto" => "Proveedor, lista o renglon."),
          "id_origen" => array("severidad" => "alta", "impacto" => "ID legacy del registro origen."),
          "id_padre_origen" => array("severidad" => "media", "impacto" => "ID legacy padre, por ejemplo proveedor de lista o lista de renglon."),
          "referencia" => array("severidad" => "media", "impacto" => "SKU, nombre o referencia visible para revision."),
          "payload_json" => array("severidad" => "alta", "impacto" => "Datos originales normalizados sin perder evidencia."),
          "hash_origen" => array("severidad" => "alta", "impacto" => "Evita duplicar registros del mismo origen dentro del staging."),
          "accion_propuesta" => array("severidad" => "media", "impacto" => "Crear, revisar, existente, descartar u otra accion de preview."),
          "estado_revision" => array("severidad" => "media", "impacto" => "Pendiente, revisado, aprobado, descartado."),
          "motivo_revision" => array("severidad" => "baja", "impacto" => "Explica por que requiere revision."),
          "id_destino" => array("severidad" => "media", "impacto" => "Registro ERP creado o vinculado si se ejecuta importacion posterior."),
          "destino_tipo" => array("severidad" => "media", "impacto" => "Tabla o entidad destino propuesta."),
          "creado_por" => array("severidad" => "media", "impacto" => "Usuario que genero el staging."),
          "fecha_registro" => array("severidad" => "media", "impacto" => "Fecha de creacion del staging."),
          "fecha_actualizacion" => array("severidad" => "media", "impacto" => "Fecha de cambio.")
        ),
        "indices" => array(
          "PRIMARY" => array("columnas" => array("id_staging"), "severidad" => "critica", "impacto" => "Identificador del staging."),
          "idx_proveedor_staging_lote" => array("columnas" => array("lote", "tipo_registro"), "severidad" => "alta", "impacto" => "Permite revisar una corrida por tipo de registro."),
          "idx_proveedor_staging_origen" => array("columnas" => array("tipo_registro", "id_origen"), "severidad" => "alta", "impacto" => "Ubica registros por origen legacy."),
          "idx_proveedor_staging_hash" => array("columnas" => array("hash_origen"), "severidad" => "alta", "impacto" => "Evita duplicados de origen."),
          "idx_proveedor_staging_estado" => array("columnas" => array("estado_revision", "accion_propuesta"), "severidad" => "media", "impacto" => "Permite bandeja de revision antes de importar.")
        )
      )
    );
  }

  private function definicionesProveedoresErp() {
    return array(
      "erp_proveedores" => array(
        "crear" => array(
          "`id_proveedor` INT(11) NOT NULL AUTO_INCREMENT",
          "`proveedor` VARCHAR(255) NOT NULL",
          "`cuota` DECIMAL(12,2) NULL DEFAULT NULL",
          "`estatus_erp` VARCHAR(40) NULL DEFAULT NULL",
          "`origen_erp` VARCHAR(40) NULL DEFAULT NULL",
          "`fecha_actualizacion` DATETIME NULL DEFAULT NULL",
          "PRIMARY KEY (`id_proveedor`)",
          "KEY `idx_proveedor_estatus_erp` (`estatus_erp`)"
        ),
        "columnas" => array(
          "estatus_erp" => "VARCHAR(40) NULL DEFAULT NULL",
          "origen_erp" => "VARCHAR(40) NULL DEFAULT NULL",
          "fecha_actualizacion" => "DATETIME NULL DEFAULT NULL"
        ),
        "indices" => array(
          "idx_proveedor_estatus_erp" => "KEY `idx_proveedor_estatus_erp` (`estatus_erp`)"
        )
      ),
      "erp_proveedores_perfil" => array(
        "crear" => array(
          "`id_proveedor` INT(11) NOT NULL",
          "`nombre_comercial` VARCHAR(255) NULL DEFAULT NULL",
          "`nombre_corto` VARCHAR(150) NULL DEFAULT NULL",
          "`codigo_proveedor_erp` VARCHAR(80) NULL DEFAULT NULL",
          "`tipo_proveedor` VARCHAR(80) NULL DEFAULT NULL",
          "`clasificacion_operativa` VARCHAR(80) NULL DEFAULT NULL",
          "`origen` VARCHAR(40) NULL DEFAULT NULL",
          "`responsable_interno_id` INT(11) NULL DEFAULT NULL",
          "`notas` TEXT NULL",
          "`creado_por` INT(11) NULL DEFAULT NULL",
          "`revisado_por` INT(11) NULL DEFAULT NULL",
          "`autorizado_por` INT(11) NULL DEFAULT NULL",
          "`fecha_revision` DATETIME NULL DEFAULT NULL",
          "`fecha_autorizacion` DATETIME NULL DEFAULT NULL",
          "`fecha_registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "`fecha_actualizacion` DATETIME NULL DEFAULT NULL",
          "PRIMARY KEY (`id_proveedor`)",
          "KEY `idx_proveedor_perfil_codigo` (`codigo_proveedor_erp`)",
          "KEY `idx_proveedor_perfil_tipo` (`tipo_proveedor`)",
          "KEY `idx_proveedor_perfil_responsable` (`responsable_interno_id`)"
        ),
        "columnas" => array(
          "nombre_comercial" => "VARCHAR(255) NULL DEFAULT NULL",
          "nombre_corto" => "VARCHAR(150) NULL DEFAULT NULL",
          "codigo_proveedor_erp" => "VARCHAR(80) NULL DEFAULT NULL",
          "tipo_proveedor" => "VARCHAR(80) NULL DEFAULT NULL",
          "clasificacion_operativa" => "VARCHAR(80) NULL DEFAULT NULL",
          "origen" => "VARCHAR(40) NULL DEFAULT NULL",
          "responsable_interno_id" => "INT(11) NULL DEFAULT NULL",
          "notas" => "TEXT NULL",
          "creado_por" => "INT(11) NULL DEFAULT NULL",
          "revisado_por" => "INT(11) NULL DEFAULT NULL",
          "autorizado_por" => "INT(11) NULL DEFAULT NULL",
          "fecha_revision" => "DATETIME NULL DEFAULT NULL",
          "fecha_autorizacion" => "DATETIME NULL DEFAULT NULL",
          "fecha_registro" => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "fecha_actualizacion" => "DATETIME NULL DEFAULT NULL"
        ),
        "indices" => array(
          "idx_proveedor_perfil_codigo" => "KEY `idx_proveedor_perfil_codigo` (`codigo_proveedor_erp`)",
          "idx_proveedor_perfil_tipo" => "KEY `idx_proveedor_perfil_tipo` (`tipo_proveedor`)",
          "idx_proveedor_perfil_responsable" => "KEY `idx_proveedor_perfil_responsable` (`responsable_interno_id`)"
        )
      ),
      "erp_proveedores_fiscales" => array(
        "crear" => array(
          "`id_proveedor_fiscal` BIGINT(20) NOT NULL AUTO_INCREMENT",
          "`id_proveedor` INT(11) NOT NULL",
          "`rfc` VARCHAR(20) NULL DEFAULT NULL",
          "`razon_social` VARCHAR(255) NULL DEFAULT NULL",
          "`regimen_fiscal` VARCHAR(120) NULL DEFAULT NULL",
          "`codigo_postal_fiscal` VARCHAR(10) NULL DEFAULT NULL",
          "`pais` VARCHAR(80) NULL DEFAULT NULL",
          "`estado` VARCHAR(120) NULL DEFAULT NULL",
          "`municipio` VARCHAR(120) NULL DEFAULT NULL",
          "`colonia` VARCHAR(160) NULL DEFAULT NULL",
          "`calle` VARCHAR(160) NULL DEFAULT NULL",
          "`numero_exterior` VARCHAR(40) NULL DEFAULT NULL",
          "`numero_interior` VARCHAR(40) NULL DEFAULT NULL",
          "`domicilio_fiscal` TEXT NULL",
          "`uso_cfdi_preferido` VARCHAR(20) NULL DEFAULT NULL",
          "`id_documento_constancia` BIGINT(20) NULL DEFAULT NULL",
          "`fecha_constancia` DATE NULL DEFAULT NULL",
          "`validado_por` INT(11) NULL DEFAULT NULL",
          "`fecha_validacion` DATETIME NULL DEFAULT NULL",
          "`vigencia_desde` DATE NULL DEFAULT NULL",
          "`vigencia_hasta` DATE NULL DEFAULT NULL",
          "`estatus` VARCHAR(40) NULL DEFAULT NULL",
          "`fecha_registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "`fecha_actualizacion` DATETIME NULL DEFAULT NULL",
          "PRIMARY KEY (`id_proveedor_fiscal`)",
          "KEY `idx_proveedor_fiscal_proveedor` (`id_proveedor`, `estatus`)",
          "KEY `idx_proveedor_fiscal_rfc` (`rfc`)",
          "KEY `idx_proveedor_fiscal_cp` (`codigo_postal_fiscal`)",
          "KEY `idx_proveedor_fiscal_vigencia` (`vigencia_desde`, `vigencia_hasta`)"
        ),
        "columnas" => array(
          "id_proveedor" => "INT(11) NOT NULL",
          "rfc" => "VARCHAR(20) NULL DEFAULT NULL",
          "razon_social" => "VARCHAR(255) NULL DEFAULT NULL",
          "regimen_fiscal" => "VARCHAR(120) NULL DEFAULT NULL",
          "codigo_postal_fiscal" => "VARCHAR(10) NULL DEFAULT NULL",
          "pais" => "VARCHAR(80) NULL DEFAULT NULL",
          "estado" => "VARCHAR(120) NULL DEFAULT NULL",
          "municipio" => "VARCHAR(120) NULL DEFAULT NULL",
          "colonia" => "VARCHAR(160) NULL DEFAULT NULL",
          "calle" => "VARCHAR(160) NULL DEFAULT NULL",
          "numero_exterior" => "VARCHAR(40) NULL DEFAULT NULL",
          "numero_interior" => "VARCHAR(40) NULL DEFAULT NULL",
          "domicilio_fiscal" => "TEXT NULL",
          "uso_cfdi_preferido" => "VARCHAR(20) NULL DEFAULT NULL",
          "id_documento_constancia" => "BIGINT(20) NULL DEFAULT NULL",
          "fecha_constancia" => "DATE NULL DEFAULT NULL",
          "validado_por" => "INT(11) NULL DEFAULT NULL",
          "fecha_validacion" => "DATETIME NULL DEFAULT NULL",
          "vigencia_desde" => "DATE NULL DEFAULT NULL",
          "vigencia_hasta" => "DATE NULL DEFAULT NULL",
          "estatus" => "VARCHAR(40) NULL DEFAULT NULL",
          "fecha_registro" => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "fecha_actualizacion" => "DATETIME NULL DEFAULT NULL"
        ),
        "indices" => array(
          "idx_proveedor_fiscal_proveedor" => "KEY `idx_proveedor_fiscal_proveedor` (`id_proveedor`, `estatus`)",
          "idx_proveedor_fiscal_rfc" => "KEY `idx_proveedor_fiscal_rfc` (`rfc`)",
          "idx_proveedor_fiscal_cp" => "KEY `idx_proveedor_fiscal_cp` (`codigo_postal_fiscal`)",
          "idx_proveedor_fiscal_vigencia" => "KEY `idx_proveedor_fiscal_vigencia` (`vigencia_desde`, `vigencia_hasta`)"
        )
      ),
      "erp_proveedores_contactos" => array(
        "crear" => array(
          "`id_contacto_proveedor` BIGINT(20) NOT NULL AUTO_INCREMENT",
          "`id_proveedor` INT(11) NOT NULL",
          "`area` VARCHAR(80) NULL DEFAULT NULL",
          "`nombre` VARCHAR(180) NULL DEFAULT NULL",
          "`puesto` VARCHAR(120) NULL DEFAULT NULL",
          "`correo` VARCHAR(180) NULL DEFAULT NULL",
          "`telefono` VARCHAR(60) NULL DEFAULT NULL",
          "`extension` VARCHAR(20) NULL DEFAULT NULL",
          "`celular` VARCHAR(60) NULL DEFAULT NULL",
          "`whatsapp` VARCHAR(60) NULL DEFAULT NULL",
          "`recibe_ordenes_compra` TINYINT(1) NOT NULL DEFAULT 0",
          "`recibe_notificaciones` TINYINT(1) NOT NULL DEFAULT 0",
          "`es_principal` TINYINT(1) NOT NULL DEFAULT 0",
          "`prioridad` INT(11) NULL DEFAULT NULL",
          "`observaciones` TEXT NULL",
          "`estatus` VARCHAR(40) NULL DEFAULT NULL",
          "`creado_por` INT(11) NULL DEFAULT NULL",
          "`fecha_registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "`fecha_actualizacion` DATETIME NULL DEFAULT NULL",
          "PRIMARY KEY (`id_contacto_proveedor`)",
          "KEY `idx_proveedor_contacto_proveedor` (`id_proveedor`, `estatus`)",
          "KEY `idx_proveedor_contacto_area` (`id_proveedor`, `area`, `estatus`)",
          "KEY `idx_proveedor_contacto_correo` (`correo`)"
        ),
        "columnas" => array(
          "id_proveedor" => "INT(11) NOT NULL",
          "area" => "VARCHAR(80) NULL DEFAULT NULL",
          "nombre" => "VARCHAR(180) NULL DEFAULT NULL",
          "puesto" => "VARCHAR(120) NULL DEFAULT NULL",
          "correo" => "VARCHAR(180) NULL DEFAULT NULL",
          "telefono" => "VARCHAR(60) NULL DEFAULT NULL",
          "extension" => "VARCHAR(20) NULL DEFAULT NULL",
          "celular" => "VARCHAR(60) NULL DEFAULT NULL",
          "whatsapp" => "VARCHAR(60) NULL DEFAULT NULL",
          "recibe_ordenes_compra" => "TINYINT(1) NOT NULL DEFAULT 0",
          "recibe_notificaciones" => "TINYINT(1) NOT NULL DEFAULT 0",
          "es_principal" => "TINYINT(1) NOT NULL DEFAULT 0",
          "prioridad" => "INT(11) NULL DEFAULT NULL",
          "observaciones" => "TEXT NULL",
          "estatus" => "VARCHAR(40) NULL DEFAULT NULL",
          "creado_por" => "INT(11) NULL DEFAULT NULL",
          "fecha_registro" => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "fecha_actualizacion" => "DATETIME NULL DEFAULT NULL"
        ),
        "indices" => array(
          "idx_proveedor_contacto_proveedor" => "KEY `idx_proveedor_contacto_proveedor` (`id_proveedor`, `estatus`)",
          "idx_proveedor_contacto_area" => "KEY `idx_proveedor_contacto_area` (`id_proveedor`, `area`, `estatus`)",
          "idx_proveedor_contacto_correo" => "KEY `idx_proveedor_contacto_correo` (`correo`)"
        )
      ),
      "erp_proveedores_condiciones" => array(
        "crear" => array(
          "`id_condicion_proveedor` BIGINT(20) NOT NULL AUTO_INCREMENT",
          "`id_proveedor` INT(11) NOT NULL",
          "`moneda_preferida` VARCHAR(10) NULL DEFAULT NULL",
          "`requiere_orden_compra` TINYINT(1) NOT NULL DEFAULT 0",
          "`forma_pago_preferida` VARCHAR(80) NULL DEFAULT NULL",
          "`metodo_pago_preferido` VARCHAR(80) NULL DEFAULT NULL",
          "`dias_credito` INT(11) NULL DEFAULT NULL",
          "`limite_credito` DECIMAL(14,2) NULL DEFAULT NULL",
          "`minimo_compra` DECIMAL(14,2) NULL DEFAULT NULL",
          "`minimo_unidades` DECIMAL(14,4) NULL DEFAULT NULL",
          "`tiempo_entrega_dias` INT(11) NULL DEFAULT NULL",
          "`dias_surtido` VARCHAR(120) NULL DEFAULT NULL",
          "`tipo_flete` VARCHAR(80) NULL DEFAULT NULL",
          "`cobertura_entrega` TEXT NULL",
          "`condiciones_pago` TEXT NULL",
          "`condiciones_logisticas` TEXT NULL",
          "`restricciones_operativas` TEXT NULL",
          "`observaciones` TEXT NULL",
          "`vigencia_desde` DATE NULL DEFAULT NULL",
          "`vigencia_hasta` DATE NULL DEFAULT NULL",
          "`autorizado_por` INT(11) NULL DEFAULT NULL",
          "`fecha_autorizacion` DATETIME NULL DEFAULT NULL",
          "`estatus` VARCHAR(40) NULL DEFAULT NULL",
          "`fecha_registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "`fecha_actualizacion` DATETIME NULL DEFAULT NULL",
          "PRIMARY KEY (`id_condicion_proveedor`)",
          "KEY `idx_proveedor_condicion_proveedor` (`id_proveedor`, `estatus`)",
          "KEY `idx_proveedor_condicion_moneda` (`moneda_preferida`)",
          "KEY `idx_proveedor_condicion_vigencia` (`vigencia_desde`, `vigencia_hasta`)"
        ),
        "columnas" => array(
          "id_proveedor" => "INT(11) NOT NULL",
          "moneda_preferida" => "VARCHAR(10) NULL DEFAULT NULL",
          "requiere_orden_compra" => "TINYINT(1) NOT NULL DEFAULT 0",
          "forma_pago_preferida" => "VARCHAR(80) NULL DEFAULT NULL",
          "metodo_pago_preferido" => "VARCHAR(80) NULL DEFAULT NULL",
          "dias_credito" => "INT(11) NULL DEFAULT NULL",
          "limite_credito" => "DECIMAL(14,2) NULL DEFAULT NULL",
          "minimo_compra" => "DECIMAL(14,2) NULL DEFAULT NULL",
          "minimo_unidades" => "DECIMAL(14,4) NULL DEFAULT NULL",
          "tiempo_entrega_dias" => "INT(11) NULL DEFAULT NULL",
          "dias_surtido" => "VARCHAR(120) NULL DEFAULT NULL",
          "tipo_flete" => "VARCHAR(80) NULL DEFAULT NULL",
          "cobertura_entrega" => "TEXT NULL",
          "condiciones_pago" => "TEXT NULL",
          "condiciones_logisticas" => "TEXT NULL",
          "restricciones_operativas" => "TEXT NULL",
          "observaciones" => "TEXT NULL",
          "vigencia_desde" => "DATE NULL DEFAULT NULL",
          "vigencia_hasta" => "DATE NULL DEFAULT NULL",
          "autorizado_por" => "INT(11) NULL DEFAULT NULL",
          "fecha_autorizacion" => "DATETIME NULL DEFAULT NULL",
          "estatus" => "VARCHAR(40) NULL DEFAULT NULL",
          "fecha_registro" => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "fecha_actualizacion" => "DATETIME NULL DEFAULT NULL"
        ),
        "indices" => array(
          "idx_proveedor_condicion_proveedor" => "KEY `idx_proveedor_condicion_proveedor` (`id_proveedor`, `estatus`)",
          "idx_proveedor_condicion_moneda" => "KEY `idx_proveedor_condicion_moneda` (`moneda_preferida`)",
          "idx_proveedor_condicion_vigencia" => "KEY `idx_proveedor_condicion_vigencia` (`vigencia_desde`, `vigencia_hasta`)"
        )
      ),
      "erp_proveedores_documentos" => array(
        "crear" => array(
          "`id_documento_proveedor` BIGINT(20) NOT NULL AUTO_INCREMENT",
          "`id_proveedor` INT(11) NOT NULL",
          "`tipo_documento` VARCHAR(80) NULL DEFAULT NULL",
          "`nivel_sensibilidad` VARCHAR(40) NULL DEFAULT NULL",
          "`entidad_origen` VARCHAR(80) NULL DEFAULT NULL",
          "`id_referencia` BIGINT(20) NULL DEFAULT NULL",
          "`referencia_tipo` VARCHAR(80) NULL DEFAULT NULL",
          "`referencia` VARCHAR(180) NULL DEFAULT NULL",
          "`archivo_nombre` VARCHAR(255) NULL DEFAULT NULL",
          "`archivo_ruta` VARCHAR(500) NULL DEFAULT NULL",
          "`archivo_tipo` VARCHAR(120) NULL DEFAULT NULL",
          "`archivo_tamano` BIGINT(20) NULL DEFAULT NULL",
          "`archivo_hash` VARCHAR(128) NULL DEFAULT NULL",
          "`metadatos_json` TEXT NULL",
          "`vigencia_desde` DATE NULL DEFAULT NULL",
          "`vigencia_hasta` DATE NULL DEFAULT NULL",
          "`validado_por` INT(11) NULL DEFAULT NULL",
          "`fecha_validacion` DATETIME NULL DEFAULT NULL",
          "`estatus` VARCHAR(40) NULL DEFAULT NULL",
          "`creado_por` INT(11) NULL DEFAULT NULL",
          "`cancelado_por` INT(11) NULL DEFAULT NULL",
          "`fecha_cancelacion` DATETIME NULL DEFAULT NULL",
          "`motivo_cancelacion` TEXT NULL",
          "`fecha_registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "`fecha_actualizacion` DATETIME NULL DEFAULT NULL",
          "PRIMARY KEY (`id_documento_proveedor`)",
          "KEY `idx_proveedor_documento_proveedor` (`id_proveedor`, `estatus`)",
          "KEY `idx_proveedor_documento_tipo` (`id_proveedor`, `tipo_documento`, `estatus`)",
          "KEY `idx_proveedor_documento_hash` (`id_proveedor`, `archivo_hash`)",
          "KEY `idx_proveedor_documento_referencia` (`referencia_tipo`, `id_referencia`)"
        ),
        "columnas" => array(
          "id_proveedor" => "INT(11) NOT NULL",
          "tipo_documento" => "VARCHAR(80) NULL DEFAULT NULL",
          "nivel_sensibilidad" => "VARCHAR(40) NULL DEFAULT NULL",
          "entidad_origen" => "VARCHAR(80) NULL DEFAULT NULL",
          "id_referencia" => "BIGINT(20) NULL DEFAULT NULL",
          "referencia_tipo" => "VARCHAR(80) NULL DEFAULT NULL",
          "referencia" => "VARCHAR(180) NULL DEFAULT NULL",
          "archivo_nombre" => "VARCHAR(255) NULL DEFAULT NULL",
          "archivo_ruta" => "VARCHAR(500) NULL DEFAULT NULL",
          "archivo_tipo" => "VARCHAR(120) NULL DEFAULT NULL",
          "archivo_tamano" => "BIGINT(20) NULL DEFAULT NULL",
          "archivo_hash" => "VARCHAR(128) NULL DEFAULT NULL",
          "metadatos_json" => "TEXT NULL",
          "vigencia_desde" => "DATE NULL DEFAULT NULL",
          "vigencia_hasta" => "DATE NULL DEFAULT NULL",
          "validado_por" => "INT(11) NULL DEFAULT NULL",
          "fecha_validacion" => "DATETIME NULL DEFAULT NULL",
          "estatus" => "VARCHAR(40) NULL DEFAULT NULL",
          "creado_por" => "INT(11) NULL DEFAULT NULL",
          "cancelado_por" => "INT(11) NULL DEFAULT NULL",
          "fecha_cancelacion" => "DATETIME NULL DEFAULT NULL",
          "motivo_cancelacion" => "TEXT NULL",
          "fecha_registro" => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "fecha_actualizacion" => "DATETIME NULL DEFAULT NULL"
        ),
        "indices" => array(
          "idx_proveedor_documento_proveedor" => "KEY `idx_proveedor_documento_proveedor` (`id_proveedor`, `estatus`)",
          "idx_proveedor_documento_tipo" => "KEY `idx_proveedor_documento_tipo` (`id_proveedor`, `tipo_documento`, `estatus`)",
          "idx_proveedor_documento_hash" => "KEY `idx_proveedor_documento_hash` (`id_proveedor`, `archivo_hash`)",
          "idx_proveedor_documento_referencia" => "KEY `idx_proveedor_documento_referencia` (`referencia_tipo`, `id_referencia`)"
        )
      ),
      "erp_proveedores_listas_erp" => array(
        "crear" => array(
          "`id_lista_proveedor_erp` BIGINT(20) NOT NULL AUTO_INCREMENT",
          "`id_proveedor` INT(11) NOT NULL",
          "`id_lista_legacy` INT(11) NULL DEFAULT NULL",
          "`nombre_lista` VARCHAR(180) NULL DEFAULT NULL",
          "`version_lista` VARCHAR(80) NULL DEFAULT NULL",
          "`origen` VARCHAR(40) NULL DEFAULT NULL",
          "`moneda` VARCHAR(10) NULL DEFAULT NULL",
          "`vigencia_desde` DATE NULL DEFAULT NULL",
          "`vigencia_hasta` DATE NULL DEFAULT NULL",
          "`fecha_emision` DATE NULL DEFAULT NULL",
          "`id_documento_proveedor` BIGINT(20) NULL DEFAULT NULL",
          "`estatus` VARCHAR(40) NULL DEFAULT NULL",
          "`observaciones` TEXT NULL",
          "`cargado_por` INT(11) NULL DEFAULT NULL",
          "`validado_por` INT(11) NULL DEFAULT NULL",
          "`fecha_validacion` DATETIME NULL DEFAULT NULL",
          "`fecha_registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "`fecha_actualizacion` DATETIME NULL DEFAULT NULL",
          "PRIMARY KEY (`id_lista_proveedor_erp`)",
          "KEY `idx_proveedor_lista_erp_proveedor` (`id_proveedor`, `estatus`)",
          "KEY `idx_proveedor_lista_erp_vigencia` (`vigencia_desde`, `vigencia_hasta`)"
        ),
        "columnas" => array(
          "id_proveedor" => "INT(11) NOT NULL",
          "id_lista_legacy" => "INT(11) NULL DEFAULT NULL",
          "nombre_lista" => "VARCHAR(180) NULL DEFAULT NULL",
          "version_lista" => "VARCHAR(80) NULL DEFAULT NULL",
          "origen" => "VARCHAR(40) NULL DEFAULT NULL",
          "moneda" => "VARCHAR(10) NULL DEFAULT NULL",
          "vigencia_desde" => "DATE NULL DEFAULT NULL",
          "vigencia_hasta" => "DATE NULL DEFAULT NULL",
          "fecha_emision" => "DATE NULL DEFAULT NULL",
          "id_documento_proveedor" => "BIGINT(20) NULL DEFAULT NULL",
          "estatus" => "VARCHAR(40) NULL DEFAULT NULL",
          "observaciones" => "TEXT NULL",
          "cargado_por" => "INT(11) NULL DEFAULT NULL",
          "validado_por" => "INT(11) NULL DEFAULT NULL",
          "fecha_validacion" => "DATETIME NULL DEFAULT NULL",
          "fecha_registro" => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "fecha_actualizacion" => "DATETIME NULL DEFAULT NULL"
        ),
        "indices" => array(
          "idx_proveedor_lista_erp_proveedor" => "KEY `idx_proveedor_lista_erp_proveedor` (`id_proveedor`, `estatus`)",
          "idx_proveedor_lista_erp_vigencia" => "KEY `idx_proveedor_lista_erp_vigencia` (`vigencia_desde`, `vigencia_hasta`)"
        )
      ),
      "erp_proveedores_listas_detalle_erp" => array(
        "crear" => array(
          "`id_lista_detalle_erp` BIGINT(20) NOT NULL AUTO_INCREMENT",
          "`id_lista_proveedor_erp` BIGINT(20) NOT NULL",
          "`id_producto_legacy` INT(11) NULL DEFAULT NULL",
          "`id_sku` BIGINT(20) NULL DEFAULT NULL",
          "`id_sku_proveedor` BIGINT(20) NULL DEFAULT NULL",
          "`sku_proveedor` VARCHAR(120) NULL DEFAULT NULL",
          "`codigo_barras` VARCHAR(120) NULL DEFAULT NULL",
          "`codigo_interno` VARCHAR(120) NULL DEFAULT NULL",
          "`marca_proveedor` VARCHAR(160) NULL DEFAULT NULL",
          "`descripcion_proveedor` TEXT NULL",
          "`unidad_compra_texto` VARCHAR(80) NULL DEFAULT NULL",
          "`id_unidad_compra` INT(11) NULL DEFAULT NULL",
          "`factor_conversion` DECIMAL(14,6) NULL DEFAULT NULL",
          "`cantidad_minima` DECIMAL(14,4) NULL DEFAULT NULL",
          "`costo` DECIMAL(14,4) NULL DEFAULT NULL",
          "`moneda` VARCHAR(10) NULL DEFAULT NULL",
          "`costo_incluye_impuestos` TINYINT(1) NULL DEFAULT NULL",
          "`existencia_reportada` DECIMAL(14,4) NULL DEFAULT NULL",
          "`estado_match` VARCHAR(40) NULL DEFAULT NULL",
          "`criterio_match` VARCHAR(120) NULL DEFAULT NULL",
          "`observaciones` TEXT NULL",
          "`fecha_registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "`fecha_actualizacion` DATETIME NULL DEFAULT NULL",
          "PRIMARY KEY (`id_lista_detalle_erp`)",
          "KEY `idx_proveedor_lista_detalle_lista` (`id_lista_proveedor_erp`)",
          "KEY `idx_proveedor_lista_detalle_sku` (`id_sku`)",
          "KEY `idx_proveedor_lista_detalle_match` (`estado_match`)"
        ),
        "columnas" => array(
          "id_lista_proveedor_erp" => "BIGINT(20) NOT NULL",
          "id_producto_legacy" => "INT(11) NULL DEFAULT NULL",
          "id_sku" => "BIGINT(20) NULL DEFAULT NULL",
          "id_sku_proveedor" => "BIGINT(20) NULL DEFAULT NULL",
          "sku_proveedor" => "VARCHAR(120) NULL DEFAULT NULL",
          "codigo_barras" => "VARCHAR(120) NULL DEFAULT NULL",
          "codigo_interno" => "VARCHAR(120) NULL DEFAULT NULL",
          "marca_proveedor" => "VARCHAR(160) NULL DEFAULT NULL",
          "descripcion_proveedor" => "TEXT NULL",
          "unidad_compra_texto" => "VARCHAR(80) NULL DEFAULT NULL",
          "id_unidad_compra" => "INT(11) NULL DEFAULT NULL",
          "factor_conversion" => "DECIMAL(14,6) NULL DEFAULT NULL",
          "cantidad_minima" => "DECIMAL(14,4) NULL DEFAULT NULL",
          "costo" => "DECIMAL(14,4) NULL DEFAULT NULL",
          "moneda" => "VARCHAR(10) NULL DEFAULT NULL",
          "costo_incluye_impuestos" => "TINYINT(1) NULL DEFAULT NULL",
          "existencia_reportada" => "DECIMAL(14,4) NULL DEFAULT NULL",
          "estado_match" => "VARCHAR(40) NULL DEFAULT NULL",
          "criterio_match" => "VARCHAR(120) NULL DEFAULT NULL",
          "observaciones" => "TEXT NULL",
          "fecha_registro" => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "fecha_actualizacion" => "DATETIME NULL DEFAULT NULL"
        ),
        "indices" => array(
          "idx_proveedor_lista_detalle_lista" => "KEY `idx_proveedor_lista_detalle_lista` (`id_lista_proveedor_erp`)",
          "idx_proveedor_lista_detalle_sku" => "KEY `idx_proveedor_lista_detalle_sku` (`id_sku`)",
          "idx_proveedor_lista_detalle_match" => "KEY `idx_proveedor_lista_detalle_match` (`estado_match`)"
        )
      ),
      "erp_proveedores_sku_costos" => array(
        "crear" => array(
          "`id_costo_proveedor_sku` BIGINT(20) NOT NULL AUTO_INCREMENT",
          "`id_proveedor` INT(11) NOT NULL",
          "`id_sku` BIGINT(20) NOT NULL",
          "`id_sku_proveedor` BIGINT(20) NULL DEFAULT NULL",
          "`id_lista_proveedor_erp` BIGINT(20) NULL DEFAULT NULL",
          "`id_lista_detalle_erp` BIGINT(20) NULL DEFAULT NULL",
          "`costo` DECIMAL(14,4) NULL DEFAULT NULL",
          "`moneda` VARCHAR(10) NULL DEFAULT NULL",
          "`tipo_cambio_referencia` DECIMAL(14,6) NULL DEFAULT NULL",
          "`id_unidad_compra` INT(11) NULL DEFAULT NULL",
          "`factor_conversion` DECIMAL(14,6) NULL DEFAULT NULL",
          "`costo_incluye_impuestos` TINYINT(1) NULL DEFAULT NULL",
          "`vigencia_desde` DATE NULL DEFAULT NULL",
          "`vigencia_hasta` DATE NULL DEFAULT NULL",
          "`origen` VARCHAR(40) NULL DEFAULT NULL",
          "`id_documento_proveedor` BIGINT(20) NULL DEFAULT NULL",
          "`estatus` VARCHAR(40) NULL DEFAULT NULL",
          "`autorizado_por` INT(11) NULL DEFAULT NULL",
          "`fecha_autorizacion` DATETIME NULL DEFAULT NULL",
          "`fecha_registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "`fecha_actualizacion` DATETIME NULL DEFAULT NULL",
          "PRIMARY KEY (`id_costo_proveedor_sku`)",
          "KEY `idx_proveedor_sku_costo_relacion` (`id_proveedor`, `id_sku`, `estatus`)",
          "KEY `idx_proveedor_sku_costo_vigencia` (`vigencia_desde`, `vigencia_hasta`)",
          "KEY `idx_proveedor_sku_costo_lista` (`id_lista_proveedor_erp`, `id_lista_detalle_erp`)"
        ),
        "columnas" => array(
          "id_proveedor" => "INT(11) NOT NULL",
          "id_sku" => "BIGINT(20) NOT NULL",
          "id_sku_proveedor" => "BIGINT(20) NULL DEFAULT NULL",
          "id_lista_proveedor_erp" => "BIGINT(20) NULL DEFAULT NULL",
          "id_lista_detalle_erp" => "BIGINT(20) NULL DEFAULT NULL",
          "costo" => "DECIMAL(14,4) NULL DEFAULT NULL",
          "moneda" => "VARCHAR(10) NULL DEFAULT NULL",
          "tipo_cambio_referencia" => "DECIMAL(14,6) NULL DEFAULT NULL",
          "id_unidad_compra" => "INT(11) NULL DEFAULT NULL",
          "factor_conversion" => "DECIMAL(14,6) NULL DEFAULT NULL",
          "costo_incluye_impuestos" => "TINYINT(1) NULL DEFAULT NULL",
          "vigencia_desde" => "DATE NULL DEFAULT NULL",
          "vigencia_hasta" => "DATE NULL DEFAULT NULL",
          "origen" => "VARCHAR(40) NULL DEFAULT NULL",
          "id_documento_proveedor" => "BIGINT(20) NULL DEFAULT NULL",
          "estatus" => "VARCHAR(40) NULL DEFAULT NULL",
          "autorizado_por" => "INT(11) NULL DEFAULT NULL",
          "fecha_autorizacion" => "DATETIME NULL DEFAULT NULL",
          "fecha_registro" => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "fecha_actualizacion" => "DATETIME NULL DEFAULT NULL"
        ),
        "indices" => array(
          "idx_proveedor_sku_costo_relacion" => "KEY `idx_proveedor_sku_costo_relacion` (`id_proveedor`, `id_sku`, `estatus`)",
          "idx_proveedor_sku_costo_vigencia" => "KEY `idx_proveedor_sku_costo_vigencia` (`vigencia_desde`, `vigencia_hasta`)",
          "idx_proveedor_sku_costo_lista" => "KEY `idx_proveedor_sku_costo_lista` (`id_lista_proveedor_erp`, `id_lista_detalle_erp`)"
        )
      ),
      "erp_proveedores_migracion_staging" => array(
        "crear" => array(
          "`id_staging` BIGINT(20) NOT NULL AUTO_INCREMENT",
          "`lote` VARCHAR(80) NOT NULL",
          "`fuente` VARCHAR(120) NULL DEFAULT NULL",
          "`tipo_registro` VARCHAR(40) NOT NULL",
          "`id_origen` BIGINT(20) NULL DEFAULT NULL",
          "`id_padre_origen` BIGINT(20) NULL DEFAULT NULL",
          "`referencia` VARCHAR(255) NULL DEFAULT NULL",
          "`payload_json` LONGTEXT NULL",
          "`hash_origen` CHAR(64) NULL DEFAULT NULL",
          "`accion_propuesta` VARCHAR(40) NULL DEFAULT NULL",
          "`estado_revision` VARCHAR(40) NULL DEFAULT NULL",
          "`motivo_revision` TEXT NULL",
          "`id_destino` BIGINT(20) NULL DEFAULT NULL",
          "`destino_tipo` VARCHAR(80) NULL DEFAULT NULL",
          "`creado_por` INT(11) NULL DEFAULT NULL",
          "`fecha_registro` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "`fecha_actualizacion` DATETIME NULL DEFAULT NULL",
          "PRIMARY KEY (`id_staging`)",
          "KEY `idx_proveedor_staging_lote` (`lote`, `tipo_registro`)",
          "KEY `idx_proveedor_staging_origen` (`tipo_registro`, `id_origen`)",
          "KEY `idx_proveedor_staging_hash` (`hash_origen`)",
          "KEY `idx_proveedor_staging_estado` (`estado_revision`, `accion_propuesta`)"
        ),
        "columnas" => array(
          "lote" => "VARCHAR(80) NOT NULL",
          "fuente" => "VARCHAR(120) NULL DEFAULT NULL",
          "tipo_registro" => "VARCHAR(40) NOT NULL",
          "id_origen" => "BIGINT(20) NULL DEFAULT NULL",
          "id_padre_origen" => "BIGINT(20) NULL DEFAULT NULL",
          "referencia" => "VARCHAR(255) NULL DEFAULT NULL",
          "payload_json" => "LONGTEXT NULL",
          "hash_origen" => "CHAR(64) NULL DEFAULT NULL",
          "accion_propuesta" => "VARCHAR(40) NULL DEFAULT NULL",
          "estado_revision" => "VARCHAR(40) NULL DEFAULT NULL",
          "motivo_revision" => "TEXT NULL",
          "id_destino" => "BIGINT(20) NULL DEFAULT NULL",
          "destino_tipo" => "VARCHAR(80) NULL DEFAULT NULL",
          "creado_por" => "INT(11) NULL DEFAULT NULL",
          "fecha_registro" => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
          "fecha_actualizacion" => "DATETIME NULL DEFAULT NULL"
        ),
        "indices" => array(
          "idx_proveedor_staging_lote" => "KEY `idx_proveedor_staging_lote` (`lote`, `tipo_registro`)",
          "idx_proveedor_staging_origen" => "KEY `idx_proveedor_staging_origen` (`tipo_registro`, `id_origen`)",
          "idx_proveedor_staging_hash" => "KEY `idx_proveedor_staging_hash` (`hash_origen`)",
          "idx_proveedor_staging_estado" => "KEY `idx_proveedor_staging_estado` (`estado_revision`, `accion_propuesta`)"
        )
      )
    );
  }

  private function auditarTablaContratoProveedor($tabla, $existe, $reglas) {
    $resultado = array(
      "existe" => $existe,
      "descripcion" => isset($reglas["descripcion"]) ? $reglas["descripcion"] : "",
      "severidad" => $existe ? "ok" : "critica",
      "impacto" => $existe ? "Tabla disponible para el contrato planeado de Proveedores ERP." : "Tabla faltante en el contrato planeado de Proveedores ERP.",
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

    foreach ($this->valorArreglo($reglas, "columnas", array()) as $columna => $meta) {
      if (is_int($columna)) {
        $columna = $meta;
        $meta = array();
      }
      $existeColumna = $this->columnaExiste($tabla, $columna);
      $resultado["columnas"][$columna] = array(
        "existe" => $existeColumna,
        "severidad" => $this->valorArreglo($meta, "severidad", "media"),
        "impacto" => $this->valorArreglo($meta, "impacto", "Columna requerida por el contrato planeado de Proveedores ERP.")
      );
      if (!$existeColumna) {
        $resultado["faltantes"]["columnas"][$columna] = $resultado["columnas"][$columna];
        $resultado["severidad"] = $this->severidadMayorProveedor($resultado["severidad"], $resultado["columnas"][$columna]["severidad"]);
      }
    }

    foreach ($this->valorArreglo($reglas, "indices", array()) as $indice => $meta) {
      if (is_int($indice)) {
        $indice = $meta;
        $meta = array();
      }
      $existeIndice = $this->indiceExiste($tabla, $indice);
      $columnasEsperadas = $this->valorArreglo($meta, "columnas", array());
      $columnasActuales = $existeIndice ? $this->columnasIndice($tabla, $indice) : array();
      $columnasCorrectas = empty($columnasEsperadas) || $columnasActuales === $columnasEsperadas;

      $resultado["indices"][$indice] = array(
        "existe" => $existeIndice,
        "columnas_esperadas" => $columnasEsperadas,
        "columnas_actuales" => $columnasActuales,
        "columnas_correctas" => $columnasCorrectas,
        "severidad" => $this->valorArreglo($meta, "severidad", "media"),
        "impacto" => $this->valorArreglo($meta, "impacto", "Indice requerido por el contrato planeado de Proveedores ERP.")
      );

      if (!$existeIndice) {
        $resultado["faltantes"]["indices"][$indice] = $resultado["indices"][$indice];
        $resultado["severidad"] = $this->severidadMayorProveedor($resultado["severidad"], $resultado["indices"][$indice]["severidad"]);
      } elseif (!$columnasCorrectas) {
        $resultado["faltantes"]["indices_columnas"][$indice] = $resultado["indices"][$indice];
        $resultado["severidad"] = $this->severidadMayorProveedor($resultado["severidad"], $resultado["indices"][$indice]["severidad"]);
      }
    }

    return $resultado;
  }

  private function severidadMayorProveedor($actual, $nueva) {
    $orden = array("ok" => 0, "baja" => 1, "media" => 2, "alta" => 3, "critica" => 4);
    $actualValor = isset($orden[$actual]) ? $orden[$actual] : 0;
    $nuevaValor = isset($orden[$nueva]) ? $orden[$nueva] : 0;
    return $nuevaValor > $actualValor ? $nueva : $actual;
  }

  private function valorArreglo($arreglo, $clave, $default = null) {
    return is_array($arreglo) && array_key_exists($clave, $arreglo) ? $arreglo[$clave] : $default;
  }
}
