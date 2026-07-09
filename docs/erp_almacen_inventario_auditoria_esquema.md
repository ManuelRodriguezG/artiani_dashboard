# ERP Almacen e Inventario - Auditoria de esquema

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-18  
Estado: Auditoria de solo lectura, sin migraciones ni DDL.

## Objetivo

Auditar las tablas relacionadas con Almacen, Recepciones e Inventario para separar:

- Nucleo ERP nuevo.
- Tablas legadas o historicas.
- Tablas vacias/residuales.
- Faltantes para un ERP robusto.
- Riesgos antes de proponer migraciones.

No se ejecutaron cambios de esquema ni escrituras de BD.

## Fuentes revisadas

- `app/modelos/AlmacenEsquema.php`
- `app/modelos/Almacenes.php`
- `app/modelos/InventarioErp.php`
- `app/controladores/Almacen.php`
- `app/controladores/Inventario.php`
- `information_schema.TABLES`, `COLUMNS`, `STATISTICS`, `KEY_COLUMN_USAGE`
- Auditor existente `AlmacenEsquema::auditarAlmacenInventario()`

## Resultado ejecutivo

El esquema ERP nuevo de recepciones/inventario ya funciona para el flujo base:

- Orden enviada prepara recepcion.
- Recepcion crea lotes.
- Recepcion crea existencias.
- Recepcion crea movimientos.
- Orden cambia a `parcial` o `recibida`.

Pero para un ERP robusto todavia falta saneamiento de arquitectura:

- El auditor de `AlmacenEsquema` esta incompleto: no declara columnas `id_sku_erp` que el codigo ya usa.
- Casi no hay llaves foraneas reales; solo se detecto una FK en `erp_catalogo_sku_reglas_inventario`.
- Hay tablas antiguas/residuales con datos o nombres similares que pueden confundir el dominio.
- Algunas tablas base (`erp_almacenes`) tienen datos con codificacion mixta y campos operativos nulos.
- Hay historicos utiles de inventario inicial/legacy que no deben borrarse sin estrategia.

## Tablas detectadas

### Nucleo ERP nuevo recomendado

| Tabla | Registros | Rol |
| --- | ---: | --- |
| `erp_almacenes` | 3 | Catalogo de almacenes/destinos fisicos |
| `erp_almacen_recepciones` | 2 | Cabecera de recepciones preparadas desde compras |
| `erp_almacen_recepciones_detalle` | 14 | Partidas inventariables a recibir |
| `erp_almacen_recepciones_lotes` | 2 | Captura fisica recibida por lote/caducidad/ubicacion |
| `erp_almacen_recepciones_incidencias` | 0 | Incidencias de recepcion |
| `erp_almacen_ubicaciones` | 7 | Ubicaciones fisicas por almacen |
| `erp_inventario_existencias` | 2 | Existencia actual por SKU/almacen/lote/ubicacion |
| `erp_inventario_movimientos` | 2 | Kardex/movimientos |
| `erp_inventario_unidades` | 0 | Codigos unicos/series/etiquetas individuales |
| `erp_catalogo_sku_reglas_inventario` | 1743 | Reglas de inventario por SKU |

### Tablas legadas/historicas o candidatas a migracion

| Tabla | Registros | Observacion |
| --- | ---: | --- |
| `erp_ajuste_inventario` | 47 | Ajustes legacy/iniciales, sin indices funcionales aparte de PK |
| `erp_ajuste_inventario_elementos` | 465 | Detalle legacy con `id_ajuste_inventario` como varchar, sin FK |
| `erp_establecimientos_productos_existencias` | 426 | Existencias antiguas por establecimiento/producto |
| `verp_establecimientos_productos_existencias` | vista | Vista legacy relacionada con existencias por establecimiento |

Estas tablas no deben borrarse ahora. Son candidatas a historico, comparativo o migracion controlada hacia `erp_inventario_existencias`/`erp_inventario_movimientos`.

### Tablas vacias/residuales

| Tabla | Registros | Observacion |
| --- | ---: | --- |
| `erp_almacen` | 0 | Parece version singular antigua de `erp_almacenes` |
| `erp_almacenes_copy1` | 0 | Copia vacia/residual |
| `erp_inventario_sicar` | 0 | Tabla vacia, pero existe `public/insercionVisit.log` con inserts SICAR antiguos |
| `erp_productos_combinaciones_almacenes` | 0 | Legacy/residual sin uso actual detectado |
| `erp_sucursales_almacenes` | 0 | Posible relacion futura, hoy vacia |
| `erp_productos_control_inventario` | 0 | Tabla preparada, pero el flujo real usa sobre todo reglas por SKU |

