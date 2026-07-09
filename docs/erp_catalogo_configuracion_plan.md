# ERP Catalogo - Plan de configuracion modular

Fecha: 2026-06-29

## Objetivo

Separar la pantalla de Configuracion de Catalogo ERP en modulos operativos para que no cargue todo al mismo tiempo y para que los catalogos maestros tengan CRUD completo, claro y mantenible.

## Decision de arquitectura

Catalogo ERP debe usar `erp_catalogo_categorias` como arbol maestro de clasificacion del producto.

- Ejemplos: `Acuario`, `Alimento`, `Decoracion`, `Filtracion`, `Iluminacion`.
- Puede tener categoria y subcategoria usando `id_categoria_padre`.
- Un producto debe tener una categoria principal mediante `erp_catalogo_producto_categorias.es_principal = 1`.
- Las categorias maestras sirven para operacion ERP, compras, almacen, calidad de catalogo y reportes.

Las clasificaciones heredadas deben usarse como insumo de saneamiento, no como clasificacion final separada.

- Sirven para reconstruir categorias maestras cuando vienen de datos anteriores.
- La clasificacion heredada debe convertirse en categoria estructural y sus ramas en categorias operativas.
- Si un producto no tiene categoria principal, la clasificacion heredada puede asignarla.
- No debe mantenerse como lenguaje operativo de ecommerce dentro de Catalogo.

La clasificacion pendiente no es un catalogo nuevo. Es una bandeja de trabajo para completar productos sin categoria principal o con marca ambigua.

## Modulos propuestos dentro de Configuracion

1. Calidad de catalogo
   - Auditoria de productos/SKUs incompletos.
   - Incidencias persistentes.
   - No debe cargar costos, clasificacion heredada ni reorden al abrir.

2. Catalogos maestros
   - Marcas.
   - Categorias maestras.
   - Unidades.
   - Atributos.
   - Debe ser el primer CRUD fuerte para ordenar productos migrados.

3. Clasificacion de productos
   - Bandeja para asignar categoria principal.
   - Bandeja para corregir marca ambigua.
   - Debe permitir trabajo masivo controlado.

4. Clasificacion heredada
   - Convierte clasificaciones anteriores en categorias maestras.
   - La clasificacion queda como categoria estructural.
   - Las ramas quedan como categorias operativas que pueden ser categoria principal.

5. Reglas operativas
   - Reorden, reglas de inventario, granel, recepcion variable y preparacion.
   - Algunas reglas pueden vivir en producto/SKU, pero la pantalla de configuracion solo debe auditar o preparar plantillas.

6. Proveedor y costos
   - En Catalogo solo debe verse como puente o alerta de completitud.
   - El costo validado corresponde a Proveedores/Compras/Costos, no al encargado normal de Catalogo.

## Estado actual auditado

Ya existe CRUD basico para:

- Marcas: codigo, nombre, descripcion y estatus.
- Categorias: padre, codigo, nombre, descripcion, ruta, nivel, tipo, origen, permite productos y estatus.
- Unidades: codigo, nombre, abreviatura, magnitud, decimales, clave SAT y estatus.
- Atributos: codigo, nombre, tipo de dato, unidad, opciones JSON, marca de variante y estatus.

Faltantes funcionales:

- UX modular: la pantalla actual mezcla muchos bloques.
- CRUD visual mas claro para categorias tipo arbol.
- Manejo de imagen/logo para marcas.
- Manejo de imagen para categorias, si se usaran en canales o pantallas visuales.
- Mejor separacion entre catalogo maestro, clasificacion heredada y futuros canales de publicacion.
- Reglas de proteccion al inactivar unidades, categorias o atributos ya usados.

## Imagenes de marcas y categorias

No se debe meter una imagen en el nombre o descripcion.

Estructura robusta aplicada con DDL acotado el 2026-06-29:

- `erp_catalogo_marca_imagenes`
  - `id_marca_imagen`
  - `id_marca_erp`
  - `tipo_imagen` (`logo`, `banner`, `referencia`)
  - `url_imagen`
  - `texto_alternativo`
  - `orden`
  - `estatus`
  - fechas

