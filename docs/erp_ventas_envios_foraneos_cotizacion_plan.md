# Ventas - Cotizacion operativa de envios foraneos

Documentacion IA: Codex GPT-5  
Fecha: 2026-10-04  
Estado: plan operativo inicial; no implica cambios de esquema, codigo ni BD.  
Relacionados: `docs/erp_tms_delivery_plan.md`, `docs/erp_ventas_pos_pedidos_arranque.md`, `docs/crm_clientes_plan.md`, `docs/erp_catalogo_avance.md`, `docs/erp_ux_operativa.md`

## Contexto operativo

El negocio comenzo a recibir interesados de envios foraneos por campanas de publicidad. El personal necesita captar informacion rapido, cotizar en una plataforma externa de envios y detectar productos del catalogo que todavia no tienen datos logisticos confiables, como peso y medidas.

La necesidad inmediata no es integrar una paqueteria por API, sino crear una vista operativa que permita:

- registrar prospecto o cliente con datos suficientes para envio;
- seleccionar productos del Catalogo ERP;
- capturar cantidades solicitadas;
- identificar si el producto tiene informacion incompleta para empaque/envio;
- capturar medidas reales del paquete a cotizar;
- conservar la cotizacion y el seguimiento comercial sin convertirlo todavia en venta confirmada;
- preparar el paso futuro hacia pedido, venta y servicio TMS.

## Decision de ubicacion

La vista debe vivir inicialmente en Ventas/Pedidos, no en Catalogo ni directamente en TMS.

Nombre recomendado de menu:

- `Ventas > Envios foraneos`

Ruta recomendada:

- `/ventas/envios_foraneos`

Motivo:

- El operador esta atendiendo un interesado con intencion de compra.
- Todavia no existe necesariamente una venta pagada ni un servicio logistico confirmado.
- El flujo necesita carrito/productos/precio/disponibilidad, que pertenece a Ventas/Pedidos.
- CRM aporta cliente/contacto/direccion.
- Catalogo aporta productos y pendientes de calidad.
- TMS debe recibir el servicio logistico solo cuando ya exista pedido/venta o una solicitud formal de envio.

## Fronteras por modulo

### Ventas/Pedidos

Debe ser dueno de:

- folio de cotizacion o prepedido;
- cliente/prospecto snapshot;
- productos solicitados, cantidades, precios estimados y disponibilidad visible;
- estatus comercial: interesado, cotizando envio, cotizacion enviada, aceptado, convertido a pedido, descartado;
- conversion futura a pedido/apartado/venta.

No debe:

- guardar ficha completa de cliente;
- modificar datos maestros del producto sin control de Catalogo;
- operar entrega como si fuera TMS;
- descontar inventario mientras sea cotizacion.

### CRM

Debe aportar o recibir:

- telefono, nombre, correo si existe;
- direccion de envio;
- notas de contacto;
- consentimiento/comunicacion cuando aplique;
- tarea o seguimiento comercial si el cliente no confirma.

Regla:

- Si el contacto es rapido, Ventas puede capturar snapshot minimo, pero el cliente canonico y sus direcciones pertenecen a CRM.

### Catalogo ERP

Debe aportar:

- busqueda de SKU ERP;
- nombre, imagen, precio, unidad y estado activo;
- informacion logistica del producto cuando exista: peso, largo, ancho, alto, fragilidad, restricciones;
- incidencias de productos incompletos.

Regla:

- Si un producto no tiene medidas/peso confiable, la vista de envios debe levantar pendiente de calidad de catalogo, no ocultarlo ni inventarlo como dato maestro.

### Almacen/Inventario

Debe aportar:

- disponibilidad orientativa por almacen;
- posibilidad futura de preparar paquete si el pedido se confirma;
- medicion fisica real del paquete cuando el producto ya este armado o seleccionado.

Regla:

- La cotizacion no descuenta inventario. La reserva o salida debe suceder despues, mediante Pedido/Venta segun el flujo autorizado.

### TMS/Delivery

Debe participar despues de la aceptacion comercial:

- crear servicio logistico externo o tercero;
- guardar guia/referencia, costo y evidencia;
- operar estados logisticos.

Regla:

- TMS no debe ser la pantalla principal de interesados. TMS empieza cuando hay servicio logistico solicitado, no cuando apenas se esta cotizando si el cliente comprara.

## Vista operativa recomendada

