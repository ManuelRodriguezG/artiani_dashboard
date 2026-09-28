# ERP Catalogo - Plan de atributos comparables

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-27  
Proyecto vigente: `C:\xampp\htdocs\panel_de_control`  
Modulo: ERP > Catalogo > Productos > Atributos  
Estado: auditoria y propuesta; sin cambios de codigo, sin DDL y sin escrituras de datos

## Proposito

Ordenar el manejo de atributos tecnicos de productos para que dejen de depender de texto libre ambiguo y puedan usarse despues en ecommerce, filtros, comparacion de productos, recomendaciones y agentes de atencion.

Este plan no cambia precios, costos, inventario, compras, ventas, almacen ni ecommerce. Catalogo conserva la responsabilidad de identidad, clasificacion y ficha tecnica del producto/SKU.

## Auditoria ejecutada

Se revisaron los documentos vivos de Catalogo, el controlador, modelo, esquema, vistas y JavaScript del modulo Productos/Configuracion.

Tambien se ejecutaron auditorias existentes en modo read-only:

- `storage/uat/uat_catalogo_atributos_base_readonly.php`
- `storage/uat/uat_catalogo_atributos_canonicos_readonly.php`
- `storage/uat/uat_catalogo_atributos_equipo_filtracion_readonly.php`
- `storage/uat/uat_catalogo_atributos_caudal_readonly.php`

No se ejecuto DDL, migracion, limpieza ni escritura masiva.

## Estado actual de implementacion

### Tablas actuales

La estructura actual de atributos vive principalmente en:

- `erp_catalogo_atributos`
  - `id_atributo_erp`
  - `codigo`
  - `nombre`
  - `tipo_dato`
  - `unidad`
  - `configuracion_json`
  - `es_variante`
  - `estatus`
- `erp_catalogo_sku_atributos`
  - `id_sku_atributo`
  - `id_sku`
  - `id_atributo_erp`
  - `valor`
  - `fecha_registro`
  - `fecha_actualizacion`
- `erp_catalogo_unidades`
  - unidades operativas para compra, venta e inventario; no son todavia un catalogo formal de unidades de atributo.

### Separacion actual entre variantes y atributos

La separacion existe por `erp_catalogo_atributos.es_variante`:

- `es_variante=1`: atributos que distinguen SKUs vendibles relacionados, por ejemplo color, talla, presentacion o dimensiones cuando se usan para diferenciar variantes.
- `es_variante=0`: atributos tecnicos de ficha, comparacion o filtros futuros, por ejemplo caudal, consumo electrico, altura maxima o contenido.

Ambos tipos usan la misma tabla de valores `erp_catalogo_sku_atributos`.

### UI actual

En el modal de producto:

- La pestana `Variantes` permite capturar atributos diferenciadores por SKU.
- La pestana `Atributos` permite capturar atributos tecnicos por SKU.
- Configuracion > Atributos permite administrar atributos base con tipo simple, unidad descriptiva, lista de opciones y bandera de variante.

### Limitacion principal

El valor actual de un atributo se guarda en un solo campo `valor VARCHAR(500)`.

Esto permite captura simple, pero limita:

- medidas compuestas;
- rangos min/max;
- unidades convertibles;
- valores numericos consultables con precision;
- filtros eficientes;
- validacion por categoria;
- trazabilidad de fuente/evidencia;
- diferenciar atributo de producto maestro contra atributo de SKU.

## Datos actuales relevantes

Auditoria read-only del 2026-09-27:

- Atributos auditados: 36.
- Canonicos de equipo existentes: `consumo_electrico`, `caudal`, `altura_maxima`, `capacidad_acuario_min`, `capacidad_acuario_max`.
- `consumo_electrico`: 61 usos.
- `altura_maxima`: 10 usos.
- `caudal`: 3 usos actuales; auditoria detecta 39 insertables adicionales con evidencia explicita.
- `Largo`: 686 usos.
- `Alto`: 611 usos.
- `Ancho`: 574 usos.
- `Diametro`: 133 usos.
- `Medidas`: 890 usos heredados.
- `Contenido`: 341 usos heredados.
- `Capacidad`: 62 usos heredados.
- `Calibre`: 44 usos.
- Errores ortograficos activos detectados: `Contendio`, `Contennido`, `Cotenido`, `Longuitd`, `Longuitud`.
- Atributos heredados `Potencia` y `Subida` ya estan inactivos, pero conservan usos historicos; sus canonicos ya existen.

