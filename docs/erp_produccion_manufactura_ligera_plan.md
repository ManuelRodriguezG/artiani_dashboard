# ERP Produccion - Plan robusto para productos fabricados

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: Plan maestro inicial para ordenar fabricacion interna antes de uso real  
Relacionados:

- `docs/erp_plan_maestro_fundamentos.md`
- `docs/erp_produccion_materia_prima_arranque.md`
- `docs/erp_catalogo_venta_granel.md`
- `docs/erp_almacen_recepciones_tareas_vivas.md`
- `docs/erp_inventario_existencias_arranque.md`

## Objetivo

Diseñar un flujo ERP robusto para un negocio que no solo revende productos, sino que tambien fabrica, empaca o transforma productos propios.

Casos reales del negocio:

- Heno de alfalfa comprado como materia prima y empacado en presentaciones propias.
- Sustratos para reptiles comprados por presentacion/proveedor en litros, pero controlados y empacados por peso.
- Peceras fabricadas internamente con vidrio cortado, silicon, accesorios, mano de obra y posibles cargos de corte.

## Diagnostico de fondo

El modulo de Compras no debe limitarse a comprar productos terminados vendibles.

En una empresa bien estructurada, Compras puede comprar:

- productos terminados para reventa;
- materia prima;
- componentes;
- insumos de empaque;
- herramientas o consumibles;
- servicios;
- cargos relacionados a una compra;
- gastos.

La diferencia no esta en si se compra o no; la diferencia esta en que efecto tiene cada partida:

- inventario de producto terminado;
- inventario de materia prima;
- inventario de componente;
- costo/cargo sin stock;
- gasto operativo;
- costo acumulable a produccion.

## Decision principal

El ERP debe evolucionar de "comprar productos de venta" a "comprar articulos y servicios del negocio".

Catalogo ERP no debe ser solo catalogo de venta. Debe convertirse en maestro de articulos/SKUs del ERP:

- SKU vendible;
- SKU comprable;
- SKU inventariable;
- SKU producible;
- SKU materia prima;
- SKU componente;
- SKU insumo de empaque;
- SKU servicio/cargo/gasto.

La venta al cliente se controla por canales y banderas de venta, no por existencia o ausencia en Catalogo.

## Tipos recomendados de SKU

### Producto terminado

Producto listo para vender.

Ejemplos:

- bolsa de heno de 500 g;
- sustrato empacado de 1 kg;
- pecera 60 x 40 x 40 cm.

Reglas:

- vendible: si;
- inventariable: si;
- producible: normalmente si;
- comprable: solo si el proveedor lo entrega ya listo para vender.

### Materia prima

Material principal que se transforma.

Ejemplos:

- heno de alfalfa materia prima;
- peatmoss materia prima;
- fibra de coco materia prima;
- vidrio por m2.

Reglas:

- comprable: si;
- inventariable: si;
- vendible tienda/ecommerce: no;
- producible: no como resultado final, pero si consumible por produccion;
- puede requerir recepcion variable.

### Componente

Parte que se usa dentro de un producto fabricado.

Ejemplos:

- vidrio cortado frontal;
- vidrio lateral;
- tapa;
- perfil;
- accesorio incluido.

Reglas:

- comprable o producible;
- inventariable si se controla fisicamente;
- consumible por produccion;
- no vendible salvo que se autorice como refaccion.

### Insumo de empaque

Material usado para empacar producto terminado.

Ejemplos:

- bolsa;
- etiqueta;
- caja;
- sello;
- empaque protector.

Reglas:

- comprable: si;
- inventariable: recomendable si impacta costo o disponibilidad;
- consumible por produccion/empaque;
- no vendible normal.

### Servicio/cargo

No crea stock fisico, pero puede afectar costo.

Ejemplos:

- corte de vidrio;
- flete;
- maquila externa;
- mano de obra externa;
- cargo especial del proveedor.

Reglas:

- comprable: si;
- inventariable: no;
- puede capitalizarse al costo de una recepcion/produccion;
- puede ser gasto si no se asigna a producto.

### Gasto

Compra que no entra a inventario ni costo de producto.

Ejemplos:

- papeleria;
- mantenimiento general;
- renta;
- servicios administrativos.

Reglas:

- comprable: si;
- inventariable: no;
- afecta finanzas/gastos, no inventario.