- `erp_catalogo_categoria_imagenes`
  - `id_categoria_imagen`
  - `id_categoria_erp`
  - `tipo_imagen` (`icono`, `portada`, `referencia`)
  - `url_imagen`
  - `texto_alternativo`
  - `orden`
  - `estatus`
  - fechas

Regla:

- No agregar mas DDL a imagenes de marcas/categorias sin respaldo externo y autorizacion explicita.
- El DDL inicial ya fue aplicado solo para las dos tablas de imagenes; paquetes quedo fuera de ese alcance.

### DDL aplicado

Token usado:

- `CATALOGO_IMAGENES_MARCAS_CATEGORIAS`

SQL base aplicado:

```sql
CREATE TABLE IF NOT EXISTS erp_catalogo_marca_imagenes (
  id_marca_imagen BIGINT NOT NULL AUTO_INCREMENT,
  id_marca_erp INT NOT NULL,
  tipo_imagen VARCHAR(30) NOT NULL DEFAULT 'logo',
  url_imagen VARCHAR(700) NOT NULL,
  texto_alternativo VARCHAR(255) NULL,
  orden INT NOT NULL DEFAULT 0,
  estatus VARCHAR(20) NOT NULL DEFAULT 'activo',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_marca_imagen),
  KEY idx_marca_imagen_marca (id_marca_erp, estatus),
  CONSTRAINT fk_marca_imagen_marca
    FOREIGN KEY (id_marca_erp) REFERENCES erp_catalogo_marcas (id_marca_erp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS erp_catalogo_categoria_imagenes (
  id_categoria_imagen BIGINT NOT NULL AUTO_INCREMENT,
  id_categoria_erp INT NOT NULL,
  tipo_imagen VARCHAR(30) NOT NULL DEFAULT 'icono',
  url_imagen VARCHAR(700) NOT NULL,
  texto_alternativo VARCHAR(255) NULL,
  orden INT NOT NULL DEFAULT 0,
  estatus VARCHAR(20) NOT NULL DEFAULT 'activo',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_categoria_imagen),
  KEY idx_categoria_imagen_categoria (id_categoria_erp, estatus),
  CONSTRAINT fk_categoria_imagen_categoria
    FOREIGN KEY (id_categoria_erp) REFERENCES erp_catalogo_categorias (id_categoria_erp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

Validaciones esperadas cuando se implemente:

- `tipo_imagen` permitido para marca: `logo`, `banner`, `referencia`.
- `tipo_imagen` permitido para categoria: `icono`, `portada`, `referencia`.
- `url_imagen` obligatoria.
- Solo una imagen principal activa por tipo principal cuando aplique:
  - una marca puede tener un logo principal activo;
  - una categoria puede tener un icono principal activo.
- No borrar fisicamente desde CRUD normal; usar `estatus='inactivo'`.

UI esperada:

- En Catalogos maestros > Marcas:
  - mostrar miniatura de logo si existe;
  - boton para administrar imagenes de la marca.
- En Catalogos maestros > Categorias:
  - mostrar icono/portada si existe;
  - boton para administrar imagenes de la categoria.

Estado:

- Propuesta documentada y aplicada.
- Tablas existentes:
  - `erp_catalogo_marca_imagenes`;
  - `erp_catalogo_categoria_imagenes`.
- El CRUD de imagenes queda habilitado para marcas y categorias.

## Configuracion modular aplicada

Cambio de codigo aplicado en `public/assets/js/custom/apps/erp/catalogo/configuracion.js`:

- La pantalla se divide en modulos navegables:
  - Catalogos maestros;
  - Calidad;
  - Clasificacion;
  - Clasificacion heredada;
  - Reglas operativas;
  - Proveedor y costos.
- Al abrir Configuracion entra en `Catalogos maestros`.
- Cada modulo carga sus datos solo cuando se activa.
- El boton `Nuevo registro` queda visible solo en `Catalogos maestros`.
- Se pueden abrir modulos directos con hashes como `#config-maestros`, `#config-calidad` o `#config-clasificacion`.
- La pestaña Categorias explica que la clasificacion heredada solo ayuda a construir el arbol maestro ERP.
- La tabla de Categorias muestra raiz/nivel, productos, hijas y si la categoria es operativa, estructural o legado.

