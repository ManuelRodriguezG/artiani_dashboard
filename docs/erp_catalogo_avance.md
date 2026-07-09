# ERP Catalogo - Avance, cierre y evidencia

Documentacion viva: Codex GPT-5  
Ultima consolidacion: 2026-06-11  
Estado: Base tecnica lista para avanzar a Proveedores/listas; saneamiento operativo continua por incidencias.

## Lectura obligatoria antes de tocar Catalogo

- `AGENTS.md`.
- `docs/erp_plan_maestro_fundamentos.md`.
- `docs/erp_ux_operativa.md`.
- `docs/ia_uso_modelos.md`.
- `docs/erp_catalogo_auditoria_responsabilidades.md`.
- `docs/erp_catalogo_guia_operativa.md`.
- `docs/erp_catalogo_handoff_proveedores_listas.md`.
- Este archivo.

Si una regla de negocio no esta escrita aqui o en codigo, preguntar al dueno antes de implementarla.

## Proposito de este archivo

Este es el unico documento principal que queda para Catalogo. Los documentos auxiliares de trabajo se consolidaron aqui para evitar confusion y lecturas repetidas.

Sirve para:

- Recordar que se hizo.
- Ubicar pendientes reales.
- Saber cuando una incidencia encontrada desde Proveedores, Compras, XML, Almacen o Inventario debe regresar a Catalogo.
- Evitar rehacer auditorias ya realizadas.

## Decision de cierre

Catalogo queda listo como base tecnica para avanzar a Proveedores/listas.

Catalogo no queda declarado como catalogo 100% saneado para Compras masivo porque siguen existiendo datos maestros incompletos, sobre todo fiscales, relaciones proveedor y calidad operativa.

La siguiente prioridad del ERP debe ser Proveedores/listas para resolver cobertura de proveedor, SKU proveedor, unidad, factor, costo y evidencia de listas.

## Semaforo

### Verde

- Esquema base de Catalogo auditado sin faltantes estructurales criticos.
- Producto maestro y SKU ERP existen como fuente tecnica.
- `id_sku_erp` queda como contrato central para Compras, XML, Almacen e Inventario.
- Busquedas de Solicitudes y Ordenes ya consideran SKU ERP, nombre, SKU proveedor y codigos activos, respetando proveedor activo.
- Incidencias persistentes de calidad existen y pueden recibir pendientes de Catalogo, Compras y XML.
- UI principal usa flags de permisos y backend conserva `requerirPermiso()` como autoridad final.
- Configuracion separa visualmente calidad, proveedor, clasificacion, costos, reglas y auxiliares.
- Imagenes heredadas de ecommerce fueron recuperadas cuando existia evidencia.

### Amarillo

- Migracion ecommerce quedo sin pendientes activos; los remanentes historicos se descartaron por decision operativa para trabajar solo sobre Catalogo ERP existente.
- XML sin coincidencia genera incidencias/sugerencias, pero no vincula ni crea maestros automaticamente.
- Producto nuevo desde Compras escala a incidencia, pero conversion a SKU/relacion proveedor requiere flujo autorizado.
- Roles de Catalogo deben probarse con perfiles reales.
- Almacen/Inventario requiere prueba real controlada cuando existan ordenes y recepciones listas.

### Rojo

- Fiscales por SKU siguen incompletos para muchos SKUs activos/comprables.
- Cobertura SKU-proveedor sigue incompleta.
- Existen miles de incidencias de calidad abiertas.
- No debe ejecutarse alta masiva desde listas proveedor, XML o producto nuevo sin politica de matching, evidencia y autorizacion.

## Evidencia tecnica consolidada

### Esquema

- `CatalogoErpEsquema` audita tablas, columnas, indices y contratos de `id_sku_erp`.
- Auditoria critica reporto:
  - `tablas_faltantes=0`.
  - `columnas_faltantes=0`.
  - `indices_faltantes=0`.
  - `indices_con_columnas_distintas=0`.
- Las tablas de Catalogo existen y las relaciones operativas hacia Compras, Almacen e Inventario ya tienen columnas/indices de SKU ERP.

### Migracion ecommerce

- Productos ecommerce auditados: 1803.
- Vinculos ERP detectados: 1741.
- Limpieza ejecutada el 2026-06-23:
  - Respaldo externo previo: `C:\xampp\panel_db_backups\artianilocal_migracion_ecommerce_incidencias_20260623.sql`.
  - Pendientes antes: 142, todos `sku_invalido`.
  - Accion: los 142 pendientes se cerraron como `descartada` con marca `limpieza_20260623`.
  - Pendientes despues: 0.
  - Incidencias finales: 186 `descartada`, 227 `resuelta`.
  - Vinculos ecommerce conservados: 1741.
  - Productos ERP conservados: 1535.
- Decision operativa: no continuar resolviendo remanentes de migracion ecommerce; revisar y sanear directamente los productos ya existentes en Catalogo ERP.
- No se borraron productos ERP, SKUs, vinculos ecommerce ni imagenes por esta limpieza.

### Productos y SKUs

- Producto maestro agrupa SKUs.
- SKU ERP es la unidad comprable/inventariable.
- `id_sku_erp` es la identidad operativa confiable; SKU, codigo y nombre ayudan a buscar, no sustituyen identidad.
- Se agrego advertencia de impacto al editar SKU/codigo con uso externo u operativo.
- Cambios de identidad visible requieren motivo y auditoria.
- `consultarProducto()` devuelve conteos de uso por SKU:
  - ecommerce.
  - proveedores activos.
  - solicitudes.
  - ordenes.
  - recepciones.
  - movimientos.
  - existencias.
- Fiscales no se borran si el bloque fiscal del formulario viene vacio.

### Codigos

- Busqueda operativa usa codigos activos de `erp_catalogo_sku_codigos`.
- `codigo_barras` es la convencion nueva.
- `barras` se conserva como tipo heredado ecommerce.
- Al editar codigo principal, codigos principales previos del mismo SKU pasan a historico.
- Hay codigos semanticamente duplicados que deben revisarse antes de confiar en escaneo/conciliacion.

### Imagenes

- Imagen de producto e imagen de SKU tienen alcance distinto.
- `fuente='ecommerce'` conserva evidencia historica.
- `fuente='erp'` corresponde a carga/validacion desde Catalogo.
- La UI de Productos muestra vista previa de URL/ruta y conserva fuente/id externo al editar.
- Desactivar imagen cambia estatus a `inactivo`; no borra archivo fisico.
- Recuperacion ecommerce ejecutada con autorizacion del dueno:
  - 217 imagenes heredadas insertadas.
  - `erp_catalogo_imagenes` quedo en 1752 registros.
  - `fuente='ecommerce'` quedo en 1752 registros.
  - candidatas pendientes quedaron en 0.
  - no se borraron, movieron ni reemplazaron archivos fisicos.

### Variantes y atributos

- Producto con multiples SKUs debe tener atributos de variante utiles.
- No se deben inventar atributos desde nombres ambiguos.
- Un SKU proveedor puede corresponder a varias variantes internas ERP si el proveedor no distingue color/talla/presentacion en su lista.
- El SKU interno ERP debe existir por cada variante que el negocio quiera vender, recibir, valorar o controlar por separado.
- Recepcion debe poder distribuir una compra con SKU proveedor general hacia SKUs internos variantes cuando la variante real se identifica fisicamente al recibir.
- Precios debe poder definir precio por SKU variante/lista/canal; Catalogo solo mantiene la identidad y atributos de la variante.
- Se sincronizaron incidencias iniciales:
  - 55 productos con variantes sin atributos.
  - 7 SKUs con valores de variante incompletos.
  - 34 firmas de variante duplicadas.
- Compras debe seguir usando `id_sku_erp`; atributos ayudan a seleccionar correctamente.

### Fiscales, costo y proveedor

- Para Compras el bloqueo principal es SKU comprable con fiscal incompleto.
- SKUs activos sin proveedor activo no deben hacerse comprables desde Compras sin relacion proveedor.
- Costo depende de proveedor/lista/costos; no cargar costo aislado sin evidencia de proveedor.
- Catalogo Productos no debe exigir al capturista conocer costos de compra.
- El costo de referencia no es criterio de calidad del producto en Catalogo; debe tratarse como preparacion de Proveedores/Compras/Costos.
- Catalogo puede mostrar alertas de preparacion operativa, pero no debe convertir "sin costo" en bloqueo de captura de producto ni exponer montos a perfiles sin permiso de costos.
- Datos fiscales capturados desde Compras/XML son evidencia/propuesta, no actualizacion automatica del maestro.
- Catalogo debe permitir captura fiscal parcial y conservar lo capturado; la validacion estricta corresponde al momento de usar el SKU en Compras/XML/Ventas/facturacion.
- `incluye_impuestos` debe mostrarse junto al bloque fiscal/impuestos para no confundirlo con precio comercial.
- El precio final debe manejarse por SKU/lista/canal en Precios/Ventas/Comercial; Catalogo solo puede mostrar precio vigente o capturar uno provisional mientras no exista modulo formal de precios.

### Estados operativos del SKU

Decision documentada el 2026-06-24:

- `activo` significa que el registro puede usarse como maestro vigente; no significa automaticamente "listo para vender".
- Catalogo debe separar estatus de vida del maestro (`borrador`, `en_revision`, `activo`, `inactivo`, `descontinuado`, `fusionado`) de estados de preparacion operativa.
- La preparacion operativa debe evaluarse por objetivo: maestro, compra, fiscal, inventario, venta, ecommerce y rentabilidad.
- Nombres recomendados de preparacion: `maestro_validado`, `compra_habilitada`, `fiscal_validado`, `inventario_habilitado`, `venta_habilitada`, `ecommerce_publicable`, `rentabilidad_revisada`, con sus equivalentes pendientes.
- Aplicado en UI/modelo: producto y SKU permiten `borrador`, `en_revision`, `activo`, `inactivo` y `descontinuado`; `fusionado` queda reservado al flujo de fusion y no debe capturarse manualmente.
- Politica aplicada: no eliminar SKUs como primera opcion si pueden tener historial operativo; usar `descontinuado` o `inactivo`, ocultarlos de la vista normal y permitir revisarlos desde filtros de archivados.
- Productos lista por defecto solo vigentes; puede alternarse a archivados o todos.
- Detalle de producto oculta SKUs archivados por defecto y permite verlos con boton dedicado.
- Al editar un SKU archivado se muestra aviso: puede recuperarse cambiando Estado maestro a `activo` o `en_revision`; si esta `fusionado`, requiere flujo de correccion de fusion.
- `comprable` requiere, como minimo:
  - SKU activo.
  - relacion proveedor activa.
  - unidad/factor de compra correctos.
  - datos fiscales suficientes si va a pasar por Compras/XML.
  - costo vigente o validado por Proveedores/Costos, fuera de la responsabilidad normal de Catalogo.
