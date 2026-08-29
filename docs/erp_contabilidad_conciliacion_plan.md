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

## Flujo operativo recomendado

1. Seleccionar periodo mensual y cuenta bancaria.
2. Cargar archivo CSV/TXT de movimientos del banco.
3. Cargar XML CFDI descargados del SAT/proveedores.
4. Clasificar cada movimiento como deposito, compra, gasto, interno, impuesto o revision.
5. Marcar CFDI ligado, pendiente o no aplica.
6. Exportar CSV/JSON y enviar al contador junto con los XML.

## Reglas iniciales

- Los ingresos bancarios se clasifican por defecto como deposito.
- Los egresos se clasifican por palabras clave en compra, gasto, interno, impuesto o revision.
- Un movimiento interno no debe solicitar factura fiscal.
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

- Contexto actual: el usuario necesita agilizar la preparacion mensual para su contador usando estados de cuenta bancarios y facturas XML.
- Cambios recientes: se creo MVP sin persistencia para validar flujo con archivos reales antes de DDL.
- Decisiones: Contabilidad es modulo transversal; no debe vivir dentro de Compras.
- Pendientes: disenar esquema con respaldos/autorizacion antes de guardar movimientos o paquetes.
- Impacta a: Compras, Ventas/POS, Proveedores, Rentabilidad y Reportes.
- Siguiente paso recomendado: probar con un CSV bancario real y varios XML para ajustar parser, columnas y reglas automaticas.
