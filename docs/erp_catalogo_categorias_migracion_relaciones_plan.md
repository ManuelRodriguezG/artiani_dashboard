# ERP Catalogo - Plan de reemplazo de categorias y relaciones

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-14  
Modulo: ERP > Catalogo > Categorias  
Proyecto vigente: `C:\xampp\htdocs\panel_de_control`  
Estado: plan operativo; no aplicado en BD

## Objetivo

Preparar el reemplazo del arbol actual de categorias por el arbol refinado 1-6, sin perder control operativo de los productos.

Documento base:

- `docs/erp_catalogo_categorias_arbol_1_6_refinado.md`

## Auditoria actual

Script read-only:

- `storage/uat/uat_catalogo_categorias_relaciones_readonly.php`

Resultado:

- Productos totales: 1610.
- Productos activos: 1416.
- Relaciones producto-categoria actuales: 6979.
- Relaciones marcadas como principales: 1377.
- Productos con al menos una categoria: 1380.
- Productos con categoria principal: 1377.
- Productos sin categoria: 230.
- Productos sin categoria principal: 233.
- Relaciones hacia categorias maestras ERP: 5162.
- Relaciones hacia categorias heredadas/ecommerce: 1817.

## Respuesta operativa

Si, es posible eliminar todas las relaciones actuales para empezar desde cero.

Pero no es la opcion recomendada como primer movimiento porque:

- dejaria 1380 productos sin clasificacion inmediatamente;
- se perderia la pista de donde estaban ubicados los productos;
- afectaria busquedas, filtros, catalogos comerciales y cualquier pantalla que use categoria;
- haria mas lento el reordenamiento porque ya no habria equivalencias visibles;
- obligaria a reclasificar todo manualmente sin respaldo funcional dentro del sistema.

## Enfoque recomendado

No borrar todo de entrada. Hacer una migracion controlada en tres capas:

1. Crear o ajustar el arbol nuevo 1-6.
2. Generar mapa de equivalencias de categorias actuales a categorias destino.
3. Reemplazar relaciones principales de forma controlada y dejar secundarias/historicas para revision.

Este enfoque permite ordenar sin perder evidencia.

## Flujo recomendado

### Paso 1 - Crear categorias nuevas faltantes

Crear solo las categorias que no existan para el arbol:

- `Perros`.
- `Gatos`.
- `Acuario y peces`.
- `Reptiles y tortugas`.
- `Aves`.
- `Pequenos mamiferos`.

Y sus subniveles definidos en:

- `docs/erp_catalogo_categorias_arbol_1_6_refinado.md`

No se deben borrar categorias heredadas en este paso.

### Paso 2 - Mapa de equivalencias

Crear una tabla/documento de trabajo con:

```text
id_categoria_actual
ruta_actual
productos_afectados
categoria_destino_propuesta
confianza
accion
observaciones
```

Acciones posibles:

- `migrar_principal`: cambiar categoria principal del producto al destino.
- `migrar_secundaria`: conservar como categoria secundaria.
- `revisar_manual`: no hay destino claro.
- `inactivar_categoria_origen`: solo cuando ya no tenga productos.

### Paso 3 - Reemplazar categoria principal

Para cada producto:

- Si tiene categoria principal actual y existe equivalencia confiable, crear/actualizar la relacion hacia la categoria destino con `es_principal=1`.
- Si tiene varias categorias secundarias, no eliminarlas inmediatamente.
- Si tiene categoria heredada ecommerce como principal, mover principal a categoria maestra refinada.

Regla:

- Cada producto debe terminar con una sola categoria principal.
- Puede tener categorias secundarias si ayudan a busqueda o catalogos comerciales.

### Paso 4 - Limpieza gradual

Despues de validar productos:

- quitar relaciones a categorias heredadas que ya no aporten;
- inactivar categorias heredadas vacias;
- ocultar categorias no operativas del selector de producto;
- mantener equivalencias como historial.

## Alternativa fuerte: limpiar todas las relaciones

