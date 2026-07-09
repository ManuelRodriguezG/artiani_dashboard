# ERP Catalogo - Runbook aplicacion imagenes de marcas y categorias

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: runbook preparado; no ejecutar sin autorizacion  
Alcance: Catalogo ERP > Configuracion > Catalogos maestros

## Objetivo

Aplicar de forma controlada el esquema para imagenes propias de marcas y categorias maestras.

## Regla obligatoria

No ejecutar actualizacion de esquema sin:

1. respaldo externo de BD fuera del proyecto;
2. confirmacion de que el respaldo existe y es legible;
3. autorizacion explicita con token `CATALOGO_IMAGENES_MARCAS_CATEGORIAS`;
4. ventana para validar Configuracion de Catalogo despues del cambio.

## Precheck

Confirmar archivos:

- `app/modelos/CatalogoErpEsquema.php`
- `docs/erp_catalogo_configuracion_plan.md`
- `docs/erp_catalogo_avance.md`
- `docs/erp_catalogo_imagenes_marcas_categorias_solicitud_autorizacion.md`
- `storage/uat/uat_catalogo_imagenes_autorizacion_preflight_readonly.php`
- `storage/uat/uat_catalogo_imagenes_schema_apply_authorized.php`

Confirmar que el respaldo no esta dentro de:

```txt
C:\xampp\htdocs\panel
```

## Preflight read-only recomendado

Ejecutar antes de solicitar autorizacion:

```txt
C:\xampp\php\php.exe storage\uat\uat_catalogo_imagenes_autorizacion_preflight_readonly.php --respaldo="RUTA_O_REFERENCIA"
```

Resultado esperado:

- `modo=read-only`;
- `token_requerido=CATALOGO_IMAGENES_MARCAS_CATEGORIAS`;
- `respaldo.ok=true`;
- `estado_esquema.auditoria_ok=true`;
- `estado_esquema.plan_ok=true`;
- `estado_esquema.ddl_plan_imagenes` contiene los `CREATE TABLE` de marca y categoria.

Si se ejecuta sin respaldo, debe devolver `ok=false` y no debe escribir nada.

## Auditoria previa

Endpoint:

```txt
/sistema/esquema_auditar_catalogo_erp
```

Permiso requerido:

```txt
sistema.soporte
```

Resultado esperado:

- Las tablas `erp_catalogo_marca_imagenes` y `erp_catalogo_categoria_imagenes` pueden aparecer como faltantes antes de aplicar.
- Si aparecen diferencias fuera del plan vigente de Catalogo, detenerse y revisar antes de ejecutar.

## Aplicacion de esquema

No usar el endpoint general de esquema para este alcance:

```txt
/sistema/esquema_actualizar_catalogo_erp
```

Motivo:

- El plan general de `CatalogoErpEsquema` tambien incluye tablas de paquetes.
- Para imagenes de marcas/categorias el alcance aprobado debe ser solo:
  - `erp_catalogo_marca_imagenes`;
  - `erp_catalogo_categoria_imagenes`.

Aplicacion acotada autorizada:

```txt
C:\xampp\php\php.exe storage\uat\uat_catalogo_imagenes_schema_apply_authorized.php --token=CATALOGO_IMAGENES_MARCAS_CATEGORIAS --respaldo="RUTA_REAL_EXTERNA"
```

Importante:

- No usar placeholders como `RUTA_O_REFERENCIA` o `REFERENCIA_EXTERNA_PENDIENTE`.
- La ruta local del respaldo, si se proporciona como archivo, debe estar fuera de `C:\xampp\htdocs\panel`.
- El script acotado valida token y respaldo antes de ejecutar DDL.

## Auditoria posterior

Ejecutar de nuevo:

```txt
/sistema/esquema_auditar_catalogo_erp
```

Validar:

- `erp_catalogo_marca_imagenes` existe.
- `erp_catalogo_categoria_imagenes` existe.
- Indices esperados:
  - `idx_marca_imagen_marca`
  - `idx_marca_imagen_tipo`
  - `idx_categoria_imagen_categoria`
  - `idx_categoria_imagen_tipo`

## Prueba funcional posterior

Validar:

1. Configuracion de Catalogo abre correctamente.
2. Catalogos maestros carga marcas, categorias, unidades y atributos.
3. La auditoria de esquema ya no marca faltantes de imagenes de marcas/categorias.
4. El gestor de imagenes en marcas/categorias deja de mostrar candado de esquema pendiente.
5. Se puede guardar una URL de imagen de prueba en una marca/categoria controlada.
6. No se crearon registros de imagen automaticamente.

## Rollback

Si falla la aplicacion:

1. No implementar UI de imagenes.
2. Guardar mensaje exacto del error.
3. Revisar `CatalogoErpEsquema`.
4. Restaurar respaldo solo con autorizacion explicita.

## Siguiente paso despues de aplicar

La UI/modelo/controlador ya tienen CRUD basico por URL:

- listar imagenes por marca/categoria;
- agregar URL o ruta local permitida;
- marcar una imagen principal por tipo;
- inactivar imagenes sin borrado fisico.

Pendiente posterior:

- Definir si se agregara carga fisica de archivo o solo URL/ruta.
