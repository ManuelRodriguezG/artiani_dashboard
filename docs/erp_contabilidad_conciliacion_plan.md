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

- Tipo de movimiento: `egreso` o `ingreso`.
- Actividad: `negocio`, `programacion`, `personal`, `publicidad`, `inversion` y `transpaso`.
- Cuenta: banco/cuenta desde la que se esta revisando el movimiento.
- Monto: una sola columna final, aunque el archivo origen tenga cargos/abonos separados.
- Folio/factura/referencia: identificador operativo que despues puede enlazarse con CFDI XML.

Los XML CFDI no definen por si solos la clasificacion contable. Se usan para corroborar gastos/compras y sugerir a que movimiento corresponden por monto, fecha, RFC o nombre del emisor.

## Ajuste operativo 2026-08-30

Se agrega soporte para estados de cuenta `.xlsx`, ademas de `.csv` y `.txt`.

Antes de importar movimientos, la UI muestra un mapeo manual porque bancos como Mercado Pago exportan demasiadas columnas. Por ahora las columnas objetivo son:

- Fecha.
- Descripcion o concepto.
- Movimiento: egreso o ingreso.
- Actividad: negocio, programacion, personal, publicidad, inversion o transpaso.
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

## Ajuste tecnico XLSX 2026-08-31

El lector XLSX de Contabilidad no debe depender de `xl/worksheets/sheet1.xml` como unica fuente. Algunos archivos bancarios contienen varias hojas, hojas vacias iniciales, rutas internas relativas o estilos que representan fechas como numeros seriales de Excel.

Se refuerza el importador para:

- Resolver las hojas reales desde `xl/workbook.xml` y `xl/_rels/workbook.xml.rels`.
- Elegir la hoja con mayor cantidad de celdas con valor para evitar tomar una portada o instrucciones vacias.
- Devolver a la UI el nombre de la hoja usada.
- Mantener la matriz cruda para que el usuario confirme la fila de encabezados.
- Convertir fechas numericas de Excel cuando el estilo de celda indica formato de fecha.

## Diagnostico archivo CTA2780 2026-08-31

Archivo revisado: `Movimientos_CTA2780_30_08_26.xlsx`.

- Hoja real: `data`.
- Rango detectado: `A1:G356`.
- La fila 1 es resumen del estado de cuenta, no encabezado.
- La fila 3 contiene encabezados reales: `FECHA`, `HORA`, `CONCEPTO`, `RETIRO`, `DEPOSITO`, `MONEDA`, `SALDO POSTERIOR`.
- Las fechas vienen como texto con mes en espanol, por ejemplo `28/ago/26`.
- Los importes vienen como numeros crudos, por ejemplo `6750`, aunque Excel los muestre como `$6,750.00`.
- Algunos saldos cero pueden venir como residuo flotante, por ejemplo `2.2737367544323E-13`; la UI debe normalizarlos a `0`.

Ajuste aplicado: la deteccion de encabezado prioriza filas que contengan `fecha`, `concepto` y `monto/importe`; la normalizacion de fecha acepta meses en espanol; los numeros cercanos a cero se tratan como cero.

Correccion posterior del mismo diagnostico: el archivo usa `sharedStrings.xml` para guardar textos y la lectura basada en SimpleXML podia devolver indices (`2`, `3`, `4`, etc.) en vez del texto real (`FECHA`, `HORA`, `CONCEPTO`). Se cambia la decodificacion de strings compartidos y lectura de celdas a DOM/XPath para respetar namespaces del XLSX.

Regla de mapeo simplificada: el archivo que se sube a Contabilidad debe traer una sola columna `Monto`. Si el estado de cuenta original trae columnas separadas como `RETIRO` y `DEPOSITO`, el ajuste preferido es preparar antes una columna auxiliar `Monto` con el importe de la operacion y no mapear `SALDO POSTERIOR`.

La clasificacion contable no debe depender del banco. El usuario debe poder editar manualmente si el movimiento es `egreso` o `ingreso`; y distinguir en actividad si corresponde a negocio, programacion, personal, publicidad, inversion o transpaso.

## Ajuste conceptual 2026-09-01

`Movimiento` representa la direccion del dinero en la cuenta: `egreso` o `ingreso`.

`Actividad` representa la intencion operativa: `negocio`, `programacion`, `personal`, `publicidad`, `inversion` o `transpaso`.

Razon: un gasto, una inversion y un traspaso pueden ser salidas de cuenta, por lo tanto todos son `egreso`; lo que los diferencia para revision contable es la actividad.

## Ajuste operativo 2026-09-01

Se agrega guardado local de cierres mensuales en navegador para que el usuario pueda conservar la clasificacion mientras se define la persistencia final en base de datos.

- Un cierre guardado conserva periodo, cuenta base, estados de cuenta cargados, movimientos clasificados y CFDI cargados.
- La seccion `Cierres guardados` permite abrir o eliminar borradores locales.
- La mesa de movimientos expone `Categoria` para distinguir compras (`compra_mercancia`) de otros gastos dentro de un mismo periodo.
- Los filtros del cierre permiten consultar por periodo abierto, movimiento, actividad, categoria y CFDI.

Limitacion: el guardado local vive en `localStorage` del navegador. No reemplaza la persistencia ERP futura ni permite consulta multiusuario/servidor.

## Ajuste UX de flujo 2026-09-01

La pantalla se separa en pestañas para evitar que carga, clasificacion, CFDI, guardados y conciliacion compitan en la misma vista.

- `Resumen`: informacion general del mes, cierres guardados y flujo operativo.
- `Estados de cuenta`: alta de nuevo archivo, carga de XML, guardado del cierre y lista de estados ya cargados.
- `Clasificacion`: mesa editable de movimientos bancarios.
- `Conciliacion`: resumen por cuenta y tipo de movimiento.

Cada estado cargado puede abrirse desde `Estados de cuenta` con accion `Ver / editar`, lo que filtra la mesa de clasificacion a ese archivo/cuenta. La accion `Ver todos` quita ese filtro para revisar el mes completo.

## Ajuste operativo 2026-09-01 eliminacion MVP

En el MVP local se permite eliminar:

- Un movimiento individual desde la mesa de clasificacion.
- Un estado de cuenta completo desde la seccion `Estados de cuenta`; esto tambien elimina sus movimientos asociados del cierre actual.

Regla para fase BD: estas acciones deben revisarse antes de persistir. En base de datos conviene modelarlas como baja logica/cancelacion de importacion para conservar historial de trabajo, no como borrado fisico silencioso.

## Ajuste operativo 2026-09-01 acciones masivas

La mesa de clasificacion permite seleccionar movimientos individuales o todos los visibles para aplicar campos en lote:

- Movimiento.
- Actividad.
- Categoria.
- CFDI.
- Cuenta.

La seleccion masiva respeta los filtros activos y el estado de cuenta abierto. Si no se elige un valor para un campo, ese campo queda sin cambio.

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
- Los cargos bancarios se clasifican por defecto como `egreso`.
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
