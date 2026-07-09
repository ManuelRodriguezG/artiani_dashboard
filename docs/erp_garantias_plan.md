# ERP Garantias - Guia de arquitectura

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: plan rector; no implica escrituras en BD.

## Decision principal

La garantia no debe vivir solo en POS.

Modulo dueno de la politica:

- Catalogo ERP.

Modulos participantes:

- Ventas/POS: guarda snapshot y muestra garantia al cliente.
- Postventa/Ventas: gestiona reclamos.
- Inventario/Almacen: recibe producto fisico, cuarentena, merma o reingreso.
- Proveedores: gestiona garantia con proveedor cuando aplique.
- Reportes: mide costo, recurrencia y calidad.

## Por que Catalogo es el dueno

La garantia depende del producto, no de la venta individual.

Catalogo conoce:

- SKU;
- categoria;
- marca;
- proveedor preferido;
- si requiere serie;
- si requiere lote;
- si es perecedero;
- tipo de producto;
- reglas por canal.

Ventas solo debe tomar la politica vigente y congelarla como snapshot al vender.

## Tipos de garantia

Tipos recomendados:

- `sin_garantia`
- `garantia_tienda`
- `garantia_proveedor`
- `garantia_fabricante`
- `cambio_inmediato`
- `reparacion`
- `satisfaccion_limitada`
- `caducidad_calidad`

## Coberturas

Coberturas posibles:

- cambio de producto;
- reparacion;
- nota de credito;
- devolucion de dinero;
- envio a proveedor;
- diagnostico previo;
- rechazo por exclusion.

## Requisitos

Una politica puede exigir:

- ticket/folio;
- cliente identificado;
- empaque;
- accesorios completos;
- numero de serie;
- lote;
- fecha de caducidad;
- fotos;
- diagnostico;
- validacion de proveedor;
- autorizacion de supervisor.

## Exclusiones

Ejemplos:

- mal uso;
- producto abierto sin defecto;
- producto consumible;
- alimento fuera de periodo;
- caducidad vencida por cliente;
- producto en oferta final;
- dano fisico evidente;
- falta de ticket cuando la politica lo exige.

## Esquema sugerido

### `erp_garantias_politicas`

Define politica reutilizable.

Campos:

- `id_garantia_politica`
- `codigo`
- `nombre`
- `tipo_garantia`
- `cobertura`
- `duracion`
- `unidad_duracion`: dias, meses
- `requiere_ticket`
- `requiere_cliente`
- `requiere_serie`
- `requiere_lote`
- `requiere_empaque`
- `requiere_diagnostico`
- `permite_cambio`
- `permite_reparacion`
- `permite_devolucion`
- `permite_nota_credito`
- `estatus`

### `erp_garantias_politicas_reglas`

Asigna politica a producto, SKU, categoria, marca o proveedor.

Campos:

- `id_regla`
- `id_garantia_politica`
- `ambito`: sku, producto, categoria, marca, proveedor
- `id_referencia`
- `prioridad`
- `canal`
- `id_almacen`
- `vigencia_desde`
- `vigencia_hasta`
- `estatus`

### `erp_ventas_detalle_garantias`

Snapshot al momento de venta.

Campos:

- `id_venta_detalle_garantia`
- `id_venta`
- `id_venta_detalle`
- `id_sku`
- `id_garantia_politica`
- `tipo_garantia_snapshot`
- `cobertura_snapshot`
- `requisitos_snapshot`
- `fecha_inicio`
- `fecha_vencimiento`
- `estatus`

### `erp_garantias_reclamos`

Caso postventa.

Campos:

- `id_reclamo_garantia`
- `folio`
- `id_venta`
- `id_venta_detalle`
- `id_cliente`
- `id_sku`
- `id_inventario_unidad`
- `motivo`
- `descripcion`
- `estatus`
- `decision`
- `creado_por`
- `autorizado_por`
- `fecha_registro`
- `fecha_resolucion`

### `erp_garantias_reclamos_eventos`

Historial del caso.

Campos:

- `id_evento`
- `id_reclamo_garantia`
- `tipo_evento`
- `comentario`
- `creado_por`
- `fecha_registro`

### `erp_garantias_adjuntos`

Evidencias.

Campos:

- `id_adjunto`
- `id_reclamo_garantia`
- `tipo_adjunto`
- `ruta`
- `nombre_original`
- `estatus`
- `creado_por`

## Flujo operativo recomendado

### Venta

1. POS agrega producto.
2. Backend resuelve garantia vigente del SKU.
3. Al confirmar venta, se guarda snapshot en detalle.
4. Ticket muestra resumen de garantia.

### Consulta

1. Usuario busca por folio, ticket, cliente, SKU o codigo de unidad.
2. Sistema muestra si esta dentro de garantia.
3. Sistema muestra requisitos y exclusiones.

### Reclamo

1. Se crea reclamo.
2. Se adjuntan fotos/comprobante si aplica.
3. Se decide si requiere revision.
4. Si entra producto fisico, Almacen lo recibe en cuarentena.
5. Se resuelve:
   - cambio;
   - reparacion;
   - devolucion;
   - nota;
   - rechazo.

### Cierre

1. Se actualiza reclamo.
2. Si hubo movimiento de inventario, se registra kardex.
3. Si hubo dinero, se registra movimiento de caja o devolucion.
4. Si aplica proveedor, se crea seguimiento con Proveedores/Compras.

## UX en POS

POS debe mostrar:

- garantia resumida en la ficha del producto;
- garantia en ticket;
- consulta rapida por folio;
- boton `Garantia/Devolucion` en venta historica;
- advertencias si el producto no tiene garantia o ya vencio.

POS no debe:

- editar politicas de garantia;
- decidir reglas de proveedor;
- borrar reclamos;
- reingresar inventario sin flujo de Almacen.

## Reportes

Reportes necesarios:

- reclamos por SKU;
- reclamos por marca;
- reclamos por proveedor;
- costo de garantias;
- garantias rechazadas;
- garantias vencidas;
- productos con alta incidencia;
- tiempos de resolucion.

## Orden recomendado de implementacion

1. Crear politicas en Catalogo.
2. Resolver garantia vigente por SKU.
3. Guardar snapshot en venta.
4. Mostrar garantia en ticket.
5. Crear consulta por folio.
6. Crear reclamos.
7. Integrar Almacen/Inventario para cuarentena.
8. Integrar Proveedores si aplica.
9. Reportes.

## Auditoria inicial para convertir el plan en tareas

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: plan de trabajo; no implica cambios de esquema ni codigo.

### Hallazgos actuales

- No existe todavia un modulo formal `Garantias` con controlador/modelo propio.
- Ventas ya tiene una base cercana para cancelaciones/devoluciones:
  - `erp_ventas_devoluciones`;
  - `erp_ventas_devoluciones_detalle`;
  - endpoint/modelo de dry-run de devolucion.
- Almacen/Inventario ya contempla destinos tecnicos como `devoluciones`, `cuarentena` y `merma`.
- Inventario ya reconoce movimiento de entrada `devolucion_cliente`.
- La garantia aparece documentada en planes de POS/Ventas, pero no esta implementada como flujo completo.

Conclusion:

- Garantias debe ser un dominio propio o subdominio formal de postventa, no solo una opcion dentro de POS.
- Catalogo define la politica del producto.
- Ventas congela la politica al vender y puede iniciar postventa.
- Almacen decide el destino fisico de la unidad devuelta.
- Proveedores solo participa cuando la politica o decision indica seguimiento externo.

### Regla de separacion con devoluciones

Una devolucion es una operacion comercial o de reversa sobre una venta.

Una garantia es un caso postventa con elegibilidad, requisitos, evidencia, diagnostico, decision y cierre.

Una garantia puede terminar en:

- cambio de producto;
- reparacion;
- rechazo;
- nota de credito;
- devolucion de dinero;
- envio a proveedor;
- sin movimiento comercial, si solo fue consulta.

Por eso, el flujo de garantias puede referenciar una devolucion, pero no debe reemplazarse por la tabla de devoluciones.

## Responsabilidades por modulo

### Catalogo ERP

Debe administrar:

- politicas de garantia reutilizables;
- asignacion de politicas por SKU, producto, categoria, marca o proveedor;
- prioridad de reglas;
- vigencias;
- restricciones por canal o almacen si aplica;
- alertas de catalogo cuando un producto requiere politica y no la tiene.

No debe:

- resolver reclamos de clientes;
- mover inventario;
- aprobar reembolsos;
- gestionar garantia con proveedor.

### Ventas/POS/Postventa

Debe administrar:

- snapshot de garantia al confirmar venta;
- consulta de elegibilidad por folio/ticket/SKU/cliente/codigo de unidad;
- inicio del reclamo;
- relacion con devolucion, nota o reembolso cuando la decision lo requiera;
- visualizacion clara en ticket y venta historica.

No debe:

- editar politicas de Catalogo;
- reingresar inventario directamente;
- borrar historial del reclamo.

### Almacen/Inventario

Debe administrar:

- recepcion fisica del producto reclamado;
- destino tecnico: cuarentena, devoluciones, merma, reparacion o reingreso;
- validacion de unidad, serie, lote o etiqueta interna cuando aplique;
- movimientos de inventario derivados de la decision.

No debe:

- decidir la politica comercial de garantia;
- modificar el snapshot de venta.

### Proveedores

Debe administrar:

- seguimiento de garantias enviadas a proveedor;
- folio/respuesta del proveedor;
- evidencia y tiempos de respuesta;
- recuperacion, reposicion, nota o rechazo del proveedor.

No debe:

- cambiar la politica vendida al cliente;
- modificar inventario sin flujo de Almacen.

## Plan de tareas necesarias

### GAR-T001 - Auditoria de esquema y contratos existentes

Objetivo: confirmar que tablas, endpoints y documentos actuales no dupliquen responsabilidades.

Archivos a revisar:

- `app/modelos/VentasErpEsquema.php`
- `app/modelos/VentasErp.php`
- `app/controladores/Ventas.php`
- `app/modelos/Almacenes.php`
- `app/modelos/InventarioErp.php`
- `docs/erp_ventas_pos_robustez_plan.md`
- `docs/erp_ventas_pos_pedidos_arranque.md`
- `docs/erp_almacen_sucursales_almacenes_arranque.md`
- `docs/erp_etiquetas_series_trazabilidad_diseno.md`

Cierre:

- mapa confirmado de tablas reutilizables;
- decision documentada sobre modulo propietario;
- riesgos de duplicidad identificados.

#### Resultado GAR-T001

Fecha: 2026-06-27  
Estado: completado como auditoria documental y de codigo; sin cambios de BD.

Hallazgos en Ventas:

- `VentasErpEsquema.php` ya define `erp_ventas_detalle`, con SKU, cantidades, unidad de venta, unidad base, precio e impuestos por partida.
- `VentasErpEsquema.php` ya define `erp_ventas_detalle_inventario`, con trazabilidad por existencia, unidad fisica, reserva, movimiento, lote, caducidad y cantidades antes/despues.
- `VentasErpEsquema.php` ya define `erp_ventas_devoluciones` y `erp_ventas_devoluciones_detalle`.
- `erp_ventas_devoluciones_detalle` ya contempla `id_venta_detalle`, `id_inventario_unidad`, `cantidad_base` y `decision_inventario`.
- `VentasErp::devolucionDryRun()` solo simula cancelacion/devolucion; no escribe BD, no mueve inventario y bloquea si falta esquema.
- El dry-run permite decisiones de inventario: `reintegrar`, `cuarentena`, `merma`, `sin_reingreso`.

Conclusion para Ventas:

- Las tablas de devolucion son reutilizables como resultado comercial de una garantia, cuando la resolucion sea devolucion, cambio, nota o reversa.
- No son suficientes para representar el reclamo completo de garantia, porque no guardan politica aplicada, elegibilidad, diagnostico, evidencias, eventos, proveedor ni autorizaciones.
- Ventas debe guardar el snapshot de garantia al vender y despues referenciarlo desde reclamos; no debe recalcular politicas vivas de Catalogo.

Hallazgos en Almacen/Inventario:

- `Almacenes.php` ya reconoce almacenes de tipo `devoluciones`, `merma` y `cuarentena`.
- Recepcion de almacen ya registra lotes con `cantidad_compra`, `unidad_compra`, `cantidad_base`, `unidad_base`, `factor_conversion`, caducidad, ubicacion y revision.
- Almacen ya genera/usa `erp_inventario_unidades` cuando aplica trazabilidad por unidad.
- Inventario ya permite movimiento de entrada `devolucion_cliente`.
- `erp_inventario_unidades` permite buscar por codigo unico, etiqueta interna o serie de fabricante.

Conclusion para Almacen/Inventario:

- Almacen/Inventario tiene la base para recibir producto devuelto o reclamado en un destino tecnico.
- Garantias no debe escribir existencia disponible directamente.
- La garantia debe solicitar/ligar una recepcion o movimiento tecnico; Almacen decide si queda en cuarentena, devoluciones, merma, reparacion o reingreso.

Hallazgos documentales:

- `docs/erp_ventas_pos_robustez_plan.md` ya define que garantia inicia en Catalogo, se aplica en Ventas y se consulta en postventa.
- `docs/erp_ventas_pos_pedidos_arranque.md` ya contempla devoluciones con decision de inventario y trazabilidad por unidad.
- `docs/erp_almacen_sucursales_almacenes_arranque.md` define `devoluciones` y `cuarentena` como almacenes tecnicos.
- `docs/erp_etiquetas_series_trazabilidad_diseno.md` indica que garantias/devoluciones deben validar codigo/unidad contra venta propia.

Brechas detectadas:

- Falta tabla de politicas de garantia.
- Falta tabla de reglas/asignaciones de politicas.
- Falta resolver de garantia vigente por SKU.
- Falta snapshot de garantia por partida vendida.
- Falta entidad de reclamo/caso de garantia.
- Falta historial de eventos del reclamo.
- Falta adjuntos/evidencia del reclamo.
- Falta contrato formal con Almacen para entrada a cuarentena/devoluciones desde reclamo.
- Falta seguimiento de garantia con proveedor cuando aplique.

Decision de arquitectura:

- Crear dominio formal de Garantias para politicas, elegibilidad, reclamos, eventos y adjuntos.
- Mantener Catalogo como dueno operativo de la asignacion de politicas por producto/SKU/categoria/marca/proveedor.
- Usar Ventas solo para snapshot y para conectar una resolucion con devolucion/reembolso/nota cuando aplique.
- Usar Almacen/Inventario para todo movimiento fisico.
- Usar Proveedores solo como seguimiento externo cuando la politica o decision lo requiera.

Riesgo si se intenta resolver solo con devoluciones:

- Se perderia diagnostico, evidencia, requisitos, autorizaciones, proveedor, vencimiento de garantia y razon formal del rechazo.
- No se podria medir calidad por SKU/proveedor ni tiempos de resolucion.
- El POS terminaria decidiendo reglas que pertenecen a Catalogo/Postventa.

### GAR-T002 - Diseno de modulo y permisos

Objetivo: definir si se crea controlador/modelo `Garantias` o si se implementa como subdominio dentro de Ventas/Postventa.

Recomendacion inicial:

- crear dominio `Garantias` para politicas, elegibilidad y reclamos;
- permitir que Catalogo consuma endpoints de politicas;
- permitir que Ventas/POS consuma resolver/snapshot/consulta;
- permitir que Almacen consuma recepcion/cuarentena por reclamo.

Permisos sugeridos:

- `garantias.ver`
- `garantias.politicas`
- `garantias.reclamos.crear`
- `garantias.reclamos.resolver`
- `garantias.autorizar`
- `garantias.adjuntos`
- `garantias.reportes`

Cierre:

- permisos definidos antes de endpoints sensibles;
- roles afectados documentados;
- sin implementar permisos hasta autorizar esquema/semillas.

#### Resultado GAR-T002

Fecha: 2026-06-27  
Estado: completado como diseno; no se agregaron permisos ni controlador.

Decision:

- Crear modulo formal `Garantias`.
- Agregar controlador futuro `Garantias.php`.
- Agregar modelo futuro `GarantiasErp.php`.
- Agregar esquema futuro `GarantiasEsquema.php`.
- Incluir `Garantias` en controladores protegidos de `Core.php` cuando se cree el controlador.
- Usar endpoints de Garantias desde Catalogo, Ventas/POS, Almacen/Inventario y Proveedores, sin duplicar reglas.

Permisos definitivos propuestos:

- `garantias.ver`: consultar politicas, snapshots, elegibilidad y reclamos.
- `garantias.politicas`: crear, editar, activar o desactivar politicas/reglas de garantia.
- `garantias.reclamos.crear`: iniciar reclamos desde venta, ticket, cliente o unidad.
- `garantias.reclamos.resolver`: diagnosticar y resolver reclamos operativos.
- `garantias.autorizar`: autorizar excepciones, devoluciones sensibles, cambios fuera de regla o rechazos especiales.
- `garantias.adjuntos`: cargar/cancelar evidencias.
- `garantias.reportes`: consultar indicadores y reportes de garantias.

Roles sugeridos:

- `direccion`: `garantias.ver`, `garantias.autorizar`, `garantias.reportes`.
- `administrador_erp`: todos los permisos de garantias.
- `catalogo_productos`: `garantias.ver`, `garantias.politicas`.
- `ventas`: `garantias.ver`, `garantias.reclamos.crear`.
- `almacen`: `garantias.ver`.
- `inventario`: `garantias.ver`.
- `proveedores`: `garantias.ver`.
- `auditor`: `garantias.ver`, `garantias.reportes`.
- `solo_lectura`: `garantias.ver`.

Reglas de seguridad:

- Toda accion POST debe pasar CSRF como el resto del ERP.
- Toda accion que cambie politica, reclamo, evidencia o decision debe registrar auditoria explicita.
- Las semillas de permisos pertenecen a `SeguridadEsquema.php`, pero no se deben modificar/aplicar sin autorizacion de esquema/semillas.
- El modulo debe respetar `docs/erp_estandar_documentacion_codigo.md` en cada metodo nuevo.

Primeros endpoints propuestos:

- `Garantias/politicas_listar_erp`
- `Garantias/politica_guardar_erp`
- `Garantias/politica_regla_guardar_erp`
- `Garantias/resolver_sku_erp`
- `Garantias/venta_snapshot_dryrun_erp`
- `Garantias/elegibilidad_consultar_erp`
- `Garantias/reclamo_dryrun_erp`
- `Garantias/reclamo_guardar_erp`
- `Garantias/reclamo_evento_guardar_erp`
- `Garantias/reclamo_adjunto_subir_erp`
- `Garantias/reportes_resumen_erp`

### GAR-T003 - Politicas de garantia desde Catalogo

Objetivo: construir la configuracion base que Catalogo necesita para indicar que garantia aplica a un SKU.

Tareas:

- refinar DDL de `erp_garantias_politicas`;
- refinar DDL de `erp_garantias_politicas_reglas`;
- definir precedencia:
  1. SKU;
  2. producto;
  3. categoria;
  4. marca;
  5. proveedor;
  6. politica por defecto o `sin_garantia`.
- definir vigencia por fecha, canal y almacen si aplica;
- disenar UI de Catalogo sin saturar el modal principal.

Cierre:

- DDL propuesto;
- resolver de politica vigente especificado;
- documentado que Catalogo no gestiona reclamos.

#### Resultado GAR-T003

Fecha: 2026-06-27  
Estado: completado como propuesta; DDL no ejecutado.

Archivo de DDL propuesto:

- `docs/erp_garantias_ddl_propuesto.sql`

Tablas propuestas:

- `erp_garantias_politicas`
- `erp_garantias_politicas_reglas`
- `erp_ventas_detalle_garantias`
- `erp_garantias_reclamos`
- `erp_garantias_reclamos_eventos`
- `erp_garantias_adjuntos`
- `erp_garantias_proveedor_seguimiento`

Decision sobre Catalogo:

- Catalogo no debe guardar reclamos.
- Catalogo solo debe mostrar/configurar la politica asignada al producto/SKU y alertar si falta.
- El modal de producto no debe saturarse con todo el expediente de garantia; la recomendacion UX es una seccion compacta o pestaña de `Garantia`, con:
  - politica vigente;
  - origen de regla;
  - vigencia;
  - requisitos principales;
  - boton/enlace a administrar politicas, solo con permiso `garantias.politicas`.

Precedencia de reglas:

1. SKU.
2. Producto.
3. Categoria.
4. Marca.
5. Proveedor.
6. Politica por defecto o `sin_garantia`.

Reglas:

- La precedencia se resuelve por `prioridad`, `ambito`, vigencia, canal y almacen.
- Si dos reglas empatan, el backend debe bloquear o devolver advertencia administrativa; no debe elegir al azar.
- Si no hay politica, el resolver devuelve `sin_garantia` y, si la categoria/marca requiere garantia, genera alerta de configuracion.
- Cambiar una politica en Catalogo no cambia snapshots de ventas pasadas.

### GAR-T004 - Resolver garantia vigente por SKU

Objetivo: crear el contrato que otros modulos usaran para consultar garantia sin conocer reglas internas de Catalogo.

Contrato esperado:

- entrada: `id_sku`, canal, almacen/sucursal opcional y fecha;
- salida: politica aplicable, duracion, requisitos, coberturas, exclusiones y origen de la regla;
- si no hay politica: devolver `sin_garantia` o alerta de configuracion segun regla.

Cierre:

- respuesta JSON documentada;
- pruebas con SKU con politica directa, por categoria y sin politica;
- sin depender de POS para calcular reglas.

#### Resultado GAR-T004

Fecha: 2026-06-27  
Estado: completado como contrato; no se implemento endpoint.

Contrato de entrada:

- `id_sku_erp`: requerido.
- `id_producto_erp`: opcional si el SKU no se encontro completo.
- `id_categoria`: opcional para resolver fallback.
- `id_marca`: opcional para resolver fallback.
- `id_proveedor`: opcional para reglas por proveedor.
- `canal`: opcional, por ejemplo `pos`, `ecommerce`, `mayoreo`.
- `id_almacen`: opcional.
- `fecha`: opcional; por defecto fecha actual.

Contrato de salida:

```json
{
  "error": false,
  "tipo": "success",
  "mensaje": "Garantia resuelta",
  "depurar": {
    "id_sku_erp": 0,
    "politica": {
      "id_garantia_politica": 0,
      "codigo": "SIN_GARANTIA",
      "nombre": "Sin garantia",
      "tipo_garantia": "sin_garantia",
      "duracion_valor": 0,
      "unidad_duracion": "dias",
      "coberturas": [],
      "requisitos": [],
      "exclusiones": []
    },
    "regla": {
      "id_regla_garantia": 0,
      "ambito": "sku",
      "id_referencia": 0,
      "prioridad": 10,
      "origen": "sku"
    },
    "snapshot_sugerido": {
      "fecha_inicio": "AAAA-MM-DD",
      "fecha_vencimiento": null,
      "resumen_ticket": "Sin garantia"
    },
    "alertas": []
  }
}
```

Bloqueos/alertas esperadas:

- `sku_no_encontrado`
- `politica_no_configurada`
- `reglas_duplicadas_misma_prioridad`
- `politica_inactiva`
- `vigencia_no_aplica`
- `requiere_serie_sin_trazabilidad`
- `requiere_lote_sin_regla_inventario`

Uso por modulo:

- Catalogo: mostrar politica vigente y alertas de configuracion.
- Ventas/POS: pedir snapshot al confirmar venta.
- Ticket: imprimir resumen congelado.
- Postventa/Garantias: consultar elegibilidad contra snapshot.
- Almacen: no resuelve politica; solo consulta reclamo y destino fisico.

Pruebas minimas futuras:

- SKU con politica directa.
- SKU sin politica directa pero con categoria.
- SKU con marca/proveedor.
- SKU con dos reglas empatadas.
- SKU sin garantia.
- Cambio de politica despues de venta: snapshot anterior no cambia.

### GAR-T005 - Snapshot de garantia en venta

Objetivo: congelar lo prometido al cliente al momento de la venta.

Tareas:

- refinar DDL de `erp_ventas_detalle_garantias`;
- enganchar snapshot al cierre de venta;
- guardar fechas de inicio/vencimiento;
- guardar texto/resumen que pueda imprimirse en ticket;
- no recalcular retroactivamente garantias de ventas pasadas.

Cierre:

- venta nueva conserva snapshot aunque despues cambie la politica del Catalogo;
- POS muestra garantia desde snapshot, no desde politica viva.

### GAR-T006 - Consulta de garantia y elegibilidad

Objetivo: permitir que el operador consulte rapidamente si un producto vendido puede entrar a garantia.

Busqueda por:

- folio/ticket;
- cliente;
- SKU;
- codigo de unidad/serie/etiqueta interna;
- venta historica.

Debe mostrar:

- politica vendida;
- vigencia;
- dias restantes o vencida;
- requisitos;
- exclusiones;
- acciones disponibles segun permiso.

Cierre:

- consulta de solo lectura lista;
- mensajes claros para productos vencidos, sin garantia o sin ticket cuando el ticket sea obligatorio.

### GAR-T007 - Reclamos de garantia

Objetivo: registrar y seguir casos postventa sin borrar historial.

Estatus sugeridos:

- `borrador`
- `recibido`
- `en_revision`
- `pendiente_cliente`
- `pendiente_proveedor`
- `autorizado`
- `rechazado`
- `resuelto`
- `cancelado`

Decisiones sugeridas:

- `cambio_producto`
- `reparacion`
- `devolucion_dinero`
- `nota_credito`
- `enviar_proveedor`
- `rechazo`
- `sin_accion`

Cierre:

- tablas de reclamo, eventos y adjuntos propuestas;
- flujo auditable;
- permisos por accion definidos.

### GAR-T008 - Integracion con Almacen/Inventario

Objetivo: que el producto fisico entre a revision sin contaminar existencia disponible.

Tareas:

- definir contrato para enviar producto a `cuarentena` o `devoluciones`;
- validar unidad, serie, lote o etiqueta interna si aplica;
- vincular movimiento de inventario con reclamo;
- impedir reingreso a disponible sin decision de Almacen.

Cierre:

- handoff documentado para Almacen/Inventario;
- no tocar inventario desde POS directamente;
- movimientos quedan trazables al reclamo.

### GAR-T009 - Integracion con Proveedores

Objetivo: controlar garantias que dependen del proveedor o fabricante.

Tareas:

- definir si se agrega tabla propia de seguimiento proveedor o se maneja como eventos del reclamo;
- guardar proveedor relacionado, folio externo, fecha de envio, fecha respuesta y decision;
- relacionar costo/recuperacion si despues participa Costos/Rentabilidad.

Cierre:

- proveedor no modifica garantia al cliente;
- se puede medir proveedor con mas reclamos o rechazos.

### GAR-T010 - Reportes y calidad operativa

Objetivo: convertir reclamos en informacion accionable.

Reportes minimos:

- reclamos por SKU;
- reclamos por marca;
- reclamos por proveedor;
- reclamos por categoria;
- costo por tipo de resolucion;
- productos con alta incidencia;
- reclamos vencidos o sin atencion;
- tiempos promedio de resolucion.

Cierre:

- indicadores definidos;
- filtros por fecha, sucursal/canal y estatus;
- datos salen de reclamos, eventos, ventas e inventario, no de capturas manuales sueltas.

## Riesgos principales

- Mezclar garantia con devolucion y perder trazabilidad del diagnostico.
- Recalcular garantias antiguas con politicas nuevas.
- Permitir que POS reingrese inventario sin revision fisica.
- Crear reglas de garantia directamente en Ventas y duplicar Catalogo.
- No distinguir garantia de tienda, proveedor y fabricante.
- No capturar evidencia suficiente para rechazos o reclamos a proveedor.
- Crear estados informales que despues no se puedan auditar.

## Propuesta de primer bloque ejecutable

Antes de implementar pantallas, ejecutar estas tareas en orden:

1. GAR-T001 - Auditoria de esquema y contratos existentes.
2. GAR-T002 - Diseno de modulo y permisos.
3. GAR-T003 - Politicas de garantia desde Catalogo.
4. GAR-T004 - Resolver garantia vigente por SKU.

No se debe avanzar a snapshot en venta hasta que el resolver de garantia este definido.

No se debe avanzar a reclamos hasta que exista consulta de elegibilidad.

No se debe mover inventario hasta que Almacen/Inventario tenga contrato documentado.

## Handoff / continuidad

Fecha: 2026-06-27

- Contexto actual: el archivo define la vision de garantias y ya existe esqueleto tecnico inicial del modulo; falta aplicar esquema y permisos en BD.
- Cambios recientes: se agrego plan de tareas por fases, separacion estricta entre Catalogo, Ventas/Postventa, Almacen/Inventario y Proveedores, y preparacion tecnica read-only/dry-run.
- Decisiones: Catalogo define politicas; Ventas guarda snapshot; Garantias/Postventa gestiona reclamos; Almacen decide destino fisico; Proveedores da seguimiento externo.
- Pendientes: respaldo externo, autorizacion DDL, aplicacion de permisos, auditoria post-DDL y UI de politicas.
- Impacta a: Catalogo ERP, Ventas/POS, Almacen/Inventario, Proveedores, Reportes y Costos/Rentabilidad.
- Siguiente paso recomendado: autorizar o posponer aplicacion de DDL/permisos; si se pospone, avanzar solo con documentacion/UI mock sin escritura.

## Avance tecnico inicial

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: preparado en codigo; esquema y permisos aun no aplicados en BD.

### Archivos preparados

- `app/controladores/Garantias.php`
- `app/modelos/GarantiasErp.php`
- `app/modelos/GarantiasEsquema.php`
- `app/core/Core.php`
- `app/modelos/SeguridadEsquema.php`
- `docs/erp_garantias_ddl_propuesto.sql`

### Que quedo listo

- Controlador protegido `Garantias`.
- Auditoria read-only del esquema de garantias:
  - `Garantias/esquema_auditar_garantias_erp`
  - permiso requerido: `sistema.soporte`, porque los permisos de garantias todavia no existen aplicados en BD.
- Plan DDL dry-run/controlado:
  - `Garantias/esquema_actualizar_garantias_erp`
  - permiso requerido: `sistema.soporte`.
  - `ejecutar=0`: genera SQL sin aplicar.
  - `ejecutar=1`: exige `autorizar=GARANTIAS_DDL_BASE` y respaldo externo fuera del proyecto.
- Resolver inicial de garantia por SKU:
  - `Garantias/resolver_sku_erp`
  - permiso requerido futuro: `garantias.ver`.
  - si falta esquema, devuelve `sin_garantia` con alerta `esquema_pendiente`.
- Dry-run de reclamo:
  - `Garantias/reclamo_dryrun_erp`
  - permiso requerido futuro: `garantias.reclamos.crear`.
  - no crea reclamo, no crea devolucion y no mueve inventario.
- Permisos base propuestos en `SeguridadEsquema.php`, aun no aplicados a BD:
  - `garantias.ver`
  - `garantias.politicas`
  - `garantias.reclamos.crear`
  - `garantias.reclamos.resolver`
  - `garantias.autorizar`
  - `garantias.adjuntos`
  - `garantias.reportes`
- `Core.php` protege el nuevo controlador y registra auditoria explicita para POST sensibles.

### Verificacion realizada

- Sintaxis PHP valida:
  - `app/modelos/GarantiasEsquema.php`
  - `app/modelos/GarantiasErp.php`
  - `app/controladores/Garantias.php`
  - `app/core/Core.php`
  - `app/modelos/SeguridadEsquema.php`
- Auditoria CLI read-only:
  - Resultado: `warning`.
  - Pendientes: 7 tablas faltantes.
- Plan DDL CLI dry-run:
  - Total: 7.
  - Existentes: 0.
  - Pendientes: 7.
  - Ejecutadas: 0.
  - Errores: 0.

### Bloqueo antes de continuar a operacion

Para usar Garantias en UI/operacion falta autorizacion explicita para:

1. Respaldo externo de BD fuera del proyecto.
2. Aplicar DDL base de garantias.
3. Aplicar semillas de permisos de `SeguridadEsquema.php`.
4. Validar auditoria post-DDL.

