# ERP Catalogo - Auditoria de responsabilidades y plan robusto

Documentacion viva: Codex GPT-5  
Fecha: 2026-06-24  
Estado: Planeacion previa a nuevos cambios funcionales

## Proposito

Esta auditoria separa que debe pertenecer a Catalogo Productos y que debe vivir en otros modulos del ERP.

El objetivo es evitar que la persona encargada de alimentar productos tenga que decidir cosas que no le corresponden, como costos, politica de inventario fisico, trazabilidad, precios comerciales o reglas fiscales complejas.

## Diagnostico ejecutivo

Catalogo ERP ya funciona como fuente tecnica central del SKU, pero la pantalla actual concentra demasiadas responsabilidades:

- datos maestros del producto;
- identidad del SKU;
- precio general;
- fiscal;
- proveedor/SKU proveedor/costo;
- reglas de inventario;
- granel y presentaciones;
- etiquetado/trazabilidad;
- imagenes;
- variantes;
- incidencias.

Esto es tecnicamente util mientras el ERP se construye, pero no es ideal para operacion delegada. En un ERP robusto, Catalogo debe ser el dueno de la identidad del producto y el punto de consulta de completitud, no el dueno de todas las decisiones operativas.

## Regla principal

Catalogo debe responder:

- Que es el producto.
- Como se identifica.
- Como se diferencia de otros SKUs.
- Como se clasifica.
- Que datos minimos necesita para que otros modulos lo usen.
- Que le falta para comprarlo, venderlo, publicarlo o moverlo.

Catalogo no debe ser responsable unico de:

- costo real de compra;
- costo de referencia financiero;
- precio comercial final;
- stock fisico;
- preparacion/empaque real;
- impresion de etiquetas fisicas;
- autorizacion fiscal compleja;
- reglas de rentabilidad.

## Avance aplicado 2026-06-24

### Documentacion de codigo generado por IA

Se creo la norma transversal `docs/erp_estandar_documentacion_codigo.md`.

Regla para Catalogo y otros modulos:

- Las funciones nuevas generadas por IA deben documentar version IA, fecha, proposito, impacto y contrato si aplica.
- Las funciones existentes modificadas por IA deben documentarse cuando cambien reglas de negocio o contratos entre modulos.
- La documentacion en codigo no sustituye la documentacion viva del modulo.

### Captura fiscal parcial en Catalogo

Decision:

- Catalogo puede guardar avances fiscales parciales.
- La captura parcial no debe bloquear guardar datos maestros.
- La auditoria de calidad debe seguir marcando `fiscal_pendiente` o fiscal incompleto hasta completar el contrato fiscal.

Limitacion actual sin migracion:

- `erp_catalogo_sku_impuestos.iva_porcentaje`, `ieps_porcentaje` e `incluye_impuestos` no distinguen perfectamente entre "desconocido" y valor cero cuando se guarda una ficha parcial.
- Por ahora se conserva el esquema y no se aplica migracion.
- Si despues se requiere trazabilidad fiscal fina, evaluar campos de estado/evidencia fiscal o permitir nulos con migracion autorizada.

Impacto en otros modulos:

- Compras/fiscal no deben asumir que un registro en `erp_catalogo_sku_impuestos` significa fiscal validado.
- Ventas/precios no deben interpretar `incluye_impuestos` como politica comercial final sin revisar lista/canal.
- Calidad de Catalogo debe seguir mostrando pendientes fiscales hasta completar clave producto SAT, clave unidad SAT, objeto impuesto, IVA, IEPS y criterio de impuestos incluidos.

## Estados operativos recomendados

En un ERP robusto conviene separar dos conceptos:

- Estatus de vida del maestro: indica si el registro existe, esta vigente, archivado, descontinuado o fusionado.
- Estado de preparacion operativa: indica si tiene la informacion minima para no interrumpir un flujo especifico.

No conviene usar un solo estatus para todo, porque un producto puede estar activo como maestro pero no estar listo para comprar, vender, publicar o calcular rentabilidad.

### Estatus de vida del maestro

Estos estatus pertenecen a Catalogo y controlan la vigencia del registro.

| Estatus | Significado | Uso recomendado |
| --- | --- | --- |
| `borrador` | Registro en captura inicial. Puede estar incompleto. | Alta temprana, producto creado desde incidencia o proveedor sin validacion completa. |
| `en_revision` | Registro pendiente de validacion de datos maestros. | Dudas de identidad, variante, unidad, duplicado, marca o categoria. |
| `activo` | Maestro vigente y utilizable por el ERP. | Puede participar en busquedas y flujos, pero no implica que este listo para todos los procesos. |
| `inactivo` | No se usa operativamente, pero se conserva historial. | Pausa temporal, error de carga no fusionado, producto no disponible internamente. |
| `descontinuado` | Producto que ya no se debe comprar normalmente, pero puede conservar existencia o historial. | Producto fuera de linea, liquidacion o solo venta de stock restante. |
| `fusionado` | Registro absorbido por otro maestro. | Correccion de duplicados; no debe seleccionarse en nuevas operaciones. |

Regla:

- `activo` significa "maestro vigente", no "listo para vender".
- La preparacion para comprar, vender, recibir o publicar debe mostrarse con indicadores separados.

### Estados de preparacion operativa

Estos no sustituyen el estatus del producto. Son evaluaciones por objetivo.

