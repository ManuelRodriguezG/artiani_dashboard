# ERP Catalogo - Handoff para modulos consumidores

Fecha: 2026-06-29  
Documentacion IA: Codex GPT-5

## Objetivo

Dejar claro que informacion de Catalogo ERP deben consumir otros modulos y que decisiones no deben duplicarse fuera de Catalogo.

## Regla general

Catalogo ERP define el maestro del producto y sus datos estructurales. Otros modulos pueden validar, bloquear o completar su propia operacion, pero no deben reinventar categorias, unidades, atributos, marcas ni estructura visual del producto.

## Compras

Debe consumir:

- SKU ERP activo.
- Relacion SKU-proveedor cuando aplique.
- Unidad y factor de compra definidos para el proveedor/SKU.
- Alertas de producto incompleto cuando falte proveedor o configuracion de compra.

No debe decidir:

- Categoria principal del producto.
- Marca maestra.
- Atributos de variante.
- Imagenes de marca o categoria.

## Almacen y recepciones

Debe consumir:

- Unidad base del SKU.
- Reglas de inventario del SKU.
- Configuracion de recepcion variable cuando exista.
- Factor de conversion de compra a unidad base cuando la recepcion venga desde una compra/proveedor.

No debe crear unidades operativas como `costal`, `saco` o `paca` solo por lenguaje de captura si la unidad formal es kg, lt, m o pza.

## Inventario y existencias

Debe consumir:

- Unidad base de inventario.
- Precision decimal.
- Reglas de lote, caducidad, serie, etiqueta interna y control de existencia.
- Presentaciones preparables cuando exista flujo de empaque o armado.

No debe cambiar:

- Nombre comercial del SKU.
- Categoria principal.
- Marca.
- Atributos maestros.

## Ventas y canales

Debe consumir:

- SKU activo y permitido para venta segun reglas comerciales del modulo de ventas/precios.
- Presentaciones de venta configuradas.
- Atributos de variante.
- Imagen principal de producto.
- En el futuro, imagenes de marca/categoria solo como recurso visual, no como dato fiscal ni de inventario.

No debe tomar como suficiente:

- `estatus=activo` de Catalogo para publicar o vender. Ese estado solo indica que el maestro no esta dado de baja.
- Clasificacion heredada como categoria final.

## Costos, rentabilidad y listas de precios

Debe consumir:

- SKU ERP.
- Relacion con proveedor y costo validado desde Proveedores/Compras/Costos.
- Categoria principal solo para analisis y agrupacion.

No debe exigir al capturista normal de Catalogo conocer o capturar costos.

## Garantias

Debe consumir:

- SKU ERP.
- Categoria principal, marca y proveedor como criterios posibles para politicas de garantia.
- Atributos o tipo de producto solo cuando la politica lo requiera.

No debe guardar reglas de garantia dentro del producto; deben vivir en el modulo de Garantias como politicas reutilizables.

## Categorias

Regla:

- `erp_catalogo_categorias` es el arbol maestro ERP.
- `erp_catalogo_producto_categorias.es_principal=1` define la categoria principal del producto.
- La clasificacion heredada solo ayuda a construir o corregir el arbol maestro.

Consumidores:

- Compras: agrupacion y busqueda.
- Almacen: referencia operativa, no regla de existencia por si sola.
- Ventas/canales: navegacion y agrupacion comercial.
- Costos: analisis de margen por familia.
- Garantias: criterio opcional de politica.

## Imagenes de marcas y categorias

Estado:

- UI preparada.
- DDL pendiente de autorizacion.

Regla:

- Marcas y categorias tendran imagenes propias cuando se aplique el esquema.
- Estas imagenes son recurso visual, no sustituyen imagenes de producto ni evidencia de recepcion.

Token pendiente:

- `CATALOGO_IMAGENES_MARCAS_CATEGORIAS`

## Criterio para otros chats/modulos

Antes de modificar Compras, Almacen, Inventario, Ventas, Costos o Garantias por un dato de Catalogo, revisar:

- `docs/erp_catalogo_avance.md`
- `docs/erp_catalogo_configuracion_plan.md`
- este documento de handoff

Si el modulo necesita un dato nuevo de Catalogo, documentar primero si es:

- dato maestro del producto;
- regla operativa de inventario/almacen;
- dato comercial de ventas/precios;
- dato financiero/costo;
- politica reutilizable de otro modulo.
