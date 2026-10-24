# ERP Catalogo - Metodologia de variantes, atributos y derivados

Documentacion IA: Codex GPT-5
Fecha: 2026-10-23
Estado: metodologia propuesta; no implica migraciones ni cambios masivos.
Alcance: Catalogo ERP, SKU, presentaciones, aperturas, paquetes y Ecommerce.

## Proposito

Establecer una regla repetible para decidir si una diferencia entre productos debe
modelarse como atributo, variante real, SKU derivado, presentacion, apertura de
empaque, paquete o producto separado. El objetivo es conservar identidad,
trazabilidad, inventario y filtros utiles sin crear variantes por diferencias
textuales.

La regla se aplica antes de fusionar productos, mover SKUs, cambiar atributos,
crear relaciones de presentacion o publicar en Ecommerce. Toda propuesta de
conversion debe ser auditable y autorizada; este documento no autoriza escrituras.

## Definiciones formales

### Producto maestro

Es la identidad comercial comun de una familia de opciones que el cliente entiende
como "el mismo producto". Agrupa SKUs cuando comparten marca, linea, funcion,
formulacion o modelo base, uso esperado y ciclo de vida comercial. No significa
que compartan precio, proveedor, existencia, fiscal o codigo.

### Variante real

Una variante real es un SKU vendible que pertenece al mismo producto maestro y
representa una opcion elegible del mismo articulo comercial mediante uno o mas
atributos de opcion controlados (por ejemplo color, talla, medida funcional,
sabor o modelo dentro de la misma linea). La opcion debe ser comprensible para el
cliente, comparable en Ecommerce y suficientemente distinta para tener identidad
operativa propia.

Una variante real debe cumplir simultaneamente:

1. Misma identidad de producto maestro y misma promesa principal.
2. Diferencia expresable con un atributo estructurado, no solo con texto libre.
3. Seleccion comparable en la ficha o listado de Ecommerce.
4. SKU, codigo y trazabilidad propios.
5. Puede tener precio, costo, inventario, proveedor, imagen o fiscal propios sin
   dejar de ser la misma familia comercial.
6. No es solamente una cantidad, conversion, empaque abierto, receta o paquete.

Si falla el punto 1, es producto separado. Si falla el punto 2 o 3 pero requiere
control propio, es SKU derivado o producto separado, no variante visual.

### Atributo descriptivo

Es un dato estructurado para comparar, filtrar, buscar o explicar productos, pero
no crea una opcion de seleccion del mismo producto. Ejemplos: material, especie
recomendada, edad, compatibilidad, dimensiones tecnicas, contenido informativo,
origen o instrucciones.

Un atributo puede tener `es_variante=0` aun cuando cambie entre SKUs. La existencia
de un valor distinto nunca es suficiente para crear una variante.

### SKU derivado

Es un SKU con identidad operativa propia que deriva de otro SKU o producto por
conversion, empaque, proceso, canal o composicion, pero que no debe aparecer como
opcion de color/talla/modelo del producto base. Conserva la relacion origen,
factor, regla de disponibilidad y trazabilidad.

Casos: bolsa propia de 100 g, SKU cerrado que se abre para generar granel,
producto preparado, presentacion con empaque propio o salida de una receta.

## Arbol de decision

Aplicar las preguntas en este orden:

1. ¿Es el mismo articulo o solucion que el cliente reconoce como la misma linea?
   No: producto separado. Sí: continuar.
2. ¿La diferencia es una opcion que el cliente elegiria en la misma ficha?
   Sí: continuar como posible variante. No: continuar como derivado/atributo.
3. ¿La diferencia cambia cantidad, peso, volumen, unidad, empaque o factor?
   Sí: presentacion o SKU derivado; nunca variante por defecto.
4. ¿La diferencia abre, fracciona, reempaca, transforma o convierte inventario?
   Sí: apertura, presentacion o SKU derivado con relacion de consumo.
5. ¿La diferencia combina dos o mas SKUs o permite elegir componentes?
   Sí: paquete/receta.
6. ¿La diferencia solo describe compatibilidad, uso, especie o material?
   Sí: atributo descriptivo.
7. ¿La diferencia altera categoria, fiscalidad, garantia, manejo, proveedor
   principal o promesa comercial hasta volver engañosa la agrupacion?
   Sí: producto separado o revision manual.