| Estado de preparacion | Responsable principal | Indica |
| --- | --- | --- |
| `maestro_incompleto` | Catalogo | Faltan datos basicos de identidad: nombre, SKU, unidad, marca/categoria si aplica, variante diferenciada. |
| `maestro_validado` | Catalogo | El producto/SKU tiene identidad suficiente para usarse como dato maestro. |
| `compra_pendiente` | Proveedores/Compras | Falta proveedor, SKU proveedor, unidad/factor de compra o costo/evidencia si aplica. |
| `compra_habilitada` | Proveedores/Compras | Puede seleccionarse en compras sin frenar captura basica. |
| `fiscal_pendiente` | Catalogo/Fiscal/Compras | Hay fiscal vacio o parcial; puede existir como maestro, pero no debe avanzar en flujo fiscal sin completar. |
| `fiscal_validado` | Fiscal/Contabilidad | Datos fiscales suficientes o validados para operaciones fiscales. |
| `inventario_pendiente` | Inventario/Almacen | Falta validar reglas fisicas, unidad, lote/caducidad/serie, granel o etiquetas. |
| `inventario_habilitado` | Inventario/Almacen | Puede recibir/mover existencia sin ambiguedad operativa. |
| `venta_pendiente` | Precios/Ventas | Falta precio/canal, fiscal, existencia/regla o configuracion de venta. |
| `venta_habilitada` | Precios/Ventas | Puede venderse por el canal correspondiente sin interrumpir el flujo. |
| `ecommerce_pendiente` | Ecommerce/Comercial | Falta imagen, descripcion comercial, categoria/canal, precio de canal o regla de publicacion. |
| `ecommerce_publicable` | Ecommerce/Comercial | Cumple requisitos para publicarse en el canal. |
| `rentabilidad_pendiente` | Costos/Rentabilidad | Falta costo confiable, precio o regla de margen. |
| `rentabilidad_revisada` | Costos/Rentabilidad | Hay informacion suficiente para analizar margen/rentabilidad. |

Regla:

- Estos estados pueden mostrarse como badges, auditorias o semaforos.
- No todos deben ser columnas fisicas desde el inicio; pueden calcularse con auditorias hasta que el flujo madure.
- Si se persisten, deben tener fecha, responsable, evidencia y criterio de calculo.

### Activo

Significa que el maestro esta vigente y puede ser usado por el ERP.

No significa automaticamente:

- listo para comprar;
- listo para vender;
- listo para ecommerce;
- listo para inventario fisico;
- rentable.

### Comprable

Responsable principal: Proveedores / Compras.

Requisitos minimos:

- SKU activo.
- Relacion proveedor-SKU activa.
- SKU proveedor si el proveedor lo maneja.
- Unidad de compra definida.
- Factor compra -> inventario correcto.
- Compra minima si aplica.
- Costo vigente o evidencia de costo en Proveedores/Costos.
- Fiscal suficiente si se va a generar orden/XML/factura.

Catalogo debe mostrar la alerta, pero no resolver todo.

### Inventariable

Responsable principal: Inventario / Almacen, con definicion base desde Catalogo.

Requisitos minimos:

- tipo de inventario correcto;
- unidad base correcta;
- factor de unidad base correcto;
- controla inventario o no;
- lote/caducidad/serie si aplica;
- estrategia de salida sugerida;
- reglas de existencia negativa o venta sin existencia revisadas;
- stock minimo, maximo y reorden si se usan como politica operativa.

Catalogo puede definir la regla base; Almacen/Inventario validan contra operacion real.

### Vendible

Responsable principal: Ventas / Precios / Inventario, con identidad desde Catalogo.

Requisitos minimos:

- SKU activo.
- precio de venta activo en lista/canal correspondiente;
- datos fiscales suficientes para venta/facturacion;
- existencia disponible o politica explicita de venta sin existencia;
- codigo escaneable si la venta lo requiere;
- presentacion configurada si se vende preparado, fraccionado o a granel.

Catalogo debe decir si el SKU existe y como se vende; Ventas/Precios deciden precio y canal.

### Publicable en ecommerce

Responsable principal: Ecommerce / Comercial, con base desde Catalogo.

Requisitos minimos:

- SKU vendible.
- nombre comercial claro;
- descripcion comercial;
- imagen activa;
- categoria/canal;
- precio de canal;
- existencia o regla de disponibilidad;
- reglas de sincronizacion.

Catalogo puede guardar imagenes y clasificacion base, pero publicar es decision comercial/canal.

### Rentable

Responsable principal: Costos / Rentabilidad / Direccion.

Requisitos minimos:

- costo confiable;
- precio vigente;
- impuestos;
- margen objetivo;
- comisiones/costos indirectos cuando aplique.

Catalogo no debe exigir ni mostrar esta decision a perfiles operativos de productos.

## Auditoria de bloques actuales

### 1. Producto maestro

Campos actuales:

- codigo interno;
- nombre;
- tipo;
- marca;
- categoria;
- descripcion;
- maneja variantes;
- estado.

Debe vivir en Catalogo.

Responsable ideal:

- Encargado de Catalogo / Datos maestros.

Observaciones:

- Marca y categoria pueden administrarse desde Configuracion, pero seleccionar una en producto si corresponde a Catalogo.
- Crear marca desde producto puede ser util, pero debe evitar duplicados y no sacar al usuario de la pantalla.
- `activo` debe explicarse como registro vigente, no como listo para vender.

### 2. SKU e identidad

Campos actuales:

- SKU;
- nombre SKU;
- unidad base;
- factor unidad base;
- codigo interno/barras;
- tipo inventario;
- estado;
- motivo de cambio de identidad.

Debe vivir principalmente en Catalogo.

Responsable ideal:

- Encargado de Catalogo.
- Revision de Inventario/Almacen cuando el tipo fisico no sea evidente.

