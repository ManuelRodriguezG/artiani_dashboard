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

## Pendientes

- Validar en UAT con un cliente aprobado que tenga solo `distribucion.catalogo.ver`.
- Confirmar si `distribucion.catalogo.ver_detalle` se conserva para un futuro modo de catalogo resumido o si se depreca del contrato visible.
- Revisar el frontend Distribucion para que no muestre botones de solicitud de precio como registro cuando el usuario ya esta autenticado.
- Autorizar respaldo y aplicacion del plan de esquema Distribucion antes de probar escritura real de surtido/inventario.
- Asignar a clientes aprobados los nuevos permisos externos segun su alcance comercial.
- Definir en ERP la pantalla de revision por partida para confirmar cantidades surtibles antes de enviar respuesta al cliente.
