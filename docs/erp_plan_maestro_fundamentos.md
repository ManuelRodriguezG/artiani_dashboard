# ERP - Plan maestro de fundamentos

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-10  
Estado: Plan rector para ordenar el ERP antes de cerrar Compras  
Relacionados: `AGENTS.md`, `docs/erp_compras_cierre_modulo.md`, `docs/erp_ux_operativa.md`, `docs/ia_uso_modelos.md`
Documento transversal adicional: `docs/erp_notificaciones_alertas_trabajo.md`
Documento transversal de codigo: `docs/erp_estandar_documentacion_codigo.md`

## Proposito

Este documento define que debe quedar bien construido antes de seguir profundizando Compras. El objetivo es evitar regresar una y otra vez por permisos, usuarios, catalogo, proveedores o inventario mal definidos.

La meta del ERP es avanzar sin recapturar lo que ya existe, pero sin seguir dependiendo del sistema ecommerce anterior como fuente principal.

## Norma de decisiones guiadas

Cuando una decision sea de arquitectura, catalogo operativo, permisos, inventario, finanzas, fiscal, costos o cualquier criterio propio de un ERP robusto, el agente no debe pedir solo "que valor quieres" si el dueno no tiene por que conocer la convencion tecnica.

Debe proponer:

- Opciones razonables usadas en ERPs bien estructurados.
- Recomendacion concreta para este negocio/proyecto.
- Explicacion corta de por que conviene.
- Impacto operativo de elegir otra opcion.
- Si requiere escritura en BD, respaldo externo, evidencia antes/despues y autorizacion explicita.

Esta norma aplica especialmente a catalogos base como tipos de almacen, estatus, tipos de movimiento, politicas de inventario, permisos, reglas de costo, FKs, impuestos y migraciones legacy.

### Norma de interpretacion de contexto operativo

El dueno del proyecto puede describir casos reales, dudas o soluciones tentativas desde la operacion diaria. Esas descripciones deben tratarse como evidencia de negocio, no como instrucciones literales de diseno.

El agente debe:

- traducir el caso operativo a una practica de ERP robusto;
- separar lo que corresponde a Catalogo, Proveedores, Compras, Almacen, Inventario, Ventas, Fiscal, Costos o Rentabilidad;
- no darle la razon al dueno si la solucion propuesta mezcla responsabilidades o crea deuda operativa;
- explicar con claridad cuando conviene usar otra estructura, estado, permiso o modulo;
- documentar la regla resultante en el documento vivo del modulo correspondiente.

Objetivo: construir un ERP que ayude al negocio a operar como empresa formal, incluso cuando el dueno todavia esta descubriendo los procesos mientras el sistema se construye.

## Norma de documentacion de codigo generado por IA

Todo codigo nuevo o funcion modificada por IA debe documentarse con version/modelo, fecha, proposito, impacto y contrato operativo cuando aplique.

Documento rector: `docs/erp_estandar_documentacion_codigo.md`.

Esta norma aplica a todos los modulos ERP. Si una funcion afecta fiscal, costos, precios, inventario, compras, proveedores, ventas, permisos o auditoria, el comentario en codigo no sustituye la documentacion viva del modulo correspondiente.

## Norma de continuidad de contexto entre chats

Fecha: 2026-06-26

Cuando un modulo avance mediante varios chats o cuando Codex indique limite/compactacion de contexto, el estado real no debe depender de la memoria del chat.

Cada documento vivo de modulo debe mantener una seccion o bloque equivalente con:

- objetivo operativo actual del modulo;
- decisiones de arquitectura ya tomadas;
- responsabilidades que pertenecen al modulo y responsabilidades que se delegan a otros modulos;
- cambios recientes implementados o autorizados;
- pendientes abiertos, riesgos y dudas de negocio;
- handoff breve para continuar en otro chat sin releer toda la conversacion.

Regla practica:

- Al cerrar una subtarea relevante, actualizar el documento vivo del modulo.
- Si un cambio afecta otro modulo, dejar instruccion concreta para el chat/documento de ese modulo.
- Si una decision corrige una confusion recurrente del dueno del proyecto, documentarla como regla operativa, no solo como respuesta conversacional.
- Evitar que los documentos dependan de frases como "como dijimos en el chat"; deben explicar el contexto suficiente para que otro agente o persona pueda continuar.

Objetivo: que la compactacion de contexto de Codex o el cambio de chat no borre decisiones, contratos ni razones operativas.

