# ERP - Distribucion API avance

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-28  
Estado: Documento vivo para contratos externos de clientes Distribucion.

## Contexto

Distribucion es un frontend externo para clientes comerciales. El ERP es la fuente de verdad de clientes, permisos, listas, precios, catalogo publicado, cotizaciones y pedidos preliminares.

## Decision 2026-09-28 - acceso inicial a catalogo

- Un cliente externo aprobado con el permiso `distribucion.catalogo.ver` debe poder entrar al portal y navegar el catalogo publicado.
- Ese permiso base habilita listado, filtros, marcas, categorias y ficha de producto publicada.
- El permiso `distribucion.catalogo.ver_detalle` queda como permiso reconocido por contrato, pero no debe bloquear el flujo inicial cuando ya existe `distribucion.catalogo.ver`.
- Precios, disponibilidad confirmada, cotizacion, pedido preliminar, descarga y edicion de cuenta siguen siendo permisos independientes.
- Si el cliente solo tiene catalogo, el ERP debe devolver productos publicados sin montos de precio, sin stock exacto y sin habilitar acciones de cotizacion.

## Cambios recientes

- `DistribucionPermisosApi::accionesPermitidas()` ahora deriva `ver_detalle` desde `distribucion.catalogo.ver` para permitir navegacion de catalogo completa.
- `DistribucionCatalogoApi::producto()` usa `distribucion.catalogo.ver` como permiso de entrada a la ficha publicada.
- `DistribucionCatalogoApi` normaliza acciones por item con el contexto del cliente, evitando que el catalogo base exponga acciones de precio, disponibilidad o cotizacion no autorizadas.

## Decision 2026-09-29 - Mi catalogo e inventario del cliente

- El cliente mayorista necesita separar productos de interes sin crear pedido inmediato. El nombre operativo sera Mi catalogo.
- "Surtido" no debe usarse como etiqueta principal porque puede sugerir producto ya comprado o ya surtido.
- El inventario declarado por el cliente vive separado del inventario ERP. El cliente captura existencia propia, minimo y maximo por SKU.
- El sugerido de resurtido se calcula con la formula: si `existencia_cliente <= minimo`, sugerido = `maximo - existencia_cliente`; en otro caso sugerido = 0.
- El catalogo Distribucion sigue sin mostrar existencia ERP exacta. Los pedidos preliminares siguen entrando como solicitud para revision interna de surtido.
- La revision interna por partida queda preparada en el esquema de `erp_distribucion_cotizacion_items` con cantidad confirmada, estatus y comentario de revision.
- Nuevos permisos externos reconocidos: `distribucion.mi_catalogo.gestionar`, `distribucion.inventario_cliente.gestionar`, `distribucion.resurtido.sugerido` y `distribucion.pedido.ver`.
- `distribucion.surtido.gestionar` queda como alias de compatibilidad temporal.

## Cambios 2026-09-29

- Se agrego `DistribucionClienteSurtidoApi` para contratos externos de Mi catalogo, inventario cliente y sugerido de resurtido.
- `DistribucionApi` expone `/mi_catalogo/listar`, `/mi_catalogo/guardar`, `/inventario_cliente/listar`, `/inventario_cliente/guardar_conteo`, `/inventario_cliente/sugerido` y `/inventario_cliente/pedido_sugerido`.
- `/distribucionadmin` expone bandejas internas read-only para Mi catalogo, inventarios y sugeridos.
- La vista `/distribucionadmin/administracion` agrega tabs internas de Mi catalogo, Inventarios y Sugeridos.
- El plan de esquema agrega tablas para Mi catalogo, inventario cliente y movimientos de inventario cliente; no se ejecuto DDL en esta etapa.

## Cambios 2026-09-29 - consola interna ERP