## Diagnostico

### Lo que ya esta bien encaminado

- Ya existe una tabla de atributos base.
- Ya existe tabla de valores por SKU.
- Ya existe separacion UI entre `Variantes` y `Atributos`.
- Ya existen atributos canonicos importantes para equipo y filtracion.
- Ya existen dimensiones separadas `Largo`, `Ancho`, `Alto` y `Diametro`.
- Ya hay historial/documentacion que prohibe inventar atributos desde texto ambiguo.

### Lo que falta robustecer

- `unidad` del atributo es texto libre; no hay conversion formal ni catalogo separado de unidades tecnicas.
- `tipo_dato` no incluye `decimal`, `seleccion_multiple`, `medida_dimension` ni `rango`.
- No hay ambito formal: producto maestro, SKU, variante, ecommerce/comercial.
- No hay reglas por categoria para sugerir atributos obligatorios o recomendados.
- No hay grupo de atributo para ordenar ficha tecnica.
- No hay bandera formal de `filtrable`, `comparable` o `visible_ecommerce`.
- No hay origen/evidencia por valor aceptado.
- No hay estructura nativa para largo/ancho/alto como atributo compuesto; hoy conviene usarlos como atributos separados.

## Decision de arquitectura recomendada

Mantener la regla actual:

- `Variantes` distingue SKUs vendibles.
- `Atributos` describe o compara el producto/SKU.

No mezclar variantes y atributos tecnicos aunque compartan tabla fisica.

Ejemplo:

- Color usado para SKU rojo/azul vendible: variante.
- Color descriptivo de un accesorio sin SKU separado: atributo tecnico/comercial.
- Largo/ancho/alto que distinguen SKUs de peceras por tamano: pueden ser variante.
- Largo/ancho/alto para ficha de un SKU unico: atributo tecnico.

## Modelo robusto propuesto

### Catalogo de atributos base

Ampliar conceptualmente `erp_catalogo_atributos` para que cada atributo tenga:

- codigo canonico;
- nombre visible;
- tipo de dato;
- unidad esperada;
- grupo;
- ambito recomendado;
- banderas de uso;
- configuracion JSON para opciones, precision, min/max y reglas.

Tipos recomendados:

- `texto`
- `numero`
- `decimal`
- `booleano`
- `seleccion_unica`
- `seleccion_multiple`
- `medida_dimension`
- `rango`

### Ambito recomendado

Agregar o modelar:

- `producto_maestro`: dato comun de todo el producto.
- `sku`: dato propio del SKU vendible/inventariable.
- `variante`: diferencia SKUs relacionados.
- `ecommerce_comercial`: dato util para publicacion/filtros sin alterar operacion.

Primera recomendacion operativa: guardar atributos tecnicos a nivel SKU, como hoy, y permitir "aplicar a todos los SKUs" desde UI cuando sea un dato comun.

### Unidades de atributo

No reutilizar automaticamente unidades operativas de compra/inventario.

Unidades tecnicas iniciales:

- longitud: `mm`, `cm`, `m`
- masa/contenido: `mg`, `g`, `kg`
- volumen: `ml`, `l`
- rendimiento: `l/h`, `gph`
- electricidad: `w`, `v`, `hz`
- porcentaje: `%`
- unidad discreta: `pza`

Mientras no exista DDL, conservar la unidad en `erp_catalogo_atributos.unidad`, pero tratarla como vocabulario controlado desde Configuracion.

### Medidas como largo/ancho/alto

No crear un unico campo libre `medidas` para captura nueva.

Para dimensiones fisicas usar atributos separados:

- `largo` + unidad `cm`
- `ancho` + unidad `cm`
- `alto` + unidad `cm`
- `diametro` + unidad `cm`
- `grosor` o `grosor_vidrio` + unidad `mm`

El atributo heredado `Medidas` debe quedar como fuente historica y migrarse solo cuando el patron sea claro.

Ejemplo:

- `30 x 20 x 40 cm` puede sugerir:
  - largo = 30 cm
  - ancho = 20 cm
  - alto = 40 cm
- `diametro 25 cm` puede sugerir `diametro = 25 cm`.
- `medidas con mueble` no debe partirse sin revisar contexto.

### Rangos

