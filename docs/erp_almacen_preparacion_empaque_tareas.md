# ERP Almacen - Preparacion/Empaque tareas vivas

Fecha: 2026-06-20

Modulo: ERP > Almacen > Preparacion/Empaque

Clave base: `ALM-PREP-001`

## Regla de trabajo

- Tareas pequenas.
- UAT documentado.
- Evidencia por folio `PREP-*`.
- Hallazgos con ID.
- Respaldo externo antes de cualquier escritura de BD.
- No mezclar ERP nuevo con legacy.
- No tocar Ventas/ecommerce en esta fase.
- No crear stock desde Catalogo.

## Bloques de implementacion

| ID | Tarea | Resultado esperado | Autorizacion |
| --- | --- | --- | --- |
| `ALM-PREP-T001` | Preparar documentacion viva y referencias | Diseno, DDL propuesto y tareas enlazadas | No |
| `ALM-PREP-T002` | Integrar tablas propuestas en auditoria de `AlmacenEsquema` | El plan dry-run reporta faltantes de preparacion/empaque | No |
| `ALM-PREP-T003` | Revisar DDL contra esquema local real | Confirmar tipos, indices y FKs antes de migrar | No, si es solo lectura |
| `ALM-PREP-T004` | Respaldo externo previo a DDL | Dump externo identificado | Si |
| `ALM-PREP-T005` | Aplicar DDL de preparacion/empaque | Tablas creadas en BD | Si |
| `ALM-PREP-T006` | Crear consultas de catalogos y disponibilidad base | Almacen puede consultar presentaciones preparables y stock base | No, codigo; si requiere pruebas con BD escrita, pedir |
| `ALM-PREP-T007` | Crear guardado de borrador | Preparacion sin afectar inventario | Si aplica escritura de BD |
| `ALM-PREP-T008` | Crear confirmacion transaccional | Salida base + entrada presentacion + etiquetas | Si aplica escritura de BD |
| `ALM-PREP-T009` | Crear UI de Preparacion/Empaque | Pantalla operativa en Almacen | No |
| `ALM-PREP-T010` | Extender Etiquetado para origen preparacion | Etiquetas muestran folio `PREP-*` | No |
| `ALM-PREP-T011` | UAT controlado | Evidencia de folio, kardex y etiquetas | Si hay escritura de BD |
| `ALM-PREP-T012` | Disenar transformaciones/reempaque | Reglas origen -> resultado y existencia origen seleccionable | No |
| `ALM-PREP-T013` | Aplicar DDL de transformaciones | Tabla de transformaciones y columnas origen en preparacion | Si |
| `ALM-PREP-T014` | UI de existencia origen | Operador elige lote/ubicacion/existencia fisica antes de confirmar | No para codigo; si se prueba con escritura, si |
| `ALM-PREP-T015` | UAT reempaque 500g -> 100g | Salida 1 unidad 500g + entrada 5 unidades 100g con trazabilidad | Si |

## Estado actual

- `ALM-PREP-T001`: completada.
- `ALM-PREP-T002`: completada.
- `ALM-PREP-T003`: completada en modo lectura.
- `ALM-PREP-T004`: completada con respaldo externo.
- `ALM-PREP-T005`: completada con DDL aplicado.
- `ALM-PREP-T006`: completada.
- `ALM-PREP-T007`: completada con borrador controlado.
- `ALM-PREP-T008`: completada con confirmacion `PREP-20260622-0001`.
- `ALM-PREP-T009`: completada.
- `ALM-PREP-T010`: completada.
- `ALM-PREP-T011`: completada tecnicamente con kardex y etiquetas; pendiente UAT visual de impresion/pegado.
- `ALM-PREP-T012`: completada en diseno; DDL propuesto en `docs/erp_almacen_preparacion_transformaciones_propuesta.sql`.
- `ALM-PREP-T013`: completada con respaldo externo, DDL aplicado y reglas sembradas.
- `ALM-PREP-T014`: implementada en codigo/UI; pendiente UAT visual.
- `ALM-PREP-T015`: completada con UAT controlado `500GR -> 100GR`.