- `vendible` requiere, como minimo:
  - SKU activo.
  - precio de venta activo.
  - codigo interno/barras si se va a escanear.
  - reglas de inventario coherentes si controla existencia.
  - existencia disponible o politica explicita de venta sin existencia.
  - presentacion configurada cuando sea granel, fraccionado o preparado.
  - datos fiscales suficientes para facturar/vender cuando aplique.
- `listo para ecommerce` agrega imagen, descripcion comercial, categoria/canal y reglas de publicacion.

Auditoria puntual de Catalogo Productos al 2026-06-24:

- Productos ERP: 1535.
- SKUs ERP: 1749.
- Productos sin SKU: 0.
- Productos sin marca: 852.
- Productos sin categoria: 154.
- Productos sin imagen: 99.
- SKUs sin precio activo: 0.
- SKUs con fiscal incompleto: 1736.
- SKUs activos con fiscal incompleto: 1716.
- SKUs comprables con fiscal incompleto: 668.
- SKUs activos sin proveedor activo: 1048.
- SKUs activos sin codigo principal: 266.
- SKUs sin reglas de inventario: 0.
- SKUs con reorden en cero: 1744.
- Variantes sin atributos: 57.

Accion tomada: se retiro "sin costo de referencia" de la calidad principal de Catalogo Productos y de los indicadores visuales de SKU. El costo queda como tema de Proveedores/Compras/Costos, no como tarea obligatoria del responsable de Catalogo.

### Auditoria de responsabilidades

El 2026-06-24 se creo `docs/erp_catalogo_auditoria_responsabilidades.md` para planear Catalogo como modulo de datos maestros y no como una pantalla que obligue al capturista a decidir costos, precios, inventario fisico, trazabilidad o rentabilidad.

Decision de planeacion:

- Catalogo es dueno de identidad, estructura y completitud base del producto/SKU.
- Proveedores/Compras son duenos de proveedor-SKU, unidad/factor de compra, costo vigente y evidencia.
- Precios/Ventas/Comercial deben ser duenos del precio final y listas de venta.
- Inventario/Almacen son duenos de existencias, preparacion, etiquetas reales y control fisico.
- Costos/Rentabilidad son duenos de costo real, costo referencia, margen y rentabilidad.
- Fiscal/Contabilidad debe validar datos fiscales cuando no exista evidencia clara.

Siguiente criterio antes de tocar mas UI:

- Separar indicadores por objetivo: maestro completo, comprable, inventariable, vendible, publicable y rentable.
- Mantener Catalogo en una sola pantalla por ahora; no dividir en modo basico/avanzado ni crear permisos nuevos por seccion en esta etapa.
- El precio queda como `precio provisional`; el tema formal debe pasar despues a Listas de precios.

### Fusion y reversa controlada

- Fusionar productos/SKUs es accion de alto impacto.
- El flujo actual mueve SKUs, imagenes, vinculos de canal, propuestas de nombre, categorias y algunas referencias de inventario por producto, y marca el origen como `fusionado`.
- Existe registro resumen de fusion, pero no snapshot detallado suficiente para reversa automatica segura.
- No debe agregarse un boton simple de "deshacer" sin snapshot e impacto.
- Plan recomendado: crear flujo de "revisar reversa" o "correccion de fusion" con snapshot, detalle de entidades movidas, validacion de movimientos posteriores y auditoria.
- Si despues de fusionar hubo compras, recepciones, inventario, ventas, ecommerce o cambios de SKU, la reversa debe ser parcial/asistida, no automatica.
- Mejora aplicada sin esquema: fusion exige motivo de al menos 10 caracteres, muestra advertencia de alto impacto y pide confirmacion antes de ejecutar.
- Sigue pendiente: snapshot/detalle de entidades movidas para soportar revision de reversa segura.

### Incidencias de calidad

- Existe `erp_catalogo_incidencias_calidad`.
- Existen endpoints/UI para listar y cambiar estatus de incidencias.
- Se ejecuto carga inicial de incidencias relevantes de calidad, inventario y XML.
- Ejemplos ya integrados:
  - reglas/inventario sospechosas.
  - variantes incompletas.
  - fiscal incompleto.
  - XML sin coincidencia.
  - producto nuevo desde Compras.
- Regla: si otro modulo detecta huecos de Catalogo, debe generar incidencia trazable en vez de improvisar o duplicar producto.

### UI y permisos

Permisos vigentes:

- `catalogo.ver`: consulta.
- `catalogo.editar`: crear/editar maestros, SKUs, variantes, imagenes, calidad, migracion y organizacion.
- `catalogo.costos`: relacion SKU-proveedor, relaciones historicas y aplicacion/sincronizacion de costos.

Implementado:

- Las vistas principales publican `window.CATALOGO_PERMISOS`.
- Productos, Configuracion, Migracion ecommerce y Organizacion ocultan acciones segun permisos.
- Backend sigue validando con `Controlador::requerirPermiso()`.
- Configuracion separa bloques de Calidad, Proveedor, Clasificacion, Costos, Reglas operativas y Auxiliares.
- Marcas ya tienen flujo operativo: Configuracion administra marcas; Productos permite seleccionar u ofrecer crear marca nueva.

Pendiente de decision:

- Confirmar si `catalogo.costos` seguira cubriendo relacion SKU-proveedor o si Proveedores tendra permiso propio.
- Confirmar si Configuracion debe abrir con `catalogo.ver` y ocultar secciones, o mantenerse como administracion con `catalogo.editar`.

## Pendientes reales que quedan en Catalogo

No son subtareas activas ahora; son incidencias o decisiones para retomar cuando el negocio lo priorice.

1. Resolver fiscales de SKUs comprables.
2. Resolver cobertura de SKU-proveedor desde Proveedores/listas.
3. Resolver `sku_invalido` y duplicados pendientes de migracion ecommerce.
4. Revisar codigos duplicados antes de usarlos para escaneo.
5. Resolver variantes sin atributos o firmas duplicadas de productos prioritarios.
6. Probar permisos con perfiles reales.
7. Ejecutar prueba controlada Almacen/Inventario con orden real cuando el flujo este listo.
8. Definir politica de producto nuevo desde Compras/XML:
   - vincular SKU existente;
   - crear SKU nuevo autorizado;
   - crear relacion proveedor;
   - descartar con motivo.
9. Disenar reversa/correccion de fusiones antes de permitir deshacer fusiones:
   - snapshot obligatorio;
   - detalle de SKUs/imagenes/categorias/vinculos movidos;
   - validacion de uso posterior;
   - permiso de alto impacto;
   - UAT de reversa segura y correccion asistida.

## Regla de retorno desde otros modulos

Si Proveedores, Compras, XML, Almacen o Inventario encuentran un problema de Catalogo:

- No crear producto duplicado.
- No cambiar maestro fiscal/costo/proveedor automaticamente sin evidencia y autorizacion.
- Crear o actualizar incidencia de Catalogo con origen, evidencia, modulo, usuario y accion sugerida.
- Si la regla de negocio no esta clara, preguntar al dueno.

Ejemplos de retorno a Catalogo:

- Proveedor trae SKU sin match exacto.
- Lista de proveedor trae descripcion parecida pero sin SKU ERP confiable.
- XML trae clave SAT, impuesto o unidad distinta al maestro.
- Compras necesita producto nuevo.
- Almacen recibe producto con variante o empaque no distinguible.
- Inventario detecta regla o unidad incompatible.

## Evidencia - SKU temporal desde Proveedores

Fecha: 2026-06-14.

Se agrego el endpoint `POST /catalogoerp/incidencia_proveedor_crear_sku_temporal` para crear producto/SKU temporal desde una incidencia individual de Proveedores tipo `proveedor_sku_sin_match`.

Tambien se agrego una tarjeta ligera `Incidencias de calidad` en la vista principal de Catalogo para listar incidencias abiertas y abrir el modal `Crear SKU temporal` cuando aplique.

Reglas:

- Solo Catalogo (`catalogo.editar`) puede crear el temporal.
- La incidencia debe estar abierta, venir de `proveedores` y no tener SKU ligado.
- Se exige unidad base explicita; no se asume una unidad por defecto.
- Producto y SKU nacen en `borrador`.
- No se crea relacion proveedor-SKU ni costo.
- Proveedores debe ejecutar matching despues de que Catalogo cree/completa el temporal.

Verificacion:

- `php -l app/controladores/CatalogoErp.php`: sin errores.
- `php -l app/modelos/CatalogoErpDatos.php`: sin errores.
- `php -l app/vistas/paginas/apps/erp/catalogo/productos.php`: sin errores.
- `node --check public/assets/js/custom/apps/erp/catalogo/productos.js`: sin errores.

## Forma de trabajar aprendida en Catalogo

Para futuros modulos:

1. Mantener un archivo `*_avance.md` como bitacora principal y evidencia.
2. Crear archivos `*_trabajo.md` solo mientras una tarea esta activa.
3. Al terminar una tarea, consolidar resultados, decisiones, verificaciones y pendientes en el `*_avance.md`.
4. Eliminar documentos auxiliares cerrados para no saturar el proyecto.
5. Separar siempre:
   - hecho;
   - pendiente implementable;
   - pendiente que requiere decision del dueno;
   - pendiente que requiere autorizacion de ejecucion sobre datos/esquema.
6. Si una instruccion dice "pedir permiso", no usarlo como excusa para no avanzar:
   - avanzar en codigo/documentacion segura;
   - preguntar solo decisiones reales de negocio;
   - pedir autorizacion explicita para migraciones, sincronizaciones, escrituras masivas o datos reales.

## Avance 2026-06-24 - responsabilidades y fiscal parcial

Se inicio la implementacion de las primeras tareas del plan de responsabilidades de Catalogo sin aplicar migraciones ni escrituras masivas.

Hecho:

- Creado `docs/erp_estandar_documentacion_codigo.md` como norma transversal para codigo nuevo o modificado por IA.
- Enlazado el estandar desde `AGENTS.md` y `docs/erp_plan_maestro_fundamentos.md`.
- Documentada la regla: cada funcion nueva o modificada por IA debe incluir version/modelo, fecha, proposito, impacto y contrato cuando aplique.
- Ajustada la validacion fiscal de SKU para permitir captura parcial.
- Agrupada la seccion `Fiscal e impuestos` en la edicion de SKU para que SAT, IVA, IEPS y "precio con impuestos" no queden mezclados con inventario/control fisico.
- Ajustada la posicion de `Fiscal e impuestos` para quedar debajo de los datos principales y antes de `Reglas de stock`.
- Renombrado visualmente `Precio` como `Precio provisional` en Catálogo, sin cambiar esquema ni flujo, para recordar que las listas/precios por canal pertenecen al modulo de Precios/Ventas.
- Aclarado `Estado maestro` para producto/SKU: `activo` significa maestro vigente, no venta habilitada.
- Cambiado badge `Listo` a `Sin alertas` para evitar interpretar que el SKU ya esta listo para vender/comprar/publicar.
- Ocultada la columna de costo en relaciones proveedor-SKU para perfiles sin `catalogo.costos`.

