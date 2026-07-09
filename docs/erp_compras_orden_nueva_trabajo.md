# ERP Compras - Punto actual: nueva orden de compra

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-08  
Estado: Guia operativa para prompts y tareas del punto actual  
Aplica a: nueva orden y editar orden en `borrador`  
Vista activa: `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

## Objetivo de esta etapa

Cerrar la experiencia de nueva orden de compra y edicion de orden en `borrador` para que Compras pueda capturar una compra real desde:

- Productos ya registrados en ERP.
- XML CFDI como carga rapida de conceptos.
- Mezcla de productos ERP y conceptos XML.
- Productos nuevos propuestos desde buscador o XML.
- Cargos/gastos de compra no inventariables, como envio, empaque u otros costos necesarios para completar el costo total.

La pantalla debe enriquecer informacion de productos, fiscales y costos sin afectar inventario, sin actualizar Catalogo maestro silenciosamente y sin obligar al usuario a rehacer captura.

Decision estructural:

- ERP debe tener sus propias tablas y campos para productos, SKUs, fiscales, costos, proveedor, compras y pendientes.
- No se debe depender de tablas `ecom_*` para cerrar Compras.
- Si una version anterior de compras dejo campos que ya no aplican, se deben auditar y migrar/limpiar con plan explicito, no adaptar la version nueva a errores heredados.
- Antes de implementar cada tarea que persista datos, revisar `ComprasEsquema.php`, `CatalogoErpEsquema.php` y tablas ERP existentes para confirmar si falta tabla/campo/indice.

## Archivos exactos de trabajo

Frontend:

- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`

Backend:

- `app/controladores/Compra.php`
- `app/modelos/OrdenesCompraErp.php`
- `app/modelos/ComprasXmlErp.php`
- `app/modelos/ComprasEsquema.php`
- `app/modelos/CatalogoErpEsquema.php` cuando falten tablas/campos maestros ERP de producto, SKU, proveedor o fiscal.

Relacionados bajo demanda:

- `app/modelos/PagosCompraErp.php`
- `app/modelos/AdjuntosCompraErp.php`
- `app/modelos/CatalogoErpDatos.php`
- `app/modelos/Almacenes.php`

No tocar para esta etapa salvo necesidad justificada:

- Inventario fisico.
- Recepcion de almacen.
- Flujo legado de compras.
- Catalogo maestro con escritura directa no autorizada.
- Tablas `ecom_*` como fuente principal del nuevo flujo ERP.

## Estado actual detectado

La vista de orden nueva ya contiene:

- Cabecera de orden: proveedor, almacen, fecha, folio proveedor, moneda, tipo de cambio, contacto, telefono, direccion, observaciones.
- Buscador de productos del proveedor usando `/compra/orden_buscar_skus_erp`.
- Carga de XML dentro del bloque de productos.
- Tabla de partidas con cantidad, costo, impuesto, incluye IVA, descuento, fiscal y total.
- Resumen de productos registrados/nuevos y fiscales completos/pendientes.
- Modal/flujo JS para editar datos fiscales por partida.
- Secciones de finanzas, adjuntos y documentos fiscales que aparecen cuando existe `id_orden_compra`.
- Parseo de XML sin orden guardada mediante `/compra/orden_xml_parse_erp`.
- Importacion formal de XML cuando ya existe orden mediante `/compra/orden_xml_importar_erp`.

El punto critico actual:

- XML sin orden guardada puede cargar productos temporalmente, pero debe intentar detectar productos ERP igual que el buscador manual.
- XML con orden guardada puede registrarse formalmente, pero la UI principal debe seguir siendo la misma tabla de partidas.
- Pagos, notas, adjuntos y documentos fiscales formales requieren orden persistida.
- Falta cerrar una politica consistente de borrador automatico y enriquecimiento delegable a Catalogo.
- La seccion visible de "Documentos fiscales" y conciliacion inferior se siente repetitiva para captura; debe eliminarse de esta vista o quedar solo como trazabilidad tecnica bajo demanda.
- Falta confirmar/crear estructura ERP propia para datos fiscales maestros y fiscales capturados/propuestos desde compras/XML.

