# ERP Almacen - Sucursales, almacenes y ubicaciones

Fecha: 2026-06-21

Modulo propuesto: ERP > Almacen > Configuracion

Clave de trabajo: `ALM-CFG-001`

## Objetivo

Definir y construir la administracion operativa de lugares fisicos donde puede existir inventario, sin mezclar el ERP nuevo con el flujo legacy de sucursales.

El modulo debe permitir:

- Alta, edicion, consulta e inactivacion de almacenes/puntos fisicos.
- Alta, edicion, consulta e inactivacion de ubicaciones internas por almacen.
- Separar tiendas, bodega/casa de almacen, areas tecnicas y futuras ubicaciones.
- Usar esos lugares en Compras, Recepcion, Inventario, Preparacion/Empaque y Traspasos.
- Preparar el camino para permisos por almacen.

## Criterio ERP recomendado

En un ERP robusto conviene separar conceptos:

- **Empresa / matriz legal**: entidad fiscal o administrativa. No necesariamente tiene stock.
- **Sucursal / punto de venta**: lugar donde se vende y puede tener existencia propia.
- **Almacen / bodega**: lugar donde se guarda existencia; puede o no vender.
- **Ubicacion interna**: zona, pasillo, rack, nivel, contenedor o area dentro de un almacen.

Para este proyecto, `erp_almacenes` debe ser la tabla oficial de cualquier lugar que afecte inventario. Una tienda que tiene stock vendible tambien se registra como almacen de tipo `sucursal` o `punto_venta`.

`erp_sucursales` existe, pero por ahora se considera legacy/administrativa y no debe usarse como fuente de verdad para inventario hasta que se disene una migracion o relacion formal.

## Caso operativo del negocio

El usuario describe un mismo terreno con areas separadas:

- Local de acuario, operativamente independiente.
- Local de accesorios para mascotas en Francisco Javier Mina 971.
- Casa/parte trasera usada como almacen en la misma direccion de Francisco Javier Mina 971.
- La direccion Francisco Javier Mina 967 corresponde al local del acuario.
- Las direcciones Francisco Javier Mina 1105 y San Jose 1727 ya no existen operativamente.

Modelo recomendado:

| Lugar fisico | Tipo recomendado | Maneja inventario | Uso |
| --- | --- | --- | --- |
| Francisco Javier Mina 967 - Acuario | `punto_venta` | Si | Venta y stock propio de acuario |
| Francisco Javier Mina 971 - Mascotas frontal | `punto_venta` | Si | Venta y stock propio de accesorios/mascotas |
| Francisco Javier Mina 971 - Bodega trasera | `bodega` | Si | Recepcion, resguardo, preparacion, surtido a tiendas |
| Transito | `transito` | Si, tecnico | Mercancia en movimiento |
| Devoluciones | `devoluciones` | Si, tecnico | Producto devuelto pendiente de revision |
| Merma | `merma` | Si, tecnico | Producto no vendible |
| Cuarentena | `cuarentena` | Si, tecnico | Producto recibido pendiente de liberar |

Recomendacion inicial: usar `bodega` para la casa/almacen trasero, no `matriz`, salvo que se quiera usar "matriz" como concepto administrativo/fiscal. Para inventario, el nombre operativo importante es bodega/almacen principal.

Aunque el local frontal de mascotas y la bodega trasera compartan la misma direccion fisica, deben ser almacenes logicos separados porque sus existencias y operaciones son distintas.

## Estado actual auditado

Tabla oficial para inventario:

- `erp_almacenes`

Columnas actuales relevantes:

- `id_almacen`
- `almacen`
- direccion: pais, estado, municipio, ciudad, colonia, codigo_postal, calle, numeros, referencias
- contacto de recepcion
- `estatus`
- `tipo_almacen`

Registros actuales:

- `1` - Francisco Javier Mina 1105 - `activo` / `sucursal`
- `2` - San Jose 1727 - `activo` / `sucursal`
- `3` - Francisco Javier Mina 971 - `activo` / `sucursal`

Correccion operativa indicada por el dueno el 2026-06-21:

- `id_almacen=1` Francisco Javier Mina 1105 ya no existe operativamente.
- `id_almacen=2` San Jose 1727 ya no existe operativamente.
- `id_almacen=3` Francisco Javier Mina 971 existe, pero debe separarse conceptualmente entre local frontal de mascotas y bodega trasera.
- Falta registrar Francisco Javier Mina 967 como local/punto de venta de acuario.
- Falta registrar Francisco Javier Mina 971 local frontal como punto de venta de mascotas si `id_almacen=3` se conserva como bodega trasera.