- `/distribucionadmin/administracion` se consolida como consola operativa interna con tabs de Resumen, Solicitudes, Clientes, Mi catalogo, Inventario cliente, Sugerido, Pedidos, Productos y Demanda.
- Se agrega el modelo `distribucionanaliticainterna.php` con clase `DistribucionAnaliticaInterna` para lecturas agregadas de dashboard, demanda y catalogos de filtros internos. Es read-only y no expone costos, margenes ni stock exacto al frontend externo.
- `/distribucionadmin` agrega endpoints internos read-only: `/resumen`, `/demanda` y `/catalogos_filtros`.
- Productos publicables soporta filtros internos por marca, categoria, proveedor, estado de canal, precio, imagen y ficha, reutilizando tablas de Catalogo ERP.
- Pedidos/cotizaciones muestran detalle de partidas y preparan revision por partida con `cantidad_confirmada`, `estatus_revision`, `comentario_revision`, `fecha_revision` e `id_usuario_revision` solo si las columnas ya existen.
- No se ejecuto DDL ni migracion. Si faltan columnas de revision por partida, deben aplicarse desde el plan de `DistribucionApiEsquema` con respaldo y autorizacion explicita.

## Cambios 2026-10-01 - navegacion interna separada

- Distribucion deja de depender de una sola entrada visual de Administracion para operar el modulo.
- El sidebar y el buscador global apuntan a paginas separadas: Resumen, Solicitudes, Clientes, Pedidos, Mi catalogo, Inventarios, Sugeridos, Productos y Demanda.
- Las rutas internas usan `/distribucionadmin/panel_*` en minusculas para no chocar con endpoints JSON existentes como `resumen`, `solicitudes`, `clientes`, `cotizaciones` y `demanda`, y para funcionar en servidores sensibles a mayusculas/minusculas.
- Cada seccion tiene su propia vista PHP dentro de `app/vistas/paginas/apps/erp/distribucion/`; el layout comun solo conserva header, sidebar, navegacion y scripts.
- El JS carga solo los datos requeridos por la pagina activa para evitar que Pedidos, Clientes, Mi catalogo, Inventarios y Productos queden mezclados en una sola pantalla.
- Pedidos queda como bandeja operativa separada para revisar partidas, cantidades solicitadas bloqueadas, cantidades confirmadas, valores e impresion de revision.

## Decision 2026-10-08 - rutas internas en minusculas

- Queda prohibido usar `/DistribucionAdmin/...` en vistas, sidebar, buscador, JS, ejemplos de documentos o llamadas AJAX nuevas.
- La ruta operativa canonica del panel interno es `/distribucionadmin/...`.
- La capitalizacion anterior solo queda como referencia historica; ningun enlace de UI debe depender de ella.
- El archivo del controlador queda como `app/controladores/distribucionadmin.php` para que produccion lo encuentre en sistemas sensibles a mayusculas/minusculas.
- `Core.php` compara CSRF/auditoria explicita sin depender de mayusculas para que las rutas internas en minusculas no generen auditoria duplicada.

## Cambios 2026-10-06 - autenticacion, registro y estados de acceso

- `DistribucionApi/auth/login` mantiene mensaje generico para credenciales invalidas y devuelve codigos publicos cuando las credenciales son validas pero el estatus comercial impide acceso.
- Login aprobado emite token aunque el cliente aun no tenga permisos comerciales; en ese caso las acciones regresan en falso y puede incluir `sin_permisos_comerciales` para que el portal muestre una pantalla de estado.
- `DistribucionClientesApi::formatearPerfilCliente()` agrega correo, `campos_pendientes` y `estado_acceso` para que Mi cuenta y Acceso en revision no dupliquen reglas comerciales.
- `DistribucionPermisosApi::accionesPermitidas()` conserva las claves existentes y agrega aliases de portal: `ver_inicio`, `ver_precios` y `solicitar_cotizacion`.
- Registro acepta la estructura fiscal anidada y devuelve `solicitud_incompleta`, `campos_faltantes` y `errores_campos` cuando faltan datos obligatorios.
- `DistribucionApi/auth/recuperar` y `auth/reenviar_activacion` quedan disponibles con respuesta uniforme segura; no enumeran cuentas ni envian correos automaticamente en esta etapa.
- `DistribucionCatalogoApi::contratos()` publica campos requeridos de registro, facturacion, categorias de interes, codigos de login, estados publicos, acciones y endpoints de ayuda de acceso.

