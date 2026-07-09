# Vision operativa de Compras en un ERP robusto

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-07  
Documento: Vision funcional y operativa  
Modulo principal: ERP Compras  
Relacionados: Finanzas, Almacen, Inventario, Catalogo, Direccion, Auditoria

## Proposito

Este documento explica como deberia funcionar Compras dentro de un ERP real y escalable, pensando en un negocio que hoy puede operar con pocas personas, pero que a futuro tendra areas separadas, empleados contratados, permisos, responsabilidades, revisiones y auditoria.

La idea central es esta:

Compras no existe sola. Compras inicia y controla la adquisicion, pero no deberia ser la unica area que confirma pagos, recibe mercancia, actualiza inventario o define catalogo fiscal. Un ERP robusto separa responsabilidades sin hacer lento el trabajo.

## Respuesta corta a la duda

No estas mal si hoy quieres que la persona de compras capture todo: proveedor, productos, XML, adjuntos, pagos y notas. En un negocio chico o en crecimiento, una sola persona puede hacer muchas cosas.

Pero en un ERP pensado para crecer, lo ideal es separar:

- Compras: que se va a comprar, a quien, cuanto, a que costo y bajo que documento.
- Finanzas/Contabilidad: como se paga, cuando se paga, si el pago esta conciliado y si los documentos cuadran.
- Almacen: que llego fisicamente, en que cantidad, lote, caducidad y ubicacion.
- Catalogo: si el producto existe, como se llama, como se vende, impuestos, unidades y reglas.
- Direccion: aprobaciones, excepciones y control.

Eso no significa que Compras no pueda capturar pagos o adjuntos. Significa que el sistema debe distinguir entre capturar informacion y validar/conciliar oficialmente.

## Como piensa un ERP robusto

Un ERP robusto separa tres cosas:

1. Captura operativa.
2. Validacion por area responsable.
3. Efecto contable, fiscal o inventario.

Ejemplo:

Compras puede capturar que la factura se pago con transferencia y subir el comprobante. Pero Finanzas podria marcar ese pago como conciliado cuando revise el banco.

Compras puede cargar XML y productos. Pero Almacen confirma si fisicamente llego todo.

Compras puede detectar producto nuevo. Pero Catalogo completa datos tecnicos, fiscales, unidades y reglas de inventario.

Esta separacion permite que el negocio crezca sin perder control.

## Pendientes interdepartamentales

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-16

Decision operativa:

El ERP debe funcionar como sistema de comunicacion entre areas, no solo como formularios aislados. Cuando Compras detecta un producto nuevo, una relacion proveedor-SKU faltante, datos fiscales incompletos, diferencia contra XML, producto no surtido o cualquier bloqueo que pertenece a otra area, el sistema debe generar un pendiente accionable para el modulo responsable.

Esto no significa que Compras deba entrar a Catalogo o resolver la tarea tecnicamente. Compras debe poder ver que el pendiente ya fue generado, consultar su estatus y continuar cuando el area responsable lo atienda.

Regla recomendada:

- Compras crea o dispara el pendiente desde su flujo normal.
- Catalogo atiende productos, SKUs, datos fiscales, unidades y reglas de inventario.
- Proveedores atiende relaciones proveedor-SKU, listas, codigos de proveedor y vigencias.
- Finanzas atiende pagos, notas, comprobantes y conciliacion.
- Almacen atiende recepcion fisica, lote, caducidad, ubicacion e incidencias de entrada.

Cada pendiente debe tener:

- area_origen,
- modulo_origen,
- referencia_origen,
- area_responsable,
- tipo_pendiente,
- prioridad,
- estatus,
- descripcion,
- evidencia o payload tecnico,
- usuario que lo genero,
- usuario que lo atendio,
- fecha de creacion,
- fecha de resolucion,
- resultado o decision.

En Compras debe existir una vista de seguimiento de pendientes relacionados con sus solicitudes y ordenes. Esta vista no reemplaza la bandeja operativa del area responsable; solo permite saber si el bloqueo esta pendiente, en proceso, resuelto, rechazado o requiere mas informacion.

Objetivo:

Evitar llamadas, mensajes externos y olvidos entre departamentos. El sistema debe dejar trazabilidad de quien detecto el problema, quien lo debe atender, cuanto tiempo lleva abierto y que impacto tiene en la operacion.

### Notificaciones operativas

