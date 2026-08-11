# ERP Inventario - Reclasificacion de inventario

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-08  
Modulo: ERP > Inventario > Reclasificacion de inventario  
Clave de trabajo: `INV-RECLAS-001`  
Estado: Diseno y auditoria read-only validada contra BD local; sin migraciones aplicadas

## Objetivo

Disenar el flujo operativo para reclasificar existencia real de un SKU origen hacia un SKU destino permitido por Catalogo, sin complicar Recepcion y sin tratar la operacion como venta, merma o ajuste libre.

Caso operativo base:

- Recepcion recibe lo que entrego proveedor, por ejemplo clasificaciones `mini`, `chico`, `mediano` o `grande`.
- Despues de revisar fisicamente, Inventario puede reclasificar algunas piezas a otra clasificacion interna vendible.
- La operacion debe ser generica para cualquier SKU reclasificable, no especifica para troncos.

## Decision ERP

- Recepcion se queda normal: registra existencia real del SKU recibido.
- Catalogo decide si un SKU permite reclasificacion y que SKUs destino son validos.
- Inventario ejecuta la reclasificacion con folio propio `RECLAS-*`.
- El kardex debe tener dos movimientos con el mismo folio:
  - salida del SKU origen;
  - entrada del SKU destino.
- La reclasificacion conserva almacen, lote, caducidad, ubicacion, costo y trazabilidad cuando aplique.
- Si el costo destino cambia contra el costo origen, debe capturarse/documentarse con autorizacion operativa; el sistema no debe inventarlo automaticamente.
- No tocar Ventas/ecommerce.
- No mezclar con endpoints legacy de Inventario.

## Auditoria del flujo actual

Nota tecnica 2026-08-08:

- La BD local del proyecto esta en MySQL puerto `3406`.
- La primera consulta manual con cliente MySQL uso el puerto por defecto `3306` y fallo; se repitio correctamente en `3406`.
- Auditoria SQL read-only ejecutada sobre base local `artianilocal`.

### 1. Consulta de existencias

`Inventario.php` expone `/inventario/existencias_erp`, que llama `InventarioErp::listarExistencias($_GET)`.

`InventarioErp::listarExistencias()` consulta `erp_inventario_existencias` y enriquece el resultado con:

- SKU y producto desde `erp_catalogo_skus` y `erp_catalogo_productos`.
- Almacen desde `erp_almacenes`.
- Lote, caducidad, ubicacion, cantidad, apartada, disponible, costo promedio y estatus.
- Resumen de unidades fisicas desde `erp_inventario_unidades`.

Conclusion:

- La consulta actual sirve para seleccionar SKU origen por almacen, lote/caducidad/ubicacion y disponibilidad.
- Para reclasificacion hace falta un endpoint especifico que filtre existencias disponibles del SKU origen y, si existen unidades fisicas, exponga la unidad exacta seleccionable.

### 2. Registro en `erp_inventario_movimientos`

`InventarioErp::aplicarCambio()` inserta movimientos genericos con:

- `tipo_movimiento`: `entrada` o `salida`.
- `origen_tipo`: motivo/documento operativo, por ejemplo `ajuste`, `traspaso`, `inventario_inicial`.
- `origen_id` y `origen_detalle_id`.
- `id_existencia_inventario`, `codigo_existencia`, lote, caducidad, ubicacion.
- cantidad, costo unitario, costo total.
- existencia anterior/nueva.
- `referencia` como folio visible.
- observaciones.

`Almacenes::confirmar_preparacion()` y `Almacenes::confirmar_apertura_empaque()` ya siguen el patron de salida y entrada bajo el mismo folio, aunque pertenecen a Almacen.

Conclusion:

- `erp_inventario_movimientos` ya puede registrar el kardex doble.
- No existe hoy un `origen_tipo='reclasificacion_inventario'` implementado.
- No hay una tabla encabezado/detalle propia de reclasificacion para documentar motivo, costo, evidencia y relacion origen-destino.

Tipos reales encontrados en `erp_inventario_movimientos`:

