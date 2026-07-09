# ERP Garantias - UAT manual de politicas y reglas

Fecha: 2026-06-29  
Documentacion IA: Codex GPT-5

## Objetivo

Probar el flujo operativo actual de configuracion de garantias sin tocar esquema ni ejecutar scripts masivos.

## Alcance

Incluye:

- consulta de politicas;
- consulta de reglas;
- validacion de politica;
- validacion e impacto de regla;
- guardado operativo normal de politica/regla;
- baja logica y reactivacion;
- resolver garantia por SKU;
- simulacion de snapshot.

No incluye:

- cambios de esquema;
- migraciones;
- reclamos reales de garantia;
- integracion definitiva con ventas historicas;
- devoluciones, almacen o proveedor.

## Precondiciones

- El usuario debe tener permiso `garantias.ver`.
- Para crear/editar/desactivar politicas o reglas, debe tener permiso `garantias.politicas`.
- El esquema base de Garantias debe existir.
- Si se va a guardar una prueba real, usar una politica/regla claramente identificable como prueba.

## UAT automatizado read-only

Script:

- `storage/uat/uat_garantias_politicas_reglas_readonly.php`
- `storage/uat/uat_garantias_cobertura_candidatos_readonly.php`

Comando:

```powershell
C:\xampp\php\php.exe storage\uat\uat_garantias_politicas_reglas_readonly.php
C:\xampp\php\php.exe storage\uat\uat_garantias_cobertura_candidatos_readonly.php
```

Contrato:

- No crea politicas.
- No crea reglas.
- No cambia estatus.
- No crea ventas.
- No guarda snapshots.
- No mueve inventario.

Validaciones incluidas:

- disponibilidad de esquema;
- listado de politicas;
- listado de reglas activas;
- auditoria de cobertura;
- busqueda de referencias por ambito;
- dry-run de regla;
- bloqueo de referencia inexistente;
- impacto dry-run;
- resolver garantia por SKU;
- snapshot dry-run de venta.
- candidatos de cobertura por categoria, marca y proveedor.

Resultado de referencia 2026-06-29:

- Esquema disponible.
- 6 politicas activas.
- 1 regla activa.
- 1730 SKUs activos auditados.
- 1 SKU con regla.
- 1729 SKUs sin regla.
- Candidatos principales por categoria:
  - `Alimentacion / Alimentos`: 313 SKUs sin regla.
  - `Salud e higiene / Higiene y limpieza`: 185 SKUs sin regla.
  - `(sin categoria)`: 155 SKUs sin regla.

## Prueba 1 - Carga inicial

Pasos:

1. Abrir `ERP > Garantias > Politicas`.
2. Presionar `Actualizar`.
3. Revisar tarjetas superiores.

Resultado esperado:

- Politicas activas muestra conteo.
- Reglas activas muestra conteo.
- SKUs sin regla muestra conteo.
- Estado resolver queda listo o pendiente segun consulta.
- Cobertura muestra porcentaje cubierto.

## Prueba 2 - Validar politica sin guardar

Pasos:

1. Capturar codigo, nombre, tipo y duracion.
2. Marcar requisitos y resultados.
3. Presionar `Validar politica`.

Resultado esperado:

- Muestra `Politica valida en dry-run` si no hay bloqueos.
- No se guarda ninguna politica.
- Si faltan datos, muestra bloqueo.

## Prueba 3 - Guardar politica

Pasos:

1. Usar una politica de prueba.
2. Presionar `Guardar politica`.
3. Confirmar el guardado.
4. Presionar `Actualizar`.

Resultado esperado:

- La politica aparece en `Politicas base`.
- El cambio queda auditado por controlador.
- No pide respaldo manual ni token.

## Prueba 4 - Buscar referencia para regla

Pasos:

1. Elegir ambito: SKU, producto, categoria, marca o proveedor.
2. Capturar texto de busqueda.
3. Presionar buscar.
4. Seleccionar una referencia.