No eliminar sin respaldo externo y autorizacion. Primero deben etiquetarse como `legacy`, `historico`, `staging` o `oficial`.

## Integridad de datos actual

Se revisaron relaciones criticas del nucleo ERP nuevo. No hay orfandades detectadas:

| Revision | Orfandades |
| --- | ---: |
| Recepciones sin orden de compra | 0 |
| Recepciones sin almacen | 0 |
| Detalles sin recepcion | 0 |
| Detalles sin detalle de orden | 0 |
| Detalles con SKU inexistente | 0 |
| Lotes sin recepcion | 0 |
| Lotes sin detalle | 0 |
| Existencias con SKU inexistente | 0 |
| Movimientos con SKU inexistente | 0 |
| Movimientos sin existencia | 0 |
| Ubicaciones sin almacen | 0 |

Estatus actuales:

- Recepciones: 1 `pendiente`, 1 `recibida`.
- Lotes: 2 `disponible`.
- Existencias: 2 `disponible`.
- Movimientos: 2 entradas con `origen_tipo='recepcion_compra'`.

## Auditoria de codificacion 2026-06-18

Estado: solo lectura, sin `UPDATE`.

Se revisaron textos visibles en tablas oficiales usando busqueda binaria de patrones mojibake (`Ã`, `Â`, `├`, `┬`, `ÔÇ`, `â”`, `â┬`, `â├`). El objetivo fue medir alcance antes de proponer correcciones.

| Tabla | Registros sospechosos | Observacion |
| --- | ---: | --- |
| `erp_almacenes` | 1 | `San Jos├â┬® 1727` requiere correccion visual. |
| `erp_catalogo_productos` | 1500 | El problema principal esta en maestros de producto ERP: nombre y descripcion. |
| `erp_catalogo_skus` | 418 | Hay SKUs con nombre mojibake; algunos snapshots heredan ese texto. |
| `erp_almacen_recepciones_detalle` | 3 | Snapshots ya generados con nombres visibles afectados. |
| `erp_almacen_ubicaciones` | 0 | Sin evidencia de mojibake. |
| `erp_inventario_existencias` | 0 | Lotes/ubicaciones de UAT sin mojibake. |

Ejemplos detectados:

- `erp_almacenes.id_almacen=2`: `San Jos├â┬® 1727`.
- `erp_catalogo_productos.id_producto_erp=7`: `Jaula h├â┬ímster residencial rosa`.
- `erp_catalogo_skus.id_sku=7`: `Jaula h├â┬ímster residencial rosa`.
- `erp_almacen_recepciones_detalle.id_recepcion_detalle=14`: `L├â┬ímpara rectangular de tapa 50 x 26 cm`.

Interpretacion:

- La recepcion no esta generando el problema; esta copiando nombres desde catalogo/snapshot.
- El mayor beneficio esta en corregir maestros (`erp_catalogo_productos`, `erp_catalogo_skus`) antes de seguir ampliando pantallas.
- Los snapshots de recepcion deben tratarse con cuidado: corregirlos mejora UX/evidencia visual, pero modifica historico visible. Debe decidirse por bloque y con respaldo.

Recomendacion:

- Preparar un preview de correccion con pares `valor_actual -> valor_propuesto`.
- Corregir primero `erp_almacenes` y una muestra corta de productos/SKUs para validar conversion.
- No aplicar correccion masiva de 1500 productos sin respaldo externo, evidencia previa/posterior y autorizacion.

Preview exportado:

- Archivo: `docs/erp_almacen_codificacion_preview_20260618.csv`
- Total de pares candidatos: 2225.
- Distribucion:
  - `erp_almacenes.almacen`: 1.
  - `erp_almacenes.calle`: 1.
  - `erp_catalogo_productos.nombre`: 305.
  - `erp_catalogo_productos.descripcion`: 1497.
  - `erp_catalogo_skus.nombre`: 418.
  - `erp_almacen_recepciones_detalle.nombre_producto`: 3.

Correccion aplicada en bloque pequeno:

- Fecha: 2026-06-18.
- Respaldo externo: `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260618_almacen_codificacion_erp_almacenes.sql`.
- Alcance: solo `erp_almacenes.id_almacen=2`, columnas `almacen` y `calle`.
- Filas actualizadas: 1.
- Antes:
  - `almacen`: `53616E204A6F73E2949CC3A2E294ACC2AE2031373237`.
  - `calle`: `73616E204A6F73E2949CC3A2E294ACC2AE`.
- Despues:
  - `almacen`: `53616E204A6F73C3A92031373237` (`San José 1727`).
  - `calle`: `73616E204A6F73C3A9` (`san José`).
