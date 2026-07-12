# ERP Almacen - Resurtido y traspasos tareas vivas

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-11  
Modulo: ERP > Almacen > Resurtido / Traspasos entre tiendas  
Estado: primeras tareas definidas; sin escrituras de BD.

## Regla de trabajo

- Tareas pequenas.
- UAT por folio/SKU/almacen.
- Hallazgos con ID `RES-H###`.
- No tocar Ventas/POS/ecommerce.
- No ejecutar migraciones ni escrituras de BD sin respaldo externo y autorizacion.
- No mezclar ERP nuevo con legacy.
- Documentar handoff futuro cuando algo afecte POS/ecommerce.

## Documentos relacionados

- `docs/erp_almacen_resurtido_traspasos_arranque.md`
- `docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql`
- `docs/erp_almacen_sucursales_almacenes_arranque.md`
- `docs/erp_inventario_existencias_arranque.md`
- `docs/erp_almacen_unidades_fisicas_arranque.md`
- `docs/erp_inventario_existencias_unidades_abiertas_handoff.md`
- `docs/erp_notificaciones_alertas_trabajo.md`

## Bloques de implementacion

| ID | Tarea | Resultado esperado | Autorizacion |
| --- | --- | --- | --- |
| `RES-T001` | Documentar auditoria inicial | Arranque con hallazgos, decision y UAT minimo | No |
| `RES-T002` | Preparar DDL propuesto | SQL revisable sin ejecutar | No |
| `RES-T003` | Definir estados/permisos | Contrato operativo antes de codigo | No |
| `RES-T004` | Preflight stock bajo por tienda/SKU | Consulta read-only de necesidades | No |
| `RES-T005` | Diseno UX de listado/captura | Pantallas y acciones esperadas | No |
| `RES-T006` | Integrar auditoria de esquema en plan | `AlmacenEsquema` detecta faltantes sin ejecutar DDL | No para codigo; si se ejecuta plan write, si |
| `RES-T007` | Aplicar DDL | Tablas reales de resurtido | Si, con respaldo externo |
| `RES-T007A` | Pantalla read-only antes de DDL | Menu, vista y endpoints GET sin escritura | No |
| `RES-T007B` | Detalle read-only por folio | Consulta documental completa sin escritura | No |
| `RES-T007C` | UAT read-only del modulo | Prueba repetible antes/despues de DDL | No |
| `RES-T008` | Backend solicitud/resurtido | Crear/listar/consultar/autorizacion sin mover inventario | Si para UAT con escritura |
| `RES-T009` | Backend preparacion/envio | Salida origen + transito con trazabilidad | Si, con respaldo externo |
| `RES-T010` | Backend recepcion/diferencias | Recepcion tienda y diferencias persistentes | Si, con respaldo externo |
| `RES-T011` | UI operativa | Pantalla Almacen > Resurtido | No para codigo; si UAT real mueve stock, si |
| `RES-T012` | Alertas stock bajo | Integracion con Notificaciones | Si hay semillas o datos |
| `RES-T013` | UAT cierre modulo | Casos `RES-UAT-*` documentados | Si para movimientos reales |

## Estado actual

- `RES-T001`: completada.
- `RES-T002`: completada como propuesta documental.
- `RES-T003`: completada en contrato inicial de estados, transiciones, permisos candidatos y auditoria.
- `RES-T004`: completada primera version read-only con reglas globales de Catalogo como fallback.
- `RES-T005`: completada en diseno UX operativo inicial.
- `RES-T006`: completada en auditoria/plan dry-run, sin ejecutar DDL.
- `RES-T007`: paquete de autorizacion preparado; aplicacion bloqueada hasta respaldo externo y autorizacion textual.
- `RES-T007A`: completada pantalla inicial read-only de Almacen > Resurtido.
- `RES-T007B`: completada consulta read-only de folio y trazabilidad documental.
- `RES-T007C`: completada prueba UAT read-only del modulo.
- `RES-T008` en adelante: bloqueadas hasta autorizacion de DDL/respaldo.

## RES-T007A - Pantalla read-only antes de DDL

Fecha: 2026-07-11

Objetivo:

- Permitir revisar el modulo desde el menu sin crear datos.
- Exponer preflight de stock bajo por tienda/SKU usando el endpoint read-only ya validado.
- Dejar visible que la creacion de solicitudes queda bloqueada hasta aplicar esquema con respaldo externo.