Decision:

- Catalogo puede guardar avances fiscales parciales sin bloquear la captura del producto.
- Fiscal incompleto sigue siendo una alerta de calidad y responsabilidad compartida con Fiscal/Compras, no un bloqueo para guardar datos maestros.

Limitacion sin migracion:

- El esquema actual no distingue perfectamente entre porcentaje fiscal desconocido y valor cero cuando se guarda una ficha parcial.
- No se modifica BD hasta tener respaldo externo y autorizacion explicita.

Impacto documentado:

- Compras/Fiscal no deben interpretar la existencia de fila en `erp_catalogo_sku_impuestos` como fiscal validado.
- Ventas/Precios no deben usar "precio con impuestos" como politica comercial final sin revisar lista/canal.
- Catalogo debe seguir mostrando pendientes fiscales mientras falten clave SAT producto, clave SAT unidad, objeto impuesto, IVA, IEPS o criterio de impuestos incluidos.
- El precio capturado en Catálogo queda como valor provisional/general mientras no exista flujo formal de Precios/Listas; no debe tratarse como precio final por canal.
- Los indicadores de Catálogo son alertas de calidad visibles en este modulo; la habilitacion real por compra, venta, inventario, ecommerce o rentabilidad corresponde a sus modulos responsables.
- Los costos de proveedor solo deben verse con permiso explicito; para perfiles de Catalogo normal basta ver cobertura proveedor-SKU, unidad y factor de compra.

Decision posterior del dueno:

- No dividir pantallas ni permisos por seccion de Catalogo en esta etapa.
- Mantener el modulo como esta, con mejoras de claridad y ayudas operativas.
- El punto fuera de lugar a separar despues es precio/listas, en un modulo futuro de Listas de precios.
- Siguiente trabajo dentro de Catalogo: indicadores/auditorias de completitud por objetivo, sin convertirlos en una pantalla separada ni en nuevos permisos.

## Avance 2026-06-24 - indicadores por objetivo

Se ajustaron indicadores visibles de SKU para separar mejor que flujo queda afectado.

Hecho:

- `Fiscal pendiente`: faltan datos fiscales minimos; afecta compras con evidencia fiscal, facturacion y validacion fiscal.
- `Compra sin proveedor` / `Compra granel sin proveedor`: falta cobertura proveedor-SKU o conversion de compra; afecta compras y recepcion.
- `Venta sin codigo`: falta codigo principal; afecta busqueda, escaneo y venta mostrador, pero no bloquea compras por `id_sku`.
- `Venta sin precio prov.`: falta precio provisional en Catalogo; el precio final debe vivir despues en Listas de precios/canal.
- `Inventario unidad decimal`: unidad base permite decimales y requiere confirmar si aplica granel o solo inventario/merma.
- `Inventario revisar`: regla de inventario pendiente o reorden en cero; afecta alertas de reposicion, no existencia actual.
- `Calidad N`: existen incidencias persistentes abiertas.

Decision:

- Los indicadores no son estatus nuevos.
- Los indicadores no habilitan ni bloquean por si mismos otros modulos.
- Sirven para orientar al operador sobre que falta y que modulo debe resolverlo.
- La auditoria general renombra `SKU sin precio activo` a `SKU sin precio provisional` y baja su severidad, porque precio final/listas no pertenece a Catalogo.

### Filtro visual por objetivo en producto

Se agrego un resumen encima de la tabla de SKUs con filtros:

- Todos.
- Fiscal.
- Compra.
- Inventario.
- Venta.
- Calidad.

Reglas:

- Es solo una capa visual dentro de la misma pantalla.
- No crea permisos nuevos.
- No crea estatus nuevos.
- No modifica BD.
- Los conteos indican cuantos SKUs del producto tienen al menos una alerta de ese objetivo.

### Auditoria de Configuracion por objetivo

Se ajusto la auditoria general de Configuracion para agrupar visualmente pendientes por:

- Maestro.
- Fiscal.
- Compra.
- Inventario.
- Venta.
- Ecommerce.
- Calidad.

Reglas:

- No cambia el endpoint `/catalogoerp/auditoria_calidad`.
- No modifica esquema.
- No crea permisos nuevos.
- La tabla ahora muestra columna `Objetivo` para ubicar a que flujo pertenece cada pendiente.
- Las tarjetas de objetivo son clicables y filtran visualmente la tabla de pendientes de auditoria.

### Incidencias persistentes por objetivo/responsable

Se ajusto la tabla de incidencias persistentes para mostrar:

- Objetivo operativo.
- Responsable sugerido.
- Prioridad/estatus.
- Incidencia, entidad, origen y acciones.

Objetivos actuales:

- Fiscal: Fiscal/Compras.
- Compra: Proveedores/Compras.
- Inventario: Almacen/Inventario.
- Venta: Ventas/Precios.
- Ecommerce: Catalogo/Ecommerce.
- Maestro: Catalogo.

Reglas:

- Es clasificacion visual calculada desde `tipo_incidencia`, `origen` y `titulo`.
- No cambia datos ni estatus.
- No cambia endpoint ni esquema.
- Sirve para orientar atencion, no para reasignar automaticamente responsabilidades.
- Los objetivos del resumen son clicables y filtran visualmente la tabla actual.

### Historial de fusiones de productos maestros

Se agrego una vista de solo lectura en Organizacion para consultar las ultimas fusiones registradas en `erp_catalogo_productos_fusiones`.

Reglas:

- No modifica esquema.
- No ejecuta reversas.
- Usa permiso `catalogo.ver`.
- Muestra origen, destino, motivo, SKUs movidos y fecha de fusion.
- La reversa queda marcada como pendiente de snapshot/flujo controlado antes de habilitar cualquier accion automatica.
- Se documento propuesta tecnica de snapshot y detalle en `docs/erp_catalogo_auditoria_responsabilidades.md`; no se debe ejecutar sin respaldo externo y autorizacion.
- La carga del historial queda aislada: si la tabla/endpoint de fusiones falla, no bloquea propuestas de nombres ni el resto de Organizacion.

### Proveedor/costos como puente temporal

Se ajusto Configuracion para que las secciones de proveedor y costos no se interpreten como responsabilidad definitiva de Catalogo.

Reglas:

- Los bloques siguen visibles solo con `catalogo.costos`.
- Los endpoints de lectura de propuestas de costo y relaciones historicas proveedor-SKU quedan restringidos a `catalogo.costos`.
- El frontend no llama endpoints de proveedor/costos si el perfil no tiene `catalogo.costos`.
- La relacion proveedor-SKU desde listas historicas se etiqueta como puente temporal y requiere confirmacion antes de sincronizar.
- El costo mostrado se renombra como costo de lista/costo de referencia provisional.
- La sincronizacion de costos exige confirmacion y aclara que no sustituye costo validado, rentabilidad ni listas formales de costos.
- No cambia esquema ni permisos; solo reduce confusion operativa antes de separar Proveedores/Costos como modulo responsable.

### Guia operativa de Catalogo

Se creo `docs/erp_catalogo_guia_operativa.md` para explicar al capturista o responsable de Catalogo:

- Que debe capturar Catalogo.
- Que no debe inventar.
- Como interpretar estatus de vida del maestro.
- Como atender indicadores por objetivo.
- Cuando derivar pendientes a Proveedores, Precios/Ventas, Almacen/Inventario o Fiscal.
- Cual es el criterio de cierre de captura de un SKU como maestro.

### Handoff a Proveedores/listas

Se creo `docs/erp_catalogo_handoff_proveedores_listas.md` para continuar en Proveedores/listas con reglas claras:

- No crear productos duplicados si Catalogo ya tiene candidato.
- Diferenciar match exacto, match por variante, match ambiguo y sin match.
- Mantener costo/lista bajo responsabilidad de Proveedores/Compras/Costos.
- Exigir unidad de compra y factor configurable por relacion proveedor-SKU.
- Devolver incidencias a Catalogo cuando falte match, variante, unidad/factor o evidencia fiscal.
- Definir criterio de cierre de SKU comprable.

## Auditoria 2026-06-25 - Configuracion y saneamiento de productos migrados

### Productos por proveedor

Resultado de auditoria:

- SKUs totales: 1749.
- SKUs activos: 1729.
- SKUs activos sin proveedor activo: 1047.
- SKUs con multiples proveedores activos: 121.
- SKUs con proveedor preferido: 689.
- SKUs con multiples preferidos: 0.
- SKUs con proveedor activo pero sin preferido: 3.

Decision operativa:

- Un SKU puede tener varios proveedores activos.
- Solo uno debe quedar como `es_preferido=1`.
- El proveedor preferido sirve como proveedor principal sugerido; no elimina proveedores alternos.
- Para compras reales, Proveedores/listas debe validar costo, unidad compra y factor.

Mejora aplicada:

- `guardarSkuProveedor()` ahora desmarca otros proveedores del mismo SKU cuando una relacion se guarda como preferida.
- Si una relacion se guarda inactiva, no puede quedar como preferida.
- La pestaña Proveedores del producto permite editar una relacion existente desde la tabla para no recapturar todo.
- La edicion de proveedor vinculado usa `id_sku_proveedor`, no solo la combinacion SKU/proveedor, para evitar duplicados accidentales al cambiar unidad, factor, proveedor principal o estado.
- La pantalla cambia entre modo `Vincular SKU con proveedor` y `Editar proveedor vinculado`; al cancelar o guardar vuelve a modo alta.
- El listado principal de productos agrega filtro de saneamiento: sin marca, sin categoria, SKU sin proveedor, proveedor sin principal y varios proveedores.
- El modelo `listarProductos()` expone contadores operativos por producto para priorizar saneamiento sin abrir cada ficha manualmente.
- Al abrir un producto desde filtros de proveedor, el modal entra directo a la pestaña Proveedores; desde filtros de marca/categoria entra a Datos maestros.
- El listado muestra accesos rapidos con conteos de saneamiento; cada boton aplica el filtro correspondiente para trabajar por bloque.
- Se agrego accion masiva desde Productos para seleccionados: asignar marca y/o categoria principal usando el contrato auditado de revision de metadatos.
- La asignacion masiva fuerza categoria principal solo cuando la UI de Productos lo solicita; el flujo avanzado de Configuracion conserva su comportamiento de revision.
- En la pestaña Proveedores se agrego accion `Marcar unicos como principales`: solo asigna preferido cuando el SKU tiene exactamente un proveedor activo y ninguno preferido.
- Los SKUs con varios proveedores activos siguen requiriendo decision manual; el sistema no elige proveedor principal por costo ni por historial automaticamente.
- Para SKUs con varios proveedores, la tabla de Proveedores permite usar `Principal` por relacion; reutiliza `guardar_sku_proveedor` y conserva unidad, factor, costo, compra minima y dias de entrega existentes.
- La pestaña Proveedores agrupa las relaciones por SKU, muestra unidad base, numero de proveedores y alerta `Sin principal` cuando aplica.
- El estatus visual de proveedor-SKU diferencia `activo` de `inactivo`; un proveedor inactivo ya no se muestra con badge verde.
- El boton `Principal` solo aparece y opera para relaciones proveedor-SKU activas; una relacion inactiva debe reactivarse/editase antes de poder ser principal.

