# ERP Produccion - Materia prima y producto terminado

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: Vision inicial para modulo futuro  
Relacionados: `docs/erp_plan_maestro_fundamentos.md`, `docs/erp_almacen_recepciones_tareas_vivas.md`, `docs/erp_catalogo_venta_granel.md`, `docs/erp_inventario_existencias_arranque.md`

## Objetivo

Definir como debe manejar el ERP productos que el negocio compra como materia prima, transforma internamente y vende como producto terminado empacado.

Casos origen:

- Heno de alfalfa comprado en unidades fisicas variables, controlado internamente en kg.
- Sustratos para reptiles comprados por presentacion/proveedor en litros, pero controlados y empacados por peso porque el cliente entiende kg/g.

## Decision principal

La recepcion variable no queda obsoleta. Cambia su papel:

- No debe usarse para vender pacas, sacos o empaques de proveedor en tienda normal.
- Si debe existir para recibir materia prima cuando la cantidad real que entra al inventario se confirma fisicamente en Almacen.
- La materia prima no debe mezclarse con producto terminado vendible.

## Separacion de tipos de SKU

### Materia prima

SKU interno usado para compras, recepcion, inventario y produccion.

Ejemplos:

- `ALIHA-MP` - Heno de alfalfa materia prima.
- `PEATMOSS-MP` - Peatmoss materia prima.
- `FIBCOCO-MP` - Fibra de coco materia prima.

Reglas recomendadas:

- Unidad base: `kg` si el proceso interno empaca por peso.
- Inventariable: si.
- Vendible en tienda: no.
- Ecommerce: no.
- Recepcion variable: si cuando el peso real cambia.
- Etiqueta interna: si se quiere rastrear unidad fisica/lote origen.
- Se consume desde Produccion/Preparacion, no desde Ventas.

### Producto terminado

SKU vendible que nace al empacar o preparar.

Ejemplos:

- `ALIHA-500G`.
- `ALIHA-1KG`.
- `PEATMOSS-500G`.
- `FIBCOCO-1KG`.

Reglas recomendadas:

- Unidad base: normalmente `pza`, porque cada bolsa cerrada es una unidad vendible.
- Vendible en POS: si.
- Ecommerce: si, si se autoriza por canal.
- Etiqueta/codigo propio: si.
- Nace por produccion/preparacion, no por compra directa, salvo que el proveedor ya entregue esa presentacion cerrada.

### Producto mayorista especial

SKU opcional y separado para vender una unidad fisica completa cuando el negocio lo autorice.

Ejemplo:

- `ALIHA-PACA-MAYOREO`.

Reglas recomendadas:

- Canal: mayoreo/especial.
- No visible en tienda normal ni ecommerce.
- No contaminar la imagen de tienda formal.
- Puede consumir materia prima o vender una unidad fisica trazable bajo flujo especial.

## Flujo ERP recomendado

### 1. Catalogo

Catalogo define identidades y reglas:

- SKU materia prima.
- SKU producto terminado.
- unidad base.
- si controla inventario.
- si requiere recepcion variable.
- si genera etiqueta interna.
- recetas o equivalencias de preparacion.
- canales donde se puede vender.

Catalogo no crea stock.

### 2. Compras

Compras registra la compra real al proveedor:

- materia prima comprada;
- proveedor;
- cantidad esperada o unidades fisicas esperadas;
- costo;
- impuestos/documentos;
- condiciones de pago.

Compras no crea producto terminado y no empaca.

### 3. Almacen / Recepcion

Almacen confirma lo recibido fisicamente:

- recibe materia prima;
- captura peso real si aplica;
- registra lote/caducidad si aplica;
- genera entrada de inventario de materia prima;
- genera unidades/etiquetas internas si aplica.

Ejemplo:

- Compra: 5 unidades fisicas de heno.
- Recepcion: peso real total 102.75 kg.
- Inventario: sube `102.75 kg` de materia prima.

### 4. Produccion / Preparacion / Empaque

Modulo futuro para transformar materia prima en producto terminado.

Movimientos esperados:

- salida de materia prima;
- salida de insumos de empaque, si se controlan;
- entrada de producto terminado;
- folio de produccion/preparacion;
- etiquetas de producto terminado.