| Tipo | Origen | Movimientos |
| --- | --- | ---: |
| `entrada` | `ajuste` | 6 |
| `salida` | `ajuste` | 1 |
| `entrada` | `apertura_empaque` | 1 |
| `salida` | `apertura_empaque` | 1 |
| `entrada` | `conteo_fisico` | 1 |
| `salida` | `conteo_fisico` | 1 |
| `entrada` | `inventario_inicial` | 22 |
| `salida` | `pedido_pos_entrega` | 3 |
| `entrada` | `preparacion_presentacion` | 5 |
| `salida` | `preparacion_presentacion` | 5 |
| `entrada` | `recepcion_compra` | 12 |
| `entrada` | `traspaso` | 2 |
| `salida` | `traspaso` | 2 |
| `salida` | `venta_pos` | 21 |

### 3. Entradas y salidas de inventario

Flujos actuales:

- Ajuste: `InventarioErp::aplicarAjuste()` crea entradas o salidas por `aplicarCambio()`.
- Traspaso: `InventarioErp::aplicarTraspaso()` crea salida en almacen origen y entrada en almacen destino, conservando lote/caducidad y costo promedio.
- Recepcion: `Almacenes::guardar_recepcion_almacen()` crea entrada por recepcion.
- Preparacion/empaque: crea salida del SKU base y entrada del SKU presentacion.
- Apertura de empaques: crea salida del SKU cerrado y entradas por resultados.

Conclusion:

- Reclasificacion debe parecerse tecnicamente a traspaso/preparacion, pero sin cambiar almacen y sin vivir en Almacen.
- No debe reutilizar ajuste, porque ajuste no valida destino permitido ni explica la relacion origen-destino.

### 4. Concepto/tipo de movimiento

La tabla usa `tipo_movimiento` generico (`entrada`/`salida`) y `origen_tipo` como clasificacion operativa.

Decision recomendada:

- Mantener `tipo_movimiento='salida'` para origen y `tipo_movimiento='entrada'` para destino.
- Usar `origen_tipo='reclasificacion_inventario'`.
- Usar el mismo `referencia='RECLAS-YYYYMMDD-0001'` en ambos movimientos.
- Usar `origen_id=id_reclasificacion_inventario`.
- Usar `origen_detalle_id` apuntando al detalle que corresponda.

### 5. Relacion movimiento origen/destino

No se observa una tabla generica para relacionar movimientos pares de Inventario bajo un mismo folio.

Auditoria SQL:

- No existen tablas `LIKE '%reclas%'`.
- Existe `erp_catalogo_sku_transformaciones`, pero sus registros actuales son:
  - `empaque_desde_granel`, `preparada`, `activa`: 5.
  - `reempaque`, `preparada`, `activa`: 3.
- Por lo tanto, reclasificacion no esta modelada hoy ni como tabla propia ni como transformacion existente.

Existen ejemplos especializados:

- `erp_almacen_preparaciones`
- `erp_almacen_preparacion_consumos`
- `erp_almacen_preparacion_resultados`
- `erp_almacen_aperturas_empaque`
- `erp_almacen_apertura_resultados`

Conclusion:

- Reclasificacion necesita sus propias tablas para encabezado y detalle, porque no es preparacion ni apertura.
- El detalle debe guardar `id_movimiento_salida` e `id_movimiento_entrada` para navegar el par exacto.

### 6. Unidades fisicas

`erp_inventario_unidades` permite identificar unidades con:

- SKU, producto, almacen, ubicacion, lote, caducidad.
- existencia relacionada.
- cantidad base original/disponible.
- estado fisico, estado etiqueta, estatus.
- origen operativo y movimiento de consumo.

Brecha:

- La unidad fisica esta asociada a un SKU y a una existencia.
- No existe una relacion explicita para decir: "esta misma unidad fisica fue reclasificada del SKU A al SKU B".

Decision recomendada:

- Si la reclasificacion es de saldo agregado sin unidad fisica, crear salida/entrada de existencias y guardar trazabilidad en `erp_inventario_reclasificaciones_detalle`.
- Si se selecciona una unidad fisica cerrada completa, no conviene "editar" la unidad origen para cambiarle SKU sin historial. Se recomienda:
  - marcar la unidad origen como `reclasificada` o `consumida_por_reclasificacion` mediante columnas nuevas o estatus controlado;
  - crear una nueva unidad destino si el SKU destino exige unidad/etiqueta;
  - enlazar ambas en el detalle de reclasificacion.