## Diagnostico actual observado

### Seguridad y roles

Ya existe una base real:

- `app/core/SesionSeguridad.php` maneja sesion, expiracion, CSRF, permisos y auditoria.
- `app/core/Core.php` protege controladores, valida CSRF en POST y registra auditoria generica.
- `app/core/Controlador.php` tiene `requerirPermiso`.
- `app/modelos/SeguridadEsquema.php` define tablas `sys_roles`, `sys_permisos`, `sys_roles_permisos`, `sys_usuarios_roles`, `sys_auditoria_eventos`.
- `app/modelos/SeguridadEsquema.php` tambien tiene roles base ERP y permisos base.
- `app/controladores/Sistema.php` ya tiene endpoints para roles, permisos, usuarios, auditoria y estatus.
- `app/modelos/SeguridadPermisos.php` ya asigna/quita roles, actualiza permisos y protege administrador.

Riesgo:

- Algunos controladores antiguos todavia validan sesion manualmente con `$_SESSION['id_usuario']`.
- Algunos endpoints legados no tienen permiso puntual.
- Hay que asegurar que cada modulo nuevo use `requerirPermiso` y permisos por seccion.

Conclusion:

- Seguridad va primero. No porque no exista, sino porque debe quedar cerrada como cimiento antes de delegar tareas por roles.

### Notificaciones y alertas operativas

Debe existir un criterio transversal para que los modulos generen avisos operativos segun permisos, roles y areas responsables.

Regla:

- Las alertas/notificaciones son flujo de trabajo vivo.
- La auditoria es historial.
- El chat, si existe despues, es conversacion humana y no debe reemplazar pendientes persistentes.
- Cada modulo debe declarar que eventos generan alerta, quien puede verlos y quien puede resolverlos.

Documento rector:

- `docs/erp_notificaciones_alertas_trabajo.md`

Avance 2026-06-16:

- Preparado esquema transversal de notificaciones en `NotificacionesEsquema`.
- Preparado permiso base `notificaciones.ver` en semillas de seguridad.
- Ejecutado esquema y permisos en base local con respaldo externo previo.
- Preparado backend inicial de consulta/marcado de notificaciones.
- Pendiente integrar contador/indicador visual en navbar.

### Catalogo ERP

Ya existe una estructura importante:

- `CatalogoErpEsquema.php` define tablas ERP propias para unidades, marcas, categorias, productos, SKU, codigos, atributos, SKU proveedor, impuestos, precios, reglas de inventario, vinculos ecommerce, imagenes, taxonomias e incidencias.
- `CatalogoErpMigracionEcommerce.php` migra de forma conservadora desde `ecom_*` hacia ERP.
- `CatalogoErpDatos.php` tiene funciones para calidad de catalogo, relaciones proveedor, costos, metadatos, reorden, productos, SKU e imagenes.
- `CatalogoErp.php` ya separa permisos `catalogo.ver`, `catalogo.editar`, `catalogo.costos`.

Riesgo:

- El sistema anterior tenia productos en `ecom_productos`.
- No conviene recapturar todo.
- No conviene que Compras dependa de `ecom_*`.
- Hay que terminar migracion/vinculacion e incidencias antes de que Compras use catalogo como verdad confiable.

Conclusion:

- Catalogo ERP y migracion ecommerce son el segundo cimiento.

### Proveedores y listas

Ya existe modulo legado:

- `Proveedor.php`
- `Proveedores.php`
- Vistas/JS en `apps/erp/proveedores`
- Funciones de proveedores, listas, listas mayoreo, pedidos, productos proveedor, historial de costos y listas de proveedor.

Riesgo:

- Parece flujo previo/legado, no necesariamente alineado al nuevo Catalogo ERP.
- Compras necesita proveedores y relaciones SKU proveedor confiables.
- Los costos de proveedor/listas no deben perderse, pero deben vincularse al ERP nuevo.

Conclusion:

- Proveedores/listas deben auditarse y conectarse al Catalogo ERP antes de cerrar ordenes de compra robustas.

### Almacen e inventario

Ya existe base ERP:

- `Almacen.php`, `Almacenes.php`, `AlmacenEsquema.php`.
- `Inventario.php`, `InventarioErp.php`.
- Recepciones, existencias, movimientos y operaciones ERP ya estan separandose del legado.
- Guia de arranque para continuar el modulo: `docs/erp_almacen_recepciones_arranque.md`.

Riesgo:

- Compras no debe afectar inventario directo.
- Para cerrar ordenes, debe existir al menos flujo base de recepcion y existencias.

Conclusion:

- Almacen/Inventario puede avanzar en paralelo despues de Catalogo/Proveedores, pero su contrato con Compras debe estar claro antes de enviar ordenes.

Regla de trazabilidad por unidad:

- Catalogo decide si un SKU requiere serie de fabricante o etiqueta interna propia.
- Almacen ejecuta la politica en la recepcion.
- Inventario conserva la unidad individual.
- Ventas asocia la unidad vendida.
- Devoluciones y garantias validan la unidad contra venta propia.

No se debe confundir serie de fabricante con etiqueta de trazabilidad interna; son controles distintos y deben tener campos separados.

## Orden maestro recomendado

### 1. Seguridad, usuarios, roles y auditoria

Objetivo:

- Poder delegar tareas por rol sin romper el negocio.

Debe quedar:

- Sesion activa y consistente.
- Roles base revisados: direccion, administrador_erp, compras, almacen, inventario, catalogo_productos, finanzas_contabilidad, auditor, soporte_sistema.
- Permisos base por modulo.
- Pantalla de usuarios/roles usable.
- Auditoria consultable.
- Endpoints sensibles con `requerirPermiso`.
- Proteccion de administrador.

No seguir profundamente con Compras hasta:

- Poder asignar un usuario a rol Compras.
- Poder asignar permisos de Catalogo, Finanzas, Almacen.
- Poder ver auditoria basica.

### 2. Catalogo ERP maestro

Objetivo:

- Tener productos/SKUs/unidades/fiscales/costos base en ERP, no en ecommerce.

Debe quedar:

- Esquema ERP de catalogo auditado/actualizado.
- Migracion conservadora desde `ecom_productos`.
- Incidencias de migracion visibles y resolubles.
- Productos maestros y SKUs ERP creados o vinculados.
- Unidades y claves SAT.
- Impuestos/fiscales por SKU.
- Reglas de inventario: inventariable, lote, caducidad, serie.
- Codigos de barras/codigos internos.
- Imagenes/vinculos si aplican.

No seguir profundamente con Compras hasta:

- Poder buscar SKU ERP confiable.
- Poder detectar producto pendiente/nuevo.
- Poder saber si un producto es inventariable.
- Poder saber sus datos fiscales basicos.

### 3. Proveedores y relaciones SKU proveedor

Objetivo:

- No perder proveedores, listas y costos existentes, pero llevarlos al flujo ERP nuevo.

Debe quedar:

- Proveedores existentes auditados.
- Listas proveedor existentes identificadas.
- Productos proveedor vinculados a `erp_catalogo_sku_proveedores`.
- SKU proveedor, costo ultimo, costo proveedor, piezas por caja, unidad compra y moneda si aplica.
- Historial de costos conservado o migrado.
- Incidencias cuando un producto proveedor no vincula a SKU ERP.

No seguir profundamente con Compras hasta:

- El buscador de productos por proveedor use relaciones ERP.
- Una orden pueda traer ultimo costo y SKU proveedor sin depender del legado.

### 4. Almacen e inventario base

Objetivo:

- Tener destino fisico para compras sin afectar inventario desde Compras.

Debe quedar:

- Almacenes activos.
- Ubicaciones si aplican.
- Reglas de recepcion.
- Existencias por SKU/almacen/ubicacion.
- Movimiento de inventario por recepcion.
- Recepcion parcial/completa.
- Incidencias de recepcion.

Avance 2026-06-19:

- Almacen/Recepciones queda cerrado funcionalmente con UAT documentado en `docs/erp_almacen_recepciones_cierre.md`.
- Recepciones ya alimentan existencias, movimientos, lotes y unidades etiquetadas.
- Siguiente paso recomendado: Inventario/Existencias operativo, con arranque en `docs/erp_inventario_existencias_arranque.md`.

No seguir con envio/recepcion de ordenes hasta:

- Una orden enviada pueda preparar recepcion.
- Almacen pueda recibir sin que Compras toque inventario.

### 5. Compras - Solicitudes

Objetivo:

- Registrar necesidad antes de comprar.

Debe quedar:

- `docs/erp_compras_solicitudes_avance.md` completo.
- Nueva/editar/ver/listado.
- Permisos por seccion.
- Documento formal.
- Generar orden desde solicitud sin duplicados.
- Diferencias solicitud vs orden.