## Flujo objetivo de orden nueva

### Camino A: productos ERP primero

1. Usuario selecciona proveedor y almacen.
2. Busca SKU/producto del proveedor.
3. Agrega partidas ERP.
4. El sistema trae ultimo costo, unidad, impuesto y datos fiscales disponibles.
5. Usuario ajusta cantidad, costo, descuento, impuesto o fiscal si aplica.
6. Guarda borrador o envia orden.

Criterio:

- Cada partida ERP conserva `id_sku_erp`.
- Si faltan fiscales, aparecen en pendientes.
- No se actualiza Catalogo maestro al editar fiscales en la orden.

### Camino B: XML primero sin orden guardada

1. Usuario selecciona XML desde el bloque de productos.
2. El frontend llama parseo temporal, no importacion formal.
3. Cada concepto XML se busca contra ERP con la misma intencion que el buscador manual.
4. Si coincide por SKU proveedor, SKU ERP, codigo interno o regla definida, se agrega como producto registrado en la misma tabla.
5. Si no coincide, se agrega como producto nuevo/propuesto en la misma tabla.
6. El sistema intenta mezclar contra partidas existentes si ya habia productos capturados.
7. Usuario puede completar proveedor, almacen y guardar borrador.

Criterio:

- No se pierde captura al no existir `id_orden_compra`.
- Los conceptos XML muestran origen XML.
- Los conceptos XML detectados como ERP se comportan igual que un producto agregado desde el buscador.
- Datos fiscales XML se guardan como datos de partida/propuesta al persistir.
- No se crea documento fiscal formal hasta tener orden guardada.
- La tabla inferior de conciliacion no debe ser necesaria para que el usuario capture la orden.

### Camino C: orden guardada y XML formal

1. Orden ya tiene ID.
2. Usuario carga XML.
3. Backend puede registrar documento fiscal y evitar duplicado por UUID/hash.
4. Backend debe usar el XML para actualizar/enriquecer la misma tabla de partidas, no para obligar a operar otra tabla repetida.
5. Las diferencias importantes deben mostrarse como alertas o pendientes accionables dentro de la experiencia de partidas.
6. La conciliacion detallada queda como herramienta tecnica/auditoria bajo demanda, no como seccion principal de captura.

Criterio:

- El XML queda trazable.
- Las diferencias quedan visibles.
- Resolver conciliacion no borra partidas de la orden sin accion explicita.
- La vista principal no duplica conceptos XML en una segunda tabla operativa.

### Camino D: buscador sin resultados y producto nuevo

1. Usuario busca SKU o nombre.
2. Si no hay resultados, la UI ofrece agregar producto nuevo/propuesto.
3. El usuario captura minimo: descripcion, SKU proveedor si existe, unidad, cantidad, costo, impuesto y datos fiscales disponibles.
4. La partida queda marcada como `producto_nuevo` o `pendiente_catalogo`.
5. Al guardar, se conserva como pendiente delegable a Catalogo.

Criterio:

- Compras puede terminar la captura aunque Catalogo aun no haya dado de alta el producto.
- Catalogo recibe informacion suficiente para completar producto/SKU despues.
- No se crea producto maestro automaticamente sin regla aprobada.

### Camino E: cargo/gasto no inventariable

1. Usuario agrega un cargo de compra: envio, empaque, maniobra, seguro, servicio, descuento/cargo especial u otro.
2. El cargo entra en la misma tabla o en una subseccion compacta de partidas de costo, pero debe sumar al total de compra.
3. El cargo queda marcado como `no_inventariable`.
4. El cargo no pasa a recepcion de almacen ni genera existencia.
5. Finanzas/contabilidad puede verlo como gasto/costo asociado a la compra.

Criterio:

- El total de la compra puede cuadrar con factura/proveedor aunque no todo sea producto inventariable.
- Inventario recibe solo productos inventariables.
- Direccion/Finanzas puede distinguir mercancia vs gastos/cargos.