- Si la unidad fisica esta abierta o parcial, permitir solo si la cantidad reclasificada es compatible con su `cantidad_base_disponible`; actualizar contenido disponible y estado fisico igual que preparacion.

Para una primera version robusta, bloquear reclasificacion parcial de unidades fisicas cerradas y exigir cantidad completa de la unidad seleccionada.

### 7. Lote, caducidad y trazabilidad

`erp_inventario_existencias` ya separa saldos por SKU, almacen, lote, caducidad y ubicacion.

Regla recomendada:

- El destino hereda por defecto almacen, lote, caducidad y ubicacion de la existencia origen.
- Si el destino exige lote/caducidad, se usan los valores heredados.
- Si el origen no tiene lote/caducidad y el destino lo exige, bloquear salvo captura autorizada/documentada antes de guardar.
- La UI debe mostrar lote/caducidad antes de guardar.

### 8. Costo

El costo actual vive en `erp_inventario_existencias.costo_promedio` y en cada movimiento como `costo_unitario/costo_total`.

Regla recomendada:

- Costo por defecto del destino = costo unitario de origen.
- Si cambia costo:
  - exigir motivo;
  - guardar costo origen, costo destino, diferencia y politica usada;
  - mostrar advertencia en resumen;
  - requerir permiso adicional futuro o autorizacion segun politica de Costos.
- Para primera version, bloquear cambio de costo y conservar costo origen, salvo que se autorice explicitamente una politica de costos.

### 9. Motivo, usuario, folio y evidencia

Lo que falta:

- Encabezado propio de reclasificacion con folio, usuario, estatus y motivo obligatorio.
- Detalle con existencia/unidad origen, SKU destino, cantidad, costos, movimientos y trazabilidad.
- Adjuntos/evidencia opcional para fotos, revision fisica o autorizacion.

## Modelo funcional propuesto

### Pantalla

Ruta sugerida:

- `Inventario > Reclasificacion`
- URL sugerida: `/inventario/reclasificacion`

Endpoints sugeridos:

- `GET /inventario/reclasificacion_catalogos_erp`
- `GET /inventario/reclasificacion_existencias_origen_erp`
- `GET /inventario/reclasificacion_destinos_erp`
- `POST /inventario/reclasificacion_previsualizar_erp`
- `POST /inventario/reclasificacion_guardar_erp`

Permiso sugerido:

- `inventario.reclasificar`

No inventar/aplicar el permiso sin revisar `SeguridadPermisos.php`, `SeguridadEsquema.php`, `Sistema.php` y convenciones existentes.

### Flujo UI

1. Usuario entra a Inventario > Reclasificacion.
2. Selecciona almacen.
3. Busca SKU origen con existencia disponible.
4. Selecciona existencia/lote/caducidad/ubicacion.
5. Si la existencia tiene unidades fisicas, selecciona unidad fisica cuando aplique.
6. Selecciona SKU destino permitido por Catalogo.
7. Captura cantidad.
8. Captura motivo obligatorio.
9. Sistema muestra resumen:
   - salida SKU origen;
   - entrada SKU destino;
   - almacen;
   - lote/caducidad/ubicacion;
   - costo origen/destino;
   - folio propuesto.
10. Al guardar, en una transaccion:
   - crea encabezado `RECLAS-*`;
   - valida existencia suficiente con `FOR UPDATE`;
   - crea/actualiza existencia destino;
   - genera movimiento de salida;
   - genera movimiento de entrada;
   - liga movimientos al detalle;
   - actualiza unidad fisica si aplica;
   - deja auditoria.

### Validaciones backend

- Cantidad mayor a cero.
- Almacen obligatorio y activo.
- SKU origen obligatorio y activo.
- SKU destino obligatorio y activo.
- SKU origen distinto de SKU destino.
- Existencia origen obligatoria.
- Existencia suficiente en `cantidad_disponible`.
- Si hay unidad fisica seleccionada, debe pertenecer a la existencia, SKU y almacen origen, estar disponible y tener contenido suficiente.
- SKU destino permitido por Catalogo.
- Motivo obligatorio.
- Lote/caducidad/serie conforme a reglas del SKU destino.
- No reclasificar SKU inactivo, fusionado o no inventariable sin bloqueo/advertencia definida.
- No permitir cambio automatico de costo.

## Catalogo requerido

Opcion recomendada:

- Crear tabla especifica `erp_catalogo_sku_reclasificaciones`.

Motivo:

- `erp_catalogo_sku_transformaciones` ya existe, pero esta orientada a preparacion/empaque, factores y disponibilidad de presentaciones.
- Reclasificacion es una decision de equivalencia/clasificacion interna, no una receta de preparacion ni un empaque.
- Una tabla especifica permite reglas mas claras: origen, destino, misma unidad, conserva lote, conserva costo, requiere autorizacion y estatus.

Alternativa:

- Extender `erp_catalogo_sku_transformaciones.tipo_transformacion='reclasificacion'`.

Riesgo:

- Mezcla semantica con preparacion/empaque y puede llevar a UI o validaciones incorrectas.

## Propuesta DDL

Archivo propuesto:

- `docs/erp_inventario_reclasificacion_schema_propuesta.sql`

Tablas principales:

- `erp_catalogo_sku_reclasificaciones`
- `erp_inventario_reclasificaciones`
- `erp_inventario_reclasificaciones_detalle`
- `erp_inventario_reclasificacion_adjuntos`

No aplicar sin:

- respaldo externo en `C:\xampp\panel_db_backups`;
- autorizacion textual del dueno;
- evidencia antes/despues;
- actualizacion de `AlmacenEsquema` o esquema nuevo de Inventario.

## Hallazgos

| ID | Severidad | Hallazgo | Recomendacion |
| --- | --- | --- | --- |
| `INV-RECLAS-H001` | Alta | No existe flujo ni `origen_tipo` implementado para reclasificacion. | Agregar flujo propio de Inventario con `origen_tipo='reclasificacion_inventario'`. |
| `INV-RECLAS-H002` | Alta | No existe tabla de encabezado/detalle para ligar salida y entrada bajo un folio operativo. | Crear tablas `erp_inventario_reclasificaciones` y detalle con movimientos salida/entrada. |
| `INV-RECLAS-H003` | Alta | Catalogo no tiene una relacion especifica de SKUs reclasificables. | Crear `erp_catalogo_sku_reclasificaciones` o extender transformaciones con mucho cuidado. |
| `INV-RECLAS-H004` | Media | `erp_inventario_unidades` no modela cambio de SKU de una misma unidad fisica con historial. | En primera version crear unidad destino y enlazar origen/destino en detalle; no editar silenciosamente el SKU de la unidad origen. |
| `INV-RECLAS-H005` | Media | `aplicarCambio()` sirve para kardex, pero no liga dos movimientos como par. | Reusar patron de salida/entrada, guardando ambos IDs en detalle. |
| `INV-RECLAS-H006` | Baja | La primera auditoria manual fallo por usar puerto MySQL `3306` en vez del puerto local del proyecto `3406`. | Usar siempre la configuracion local del proyecto para auditorias SQL. Evidencia read-only ya corregida. |
| `INV-RECLAS-H007` | Media | Costo destino podria confundirse con ajuste de costo. | Primera version debe conservar costo origen; cambios de costo requieren politica/autorizacion separada. |

## Orden recomendado de implementacion

1. Catalogo: definir estructura `erp_catalogo_sku_reclasificaciones` y UI/endpoint para configurar origen-destinos permitidos.
2. Seguridad: agregar permiso `inventario.reclasificar` si se autoriza.
3. Esquema Inventario: agregar tablas de reclasificacion en dry-run y aplicar solo con respaldo/autorizacion.
4. Backend read-only: endpoints para consultar existencias origen y destinos permitidos.
5. Backend dry-run: previsualizar salida/entrada, costo, lote/caducidad y folio.
6. Backend escritura: guardar reclasificacion en transaccion.
7. UI: pantalla operativa con resumen antes de guardar.
8. Kardex/Trazabilidad: extender consultas para buscar `RECLAS-*` y mostrar par salida/entrada.
9. UAT negativo: cantidad cero, origen=destino, sin motivo, destino no permitido, existencia insuficiente, unidad fisica incorrecta.
10. UAT controlado con respaldo externo y folio `RECLAS-*`.

## Criterio de cierre para fase de diseno

- Documento de diseno creado.
- Auditoria actual documentada.
- DDL propuesto separado y no aplicado.
- Orden de implementacion definido.
- Validacion SQL real ejecutada en MySQL local por puerto `3406`.