## Cambios 2026-10-06 - control de duplicados de registro

- `DistribucionClientesApi::registrarSolicitud()` revisa primero `erp_distribucion_clientes` y `erp_distribucion_solicitudes` por correo normalizado antes de crear folio.
- Si ya existe cliente, la API devuelve `cliente_existente` o `cuenta_suspendida` sin crear nueva solicitud.
- Si ya existe solicitud pendiente/en revision, la API devuelve `solicitud_existente` con el folio original y `no_crea_folio_nuevo=true`.
- Si la solicitud existente esta rechazada, devuelve `solicitud_rechazada`; si esta aprobada, devuelve `cliente_existente`.
- El registro nuevo queda con estatus inicial `en_revision`, codigo `solicitud_recibida`, `contacto_soporte`, `campos_recibidos`, `categorias_interes_recibidas` y `requiere_factura`.
- Login con correo que solo existe en solicitudes devuelve el estado publico de esa solicitud, para que el portal muestre revision/pendiente en lugar de credenciales invalidas.
- Validacion fiscal agrega `campos_faltantes`, `errores_campos` y `etiquetas_campos`; cuando solo faltan fiscales, usa codigo `campos_fiscales_requeridos`.
- El alias `gestionar_surtido` se mantiene sincronizado con `distribucion.mi_catalogo.gestionar` para compatibilidad con frontends anteriores.
- La contrasenia temporal capturada al aprobar desde ERP respeta el mismo minimo de 8 caracteres que el flujo de activacion por link.
- El manifiesto publico de Distribucion ya no mezcla rutas internas del panel dentro de endpoints externos; esas rutas quedan separadas como `endpoints_internos_erp` y marcadas solo para panel ERP.
- Mi catalogo usa el mismo criterio de URL absoluta de imagenes que el catalogo general, evitando rutas `/uploads/...` relativas al dominio externo.
- `Core.php` no aplica CSRF/auditoria de sesion ERP interna a `DistribucionApi`; la API externa se protege con Authorization Bearer, permisos comerciales y CORS propios.
- Los POST internos de Distribucion que ya auditan en modelos quedan registrados como auditoria explicita en `Core.php` para evitar auditoria generica duplicada.

## Cambios 2026-10-06 - backend tester read-only

- Se valido por lectura que `artiani_tester@artiani.com.mx` tiene dos solicitudes pendientes (`DIST-20261006-0001` y `DIST-20261006-0002`) y ningun cliente externo creado todavia.
- El panel ERP de Distribucion tiene vistas separadas para Resumen, Solicitudes, Clientes, Pedidos, Mi catalogo, Inventarios, Sugeridos, Productos y Demanda; las vistas PHP y el JS principal pasan validacion de sintaxis.
- Los endpoints/modelos internos read-only de panel responden para solicitudes, clientes, cotizaciones, Mi catalogo, inventarios, sugeridos y resumen.
- Se valido catalogo Distribucion con contexto autenticado simulado: catalogo, producto, categorias, marcas, filtros y categorias_interes responden correctamente.
- `CatalogoCanalesErp::urlRecurso()` ahora convierte rutas `/uploads/...` en URLs absolutas del ERP cuando existe `RUTA_RECURSOS_IMG`/`RUTA_URL`, para que el frontend externo pueda pintar imagenes.
- Se valido dry-run de cotizacion con SKU `1867` y lista `4`; recalcula precio en servidor, devuelve totales y conserva guardrails de no apartar inventario ni crear venta/pedido.
- El flujo de activacion exige contrasenia minima de 8 caracteres; `Tester` no cumple. Para pruebas autenticadas se recomienda usar una contrasenia de prueba de al menos 8 caracteres o cambiar explicitamente la politica.

## Cambios 2026-10-06 - validacion UAT con cliente tester