Observaciones:

- Unidad base y factor pueden ser dificiles para personal de Catalogo si el producto se compra en cajas, costales o bolsas.
- El sistema debe ayudar con alertas y ejemplos, pero no asumir factores por unidad.
- Cambios de SKU/codigo deben seguir con motivo y auditoria.

### 3. Precio de venta

Campos actuales:

- precio general;
- moneda;
- indicador de precio con impuestos.

Debe moverse conceptualmente a Precios / Ventas / Comercial.

Responsable ideal:

- Encargado de precios o comercial.
- Direccion cuando afecte margen.

Regla recomendada:

- Catalogo puede mostrar si existe precio activo, pero no debe ser el dueno del precio.
- Mientras no exista modulo formal de Precios, puede mantenerse como captura provisional, marcada como "precio inicial/general".
- El precio no debe calcularse automaticamente desde costo sin politica de margen autorizada.
- El precio debe poder variar por SKU, lista, canal y vigencia.
- Si un producto tiene variantes, el precio debe pertenecer al SKU variante, no solo al producto maestro.

Riesgo actual:

- El capturista de Catalogo podria creer que debe saber margen, costo o politica comercial.

Decision de diseno:

- `erp_catalogo_sku_precios` puede seguir siendo la estructura base tecnica para precios por SKU/lista/moneda/vigencia.
- La administracion funcional de precios debe vivir en un modulo o pantalla de Precios/Listas de venta.
- Catalogo Productos puede mostrar resumen de precio vigente, pero no debe ser el lugar normal para administrar precios por canal.
- Para canales como mostrador, ecommerce, mayoreo o marketplace, cada canal debe tener lista/precio propio o regla comercial propia.

### 4. Costo

Campos/flujos actuales:

- `erp_catalogo_skus.costo_referencia`;
- `erp_catalogo_sku_proveedores.costo_ultimo`;
- propuestas de costo desde proveedor;
- permiso `catalogo.costos`.

Debe pertenecer a Proveedores / Compras / Costos / Rentabilidad.

Responsable ideal:

- Compras para costo proveedor/lista.
- Costos/Finanzas para costo de referencia autorizado.
- Direccion para politica de margen.

Regla recomendada:

- Catalogo Productos no debe pedir costo.
- Catalogo puede alertar "costo pendiente" solo como preparacion operativa y sin monto para perfiles sin permiso.
- La actualizacion de costo de referencia debe venir de flujo controlado con evidencia.

Estado aplicado:

- El costo ya se retiro de la calidad principal de Catalogo Productos.

### 5. Relacion proveedor-SKU

Campos actuales:

- proveedor;
- SKU proveedor;
- unidad compra;
- factor compra -> inventario;
- compra minima;
- costo ultimo;
- dias entrega;
- proveedor preferido.

Debe pertenecer principalmente a Proveedores.

Responsable ideal:

- Proveedores / Compras.

Que si debe ver Catalogo:

- si el SKU tiene proveedor activo;
- si hay alerta de proveedor faltante;
- evidencia minima para entender por que un SKU se puede comprar.

Que no deberia resolver Catalogo normal:

- costo ultimo;
- proveedor preferido;
- vigencia de costo;
- condiciones de compra;
- dias de entrega comerciales.

Recomendacion:

- Mantener una vista de consulta en Catalogo.
- Mover la edicion principal al modulo Proveedores o restringirla con permiso especifico de Proveedores/Costos.

### 6. Fiscal

Campos actuales:

- clave producto SAT;
- clave unidad SAT;
- objeto de impuesto;
- IVA;
- IEPS;
- incluye impuestos.

Debe ser compartido entre Catalogo y Fiscal/Compras.

Responsable ideal:

- Catalogo puede capturar propuesta basica.
- Compras/XML pueden aportar evidencia.
- Fiscal/Contabilidad deberia validar o autorizar cuando haya duda.

Regla recomendada:

- No bloquear la creacion del producto por no tener fiscal.
- Permitir captura fiscal parcial sin perder lo capturado.
- Bloquear o alertar cuando el SKU ya sea comprable/vendible y siga con fiscal incompleto.
- Cuando XML trae datos fiscales, guardarlos como evidencia/propuesta, no sobrescribir automaticamente.
- El campo `incluye_impuestos` pertenece al bloque fiscal/comercial de impuestos, no debe quedar visualmente separado junto a precio si eso confunde al operador.
- La UI debe agrupar clave producto SAT, clave unidad SAT, objeto de impuesto, IVA, IEPS e incluye impuestos en una sola seccion fiscal.
- La validacion debe distinguir entre:
  - sin fiscal capturado;
  - fiscal parcial capturado;
  - fiscal completo;
  - fiscal validado por evidencia.

Prioridad actual:

- Es el principal bloqueo operativo detectado en Catalogo para SKUs comprables.

Decision de diseno:

- Un usuario de Catalogo puede capturar lo que conoce aunque no tenga todos los datos fiscales.
- Guardar fiscal parcial debe ser permitido y debe generar o mantener alerta de "fiscal incompleto".
- El sistema no debe obligar a borrar lo capturado para guardar otros cambios del SKU.
- La validacion estricta debe aplicarse al usar el SKU en Compras/XML/Ventas/facturacion, no necesariamente al editar el maestro.

### 7. Inventario base

Campos actuales:

- tipo inventario;
- controla inventario;
- stock minimo;
- stock maximo;
- punto reorden;
- estrategia salida;
- dias alerta caducidad;
- vida minima al recibir;
- existencia negativa;
- venta sin existencia.

