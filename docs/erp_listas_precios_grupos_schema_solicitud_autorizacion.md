# Solicitud de autorizacion - Grupos comerciales para Listas de precios

Documentacion IA: Codex GPT-5  
Fecha: 2026-10-04  
Estado: solicitud preparada; DDL no ejecutado.

## Objetivo

Crear el esquema base para guardar grupos comerciales de productos usados por ERP > Comercial > Listas de precios.

La fase actual ya permite filtrar productos por categoria, proveedor y marca sin escribir BD. Esta solicitud prepara la fase siguiente: guardar grupos con nombre, reglas e items manuales para revisar o actualizar precios por conjunto.

## Respaldo requerido

Antes de ejecutar DDL se requiere respaldo externo valido fuera del proyecto:

```text
C:\xampp\panel_db_backups
```

Formato sugerido:

```text
C:\xampp\panel_db_backups\productivo_artianicom_sys_panel_YYYYMMDD_HHmmss_antes_listas_precios_grupos_comerciales.sql
```

Reglas:

- No guardar respaldos dentro del repo.
- No ejecutar DDL sin respaldo externo validado.
- La base operativa actual se trata como productiva.

## Token requerido

```text
VENTAS_LISTAS_PRECIOS_GRUPOS_DDL
```

## UAT read-only previo

Ejecutar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_listas_precios_grupos_schema_readonly.php --compact=1
```

Esperado antes del DDL:

- `read_only=true`.
- `ddl_pasos_generados=4`.
- `faltantes=4` si no existen tablas.
- No crea tablas.
- No modifica listas.
- No modifica precios.

## Alcance del DDL propuesto

Crear tablas:

- `erp_comercial_grupos_productos`
- `erp_comercial_grupos_productos_reglas`
- `erp_comercial_grupos_productos_items`
- `erp_comercial_grupos_productos_eventos`

## Uso previsto

Tipos de grupos:

- `manual`: productos agregados explicitamente por el usuario.
- `dinamico`: reglas por categoria, proveedor, marca, margen, sin costo o sin precio.
- `incidencia`: grupo generado por cambio de costo/proveedor/rentabilidad.
- `sistema`: grupo operativo creado por reglas internas.

## Guardrails

- No crea grupos iniciales.
- No modifica listas de precios existentes.
- No cambia precios.
- No activa listas.
- No modifica ventas pasadas.
- No toca POS ni ecommerce.
- Solo prepara estructura para una fase posterior de CRUD/auditoria de grupos.

## Contrato con Proveedores/Costos/Rentabilidad

Las incidencias por aumentos de proveedor no deben escribir precios directamente.

Flujo esperado:

1. Proveedores/Costos detecta aumento, baja, producto nuevo o cambio de factor.
2. Rentabilidad calcula impacto en margen.
3. Se genera incidencia comercial.
4. Listas de precios puede convertir esa incidencia en grupo revisable.
5. El usuario decide aplicar sugeridos, guardar cambios y activar listas segun permisos.

## Texto de autorizacion sugerido

```text
Autorizo generar respaldo externo productivo y aplicar DDL de grupos comerciales de Listas de precios con token VENTAS_LISTAS_PRECIOS_GRUPOS_DDL usando el respaldo C:\xampp\panel_db_backups\productivo_artianicom_sys_panel_YYYYMMDD_HHmmss_antes_listas_precios_grupos_comerciales.sql.
```

## Siguiente fase posterior al DDL

- Crear auditoria/apply protegido.
- Crear CRUD de grupos manuales.
- Crear evaluador de grupos dinamicos.
- Permitir usar un grupo como alcance de acciones masivas en Listas.
- Integrar incidencias de proveedor/rentabilidad como grupos temporales revisables.