- Verificacion: busqueda por bytes mojibake en `erp_almacenes.almacen/calle` devuelve 0.

Pendiente despues del bloque pequeno:

- Catalogo ERP maestro: `erp_catalogo_productos` y `erp_catalogo_skus`.
- Snapshots ya generados: `erp_almacen_recepciones_detalle`.
- No aplicar correccion masiva sin nueva autorizacion.
- CSV de pendientes sin `erp_almacenes`: `docs/erp_almacen_codificacion_pendiente_20260618.csv`.
  - `erp_catalogo_productos.nombre`: 305.
  - `erp_catalogo_productos.descripcion`: 1497.
  - `erp_catalogo_skus.nombre`: 418.
  - `erp_almacen_recepciones_detalle.nombre_producto`: 3.

Correccion aplicada en bloque de nombres:

- Fecha: 2026-06-18.
- Respaldo externo: `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260618_almacen_codificacion_catalogo_nombres.sql`.
- Alcance:
  - `erp_catalogo_productos.nombre`: 305.
  - `erp_catalogo_skus.nombre`: 418.
  - `erp_almacen_recepciones_detalle.nombre_producto`: 3.
- Total actualizado: 726.
- Verificacion por bytes mojibake:
  - `erp_catalogo_productos.nombre`: 0.
  - `erp_catalogo_skus.nombre`: 0.
  - `erp_almacen_recepciones_detalle.nombre_producto`: 0.
- Ejemplos con bytes UTF-8 correctos:
  - `hámster`: contiene `C3A1`.
  - `café`: contiene `C3A9`.
  - `Lámpara`: contiene `C3A1`.

Pendiente despues del bloque de nombres:

- `erp_catalogo_productos.descripcion`: 1497 descripciones HTML.
- CSV pendiente: `docs/erp_almacen_codificacion_pendiente_descripciones_20260618.csv`.
- El CSV fue regenerado desde la BD actual despues de corregir nombres.
- No aplicar sin nueva autorizacion porque puede afectar contenido largo/HTML visible en Catalogo o ecommerce.

Correccion aplicada en bloque de descripciones:

- Fecha: 2026-06-18.
- Respaldo externo: `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260618_almacen_codificacion_catalogo_descripciones.sql`.
- Alcance: `erp_catalogo_productos.descripcion`.
- Total actualizado: 1497.
- Verificacion con bytes estructurales de mojibake (`E2949C`, `E294AC`):
  - `erp_catalogo_productos.descripcion`: 0.
  - `erp_catalogo_productos.nombre`: 0.
  - `erp_catalogo_skus.nombre`: 0.
  - `erp_almacen_recepciones_detalle.nombre_producto`: 0.
- Nota: la consola `mysql.exe` puede mostrar acentos validos como ` `, `¢` o `‚`, pero los bytes verificados son UTF-8 correctos (`C3A1`, `C3B3`, `C3A9`).

Conversion candidata validada en muestra:

```php
$propuesto = iconv(
    'UTF-8',
    'ISO-8859-1//IGNORE',
    iconv('UTF-8', 'CP850//IGNORE', $valorActual)
);
```

Muestras:

| Origen | Actual | Propuesto |
| --- | --- | --- |
| `erp_almacenes.almacen#2` | `San Jos├â┬® 1727` | `San José 1727` |
| `erp_catalogo_productos.nombre#7` | `Jaula h├â┬ímster residencial rosa` | `Jaula hámster residencial rosa` |
| `erp_catalogo_productos.nombre#9` | `Jaula h├â┬ímster 3 pisos caf├â┬® 31 x 24 x  43 cm` | `Jaula hámster 3 pisos café 31 x 24 x  43 cm` |
| `erp_almacen_recepciones_detalle.nombre_producto#14` | `L├â┬ímpara rectangular de tapa 50 x 26 cm` | `Lámpara rectangular de tapa 50 x 26 cm` |

Riesgo:

- La conversion debe aplicarse solo a filas/columnas sospechosas, nunca a toda la tabla sin filtro.
- Antes del `UPDATE`, se necesita preview exportable de todos los pares a corregir y respaldo externo.
- En snapshots de recepcion, conviene decidir si se corrige historico visible o solo maestros para nuevas operaciones.

## Hallazgos

### ALM-ESQ-H000 - Reglas de control por lote/caducidad/serie sin activar

Prioridad: Alta para trazabilidad operativa  
Estado: Piloto aplicado; masificacion pendiente de decision/autorizacion

`erp_catalogo_sku_reglas_inventario` existe y tiene 1744 reglas, pero todas tienen apagadas las banderas criticas:

- `requiere_lote=1`: 0.
- `requiere_caducidad=1`: 0.
- `requiere_serie=1`: 0.

Impacto:

- Recepciones no exige lote/caducidad aunque el producto sea alimento, medicamento, suplemento, quimico o producto a granel.
- Inventario puede quedar sin trazabilidad por vencimiento.
- `UAT-ALM-006` queda bloqueado hasta activar un piloto o preparar un folio con reglas reales.

Recomendacion:

- No activar reglas masivas por texto automaticamente.
- Usar un piloto acotado con los 5 SKUs de alimento presentes en `REC-OC-20`.
- Documentar criterio por tipo de producto en `docs/erp_almacen_reglas_inventario_propuesta.md`.

Aplicacion piloto:

- Respaldo externo: `artianilocal_panel_20260619_almacen_reglas_piloto_rec_oc_20.sql`.
- SKUs: `TP-7838`, `TP-7840`, `SFF-03`, `SFF-303`, `TP-40372`.
- Resultado: lote y caducidad activos; serie apagada; estrategia `FEFO`; alerta 90 dias; minimo recepcion 30 dias.
- La aplicacion fue acotada a 5 filas, no a los 593 candidatos textuales.

### ALM-ESQ-H001 - Auditor de Almacen no declara `id_sku_erp`

Prioridad: Alta  
Estado: Cerrado en codigo, sin DDL ejecutado

`Almacenes.php` e `InventarioErp.php` ya trabajan con `id_sku_erp` en recepciones, lotes, existencias y movimientos. Las tablas actuales tambien tienen esas columnas.

Pero `AlmacenEsquema::columnasAlmacenInventario()` no lista `id_sku_erp`, y `planActualizarAlmacenInventario()` no lo crea. Por eso `auditarAlmacenInventario()` puede decir que el esquema esta completo aunque una instalacion limpia quedaria incompleta para el flujo real.

Impacto:

- Una BD nueva o una migracion parcial podria fallar al recibir mercancia.
- El auditor da falso positivo de completitud.
- No queda documentado el contrato actual: inventario debe operar por SKU ERP, no solo por producto legacy.

Recomendacion:

- Actualizar `AlmacenEsquema.php` para declarar `id_sku_erp` en:
  - `erp_almacen_recepciones_detalle`
  - `erp_almacen_recepciones_lotes`
  - `erp_inventario_existencias`
  - `erp_inventario_movimientos`
- Agregar indices por `id_sku_erp` donde falten.
- Mantener `id_producto` como referencia historica/compatibilidad, pero usar SKU ERP como eje operativo.

Correccion aplicada 2026-06-18:

- `AlmacenEsquema.php` ya declara `id_sku_erp` en detalle de recepcion, lotes, existencias y movimientos.
- El plan dry-run ya contempla columnas e indices SKU ERP para instalaciones nuevas o incompletas.
- Se respeto el indice real existente `idx_inventario_existencia_sku_erp`.
- Verificacion: `C:\xampp\php\php.exe -l app\modelos\AlmacenEsquema.php`.
- Verificacion: `AlmacenEsquema::auditarAlmacenInventario()` devuelve `success` sin pendientes.

### ALM-ESQ-H002 - Faltan llaves foraneas en el nucleo de Almacen/Inventario

Prioridad: Alta  
Estado: Cerrado para nucleo actual

`information_schema.KEY_COLUMN_USAGE` muestra una sola FK relacionada: `erp_catalogo_sku_reglas_inventario.id_sku -> erp_catalogo_skus.id_sku`.

El nucleo de recepcion/inventario no tiene FKs reales para:

- Recepcion -> orden de compra.
- Recepcion -> almacen.
- Detalle de recepcion -> recepcion.
- Detalle de recepcion -> detalle de orden.
- Detalle/lote/existencia/movimiento -> SKU ERP.
- Lote -> recepcion/detalle.
- Movimiento -> existencia/lote.
- Ubicacion -> almacen.

Impacto:

- Es posible crear datos inconsistentes desde scripts o errores futuros.
- La integridad depende del modelo PHP y no de la BD.

Recomendacion:

- Antes de agregar FKs, crear plan con respaldo externo, auditoria de orfandades y DDL incremental.
- No aplicar FKs hasta confirmar reglas de borrado: probablemente `RESTRICT` para compras/almacen/inventario y no `CASCADE`.

Aplicacion 2026-06-18:

- Respaldo externo: `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260618_almacen_indices_fks.sql`.
- Auditoria previa de orfandades: 0.
- Indices de soporte aplicados: 5.
- FKs aplicadas: 16.
- Politica usada: `ON UPDATE RESTRICT ON DELETE RESTRICT`.
- Verificacion posterior:
  - Las 16 constraints aparecen en `information_schema.TABLE_CONSTRAINTS`.
  - Orfandades criticas posteriores: 0.
  - Conteos intactos: existencias 2, movimientos 2, lotes 2, ubicaciones 8.