Esto reduce peticiones iniciales sin modificar endpoints ni persistencia.

## Protecciones aplicadas en catalogos maestros

Cambio de codigo aplicado en `app/modelos/CatalogoErpDatos.php`:

- No permite inactivar marca si tiene productos relacionados.
- No permite inactivar categoria si tiene productos, subcategorias activas o nodos de clasificacion heredada relacionados.
- No permite inactivar unidad si se usa en SKUs, proveedores, componentes de paquete u opciones de paquete.
- No permite inactivar atributo si se usa en SKUs o variantes.

Regla:

- En ERP robusto, un catalogo maestro usado no se borra ni se inactiva sin reasignar primero sus relaciones.
- La baja logica debe ser segura y trazable.

## Orden de implementacion recomendado

1. Usar el gestor de imagenes ya habilitado en Catalogos maestros para completar logos/iconos/portadas de marcas y categorias.
2. Mantener Proveedor/Costos como puente transitorio, no como responsabilidad principal de Catalogo.
3. Documentar instrucciones para otros modulos que consuman categoria principal, atributos, unidades o clasificacion heredada.
4. Tratar paquetes configurables en su propio plan y autorizacion.
5. No aplicar DDL adicional sin respaldo externo y autorizacion explicita.

## UI segura de imagenes preparada

Fecha: 2026-06-29

- La vista de Configuracion ya incluye modal para administrar imagenes de marcas y categorias.
- El backend ya expone endpoints de listar, guardar y desactivar imagenes maestras.
- Las tablas `erp_catalogo_marca_imagenes` y `erp_catalogo_categoria_imagenes` ya existen.
- El guardado real queda disponible; si en otro entorno faltan las tablas, la UI vuelve a modo informativo y no permite guardar.

## UX de categorias maestras aplicada

Fecha: 2026-06-29

- La pestaña de Categorias incluye filtros por tipo, uso operativo y estatus.
- La pestaña de Categorias incluye resumen clicable para priorizar limpieza de categorias operativas, estructurales, usadas, vacias e inactivas.
- Marcas, unidades y atributos muestran resumen compacto de totales y señales relevantes para captura.
- Las acciones masivas de categorias quedan ocultas cuando el usuario trabaja en Marcas, Unidades o Atributos.
- El selector de categoria padre evita elegir la misma categoria o un descendiente al editar.
- Esto no cambia reglas de negocio ni esquema; solo reduce ruido operativo al sanear catalogos maestros.

## Criterio de cierre

- Configuracion no debe saturar la carga inicial.
- Catalogo debe poder crear y editar categorias maestras como `Acuario`.
- Producto debe tener categoria principal clara.
- La clasificacion heredada debe quedar entendida como insumo para categorias maestras, no como modulo comercial final.
- Marcas/categorias con imagen deben tener estructura propia y trazable.
- Los modulos consumidores deben seguir `docs/erp_catalogo_handoff_modulos.md` antes de duplicar decisiones de Catalogo.
- Ninguna migracion de BD debe aplicarse sin respaldo externo y autorizacion.

## Auditoria read-only de limpieza operativa

Fecha: 2026-06-29

Script creado:

- `storage/uat/uat_catalogo_configuracion_limpieza_readonly.php`

Contrato:

- Solo lectura.
- No ejecuta DDL.
- No actualiza productos.
- No borra ni archiva migracion.
- No escribe relaciones, categorias, marcas ni proveedores.

Resultado de ejecucion:

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
- Tablas de imagenes de marca/categoria: existentes despues del DDL acotado aplicado el 2026-06-29.

Prioridad operativa:

1. Resolver productos sin categoria principal, porque afecta Garantias, Ventas, reportes y navegacion.
2. Resolver SKUs sin proveedor activo; el proveedor preferido ya no aparece como problema masivo.
3. Corregir categorias con texto danado antes de usarlas como reglas amplias.
4. Mantener vinculos ecommerce como trazabilidad historica por ahora; no borrarlos sin plan de archivo.
5. Completar marcas despues de categorias/proveedores, salvo marcas criticas para busqueda o garantia.