Implementado:

- Vista: `app/vistas/paginas/apps/erp/almacen/resurtido.php`.
- JS: `public/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js`.
- Menu: `Almacen > Resurtido`.
- Controlador:
  - `Almacen::resurtido()`
  - `Almacen::resurtido_listar_erp()`
- Modelo:
  - `Almacenes::consultar_resurtidos_readonly()`
  - `Almacenes::tablaExisteAlmacen()`

Contrato:

- Solo usa `GET`.
- No crea solicitudes.
- No mueve inventario.
- No crea alertas persistentes.
- Si faltan tablas, devuelve `schema_pendiente=1`, lista vacia y mensaje informativo.

Validaciones:

- `php -l app/controladores/Almacen.php`: OK.
- `php -l app/modelos/Almacenes.php`: OK.
- `php -l app/vistas/paginas/apps/erp/almacen/resurtido.php`: OK.
- `node --check public/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js`: OK.
- Prueba CLI read-only de `consultar_resurtidos_readonly()`: `schema_pendiente=1`, `total=0`.

## RES-T007B - Detalle read-only por folio

Fecha: 2026-07-12

Objetivo:

- Preparar la consulta completa por folio antes de habilitar escrituras.
- Validar que el backend use los nombres reales de la propuesta DDL (`id_resurtido_almacen`).
- Permitir UAT documental por folio/SKU cuando el esquema este aplicado.

Implementado:

- Controlador: `Almacen::resurtido_consultar_erp()`.
- Modelo: `Almacenes::consultar_resurtido_readonly()`.
- UI: folio clicable en `Almacen > Resurtido` y panel lateral `Detalle folio`.

Alcance de la consulta:

- Encabezado del resurtido.
- Detalle por SKU.
- Preparacion por existencia, lote, caducidad, ubicacion y unidad fisica.
- Envios y referencias a movimientos/transito.
- Recepciones en tienda.
- Diferencias por faltante, dano, lote o caducidad.

Contrato:

- Solo usa `GET`.
- No crea ni modifica datos.
- No mueve inventario.
- Si faltan tablas, devuelve `schema_pendiente=1`.

Correccion aplicada:

- El listado read-only quedo alineado con el DDL propuesto:
  - llave primaria `id_resurtido_almacen`;
  - destino operativo como `id_almacen_solicitante`;
  - sin uso de columnas no propuestas como `fecha_requerida`.

Validaciones:

- `php -l app/controladores/Almacen.php`: OK.
- `php -l app/modelos/Almacenes.php`: OK.
- `php -l app/vistas/paginas/apps/erp/almacen/resurtido.php`: OK.
- `node --check public/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js`: OK.
- Prueba CLI read-only de listado: `schema_pendiente=1`, `total=0`.
- Prueba CLI read-only de detalle: `schema_pendiente=1`, `detalle=0`.

## RES-T007C - UAT read-only del modulo

Fecha: 2026-07-12

Archivo:

- `storage/uat/uat_almacen_resurtido_readonly.php`

Objetivo:

- Validar el modulo sin ejecutar DDL ni escribir datos.
- Confirmar que vista/JS existen.
- Confirmar que listado y detalle responden en modo `schema_pendiente`.
- Confirmar que el preflight de stock bajo sigue funcionando con datos reales.
- Reportar guardrails para DDL y UAT posterior.

Resultado ejecutado:

- `ok=true`.
- `read_only=true`.
- Tablas pendientes detectadas: 7.
- Listado read-only: `schema_pendiente=1`, `total=0`.
- Detalle read-only: `schema_pendiente=1`.
- Stock bajo: `success`, `total=2`.
- Almacen usado por preflight: `Francisco Javier Mina 967 - Acuario` (`id_almacen=4`).

Guardrails confirmados:

- Requiere respaldo externo para DDL.
- Requiere autorizacion textual:
  - `AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA`
- No escribe BD.
- No mueve kardex.
- No modifica etiquetas.
- No toca POS/ecommerce.

## Contrato preliminar de estados

Estados del encabezado:

- `borrador`: captura interna no enviada.
- `solicitado`: tienda envio solicitud.
- `autorizado`: responsable aprueba total/parcial.
- `rechazado`: responsable rechaza antes de preparar.
- `preparando`: bodega esta seleccionando existencias/unidades.
- `preparado`: surtido listo para enviar.
- `enviado`: mercancia salio de origen y esta en transito.
- `recibido_parcial`: tienda recibio con faltantes o diferencias pendientes.
- `recibido`: tienda recibio completo.
- `cerrado`: diferencias resueltas y documento concluido.
- `cancelado`: documento cancelado sin movimiento pendiente.

Estados de detalle:

- `pendiente`
- `autorizado`
- `rechazado`
- `preparado`
- `enviado`
- `recibido`
- `diferencia`
- `cancelado`

Estados de diferencia:

- `abierta`
- `en_revision`
- `resuelta`
- `cancelada`

## Permisos sugeridos

No crear permisos sin revisar `SeguridadPermisos.php`, `SeguridadEsquema.php`, `Core.php` y convenciones existentes.

Permisos candidatos:

- `almacen.resurtido.ver`
- `almacen.resurtido.solicitar`
- `almacen.resurtido.autorizar`
- `almacen.resurtido.preparar`
- `almacen.resurtido.enviar`
- `almacen.resurtido.recibir`
- `almacen.resurtido.diferencias`
- `almacen.resurtido.configurar`

Alternativa conservadora inicial:

- usar temporalmente `almacen.ver` para consultas;
- usar `almacen.recibir` para acciones operativas;
- usar `inventario.traspasar` para envio;
- crear permisos finos cuando el flujo este estable.

Recomendacion:

- Crear permisos finos antes de usar en operacion real, porque tiendas y bodega no deben tener las mismas acciones.

## RES-T003 - Contrato inicial de estados, permisos y auditoria

Fecha: 2026-07-11

Auditoria de seguridad revisada:

- `SeguridadEsquema::permisosBaseERP()` ya define permisos base para Almacen e Inventario.
- Permisos actuales relacionados:
  - `almacen.ver`
  - `almacen.recibir`
  - `almacen.ubicaciones`
  - `inventario.ver`
  - `inventario.traspasar`
  - `inventario.ajustar`
  - `inventario.conteo`
- Roles base actuales:
  - `almacen` tiene `almacen.ver`, `almacen.recibir`, `almacen.ubicaciones`, `inventario.ver`, `inventario.traspasar`.
  - `inventario` tiene `inventario.ver`, `inventario.ajustar`, `inventario.traspasar`, `inventario.conteo`.
  - `auditor` tiene consulta sin operacion transaccional.
- `Core.php` ya contempla auditoria explicita para `Inventario.traspasar_erp` y `Almacen.guardar_recepcion`.

Decision:

- Para codigo read-only/preflight se puede usar `almacen.ver` o `inventario.ver`.
- Para operacion real del modulo conviene crear permisos finos de resurtido.
- No conviene reutilizar solo `inventario.traspasar` para todo, porque autorizar, preparar, enviar y recibir son responsabilidades distintas.

Permisos finos recomendados:

| Permiso | Uso |
| --- | --- |
| `almacen.resurtido.ver` | Consultar solicitudes, envios, recepciones y diferencias |
| `almacen.resurtido.solicitar` | Crear solicitud desde tienda |
| `almacen.resurtido.autorizar` | Aprobar, rechazar o ajustar cantidades |
| `almacen.resurtido.preparar` | Seleccionar existencias/unidades origen |
| `almacen.resurtido.enviar` | Confirmar salida y transito |
| `almacen.resurtido.recibir` | Confirmar recepcion en tienda |
| `almacen.resurtido.diferencias` | Resolver diferencias de recepcion |
| `almacen.resurtido.configurar` | Configurar politicas por tienda/SKU |

Asignacion inicial sugerida por rol:

| Rol | Permisos sugeridos |
| --- | --- |
| `direccion` | ver, autorizar, diferencias |
| `administrador_erp` | todos |
| `almacen` | ver, preparar, enviar, recibir |
| `inventario` | ver, configurar, diferencias |
| `ventas` | ver limitado en fase futura, no operar |
| `auditor` | ver |
| `solo_lectura` | ver si negocio lo permite |

### Transiciones permitidas