Solo recomendable si se decide reclasificar manualmente desde cero.

Condiciones minimas:

- respaldo externo de BD completo;
- respaldo JSON/CSV de `erp_catalogo_producto_categorias`;
- autorizacion explicita con token;
- confirmacion de que el negocio acepta que temporalmente los productos queden sin categoria;
- pantalla o flujo listo para reasignacion masiva.

Riesgos:

- todos los productos dependeran de recaptura manual;
- catalogos comerciales y filtros pueden quedar incompletos;
- se pierde comparacion directa de categorias anteriores contra nuevas.

## Decision actualizada 2026-08-14

El dueno del proyecto confirmo que la pagina y las categorias aun estan en construccion, y que operativamente no dependen todavia de estas relaciones.

Con ese contexto, la limpieza total puede usarse como via acelerada para reclasificar desde cero, siempre con respaldo externo y token explicito.

Preparacion realizada:

- Script de respaldo externo:
  `storage/uat/uat_catalogo_categorias_relaciones_backup_readonly.php`.
- Script de limpieza con preview/token:
  `storage/uat/uat_catalogo_categorias_relaciones_limpiar_apply.php`.
- Respaldo JSON generado:
  `C:\xampp\panel_db_backups\catalogo_producto_categorias_20260814_092436_pre_limpieza.json`.

Preview de impacto:

- Productos totales: 1610.
- Relaciones a eliminar: 6979.
- Productos que quedarian sin categoria: 1610.

Token requerido:

```text
CATALOGO_CATEGORIAS_RELACIONES_LIMPIAR
```

Comando de aplicacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_categorias_relaciones_limpiar_apply.php --execute --token=CATALOGO_CATEGORIAS_RELACIONES_LIMPIAR --respaldo=C:\xampp\panel_db_backups\catalogo_producto_categorias_20260814_092436_pre_limpieza.json
```

Despues de aplicar:

- Reclasificar productos desde el arbol 1-6.
- Ocultar/inactivar categorias heredadas para captura operativa.
- Usar categorias antiguas solo como referencia historica desde el respaldo.

## Ejecucion aplicada 2026-08-14

Autorizacion recibida:

```text
CATALOGO_CATEGORIAS_RELACIONES_LIMPIAR
```

Respaldo usado:

```text
C:\xampp\panel_db_backups\catalogo_producto_categorias_20260814_092436_pre_limpieza.json
```

Resultado:

- Filas eliminadas de `erp_catalogo_producto_categorias`: 6979.
- Productos totales antes: 1610.
- Relaciones antes: 6979.
- Productos con categoria antes: 1380.
- Productos sin categoria antes: 230.
- Relaciones despues: 0.
- Productos con categoria despues: 0.
- Productos sin categoria despues: 1610.

Validacion:

- `erp_catalogo_producto_categorias` quedo sin relaciones.
- `erp_catalogo_categorias` no fue modificada.
- Auditoria de categorias sigue sin texto danado, padres inexistentes, codigos duplicados, nombres duplicados ni rutas inconsistentes.

## Recomendacion concreta

Con el contexto actualizado, usar la ruta acelerada es aceptable en este ambiente de construccion.

Ruta acelerada:

- Limpiar todas las relaciones producto-categoria con respaldo externo.
- Reclasificar desde cero usando el arbol 1-6.
- Inactivar/ocultar categorias heredadas para que no estorben en la captura.

Ruta controlada alternativa:

- Generar mapa de equivalencias.
- Migrar por grupos: Acuario, Perros, Gatos, Reptiles, Aves y Pequenos mamiferos.
- Conservar secundarias/historicas hasta terminar revision.

Decision operativa actual:

- Avanzar con ruta acelerada si el dueno autoriza aplicar limpieza.
- Mantener respaldo JSON como referencia para consultar categorias anteriores si hiciera falta.

## Criterio para avanzar a ejecucion

Antes de aplicar cambios en BD se debe tener:

- arbol 1-6 aprobado;
- respaldo externo;
- script preview con conteos antes/despues;
- autorizacion explicita para aplicar.

## Siguiente tarea

Si se autoriza, aplicar limpieza con token `CATALOGO_CATEGORIAS_RELACIONES_LIMPIAR` y comenzar reclasificacion desde el arbol 1-6.

## Preparacion de arbol operativo 1-6

Fecha: 2026-08-14

Scripts preparados:

- Respaldo read-only:
  `storage/uat/uat_catalogo_categorias_arbol_1_6_backup_readonly.php`.
- Aplicacion con preview/token:
  `storage/uat/uat_catalogo_categorias_arbol_1_6_apply.php`.

Respaldo externo generado:

```text
C:\xampp\panel_db_backups\catalogo_categorias_arbol_1_6_20260814_121617_pre.json
```

Preview:

- Categorias definidas en arbol 1-6: 192.
- Categorias a crear: 192.
- Categorias a actualizar: 0.
- No asigna productos.
- No elimina categorias heredadas.
- No modifica relaciones producto-categoria.

Token requerido:

```text
CATALOGO_CATEGORIAS_ARBOL_1_6_APLICAR
```

Comando de aplicacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_categorias_arbol_1_6_apply.php --execute --token=CATALOGO_CATEGORIAS_ARBOL_1_6_APLICAR --respaldo=C:\xampp\panel_db_backups\catalogo_categorias_arbol_1_6_20260814_121617_pre.json
```