## Bandeja de clasificacion pendiente alineada

Fecha: 2026-06-29

Cambios aplicados:

- `CatalogoErpDatos::listarRevisionMetadatosCatalogo()` ahora lista productos sin categoria principal, no solo productos sin cualquier categoria.
- La UI de `Clasificacion pendiente` explica que el pendiente es categoria principal.
- Al guardar una categoria desde la bandeja, el payload envia `forzar_categoria_principal=1` para que el modelo marque esa relacion como principal.
- Se creo UAT read-only:
  - `storage/uat/uat_catalogo_clasificacion_pendiente_readonly.php`.

Validacion:

- `php -l app/modelos/CatalogoErpDatos.php`: sin errores.
- `php -l app/vistas/paginas/apps/erp/catalogo/configuracion.php`: sin errores.
- `node --check public/assets/js/custom/apps/erp/catalogo/configuracion.js`: sin errores.
- UAT read-only:
  - `sin_categoria`: 155;
  - `marcas_ambiguas`: 6.

Decision:

- La bandeja queda lista para trabajar primero el lote de productos sin categoria principal.
- No se aplicaron asignaciones reales durante esta tarea.

## Auditoria read-only de SKUs sin proveedor

Fecha: 2026-06-29

Script creado:

- `storage/uat/uat_catalogo_skus_sin_proveedor_readonly.php`

Resultado:

- SKUs activos sin proveedor: 890.
- Con match exacto en listas de proveedor: 7.
- Sin match exacto en listas de proveedor: 883.
- Categoria con mas pendientes:
  - `Alimentacion / Alimentos`: 165 SKUs sin proveedor.
  - `Habitat y descanso / Camas, casas y refugios`: 89.
  - `(sin categoria principal)`: 88.
  - `Transporte, paseo y entrenamiento / Paseo y sujecion`: 82.
  - `Habitat y descanso / Terrarios y tortugueros`: 59.
- Marca con mas pendientes:
  - `(sin marca)`: 808 SKUs sin proveedor.

Decision:

- No conviene sincronizar proveedores de forma masiva desde listas historicas para este bloque, porque solo 7 SKUs tienen match exacto.
- Usar `Catalogo > Productos` con filtro `SKU sin proveedor` y seleccion masiva cuando el operador sepa que todos los seleccionados comparten proveedor, unidad de compra, factor y minima.
- En Configuracion > Proveedor y costos, usar coincidencias exactas solo para los 7 casos detectados o casos puntuales revisados.
- No usar costo como requisito de Catalogo; la prioridad es relacion proveedor-SKU, unidad de compra y factor.

## Categorias maestras: auditoria y saneamiento propio

Fecha: 2026-06-29

Alcance:

- Se trabajo solo el maestro de categorias.
- No se resolvieron productos, proveedores ni asignaciones producto-categoria.
- Se oculto en UI el boton de `Aplicar relaciones historicas`, porque ese flujo modifica relaciones de productos y no corresponde al CRUD maestro de categorias.

Scripts creados:

- `storage/uat/uat_catalogo_categorias_maestro_readonly.php`
- `storage/uat/uat_catalogo_categorias_texto_reparar_apply.php`

Resultado:

- Categorias totales: 106.
- Categorias maestras: 31.
- Categorias heredadas: 75.
- Categorias activas: 106.
- Padres inexistentes: 0.
- Codigos duplicados: 0.
- Nombres duplicados bajo el mismo padre: 0.
- Rutas inconsistentes: 0.
- Texto danado antes: 20.
- Texto danado despues: 0.

Correccion aplicada:

- Se corrigieron 20 categorias heredadas con texto mojibake en `nombre` y `ruta`.
- La correccion fue deterministica con mapa explicito de secuencias heredadas, por ejemplo `├â┬│` a `ó`, `├â┬í` a `á`, `├â┬¡` a `í`.
- No se modificaron productos, SKUs ni relaciones de categorias.

UI:

- Se agrego filtro `Texto dañado` en Categorias para detectar problemas futuros.
- El resumen de categorias ahora contabiliza registros con texto danado.

Decision:

- El maestro de categorias queda estructuralmente sano para seguir trabajando el CRUD.
- La resolucion de productos sin categoria principal queda fuera de esta tarea y debe hacerse desde la bandeja de clasificacion pendiente o desde Productos, por lotes revisados.

## Marcas, unidades y atributos: auditoria de maestros auxiliares

Fecha: 2026-06-29

Alcance:

- Se auditaron marcas, unidades y atributos como catalogos maestros.
- No se tocaron productos, SKUs ni valores capturados en atributos.

Scripts creados:

- `storage/uat/uat_catalogo_maestros_auxiliares_readonly.php`
- `storage/uat/uat_catalogo_atributos_texto_reparar_apply.php`

Resultado marcas:

- Marcas totales: 42.
- Activas: 42.
- Texto danado: 0.
- Codigos duplicados: 0.
- Nombres duplicados: 0.
- Todas las marcas tienen productos relacionados.

Resultado unidades:

- Unidades totales: 10.
- Activas: 10.
- Texto danado: 0.
- Codigos duplicados: 0.
- Nombres duplicados: 0.
- Sin abreviatura: 0.
- Sin clave SAT: 0.
- Permiten decimales: 7.
- Unidad mas usada: `Pieza`, con 1742 SKUs y 966 relaciones de proveedor.

Resultado atributos:

- Atributos totales: 31.
- Activos: 31.
- Texto danado antes: 5 atributos.
- Texto danado despues: 0.
- Codigos duplicados: 0.
- Listas sin opciones: 0.
- Atributos sin uso: 2.
- Duplicados por nombre pendientes:
  - `Alto`: `ATR-ALTO` y atributo heredado ecommerce.
  - `Ancho`: `ATR-ANCHO` y atributo heredado ecommerce.
  - `Diametro`: `ATR-DIAMETRO` y atributo heredado ecommerce.

Correccion aplicada:

- Se corrigieron nombres danados: `Absorcion`, `Peso maximo`, `Diametro`, `Diseno`.
- No se fusionaron atributos duplicados porque cada atributo puede tener valores relacionados en `erp_catalogo_sku_atributos`.

Decision:

- Marcas y unidades quedan sanas para CRUD operativo.
- Atributos quedan legibles, pero la fusion de duplicados debe tratarse como subtarea propia con migracion controlada:
  1. Elegir atributo canonico.
  2. Migrar valores de SKUs del atributo heredado al canonico.
  3. Resolver conflictos si un SKU tiene ambos valores.
  4. Inactivar el atributo heredado.
  5. Auditar antes/despues.

### Auditoria de fusion posible de atributos duplicados

Fecha: 2026-06-29

Script creado:

- `storage/uat/uat_catalogo_atributos_duplicados_readonly.php`

Resultado read-only:

- `Alto`:
  - canonico `ATR-ALTO`: 609 SKUs;
  - heredado ecommerce: 1 SKU;
  - SKUs con ambos: 0.
- `Ancho`:
  - canonico `ATR-ANCHO`: 572 SKUs;
  - heredado ecommerce: 1 SKU;
  - SKUs con ambos: 0.
- `Diametro`:
  - canonico `ATR-DIAMETRO`: 131 SKUs;
  - heredado ecommerce: 2 SKUs;
  - SKUs con ambos: 0.

Accion aplicada:

- Se creo y ejecuto `storage/uat/uat_catalogo_atributos_duplicados_fusion_apply.php`.
- Se movieron valores heredados sin conflicto:
  - `Alto`: 1 valor movido a `ATR-ALTO`.
  - `Ancho`: 1 valor movido a `ATR-ANCHO`.
  - `Diametro`: 2 valores movidos a `ATR-DIAMETRO`.
- Se inactivaron los 3 atributos heredados sin uso.

Validacion posterior:

- `Alto`: 610 SKUs en canonico, 0 en heredado, 0 conflictos.
- `Ancho`: 573 SKUs en canonico, 0 en heredado, 0 conflictos.
- `Diametro`: 133 SKUs en canonico, 0 en heredado, 0 conflictos.
- Duplicados activos en atributos: 0.