- `AlmacenEsquema::auditarAlmacenInventario()` ahora valida FKs esperadas y devuelve `success` sin pendientes.

### ALM-ESQ-H003 - Tablas legacy de existencias y ajustes conviven con nucleo ERP nuevo

Prioridad: Media  
Estado: Abierto

Existen tablas con inventario historico:

- `erp_ajuste_inventario`
- `erp_ajuste_inventario_elementos`
- `erp_establecimientos_productos_existencias`
- `verp_establecimientos_productos_existencias`

Tienen datos de 2023 y no siguen el contrato ERP nuevo de SKU/almacen/lote/movimiento.

Impacto:

- Pueden confundir reportes o futuras pantallas si se mezclan.
- Pueden contener datos utiles para migracion inicial de existencias.

Recomendacion:

- Clasificarlas como `legacy_historico`.
- No usarlas para nuevas operaciones.
- Crear, en fase futura, un preview de migracion a `erp_inventario_existencias` + `erp_inventario_movimientos` con respaldo externo y autorizacion.

### ALM-ESQ-H004 - Catalogo `erp_almacenes` requiere limpieza de datos base

Prioridad: Media  
Estado: Cerrado para estatus/tipo/codificacion; contacto pendiente si se requiere operativamente

`erp_almacenes` tiene 3 registros, pero campos como `estatus`, `tipo_almacen`, contacto y ubicacion administrativa estan nulos. Tambien hay evidencia de codificacion mixta en textos (`M├...`, `San Jos├...`).

Impacto:

- Puede afectar filtros, permisos por almacen, documentos y UX.
- La codificacion afecta confianza visual.

Recomendacion:

- Definir estatus (`activo`/`inactivo`) y tipo (`principal`, `sucursal`, `transito`, etc.).
- Corregir codificacion con respaldo y prueba controlada.
- Mantener `erp_almacenes` como tabla oficial; no usar `erp_almacen` ni `erp_almacenes_copy1`.

Catalogo recomendado de tipos:

- `principal`: bodega central o matriz.
- `sucursal`: punto fisico operativo con inventario propio.
- `transito`: mercancia en movimiento, no disponible para venta.
- `devoluciones`: mercancia devuelta en evaluacion.
- `merma`: producto no vendible por dano, caducidad o perdida.
- `cuarentena`: recibido pero pendiente de revision/liberacion.

Avance 2026-06-18:

- Codificacion de `erp_almacenes.id_almacen=2` corregida con respaldo externo.
- Los 3 almacenes siguen con `estatus`, `tipo_almacen`, contacto, telefono y email en `NULL`.
- El codigo actual trata `COALESCE(estatus,'activo')` como activo en solicitudes de compra, por lo que cambiar `estatus` a `activo` haria explicita la regla actual.

Propuesta pendiente de decision:

| id_almacen | almacen | estatus propuesto | tipo propuesto |
| ---: | --- | --- | --- |
| 1 | Francisco Javier Mina 1105 | `activo` | `sucursal` |
| 2 | San José 1727 | `activo` | `sucursal` |
| 3 | Francisco Javier Mina 971 | `activo` | `sucursal` |

Recomendacion:

- Aplicar `activo/sucursal` a los 3 almacenes actuales.
- No declarar `principal` hasta confirmar bodega central/matriz.
- Crear almacenes tecnicos separados para `transito`, `devoluciones`, `merma` o `cuarentena` cuando el flujo los necesite.

No aplicar hasta autorizacion explicita.

Artefacto preparado:

- `docs/erp_almacen_normalizacion_almacenes_propuesta.sql`

Aplicacion 2026-06-18:

- Respaldo externo: `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260618_almacen_normalizacion_almacenes.sql`.
- `erp_almacenes.id_almacen IN (1,2,3)` actualizado a:
  - `estatus='activo'`
  - `tipo_almacen='sucursal'`
- Filas actualizadas: 3.
- Validacion:
  - Almacenes activos: 3.
  - Conteo por tipo: `sucursal=3`.
  - Solicitudes con almacen destino invalido: 0.
  - Ordenes con almacen destino invalido: 0.

Pendiente opcional:

- Completar contacto, telefono y email de recepcion si se van a imprimir o usar en ordenes/documentos.

### ALM-ESQ-H005 - Falta tabla de alcance por almacen para usuarios/roles

Prioridad: Media  
Estado: Abierto