## Modelo de datos funcional por partida

Cada partida de orden debe poder representar:

- Identidad ERP: `id_sku_erp`, `sku`, `sku_proveedor`, nombre.
- Identidad temporal: concepto XML o producto nuevo sin SKU ERP.
- Origen: ERP, XML, solicitud, manual futura.
- Cantidad y unidad.
- Costo unitario capturado.
- Si costo incluye impuesto.
- Porcentaje impuesto.
- Descuento.
- Datos fiscales capturados/propuestos.
- Estado fiscal: completo o pendiente.
- Estado catalogo: registrado o pendiente de alta.
- Referencia a solicitud/XML si aplica.
- Tipo de partida: producto inventariable, producto/servicio no inventariable, cargo/gasto de compra.

Regla:

- Si el dato viene del XML, es propuesta o dato de documento fiscal.
- Si el dato viene de ERP/Catalogo, es dato maestro vigente.
- Si el usuario lo edita en Compras, es dato capturado en orden o pendiente para validar.
- Si la partida es cargo/gasto, no debe requerir recepcion fisica ni afectar existencia.

## Esquema ERP propio requerido

Objetivo:

- Que Compras, Catalogo, Finanzas, Almacen e Inventario trabajen sobre estructura ERP propia, limpia y mantenible.
- Evitar que el modulo nuevo dependa de tablas ecommerce o campos heredados que no representan el flujo actual.

Regla de trabajo:

- Antes de implementar persistencia nueva, auditar esquema actual.
- Si falta una tabla/campo/indice, agregarlo mediante modelos `*Esquema.php`.
- Si existe campo heredado que ya no debe usarse, documentar reemplazo y migracion antes de eliminarlo.
- No eliminar campos con datos sin plan de migracion/respaldo y confirmacion del dueno.

### Tablas/capacidades ERP que deben existir o confirmarse

Catalogo maestro:

- Producto ERP.
- SKU ERP.
- Relacion SKU proveedor.
- Datos fiscales maestro por producto/SKU.
- Unidad de compra/venta y conversiones si aplican.
- Reglas de inventario: inventariable, requiere lote, caducidad, serie.

Compras:

- Orden de compra cabecera.
- Orden de compra detalle.
- Tipo de partida: producto inventariable, producto no inventariable/servicio, cargo/gasto.
- Origen de partida: ERP, XML, solicitud, manual.
- Datos fiscales capturados/propuestos por partida.
- Costo sin IVA, costo con IVA, impuesto, descuento e importe de descuento.
- Pendientes generados para Catalogo/Costos/Fiscales.
- Documento XML fiscal formal ligado a orden cuando exista ID.

Pendientes/flujo inter-area:

- Pendiente de producto nuevo.
- Pendiente de fiscal incompleto/diferente.
- Pendiente de relacion SKU proveedor.
- Pendiente de costo diferente.
- Pendiente de unidad diferente.

### Datos fiscales ERP

Se requiere estructura ERP para datos fiscales, sin depender de ecommerce.

Debe poder guardar a nivel maestro:

- Clave producto/servicio SAT.
- Clave unidad SAT.
- Unidad fiscal.
- Objeto impuesto.
- Tipo impuesto.
- IVA porcentaje.
- IEPS porcentaje.
- Si requiere factura.
- Vigencia/estatus.
- Usuario/fecha de actualizacion.

Debe poder guardar a nivel compra/partida:

- Dato fiscal usado en esa compra.
- Origen del dato: ERP, XML, usuario compras.
- Diferencia contra maestro si existe.
- Estado: completo, pendiente, propuesto, validado.

Reglas:

- Dato fiscal maestro vive en Catalogo ERP.
- Dato fiscal de compra vive en Compras como evidencia/propuesta.
- XML puede proponer fiscales, pero no debe actualizar maestro sin flujo de Catalogo.
- Si ERP no tiene tabla fiscal maestro, se debe crear antes de cerrar tareas de fiscales.

### Limpieza de version anterior

Como ya existio una version previa de Compras, cada tarea debe revisar si esta adaptandose a estructuras viejas.