### Vinculacion asistida de proveedores desde listas

- Se reemplazo en UI la sincronizacion global de coincidencias exactas por aplicacion seleccionada: el usuario marca renglones revisados y solo esos se vinculan.
- Endpoint nuevo: `CatalogoErp::relaciones_proveedor_aplicar_seleccion()`.
- Modelo nuevo: `CatalogoErpDatos::aplicarRelacionesProveedorSeleccionadas()`.
- Regla: solo acepta match exacto `SKU lista proveedor = SKU ERP`, proveedor valido y relacion inexistente.
- Valores iniciales: unidad compra = unidad base del SKU, factor = 1, compra minima = 1, dias entrega = 0, costo ultimo = costo de lista si existe.
- Advertencia operativa: para cajas, granel o empaques, unidad/factor deben revisarse antes de usar la relacion en Compras/Recepcion.
- Si tras aplicar queda un unico proveedor activo del SKU y no hay preferido, se marca como preferido; si hay varios, requiere decision manual.

### Marcas y categorias

Resultado de auditoria:

- Marcas totales/activas: 42.
- Productos vigentes sin marca: 852.
- Categorias totales: 106.
- Categorias maestras activas: 31.
- Categorias que permiten productos: 98.
- Categorias raiz: 83.
- Categorias hijas: 23.
- Productos vigentes sin categoria principal: 154.

Alcance actual:

- Marcas tienen alta, edicion e inactivacion logica desde Configuracion.
- Categorias tienen alta, edicion, categoria padre, tipo (`maestra`/`legado_canal`), origen, permite productos e inactivacion logica.
- No hay borrado fisico, correcto para conservar historial.
- La mayor deuda no es estructural sino de captura/saneamiento: muchos productos siguen sin marca y una parte menor sin categoria principal.

Orden recomendado para acelerar trabajo:

1. Resolver primero SKUs activos sin proveedor activo, priorizando productos que ya se compran.
2. Para SKUs con varios proveedores, editar la relacion y marcar solo el principal como preferido.
3. Resolver los 3 SKUs con proveedor activo sin preferido.
4. Usar Configuracion > Clasificacion pendiente para asignar categorias en lote.
5. Atender marcas faltantes por bloques de proveedor/lista o familia, no producto por producto cuando haya patron claro.

## Siguiente modulo recomendado

Proveedores/listas.

Motivo:

- Compras necesita productos comprables por proveedor.
- Catalogo ya tiene SKU ERP como base.
- Falta convertir proveedores/listas/costos historicos en relaciones confiables `SKU ERP <-> proveedor`.
- Este modulo permite aprovechar datos existentes sin recapturar todo y sin depender del flujo ecommerce anterior.

## Pendiente planificado 2026-06-26 - Modulo de paquetes / kits

Contexto:

- El negocio necesita manejar paquetes, combos o kits compuestos por varios SKUs ERP.
- La duda operativa es si debe vivir en Catalogo. Decision: si, la definicion maestra del paquete debe nacer en Catalogo ERP, pero la existencia, preparacion, armado, desarmado y consumo real pertenecen a Almacen/Inventario y Ventas.

Decision de responsabilidades:

- Catalogo ERP:
  - define el paquete como SKU vendible o producto compuesto;
  - guarda nombre, codigo, imagen, codigos de barra internos y componentes;
  - define cantidades requeridas por componente;
  - indica si el paquete es virtual, armado bajo demanda o prearmado;
  - define si el paquete puede venderse aunque no exista armado fisico.
- Almacen/Inventario:
  - arma paquetes fisicos cuando aplique;
  - descuenta componentes y genera existencia del paquete prearmado;
  - desarma paquetes si se autoriza;
  - controla lote, caducidad, serie y etiquetas reales.
- Ventas/Precios:
  - define precio por canal/lista;
  - valida disponibilidad del paquete segun componentes o existencia armada;
  - consume paquete o componentes segun la modalidad.
- Compras:
  - normalmente compra componentes, no paquetes internos;
  - solo compra paquete si el proveedor lo vende como producto cerrado y entonces debe ser SKU normal/proveedor.

Tipos propuestos:

1. Paquete virtual:
   - No tiene existencia propia.
   - Ventas descuenta componentes al vender.
   - Catalogo guarda la receta comercial.
2. Kit prearmado:
   - Tiene existencia propia como SKU paquete.
   - Almacen arma el kit consumiendo componentes.
   - Ventas descuenta el SKU paquete armado.
3. Combo comercial:
   - Agrupa SKUs para promocion/precio.
   - Puede no requerir SKU paquete si solo es regla de venta/precio.
   - Debe evaluarse con Ventas/Listas de precios.
4. Paquete comprado cerrado:
   - El proveedor lo vende como unidad cerrada.
   - Debe tratarse como SKU normal con proveedor, no como receta interna, salvo que tambien se desarme.

Estructura candidata de Catalogo:

- `erp_catalogo_sku_paquetes`
  - `id_paquete`
  - `id_sku_paquete`
  - `tipo_paquete` (`virtual`, `prearmado`, `combo`, `comprado_cerrado`)
  - `modo_disponibilidad` (`por_componentes`, `por_existencia_armada`, `mixto`)
  - `permite_desarmar`
  - `estatus`
- `erp_catalogo_sku_paquete_componentes`
  - `id_componente`
  - `id_paquete`
  - `id_sku_componente`
  - `cantidad`
  - `id_unidad`
  - `factor_conversion`
  - `obligatorio`
  - `permite_sustituto`
  - `orden`
  - `estatus`

Validaciones minimas:

- Un paquete debe tener al menos un componente activo.
- El SKU paquete no puede ser componente de si mismo.
- Evitar ciclos: A contiene B y B contiene A.
- Cantidad de componente mayor a cero.
- Factor conversion mayor a cero cuando la unidad del componente no sea la unidad base.
- No permitir publicar/vender paquete sin regla de disponibilidad clara.
- No permitir armar paquete fisico desde Catalogo; solo definir receta.

Plan de implementacion recomendado:

1. Auditar si ya existe algo legado de paquetes en el sistema anterior y si conviene migrarlo.
2. Definir formalmente tipos de paquete que se usaran en el negocio.
3. Crear esquema de tablas en Catalogo, solo propuesta primero.
4. Agregar UI en ficha de producto/SKU: pestaña `Paquetes` o seccion dentro de SKU.
5. Permitir seleccionar SKU paquete y componentes.
6. Agregar auditorias de calidad: paquete sin componentes, componente inactivo, ciclo, unidad/factor incompleto.
7. Preparar handoff para Almacen/Inventario: armado/desarmado y movimientos.
8. Preparar handoff para Ventas/Precios: disponibilidad, precio por paquete y consumo.

Criterio de cierre para iniciar codigo:

- Confirmar si el primer caso real sera paquete virtual, kit prearmado o paquete comprado cerrado.
- Confirmar si el paquete tendra SKU propio y codigo escaneable.
- Confirmar si se vendera por ecommerce, mostrador o ambos.
- No aplicar migraciones sin respaldo externo y autorizacion.

Handoff:

- Al volver, iniciar con auditoria de archivos/tablas legacy relacionados con paquetes (`Paquetes`, `Productos`, ecommerce o ventas antiguas).
- No mezclar este trabajo con Compras hasta definir si el paquete se compra cerrado o se arma internamente.

## Plan generado 2026-06-26 - Paquetes y recepcion variable

Nota de continuidad:

- La parte de recepcion variable sigue vigente.
- La parte inicial de paquetes queda como antecedente historico.
- El diseño vigente de paquetes es el bloque posterior `Correccion de alcance paquetes configurables`, donde se descarta migrar legacy y se modelan grupos/opciones configurables.

Documentos creados:

- `docs/erp_catalogo_paquetes_plan.md`
- `docs/erp_catalogo_recepcion_cantidad_variable.md`

Decisiones:

- Paquetes/kits nacen en Catalogo solo como receta maestra y SKU paquete; Almacen/Inventario ejecuta armado/desarmado y Ventas/Precios define precio/disponibilidad comercial.
- Hay legacy de paquetes en `ecom_paquetes`, `ecom_paquetes_productos` y `ecom_paquetes_imagenes`; antes de implementar ERP nuevo se debe auditar si se migra, se consulta o se descarta.
- Cantidad variable en recepcion debe vivir como regla de inventario del SKU, no como unidad nueva.
- No crear unidades como costal, saco, paca o bolsa solo por lenguaje operativo.
- Compras conserva unidad/factor teorico por proveedor; Almacen/Recepcion captura cantidad real cuando el SKU lo exige.

DDL propuesto sin ejecutar:

- `erp_catalogo_sku_paquetes`
- `erp_catalogo_sku_paquete_componentes`
- columnas nuevas en `erp_catalogo_sku_reglas_inventario` para recepcion variable:
  - `requiere_cantidad_variable_recepcion`
  - `requiere_unidades_fisicas_recepcion`
  - `tolerancia_recepcion_porcentaje`
  - `nota_recepcion_variable`

Siguiente paso recomendado:

1. Revisar y aprobar los dos documentos de diseno.
2. Respaldar BD fuera del proyecto antes de cualquier migracion.
3. Si se aprueba primero recepcion variable, implementar solo Catalogo: esquema, modelo, UI y badges; dejar Almacen para su chat/modulo.
4. Si se aprueba primero paquetes, auditar datos reales de `ecom_paquetes*` antes de crear tablas ERP.

### Preparacion tecnica sin ejecutar migracion

Fecha: 2026-06-26

Archivo actualizado:

- `app/modelos/CatalogoErpEsquema.php`

Cambios preparados:

- La auditoria de Catalogo ahora considera como contrato pendiente:
  - `erp_catalogo_sku_paquetes`
  - `erp_catalogo_sku_paquete_componentes`
  - columnas de recepcion variable en `erp_catalogo_sku_reglas_inventario`
- El plan de actualizacion genera DDL en dry-run para:
  - `requiere_cantidad_variable_recepcion`
  - `requiere_unidades_fisicas_recepcion`
  - `tolerancia_recepcion_porcentaje`
  - `nota_recepcion_variable`
  - `idx_catalogo_regla_recepcion_variable`
  - `erp_catalogo_sku_paquetes`
  - `erp_catalogo_sku_paquete_componentes`

Resultado de auditoria local en modo lectura:

- Tablas faltantes: 2.
- Columnas faltantes: 4.
- Indices faltantes: 1.
- Indices con columnas distintas: 0.

Faltantes detectados:

- `erp_catalogo_sku_reglas_inventario.requiere_cantidad_variable_recepcion`
- `erp_catalogo_sku_reglas_inventario.requiere_unidades_fisicas_recepcion`
- `erp_catalogo_sku_reglas_inventario.tolerancia_recepcion_porcentaje`
- `erp_catalogo_sku_reglas_inventario.nota_recepcion_variable`
- `erp_catalogo_sku_reglas_inventario.idx_catalogo_regla_recepcion_variable`
- `erp_catalogo_sku_paquetes`
- `erp_catalogo_sku_paquete_componentes`

Auditoria legacy de paquetes:

- `ecom_paquetes`: existe, 91 registros.
- `ecom_paquetes_productos`: existe, 141 registros.
- `ecom_paquetes_imagenes`: existe, 93 registros.
- Paquetes legacy activos: 90.
- Paquetes legacy inactivos: 1.
- Paquetes sin componentes: 0.
- Paquetes sin imagen: 1.
- Componentes por paquete: minimo 1, maximo 4, promedio 1.5495.
- Componentes distintos: 78.
- Componentes distintos con vinculo ERP: 78.
- Renglones de componentes con vinculo ERP: 141 de 141.
- Paquetes con todos sus componentes vinculados a ERP: 91.
- Paquetes con componentes faltantes de vinculo ERP: 0.
- Paquetes legacy cuyo SKU ya coincide con SKU ERP: 12.
- Paquetes legacy cuyo codigo de barras ya coincide con codigo ERP activo: 14.
- SKUs duplicados dentro de paquetes legacy: 3.
- Codigos de barras duplicados dentro de paquetes legacy: 2.
- Paquetes con precio base cero: 1.
- Paquetes con existencia legacy mayor a cero: 89.

Decision:

- No migrar automaticamente todavia.
- Los componentes legacy ya tienen cobertura ERP completa por vinculo ecommerce, lo que facilita migrar recetas.
- El riesgo esta en el SKU paquete: hay coincidencias y duplicados, por lo que se requiere migracion asistida.
- No migrar `precio_base`, `precio_costo` ni `existencia` legacy como verdad operativa sin pasar por Precios, Costos e Inventario.

Verificacion:

- Sintaxis PHP valida con `C:\xampp\php\php.exe -l app\modelos\CatalogoErpEsquema.php`.
- No se ejecuto migracion ni escritura de esquema.

Proxima autorizacion necesaria:

- Respaldar BD fuera del proyecto y ejecutar `CatalogoErpEsquema::planActualizarCatalogoErp(true)` desde el endpoint/flujo autorizado de esquema.

### Correccion de alcance paquetes configurables

Fecha: 2026-06-26

Decision vigente:

- No usar los paquetes legacy como base del modulo nuevo.
- No migrar automaticamente `ecom_paquetes*`.
- El nuevo modulo de paquetes debe diseñarse desde cero para soportar paquetes simples y paquetes configurables.
- El legacy queda solo como referencia historica si despues se quiere rescatar un caso puntual.

Nuevo alcance funcional:

- Paquete simple: componentes fijos y cantidades fijas.
- Paquete configurable: componentes fijos mas grupos de seleccion.
- Cada grupo define minimo y maximo de opciones que el cliente u operador puede elegir.
- Cada opcion apunta a un SKU ERP y puede tener cantidad fija o cantidad editable dentro de limites.
- Ventas debe guardar la seleccion final elegida por el cliente.
- Almacen/Inventario debe consumir o armar los SKUs realmente seleccionados.

Ejemplo conceptual:

- Paquete de pecera equipada:
  - componente fijo: pecera;
  - grupo `grava`: elegir uno o varios tipos/colores y cantidad;
  - grupo `filtro`: elegir una opcion entre filtro de cascada o cabeza de poder.

Estructura propuesta corregida:

- `erp_catalogo_sku_paquetes`
- `erp_catalogo_sku_paquete_componentes`
- `erp_catalogo_sku_paquete_grupos`
- `erp_catalogo_sku_paquete_grupo_opciones`

Archivo actualizado:

- `docs/erp_catalogo_paquetes_plan.md`
- `app/modelos/CatalogoErpEsquema.php`

Pendiente antes de migrar:

- Validar sintaxis y dry-run del nuevo DDL corregido.
- No ejecutar esquema sin respaldo externo y autorizacion.

Validacion corregida:

- Sintaxis PHP valida en `app/modelos/CatalogoErpEsquema.php`.
- Auditoria local en modo lectura:
  - Tablas faltantes: 4.
  - Columnas faltantes: 4.
  - Indices faltantes: 1.
  - Indices con columnas distintas: 0.

Faltantes vigentes:

- `erp_catalogo_sku_reglas_inventario.requiere_cantidad_variable_recepcion`
- `erp_catalogo_sku_reglas_inventario.requiere_unidades_fisicas_recepcion`
- `erp_catalogo_sku_reglas_inventario.tolerancia_recepcion_porcentaje`
- `erp_catalogo_sku_reglas_inventario.nota_recepcion_variable`
- `erp_catalogo_sku_reglas_inventario.idx_catalogo_regla_recepcion_variable`
- `erp_catalogo_sku_paquetes`
- `erp_catalogo_sku_paquete_componentes`
- `erp_catalogo_sku_paquete_grupos`
- `erp_catalogo_sku_paquete_grupo_opciones`

### Handoff y DDL revisable

Fecha: 2026-06-26

Archivos creados:

- `docs/erp_catalogo_paquetes_configurables_ddl_propuesto.sql`
- `docs/erp_catalogo_handoff_ventas_paquetes.md`
- `docs/erp_catalogo_handoff_almacen_paquetes_recepcion.md`

Regla:

- El SQL es propuesta no ejecutada.
- Ventas debe implementar configurador y snapshot de opciones elegidas.
- Almacen/Inventario debe implementar recepcion variable, armado/desarmado y consumo real de componentes/opciones.

### Scaffold UI paquetes sin migracion

Fecha: 2026-06-26

Cambio aplicado:

- `CatalogoErpDatos::consultarProducto()` ahora incluye `paquetes`.
- `CatalogoErpDatos::consultarPaquetesProducto()` consulta recetas de paquetes si el esquema existe.
- Si faltan las tablas, devuelve `esquema_disponible=false` y una lista de pendientes sin romper el modal.
- El modal de productos tiene pestaña `Paquetes`.
- `productos.js` renderiza paquetes en modo lectura o muestra aviso de esquema pendiente.

Alcance:

- Sin escrituras de paquetes.
- Sin migraciones ejecutadas.
- Sin usar legacy `ecom_paquetes*`.
- Sin tocar Ventas, Almacen ni Inventario.

Criterio para continuar:

- Aplicar respaldo externo.
- Autorizar ejecucion del DDL propuesto.
- Despues de tener esquema, implementar CRUD de recetas, componentes fijos, grupos y opciones.

### Scaffold CRUD paquete simple

Fecha: 2026-06-26

Cambio aplicado:

- `CatalogoErpDatos::buscarSkusParaPaquete()` busca SKUs de todo Catalogo para componentes.
- Endpoint `CatalogoErp::paquetes_buscar_skus()` expone esa busqueda con permiso `catalogo.ver`.
- `CatalogoErpDatos::guardarPaqueteSimple()` guarda encabezado y componentes fijos si el esquema existe.
- `CatalogoErpDatos::desactivarPaquete()` desactiva receta sin borrar.
- Endpoints:
  - `/catalogoerp/guardar_paquete_simple`
  - `/catalogoerp/desactivar_paquete`
- El modal tiene formulario de paquete simple condicionado a esquema disponible.
- `productos.js` permite buscar componente, agregarlo, capturar cantidad/factor y guardar.

Reglas vigentes:

- Paquete simple no afecta ventas ni existencias.
- Componentes pueden pertenecer a cualquier producto ERP.
- No se puede usar el mismo SKU paquete como componente.
- Componentes activos anteriores se marcan inactivos al reemplazar receta.
- Si el esquema no existe, el modelo responde warning y no escribe.

Pendiente:

- Aplicar DDL con respaldo/autorizacion.
- Agregar edicion visual de paquetes existentes.
- Agregar grupos y opciones configurables.
- Definir permisos finos si paquetes se separa como submodulo operativo.

### Edicion visual paquete simple preparada

Fecha: 2026-06-26

Cambio aplicado:

- La lista de paquetes puede cargar una receta existente al formulario.
- La edicion permite modificar encabezado y componentes fijos.
- Se agrego accion de desactivar receta sin borrarla.

Alcance:

- Sigue condicionado a que el esquema exista.
- No descuenta inventario.
- No genera armado en Almacen.
- No habilita configurador de Ventas.

Pendiente siguiente:

- Aplicar DDL con respaldo/autorizacion para probar el flujo real.
- Despues, construir grupos/opciones configurables.

### Scaffold backend grupos configurables

Fecha: 2026-06-26

Cambio aplicado:

- `CatalogoErpDatos::guardarPaqueteGrupo()` crea/edita grupos de seleccion.
- `CatalogoErpDatos::desactivarPaqueteGrupo()` desactiva grupo y sus opciones activas.
- `CatalogoErpDatos::guardarPaqueteGrupoOpcion()` crea/edita opciones SKU dentro de un grupo.
- `CatalogoErpDatos::desactivarPaqueteGrupoOpcion()` desactiva opciones sin borrarlas.
- Endpoints:
  - `/catalogoerp/guardar_paquete_grupo`
  - `/catalogoerp/desactivar_paquete_grupo`
  - `/catalogoerp/guardar_paquete_opcion`
  - `/catalogoerp/desactivar_paquete_opcion`

Reglas preparadas:

- Grupo requiere paquete existente.
- `min_selecciones` no puede ser negativo.
- `max_selecciones` debe ser mayor o igual que minimo.
- Si `modo_cantidad=distribuir_total`, la cantidad total del grupo es obligatoria y mayor a cero.
- Opcion requiere grupo activo y SKU operativo.
- La opcion no puede ser el mismo SKU paquete.
- Cantidad default y factor deben ser mayores a cero.
- Cantidad default debe respetar minimo/maximo si se capturan.
- No se permite repetir el mismo SKU como opcion activa dentro del mismo grupo.

Alcance:

- Backend preparado, protegido por esquema pendiente.
- Sin UI completa de grupos/opciones todavia.
- Sin cambios en Ventas, Almacen ni Inventario.

### UI grupos y opciones configurables preparada

Fecha: 2026-06-26

Cambio aplicado:

- El modal de paquetes incluye formulario de grupo configurable.
- El modal incluye formulario de opcion por grupo.
- La lista de paquetes permite:
  - crear grupo desde una receta;
  - editar grupo existente;
  - crear opcion desde un grupo;
  - editar opcion existente;
  - desactivar grupo;
  - desactivar opcion.