### 1. Bandeja de envios foraneos

Columnas:

- folio;
- fecha;
- cliente/contacto;
- ciudad/estado/codigo postal;
- estatus;
- total productos estimado;
- envio estimado;
- pendientes de catalogo;
- ultimo seguimiento;
- acciones.

Filtros:

- estatus;
- fecha;
- operador;
- estado/ciudad;
- con pendientes de medidas;
- cotizacion enviada/no enviada.

### 2. Captura rapida

Bloques en orden operativo:

1. Contacto del interesado:
   - nombre;
   - telefono/WhatsApp;
   - correo opcional;
   - notas.
2. Destino:
   - codigo postal;
   - estado;
   - ciudad;
   - colonia;
   - calle, numero y referencias si ya las tiene.
3. Productos:
   - buscador de SKU ERP;
   - cantidad;
   - precio estimado;
   - disponibilidad visible;
   - alertas de medidas/peso faltante.
4. Paquete para cotizar:
   - largo;
   - ancho;
   - alto;
   - peso real o estimado;
   - numero de paquetes;
   - observaciones de empaque.
5. Cotizacion externa:
   - paqueteria/plataforma;
   - servicio elegido;
   - costo envio;
   - precio cobrado al cliente;
   - vigencia;
   - captura de guia solo si ya se contrato.
6. Seguimiento:
   - enviar cotizacion;
   - marcar aceptado;
   - convertir a pedido;
   - descartar con motivo.

## Estados recomendados

Estados comerciales:

- `borrador`: captura interna sin cotizacion lista.
- `datos_incompletos`: falta direccion, medidas o productos.
- `cotizando_envio`: se esta calculando con plataforma externa.
- `cotizacion_enviada`: el cliente ya recibio precio/condiciones.
- `aceptada`: cliente acepto continuar.
- `convertida_pedido`: ya se genero pedido/apartado/venta segun flujo.
- `descartada`: no procedio.

Estados de calidad de catalogo por partida:

- `completa`: tiene informacion suficiente.
- `faltan_medidas_producto`: falta largo/ancho/alto del producto.
- `falta_peso_producto`: falta peso.
- `requiere_medicion_fisica`: aunque exista dato maestro, el paquete real debe medirse.
- `producto_no_identificado`: interesado pidio algo que no se encontro en Catalogo ERP.

## Datos minimos para fase urgente

Para arrancar sin integracion avanzada:

- contacto;
- telefono;
- codigo postal, ciudad y estado;
- productos seleccionados de Catalogo ERP;
- cantidades;
- medidas del paquete;
- peso del paquete;
- costo de envio cotizado;
- precio de envio comunicado al cliente;
- estatus y observaciones.

No bloquear la captura porque falte ficha CRM completa. Si falta direccion completa, mantener estatus `datos_incompletos`.

## Pendientes accionables

La vista debe generar pendientes visibles, no solo mensajes temporales:

- producto sin peso;
- producto sin largo/ancho/alto;
- producto solicitado no encontrado;
- paquete pendiente de medir;
- cotizacion pendiente de enviar;
- cliente pendiente de confirmar;
- cotizacion vencida.

Estos pendientes pueden vivir en la bandeja inicial de Envios foraneos y despues integrarse con SYS Notificaciones o CRM Tareas cuando se consolide el contrato.

## Fase 1 recomendada

Objetivo: operar campana actual sin esperar integracion de paqueterias.

Incluye:

- vista `/ventas/envios_foraneos`;
- bandeja y formulario operativo;
- busqueda de productos Catalogo ERP;
- captura manual de datos destino;
- captura manual de medidas/peso de paquete;
- registro manual de cotizacion de plataforma externa;
- alertas de datos logisticos faltantes por producto;
- conversion futura documentada hacia Pedido.

No incluye:

- API de paqueterias;
- generacion automatica de guias;
- cobro integrado;
- descuento de inventario;
- DDL o migraciones sin autorizacion.

## Avance 2026-10-04 - Pantalla operativa inicial

Estado: implementado sin persistencia en BD.

Archivos:

- `app/controladores/Ventas.php`
- `app/vistas/paginas/apps/erp/ventas/envios_foraneos.php`
- `public/assets/js/custom/apps/erp/ventas/envios_foraneos.js`
- `app/vistas/includes/header/sidebar.php`
- `app/modelos/VentasErp.php`

Resultado:

- Se agrego la ruta `/ventas/envios_foraneos`.
- Se agrego acceso en `Ventas y POS > Envios foraneos`.
- La vista permite capturar contacto, destino, productos, medidas/peso de paquete y cotizacion externa.
- La busqueda de productos reutiliza el buscador de SKUs de Ventas/Catalogo.
- La pantalla detecta pendientes logisticos por producto: falta peso, faltan medidas o producto no identificado.
- Los borradores se guardan temporalmente en `localStorage` del navegador.
- No se escribe BD, no se crean pedidos, no se descuenta inventario y no se crea TMS.

Limitacion conocida:

- Los borradores locales no son multiusuario ni persistentes a nivel sistema. Para operacion formal se requiere aplicar esquema y conectar endpoints reales.

## Avance 2026-10-04 - Persistencia formal preparada en dry-run

Estado: aplicado y conectado a backend.

Archivos:

- `app/modelos/VentasErpEsquema.php`
- `app/controladores/Ventas.php`
- `docs/erp_ventas_envios_foraneos_schema_propuesta.sql`
- `docs/erp_ventas_envios_foraneos_schema_solicitud_autorizacion.md`

Resultado inicial:

- Se agrego plan DDL read-only para:
  - `erp_ventas_envios_foraneos`;
  - `erp_ventas_envios_foraneos_detalle`;
  - `erp_ventas_envios_foraneos_eventos`.
- Se agrego auditoria read-only:
  - `/ventas/esquema_auditar_envios_foraneos`
- Se agrego endpoint de plan/aplicacion controlada:
  - `/ventas/esquema_actualizar_envios_foraneos`
- El endpoint solo ejecuta DDL con permiso `sistema.soporte`, respaldo externo valido y token `VENTAS_ENVIOS_FORANEOS_DDL`.

Regla:

- Aunque exista el endpoint, no ejecutar DDL sin autorizacion textual del dueno y respaldo externo en `C:\xampp\panel_db_backups`.

Aplicacion autorizada:

- Autorizacion recibida: `VENTAS_ENVIOS_FORANEOS_DDL`.
- Respaldo externo:
  - `C:\xampp\panel_db_backups\productivo_artianicom_sys_panel_20261004_223346_antes_ventas_envios_foraneos.sql`
  - `tamano_bytes=131205837`
  - `sha256=E47FE8857D1258C9905C27E44524D6EFFFDBC255C1E1EFBDE9807F4A2FD58B65`
- Auditoria posterior:
  - tablas existen;
  - sin columnas faltantes;
  - sin indices faltantes.

Conexiones backend agregadas:

- `/ventas/envios_foraneos_listar_erp`
- `/ventas/envios_foraneos_consultar_erp`
- `/ventas/envios_foraneos_guardar_erp`

La vista `/ventas/envios_foraneos` ya consulta la bandeja desde BD y guarda cotizaciones con `ventas.operar`. Las capturas no crean pedido, venta, TMS ni movimiento de inventario.

## Fase 2 recomendada

Objetivo: conectar el flujo a Pedido/Venta y CRM.

Incluye:

- crear o vincular cliente CRM;
- guardar direcciones CRM;
- convertir cotizacion aceptada en pedido/apartado;
- permitir anticipo o pago;
- crear solicitud TMS cuando proceda;
- conservar snapshot de envio y costos.

## Fase 3 recomendada

Objetivo: automatizar y medir rentabilidad logistica.

Incluye:

- integracion API con paqueteria o plataforma externa;
- reglas por dimensiones, peso, zona y fragilidad;
- historico de costos reales vs cobrados;
- reportes por campana, producto, estado y paqueteria;
- calidad de catalogo logistico por prioridad comercial.

## Riesgos a evitar

- Crear envios como productos inventariables.
- Guardar clientes foraneos solo en una tabla de ventas sin CRM.
- Modificar productos desde la pantalla de cotizacion sin flujo de Catalogo.
- Descontar inventario por una cotizacion.
- Convertir TMS en CRM o en Ventas.
- Ocultar productos incompletos en lugar de convertirlos en pendientes operativos.

## Handoff

Siguiente paso recomendado:

1. Probar captura real desde `/ventas/envios_foraneos` con un interesado autentico.
2. Ajustar UX segun uso del personal.
3. Implementar conversion de cotizacion aceptada a Pedido.
4. Despues, implementar solicitud TMS cuando exista guia/servicio logistico real.