La documentacion de seguridad ya indica que almacen/inventario debe planear alcance por almacen en tabla separada, no como campo simple del usuario.

Impacto:

- Hoy un usuario con permiso `almacen.recibir` podria operar cualquier almacen visible.
- Para multi-sucursal real se necesita alcance por almacen.

Recomendacion:

- Diseñar `erp_almacen_usuarios_alcance` o equivalente:
  - `id_usuario`
  - `id_almacen`
  - permisos operativos (`ver`, `recibir`, `ajustar`, `traspasar`, etc.) o alcance por modulo
  - estatus
  - auditoria basica

## Modelo robusto recomendado

### Catalogos

- `erp_almacenes`: catalogo oficial de almacenes.
- `erp_almacen_ubicaciones`: ubicaciones por almacen.
- `erp_catalogo_sku_reglas_inventario`: reglas oficiales por SKU.
- `erp_productos_control_inventario`: conservar solo si se define como fallback por producto; si no, mover a legacy o simplificar.

### Recepcion

- `erp_almacen_recepciones`: cabecera por orden enviada.
- `erp_almacen_recepciones_detalle`: snapshot de partidas inventariables.
- `erp_almacen_recepciones_lotes`: capturas fisicas confirmadas.
- `erp_almacen_recepciones_incidencias`: faltantes, excedentes, caducidad, lote, dano, revision.

### Inventario

- `erp_inventario_existencias`: saldo actual por SKU/almacen/lote/caducidad/ubicacion.
- `erp_inventario_movimientos`: kardex inmutable por evento.
- `erp_inventario_unidades`: codigos unicos/series cuando aplique.

### Operacion futura

- Conteos fisicos/ciclicos.
- Ajustes ERP robustos, separados del legacy.
- Traspasos entre almacenes.
- Reservas/apartados por venta.
- Cierre de recepcion y costeo en Finanzas/Costos, sin que Almacen cambie costos comerciales.

## Orden recomendado de trabajo

1. Congelar mapa de tablas: oficial, legacy historico, staging y residual.
2. Corregir contrato de esquema en codigo para que el auditor refleje el flujo real.
3. Auditar y corregir codificacion de textos base antes de fortalecer relaciones.
4. Normalizar catalogo de almacenes y ubicaciones.
5. Crear auditoria extendida de orfandades y consistencia como endpoint/funcion de soporte, sin DDL.
6. Proponer DDL de FKs e indices como plan, sin ejecutar.
7. Generar respaldo externo.
8. Aplicar DDL en bloques pequenos solo con autorizacion.
9. Crear preview de migracion de existencias legacy si se decide usar datos 2023.
10. Implementar alcance por almacen para usuarios/roles.

## Roadmap recomendado

### Fase 0 - Reglas de trabajo

Estado: Activa

- No borrar, renombrar ni truncar tablas antiguas sin respaldo externo y autorizacion.
- No usar tablas legacy para nuevas pantallas.
- No aplicar DDL sin auditoria previa de orfandades.
- Todo cambio de BD debe tener respaldo externo, evidencia antes/despues y folio de tarea.
- El flujo nuevo debe usar SKU ERP como eje operativo.

### Fase 1 - Mapa oficial vs legacy

Objetivo:

- Que el proyecto sepa que tablas son oficiales y cuales son historicas.

Tareas:

- Marcar como oficiales: `erp_almacenes`, recepciones, lotes, ubicaciones, existencias, movimientos, unidades y reglas SKU.
- Marcar como legacy historico: `erp_ajuste_inventario`, `erp_ajuste_inventario_elementos`, `erp_establecimientos_productos_existencias`, `verp_establecimientos_productos_existencias`.
- Marcar como residual/vacia pendiente de decision: `erp_almacen`, `erp_almacenes_copy1`, `erp_inventario_sicar`, `erp_productos_combinaciones_almacenes`, `erp_sucursales_almacenes`.
- Documentar que `erp_inventario_sicar` no es fuente oficial; existe evidencia en `public/insercionVisit.log`, pero la tabla actual esta vacia.

Criterio de salida:

- Ningun controlador/modelo nuevo debe consultar tablas legacy salvo pantalla de auditoria/migracion.

### Fase 2 - Codificacion y datos base

Objetivo:

- Corregir nombres visibles antes de construir reglas fuertes.

Problema observado:

- Hay textos con mojibake en almacenes y productos, por ejemplo `M├...`, `San Jos├...`, `L├...`.
- En algunos casos el SKU tiene nombre correcto y el producto no, lo que indica mezcla de origen/codificacion.

Tareas:

- Crear auditoria de textos sospechosos en tablas oficiales:
  - `erp_almacenes`
  - `erp_almacen_ubicaciones`
  - `erp_catalogo_productos`
  - `erp_catalogo_skus`
  - snapshots de recepcion ya generados
- Generar reporte de candidatos, sin corregir automaticamente.
- Definir si se corrige dato maestro y se dejan snapshots historicos como estaban, o si tambien se corrigen snapshots visibles.
- Hacer respaldo externo antes de cualquier `UPDATE`.
- Aplicar correcciones en bloques pequenos y verificar con SELECT.

Criterio de salida:

- Almacenes activos y SKUs principales se ven correctamente en pantalla.

### Fase 3 - Catalogo de almacenes y ubicaciones

Objetivo:

- Tener almacenes y ubicaciones listos para operacion real.

Tareas:

- Normalizar `erp_almacenes.estatus` a `activo`/`inactivo`.
- Definir `tipo_almacen`: principal, sucursal, transito, devoluciones, merma u otros que confirmes.
- Completar estado/municipio/contacto si sera util para compras/recepcion.
- Revisar ubicaciones generadas automaticamente por recepcion (`UAT-ALM-REC-24`) y decidir formato final.
- Definir si ubicacion es obligatoria para todas las recepciones o solo para almacenes con control fino.

Criterio de salida:

- Almacen destino de compras esta activo, visible y consistente.
- Recepcion puede capturar ubicacion con reglas claras.

### Fase 4 - Contrato de esquema ERP

Objetivo:

- Que `AlmacenEsquema` represente exactamente lo que el codigo necesita.

Avance:

- `id_sku_erp` ya fue agregado al contrato de esquema en codigo para recepciones, lotes, existencias y movimientos.
- El auditor vuelve a marcar `success`.

Tareas siguientes:

- Agregar auditoria extendida para:
  - columnas extra importantes no declaradas;
  - indices con nombre distinto;
  - tipos incompatibles;
  - tablas legacy candidatas.
- Separar auditoria simple de esquema vs auditoria arquitectonica.

Criterio de salida:

- Una instalacion limpia no queda incompleta para recibir mercancia.

### Fase 5 - Integridad relacional

Objetivo:

- Agregar FKs/indices sin romper datos existentes.

Tareas:

- Repetir auditoria de orfandades justo antes del DDL.
- Confirmar tipos compatibles entre `INT`/`BIGINT`.
- Generar respaldo externo.
- Aplicar llaves foraneas por bloques pequenos.
- Usar `RESTRICT`, no `CASCADE`, salvo decision explicita.

Criterio de salida:

- BD impide datos huerfanos en recepciones, lotes, existencias y movimientos.

### Fase 6 - Migracion legacy controlada

Objetivo:

- Decidir si las existencias/ajustes antiguos se conservan solo historicos o si alimentan inventario inicial.

Tareas:

- Crear preview de migracion desde `erp_establecimientos_productos_existencias`.
- Mapear producto legacy a SKU ERP.
- Mostrar incluidos/excluidos/motivos.
- No aplicar inventario inicial sin autorizacion.
- Si se aplica, crear movimientos tipo `inicial` o `migracion_inicial`, no editar existencias directo sin kardex.

Criterio de salida:

- Inventario nuevo tiene trazabilidad desde recepcion o movimiento inicial.

### Fase 7 - Seguridad por almacen

Objetivo:

- Que permisos de almacen/inventario no sean globales si el negocio opera multiples almacenes.

Tareas:

- Disenar tabla de alcance por usuario/almacen.
- Aplicar alcance en listado de recepciones, existencias, ajustes y traspasos.
- Mantener permisos actuales como permiso de modulo, y alcance como filtro operativo.

Criterio de salida:

- Un usuario de almacen solo ve/opera almacenes permitidos.

### Fase 8 - Operacion avanzada

Objetivo:

- Completar inventario real despues de recepciones base.

Tareas:

- Conteos fisicos/ciclicos.
- Ajustes ERP nuevos con kardex.
- Traspasos entre almacenes.
- Reservas/apartados por ventas.
- Mermas/devoluciones.
- Series/codigos unicos cuando aplique.
- Caducidades y alertas FEFO.

Criterio de salida:

- Inventario puede sostener compras, ventas y auditoria operativa.

## Propuesta de robustecimiento futuro

No ejecutar sin respaldo externo, auditoria de orfandades inmediatamente previa y autorizacion.

### Indices

El auditor corregido queda completo contra la BD actual. Para instalaciones limpias o incompletas, el plan debe asegurar:

- `erp_almacen_recepciones_detalle.idx_recepcion_detalle_sku_erp(id_sku_erp)`.
- `erp_almacen_recepciones_lotes.idx_recepcion_lote_sku_erp(id_sku_erp)`.
- `erp_inventario_movimientos.idx_inventario_mov_sku_erp(id_sku_erp)`.
- `erp_inventario_existencias.idx_inventario_existencia_sku_erp(id_sku_erp)`.
- `erp_inventario_existencias.idx_existencia_producto_lote_ubicacion(id_producto,id_sku_erp,id_almacen_clave,lote_clave,fecha_caducidad_clave,ubicacion_clave)`.

Auditoria extendida 2026-06-18:

- Tipos compatibles detectados para FKs principales:
  - `INT`: recepciones, almacenes, ordenes, detalles de orden, lotes, existencias.
  - `BIGINT`: `id_sku_erp` contra `erp_catalogo_skus.id_sku`.
- Orfandades actuales: 0 en recepciones, detalle, lotes, existencias, movimientos y ubicaciones.
- Antes de FKs faltan o conviene agregar indices de soporte:
  - `erp_almacen_recepciones_detalle.id_orden_compra_detalle`.
  - `erp_almacen_recepciones_lotes.id_almacen`.
  - `erp_inventario_existencias.id_almacen_clave` como indice directo para FK a almacen.
  - `erp_inventario_movimientos.id_existencia_inventario`.
  - `erp_inventario_movimientos.id_recepcion_lote`.

No se ejecuto DDL.

Artefacto preparado:

- `docs/erp_almacen_inventario_ddl_indices_fks_propuesto.sql`
- Validacion de nombres: no hay constraints ni indices existentes con los nombres propuestos.

### Llaves foraneas candidatas

Regla sugerida: usar `RESTRICT` para evitar borrar historial operativo por accidente. No usar `CASCADE` en compras, recepciones, existencias ni movimientos salvo decision explicita.

| Tabla | Columna | Referencia | Politica sugerida |
| --- | --- | --- | --- |
| `erp_almacen_recepciones` | `id_orden_compra` | `erp_compras_ordenes.id_orden_compra` | RESTRICT |
| `erp_almacen_recepciones` | `id_almacen` | `erp_almacenes.id_almacen` | RESTRICT |
| `erp_almacen_recepciones_detalle` | `id_recepcion_almacen` | `erp_almacen_recepciones.id_recepcion_almacen` | RESTRICT |
| `erp_almacen_recepciones_detalle` | `id_orden_compra_detalle` | `erp_compras_ordenes_detalle.id_detalle` | RESTRICT |
| `erp_almacen_recepciones_detalle` | `id_sku_erp` | `erp_catalogo_skus.id_sku` | RESTRICT |
| `erp_almacen_recepciones_lotes` | `id_recepcion_almacen` | `erp_almacen_recepciones.id_recepcion_almacen` | RESTRICT |
| `erp_almacen_recepciones_lotes` | `id_recepcion_detalle` | `erp_almacen_recepciones_detalle.id_recepcion_detalle` | RESTRICT |
| `erp_almacen_recepciones_lotes` | `id_sku_erp` | `erp_catalogo_skus.id_sku` | RESTRICT |
| `erp_inventario_existencias` | `id_sku_erp` | `erp_catalogo_skus.id_sku` | RESTRICT |
| `erp_inventario_existencias` | `id_almacen_clave` | `erp_almacenes.id_almacen` | RESTRICT |
| `erp_inventario_movimientos` | `id_sku_erp` | `erp_catalogo_skus.id_sku` | RESTRICT |
| `erp_inventario_movimientos` | `id_existencia_inventario` | `erp_inventario_existencias.id_existencia_inventario` | RESTRICT |
| `erp_inventario_movimientos` | `id_recepcion_lote` | `erp_almacen_recepciones_lotes.id_recepcion_lote` | RESTRICT |
| `erp_almacen_ubicaciones` | `id_almacen_clave` | `erp_almacenes.id_almacen` | RESTRICT |

### Auditoria previa obligatoria para FKs

Antes de cualquier DDL:

- Repetir conteos de orfandades.
- Confirmar tipos exactos compatibles (`INT` vs `BIGINT`) entre columnas origen/destino.
- Revisar nombres de indices existentes para no duplicar.
- Generar respaldo externo.
- Aplicar una FK por bloque y verificar.

## No hacer todavia

- No borrar tablas legacy.
- No renombrar tablas.
- No agregar FKs sin respaldo y auditoria de orfandades inmediatamente antes.
- No mezclar `erp_establecimientos_productos_existencias` con `erp_inventario_existencias` en pantallas nuevas.
- No usar `erp_inventario_sicar` como fuente oficial sin proceso de staging/preview.
