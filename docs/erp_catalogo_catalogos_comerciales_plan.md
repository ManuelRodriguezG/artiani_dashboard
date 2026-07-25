# ERP Catalogo - Plan de catalogos comerciales

Fecha: 2026-07-23
Proyecto vigente: `C:\xampp\htdocs\panel_de_control`

## Contexto operativo

El negocio usa mucho Facebook, WhatsApp y redes sociales. El flujo actual obliga a tomar o reenviar fotos manualmente, llenar telefonos de imagenes y generar materiales de venta de forma repetitiva.

Existe un flujo legacy en `http://panel.com.local/producto/catalogo_pdf`:

- Controlador: `app/controladores/Producto.php`, metodo `catalogo_pdf()`.
- Vista: `app/vistas/paginas/apps/ecommerce/catalog/catalogo_pdf.php`.
- Fuente: tablas/modelos ecommerce legacy, categorias/clasificaciones anteriores y HTML grande incrustado.

## Hallazgos del flujo legacy

- El flujo esta acoplado a `Producto` legacy y a ecommerce anterior, no al Catalogo ERP nuevo.
- La vista mezcla consulta, composicion visual, imagenes base64 grandes y estructura imprimible en un solo archivo.
- `public/error_log` registra fallos historicos de `catalogo_pdf.php` por datos nulos (`count()` sobre null) y agotamiento de memoria.
- La salida esta pensada para PDF, pero el uso real del negocio es generar material visual para compartir por redes, no necesariamente imprimir.
- No contempla bien lo nuevo de Catalogo ERP: SKUs, presentaciones, paquetes configurables, imagen portada, estado maestro, publicaciones, precios por canal, visibilidad y curaduria.

## Decision propuesta

No continuar el catalogo comercial como PDF legacy dentro de `Producto`.

Crear un flujo nuevo de "Catalogos comerciales" alimentado por Catalogo ERP, pero no convertir `erp/catalogo` en el lugar donde se decide todo lo comercial.

Responsabilidades:

- Catalogo ERP: verdad del producto, SKU, imagenes, marca, categoria, presentaciones, paquetes y calidad minima.
- Ventas/Comercial/Listas de precios: precio que se mostrara segun canal o lista.
- Ecommerce/Publicaciones: curaduria publica, titulo comercial, descripcion publica, slug, disponibilidad publica, mostrar/ocultar precio, producto publicado o pausado.
- Catalogos comerciales: seleccion, plantilla y exportacion visual para compartir por redes.

## Objetivo del nuevo modulo/flujo

Generar catalogos profesionales, reutilizables y rapidos para redes sociales, WhatsApp/Facebook y clientes, sin volver a tomar fotos manualmente.

El primer entregable no debe ser un PDF pesado. Debe ser una vista web exportable y compartible, con posibilidad posterior de generar imagenes por pagina o PDF si realmente se necesita.

## Formatos recomendados

1. Catalogo web compartible
   - URL interna o publica controlada.
   - Filtros por categoria, marca, mascota/necesidad, disponibilidad y busqueda.
   - Ideal para enviar enlace por WhatsApp/Facebook.

2. Galeria visual para redes
   - Tarjetas cuadradas o verticales listas para captura/exportacion.
   - Formatos sugeridos: 1080x1080, 1080x1350 y hoja tipo catalogo.
   - Cada tarjeta puede mostrar imagen, nombre, SKU/presentacion, precio opcional y CTA.

3. Catalogo por coleccion
   - Seleccion manual de productos o por reglas: categoria, marca, temporada, promocion, acuario, perro/gato, etc.
   - Puede tener portada, orden y secciones.

4. PDF imprimible o descargable
   - Fase posterior.
   - Debe generarse por lotes/paginas y no como una vista monolitica gigante.

## Datos minimos por item

- `id_producto_erp`.
- `id_sku`.
- SKU visible o codigo comercial.
- Nombre publico.
- Marca.
- Categoria/ruta.
- Imagen portada.
- Presentacion publica.
- Precio segun lista/canal, opcional.
- Disponibilidad publica simple, no existencia exacta.
- Badges opcionales: nuevo, destacado, oferta, bajo pedido, disponible.
- URL/slug si se comparte como producto publico.

## Reglas importantes

- No mostrar costo proveedor ni costo de referencia.
- No tomar precio desde Catalogo ERP como verdad comercial final si ya existe Listas de precios.
- No mostrar stock exacto en catalogos publicos; usar disponibilidad simple.
- No publicar automaticamente todos los productos activos. Activo en Catalogo no significa publicado.
- Un producto puede estar activo para operacion interna y no estar listo para catalogo publico.
- Las presentaciones y paquetes deben poder mostrarse como opciones comerciales cuando esten configuradas.
- La imagen de portada debe venir de Catalogo ERP; si falta, el producto debe aparecer como incompleto para catalogo comercial.

