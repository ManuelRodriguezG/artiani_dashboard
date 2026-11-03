# Plan de inventario consolidado temporal

## Estado

Diagnostico read-only completado el 2026-11-02. No se modificaron datos, existencias, movimientos, recepciones ni estatus.

## Objetivo

Permitir que el negocio opere temporalmente como un solo inventario consolidado mientras se ordenan catalogo, compras y existencias fisicas. La separacion futura de Bodega, Acuario y Mascotas se conserva como una etapa posterior, no se elimina.

## Diagnostico actual

La base operativa detectada es `artianicom_sys`.

Almacenes actuales:

| ID | Codigo | Uso | Estado | Recepcion | Venta | Preparacion | Ajustes |
|---:|---|---|---|---:|---:|---:|---:|
| 3 | `BOD971` | Bodega trasera Mina 971 | Activo | Si | No | Si | Si |
| 4 | `ACUARIO967` | Local Acuario Mina 967 | Activo | Si | Si | No | Si |
| 5 | `MASCOTAS971` | Local Mascotas Mina 971 | Activo | Si | Si | No | Si |

Almacenes historicos inactivos:

- ID 1: `MINA1105-BAJA`.
- ID 2: `SANJOSE1727-BAJA`.

Existencias leidas:

- Almacen 3: 18 filas, cantidad total registrada 109.45.
- Almacen 5: 4 filas, cantidad total registrada 1019.
- Almacen 4: no tiene filas de existencias en la consulta.
- Almacen 1 conserva una fila con cantidad cero.

Referencias operativas observadas:

- Ventas: 54 registros en Mascotas y 12 en Acuario.
- Recepciones: 3 registros en Bodega.
- Movimientos de inventario: 47 en Mascotas, 34 en Bodega y 2 historicos en el almacen 1.
- Incidencias operativas de compras: 44 registros en Bodega.
- `erp_compras_ordenes` no tiene actualmente una columna directa `id_almacen`; el destino se maneja en partidas, recepciones o incidencias segun el flujo.

Fuente de la auditoria: `storage/uat/uat_almacen_consolidado_readonly.php`.

## Decision operativa propuesta

Crear un **Almacen General** como destino operativo unico para nuevas operaciones, pero sin borrar ni reescribir los almacenes actuales.

Durante la fase temporal:

- Compras nuevas y recepciones futuras usan Almacen General.
- Ventas nuevas pueden usar Almacen General cuando se active el inventario formal.
- Las existencias antiguas se conservan en sus almacenes originales.
- Los reportes pueden mostrar una vista consolidada sumando los almacenes vigentes.
- No se habilitan traspasos entre Bodega, Acuario y Mascotas.
- Las incidencias de compras no afectan existencias; solo quedan pendientes para Catalogo o Inventario.

## Fases

### Fase 0: diagnostico read-only

- [x] Auditar almacenes activos e inactivos.
- [x] Auditar existencias por almacen.
- [x] Auditar referencias de ventas, recepciones, movimientos e incidencias.
- [x] Confirmar que la auditoria no escribe en BD.

### Fase 1: configuracion operativa sin migrar existencias

- [ ] Definir si se reutilizara un almacen existente o se creara `Almacen General`.
- [ ] Definir el identificador de configuracion global del almacen operativo.
- [ ] Hacer que Compras y Sugerido de compra propongan el almacen operativo sin obligar al usuario a elegir entre tres.
- [ ] Mantener visibles los almacenes historicos solo para consulta.
- [ ] No cambiar saldos, movimientos ni recepciones existentes.

Esta fase requiere autorizacion si modifica configuracion productiva o crea un almacen.

### Fase 2: consolidacion virtual

- [ ] Crear reportes de existencia consolidada de los almacenes vigentes.
- [ ] Separar claramente `existencia consolidada` de `existencia por ubicacion`.
- [ ] Mantener trazabilidad del almacen original de cada movimiento.
- [ ] No generar traspasos ni ajustes automaticos.

### Fase 3: corte fisico opcional

- [ ] Realizar conteo fisico real.
- [ ] Comparar contra existencias registradas.
- [ ] Preparar un preflight con diferencias por SKU.
- [ ] Respaldar la BD fuera del proyecto.
- [ ] Autorizar el corte de existencias hacia Almacen General.
- [ ] Ejecutar ajustes auditados, no borrar movimientos historicos.

Esta fase no debe ejecutarse automaticamente.

### Fase 4: separacion futura

- [ ] Reactivar almacenes por ubicacion cuando exista control operativo.
- [ ] Definir reglas de minimos/maximos por almacen.
- [ ] Habilitar traspasos y resurtido.
- [ ] Distribuir existencias mediante movimientos autorizados.

## Guardrails

- No borrar almacenes existentes.
- No cambiar historicamente `id_almacen` en ventas, recepciones o movimientos.
- No crear existencias desde Compras.
- No convertir una incidencia de Catalogo en un ajuste de Inventario.
- Toda migracion de saldos requiere respaldo externo, preflight, autorizacion y auditoria.
- El almacen consolidado debe ser una configuracion operativa, no un reemplazo destructivo del historial.

## Proxima autorizacion requerida

Antes de tocar la base, el dueno debe definir una de estas opciones:

1. Reutilizar el almacen 3 como almacen operativo temporal.
2. Crear un nuevo almacen `Almacen General` y dejar 3, 4 y 5 como historicos operativos.
3. Mantener los tres almacenes y usar solo una vista consolidada sin cambiar el destino de nuevas operaciones.

La recomendacion tecnica es la opcion 2 si se desea separar claramente el inventario nuevo del historial existente. La opcion 3 es la de menor riesgo inmediato.