Decision recomendada:

Las incidencias y pendientes interdepartamentales deben alimentar un centro de notificaciones del ERP. El navbar puede mostrar un indicador visual cuando el usuario tiene pendientes relevantes segun sus permisos, rol o areas asignadas.

Uso sugerido:

- `Notifications / Alerts`: pendientes que requieren accion del usuario o su area.
- `Activity Logs`: historial de eventos ya ocurridos, auditoria y cambios.
- `Chat`: comunicacion humana futura; no debe ser la fuente principal de pendientes operativos.

Regla:

Una incidencia no debe depender de que alguien mande un mensaje. El ERP debe crear la notificacion automaticamente, mostrarla a los perfiles con permiso y permitir abrir el origen: orden, solicitud, proveedor, SKU, recepcion, pago o incidencia.

Ejemplos:

- Catalogo ve: producto pendiente de alta desde Compras.
- Proveedores ve: relacion proveedor-SKU faltante.
- Compras ve: Catalogo resolvio un producto pendiente y ya puede continuar.
- Almacen ve: orden enviada lista para recepcion.
- Finanzas ve: pago capturado pendiente de conciliacion.

## Areas involucradas

### 1. Compras

Responsabilidad principal:

Conseguir productos, servicios o insumos necesarios para operar el negocio, negociando con proveedores y dejando documentado que se pidio, que se compro y bajo que condiciones.

Compras debe manejar:

- Proveedores.
- Solicitudes de compra.
- Ordenes de compra.
- Cotizaciones.
- Facturas o XML recibidos.
- Productos comprados.
- Costos de compra.
- Descuentos.
- Diferencias contra solicitud.
- Productos no surtidos.
- Productos nuevos.
- Pendientes para futuras solicitudes.
- Adjuntos operativos.

Compras NO deberia ser la fuente final de:

- Existencia fisica.
- Lotes y caducidades recibidos.
- Conciliacion bancaria.
- Declaracion contable final.
- Alta definitiva de productos.

Pero Compras si puede iniciar o proponer informacion para esas areas.

### 2. Finanzas y Contabilidad

Responsabilidad principal:

Controlar dinero, saldos, pagos, creditos, notas de credito, anticipos, comprobantes y conciliacion.

Finanzas debe manejar:

- Pagos aplicados.
- Pagos pendientes.
- Pagos conciliados.
- Notas de credito.
- Anticipos.
- Saldos por proveedor.
- Cuentas por pagar.
- Fechas de vencimiento.
- Referencias bancarias.
- Comprobantes.
- Validacion de factura.
- Reportes contables.

Compras puede capturar:

- Metodo de pago reportado.
- Referencia.
- Comprobante.
- Nota de credito recibida.

Pero Finanzas deberia poder cambiar el estado:

- pendiente,
- aplicado,
- conciliado,
- cancelado.

Esta separacion evita problemas como:

- Compras marca como pagado algo que no salio del banco.
- Se duplica un pago.
- Se pierde un anticipo.
- El contador no puede cuadrar facturas contra bancos.

### 3. Almacen

Responsabilidad principal:

Confirmar la recepcion fisica de mercancia.

Almacen debe manejar:

- Recepciones.
- Cantidad recibida.
- Cantidad faltante.
- Cantidad excedente.
- Producto dañado.
- Producto cambiado.
- Lote.
- Caducidad.
- Serie.
- Ubicacion.
- Etiqueta o codigo unico.
- Evidencias de recepcion.

Compras puede decir:

"Se compro esto."

Almacen confirma:

"Esto llego realmente."

Por eso una orden no deberia pasar a `recibida` desde Compras. Ese estatus debe venir de Almacen.

### 4. Inventario

Responsabilidad principal:

Controlar existencias disponibles, apartadas, en transito, dañadas, vencidas o bloqueadas.

Inventario debe alimentarse desde:

- Recepcion de almacen.
- Ajustes.
- Traspasos.
- Conteos.
- Ventas.
- Devoluciones.

Compras no debe modificar inventario directamente. Una compra puede estar creada aunque el producto todavia no exista fisicamente.

### 5. Catalogo de productos

Responsabilidad principal:

Mantener la verdad maestra de productos, SKUs, unidades, impuestos, presentaciones, reglas de venta y reglas de inventario.

Catalogo debe manejar:

- Producto maestro.
- SKU.
- Unidad de compra.
- Unidad de venta.
- Conversiones.
- Codigo de barras.
- Clave SAT.
- Clave unidad SAT.
- Objeto impuesto.
- IVA.
- IEPS.
- Precio de venta.
- Margen.
- Costo referencia.
- Imagenes.
- Requiere lote.
- Requiere caducidad.
- Requiere serie.

Compras puede detectar:

- Producto nuevo.
- SKU nuevo del proveedor.
- Producto con datos fiscales faltantes.
- Producto que no existe en lista proveedor.

Pero Catalogo debe resolverlo y dejarlo limpio.

### 6. Direccion o Gerencia

Responsabilidad principal:

Aprobar decisiones sensibles.

Direccion puede intervenir cuando:

- Compra supera cierto monto.
- Costo sube demasiado.
- Proveedor no esta autorizado.
- Se compra producto nuevo.
- Hay diferencia importante entre solicitud y factura.
- Se cancela una orden enviada.
- Se registra descuento o nota importante.

### 7. Auditoria o Soporte

Responsabilidad principal:

Ver que paso, cuando paso, quien lo hizo y por que.

Debe poder consultar:

- Cambios de estatus.
- Ediciones.
- Cancelaciones.
- Pagos.
- Adjuntos.
- XML importados.
- Productos nuevos.
- Recepciones.
- Diferencias.

No necesariamente debe operar el proceso.

## Flujo ideal completo

### Paso 1: Necesidad

Alguien detecta que se necesita comprar.

Puede venir de:

- Bajo inventario.
- Pedido de cliente.
- Resurtido manual.
- Producto caducando.
- Producto faltante.
- Compra especial.

Resultado:

Solicitud de compra.

### Paso 2: Solicitud de compra

La solicitud responde:

- Que se necesita.
- Cuanto se necesita.
- Para cuando.
- Para que almacen.
- Quien lo solicita.
- Proveedor sugerido si existe.
- Prioridad.
- Observaciones.

Estatus recomendados:

- `borrador`
- `pendiente`
- `aprobada`
- `rechazada`
- `orden_generada`
- `cancelada`

Responsable:

Compras o usuario solicitante.

Regla viva 2026-06-15:

- La solicitud debe guardar `id_almacen_destino` como intencion operativa de destino.
- La orden de compra hereda ese almacen al generarse, pero Compras puede confirmarlo o corregirlo antes de enviarla.
- La recepcion de almacen confirma el destino fisico final.
- El solicitante no debe capturarse como texto libre: `solicitado_por` corresponde al usuario autenticado que crea/envia la solicitud.
- Si en el futuro se requiere capturar para otra area o persona, debe agregarse un campo controlado de area/departamento o flujo de delegacion, sin reemplazar la trazabilidad del usuario real.

Regla viva 2026-06-15 - productos nuevos en orden:

- Una orden en `borrador` debe permitir captura flexible, incluyendo productos propuestos, XML incompleto y datos fiscales pendientes.
- Para enviar una orden a proveedor/almacen, ninguna partida fisica inventariable puede quedar sin SKU ERP.
- Las partidas sin SKU ERP solo pueden avanzar si son cargos o servicios no inventariables (`servicio`, `cargo`, `no_inventariable`, `adicional`).
- Datos fiscales incompletos en un SKU ERP generan advertencia/incidencia para Catalogo, pero no bloquean por si solos el envio de la orden.
- Catalogo/Proveedores deben resolver productos nuevos, relaciones proveedor-SKU y fiscales pendientes; Compras no crea maestros automaticamente.

### Paso 3: Aprobacion de solicitud

No siempre es necesaria en negocios chicos, pero debe existir.

Preguntas:

- Tiene sentido comprarlo?
- Hay presupuesto?
- Es proveedor correcto?
- La cantidad es razonable?

Responsable:

Direccion, encargado de area o compras senior.

### Paso 4: Orden de compra

La orden responde:

- A que proveedor se le compra.
- Que productos se compran.
- Cantidades.
- Costos.
- Descuentos.
- Impuestos.
- Total.
- Almacen destino.
- Fecha esperada.
- Folio proveedor.
- Observaciones.

La orden puede venir de:

- Solicitud aprobada.
- Captura directa.
- XML/factura.
- Cotizacion.

Estatus recomendados:

- `borrador`
- `enviada`
- `parcial`
- `recibida`
- `cancelada`