### 6. Compras - Ordenes

Objetivo:

- Capturar compra real usando catalogo/proveedor/inventario ya preparados.

Debe quedar:

- Orden nueva.
- Editar orden.
- Ver orden.
- XML como carga/enriquecimiento, no doble captura.
- Productos nuevos como pendientes.
- Cargos no inventariables.
- Costos/descuentos/fiscales claros.
- Adjuntos.
- Finanzas basica.
- Envio a almacen.

### 7. Finanzas y cuentas por pagar

Objetivo:

- Separar captura de compras de validacion financiera.

Debe quedar:

- Pagos y notas.
- Saldos.
- Estados pendiente/aplicado/conciliado/cancelado.
- Comprobantes.
- Restricciones al editar totales con pagos.

### 8. Reportes y direccion

Objetivo:

- Tomar decisiones con informacion real.

Debe quedar:

- Compras por proveedor/producto/fecha.
- Pendientes por area.
- Costos cambiantes.
- Productos por recibir.
- Saldos por proveedor.
- Incidencias de catalogo, almacen y compras.

## Checklist maestro inicial

### A. Seguridad base

- Estado: [ ]
- Nivel IA sugerido: D.
- Documento sugerido a crear: `docs/erp_seguridad_roles_avance.md`.
- Terminado cuando:
  - Roles/permisos estan auditados.
  - Usuarios pueden asignarse a roles.
  - Permisos se aplican en backend.
  - Auditoria basica funciona.

### B. Catalogo ERP y migracion ecommerce

- Estado: [ ]
- Nivel IA sugerido: D.
- Documento: `docs/erp_catalogo_avance.md`.
- Terminado cuando:
  - Productos `ecom_*` utiles estan migrados o vinculados.
  - Incidencias estan identificadas.
  - SKU ERP puede usarse en Compras.

### C. Proveedores/listas a ERP

- Estado: [ ]
- Nivel IA sugerido: D.
- Documento sugerido a crear: `docs/erp_proveedores_avance.md`.
- Terminado cuando:
  - Proveedores existentes estan auditados.
  - Listas/costos se conservan.
  - Relaciones SKU proveedor estan en ERP.

### D. Almacen/Inventario base

- Estado: Recepciones cerrado funcionalmente; Inventario/Existencias siguiente.
- Nivel IA sugerido: D.
- Documentos: `docs/erp_almacen_recepciones_cierre.md`, `docs/erp_inventario_existencias_arranque.md`.
- Terminado cuando:
  - Almacenes, ubicaciones, existencias y recepcion base funcionan.
  - Ajustes, traspasos, disponibilidad y kardex de Inventario queden validados.

### E. Compras Solicitudes

- Estado: en progreso.
- Documento: `docs/erp_compras_solicitudes_avance.md`.
- Terminado cuando:
  - Checklist de solicitudes completo.

### F. Compras Ordenes

- Estado: planificado.
- Documentos:
  - `docs/erp_compras_orden_nueva_trabajo.md`
  - `docs/erp_compras_orden_editar_trabajo.md`
  - `docs/erp_compras_orden_ver_trabajo.md`
- Terminado cuando:
  - Ordenes usan Catalogo ERP, Proveedores ERP, permisos y Almacen base.

## Siguiente paso recomendado

Antes de seguir implementando mas Compras, crear y ejecutar el plan de Seguridad:

```text
docs/erp_seguridad_roles_avance.md
```

Prompt recomendado:

```text
Trabaja solo en ERP > Fundamentos > Seguridad y roles.
Usa AGENTS.md, docs/erp_plan_maestro_fundamentos.md y docs/ia_uso_modelos.md.
Objetivo: auditar usuarios, roles, permisos, auditoria, sesion y endpoints sensibles para definir que falta antes de delegar tareas por roles.
No modificar Compras ni Catalogo todavia.
Criterio: entregar checklist exacto de seguridad: hecho, pendiente, riesgos y orden de implementacion.
```

## Decision recomendada

Si quieres evitar devolverte, el camino sano es:

1. Cerrar seguridad/roles.
2. Cerrar catalogo ERP/migracion ecommerce con `docs/erp_catalogo_avance.md`.
3. Cerrar proveedores/listas hacia ERP.
4. Retomar Compras Solicitudes.
5. Seguir Ordenes.

Esto no significa pausar todo indefinidamente; significa que cada avance de Compras debe depender de una base real y no de suposiciones.