Resultado esperado:

- El ID referencia se llena desde la seleccion.
- La etiqueta muestra la referencia seleccionada.
- Cambiar ambito limpia la referencia.

## Prueba 5 - Validar regla antes de guardar

Pasos:

1. Elegir politica.
2. Elegir ambito y referencia.
3. Definir prioridad y canal.
4. Presionar `Validar`.

Resultado esperado:

- Si no hay bloqueos, muestra `Regla valida en dry-run`.
- No se guarda ninguna regla.
- Si se cambia politica, ambito, referencia, prioridad o canal, la revision queda invalidada.
- Si la referencia no existe o no corresponde al ambito, muestra bloqueo.

## Prueba 5.1 - Bloqueo por referencia invalida

Pasos:

1. Elegir una politica activa.
2. Elegir un ambito.
3. Capturar un ID referencia que no exista para ese ambito.
4. Presionar `Validar`.

Resultado esperado:

- El dry-run bloquea la regla.
- Debe mostrar que la referencia seleccionada no existe o no esta disponible para el ambito.
- No permite guardar la regla.

## Prueba 5.2 - Bloqueo por regla solapada

Pasos:

1. Elegir una politica activa.
2. Elegir el mismo ambito y referencia de una regla activa existente.
3. Capturar la misma prioridad.
4. Usar el mismo canal o dejar canal abierto si la regla existente tambien aplica de forma general.
5. Usar una vigencia que se cruce con la regla existente.
6. Presionar `Validar`.

Resultado esperado:

- El dry-run bloquea la regla.
- Debe indicar que ya existe una regla activa solapada.
- No permite guardar una regla ambigua.

## Prueba 6 - Impacto de regla

Pasos:

1. Con la misma configuracion de regla, presionar `Impacto`.

Resultado esperado:

- Muestra SKUs afectados.
- Muestra SKUs con regla previa y sin regla previa.
- Muestra ejemplos.
- Si hay solapamientos o advertencias, el panel se muestra como advertencia.
- No se guarda ninguna regla.

## Prueba 7 - Guardar regla

Pasos:

1. Ejecutar `Validar` o `Impacto`.
2. Sin cambiar campos, presionar `Guardar regla`.
3. Confirmar.

Resultado esperado:

- Si no hubo validacion/impacto vigente, bloquea con `Revisa antes de guardar`.
- Si la revision esta vigente, guarda la regla.
- La tabla de reglas se refresca.
- Cobertura se recalcula.

## Prueba 8 - Baja logica y reactivacion

Pasos:

1. En Politicas base, desactivar una politica de prueba.
2. Reactivarla.
3. En Reglas asignadas, desactivar una regla de prueba.
4. Reactivarla.

Resultado esperado:

- No se borra fisicamente.
- Las listas se refrescan.
- La cobertura cambia si la regla afectaba SKUs.
- Las acciones quedan auditadas.

## Prueba 9 - Resolver garantia por SKU

Pasos:

1. Capturar SKU ERP.
2. Elegir canal.
3. Presionar resolver.

Resultado esperado:

- Muestra politica aplicable o `SIN_GARANTIA`.
- Muestra origen/regla aplicada.
- No recalcula ventas historicas.

## Prueba 10 - Snapshot dry-run

Pasos:

1. Capturar SKU ERP.
2. Elegir canal.
3. Presionar snapshot.

Resultado esperado:

- Muestra snapshot sugerido.
- No crea venta real.
- No mueve inventario.

## Criterio de aprobacion

- La pantalla permite entender diferencia entre politica, regla, cobertura e impacto.
- No permite guardar regla sin revisar la configuracion vigente.
- Las bajas son logicas.
- El resolver respeta reglas activas.
- No se requiere respaldo externo para CRUD normal.
- Cualquier cambio de esquema sigue fuera de este UAT.