## Ejecucion aplicada de arbol operativo 1-6

Fecha: 2026-08-14

Resultado:

- Categorias definidas: 192.
- Categorias creadas: 192.
- Categorias actualizadas: 0.
- Relaciones producto-categoria: 0.
- Productos sin categoria: 1610.

Validacion:

- El preview posterior del script indica 0 categorias pendientes por crear y 0 por actualizar.
- La auditoria de categorias conserva 0 textos danados, 0 padres inexistentes, 0 codigos duplicados y 0 rutas inconsistentes.

Pendiente operativo:

- Ocultar o inactivar categorias historicas que no pertenezcan al arbol `CAT16-*`, para evitar ruido visual durante la reclasificacion.
- La auditoria detecta nombre duplicado activo en raiz: `Aves` historico (`CLAS-HIST-5`) y `Aves` nuevo (`CAT16-AVES`).

## Preparacion para ocultar categorias historicas

Fecha: 2026-08-14

Script preparado:

```text
storage/uat/uat_catalogo_categorias_historicas_ocultar_apply.php
```

Preview:

- Categorias totales: 438.
- Categorias activas: 438.
- Categorias activas del arbol `CAT16-*`: 192.
- Categorias historicas activas a ocultar: 246.

Respaldo externo:

```text
C:\xampp\panel_db_backups\catalogo_categorias_arbol_1_6_20260814_125607_pre.json
```

Token requerido:

```text
CATALOGO_CATEGORIAS_HISTORICAS_OCULTAR
```

