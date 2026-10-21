# Ventas historicas - Captura asistida desde WhatsApp

Documentacion IA: Codex GPT-6  
Fecha: 2026-10-20  
Estado: consultas de catalogo/ventas realizadas; borradores externos pendientes de confirmacion. Sin captura real; acceso UI aun no comprobado.

## Objetivo y alcance

El dueno entrega mensajes de WhatsApp y reportes descargados de Mercado Pago. La captura se realiza mediante Carga historica y sus herramientas existentes, comprobando comportamiento real y registrando folios. No escribir ventas mediante SQL directo ni usar ventas ficticias para probar productivo. No aplicar migraciones dentro de esta dinamica.

El flujo historico conserva fecha/hora e importes reales, sin afectar caja, turno, kardex ni existencias. El codigo revisado permite precio historico por partida y multiples pagos documentales; no se da por comprobado que tenga las mismas herramientas de paquetes y excepciones comerciales del POS.

## Secuencia por lote

1. Conservar mensaje original, autor, fecha y hora; asignar referencia de origen y revisar ventas ya existentes antes de guardar. Un mensaje se considera una agrupacion provisional: mensajes consecutivos pueden pertenecer a una compra y dos mensajes con igual hora no prueban que sea la misma venta.
2. Buscar el producto real y verificar variante, presentacion, unidad y cantidad. Un nombre parecido o precio coincidente no bastan. Guardar equivalencias solo despues de confirmarlas; no equiparar falta de existencias con falta de producto.
3. Si el producto no se encuentra, usar la partida manual/venta rapida del flujo historico con descripcion fiel y accion de clasificar SKU cuando corresponda. Si hay varios candidatos plausibles, consultar al dueno y continuar otras ventas resolubles.
4. Conservar precio efectivamente vendido. Usar herramientas existentes de precio o paquetes cuando correspondan y esten disponibles en historico. No cambiar listas actuales ni crear paquetes permanentes para explicar una venta pasada sin una decision adicional del dueno.
5. Conciliar pagos con evidencia del reporte: identificador de operacion, importe bruto, fecha/hora y tipo informado. Registrar pagos parciales o mixtos solo con evidencia. Diferencias de hora, comisiones, netos, devoluciones y montos repetidos requieren revision; no asignar una operacion a dos ventas.
6. Prevalidar, guardar las ventas resueltas dentro del alcance acordado y volver a consultar folio, partidas, fecha/hora, total, pagos y acciones. Confirmar ausencia de afectacion a caja/inventario en la primera captura real. No repetir guardado tras un error de red sin consultar si ya se creo.
7. Informar ventas registradas y folios, pendientes de identificacion/pago y fallas observadas. Detener la captura afectada si un fallo puede alterar importes o duplicar datos. Documentar correcciones del sistema y su validacion por separado.

## Pagos pendientes

MP significa Mercado Pago, pero no identifica por si solo debito, credito, transferencia u otra modalidad. Ausencia de MP no significa efectivo. Si el reporte no contiene el tipo, conservar la incertidumbre. No representar automaticamente una venta cobrada con metodo desconocido como deuda del cliente: comprobar primero como maneja el sistema ese caso.

## Equivalencias confirmadas por el dueno

- `Mp`: Mercado Pago; subtipo pendiente de evidencia.
- `Baño azul esquineor`: baño azul esquinero.
- Venta del 16/10/2026 a las 18:08, polvo de baño: 3 porciones de 100 g a $20 cada una, total $60 y peso total 300 g. No son tres envases de 300 g. Vinculacion al SKU y conversion de unidad aun pendientes.

## Primer lote disponible

Mensajes del 16/10/2026: 13 agrupaciones provisionales, 28 renglones, total interpretado $3,580. Mercado Pago declarado en las agrupaciones de 17:37 ($315) y 18:08 ($530). Cantidades omitidas, nombres dudosos, agrupacion definitiva y demas pagos siguen pendientes de comprobar. Estos valores son preparacion en chat, no evidencia de registro en BD.

## Estado de revision

Se revisaron Ventas::carga_historica, VentasErp::ventaHistoricaDryRun, el JS de carga historica y el handoff de Ventas/POS. Hay cambios preexistentes del dueno en modelo, vista y JS; no se modificaron. Se consultaron catalogo operativo, almacenes y ventas/pagos ERP de octubre mediante una transaccion READ ONLY con rollback. No se ha iniciado sesion en la UI ni registrado ventas durante esta preparacion.

## Control externo mensual y almacenes por partida

Decision del dueno: guardar archivos operativos fuera del proyecto/Git, en `C:\Users\aleja\Documents\Control_ventas\AAAA-MM`. Conservar cada original en `fuentes`, una copia mensual `mercado_pago_AAAA-MM_vigente.xlsx`, control de procedencia/hash/cobertura en `control_mes.json` y borradores en `revision`. Al recibir el mes completo reemplazar vigente, conservar originales y regenerar cruces por ID de operacion sin sumar reportes solapados. Las equivalencias confirmadas deben conservarse entre reemplazos.

Recordatorio obligatorio antes de consolidar: el dueno debe indicar almacen por producto/partida, porque una venta puede involucrar el local de mascotas y el acuario. No inferirlo por familia de producto ni por caja/tienda del reporte Mercado Pago. Registrar la decision en el borrador externo. No crear productos ni registrar ventas durante la identificacion; proponer candidatos y solicitar confirmacion cuando falte evidencia.

El detalle de candidatos, movimientos y revision de duplicados queda exclusivamente en la carpeta externa. Reconsultar ventas existentes antes de cada guardado; ausencia de una fecha en ERP no descarta registros legacy o mal fechados. El reporte recibido contiene horas con offset -05:00: conservar original y conversion a Ciudad de Mexico para evitar comparar horas sin zona.