8. Si quedan respuestas mezcladas, marcar `revisar_manual`; no inferir por nombre.

## Matriz de decision

| Caso | Modelo recomendado | ¿SKU propio? | ¿Selector Ecommerce? | Evidencia minima |
|---|---|---:|---:|---|
| Color intercambiable del mismo modelo | Variante real | Sí | Sí | misma linea/modelo; color controlado |
| Talla de la misma prenda/arnes | Variante real | Sí | Sí | misma funcion; talla normalizada |
| Medida funcional de la misma linea | Variante real | Sí | Sí | dimensiones y uso comparables |
| Capacidad/peso de alimento cerrado | Presentacion o SKU derivado | Sí | No por defecto | unidad, factor, precio y stock propios |
| 25 g, 50 g, 100 g, 500 g | Presentacion de venta | Sí | Sí como opciones de presentacion, no como variante de color/talla | `id_sku_base`, factor y modo de disponibilidad |
| Compra en costal/caja/paquete del proveedor | Unidad de compra en SKU-proveedor | No adicional si es solo compra | No | `id_unidad_compra` y `factor_conversion` |
| Producto cerrado que se abre | Apertura de empaque | Sí para origen/destino | No como variante | origen, destino, factor, lote/caducidad y merma |
| Producto granel/fraccionario | SKU granel o SKU base con regla de venta | Sí si se vende como articulo distinto | Bloqueado salvo presentacion cerrada | unidad base, precision, incremento y disponibilidad |
| Kit o combo de varios SKUs | Paquete/receta | Sí para paquete vendible | Sí como paquete | componentes, cantidades y modo de disponibilidad |
| Sabor de la misma formula/linea | Variante real si es opcion de la ficha | Sí | Sí | sabor controlado; misma promesa y categoria |
| Especie recomendada | Atributo descriptivo/filtro | No | Filtro, no selector de variante | vocabulario controlado |
| Material de un articulo equivalente | Variante real solo si el cliente elige material | Sí | Sí | misma funcion y modelo base |
| Modelo diferente con funcion/promesa distinta | Producto separado | Sí | Ficha separada | evidencia comercial y tecnica |
| Texto de proveedor, color no confirmado o nombre ambiguo | Revisar manualmente | No cambiar | No publicar agrupado | foto, empaque, proveedor o evidencia fisica |

## Diferencias que exigen SKU propio sin ser variante

Un SKU propio es obligatorio cuando el negocio debe recibir, almacenar, costear,
vender, fiscalizar o rastrear la diferencia por separado, aunque Ecommerce no la
muestre como selector de variante. Esto incluye:

- presentacion cerrada con peso, volumen o contenido diferente;
- bolsa propia o producto reempacado;
- producto granel con unidad base y precision distintas;
- apertura de un empaque cerrado hacia un SKU destino;
- paquete, combo, kit o receta con componentes;
- producto con codigo de barras, garantia, fiscal o trazabilidad propia;
- conversion que consume existencia base y genera existencia vendible;
- salida preparada que requiere trabajo de almacen o empaque.

El SKU propio no convierte automaticamente el caso en variante. La pregunta de
SKU es operativa; la pregunta de variante es de identidad comercial y UX.

## Reglas por campo de negocio

| Campo | Regla base | Tratamiento usual |
|---|---|---|
| Color | Opcion visual del mismo modelo | Variante si cambia solo apariencia |
| Talla | Opcion normalizada del mismo articulo | Variante |
| Medida | Variante solo si es una dimension elegible del mismo articulo; si es peso/volumen comercial, presentacion | Variante o presentacion según naturaleza |
| Capacidad | Si representa capacidad de uso del articulo, variante; si es cantidad vendida, presentacion | Revisar unidad y factor |
| Sabor | Variante si misma linea y formula base | Variante; revisar fiscal/ingredientes |
| Especie recomendada | Compatibilidad o marketing | Atributo/filtro, no variante |
| Modelo | Variante solo dentro de misma familia y promesa | Si cambia funcion, producto separado |
| Material | Variante si el cliente elige entre materiales equivalentes | Atributo o variante según selector |
| Presentacion | Forma comercial de entrega | SKU/presentacion, no variante genérica |
| Contenido | Cantidad o composición comercial | SKU derivado/presentacion; si cambia receta, separado |
| Peso | Cantidad vendida o inventario | Presentacion; granel requiere unidad y factor |
| Unidad | Unidad de inventario/compra/venta | Unidad y conversion; no variante textual |
| Paquete | Combinacion de componentes | Paquete/receta |
| Apertura | Transformacion de cerrado a abierto/granel | Apertura de empaque |
| Producto granel | Modalidad fisica y operativa de venta | SKU granel/regla de fraccionaria |