## Arquitectura sugerida

Fase 1 - Auditoria y diseno

- Auditar `erp_ecommerce_publicaciones` y endpoints actuales de `EcommerceCatalogoPublico`.
- Revisar si publicaciones ya cubren titulo publico, descripcion, presentacion, mostrar precio, destacado, orden y disponibilidad.
- Revisar si falta estructura para colecciones/catalogos comerciales:
  - `erp_catalogo_comercial_catalogos`
  - `erp_catalogo_comercial_items`
  - `erp_catalogo_comercial_plantillas`
  - opcional `erp_catalogo_comercial_exportaciones`

Fase 2 - Vista interna de armado

- Crear pantalla protegida para seleccionar productos/SKUs publicados o candidatos.
- Permitir filtros por categoria, marca, estado de publicacion, sin imagen, sin precio, sin presentacion.
- Permitir ordenar productos y elegir plantilla.
- Permitir vista previa sin escribir archivos.

Fase 3 - Catalogo web/galeria

- Generar una vista visual con tarjetas limpias.
- Permitir modo "sin precio", "con precio", "solo imagen y nombre", "redes".
- Exportacion inicial recomendada: captura/impresion del navegador por secciones, no PDF pesado del backend.

Fase 4 - Exportacion formal

- Generar paginas con tamano fijo y exportar como PNG/JPG/PDF por lote.
- Guardar historial de exportaciones solo si se requiere trazabilidad.

## Ubicacion recomendada

No usar `Producto::catalogo_pdf()` como base.

Opciones:

- Controlador nuevo: `CatalogoComercial` o `ComercialCatalogos`.
- Si se quiere mantener cerca de lo publico: extender `EcommercePublico` solo para API/read-only y crear una vista interna en ERP.
- Menu recomendado: Comercial > Catalogos comerciales, o Ecommerce > Publicaciones/Catalogos si el enfoque principal sera redes y web publica.

Catalogo ERP debe tener enlaces o alertas de "apto para catalogo comercial", pero no debe ser el constructor visual principal.

## Criterio de cierre de planeacion

- Decidir si el primer MVP sera:
  - enlace web compartible;
  - galeria para exportar imagenes;
  - o PDF.
- Definir si se mostraran precios y desde que lista/canal.
- Definir si usara solo productos publicados o tambien candidatos internos.
- Definir plantillas iniciales.
- No aplicar DDL ni migraciones sin autorizacion.

## Plan de arranque recomendado

### Principio rector

El modulo no debe empezar generando archivos. Primero debe permitir armar, revisar y previsualizar un catalogo comercial con datos vivos del ERP.

La exportacion a imagen/PDF viene despues, cuando la seleccion, el orden, las plantillas y la informacion comercial ya esten correctas.

### MVP recomendado

Primer MVP: "Galeria comercial interna con vista compartible".

Incluye:

- pantalla interna para crear una coleccion de catalogo;
- seleccion de productos/SKUs desde Catalogo ERP;
- filtros por categoria, marca, imagen, precio y publicacion;
- plantilla visual de tarjetas para redes;
- vista previa responsive;
- modo con precio y modo sin precio;
- URL interna protegida para revisar;
- boton de impresion/captura desde navegador como salida inicial.

No incluye todavia:

- exportacion PNG/JPG automatica;
- PDF definitivo;
- publicacion publica sin control;
- historial pesado de exportaciones;
- edicion de precios desde este modulo.

## Modulo y ubicacion

Nombre funcional sugerido: `Catalogos comerciales`.

Ubicacion de menu recomendada:

- `Comercial > Catalogos comerciales`

Motivo:

- No es mantenimiento de producto puro.
- No es POS.
- No es compras ni inventario.
- Puede usar Catalogo ERP, Publicaciones ecommerce y Listas de precios, pero su objetivo es material comercial.

Alternativa aceptable:

- `Ecommerce > Catalogos comerciales` si el enfoque principal se vuelve web/publicaciones.

No recomendado:

- `Producto::catalogo_pdf()` legacy.
- Meter el constructor visual dentro de `Catalogo ERP > Productos`, porque saturaria una pantalla que ya es de mantenimiento maestro.

## Permisos propuestos

Sin aplicar DDL hasta autorizacion, pero la matriz deberia contemplar:

- `catalogos_comerciales.ver`: consultar catalogos, previsualizar y usar material ya armado.
- `catalogos_comerciales.crear`: crear borradores y seleccionar items.
- `catalogos_comerciales.editar`: modificar contenido, orden, plantilla y textos comerciales.
- `catalogos_comerciales.publicar`: habilitar enlace publico/compartible o marcar como listo para uso.
- `catalogos_comerciales.exportar`: generar archivos descargables si despues se implementa exportacion formal.
- `catalogos_comerciales.administrar`: administrar plantillas, formatos y configuracion.

Regla:

- Un usuario de Catalogo puede preparar informacion del producto, pero no necesariamente publicar materiales comerciales.
- Un usuario de Ventas/Comercial puede armar catalogos desde productos ya aprobados/publicables, sin editar datos maestros del producto.

## Pantallas iniciales

### 1. Listado de catalogos comerciales

Objetivo:

- Ver catalogos creados, estatus, fecha, responsable, total de items y plantilla.

Columnas:

- Codigo/nombre.
- Tipo: redes, web, temporada, marca, categoria, cliente.
- Estatus: borrador, en_revision, listo, pausado, archivado.
- Visibilidad: interno, enlace compartible, publico.
- Items.
- Plantilla.
- Ultima actualizacion.
- Acciones segun permiso.

Acciones:

- Nuevo catalogo.
- Editar.
- Previsualizar.
- Duplicar.
- Archivar.

### 2. Constructor de catalogo

Objetivo:

- Armar el catalogo sin tocar datos maestros.

Secciones:

- Datos generales: nombre, descripcion interna, tipo, canal, vigencia opcional.
- Fuente de productos: manual, por categoria, por marca, por etiqueta comercial o por publicaciones.
- Filtros de saneamiento: sin imagen, sin precio, no publicado, sin presentacion, inactivo.
- Items seleccionados: orden, destacado, texto corto opcional, mostrar precio, mostrar disponibilidad.
- Plantilla: formato redes, grid, lista compacta, ficha por producto.
- Vista previa.

### 3. Vista previa / galeria

Objetivo:

- Revisar el resultado como lo veria una persona externa o como se exportaria.

Modos:

- Tarjetas 1080x1080.
- Tarjetas 1080x1350.
- Catalogo web responsive.
- Hoja compacta para imprimir, fase posterior.

Controles:

- Mostrar/ocultar precio.
- Mostrar/ocultar SKU.
- Mostrar/ocultar marca.
- Mostrar disponibilidad simple.
- Cambiar densidad visual.

## Estatus propuestos

Para el catalogo comercial:

- `borrador`: editable; no debe compartirse como version final.
- `en_revision`: listo para revisar contenido/precios/imagenes.
- `listo`: aprobado internamente para usar.
- `pausado`: no se usa temporalmente, conserva historial.
- `archivado`: ya no aparece por defecto.

Para items dentro de un catalogo:

- `activo`: aparece en la vista.
- `oculto`: queda en el catalogo pero no se muestra.
- `pendiente`: requiere completar imagen/precio/publicacion antes de usarse.

Regla:

- No usar "activo" del producto como equivalente a "listo para catalogo comercial".

## Datos y contratos

### Fuente de verdad

Datos de producto:

- `erp_catalogo_productos`
- `erp_catalogo_skus`
- `erp_catalogo_imagenes`
- `erp_catalogo_producto_categorias`
- `erp_catalogo_marcas`
- presentaciones/paquetes configurables cuando aplique

Datos publicables:

- `erp_ecommerce_publicaciones`, si la publicacion ya existe.

Precios:

- Listas de precios/Comercial cuando este disponible.
- Precio general solo como fallback temporal si todavia no hay lista formal para ese canal.

Inventario:

- Disponibilidad simple, no existencia exacta.
- No descuenta, no aparta, no genera movimientos.

## Estructura DDL propuesta para fase futura

No aplicar sin autorizacion y respaldo externo.

### `erp_catalogos_comerciales`

Campos sugeridos:

- `id_catalogo_comercial`
- `codigo`
- `nombre`
- `descripcion`
- `tipo_catalogo`
- `canal`
- `visibilidad`
- `estatus`
- `id_lista_precio`
- `mostrar_precios`
- `mostrar_disponibilidad`
- `plantilla`
- `configuracion_json`
- `fecha_inicio`
- `fecha_fin`
- `creado_por`
- `actualizado_por`
- `fecha_registro`
- `fecha_actualizacion`

### `erp_catalogos_comerciales_items`

Campos sugeridos:

- `id_catalogo_item`
- `id_catalogo_comercial`
- `id_producto_erp`
- `id_sku`
- `id_publicacion`
- `orden`
- `destacado`
- `titulo_override`
- `descripcion_corta_override`
- `imagen_override`
- `mostrar_precio`
- `mostrar_disponibilidad`
- `estatus`
- `configuracion_json`
- `fecha_registro`
- `fecha_actualizacion`

### `erp_catalogos_comerciales_plantillas`

Campos sugeridos:

- `id_plantilla`
- `codigo`
- `nombre`
- `tipo_salida`
- `ancho`
- `alto`
- `configuracion_json`
- `estatus`
- `fecha_registro`
- `fecha_actualizacion`

### `erp_catalogos_comerciales_exportaciones`

Fase posterior, solo si se decide guardar historial de archivos:

- `id_exportacion`
- `id_catalogo_comercial`
- `tipo_exportacion`
- `ruta_archivo`
- `estatus`
- `mensaje`
- `generado_por`
- `fecha_registro`

## Tareas especificas iniciales

### Tarea 1 - Auditoria de base y contratos existentes

Objetivo:

- Confirmar que datos actuales alcanzan para armar una galeria comercial sin DDL.

Leer/auditar:

- `EcommercePublico.php`
- `EcommerceCatalogoPublico.php`
- `CatalogoErpDatos.php`
- `CatalogoErpEsquema.php`
- `ListasPreciosErp` si aplica
- tablas `erp_ecommerce_publicaciones`, `erp_catalogo_imagenes`, `erp_catalogo_sku_precios`

Cierre:

- Documento con campos disponibles, faltantes y riesgos.

## Auditoria Tarea 1 - 2026-07-23

Proyecto auditado: `C:\xampp\htdocs\panel_de_control`.

### Confirmacion de alcance

El flujo nuevo de catalogos comerciales no debe continuar desde `Producto::catalogo_pdf()` ni desde `app/vistas/paginas/apps/ecommerce/catalog/catalogo_pdf.php`.

Motivo:

- Ese flujo pertenece al ecommerce/Producto legacy.
- Tiene historial de fallos por datos nulos y consumo alto de memoria.
- No entiende bien Catalogo ERP, publicaciones, presentaciones, paquetes configurables ni listas de precios.

El primer MVP debe arrancar como lectura interna de datos ERP y vista previa web, sin exportar archivos todavia.

### Tablas actuales utiles

Conteos observados en `panel_de_control`:

- `erp_catalogo_productos`: existe, 1544 registros.
- `erp_catalogo_skus`: existe, 1765 registros.
- `erp_catalogo_imagenes`: existe, 1740 registros.
- `erp_catalogo_producto_categorias`: existe, 7194 registros.
- `erp_catalogo_sku_precios`: existe, 1765 registros.
- `erp_ecommerce_publicaciones`: existe, 2 registros.
- `erp_catalogo_sku_presentaciones`: existe, 9 registros.
- `erp_catalogo_sku_paquetes`: existe, 2 registros.
- `erp_catalogo_sku_paquete_componentes`: existe, 4 registros.
- `erp_catalogo_sku_paquete_grupos`: existe, 1 registro.
- `erp_catalogo_sku_paquete_grupo_opciones`: existe, 2 registros.

Conclusion:

Los datos actuales alcanzan para una primera galeria comercial read-only sin crear tablas nuevas. La persistencia de catalogos armados debe decidirse en una fase posterior.

### Contratos existentes reutilizables

`EcommercePublico.php` ya expone endpoints internos protegidos por `catalogo.ver`:

- `publicaciones_auditar_erp()`
- `publicaciones_readiness_erp()`
- `publicaciones_preparar_erp()`

`EcommerceCatalogoPublico.php` ya contiene consultas utiles:

- `auditarPublicabilidad()`: lista SKUs candidatos y bloqueos.
- `prepararPublicacion()`: arma propuesta read-only por SKU.
- `catalogoPublico()`: lista solo publicaciones con estatus `publicado`.
- `sqlPublicacionesBase()`: contrato publico con imagen, precio general, categoria, marca y disponibilidad simple.

Estos contratos sirven como referencia, pero no deben copiarse tal cual para catalogos comerciales.

### Diferencia clave contra ecommerce publico

El ecommerce publico actual usa criterios estrictos:

- producto activo;
- SKU activo;
- precio general activo;
- imagen activa;
- categoria principal;
- bloqueo de venta fraccionaria en fase 1;
- publicacion ecommerce existente para salida publica.

Para catalogos comerciales, esos criterios deben transformarse en alertas, no siempre en bloqueos.

Ejemplos:

- Un catalogo interno puede armarse sin precio si el modo elegido es `sin precio`.
- Un producto fraccionario o con presentaciones puede mostrarse si la tarjeta explica la presentacion.
- Un producto sin publicacion ecommerce puede aparecer como candidato interno, pero no como enlace publico final.
- Falta de imagen debe ser alerta fuerte porque afecta directamente el material visual.

### Campos disponibles para item comercial

Ya se pueden obtener desde estructuras existentes:

- producto/SKU: `erp_catalogo_productos`, `erp_catalogo_skus`.
- imagen portada: `erp_catalogo_imagenes` con prioridad por `tipo_imagen`, `orden` e `id_imagen_erp`.
- marca: `erp_catalogo_marcas`.
- categoria/ruta principal: `erp_catalogo_producto_categorias` + `erp_catalogo_categorias`.
- precio temporal: `erp_catalogo_sku_precios` con `lista_precio='general'`, `moneda='MXN'`, `estatus='activo'`.
- publicacion ecommerce: `erp_ecommerce_publicaciones`.
- presentaciones: `erp_catalogo_sku_presentaciones`.
- paquetes configurables: `erp_catalogo_sku_paquetes`, componentes, grupos y opciones.
- disponibilidad simple: puede derivarse de inventario solo como estado, no cantidad exacta.

