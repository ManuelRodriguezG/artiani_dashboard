# ERP Catalogo - Solicitud de autorizacion para paquetes configurables

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: Autorizada y aplicada el 2026-06-29  
Alcance: Catalogo ERP > paquetes configurables

## Objetivo

Crear la estructura base de paquetes configurables en Catalogo ERP para poder guardar recetas de paquetes simples y configurables.

Catalogo solo define la receta. No vende, no descuenta inventario, no arma fisicamente y no define precio final.

## Resultado de aplicacion

Aplicado el 2026-06-29 con token:

```txt
CATALOGO_PAQUETES_CONFIGURABLES_DDL
```

Respaldo externo generado:

```txt
C:\xampp\panel_db_backups\artianilocal_panel_20260629_233248_antes_catalogo_paquetes_configurables.sql
```

Tamano:

```txt
27763752 bytes
```

Script ejecutado:

```txt
storage/uat/uat_catalogo_paquetes_schema_apply_authorized.php
```

Tablas creadas:

- `erp_catalogo_sku_paquetes`
- `erp_catalogo_sku_paquete_componentes`
- `erp_catalogo_sku_paquete_grupos`
- `erp_catalogo_sku_paquete_grupo_opciones`

Auditoria posterior:

- Tablas faltantes: 0.
- Columnas faltantes: 0.
- Indices faltantes: 0.
- Indices con columnas distintas: 0.

## Estado auditado

Auditoria read-only ejecutada:

```txt
C:\xampp\php\php.exe storage\uat\uat_catalogo_esquema_pendientes_readonly.php
```

Resultado:

- Tablas faltantes: 4.
- Columnas faltantes: 0.
- Indices faltantes: 0.
- Indices con columnas distintas: 0.

Faltantes:

- `erp_catalogo_sku_paquetes`
- `erp_catalogo_sku_paquete_componentes`
- `erp_catalogo_sku_paquete_grupos`
- `erp_catalogo_sku_paquete_grupo_opciones`

Importante:

- La configuracion de recepcion variable ya no aparece como faltante de esquema.
- Esta autorizacion no debe agregar columnas de recepcion variable.
- Esta autorizacion no debe migrar paquetes legacy.
- Esta autorizacion no debe tocar Ventas, Almacen, Inventario, Compras ni ecommerce.

## DDL acotado

Archivo preparado:

```txt
docs/erp_catalogo_paquetes_configurables_ddl_acotado.sql
```

Scripts preparados:

```txt
storage/uat/uat_catalogo_paquetes_preflight_readonly.php
storage/uat/uat_catalogo_paquetes_schema_apply_authorized.php
```

Alcance del DDL:

- Crear `erp_catalogo_sku_paquetes`.
- Crear `erp_catalogo_sku_paquete_componentes`.
- Crear `erp_catalogo_sku_paquete_grupos`.
- Crear `erp_catalogo_sku_paquete_grupo_opciones`.

No incluye:

- columnas de recepcion variable;
- imagenes de marcas/categorias;
- migracion desde `ecom_paquetes*`;
- semillas;
- escrituras de paquetes reales;
- cambios en otros modulos.

## Respaldo requerido

Antes de aplicar, generar respaldo externo fuera del proyecto.

Ejemplo:

```txt
C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_antes_catalogo_paquetes_configurables.sql
```

No guardar respaldos dentro de `C:\xampp\htdocs\panel`.

Aunque exista un respaldo anterior de otro cambio, se recomienda generar respaldo fresco inmediatamente antes de aplicar paquetes configurables.

## Token de autorizacion

Texto sugerido para autorizar:

```txt
Autorizo aplicar DDL acotado de paquetes configurables de Catalogo con token CATALOGO_PAQUETES_CONFIGURABLES_DDL.
Respaldo externo: <ruta real fuera del proyecto>.
Alcance: crear solo las 4 tablas de paquetes configurables; sin recepcion variable, sin migrar legacy, sin tocar otros modulos.
```

## Validacion posterior esperada

Ejecutar:

```txt
C:\xampp\php\php.exe storage\uat\uat_catalogo_esquema_pendientes_readonly.php
```

Resultado esperado:

- Tablas faltantes: 0.
- Columnas faltantes: 0.
- Indices faltantes: 0.
- Indices con columnas distintas: 0.

## Evidencia preflight

Comando read-only:

```txt
C:\xampp\php\php.exe storage\uat\uat_catalogo_paquetes_preflight_readonly.php --respaldo=<ruta real fuera del proyecto>
```

Resultado esperado con respaldo real:

- `ok=true`;
- `faltantes_esperados=true`;
- `resumen_esperado=true`;
- `token_requerido=CATALOGO_PAQUETES_CONFIGURABLES_DDL`.

Resultado esperado con placeholder o sin respaldo:

- `ok=false`;
- no ejecuta DDL;
- conserva las 4 tablas como faltantes.

Prueba funcional:

1. Abrir Catalogo ERP.
2. Abrir producto con SKU que actuara como paquete.
3. Entrar a pestaña `Paquetes`.
4. Guardar paquete simple con componentes.
5. Guardar grupo configurable.
6. Guardar opcion del grupo.
7. Recargar y confirmar que la receta se conserva.

## Criterio de no avance

Detener si:

- la auditoria read-only muestra faltantes distintos a las 4 tablas;
- no existe respaldo externo real;
- se intenta ejecutar el DDL completo antiguo con columnas de recepcion variable;
- se pretende migrar paquetes legacy en el mismo paso.