Comando de aplicacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_categorias_historicas_ocultar_apply.php --execute --token=CATALOGO_CATEGORIAS_HISTORICAS_OCULTAR --respaldo=C:\xampp\panel_db_backups\catalogo_categorias_arbol_1_6_20260814_125607_pre.json
```

Alcance:

- Cambia a `inactiva` toda categoria activa cuyo codigo no empieza con `CAT16-`.
- No borra categorias.
- No toca productos.
- No toca relaciones producto-categoria.

## Actualizacion 2026-08-14 - Categorias historicas ocultas

Autorizacion recibida:

- Token: `CATALOGO_CATEGORIAS_HISTORICAS_OCULTAR`.
- Respaldo externo: `C:\xampp\panel_db_backups\catalogo_categorias_arbol_1_6_20260814_125607_pre.json`.
- Proyecto aplicado: `C:\xampp\htdocs\panel_de_control`.

Resultado aplicado:

- Categorias totales: 438.
- Categorias activas: 192.
- Categorias activas del arbol operativo `CAT16-*`: 192.
- Categorias historicas activas: 0.
- Categorias historicas inactivadas: 246.
- Relaciones producto-categoria: 0.
- Productos sin categoria: 1607.
- Duplicados activos por nombre y mismo padre: 0.

Decision operativa:

- No se eliminaron categorias historicas; quedaron inactivas para conservar trazabilidad y posibilidad de auditoria.
- La UI de producto y asignacion masiva debe usar solo categorias activas, maestras y con `permite_productos=1`.
- La reclasificacion de productos queda lista para hacerse manualmente desde el arbol operativo 1-6.

Siguiente paso recomendado:

- Probar en UI que los selectores de categoria solo muestren el arbol operativo `CAT16-*`.
- Comenzar reclasificacion por bloques: primero especies principales, despues alimento/accesorios/equipo, y al final casos ambiguos.

## Actualizacion 2026-08-14 - UX de categorias operativas en Configuracion

Contexto:

- El arbol operativo `CAT16-*` ya estaba creado y las categorias historicas estaban inactivas.
- En `Configuracion > Categorias` seguian apareciendo categorias anteriores porque el listado administrativo consulta todas las categorias para auditoria y el filtro de estado estaba en `Todo estado`.

Ajuste aplicado en `C:\xampp\htdocs\panel_de_control`:

- `app/vistas/paginas/apps/erp/catalogo/configuracion.php`: el filtro de estado de categorias inicia en `Activas`.
- `public/assets/js/custom/apps/erp/catalogo/configuracion.js`: el filtro `Arbol principal ERP` ahora exige categoria activa, maestra y no historica/ecommerce.
- El selector de categoria padre al crear/editar categoria solo ofrece categorias activas del arbol operativo.

Validacion:

- `node --check public/assets/js/custom/apps/erp/catalogo/configuracion.js`: OK.
- `C:\xampp\php\php.exe -l app/vistas/paginas/apps/erp/catalogo/configuracion.php`: OK.
- Categorias que deberia mostrar el filtro principal: 192.
- Raices activas visibles: Perros, Gatos, Acuario y peces, Reptiles y tortugas, Aves, Pequenos mamiferos.

Nota operativa:

- Las categorias historicas siguen guardadas como `inactiva` para auditoria; si se selecciona `Todo estado` o `Inactivas`, pueden consultarse, pero ya no deben mezclarse con el arbol de trabajo diario.

## Actualizacion 2026-08-14 - Reclasificacion masiva por categoria principal/secundaria

Contexto:

- El arbol operativo `CAT16-*` ya esta activo y las relaciones producto-categoria fueron limpiadas para reclasificar desde cero.
- La lista de productos ya tenia accion masiva de marca, categoria, estado y proveedor, pero no distinguia si la categoria se aplicaba como principal o secundaria.

Ajuste aplicado en `C:\xampp\htdocs\panel_de_control`:

- `app/vistas/paginas/apps/erp/catalogo/productos.php`: se agrego selector `Modo categoria` en la barra masiva.
- `public/assets/js/custom/apps/erp/catalogo/productos.js`: la confirmacion indica si la categoria se aplicara como principal o secundaria.
- `app/modelos/CatalogoErpDatos.php`: `categoria_secundaria=1` agrega categoria alterna sin reemplazar ni crear categoria principal.

Regla operativa:

- Para la reclasificacion inicial usar `Modo categoria = Principal`.
- Usar `Secundaria` solo para navegacion/venta alterna cuando el producto ya tiene o tendra una categoria principal clara.

Validacion:

- `C:\xampp\php\php.exe -l app/vistas/paginas/apps/erp/catalogo/productos.php`: OK.
- `C:\xampp\php\php.exe -l app/modelos/CatalogoErpDatos.php`: OK.
- `node --check public/assets/js/custom/apps/erp/catalogo/productos.js`: OK.

Siguiente paso recomendado:

- En Productos, filtrar `Sin categoria`, seleccionar pagina y aplicar una categoria principal por bloques usando el arbol operativo.

## Actualizacion 2026-08-14 - Seleccion masiva de productos filtrados

Contexto:

- Para reclasificar 1600+ productos, seleccionar solo la pagina actual de 25 productos era lento.
- La lista de productos ya tiene filtros por saneamiento, busqueda y estado; faltaba seleccionar el bloque filtrado sin recorrer pagina por pagina.

Ajuste aplicado en `C:\xampp\htdocs\panel_de_control`:

- `app/vistas/paginas/apps/erp/catalogo/productos.php`: se agregaron botones `Seleccionar filtrados` y `Limpiar seleccion` en la barra masiva.
- `public/assets/js/custom/apps/erp/catalogo/productos.js`: se guardan los IDs filtrados actuales y se seleccionan hasta 250 productos por operacion.

Regla operativa:

- `Seleccionar filtrados` respeta busqueda, estado y filtro de saneamiento activos.
- El limite de 250 evita fallas con endpoints masivos que no aceptan lotes mayores.
- La seleccion no modifica datos; solo prepara el bloque. El cambio ocurre hasta presionar `Aplicar a seleccionados` y confirmar.

Validacion:

- `C:\xampp\php\php.exe -l app/vistas/paginas/apps/erp/catalogo/productos.php`: OK.
- `C:\xampp\php\php.exe -l app/modelos/CatalogoErpDatos.php`: OK.
- `node --check public/assets/js/custom/apps/erp/catalogo/productos.js`: OK.

Uso recomendado:

1. Filtrar `Sin categoria`.
2. Buscar por familia, marca o texto si quieres acotar el bloque.
3. Presionar `Seleccionar filtrados`.
4. Elegir categoria y `Modo categoria = Principal`.
5. Aplicar y repetir con el siguiente bloque.

## Actualizacion 2026-08-14 - Categoria visible en lista de productos

Contexto:

- Durante la reclasificacion era necesario saber desde la lista si un producto ya tenia categoria principal asignada.
- El backend ya entregaba `categoria`, por lo que no se requirio cambiar consulta ni esquema.

Ajuste aplicado en `C:\xampp\htdocs\panel_de_control`:

- `app/vistas/paginas/apps/erp/catalogo/productos.php`: se agrego columna `Categoria` en la tabla principal.
- `public/assets/js/custom/apps/erp/catalogo/productos.js`: se muestra la ruta de categoria como badge verde o `Sin categoria` como badge de advertencia.

Validacion:

- `C:\xampp\php\php.exe -l app/vistas/paginas/apps/erp/catalogo/productos.php`: OK.
- `node --check public/assets/js/custom/apps/erp/catalogo/productos.js`: OK.

Uso operativo:

- Despues de aplicar categorias por bloque, la lista permite confirmar avance sin abrir el modal de cada producto.

## Actualizacion 2026-08-15 - Clasificacion asistida lote 01 read-only

Contexto:

- El dueno del proyecto solicito ayuda para que IA proponga categorias y detecte categorias faltantes, sin hacer clasificacion manual producto por producto.
- Se mantiene la regla de no escribir BD sin autorizacion.

Entregables:

- Script read-only: `storage/uat/uat_catalogo_clasificacion_sugerida_readonly.php`.
- Documento de lote: `docs/erp_catalogo_clasificacion_asistida_lote_01.md`.

Resultado lote 01 (`--limite=200`):

- Alta confianza: 52.
- Media: 51.
- Baja: 19.
- Sin sugerencia: 78.

Categorias faltantes detectadas:

- `Reptiles y tortugas / Reptiles generales / Terrarios generales`.
- `Pequenos mamiferos / Animales vivos`.
- `Acuario y peces / Decoracion y ambientacion / Raices y troncos`.
- `Reptiles y tortugas / Reptiles generales / Sustratos`.
- `Pequenos mamiferos / Habitat y jaulas generales`.

Decision recomendada:

- Aplicar solo sugerencias de confianza alta con token explicito.
- Revisar medias/bajas antes de aplicar.
- Decidir categorias faltantes antes de clasificar productos de esos grupos.

Token sugerido para aplicar altas lote 01:

- `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_01`.

## Actualizacion 2026-08-15 - Apply preparado para altas lote 01

Contexto:

- El lote 01 read-only encontro 52 sugerencias de confianza alta.
- Se preparo aplicacion controlada para escribir solo esas relaciones como categoria principal.

Script preparado:

- `storage/uat/uat_catalogo_clasificacion_asistida_altas_lote_01_apply.php`.

Preview validado:

- Candidatos alta confianza: 52.
- Aplicables: 52.
- Omitidos por categoria previa: 0.
- Insertados en preview: 0.

Validacion:

- `C:\xampp\php\php.exe -l storage\uat\uat_catalogo_clasificacion_sugerida_readonly.php`: OK.
- `C:\xampp\php\php.exe -l storage\uat\uat_catalogo_clasificacion_asistida_altas_lote_01_apply.php`: OK.
- Ejecucion sin `--execute`: preview OK, sin cambios en BD.

Token requerido para aplicar:

- `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_01`.

Comando de aplicacion, solo despues de respaldo externo y autorizacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_clasificacion_asistida_altas_lote_01_apply.php --execute --token=CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_01 --respaldo=RUTA_RESPALDO_EXTERNO
```

