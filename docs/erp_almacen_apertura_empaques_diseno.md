# ERP Almacen - Apertura de empaques cerrados

Fecha: 2026-07-25

Modulo: ERP > Almacen > Apertura de empaques

Clave de trabajo: `ALM-APE-001`

## Objetivo

Disenar el flujo operativo para abrir una unidad cerrada comprada al proveedor y habilitar sus piezas internas como SKUs vendibles en tienda fisica, conservando lote, caducidad, costo y trazabilidad.

Caso base:

- SKU cerrado: `USA654` - bote cerrado de premios para gato.
- Contenido fisico: `50` piezas individuales.
- Salidas vendibles: SKUs de sabor, por ejemplo `USA654-TUNA`, `USA654-TUNACLAM`, `USA654-TUNACRAB`, `USA654-TUNASCALLOP`.
- Operacion esperada: seleccionar un bote fisico especifico, abrirlo, capturar cuantas piezas salen de cada sabor y registrar:
  - salida de `1` unidad cerrada `USA654`;
  - entradas por sabor con el mismo folio `APE-*`;
  - mismo lote/caducidad heredado del bote, salvo decision documentada distinta.

## Decision operativa

Este flujo no es Compras, no es Recepcion variable y no es Produccion.

- Compras registra que se compra el bote cerrado como lo vende el proveedor.
- Recepcion ingresa existencia real del bote cerrado.
- Inventario inicial puede cargar botes cerrados ya existentes y, si ya habia piezas sueltas antes del ERP, tambien puede cargar esas piezas sueltas con folio de inventario inicial.
- Almacen/Apertura convierte un bote cerrado real en piezas sueltas reales.
- Inventario solo registra saldos y kardex.
- POS vendera los SKUs sueltos cuando exista stock.
- Ecommerce no debe vender piezas sueltas de este caso en esta fase; solo el bote cerrado si el negocio decide publicarlo.

Termino recomendado para UI: `Apertura de empaque`.

Motivo: describe mejor la accion que "preparacion", porque el producto ya viene fabricado y empacado individualmente; Almacen solo abre el contenedor comercial y pone sus unidades internas disponibles para venta suelta.

## Auditoria del sistema actual

### Flujo reutilizable

El flujo `Preparacion/Empaque` ya cuenta con estructura y reglas utiles:

- `erp_almacen_preparaciones`: encabezado con folio, almacen, SKU origen, existencia origen, unidad fisica origen y estatus.
- `erp_almacen_preparacion_consumos`: linea de consumo con existencia, unidad fisica, lote, caducidad, cantidad y costo.
- `erp_almacen_preparacion_resultados`: resultado con SKU destino, existencia destino, unidades, costo y movimiento de entrada.
- `erp_inventario_movimientos`: soporta salida y entrada con el mismo folio mediante `origen_tipo`, `origen_id`, `origen_detalle_id`.
- `erp_inventario_unidades`: soporta unidad fisica origen y estados `cerrada`, `abierta`, `consumida`.

El codigo actual de `Almacenes::confirmar_preparacion()` ya hace una transaccion completa:

- valida almacen de preparacion;
- bloquea existencia/unidad origen;
- registra consumo;
- aplica salida;
- actualiza unidad fisica origen;
- crea o actualiza existencia destino;
- registra resultado;
- aplica entrada;
- genera etiquetas si la regla del SKU destino lo requiere.

### Brecha detectada

El flujo actual fue disenado para una sola salida por operacion:

- el encabezado `erp_almacen_preparaciones.id_sku_presentacion` obliga un SKU resultado principal;
- `guardar_borrador_preparacion()` recibe una sola regla o transformacion;
- `confirmar_preparacion()` calcula una sola cantidad de salida y una sola entrada;
- la UI selecciona una sola presentacion.

Para `USA654`, una apertura correcta puede producir varias salidas en el mismo folio, una por sabor. Hacer cuatro preparaciones separadas no es correcto, porque consumirian el mismo bote varias veces o partirian la trazabilidad real.

## Hallazgos