Sin esos pasos, los endpoints operativos de `garantias.ver` y `garantias.reclamos.crear` deben considerarse preparados pero no utilizables por roles normales.

## Avance tecnico previo a integracion con Ventas/Almacen

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: contratos dry-run preparados; sin escritura en BD.

### Contratos nuevos

- `Garantias/venta_snapshot_dryrun_erp`
  - Simula los snapshots que Ventas/POS debera guardar por partida.
  - No crea venta.
  - No guarda snapshot.
  - No mueve inventario.
  - Si falta esquema, bloquea con `Esquema de Garantias ERP pendiente de autorizacion y respaldo externo`.
- `Garantias/elegibilidad_consultar_erp`
  - Consulta preliminar de elegibilidad sin crear reclamo.
  - Si falta esquema, bloquea con `esquema_pendiente`.
  - Cuando exista esquema, debe consultar `erp_ventas_detalle_garantias`.

### Handoffs creados

- `docs/erp_garantias_handoff_ventas_pos.md`
- `docs/erp_garantias_handoff_almacen_inventario.md`

### Documentos operativos preparados

- `docs/erp_garantias_runbook_aplicacion.md`
- `docs/erp_garantias_uat_plan.md`
- `docs/erp_garantias_politicas_iniciales.md`
- `docs/erp_garantias_solicitud_autorizacion.md`
- `docs/erp_garantias_preflight_autorizacion.md`
- `docs/erp_garantias_permisos_plan.md`

### Verificacion adicional

- Sintaxis PHP valida:
  - `app/modelos/GarantiasErp.php`
  - `app/controladores/Garantias.php`
  - `app/core/Core.php`
- Prueba CLI de `ventaSnapshotDryRun`:
  - Resultado: `warning`.
  - Bloqueo esperado: esquema pendiente.
  - Contrato confirmado: no crea venta, no guarda snapshot, no mueve inventario.
- Prueba CLI de `elegibilidadConsultar`:
  - Resultado: `warning`.
  - Bloqueo esperado: esquema pendiente.
  - Contrato confirmado: no crea reclamo.

### Siguiente punto de autorizacion

Ya no conviene avanzar a UI real ni integracion con POS hasta aplicar:

1. DDL base de Garantias.
2. Semillas de permisos.
3. Auditoria post-DDL.

Sin eso, cualquier pantalla operativa quedaria bloqueada por permisos o por tablas faltantes.

## Preparacion operativa previa a autorizacion

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: completado; no se ejecuto DDL.

Se agregaron documentos para que la siguiente autorizacion no dependa del chat:

- Runbook de aplicacion: pasos de auditoria, dry-run, aplicacion autorizada, auditoria posterior y validacion.
- Plan UAT: casos minimos para esquema, resolver SKU, precedencia, snapshot, elegibilidad y reclamos dry-run.
- Politicas iniciales sugeridas: plantillas para arrancar sin inventar reglas por SKU.

Siguiente autorizacion requerida:

- Aplicar DDL base de Garantias con `GARANTIAS_DDL_BASE`.
- Aplicar permisos/semillas de Seguridad.
- Validar auditoria post-DDL.

Hasta que eso ocurra, el trabajo restante seguro es solo documentacion adicional o refinamiento de decisiones; no conviene seguir agregando UI operativa.

## Cierre de preparacion previa

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: listo para autorizacion; sin cambios de BD.

Se agregaron los ultimos documentos necesarios antes de aplicar cambios:

- Solicitud de autorizacion: alcance, token, evidencia y criterio de no ejecucion.
- Preflight de autorizacion: checklist previo para respaldo, auditoria, dry-run, permisos y ventana operativa.
- Plan de permisos: definicion de permisos y roles sugeridos.

Conclusion:

- No queda una tarea tecnica util que convenga ejecutar antes de autorizacion.
- La siguiente accion debe ser decidir si se autoriza o no el DDL base de Garantias.
- Si se autoriza, seguir `docs/erp_garantias_runbook_aplicacion.md`.
- Si no se autoriza todavia, el modulo queda en modo preparado/documentado.

## Preflight read-only ejecutado

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: ejecutado sin aplicar DDL.

Resultado:

- Auditoria: `warning`, 7 tablas faltantes esperadas.
- Dry-run DDL: total 7, pendientes 7, ejecutadas 0, errores 0.
- Disponibilidad: `disponible=false`.

Tablas faltantes:

- `erp_garantias_politicas`
- `erp_garantias_politicas_reglas`
- `erp_ventas_detalle_garantias`
- `erp_garantias_reclamos`
- `erp_garantias_reclamos_eventos`
- `erp_garantias_adjuntos`
- `erp_garantias_proveedor_seguimiento`

Conclusion:

- No hay mas acciones tecnicas utiles antes de autorizacion.
- Siguiente paso: respaldo externo y autorizacion para aplicar `GARANTIAS_DDL_BASE`.

## DDL base aplicado

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: aplicado con respaldo externo autorizado.

Autorizacion recibida:

- Token: `GARANTIAS_DDL_BASE`.
- Respaldo externo generado:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260627_antes_garantias_ddl_base.sql`
  - Tamano: `27248654` bytes.

Resultado DDL:

- Total: 7.
- Existentes antes: 0.
- Pendientes despues: 0.
- Ejecutadas: 7.
- Errores: 0.

Auditoria post-DDL:

- `tipo=success`.
- `tiene_pendientes=false`.
- `pendientes=[]`.
- `disponible=true`.

Permisos/semillas:

- `SeguridadEsquema::planActualizarSeguridad(true)` ejecutado.
- Permisos de Garantias encontrados: 7.
- Asignaciones rol-permiso encontradas: 20.

Validaciones post-DDL:

- Resolver SKU real `id_sku=7`, `sku=ART.10198`:
  - responde sin error tecnico;
  - devuelve `SIN_GARANTIA`;
  - alerta esperada: `politica_no_configurada`.
- Snapshot dry-run con SKU `7`:
  - `dry_run=true`;
  - sin bloqueos de esquema;
  - no crea venta;
  - no guarda snapshot;
  - no mueve inventario.
- Reclamo dry-run:
  - sin bloqueos de esquema;
  - no crea reclamo;
  - no crea devolucion;
  - no mueve inventario.

Hallazgo corregido:

- El resolver de Garantias asumio inicialmente `erp_catalogo_skus.id_producto`.
- El esquema real usa `id_producto_erp`, `id_marca_erp` e `id_categoria_erp`.
- Se corrigio `GarantiasErp::consultarContextoSku()` y la precedencia de reglas para usar el contrato real de Catalogo ERP.

Siguiente paso tecnico:

- Preparar endpoints de politicas/reglas en modo codigo.
- No insertar politicas iniciales sin autorizacion operativa posterior.

## Politicas y reglas en modo dry-run

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: preparado en codigo; no se insertaron politicas.

Endpoints preparados:

- `Garantias/politicas_listar_erp`
  - Read-only.
  - Resultado actual esperado: lista vacia.
- `Garantias/politica_dryrun_erp`
  - Valida codigo, nombre, tipo, duracion y unidad.
  - No crea ni actualiza politicas.
- `Garantias/politica_regla_dryrun_erp`
  - Valida politica, ambito, referencia, prioridad y vigencia.
  - No crea ni actualiza reglas.

Verificacion:

- Sintaxis PHP valida:
  - `app/modelos/GarantiasErp.php`
  - `app/controladores/Garantias.php`
  - `app/core/Core.php`
- `listarPoliticas()` devuelve `success` con `politicas=[]`.
- `politicaDryRun()` con politica tienda de 7 dias devuelve `success` y `no_crea_politica=true`.
- `politicaReglaDryRun()` con regla SKU `7` devuelve `success` y `no_crea_regla=true`.

Nuevo punto de autorizacion:

- Crear politicas iniciales reales.
- Crear reglas reales de asignacion.
- O habilitar endpoints persistentes `politica_guardar_erp` y `politica_regla_guardar_erp`.

Hasta autorizar eso, Garantias ya tiene esquema, permisos, resolver y dry-runs, pero no tiene politicas operativas configuradas.

## Guardado persistente preparado y bloqueado

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: codigo preparado; no se crearon politicas ni reglas reales.

Endpoints persistentes preparados:

- `Garantias/politica_guardar_erp`
- `Garantias/politica_regla_guardar_erp`

Candados:

- Permiso requerido: `garantias.politicas`.
- Token requerido: `GARANTIAS_POLITICAS_BASE`.
- Respaldo externo requerido fuera del proyecto.
- Auditoria explicita en `Core.php`.

Verificacion:

- Sintaxis PHP valida:
  - `app/modelos/GarantiasErp.php`
  - `app/controladores/Garantias.php`
  - `app/core/Core.php`
- `politicaDryRun()` valido para `SIN_GARANTIA`.
- `listarPoliticas()` devuelve lista vacia.
- Conteo en `erp_garantias_politicas`: `0`.

Nuevo punto de autorizacion:

- Crear las primeras politicas reales.
- Crear reglas reales de asignacion.
- Token sugerido: `GARANTIAS_POLITICAS_BASE`.

No continuar a UI operativa ni integracion con Catalogo/Ventas hasta tener al menos politicas base y reglas de prueba.

## Paquete de politicas base validado en dry-run

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: validado sin escritura; requiere autorizacion para persistir.

Script de apoyo:

- `storage/uat/uat_garantias_politicas_base_dryrun.php`

Politicas validadas:

- `SIN_GARANTIA`
- `GAR_TIENDA_7_DIAS_CAMBIO`
- `GAR_TIENDA_30_DIAS_DIAGNOSTICO`
- `GAR_PROVEEDOR_SEGUN_POLITICA`
- `GAR_FABRICANTE_SERIE`
- `CADUCIDAD_CALIDAD_LIMITADA`

Resultado:

- Las 6 politicas pasan `politicaDryRun()`.
- La regla UAT sugerida pasa `politicaReglaDryRun()`.
- No se insertaron politicas.
- No se insertaron reglas.
- No se modifico inventario.
- No se crearon reclamos.
- No se integro todavia con UI operativa.

Regla UAT sugerida:

- Politica esperada: `GAR_TIENDA_7_DIAS_CAMBIO`.
- Ambito: `sku`.
- Referencia: SKU `7`.
- Prioridad: `10`.
- Canal: `pos`.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\modelos\GarantiasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\controladores\Garantias.php`: sin errores.
- `C:\xampp\php\php.exe -l storage\uat\uat_garantias_politicas_base_dryrun.php`: sin errores.

Siguiente autorizacion necesaria:

```text
Autorizo crear politicas base de Garantias con token GARANTIAS_POLITICAS_BASE.
Respaldo externo: <ruta real fuera del proyecto>
Alcance: crear 6 politicas base y 1 regla UAT directa para SKU 7.
```

