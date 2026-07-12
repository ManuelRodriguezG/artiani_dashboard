# ERP Almacen - Runbook de aplicacion DDL Resurtido

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-11  
Estado: guia operativa; no ejecutar sin autorizacion explicita.

## Objetivo

Aplicar el esquema base de Resurtido para permitir desarrollo posterior de solicitudes y traspasos documentales entre tiendas.

## Preflight read-only

Validar sintaxis:

```bash
C:\xampp\php\php.exe -l app/modelos/AlmacenEsquema.php
```

Validar auditoria:

```bash
C:\xampp\php\php.exe -r "require 'app/iniciador.php'; require 'app/core/DBSchema.php'; require 'app/modelos/AlmacenEsquema.php'; $m = new AlmacenEsquema(); echo json_encode($m->auditarAlmacenInventario());"
```

Esperado antes de DDL:

- `error=false`
- `tipo=warning`
- `tiene_pendientes=true`
- tablas `erp_almacen_resurtido_*` aparecen como faltantes.

## Respaldo externo

Crear respaldo externo antes de aplicar.

Ejemplo de referencia:

```text
C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_antes_resurtido_schema.sql
```

No continuar si:

- no existe respaldo;
- el respaldo tiene tamano sospechosamente bajo;
- MySQL no responde correctamente;
- hay errores de integridad en tablas base.

## Aplicacion autorizada

Aplicar solo si el dueno dio autorizacion textual:

```text
AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA
```

Ruta tecnica recomendada:

- usar `AlmacenEsquema::planActualizarAlmacenInventario(true)`;
- no usar SQL manual salvo que el plan falle por limitacion concreta del helper;
- guardar evidencia de salida.

## Verificacion posterior

Ejecutar de nuevo auditoria read-only:

```bash
C:\xampp\php\php.exe -r "require 'app/iniciador.php'; require 'app/core/DBSchema.php'; require 'app/modelos/AlmacenEsquema.php'; $m = new AlmacenEsquema(); echo json_encode($m->auditarAlmacenInventario());"
```

Validar:

- las tablas de resurtido existen;
- columnas faltantes de resurtido `0`;
- indices faltantes de resurtido `0`;
- FKs faltantes de resurtido `0`, si el helper las creo en `CREATE TABLE`.

## No hacer durante esta aplicacion

- No insertar politicas por tienda/SKU.
- No crear folios `RES-*`.
- No mover inventario.
- No crear stock en transito.
- No crear notificaciones.
- No tocar POS/ecommerce.

## Siguiente paso despues de aplicar

Implementar `RES-T008`:

- listar solicitudes;
- consultar solicitud;
- guardar borrador o solicitud;
- autorizar/rechazar sin mover inventario;
- UAT de escritura documental con folio `RES-*`.