Debe ser compartido.

Responsable ideal:

- Catalogo define si un SKU es inventariable, consumible, kit, servicio o cargo.
- Inventario/Almacen validan reglas fisicas.
- Compras puede detectar inconsistencias al recibir.

Regla recomendada:

- No pedir al capturista normal que decida todo el control fisico si no lo sabe.
- Usar estados "pendiente revision inventario" o incidencias cuando haya duda.
- Reorden/stock minimo no deberian sentirse obligatorios para completar el producto si el negocio aun no definio politica.

### 8. Granel y venta fraccionaria

Campos actuales:

- permite venta fraccionaria;
- precision decimal;
- incremento minimo venta;
- unidad venta label;
- permite etiqueta fraccionada.

Debe iniciar en Catalogo, pero validarse con Inventario/Ventas.

Responsable ideal:

- Catalogo define la forma comercial y unidad base.
- Inventario valida control de existencia.
- Ventas/ecommerce valida si lo puede vender.

Regla recomendada:

- Catalogo debe permitir configurar granel porque define el SKU.
- No debe generar existencias ni etiquetas fisicas.
- Si la persona de Catalogo no sabe si se vende a granel, el SKU puede quedar activo pero no vendible hasta revision.

### 9. Presentaciones de venta

Campos actuales:

- SKU base;
- SKU presentacion;
- factor salida base;
- modo disponibilidad;
- consume base en preparacion o venta;
- requiere empaque;
- capacidad diaria;
- merma;
- estado.

Debe ser compartido entre Catalogo, Almacen/Inventario y Ventas.

Responsable ideal:

- Catalogo define que presentaciones existen.
- Almacen/Inventario define como se preparan y generan existencia.
- Ventas/ecommerce decide si vende preparada o bajo demanda.
- Costos/Rentabilidad calcula impacto de merma/empaque.

Regla recomendada:

- Catalogo puede guardar la receta conceptual de presentacion.
- Almacen debe ejecutar preparacion/empaque.
- Inventario debe mover existencia base -> existencia presentacion.
- Ventas debe consumir presentacion disponible o disparar preparacion bajo demanda si se autoriza.

### 10. Etiquetado y trazabilidad

Campos actuales:

- requiere serie fabricante;
- generar etiqueta interna;
- requiere escaneo venta;
- prefijo etiqueta;
- plantilla etiqueta;
- tipo seguridad;
- instrucciones etiquetado.

Debe ser compartido, con ejecucion fuera de Catalogo.

Responsable ideal:

- Catalogo define si el SKU requiere identificacion.
- Almacen imprime/pega etiquetas en recepcion o preparacion.
- Inventario conserva unidades etiquetadas.
- Ventas exige escaneo si corresponde.

Regla recomendada:

- Catalogo no debe imprimir ni crear unidades fisicas.
- "Prefijo etiqueta" no es granel ni unidad de venta; solo es convencion de codigo interno.
- La decision de obligar etiqueta/serie puede requerir validacion de Almacen o Direccion.

### 11. Imagenes

Campos actuales:

- tipo imagen;
- SKU especifico;
- URL/ruta;
- orden;
- estado;
- texto alternativo.

Debe vivir principalmente en Catalogo / Ecommerce.

Responsable ideal:

- Catalogo para evidencia visual y ficha base.
- Ecommerce/Comercial para imagen comercial final y publicacion.

Regla recomendada:

- Imagen no debe bloquear compra interna.
- Imagen si puede bloquear ecommerce/publicacion.

### 12. Variantes y atributos

Campos actuales:

- atributos diferenciadores;
- valores por SKU;
- firmas duplicadas.

Debe vivir en Catalogo.

Responsable ideal:

- Encargado de Catalogo.

Regla recomendada:

- Si hay varios SKUs parecidos, debe existir atributo diferenciador.
- No inventar atributos ambiguos desde nombres; usar revision humana.

## Variantes internas con mismo SKU proveedor

Caso operativo:

- El proveedor puede vender un producto bajo un solo SKU proveedor, por ejemplo `SP-3675`.
- El negocio puede necesitar separar variantes internas por color, talla, presentacion visual u otra caracteristica.
- Ejemplo: SKU proveedor `SP-3675`, pero Catalogo ERP maneja SKUs internos como `SP-3675-MA`, `SP-3675-NE`, etc.

Regla de ERP robusto:

- El SKU interno ERP identifica lo que el negocio necesita controlar, vender, recibir o valorizar de forma separada.
- El SKU proveedor identifica como el proveedor lo nombra o lo factura.
- No es obligatorio que cada SKU interno exista como SKU separado en la lista del proveedor.
- Si el proveedor usa un solo SKU para varias variantes, el ERP debe modelar una relacion de proveedor que permita seleccionar la variante interna al comprar o recibir.

### Configuracion recomendada en Catalogo

Producto maestro:

- Agrupa el producto base. Ejemplo: producto `SP-3675`.
- Activa `maneja_variantes=1`.

SKUs internos:

- Crear un SKU ERP por variante que se quiera distinguir en inventario, venta o precio.
- Ejemplo:
  - `SP-3675-MA` = variante marron;
  - `SP-3675-NE` = variante negro;
  - `SP-3675-AZ` = variante azul.

Atributos:

- Crear atributo diferenciador, por ejemplo `Color`.
- Asignar valor a cada SKU variante.
- La combinacion de atributos debe ser unica por producto.

Proveedor:

- Puede existir una relacion proveedor-SKU hacia cada SKU interno usando el mismo `sku_proveedor=SP-3675`, si el proveedor no distingue variante en su lista.
- Esa relacion debe marcarse como "requiere seleccion de variante al recibir" cuando el proveedor/factura no trae el atributo.
- Si el proveedor si distingue color en descripcion, lista o codigo alterno, Proveedores debe guardar esa evidencia para proponer la variante correcta.

Precios:

- El precio de venta debe estar en el SKU interno variante.
- Si el color negro es mas escaso o tiene precio distinto, la lista de precios debe permitir precio diferente para `SP-3675-NE`.
- Catalogo no decide ese precio; solo permite que exista la variante.

Inventario/Recepcion:

- Recepcion debe poder recibir una orden/renglon proveedor con SKU proveedor general y asignar cantidades a SKUs internos variantes.
- Si llegaron 10 piezas del proveedor `SP-3675`, Recepcion debe permitir distribuir:
  - 4 a `SP-3675-MA`;
  - 3 a `SP-3675-NE`;
  - 3 a `SP-3675-AZ`.
- Si no se identifica la variante al recibir, debe quedar incidencia o existencia en revision, no inventar una variante.

Regla de bloqueo:

- No se debe forzar que el SKU proveedor sea unico por SKU interno cuando el proveedor realmente no lo diferencia.
- Tampoco se debe colapsar todo en un solo SKU interno si el negocio vende, valora o controla variantes por separado.

Contrato hacia otros modulos:

- Proveedores debe poder indicar si un renglon proveedor tiene variante explicita, variante ambigua o variante no informada.
- Compras debe permitir pedir el producto aunque el proveedor tenga SKU general, pero debe avisar que Recepcion tendra que identificar variante.
- Almacen/Recepcion debe capturar variante real recibida cuando no venga en la orden.
- Inventario debe guardar existencia por `id_sku_erp` final, no por SKU proveedor generico.
- Precios debe poder mantener precios por SKU variante.

### 13. Incidencias de calidad

Debe quedarse como centro de retorno a Catalogo.

Responsable ideal:

- Cada modulo genera incidencias cuando detecta huecos.
- Catalogo resuelve solo lo que le corresponde.
- Otros modulos resuelven su parte y notifican.

Regla recomendada:

- Las incidencias deben tener "modulo responsable".
- No todo pendiente visible en Catalogo debe ser resoluble por Catalogo.

## Fusion de productos/SKUs y reversibilidad

La fusion de productos o SKUs es una accion de alto impacto. En un ERP robusto no debe tratarse como una edicion comun.

### Estado actual observado

El flujo actual de fusion de productos maestros:

- mueve SKUs del producto origen al producto destino;
- mueve vinculos de canal;
- mueve imagenes;
- mueve propuestas de nombre;
- copia categorias secundarias;
- marca el producto origen como `fusionado`;
- actualiza referencias de inventario por `id_producto` cuando existen esas columnas;
- guarda un registro resumen en `erp_catalogo_productos_fusiones` con origen, destino, motivo, cantidad de SKUs movidos y usuario.

Riesgo:

- El registro actual no guarda snapshot detallado de que SKUs, imagenes, categorias, vinculos y referencias fueron movidos.
- Sin snapshot, una reversa automatica completa puede ser insegura si despues de la fusion hubo compras, recepciones, movimientos, ventas, cambios de imagen, cambios de categoria o ediciones de SKU.

### Regla recomendada

Toda fusion debe tener:

- previsualizacion obligatoria;
- motivo obligatorio;
- usuario y fecha;
- snapshot antes de fusionar;
- lista exacta de entidades movidas;
- impacto operativo detectado antes de ejecutar;
- bloqueo o autorizacion adicional si hay inventario, compras, ventas, recepciones, ecommerce o costos relacionados.

### Reversion recomendada

No conviene implementar un "deshacer" simple que revierta a ciegas.

Debe existir un flujo de correccion controlada:

1. Revisar fusion ejecutada.
2. Mostrar snapshot original.
3. Mostrar cambios posteriores a la fusion.
4. Permitir seleccionar que elementos se regresan:
   - SKUs;
   - imagenes;
   - categorias;
   - vinculos de canal;
   - propuestas/metadatos;
   - referencias no operativas.
5. Bloquear reversa automatica si algun SKU ya tuvo movimientos posteriores.
6. Si hay movimientos posteriores, crear tarea de correccion manual con responsable y evidencia.

### Tipos de reversa

| Tipo | Uso | Requisito |
| --- | --- | --- |
| `reversa_segura` | Fusion reciente sin movimientos posteriores. | Snapshot completo y sin uso operativo posterior. |
| `reversa_parcial` | Solo se regresan ciertos SKUs o imagenes. | Seleccion explicita y auditoria. |
| `correccion_asistida` | Ya hubo compras, inventario, ventas o cambios posteriores. | No se revierte masivo; se corrige con tareas y evidencia. |
| `solo_auditoria` | No se puede revertir sin riesgo. | Se conserva fusion y se documenta motivo. |

### Estructura deseable

Antes de agregar boton de reversa, evaluar si se requiere ampliar estructura con:

- encabezado de fusion:
  - id_fusion;
  - origen;
  - destino;
  - motivo;
  - usuario;
  - fecha;
  - estatus;
  - snapshot_json;
  - impacto_json.
- detalle de fusion:
  - entidad_tipo;
  - entidad_id;
  - valor_anterior_json;
  - valor_nuevo_json;
  - puede_revertir;
  - revertido_en;
  - revertido_por.

### Propuesta tecnica para snapshot de fusiones

Estado actual:

- `erp_catalogo_productos_fusiones` guarda solo encabezado/resumen.
- No guarda snapshot detallado de SKUs, imagenes, vinculos, categorias ni referencias movidas.
- No permite saber con precision que puede regresar sin afectar operaciones posteriores.

Ampliacion propuesta sobre `erp_catalogo_productos_fusiones`:

```sql
ALTER TABLE erp_catalogo_productos_fusiones
  ADD COLUMN estatus VARCHAR(30) NOT NULL DEFAULT 'ejecutada',
  ADD COLUMN snapshot_json LONGTEXT NULL,
  ADD COLUMN impacto_json LONGTEXT NULL,
  ADD COLUMN fecha_reversa DATETIME NULL,
  ADD COLUMN usuario_reversa_id INT NULL,
  ADD COLUMN motivo_reversa VARCHAR(255) NULL,
  ADD KEY idx_catalogo_fusion_estatus (estatus);
```

Tabla propuesta de detalle:

```sql
CREATE TABLE erp_catalogo_productos_fusiones_detalle (
  id_detalle BIGINT NOT NULL AUTO_INCREMENT,
  id_fusion BIGINT NOT NULL,
  entidad_tipo VARCHAR(60) NOT NULL,
  entidad_id BIGINT NOT NULL,
  accion VARCHAR(30) NOT NULL DEFAULT 'movido',
  valor_anterior_json LONGTEXT NULL,
  valor_nuevo_json LONGTEXT NULL,
  puede_revertir TINYINT(1) NOT NULL DEFAULT 1,
  revertido TINYINT(1) NOT NULL DEFAULT 0,
  fecha_reversa DATETIME NULL,
  usuario_reversa_id INT NULL,
  motivo_reversa VARCHAR(255) NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_detalle),
  KEY idx_catalogo_fusion_detalle_fusion (id_fusion),
  KEY idx_catalogo_fusion_detalle_entidad (entidad_tipo, entidad_id),
  CONSTRAINT fk_catalogo_fusion_detalle_fusion
    FOREIGN KEY (id_fusion) REFERENCES erp_catalogo_productos_fusiones (id_fusion)
);
```

Entidades minimas a guardar en detalle:

- `erp_catalogo_skus`: `id_sku`, producto anterior y producto destino.
- `erp_catalogo_imagenes`: `id_imagen_erp`, producto/SKU anterior y nuevo.
- `erp_catalogo_canales_vinculos`: vinculo anterior y nuevo.
- `erp_catalogo_revision_nombres`: producto anterior y nuevo.
- `erp_catalogo_producto_categorias`: categorias copiadas o movidas.
- Referencias por producto en inventario si existen columnas usadas por el flujo.

Regla de seguridad:

- No habilitar "Revisar reversa" si la fusion no tiene detalle.
- No ejecutar reversa automatica si algun SKU fusionado tuvo compras, recepciones, movimientos de inventario, ventas, preparacion/empaque o ediciones posteriores a `fecha_registro`.
- Si hay uso posterior, crear correccion asistida por entidad, no reversa masiva.

### Reglas de UX

- La accion debe decir "Fusionar" con advertencia de alto impacto.
- La reversa no debe decir solo "Deshacer"; debe decir "Revisar reversa" o "Correccion de fusion".
- Mostrar claramente que una fusion con uso operativo posterior puede no ser reversible automaticamente.
- La previsualizacion debe decir exactamente que se movera.

### Decision de planeacion

Agregar reversibilidad al plan, pero no implementar hasta:

- auditar estructura actual de `erp_catalogo_productos_fusiones`;
- definir si se fusionan solo productos maestros o tambien SKUs individuales;
- definir permisos de alto impacto;
- definir snapshot obligatorio;
- crear UAT con fusion reciente reversible y fusion con movimientos posteriores no reversible.

## Fase futura - Paquetes, kits y combos

Fecha: 2026-06-26

Regla:

- Catalogo define la estructura maestra del paquete: SKU paquete, componentes, cantidades, modalidad y restricciones.
- Almacen/Inventario ejecuta armado, desarmado, consumo y existencia fisica cuando aplique.
- Ventas/Precios define precio, canal, promociones y consumo al vender.

Modalidades:

- `virtual`: no existe inventario propio; se vende como agrupacion y se consumen componentes.
- `prearmado`: existe SKU paquete con existencia; Almacen arma consumiendo componentes.
- `combo`: regla comercial o promocional; puede vivir mas cerca de Ventas/Listas de precios si no requiere SKU propio.
- `comprado_cerrado`: se compra como unidad de proveedor; se maneja como SKU normal salvo que se desarme.

Riesgos:

- Crear paquetes desde Catalogo sin inventario preparado puede prometer disponibilidad falsa.
- Mezclar paquete con presentacion puede duplicar recetas. Presentacion transforma un mismo producto base; paquete combina SKUs distintos.
- Usar paquete para promociones simples puede complicar inventario innecesariamente; esos casos deben evaluarse en Ventas/Precios.

Pendiente:

- Auditar si hay modulo legacy `Paquetes` o tablas ecommerce relacionadas.
- Definir primer caso real antes de crear esquema.
- Documentar handoff para Almacen/Inventario y Ventas cuando se implemente.

## Matriz de responsabilidad recomendada

