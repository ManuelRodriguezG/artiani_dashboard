# Ventas - Envios foraneos: solicitud de autorizacion de esquema

Documentacion IA: Codex GPT-5  
Fecha: 2026-10-04  
Estado: solicitud preparada; no implica ejecucion.

## Objetivo

Crear persistencia formal para la vista `Ventas > Envios foraneos`, de modo que las cotizaciones de interesados de paqueteria dejen de vivir solo en el navegador y puedan ser consultadas por el equipo.

## Alcance

Tablas propuestas:

- `erp_ventas_envios_foraneos`
- `erp_ventas_envios_foraneos_detalle`
- `erp_ventas_envios_foraneos_eventos`

Archivo SQL documental:

- `docs/erp_ventas_envios_foraneos_schema_propuesta.sql`

Modelo dry-run:

- `app/modelos/VentasErpEsquema.php`
  - `auditarEnviosForaneos()`
  - `planActualizarEnviosForaneos($ejecutar = false)`

Endpoints:

- `/ventas/esquema_auditar_envios_foraneos`
- `/ventas/esquema_actualizar_envios_foraneos`

## Reglas de seguridad

El endpoint de actualizacion solo ejecuta DDL si:

- el usuario tiene permiso `sistema.soporte`;
- se envia `ejecutar=1`;
- se envia `autorizar=VENTAS_ENVIOS_FORANEOS_DDL`;
- se envia una ruta/referencia de respaldo externo valida segun `validarRespaldoVentasPos()`.

Sin esos datos, el endpoint solo devuelve el plan o bloquea la ejecucion.

## Lo que NO hace

- No crea pedidos.
- No crea ventas reales.
- No crea servicios TMS.
- No cobra.
- No descuenta inventario.
- No modifica Catalogo ERP.
- No crea clientes CRM de forma automatica.

## Respaldo requerido

Antes de ejecutar DDL se requiere respaldo externo en:

```text
C:\xampp\panel_db_backups
```

La base actual debe tratarse como productiva. El nombre del respaldo debe reflejar el entorno real y el motivo, por ejemplo:

```text
C:\xampp\panel_db_backups\productivo_panel_YYYYMMDD_HHmmss_antes_ventas_envios_foraneos.sql
```

## Plan de reversa

Si el esquema se aplica y debe revertirse antes de capturar datos reales, se pueden eliminar las tablas en este orden:

```sql
DROP TABLE IF EXISTS `erp_ventas_envios_foraneos_eventos`;
DROP TABLE IF EXISTS `erp_ventas_envios_foraneos_detalle`;
DROP TABLE IF EXISTS `erp_ventas_envios_foraneos`;
```

Si ya existen cotizaciones reales, no ejecutar reversa destructiva sin exportacion, revision del dueno y respaldo adicional.

## Autorizacion requerida del dueno

Para aplicar el esquema, el dueno debe autorizar explicitamente una frase equivalente a:

```text
Autorizo aplicar DDL de Ventas Envios Foraneos con token VENTAS_ENVIOS_FORANEOS_DDL usando el respaldo [ruta completa].
```

## Siguiente paso despues de autorizar

1. Generar respaldo externo.
2. Validar que el respaldo exista y pese mas de 0 bytes.
3. Ejecutar `/ventas/esquema_actualizar_envios_foraneos` con `ejecutar=1`, token y respaldo.
4. Auditar con `/ventas/esquema_auditar_envios_foraneos`.
5. Conectar la vista actual a endpoints reales de listar/guardar/consultar.

## Aplicacion autorizada 2026-10-04

Autorizacion recibida del dueno:

```text
Autorizo aplicar DDL de Ventas Envios Foraneos con token VENTAS_ENVIOS_FORANEOS_DDL
```

Respaldo externo generado:

```text
C:\xampp\panel_db_backups\productivo_artianicom_sys_panel_20261004_223346_antes_ventas_envios_foraneos.sql
```

Validacion:

```text
tamano_bytes=131205837
sha256=E47FE8857D1258C9905C27E44524D6EFFFDBC255C1E1EFBDE9807F4A2FD58B65
base=artianicom_sys
dentro_repo=no
```

Resultado DDL:

- `erp_ventas_envios_foraneos`: creada.
- `erp_ventas_envios_foraneos_detalle`: creada.
- `erp_ventas_envios_foraneos_eventos`: creada.

Auditoria posterior:

```text
erp_ventas_envios_foraneos: existe=true, faltan_columnas=[], faltan_indices=[]
erp_ventas_envios_foraneos_detalle: existe=true, faltan_columnas=[], faltan_indices=[]
erp_ventas_envios_foraneos_eventos: existe=true, faltan_columnas=[], faltan_indices=[]
```

Pendiente inmediato:

- Conectar la pantalla `/ventas/envios_foraneos` a endpoints reales de listar/guardar/consultar.