Preguntas obligatorias antes de tocar persistencia:

- Este campo pertenece al flujo ERP nuevo o al flujo legado?
- Se usa actualmente en vistas/modelos nuevos?
- Tiene datos reales que deban migrarse?
- Se puede dejar de usar sin eliminarlo todavia?
- Hace falta crear campo nuevo con nombre claro en vez de reutilizar uno ambiguo?

Criterio:

- El codigo nuevo debe leer/escribir campos claros del flujo ERP.
- Lo legado puede mantenerse temporalmente, pero no debe dirigir las decisiones del modulo nuevo.

## Datos fiscales que debe manejar esta etapa

Campos minimos:

- Clave producto/servicio SAT.
- Clave unidad SAT.
- Unidad.
- Objeto impuesto.
- Tipo impuesto.
- IVA porcentaje.
- IEPS porcentaje.
- Si el costo incluye IVA.
- Si requiere factura.

Reglas:

- Fiscal completo requiere clave SAT, clave unidad SAT, unidad, objeto impuesto y tipo impuesto.
- IVA/IEPS pueden ser 0 si la regla fiscal lo permite.
- No llenar datos maestros con valores vacios.
- Si XML y ERP difieren, generar diferencia visible o pendiente; no decidir en silencio.
- Si no existe tabla ERP para fiscales maestros, crear tarea de esquema antes de persistir estos datos como definitivos.
- Mientras Catalogo no valide, los fiscales capturados en Compras son evidencia/propuesta de partida.

## Costos que debe manejar esta etapa

Campos y conceptos:

- Ultimo costo ERP.
- Costo capturado en orden.
- Costo XML.
- Costo unitario neto sin IVA.
- Costo unitario bruto si incluye IVA.
- Descuento.
- Subtotal.
- Impuestos.
- Total.
- Moneda y tipo de cambio.
- Tipo de entrada de precio: precio sin IVA, precio con IVA o importe/cargo.

Reglas:

- El total de la orden se calcula desde partidas.
- El costo de orden no debe actualizar inventario.
- El costo de orden no debe cambiar costo maestro sin flujo aprobado.
- El costo promedio pertenece a recepcion/inventario, no a captura de orden.
- La captura debe ser intuitiva: el usuario debe saber si esta escribiendo precio sin IVA o precio con IVA.
- Por defecto, la captura debe trabajar con precio unitario sin IVA.
- La columna de costo unitario y la columna de precio unitario sin IVA deben ser editables y mantenerse sincronizadas segun el modo seleccionado.
- Quitar el checkbox por partida "precio incluye IVA" como control principal; reemplazarlo por un selector/checkbox de encabezado o configuracion de captura que indique el modo de precio.
- Si el modo activo es "precio unitario sin IVA", el costo unitario mostrado/calculado con IVA debe derivarse del impuesto.
- Si el modo activo es "precio unitario con IVA", el precio sin IVA debe calcularse descontando el impuesto.

## UX de tabla de partidas

La tabla debe ser la unica superficie principal para productos ERP, conceptos XML, productos nuevos y cargos no inventariables.

Columnas esperadas:

- Tipo/estado: registrado, nuevo, XML, cargo, pendiente Catalogo.
- SKU o referencia.
- Producto/concepto.
- Cantidad.
- Precio unitario sin IVA editable.
- Costo unitario con IVA editable o calculado segun modo.
- Impuesto.
- Descuento.
- Fiscal.
- Total.
- Acciones.

Encabezado/configuracion de precio:

- Control visible para elegir modo de captura: `precio sin IVA` por defecto, o `precio con IVA`.
- El modo debe poder aplicarse a toda la tabla y, si hace falta despues, permitir excepcion por partida.
- El usuario no debe tener que interpretar si el costo incluye IVA por una casilla escondida en cada fila.

Descuento general:

- El input de descuento masivo debe estar junto al encabezado/columna `Descuento`, no separado en una zona confusa.
- Al escribir descuento general, el sistema debe preguntar o permitir elegir tipo:
  - Porcentaje base 100: `50` significa 50%.
  - Porcentaje decimal: `0.50` significa 50%.
  - Importe SAT/cantidad: descuento monetario por partida o distribuido, segun regla definida.