## Mismo producto maestro: prueba de identidad

Dos SKUs pueden compartir producto maestro solo si responden afirmativamente a
estas preguntas:

- ¿La marca y linea son la misma o existe evidencia de que son una sola linea?
- ¿La promesa principal, uso y publico objetivo son los mismos?
- ¿La diferencia puede describirse con atributos del mismo vocabulario?
- ¿Un cliente entenderia que el segundo es otra opcion del primero?
- ¿Pueden compartir contenido editorial base sin ocultar la diferencia?
- ¿La agrupacion no mezclaria categoria, especie, formula o garantia de manera
  engañosa?

Se debe separar cuando cambia la formula, composicion, funcion, especie objetivo,
regulacion, riesgo, garantia, marca/linea o modo de disponibilidad de forma que la
ficha comun seria ambigua. Un nombre parecido o un prefijo de SKU no prueba
identidad.

## Precio, costo, inventario, codigo y fiscal

Una variante puede tener valores distintos en estas dimensiones; eso no la vuelve
producto separado por sí mismo:

- costo y precio: viven por SKU/lista/canal;
- inventario: vive por SKU y sus reglas fisicas;
- codigo de barras: cada SKU conserva sus codigos;
- proveedor: cada SKU conserva sus relaciones y conversiones;
- fiscal: cada SKU conserva su configuracion SAT e impuestos;
- garantia: puede ser propia del SKU o heredada explícitamente, nunca asumida por
  la UI;
- imagen: puede ser propia del SKU; nunca se debe prestar la imagen de otra
  variante sin declararlo.

Si una diferencia solo cambia precio, proveedor o codigo por razones comerciales,
primero revisar si el SKU ya representa la misma identidad. No crear una variante
por proveedor, costo o codigo alterno. Si el proveedor vende la misma mercancía
en otra unidad, usar `erp_catalogo_sku_proveedores` y `factor_conversion`.

## Presentaciones, granel y aperturas

La documentacion vigente de venta a granel establece que las presentaciones de
25 g, 50 g, 100 g y 500 g son SKUs comerciales relacionados al SKU base, no
atributos de variante genéricos. Cada una puede tener codigo, precio, imagen,
publicacion y disponibilidad propios.

Reglas operativas:

- `erp_catalogo_sku_proveedores` responde como se compra y convierte a inventario.
- `erp_catalogo_sku_presentaciones` responde cuanto SKU base consume una unidad de
  venta y si la disponibilidad es preparada, bajo demanda o mixta.
- `erp_catalogo_sku_aperturas_empaque` responde como un SKU cerrado se abre para
  producir/abastecer un SKU destino granel; no es un paquete ni una variante.
- `erp_catalogo_sku_paquetes` responde como una receta agrupa componentes; no se
  usa para representar 100 g de un producto.
- Si una presentacion requiere embolsado, el stock vendible debe distinguir stock
  base de stock terminado; el stock teorico no basta para publicar disponibilidad.
- Ecommerce no debe publicar granel/fraccionario como venta directa mientras no
  exista una presentacion cerrada y un contrato de disponibilidad.

## Atributos base por familias

Los nombres son propuestas de vocabulario; los valores deben normalizarse en
catalogos y no capturarse como frases libres.

### Acuario y peces

- especie o grupo recomendado;
- etapa/tamano del pez;
- tipo de alimento;
- sabor o formula;
- granulometria/tamano de particula;
- color del producto si es relevante;
- capacidad del acuario en litros como compatibilidad;
- peso/presentacion como dato comercial;
- material, forma y medidas para accesorios;
- agua dulce/salada, temperatura o parametros cuando aplique.