Uso actual con datos:

- Recepciones: almacen `3`.
- Existencias: almacenes `1` y `3`.
- Movimientos: almacenes `1` y `3`.
- Ubicaciones: almacen `3`.

Integridad actual:

- `erp_almacen_recepciones.id_almacen` tiene FK a `erp_almacenes.id_almacen`.
- `erp_almacen_recepciones_lotes.id_almacen` tiene FK a `erp_almacenes.id_almacen`.
- `erp_inventario_existencias.id_almacen_clave` tiene FK a `erp_almacenes.id_almacen`.
- `erp_inventario_movimientos.id_almacen` tiene FK a `erp_almacenes.id_almacen`.
- `erp_almacen_ubicaciones.id_almacen_clave` tiene FK a `erp_almacenes.id_almacen`.

Conclusion: conviene evolucionar `erp_almacenes` como tabla oficial en lugar de crear una tabla nueva principal.

Tabla de ubicaciones:

- `erp_almacen_ubicaciones`

Columnas actuales:

- `id_ubicacion`
- `id_almacen_clave`
- `codigo_ubicacion`
- `nombre`
- `zona`, `pasillo`, `rack`, `nivel`, `contenedor`
- `descripcion`
- `estatus`

Tabla legacy/administrativa:

- `erp_sucursales`

Tablas residuales detectadas:

- `erp_almacen`: vacia, estructura antigua similar a `erp_almacenes`.
- `erp_almacenes_copy1`: vacia, copia residual.
- `erp_sucursales_almacenes`: vacia, relacion antigua sin uso actual.
- `erp_productos_combinaciones_almacenes`: vacia, residual de combinaciones/productos legacy.

Riesgo:

- `Sucursal.php` y `Sucursales.php` no usan permisos finos ERP, no estan conectados al inventario nuevo y conservan patrones legacy.
- No deben ampliarse como fuente principal de inventario sin redisenar.

## Hallazgos

| ID | Severidad | Hallazgo | Recomendacion |
| --- | --- | --- | --- |
| `ALM-CFG-H001` | Alta | No existe CRUD ERP nuevo para `erp_almacenes`. | Crear `Almacen > Configuracion > Almacenes` usando `erp_almacenes` como fuente oficial. |
| `ALM-CFG-H002` | Alta | `erp_sucursales` existe, pero no esta integrada al inventario nuevo. | Tratarla como legacy/administrativa hasta definir migracion o relacion. |
| `ALM-CFG-H003` | Media | Falta clasificacion operativa mas clara para bodega, punto venta y almacenes tecnicos. | Normalizar catalogo de tipos y validar en UI. |
| `ALM-CFG-H004` | Media | No hay administracion visual de ubicaciones por almacen. | Crear CRUD de ubicaciones dependiente de almacen. |
| `ALM-CFG-H005` | Media | Los permisos actuales son globales por modulo, no por almacen. | Disenar alcance por usuario/almacen despues del CRUD base. |
| `ALM-CFG-H006` | Media | `erp_almacenes` no tiene codigo corto, flags operativos ni orden de despliegue. | Agregar columnas incrementales antes del CRUD si se autoriza. |
| `ALM-CFG-H007` | Baja | Hay tablas vacias/residuales relacionadas con almacenes y sucursales. | No usarlas en el ERP nuevo; evaluar limpieza/migracion en fase posterior. |

## Tipos recomendados

Tipos de almacen/lugar inventariable:

- `punto_venta`: local que vende y tiene stock propio.
- `sucursal`: punto fisico que vende y tiene stock.
- `bodega`: almacen operativo sin venta directa o con venta limitada.
- `principal`: bodega central cuando se quiera marcar una como origen principal.
- `transito`: mercancia en movimiento.
- `devoluciones`: producto devuelto pendiente de revision.
- `merma`: producto no vendible.
- `cuarentena`: recibido o retenido pendiente de liberar.

Estatus:

- `activo`: disponible para operaciones nuevas.
- `inactivo`: no disponible para operaciones nuevas, conserva historial.

Regla: no borrar almacenes con movimientos; se inactivan.

## CRUD recomendado

### Almacenes

Campos minimos:

- Nombre operativo.
- Tipo.
- Estatus.
- Direccion.
- Contacto de recepcion.
- Telefono/email de recepcion.
- Observaciones o referencias de acceso.

Campos deseables en una fase posterior:

- Codigo corto de almacen.
- Permite recepcion.
- Permite venta.
- Permite preparacion/empaque.
- Permite ajustes.
- Es tecnico.
- Orden de despliegue.

Propuesta actual: agregar estos campos desde la primera version del CRUD, porque reducen ambiguedad operativa y ayudan a filtrar pantallas:

- `codigo_almacen`: codigo corto unico, por ejemplo `ACUARIO`, `MASCOTAS`, `BOD-TRASERA`.
- `nombre_comercial`: nombre visible alterno si se requiere.
- `permite_recepcion`: si puede recibir compras.
- `permite_venta`: si puede vender/ser punto de venta.
- `permite_preparacion`: si puede preparar/empaquetar.
- `permite_ajustes`: si permite ajustes manuales.
- `es_tecnico`: para transito, merma, devoluciones o cuarentena.
- `orden`: orden visual en combos.
- `observaciones`: notas operativas.
- `fecha_actualizacion`: auditoria basica.

### Ubicaciones

Campos minimos:

- Almacen.
- Codigo de ubicacion.
- Nombre.
- Zona.
- Pasillo.
- Rack.
- Nivel.
- Contenedor.
- Estatus.
- Descripcion.

Regla: la ubicacion pertenece a un solo almacen y su codigo debe ser unico dentro de ese almacen.

## Orden recomendado de implementacion

1. Documentar decision y alcance del modulo `ALM-CFG-001`.
2. Auditar si `erp_almacenes` requiere columnas adicionales para operar bien el CRUD. **Completado en lectura el 2026-06-21.**
3. Proponer DDL incremental si faltan columnas como codigo, flags operativos u orden. **Preparado en `docs/erp_almacen_configuracion_schema_propuesta.sql`; no ejecutar sin autorizacion.**
4. Implementar endpoints ERP protegidos en `Almacen.php`.
5. Implementar metodos de dominio en `Almacenes.php`.
6. Crear vista `Almacen > Configuracion` con pestañas:
   - Almacenes
   - Ubicaciones
7. Agregar enlace al menu.
8. UAT sin borrar datos:
   - crear/inactivar un almacen de prueba si se autoriza;
   - crear ubicacion de prueba;
   - validar que recepcion, preparacion e inventario lo vean.
9. Disenar despues alcance por usuario/almacen.

## Reglas de seguridad

- Ver catalogo: `almacen.ver`.
- Crear/editar/inactivar almacenes y ubicaciones: usar `almacen.ubicaciones` inicialmente o proponer permiso nuevo `almacen.configurar`.
- No crear permiso nuevo sin revisar `SeguridadEsquema.php` y autorizar el criterio.

## Criterio de cierre

- Documento de diseno listo.
- Auditoria de tablas actuales lista.
- Propuesta de DDL si falta estructura.
- Orden de implementacion definido.
- Sin aplicar migraciones ni altas reales hasta autorizacion.

## Autorizacion requerida

Autorizacion recibida y ejecutada el 2026-06-21:

1. Crear respaldo externo de BD.
2. Aplicar DDL incremental de configuracion de almacenes.
3. Sembrar/corregir valores iniciales de almacenes operativos.

Respaldo:

- `storage/backups/artianilocal_almacen_configuracion_20260621_antes_ddl.sql`
- Tamano: `53170006` bytes.

DDL aplicado:

- `docs/erp_almacen_configuracion_schema_propuesta.sql`

Resultado:

- Almacenes activos:
  - `ACUARIO967` - Francisco Javier Mina 967 - Acuario - `punto_venta`
  - `MASCOTAS971` - Francisco Javier Mina 971 - Mascotas frontal - `punto_venta`
  - `BOD971` - Francisco Javier Mina 971 - Bodega trasera - `bodega`
- Almacenes historicos inactivos:
  - `MINA1105-BAJA`
  - `SANJOSE1727-BAJA`
- Ubicaciones existentes permanecen en `BOD971` (`id_almacen=3`).

## Implementacion CRUD inicial

Fecha: 2026-06-21

Cambios:

- `Almacen::configuracion()`
- `Almacen::almacenes_configuracion_erp()`
- `Almacen::almacen_configuracion_guardar_erp()`
- `Almacen::ubicaciones_configuracion_erp()`
- `Almacen::ubicacion_configuracion_guardar_erp()`
- `Almacenes::consultar_almacenes_configuracion()`
- `Almacenes::guardar_almacen_configuracion()`
- `Almacenes::consultar_ubicaciones_configuracion()`
- `Almacenes::guardar_ubicacion_configuracion()`
- Vista `app/vistas/paginas/apps/erp/almacen/configuracion.php`
- JS `public/assets/js/custom/apps/erp/almacen/configuracion.js`
- Menu lateral: `Almacen > Configuracion`

