# ERP Ventas/POS - Clientes, precios e incentivos

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: plan maestro del submodulo; no implica escrituras en BD.

## Principio rector

El POS debe permitir vender rapido, pero el ERP no debe modelar clientes como un campo suelto de nombre o telefono. La captura rapida en caja es una puerta de entrada; el modelo final debe soportar historial, listas de precios, incentivos, recompensas, garantias, pedidos, devoluciones y reportes por cliente.

Regla para futuras decisiones:

- Una sugerencia operativa como "capturar telefono rapido" representa una necesidad de velocidad en caja.
- No debe convertirse en arquitectura literal.
- La arquitectura debe separar identificacion rapida, ficha de cliente, condiciones comerciales, recompensas y analitica.

## Objetivos

- Identificar clientes sin frenar la fila.
- Permitir venta anonima o publico general.
- Crear clientes rapidos con datos minimos.
- Completar ficha fuera del flujo de cobro.
- Soportar listas de precios por cliente, grupo, canal, sucursal o vigencia.
- Preparar incentivos y recompensas futuras sin rehacer POS.
- Mantener trazabilidad: que precio, promocion o recompensa aplicada quede congelada en la venta.
- Evitar mezclar clientes legacy/ecommerce sin auditoria.

## Niveles de captura en POS

### Nivel 0 - Publico general

Uso:

- venta rapida sin datos de cliente;
- compras pequenas;
- cliente no desea identificarse.

Regla:

- La venta debe poder cerrarse sin `id_cliente`.
- El ticket conserva `cliente_nombre_publico='Publico general'` o equivalente.

### Nivel 1 - Identificacion express

Uso:

- el cajero captura telefono, nombre corto, correo o codigo de cliente.

Regla:

- POS busca coincidencias.
- Si existe cliente, permite seleccionarlo rapidamente.
- Si no existe, puede dejar identificador temporal o proponer alta rapida.
- No debe obligar formulario largo.

### Nivel 2 - Alta rapida

Datos minimos recomendados:

- telefono o correo;
- nombre/alias;
- consentimiento de contacto si aplica;
- sucursal de alta;
- usuario que registro.

Regla:

- Debe crear `id_cliente`.
- Debe marcar calidad de datos como `basica`.
- Debe permitir completar datos despues.

### Nivel 3 - Ficha completa

Datos completos:

- nombre legal y nombre comercial;
- telefonos;
- correos;
- direcciones;
- RFC/razon social cuando aplique;
- preferencias;
- notas operativas;
- restricciones;
- historial de compras;
- condiciones comerciales.

Regla:

- No se captura completa durante una fila de POS salvo que el usuario lo pida.
- Debe vivir en una pantalla CRM/Clientes separada.

## Modelo conceptual

Entidades sugeridas:

- `erp_clientes`: identidad principal.
- `erp_clientes_contactos`: telefonos, correos y contactos secundarios.
- `erp_clientes_direcciones`: entrega, facturacion, referencia.
- `erp_clientes_identificadores`: telefono normalizado, correo normalizado, codigo de cliente, RFC, ecommerce_id futuro.
- `erp_clientes_segmentos`: grupos comerciales o demograficos.
- `erp_clientes_notas`: notas auditables.
- `erp_clientes_consentimientos`: marketing, WhatsApp, correo, privacidad.
- `erp_clientes_historial`: eventos importantes de compra, devolucion, ajuste, fusion.
- `erp_clientes_merge`: fusiones de duplicados.

Campos importantes en `erp_clientes`:

- `id_cliente`
- `codigo_cliente`
- `tipo_cliente`: `persona`, `empresa`, `publico_general`
- `nombre_publico`
- `estatus`: `activo`, `bloqueado`, `inactivo`, `duplicado`
- `calidad_datos`: `express`, `basica`, `completa`, `revisar`
- `id_lista_precio_default`
- `id_segmento_default`
- `creado_desde`: `pos`, `erp`, `ecommerce`, `importacion`
- `id_sucursal_alta`
- `creado_por`

