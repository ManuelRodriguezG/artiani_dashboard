# ERP POS - Asistente conversacional de registro de ventas

Fecha: 2026-11-17  
Proyecto: `C:\xampp\htdocs\panel_de_control`  
Base operativa: productiva

## Objetivo

Permitir que una persona registre ventas desde una conversación rápida, con una experiencia parecida a WhatsApp, sin obligarla a usar toda la interfaz del POS en cada operación.

El asistente no sustituye las reglas del POS. Solo captura la intención del operador y llama contratos controlados del backend.

## Estado operativo actual

- Almacén central: `id_almacen=6`, código `OPERACION-GENERAL`.
- Caja central: `id_caja=4`, código `CJ-OPERACION-GENERAL-01`.
- Modo de la caja central: `piloto_sin_inventario`, activado y verificado el 2026-11-17. El dry-run permite validar SKUs catalogados con cero existencias, sin salidas de inventario previstas.
- Métodos habilitados: efectivo, tarjeta y transferencia.
- Terminal central: todavía no creada; no es necesaria para operar desde cualquier navegador autorizado.
- Turno central abierto al corte del 2026-11-17. `TUR-20261117-004-001`, operador `1`, fondo inicial de `$500.00 MXN`.
- Cajas antiguas 1 y 2: se conservan para historial; no deben mezclarse con el flujo central.

## Decisión de arquitectura

No se debe dar a un chat genérico acceso directo a MySQL ni permitir que interprete comandos SQL. El diseño recomendado es:

1. El usuario conversa con un asistente.
2. El asistente convierte el mensaje a una estructura de venta.
3. Un adaptador autenticado llama endpoints controlados del POS.
4. El backend vuelve a validar precio, almacén, caja, turno, cliente, inventario, pagos y permisos.
5. El asistente muestra un resumen y exige confirmación antes de cobrar.
6. El backend registra usuario, folio, caja, turno, partidas, pagos, origen conversacional e idempotency key.

El asistente nunca debe aceptar tokens de respaldo, frases de autorización de base de datos ni instrucciones para modificar tablas desde la conversación.

## Dos modalidades operativas

### 1. Captura rápida de cuenta

Sirve para que una persona vaya anotando productos mientras atiende:

- Busca SKU, código de barras o descripción.
- Permite venta rápida cuando no existe el producto.
- Permite cantidad, peso, precio autorizado y cliente opcional.
- Crea o actualiza una atención POS compartida.
- No cobra, no registra pago y no cierra venta.
- La cuenta queda disponible para que otra persona la tome desde POS.

### 2. Cobro confirmado

Solo después de que el asistente muestre el resumen:

- Folio o cuenta.
- Productos y cantidades.
- Precio autorizado por backend.
- Descuentos o precio conjunto.
- Total.
- Método o métodos de pago.
- Cliente, si está identificado.
- Almacén central y caja central.
- Situación de inventario.

El usuario debe responder una confirmación inequívoca, por ejemplo: `CONFIRMAR COBRO 1250 EFECTIVO`.

El backend debe ser quien cobre. El asistente no calcula ni decide por sí solo el total final.

## Contratos existentes que debe reutilizar

- Búsqueda: `pos_buscar_skus_erp`.
- Prevalidación de carrito: `pos_carrito_prevalidar_erp`.
- Confirmación read-only: `pos_confirmar_dryrun_erp`.
- Cobro real: `pos_confirmar_erp`.
- Venta rápida y resolución de SKU: endpoints `pos_venta_rapida_*`.
- Atenciones compartidas: `atencion_persistente_dryrun_erp`, `atencion_persistente_crear_erp`, `atenciones_bandeja_dryrun_erp`, `atencion_detalle_readonly_erp`.
- Apertura y cierre de turnos: usar los endpoints de Caja/Turnos existentes, siempre con permiso y auditoría.

No se debe crear un segundo flujo de ventas paralelo que inserte directamente en `erp_ventas`.

## Formato conversacional recomendado

Ejemplos aceptables:

- `Agrega 2 alimento para perro 500g a la cuenta de Juan.`
- `Agrega 1 FOHRS-PAFAKG a 50, cliente mostrador.`
- `Cobra la cuenta ATN-2026... en efectivo.`
- `Agrega venta rápida: fondo acuario azul, 1.5 metros, precio 120.`

El asistente debe preguntar solo lo que falte:

- Producto ambiguo: mostrar máximo cinco coincidencias.
- Precio inexistente: solicitar precio provisional y motivo, o pedir autorización comercial.
- Cantidad a granel: solicitar unidad y cantidad.
- Pago incompleto: pedir diferencia.
- Turno cerrado: informar que primero debe abrirse el turno.
- Producto no registrado: crear venta rápida controlada y pendiente de Catalogo, sin ocultar esa condición.