| ID | Severidad | Hallazgo | Recomendacion |
| --- | --- | --- | --- |
| `ALM-APE-H001` | Alta | `Preparacion/Empaque` actual no soporta multi-salida desde una sola unidad cerrada. | Crear modo/flujo de apertura con varias lineas resultado bajo un solo folio. |
| `ALM-APE-H002` | Alta | `USA654` y sus SKUs de sabor existen, pero no hay receta/relacion configurada en `erp_catalogo_sku_paquetes` ni transformaciones. | En Catalogo definir relacion del SKU cerrado con componentes/sabores antes del UAT. |
| `ALM-APE-H003` | Media | No hay existencias actuales de `USA654` ni sabores en la BD revisada. | Para UAT se requiere inventario inicial o recepcion real/controlada de botes cerrados. |
| `ALM-APE-H004` | Media | La tabla de resultados puede almacenar varias lineas, pero el encabezado y el codigo asumen una sola salida. | Reutilizar tablas con columnas adicionales o crear tablas especificas de apertura para evitar ambiguedad. |
| `ALM-APE-H005` | Media | Las piezas sueltas normalmente no requieren etiqueta interna individual. | Generar etiquetas solo si Catalogo/Inventario lo exige; por defecto la entrada por sabor suma existencia sin crear 50 etiquetas. |

## Modelo recomendado

### Catalogo

Configurar `USA654` como SKU cerrado inventariable y vendible segun decision comercial.

Configurar cada sabor como SKU inventariable y vendible en POS:

- `USA654-TUNA`
- `USA654-TUNACLAM`
- `USA654-TUNACRAB`
- `USA654-TUNASCALLOP`

Registrar una receta de apertura usando las tablas de paquetes/componentes o una estructura equivalente:

- paquete/contenedor: `USA654`;
- componentes: SKUs de sabor;
- cantidad total esperada: `50`;
- si el surtido es fijo, guardar cantidades por sabor;
- si el surtido puede variar, permitir captura manual y validar total esperado.

### Almacen

Crear pantalla o modo: `Almacen > Apertura de empaques`.

La apertura no debe limitarse a bodegas. Debe poder ocurrir en cualquier ubicacion operativa autorizada:

- tienda fisica;
- bodega/almacen;
- punto de mayoreo;
- ubicacion mixta.

Regla recomendada: agregar permiso operativo por almacen/sucursal `permite_apertura_empaque`.

Motivo:

- `permite_preparacion` sirve para embolsar, cortar, reempacar o preparar producto internamente.
- `permite_apertura_empaque` sirve para abrir una unidad cerrada comprada y vender piezas internas.
- Una tienda puede abrir botes para venta suelta sin ser area de preparacion.
- Una bodega podria abrir empaques si despues vende mayoreo o habilita piezas individuales.
- POS no debe abrir automaticamente; solo vende el stock suelto que Almacen/Apertura ya genero.

Flujo:

1. Seleccionar almacen.
2. Buscar SKU cerrado con configuracion de apertura.
3. Seleccionar existencia fisica especifica:
   - lote;
   - caducidad;
   - ubicacion;
   - codigo de existencia;
   - etiqueta/unidad fisica si existe.
4. Mostrar componentes/sabores esperados.
5. Capturar cantidades reales por sabor.
6. Validar que el total sea mayor a cero y, cuando aplique, igual a la capacidad esperada.
7. Guardar borrador.
8. Confirmar con transaccion:
   - salida de `1` unidad del SKU cerrado;
   - una entrada por cada SKU sabor con cantidad mayor a cero;
   - mismo folio `APE-*`;
   - herencia de lote/caducidad/ubicacion operativa.

### Inventario

Inventario debe ver:

- `USA654` cerrado disminuido en `1`.
- Cada sabor incrementado por su cantidad real.
- Kardex con salida y entradas enlazadas por `origen_tipo='apertura_empaque'`.
- Trazabilidad desde cada sabor al bote origen.

## DDL propuesto pendiente

No aplicar sin respaldo externo y autorizacion.

Agregar bandera operativa a almacenes:

```sql
ALTER TABLE erp_almacenes
  ADD COLUMN permite_apertura_empaque TINYINT(1) NOT NULL DEFAULT 0 AFTER permite_preparacion;
```