Para rangos recomendados usar dos atributos simples mientras no exista tipo compuesto:

- `capacidad_acuario_min` + `l`
- `capacidad_acuario_max` + `l`
- `cuello_min` / `cuello_max`
- `pecho_min` / `pecho_max`

Esto es mas consultable que un texto `20-50 L`.

## Atributos base iniciales recomendados

### Generales

- `color`
- `talla`
- `tamano`
- `material`
- `modelo`
- `compatibilidad`
- `incluye`
- `especie_recomendada`
- `etapa_vida`
- `uso_recomendado`

### Dimensiones

- `largo`
- `ancho`
- `alto`
- `diametro`
- `grosor`
- `grosor_vidrio`
- `peso_producto`
- `peso_maximo_soportado`
- `capacidad_volumen`

### Acuario, filtracion y equipo

- `caudal`
- `consumo_electrico`
- `voltaje`
- `frecuencia`
- `altura_maxima`
- `capacidad_acuario_min`
- `capacidad_acuario_max`
- `litros_aproximados`
- `tipo_filtracion`
- `tipo_agua`
- `repuesto_compatible`

### Peceras, terrarios y habitats

- `litros_aproximados`
- `grosor_vidrio`
- `tipo_tapa`
- `incluye_mueble`
- `incluye_luz`
- `incluye_filtro`
- `tipo_habitat`
- `material`

### Alimentos y consumibles

- `peso_contenido`
- `volumen_contenido`
- `contenido_piezas`
- `tipo_alimento`
- `especie_objetivo`
- `etapa_vida`
- `formato`
- `sabor`
- `tamano_grano`
- `proteina`
- `grasa`
- `fibra`

### Accesorios, camas, correas y ropa

- `talla`
- `color`
- `material`
- `cuello_min`
- `cuello_max`
- `pecho_min`
- `pecho_max`
- `largo_mascota`
- `lavable`
- `impermeable`

### Reptiles, terrarios e impresion 3D

- `compatibilidad`
- `material`
- `color`
- `medidas` separadas en largo/ancho/alto cuando aplique
- `uso_recomendado`
- `resistencia_temperatura` si existe evidencia real

## Sugerencias por categoria

Las categorias deben ayudar a sugerir atributos, no forzar captura ciega.

Ejemplos:

- Filtros/bombas: `caudal`, `consumo_electrico`, `altura_maxima`, `capacidad_acuario_min/max`, `tipo_filtracion`.
- Peceras/acuario: `largo`, `ancho`, `alto`, `litros_aproximados`, `grosor_vidrio`.
- Alimento: `especie_objetivo`, `etapa_vida`, `formato`, `peso_contenido`, `tipo_alimento`.
- Accesorios: `material`, `color`, `largo`, `ancho`, `alto`.
- Reptiles/terrarios: `compatibilidad`, `tipo_habitat`, `material`, `largo`, `ancho`, `alto`.
- Impresion 3D: `material`, `color`, `medidas`, `uso_recomendado`.

## UX/UI propuesta

Mantener la pestana `Atributos`, pero hacerla mas guiada:

1. Panel compacto de atributos capturados.
2. Boton `Agregar atributo`.
3. Selector con busqueda de atributo existente.
4. Bloque de `Sugeridos por categoria`.
5. Para atributos numericos, input numerico con unidad visible.
6. Para rangos, dos campos min/max con la misma unidad.
7. Para dimensiones, grupo visual `Medidas` con campos `Largo`, `Ancho`, `Alto`, `Diametro`, `Grosor`.
8. Accion `Aplicar a todos los SKUs` cuando el producto tiene varias variantes.
9. Advertencia cuando el usuario escriba un atributo nuevo parecido a uno existente.
10. En Configuracion > Atributos, mostrar tipo, unidad, grupo, uso y si es variante.

No saturar el modal principal: los atributos sugeridos deben aparecer colapsados o como chips accionables.

## Que se puede hacer sin DDL

- Documentar vocabulario canonico y reglas por categoria.
- Mejorar textos de la pestana Atributos.
- Restringir altas manuales para evitar nombres duplicados obvios.
- Usar `configuracion_json` para guardar metadatos no criticos:
  - `grupo`
  - `ambito_sugerido`
  - `filtrable`
  - `comparable`
  - `visible_ecommerce`
  - `unidad_permitida`
  - `precision`
  - `sinonimos`