## Reglas de seguridad

- Autenticación individual por usuario; nunca usar un usuario compartido.
- El backend obtiene el `id_usuario` de la sesión o credencial del adaptador, no del texto del chat.
- Caja fija: `id_caja=4`.
- Almacén fijo: `id_almacen=6`.
- No permitir que el chat cambie de almacén o caja mediante texto.
- Exigir turno abierto para cobrar.
- Revalidar el carrito inmediatamente antes del cobro.
- Usar idempotency key por intento para evitar ventas duplicadas si el chat reintenta.
- Registrar mensaje de origen resumido, no credenciales ni secretos.
- Mantener los permisos existentes `ventas.operar`, permisos de inventario pendiente y permisos comerciales.
- Las devoluciones, cancelaciones, gastos de caja y apartados deben seguir sus flujos propios; no deben resolverse con una instrucción ambigua del chat.

## Respuesta del asistente

Cada acción debe responder con un estado claro:

- `BORRADOR`: cuenta capturada, no cobrada.
- `REQUIERE_DATOS`: falta información.
- `REQUIERE_AUTORIZACION`: el backend exige supervisor o permiso.
- `LISTA_PARA_CONFIRMAR`: prevalidación exitosa, aún no cobrada.
- `COBRADA`: venta real con folio y ticket.
- `BLOQUEADA`: el backend impidió cobrar y explica el motivo.
- `ERROR_REINTENTABLE`: fallo técnico sin evidencia de cobro.
- `DUPLICADA_DETECTADA`: se encontró la misma idempotency key o atención ya convertida.

## Plan de implementación

### Fase 1 - Adaptador interno seguro

- Preparar un servicio interno para búsqueda, simulación de una partida, consulta de una atención y prevalidación de una cuenta local.
- Fijar almacén `6` y caja `4` en el servidor. Usar la identidad autenticada del operador para consultar la asignación POS y el turno al prevalidar.
- Conservar el borrador en la sesión del canal cuando se integre. La creación de atenciones requiere una autorización productiva propia.

### Fase 2 - Prevalidación y confirmación

- Implementar resumen único antes de cobrar.
- Mostrar bloqueos de precio, inventario pendiente, cliente, pagos y autorizaciones.
- Requerir confirmación explícita.

### Fase 3 - Cobro real controlado

- Llamar `pos_confirmar_erp` únicamente después de confirmación.
- Persistir la idempotency key y la correlación conversacional en la misma transacción que la venta.
- Registrar usuario operador y turno central.
- Devolver folio, ticket no fiscal y resultado de inventario.

### Fase 4 - Operación asistida

- Atenciones multiusuario.
- Venta rápida con solicitud accionable para Catalogo.
- Clientes y listas de precio.
- Apartados y abonos.
- Reporte de ventas conversacionales separado por operador, sin duplicar ventas.

## Lo que no se debe hacer

- No guardar una venta solo porque el usuario escribió un mensaje ambiguo.
- No cobrar usando el precio escrito si backend devuelve otro.
- No crear ventas duplicadas por reintentos del chat.
- No usar WhatsApp o el chat como fuente contable sin folio backend.
- No permitir que el asistente cierre turnos automáticamente.
- No usar el chat para aplicar DDL, respaldos, reversas o ajustes de inventario.

## Auditoría de contratos 2026-11-17

La auditoría se hizo por inspección de `Ventas.php`, `VentasErp.php`, `VentasErpEsquema.php`, `pos.js` y `Core.php`. Los POST del POS reciben campos de formulario en `$_POST` con `items` y `pagos` serializados como JSON. `Ventas` requiere sesión ERP. `Core` valida CSRF y los POST dry-run indicados abajo pueden generar una escritura de auditoría genérica aunque el modelo solo lea datos. En fase 1 el servicio invoca directamente los métodos de dominio que respaldan estas rutas para evitar esa escritura y permanece fuera del router. La futura integración web debe conservar los controles de sesión, permiso, CSRF y auditoría que correspondan.