No continuar a reglas masivas por categoria, marca o proveedor hasta validar la regla UAT con un producto real.

## Script de aplicacion autorizado preparado

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: preparado; no ejecutado con token real.

Archivo:

- `storage/uat/uat_garantias_politicas_base_apply_authorized.php`

Contrato:

- Requiere `--token=GARANTIAS_POLITICAS_BASE`.
- Requiere `--respaldo=<ruta externa existente>`.
- Acepta `--sku-uat=<id_sku>`; por defecto usa SKU `7`.
- Crea o actualiza por codigo las 6 politicas base.
- Crea la regla UAT solo si no existe una regla equivalente.
- No crea ventas.
- No crea reclamos.
- No mueve inventario.

Protecciones agregadas:

- `GarantiasErp::guardarPolitica()` queda idempotente por `codigo`.
- `GarantiasErp::guardarPoliticaRegla()` valida esquema disponible.
- `GarantiasErp::guardarPoliticaRegla()` valida que la politica exista.
- `GarantiasErp::guardarPoliticaRegla()` evita duplicar una regla equivalente por politica, ambito, referencia, prioridad, canal, almacen, vigencia y estatus.

Validacion:

- `C:\xampp\php\php.exe -l app\modelos\GarantiasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l storage\uat\uat_garantias_politicas_base_apply_authorized.php`: sin errores.
- Ejecucion sin token: bloqueada con `Token de autorizacion invalido`.

Punto actual:

- Ya no quedan tareas seguras de configuracion base sin escritura real.
- Para continuar se requiere autorizacion de creacion de politicas base o cambiar el alcance a UI/consulta read-only.

## Politicas base aplicadas

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: aplicado con respaldo externo y token autorizado.

Autorizacion recibida:

- Token: `GARANTIAS_POLITICAS_BASE`.
- Alcance: crear 6 politicas base y 1 regla UAT directa para SKU `7`.

Respaldo externo:

- `C:\xampp\panel_db_backups\artianilocal_panel_20260627_antes_garantias_politicas_base.sql`
- Tamano: `27278453` bytes.

Script ejecutado:

- `storage/uat/uat_garantias_politicas_base_apply_authorized.php`

Resultado:

- `SIN_GARANTIA`: creada/guardada con `id_garantia_politica=1`.
- `GAR_TIENDA_7_DIAS_CAMBIO`: creada/guardada con `id_garantia_politica=2`.
- `GAR_TIENDA_30_DIAS_DIAGNOSTICO`: creada/guardada con `id_garantia_politica=3`.
- `GAR_PROVEEDOR_SEGUN_POLITICA`: creada/guardada con `id_garantia_politica=4`.
- `GAR_FABRICANTE_SERIE`: creada/guardada con `id_garantia_politica=5`.
- `CADUCIDAD_CALIDAD_LIMITADA`: creada/guardada con `id_garantia_politica=6`.
- Regla UAT creada:
  - `id_regla_garantia=1`.
  - politica: `GAR_TIENDA_7_DIAS_CAMBIO`.
  - ambito: `sku`.
  - referencia: SKU `7`.
  - prioridad: `10`.
  - canal: `pos`.

Validacion post-aplicacion:

- Script read-only:
  - `storage/uat/uat_garantias_politicas_base_post_apply_readonly.php`.
- Sintaxis PHP del script: sin errores.
- `listarPoliticas()` devuelve 6 politicas activas.
- `GAR_TIENDA_7_DIAS_CAMBIO` devuelve `reglas_activas=1`.
- Resolver SKU `7`:
  - SKU: `ART.10198`.
  - Producto: `Jaula hámster residencial rosa`.
  - Politica resuelta: `GAR_TIENDA_7_DIAS_CAMBIO`.
  - Regla origen: `sku`.
  - Fecha inicio: `2026-06-27`.
  - Fecha vencimiento sugerida: `2026-07-04`.
  - Alertas: ninguna.
- Snapshot dry-run:
  - `dry_run=true`.
  - `bloqueos=[]`.
  - no crea venta;
  - no guarda snapshot;
  - no mueve inventario.

Siguiente paso recomendado:

- Definir la UI/flujo administrativo para ver politicas y asignarlas a SKU/producto/categoria/marca/proveedor desde Catalogo/Garantias.
- Antes de asignar reglas masivas, validar con el SKU UAT que el comportamiento en POS/Postventa sea el esperado.

## Consola inicial de politicas y reglas

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: implementado como consulta operativa; sin edicion masiva.

Archivos:

- `app/controladores/Garantias.php`
- `app/modelos/GarantiasErp.php`
- `app/vistas/paginas/apps/erp/garantias/politicas.php`
- `public/assets/js/custom/apps/erp/garantias/politicas.js`
- `app/vistas/includes/header/sidebar.php`
- `storage/uat/uat_garantias_politicas_base_post_apply_readonly.php`

Rutas nuevas:

- `/garantias/politicas`
- `/garantias/politicas_reglas_listar_erp`

Alcance de la pantalla:

- Muestra conteo de politicas activas.
- Muestra conteo de reglas activas.
- Lista politicas base, duracion, requisitos, reglas activas y estatus.
- Lista reglas activas por politica, ambito, referencia, canal, prioridad y vigencia.
- Permite resolver garantia por SKU y canal.
- Permite simular snapshot por SKU y canal.

Contrato:

- La pantalla requiere `garantias.ver`.
- El endpoint de reglas es read-only.
- El snapshot sigue siendo dry-run.
- No crea politicas.
- No crea reglas.
- No crea ventas.
- No crea reclamos.
- No mueve inventario.

Validacion:

- `C:\xampp\php\php.exe -l app\modelos\GarantiasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\controladores\Garantias.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\garantias\politicas.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\includes\header\sidebar.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\garantias\politicas.js`: sin errores.
- UAT read-only actualizado:
  - lista 6 politicas;
  - lista 1 regla activa;
  - la regla activa corresponde a `GAR_TIENDA_7_DIAS_CAMBIO` por SKU `7`;
  - referencia visible: `ART.10198`;
  - resolver SKU `7` sigue devolviendo vencimiento `2026-07-04`.

Siguiente paso:

- Validar visualmente la pantalla en navegador con usuario que tenga `garantias.ver`.
- Despues decidir si la siguiente fase sera:
  - UI de alta/edicion de reglas individuales con respaldo/token;
  - integracion de snapshot real en Ventas/POS;
  - consulta de elegibilidad/reclamo en Postventa.

## Busqueda SKU y auditoria de cobertura

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: implementado como consulta/dry-run; sin asignaciones reales nuevas.

Archivos:

- `app/controladores/Garantias.php`
- `app/modelos/GarantiasErp.php`
- `app/vistas/paginas/apps/erp/garantias/politicas.php`
- `public/assets/js/custom/apps/erp/garantias/politicas.js`
- `storage/uat/uat_garantias_politicas_base_post_apply_readonly.php`

Rutas nuevas:

- `/garantias/skus_buscar_erp`
- `/garantias/cobertura_skus_erp`

Mejoras UI:

- La prueba por SKU ya no exige conocer el ID interno.
- Permite buscar por SKU/nombre/producto.
- Al seleccionar un SKU, llena el ID tecnico y ejecuta resolver.
- Muestra cobertura de garantias:
  - total SKUs activos;
  - SKUs con regla aplicable;
  - SKUs sin regla aplicable;
  - muestra corta de SKUs pendientes.
- Agrega panel `Validar regla nueva` en modo dry-run para usuarios con `garantias.politicas`.

Validacion read-only:

- Busqueda `ART.10198` devuelve `id_sku=7`.
- Cobertura actual:
  - `total_skus_activos=1730`.
  - `skus_con_regla=1`.
  - `skus_sin_regla=1729`.