Opcion conservadora: extender las tablas actuales de preparacion para soportar tipo de operacion.

```sql
ALTER TABLE erp_almacen_preparaciones
  ADD COLUMN tipo_operacion VARCHAR(40) NOT NULL DEFAULT 'preparacion_presentacion' AFTER folio,
  ADD COLUMN total_resultados INT NOT NULL DEFAULT 1 AFTER unidades_preparadas,
  ADD COLUMN cantidad_resultado_total DECIMAL(18,6) NOT NULL DEFAULT 0 AFTER total_resultados;

ALTER TABLE erp_almacen_preparacion_resultados
  ADD COLUMN orden_resultado INT NOT NULL DEFAULT 0 AFTER id_sku_presentacion,
  ADD COLUMN id_sku_componente BIGINT NULL AFTER id_sku_presentacion,
  ADD COLUMN cantidad_esperada DECIMAL(18,6) NULL AFTER unidades_preparadas,
  ADD COLUMN cantidad_real DECIMAL(18,6) NULL AFTER cantidad_esperada;
```

Notas:

- `tipo_operacion='apertura_empaque'` distinguiria este flujo de `preparacion_presentacion`.
- Para compatibilidad, `id_sku_presentacion` puede seguir guardando el SKU resultado de cada linea.
- `id_sku_componente` puede apuntar al componente de la receta si se usa `erp_catalogo_sku_paquete_componentes`.
- El encabezado actual obliga `id_sku_presentacion NOT NULL`; para apertura multi-salida conviene cambiarlo a NULL o guardar un SKU resultado principal solo como compatibilidad. Esto requiere evaluar impacto antes de DDL.

Opcion mas limpia: crear tablas especificas.