| Ruta bajo `/ventas/` | Permiso y comportamiento observado |
| --- | --- |
| `GET pos_buscar_skus_erp` | `ventas.ver` o `catalogo.ver`. Busca SKU, código activo y descripción con `q` e `id_almacen`. Devuelve precio referencial y disponibilidad del almacén. El modelo aplica un mínimo de diez resultados de búsqueda. El adaptador presenta hasta cinco. |
| `POST atencion_persistente_dryrun_erp` | `ventas.operar`, usuario de sesión. Prepara una atención sin caja ni turno si se omiten ambos. Con caja o turno exige validar ambos. Envía los bloqueos de partida como avisos y conserva los bloqueos en cada partida. El adaptador también revisa los de la línea simulada. |
| `POST atencion_persistente_crear_erp` | `ventas.operar`, usuario de sesión y auditoría de creación. Escribe encabezado y detalle en una transacción, con estatus `abierta` o `lista_para_cobro`. Genera folio de atención. No se observó una clave de idempotencia para la creación ni una ruta para actualizar una atención existente. El JS impide reenviarla como nueva y cargarla solo la copia a una cuenta del navegador. |
| `GET atenciones_bandeja_dryrun_erp` | `ventas.operar`. Lista hasta 50 cuentas activas, filtrables por almacén. La bandeja consulta, sin tomar ni bloquear la atención. |
| `GET atencion_detalle_readonly_erp` | `ventas.operar`. Consulta por `id_atencion`, con filtro `id_almacen` opcional en el endpoint existente y obligatorio (`6`) en el adaptador. Devuelve encabezado y líneas activas con su snapshot. El adaptador entrega folio, estatus y partidas sin datos personales. |
| `POST pos_venta_rapida_dryrun_erp` | `ventas.operar`, usuario de sesión. Valida descripción, cantidad, unidad, precio provisional y motivo. Sus pendientes son una previsión informativa. |
| `POST pos_carrito_prevalidar_erp` y `POST pos_confirmar_dryrun_erp` | `ventas.operar`. La primera valida partidas, inventario, importes y pagos, incluido el pago completo cuando se solicita. La segunda verifica el esquema de confirmación y fuerza `exigir_pago_completo=0`. Ambas pueden responder `error=false` con bloqueos. Hay que consultar ambos resultados y sus bloqueos. |
| `POST pos_confirmar_erp` | `ventas.operar`, usuario de sesión y auditoría de cobro. Resuelve por prioridad la asignación activa del operador y el turno abierto. Bloquea la fila de la atención y el turno en la transacción. Guarda la venta, los pagos y la conversión de la atención. El permiso `ventas.pos.inventario_pendiente.autorizar` protege el flujo manual de faltantes. |

### Brechas antes de habilitar atenciones y cobro

- **Contexto central.** El cobro real vuelve a elegir la asignación de mayor prioridad. Debe comprobar dentro del flujo real que dicha asignación y el turno correspondan a la caja `4` y al almacén `6`, y que la atención y todas sus partidas pertenezcan al ámbito central. El contrato de asignación permite una terminal nula y el navegador sigue dependiendo de la identidad y asignación del operador. Debe verificarse el estado de éstas en la sesión autorizada de UAT.
- **Atención persistida.** El cobro sustituye los ítems enviados por los que recupera `cargarAtencionPosReal`. Ese método hoy reconstruye solo SKU, cantidad, precio y modo de salida. Se pierden tipo, descripción, unidad y motivo de `VENTA-RAPIDA`, así como el almacén de partida y el ID de unidad física del snapshot. Antes de cobrar una cuenta persistida hay que restaurar esos datos en el dominio y validar la revisión de la cuenta bajo el mismo bloqueo de fila. La edición requiere un contrato de actualización del modelo con versión esperada y cierre de edición al convertir. Un reenvío como nueva atención crearía otro folio.
- **Importes.** El precio mostrado por la búsqueda es referencial. El adaptador no acepta precios de SKU formal ni descuentos. La venta rápida sí requiere un precio unitario provisional declarado, que el POS valida y utiliza para estimar el importe. Precio provisional de SKU formal, excepciones comerciales, cliente identificado y selección de unidad física quedan para el flujo ERP autorizado y sus pruebas. La fase 1 simula público general y pagos de mostrador por identificador de método. Antes del cobro se debe comprobar la habilitación del método en la caja y sus reglas de referencia y cambio.
- **Confirmación consistente.** El modelo actual revalida el carrito, pero no vincula el intento a un resumen previamente mostrado por el asistente. La integración real debe verificar identidad, cuenta, versión o huella del borrador, partidas, importes, pagos, caja y turno del resumen inmediatamente antes de la escritura. Si cambian, se devuelve un resumen nuevo para confirmar. En la llamada interna de fase 1, la simulación de confirmación de una venta rápida puede incluir un bloqueo de habilitación del flujo real. Ese estado se conserva.
- **Idempotencia y trazabilidad.** La conversión de una atención bajo bloqueo protege esa atención contra otro cobro, pero no recupera el resultado de un reintento ni cubre ventas sin atención o altas de atención. Definir una operación durable y única por acción, operador, ámbito y clave generada por el servidor. Guardar huella del intento canónico y resultado confirmado junto a la transacción. Misma clave y misma huella devuelven folio y resultado previos. La misma clave con otro contenido se rechaza. Ante un timeout se reconcilia con la misma clave y nunca se emite una nueva hasta saber si hubo cobro. Conservar correlación, origen, usuario de sesión, almacén, caja, turno, venta, detalle, folio y pagos. Una falla antes del commit deja el intento sin venta ni alerta.