## Flujo objetivo

### 1. Catalogo ERP

Catalogo define el articulo/SKU y sus reglas:

- tipo de SKU;
- unidad base;
- comprable;
- vendible;
- producible;
- inventariable;
- consumible en produccion;
- canales permitidos;
- si requiere lote/caducidad/serie/etiqueta;
- si requiere recepcion variable;
- si usa receta/lista de materiales.

Catalogo no crea stock ni costos reales por si solo.

### 2. Proveedores / Relaciones proveedor-SKU

Un proveedor puede vender:

- materia prima;
- componente;
- producto terminado;
- insumo;
- servicio/cargo.

La relacion proveedor-SKU debe poder decir:

- proveedor;
- SKU interno;
- SKU/prod proveedor;
- unidad en la que se compra;
- factor si es fijo;
- si la recepcion requiere captura real;
- costo esperado;
- minimo de compra;
- evidencia/lista vigente.

### 3. Compras

Compras registra la obligacion real con proveedor.

Una orden puede contener:

- partidas inventariables;
- partidas de materia prima;
- componentes;
- insumos;
- cargos o servicios;
- gastos.

Compras no debe decidir el producto terminado fabricado. Compra lo que se pidio.

Ejemplo heno:

- Compra: 5 unidades fisicas esperadas de heno materia prima.
- Costo: por kg o por unidad segun proveedor.
- Recepcion captura peso real.

Ejemplo vidrio:

- Compra: vidrio o piezas cortadas.
- Costo base: por m2.
- Cargo: corte de vidrio.
- Adjuntos: plano, medidas o lista de cortes.

### 4. Almacen / Recepcion

Recepcion confirma lo fisico:

- productos terminados comprados;
- materia prima;
- componentes;
- insumos.

No crea stock para servicios, cargos o gastos.

Si aplica recepcion variable:

- captura unidades fisicas;
- captura cantidad real en unidad base;
- genera existencia real.

### 5. Inventario

Inventario muestra y controla:

- materia prima disponible;
- componentes disponibles;
- insumos disponibles;
- producto terminado disponible;
- kardex;
- lotes;
- unidades fisicas;
- etiquetas.

Inventario no produce, no compra y no vende.

### 6. Produccion / Empaque

Modulo dueño de transformar materiales en producto terminado.

Debe registrar:

- folio de produccion;
- producto terminado a producir;
- cantidad a producir;
- materia prima consumida;
- componentes consumidos;
- insumos de empaque consumidos;
- merma;
- observaciones;
- responsable;
- entrada de producto terminado;
- etiquetas del producto terminado.

Movimientos:

- salida de materia prima;
- salida de componentes/insumos;
- entrada de producto terminado;
- todos ligados al mismo folio.

### 7. Costos

Costos calcula el costo del producto terminado.

Primera version:

- costo materia prima consumida;
- costo componentes;
- costo empaque;
- cargos asignados;
- merma.

Version posterior:

- mano de obra;
- indirectos;
- energia;
- depreciacion;
- costo estandar vs costo real.

### 8. Ventas / POS / Ecommerce

Ventas solo vende lo que Catalogo autorice como vendible por canal.

Reglas:

- materia prima no se vende en tienda normal;
- componentes no se venden salvo refaccion autorizada;
- producto terminado si se vende;
- mayoreo puede tener SKUs o canal separado;
- ecommerce solo publica producto terminado estable.

## Casos del negocio

### Heno de alfalfa

Recomendacion:

- `ALIHA-MP`: materia prima, kg, recepcion variable, no vendible.
- `ALIHA-500G`: producto terminado, pza, vendible.
- `ALIHA-1KG`: producto terminado, pza, vendible.
- `ALIHA-PACA-MAYOREO`: opcional, canal mayoreo/especial, no tienda normal.

Flujo:

- Compra materia prima.
- Recepcion pesa real.
- Produccion empaca.
- Ventas vende bolsas.

### Sustratos

Recomendacion:

- Materia prima por tipo: peatmoss, fibra de coco, mezcla, etc.
- Unidad base interna: `kg`, si se empaca y vende por peso.
- Presentacion proveedor en litros queda como dato operativo/proveedor, no unidad principal de inventario si el negocio controla por peso.

Flujo:

- Compra presentacion proveedor.
- Recepcion pesa real.
- Produccion empaca por gramos/kg.
- Ventas vende producto empacado.

### Peceras

Recomendacion inicial:

- `VIDRIO-6MM-MP`: materia prima/componente en `m2`.
- `CORTE-VIDRIO`: servicio/cargo de compra, no inventariable.
- `SILICON-MP` o `SILICON-INS`: insumo.
- `PECERA-60X40X40`: producto terminado.

Si el proveedor ya entrega piezas cortadas:

- Opcion inicial robusta:
  - comprar vidrio por `m2`;
  - registrar cargo de corte;
  - adjuntar/observar medidas;
  - recibir inventario de vidrio/componente en m2;
  - produccion consume m2 segun receta.

- Opcion avanzada:
  - registrar piezas cortadas como componentes/unidades fisicas con medidas;
  - largo, alto, espesor, m2 calculado;
  - cada pieza puede rastrearse hasta una pecera fabricada.

Recomendacion para empezar:

- No iniciar con despiece pieza por pieza si todavia no es indispensable.
- Empezar con m2 + cargo de corte + receta por modelo de pecera.
- Evolucionar a piezas individuales cuando el control de merma/corte lo justifique.

## Orden recomendado de implementacion

### Fase 0 - Diagnostico y frontera

Objetivo:

- Saber que ya existe y que falta sin tocar BD.

Tareas:

- Auditar Catalogo: tipos actuales de SKU y reglas.
- Auditar Compras: tipos de partida actuales.
- Auditar Recepcion: que partidas generan stock y cuales no.
- Auditar Preparacion/Empaque actual.
- Auditar Costos: costo promedio, cargos y costo real.

Cierre:

- Documento de brechas por modulo.

### Fase 1 - Catalogo maestro de articulos

Objetivo:

- Que el ERP pueda distinguir materia prima, producto terminado, componente, insumo, servicio y gasto.

Tareas:

- Definir tipos de SKU.
- Definir banderas:
  - comprable;
  - vendible;
  - producible;
  - consumible_produccion;
  - inventariable;
  - canal tienda/ecommerce/mayoreo.
- Proponer DDL si falta estructura.
- No migrar masivamente sin plan.

Cierre:

- Catalogo puede clasificar articulos sin mezclarlos con ecommerce.

### Fase 2 - Compras ampliado

Objetivo:

- Que Compras pueda comprar todo lo que la empresa necesita sin forzar que todo sea producto terminado.

Tareas:

- Permitir partidas:
  - inventariable;
  - servicio/cargo;
  - gasto;
  - materia prima;
  - componente;
  - insumo.
- Definir que partidas van a Recepcion.
- Definir que cargos afectan costo de recepcion o produccion.
- Validar XML/CFDI para conceptos no inventariables.

Cierre:

- OC puede mezclar materiales y cargos sin crear stock incorrecto.

### Fase 3 - Recepcion de materia prima/componentes

Objetivo:

- Que Almacen reciba correctamente materiales comprados.

Tareas:

- Usar recepcion variable cuando aplique.
- No recibir servicios/cargos como stock.
- Generar existencias de materia prima/componentes.
- Etiquetar unidades fisicas si aplica.

Cierre:

- Materia prima entra a inventario real.

### Fase 4 - Produccion / Empaque V1

Objetivo:

- Transformar materia prima en producto terminado con kardex.

Tareas:

- Crear orden/preparacion de produccion.
- Seleccionar SKU terminado.
- Consumir materia prima.
- Consumir insumos opcionales.
- Capturar merma.
- Generar entrada de producto terminado.
- Generar etiquetas.

Cierre:

- Producto terminado nace por produccion, no por compra ficticia.

### Fase 5 - Costeo V1

Objetivo:

- Transferir costo real de materiales al producto terminado.

Tareas:

- Calcular costo de materia prima consumida.
- Sumar insumos.
- Sumar cargos asignados.
- Calcular costo por unidad terminada.
- Documentar merma.

Cierre:

- Producto terminado tiene costo razonable para ventas/rentabilidad.

### Fase 6 - Ventas por canal

Objetivo:

- Vender solo productos autorizados.

Tareas:

- POS ve producto terminado.
- Ecommerce ve producto terminado publicable.
- Mayoreo puede ver SKUs especiales.
- Materia prima queda oculta de venta normal.