- SAT maneja descuento como importe, por lo que el sistema debe guardar/calcular el importe final de descuento aunque la captura sea porcentaje.
- La UI puede usar modal o configuracion inline, pero debe dejar claro que se aplicara a todas las partidas seleccionadas o a todas las partidas.

Reglas de descuento:

- Descuento por partida debe conservarse editable.
- Descuento general debe ser una herramienta para llenar/recalcular descuentos, no un dato ambiguo sin trazabilidad.
- Si se aplica porcentaje, guardar tambien el importe calculado.
- Si se aplica importe general, definir si se distribuye proporcionalmente o como importe por partida antes de codificar.

## Borrador automatico

Problema:

- Algunas acciones requieren `id_orden_compra`: adjuntos, pagos/notas, XML formal.
- En orden nueva, el usuario no debe guardar manualmente, salir y volver.

Politica recomendada:

- Si una accion requiere ID y la orden no existe, el frontend debe guardar borrador automaticamente con datos minimos y continuar.
- Datos minimos: proveedor, almacen destino y al menos una partida cuando la accion dependa de partidas.
- Al guardar, actualizar `#orden_id`, mostrar secciones dependientes y continuar accion original.

Acciones candidatas:

- Importar XML formal.
- Subir adjunto.
- Registrar pago.
- Registrar nota de credito.

Decision pendiente del dueno:

- Si cargar XML en orden nueva debe seguir siendo parseo temporal o debe guardar borrador automaticamente antes de importar formalmente.

Recomendacion para esta etapa:

- Mantener XML sin ID como parseo temporal para captura rapida.
- Implementar borrador automatico para adjuntos, pagos/notas y opcion futura de "guardar e importar XML formal".

## Documentos fiscales y conciliacion

Decision de UX para esta etapa:

- Quitar de la vista principal la seccion visible de `Documentos fiscales` y la tabla de conciliacion inferior.
- Conservar adjuntos como seccion documental principal.
- Conservar XML como mecanismo de carga/enriquecimiento de partidas.
- Si se requiere trazabilidad fiscal del XML, mostrar un resumen compacto o acceso tecnico bajo demanda, no una segunda tabla de trabajo.

Explicacion operativa:

- Para el usuario de Compras, "conciliar" no debe sentirse como capturar lo mismo dos veces.
- La conciliacion debe ser una regla interna: el sistema detecta si el XML coincide o difiere de las partidas.
- Las diferencias deben convertirse en pendientes/alertas dentro de la tabla principal: producto no encontrado, costo diferente, impuesto diferente, cantidad diferente, concepto no incluido.

Criterio:

- El usuario carga XML y ve resultados en la tabla principal.
- No hay duplicidad visual entre "partidas" y "conceptos XML".
- Auditoria o soporte puede revisar documentos XML si hace falta, pero no estorba el flujo diario.

## Pendientes delegables a Catalogo

Compras debe generar informacion accionable para Catalogo cuando:

- Producto XML no tiene `id_sku_erp`.
- Producto existe pero faltan fiscales.
- Producto existe pero XML trae fiscal diferente.
- SKU proveedor no esta relacionado.
- Costo cambio contra ultimo costo.
- Unidad XML no coincide con unidad ERP.
- El usuario agrega producto nuevo desde buscador sin resultados.
- El XML agrega concepto no detectado como ERP.

Salida esperada:

- Lista de pendientes por partida.
- Tipo de pendiente.
- Datos propuestos.
- Origen del dato.
- Accion sugerida.

No resolver aqui sin confirmacion:

- Alta definitiva de producto.
- Cambio de impuestos maestros.
- Cambio de unidad maestra.
- Cambio de costo maestro.
- Alta definitiva de cargos/gastos como productos inventariables.

## Orden recomendado antes de codificar