## Fase nueva: transformaciones/reempaque

Origen:

- Observacion operativa del usuario: una preparacion debe partir de una existencia fisica especifica, no solo de un SKU conceptual.
- Caso clave: consumir `1` unidad de `TP-40372-500GR` para crear `5` unidades de `TP-40372-100GR`.

Hallazgos:

- `ALM-PREP-H010`: falta seleccionar existencia fisica/lote/ubicacion origen en la UI.
- `ALM-PREP-H011`: falta soportar presentacion -> presentacion sin forzar `erp_catalogo_sku_presentaciones`.
- `ALM-PREP-H012`: al consumir por completo una existencia origen, el saldo bajaba a `0` pero el estado podia permanecer `disponible`.

Propuesta:

- Crear `erp_catalogo_sku_transformaciones`.
- Agregar a `erp_almacen_preparaciones`:
  - `id_sku_transformacion`;
  - `id_existencia_origen`;
  - `cantidad_origen_consumida`.

Archivo:

- `docs/erp_almacen_preparacion_transformaciones_propuesta.sql`

Criterio antes de implementar:

- No ejecutar DDL sin respaldo externo y autorizacion.
- No hacer UAT de reempaque hasta tener una existencia real o controlada de `TP-40372-500GR`.

## Evidencia T013/T014 - transformaciones y existencia origen

Fecha: 2026-06-21

Respaldo externo:

- `storage/backups/artianilocal_alm_prep_transformaciones_20260621_antes_ddl.sql`
- Tamano: `26648994` bytes.

DDL aplicado:

- `docs/erp_almacen_preparacion_transformaciones_propuesta.sql`

Estructura creada:

- Tabla `erp_catalogo_sku_transformaciones`.
- Columnas en `erp_almacen_preparaciones`:
  - `id_sku_transformacion`.
  - `id_existencia_origen`.
  - `cantidad_origen_consumida`.

Reglas sembradas:

- Migradas desde `erp_catalogo_sku_presentaciones`: `5`.
- Reempaque desde `TP-40372-500GR`:
  - `TP-40372-500GR -> 5 x TP-40372-100GR`.
  - `TP-40372-500GR -> 10 x TP-40372-50GR`.
  - `TP-40372-500GR -> 20 x TP-40372-25GR`.

Historial actualizado:

- `PREP-20260622-0001` queda ligado a:
  - `id_sku_transformacion=2`;
  - `id_existencia_origen=26`;
  - `cantidad_origen_consumida=0.500000`.

Codigo actualizado:

- `Almacenes::consultar_presentaciones_preparables()` ahora lee desde `erp_catalogo_sku_transformaciones`.
- `Almacenes::guardar_borrador_preparacion()` guarda transformacion y existencia origen.
- `Almacenes::confirmar_preparacion()` consume la existencia origen seleccionada.
- La UI de `Preparacion/Empaque` muestra y selecciona la existencia fisica origen.
- `AlmacenEsquema` ya audita tabla, columnas e indices de transformaciones.

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`: OK.
- `C:\xampp\php\php.exe -l app\modelos\AlmacenEsquema.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\preparacion_empaque.php`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\preparacion_empaque\preparacion_empaque.js`: OK.
- `storage/uat/uat_alm_prep_transformaciones_read.php`: catalogo `total=8`; transformaciones `TP-40372-500GR` visibles; existencia `TP-40372-500GR` disponible `0`.
- `storage/uat/uat_alm_prep_transformaciones_auditoria.php`: esquema `success`, sin pendientes.
- Etiquetas `PREP-20260622-0001`: `20` en estado `impresa`; pendiente cambiar a `pegada` cuando se confirme adherencia fisica.

Bloqueo para UAT T015 resuelto:

- Se creo una existencia controlada de `TP-40372-500GR` desde granel y despues se reempaco a `TP-40372-100GR`.