## Listas de precios

Objetivo:

- Tener precios flexibles sin hardcodear descuentos en POS.

Entidades sugeridas:

- `erp_listas_precios`
- `erp_listas_precios_detalle`
- `erp_clientes_listas_precios`
- `erp_segmentos_listas_precios`
- `erp_promociones`
- `erp_promociones_reglas`
- `erp_promociones_aplicaciones`

Prioridad recomendada de precio:

1. Precio manual autorizado en venta.
2. Promocion especifica vigente.
3. Lista asignada al cliente.
4. Lista asignada al segmento/tipo del cliente.
5. Lista por canal/sucursal.
6. Lista general.

Reglas:

- Toda venta debe guardar snapshot del precio aplicado.
- Si una lista cambia manana, ventas pasadas no cambian.
- Descuentos manuales requieren permiso y motivo.
- Lista por cliente debe tener vigencia y auditoria.
- Lista por cliente es excepcion comercial; para miles de clientes, usar segmentos/tipos CRM.
- El cliente puede subir a un segmento con mejores precios mediante autorizacion futura, sin reescribir ventas pasadas.
- Debe poder haber precio por SKU, categoria, marca o familia, pero POS debe recibir un precio final ya resuelto por backend.

## Incentivos y recompensas

Objetivo:

- Preparar un programa futuro sin redisenar ventas.

Conceptos:

- puntos por compra;
- niveles de cliente;
- monedero;
- cupones;
- recompensas por frecuencia;
- beneficios por segmento;
- promociones condicionadas;
- incentivos a vendedor/cajero separados de beneficios al cliente.

Entidades sugeridas:

- `erp_recompensas_programas`
- `erp_recompensas_cuentas`
- `erp_recompensas_movimientos`
- `erp_recompensas_reglas`
- `erp_cupones`
- `erp_cupones_usos`
- `erp_clientes_niveles`
- `erp_incentivos_vendedores`
- `erp_incentivos_vendedores_movimientos`

Reglas:

- Puntos/recompensas se calculan sobre ventas confirmadas, no sobre carritos.
- Cancelacion/devolucion debe revertir puntos o dejar ajuste trazable.
- Redencion de puntos debe ser forma de pago o descuento claramente separado.
- Incentivo a vendedor no debe confundirse con recompensa al cliente.
- Recompensas no deben permitir vender inventario inexistente.

## Integracion con POS

El POS debe mostrar:

- cliente seleccionado o publico general;
- telefono/codigo/correo como busqueda rapida;
- boton `Crear cliente` o `Alta rapida` cuando no existe coincidencia;
- lista de precio aplicada;
- beneficios disponibles;
- advertencias de cliente bloqueado o duplicado;
- puntos estimados solo como referencia hasta confirmar venta.

El POS no debe:

- recalcular reglas complejas solo en JS;
- aplicar descuentos sin backend;
- crear clientes completos en medio del cobro;
- modificar datos sensibles sin permiso.

## Crear cliente desde POS

Si debe existir la opcion, pero con alcance controlado.

Estado 2026-06-27:

- Existe dry-run backend `VentasErp::clienteAltaRapidaDryRun`.
- Existe endpoint `/ventas/pos_cliente_alta_rapida_dryrun_erp`.
- Existe prueba CLI `storage/uat/uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php`.
- La UI del POS valida alta rapida sin escribir BD.
- Existe aplicador real autorizado `storage/uat/uat_ventas_pos_cliente_alta_rapida_apply_authorized.php`, bloqueado por defecto y no ejecutado.
- Falta autorizacion para crear el primer cliente real por alta rapida.
- Tablas de contacto, consentimiento e historial siguen como fase posterior; el alcance real inicial debe ser cliente express + identificador principal.

### Alta rapida desde POS

