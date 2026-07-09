# ERP Catalogo - Guia operativa para captura y saneamiento

Documentacion viva: Codex GPT-5  
Fecha: 2026-06-24  
Estado: Guia de uso para operador de Catalogo

## Proposito

Esta guia explica como debe trabajar una persona encargada de Catalogo Productos sin tener que decidir costos, precios finales, stock fisico, rentabilidad o reglas complejas de otros modulos.

Catalogo es la fuente de identidad del producto y del SKU. Su objetivo principal es que el ERP sepa que es el producto, como se identifica, como se clasifica y que le falta para que otros modulos lo puedan usar.

## Regla base

Catalogo debe capturar lo que conoce con evidencia razonable y dejar alertas sobre lo que falta.

No debe inventar:

- costo de compra;
- precio final por canal;
- existencia fisica;
- margen o rentabilidad;
- datos fiscales si no hay evidencia;
- reglas fisicas de almacen si no estan confirmadas.

## Estatus del maestro

El estatus indica la vida del registro, no si ya se puede vender.

| Estatus | Significado operativo |
| --- | --- |
| `borrador` | Captura inicial incompleta. Puede venir de proveedor, incidencia o alta rapida. |
| `en_revision` | Hay duda de identidad, unidad, variante, marca, categoria o duplicado. |
| `activo` | Maestro vigente para el ERP. No significa listo para venta. |
| `inactivo` | No se usa por ahora, pero se conserva historial. |
| `descontinuado` | Ya no debe comprarse normalmente; puede conservar existencia o historial. |
| `fusionado` | Registro absorbido por otro maestro; requiere correccion controlada si hubo error. |

Regla:

- No eliminar productos/SKUs con posible historial operativo.
- Usar `inactivo` o `descontinuado` para limpiar ruido visual.
- Usar filtros de archivados para revisar o recuperar.

## Orden recomendado para sanear productos

1. Confirmar que el producto/SKU existe y no esta duplicado.
2. Revisar nombre, marca, categoria y variante.
3. Confirmar unidad base y factor base razonables.
4. Capturar codigo interno o codigo de barras si se usara en busqueda/escaneo.
5. Capturar fiscal si se conoce con evidencia.
6. Revisar si tiene proveedor relacionado, pero no inventar costo.
7. Capturar precio provisional solo si hoy se necesita como puente temporal.
8. Revisar reglas de inventario solo si el producto ya se recibira, movera o vendera.
9. Configurar granel/presentaciones solo si ya hay necesidad operativa.
10. Agregar imagenes/ecommerce solo si se publicara o vendera por canal digital.

## Indicadores por objetivo

Los indicadores no son estatus nuevos. Son avisos para orientar que falta y que area debe resolverlo.

| Indicador | Responsable principal | Que hacer desde Catalogo |
| --- | --- | --- |
| Fiscal pendiente | Fiscal/Compras/Catalogo | Capturar lo conocido; dejar pendiente si falta evidencia. |
| Compra sin proveedor | Proveedores/Compras | No inventar proveedor; mandar a relacion proveedor-SKU. |
| Inventario revisar | Inventario/Almacen | Capturar intencion basica; validar fisicamente en Inventario/Almacen. |
| Venta sin codigo | Catalogo/Ventas | Capturar codigo si existe; si no, decidir si el canal lo requiere. |
| Venta sin precio provisional | Precios/Ventas | No inventar precio final; usar precio provisional solo como puente. |
| Calidad N | Catalogo u otro modulo origen | Revisar incidencia, responsable sugerido y evidencia. |

## Que si debe capturar Catalogo

- Producto maestro: nombre, descripcion base, marca, categoria.
- SKU: codigo SKU, nombre, unidad base, tipo de inventario.
- Variante: atributos que diferencian SKUs internos.
- Fiscal parcial si se conoce: clave SAT producto, clave unidad SAT, objeto impuesto, IVA/IEPS.
- Imagenes y clasificacion comercial base si se usaran.
- Reglas base de granel/presentaciones cuando ya estan definidas.

## Que no debe resolver solo Catalogo

| Tema | Modulo responsable |
| --- | --- |
| Costo validado, condiciones de compra, lista proveedor | Proveedores/Compras/Costos |
| Precio final, lista por canal, promociones | Precios/Ventas/Comercial |
| Existencia real, lotes, ubicaciones, etiquetas fisicas | Almacen/Inventario |
| Preparacion/empaque real | Almacen/Preparacion |
| Margen y rentabilidad | Costos/Rentabilidad |
| Validacion fiscal formal | Fiscal/Contabilidad |

## Producto listo como maestro

Un SKU esta razonablemente completo como maestro cuando:

- tiene SKU unico;
- nombre claro;
- producto maestro correcto;
- unidad base definida;
- estatus vigente o en revision justificada;
- variante clara si aplica;
- no esta duplicado o pendiente de fusion.

Esto no significa que este listo para comprar, vender, publicar o calcular rentabilidad.

## Cuando mandar a otro modulo

Mandar a Proveedores/Compras cuando:

- falta relacion proveedor-SKU;
- el proveedor usa otro codigo;
- hay costo/lista/proveedor sin validar;
- el proveedor no distingue variantes.

Documento de continuidad: `docs/erp_catalogo_handoff_proveedores_listas.md`.

Mandar a Precios/Ventas cuando:

- falta precio final;
- se requiere precio por canal;
- hay variantes con precios diferentes;
- se vendera en ecommerce, mostrador o mayoreo con politicas distintas.

Mandar a Almacen/Inventario cuando:

- hay duda de unidad fisica;
- se vende a granel;
- requiere lote, caducidad, serie o etiqueta;
- se preparan presentaciones desde un SKU base.

Mandar a Fiscal/Contabilidad cuando:

- falta clave SAT o unidad SAT;
- el XML/proveedor contradice el maestro;
- no esta claro si aplica IVA/IEPS u objeto de impuesto.

## Reglas para fusionar

- Fusionar productos es accion de alto impacto.
- Siempre debe tener motivo claro.
- No usar fusion como limpieza rapida sin revisar SKUs, imagenes, categorias y uso operativo.
- La reversa automatica no debe habilitarse hasta tener snapshot y detalle de entidades movidas.

## Criterio de cierre de captura

La captura de Catalogo se puede considerar atendida cuando:

- el SKU queda identificable;
- no hay duplicado obvio;
- se sabe que modulo debe resolver cada alerta restante;
- las incidencias abiertas tienen responsable o razon de permanencia;
- el producto no queda marcado como activo con informacion contradictoria grave.
