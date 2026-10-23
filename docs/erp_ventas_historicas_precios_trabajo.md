# Ventas historicas: listas, descuentos y precio conjunto

## Objetivo

Permitir capturar ventas pasadas con el precio que realmente se cobro y, cuando sea necesario, comparar o normalizar contra el precio vigente del ERP sin convertir la carga historica en una venta POS.

## Hallazgo actual

- La carga historica ya permite capturar un descuento general en monto.
- Cada partida permite editar cantidad y precio manualmente.
- El motor de precio conjunto existente pertenece a excepciones comerciales POS y requiere autorizacion de cobro; no debe invocarse desde carga historica.
- La carga historica no ofrece aun listas de precio, descuento por porcentaje ni paquete seleccionado por partidas.

## Decision de negocio

Una venta historica debe conservar dos lecturas:

1. **Importe historico real:** lo que efectivamente se cobro en la fecha original.
2. **Importe normalizado ERP:** el precio que corresponderia segun la lista vigente, descuento o paquete seleccionado.

No se debe sobrescribir el importe real. Los reportes deben poder elegir entre ventas reales historicas y ventas normalizadas para no distorsionar margen, tendencias ni auditoria.

## Flujo propuesto

### Precio por partida

Cada partida tendra una fuente de precio:

- `historico_real`: captura libre del precio pagado.
- `lista_general_actual`: toma el precio vigente del ERP.
- `lista_cliente_actual`: preparada para CRM/listas por cliente.
- `manual_autorizado`: precio ajustado con motivo y autorizacion cuando aplique.

El backend debe resolver el precio; el navegador solo selecciona la fuente y muestra el resultado.

### Descuento

- Descuento general por monto.
- Descuento general por porcentaje.
- Descuento por partida.
- Mostrar subtotal antes del descuento, descuento y total resultante.
- No permitir que el descuento deje un importe negativo.

### Precio conjunto o paquete

- El operador selecciona las partidas que forman el conjunto.
- Captura un total conjunto o un porcentaje de descuento.
- El backend calcula únicamente sobre las partidas seleccionadas.
- El descuento se distribuye proporcionalmente y se guarda por partida para conservar trazabilidad.
- Las partidas no seleccionadas no se alteran.
- Si se agrega o elimina un componente, se recalcula antes de guardar y se conserva una nueva instantanea de la operacion.

## Persistencia y auditoria

La implementacion debe conservar por partida:

- precio historico capturado;
- precio de lista de referencia, si se consulto;
- precio aplicado para el total normalizado;
- fuente de precio;
- descuento asignado;
- identificador o descripcion del paquete;
- motivo y usuario que hizo el ajuste.

La venta continuara con `canal='historico'`, `afecta_caja=0` y `afecta_inventario=0`. Las acciones de inventario, catalogo, proveedor y precio seguiran siendo pendientes separados.

## Reportes

Los reportes deberan distinguir:

- venta historica real;
- venta normalizada por lista;
- diferencia entre ambos importes;
- margen calculable y margen pendiente por falta de costo historico.

## Estado de implementacion 2026-10-22

- La carga historica ya permite marcar partidas seleccionadas como conjunto.
- Se puede capturar total fijo o porcentaje de descuento del conjunto.
- El backend calcula solo las partidas marcadas y distribuye el descuento proporcionalmente.
- El `dry-run` devuelve precio historico, precio aplicado, descuento por partida y totales separados.
- La interfaz actualiza el total visible incluyendo el descuento del conjunto y usa el total fijo como prioridad si tambien se captura porcentaje.
- El boton de conjunto ejecuta el mismo `dry-run` real que la prevalidacion general.
- La respuesta muestra subtotal, descuentos, total, pagado, saldo y desglose por partida.
- El backend bloquea valores negativos, conjuntos mayores al subtotal, descuentos generales excesivos y diferencias entre suma de partidas y total.
- Al guardar, la partida conserva precio base historico, precio aplicado y regla `precio_conjunto_historico`.
- La operacion sigue sin afectar caja ni inventario.

## Orden de implementacion

1. Extender dry-run de carga historica para resolver lista general y listas de cliente.
2. Agregar la vista de fuente de precio y consulta de precio vigente por partida.
3. Guardar snapshots completos de lista y descuentos sin afectar caja/inventario.
4. Mostrar en el detalle historico ambos importes y su justificacion.
5. Incorporar filtros de reportes para real, normalizado y diferencia.

## Regla de seguridad

La carga historica no debe consumir codigos de autorizacion POS ni crear excepciones comerciales POS. Si un ajuste historico requiere aprobacion, debe quedar como autorizacion documental propia de historico, con usuario, motivo y evidencia.