- Se preparo el cliente de prueba `artiani_tester@artiani.com.mx` desde los metodos internos existentes, sin tocar esquema ni tablas.
- Antes de escribir datos se guardo snapshot en `storage/uat/distribucion_tester_before_20261006_231710.json`; despues se guardo snapshot en `storage/uat/distribucion_tester_after_20261006_231910.json`.
- La solicitud `DIST-20261006-0001` quedo aprobada como cliente Distribucion `14`; la solicitud duplicada `DIST-20261006-0002` quedo rechazada para no ensuciar pendientes.
- El cliente tester quedo como `mayorista`, con lista de precio `4` (`mayoreo_pruba`) y permisos comerciales completos de Distribucion.
- Login real del tester devuelve token, perfil aprobado, lista asignada y acciones completas para catalogo, precios, disponibilidad, cotizacion, pedido, Mi catalogo, inventario cliente, sugerido y cuenta.
- Se actualizo la contrasenia UAT acordada del tester a `DistFront1094`; prueba HTTP real de `/DistribucionApi/auth/login` devuelve `error=false`, `token_len=64`, cliente `14`, estatus `aprobado`, lista `4`, 15 permisos y acciones criticas activas.
- Se valido SKU `1867`: precio visible `41.25` MXN con lista asignada; dry-run de 2 piezas devuelve total estimado `82.5` sin bloqueos.
- Se crearon datos UAT del flujo autenticado: Mi catalogo con SKU `1867`, inventario cliente con sugerido `3`, cotizacion `DCOT-20261006-0001` y pedido preliminar `DPED-20261006-0001`.
- La lectura posterior confirma 1 item en Mi catalogo, 1 item en inventario, 1 sugerido, 1 cotizacion y 1 pedido visible para el cliente tester.

## Cambios 2026-10-07 - validacion de pedidos y cotizaciones frontend

- Se validaron los folios generados desde el frontend para el cliente `14`: `DPED-20261007-0001`, `DCOT-20261007-0001`, `DCOT-20261007-0003`, `DCOT-20261007-0004` y `DPED-20261007-0002`.
- Todos los folios pertenecen a `artiani_tester@artiani.com.mx`, lista de precio `4`, SKU `1867`, cantidad `1` y precio snapshot `41.25`; el backend recalculo el precio y no acepto precio manipulado desde el frontend.
- `DCOT-20261007-0003` quedo trazada hacia `DPED-20261007-0002` mediante `id_pedido_relacionado` / `id_cotizacion_origen`; `DCOT-20261007-0004` quedo cancelada.
- Se confirmo que estos documentos viven solo en Distribucion: no crearon venta, pedido ERP final ni movimiento de inventario fuera del modulo.
- Se preparo respuesta comercial UAT para `DPED-20261007-0002`: partida confirmada, entrega por definir, costo de envio `0`, total confirmado `41.25` y estatus `respondida`.
- El detalle autenticado del cliente ya puede leer la propuesta comercial de `DPED-20261007-0002`; las respuestas invalidas del cliente se rechazan.
- Cuando un pedido aun no tiene propuesta comercial, el mensaje publico ya no menciona ERP: `Este pedido aun no tiene una propuesta comercial para responder.`
- Las cotizaciones/pedidos bloquean partidas vacias, cantidades cero, cantidades negativas y SKU invalidos sin crear folio nuevo.
- El panel interno de Pedidos agrega filtros por fecha desde/hasta y los aplica en `DistribucionCotizacionesApi::cotizacionesInternas()`.
- La auditoria interna registra eventos de guardar borrador, cancelar, pedido preliminar, enviar como pedido, revision de partida y configurar entrega para el flujo tester.
- La evidencia UAT debe seguir evitando hashes y tokens completos; en reportes usar `token_len` o `token_emitido`.

## Cambios 2026-10-07 - catalogo personalizado por cliente