## Contrato JSON de fase 1

`app/modelos/pos_conversacional.php` expone `PosConversacional::simular($solicitud, $idUsuario)` como servicio interno. El llamador de confianza deberá cargar `VentasErp`, resolver la identidad de sesión y exigir `ventas.operar`. El JSON de entrada trae exclusivamente una acción y los campos de esa acción. Los identificadores son enteros positivos. Para cantidades y dinero se recomienda enviar decimales en cadena, con hasta seis decimales y punto decimal. El servicio fija `6/4` y hace consultas o simulaciones por medio del modelo existente. El canal conserva su borrador local y añade una partida únicamente después del resultado `BORRADOR`. `prevalidar` recibe el borrador completo para revisar importes acumulados.

| Acción | Entrada de ejemplo | Resultado de fase 1 |
| --- | --- | --- |
| Buscar | `{"accion":"buscar","q":"FOHRS-PAFAKG"}` | `depurar.coincidencias` contiene hasta cinco SKUs. Una lista vacía permite presentar la opción de venta rápida. |
| Agregar SKU | `{"accion":"agregar_partida","partida":{"id_sku":42,"cantidad":"2"}}` | `BORRADOR` con estimado de una partida, o bloqueos de SKU, precio, unidad o disponibilidad. |
| Agregar venta rápida | `{"accion":"agregar_partida","partida":{"tipo_partida":"venta_rapida","descripcion_manual":"Fondo azul para acuario","cantidad":"1.5","unidad_venta":"m","precio_unitario":"120.00","motivo":"Producto sin SKU identificado"}}` | `BORRADOR` con el marcador `VENTA-RAPIDA` tras la simulación. El cliente conserva el motivo y los demás campos originales como parte del borrador. |
| Consultar cuenta | `{"accion":"consultar_cuenta","id_atencion":71}` | `CUENTA_CONSULTADA` con folio temporal, estatus, total guardado y partidas de una atención central. Para elegir una cuenta desde bandeja se reutilizará `atenciones_bandeja_dryrun_erp?id_almacen=6` en la sesión ERP. |
| Prevalidar borrador | `{"accion":"prevalidar","items":[{"id_sku":42,"cantidad":"2"}],"pagos":[{"id_metodo_pago":1,"monto":"240.00"}]}` | `LISTA_PARA_CONFIRMAR` solo si las simulaciones son favorables, el turno central está abierto y se cubre el total. La referencia, si aplica, viaja en `pagos[].referencia` y se omite en la respuesta. Los IDs e importes son ilustrativos. |
| Confirmar | `{"accion":"confirmar_cobro"}` | `BLOQUEADA` en fase 1. El servicio no acepta credenciales ni invoca una operación de cobro. |

La simulación de una partida validada devuelve un estimado como este.

```json
{
  "error": false,
  "tipo": "success",
  "mensaje": "Partida simulada para la cuenta local.",
  "depurar": {
    "estado": "BORRADOR",
    "dry_run": true,
    "habilita_cobro": false,
    "id_almacen": 6,
    "id_caja": 4,
    "id_turno_caja": null,
    "partidas": [{"id_sku": 42, "sku": "SKU-EJEMPLO", "cantidad": 2, "precio_unitario": 120, "subtotal": 240, "bloqueos": []}],
    "totales": {"subtotal": 240, "total_estimado": 240, "partidas": 1},
    "pagos": [],
    "bloqueos": [],
    "avisos": []
  }
}
```

El contrato futuro para confirmar recibe solo `{"accion":"confirmar_cobro","id_resumen":"<referencia opaca>","idempotency_key":"<clave emitida por el servidor>"}` desde el adaptador autenticado, después de una acción explícita del operador sobre ese resumen. El texto, precio o total que el operador escriba jamás reemplaza el resumen del servidor. La confirmación validará de nuevo la sesión, el contexto y la vigencia del resumen. El resultado de éxito será `COBRADA` e incluirá `id_venta`, folio, total, pagos aplicados, turno, acceso al ticket y evidencia de inventario y pendientes. Un reintento confirmado puede responder `DUPLICADA_DETECTADA` con el mismo resultado. El estado `LISTA_PARA_CONFIRMAR` de fase 1 siempre lleva `habilita_cobro=false` y no emite ID de resumen ni clave de cobro.