| Tema | Dueno principal | Catalogo hace | Catalogo no deberia hacer |
| --- | --- | --- | --- |
| Producto, nombre, marca, categoria | Catalogo | Captura y mantiene | Decidir margen o costo |
| SKU, unidad base, codigo | Catalogo | Define identidad | Simular existencia |
| Precio venta | Precios/Ventas/Comercial | Mostrar alerta o precio vigente | Ser dueno final del precio |
| Costo | Proveedores/Compras/Costos | Mostrar estado sin monto si no hay permiso | Pedir costo al capturista |
| Proveedor-SKU | Proveedores/Compras | Mostrar cobertura | Mantener costo/condiciones normales |
| Fiscal | Fiscal/Contabilidad + Catalogo | Capturar propuesta o evidencia | Sobrescribir XML sin validacion |
| Inventario fisico | Inventario/Almacen | Definir regla base si se sabe | Decidir stock real o movimientos |
| Granel | Catalogo + Inventario + Ventas | Definir unidad/precision/incremento | Generar existencia preparada |
| Presentaciones | Catalogo + Almacen + Ventas | Definir SKU base/presentacion | Ejecutar empaque |
| Paquetes / kits | Catalogo + Almacen + Ventas | Definir receta, componentes y modalidad | Armar/desarmar fisicamente o decidir precio final |
| Etiquetas | Almacen/Inventario + Catalogo | Definir requisito | Imprimir o crear unidades |
| Imagenes | Catalogo/Ecommerce | Mantener evidencia | Bloquear compra interna |
| Variantes | Catalogo | Diferenciar SKUs | Inventar atributos ambiguos |

## Perfil de usuario recomendado

### Catalogo basico

Puede:

- crear/editar producto;
- crear/editar SKU;
- asignar marca/categoria;
- capturar descripcion;
- administrar imagenes;
- administrar variantes;
- ver alertas.

No debe:

- ver costos;
- editar relacion proveedor-SKU;
- editar precio final si existe modulo de precios;
- decidir reglas avanzadas de inventario sin permiso.

### Catalogo avanzado

Puede:

- configurar unidad base/factor;
- configurar granel;
- configurar presentaciones;
- proponer reglas de inventario.

Debe ver advertencias claras cuando una decision impacta Almacen, Inventario o Ventas.

### Proveedores/Compras

Puede:

- relacionar proveedor-SKU;
- definir unidad compra/factor;
- cargar costos/listas;
- proveedor preferido;
- compra minima;
- dias entrega.

### Inventario/Almacen

Puede:

- validar controles fisicos;
- confirmar lote/caducidad/serie;
- preparar/empaquetar;
- generar etiquetas reales;
- ajustar existencias.

### Precios/Ventas/Comercial

Puede:

- mantener listas de precios;
- precios por canal;
- promociones;
- disponibilidad comercial;
- publicacion ecommerce.

### Costos/Rentabilidad

Puede:

- revisar costo vigente;
- costo real comprado;
- costo referencia;
- margen;
- rentabilidad.

## Riesgos si no se separa

1. El capturista de Catalogo se bloquea porque no sabe costo, fiscal, stock o politica de etiqueta.
2. Se captura informacion inventada para quitar alertas.
3. Un producto aparece activo y se interpreta como listo para vender.
4. Compras usa productos sin fiscal o sin proveedor correcto.
5. Ventas publica productos sin precio/canal/existencia.
6. Almacen recibe productos con reglas fisicas incorrectas.
7. Rentabilidad calcula sobre costos o precios no autorizados.

## Plan recomendado

### Fase 1 - Criterios y etiquetas de estado

Objetivo:

- Hacer que Catalogo explique estados sin mover todavia responsabilidades.

Acciones:

- Documentar y mostrar diferencia entre `activo`, `comprable`, `vendible`, `publicable` y `rentable`.
- Cambiar textos de UI para que "Activo" no prometa venta.
- Separar indicadores por responsable:
  - Catalogo;
  - Proveedores/Compras;
  - Fiscal;
  - Inventario/Almacen;
  - Precios/Ventas;
  - Ecommerce;
  - Costos/Rentabilidad.

### Fase 2 - Claridad en la pantalla actual

Objetivo:

- Que un usuario de Catalogo pueda alimentar productos sin enfrentar decisiones tecnicas profundas, manteniendo por ahora la pantalla actual.

Acciones:

- No dividir Catalogo en modo basico/avanzado en esta etapa.
- No crear permisos nuevos por seccion de Catalogo en esta etapa.
- Mejorar textos, ayudas, secciones visuales y alertas dentro de la pantalla actual.
- Mantener el precio como `precio provisional` hasta que exista modulo formal de Listas de precios.
- Dejar el tema de precio final por canal para Precios/Listas, no para Catalogo.

Decision 2026-06-24:

- El unico punto fuera de lugar confirmado por el dueno es el precio final/listas.
- Catalogo se mantiene como esta, con mejoras de claridad operativa.
- No se debe retomar division de pantallas sin una nueva decision explicita.

### Fase 3 - Mover responsabilidades comerciales

Objetivo:

- Sacar precio final de la responsabilidad normal de Catalogo.

Acciones:

- Mantener precio general solo como lectura o provisional hasta tener modulo de Precios.
- Crear plan de modulo Precios/Listas de venta.
- Catalogo solo muestra "sin precio activo" como alerta de vendibilidad.

### Fase 4 - Proveedor/costo fuera de Catalogo normal

Objetivo:

- Que Catalogo no sea el lugar natural para costos ni condiciones de compra.

Acciones:

- Dejar en Catalogo solo lectura de proveedor-SKU.
- Mantener edicion bajo permiso especifico o moverla al modulo Proveedores.
- Mostrar "sin proveedor activo" como alerta de comprabilidad.
- Mostrar "sin costo vigente" como alerta de Proveedores/Costos, no como calidad de producto.

### Fase 5 - Fiscal con evidencia

Objetivo:

- Resolver fiscal sin exigir que Catalogo invente claves.

Acciones:

- Catalogo captura datos fiscales si los conoce.
- Compras/XML propone datos fiscales con evidencia.
- Fiscal/Contabilidad valida cuando sea necesario.
- SKU comprable/vendible con fiscal incompleto queda bloqueado o advertido segun flujo.

### Fase 6 - Inventario, granel y etiquetas con aprobacion operativa

Objetivo:

- No pedir al capturista normal decidir controles fisicos que afectan Almacen/Inventario.

Acciones:

- Catalogo define la intencion.
- Almacen/Inventario confirma la regla fisica.
- Presentaciones preparadas se ejecutan en Almacen.
- Etiquetas reales se generan en Almacen/Inventario.

### Fase 7 - Dashboard de completitud por objetivo

Objetivo:

- Dejar de tener una sola "calidad" mezclada.

Acciones:

- Crear vistas o filtros:
  - Completo como maestro;
  - Listo para comprar;
  - Listo para vender;
  - Listo para ecommerce;
  - Pendiente de costos/rentabilidad;
  - Pendiente de inventario/almacen.

## Prioridad para tu trabajo actual de ordenar productos

Para acomodar productos existentes, el orden recomendado es:

1. Producto/SKU existe y no esta duplicado.
2. Nombre, marca, categoria y variante clara.
3. Unidad base y factor razonables.
4. Codigo interno/barras si se va a buscar o escanear.
5. Fiscal basico si ya es comprable o vendible.
6. Proveedor-SKU desde Proveedores si se va a comprar.
7. Precio solo si se va a vender y mientras no exista modulo de precios.
8. Reglas de inventario solo para productos prioritarios o con flujo real.
9. Granel/presentaciones solo donde ya exista necesidad operativa.
10. Imagen/ecommerce si se va a publicar.

## Decision recomendada

No conviene seguir agregando campos a Catalogo Productos sin justificar primero si pertenecen realmente a Catalogo.

Recomendacion:

- Mantener Catalogo como fuente de identidad y estructura del SKU.
- Convertir lo demas en alertas por area responsable.
- Construir o planear modulos separados para Precios/Listas y Costos/Rentabilidad.
- Dejar Proveedores como dueno de relacion proveedor-SKU y costo vigente.
- Dejar Almacen/Inventario como dueno de existencias, etiquetas reales y preparacion.

## Siguiente paso sugerido

Antes de tocar codigo funcional:

1. Mantener Catalogo Productos en una sola pantalla operativa.
2. Dejar `precio provisional` como puente temporal.
3. Planear el modulo de Listas de precios por separado cuando toque Ventas/Precios.
4. Crear auditoria de "vendibilidad" separada de "calidad de maestro" sin convertirla en un estatus unico ni dividir la pantalla.
5. Preparar criterio de completitud por objetivo: maestro, compra, fiscal, inventario, venta, ecommerce y rentabilidad.

## Instrucciones para continuar en otros modulos

### Para Proveedores

Objetivo:

- Permitir que un SKU proveedor general se relacione con uno o varios SKUs internos ERP cuando el proveedor no distingue variantes.

Debe contemplar:

- Relacion proveedor-SKU con mismo `sku_proveedor` en varias variantes internas cuando sea necesario.
- Evidencia de variante si viene en descripcion/lista/codigo alterno.
- Estado de matching:
  - match exacto;
  - match por variante sugerida;
  - variante ambigua;
  - variante no informada.
- No aplicar costo/precio a Catalogo sin flujo controlado.

### Para Compras

Objetivo:

- Comprar con el SKU proveedor correcto y conservar la necesidad de identificar variante si el proveedor no la especifica.

Debe contemplar:

- Orden con renglon proveedor general puede requerir seleccion de variante antes o durante recepcion.
- Si la variante ya esta definida en la orden, Recepcion debe recibir directo al SKU interno.
- Si la variante no esta definida, la orden debe llegar con advertencia: "requiere clasificacion de variante al recibir".

### Para Almacen / Recepcion

Objetivo:

- Convertir lo recibido fisicamente al `id_sku_erp` correcto.

Debe contemplar:

- Al recibir un renglon con SKU proveedor general, mostrar variantes internas disponibles del producto.
- Permitir distribuir cantidades entre variantes.
- Exigir lote/caducidad/serie/etiqueta segun la regla del SKU interno final.
- Si no se puede identificar variante, generar incidencia y no meter existencia vendible al SKU incorrecto.

### Para Inventario

Objetivo:

- Guardar existencia por SKU interno real.

Debe contemplar:

- Existencia final siempre por `id_sku_erp`.
- Movimientos deben conservar referencia a proveedor/renglon/recepcion original.
- Para variantes ambiguas no resueltas, usar estado o incidencia de revision antes de disponibilizar.

### Para Precios / Ventas

Objetivo:

- Permitir precio por SKU variante, lista, canal y vigencia.

Debe contemplar:

- Precio por SKU interno, no por SKU proveedor.
- Lista general, ecommerce, mostrador, mayoreo u otros canales.
- Vigencias.
- Alertas cuando SKU no tenga precio para el canal requerido.
- No depender de que Catalogo capture precio final.

### Para Fiscal / Contabilidad

Objetivo:

- Validar fiscal sin frenar la alimentacion progresiva de Catalogo.

Debe contemplar:

- Fiscal parcial permitido en maestro.
- Fiscal completo requerido para operaciones fiscales.
- Evidencia desde XML/CFDI como propuesta.
- Flujo de validacion/autorizacion cuando haya conflicto entre proveedor/XML/maestro.