1. Auditar esquema ERP actual de Compras y Catalogo.
2. Identificar campos/tablas heredadas de la version anterior de Compras.
3. Definir tablas/campos faltantes para fiscales ERP, tipo de partida, origen, descuento, costos y pendientes.
4. Crear/actualizar `*Esquema.php` con auditoria y plan de actualizacion.
5. Migrar o mapear datos existentes si el dueno confirma que deben conservarse.
6. Implementar UI/backend de orden nueva y editar borrador sobre estructura ERP limpia.

## Nueva orden vs editar orden

Nueva orden:

- Permite captura flexible.
- XML sin ID puede parsearse temporalmente.
- Borrador automatico puede crear orden cuando una accion requiera ID.
- Debe evitar perdida de captura.

Editar orden en `borrador`:

- Debe permitir las mismas operaciones que nueva orden.
- XML formal puede registrarse porque ya existe ID.
- Debe poder agregar productos ERP, productos nuevos y cargos.
- Debe conservar trazabilidad de cambios y recalcular totales.

Editar orden `enviada`, `parcial`, `recibida` o `cancelada`:

- No debe reutilizarse como flujo libre de captura.
- Las modificaciones deben ser restringidas por reglas de negocio y permisos.
- Si se requiere correccion despues de enviada, debe documentarse como ajuste, cancelacion controlada o flujo especial.

## Secciones de trabajo y prompts listos

### Tarea 1: Persistir origen y fiscales de partidas

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Persistencia de partidas.
Objetivo: asegurar que al guardar orden se persistan origen de partida, datos fiscales capturados/propuestos, si costo incluye IVA y estado de producto registrado/nuevo.
Archivos esperados: public/assets/js/custom/apps/erp/compras/ordenes/formulario.js, app/controladores/Compra.php, app/modelos/OrdenesCompraErp.php, app/modelos/ComprasEsquema.php si falta columna.
No modificar inventario, catalogo maestro ni recepcion.
Criterio: al recargar la orden se conserva fiscal, costo, origen XML/ERP y estado pendiente/registrado.
```

### Tarea 1.0: Auditar y preparar esquema ERP de Compras/Catalogo

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Esquema ERP.
Objetivo: auditar tablas/campos actuales de Compras y Catalogo para confirmar si existen estructuras ERP propias para datos fiscales, tipo de partida, origen XML/ERP, cargos no inventariables, descuentos e indicadores de pendientes.
Revisar: ComprasEsquema.php, CatalogoErpEsquema.php, OrdenesCompraErp.php y CatalogoErpDatos.php.
No usar tablas ecom_* como fuente principal del nuevo flujo.
No eliminar campos heredados sin plan de migracion y confirmacion.
Criterio: entregar plan exacto de columnas/tablas a crear, conservar, migrar o dejar de usar antes de implementar UI.
```

### Tarea 1.1: Crear datos fiscales ERP maestros y fiscales de compra

```text
Trabaja solo en Compras/Catalogo > Datos fiscales ERP.
Objetivo: crear o ajustar esquema para que ERP tenga datos fiscales maestros por producto/SKU y datos fiscales capturados/propuestos por partida de compra.
Los fiscales de XML o usuario Compras no actualizan maestro automaticamente; generan propuesta/pendiente para Catalogo.
Archivos esperados: CatalogoErpEsquema.php, ComprasEsquema.php y modelos relacionados.
No depender de tablas ecom_*.
Criterio: existe estructura para guardar fiscal maestro ERP y fiscal usado/propuesto en compra con origen y estado.
```

### Tarea 2: UX de XML temporal en orden nueva

```text
Trabaja solo en Compras > Orden nueva/editar borrador > XML temporal.
Objetivo: mejorar la carga XML sin id_orden_compra para que busque cada concepto contra ERP igual que el buscador manual, agregue productos registrados cuando coincidan, agregue productos nuevos cuando no coincidan, marque origen XML, muestre fiscales pendientes y no pierda captura.
Archivos esperados: formulario.js y, solo si hace falta, Compra.php/ComprasXmlErp.php.
No registrar documento fiscal formal sin orden guardada.
Criterio: cargar XML recalcula totales, conserva productos ERP previos, detecta SKUs ERP/proveedor y muestra productos nuevos/fiscales pendientes en la misma tabla de partidas.
```