### Riesgos detectados

- Precio: `erp_catalogo_sku_precios.lista_precio='general'` puede servir solo como fallback. El catalogo comercial no debe convertirse en modulo de precios.
- Publicaciones: solo hay 2 registros en `erp_ecommerce_publicaciones`; si el MVP depende solo de publicaciones, quedara demasiado limitado.
- Presentaciones y paquetes: ya existen, pero requieren una representacion visual clara para no confundir SKU base, presentacion preparada y paquete vendible.
- Disponibilidad: no debe mostrar existencia exacta en materiales comerciales.
- Permisos: todavia no deben inventarse permisos nuevos sin una fase de seguridad; para MVP read-only se puede usar temporalmente `catalogo.ver` o documentar `catalogos_comerciales.ver` como pendiente.
- Codificacion: se observaron textos con mojibake en documentos/vistas previas; si se reutilizan etiquetas visibles, revisar UTF-8 antes de cerrar UI.

### Decision para MVP

Arrancar con una consulta interna read-only de candidatos a catalogo comercial, distinta de la auditoria ecommerce publica.

Debe devolver:

- `id_producto_erp`, `id_sku`, `sku`, `nombre`.
- `marca`, `categoria`.
- `imagen_portada`.
- `presentacion_comercial`.
- `precio` solo si existe y si el modo lo permite.
- `publicacion` si existe.
- `tipo_item`: `sku`, `presentacion`, `paquete` cuando pueda inferirse.
- alertas: `sin_imagen`, `sin_precio`, `sin_categoria`, `sin_publicacion`, `sku_inactivo`, `producto_inactivo`, `requiere_revision_presentacion`, `paquete_configurable`.

No debe:

- escribir BD;
- publicar productos;
- generar PDF;
- exponer costos;
- usar tablas legacy `ecom_*` como fuente nueva;
- exigir que todo este listo para ecommerce publico.

### Siguiente tarea recomendada

Implementar un endpoint read-only de candidatos para catalogos comerciales y una vista interna minima de prueba.

La primera version puede vivir sin DDL y sin persistencia:

- backend: consulta agregada desde Catalogo ERP;
- frontend: filtros, seleccion temporal y vista previa;
- salida: impresion/captura manual desde navegador.

## Implementacion Tarea 2 - 2026-07-23

Se implemento el endpoint read-only de candidatos para catalogos comerciales.

Archivos:

- `app/controladores/CatalogoErp.php`
- `app/modelos/CatalogoErpDatos.php`

Ruta interna:

- `GET /catalogoerp/catalogos_comerciales_candidatos`

Permiso:

- `catalogo.ver` temporalmente para MVP.

Contrato:

- No escribe BD.
- No expone costos.
- No muestra existencia exacta.
- No publica productos.
- No usa `Producto::catalogo_pdf()` ni tablas legacy `ecom_*` como fuente nueva.

Filtros iniciales:

- `q`: busqueda por producto, SKU, codigo, marca o categoria.
- `limite`: maximo de items, de 1 a 200.
- `solo_alertas=1`: devuelve candidatos con faltantes o condiciones a revisar.
- `solo_con_imagen=1`: filtra candidatos con imagen portada.
- `modo_precio`: `indistinto`, `con_precio`, `sin_precio`.

Datos devueltos por item:

- producto/SKU;
- tipo de item: `sku`, `presentacion` o `paquete`;
- marca/categoria;
- imagen portada;
- presentacion comercial;
- precio general si existe;
- disponibilidad simple;
- publicacion ecommerce relacionada si existe;
- datos basicos de paquete/presentacion cuando aplique;
- alertas accionables.

Alertas iniciales:

- `sin_imagen`;
- `sin_precio`;
- `sin_categoria`;
- `sin_publicacion`;
- `producto_{estatus}`;
- `sku_{estatus}`;
- `venta_fraccionaria`;
- `presentacion_preparada`;
- `paquete_configurable`.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\CatalogoErp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\modelos\CatalogoErpDatos.php`: sin errores.
- Prueba directa del modelo con `limite=5`, `solo_alertas=1`, `modo_precio=sin_precio`: devuelve 5 items y resumen.

Siguiente paso recomendado:

- Crear la vista interna minima de previsualizacion de catalogo comercial usando este endpoint.

## Implementacion Tarea 3 - MVP visual interno 2026-07-23

Se agrego una primera vista interna sin persistencia para validar la experiencia de catalogos comerciales.

Archivos:

- `app/controladores/Catalogoerp.php`
- `app/vistas/paginas/apps/erp/catalogo/catalogos_comerciales.php`
- `public/assets/js/custom/apps/erp/catalogo/catalogos_comerciales.js`

Ruta interna:

- `GET /catalogoerp/catalogos_comerciales`

Permiso:

- `catalogo.ver` temporalmente para MVP.

Funcionalidad:

- Filtros por busqueda, precio, imagen, alertas y limite.
- Tabla de candidatos desde `/catalogoerp/catalogos_comerciales_candidatos`.
- Seleccion temporal en navegador.
- Vista previa de tarjetas comerciales.
- Impresion desde navegador como salida inicial.

Guardrails:

- No guarda catalogos.
- No genera PDF ni imagenes.
- No publica productos.
- No expone costos.
- No muestra existencia exacta.
- No se agrego menu definitivo; la ubicacion final sigue recomendada como `Comercial > Catalogos comerciales`.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Catalogoerp.php`: sin errores.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`: sin errores.
- `C:\xampp\php\php.exe -l app\modelos\CatalogoErpDatos.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`: sin errores.

Siguiente paso recomendado:

- Probar en navegador la ruta `/catalogoerp/catalogos_comerciales`.
- Revisar si las tarjetas muestran imagen, nombre, presentacion y precio de forma util.
- Despues decidir si se agrega menu temporal o si se mueve formalmente a `Comercial`.

## Ajuste MVP visual - Sidebar y controles de tarjeta 2026-07-24

Se agrego acceso en el sidebar:

- Seccion: `ERP > Comercial`.
- Item: `Catalogos comerciales`.
- Ruta: `/catalogoerp/catalogos_comerciales`.
- Permiso temporal MVP: `catalogo.ver`.

Decision:

- Aunque la ruta tecnica vive por ahora bajo `CatalogoErp`, la ubicacion operativa correcta es Comercial porque el objetivo es armar material de venta, no editar datos maestros.

Mejoras UX:

- La vista previa permite mostrar/ocultar:
  - precio;
  - SKU;
  - disponibilidad simple.
- La seleccion sigue siendo temporal en navegador.
- La impresion del navegador sigue siendo la unica salida inicial.

Siguiente paso recomendado:

- Probar la ruta desde el sidebar.
- Si el flujo se valida, definir si el siguiente paso sera persistencia de catalogos o mejora visual de plantillas.

## Ajuste MVP visual - Seleccion masiva 2026-07-24

Se agregaron acciones masivas sobre los candidatos cargados:

- `Seleccionar visibles`: agrega a la seleccion temporal todos los candidatos visibles con el filtro actual.
- `Quitar visibles`: remueve de la seleccion temporal los candidatos visibles con el filtro actual.
- `Quitar todo`: limpia toda la seleccion temporal.

Regla operativa:

- Estas acciones no eliminan productos, SKUs, imagenes ni registros.
- Solo modifican la seleccion temporal en el navegador.
- La persistencia de catalogos sigue pendiente de decision y autorizacion si requiere DDL.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`: sin errores.

## Ajuste MVP visual - Orden y recuperacion local 2026-07-24

Se agrego recuperacion local del armado en curso:

- La seleccion temporal se guarda en `localStorage` del navegador.
- Si el operador recarga la pantalla, la seleccion vuelve a aparecer.
- No se guarda en BD y no se comparte entre usuarios, equipos o navegadores.

Se agrego orden manual:

- Cada item seleccionado puede subir o bajar dentro de la seleccion.
- La vista previa respeta ese orden.
- El orden tambien queda en `localStorage`.

Regla operativa:

- Esto no reemplaza el futuro CRUD de catalogos comerciales.
- Sirve solo para validar el flujo antes de decidir DDL/persistencia formal.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`: sin errores.

## Ajuste MVP visual - Plantillas de previsualizacion 2026-07-24

Se agregaron formatos de vista previa:

- `Cuadrada redes`: tarjetas cuadradas para uso general en redes.
- `Vertical redes`: tarjetas mas altas para publicaciones visuales verticales.
- `Compacta`: formato tipo lista para revisar muchos productos rapido.

Controles relacionados:

- Mostrar/ocultar precio.
- Mostrar/ocultar SKU.
- Mostrar/ocultar disponibilidad simple.
- Cambiar plantilla sin recargar ni perder seleccion.

Reglas:

- No genera imagenes ni PDF automaticamente.
- No guarda la plantilla en BD.
- La plantilla solo afecta la previsualizacion del MVP en navegador.
- Se reemplazaron separadores visuales especiales por guion ASCII para evitar problemas recurrentes de codificacion.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`: sin errores.

## Ajuste MVP visual - Encabezado comercial temporal 2026-07-24

Se agrego bloque `Datos del material`:

- Titulo.
- Subtitulo.
- Contacto / CTA.

Comportamiento:

- Los campos se guardan en `localStorage` del navegador.
- El encabezado aparece arriba de la vista previa.
- Si se imprime desde navegador, se ocultan controles operativos y se imprime solo el area de material.