- Dry-run de regla:
  - politica `GAR_TIENDA_7_DIAS_CAMBIO`;
  - ambito `sku`;
  - referencia `7`;
  - prioridad `10`;
  - resultado valido;
  - no crea regla;
  - no actualiza regla.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\modelos\GarantiasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\controladores\Garantias.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\garantias\politicas.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\garantias\politicas.js`: sin errores.
- UAT read-only actualizado ejecuta politicas, reglas, busqueda SKU, cobertura, dry-run de regla, resolver y snapshot.

Decision pendiente:

- No asignar automaticamente politicas a los 1729 SKUs sin regla.
- Primero definir reglas por categoria/marca/proveedor donde tenga sentido operativo.
- Para guardar reglas reales se debe seguir usando autorizacion, respaldo externo y token `GARANTIAS_POLITICAS_BASE`.

## Pendiente de definicion operativa: garantias y cambios

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: pendiente de entrevista operativa con el dueno del proceso.

### Lo definido tecnicamente hasta ahora

- La garantia no se debe recalcular desde Catalogo cuando el cliente regresa.
- Al confirmar una venta, Ventas/POS debe guardar un snapshot de la garantia prometida por partida.
- Ese snapshot debe incluir:
  - politica aplicada;
  - fecha de inicio;
  - fecha de vencimiento;
  - requisitos;
  - exclusiones;
  - resumen para ticket;
  - regla que resolvio la garantia.
- Garantias/Postventa debe consultar ese snapshot para decidir si un reclamo sigue vigente.
- Almacen/Inventario no decide si procede la garantia; solo recibe/mueve fisicamente el producto segun la resolucion autorizada.
- Catalogo/Garantias define politicas y reglas; no debe operar reclamos.

### Politicas base creadas

- `SIN_GARANTIA`.
- `GAR_TIENDA_7_DIAS_CAMBIO`.
- `GAR_TIENDA_30_DIAS_DIAGNOSTICO`.
- `GAR_PROVEEDOR_SEGUN_POLITICA`.
- `GAR_FABRICANTE_SERIE`.
- `CADUCIDAD_CALIDAD_LIMITADA`.

Estas politicas son plantillas iniciales, no significan que ya apliquen masivamente a todos los productos.

### Tema a resolver

Se necesita definir con precision la diferencia entre:

- garantia;
- cambio;
- devolucion de dinero;
- nota de credito;
- reparacion;
- envio/seguimiento con proveedor;
- rechazo de garantia;
- excepcion autorizada por supervisor/direccion.

Tambien falta decidir que condiciones deben bloquear o permitir cada resultado.

### Preguntas para continuar

#### 1. Politica comercial general

- En tu negocio, cuando dices "garantia", ¿normalmente significa cambio fisico por otro producto, reparacion, devolucion de dinero o revision caso por caso?
- ¿Hay productos que siempre son venta final?
- ¿Hay productos que solo aceptan cambio el mismo dia?
- ¿Hay productos que aceptan cambio dentro de 7 dias?
- ¿Hay productos que aceptan garantia de 30 dias o mas?
- ¿Quieres manejar garantia diferente para POS, ecommerce y mayoreo?
- ¿La fecha de inicio siempre debe ser la fecha de venta, o en algunos casos debe ser la fecha de entrega?

#### 2. Cambios

- ¿Un cambio requiere ticket obligatorio?
- ¿Un cambio requiere que el producto venga con empaque?
- ¿Un cambio requiere que el producto no haya sido usado?
- ¿Un cambio puede hacerse por otro SKU de igual precio?
- ¿Un cambio puede hacerse por otro SKU de mayor precio pagando diferencia?
- ¿Un cambio puede hacerse por otro SKU de menor precio generando saldo/nota?
- ¿Se permite cambiar productos comprados en promocion?
- ¿Se permite cambio si el cliente no esta registrado?
- ¿Se permite cambio parcial de una venta?

#### 3. Devoluciones y dinero

- ¿En que casos se devuelve dinero?
- ¿La devolucion de dinero requiere autorizacion especial?
- ¿La devolucion se hace al mismo metodo de pago?
- ¿Se permite devolver efectivo si la venta fue con tarjeta/transferencia?
- ¿Prefieres usar nota de credito/saldo a favor en lugar de devolver dinero?
- ¿Cuanto tiempo despues de la venta se permite devolucion?

#### 4. Garantia por defecto

- Si un producto no tiene politica configurada, ¿debe tratarse como `SIN_GARANTIA` o debe bloquear venta hasta definir garantia?
- ¿Hay categorias donde todos los productos pueden heredar una misma garantia?
- ¿Hay marcas o proveedores que deban definir garantia por defecto?
- ¿Hay productos que por ser vivos, alimento, medicamento, higiene o consumible tengan reglas especiales?

#### 5. Productos vivos, alimentos y consumibles

- Para peces/mascotas/seres vivos, ¿existe garantia?
- Si aplica, ¿por cuantas horas o dias?
- ¿Que evidencia se pide?
- Para alimento a granel o reempacado, ¿aceptas cambios?
- Para alimento abierto, ¿se acepta devolucion?
- Para productos con caducidad, ¿la garantia depende de lote/caducidad?
- Si el cliente reporta mala calidad, ¿se cambia, se revisa, se manda a proveedor o se rechaza?

#### 6. Productos con serie, equipos y accesorios

- ¿Que productos requieren numero de serie?
- ¿El sistema debe exigir capturar serie al vender?
- Si no se capturo serie al vender, ¿se permite garantia?
- ¿Requiere diagnostico antes de aprobar cambio?
- ¿Hay productos que se reparan antes de cambiar?
- ¿Quien decide si va a reparacion, cambio o proveedor?

#### 7. Requisitos y evidencias

- ¿Que evidencia minima se pide para iniciar reclamo?
- Ticket.
- Cliente identificado.
- Fotos.
- Video.
- Empaque.
- Numero de serie.
- Lote/caducidad.
- Diagnostico interno.
- Validacion de proveedor.
- ¿Se puede abrir un reclamo incompleto como borrador?

#### 8. Flujo operativo

- ¿Quien inicia el reclamo: ventas, caja, postventa o encargado?
- ¿Quien diagnostica?
- ¿Quien autoriza excepciones?
- ¿Quien decide el destino fisico del producto devuelto?
- ¿Quieres que un reclamo tenga folio propio?
- ¿Quieres estados formales como `abierto`, `en_revision`, `aprobado`, `rechazado`, `resuelto`, `cancelado`?
- ¿Debe haber historial de eventos y comentarios?

#### 9. Inventario y almacen

- Cuando entra producto por garantia, ¿debe ir a cuarentena/devoluciones hasta diagnostico?
- Si se cambia por otro producto, ¿el nuevo producto sale de inventario como venta/cambio?
- Si el producto defectuoso se manda a proveedor, ¿debe quedar separado de inventario disponible?
- Si se rechaza garantia, ¿el producto se devuelve al cliente o queda en tienda?
- Si se acepta devolucion pero el producto ya no es vendible, ¿va a merma?

#### 10. Proveedores

- ¿Hay proveedores que aceptan garantias formales?
- ¿Quieres registrar folio/RMA del proveedor?
- ¿Quieres medir tiempos de respuesta por proveedor?
- ¿Quieres que algunas politicas dependan del proveedor principal del SKU?
- Si el proveedor no responde, ¿quien absorbe el costo?

#### 11. Ticket y comunicacion al cliente

- ¿Quieres imprimir la garantia en el ticket?
- ¿Quieres que diga fecha limite?
- ¿Quieres que diga requisitos principales?
- ¿Quieres que algunos productos impriman `sin garantia`?
- ¿Quieres texto legal/comercial general para cambios y garantias?

#### 12. Excepciones

- ¿Quien puede autorizar garantia vencida?
- ¿Quien puede autorizar devolucion de dinero?
- ¿Quien puede autorizar cambio sin ticket?
- ¿Debe quedar motivo obligatorio?
- ¿Debe quedar evidencia obligatoria?

### Primeras decisiones recomendadas

Antes de crear reglas masivas:

1. Definir politica general de cambios de tienda.
2. Definir categorias sin garantia o con garantia limitada.
3. Definir categorias que requieren diagnostico.
4. Definir productos que requieren serie/lote/caducidad.
5. Definir si la garantia empieza en fecha de venta o entrega.
6. Definir estados del reclamo.
7. Definir destinos fisicos en Almacen para producto recibido por garantia.

### Siguiente sesion sugerida

Al regresar, iniciar por estas 5 respuestas minimas:

1. Dias generales para cambio en tienda.
2. Casos donde no hay garantia.
3. Si se devuelve dinero o se prefiere nota/saldo.
4. Quien autoriza excepciones.
5. Que productos requieren diagnostico antes de cambio.

Con esas respuestas se podra proponer el flujo formal de Garantias/Cambios y decidir que reglas se cargan primero.

## Enfoque recomendado: garantias configurables reutilizables

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: decision de diseno propuesta; pendiente de aterrizar UI completa.

### Problema

El dueno del negocio no siempre recordara todas las condiciones de garantia al momento de capturar o corregir productos.

No conviene crear reglas rigidas por producto ni obligar a definir todo desde el inicio, porque:

- hay muchos SKUs;
- algunas politicas aplican a muchos productos;
- algunas garantias dependen de categoria, proveedor, marca o canal;
- el criterio operativo puede madurar con el uso real;
- una persona de Catalogo no necesariamente conoce todas las reglas comerciales/postventa.

### Decision recomendada

Crear un catalogo de politicas de garantia configurables y reutilizables.

Despues, esas politicas se asignan por regla a:

- SKU especifico;
- producto maestro;
- categoria;
- marca;
- proveedor;
- canal de venta;
- almacen/sucursal si mas adelante aplica;
- vigencia temporal.

La politica debe existir una sola vez y poder usarse en muchos productos.

### Ejemplo de politicas configurables

#### Sin garantia

- Tipo: `sin_garantia`.
- Duracion: `0 dias`.
- Resultado permitido: ninguno.
- Uso: productos de venta final, liquidacion o casos donde no se ofrece garantia.

#### Cambio tienda 7 dias

- Tipo: `garantia_tienda`.
- Duracion: `7 dias`.
- Requiere ticket: si.
- Requiere empaque: configurable.
- Requiere diagnostico: configurable.
- Permite cambio: si.
- Permite devolucion dinero: no por defecto.
- Uso: productos generales donde la tienda acepta cambio rapido.

#### Diagnostico 30 dias

- Tipo: `garantia_tienda`.
- Duracion: `30 dias`.
- Requiere ticket: si.
- Requiere cliente: configurable.
- Requiere diagnostico: si.
- Permite cambio: si, solo tras diagnostico.
- Permite reparacion: configurable.
- Uso: equipos, accesorios electricos, filtros, bombas, calentadores.

#### Proveedor segun politica

- Tipo: `garantia_proveedor`.
- Duracion: configurable.
- Requiere ticket: si.
- Requiere validacion proveedor: si.
- Permite envio proveedor: si.
- Uso: productos donde la tienda no decide sola, sino que depende del proveedor/fabricante.

#### Caducidad/calidad limitada

- Tipo: `caducidad_calidad`.
- Duracion: configurable.
- Requiere lote: configurable.
- Requiere fotos: configurable.
- Requiere diagnostico: configurable.
- Uso: alimento, consumibles, productos con caducidad, calidad o lote.

### Campos configurables de una politica

Identidad:

- Codigo.
- Nombre.
- Descripcion.
- Tipo de garantia.
- Estatus.

Vigencia:

- Duracion.
- Unidad de duracion: dias/meses.
- Fecha de inicio de garantia:
  - venta;
  - entrega;
  - recepcion cliente;
  - captura manual, si algun caso futuro lo requiere.

Requisitos:

- Requiere ticket.
- Requiere cliente identificado.
- Requiere serie.
- Requiere lote.
- Requiere empaque.
- Requiere diagnostico.
- Requiere fotos/video.
- Requiere autorizacion de supervisor.
- Requiere validacion de proveedor.

Resultados permitidos:

- Permite cambio.
- Permite reparacion.
- Permite devolucion de dinero.
- Permite nota de credito/saldo.
- Permite envio a proveedor.
- Permite rechazo documentado.

Condiciones opcionales:

- Producto sin uso.
- Producto completo.
- Empaque completo.
- No aplica en liquidacion/promocion.
- No aplica en productos abiertos.
- No aplica en productos vivos.
- No aplica si hay mal uso.

Mensajes:

- Resumen para ticket.
- Texto corto para POS.
- Texto operativo para quien atiende el reclamo.
- Exclusiones visibles.

### Asignacion flexible

La asignacion debe tener precedencia:

1. SKU.
2. Producto.
3. Categoria.
4. Marca.
5. Proveedor.
6. Politica por defecto.

Reglas:

- Una politica por SKU gana contra categoria/marca.
- Una politica por categoria sirve para avanzar rapido sin tocar cada SKU.
- Una politica por proveedor sirve cuando el proveedor impone condiciones.
- Una regla puede aplicar solo a un canal, por ejemplo POS o ecommerce.
- Una regla puede tener vigencia temporal para promociones o pruebas.

### Flujo propuesto para operar sin rigidez

1. Crear politicas base reutilizables.
2. Asignar primero solo reglas puntuales de prueba.
3. Auditar cobertura de SKUs sin politica.
4. Proponer reglas por categoria/marca/proveedor.
5. Revisar ejemplos antes de aplicar masivo.
6. Aplicar reglas reales con respaldo y autorizacion.
7. Ventas guarda snapshot al vender.
8. Postventa usa el snapshot para reclamos.

### UI recomendada

Pantalla `Garantias > Politicas y reglas`:

- Listado de politicas.
- Conteo de reglas activas.
- Auditoria de cobertura.
- Buscador de SKU.
- Resolver garantia por SKU.
- Simular snapshot.
- Validar regla nueva en dry-run.

Siguiente etapa UI:

- Crear/editar politica configurable.
- Crear/editar regla individual.
- Previsualizar impacto antes de guardar:
  - cuantos SKUs cubriria;
  - ejemplos de SKUs afectados;
  - reglas que podria desplazar por precedencia.
- Aplicar solo con respaldo externo y autorizacion.

### Lo que no debe hacerse

- No asignar garantia SKU por SKU si una categoria puede cubrirlo.
- No crear politicas duplicadas con nombres distintos pero misma regla.
- No hacer que POS decida garantias.
- No cambiar ventas historicas cuando cambie una politica.
- No mover inventario desde Garantias.
- No aplicar reglas masivas sin vista previa.

### Plan de implementacion sugerido

#### Fase 1 - Configuracion segura

- Mantener politicas base.
- Agregar CRUD de politicas configurable.
- Agregar CRUD de reglas individuales.
- Mantener dry-run y vista previa obligatoria.

#### Fase 2 - Asignacion asistida

- Sugerir reglas por categoria.
- Sugerir reglas por marca.
- Sugerir reglas por proveedor.
- Mostrar impacto antes de aplicar.

#### Fase 3 - Venta

- Integrar snapshot real al confirmar venta.
- Mostrar resumen de garantia en POS.
- Preparar texto para ticket.

#### Fase 4 - Reclamos

- Crear reclamo desde venta/ticket/SKU.
- Validar vigencia.
- Validar requisitos.
- Registrar diagnostico, evidencia y decision.

#### Fase 5 - Almacen/Inventario

- Enviar producto recibido por garantia a cuarentena/devoluciones.
- Registrar destino final:
  - reingreso;
  - merma;
  - proveedor;
  - reparacion;
  - devolucion al cliente.

### Decision actual

El modulo debe avanzar como sistema configurable de politicas reutilizables, no como lista cerrada de garantias rigidas.

Esto permite empezar simple y madurar reglas conforme aparezcan casos reales.

## Avance Fase 1: formulario configurable en dry-run

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: implementado sin guardado real desde UI.

Archivos:

- `app/modelos/GarantiasErp.php`
- `app/vistas/paginas/apps/erp/garantias/politicas.php`
- `public/assets/js/custom/apps/erp/garantias/politicas.js`
- `storage/uat/uat_garantias_politicas_base_post_apply_readonly.php`

Alcance:

- Se agrego panel `Configurar politica` para usuarios con `garantias.politicas`.
- Permite capturar:
  - codigo;
  - nombre;
  - descripcion;
  - tipo de garantia;
  - duracion;
  - unidad de duracion;
  - requisitos;
  - resultados permitidos.
- Permite cargar una politica existente como base desde el listado.
- Permite limpiar el formulario.
- Permite validar la politica con `politica_dryrun_erp`.

Contrato:

- No guarda politicas.
- No actualiza politicas.
- No crea reglas.
- No requiere respaldo porque no escribe BD.
- El guardado real sigue protegido por:
  - permiso `garantias.politicas`;
  - token `GARANTIAS_POLITICAS_BASE`;
  - respaldo externo fuera del proyecto.

Validacion:

- `C:\xampp\php\php.exe -l app\modelos\GarantiasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\controladores\Garantias.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\garantias\politicas.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\garantias\politicas.js`: sin errores.
- UAT read-only valida `GAR_PRUEBA_CONFIGURABLE` con:
  - tipo `garantia_tienda`;
  - duracion `15 dias`;
  - requiere ticket;
  - requiere diagnostico;
  - permite cambio;
  - resultado `success`;
  - no crea politica.

## Avance Fase 1: vista previa de impacto de reglas

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: implementado en dry-run/read-only.

Archivos:

- `app/controladores/Garantias.php`
- `app/modelos/GarantiasErp.php`
- `app/vistas/paginas/apps/erp/garantias/politicas.php`
- `public/assets/js/custom/apps/erp/garantias/politicas.js`
- `storage/uat/uat_garantias_politicas_base_post_apply_readonly.php`

Alcance:

- Se agrego endpoint `politica_regla_impacto_erp`.
- Se agrego boton `Impacto` en la seccion de validacion de regla.
- La vista previa calcula:
  - SKUs afectados;
  - SKUs que ya tienen alguna regla activa;
  - SKUs sin regla actual;
  - reglas existentes del mismo ambito;
  - ejemplos de SKUs afectados;
  - advertencias operativas.
- Ambitos soportados para impacto:
  - `sku`;
  - `producto`;
  - `categoria`;
  - `marca`;
  - `proveedor`.

Decision:

- Las reglas por `proveedor` quedan permitidas solo como configuracion evaluable, pero deben usarse con cuidado porque el flujo de resolucion necesita conocer el proveedor de la partida o el proveedor principal vigente del SKU.
- Antes de guardar reglas masivas por categoria, marca o proveedor, el operador debe revisar la vista previa de impacto.

Contrato:

- No crea reglas.
- No actualiza reglas.
- No asigna politicas.
- No requiere respaldo porque no escribe BD.
- El guardado real de reglas sigue protegido por:
  - permiso `garantias.politicas`;
  - token `GARANTIAS_POLITICAS_BASE`;
  - respaldo externo fuera del proyecto.

Validacion:

- `C:\xampp\php\php.exe -l app\modelos\GarantiasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\controladores\Garantias.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\garantias\politicas.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\garantias\politicas.js`: sin errores.
- UAT read-only `regla_impacto_categoria_dryrun`:
  - resultado `success`;
  - mensaje `Vista previa de impacto generada`;
  - SKUs afectados: `313`;
  - SKUs con alguna regla actual: `0`;
  - SKUs sin regla actual: `313`;
  - no crea regla.

Siguiente paso recomendado:

- Probar visualmente la pantalla `Garantias > Politicas y reglas` en navegador.
- Despues, habilitar guardado real individual de politica/regla desde UI solo con respaldo externo y token.

## Avance Fase 1: ayudas operativas en vista de politicas

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: implementado sin cambios de esquema.

Archivo:

- `app/vistas/paginas/apps/erp/garantias/politicas.php`

Alcance:

- Se agregaron textos de ayuda en `Configurar politica`.
- Se explico cada requisito:
  - `Ticket`: debe existir comprobante de venta.
  - `Cliente`: requiere identificar al cliente.
  - `Serie`: se valida numero de serie/equipo.
  - `Lote`: se revisa lote, caducidad o trazabilidad.
  - `Empaque`: debe conservar empaque o accesorios.
  - `Diagnostico`: requiere revision antes de decidir.
  - `Fotos`: debe anexar evidencia visual.
  - `Autorizacion`: requiere aprobacion de supervisor.
  - `Proveedor`: depende de respuesta externa.
- Se explicaron resultados permitidos:
  - cambio;
  - reparacion;
  - devolucion;
  - nota de credito;
  - envio a proveedor.
- Se aclaro en UI que una politica puede tener varias reglas.
- Se renombro visualmente `Ambito` a `Asignar por` para que sea evidente que la asignacion puede ser por:
  - SKU;
  - producto;
  - categoria;
  - marca;
  - proveedor.

Decision:

- Si un producto/SKU coincide con mas de una regla, no se deben aplicar varias garantias al mismo tiempo.
- El resolver debe elegir una politica aplicable siguiendo:
  1. especificidad del ambito;
  2. prioridad numerica, donde menor numero gana;
  3. vigencia/canal cuando aplique.
- Esto permite tener una regla general por categoria y una excepcion puntual por SKU sin duplicar politicas.

Contrato:

- Estos cambios solo documentan y aclaran la operacion en la vista.
- No crean politicas.
- No crean reglas.
- No modifican BD.

## Avance Fase 1: buscador de referencias para asignacion de reglas

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: implementado en read-only/dry-run.

Archivos:

- `app/controladores/Garantias.php`
- `app/modelos/GarantiasErp.php`
- `app/vistas/paginas/apps/erp/garantias/politicas.php`
- `public/assets/js/custom/apps/erp/garantias/politicas.js`
- `storage/uat/uat_garantias_politicas_base_post_apply_readonly.php`

Alcance:

- Se agrego endpoint `referencias_buscar_erp`.
- Se agrego buscador `Buscar referencia` en `Validar regla nueva`.
- El buscador respeta el valor de `Asignar por`.
- Permite buscar referencias para:
  - SKU;
  - producto;
  - categoria;
  - marca;
  - proveedor.
- Al seleccionar una referencia, la UI llena automaticamente `ID referencia`.
- El ID sigue visible para auditoria y para validar el impacto antes del guardado real.

Decision:

- No se debe exigir al operador conocer IDs internos.
- La asignacion por categoria, marca, proveedor o producto debe iniciar con busqueda contextual y despues pasar por `Validar` o `Impacto`.
- El guardado real seguira separado y protegido por respaldo externo/token.

Contrato:

- No crea reglas.
- No crea politicas.
- No modifica Catalogo.
- No modifica Proveedores.
- Solo consulta referencias candidatas.

Validacion:

- `C:\xampp\php\php.exe -l app\modelos\GarantiasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\controladores\Garantias.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\garantias\politicas.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\garantias\politicas.js`: sin errores.
- UAT read-only:
  - `buscar_referencia_sku`: resultado `success`;
  - `buscar_referencia_categoria`: resultado `success`;
  - no crea regla.

## Avance Fase 1: UI de guardado controlado

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: preparado sin ejecutar guardados reales.

Archivos:

- `app/vistas/paginas/apps/erp/garantias/politicas.php`
- `public/assets/js/custom/apps/erp/garantias/politicas.js`

Alcance:

- Se agrego aviso `Guardado operativo`.
- Se agrego boton `Guardar politica`.
- Se agrego boton `Guardar regla`.
- Ambos botones usan los endpoints existentes:
  - `politica_guardar_erp`;
  - `politica_regla_guardar_erp`.
- Antes de enviar, la UI pide confirmacion del navegador.
- Despues de guardar correctamente, la UI refresca politicas/reglas/cobertura segun corresponda.

Decision:

- `Validar` e `Impacto` siguen siendo los pasos recomendados antes de guardar.
- El servidor sigue siendo la autoridad de seguridad:
  - exige permiso `garantias.politicas`;
  - registra auditoria operativa;
  - valida reglas de negocio en el modelo.
- Crear/editar politicas y reglas es CRUD normal del ERP; no requiere respaldo manual de BD.
- El respaldo externo queda reservado para DDL, migraciones y cargas masivas de construccion/mantenimiento.

Contrato:

- En esta tarea no se ejecuto ningun guardado real.
- No se crearon politicas nuevas.
- No se crearon reglas nuevas.
- No se modifico BD.
- La UI solo quedo preparada para operar los endpoints protegidos.

Validacion:

- `C:\xampp\php\php.exe -l app\modelos\GarantiasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\controladores\Garantias.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\garantias\politicas.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\garantias\politicas.js`: sin errores.

Siguiente paso recomendado:

- Probar visualmente el flujo completo:
  1. buscar referencia;
  2. validar regla;
  3. revisar impacto;
  4. guardar una politica/regla real de prueba con permiso `garantias.politicas`;
  5. confirmar auditoria y refresco de listados.

## Avance Fase 1: edicion controlada de politicas y reglas

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: preparado sin ejecutar guardados reales.

Archivos:

- `app/vistas/paginas/apps/erp/garantias/politicas.php`
- `public/assets/js/custom/apps/erp/garantias/politicas.js`

Alcance:

- Se agregaron IDs ocultos para diferenciar alta vs edicion:
  - `garantias_politica_id`;
  - `garantias_regla_id`.
- Al cargar una politica desde `Politicas base`, el formulario conserva su ID.
- Al guardar una politica con ID, el endpoint actualiza esa politica.
- Al guardar una politica sin ID, el endpoint crea nueva o reutiliza una existente por `codigo`.
- Se agrego boton de edicion en `Reglas asignadas`.
- Al cargar una regla existente, el formulario conserva su ID.
- Al guardar una regla con ID, el endpoint actualiza esa regla.
- Al guardar una regla sin ID, el endpoint crea una nueva o evita duplicado exacto.

Respuesta a duda operativa:

- Si se guarda una politica nueva, debe aparecer en `Politicas base` despues de refrescar el listado.
- La lista `Politicas base` no significa que solo existan las politicas iniciales; ahora tambien mostrara politicas configuradas por el negocio.

Estado CRUD:

- Listar politicas: listo.
- Crear politica: listo, protegido por permiso y auditoria.
- Editar politica: listo, protegido por permiso y auditoria.
- Listar reglas: listo.
- Crear regla: listo, protegido por permiso y auditoria.
- Editar regla: listo, protegido por permiso y auditoria.
- Desactivar/reactivar politica: listo como baja logica.
- Desactivar/reactivar regla: listo como baja logica.
- Eliminar fisico: no recomendado para ERP; debe usarse baja logica/desactivacion.

Decision:

- No llamar al modulo "CRUD completo" hasta tener acciones formales de desactivar/reactivar con auditoria visible.
- No borrar politicas/reglas fisicamente porque Ventas y Postventa pueden necesitar historial de la politica aplicada.

Validacion:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\garantias\politicas.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\garantias\politicas.js`: sin errores.