Alcance:

- Inserta en `erp_catalogo_producto_categorias` solo productos de alta confianza del lote 01.
- Solo si el producto sigue sin categoria principal.
- No crea categorias.
- No modifica productos.
- No toca SKU, proveedores, inventario, ventas ni ecommerce.

## Actualizacion 2026-08-15 - Relacion asistida lote 01 aplicada

Se aplico el primer lote de clasificacion asistida solo para productos de confianza alta.

Autorizacion:

- Token: `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_01`.
- Respaldo externo: `C:\xampp\panel_db_backups\catalogo_producto_categorias_20260815_132438_pre_clasificacion_asistida_lote_01.sql`.

Resultado:

- 52 relaciones nuevas en `erp_catalogo_producto_categorias`.
- 53 productos con categoria principal al cierre de la verificacion.
- 1554 productos activos/no fusionados quedan sin categoria principal.

Control de riesgo:

- No se crearon categorias nuevas.
- No se modificaron productos.
- No se tocaron SKU, proveedores, inventario, ventas, ecommerce ni listas de precios.
- El apply fue corregido para usar solo columnas existentes de la tabla de relaciones.

Proximo bloque:

- Ejecutar lectura asistida lote 02.
- Aplicar solo altas con autorizacion.
- Revisar si se agregan categorias faltantes antes de aceptar sugerencias medias o bajas.