- `productos.js` conecta los formularios con los endpoints ya preparados.

Alcance:

- Sigue condicionado a esquema disponible.
- No afecta inventario ni ventas.
- La seleccion final del cliente sigue siendo responsabilidad futura de Ventas.
- El armado, consumo y disponibilidad operativa siguen siendo responsabilidad futura de Almacen/Inventario.

### Checklist para autorizar esquema de paquetes

Fecha: 2026-06-26

Estado:

- DDL propuesto: `docs/erp_catalogo_paquetes_configurables_ddl_propuesto.sql`.
- Endpoint de auditoria: `/sistema/esquema_auditar_catalogo_erp`.
- Endpoint de actualizacion: `/sistema/esquema_actualizar_catalogo_erp` con `POST ejecutar=1`.
- No ejecutar sin respaldo externo.

Antes de ejecutar:

1. Generar respaldo externo de la BD fuera del proyecto.
2. Confirmar que el respaldo se puede abrir o restaurar.
3. Ejecutar auditoria de Catalogo y confirmar faltantes esperados:
   - 4 tablas de paquetes;
   - 4 columnas de recepcion variable;
   - 1 indice de recepcion variable.
4. Confirmar autorizacion explicita del dueno para ejecutar esquema.

Prueba posterior esperada:

1. Abrir Catalogo > producto con SKU paquete.
2. Pestaña `Paquetes` debe mostrar formulario.
3. Guardar paquete simple con componentes de distintos productos.
4. Recargar producto y confirmar que la receta aparece.
5. Editar componentes y confirmar que la receta se reemplaza sin borrar historial.
6. Crear grupo configurable con minimo/maximo.
7. Crear opciones dentro del grupo.
8. Desactivar opcion y grupo para validar baja logica.
9. Confirmar que no se movio inventario ni ventas.

Riesgo:

- Una vez aplicado el esquema, el siguiente riesgo ya no es Catalogo sino el contrato con Ventas y Almacen/Inventario. Catalogo solo define recetas; otros modulos deben decidir seleccion final, disponibilidad, armado y consumo.

### Auditoria previa sin ejecucion

Fecha: 2026-06-26

Comando local usado en modo lectura:

- `CatalogoErpEsquema::auditarCatalogoErp()`

Resultado:

- Estado: pendientes criticos esperados.
- Tablas faltantes: 4.
- Columnas faltantes: 4.
- Indices faltantes: 1.
- Indices con columnas distintas: 0.

Confirmacion:

- Los faltantes coinciden con la preparacion de paquetes configurables y recepcion variable.
- No se ejecuto `planActualizarCatalogoErp(true)`.
- No se hicieron cambios de esquema ni escrituras de BD.

### Runbook aplicacion paquetes

Fecha: 2026-06-26

Archivo creado:

- `docs/erp_catalogo_paquetes_runbook_aplicacion.md`

Notas:

- Corrige el detalle operativo del endpoint de actualizacion: debe enviarse `POST ejecutar=1`.
- No usar `?ejecutar=1` por GET, porque `Sistema::esquema_actualizar_catalogo_erp()` lee `$_POST`.
- Incluye pasos de respaldo externo, auditoria previa, aplicacion, auditoria posterior y prueba funcional.

### Dry-run esquema paquetes

Fecha: 2026-06-26

Comando local usado en modo lectura:

- `CatalogoErpEsquema::planActualizarCatalogoErp(false)`

Resultado:

- Estado: plan generado en dry-run.
- No se ejecuto SQL.
- El plan genera exactamente los SQL pendientes de:
  - 4 columnas de recepcion variable;
  - 1 indice de recepcion variable;
  - 4 tablas de paquetes configurables.
- El resto de tablas/columnas/indices existentes se reportan como ya existentes.

Confirmacion:

- El modelo `CatalogoErpEsquema` y el DDL propuesto estan alineados para paquetes configurables.
- Siguiente paso operativo sigue siendo respaldo externo + autorizacion explicita para ejecutar `POST ejecutar=1`.

### Configuracion recepcion variable preparada

Fecha: 2026-06-26

Cambio aplicado:

- `CatalogoErpDatos::consultarProducto()` devuelve campos de recepcion variable.
- Si las columnas aun no existen, devuelve valores seguros:
  - `requiere_cantidad_variable_recepcion = 0`
  - `requiere_unidades_fisicas_recepcion = 0`
  - `tolerancia_recepcion_porcentaje = NULL`
  - `nota_recepcion_variable = NULL`
- Se agrego deteccion segura de columnas con `columnaExisteCatalogo()`.
- `guardarRecepcionVariableSku()` guarda la configuracion solo si el DDL ya fue aplicado.
- La validacion impide:
  - recepcion variable en SKU que no controla inventario;
  - tolerancia menor a 0 o mayor a 100;
  - tolerancia capturada sin activar cantidad variable.
- La UI de SKU muestra una seccion `Recepcion variable`.

Regla operativa:

- No crear unidades como costal, saco, paca o bolsa por lenguaje operativo.
- La unidad formal sigue siendo la unidad base del SKU.
- Lo variable es la cantidad real capturada en Almacen/Recepcion.

Estado:

- Preparado en codigo.
- No persistira en BD hasta aplicar las columnas pendientes con respaldo/autorizacion.
- Almacen/Recepciones debe implementar despues el uso operativo de estas reglas.

### Configuracion modular de Catalogo

Fecha: 2026-06-29

Documento creado:

- `docs/erp_catalogo_configuracion_plan.md`

Decision:

- `erp_catalogo_categorias` queda como arbol maestro ERP para categoria/subcategoria y categoria principal del producto.
- La clasificacion heredada queda como insumo para construir categorias maestras, no como modulo ecommerce separado.
- Al sincronizar clasificacion heredada:
  - la clasificacion se convierte en categoria estructural;
  - sus ramas se convierten en categorias operativas;
  - si un producto no tiene categoria principal, se le asigna desde esa clasificacion.
- La bandeja de clasificacion pendiente solo ayuda a completar productos, no es otro catalogo maestro.

Cambios aplicados:

- `app/vistas/paginas/apps/erp/catalogo/configuracion.php` separa la pantalla en modulos navegables.
- `public/assets/js/custom/apps/erp/catalogo/configuracion.js` ya no carga todos los bloques al abrir.
- Al entrar abre `Catalogos maestros`.
- Cada modulo carga sus datos solo cuando se activa:
  - Calidad;
  - Clasificacion;
  - Clasificacion heredada;
  - Reglas operativas;
  - Proveedor y costos.
- El boton `Nuevo registro` queda visible solo en `Catalogos maestros`.
- La pestaña Categorias ahora explica que la clasificacion heredada solo ayuda a construir el arbol maestro ERP.
- La tabla de Categorias muestra raiz/nivel y distingue uso operativo, estructural o legado.
- `app/modelos/CatalogoErpDatos.php` protege la baja logica de catalogos maestros usados:
  - marcas con productos;
  - categorias con productos, hijas activas o clasificacion heredada;
  - unidades usadas por SKUs, proveedores o paquetes;
  - atributos usados por SKUs.

Estado posterior:

- El DDL acotado de imagenes de marcas/categorias ya fue auditado y aplicado con respaldo externo.
- La UI real queda habilitada para registrar imagenes de marcas y categorias.

### Propuesta imagenes de marcas y categorias

Fecha: 2026-06-29

Documento actualizado:

- `docs/erp_catalogo_configuracion_plan.md`

Estado actualizado:

- DDL aplicado para:
  - `erp_catalogo_marca_imagenes`;
  - `erp_catalogo_categoria_imagenes`.
- Token usado:
  - `CATALOGO_IMAGENES_MARCAS_CATEGORIAS`.
- Documentos de autorizacion preparados:
  - `docs/erp_catalogo_imagenes_marcas_categorias_solicitud_autorizacion.md`;
  - `docs/erp_catalogo_imagenes_marcas_categorias_runbook_aplicacion.md`.
- Preflight read-only preparado:
  - `storage/uat/uat_catalogo_imagenes_autorizacion_preflight_readonly.php`.
  - Ejecucion sin respaldo: `ok=false`, `modo=read-only`, `pendientes_imagenes=2`, sin DDL ejecutado.
  - El preflight muestra los `CREATE TABLE` generados en dry-run para ambas tablas.
- `CatalogoErpEsquema` ya incluye estas tablas como contrato auditable.
- La migracion acotada fue ejecutada solo para estas dos tablas.
- Paquetes sigue fuera de este alcance y requiere su propio plan/autorizacion.

### Correccion clasificacion heredada

Fecha: 2026-06-29

Cambio aplicado:

- `CatalogoErpDatos::sincronizarTaxonomiaEcommerce()` conserva el endpoint historico, pero la regla operativa cambio:
  - ya no se presenta como flujo ecommerce ni como modulo comercial separado;
  - convierte clasificaciones heredadas en categorias maestras estructurales;
  - convierte ramas heredadas en categorias operativas hijas;
  - asigna categoria principal a productos que aun no tenian una.
- La UI de Configuracion ahora usa el modulo `Clasificacion heredada` para revisar ese insumo historico.
- Se corrigieron textos visibles y cadenas con caracteres especiales mal codificados en Configuracion.

Decision:

- Catálogo ERP debe trabajar con categoria principal y arbol maestro.
- Las clasificaciones heredadas son solo material de apoyo para construir ese arbol y acelerar saneamiento.

### UI segura para imagenes de marcas y categorias

Fecha: 2026-06-29

Cambio aplicado:

- Se preparo el gestor visual de imagenes dentro de `Catalogo ERP > Configuracion > Catalogos maestros`.
- Marcas y categorias muestran un boton de imagenes junto al boton de edicion.
- El modal permite listar, preparar captura, editar o desactivar imagenes.
- Si en otro entorno faltan las tablas, el modal abre en modo informativo y bloquea el guardado.
- Endpoints preparados:
  - `catalogoerp/imagenes_maestro_listar`;
  - `catalogoerp/imagen_maestro_guardar`;
  - `catalogoerp/imagen_maestro_desactivar`.
- `CatalogoErpDatos` valida que la imagen pertenezca a la marca/categoria antes de editarla o desactivarla.

Alcance inicial:

- No se escribieron registros de imagenes.
- No se tocaron productos, ventas, almacen, inventario ni canales.

Estado posterior:

- El DDL de imagenes de marcas/categorias ya fue aplicado con respaldo externo y token `CATALOGO_IMAGENES_MARCAS_CATEGORIAS`.
- El guardado real de imagenes maestras queda disponible.

### UX de categorias maestras en Configuracion

Fecha: 2026-06-29

Cambio aplicado:

- En `Catalogo ERP > Configuracion > Catalogos maestros > Categorias` se agregaron filtros por:
  - tipo de categoria;
  - uso operativo: operativa, estructural, con productos o sin productos;
  - estatus.
