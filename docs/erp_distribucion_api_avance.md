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

## Decision 2026-09-29 - surtido e inventario del cliente

- El cliente mayorista necesita separar productos de interes sin crear pedido inmediato. El nombre operativo sera surtido habitual o productos seleccionados.
- El inventario declarado por el cliente vive separado del inventario ERP. El cliente captura existencia propia, minimo y maximo por SKU.
- El sugerido de resurtido se calcula con la formula: si `existencia_cliente <= minimo`, sugerido = `maximo - existencia_cliente`; en otro caso sugerido = 0.
- El catalogo Distribucion sigue sin mostrar existencia ERP exacta. Los pedidos preliminares siguen entrando como solicitud para revision interna de surtido.
- La revision interna por partida queda preparada en el esquema de `erp_distribucion_cotizacion_items` con cantidad confirmada, estatus y comentario de revision.
- Nuevos permisos externos reconocidos: `distribucion.surtido.gestionar`, `distribucion.inventario_cliente.gestionar`, `distribucion.resurtido.sugerido` y `distribucion.pedido.ver`.

## Cambios 2026-09-29

- Se agrego `DistribucionClienteSurtidoApi` para contratos externos de surtido, inventario cliente y sugerido de resurtido.
- `DistribucionApi` expone `/surtido/listar`, `/surtido/guardar`, `/inventario_cliente/listar`, `/inventario_cliente/guardar_conteo`, `/inventario_cliente/sugerido` y `/inventario_cliente/pedido_sugerido`.
- `DistribucionAdmin` expone bandejas internas read-only para surtidos, inventarios y sugeridos.
- La vista `DistribucionAdmin/administracion` agrega tabs internas de Surtidos, Inventarios y Sugeridos.
- El plan de esquema agrega tablas para surtido habitual, inventario cliente y movimientos de inventario cliente; no se ejecuto DDL en esta etapa.

## Pendientes

- Validar en UAT con un cliente aprobado que tenga solo `distribucion.catalogo.ver`.
- Confirmar si `distribucion.catalogo.ver_detalle` se conserva para un futuro modo de catalogo resumido o si se depreca del contrato visible.
- Revisar el frontend Distribucion para que no muestre botones de solicitud de precio como registro cuando el usuario ya esta autenticado.
- Autorizar respaldo y aplicacion del plan de esquema Distribucion antes de probar escritura real de surtido/inventario.
- Asignar a clientes aprobados los nuevos permisos externos segun su alcance comercial.
- Definir en ERP la pantalla de revision por partida para confirmar cantidades surtibles antes de enviar respuesta al cliente.
