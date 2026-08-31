# ERP - Contabilidad y conciliacion mensual

Documentacion IA: Codex GPT-5  
Fecha base: 2026-08-29  
Estado: Arranque operativo del modulo

## Proposito

Crear una seccion para preparar, mes a mes, la informacion que se entrega al contador: movimientos bancarios clasificados, CFDI relacionados, pendientes de factura, movimientos internos y resumen exportable.

## Decision de arquitectura

La necesidad corresponde a un modulo propio de Finanzas/Contabilidad, no a Compras ni a Ventas. Compras genera ordenes, pagos, notas y XML de proveedores; Ventas/POS genera ingresos y caja; Contabilidad cruza todo contra bancos y prepara el expediente mensual.

## MVP implementado

- Vista: `app/vistas/paginas/apps/erp/contabilidad/cierre_mensual.php`.
- Controlador: `app/controladores/Contabilidad.php`.
- JS: `public/assets/js/custom/apps/erp/contabilidad/cierre_mensual.js`.
- Menu: grupo Contabilidad dentro de ERP con permiso `finanzas.ver`.
- Sin escritura en BD; los archivos se procesan en el navegador.
- XLSX: se lee temporalmente desde `Contabilidad::importar_estado_cuenta_erp` y solo devuelve encabezados/filas para mapeo; no guarda archivo.

## Ajuste operativo 2026-08-29

El flujo inicial se ajusta a la forma en que el dueno clasifica sus estados de cuenta:

- Tipo de movimiento: `gasto`, `ingreso`, `transpaso`.
- Actividad: `negocio`, `programacion`, `personal`, `publicidad` y `inversion` como opcion conservada de la definicion inicial.
- Cuenta: banco/cuenta desde la que se esta revisando el movimiento.
- Monto: una sola columna final, aunque el archivo origen tenga cargos/abonos separados.
- Folio/factura/referencia: identificador operativo que despues puede enlazarse con CFDI XML.

Los XML CFDI no definen por si solos la clasificacion contable. Se usan para corroborar gastos/compras y sugerir a que movimiento corresponden por monto, fecha, RFC o nombre del emisor.

## Ajuste operativo 2026-08-30

Se agrega soporte para estados de cuenta `.xlsx`, ademas de `.csv` y `.txt`.

Antes de importar movimientos, la UI muestra un mapeo manual porque bancos como Mercado Pago exportan demasiadas columnas. Por ahora las columnas objetivo son:

- Fecha.
- Descripcion o concepto.
- Movimiento: gasto, ingreso o transpaso.
- Actividad: negocio, programacion, personal o publicidad.
- Cuenta: Santander, Mercado Pago, Efectivo u otra cuenta escrita por el usuario.
- Monto, o columnas separadas de egreso/ingreso si el archivo las trae asi.
- Folio/factura/referencia para enlazar despues con CFDI XML.

Regla: el importador no debe asumir que Mercado Pago o un banco tiene nombres de columna estables. Debe mostrar vista previa y permitir corregir el mapeo antes de construir movimientos.

## Ajuste UX 2026-08-30

La previsualizacion de estados de cuenta debe seguir el patron usado en listas de proveedor: modal amplio, resumen con badges, selector de limite de filas, mapeo en grid compacto y tabla de muestra dentro de contenedor con scroll horizontal.

No mostrar la previsualizacion como una tabla cruda dentro de la pantalla principal, porque archivos como Mercado Pago tienen muchas columnas y vuelven incomoda la mesa mensual.

## Ajuste de flujo 2026-08-30

El cierre mensual debe separar dos momentos operativos:

- Estados de cuenta: carga de archivos por periodo/cuenta, seleccion de fila de encabezados, mapeo de columnas utiles y registro visual de cada archivo cargado.
- Clasificacion: mesa editable donde cada movimiento puede corregirse por movimiento, actividad, cuenta, folio/factura y estado CFDI.
- Conciliacion por cuenta: resumen automatico separado por cuenta y tipo de movimiento, con exportacion individual por cuenta para entregar informacion separada al contador.

Razon: el dueno separa manualmente los movimientos por cuenta antes de enviarlos. El sistema debe respetar esa forma de trabajo y permitir cargar varias cuentas dentro del mismo periodo sin mezclar el origen de cada archivo.

Nota tecnica: el importador XLSX ahora devuelve tambien una matriz cruda. La UI permite escoger que fila es el encabezado porque algunos bancos exportan filas introductorias antes de los nombres reales de columnas. Google Sheets suele resolver esto visualmente, pero el parser propio necesita que esa fila se confirme antes de mapear.

## Flujo operativo recomendado

1. Seleccionar periodo mensual y cuenta bancaria.
2. Cargar archivo XLSX/CSV/TXT de movimientos del banco.
3. Mapear columnas utiles desde el archivo original.
4. Cargar XML CFDI descargados del SAT/proveedores.
5. Clasificar cada movimiento por tipo, actividad, forma de pago y categoria.
6. Marcar CFDI ligado, pendiente o no aplica segun corresponda.
7. Exportar CSV/JSON y enviar al contador junto con los XML/PDF requeridos.

## Reglas iniciales

- Los ingresos bancarios se clasifican por defecto como `ingreso`.
- Los cargos bancarios se clasifican por defecto como `gasto`.
- Los transpasos entre cuentas propias no deben solicitar factura fiscal.
- Movimientos personales e inversiones no deben mezclarse con deducciones del negocio sin revision del contador.
- Un impuesto normalmente no requiere CFDI de proveedor.
- Cuando un egreso no tiene CFDI ligado ni no aplica, queda como pendiente.

## Siguiente fase

- Persistir cierres mensuales y archivos importados.
- Agregar tabla de reglas recurrentes por concepto/RFC/cuenta.
- Conciliar contra Compras, POS/Ventas y pagos registrados.
- Generar paquete mensual versionado para el contador.
- Separar permisos: `finanzas.ver`, `finanzas.operar`, `finanzas.conciliar`, `finanzas.exportar`.

## Handoff / continuidad

Fecha: 2026-08-29

- Contexto actual: el usuario necesita subir estados de cuenta, clasificar cada movimiento y corroborar compras/gastos con XML CFDI.
- Cambios recientes: se ajusto el MVP a tipo de movimiento, actividad, cuenta, forma de pago, categoria, sugerencia CFDI, soporte XLSX y mapeo manual de columnas.
- Decisiones: Contabilidad es modulo transversal; XML corrobora movimientos, no reemplaza la clasificacion humana.
- Pendientes: probar con CSV reales de cada banco y XML reales para ajustar aliases, reglas y categorias antes de persistir.
- Impacta a: Compras, Ventas/POS, Proveedores, Rentabilidad y Reportes.
- Siguiente paso recomendado: probar con un CSV bancario real y varios XML para ajustar parser, columnas y reglas automaticas.