## Correccion de criterio: respaldo no aplica al CRUD normal

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: corregido en codigo y documentacion reciente.

Decision:

- El respaldo externo de BD no debe ser requisito del modulo para crear/editar registros normales.
- Las politicas y reglas de garantia son registros operativos, equivalentes a configurar categorias, marcas o datos de producto.
- El respaldo externo se mantiene solo para:
  - DDL/cambios de esquema;
  - migraciones;
  - cargas masivas;
  - scripts de construccion o mantenimiento que puedan afectar informacion en lote.

Cambios aplicados:

- Se elimino el requisito de token/respaldo en:
  - `politica_guardar_erp`;
  - `politica_regla_guardar_erp`.
- Se retiro de la UI el bloque de `Autorizacion para guardado real`.
- Se dejo confirmacion visual antes de guardar.
- Se mantiene auditoria en controlador.
- Se mantiene permiso `garantias.politicas`.

Contrato actual:

- Guardar politica/regla escribe BD como operacion normal del ERP.
- No pide respaldo manual.
- No pide token.
- Si una regla puede afectar muchos SKUs, se debe revisar `Impacto` antes de guardar.
- Las acciones de esquema siguen requiriendo respaldo externo y autorizacion.

## Avance Fase 1: baja logica y reactivacion

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: implementado sin borrar datos.

