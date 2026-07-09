# ERP Catalogo - Runbook aplicacion paquetes configurables

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: Aplicado el 2026-06-29; conservar como runbook y evidencia  
Alcance: Catalogo ERP, paquetes configurables

## Objetivo

Aplicar de forma controlada el esquema pendiente para paquetes simples y configurables con grupos/opciones.

Resultado aplicado 2026-06-29:

- Respaldo externo:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260629_233248_antes_catalogo_paquetes_configurables.sql`
- Token:
  - `CATALOGO_PAQUETES_CONFIGURABLES_DDL`
- Script:
  - `storage/uat/uat_catalogo_paquetes_schema_apply_authorized.php`
- Auditoria posterior:
  - tablas faltantes: 0;
  - columnas faltantes: 0;
  - indices faltantes: 0;
  - indices con columnas distintas: 0.

Estado actualizado 2026-06-29:

- Recepcion variable ya no aparece como faltante de esquema.
- La aplicacion pendiente debe crear solo 4 tablas de paquetes configurables.
- No usar el DDL completo antiguo si intenta agregar columnas ya existentes de recepcion variable.

## Regla obligatoria

No ejecutar actualizacion de esquema sin:

1. respaldo externo de BD fuera del proyecto;
2. confirmacion de que el respaldo existe;
3. autorizacion explicita del dueno;
4. ventana operativa para validar Catalogo despues del cambio.

## Archivos relacionados

- `docs/erp_catalogo_paquetes_configurables_ddl_acotado.sql`
- `docs/erp_catalogo_paquetes_configurables_solicitud_autorizacion.md`
- `docs/erp_catalogo_paquetes_configurables_ddl_propuesto.sql` queda como antecedente historico completo.
- `storage/uat/uat_catalogo_paquetes_preflight_readonly.php`
- `storage/uat/uat_catalogo_paquetes_schema_apply_authorized.php`
- `docs/erp_catalogo_paquetes_plan.md`
- `docs/erp_catalogo_handoff_ventas_paquetes.md`
- `docs/erp_catalogo_handoff_almacen_paquetes_recepcion.md`
- `docs/erp_catalogo_avance.md`
- `app/modelos/CatalogoErpEsquema.php`
- `app/modelos/CatalogoErpDatos.php`
- `app/controladores/CatalogoErp.php`
- `app/vistas/paginas/apps/erp/catalogo/productos.php`
- `public/assets/js/custom/apps/erp/catalogo/productos.js`

## Respaldo externo

El respaldo debe guardarse fuera de `C:\xampp\htdocs\panel`.

Ejemplo de destino sugerido:

```txt
C:\respaldos_erp\catalogo\YYYYMMDD_HHMM_catalogo_pre_paquetes.sql
```

No guardar respaldos dentro del repositorio.

Generar respaldo fresco antes del DDL de paquetes, aunque exista un respaldo anterior de imagenes u otro cambio.

## Preflight autorizado

Ejecutar antes de pedir/aplicar autorizacion:

```txt
C:\xampp\php\php.exe storage\uat\uat_catalogo_paquetes_preflight_readonly.php --respaldo=<ruta real fuera del proyecto>
```

Resultado esperado:

- `ok=true`;
- `token_requerido=CATALOGO_PAQUETES_CONFIGURABLES_DDL`;
- faltantes exactamente iguales a las 4 tablas de paquetes.

Si `ok=false`, no aplicar DDL.

## Auditoria previa

Endpoint:

```txt
/sistema/esquema_auditar_catalogo_erp
```

Resultado esperado antes de aplicar, despues de imagenes de marcas/categorias:

- tablas faltantes: 4;
- columnas faltantes: 0;
- indices faltantes: 0;
- indices con columnas distintas: 0.

Los faltantes deben ser exactamente:

- `erp_catalogo_sku_paquetes`;
- `erp_catalogo_sku_paquete_componentes`;
- `erp_catalogo_sku_paquete_grupos`;
- `erp_catalogo_sku_paquete_grupo_opciones`.

Si aparecen faltantes distintos, detenerse y revisar `CatalogoErpEsquema`.

## Aplicacion de esquema

Endpoint:

```txt
/sistema/esquema_actualizar_catalogo_erp
```

Metodo:

```txt
POST
```

Payload:

```txt
ejecutar=1
```

No usar `?ejecutar=1` por GET, porque el controlador lee `$_POST`.

Alternativa acotada por script:

```txt
C:\xampp\php\php.exe storage\uat\uat_catalogo_paquetes_schema_apply_authorized.php --token=CATALOGO_PAQUETES_CONFIGURABLES_DDL --respaldo=<ruta real fuera del proyecto>
```

La alternativa por script valida token, respaldo y que los faltantes coincidan con el alcance autorizado antes de ejecutar.

## Auditoria posterior

Ejecutar de nuevo:

```txt
/sistema/esquema_auditar_catalogo_erp
```

Resultado esperado:

- tablas faltantes: 0;
- columnas faltantes: 0;
- indices faltantes: 0;
- indices con columnas distintas: 0.

## Prueba funcional Catalogo

1. Abrir Catalogo ERP.
2. Abrir producto con un SKU que representara paquete.
3. Entrar a pestaña `Paquetes`.
4. Confirmar que el formulario ya aparece.
5. Crear paquete simple:
   - seleccionar SKU paquete;
   - buscar componentes de distintos productos;
   - capturar cantidad y factor;
   - guardar.
6. Recargar producto y confirmar que aparece la receta.
7. Editar receta:
   - cambiar cantidad;
   - agregar/quitar componente;
   - guardar.
8. Crear grupo configurable:
   - codigo;
   - nombre;
   - minimo;
   - maximo;
   - modo de cantidad.
9. Crear opciones del grupo con SKUs distintos.
10. Desactivar opcion y grupo para confirmar baja logica.

## Validaciones esperadas

- No permitir paquete sin SKU paquete.
- No permitir paquete simple sin componentes.
- No permitir que el paquete se use como su propio componente.
- No permitir componentes inactivos, descontinuados o fusionados.
- No permitir opcion igual al SKU paquete.
- No permitir opcion duplicada activa dentro del mismo grupo.
- No permitir maximo menor que minimo.
- No permitir cantidad default fuera de minimo/maximo si se capturan.

## Contratos con otros modulos

Catalogo:

- Define receta.
- Define componentes fijos.
- Define grupos y opciones elegibles.
- No mueve inventario.
- No define precio final por canal.
- No guarda seleccion final del cliente.

Ventas:

- Debe guardar snapshot de la seleccion final.
- Debe calcular precio final, cargos, descuentos o listas.

Almacen/Inventario:

- Debe armar/desarmar paquetes fisicos si aplica.
- Debe consumir componentes reales y opciones seleccionadas.
- Debe controlar etiquetas, lote, caducidad y ubicacion.

## Rollback

Si falla la prueba posterior:

1. No continuar con Ventas ni Almacen.
2. Documentar error exacto.
3. Restaurar respaldo solo con autorizacion explicita.
4. Revisar `CatalogoErpEsquema` y comparar contra el DDL propuesto.