- `DistribucionPermisosApi::contextoDesdeRequest()` devuelve `catalogo_modo` y `categorias_interes` para que catalogo, precios y disponibilidad apliquen el perfil del cliente autenticado.
- `CatalogoCanalesErp` y `PreciosCanalesErp` conservan el contrato de visibilidad por cliente: las reglas `ocultar` tienen prioridad, el modo `personalizado` requiere reglas `permitir` activas y las reglas por categoria cubren descendientes por ruta.
- El panel interno de Clientes agrega accion `Catalogo` para revisar datos del cliente, modo general/personalizado, categorias de interes y reglas separadas entre permitidos y ocultos.
- El modal de catalogo permite guardar el modo del cliente y crear/actualizar reglas por SKU, categoria o marca con accion, prioridad, notas y estatus, usando endpoints internos auditados.
- La seccion Productos agrega accion masiva `Asignar a cliente` y accion individual por SKU; ambas crean reglas de catalogo por cliente sin modificar el catalogo global ni afectar otros clientes.
- El dry-run de cotizacion ahora devuelve `error=true`, codigo `sku_no_visible_cliente` y `no_crea_folio=true` cuando el SKU no esta visible para el cliente; esto alinea la prevalidacion con registrar cotizacion y pedido preliminar.
- El modal de reglas agrega sugerencias de objeto para SKU, categoria o marca segun los datos cargados en el panel, reduciendo captura manual de IDs.
- No se ejecuto DDL ni escritura de datos durante esta implementacion; las escrituras quedan disponibles solo cuando el usuario opere los botones del panel ERP.

## Cambios 2026-10-07 - pedidos UAT y cambios de cuenta

- Se dejaron dos pedidos nuevos del cliente tester `14` listos para que frontend pruebe respuesta del cliente sin consumir el caso ya aceptado:
  - `DPED-20261007-0003` (`id_pedido_distribucion=13`): usar para probar `requiere_ajuste`.
  - `DPED-20261007-0004` (`id_pedido_distribucion=14`): usar para probar `rechazado`.
- Ambos pedidos tienen SKU `1867`, cantidad solicitada `1`, cantidad confirmada `1`, estatus `respondida`, total confirmado `41.25`, revision por partida y detalle visible desde el perfil autenticado del tester.
- La preparacion interna de estos pedidos conserva guardrails: no aparta inventario, no crea venta y no crea pedido ERP final.
- `DistribucionClientesApi::solicitarCambioPerfil()` ahora compara contra los datos actuales del cliente antes de actualizar contacto o registrar solicitud; si no hay diferencias responde `No detectamos cambios para procesar` y no crea solicitud.
- Los cambios simples de contacto siguen aplicando directo; los cambios comerciales/fiscales generan solicitud pendiente solo por los campos realmente modificados.
- Las solicitudes de cambio ahora guardan y devuelven etiquetas humanas (`nombre comercial`, `correo de facturacion`, etc.) y resumen legible, evitando textos genericos como `Cambio de empresa... y otros datos` cuando no aplica.
- Se valido un caso sin cambios reales para el cliente `14`: respuesta `error=false`, `tipo=info`, `sin_cambios=true`, sin escritura nueva.
- Se valido un cambio comercial UAT para el cliente `14`: solicitud `id_solicitud=2`, resumen `Cambio de nombre comercial`, estatus `pendiente`.
- Se reviso auditoria para los nuevos pedidos y para el cambio de perfil; registra crear pedido preliminar, configurar respuesta interna y solicitud de cambio sin hashes, tokens ni contrasenas.
- Se suavizaron mensajes publicos base para rutas/acciones no disponibles y metodo incorrecto, evitando texto visible como `endpoint`; las claves tecnicas internas se conservan para compatibilidad del contrato.

## Cambios 2026-10-07 - API de notificaciones del portal