## Alerta de VENTA-RAPIDA y trabajo de Catálogo

El cobro POS ya crea `erp_pos_venta_rapida_pendientes` por detalle confirmado con `sku=VENTA-RAPIDA`, registra `pendiente_creado` y genera `pendiente_catalogo_pos` para `catalogo.ver` dentro de la transacción de la venta. El importe, la cantidad y el usuario operador provienen del pendiente y la unidad se conserva en el snapshot. La notificación guarda hoy los folios, almacén e ID del pendiente en `payload_json`, y su columna `creado_por` conserva el operador. Si falta `erp_notificaciones`, el método actual retorna `0` y la venta puede confirmarse con el pendiente persistido. Antes de habilitar el canal se deberá comprobar el esquema y que el camino conversacional pueda garantizar tanto el pendiente como su alerta.

La propuesta de `payload_json` toma los datos de la partida persistida y el contexto real del cobro.

```json
{
  "id_venta_rapida_pendiente": 301,
  "id_venta_detalle": 702,
  "folio_pendiente": "VRP-EJEMPLO",
  "folio_venta": "POS-EJEMPLO",
  "descripcion": "Fondo azul para acuario",
  "cantidad": "1.500000",
  "unidad_venta": "m",
  "precio_unitario": "120.000000",
  "total": "180.000000",
  "id_usuario_operador": 15,
  "id_almacen": 6,
  "id_caja": 4,
  "accion_sugerida": "Vincular SKU existente o solicitar borrador a Catálogo"
}
```

- Emitir una alerta de clasificación por detalle confirmado, identificada por su pendiente VRP. El índice actual de venta y detalle permite buscar el pendiente, pero no impone unicidad. Antes de habilitar el canal, garantizar esa relación bajo el control transaccional de la venta y de la clave de idempotencia. El carrito, el dry-run y el alta de atención no son disparadores.
- Abrir `/ventas/venta_rapida_pendientes?folio=<folio_vrp>`. `pos_venta_rapida_pendientes_erp` y `pos_venta_rapida_pendiente_erp` permiten consulta con `ventas.ver` o `catalogo.ver`. `pos_venta_rapida_resolucion_dryrun_erp` simula la vinculación de SKU. Sus opciones de inventario se revisan antes de cualquier clasificación.
- Si no hay SKU, el operador autorizado usa `pos_venta_rapida_solicitar_borrador_catalogo_erp`, que crea o recupera la incidencia `venta_rapida_sku_sin_match` en Catálogo por huella del folio VRP. La clasificación real usa `pos_venta_rapida_resolver_erp` desde ERP con el permiso, motivo y confirmación del flujo vigente. Buscar o solicitar el borrador deja abierto el pendiente hasta la vinculación. Confirmar la equivalencia de unidades antes de habilitar la resolución conversacional. Conservar el precio y cantidades de venta. Si procede regularizar inventario, queda una tarea de Inventario para ese almacén sin movimiento automático al clasificar.

## Siguiente autorización

Pasaron 18 casos con un modelo simulado y dos casos que ejecutaron la validación de venta rápida de `VentasErp` con la conexión deshabilitada. La consulta posterior de los modelos del ERP en productivo confirmó la caja `4` activa y el almacén `6`, con cero asignaciones centrales y cero turnos centrales abiertos. Los tres operadores asignados siguen en `5/2`. La búsqueda conversacional funcionó con el modelo real. La prevalidación se bloqueó al detectar una asignación no central. [Evidencia y prioridades de asignación](erp_ventas_pos_handoff_contexto.md#verificación-operativa-de-caja-central-2026-11-17).

El dueño autorizó después el alta de asignaciones para los operadores `1`, `2` y `3`. La lectura independiente confirmó que la caja `4` del almacén `6` es su asignación efectiva, con terminal nula y prioridad `1`. El operador `1` tiene el turno central abierto desde la apertura autorizada del 2026-11-17. [Apertura y auditoría](erp_ventas_pos_handoff_contexto.md#apertura-de-la-caja-central-verificada-2026-11-17).

Las pruebas de creación o edición de atenciones, el cobro real, la resolución de Catálogo y los cambios de esquema requieren sus propias autorizaciones conforme a `docs/erp_respaldo_bd_estandar.md`. La autorización de cambios productivos se gestiona fuera del chat del operador.