Responsable:

Compras.

### Paso 5: XML, factura y conciliacion

El XML permite acelerar captura.

Debe ayudar a:

- Leer proveedor.
- Leer folio/factura.
- Leer productos.
- Leer cantidades.
- Leer costos antes de impuesto.
- Leer descuentos.
- Leer impuestos.
- Leer datos fiscales.

Pero debe conciliar, no imponer ciegamente.

Casos:

- Concepto XML coincide con producto de orden.
- Concepto XML no existe en orden.
- Producto de orden no aparece en XML.
- Producto existe en ERP pero no en proveedor.
- Producto no existe en ERP.
- Datos fiscales incompletos.

Resultado:

- Partidas conciliadas.
- Pendientes de atencion.
- Alertas para catalogo/proveedor.

### Paso 6: Adjuntos

Adjuntos documentan la compra.

Tipos:

- Cotizacion.
- Factura PDF.
- XML.
- Comprobante de pago.
- Nota de credito.
- Orden firmada.
- Captura.
- Otro.

Responsable:

Compras puede adjuntar documentos operativos. Finanzas puede adjuntar comprobantes contables.

Regla:

Los archivos deben consultarse desde el ERP, no desde una carpeta publica sin control.

### Paso 7: Pago y condiciones

Aqui esta la diferencia importante.

En ERP robusto, pago puede tener dos niveles:

#### Captura operativa

Compras puede capturar:

- "El proveedor dice que se pago."
- "Se uso transferencia."
- "Referencia X."
- "Se aplico nota Y."
- "Aqui esta el comprobante."

#### Validacion financiera

Finanzas confirma:

- El dinero salio.
- La referencia coincide.
- El banco cuadra.
- La nota existe.
- El saldo es correcto.
- El pago queda conciliado.

Estados utiles:

- `pendiente`: capturado, no confirmado.
- `aplicado`: reduce saldo operativo.
- `conciliado`: validado por Finanzas.
- `cancelado`: anulado con historial.

Mi recomendacion:

Para tu etapa actual, deja que Compras capture pagos y notas para velocidad, pero usa estados. Mas adelante Finanzas puede tomar control de la conciliacion.

### Paso 8: Envio de orden

Enviar significa:

- La compra ya esta formalizada.
- Se espera recepcion.
- Ya no deberia editarse libremente.
- Se prepara recepcion de almacen.

Responsable:

Compras con permiso de aprobacion.

### Paso 9: Recepcion de almacen

Almacen confirma:

- Que llego.
- Cuanto llego.
- Que falta.
- Que llego de mas.
- Lote.
- Caducidad.
- Ubicacion.
- Estado fisico.

Resultado:

- Orden parcial o recibida.
- Inventario actualizado.
- Incidencias registradas.

### Paso 10: Cierre

Una compra esta realmente cerrada cuando:

- Factura/documentos estan completos.
- Mercancia recibida.
- Inventario actualizado.
- Pagos/notas conciliados.
- No hay pendientes de producto/catalogo/proveedor.

### Consolidacion de costos al enviar

El costo operativo de Catalogo no debe cambiarse desde Almacen durante la recepcion. Almacen confirma cantidades, lotes, caducidades, ubicaciones e inventario.

El costo comprometido debe consolidarse desde Compras al enviar la orden, sin crear un paso operativo adicional:

- La orden enviada ya no debe modificarse directamente; si hay un problema operativo, se cancela o se atiende con flujo controlado.
- Todas las partidas deben tener SKU ERP, cantidad comprometida y costo unitario valido.
- El cierre usa el costo neto guardado en el snapshot de la orden; la normalizacion de impuesto ocurre al guardar la orden.
- Si la orden esta en moneda distinta de MXN, se usa el tipo de cambio guardado en la orden.
- `costo_referencia` puede actualizarse con el costo comprometido calculado de la orden enviada.
- `costo_promedio_historico` puede calcularse como indicador, pero requiere decision aparte si se va a guardar como dato formal.

## Como deberia verse el modulo para el usuario

### Compras

Debe ver:

- Solicitudes.
- Ordenes.
- Proveedores.
- Productos del proveedor.
- XML.
- Diferencias.
- Adjuntos.
- Estado de recepcion.
- Estado financiero resumido.

Debe poder:

- Crear solicitud.
- Crear orden.
- Editar borrador.
- Importar XML.
- Adjuntar documentos.
- Enviar orden.
- Cancelar si todavia es seguro.
- Capturar pagos/notas si tiene permiso.

### Finanzas

Debe ver:

- Ordenes con saldo.
- Pagos pendientes.
- Notas.
- Facturas.
- Comprobantes.
- Proveedor.
- Vencimientos.

Debe poder:

- Registrar pago.
- Conciliar pago.
- Cancelar pago.
- Aplicar nota.
- Revisar saldo.

### Almacen

Debe ver:

- Recepciones pendientes.
- Orden relacionada.
- Productos esperados.
- Cantidades.
- Lote/caducidad requeridos.

Debe poder:

- Recibir parcial.
- Recibir total.
- Registrar incidencias.
- Ubicar mercancia.

### Catalogo

Debe ver:

- Productos nuevos detectados.
- SKUs sin datos fiscales.
- Productos no relacionados con proveedor.
- Diferencias de unidad.

Debe poder:

- Completar producto.
- Relacionar SKU proveedor.
- Completar fiscalidad.
- Definir reglas de inventario.

## Recomendacion para tu ERP

Para avanzar rapido sin perder robustez:

1. Permite que Compras capture casi todo.
2. Usa permisos para que a futuro puedas separar areas.
3. Usa estados para distinguir capturado vs validado.
4. No hagas que el usuario de vueltas innecesarias.
5. Implementa guardado automatico de borrador para acciones que necesitan ID.
6. No mezcles responsabilidades internas de backend.

Ejemplo practico:

Compras crea orden nueva, carga XML, agrega adjuntos y captura pago en una sola pantalla.

Internamente el sistema hace:

- Guarda borrador automatico.
- Importa XML.
- Crea pendientes.
- Sube adjuntos.
- Registra pago como `pendiente` o `aplicado`.
- Si se envia, prepara recepcion.

El usuario siente un flujo rapido. El ERP conserva control.

## Donde creo que puede estar la confusion

Tu estas pensando como operador principal del negocio:

"Yo necesito capturar todo de una vez para avanzar."

Eso es correcto.

Yo estoy construyendo como ERP escalable:

"Cada cosa debe quedar separada para que manana otra persona pueda operar su parte sin romper el sistema."

Tambien es correcto.

La solucion no es escoger una u otra. La solucion es:

- Una pantalla agil para capturar.
- Backend separado por responsabilidades.
- Permisos y estados para crecer.

## Decisiones recomendadas para el modulo actual

### Compra en borrador

Debe permitir:

- Capturar proveedor.
- Capturar productos.
- Cargar XML mediante guardado automatico.
- Subir adjuntos mediante guardado automatico.
- Capturar pago mediante guardado automatico, pero idealmente como `pendiente` si aun no esta enviada.

Debe evitar:

- Afectar inventario.
- Marcar recibida.
- Conciliar pago final sin finanzas.

### Orden enviada

Debe permitir:

- Ver todo.
- Adjuntar documentos.
- Registrar pagos/notas.
- Preparar recepcion.
- Consultar recepcion.

Debe limitar:

- Edicion de productos.
- Cambio de proveedor.
- Cambio de almacen si ya hay recepcion.

### Orden parcial o recibida

Debe permitir:

- Ver.
- Adjuntar documentos tardios.
- Consultar pagos.
- Consultar recepcion.

Debe evitar:

- Editar productos.
- Cancelar libremente.
- Cambiar cantidades compradas.

### Orden cancelada

Debe permitir:

- Ver historial.
- Descargar documentos activos si existen.

Debe evitar:

- Nuevos pagos.
- Nuevos adjuntos.
- Cambios de productos.
- Recepcion.

## Tareas que salen de esta vision

1. Implementar guardado automatico de borrador para acciones avanzadas.
2. Definir si pago en borrador se guarda como `pendiente`.
3. Agregar estado `conciliado` visible para Finanzas.
4. Crear vista de pendientes para Catalogo.
5. Crear vista de recepciones para Almacen conectada desde orden.
6. Crear historial/auditoria visible por orden.
7. Crear resumen de compra en modo Ver.
8. Crear reportes de compras y saldos.

## Regla de oro

El ERP debe permitir trabajar rapido hoy, pero sin impedir que manana el negocio tenga areas separadas.

Por eso la interfaz puede ser muy agil, pero el backend debe estar bien dividido.