## Actualizacion 2026-08-15 - Lote 02 preparado para autorizacion

Se ejecuto lectura asistida sobre el siguiente bloque de 200 productos sin categoria principal.

Resumen:

- Alta confianza: 3.
- Media: 55.
- Baja: 21.
- Sin sugerencia: 121.

Entregables:

- `docs/erp_catalogo_clasificacion_asistida_lote_02.md`.
- `storage/uat/uat_catalogo_clasificacion_asistida_altas_lote_02_apply.php`.

Regla de control:

- El lote 02 usa candidatos fijos para evitar que el resultado cambie si otro producto se reclasifica antes de ejecutar.
- El lote 01 quedo cerrado y no debe reutilizarse.

Token pendiente:

- `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_02`.

## Actualizacion 2026-08-15 - Lote 02 aplicado

Se aplico el lote 02 de confianza alta.

Autorizacion:

- Token: `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_02`.
- Respaldo externo: `C:\xampp\panel_db_backups\catalogo_producto_categorias_20260815_antes_clasificacion_asistida_lote_02.sql`.

Resultado:

- 3 relaciones nuevas en `erp_catalogo_producto_categorias`.
- 56 productos con categoria principal al cierre.
- El lote 02 quedo cerrado para no reutilizar el token sobre otros productos.