## Evidencia T015 - reempaque 500GR -> 100GR

Fecha: 2026-06-21

Respaldo externo:

- `storage/backups/artianilocal_alm_prep_reempaque_20260621_antes_uat.sql`
- Tamano: `26652586` bytes.

Script:

- `storage/uat/uat_alm_prep_reempaque_500_a_100.php`
- Candado validado: sin `--execute` no afecta inventario.

Ajustes aplicados antes/durante UAT:

- `Almacenes::aplicar_salida_preparacion()` ahora marca `estatus_existencia='agotada'` cuando la disponibilidad queda en `0`.
- `TP-40372-100GR` queda con `generar_etiqueta_interna=1` y prefijo `P100` para validar etiquetas de bolsa propia.

Folios:

- `PREP-20260621-0002`: preparo `1` unidad de `TP-40372-500GR` desde `0.5000 kg` de `TP-40372`, lote `L1`.
- `PREP-20260621-0003`: reempaco `1` unidad de `TP-40372-500GR` en `5` unidades de `TP-40372-100GR`, lote `L1`.

Kardex:

| Movimiento | Tipo | Folio | Existencia | SKU | Cantidad | Antes | Despues |
| ---: | --- | --- | --- | --- | ---: | ---: | ---: |
| 39 | salida | `PREP-20260621-0002` | `EXI-50-26` | `TP-40372` | `0.5000` | `15.5000` | `15.0000` |
| 40 | entrada | `PREP-20260621-0002` | `EXI-50-29` | `TP-40372-500GR` | `1.0000` | `0.0000` | `1.0000` |
| 41 | salida | `PREP-20260621-0003` | `EXI-50-29` | `TP-40372-500GR` | `1.0000` | `1.0000` | `0.0000` |
| 42 | entrada | `PREP-20260621-0003` | `EXI-50-30` | `TP-40372-100GR` | `5.0000` | `0.0000` | `5.0000` |

Saldos despues:

- `TP-40372`, lote `L1`: `15.0000 kg` disponibles.
- `TP-40372`, lote `L2`: `4.0000 kg` disponibles.
- `TP-40372-500GR`, existencia `EXI-50-29`: `0.0000`, estado `agotada`.
- `TP-40372-100GR`, existencia `EXI-50-30`: `5.0000`, estado `disponible`.

Etiquetas generadas:

- `5` etiquetas con `origen_tipo='preparacion_presentacion'`, `origen_id=4`.
- Rango: `P100-P000004-0001` a `P100-P000004-0005`.

Cierre visual:

- Usuario confirmo impresion y pegado fisico de las etiquetas.
- Verificacion BD: `5` etiquetas en estado `pegada`.
- `fecha_impresion`: `2026-06-21 23:09:01`.
- `fecha_etiquetado`: `2026-06-21 23:09:38`.
- Rango verificado: `P100-P000004-0001` a `P100-P000004-0005`.

## Evidencia de auditoria

Fecha: 2026-06-20

Comando de validacion: auditoria PHP de `AlmacenEsquema::auditarAlmacenInventario()` usando bootstrap del proyecto. No ejecuto DDL.

Resultado general:

- `tipo=warning`
- `mensaje=Hay pendientes en el esquema de almacen e inventario`
- `tiene_pendientes=true`

Resultado especifico de preparacion/empaque:

- `erp_almacen_preparaciones`: no existe; faltan columnas, indices y FKs esperadas.
- `erp_almacen_preparacion_consumos`: no existe; faltan columnas, indices y FKs esperadas.
- `erp_almacen_preparacion_resultados`: no existe; faltan columnas, indices y FKs esperadas.

Conclusion:

- La auditoria ya detecta correctamente la estructura pendiente.
- El siguiente paso tecnico es respaldo externo previo a DDL.
- No se debe ejecutar `planActualizarAlmacenInventario(true)` ni SQL directo sin autorizacion.

## Evidencia de migracion

Fecha: 2026-06-20

Respaldo externo generado antes de DDL:

- `storage/backups/artianilocal_almacen_preparacion_empaque_20260620_antes_ddl.sql`
- Tamano verificado: `26615038` bytes.

DDL aplicado:

- `docs/erp_almacen_preparacion_empaque_schema_propuesta.sql`

Tablas creadas:

- `erp_almacen_preparaciones`
- `erp_almacen_preparacion_consumos`
- `erp_almacen_preparacion_resultados`

Auditoria posterior:

- `AlmacenEsquema::auditarAlmacenInventario()`
- Resultado: `tipo=success`
- Mensaje: `El esquema de almacen e inventario esta completo`
- Sin columnas, indices ni FKs faltantes para las tres tablas de preparacion/empaque.

## Evidencia de consultas base

Fecha: 2026-06-20

Cambios de codigo:

- `Almacenes::consultar_presentaciones_preparables()`
- `Almacenes::consultar_existencias_base_preparacion()`
- `Almacen::preparacion_presentaciones_erp()`
- `Almacen::preparacion_existencias_base_erp()`

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Almacen.php`: OK.
- Consulta CLI de presentaciones preparables: `tipo=success`, total `4`.
- Consulta CLI de existencias base para primer SKU base encontrado: `tipo=success`, total `0`.

Notas:

- No se escribieron datos de preparacion.
- La consulta de existencias puede regresar `0` si el SKU base no tiene saldo disponible; eso no es error funcional.

## Evidencia de borrador

Fecha: 2026-06-21

Cambio de codigo:

- `Almacenes::guardar_borrador_preparacion()`
- `Almacen::preparacion_guardar_borrador_erp()`

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Almacen.php`: OK.

Borrador UAT creado:

- Folio: `PREP-20260621-0001`
- Estado: `borrador`
- Almacen: `1`
- Regla de presentacion: `2`
- SKU base: `146`
- SKU presentacion: `1757`
- Unidades: `20`
- Cantidad base calculada: `0.500000`

Verificacion de no afectacion:

- Movimientos `origen_tipo='preparacion_presentacion'` para `origen_id=1`: `0`.
- Unidades `origen_tipo='preparacion_presentacion'` para `origen_id=1`: `0`.

Conclusion:

- El borrador queda registrado sin afectar inventario ni etiquetas.
- El siguiente paso es `ALM-PREP-T008`: confirmacion transaccional con salida base, entrada presentacion y etiquetas si aplica.

## Evidencia de confirmacion

Fecha: 2026-06-21

Cambio de codigo:

- `Almacenes::confirmar_preparacion()`
- `Almacen::preparacion_confirmar_erp()`

Reglas implementadas:

- Solo confirma preparaciones en estado `borrador`.
- Valida que la regla de presentacion siga activa y configurada para `preparacion`.
- Exige existencia suficiente del SKU base en un solo lote/ubicacion para no mezclar trazabilidad.
- Si confirma, debe registrar:
  - consumo en `erp_almacen_preparacion_consumos`;
  - salida en `erp_inventario_movimientos`;
  - entrada de presentacion en `erp_inventario_movimientos`;
  - resultado en `erp_almacen_preparacion_resultados`;
  - etiquetas en `erp_inventario_unidades` si el SKU presentacion lo requiere.

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Almacen.php`: OK.

Prueba controlada con folio `PREP-20260621-0001`:

- Resultado: bloqueo esperado.
- Mensaje: `Existencia insuficiente del SKU base en un solo lote/ubicacion para confirmar la preparacion`.
- Estado posterior del folio: `borrador`.
- Consumos creados: `0`.
- Resultados creados: `0`.
- Movimientos `origen_tipo='preparacion_presentacion'`: `0`.
- Unidades `origen_tipo='preparacion_presentacion'`: `0`.

Conclusion:

- La confirmacion queda protegida contra stock insuficiente y conserva rollback.
- El borrador historico `PREP-20260621-0001` pertenece a `MINA1105-BAJA`; no debe confirmarse ni borrarse sin autorizacion.
- El flujo nuevo valida almacenes activos con `permite_preparacion`.

## Evidencia de UI

Fecha: 2026-06-21

Cambios:

- Ruta vista: `Almacen::preparacion_empaque()`.
- Vista: `app/vistas/paginas/apps/erp/almacen/preparacion_empaque.php`.
- JS: `public/assets/js/custom/apps/erp/almacen/preparacion_empaque/preparacion_empaque.js`.
- Menu lateral: enlace `Preparacion/Empaque`.
- Endpoint listado: `Almacen::preparaciones_erp()`.
- Modelo listado: `Almacenes::consultar_preparaciones()`.
- Ajuste UX: la captura inicia por SKU base preparable y despues muestra solo sus presentaciones relacionadas.

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Almacen.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\preparacion_empaque.php`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\preparacion_empaque\preparacion_empaque.js`: OK.
- Consulta CLI de preparaciones en `borrador`: `tipo=success`, total `1`.

Alcance:

- Permite consultar presentaciones preparables.
- Permite ver existencia base disponible.
- Permite guardar borrador.
- Permite intentar confirmacion y mostrar bloqueo si no hay stock.
- No toca Ventas/ecommerce.

## Evidencia de etiquetado por preparacion

Fecha: 2026-06-21

Cambio:

- `InventarioErp::listarEtiquetas()` ahora une `erp_almacen_preparaciones` cuando `origen_tipo='preparacion_presentacion'`.
- La pantalla existente de Almacen > Etiquetado puede mostrar folio `PREP-*` en la columna de origen.

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- Consulta CLI de etiquetas buscando `PREP-20260621-0001`: `tipo=success`, total `0`.

Evidencia posterior:

- Folio confirmado: `PREP-20260622-0001`.
- Etiquetas encontradas por origen `preparacion_presentacion`: `20`.
- Rango: `P25-P000002-0001` a `P25-P000002-0020`.
- Estado: `pendiente_impresion`.

Notas:

- Las etiquetas de preparacion aparecen con folio `PREP-*`.
- Falta UAT visual de impresion y pegado fisico desde `Almacen > Etiquetado`.

## Evidencia UAT exitosa ALM-PREP-001

Fecha: 2026-06-21

Respaldo externo:

- `storage/backups/artianilocal_alm_prep_001_20260621_antes_uat.sql`

Operacion:

- Folio: `PREP-20260622-0001`.
- Almacen: `BOD971`.
- SKU base: `TP-40372`.
- Presentacion: `TP-40372-25GR`.
- Unidades: `20`.
- Consumo base: `0.5000 kg`.
- Lote: `L1`.

Resultado:

- Movimiento salida: `37`.
- Movimiento entrada: `38`.
- Etiquetas generadas: `20`.

Saldos verificados:

- `TP-40372` lote `L1`: `15.5000 kg`.
- `TP-40372` lote `L2`: `4.0000 kg`.
- `TP-40372-25GR` lote `L1`: `20.0000`.

Hallazgo resuelto/mitigado:

- `ALM-PREP-H009`: PHP usaba `Europe/Berlin` fuera del bootstrap del proyecto.
- Se fijo `date_default_timezone_set('America/Mexico_City')` en `app/config/configuracion.php`.
- Verificacion: `storage/uat/uat_timezone_check.php`.

## Criterio para pedir autorizacion

Pedir autorizacion antes de:

- ejecutar `mysqldump`;
- ejecutar endpoint `esquema_actualizar_almacen_inventario` con escritura;
- correr SQL directo;
- crear preparaciones reales o datos UAT que afecten inventario.

## Notas tecnicas

- `DBSchema` genera FKs dentro de `CREATE TABLE`, pero no tiene helper generico para agregar FKs a tablas ya existentes.
- Si durante auditoria aparecen tablas existentes sin FKs, se debe proponer DDL incremental separado antes de aplicar.
- La primera version no debe permitir mezclar lotes/caducidades en una misma linea de resultado.
