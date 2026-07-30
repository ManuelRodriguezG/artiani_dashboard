<?php

class ResumenErp extends CRUD {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: concentrar indicadores operativos de la primera pantalla ERP sin depender de vistas legacy.
   * Impacto: Resumen ERP; lee notificaciones, ventas, compras, almacen, inventario, catalogo, proveedores, CRM y TMS.
   * Contrato: read-only; tolera tablas faltantes y respeta permisos visibles del usuario.
   */
  public function consultar($idUsuario, $permisos = array()) {
    try {
      $db = $this->getConexion();
      $permisos = is_array($permisos) ? $permisos : array();
      return $this->respuesta(false, "success", "Resumen ERP consultado", array(
        "fecha" => date("Y-m-d H:i:s"),
        "notificaciones" => $this->resumenNotificaciones($db, $idUsuario, $permisos),
        "modulos" => array(
          "ventas" => $this->puede($permisos, "ventas.ver") ? $this->resumenVentas($db) : $this->moduloOculto("ventas.ver"),
          "compras" => $this->puede($permisos, "compras.ver") ? $this->resumenCompras($db) : $this->moduloOculto("compras.ver"),
          "almacen" => $this->puede($permisos, "almacen.ver") ? $this->resumenAlmacen($db) : $this->moduloOculto("almacen.ver"),
          "inventario" => $this->puede($permisos, "inventario.ver") ? $this->resumenInventario($db) : $this->moduloOculto("inventario.ver"),
          "catalogo" => $this->puede($permisos, "catalogo.ver") ? $this->resumenCatalogo($db) : $this->moduloOculto("catalogo.ver"),
          "proveedores" => $this->puede($permisos, "proveedores.ver") ? $this->resumenProveedores($db) : $this->moduloOculto("proveedores.ver"),
          "crm" => $this->puede($permisos, "crm.ver") ? $this->resumenCrm($db) : $this->moduloOculto("crm.ver"),
          "tms" => $this->puede($permisos, "tms.ver") ? $this->resumenTms($db) : $this->moduloOculto("tms.ver")
        ),
        "acciones" => $this->accionesRapidas($permisos)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: resumir alertas operativas visibles en la pantalla inicial.
   * Impacto: Notificaciones ERP; reutiliza reglas de visibilidad por usuario y permiso.
   * Contrato: no resuelve ni marca lectura; solo consulta pendientes activas.
   */
  private function resumenNotificaciones($db, $idUsuario, $permisos) {
    if (!$this->tablaExiste($db, "erp_notificaciones") || !$this->tablaExiste($db, "erp_notificaciones_lecturas")) {
      return array("visible" => false, "pendiente_schema" => true, "total" => 0, "criticas" => 0, "altas" => 0, "items" => array(), "por_modulo" => array());
    }
    if (!$this->puede($permisos, "notificaciones.ver")) {
      return $this->moduloOculto("notificaciones.ver") + array("total" => 0, "criticas" => 0, "altas" => 0, "items" => array(), "por_modulo" => array());
    }
    $filtro = $this->filtroNotificaciones($idUsuario, $permisos);
    if ($filtro["sql"] === "") {
      return array("visible" => true, "total" => 0, "criticas" => 0, "altas" => 0, "items" => array(), "por_modulo" => array());
    }
    $where = "n.estatus IN ('pendiente','en_revision','bloqueada') AND COALESCE(l.descartada,0)=0 AND " . $filtro["sql"];
    $params = array_merge(array(":usuario_lectura" => intval($idUsuario)), $filtro["params"]);
    $resumen = $this->fila($db, "SELECT COUNT(*) total,
        SUM(CASE WHEN n.prioridad='critica' THEN 1 ELSE 0 END) criticas,
        SUM(CASE WHEN n.prioridad='alta' THEN 1 ELSE 0 END) altas
      FROM erp_notificaciones n
      LEFT JOIN erp_notificaciones_lecturas l ON l.id_notificacion=n.id_notificacion AND l.id_usuario=:usuario_lectura
      WHERE {$where}", $params);
    return array(
      "visible" => true,
      "total" => intval($this->valor($resumen, "total", 0)),
      "criticas" => intval($this->valor($resumen, "criticas", 0)),
      "altas" => intval($this->valor($resumen, "altas", 0)),
      "items" => $this->filas($db, "SELECT n.id_notificacion, n.tipo, n.modulo_origen, n.area_responsable,
          n.titulo, n.descripcion, n.prioridad, n.estatus, n.url_accion, n.fecha_registro, COALESCE(l.leida,0) leida
        FROM erp_notificaciones n
        LEFT JOIN erp_notificaciones_lecturas l ON l.id_notificacion=n.id_notificacion AND l.id_usuario=:usuario_lectura
        WHERE {$where}
        ORDER BY FIELD(n.prioridad,'critica','alta','normal','info'), n.id_notificacion DESC
        LIMIT 8", $params),
      "por_modulo" => $this->filas($db, "SELECT n.modulo_origen, COUNT(*) total
        FROM erp_notificaciones n
        LEFT JOIN erp_notificaciones_lecturas l ON l.id_notificacion=n.id_notificacion AND l.id_usuario=:usuario_lectura
        WHERE {$where}
        GROUP BY n.modulo_origen
        ORDER BY total DESC, n.modulo_origen ASC
        LIMIT 8", $params)
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: entregar KPIs de ventas/POS para direccion y operacion diaria.
   * Impacto: Ventas/POS; no cobra, no mueve caja y no toca inventario.
   */
  private function resumenVentas($db) {
    if (!$this->tablaExiste($db, "erp_ventas")) {
      return $this->moduloPendiente("Esquema Ventas/POS pendiente");
    }
    $resumen = $this->fila($db, "SELECT
        SUM(CASE WHEN tipo_documento='venta' AND DATE(fecha_venta)=:hoy THEN 1 ELSE 0 END) ventas_hoy,
        SUM(CASE WHEN tipo_documento='venta' AND DATE(fecha_venta)=:hoy THEN total ELSE 0 END) total_hoy,
        SUM(CASE WHEN tipo_documento='pedido' AND estatus IN ('borrador','reservado','pendiente_pago','pagado') THEN 1 ELSE 0 END) pedidos_abiertos,
        SUM(CASE WHEN tipo_documento IN ('pedido','apartado') AND estatus IN ('reservado','pendiente_pago') THEN 1 ELSE 0 END) reservas_pendientes
      FROM erp_ventas", array(":hoy" => date("Y-m-d")));
    return array(
      "visible" => true,
      "ventas_hoy" => intval($this->valor($resumen, "ventas_hoy", 0)),
      "total_hoy" => round(floatval($this->valor($resumen, "total_hoy", 0)), 2),
      "pedidos_abiertos" => intval($this->valor($resumen, "pedidos_abiertos", 0)),
      "reservas_pendientes" => intval($this->valor($resumen, "reservas_pendientes", 0)),
      "turnos_abiertos" => $this->tablaExiste($db, "erp_pos_turnos") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_pos_turnos WHERE estatus='abierto'")) : 0,
      "venta_rapida_pendientes" => $this->tablaExiste($db, "erp_pos_venta_rapida_pendientes") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_pos_venta_rapida_pendientes WHERE estatus IN ('pendiente_catalogo','en_revision','pendiente_regularizacion')")) : 0
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: resumir solicitudes y ordenes que pueden bloquear compras, recepcion o finanzas.
   * Impacto: Compras ERP; consulta folios y saldos sin editar ordenes.
   */
  private function resumenCompras($db) {
    if (!$this->tablaExiste($db, "erp_compras_ordenes")) {
      return $this->moduloPendiente("Esquema Compras pendiente");
    }
    $solicitudes = $this->tablaExiste($db, "erp_compras_solicitudes")
      ? $this->fila($db, "SELECT SUM(estatus='pendiente') pendientes, SUM(estatus='aprobada') aprobadas FROM erp_compras_solicitudes")
      : array();
    $ordenes = $this->fila($db, "SELECT SUM(estatus='enviada') enviadas, SUM(estatus='parcial') parciales,
        SUM(estatus IN ('pendiente','enviada','parcial')) abiertas,
        COALESCE(SUM(CASE WHEN estatus<>'cancelada' THEN saldo_pendiente ELSE 0 END),0) saldo_pendiente
      FROM erp_compras_ordenes");
    return array(
      "visible" => true,
      "solicitudes_pendientes" => intval($this->valor($solicitudes, "pendientes", 0)),
      "solicitudes_aprobadas" => intval($this->valor($solicitudes, "aprobadas", 0)),
      "ordenes_abiertas" => intval($this->valor($ordenes, "abiertas", 0)),
      "ordenes_enviadas" => intval($this->valor($ordenes, "enviadas", 0)),
      "ordenes_parciales" => intval($this->valor($ordenes, "parciales", 0)),
      "saldo_pendiente" => round(floatval($this->valor($ordenes, "saldo_pendiente", 0)), 2)
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: exponer recepciones y etiquetas pendientes para seguimiento diario de almacen.
   * Impacto: Almacen; no guarda recepciones ni cambia estados de etiqueta.
   */
  private function resumenAlmacen($db) {
    if (!$this->tablaExiste($db, "erp_almacen_recepciones")) {
      return $this->moduloPendiente("Esquema Almacen pendiente");
    }
    $recepciones = $this->fila($db, "SELECT SUM(estatus='pendiente') pendientes, SUM(estatus='parcial') parciales, SUM(estatus='recibida') recibidas FROM erp_almacen_recepciones");
    return array(
      "visible" => true,
      "recepciones_pendientes" => intval($this->valor($recepciones, "pendientes", 0)),
      "recepciones_parciales" => intval($this->valor($recepciones, "parciales", 0)),
      "recepciones_recibidas" => intval($this->valor($recepciones, "recibidas", 0)),
      "etiquetas_pendientes" => $this->tablaExiste($db, "erp_inventario_unidades") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_inventario_unidades WHERE estatus<>'cancelada' AND estado_etiqueta IN ('pendiente_impresion','reimpresa')")) : 0,
      "incidencias_pendientes" => $this->tablaExiste($db, "erp_almacen_recepciones_incidencias") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_almacen_recepciones_incidencias WHERE estatus IN ('pendiente','en_revision','bloqueada')")) : 0
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: resumir salud de existencias, reservas y pendientes POS de inventario.
   * Impacto: Inventario ERP; lectura agregada sin crear kardex ni ajustes.
   */
  private function resumenInventario($db) {
    if (!$this->tablaExiste($db, "erp_inventario_existencias")) {
      return $this->moduloPendiente("Esquema Inventario pendiente");
    }
    $base = $this->fila($db, "SELECT COUNT(DISTINCT id_sku_erp) skus_con_existencia,
        COUNT(*) saldos, COALESCE(SUM(cantidad_disponible),0) disponible_total,
        SUM(CASE WHEN cantidad_disponible<=0 THEN 1 ELSE 0 END) saldos_sin_disponible
      FROM erp_inventario_existencias WHERE COALESCE(estatus_existencia,'disponible')<>'cancelada'");
    $stockBajo = 0;
    if ($this->tablaExiste($db, "erp_catalogo_sku_reglas_inventario")) {
      $stockBajo = intval($this->escalar($db, "SELECT COUNT(*) FROM (
        SELECT e.id_sku_erp
        FROM erp_inventario_existencias e
        INNER JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=e.id_sku_erp
        WHERE r.controla_inventario=1 AND r.punto_reorden>0
        GROUP BY e.id_sku_erp
        HAVING SUM(e.cantidad_disponible)<=MAX(r.punto_reorden)
      ) bajo"));
    }
    return array(
      "visible" => true,
      "skus_con_existencia" => intval($this->valor($base, "skus_con_existencia", 0)),
      "saldos" => intval($this->valor($base, "saldos", 0)),
      "disponible_total" => round(floatval($this->valor($base, "disponible_total", 0)), 2),
      "saldos_sin_disponible" => intval($this->valor($base, "saldos_sin_disponible", 0)),
      "stock_bajo" => $stockBajo,
      "reservas_pendientes" => $this->tablaExiste($db, "erp_inventario_reservas") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_inventario_reservas WHERE (cantidad_reservada-cantidad_consumida-cantidad_liberada)>0.0001")) : 0,
      "pos_pendientes" => $this->tablaExiste($db, "erp_pos_inventario_pendientes") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_pos_inventario_pendientes WHERE estatus IN ('pendiente_revision','en_revision','pendiente_regularizacion')")) : 0
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: resumir calidad del catalogo que afecta ventas, compras e inventario.
   * Impacto: Catalogo ERP; lectura agregada de faltantes operativos.
   */
  private function resumenCatalogo($db) {
    if (!$this->tablaExiste($db, "erp_catalogo_productos")) {
      return $this->moduloPendiente("Esquema Catalogo pendiente");
    }
    return array(
      "visible" => true,
      "productos_sin_sku" => $this->tablaExiste($db, "erp_catalogo_skus") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_skus s WHERE s.id_producto_erp=p.id_producto_erp AND s.estatus<>'fusionado')")) : 0,
      "productos_sin_marca" => intval($this->escalar($db, "SELECT COUNT(*) FROM erp_catalogo_productos WHERE estatus<>'fusionado' AND id_marca_erp IS NULL")),
      "productos_sin_categoria" => $this->tablaExiste($db, "erp_catalogo_producto_categorias") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_producto_categorias pc WHERE pc.id_producto_erp=p.id_producto_erp)")) : 0,
      "skus_sin_precio" => ($this->tablaExiste($db, "erp_catalogo_skus") && $this->tablaExiste($db, "erp_catalogo_sku_precios")) ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_catalogo_skus s WHERE s.estatus<>'fusionado' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_precios pr WHERE pr.id_sku=s.id_sku AND pr.estatus='activo')")) : 0,
      "incidencias_abiertas" => $this->tablaExiste($db, "erp_catalogo_incidencias_calidad") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_catalogo_incidencias_calidad WHERE estatus IN ('pendiente','en_revision','bloqueada')")) : 0
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: resumir preparacion de proveedores, listas, costos e incidencias.
   * Impacto: Proveedores ERP; lectura agregada para priorizar matching y costos.
   */
  private function resumenProveedores($db) {
    if (!$this->tablaExiste($db, "erp_proveedores")) {
      return $this->moduloPendiente("Esquema Proveedores pendiente");
    }
    $totalProveedores = intval($this->escalar($db, "SELECT COUNT(*) FROM erp_proveedores"));
    return array(
      "visible" => true,
      "proveedores" => $totalProveedores,
      "proveedores_activos" => $this->columnaExiste($db, "erp_proveedores", "estatus") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_proveedores WHERE COALESCE(estatus,'activo')='activo'")) : $totalProveedores,
      "listas" => $this->tablaExiste($db, "erp_proveedores_listas_erp") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_proveedores_listas_erp")) : 0,
      "costos_vigentes" => $this->tablaExiste($db, "erp_proveedores_sku_costos") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_proveedores_sku_costos WHERE estatus='vigente'")) : 0,
      "incidencias_pendientes" => $this->tablaExiste($db, "erp_catalogo_incidencias_calidad") ? intval($this->escalar($db, "SELECT COUNT(*) FROM erp_catalogo_incidencias_calidad WHERE origen='proveedores' AND estatus IN ('pendiente','en_revision','bloqueada')")) : 0
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: exponer salud comercial de clientes sin reemplazar reportes CRM especializados.
   * Impacto: CRM Clientes; lectura agregada de clientes, tareas e interacciones.
   */
  private function resumenCrm($db) {
    if (!$this->tablaExiste($db, "crm_clientes_maestro")) {
      return $this->moduloPendiente("Esquema CRM Clientes pendiente");
    }
    $clientes = $this->fila($db, "SELECT COUNT(*) clientes_total,
        SUM(CASE WHEN COALESCE(estatus,'activo')='activo' THEN 1 ELSE 0 END) clientes_activos,
        SUM(CASE WHEN COALESCE(calidad_datos,'') NOT IN ('completa','validada') THEN 1 ELSE 0 END) calidad_revisar
      FROM crm_clientes_maestro");
    return array(
      "visible" => true,
      "clientes_total" => intval($this->valor($clientes, "clientes_total", 0)),
      "clientes_activos" => intval($this->valor($clientes, "clientes_activos", 0)),
      "calidad_revisar" => intval($this->valor($clientes, "calidad_revisar", 0)),
      "tareas_pendientes" => $this->tablaExiste($db, "crm_clientes_tareas") ? intval($this->escalar($db, "SELECT COUNT(*) FROM crm_clientes_tareas WHERE estatus IN ('pendiente','programada','en_revision')")) : 0,
      "tareas_vencidas" => $this->tablaExiste($db, "crm_clientes_tareas") ? intval($this->escalar($db, "SELECT COUNT(*) FROM crm_clientes_tareas WHERE estatus IN ('pendiente','programada','en_revision') AND fecha_vencimiento IS NOT NULL AND fecha_vencimiento<:hoy", array(":hoy" => date("Y-m-d")))) : 0,
      "interacciones_hoy" => $this->tablaExiste($db, "crm_clientes_interacciones") ? intval($this->escalar($db, "SELECT COUNT(*) FROM crm_clientes_interacciones WHERE DATE(fecha_interaccion)=:hoy", array(":hoy" => date("Y-m-d")))) : 0
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: resumir estado operativo de entregas TMS en la pantalla inicial.
   * Impacto: TMS Delivery; lectura agregada de servicios, rutas y cobro logistico.
   */
  private function resumenTms($db) {
    if (!$this->tablaExiste($db, "erp_tms_servicios")) {
      return $this->moduloPendiente("Esquema TMS Delivery pendiente");
    }
    $servicios = $this->fila($db, "SELECT COUNT(*) total,
        SUM(CASE WHEN estatus_servicio IN ('solicitada','programada','preparando','lista_para_salida','en_ruta','pendiente_cliente','reprogramada') THEN 1 ELSE 0 END) abiertos,
        SUM(CASE WHEN estatus_servicio='en_ruta' THEN 1 ELSE 0 END) en_ruta,
        SUM(CASE WHEN resultado_logistico='nuevo_intento_requerido' OR estatus_servicio='reprogramada' THEN 1 ELSE 0 END) reintentos,
        SUM(CASE WHEN estatus_cobro IN ('pendiente','por_cobrar') THEN 1 ELSE 0 END) cobro_pendiente,
        SUM(CASE WHEN prioridad IN ('alta','critica') THEN 1 ELSE 0 END) prioridad_alta
      FROM erp_tms_servicios
      WHERE COALESCE(estatus,'activo')<>'cancelado'");
    return array(
      "visible" => true,
      "total" => intval($this->valor($servicios, "total", 0)),
      "abiertos" => intval($this->valor($servicios, "abiertos", 0)),
      "en_ruta" => intval($this->valor($servicios, "en_ruta", 0)),
      "reintentos" => intval($this->valor($servicios, "reintentos", 0)),
      "cobro_pendiente" => intval($this->valor($servicios, "cobro_pendiente", 0)),
      "prioridad_alta" => intval($this->valor($servicios, "prioridad_alta", 0))
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: construir accesos rapidos visibles sin duplicar reglas del sidebar.
   * Impacto: Resumen ERP; solo devuelve rutas ya existentes.
   */
  private function accionesRapidas($permisos) {
    $acciones = array(
      array("titulo" => "Abrir POS", "url" => "/ventas/pos", "icono" => "bi-shop-window", "permiso" => "ventas.operar"),
      array("titulo" => "Nueva orden", "url" => "/compra/crear_orden_compra", "icono" => "bi-cart-plus", "permiso" => "compras.crear"),
      array("titulo" => "Recepciones", "url" => "/almacen/mostrar_recepciones", "icono" => "bi-box-arrow-in-down", "permiso" => "almacen.ver"),
      array("titulo" => "Existencias", "url" => "/inventario/productos_existencias", "icono" => "bi-clipboard-data", "permiso" => "inventario.ver"),
      array("titulo" => "Catalogo", "url" => "/catalogoerp", "icono" => "bi-box-seam", "permiso" => "catalogo.ver"),
      array("titulo" => "Clientes", "url" => "/crm/clientes", "icono" => "bi-person-vcard", "permiso" => "crm.ver"),
      array("titulo" => "Entregas", "url" => "/tms/servicios", "icono" => "bi-truck", "permiso" => "tms.ver"),
      array("titulo" => "Notificaciones", "url" => "/sistema/notificaciones", "icono" => "bi-bell", "permiso" => "notificaciones.ver")
    );
    return array_values(array_filter($acciones, function ($accion) use ($permisos) {
      return $this->puede($permisos, $accion["permiso"]);
    }));
  }

  private function filtroNotificaciones($idUsuario, $permisos) {
    $partes = array();
    $params = array();
    if (intval($idUsuario) > 0) {
      $partes[] = "n.asignado_a=:usuario_asignado";
      $params[":usuario_asignado"] = intval($idUsuario);
    }
    if (!empty($permisos)) {
      $marcadores = array();
      foreach ($permisos as $i => $permiso) {
        $clave = ":permiso_" . $i;
        $marcadores[] = $clave;
        $params[$clave] = $permiso;
      }
      $partes[] = "n.permiso_requerido IN (" . implode(",", $marcadores) . ")";
    }
    return array("sql" => empty($partes) ? "" : "(" . implode(" OR ", $partes) . ")", "params" => $params);
  }

  private function tablaExiste($db, $tabla) {
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
      return false;
    }
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function columnaExiste($db, $tabla, $columna) {
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
      return false;
    }
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function escalar($db, $sql, $params = array()) {
    try {
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      return $stmt->fetchColumn();
    } catch (Exception $e) {
      return 0;
    }
  }

  private function fila($db, $sql, $params = array()) {
    try {
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      return $fila ? $fila : array();
    } catch (Exception $e) {
      return array();
    }
  }

  private function filas($db, $sql, $params = array()) {
    try {
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return array();
    }
  }

  private function puede($permisos, $permiso) {
    return in_array($permiso, $permisos, true);
  }

  private function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
  }

  private function moduloOculto($permiso) {
    return array("visible" => false, "permiso" => $permiso);
  }

  private function moduloPendiente($mensaje) {
    return array("visible" => true, "pendiente_schema" => true, "mensaje" => $mensaje);
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
