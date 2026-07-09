# ERP Garantias - Preflight antes de autorizacion

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: checklist previo; no ejecuta cambios.

## Checklist

### 1. Confirmar respaldo externo

Debe existir respaldo fuera del proyecto.

Valido:

- Ruta fuera de `C:\xampp\htdocs\panel`.
- Referencia externa documentada.

No valido:

- Cualquier archivo dentro de `C:\xampp\htdocs\panel`.
- Un respaldo no verificable.

### 2. Confirmar alcance

El alcance es solo Garantias base:

- politicas;
- reglas;
- snapshot de venta;
- reclamos;
- eventos;
- adjuntos;
- seguimiento proveedor.

No se autorizan:

- movimientos de inventario;
- ventas reales;
- devoluciones reales;
- UI final;
- politicas iniciales insertadas.

### 3. Auditoria previa

Ejecutar:

```text
GET /Garantias/esquema_auditar_garantias_erp
```

Esperado si no se ha aplicado:

- 7 tablas faltantes.
- Sin errores de conexion.

### 4. Dry-run DDL

Ejecutar:

```text
POST /Garantias/esquema_actualizar_garantias_erp
ejecutar=0
```

Esperado:

- 7 pendientes.
- 0 ejecutadas.
- 0 errores.

### 5. Revisar permisos

Confirmar que `SeguridadEsquema.php` contiene:

- `garantias.ver`
- `garantias.politicas`
- `garantias.reclamos.crear`
- `garantias.reclamos.resolver`
- `garantias.autorizar`
- `garantias.adjuntos`
- `garantias.reportes`

### 6. Ventana de aplicacion

Confirmar que no hay captura activa en:

- Ventas/POS;
- Almacen/Recepciones;
- Inventario;
- Seguridad.

### 7. Token de autorizacion

Confirmar token exacto:

```text
GARANTIAS_DDL_BASE
```

## Resultado esperado del preflight

Se puede pedir autorizacion final si:

- respaldo externo listo;
- auditoria previa entendida;
- dry-run sin errores;
- alcance aceptado;
- token confirmado.

Si cualquiera falla, no aplicar DDL.

## Evidencia de preflight read-only

Fecha: 2026-06-27  
Documentacion IA: Codex GPT-5  
Estado: ejecutado sin cambios en BD.

### Auditoria actual

Resultado:

- `tipo=warning`
- `tiene_pendientes=true`
- 7 tablas faltantes:
  - `erp_garantias_politicas`
  - `erp_garantias_politicas_reglas`
  - `erp_ventas_detalle_garantias`
  - `erp_garantias_reclamos`
  - `erp_garantias_reclamos_eventos`
  - `erp_garantias_adjuntos`
  - `erp_garantias_proveedor_seguimiento`

### Dry-run DDL actual

Resultado:

- total: 7
- existentes: 0
- pendientes: 7
- ejecutadas: 0
- errores: 0

### Disponibilidad del esquema

Resultado:

- `disponible=false`
- faltan las 7 tablas base de Garantias ERP.

### Conclusion

El preflight no detecta conflictos distintos a las tablas faltantes esperadas. El siguiente paso ya requiere respaldo externo y autorizacion explicita para aplicar DDL base.