### Tarea 2.1: Detectar productos ERP al cargar XML

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Deteccion ERP desde XML.
Objetivo: al parsear XML, buscar cada concepto por SKU proveedor, SKU ERP, no_identificacion y descripcion normalizada para detectar si ya existe en ERP.
Si existe, usar id_sku_erp, ultimo costo y datos maestros disponibles como si se hubiera seleccionado desde el buscador.
Si no existe, agregar como producto nuevo pendiente de Catalogo.
No usar la tabla inferior de conciliacion como paso obligatorio.
Criterio: un producto que ya esta en ERP queda marcado como registrado aunque venga desde XML.
```

### Tarea 3: Borrador automatico para acciones con ID

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Borrador automatico.
Objetivo: crear una funcion frontend que guarde borrador automaticamente cuando una accion requiera id_orden_compra y la orden sea nueva.
Acciones: subir adjunto, registrar pago, registrar nota de credito; dejar XML formal como opcion separada.
Archivos esperados: formulario.js; backend solo si orden_guardar_erp no devuelve id suficiente.
No cambiar reglas contables ni permitir pagos si backend los rechaza por estatus.
Criterio: despues del autoguardado se actualiza #orden_id, se muestran secciones y continua la accion original.
```

### Tarea 4: Productos nuevos desde buscador o XML

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Producto nuevo.
Objetivo: cuando el buscador no encuentre resultados o XML traiga un concepto no detectado, permitir agregar una partida como producto nuevo/propuesto.
Debe capturar descripcion, SKU proveedor opcional, unidad, cantidad, precio, impuesto y fiscales disponibles.
No crear producto maestro automaticamente.
Criterio: la partida se guarda como pendiente de Catalogo y se puede terminar la orden sin perder el dato.
```

### Tarea 5: Cargos/gastos no inventariables

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Cargos no inventariables.
Objetivo: permitir agregar conceptos como envio, empaque, seguro u otros gastos que suman al total de compra pero no generan inventario ni recepcion fisica.
Archivos esperados: formulario.js, OrdenesCompraErp.php y ComprasEsquema.php si falta tipo de partida.
No afectar Inventario ni Almacen.
Criterio: el total de orden incluye el cargo, pero recepcion/inventario solo recibe productos inventariables.
```

### Tarea 6: UX de precios con IVA/sin IVA

```text
Trabaja solo en Compras > Orden nueva/editar borrador > UX precios.
Objetivo: reemplazar la logica confusa de "precio incluye IVA" por un modo de captura visible en encabezado/configuracion: precio sin IVA por defecto o precio con IVA.
Hacer editables precio unitario sin IVA y costo unitario con IVA, sincronizandolos segun impuesto y modo activo.
No cambiar reglas fiscales ni totales backend sin validar equivalencia.
Criterio: el usuario entiende que precio esta capturando y los totales coinciden.
```

### Tarea 7: Descuento general en encabezado

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Descuento general.
Objetivo: mover o agregar el descuento masivo junto al encabezado/columna Descuento y permitir elegir tipo de captura: porcentaje base 100, porcentaje decimal o importe SAT/cantidad.
Si se captura porcentaje, calcular y guardar importe de descuento por partida.
Antes de codificar pregunta si el importe general se distribuye proporcionalmente o se aplica por partida.
Criterio: aplicar descuento general llena descuentos de partidas seleccionadas o todas, con calculo claro y reversible.
```

### Tarea 8: Quitar documentos fiscales/conciliacion de la vista principal

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Simplificar XML.
Objetivo: quitar de la vista principal la seccion Documentos fiscales y la tabla inferior de conciliacion, dejando XML como carga/enriquecimiento de la tabla principal y adjuntos como documentos visibles.
Conservar endpoints backend si se usan para trazabilidad, pero no obligar al usuario a operar esa tabla.
Criterio: la pantalla tiene una sola tabla operativa de partidas y las diferencias XML aparecen como pendientes/alertas.
```