`Especie recomendada`, litros compatibles y parametros de agua son filtros o
atributos descriptivos, no variantes. Granulometria puede ser variante solo si el
cliente elige la misma formula en tamanos distintos; si cambia el empaque o peso,
se agrega la relacion de presentacion.

### Perros y gatos

- especie objetivo;
- etapa/edad;
- talla o raza compatible;
- sabor/formula;
- peso del animal;
- material, color y modelo para accesorios;
- medidas funcionales;
- peso/contenido/presentacion;
- necesidades: digestivo, dental, esterilizado, etc.;
- aroma, textura o tipo de pelo cuando sean filtros utiles.

Talla, color y modelo son variantes cuando el mismo articulo sigue siendo la
misma linea. Edad, raza y necesidad suelen ser filtros; formula o sabor pueden ser
variantes si comparten identidad y receta base. Un cambio de formula regulada o
promesa principal puede exigir producto separado.

### Reptiles y tortugas

- especie o grupo recomendado;
- etapa/tamano;
- tipo de habitat;
- material;
- medidas;
- potencia/voltaje;
- temperatura o rango tecnico;
- color/modelo;
- peso y presentacion;
- compatibilidad con accesorio o terrario.

Voltaje, potencia, rango tecnico o funcion distinta no deben esconderse como una
simple variante si cambian seguridad, instalacion o garantia. La especie suele ser
atributo/filtro.

### Pequenos mamiferos

- especie objetivo;
- etapa/tamano;
- sabor/formula;
- material;
- medidas;
- color/modelo;
- tipo de habitat;
- peso/presentacion;
- compatibilidad y necesidades.

### Productos generales

- marca y linea;
- modelo;
- color;
- talla/medida;
- material;
- capacidad tecnica;
- unidad y contenido;
- garantia;
- compatibilidad;
- presentacion y modalidad de venta.

## Reglas de nombres y SKU

- El nombre del producto maestro describe identidad; no debe acumular todas las
  opciones ni repetir peso de cada presentacion.
- El nombre del SKU identifica la opcion o presentacion que se recibe/vende.
- Usar valores normalizados: `Rojo`, `Azul`, `M`, `500 g`, `20 L`; no mezclar
  `500GR`, `500 grs`, `medio kilo` como valores equivalentes.
- El SKU debe ser estable, unico y legible; no reutilizarlo para otra identidad.
- Un SKU de variante puede incorporar el valor corto del atributo; un SKU de
  presentacion debe incorporar peso/unidad cuando la convencion vigente lo permita.
- El codigo de barras, codigo interno y SKU proveedor son identificadores
  distintos; no sustituir uno por otro.
- No crear SKU por cada fraccion libre vendida de un granel. Usar unidad base,
  precision e incremento autorizados.
- No renombrar ni reciclar SKUs existentes por limpieza textual sin snapshot y
  autorizacion.

## Ecommerce y agrupacion visual

El contrato actual publica por SKU, pero permite agrupar tarjetas del mismo
`id_producto_erp` cuando hay varias publicaciones visibles. Las variantes del
selector solo provienen de atributos activos con `es_variante=1`.

Reglas:

- Agrupar visualmente solo SKUs que compartan producto maestro validado.
- Mostrar cada SKU como opcion cuando el selector ayude a elegir una variante o
  presentacion vendible; conservar URL, precio, permisos, imagen y disponibilidad
  propios.
- No agrupar productos distintos porque el nombre, marca o especie se parezcan.
- No usar atributos administrativos, especie recomendada o texto de proveedor
  como selector de variante.
- Presentaciones de peso pueden aparecer como opciones comerciales relacionadas,
  pero deben conservar identidad, URL/precio/imagen/disponibilidad por SKU.
- Paquetes deben mostrar su contenido y modo de disponibilidad; no mezclarse con
  el producto componente como si fueran colores o tallas.
- Granel/fraccionario queda fuera de publicacion directa mientras no exista
  presentacion cerrada y disponibilidad segura.
- Filtrar antes de agrupar; el representante debe satisfacer el filtro original.
- La ficha no debe copiar descripcion, precio o imagen de otra variante.

## Receta de analisis de un producto