Decision:

- Los duplicados historicos pueden permanecer inactivos como trazabilidad.
- No se borran atributos heredados fisicamente.

## Imagenes de marcas/categorias: preflight y DDL acotado

Fecha: 2026-06-29

Hallazgo:

- El esquema general de Catalogo reporta 6 tablas faltantes:
  - 4 de paquetes;
  - 2 de imagenes de marcas/categorias.
- Por esto no conviene usar el endpoint general `/sistema/esquema_actualizar_catalogo_erp` para autorizar solo imagenes.

Scripts preparados:

- `storage/uat/uat_catalogo_imagenes_autorizacion_preflight_readonly.php`
- `storage/uat/uat_catalogo_esquema_pendientes_readonly.php`
- `storage/uat/uat_catalogo_imagenes_schema_apply_authorized.php`

Decision:

- Imagenes de marcas/categorias debe aplicarse con DDL acotado, no con el plan completo.
- El script autorizado solo crea:
  - `erp_catalogo_marca_imagenes`;
  - `erp_catalogo_categoria_imagenes`.
- Paquetes queda como DDL separado para su propio plan/autorizacion.

Estado:

- Preflight sin respaldo real queda bloqueado.
- La UI/modelo/controlador de imagenes ya estan preparados y operan con candado si las tablas no existen.
- Falta autorizacion fuerte con token `CATALOGO_IMAGENES_MARCAS_CATEGORIAS` y respaldo externo real.

### DDL acotado aplicado

Fecha: 2026-06-29

Autorizacion:

- Token: `CATALOGO_IMAGENES_MARCAS_CATEGORIAS`.
- Respaldo generado fuera del proyecto:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260629_antes_catalogo_imagenes_marcas_categorias.sql`
  - Tamano: 27759132 bytes.

Aplicacion:

- Script ejecutado:
  - `storage/uat/uat_catalogo_imagenes_schema_apply_authorized.php`
- Tablas creadas:
  - `erp_catalogo_marca_imagenes`;
  - `erp_catalogo_categoria_imagenes`.

Validacion posterior:

- `pendientes_imagenes`: 0.
- `erp_catalogo_marca_imagenes`: existe.
- `erp_catalogo_categoria_imagenes`: existe.
- Se creo y ejecuto UAT read-only:
  - `storage/uat/uat_catalogo_imagenes_maestros_readiness_readonly.php`.
- Resultado readiness:
  - `schema_disponible`: true;
  - registros de imagenes de marca: 0;
  - registros de imagenes de categoria: 0;
  - indices de marca: `PRIMARY`, `idx_marca_imagen_marca`, `idx_marca_imagen_tipo`;
  - indices de categoria: `PRIMARY`, `idx_categoria_imagen_categoria`, `idx_categoria_imagen_tipo`.
- El esquema general de Catalogo queda con 4 tablas faltantes, todas de paquetes:
  - `erp_catalogo_sku_paquetes`;
  - `erp_catalogo_sku_paquete_componentes`;
  - `erp_catalogo_sku_paquete_grupos`;
  - `erp_catalogo_sku_paquete_grupo_opciones`.

Decision:

- Imagenes de marcas/categorias queda habilitado a nivel de BD.
- Paquetes sigue fuera de esta autorizacion y debe tratarse en su propio plan.

### UX posterior a imagenes maestras

Fecha: 2026-06-29

Cambio aplicado:

- En `Configuracion > Catalogos maestros > Marcas` se agregaron filtros por busqueda, imagen y estado.
- En `Configuracion > Catalogos maestros > Categorias` se agrego filtro por imagen.
- Los resumenes de marcas/categorias ahora muestran conteo `Con imagen` y `Sin imagen`.
- En categorias, los botones de resumen pueden activar el filtro visual correspondiente.

Decision:

- La imagen de marca/categoria es un atributo visual del maestro, no del producto.
- El saneamiento visual se opera desde Configuracion para no mezclarlo con edicion de productos.
- No se crean imagenes automaticamente ni se relacionan registros sin decision del usuario.