```sql
CREATE TABLE erp_almacen_aperturas_empaque (
  id_apertura_empaque INT NOT NULL AUTO_INCREMENT,
  folio VARCHAR(60) NOT NULL,
  id_almacen INT NOT NULL,
  id_sku_origen BIGINT NOT NULL,
  id_existencia_origen INT NOT NULL,
  id_unidad_origen INT NULL,
  id_paquete BIGINT NULL,
  cantidad_origen DECIMAL(18,6) NOT NULL DEFAULT 1,
  cantidad_resultado_total DECIMAL(18,6) NOT NULL DEFAULT 0,
  estatus VARCHAR(30) NOT NULL DEFAULT 'borrador',
  lote VARCHAR(150) NULL,
  fecha_caducidad DATE NULL,
  observaciones TEXT NULL,
  creado_por INT NULL,
  confirmado_por INT NULL,
  fecha_apertura DATETIME NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_apertura_empaque),
  UNIQUE KEY uk_almacen_apertura_folio (folio),
  KEY idx_almacen_apertura_almacen (id_almacen),
  KEY idx_almacen_apertura_sku_origen (id_sku_origen),
  KEY idx_almacen_apertura_existencia (id_existencia_origen),
  KEY idx_almacen_apertura_unidad (id_unidad_origen),
  KEY idx_almacen_apertura_estatus (estatus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE erp_almacen_apertura_resultados (
  id_apertura_resultado INT NOT NULL AUTO_INCREMENT,
  id_apertura_empaque INT NOT NULL,
  id_sku_resultado BIGINT NOT NULL,
  id_existencia_inventario INT NULL,
  id_componente BIGINT NULL,
  cantidad_esperada DECIMAL(18,6) NULL,
  cantidad_real DECIMAL(18,6) NOT NULL,
  costo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0,
  costo_total DECIMAL(12,4) NOT NULL DEFAULT 0,
  id_movimiento_entrada INT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_apertura_resultado),
  KEY idx_apertura_resultado_apertura (id_apertura_empaque),
  KEY idx_apertura_resultado_sku (id_sku_resultado),
  KEY idx_apertura_resultado_existencia (id_existencia_inventario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

Recomendacion tecnica: usar la opcion limpia si vamos a construir pantalla propia. Evita forzar `preparaciones` a cargar con campos de compatibilidad que no representan bien multi-salida.

## Configuracion actual observada

Consulta solo lectura del 2026-07-25:

| Almacen | Tipo | Venta | Recepcion | Preparacion | Estatus | Lectura para apertura |
| --- | --- | ---: | ---: | ---: | --- | --- |
| `ACUARIO967` | `punto_venta` | 1 | 1 | 0 | activo | Candidato para apertura si vende piezas sueltas. |
| `MASCOTAS971` | `punto_venta` | 1 | 1 | 0 | activo | Candidato para apertura si vende piezas sueltas. |
| `BOD971` | `bodega` | 0 | 1 | 1 | activo | Candidato si tambien abrira empaques para mayoreo o surtido interno. |
| `MINA1105-BAJA` | `sucursal` | 0 | 0 | 0 | inactivo | No operar. |
| `SANJOSE1727-BAJA` | `sucursal` | 0 | 0 | 0 | inactivo | No operar. |

Decision propuesta:

- Activar `permite_apertura_empaque=1` por ubicacion cuando el lugar pueda abrir empaques fisicos.
- No reutilizar automaticamente `permite_preparacion`.
- No exigir `permite_venta=1`, porque una bodega podria abrir empaques para transferirlos despues.
- Validar siempre que la existencia cerrada este fisicamente en el almacen seleccionado.

## Reglas de costo

Primera version:

- costo total consumido = costo unitario del bote cerrado * `1`;
- costo por pieza = costo total consumido / total real de piezas capturadas;
- cada sabor recibe costo unitario proporcional igual, salvo que Catalogo defina ponderacion por componente en el futuro.

Ejemplo:

- bote costo `100`;
- total real `50`;
- costo por pieza `2`;
- `10` piezas de sabor A: costo total `20`;
- `15` piezas de sabor B: costo total `30`.

## UAT propuesto

Clave: `ALM-APE-UAT-001`

Precondiciones:

- `USA654` activo.
- Skus de sabor activos.
- Relacion de apertura configurada.
- Existencia real/controlada de `USA654` en almacen operativo.
- Si se usara unidad fisica/etiqueta, debe existir una unidad cerrada disponible.

Pasos:

1. Entrar a `Almacen > Apertura de empaques`.
2. Seleccionar almacen operativo.
3. Seleccionar `USA654`.
4. Seleccionar bote especifico por lote/caducidad/unidad.
5. Capturar cantidades por sabor hasta total `50`.
6. Guardar borrador.
7. Confirmar apertura.
8. Validar kardex:
   - salida `1` de `USA654`;
   - entradas por cada sabor;
   - mismo folio `APE-*`;
   - origen `apertura_empaque`.
9. Validar existencias por sabor.
10. Validar que POS podra vender solo las piezas con stock, sin abrir botes automaticamente.

## Orden recomendado de implementacion

1. Catalogo: confirmar relacion `USA654` -> sabores y si las cantidades son fijas o capturables.
2. Almacen: elegir DDL final para apertura multi-salida. Completado: se recomienda estructura propia.
3. Almacen: agregar permiso operativo `permite_apertura_empaque`. Preparado en auditoria/plan, pendiente DDL.
4. Almacen: crear auditoria de esquema en `AlmacenEsquema`. Preparado.
5. Almacen: pantalla independiente `Apertura de empaques`. Preparada en modo lectura/bloqueo.
6. Autorizacion: respaldo externo previo a DDL.
7. Almacen: aplicar DDL.
8. Almacen: endpoints de catalogo de apertura, existencias origen, guardar borrador y confirmar.
9. Inventario: mostrar trazabilidad `apertura_empaque` en existencias/kardex/etiquetado si aplica.
10. UAT controlado con folio `APE-*`.
11. Handoff a POS para vender piezas sueltas ya existentes.

## Avance tecnico 2026-07-25

Implementado sin escrituras de BD:

- `AlmacenEsquema` audita:
  - columna `erp_almacenes.permite_apertura_empaque`;
  - tabla `erp_almacen_aperturas_empaque`;
  - tabla `erp_almacen_apertura_resultados`;
  - indices y FKs de apertura.
- `Almacenes` lee `permite_apertura_empaque` de forma compatible:
  - si la columna no existe, devuelve `0 AS permite_apertura_empaque`;
  - el guardado de configuracion ignora el flag hasta que exista la columna.
- `Almacen::apertura_empaques()` expone vista independiente.
- Sidebar agrega `Almacen > Apertura de empaques`.
- Vista `app/vistas/paginas/apps/erp/almacen/apertura_empaques.php` queda en modo preparacion/bloqueo.
- JS `public/assets/js/custom/apps/erp/almacen/apertura_empaques/apertura_empaques.js` carga almacenes y muestra si la ubicacion tiene apertura autorizada.
- Configuracion de almacenes muestra check `Apertura empaque`, preparado para persistir despues del DDL.

Validaciones:

- `C:\xampp\php\php.exe -l app/controladores/Almacen.php`: OK.
- `C:\xampp\php\php.exe -l app/modelos/AlmacenEsquema.php`: OK.
- `C:\xampp\php\php.exe -l app/modelos/Almacenes.php`: OK.
- `C:\xampp\php\php.exe -l app/vistas/paginas/apps/erp/almacen/configuracion.php`: OK.
- `C:\xampp\php\php.exe -l app/vistas/paginas/apps/erp/almacen/apertura_empaques.php`: OK.
- `node --check public/assets/js/custom/apps/erp/almacen/configuracion.js`: OK.
- `node --check public/assets/js/custom/apps/erp/almacen/apertura_empaques/apertura_empaques.js`: OK.
- `Almacenes::obtener_almacenes()` devuelve 3 almacenes activos y campo compatible `permite_apertura_empaque=0`.
- Auditoria `AlmacenEsquema::auditarAlmacenInventario()` devuelve warning esperado por DDL pendiente.

Bloqueo sano:

- No se puede persistir `permite_apertura_empaque`.
- No se pueden crear folios `APE-*`.
- No se pueden confirmar aperturas ni afectar inventario.
- Siguiente paso requiere respaldo externo y autorizacion para aplicar DDL.

## Evidencia DDL ALM-APE-001

Fecha: 2026-07-25

Autorizacion:

- Usuario indico continuar despues de presentar que el siguiente paso requeria respaldo externo y DDL.

Respaldo externo previo:

- `storage/backups/artianilocal_alm_ape_001_20260725_antes_ddl.sql`
- Tamano: `32823995` bytes.

DDL aplicado:

- `docs/erp_almacen_apertura_empaques_propuesta.sql`

Estructura creada:

- Columna `erp_almacenes.permite_apertura_empaque`.
- Tabla `erp_almacen_aperturas_empaque`.
- Tabla `erp_almacen_apertura_resultados`.
- FKs verificadas:
  - `fk_almacen_apertura_almacen`.
  - `fk_almacen_apertura_sku_origen`.
  - `fk_almacen_apertura_existencia`.
  - `fk_almacen_apertura_paquete`.
  - `fk_apertura_resultado_apertura`.
  - `fk_apertura_resultado_sku`.
  - `fk_apertura_resultado_componente`.
  - `fk_apertura_resultado_existencia`.
  - `fk_apertura_resultado_almacen`.

Auditoria posterior:

- `AlmacenEsquema::auditarAlmacenInventario()` devuelve `success`.
- `tiene_pendientes=false`.

Ubicaciones despues del DDL:

| Ubicacion | Apertura empaque |
| --- | ---: |
| `ACUARIO967` | 0 |
| `MASCOTAS971` | 0 |
| `BOD971` | 0 |

No se activo ninguna ubicacion porque eso es decision operativa pendiente.

## Avance endpoints lectura ALM-APE-001

Fecha: 2026-07-25

Implementado:

- `Almacenes::consultar_skus_apertura_empaque()`.
- `Almacenes::consultar_receta_apertura_empaque()`.
- `Almacenes::consultar_existencias_apertura_empaque()`.
- `Almacenes::consultar_aperturas_empaque()`.
- `/almacen/apertura_skus_erp`.
- `/almacen/apertura_receta_erp`.
- `/almacen/apertura_existencias_erp`.
- `/almacen/aperturas_empaque_erp`.
- Vista `Apertura de empaques` conectada a endpoints de lectura.

Validaciones:

- `C:\xampp\php\php.exe -l app/controladores/Almacen.php`: OK.
- `C:\xampp\php\php.exe -l app/modelos/Almacenes.php`: OK.
- `C:\xampp\php\php.exe -l app/vistas/paginas/apps/erp/almacen/apertura_empaques.php`: OK.
- `node --check public/assets/js/custom/apps/erp/almacen/apertura_empaques/apertura_empaques.js`: OK.
- `consultar_skus_apertura_empaque()`: `error=false`, `total=0`.
- `consultar_aperturas_empaque()`: `error=false`, `total=0`.

Lectura del resultado:

- No hay SKUs abribles porque Catalogo aun no tiene receta `USA654 -> sabores` marcada como desarmable/aperturable.
- No hay aperturas porque todavia no se han creado folios `APE-*`.
- No se implemento confirmacion ni afectacion de inventario en esta etapa.

## Avance escritura controlada ALM-APE-001

Fecha: 2026-07-25

Implementado:

- `Almacenes::guardar_borrador_apertura_empaque()`.
- `Almacenes::confirmar_apertura_empaque()`.
- `/almacen/apertura_guardar_borrador_erp`.
- `/almacen/apertura_confirmar_erp`.
- UI con selector de ubicacion, SKU cerrado, unidad fisica origen y cantidades reales por componente.
- Botones de guardar/confirmar bloqueados si falta ubicacion autorizada, receta, existencia o unidad origen.
- Auditoria explicita para guardar y confirmar apertura.
- Kardex esperado al confirmar:
  - salida de `1` unidad del SKU cerrado;
  - entradas por cada SKU interno con cantidad real mayor a cero;
  - `origen_tipo='apertura_empaque'`;
  - mismo folio `APE-*`;
  - lote/caducidad/ubicacion heredados de la existencia cerrada.

Validaciones:

- `C:\xampp\php\php.exe -l app/controladores/Almacen.php`: OK.
- `C:\xampp\php\php.exe -l app/modelos/Almacenes.php`: OK.
- `C:\xampp\php\php.exe -l app/vistas/paginas/apps/erp/almacen/apertura_empaques.php`: OK.
- `node --check public/assets/js/custom/apps/erp/almacen/apertura_empaques/apertura_empaques.js`: OK.

No ejecutado todavia:

- UAT real con folio `APE-*`.
- Activacion de `permite_apertura_empaque` en una ubicacion.
- Configuracion de receta `USA654 -> sabores`.
- Creacion/recepcion/inventario inicial de existencia cerrada `USA654`.

Motivo: esas acciones ya escriben datos operativos y requieren decision/configuracion del dueno.

## Pendientes antes de UAT real

- Confirmar si el bote `USA654` trae siempre las mismas cantidades por sabor o si el operador debe capturar cantidades reales.
- Confirmar si piezas sueltas requieren etiqueta interna. Recomendacion inicial: no.
- Confirmar si la apertura ocurre en almacen trasero o en tienda fisica. Recomendacion: permitir ambos solo si el almacen/sucursal tiene permiso de apertura.
- Configurar en Catalogo la receta de apertura del bote cerrado a sabores.
- Activar `permite_apertura_empaque=1` solo en la ubicacion donde se probara.
- Tener una existencia cerrada real/controlada de `USA654` con lote/caducidad.
- Ejecutar UAT con un bote y validar kardex/existencias antes de usarlo en operacion diaria.

## Auditoria USA654 para UAT ALM-APE-001

Fecha: 2026-07-25

Consulta solo lectura:

| SKU | Nombre | Unidad | Estatus | Lectura |
| --- | --- | --- | --- | --- |
| `USA654` | Churu premios para gato | `PAQ` | activo | SKU cerrado correcto para recibir/comprar/vender bote completo si se decide. |
| `USA654-TUNA` | Churu premios para gato receta de atun | `PZA` | activo | SKU interno vendible por pieza/sabor. |
| `USA654-TUNACLAM` | Churu premios para gato receta de atun con sabor a almeja | `PZA` | activo | SKU interno vendible por pieza/sabor. |
| `USA654-TUNACRAB` | Churu premios para gato receta de atun con sabor a cangrejo | `PZA` | activo | SKU interno vendible por pieza/sabor. |
| `USA654-TUNASCALLOP` | Churu premios para gato receta de atun con vieras | `PZA` | activo | SKU interno vendible por pieza/sabor. |

Estado operativo detectado:

- No existe paquete/receta activa para `USA654` en `erp_catalogo_sku_paquetes`.
- No existen componentes activos `USA654 -> sabores`.
- No existen existencias actuales para `USA654%` en `erp_inventario_existencias`.
- No existen folios `APE-*` creados.
- Ninguna ubicacion activa tiene `permite_apertura_empaque=1`.

Ubicaciones activas:

| Codigo | Ubicacion | Tipo | Venta | Recepcion | Preparacion | Apertura |
| --- | --- | --- | ---: | ---: | ---: | ---: |
| `ACUARIO967` | Francisco Javier Mina 967 - Acuario | punto_venta | 1 | 1 | 0 | 0 |
| `BOD971` | Francisco Javier Mina 971 - Bodega trasera | bodega | 0 | 1 | 1 | 0 |
| `MASCOTAS971` | Francisco Javier Mina 971 - Mascotas frontal | punto_venta | 1 | 1 | 0 | 0 |

Interpretacion:

- La estructura tecnica de Almacen ya puede abrir empaques, pero el caso `USA654` aun no esta listo para UAT real.
- El primer desbloqueo debe ocurrir en Catalogo: registrar la receta de apertura con `USA654` como SKU paquete y los cuatro sabores como componentes.
- Despues debe existir una unidad cerrada real, por recepcion o inventario inicial.
- Finalmente se activa `Apertura empaque` solo en la ubicacion donde se hara la prueba.

Checklist recomendado para primera prueba:

1. Catalogo: crear paquete `USA654` con `permite_desarmar=1`.
2. Catalogo: agregar componentes de sabor; si el contenido fijo suma 50, capturar cantidades esperadas; si puede variar, capturar cantidades esperadas como referencia y permitir ajuste real en apertura.
3. Inventario inicial o Recepcion: registrar al menos `1 PAQ` cerrado de `USA654`, con lote/caducidad si aplica.
4. Almacen/Configuracion: activar `Apertura empaque` en la ubicacion exacta donde se abrira el bote.
5. Almacen/Apertura: seleccionar ubicacion, `USA654`, unidad cerrada real, capturar piezas por sabor y guardar borrador.
6. Confirmar folio `APE-*`.
7. Inventario: validar salida de `1 PAQ` cerrado y entradas por cada sabor en `PZA`.
8. POS: solo despues de inventario validado, probar venta de pieza suelta por sabor.

## Preflight readonly ALM-APE-001

Fecha: 2026-07-25

Script:

- `storage/uat/uat_almacen_apertura_empaques_preflight_readonly.php`

Comando:

```powershell
C:\xampp\php\php.exe storage\uat\uat_almacen_apertura_empaques_preflight_readonly.php --sku=USA654
```

Contrato:

- `read_only=true`.
- No crea recetas.
- No habilita almacenes.
- No crea existencias.
- No confirma aperturas.

Resultado actual para `USA654`:

- `sku_encontrado=true`.
- `componentes_candidatos=4`.
- `receta_existe=false`.
- `ubicaciones_con_apertura=0`.
- `existencias_cerradas=0`.
- `folios_ape=0`.

Bloqueos actuales:

- No existe receta de paquete para el SKU cerrado.
- Ninguna ubicacion activa tiene Apertura empaque habilitada.
- No existe stock cerrado del SKU para abrir.

## Instrucciones exactas por modulo para desbloquear UAT

### Catalogo ERP

Objetivo: definir la receta maestra, no crear stock.

1. Abrir el producto/SKU `USA654`.
2. Entrar a la pestana `Paquetes`.
3. Crear o editar receta del SKU paquete:
   - `SKU paquete`: `USA654`.
   - `Tipo`: `comprado_cerrado`.
   - `Disponibilidad`: `por_existencia_armada` o `mixto`.
   - Activar `Permite desarmar`.
   - No activar `Requiere armado en almacen` para este caso, porque el bote llega comprado cerrado.
4. Agregar componentes fijos:
   - `USA654-TUNA`, unidad `PZA`, cantidad esperada segun bote.
   - `USA654-TUNACLAM`, unidad `PZA`, cantidad esperada segun bote.
   - `USA654-TUNACRAB`, unidad `PZA`, cantidad esperada segun bote.
   - `USA654-TUNASCALLOP`, unidad `PZA`, cantidad esperada segun bote.
5. Guardar receta.

Nota operativa:

- Si el surtido exacto por sabor puede variar por bote, las cantidades de Catalogo sirven como referencia. Almacen/Apertura capturara las cantidades reales al abrir.
- Si siempre son 50 piezas fijas, la suma esperada debe ser 50.

### Inventario inicial o Recepcion

Objetivo: tener una unidad cerrada fisica real de `USA654`.

1. Si es mercancia ya existente antes del ERP: cargar por Inventario inicial.
2. Si entra por compra nueva: recibir desde Almacen/Recepciones.
3. Registrar `USA654` como unidad cerrada en `PAQ`.
4. Capturar lote/caducidad si aplica.
5. Si se genera etiqueta/unidad fisica, debe quedar como unidad cerrada disponible.

### Almacen/Configuracion

Objetivo: habilitar donde se permite abrir empaques.

1. Entrar a `Almacen > Configuracion`.
2. Elegir la ubicacion donde se hara la apertura real.
3. Activar `Apertura empaque`.
4. Guardar.

Recomendacion inicial:

- Si la apertura se hara para venta suelta en tienda de mascotas, activar primero `MASCOTAS971`.
- Si la apertura se hara en bodega para surtir despues, activar `BOD971`.
- No activar todas las ubicaciones hasta probar el flujo.

### Almacen/Apertura de empaques

Objetivo: crear el primer folio `APE-*`.

1. Entrar a `Almacen > Apertura de empaques`.
2. Seleccionar la ubicacion habilitada.
3. Seleccionar `USA654`.
4. Seleccionar la unidad cerrada real por existencia/lote/caducidad.
5. Capturar cantidades reales por sabor.
6. Guardar borrador.
7. Confirmar solo si las cantidades coinciden fisicamente con lo abierto.

### Inventario

Objetivo: validar trazabilidad.

1. Confirmar que `USA654` disminuyo `1 PAQ`.
2. Confirmar entradas de los SKUs de sabor en `PZA`.
3. Revisar kardex con `origen_tipo=apertura_empaque`.
4. Validar que todas las entradas comparten folio `APE-*`.

### POS

Objetivo: vender solo stock real.

1. No tocar POS antes de validar inventario.
2. Una vez confirmada la apertura, POS debe vender los SKUs de sabor como piezas normales.
3. POS no debe abrir botes automaticamente.

## Cierre tecnico 2026-07-28 - enlace APE con regla de Catalogo

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-28

Se cerro la brecha detectada entre Catalogo y Almacen para Apertura de empaques:

- Catalogo define la regla en `erp_catalogo_sku_aperturas_empaque`.
- Almacen persiste la regla usada en cada folio APE mediante `erp_almacen_aperturas_empaque.id_apertura_catalogo`.
- `id_paquete` queda nullable y reservado para el flujo historico de paquetes/componentes; apertura cerrado -> granel usa `id_apertura_catalogo`.
- Se agrego FK `fk_almacen_apertura_catalogo` hacia `erp_catalogo_sku_aperturas_empaque(id_apertura_empaque)`.
- `AlmacenEsquema` ya audita `erp_almacen_aperturas_empaque` y `erp_almacen_apertura_resultados` con columnas, indices y FKs esperadas.

Respaldo externo previo a DDL:

```txt
C:\xampp\panel_db_backups\artianilocal_panel_20260728_antes_almacen_apertura_catalogo.sql
```

Validacion:

- `id_apertura_catalogo` existe en `erp_almacen_aperturas_empaque`.
- Indice `idx_almacen_apertura_catalogo` existe.
- FK `fk_almacen_apertura_catalogo` existe.
- Auditoria puntual de Apertura: sin columnas, indices ni FKs faltantes.
- No se creo folio APE de prueba desde CLI para no dejar escritura operativa artificial.