Cierre:

- El negocio se presenta formalmente sin exponer materia prima como producto de tienda.

## Hallazgos esperados

Usar IDs:

- `PROD-H###` para Produccion.
- `CAT-PROD-H###` para Catalogo.
- `COM-PROD-H###` para Compras.
- `COST-PROD-H###` para Costos.

## Reglas de trabajo

- No aplicar migraciones sin respaldo externo y autorizacion.
- No mover inventario sin folio y UAT.
- No crear productos terminados desde Compras.
- No vender materia prima en canales normales.
- No usar Catalogo como lugar donde se crea stock.
- No mezclar ERP nuevo con legacy.
- Explicar decisiones de ERP robusto antes de implementarlas.

## Prompts sugeridos por modulo

### Catalogo

```text
Trabaja en ERP > Catalogo para preparar maestro de articulos para produccion/manufactura ligera.

Lee:
- AGENTS.md
- docs/erp_plan_maestro_fundamentos.md
- docs/erp_produccion_manufactura_ligera_plan.md
- docs/erp_produccion_materia_prima_arranque.md
- app/controladores/CatalogoErp.php
- app/modelos/CatalogoErpDatos.php
- app/modelos/CatalogoErpEsquema.php

Objetivo:
Auditar y proponer como distinguir materia prima, producto terminado, componente, insumo, servicio y gasto.

Reglas:
- No crear stock desde Catalogo.
- No publicar materia prima en ventas/ecommerce.
- No aplicar DDL sin respaldo y autorizacion.

Criterio:
Documento de brechas, propuesta de campos/DDL y orden de implementacion.
```

### Compras

```text
Trabaja en ERP > Compras para ampliar compras a materia prima, componentes, insumos, servicios, cargos y gastos.

Lee:
- AGENTS.md
- docs/erp_plan_maestro_fundamentos.md
- docs/erp_produccion_manufactura_ligera_plan.md
- docs/erp_compras_vision_operativa.md
- app/controladores/Compra.php
- app/modelos/OrdenesCompraErp.php
- app/modelos/ComprasEsquema.php

Objetivo:
Auditar si Compras puede comprar articulos no vendibles y cargos sin generar stock incorrecto.

Reglas:
- Compras registra obligacion con proveedor.
- Compras no produce producto terminado.
- Servicios/cargos no entran a inventario como stock.
- No aplicar DDL sin respaldo y autorizacion.

Criterio:
Flujo propuesto para OC con materia prima + cargo de corte/flete/servicio.
```

### Almacen / Recepcion

```text
Trabaja en ERP > Almacen > Recepciones para recibir materia prima/componentes.

Lee:
- AGENTS.md
- docs/erp_produccion_manufactura_ligera_plan.md
- docs/erp_almacen_recepciones_tareas_vivas.md
- app/controladores/Almacen.php
- app/modelos/Almacenes.php

Objetivo:
Validar recepcion de materia prima con cantidad real variable y recepcion de componentes/insumos sin tocar producto terminado.

Reglas:
- Recepcion crea existencia de lo fisico recibido.
- No recibe servicios/cargos como stock.
- No produce producto terminado.
- Respaldo antes de UAT con escrituras.
```

### Produccion / Empaque

```text
Trabaja en ERP > Produccion / Empaque para fabricar producto terminado desde materia prima.

Lee:
- AGENTS.md
- docs/erp_produccion_manufactura_ligera_plan.md
- docs/erp_produccion_materia_prima_arranque.md
- docs/erp_almacen_preparacion_empaque_diseno.md
- app/controladores/Almacen.php
- app/modelos/Almacenes.php
- app/modelos/InventarioErp.php

Objetivo:
Diseñar V1 de orden/preparacion de produccion: salida de materia prima + entrada de producto terminado + etiquetas + merma.

Reglas:
- No comprar producto terminado fabricado internamente.
- Todo debe dejar kardex.
- No tocar Ventas/ecommerce.
- No aplicar DDL sin respaldo y autorizacion.
```

### Costos