Datos minimos:

- nombre publico o alias;
- telefono o correo;
- consentimiento de contacto, cuando aplique;
- sucursal;
- usuario/cajero;
- origen `pos`.

Resultado:

- crea `id_cliente`;
- queda marcado con `calidad_datos='basica'` o `express`;
- puede ligarse inmediatamente a la venta/pedido;
- genera pendiente de completar ficha si faltan datos importantes.

### Alta completa

Debe abrir pantalla/modal separado, no mezclarse con cobro rapido.

Datos adicionales:

- RFC/razon social;
- direccion fiscal;
- direccion de entrega;
- contactos secundarios;
- notas;
- preferencias;
- segmento;
- lista de precio default.

### Guardrails

- Si el telefono/correo ya existe, sugerir cliente existente antes de crear.
- Si hay duplicado probable, permitir continuar solo como identificador temporal o pedir confirmacion con permiso.
- No bloquear una venta de mostrador por falta de cliente.
- No crear cliente sin al menos un identificador util, salvo cliente publico general.

## Integracion con ventas

`erp_ventas` debe conservar:

- `id_cliente`
- `cliente_nombre_publico`
- `id_lista_precio`
- `lista_precio_nombre_snapshot`
- `segmento_cliente_snapshot`
- `beneficios_snapshot`

`erp_ventas_detalle` debe conservar:

- precio base;
- precio aplicado;
- descuento aplicado;
- regla/lista/promocion origen;
- autorizacion si aplica.

## Apartados, pedidos y pagos parciales

Este caso es parte central del POS robusto.

### Tipos de documento

- `venta`: pago completo y salida/entrega inmediata.
- `pedido`: solicitud de producto con compromiso futuro; puede reservar o no segun politica.
- `apartado`: cliente deja anticipo y se reserva inventario.

### Reglas de apartado

- Debe tener cliente identificado o al menos datos de contacto suficientes.
- Debe tener fecha compromiso o politica de vencimiento.
- Debe registrar anticipo minimo si la politica lo exige.
- Debe crear reserva de inventario cuando el producto exista en tienda/bodega asignada.
- Debe mostrar saldo pendiente.
- Debe permitir abonos posteriores.
- Debe bloquear entrega si saldo no esta cubierto, salvo permiso especial.
- Debe liberar o penalizar reserva al vencer/cancelar segun politica.

### Abonos

Cada abono debe registrar:

- folio de apartado/pedido;
- id_cliente;
- id_caja;
- id_turno_caja;
- metodo de pago;
- monto;
- referencia/autorizacion;
- usuario;
- fecha.

El abono no debe duplicar venta. Debe reducir saldo del documento original.

### Entrega final

Al liquidar o autorizar entrega:

- validar saldo;
- consumir reserva;
- generar kardex si no se genero antes;
- cambiar estatus a `entregada` o equivalente;
- emitir ticket/recibo final.

### Entidades sugeridas

- `erp_ventas` con `tipo_documento='pedido'|'apartado'`.
- `erp_ventas_pagos` para anticipos y abonos.
- `erp_inventario_reservas` para stock apartado.
- `erp_ventas_eventos` para historial de abonos, vencimientos, cambios y entrega.
- `erp_ventas_politicas_apartado` para porcentaje minimo, dias de vigencia y penalizaciones.

## Integracion con ecommerce

Regla:

- No mezclar clientes ecommerce con ERP POS sin auditoria.
- La vinculacion debe hacerse por una tabla de identificadores o relacion externa, no sobrescribiendo clientes.

Escenarios:

- cliente creado en POS luego compra en ecommerce;
- cliente ecommerce llega a tienda;
- telefonos/correos duplicados;
- facturacion con datos distintos al comprador.

## Fases propuestas

### Fase 1 - POS con identificacion limpia

- Mantener venta publico general.
- Captura express solo como busqueda/identificador.
- Preparar `id_cliente` en contrato de venta.
- No activar recompensas todavia.