- Se agrego resumen clicable de categorias visibles para priorizar limpieza por total, operativas, estructurales, con productos, sin productos e inactivas.
- Las acciones masivas de categorias (`Aplicar relaciones historicas` y `Preparar arbol maestro`) ahora solo se muestran cuando esta activa la pestaña de Categorias.
- Al editar una categoria, el selector de categoria padre oculta la misma categoria y sus descendientes para evitar ciclos.
- Marcas, unidades y atributos muestran resumen compacto de totales, activos/inactivos y datos relevantes como unidades con clave SAT o atributos de variante.

Alcance:

- Cambio de UX/UI solamente.
- No se modifico esquema.
- No se escribieron datos.
- Ayuda a limpiar categorias maestras migradas sin confundirlas con marcas, unidades o atributos.

### Handoff de Catalogo para modulos consumidores

Fecha: 2026-06-29

Documento creado:

- `docs/erp_catalogo_handoff_modulos.md`

Contenido:

- Que debe consumir Compras desde Catalogo.
- Que debe consumir Almacen/Recepciones.
- Que debe consumir Inventario/Existencias.
- Que debe consumir Ventas/Canales.
- Que debe consumir Costos/Rentabilidad.
- Que debe consumir Garantias.
- Regla de categoria principal, clasificacion heredada e imagenes de marcas/categorias.

Decision:

- Otros modulos no deben duplicar decisiones maestras de Catalogo.
- Si un modulo necesita un dato nuevo, primero debe clasificarse como dato maestro, regla operativa, dato comercial, dato financiero o politica reutilizable.

### Auditoria read-only de limpieza en Configuracion

Fecha: 2026-06-29

Script creado:

- `storage/uat/uat_catalogo_configuracion_limpieza_readonly.php`

Resultado:

- Productos no fusionados: 1535.
- SKUs activos: 1730.
- Productos sin categoria principal: 155.
- Productos con mas de una categoria principal: 0.
- Productos sin marca: 842.
- SKUs activos sin proveedor activo: 890.
- SKUs con varios proveedores activos sin preferido: 0.
- Categorias heredadas: 75.
- Vinculos de canal ecommerce: 1741.
- Incidencias de migracion pendientes: 0.
- Categorias con texto danado: 20.
- Marcas con texto danado: 0.
- Productos con texto danado: 17.

Decision:

- No borrar migracion ni vinculos ecommerce desde esta auditoria.
- Usar el reporte para priorizar saneamiento operativo desde Configuracion.
- Primer foco: categoria principal, proveedores activos y texto danado de categorias.

### Bandeja de clasificacion pendiente ajustada

Fecha: 2026-06-29

Cambio aplicado:

- La bandeja `Catalogo ERP > Configuracion > Clasificacion pendiente` ahora lista productos sin categoria principal.
- Antes detectaba productos sin cualquier categoria; eso podia dejar fuera productos con categorias secundarias pero sin principal.
- Al guardar una categoria desde esta bandeja, se envia `forzar_categoria_principal=1`.
- Se creo UAT read-only:
  - `storage/uat/uat_catalogo_clasificacion_pendiente_readonly.php`.

Validacion:

- La bandeja reporta 155 pendientes de categoria principal.
- Reporta tambien 6 marcas ambiguas.
- No se aplicaron asignaciones reales.

### Auditoria de SKUs sin proveedor

Fecha: 2026-06-29

Script creado:

- `storage/uat/uat_catalogo_skus_sin_proveedor_readonly.php`

Resultado:

- SKUs activos sin proveedor: 890.
- Con match exacto en listas de proveedor: 7.
- Sin match exacto en listas de proveedor: 883.

Decision:

- No conviene resolver el bloque con sincronizacion historica ciega.
- Usar Productos con filtro `SKU sin proveedor` y asignacion masiva solo por lotes que el operador revise.
- Priorizar lotes por categoria, empezando por:
  - `Alimentacion / Alimentos`;
  - `Habitat y descanso / Camas, casas y refugios`;
  - productos sin categoria principal;
  - `Transporte, paseo y entrenamiento / Paseo y sujecion`.

### Categorias maestras saneadas

Fecha: 2026-06-29

Cambio aplicado:

- Se audito solo el maestro de categorias; no se tocaron productos.
- Se creo `storage/uat/uat_catalogo_categorias_maestro_readonly.php`.
- Se corrigieron 20 categorias heredadas con caracteres danados en `nombre` y `ruta`.
- Validacion posterior:
  - categorias totales: 106;
  - maestras: 31;
  - heredadas: 75;
  - texto danado: 0;
  - padres inexistentes: 0;
  - codigos duplicados: 0;
  - rutas inconsistentes: 0.

UI:

- En Configuracion > Catalogos maestros > Categorias se agrego filtro `Texto dañado`.
- Se oculto el boton `Aplicar relaciones historicas` dentro del CRUD de categorias para evitar modificar productos desde esta vista.

Decision:

- Categorias queda lista como catalogo maestro.
- La clasificacion de productos sigue siendo trabajo separado del usuario o de la bandeja `Clasificacion pendiente`.

### Marcas, unidades y atributos auditados

Fecha: 2026-06-29

Cambio aplicado:

- Se creo `storage/uat/uat_catalogo_maestros_auxiliares_readonly.php`.
- Se creo `storage/uat/uat_catalogo_atributos_texto_reparar_apply.php`.
- Se corrigieron 5 atributos con texto danado:
  - `Absorcion`;
  - `Peso maximo`;
  - `Diametro`;
  - `Diseno`.

Resultado:

- Marcas:
  - total: 42;
  - texto danado: 0;
  - codigos duplicados: 0;
  - nombres duplicados: 0.
- Unidades:
  - total: 10;
  - texto danado: 0;
  - sin abreviatura: 0;
  - sin clave SAT: 0;
  - codigos duplicados: 0.
- Atributos:
  - total: 31;
  - texto danado despues: 0;
  - codigos duplicados: 0;
  - duplicados por nombre pendientes: `Alto`, `Ancho`, `Diametro`.

Decision:

- No se fusionaron atributos duplicados porque eso requiere mover valores de SKUs.
- La fusion de atributos debe ser subtarea controlada con auditoria antes/despues y resolucion de conflictos.

Auditoria y fusion adicional:

- Se creo `storage/uat/uat_catalogo_atributos_duplicados_readonly.php`.
- Se creo y ejecuto `storage/uat/uat_catalogo_atributos_duplicados_fusion_apply.php`.
- No habia SKUs con valores en atributo canonico y heredado al mismo tiempo.
- Se movieron valores heredados:
  - 1 SKU de `Alto` a `ATR-ALTO`;
  - 1 SKU de `Ancho` a `ATR-ANCHO`;
  - 2 SKUs de `Diametro` a `ATR-DIAMETRO`.
- Se inactivaron los 3 atributos heredados.
- Validacion posterior:
  - duplicados activos en atributos: 0;
  - texto danado en atributos: 0;
  - valores heredados pendientes: 0.

### Imagenes de marcas/categorias preparadas para autorizacion

Fecha: 2026-06-29

Hallazgo:

- El esquema general de Catalogo aun tiene 6 tablas faltantes.
- Las faltantes son:
  - 4 tablas de paquetes;
  - `erp_catalogo_marca_imagenes`;
  - `erp_catalogo_categoria_imagenes`.
- No conviene usar el endpoint general de esquema si solo se quiere aplicar imagenes, porque tambien podria crear paquetes.

Cambio aplicado:

- Se endurecio `storage/uat/uat_catalogo_imagenes_autorizacion_preflight_readonly.php` para rechazar placeholders de respaldo.
- Se creo `storage/uat/uat_catalogo_esquema_pendientes_readonly.php`.
- Se creo `storage/uat/uat_catalogo_imagenes_schema_apply_authorized.php`, acotado solo a imagenes.
- Se actualizaron:
  - `docs/erp_catalogo_imagenes_marcas_categorias_solicitud_autorizacion.md`;
  - `docs/erp_catalogo_imagenes_marcas_categorias_runbook_aplicacion.md`.

Decision:

- Imagenes de marcas/categorias requiere autorizacion fuerte con token `CATALOGO_IMAGENES_MARCAS_CATEGORIAS` y respaldo externo real.
- Paquetes queda fuera de esta autorizacion.

### DDL de imagenes de marcas/categorias aplicado

Fecha: 2026-06-29

Respaldo:

- Se genero respaldo externo:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260629_antes_catalogo_imagenes_marcas_categorias.sql`
  - Tamano: 27759132 bytes.

Aplicacion:

- Token usado: `CATALOGO_IMAGENES_MARCAS_CATEGORIAS`.
- Script ejecutado:
  - `storage/uat/uat_catalogo_imagenes_schema_apply_authorized.php`.
- Alcance aplicado:
  - `erp_catalogo_marca_imagenes`;
  - `erp_catalogo_categoria_imagenes`.

Validacion:

- Preflight posterior:
  - `pendientes_imagenes`: 0;
  - ambas tablas existen;
  - no se crearon tablas de paquetes.
- UAT read-only:
  - `storage/uat/uat_catalogo_imagenes_maestros_readiness_readonly.php`;
  - `schema_disponible`: true;
  - registros creados automaticamente: 0;
  - indices esperados disponibles.
- Auditoria de esquema posterior:
  - faltantes restantes: 4 tablas de paquetes.

Decision:

- Configuracion > Catalogos maestros ya puede usar el CRUD basico de imagenes por URL/ruta para marcas y categorias.
- Paquetes queda pendiente como modulo/plan separado.

### UX de imagenes en catalogos maestros

Fecha: 2026-06-29

Cambio aplicado:

- Marcas: filtros por texto, estado y presencia de imagen.
- Categorias: filtro por presencia de imagen y resumen visual `Con imagen` / `Sin imagen`.
- El JS documenta las funciones nuevas para filtrar marcas/categorias por avance visual.

Criterio operativo:

- Las imagenes de marcas/categorias se administran desde Configuracion.
- No se asignan imagenes automaticamente desde recuperaciones o migraciones.
- El usuario decide que imagen queda vinculada al maestro antes de usarla como referencia visual.

### Paquetes configurables: preflight acotado

Fecha: 2026-06-29

Hallazgo actualizado:

- Despues de aplicar imagenes de marcas/categorias, la auditoria de Catalogo reporta:
  - tablas faltantes: 4;
  - columnas faltantes: 0;
  - indices faltantes: 0;
  - indices con columnas distintas: 0.
- Los faltantes vigentes son solo:
  - `erp_catalogo_sku_paquetes`;
  - `erp_catalogo_sku_paquete_componentes`;
  - `erp_catalogo_sku_paquete_grupos`;
  - `erp_catalogo_sku_paquete_grupo_opciones`.
- Recepcion variable ya no aparece como faltante de esquema; no debe incluirse en el apply de paquetes.

Archivos preparados:

- `docs/erp_catalogo_paquetes_configurables_ddl_acotado.sql`;
- `docs/erp_catalogo_paquetes_configurables_solicitud_autorizacion.md`;
- `storage/uat/uat_catalogo_paquetes_preflight_readonly.php`;
- `storage/uat/uat_catalogo_paquetes_schema_apply_authorized.php`.

Validacion:

- `storage/uat/uat_catalogo_paquetes_preflight_readonly.php` con respaldo real devuelve `ok=true`.
- `storage/uat/uat_catalogo_paquetes_schema_apply_authorized.php` sin token/respaldo devuelve bloqueo y no ejecuta DDL.
- Auditoria posterior sigue mostrando las 4 tablas faltantes, confirmando que no se aplico esquema durante esta preparacion.

Proxima autorizacion necesaria:

```txt
Autorizo aplicar DDL acotado de paquetes configurables de Catalogo con token CATALOGO_PAQUETES_CONFIGURABLES_DDL.
Respaldo externo: <ruta real fuera del proyecto>.
Alcance: crear solo las 4 tablas de paquetes configurables; sin recepcion variable, sin migrar legacy, sin tocar otros modulos.
```

### DDL acotado de paquetes configurables aplicado

Fecha: 2026-06-29

Autorizacion:

- Token: `CATALOGO_PAQUETES_CONFIGURABLES_DDL`.
- Alcance autorizado: crear solo las 4 tablas de paquetes configurables; sin recepcion variable, sin migrar legacy, sin tocar otros modulos.

Respaldo:

- Se genero respaldo externo fresco:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260629_233248_antes_catalogo_paquetes_configurables.sql`
  - Tamano: 27763752 bytes.