Proximo bloque:

- Generar lote 03 read-only.
- Revisar categorias faltantes antes de seguir aplicando reglas con baja confianza.

## Actualizacion 2026-08-15 - Lote 03 preparado

Se preparo el lote 03 despues de enriquecer reglas read-only.

Resultado:

- Alta confianza: 19.
- Media: 55.
- Baja: 21.
- Sin sugerencia: 105.

Entregables:

- `docs/erp_catalogo_clasificacion_asistida_lote_03.md`.
- `storage/uat/uat_catalogo_clasificacion_asistida_altas_lote_03_apply.php`.

Preview:

- 19 aplicables.
- 0 omitidos por categoria previa.
- 0 insertados en preview.

Token pendiente:

- `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_03`.

Nota:

- Antes de seguir con mas lotes conviene resolver categorias faltantes recurrentes para reptiles, animales vivos de pequenos mamiferos y raices/troncos de acuario.

## Actualizacion 2026-08-15 - Lote 03 aplicado

Se aplico el lote 03 de confianza alta.

Autorizacion:

- Token: `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_03`.
- Respaldo externo: `C:\xampp\panel_db_backups\catalogo_producto_categorias_20260815_antes_clasificacion_asistida_lote_03.sql`.

Resultado:

- 19 relaciones nuevas en `erp_catalogo_producto_categorias`.
- 75 productos con categoria principal al cierre.
- El lote 03 quedo cerrado para no reutilizar el token sobre otros productos.

Proximo bloque:

- No generar mas apply automatico hasta decidir categorias faltantes recurrentes.

## Actualizacion 2026-08-17 - Categorias faltantes recurrentes listas para autorizacion

Se preparo el alta controlada de 5 categorias faltantes recurrentes del arbol `CAT16-*`.

Entregables:

- `docs/erp_catalogo_categorias_faltantes_recurrentes.md`.
- `storage/uat/uat_catalogo_categorias_faltantes_recurrentes_apply.php`.

Preview:

- Definidas: 5.
- A crear: 5.
- Existentes: 0.
- Errores: 0.

Token pendiente:

- `CATALOGO_CATEGORIAS_FALTANTES_RECURRENTES`.

Alcance:

- Solo crea categorias maestras activas.
- No modifica productos.
- No modifica relaciones producto-categoria.

## Actualizacion 2026-08-17 - Categorias recurrentes aplicadas y lote 04 preparado

Categorias faltantes recurrentes:

- Token aplicado: `CATALOGO_CATEGORIAS_FALTANTES_RECURRENTES`.
- Respaldo externo: `C:\xampp\panel_db_backups\catalogo_categorias_20260817_antes_categorias_faltantes_recurrentes.sql`.
- Categorias creadas: 5.

Lote 04:

- Script: `storage/uat/uat_catalogo_clasificacion_asistida_altas_lote_04_apply.php`.
- Documento: `docs/erp_catalogo_clasificacion_asistida_lote_04.md`.
- Candidatos alta confianza: 29.
- Aplicables en preview: 29.

Token pendiente:

- `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_04`.

## Actualizacion 2026-08-18 - Lote 04 aplicado

Se aplico el lote 04 de confianza alta.

Autorizacion:

- Token: `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_04`.
- Respaldo externo: `C:\xampp\panel_db_backups\catalogo_producto_categorias_20260818_antes_clasificacion_asistida_lote_04.sql`.

Resultado:

- 25 relaciones nuevas insertadas.
- 4 candidatos fueron omitidos porque ya tenian categoria principal.
- Los 29 candidatos del lote quedaron con categoria principal.
- 155 productos tienen categoria principal al cierre.
- 1452 productos activos/no fusionados quedan sin categoria principal.

Proximo bloque:

- Generar lote 05 read-only y no aplicar nada sin token nuevo.
