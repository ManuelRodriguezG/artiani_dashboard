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
- `DistribucionAdmin` expone bandejas internas read-only para Mi catalogo, inventarios y sugeridos.
- La vista `DistribucionAdmin/administracion` agrega tabs internas de Mi catalogo, Inventarios y Sugeridos.
- El plan de esquema agrega tablas para Mi catalogo, inventario cliente y movimientos de inventario cliente; no se ejecuto DDL en esta etapa.

## Cambios 2026-09-29 - consola interna ERP

- `DistribucionAdmin/administracion` se consolida como consola operativa interna con tabs de Resumen, Solicitudes, Clientes, Mi catalogo, Inventario cliente, Sugerido, Pedidos, Productos y Demanda.
- Se agrega el modelo `distribucionanaliticainterna.php` con clase `DistribucionAnaliticaInterna` para lecturas agregadas de dashboard, demanda y catalogos de filtros internos. Es read-only y no expone costos, margenes ni stock exacto al frontend externo.
- `DistribucionAdmin` agrega endpoints internos read-only: `/resumen`, `/demanda` y `/catalogos_filtros`.
- Productos publicables soporta filtros internos por marca, categoria, proveedor, estado de canal, precio, imagen y ficha, reutilizando tablas de Catalogo ERP.
- Pedidos/cotizaciones muestran detalle de partidas y preparan revision por partida con `cantidad_confirmada`, `estatus_revision`, `comentario_revision`, `fecha_revision` e `id_usuario_revision` solo si las columnas ya existen.
- No se ejecuto DDL ni migracion. Si faltan columnas de revision por partida, deben aplicarse desde el plan de `DistribucionApiEsquema` con respaldo y autorizacion explicita.

## Cambios 2026-10-01 - navegacion interna separada

- Distribucion deja de depender de una sola entrada visual de Administracion para operar el modulo.
- El sidebar y el buscador global apuntan a paginas separadas: Resumen, Solicitudes, Clientes, Pedidos, Mi catalogo, Inventarios, Sugeridos, Productos y Demanda.
- Las rutas internas usan `DistribucionAdmin/panel_*` para no chocar con endpoints JSON existentes como `resumen`, `solicitudes`, `clientes`, `cotizaciones` y `demanda`.
- Cada seccion tiene su propia vista PHP dentro de `app/vistas/paginas/apps/erp/distribucion/`; el layout comun solo conserva header, sidebar, navegacion y scripts.
- El JS carga solo los datos requeridos por la pagina activa para evitar que Pedidos, Clientes, Mi catalogo, Inventarios y Productos queden mezclados en una sola pantalla.
- Pedidos queda como bandeja operativa separada para revisar partidas, cantidades solicitadas bloqueadas, cantidades confirmadas, valores e impresion de revision.

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
- El manifiesto publico de Distribucion ya no mezcla rutas `DistribucionAdmin` dentro de endpoints externos; esas rutas quedan separadas como `endpoints_internos_erp` y marcadas solo para panel ERP.
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

## Pendientes

- Validar en UAT con un cliente aprobado que tenga solo `distribucion.catalogo.ver`.
- Confirmar si `distribucion.catalogo.ver_detalle` se conserva para un futuro modo de catalogo resumido o si se depreca del contrato visible.
- Revisar el frontend Distribucion para que no muestre botones de solicitud de precio como registro cuando el usuario ya esta autenticado.
- Autorizar respaldo y aplicacion del plan de esquema Distribucion antes de probar escritura real de surtido/inventario.
- Asignar a clientes aprobados los nuevos permisos externos segun su alcance comercial.
- Definir en ERP la pantalla de revision por partida para confirmar cantidades surtibles antes de enviar respuesta al cliente.