```text
Trabaja en ERP > Costos para costear producto fabricado.

Lee:
- AGENTS.md
- docs/erp_produccion_manufactura_ligera_plan.md
- docs/erp_costos_rentabilidad_arranque.md

Objetivo:
Diseñar costeo V1 para producto terminado: materia prima consumida + empaque + cargos asignados + merma.

Reglas:
- No inventar utilidad desde Inventario.
- No cambiar costos historicos sin respaldo.
- Separar costo real, costo promedio y precio de venta.
```

## Handoff / continuidad

Fecha: 2026-06-27

- Contexto actual: el negocio fabrica/empaqueta algunos productos y necesita ERP formal para comprar materia prima, producir y vender producto terminado.
- Decision: Catalogo ERP debe evolucionar a maestro de articulos, no solo catalogo de venta.
- Primer modulo recomendado: Catalogo, porque todos los demas necesitan saber si un SKU es materia prima, producto terminado, componente, insumo, servicio o gasto.
- Segundo modulo recomendado: Compras ampliado, para poder comprar materiales y cargos sin simular producto terminado.
- Tercer modulo recomendado: Produccion/Empaque, apoyandose en lo ya hecho en Almacen.
- Riesgo principal: seguir comprando o recibiendo todo como si fuera producto vendible; eso distorsiona inventario, costos y ventas.

## Decision de fase temporal - Produccion diferida

Fecha: 2026-06-27  
Estado: Documentado para retomar despues

Contexto:

- El objetivo inmediato del negocio es controlar productos comprados/vendidos, estabilizar inventario y preparar venta en linea.
- Construir Produccion/Manufactura ligera completa ahora puede retrasar demasiado la salida operativa.
- El negocio necesita manejar algunos productos propios ya empacados como stock vendible antes de tener recetas, consumo de materia prima, merma y costeo formal.

Decision:

- Produccion/Manufactura ligera queda como fase posterior.
- En fase 1 se permite una solucion temporal controlada para producto terminado propio:
  - registrar stock de producto terminado ya empacado mediante entrada interna documentada;
  - usar un proveedor interno/controlado o tipo de origen equivalente, por ejemplo `Produccion interna`;
  - no mezclar esta entrada con compras reales a proveedores externos;
  - no simular que se compro de nuevo la materia prima;
  - dejar evidencia/observacion que explique que el producto terminado proviene de empaque/fabricacion interna temporal.

Objetivo de la solucion temporal:

- Tener existencias vendibles para POS/ecommerce.
- Evitar bloquear el avance por no tener aun Produccion formal.
- Mantener trazabilidad minima y evitar inventario ficticio sin origen.

Reglas temporales:

- Solo aplica a producto terminado propio ya empacado o fabricado fisicamente.
- No debe usarse para crear materia prima.
- No debe usarse para esconder faltantes de inventario.
- No debe generar cuenta por pagar real si no hay proveedor externo.
- El costo puede ser estimado/documentado hasta que exista Costos/Produccion formal.
- Usar folios, notas o referencias claras como `produccion interna temporal`, `entrada interna producto terminado` o equivalente.
- Cuando exista Produccion formal, estas entradas temporales deben quedar historicas; no se deben reescribir sin respaldo y autorizacion.

Frontera de fase 1:

- Catalogo: productos terminados propios pueden existir y ser vendibles.
- Inventario: puede tener stock de producto terminado propio por entrada interna controlada.
- Ventas/ecommerce: pueden vender productos terminados propios.
- Compras: sigue controlando proveedores externos y productos comprados; no debe forzarse todavia a manejar recetas de produccion.

Fase 2 futura:

- Crear modulo formal `Produccion / Empaque`.
- Registrar materia prima, componentes e insumos.
- Crear recetas/listas de materiales.
- Consumir materia prima y generar producto terminado.
- Calcular costo real del producto terminado.
- Registrar merma, etiquetas y trazabilidad completa.

Riesgo aceptado:

- Durante fase 1, el costo de producto terminado propio puede ser aproximado.
- La trazabilidad de materia prima hacia producto terminado sera limitada.
- Este riesgo se acepta para poder iniciar control de inventario y venta en linea mas rapido.

Criterio para retomar Produccion formal:

- Cuando Catalogo, Inventario, Recepciones, Ventas/POS y ecommerce base esten operando.
- Cuando el volumen de producto propio haga necesario costear materia prima, merma, empaque y mano de obra.
- Cuando el negocio quiera saber utilidad real por producto fabricado.