Validaciones:

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`: OK.
- `C:\xampp\php\php.exe -l app\controladores\Almacen.php`: OK.
- `C:\xampp\php\php.exe -l app\modelos\AlmacenEsquema.php`: OK.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\almacen\configuracion.php`: OK.
- `C:\xampp\php\php.exe -l app\modelos\InventarioErp.php`: OK.
- `C:\xampp\php\php.exe -l app\modelos\OrdenesCompraErp.php`: OK.
- `C:\xampp\php\php.exe -l app\modelos\SolicitudesCompraErp.php`: OK.
- `node --check public\assets\js\custom\apps\erp\almacen\configuracion.js`: OK.

Pendientes:

- UAT visual de la pantalla `Almacen > Configuracion`.
- Disenar alcance por usuario/almacen.

## Auditoria posterior de almacenes historicos

Fecha: 2026-06-21

Consulta de solo lectura:

- `MINA1105-BAJA` conserva 1 registro en `erp_inventario_existencias`, SKU `SP-2823`, con `cantidad=0.0000`, `cantidad_disponible=0.0000` y `estatus_existencia=agotada`.
- `SANJOSE1727-BAJA` no conserva registros de existencia.

Decision operativa:

- No se requiere traspaso ni ajuste por saldo fisico en almacenes historicos en este momento.
- El registro agotado puede conservarse como historial tecnico del kardex, siempre que los flujos nuevos filtren almacenes activos.

Refuerzos aplicados:

- Inventario rechaza ajustes/traspasos contra almacenes inactivos.
- Solicitudes y ordenes de compra solo listan destinos activos con `permite_recepcion` y rechazan destino inactivo o sin `permite_recepcion`.
- Recepcion de almacen valida que el almacen siga activo y permita recepcion antes de afectar existencias.
- Preparacion/empaque valida que el almacen siga activo y permita preparacion.

## UAT propuesto: ALM-CFG-001

Objetivo:

- Validar que `Almacen > Configuracion` permita consultar la estructura operativa actual sin mezclar almacenes historicos ni sucursales administrativas.

Pasos sin escritura:

1. Entrar a `Almacen > Configuracion`.
2. Confirmar que en el tab `Almacenes`, filtro `Activos`, aparezcan:
   - `ACUARIO967` - Francisco Javier Mina 967 - Acuario.
   - `MASCOTAS971` - Francisco Javier Mina 971 - Mascotas frontal.
   - `BOD971` - Francisco Javier Mina 971 - Bodega trasera.
3. Cambiar filtro a `Inactivos` y confirmar que aparezcan:
   - `MINA1105-BAJA`.
   - `SANJOSE1727-BAJA`.
4. En el tab `Ubicaciones`, confirmar que los filtros solo permitan seleccionar almacenes activos.
5. Ir a Compras, solicitud u orden de compra, y confirmar que el destino solo muestre almacenes activos con recepcion habilitada.

Pasos con escritura, requieren autorizacion o ejecucion manual del usuario:

1. Editar un almacen activo y guardar un cambio menor controlado, por ejemplo una observacion.
2. Crear una ubicacion de prueba en `BOD971`.
3. Inactivar esa ubicacion de prueba.
4. Documentar evidencia por folio/hallazgo si falla algun punto.

Resultado esperado:

- No se crean existencias.
- No se mueve inventario.
- No aparecen almacenes historicos como destino operativo.
- Toda escritura queda limitada a configuracion de almacenes/ubicaciones.

## Evidencia UAT: ALM-CFG-001

Fecha: 2026-06-21

Resultado reportado por usuario:

- Pruebas visuales y de escritura controlada ejecutadas sin errores.

Evidencia verificada en BD:

- `BOD971` conserva observacion de prueba `UAT ALM-CFG-001 2026-06-21`.
- Ubicacion `UAT-BOD971-001` fue creada en `BOD971` y quedo en estatus `inactiva`.
- El texto `Ubicación prueba UAT` quedo guardado correctamente en `utf8mb4`.
- El catalogo de destinos de recepcion devuelve solo:
  - `ACUARIO967`.
  - `MASCOTAS971`.
  - `BOD971`.

Cierre:

- `ALM-CFG-001` aprobado.
- No se detectaron errores funcionales en la prueba.
- No se crearon existencias ni movimientos de inventario durante este UAT.