### Fase 2 - Modulo Clientes ERP

- Crear esquema de clientes.
- Busqueda por telefono/correo/codigo.
- Alta rapida.
- Ficha completa.
- Duplicados y fusion.
- Permisos y auditoria.

### Fase 3 - Listas de precios

- Crear listas y vigencias.
- Resolver precio en backend para POS.
- Snapshot en venta.
- UAT por cliente, segmento y sucursal.

### Fase 4 - Promociones

- Reglas por vigencia, SKU, categoria, cantidad, canal.
- Bloqueos por margen minimo si aplica.
- Auditoria de descuentos.

### Fase 5 - Recompensas

- Cuenta de puntos/monedero.
- Acumulacion por venta confirmada.
- Redencion.
- Reversion por devolucion.
- Reportes.

### Fase 6 - Incentivos internos

- Comisiones o incentivos a vendedor/cajero.
- Separacion total de beneficios al cliente.
- Reportes por sucursal, turno y vendedor.

## UAT inicial

| ID | Caso | Resultado esperado |
| --- | --- | --- |
| CLI-UAT-001 | Venta publico general | POS no exige cliente. |
| CLI-UAT-002 | Buscar por telefono existente | POS sugiere cliente sin frenar captura. |
| CLI-UAT-003 | Telefono nuevo | POS permite alta rapida o venta con identificador temporal. |
| CLI-UAT-004 | Cliente con lista especial | Backend devuelve precio aplicado y snapshot. |
| CLI-UAT-005 | Cliente bloqueado | POS muestra advertencia y requiere permiso para continuar. |
| CLI-UAT-006 | Promocion vigente | Descuento aparece con regla origen. |
| CLI-UAT-007 | Devolucion | Reversa beneficios/recompensas con trazabilidad. |
| CLI-UAT-008 | Duplicado probable | POS sugiere revisar sin bloquear fila. |

## Pendientes de decision

- Si el programa de recompensas sera puntos, monedero, cupones o mezcla.
- Si habra niveles de cliente.
- Si habra precios de mayoreo por volumen o por cliente.
- Politica de privacidad y consentimiento de contacto.
- Reglas de descuento maximo y margen minimo.
- Permisos para precio manual y excepciones.

## Avance POS/CRM canonico 2026-06-30

- El endpoint POS `/ventas/pos_cliente_alta_rapida_dryrun_erp` ya delega a `ClientesCrm::altaRapidaDryRun`.
- La alta rapida de POS debe crear, cuando se autorice, en `crm_clientes_maestro` y `crm_clientes_identificadores`, no en `erp_clientes`.
- Los scripts UAT POS de cliente fueron corregidos para validar/aplicar contra CRM canonico:
  - `storage/uat/uat_ventas_pos_cliente_alta_rapida_dryrun_readonly.php`;
  - `storage/uat/uat_ventas_pos_cliente_alta_rapida_apply_authorized.php`.
- La venta POS puede seguir como publico general; si se selecciona cliente CRM, la venta debe guardar `id_cliente_crm` y snapshots de codigo/nombre/origen.
- No se debe activar alta real desde UI sin autorizacion con respaldo externo y permiso fino `crm.crear`.
- El modal POS separa busqueda de cliente y resolucion de lista:
  - `Buscar` consulta CRM canonico por telefono/correo/codigo/nombre sin requerir carrito.
  - `Resolver lista` requiere carrito y calcula precios/listas en backend.
  - `Validar alta` sigue en dry-run hasta autorizacion real.

### Autorizacion futura sugerida

`AUTORIZO CREAR CLIENTE CRM DESDE POS UAT REAL usando respaldo C:\xampp\htdocs\panel\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CLIENTE_ALTA_RAPIDA id_usuario=1 id_almacen=5 nombre="Cliente POS UAT" identificador=33XXXXXXXX consentimiento=1`