### Tarea 9: Pendientes para Catalogo desde Compras

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Pendientes Catalogo.
Objetivo: definir y guardar pendientes accionables cuando una partida XML o ERP tenga producto nuevo, fiscales faltantes, fiscal diferente, unidad diferente, SKU proveedor no relacionado o costo diferente.
Archivos esperados: OrdenesCompraErp.php, ComprasEsquema.php y vista/JS solo para mostrar resumen.
No crear productos definitivos ni actualizar catalogo maestro automaticamente.
Criterio: cada pendiente queda vinculado a orden/partida, tiene tipo, datos propuestos y estado pendiente/resuelto.
```

### Tarea 10: Validaciones antes de enviar orden

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Enviar orden.
Objetivo: reforzar validaciones para enviar orden: proveedor, almacen, partidas validas, cantidades/costos positivos, permisos y reglas de fiscales/productos pendientes segun politica documentada.
Antes de codificar pregunta si se permite enviar con productos nuevos o fiscales pendientes.
Archivos esperados: OrdenesCompraErp.php, Compra.php y formulario.js para mensajes.
No afectar inventario.
Criterio: borrador guarda flexible; enviada exige reglas claras y mensajes accionables.
```

### Tarea 11: Diferencias costo/XML/ERP

```text
Trabaja solo en Compras > Orden nueva/editar borrador > Diferencias de costos.
Objetivo: mostrar y persistir diferencias entre ultimo costo ERP, costo capturado y costo XML, generando propuesta o pendiente cuando exceda una tolerancia.
Antes de codificar pregunta la tolerancia aceptada.
No actualizar costo maestro ni costo promedio.
Criterio: usuario ve diferencia, puede justificarla o dejar pendiente para Catalogo/Costos.
```

## Checklist de terminado de esta etapa

- Existe o queda planificada estructura ERP propia para fiscales, tipo de partida, origen, descuentos, cargos y pendientes.
- No se depende de tablas `ecom_*` para cerrar orden nueva/editar borrador.
- Se puede crear orden nueva desde productos ERP.
- Se puede crear orden nueva desde XML sin guardar primero.
- Se pueden mezclar productos ERP y XML.
- XML detecta productos ERP igual que el buscador manual.
- Se puede agregar producto nuevo cuando buscador/XML no detecta ERP.
- Se pueden agregar cargos/gastos no inventariables que suman al total.
- Se conservan datos fiscales al guardar y recargar.
- Se conservan indicadores de producto registrado/nuevo.
- Se conservan indicadores de origen de partida.
- Se conserva tipo de partida: inventariable, no inventariable, cargo/gasto.
- Precio sin IVA y precio con IVA son claros, editables/sincronizados y con modo de captura visible.
- Descuento general se aplica con tipo de descuento claro y guarda importe calculado.
- Totales se calculan igual en frontend y backend.
- La orden no afecta inventario al guardar/enviar.
- XML formal solo se registra cuando existe orden.
- La vista principal no muestra una seccion repetitiva de documentos fiscales/conciliacion.
- Adjuntos/pagos/notas no obligan a salir de la pantalla.
- Los pendientes para Catalogo quedan visibles y delegables.

## Dudas que deben preguntarse antes de implementar

- Se permite enviar orden con productos nuevos pendientes de alta?
- Se permite enviar orden con fiscales incompletos?
- XML sin ID debe quedarse como parseo temporal o debe autoguardar e importar formalmente?
- Compras puede registrar pago real o solo intencion/reporte de pago?
- Que diferencia de costo requiere autorizacion?
- Que dato fiscal del XML puede actualizar Catalogo y cual solo genera pendiente?
- Que campos exactos se usan para detectar ERP desde XML: SKU proveedor, SKU ERP, no_identificacion, descripcion o combinacion?
- Los cargos/gastos no inventariables se prorratean al costo de productos o solo se registran como gasto separado?
- Si hay descuento general por importe, se distribuye proporcionalmente entre partidas o se aplica como importe por partida?