Aplicacion:

- Script ejecutado:
  - `storage/uat/uat_catalogo_paquetes_schema_apply_authorized.php`.
- Tablas creadas:
  - `erp_catalogo_sku_paquetes`;
  - `erp_catalogo_sku_paquete_componentes`;
  - `erp_catalogo_sku_paquete_grupos`;
  - `erp_catalogo_sku_paquete_grupo_opciones`.

Validacion posterior:

- `storage/uat/uat_catalogo_esquema_pendientes_readonly.php`:
  - tablas faltantes: 0;
  - columnas faltantes: 0;
  - indices faltantes: 0;
  - indices con columnas distintas: 0.
- `storage/uat/uat_catalogo_paquetes_preflight_readonly.php`:
  - `ok=true`;
  - `sin_faltantes=true`;
  - `resumen_completo=true`.

Siguiente paso:

- Ejecutar prueba funcional desde Catalogo:
  - abrir un producto/SKU que representara paquete;
  - crear paquete simple;
  - agregar componentes;
  - crear grupo configurable;
  - agregar opciones;
  - recargar y validar persistencia.
- No conectar Ventas/Almacen todavia; primero validar que Catalogo guarde la receta correctamente.

### Ajuste UX paquete configurable - busqueda global de opciones

Fecha: 2026-06-30

Hallazgo:

- En la pestaña `Paquetes`, el select de `SKU opcion` se llenaba solo con los SKUs del producto abierto.
- Esto confundia el flujo porque una opcion de grupo puede ser un SKU de otro producto.
- Ejemplo: paquete de pecera con grupo `Grava`; las opciones deben poder ser SKUs de gravas de distintos productos/familias.

Cambio aplicado:

- El formulario de `Opcion del grupo` ahora tiene buscador global de SKU.
- El usuario busca por SKU, nombre o producto, selecciona el resultado y el select queda con el SKU elegido.
- El select ya no se precarga con los SKUs del producto actual.
- Se agrego texto operativo para distinguir:
  - componentes fijos: siempre van en el paquete;
  - grupos configurables: opciones entre las que despues se elige.

Criterio:

- Catalogo define las opciones permitidas, pero no decide la seleccion final del cliente.
- Ventas debera capturar la opcion elegida cuando venda el paquete configurable.

### Ajuste paquete configurable - encabezado coherente

Fecha: 2026-06-30

Hallazgo:

- El paquete probado `PER-05` tenia grupo configurable y opciones, pero el encabezado seguia como `tipo_paquete=simple` y `permite_configuracion_cliente=0`.
- Esto era inconsistente: si un paquete tiene grupos configurables activos, debe quedar identificado como configurable.

Cambio aplicado:

- La UI del encabezado de paquete ahora muestra:
  - tipo `Configurable`;
  - check `Permite configurar opciones`.
- Al crear un grupo desde la UI, el formulario del paquete sugiere automaticamente `tipo_paquete=configurable`.
- `CatalogoErpDatos::guardarPaqueteGrupo()` marca el paquete padre como configurable cuando se guarda un grupo.
- Se corrigio el paquete actual:
  - `id_paquete=1`;
  - SKU paquete `PER-05`;
  - `tipo_paquete=configurable`;
  - `permite_configuracion_cliente=1`;
  - 1 componente fijo;
  - 1 grupo;
  - 2 opciones.

Criterio:

- Solo puede existir una receta de paquete por SKU paquete.
- Si se necesitan varios paquetes distintos, deben existir varios SKUs paquete.
- Un mismo producto puede tener varios SKUs, y cada SKU puede representar un paquete distinto.

### Ajuste paquetes - varios paquetes sobre el mismo producto fisico

Fecha: 2026-06-30

Hallazgo:

- Operativamente un mismo producto fisico puede formar parte de varios paquetes comerciales.
- Ejemplo: una pecera fisica puede usarse en un paquete para terrario y en otro paquete para acuario equipado.
- El diseno robusto no debe guardar varias recetas activas sobre el mismo SKU paquete, porque Ventas, Almacen e Inventario no sabrian cual receta consumir al vender ese SKU.

Decision:

- Cada paquete vendible debe tener su propio SKU paquete.
- El producto fisico compartido debe ir como componente fijo u opcion dentro de cada paquete.
- Ejemplo:
  - `PER-05-TERRARIO-KIT` como SKU paquete para terrario;
  - `PER-05-ACUARIO-KIT` como SKU paquete para acuario;
  - `PER-05` como componente fisico si representa la pecera sola.

Cambio aplicado:

- En la pestana `Paquetes` se agrego una guia operativa que explica que un paquete vendible necesita su propio SKU.
- Se agrego accion `Crear SKU de paquete` para iniciar el alta del SKU paquete del producto abierto.
- La guia detecta si el producto solo tiene un SKU activo o si todos sus SKUs ya tienen receta, y recomienda crear otro SKU paquete.
- El boton de guardado ahora dice `Guardar receta del SKU paquete` para evitar confundir paquete con producto fisico.
- Al crear una receta nueva, el selector `SKU paquete` deshabilita los SKUs que ya tienen receta y los marca como `receta existente`.
- Backend separa crear de editar: si se intenta crear una receta con un SKU que ya tiene receta, responde advertencia y no sobrescribe la receta existente.

Ajuste UX posterior:

- La accion `Crear SKU de paquete` ya no manda al usuario a la pestana `SKU`.
- En la pestana `Paquetes` se agrego un alta inline de `SKU paquete`.
- El alta inline pide:
  - nuevo SKU paquete;
  - nombre del paquete;
  - unidad del paquete;
  - SKU fisico base del producto actual;
  - cantidad base del componente.
- Al guardar:
  - se crea el SKU paquete usando el endpoint formal de Catalogo;
  - queda seleccionado como `SKU paquete` en la receta;
  - el SKU fisico base se agrega automaticamente como componente fijo inicial.
- El selector de componente base excluye SKUs tipo `kit`, `servicio` y `cargo` para evitar usar otro paquete como producto fisico base.

Contrato para otros modulos:

- Ventas debe vender el SKU paquete y guardar, cuando aplique, las opciones elegidas del cliente.
- Almacen/Inventario debe consumir o armar los componentes de la receta del SKU paquete vendido.
- Catalogo no arma existencia ni descuenta inventario; solo define receta y opciones permitidas.

### Ajuste paquetes - limpieza visual y selects con buscador

Fecha: 2026-06-30

Necesidad:

- Al configurar paquetes se requiere eliminar componentes, grupos, opciones o paquetes incompletos para no ensuciar la vista operativa.
- El usuario pidio que los campos con muchas opciones tengan busqueda para acelerar captura.

Decision:

- `Eliminar` en la UI de paquetes significa inactivar y ocultar de la vista normal, no borrado fisico.
- La recuperacion de paquetes/grupos/opciones inactivos queda para una mejora futura con rol de administrador.
- Componentes fijos se eliminan editando el paquete, quitando el renglon y guardando la receta; el modelo reemplaza los componentes activos por la nueva lista.

Cambio aplicado:

- Paquetes con estatus `inactivo` ya no aparecen en la consulta normal de paquetes.
- Los botones de paquete, grupo y opcion ahora usan icono/texto de eliminacion visual.
- Los mensajes de confirmacion aclaran que se conserva historial.
- La tabla de componentes muestra ayuda: para eliminar componente guardado, editar paquete, quitar renglon y guardar.
- Los selects del modal de Catalogo activan busqueda con Select2 cuando la libreria esta disponible.
- Si Select2 no esta disponible, los selects nativos siguen funcionando sin romper el flujo.

### Categorias - clasificaciones heredadas como raices del arbol

Fecha: 2026-07-03

Hallazgo:

- El CRUD de categorias tenia familias maestras y categorias legado, pero no estaban creadas las clasificaciones heredadas como categorias raiz `CLAS-HIST-*`.
- Esto dejaba incompleto el arbol para operar con raices como `Acuario`, `Aves`, `Betta`, etc.
- La opcion para agregar una categoria nueva existia como boton generico `Nuevo registro`, pero en la pestana Categorias no era suficientemente clara.

Cambio aplicado:

- Se ejecuto la sincronizacion de clasificacion heredada hacia categorias maestras.
- Resultado:
  - clasificaciones raiz creadas: 15;
  - ramas/categorias operativas sincronizadas: 125;
  - categorias `CLAS-HIST-*` totales: 140;
  - productos vinculados a taxonomia historica: 1300;
  - productos ecommerce sin vinculo ERP: 8.
- El arbol ahora incluye clasificaciones heredadas como categorias estructurales raiz con `permite_productos=0`.
- Sus categorias hijas quedan como categorias operativas con `permite_productos=1`.
- Se agrego boton directo `Nueva categoria` en la pestana Categorias de Configuracion para abrir el modal ya preparado como categoria.

Criterio:

- Las clasificaciones heredadas quedan como soporte estructural del arbol, no como canal ecommerce.
- Una categoria raiz estructural ordena el arbol; las categorias hijas operativas son las que se asignan a productos.
- Si se necesita crear una nueva raiz o subcategoria, se usa `Nueva categoria` desde Configuracion > Catalogos maestros > Categorias.