- Crear auditorias read-only para `Medidas`, `Contenido`, `Capacidad`, `Calibre/Grosor`.
- Preparar propuestas SQL idempotentes, sin aplicarlas.
- Mostrar atributos sugeridos por categoria con una matriz fija en codigo o JSON local.

## Que requiere autorizacion fuerte

Requiere autorizacion explicita, respaldo externo y plan de antes/despues:

- DDL para ampliar `erp_catalogo_atributos`.
- DDL para tabla de unidades de atributo.
- DDL para reglas atributo-categoria.
- DDL para valores estructurados por tipo.
- Cualquier migracion de `Medidas`, `Contenido`, `Capacidad` o atributos mal escritos.
- Inactivar atributos con uso historico.
- Aplicar caudales detectados como insertables.

## DDL propuesto, no aplicado

### Opcion A - evolucion compatible minima

Agregar columnas a `erp_catalogo_atributos`:

```sql
ALTER TABLE erp_catalogo_atributos
  ADD COLUMN grupo VARCHAR(60) NULL AFTER unidad,
  ADD COLUMN ambito VARCHAR(30) NOT NULL DEFAULT 'sku' AFTER grupo,
  ADD COLUMN filtrable TINYINT(1) NOT NULL DEFAULT 0 AFTER ambito,
  ADD COLUMN comparable TINYINT(1) NOT NULL DEFAULT 0 AFTER filtrable,
  ADD COLUMN visible_ecommerce TINYINT(1) NOT NULL DEFAULT 0 AFTER comparable,
  ADD COLUMN orden INT NOT NULL DEFAULT 0 AFTER visible_ecommerce,
  ADD KEY idx_catalogo_atributo_grupo (grupo),
  ADD KEY idx_catalogo_atributo_ambito (ambito);
```

Ventaja: menor impacto.  
Limite: los valores siguen en `VARCHAR(500)`.

### Opcion B - reglas por categoria

```sql
CREATE TABLE erp_catalogo_categoria_atributos (
  id_categoria_atributo BIGINT NOT NULL AUTO_INCREMENT,
  id_categoria_erp INT NOT NULL,
  id_atributo_erp INT NOT NULL,
  requerido TINYINT(1) NOT NULL DEFAULT 0,
  sugerido TINYINT(1) NOT NULL DEFAULT 1,
  filtrable TINYINT(1) NOT NULL DEFAULT 0,
  comparable TINYINT(1) NOT NULL DEFAULT 0,
  visible_ecommerce TINYINT(1) NOT NULL DEFAULT 0,
  orden INT NOT NULL DEFAULT 0,
  estatus VARCHAR(20) NOT NULL DEFAULT 'activo',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_categoria_atributo),
  UNIQUE KEY idx_categoria_atributo (id_categoria_erp, id_atributo_erp),
  KEY idx_categoria_atributo_atributo (id_atributo_erp)
);
```

Ventaja: habilita sugerencias por categoria.  
Limite: no resuelve todavia valores compuestos.

### Opcion C - valores estructurados

Crear tabla complementaria:

```sql
CREATE TABLE erp_catalogo_sku_atributos_valores (
  id_valor BIGINT NOT NULL AUTO_INCREMENT,
  id_sku BIGINT NOT NULL,
  id_atributo_erp INT NOT NULL,
  valor_texto VARCHAR(500) NULL,
  valor_numero DECIMAL(18,6) NULL,
  valor_booleano TINYINT(1) NULL,
  unidad VARCHAR(30) NULL,
  valor_min DECIMAL(18,6) NULL,
  valor_max DECIMAL(18,6) NULL,
  origen VARCHAR(40) NOT NULL DEFAULT 'manual',
  evidencia_texto VARCHAR(500) NULL,
  estatus VARCHAR(20) NOT NULL DEFAULT 'activo',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_valor),
  UNIQUE KEY idx_sku_atributo_valor_activo (id_sku, id_atributo_erp, estatus),
  KEY idx_atributo_numero (id_atributo_erp, valor_numero),
  KEY idx_atributo_rango (id_atributo_erp, valor_min, valor_max)
);
```

Ventaja: permite filtros numericos y evidencia.  
Limite: requiere migracion controlada y convivencia con la tabla actual.

## Prioridad de cambios

### Prioridad 1 - Sin DDL