- Se agrega el contrato externo `/DistribucionApi/notificacion/*` con resumen, listado, marcado de lectura y solicitud de envio/reenvio por canal.
- Se crea el modelo `app/modelos/distribucionnotificacionesapi.php` con archivo en minusculas para evitar fallas de despliegue por sensibilidad de mayusculas/minusculas.
- Las notificaciones externas se separan de la bandeja interna `erp_notificaciones`: viven en `erp_distribucion_notificaciones` y sus intentos de envio en `erp_distribucion_notificacion_envios`.
- `DistribucionApiEsquema::planActualizarDistribucionApi()` queda preparado para crear esas tablas, pero no se ejecuto DDL ni escritura de esquema en esta etapa.
- La API siempre resuelve el cliente desde el token externo; no acepta `id_cliente_distribucion` del frontend para listar, marcar o reenviar notificaciones.
- `DistribucionCotizacionesApi` genera avisos best effort al recibir pedido, pasar a revision, guardar propuesta comercial, cancelar/actualizar solicitud y recibir respuesta del cliente.
- `DistribucionClientesApi` genera avisos best effort al aprobar cuenta, suspender/rechazar/actualizar estatus, asignar lista de precios y actualizar permisos comerciales.
- Los envios por correo, WhatsApp, SMS y llamada quedan auditados como intentos; si el proveedor aun no esta integrado, la API responde mensaje claro con `configurado=false` sin romper el frontend.
- Los destinos de envio se guardan enmascarados y la auditoria no guarda tokens, hashes, contrasenas ni contactos internos.

## Decision 2026-10-08 - UX de atencion de clientes y sublistas comerciales

- La bandeja de Clientes deja de ser el lugar para configurar acciones profundas. Debe servir para encontrar al cliente y entrar a pantallas dedicadas: Atender cliente, Listas, Permisos y Entrega.
- No usar modales para administrar listas, permisos o entrega cuando la accion requiere revisar contexto y tomar decisiones comerciales.
- Las listas de precio por cliente ya no deben pensarse como una unica lista ni como reglas sueltas de catalogo. Un cliente puede tener varias listas activas: base, express o especial.
- Cada lista asignada puede operar en modo `todos` o `seleccionados`. En modo `seleccionados`, la sublista de productos se toma solo desde los productos que existen en esa lista de precios.
- La lista express cubre el caso comercial de bajo margen para productos que el cliente no maneja en stock, pero que Artiani puede surtir si el cliente necesita venderlos.
- El plan de esquema agrega columnas avanzadas a `erp_distribucion_cliente_listas` y la tabla `erp_distribucion_cliente_lista_productos`; no se ejecuto DDL en esta etapa.
- Ajuste UX posterior: la UI ya no pide tipo de lista ni modo todos/seleccionados; el admin asigna listas reales y decide productos con checks. La pantalla de listas queda a lo ancho arriba, productos abajo, y muestra categorias de interes del cliente antes de permitir guardar productos.
- La pantalla agrega preferencias de categorias por cliente y una primera ayuda de asignaciones sugeridas por tipo de negocio para marcar categorias recomendadas; si se requiere administrar plantillas persistentes por negocio, se debe agregar una tabla/configuracion dedicada antes de consolidarlo como regla global.
- Ajuste UX posterior 2: la pantalla Listas solo muestra categorias ya seleccionadas y las usa como apoyo para marcar productos coincidentes; agregar/quitar categorias se mueve a la vista dedicada `cliente_categorias`.

## Pendientes

- Validar en UAT con un cliente aprobado que tenga solo `distribucion.catalogo.ver`.
- Confirmar si `distribucion.catalogo.ver_detalle` se conserva para un futuro modo de catalogo resumido o si se depreca del contrato visible.
- Revisar el frontend Distribucion para que no muestre botones de solicitud de precio como registro cuando el usuario ya esta autenticado.
- Autorizar respaldo y aplicacion del plan de esquema Distribucion antes de probar escritura real de surtido/inventario.
- Asignar a clientes aprobados los nuevos permisos externos segun su alcance comercial.
- Terminar pruebas visuales del panel ERP en navegador para confirmar filtros, modales y estados con datos reales.
- Decidir si la API externa debe cambiar codigos HTTP para errores de negocio (`422`, `404`, `409`) o conservar `200` con `error=true` por compatibilidad.
- Hacer una segunda pasada de textos visibles cuando frontend confirme que mensajes de `depurar` no se muestran al usuario final.