Ejemplo:

- Consume `20 kg` de `ALIHA-MP`.
- Produce `40` bolsas de `ALIHA-500G`.
- El costo de esos 20 kg se transfiere al producto terminado.

### 5. Inventario

Inventario consulta:

- saldo de materia prima;
- saldo de producto terminado;
- unidades abiertas/cerradas;
- kardex de compra, recepcion, consumo y produccion.

Inventario no prepara ni vende.

### 6. Ventas / POS / Ecommerce

Ventas solo consume producto terminado autorizado para venta.

Reglas:

- Tienda fisica vende bolsas terminadas.
- Ecommerce vende solo presentaciones terminadas.
- Materia prima no aparece como producto vendible.
- Mayoreo especial se maneja con SKU/canal separado si se autoriza.

## Costos

La materia prima se paga una sola vez en Compras.

Cuando se empaca:

- no se vuelve a pagar el producto terminado;
- el costo de la materia prima se transfiere al producto terminado;
- si se controla empaque, tambien se suma bolsa/etiqueta;
- si se desea mas formalidad, se pueden sumar mano de obra, merma y costos indirectos.

Ejemplo simple:

- Materia prima recibida: `100 kg`.
- Costo total compra: `$2,000`.
- Costo materia prima: `$20/kg`.
- Produccion consume `20 kg`.
- Costo transferido: `$400`.
- Produce `40 bolsas de 500 g`.
- Costo materia prima por bolsa: `$10`.

## Papel de recepcion variable

Recepcion variable debe conservarse porque resuelve una necesidad real:

- proveedor entrega unidades fisicas cuyo contenido real cambia;
- proveedor puede vender en litros, pero el negocio controla por kg;
- el inventario necesita recibir la cantidad real fisica, no una equivalencia teorica.

No debe usarse para:

- crear productos terminados automaticamente;
- vender materia prima como producto de tienda normal;
- reemplazar Produccion/Preparacion;
- inventar unidades operativas como paca, saco o costal en el catalogo formal.

## Modulo futuro recomendado

Nombre recomendado:

- `Produccion / Empaque`

Primera version posible:

- Puede crecer desde `Almacen > Preparacion/Empaque`.
- Debe soportar materia prima, producto terminado, recetas simples, merma y etiquetas.

Cuando el negocio requiera mas formalidad:

- separar como modulo `Produccion`;
- agregar ordenes de produccion;
- agregar insumos de empaque;
- agregar mano de obra/costos indirectos;
- agregar control de merma y rendimiento.

## Tareas sugeridas

| ID | Tarea | Modulo | Autorizacion |
| --- | --- | --- | --- |
| PROD-MP-001 | Auditar Catalogo para distinguir materia prima, producto terminado y vendible por canal | Catalogo | No |
| PROD-MP-002 | Disenar recetas simples materia prima -> producto terminado | Catalogo/Produccion | No |
| PROD-MP-003 | Auditar Preparacion/Empaque actual y decidir si evoluciona a Produccion | Almacen | No |
| PROD-MP-004 | Definir costos de produccion simples: materia prima + empaque + merma | Costos | No |
| PROD-MP-005 | Proponer DDL si faltan tipos de SKU, canal vendible o recetas | Catalogo/Produccion | Si |
| PROD-MP-006 | UAT con materia prima variable y producto terminado empacado | Almacen/Produccion/Inventario | Si, con respaldo |

## Handoff / continuidad

Fecha: 2026-06-27

- Contexto actual: el negocio compra materias primas que no quiere vender como producto normal; las transforma en presentaciones propias.
- Decision: conservar recepcion variable para materia prima, pero separar producto terminado vendible.
- Pendiente: disenar modulo `Produccion / Empaque` o evolucionar `Almacen > Preparacion/Empaque`.
- Impacta a: Catalogo, Compras, Almacen/Recepcion, Inventario, Costos, Ventas/POS/Ecommerce.
- Siguiente paso recomendado: auditar Catalogo para ver si ya existe campo/tipo que permita marcar un SKU como `materia_prima`, `producto_terminado`, `insumo_empaque` y si se puede controlar venta por canal.
