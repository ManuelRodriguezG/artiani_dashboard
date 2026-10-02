# ERP Almacen - Planeaciones de preparacion

Documentacion IA: Codex GPT-5  
Fecha: 2026-10-01  
Modulo: ERP > Almacen > Planeaciones de preparacion  
Estado: UI inicial read-only, sin DDL ni escrituras de inventario

## Objetivo

Crear una capa previa a `Preparacion/Empaque` para consultar las recetas que puede generar cada producto y guardar escenarios operativos de mezcla, sin afectar inventario todavia.

Esta vista no reemplaza la preparacion real. Su funcion es ver recetas, armar planeaciones, editarlas y generar una hoja de trabajo para quien va a preparar producto, usando las presentaciones ya configuradas en Catalogo.

## Decision operativa

- Catalogo mantiene las reglas de presentacion: SKU origen, SKU resultado, factor y disponibilidad.
- Planeaciones de preparacion arma escenarios de mezcla por producto y genera instrucciones.
- Preparacion/Empaque sigue siendo el unico flujo de Almacen que descuenta origen, crea presentaciones, genera kardex y prepara etiquetas cuando aplique.
- Inventario no se toca desde esta vista.
- V1 guarda planeaciones en `localStorage` del navegador para probar UX sin escribir BD productiva.

## Flujo V1

1. Seleccionar producto/SKU origen preparable.
2. El sistema consulta recetas existentes con `/almacen/preparacion_presentaciones_erp`.
3. El sistema muestra las presentaciones que puede generar ese producto.
4. El usuario captura cantidad base a distribuir.
5. El usuario carga un escenario sugerido:
   - balanceado;
   - mas chica;
   - mas grande.
6. El usuario ajusta cantidades manualmente.
7. El usuario guarda o edita la planeacion local.
8. El sistema muestra consumo planeado y una hoja imprimible.

## Guardrails

- No guarda BD.
- No aparta stock.
- No crea folio `PREP-*`.
- No genera movimientos.
- No genera unidades ni etiquetas.
- No toca Ventas, ecommerce ni POS.
- No consulta existencias ni depende de almacen.
- Las planeaciones V1 no son compartidas entre usuarios porque viven en el navegador.

## Pendientes futuros

- Persistir escenarios favoritos por SKU en tablas formales.
- Generar orden formal de preparacion con folio propio, antes de ejecutar inventario.
- Convertir una planeacion aprobada en borrador de `Preparacion/Empaque`.
- Sugerir escenarios por rotacion real, stock minimo/maximo y ventas recientes.
- Generar PDF o imagen de hoja de trabajo si la operacion lo requiere.