Reglas:

- No se guarda catalogo en BD.
- No se publica enlace.
- No genera imagenes ni PDF automaticamente.
- El encabezado es temporal para validar capturas/impresion del MVP.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`: sin errores.

## Ajuste MVP visual - Modo captura 2026-07-24

Se agrego boton `Modo captura` en la vista previa.

Comportamiento:

- Oculta sidebar, toolbar, filtros, metricas, candidatos y seleccion temporal.
- Deja visible solo el area del material comercial.
- Mantiene un boton flotante para salir del modo captura.
- No modifica seleccion, orden, plantilla ni datos del material.

Uso operativo:

- Pensado para tomar capturas limpias desde navegador.
- Complementa la impresion del navegador, pero no genera archivos automaticamente.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`: sin errores.

## Ajuste MVP operativo - Borrador local y copiado 2026-07-24

Se agregaron acciones de trabajo sobre el borrador temporal:

- `Copiar listado`: genera texto desde los productos seleccionados para pegarlo en WhatsApp, Facebook o notas.
- `Reiniciar borrador`: limpia seleccion y encabezado temporal guardados en el navegador.

Reglas:

- No escribe base de datos.
- No genera archivos.
- No publica enlaces.
- No consulta ni expone costos.
- Respeta opciones visibles de precio, SKU y disponibilidad al construir el texto.

Uso operativo:

- Sirve para validar rapidamente surtidos o propuestas comerciales antes de invertir en exportacion formal.
- El borrador sigue siendo local del navegador; no es un catalogo ERP persistente.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`.

## Ajuste MVP visual - Campos visibles por material 2026-07-24

Se agregaron controles para mostrar u ocultar:

- precio;
- marca;
- categoria;
- presentacion;
- SKU;
- disponibilidad simple.

Reglas:

- Los controles solo afectan la vista previa y el texto de `Copiar listado`.
- No modifican datos maestros del producto.
- No cambian precios, categorias, marcas ni disponibilidad real.
- Permiten armar materiales mas limpios segun el canal: redes, WhatsApp, revision interna o impresion.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`.

## Ajuste MVP operativo - Borradores locales nombrados 2026-07-24

Se agrego administracion de borradores locales:

- nombre del borrador;
- guardar local;
- cargar;
- eliminar.

Reglas:

- El borrador se guarda en `localStorage` del navegador.
- No es un catalogo ERP formal.
- No se comparte entre usuarios.
- No genera archivos.
- No escribe base de datos.
- Guarda seleccion temporal y datos del material.

Uso operativo:

- Permite preparar varias propuestas comerciales antes de decidir si hace falta persistencia formal.
- Sirve para validar surtidos por tema, categoria, temporada o promocion.
- Si el usuario cambia de navegador/equipo, esos borradores no viajan.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`: sin errores.

## Ajuste MVP visual - Portada y paginacion operativa 2026-07-24

Se agrego una portada temporal del material:

- mostrar/ocultar portada;
- etiqueta;
- descripcion;
- nota.

Comportamiento:

- La portada aparece antes de las tarjetas de producto en la vista previa.
- Usa el titulo del material como titulo principal de portada.
- La descripcion y nota ayudan a explicar al cliente de que trata el conjunto de productos.
- La portada se guarda dentro de los borradores locales.

Se agrego paginacion operativa:

- candidatos: 12 por pagina;
- seleccion temporal: 8 por pagina.

Reglas:

- La paginacion solo afecta la operacion de la pantalla.
- La vista previa sigue mostrando todo el material armado.
- `Seleccionar visibles` y `Quitar visibles` aplican sobre la pagina visible de candidatos.
- No escribe BD ni genera catalogos reales.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`: sin errores.

## Decision pendiente - Persistencia formal de catalogos comerciales

El MVP ya permite guardar borradores locales, pero no catalogos ERP formales.

Para guardar catalogos compartidos entre usuarios/equipos se requiere una fase nueva:

- disenar tablas de catalogos, items, plantilla, portada y estatus;
- permisos propios `catalogos_comerciales.*`;
- auditoria de crear/editar/archivar/publicar;
- endpoint de guardado real;
- criterio de publicacion o exportacion.

Esta fase requiere propuesta DDL y autorizacion explicita antes de aplicar cambios de esquema.

## Ajuste MVP operativo - Exportar/importar borradores JSON 2026-07-24

Se agrego portabilidad de borradores locales:

- exportar borrador seleccionado a archivo `.json`;
- importar borrador desde archivo `.json`;
- validar formato basico antes de guardarlo localmente.

Reglas:

- No escribe base de datos.
- No crea catalogos ERP reales.
- No publica enlaces.
- No genera imagenes ni PDF.
- Sirve como puente para mover borradores entre navegadores/equipos mientras no exista persistencia formal.