Archivos:

- `app/controladores/Garantias.php`
- `app/modelos/GarantiasErp.php`
- `app/vistas/paginas/apps/erp/garantias/politicas.php`
- `public/assets/js/custom/apps/erp/garantias/politicas.js`

Alcance:

- Se agrego cambio de estatus para politicas:
  - `activa`;
  - `inactiva`.
- Se agrego cambio de estatus para reglas:
  - `activa`;
  - `inactiva`.
- Se agregaron endpoints:
  - `politica_estatus_erp`;
  - `politica_regla_estatus_erp`.
- Se agregaron botones en tablas para desactivar/reactivar.
- Se agrego filtro de reglas por estatus:
  - activas;
  - inactivas;
  - todas.

Decision:

- No se borra fisicamente ninguna politica ni regla.
- Una politica inactiva deja de resolver garantias futuras porque el resolver solo considera politicas activas.
- Una regla inactiva deja de participar en la resolucion.
- Los snapshots historicos de ventas no se recalculan por cambios posteriores de politicas o reglas.

Contrato:

- Desactivar/reactivar escribe BD como operacion normal del ERP.
- Requiere permiso `garantias.politicas`.
- Registra auditoria en controlador.
- No requiere respaldo manual.
- No modifica ventas historicas ni inventario.

Validacion:

- `C:\xampp\php\php.exe -l app\modelos\GarantiasErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\controladores\Garantias.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\garantias\politicas.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\garantias\politicas.js`: sin errores.

Siguiente paso recomendado:

- Probar visualmente:
  - cargar politica;
  - guardar politica de prueba;
  - crear regla de prueba;
  - desactivar/reactivar regla;
  - desactivar/reactivar politica;
  - confirmar que cobertura y resolver reflejan el estatus.

## Avance UX: claridad de reglas por ambito

Fecha: 2026-06-29  
Documentacion IA: Codex GPT-5  
Estado: implementado en UI; sin cambios de esquema.

Cambios aplicados:

- En `Validar regla nueva` se agrego una guia compacta de precedencia:
  - SKU;
  - producto;
  - categoria;
  - marca;
  - proveedor;
  - menor prioridad gana si empatan.
- En `Reglas asignadas` se agrego resumen clicable por ambito.
- Los badges del resumen cambian el filtro de reglas sin guardar datos.
- En `Configurar politica` se agrego ayuda contextual para explicar el tipo de garantia seleccionado.
- Se agrego glosario visual para separar politica, regla, cobertura e impacto.
- La cobertura ahora muestra porcentaje cubierto y pendientes de forma mas directa.
- El dry-run de impacto ahora muestra semaforo visual si una regla tiene alcance amplio, solapa reglas existentes o trae advertencias.
- El guardado de reglas desde UI exige haber ejecutado `Validar` o `Impacto` sobre la misma configuracion capturada; si se cambia politica, ambito, referencia, prioridad o canal, se invalida la revision.
- Se creo UAT manual especifico: `docs/erp_garantias_uat_politicas_reglas.md`.
- La auditoria de cobertura ahora considera tambien reglas por proveedor activo del SKU, no solo SKU/producto/categoria/marca.
- El dry-run/guardado de reglas valida en backend que la politica exista y que la referencia pertenezca realmente al ambito seleccionado.
- El dry-run/guardado de reglas bloquea reglas activas solapadas para el mismo ambito, referencia, prioridad, canal, almacen y vigencia.
- Se creo UAT automatizado read-only: `storage/uat/uat_garantias_politicas_reglas_readonly.php`.
- El UAT automatizado confirma:
  - esquema disponible;
  - politicas consultables;
  - reglas activas consultables;
  - cobertura auditable;
  - busqueda de referencias por ambito;
  - dry-run valido o bloqueado de regla;
  - bloqueo de referencia inexistente;
  - impacto dry-run;
  - resolver garantia por SKU;
  - snapshot dry-run de venta.
- Ejecucion 2026-06-29:
  - 6 politicas activas;
  - 1 regla activa;
  - 1730 SKUs activos auditados;
  - 1 SKU con regla;
  - 1729 SKUs sin regla.
- Se creo UAT/reporte read-only de candidatos de cobertura:
  - `storage/uat/uat_garantias_cobertura_candidatos_readonly.php`.
- Resultado de candidatos 2026-06-29:
  - Por categoria conviene revisar primero:
    - `Alimentacion / Alimentos`: 313 SKUs sin regla;
    - `Salud e higiene / Higiene y limpieza`: 185 SKUs sin regla;
    - `(sin categoria)`: 155 SKUs sin regla;
    - `Habitat y descanso / Camas, casas y refugios`: 101 SKUs sin regla;
    - `Transporte, paseo y entrenamiento / Paseo y sujecion`: 97 SKUs sin regla.
  - Por marca no conviene arrancar como eje principal porque hay 987 SKUs sin marca.
  - Por proveedor no conviene arrancar como eje principal porque hay 897 SKUs sin proveedor.
  - Recomendacion operativa: usar reglas por categoria como primer barrido, reglas por marca/proveedor cuando esos catalogos esten confiables, y reglas por SKU solo para excepciones.
- Hallazgo relacionado:
  - algunos nombres de categorias muestran caracteres danados desde datos de Catalogo, por ejemplo textos con `├`.
  - No bloquea Garantias, pero debe corregirse en limpieza de Catalogo para que las reglas por categoria sean legibles.

Alcance:

- Cambio UX/UI, validaciones backend y UAT read-only.
- No se aplico DDL.
- No se escribieron politicas ni reglas durante esta fase.
- Ayuda a revisar si las garantias estan demasiado atomizadas por SKU o correctamente reutilizadas por producto/categoria/marca/proveedor.

## Nota POS - Devoluciones fisicas pendientes

Fecha: 2026-06-30

- POS/Ventas ya cuenta con una bandeja read-only para devoluciones con decision fisica pendiente:
  - metodo `VentasErp::devolucionesInventarioPendientesReadOnly`;
  - endpoint `/ventas/devoluciones_inventario_pendientes_erp`;
  - script UAT `storage/uat/uat_ventas_pos_devoluciones_inventario_pendientes_readonly.php`;
  - UI POS en `Caja > Devoluciones fisicas`.
- UAT actual:
  - 2 partidas pendientes;
  - ambas en `cuarentena`;
  - importe comercial acumulado `590`;
  - sin movimiento de inventario de devolucion.
- Regla de arquitectura:
  - POS no debe resolver la condicion fisica del producto devuelto;
  - Almacen/Inventario debe inspeccionar y decidir reintegro, cuarentena, merma o envio a garantia/proveedor;
  - Garantias debe referenciar la devolucion cuando el caso postventa termine en cambio, reparacion, proveedor o devolucion de dinero.
- Avance tecnico POS:
  - DDL aplicado para `erp_ventas_devoluciones_inspecciones`;
  - `erp_ventas_devoluciones_detalle` tiene `inspeccion_estado`, `id_inspeccion_fisica` y `fecha_inspeccion_fisica`;
  - dry-run disponible para validar decision fisica antes de escritura real;
  - falta ejecucion real y contrato completo con Inventario/Garantias para crear kardex o reclamo cuando aplique.