1. Confirmar vocabulario canonico y nombres visibles.
2. Auditar `Medidas` con extractor read-only para patrones largo/ancho/alto.
3. Auditar `Contenido` por unidad detectada.
4. Auditar `Capacidad` por categoria.
5. Mejorar UI para agregar atributos existentes y evitar duplicados.
6. Documentar matriz atributo-categoria inicial.

### Prioridad 2 - DDL ligero

1. Agregar metadatos a `erp_catalogo_atributos`.
2. Crear reglas por categoria.
3. Ajustar Configuracion para editar grupo, ambito, filtrable, comparable y visible ecommerce.
4. Ajustar Productos para mostrar sugeridos por categoria.

### Prioridad 3 - Valores estructurados

1. Crear tabla complementaria de valores tipados.
2. Migrar atributos numericos de alta confianza.
3. Dejar compatibilidad con `erp_catalogo_sku_atributos.valor`.
4. Crear filtros internos por atributos.
5. Habilitar cola de sugerencias desde descripcion o proveedor.

## Plan inmediato recomendado

Antes de tocar codigo:

1. Revisar este documento con el dueno del proyecto.
2. Autorizar si el primer paso sera solo mejora UI sin DDL o si se prepara DDL ligero.
3. Confirmar si dimensiones `Largo`, `Ancho`, `Alto` deben seguir marcadas como variantes cuando ya se usan para diferenciar SKUs, o si se quiere una separacion adicional para ficha tecnica.
4. Confirmar si se permite usar `configuracion_json` como puente temporal para metadatos de atributo.
5. Preparar extractor read-only especifico para `Medidas`, sin aplicar migracion.

## Autorizaciones requeridas

No se requiere autorizacion para:

- auditorias read-only;
- documentacion;
- propuestas SQL no ejecutadas;
- cambios UI sin escritura masiva.

Si se decide avanzar con DDL o migracion, se requiere:

- respaldo externo en `C:\xampp\panel_db_backups`;
- token explicito del dueno;
- comando exacto;
- evidencia antes/despues;
- actualizacion de este documento y de `docs/erp_catalogo_avance.md`.

## Handoff / continuidad

Fecha: 2026-09-27

- Contexto actual: atributos tecnicos ya existen en el modal y estan separados de variantes por `es_variante`, pero la estructura es insuficiente para medidas compuestas, rangos y filtros robustos.
- Decision recomendada: conservar la separacion conceptual variantes vs atributos y evolucionar atributos hacia vocabulario canonico, sugerencias por categoria y valores estructurados.
- Pendiente inmediato: extractor read-only de `Medidas` para proponer largo/ancho/alto/diametro/grosor con evidencia.
- No hacer todavia: DDL, migracion de valores, inactivacion de atributos usados o escritura masiva sin autorizacion.
- Impacta a futuro: ecommerce, catalogos comerciales, busqueda, comparadores y agente asesor. No impacta precios, costos, inventario ni ventas en esta fase.

## Implementacion puente 2026-09-27 - Captura rapida de medidas

Decision:

- No crear atributos duplicados como `Largo tecnico`, `Ancho tecnico` o `Alto tecnico`.
- Reutilizar los atributos canonicos existentes `ATR-LARGO`, `ATR-ANCHO` y `ATR-ALTO`.
- Aunque esos atributos esten marcados como variante por el modelo actual, se permiten tambien en la pestana `Atributos` porque son dimensiones fisicas comparables.
- La captura rapida guarda cada dimension por SKU usando el endpoint existente `guardar_atributos_tecnicos`.
- La unidad visible de esta captura es `cm`.

Cambios aplicados:

- `CatalogoErpDatos::guardarAtributosTecnicosProducto()` permite dimensiones canonicas aunque `es_variante=1`.
- `CatalogoErpDatos::consultarAtributosTecnicosProducto()` incluye dimensiones canonicas en la ficha tecnica.
- `productos.php` agrega bloque `Medidas` dentro de la pestana `Atributos`.
- `productos.js` renderiza y guarda largo/ancho/alto por SKU.

Limites:

- Es una solucion compatible sin DDL.
- Las dimensiones canonicas pueden seguir apareciendo en la matriz de variantes cuando se usan para diferenciar SKUs.
- La solucion robusta futura sigue siendo modelar ambito/uso del atributo de forma mas rica que el booleano `es_variante`.