1. Congelar el universo: producto, SKUs activos/archivados, codigos, nombres,
   marca, categorias, proveedor, unidad, fiscal, inventario, imagenes y
   publicaciones.
2. Separar evidencia de inferencia: nombre es pista; foto, empaque, proveedor,
   factura, codigo y uso operativo son evidencia.
3. Normalizar texto para comparar sin borrar el valor original: peso, unidades,
   abreviaturas, color, talla, especie, modelo y palabras de empaque.
4. Construir una ficha por SKU con campos operativos y atributos estructurados.
5. Aplicar el arbol de decision y asignar una propuesta: `conservar`, `variante`,
   `atributo`, `sku_derivado`, `producto_separado` o `revisar_manual`.
6. Verificar impactos: costo, precio, inventario, proveedor, fiscal, garantia,
   codigos, imagenes, URLs, publicaciones y pedidos historicos.
7. Comparar contra Ecommerce: ¿la agrupacion explicaria bien la opcion y el
   precio? ¿la URL seguiria siendo estable? ¿el SKU seguiria siendo comprable?
8. Generar propuesta auditable con evidencia, confianza, riesgo y accion; no
   modificar datos.
9. Solicitar autorizacion solo para los casos confirmados y respaldar antes de
   fusionar, mover, desactivar o crear relaciones.
10. Ejecutar cambios por lotes pequenos, verificar antes/despues y dejar reversa.

## Auditoria read-only disponible

### Evidencia estructural

La auditoria de codigo y documentos confirma que el esquema contempla:

- `erp_catalogo_productos.maneja_variantes`;
- `erp_catalogo_skus.es_principal_variante` e `id_sku_principal_variante`;
- `erp_catalogo_atributos.es_variante` y `erp_catalogo_sku_atributos`;
- `erp_catalogo_sku_presentaciones`;
- `erp_catalogo_sku_aperturas_empaque`;
- `erp_catalogo_sku_paquetes` y sus componentes/grupos/opciones;
- `erp_catalogo_sku_proveedores.factor_conversion`;
- vinculos de canal y publicaciones Ecommerce por SKU.

Esto permite implementar la metodologia sin inventar una tabla de variantes
generica ni reutilizar atributos para conversiones.

### Candidatos históricos que deben revisarse

Los siguientes datos provienen de auditorias read-only ya documentadas en el
avance del Catalogo; son evidencia historica, no certifican el estado actual de
la base al 2026-10-23:

| Candidato | Evidencia | Propuesta inicial |
|---|---|---|
| 55 productos con multiples SKUs sin atributos de variante | `erp_catalogo_avance.md`, auditoria de variantes | Revisar SKU por SKU; convertir solo diferencias elegibles a atributos; separar presentaciones/derivados |
| 7 SKUs con valores de variante incompletos | Auditoria de calidad del Catalogo | Completar valor estructurado o marcar revision; no publicar selector ambiguo |
| 34 firmas de variante duplicadas | Auditoria de calidad del Catalogo | Comparar si son duplicados reales, valores normalizados o SKUs distintos; no fusionar automaticamente |
| 13 SKUs fraccionarios | Auditoria post-DDL de aperturas 2026-07-28 | Validar unidad, precision y presentacion cerrada antes de Ecommerce |
| `ECOM-1560`: 20 kg cerrado y granel | Candidato documentado en venta a granel | Modelar apertura cerrado -> granel con factor confirmado; no variante |
| `ECOM-5`: churro blanco con 25/50/100/500 g y SKU base | Candidato documentado en venta a granel | Mantener maestro; relacionar presentaciones y separar stock preparado/base |
| `ECOM-1443`: churro rojo con SKU base y 500 g fraccionarios | Candidato documentado en venta a granel | Saneamiento de reglas; decidir presentacion vs granel antes de publicar |

### Limitacion de la auditoria de esta sesion

Se intentaron ejecutar scripts read-only del repositorio, pero el servicio local
`mysqld` no estaba iniciado. No se inicio el servicio, no se ejecuto DDL y no se
consulto una base alternativa. Por tanto, en esta sesion no se certifican conteos
actuales, filas actuales ni nuevos candidatos. La siguiente auditoria debe correr
con MySQL disponible y guardar solo un reporte read-only con:

- conteo de productos maestros, SKUs y SKUs activos por producto;
- productos con `maneja_variantes=1` sin firma completa;
- firmas duplicadas por producto;
- valores de atributo no normalizados;
- presentaciones sin SKU base, factor o modo de disponibilidad;
- aperturas con origen/destino incompatibles o factor faltante;
- paquetes sin componentes, ciclos o componentes inactivos;
- grupos Ecommerce que mezclen categorias, especies o presentaciones incompatibles.

## Criterios para detectar modelado incorrecto

Marcar como candidato cuando ocurra una o más condiciones:

- multiples SKUs del mismo producto sin atributos de opcion y con diferencias
  visibles en nombres;
- atributo `es_variante=1` usado para peso, unidad, paquete, especie o proveedor;
- presentacion sin relacion `SKU base -> SKU presentacion`;
- SKU granel publicado sin presentacion cerrada;
- producto con varios SKUs pero todos comparten exactamente la misma firma de
  variante;
- dos productos maestros con misma marca/linea y firmas compatibles, pero sin
  evidencia de que deban seguir separados;
- un paquete tratado como variante de su componente;
- un SKU con nombre de otro SKU, imagen heredada incorrectamente o descripcion de
  otra presentacion;
- una sola URL publica que oculta precios, disponibilidad o codigos distintos;
- codigo de barras o fiscal compartido entre SKUs que operativamente son distintos.

## Clasificaciones y confianza

Cada propuesta debe devolver una sola clasificacion principal y, si aplica, una
alternativa:

- `conservar`: el modelo actual es coherente;
- `convertir_a_variante`: mismo maestro y opcion elegible;
- `convertir_a_atributo`: dato estructurado no seleccionable;
- `crear_sku_derivado`: requiere identidad operativa por conversion/presentacion;
- `separar_producto`: cambia identidad, promesa, formula o riesgo;
- `revisar_manual`: evidencia insuficiente o impactos cruzados.

Confianza alta exige evidencia de empaque, proveedor o uso operativo y consistencia
con esquema/contrato. Confianza media puede proponer, pero requiere confirmacion
del responsable. Confianza baja solo genera pendiente; nunca autoriza cambio.

## Riesgos y orden de implementacion

Riesgos principales:

- fusionar productos que ya tienen ventas, URLs o fiscales diferentes;
- mostrar 25 g y 4 kg como si fueran tallas equivalentes sin controlar stock;
- publicar granel con stock teorico o sin preparacion real;
- usar especie recomendada como variante y fragmentar el catalogo;
- borrar firmas o SKUs duplicados sin conservar historia;
- agrupar Ecommerce por nombre y mezclar productos distintos.

Orden recomendado:

1. Ejecutar auditoria read-only con MySQL disponible y guardar evidencia.
2. Normalizar vocabularios de atributos y clasificar candidatos sin escribir.
3. Revisar primero casos bloqueantes de Ecommerce, granel, paquetes y URLs.
4. Presentar propuesta por lote pequeno con impacto y reversa.
5. Respaldar antes de cualquier DDL, fusion o cambio de relaciones.
6. Aplicar solo casos autorizados; validar Catalogo, Compras, Almacen, Ventas y
   Ecommerce despues de cada lote.
7. Habilitar nuevas reglas de publicacion/agrupacion solo cuando el contrato de
   SKU, presentacion y disponibilidad este certificado.

## Handoff / continuidad

Fecha: 2026-10-23

- Contexto actual: metodologia formal creada; no se modificaron productos, SKUs,
  atributos, relaciones, precios, inventario ni publicaciones.
- Decisiones: peso/contenido/presentacion no son variantes genericas; aperturas y
  paquetes tienen relaciones propias; especie recomendada es atributo/filtro.
- Evidencia: el esquema y contratos existentes soportan la separacion; los
  conteos de candidatos son historicos y deben reconfirmarse.
- Pendientes: ejecutar auditoria read-only con MySQL disponible; revisar 55/7/34 y
  los candidatos de granel; definir vocabularios finales por familia.
- Impacta a: Compras, Almacen/Inventario, Precios/Ventas y Ecommerce publico.
- Siguiente paso: generar reporte tabular read-only por producto/SKU con evidencia,
  confianza y clasificacion propuesta; no aplicar cambios masivos.