| Estado actual | Accion | Estado destino | Permiso recomendado |
| --- | --- | --- | --- |
| `borrador` | enviar solicitud | `solicitado` | `almacen.resurtido.solicitar` |
| `solicitado` | autorizar total/parcial | `autorizado` | `almacen.resurtido.autorizar` |
| `solicitado` | rechazar | `rechazado` | `almacen.resurtido.autorizar` |
| `autorizado` | iniciar preparacion | `preparando` | `almacen.resurtido.preparar` |
| `preparando` | confirmar preparacion | `preparado` | `almacen.resurtido.preparar` |
| `preparado` | enviar | `enviado` | `almacen.resurtido.enviar` |
| `enviado` | recibir parcial | `recibido_parcial` | `almacen.resurtido.recibir` |
| `enviado` | recibir completo | `recibido` | `almacen.resurtido.recibir` |
| `recibido_parcial` | resolver diferencias | `cerrado` o `recibido` | `almacen.resurtido.diferencias` |
| `recibido` | cerrar | `cerrado` | `almacen.resurtido.recibir` |
| `borrador`/`solicitado`/`autorizado` | cancelar | `cancelado` | segun estado y permiso responsable |

Reglas:

- No permitir volver de `enviado` a `preparado` si ya hubo movimientos.
- No permitir cancelar `enviado` sin recepcion, diferencia o reversa documentada.
- No permitir editar cantidades autorizadas despues de `preparado`; usar ajuste documental o cancelar antes.
- Autorizacion parcial, rechazo, diferencia y cancelacion requieren comentario.
- Si una linea preparada mezcla lotes, debe dividirse por lote/caducidad/unidad.

### Auditoria explicita recomendada

Acciones que deben registrar auditoria explicita:

- `resurtido_solicitar_erp`
- `resurtido_autorizar_erp`
- `resurtido_rechazar_erp`
- `resurtido_preparar_erp`
- `resurtido_enviar_erp`
- `resurtido_recibir_erp`
- `resurtido_diferencia_resolver_erp`
- `resurtido_cancelar_erp`
- `resurtido_politica_guardar_erp`

Entidad principal:

```text
erp_almacen_resurtidos
```

Entidades secundarias:

```text
erp_almacen_resurtido_detalle
erp_almacen_resurtido_preparacion
erp_almacen_resurtido_envios
erp_almacen_resurtido_recepciones
erp_almacen_resurtido_diferencias
erp_inventario_politicas_almacen_sku
```

Resultado:

- `RES-T003` queda documentada.
- No se modifico codigo ni BD.

## RES-T004 - Preflight stock bajo por tienda/SKU

Fecha: 2026-07-11

Objetivo:

- Consultar necesidades potenciales de resurtido por almacen/SKU sin crear documentos, alertas ni movimientos.
- Usar reglas globales de Catalogo mientras no exista politica local por tienda/SKU.

Endpoint agregado:

```text
GET /almacen/resurtido_stock_bajo_preflight_erp
```

Permiso:

```text
almacen.ver
```

Metodo de dominio:

```text
Almacenes::preflight_stock_bajo_resurtido($filtros)
```

Filtros soportados:

- `id_almacen`: obligatorio.
- `id_sku`: opcional.
- `q`: busqueda por SKU/producto.
- `solo_bajos`: default `1`.

Contrato:

- Read-only.
- No usa tablas nuevas.
- No crea solicitudes.
- No crea notificaciones.
- No mueve inventario.
- Devuelve `politica_fuente='catalogo_global'` hasta que exista `erp_inventario_politicas_almacen_sku`.

Formula aplicada:

```text
umbral_usado = punto_reorden > 0 ? punto_reorden : stock_minimo
requiere_resurtido = umbral_usado > 0 AND disponible <= umbral_usado
si stock_maximo existe:
  cantidad_sugerida = stock_maximo - disponible
si no:
  cantidad_sugerida = punto_reorden - disponible o stock_minimo - disponible
```

Archivos modificados:

- `app/controladores/Almacen.php`
- `app/modelos/Almacenes.php`

Validaciones tecnicas:

| Comando | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l app/controladores/Almacen.php` | OK |
| `C:\xampp\php\php.exe -l app/modelos/Almacenes.php` | OK |

Prueba read-only CLI:

```text
Almacenes::preflight_stock_bajo_resurtido(id_almacen=3, solo_bajos=1)
```

Resultado:

```json
{"error":false,"tipo":"success","mensaje":"Preflight de stock bajo consultado","total":1}
```

Hallazgo derivado:

- `RES-H009`: el preflight ya puede detectar necesidades con politica global, pero falta politica local por tienda/SKU para que Acuario y Mascotas tengan minimos/maximos propios.

## RES-T005 - Diseno UX operativo inicial

Fecha: 2026-07-11

Objetivo:

- Definir la primera pantalla `Almacen > Resurtido` antes de crear vistas/JS.
- Mantener la experiencia separada de Inventario > Traspaso directo.
- Evitar una pantalla "bonita" pero inutil para bodega/tienda.

Ruta sugerida:

```text
/almacen/resurtido
```

Vistas sugeridas:

```text
app/vistas/paginas/apps/erp/almacen/resurtido/listado.php
app/vistas/paginas/apps/erp/almacen/resurtido/formulario.php
```

JS sugerido:

```text
public/assets/js/custom/apps/erp/almacen/resurtido/listado.js
public/assets/js/custom/apps/erp/almacen/resurtido/formulario.js
```

Primera pantalla recomendada:

- Encabezado compacto con filtros:
  - tienda solicitante;
  - almacen origen;
  - estatus;
  - prioridad;
  - busqueda por folio/SKU.
- Tabla operativa:
  - folio;
  - tienda;
  - origen;
  - estatus;
  - partidas;
  - pendientes;
  - diferencias;
  - fecha;
  - acciones.
- Panel lateral o modal para `Stock bajo`:
  - llama al preflight read-only;
  - muestra SKU, disponible, punto de reorden, maximo y cantidad sugerida;
  - no crea solicitud hasta que exista flujo de solicitud.

Formulario recomendado por fases:

1. Solicitud:
   - tienda solicitante;
   - prioridad;
   - partidas con SKU, disponible en tienda, sugerido y solicitado;
   - comentario.
2. Autorizacion:
   - solicitado vs autorizado;
   - comentario obligatorio si autoriza parcial o rechaza.
3. Preparacion:
   - existencia origen exacta;
   - lote/caducidad;
   - ubicacion;
   - unidad fisica si aplica;
   - cantidad preparada.
4. Envio:
   - confirmar salida;
   - mostrar transito;
   - no permitir editar cantidades.
5. Recepcion:
   - confirmar recibido;
   - capturar diferencia si falta o llega danado/lote distinto;
   - ubicacion destino.

Reglas UX:

- Las acciones visibles dependen del estatus y permiso.
- No mezclar lotes en una misma linea visual de preparacion.
- Cantidades importantes deben usar stepper o input decimal con unidad visible, segun `docs/erp_ux_operativa.md`.
- La pantalla debe mostrar diferencia entre solicitado, autorizado, preparado, enviado y recibido.
- No mostrar stock en transito como disponible en tienda.

Resultado:

- `RES-T005` queda lista para convertir a vista/JS cuando exista backend minimo.

## RES-T006 - Auditoria de esquema dry-run

Fecha: 2026-07-11

Objetivo:

- Que `AlmacenEsquema` detecte estructuras faltantes de Resurtido sin ejecutar DDL.
- Mantener el DDL real bloqueado hasta respaldo externo y autorizacion.

Archivo modificado:

```text
app/modelos/AlmacenEsquema.php
```

Tablas agregadas a auditoria/plan:

- `erp_inventario_politicas_almacen_sku`
- `erp_almacen_resurtidos`
- `erp_almacen_resurtido_detalle`
- `erp_almacen_resurtido_preparacion`
- `erp_almacen_resurtido_envios`
- `erp_almacen_resurtido_recepciones`
- `erp_almacen_resurtido_diferencias`

Validaciones tecnicas:

| Comando | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l app/modelos/AlmacenEsquema.php` | OK |
| `AlmacenEsquema::auditarAlmacenInventario()` | `tipo=warning`, `pendientes=true` |

Resultado especifico de resurtido:

| Tabla | Existe | Columnas faltantes | Indices faltantes |
| --- | --- | ---: | ---: |
| `erp_inventario_politicas_almacen_sku` | no | 15 | 3 |
| `erp_almacen_resurtidos` | no | 25 | 6 |
| `erp_almacen_resurtido_detalle` | no | 18 | 3 |
| `erp_almacen_resurtido_preparacion` | no | 18 | 5 |
| `erp_almacen_resurtido_envios` | no | 12 | 5 |
| `erp_almacen_resurtido_recepciones` | no | 16 | 5 |
| `erp_almacen_resurtido_diferencias` | no | 24 | 8 |

Decision:

- Se agregaron FKs minimas al plan dry-run y al SQL propuesto para que el esquema nazca con integridad referencial.
- El siguiente paso tecnico antes de migrar es solicitar respaldo externo y autorizacion formal.

## RES-T007 - Paquete de autorizacion DDL preparado

Fecha: 2026-07-11

Objetivo:

- Dejar lista la autorizacion formal para crear tablas de Resurtido.
- Documentar aplicacion y reversa antes de tocar BD.

Documentos creados:

- `docs/erp_almacen_resurtido_schema_solicitud_autorizacion.md`
- `docs/erp_almacen_resurtido_schema_runbook_aplicacion.md`
- `docs/erp_almacen_resurtido_schema_plan_reversa.md`

Cambios de integridad:

- `app/modelos/AlmacenEsquema.php` ahora contempla FKs esperadas de resurtido.
- `docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql` queda sincronizado con esas FKs.

Token de autorizacion sugerido:

```text
AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA
```

Guardrails:

- No ejecutar DDL sin respaldo externo.
- No insertar datos reales en esta fase.
- No crear solicitudes `RES-*` todavia.
- No mover inventario.
- No tocar Ventas/POS/ecommerce.

Estado:

- Paquete preparado.
- Aplicacion no ejecutada.

## UAT minimo de cierre

| ID | Caso | Datos requeridos | Resultado esperado |
| --- | --- | --- | --- |
| `RES-UAT-001` | Solicitud tienda | tienda, SKU, cantidad | folio `RES-*` solicitado |
| `RES-UAT-002` | Autorizacion parcial | folio, cantidad autorizada menor | detalle conserva solicitado/autorizado |
| `RES-UAT-003` | Preparacion con lote | existencia origen con lote/caducidad | linea preparada con origen fisico |
| `RES-UAT-004` | Envio a transito | folio preparado | movimientos salida/transito |
| `RES-UAT-005` | Recepcion completa | folio enviado | entrada a tienda y cierre recibido |
| `RES-UAT-006` | Recepcion con faltante | recibir menos | diferencia abierta |
| `RES-UAT-007` | Unidad cerrada | etiqueta/unidad disponible | unidad cambia de almacen conservando codigo |
| `RES-UAT-008` | Unidad abierta | contenido disponible | contenido y estado fisico conservados |
| `RES-UAT-009` | Stock bajo | politica tienda/SKU | necesidad read-only o alerta |
| `RES-UAT-010` | No afectacion POS/ecommerce | stock en transito | no aparece como vendible por canal |

## Primer sprint recomendado

### RES-S1-001 - Diseno de estados/permisos

Resultado:

- cerrar estados validos;
- cerrar transiciones permitidas;
- definir permisos candidatos;
- documentar que acciones quedan ocultas por estado.

### RES-S1-002 - Preflight stock bajo read-only

Resultado:

- consulta que compare existencia disponible por almacen/SKU contra politica local o default global;
- no genera solicitudes;
- no genera notificaciones;
- devuelve necesidad sugerida.

Formula inicial:

```text
si disponible <= punto_reorden:
  cantidad_sugerida = max(stock_maximo - disponible, 0)
si no hay stock_maximo:
  cantidad_sugerida = max(punto_reorden - disponible, 0)
```

### RES-S1-003 - DDL revisable

Resultado:

- revisar `docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql`;
- ajustar nombres/indices antes de tocar `AlmacenEsquema`.

### RES-S1-004 - Auditoria de esquema modo plan

Resultado:

- `AlmacenEsquema` puede reportar faltantes de resurtido sin ejecutar DDL.
- No crear tablas todavia.

## Pendientes de decision

1. Confirmar codigos/nombres finales de almacenes:
   - Acuario.
   - Mascotas.
   - Bodega trasera.
   - Transito.
2. Decidir si `TRANSITO` sera almacen tecnico real o solo estado documental.
3. Definir si tienda puede solicitar cualquier SKU o solo SKUs habilitados para esa tienda.
4. Definir si autorizacion parcial requiere comentario obligatorio.
5. Definir si envio puede mezclar lotes en una misma linea o debe partir detalle por lote.

Recomendacion inicial:

- usar almacen tecnico `TRANSITO` real;
- partir detalle por lote/caducidad/unidad cuando se prepare;
- no mezclar lotes en una misma linea recibida;
- comentario obligatorio en autorizacion parcial, rechazo y diferencia.
