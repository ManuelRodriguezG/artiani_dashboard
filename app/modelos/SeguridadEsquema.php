<?php

class SeguridadEsquema extends DBSchema {

  public function tablasSeguridad() {
    return array(
      "sys_roles",
      "sys_permisos",
      "sys_roles_permisos",
      "sys_usuarios_roles",
      "sys_auditoria_eventos"
    );
  }

  public function planActualizarSeguridad($ejecutar = false) {
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("sys_roles", array(
      "`id_rol` INT NOT NULL AUTO_INCREMENT",
      "`rol` VARCHAR(80) NOT NULL",
      "`descripcion` TEXT NULL",
      "`estatus` TINYINT(1) NOT NULL DEFAULT 1",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_rol`)",
      "UNIQUE KEY `idx_sys_roles_rol` (`rol`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_permisos", array(
      "`id_permiso` INT NOT NULL AUTO_INCREMENT",
      "`modulo` VARCHAR(80) NOT NULL",
      "`accion` VARCHAR(80) NOT NULL",
      "`permiso` VARCHAR(180) NOT NULL",
      "`descripcion` TEXT NULL",
      "`estatus` TINYINT(1) NOT NULL DEFAULT 1",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_permiso`)",
      "UNIQUE KEY `idx_sys_permisos_permiso` (`permiso`)",
      "KEY `idx_sys_permisos_modulo_accion` (`modulo`, `accion`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_roles_permisos", array(
      "`id_rol_permiso` INT NOT NULL AUTO_INCREMENT",
      "`id_rol` INT NOT NULL",
      "`id_permiso` INT NOT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_rol_permiso`)",
      "UNIQUE KEY `idx_sys_roles_permisos_rol_permiso` (`id_rol`, `id_permiso`)",
      "KEY `idx_sys_roles_permisos_permiso` (`id_permiso`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_usuarios_roles", array(
      "`id_usuario_rol` INT NOT NULL AUTO_INCREMENT",
      "`id_usuario` INT NOT NULL",
      "`id_rol` INT NOT NULL",
      "`estatus` TINYINT(1) NOT NULL DEFAULT 1",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_usuario_rol`)",
      "UNIQUE KEY `idx_sys_usuarios_roles_usuario_rol` (`id_usuario`, `id_rol`)",
      "KEY `idx_sys_usuarios_roles_rol` (`id_rol`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_auditoria_eventos", array(
      "`id_auditoria_evento` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_usuario` INT NULL",
      "`modulo` VARCHAR(80) NOT NULL",
      "`accion` VARCHAR(80) NOT NULL",
      "`entidad` VARCHAR(120) NULL",
      "`entidad_id` VARCHAR(80) NULL",
      "`resultado` VARCHAR(40) NOT NULL DEFAULT 'ok'",
      "`ip` VARCHAR(80) NULL",
      "`user_agent` TEXT NULL",
      "`datos_antes` JSON NULL",
      "`datos_despues` JSON NULL",
      "`mensaje` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_auditoria_evento`)",
      "KEY `idx_sys_auditoria_usuario` (`id_usuario`)",
      "KEY `idx_sys_auditoria_modulo_accion` (`modulo`, `accion`)",
      "KEY `idx_sys_auditoria_entidad` (`entidad`, `entidad_id`)",
      "KEY `idx_sys_auditoria_fecha` (`fecha_registro`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->agregarColumnaSiNoExiste("sys_usuarios", "nombre_mostrar", "VARCHAR(255) NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("sys_usuarios", "area_departamento", "VARCHAR(180) NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("sys_usuarios", "puesto", "VARCHAR(180) NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("sys_usuarios", "telefono_secundario", "VARCHAR(50) NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("sys_usuarios", "notas_admin", "TEXT NULL", $ejecutar);
    $plan[] = $this->agregarIndiceSiNoExiste("sys_usuarios", "idx_sys_usuarios_alias", "KEY `idx_sys_usuarios_alias` (`alias`)", $ejecutar);
    $plan[] = $this->agregarIndiceSiNoExiste("sys_usuarios", "idx_sys_usuarios_correo", "KEY `idx_sys_usuarios_correo` (`correo`)", $ejecutar);

    $plan = array_merge($plan, $this->planSemillaRolesPermisosERP($ejecutar));

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Plan de seguridad ejecutado" : "Plan de seguridad generado en dry-run",
      "depurar" => $plan
    );
  }

  public function rolesBaseERP() {
    return array(
      array("rol" => "direccion", "descripcion" => "Consulta ejecutiva, aprobaciones y reportes del ERP"),
      array("rol" => "administrador_erp", "descripcion" => "Administra configuracion general, usuarios, roles y catalogos maestros"),
      array("rol" => "compras", "descripcion" => "Gestiona proveedores, solicitudes, ordenes de compra y seguimiento de compras"),
      array("rol" => "almacen", "descripcion" => "Recibe mercancia, asigna ubicaciones, registra lotes e incidencias"),
      array("rol" => "inventario", "descripcion" => "Controla existencias, ajustes, traspasos, conteos y reglas de inventario"),
      array("rol" => "crm", "descripcion" => "Administra clientes, contactos, segmentos, historial comercial y relacion postventa"),
      array("rol" => "ventas", "descripcion" => "Consulta clientes, pedidos, ventas, apartados y disponibilidad comercial"),
      array("rol" => "ecommerce", "descripcion" => "Opera sincronizacion y seguimiento ecommerce cuando el canal este activo"),
      array("rol" => "catalogo_productos", "descripcion" => "Mantiene productos, SKUs, variantes, costos, precios, impuestos e imagenes"),
      array("rol" => "finanzas_contabilidad", "descripcion" => "Opera pagos, notas, saldos y conciliacion financiera"),
      array("rol" => "auditor", "descripcion" => "Consulta trazabilidad, bitacoras, movimientos y reportes sin operar transacciones"),
      array("rol" => "solo_lectura", "descripcion" => "Acceso de consulta general sin permisos de edicion"),
      array("rol" => "soporte_sistema", "descripcion" => "Diagnostico tecnico, auditoria de esquema y soporte del sistema")
    );
  }

  public function permisosBaseERP() {
    return array(
      array("modulo" => "seguridad", "accion" => "ver", "permiso" => "seguridad.ver", "descripcion" => "Consultar usuarios, roles y permisos"),
      array("modulo" => "seguridad", "accion" => "administrar", "permiso" => "seguridad.administrar", "descripcion" => "Crear y modificar usuarios, roles y permisos"),
      array("modulo" => "configuracion", "accion" => "administrar", "permiso" => "configuracion.administrar", "descripcion" => "Administrar configuracion general del ERP"),
      array("modulo" => "catalogo", "accion" => "ver", "permiso" => "catalogo.ver", "descripcion" => "Consultar catalogo de productos"),
      array("modulo" => "catalogo", "accion" => "editar", "permiso" => "catalogo.editar", "descripcion" => "Crear y modificar productos, SKUs y variantes"),
      array("modulo" => "catalogo", "accion" => "costos", "permiso" => "catalogo.costos", "descripcion" => "Consultar y modificar costos, margenes e impuestos"),
      array("modulo" => "compras", "accion" => "ver", "permiso" => "compras.ver", "descripcion" => "Consultar solicitudes y ordenes de compra"),
      array("modulo" => "compras", "accion" => "crear", "permiso" => "compras.crear", "descripcion" => "Crear solicitudes y ordenes de compra"),
      array("modulo" => "compras", "accion" => "editar", "permiso" => "compras.editar", "descripcion" => "Modificar solicitudes y ordenes de compra existentes"),
      array("modulo" => "compras", "accion" => "aprobar", "permiso" => "compras.aprobar", "descripcion" => "Aprobar o enviar ordenes de compra"),
      array("modulo" => "compras", "accion" => "cancelar", "permiso" => "compras.cancelar", "descripcion" => "Cancelar documentos de compra"),
      array("modulo" => "compras", "accion" => "adjuntos", "permiso" => "compras.adjuntos", "descripcion" => "Subir y cancelar documentos adjuntos de compra"),
      array("modulo" => "proveedores", "accion" => "ver", "permiso" => "proveedores.ver", "descripcion" => "Consultar maestro y ficha general de proveedores"),
      array("modulo" => "proveedores", "accion" => "crear", "permiso" => "proveedores.crear", "descripcion" => "Crear proveedores y captura inicial general"),
      array("modulo" => "proveedores", "accion" => "editar", "permiso" => "proveedores.editar", "descripcion" => "Editar datos generales no fiscales ni financieros de proveedores"),
      array("modulo" => "proveedores", "accion" => "fiscales", "permiso" => "proveedores.fiscales", "descripcion" => "Consultar y administrar datos fiscales de proveedores"),
      array("modulo" => "proveedores", "accion" => "contactos", "permiso" => "proveedores.contactos", "descripcion" => "Administrar contactos por area de proveedores"),
      array("modulo" => "proveedores", "accion" => "condiciones", "permiso" => "proveedores.condiciones", "descripcion" => "Administrar condiciones comerciales y logisticas de proveedores"),
      array("modulo" => "proveedores", "accion" => "documentos", "permiso" => "proveedores.documentos", "descripcion" => "Administrar documentos no sensibles de proveedores"),
      array("modulo" => "proveedores", "accion" => "documentos_sensibles", "permiso" => "proveedores.documentos_sensibles", "descripcion" => "Consultar y cargar evidencia confidencial o financiera sensible de proveedores"),
      array("modulo" => "proveedores", "accion" => "listas", "permiso" => "proveedores.listas", "descripcion" => "Cargar y administrar listas de proveedor como evidencia versionada"),
      array("modulo" => "proveedores", "accion" => "matching", "permiso" => "proveedores.matching", "descripcion" => "Conciliar SKU proveedor contra SKU ERP"),
      array("modulo" => "proveedores", "accion" => "costos", "permiso" => "proveedores.costos", "descripcion" => "Administrar costos proveedor-SKU, vigencias y evidencia de costo"),
      array("modulo" => "proveedores", "accion" => "autorizar", "permiso" => "proveedores.autorizar", "descripcion" => "Autorizar estatus, condiciones, listas, relaciones o costos de proveedores"),
      array("modulo" => "proveedores", "accion" => "auditoria", "permiso" => "proveedores.auditoria", "descripcion" => "Consultar auditorias, dry-runs y trazabilidad de proveedores"),
      array("modulo" => "almacen", "accion" => "ver", "permiso" => "almacen.ver", "descripcion" => "Consultar recepciones y almacenes"),
      array("modulo" => "almacen", "accion" => "recibir", "permiso" => "almacen.recibir", "descripcion" => "Recibir mercancia desde ordenes de compra"),
      array("modulo" => "almacen", "accion" => "ubicaciones", "permiso" => "almacen.ubicaciones", "descripcion" => "Administrar ubicaciones de almacen"),
      array("modulo" => "inventario", "accion" => "ver", "permiso" => "inventario.ver", "descripcion" => "Consultar existencias y movimientos"),
      array("modulo" => "inventario", "accion" => "ajustar", "permiso" => "inventario.ajustar", "descripcion" => "Registrar ajustes de inventario"),
      array("modulo" => "inventario", "accion" => "traspasar", "permiso" => "inventario.traspasar", "descripcion" => "Registrar traspasos entre almacenes o ubicaciones"),
      array("modulo" => "inventario", "accion" => "conteo", "permiso" => "inventario.conteo", "descripcion" => "Levantar conteos fisicos y conciliaciones"),
      array("modulo" => "rentabilidad", "accion" => "ver", "permiso" => "rentabilidad.ver", "descripcion" => "Consultar costos, utilidad estimada, escenarios y recomendaciones de rentabilidad"),
      array("modulo" => "rentabilidad", "accion" => "snapshot", "permiso" => "rentabilidad.snapshot", "descripcion" => "Guardar snapshots de analisis de rentabilidad"),
      array("modulo" => "rentabilidad", "accion" => "configurar", "permiso" => "rentabilidad.configurar", "descripcion" => "Auditar y configurar estructura de rentabilidad"),
      array("modulo" => "crm", "accion" => "ver", "permiso" => "crm.ver", "descripcion" => "Consultar clientes, ficha, historial y relaciones CRM"),
      array("modulo" => "crm", "accion" => "crear", "permiso" => "crm.crear", "descripcion" => "Crear clientes y altas express autorizadas"),
      array("modulo" => "crm", "accion" => "editar", "permiso" => "crm.editar", "descripcion" => "Editar ficha, contactos, direcciones y condiciones CRM"),
      array("modulo" => "crm", "accion" => "fusionar", "permiso" => "crm.fusionar", "descripcion" => "Fusionar duplicados de clientes con trazabilidad"),
      array("modulo" => "crm", "accion" => "auditoria", "permiso" => "crm.auditoria", "descripcion" => "Auditar fuentes, migraciones, duplicados y esquema CRM"),
      array("modulo" => "ventas", "accion" => "ver", "permiso" => "ventas.ver", "descripcion" => "Consultar ventas, clientes y disponibilidad comercial"),
      array("modulo" => "ventas", "accion" => "operar", "permiso" => "ventas.operar", "descripcion" => "Registrar pedidos, ventas o apartados"),
      array("modulo" => "ventas", "accion" => "precio_manual", "permiso" => "ventas.precio_manual", "descripcion" => "Solicitar o aplicar precio manual en POS segun politica autorizada"),
      array("modulo" => "ventas", "accion" => "descuento_partida", "permiso" => "ventas.descuento_partida", "descripcion" => "Solicitar o aplicar descuento por partida en POS segun politica autorizada"),
      array("modulo" => "ventas", "accion" => "descuento_general", "permiso" => "ventas.descuento_general", "descripcion" => "Solicitar o aplicar descuento general en POS segun politica autorizada"),
      array("modulo" => "ventas", "accion" => "autorizar_excepcion_comercial", "permiso" => "ventas.autorizar_excepcion_comercial", "descripcion" => "Autorizar excepciones comerciales de precio o descuento en POS"),
      array("modulo" => "ventas", "accion" => "caja_evidencias_revisar", "permiso" => "ventas.caja_evidencias.revisar", "descripcion" => "Aprobar o rechazar evidencias de movimientos sensibles de caja POS"),
      array("modulo" => "ventas", "accion" => "caja_diferencias_ver", "permiso" => "ventas.caja_diferencias.ver", "descripcion" => "Consultar faltantes y sobrantes de caja POS por turno, usuario y sucursal"),
      array("modulo" => "ventas", "accion" => "caja_diferencias_revisar", "permiso" => "ventas.caja_diferencias.revisar", "descripcion" => "Crear o tomar expedientes de revision de diferencias de caja POS"),
      array("modulo" => "ventas", "accion" => "caja_diferencias_resolver", "permiso" => "ventas.caja_diferencias.resolver", "descripcion" => "Resolver administrativamente diferencias de caja POS sin mover efectivo ni inventario"),
      array("modulo" => "ventas", "accion" => "pos_config_ver", "permiso" => "ventas.pos_config.ver", "descripcion" => "Consultar configuracion POS de tiendas, cajas, terminales y asignaciones"),
      array("modulo" => "ventas", "accion" => "pos_config_crear", "permiso" => "ventas.pos_config.crear", "descripcion" => "Crear cajas, terminales y asignaciones POS sin abrir turnos ni mover caja"),
      array("modulo" => "ventas", "accion" => "pos_config_editar", "permiso" => "ventas.pos_config.editar", "descripcion" => "Editar cajas, terminales y asignaciones POS con trazabilidad"),
      array("modulo" => "ventas", "accion" => "pos_config_desactivar", "permiso" => "ventas.pos_config.desactivar", "descripcion" => "Desactivar configuracion POS con baja logica y motivo obligatorio"),
      array("modulo" => "ventas", "accion" => "pos_config_asignar_usuario", "permiso" => "ventas.pos_config.asignar_usuario", "descripcion" => "Asignar usuarios a tienda, caja y terminal POS oficial"),
      array("modulo" => "garantias", "accion" => "ver", "permiso" => "garantias.ver", "descripcion" => "Consultar politicas, elegibilidad y reclamos de garantia"),
      array("modulo" => "garantias", "accion" => "politicas", "permiso" => "garantias.politicas", "descripcion" => "Administrar politicas y reglas de garantia por catalogo"),
      array("modulo" => "garantias", "accion" => "reclamos_crear", "permiso" => "garantias.reclamos.crear", "descripcion" => "Iniciar reclamos de garantia desde venta, cliente, SKU o unidad"),
      array("modulo" => "garantias", "accion" => "reclamos_resolver", "permiso" => "garantias.reclamos.resolver", "descripcion" => "Diagnosticar y resolver reclamos de garantia"),
      array("modulo" => "garantias", "accion" => "autorizar", "permiso" => "garantias.autorizar", "descripcion" => "Autorizar excepciones o decisiones sensibles de garantia"),
      array("modulo" => "garantias", "accion" => "adjuntos", "permiso" => "garantias.adjuntos", "descripcion" => "Cargar y cancelar evidencias de reclamos de garantia"),
      array("modulo" => "garantias", "accion" => "reportes", "permiso" => "garantias.reportes", "descripcion" => "Consultar reportes e indicadores de garantias"),
      array("modulo" => "ecommerce", "accion" => "ver", "permiso" => "ecommerce.ver", "descripcion" => "Consultar estado de sincronizacion ecommerce"),
      array("modulo" => "ecommerce", "accion" => "sincronizar", "permiso" => "ecommerce.sincronizar", "descripcion" => "Ejecutar sincronizaciones de productos, stock, precios o pedidos"),
      array("modulo" => "finanzas", "accion" => "ver", "permiso" => "finanzas.ver", "descripcion" => "Consultar informacion financiera y contable"),
      array("modulo" => "finanzas", "accion" => "operar", "permiso" => "finanzas.operar", "descripcion" => "Registrar pagos, saldos, notas y movimientos financieros"),
      array("modulo" => "notificaciones", "accion" => "ver", "permiso" => "notificaciones.ver", "descripcion" => "Consultar notificaciones y alertas operativas propias"),
      array("modulo" => "auditoria", "accion" => "ver", "permiso" => "auditoria.ver", "descripcion" => "Consultar bitacoras, trazabilidad y eventos del sistema"),
      array("modulo" => "reportes", "accion" => "ver", "permiso" => "reportes.ver", "descripcion" => "Consultar reportes operativos y administrativos"),
      array("modulo" => "sistema", "accion" => "soporte", "permiso" => "sistema.soporte", "descripcion" => "Ejecutar herramientas tecnicas de diagnostico y mantenimiento")
    );
  }

  public function permisosPorRolBaseERP() {
    return array(
      "direccion" => array(
        "seguridad.ver", "catalogo.ver", "catalogo.costos",
        "compras.ver", "compras.aprobar", "almacen.ver", "inventario.ver", "crm.ver", "crm.auditoria", "ventas.ver",
        "ventas.precio_manual", "ventas.descuento_partida", "ventas.descuento_general", "ventas.autorizar_excepcion_comercial",
        "ventas.caja_evidencias.revisar", "ventas.caja_diferencias.ver", "ventas.caja_diferencias.revisar", "ventas.caja_diferencias.resolver",
        "ventas.pos_config.ver", "ventas.pos_config.crear", "ventas.pos_config.editar", "ventas.pos_config.desactivar", "ventas.pos_config.asignar_usuario",
        "ecommerce.ver", "finanzas.ver", "notificaciones.ver", "auditoria.ver", "reportes.ver",
        "proveedores.ver", "proveedores.fiscales", "proveedores.condiciones", "proveedores.documentos",
        "proveedores.documentos_sensibles", "proveedores.listas", "proveedores.costos",
        "proveedores.autorizar", "proveedores.auditoria", "rentabilidad.ver", "rentabilidad.snapshot",
        "garantias.ver", "garantias.autorizar", "garantias.reportes"
      ),
      "administrador_erp" => array(
        "seguridad.ver", "seguridad.administrar", "configuracion.administrar", "catalogo.ver",
        "catalogo.editar", "catalogo.costos", "compras.ver", "compras.crear", "compras.editar",
        "compras.aprobar", "compras.cancelar", "compras.adjuntos", "almacen.ver", "almacen.recibir",
        "almacen.ubicaciones", "inventario.ver",
        "inventario.ajustar", "inventario.traspasar", "inventario.conteo",
        "crm.ver", "crm.crear", "crm.editar", "crm.fusionar", "crm.auditoria",
        "ventas.ver", "ventas.operar", "ventas.precio_manual", "ventas.descuento_partida",
        "ventas.descuento_general", "ventas.autorizar_excepcion_comercial", "ventas.caja_evidencias.revisar",
        "ventas.caja_diferencias.ver", "ventas.caja_diferencias.revisar", "ventas.caja_diferencias.resolver",
        "ventas.pos_config.ver", "ventas.pos_config.crear", "ventas.pos_config.editar", "ventas.pos_config.desactivar", "ventas.pos_config.asignar_usuario", "ecommerce.ver",
        "ecommerce.sincronizar", "finanzas.ver", "finanzas.operar", "notificaciones.ver", "auditoria.ver", "reportes.ver",
        "proveedores.ver", "proveedores.crear", "proveedores.editar", "proveedores.fiscales",
        "proveedores.contactos", "proveedores.condiciones", "proveedores.documentos",
        "proveedores.documentos_sensibles", "proveedores.listas", "proveedores.matching",
        "proveedores.costos", "proveedores.autorizar", "proveedores.auditoria",
        "rentabilidad.ver", "rentabilidad.snapshot", "rentabilidad.configurar",
        "garantias.ver", "garantias.politicas", "garantias.reclamos.crear", "garantias.reclamos.resolver",
        "garantias.autorizar", "garantias.adjuntos", "garantias.reportes"
      ),
      "compras" => array(
        "catalogo.ver", "catalogo.costos", "compras.ver", "compras.crear", "compras.editar",
        "compras.aprobar", "compras.cancelar", "compras.adjuntos", "almacen.ver", "inventario.ver",
        "finanzas.ver", "notificaciones.ver", "reportes.ver",
        "proveedores.ver", "proveedores.crear", "proveedores.editar", "proveedores.contactos",
        "proveedores.condiciones", "proveedores.documentos", "proveedores.listas",
        "proveedores.matching", "proveedores.auditoria", "rentabilidad.ver", "garantias.ver"
      ),
      "almacen" => array(
        "catalogo.ver", "compras.ver", "almacen.ver", "almacen.recibir", "almacen.ubicaciones",
        "inventario.ver", "inventario.traspasar", "notificaciones.ver", "auditoria.ver",
        "proveedores.ver", "proveedores.contactos", "garantias.ver"
      ),
      "inventario" => array(
        "catalogo.ver", "almacen.ver", "almacen.ubicaciones", "inventario.ver", "inventario.ajustar",
        "inventario.traspasar", "inventario.conteo", "notificaciones.ver", "auditoria.ver", "reportes.ver",
        "proveedores.ver", "garantias.ver"
      ),
      "ventas" => array(
        "catalogo.ver", "inventario.ver", "crm.ver", "crm.crear", "ventas.ver", "ventas.operar", "ventas.caja_diferencias.ver", "ecommerce.ver", "notificaciones.ver", "reportes.ver",
        "garantias.ver", "garantias.reclamos.crear"
      ),
      "crm" => array(
        "crm.ver", "crm.crear", "crm.editar", "crm.fusionar", "crm.auditoria",
        "ventas.ver", "garantias.ver", "garantias.reclamos.crear", "notificaciones.ver", "reportes.ver"
      ),
      "ecommerce" => array(
        "catalogo.ver", "catalogo.editar", "inventario.ver", "ventas.ver", "ecommerce.ver",
        "ecommerce.sincronizar", "notificaciones.ver", "reportes.ver"
      ),
      "catalogo_productos" => array(
        "catalogo.ver", "catalogo.editar", "catalogo.costos", "compras.ver", "inventario.ver", "ecommerce.ver",
        "proveedores.ver", "proveedores.listas", "proveedores.matching", "proveedores.costos",
        "proveedores.auditoria", "notificaciones.ver", "rentabilidad.ver", "rentabilidad.snapshot",
        "garantias.ver", "garantias.politicas"
      ),
      "finanzas_contabilidad" => array(
        "catalogo.ver", "catalogo.costos", "compras.ver", "ventas.ver",
        "ventas.caja_diferencias.ver", "ventas.caja_diferencias.revisar", "ventas.caja_diferencias.resolver",
        "finanzas.ver", "finanzas.operar", "notificaciones.ver", "auditoria.ver", "reportes.ver",
        "proveedores.ver", "proveedores.fiscales", "proveedores.condiciones", "proveedores.documentos",
        "proveedores.documentos_sensibles", "proveedores.costos", "proveedores.autorizar",
        "proveedores.auditoria", "rentabilidad.ver", "rentabilidad.snapshot"
      ),
      "auditor" => array(
        "catalogo.ver", "compras.ver", "almacen.ver", "inventario.ver", "ventas.ver",
        "ventas.pos_config.ver", "ecommerce.ver", "finanzas.ver", "notificaciones.ver", "auditoria.ver", "reportes.ver",
        "proveedores.ver", "proveedores.auditoria", "rentabilidad.ver", "garantias.ver", "garantias.reportes"
      ),
      "solo_lectura" => array(
        "catalogo.ver", "compras.ver", "almacen.ver", "inventario.ver", "ventas.ver",
        "ecommerce.ver", "finanzas.ver", "notificaciones.ver", "reportes.ver", "proveedores.ver", "rentabilidad.ver",
        "garantias.ver"
      ),
      "soporte_sistema" => array(
        "seguridad.ver", "notificaciones.ver", "auditoria.ver", "reportes.ver", "sistema.soporte", "proveedores.auditoria", "rentabilidad.configurar"
      )
    );
  }

  private function planSemillaRolesPermisosERP($ejecutar) {
    $plan = array();

    foreach ($this->rolesBaseERP() as $rol) {
      $sql = "INSERT INTO sys_roles (rol, descripcion, estatus)
              VALUES (" . $this->sqlTexto($rol["rol"]) . ", " . $this->sqlTexto($rol["descripcion"]) . ", 1)
              ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), estatus = VALUES(estatus), fecha_actualizacion = CURRENT_TIMESTAMP;";
      $plan[] = $this->ejecutarSQLSemilla($sql, $ejecutar);
    }

    foreach ($this->permisosBaseERP() as $permiso) {
      $sql = "INSERT INTO sys_permisos (modulo, accion, permiso, descripcion, estatus)
              VALUES (" . $this->sqlTexto($permiso["modulo"]) . ", " . $this->sqlTexto($permiso["accion"]) . ", " . $this->sqlTexto($permiso["permiso"]) . ", " . $this->sqlTexto($permiso["descripcion"]) . ", 1)
              ON DUPLICATE KEY UPDATE modulo = VALUES(modulo), accion = VALUES(accion), descripcion = VALUES(descripcion), estatus = VALUES(estatus), fecha_actualizacion = CURRENT_TIMESTAMP;";
      $plan[] = $this->ejecutarSQLSemilla($sql, $ejecutar);
    }

    foreach ($this->permisosPorRolBaseERP() as $rol => $permisos) {
      foreach ($permisos as $permiso) {
        $sql = "INSERT IGNORE INTO sys_roles_permisos (id_rol, id_permiso)
                SELECT sr.id_rol, sp.id_permiso
                FROM sys_roles sr
                INNER JOIN sys_permisos sp ON sp.permiso = " . $this->sqlTexto($permiso) . "
                WHERE sr.rol = " . $this->sqlTexto($rol) . ";";
        $plan[] = $this->ejecutarSQLSemilla($sql, $ejecutar);
      }
    }

    return $plan;
  }

  private function ejecutarSQLSemilla($sql, $ejecutar) {
    if (!$ejecutar) {
      return array(
        "error" => false,
        "tipo" => "info",
        "mensaje" => "SQL de semilla generado sin ejecutar",
        "depurar" => array("sql" => $sql, "ejecutado" => false)
      );
    }

    try {
      $db = $this->conectar();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      return array(
        "error" => false,
        "tipo" => "success",
        "mensaje" => "SQL de semilla ejecutado correctamente",
        "depurar" => array("sql" => $sql, "ejecutado" => true)
      );
    } catch (Exception $e) {
      return array(
        "error" => true,
        "tipo" => "danger",
        "mensaje" => $e->getMessage(),
        "depurar" => $sql
      );
    }
  }

  private function sqlTexto($valor) {
    return "'" . str_replace("'", "''", $valor) . "'";
  }
}
