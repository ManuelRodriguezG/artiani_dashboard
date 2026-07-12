# ERP Almacen - Solicitud de autorizacion DDL Resurtido

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-11  
Estado: solicitud preparada; no autoriza ni ejecuta cambios por si sola.

## Proposito

Preparar la autorizacion formal para crear el esquema base de `Almacen > Resurtido / Traspasos entre tiendas`.

El objetivo es habilitar solicitudes, autorizaciones, preparacion, envio, transito, recepcion y diferencias sin tocar POS, ecommerce ni movimientos reales de inventario en esta fase.

## Alcance solicitado

Crear, si no existen, las tablas:

- `erp_inventario_politicas_almacen_sku`
- `erp_almacen_resurtidos`
- `erp_almacen_resurtido_detalle`
- `erp_almacen_resurtido_preparacion`
- `erp_almacen_resurtido_envios`
- `erp_almacen_resurtido_recepciones`
- `erp_almacen_resurtido_diferencias`

El DDL esta reflejado en:

- `app/modelos/AlmacenEsquema.php`
- `docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql`

## Fuera de alcance

- Crear solicitudes reales.
- Mover inventario.
- Crear almacenes Acuario/Mascotas/Transito si no existen.
- Insertar politicas por tienda/SKU.
- Generar notificaciones.
- Tocar Ventas/POS/ecommerce.
- Migrar datos legacy.

## Requisitos antes de autorizar

1. Respaldo externo completo y verificable.
2. Auditoria read-only de esquema:

```bash
C:\xampp\php\php.exe -r "require 'app/iniciador.php'; require 'app/core/DBSchema.php'; require 'app/modelos/AlmacenEsquema.php'; $m = new AlmacenEsquema(); echo json_encode($m->auditarAlmacenInventario());"
```

3. Confirmar que el plan reporta pendientes de resurtido, no errores de conexion.
4. Confirmacion textual del dueno del proyecto con el token:

```text
AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA
```

## Riesgo operativo

Riesgo medio-bajo si se ejecuta despues de respaldo:

- Son tablas nuevas.
- No alteran tablas existentes.
- No mueven inventario.
- Si se agregan FKs, exigen que las tablas base de Almacen, Catalogo e Inventario ya existan.

Riesgo a cuidar:

- Si faltan tablas base, el DDL debe detenerse y no forzarse.
- Si se decide usar un almacen tecnico `TRANSITO`, debe existir antes de UAT de envio/recepcion o crearse con autorizacion separada.

## Decision recomendada

Autorizar DDL solo despues de:

- revisar que Acuario, Mascotas y Bodega esten modelados en `erp_almacenes`;
- confirmar si `TRANSITO` sera almacen tecnico real;
- validar que no se espera mover inventario en esta aplicacion;
- tener respaldo externo claro.

Despues de aplicar DDL, el siguiente paso debe ser backend minimo de solicitud/listado/consulta, no envio ni recepcion.