Formato exportado:

- `formato`: `erp_catalogo_comercial_borrador_local`;
- `version`: `1`;
- `exportado_en`;
- `borrador` con nombre, material, portada y seleccion.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\catalogos_comerciales.php`: sin errores.
- `node --check public\assets\js\custom\apps\erp\catalogo\catalogos_comerciales.js`: sin errores.

## Propuesta formal - Persistencia en BD 2026-07-24

Se creo documento de propuesta:

- `docs/erp_catalogo_catalogos_comerciales_persistencia_propuesta.md`

Incluye:

- estados formales;
- permisos propios;
- tablas propuestas;
- DDL propuesto;
- endpoints propuestos;
- flujo recomendado;
- texto de autorizacion sugerido.

Regla:

- No se aplico DDL.
- No se modifico esquema.
- No se cambio el MVP a guardado real.
- La propuesta queda lista para autorizacion futura.

## Preparacion tecnica - Contrato de esquema 2026-07-24

Se preparo `app/modelos/CatalogoErpEsquema.php` para reconocer las tablas propuestas:

- `erp_catalogo_comercial_catalogos`;
- `erp_catalogo_comercial_items`;
- `erp_catalogo_comercial_eventos`.

Incluye:

- auditoria de columnas e indices;
- plan DDL en `planActualizarCatalogoErp`;
- compatibilidad de tipos `BIGINT` con `erp_catalogo_skus.id_sku`.

Regla:

- No se ejecuto DDL.
- No se crearon tablas.
- El cambio solo deja listo el contrato para dry-run/autorizacion.

Validacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\CatalogoErpEsquema.php`: sin errores.
- `CatalogoErpEsquema::planActualizarCatalogoErp(false)`: OK, plan generado en dry-run sin ejecutar DDL.

### Tarea 2 - Endpoint read-only de candidatos

Objetivo:

- Crear una consulta interna de candidatos para catalogos comerciales.

Debe devolver:

- producto/SKU;
- imagen portada;
- marca/categoria;
- presentacion;
- precio resoluble o bandera `sin_precio`;
- publicacion relacionada si existe;
- alertas: sin imagen, sin precio, producto inactivo, SKU inactivo.

Permiso:

- `catalogos_comerciales.ver` futuro o temporalmente `catalogo.ver`/`ventas.listas.ver` segun decision.

Sin escritura.

### Tarea 3 - Vista interna MVP

Objetivo:

- Pantalla de prueba para filtrar candidatos y previsualizar tarjetas.

Sin DDL:

- La primera version puede trabajar en memoria/session/local UI sin guardar catalogos.
- Sirve para validar diseño visual, datos, imagenes y precio.

Cierre:

- Poder seleccionar productos y ver una galeria profesional.

### Tarea 4 - Decision de persistencia

Objetivo:

- Decidir si ya se necesita guardar catalogos reales.

Resultado 2026-07-24:

- Persistencia formal aprobada para fase base.
- DDL aplicado con token `CATALOGO_COMERCIAL_PERSISTENCIA_DDL`.
- Respaldo externo: `C:\xampp\panel_db_backups\artianilocal_panel_20260724_antes_catalogos_comerciales.sql`.
- Tablas creadas/verificadas:
  - `erp_catalogo_comercial_catalogos`;
  - `erp_catalogo_comercial_items`;
  - `erp_catalogo_comercial_eventos`.

Fuera de alcance:

- Sin publicar enlaces.
- Sin exportacion automatica.
- Sin tocar Ventas.

Cierre:

- DDL aprobado, aplicado y verificado.

### Tarea 5 - CRUD de catalogos comerciales

Objetivo:

- Guardar catalogos, items, orden y plantilla.

Incluye:

- crear borrador;
- editar items;
- duplicar catalogo;
- archivar;
- previsualizar.

### Tarea 6 - Exportacion

Objetivo:

- Convertir la vista en material reutilizable.

Orden recomendado:

1. Vista web compartible.
2. Impresion/captura del navegador.
3. Exportacion PNG/JPG por tarjeta.
4. PDF por lote.

## Riesgos principales

- Volver a depender de `ecom_*` legacy como fuente.
- Mostrar precios incorrectos si no se amarra a lista/canal.
- Mostrar productos activos internamente pero no aptos para publicacion.
- Generar archivos pesados desde PHP y repetir problemas de memoria del PDF legacy.
- Saturar Catalogo ERP con decisiones comerciales que corresponden a Comercial/Ecommerce.

## Proxima accion recomendada

Comenzar con Tarea 5: CRUD de catalogos comerciales.

Criterio para avanzar a codigo:

- Crear endpoints para listar, consultar y guardar borradores en BD.
- Mantener los borradores locales como apoyo, no como fuente oficial.
- No publicar enlaces ni exportar automaticamente hasta una fase autorizada posterior.
