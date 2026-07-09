# ERP Catalogo - Solicitud de autorizacion imagenes de marcas y categorias

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: solicitud preparada; no ejecuta cambios  
Alcance: Catalogo ERP > Configuracion > Catalogos maestros

## Objetivo

Autorizar la aplicacion controlada del DDL que agrega estructura propia para imagenes de marcas y categorias maestras.

## Token de autorizacion

`CATALOGO_IMAGENES_MARCAS_CATEGORIAS`

## Tablas a crear

- `erp_catalogo_marca_imagenes`
- `erp_catalogo_categoria_imagenes`

## Motivo operativo

Catalogo necesita poder administrar:

- logo o referencia visual de marca;
- icono, portada o referencia visual de categoria;
- imagenes trazables sin mezclar archivos visuales dentro del nombre o descripcion del catalogo maestro.

Esto ayuda a completar productos migrados y prepara futuros canales visuales sin convertir Catalogo en ecommerce.

## Alcance permitido

- Crear solo las tablas nuevas de imagenes de marcas/categorias.
- Validar auditoria antes y despues.
- No migrar imagenes historicas automaticamente.
- No tocar Ventas, Compras, Almacen ni Inventario.
- No crear tablas de paquetes en esta autorizacion.

## Archivos involucrados

- `app/modelos/CatalogoErpEsquema.php`
- `storage/uat/uat_catalogo_imagenes_autorizacion_preflight_readonly.php`
- `storage/uat/uat_catalogo_imagenes_schema_apply_authorized.php`
- `docs/erp_catalogo_configuracion_plan.md`
- `docs/erp_catalogo_avance.md`
- `docs/erp_catalogo_imagenes_marcas_categorias_runbook_aplicacion.md`

## Requisitos previos

1. Respaldo externo de base de datos fuera de `C:\xampp\htdocs\panel`.
2. Confirmar que el respaldo existe, es legible y no esta vacio.
3. Tener usuario con permiso `sistema.soporte`.
4. Revisar auditoria previa de Catalogo.
5. Confirmar que el unico alcance autorizado es imagenes de marcas/categorias.

## Nota de alcance

No usar el endpoint general `/sistema/esquema_actualizar_catalogo_erp` para esta autorizacion, porque el plan general tambien contiene tablas de paquetes pendientes.

La aplicacion autorizada debe usar el script acotado:

```txt
C:\xampp\php\php.exe storage\uat\uat_catalogo_imagenes_schema_apply_authorized.php --token=CATALOGO_IMAGENES_MARCAS_CATEGORIAS --respaldo="RUTA_REAL_EXTERNA"
```

## Frase sugerida para autorizar

```txt
Autorizo aplicar DDL de imagenes de marcas y categorias de Catalogo con token CATALOGO_IMAGENES_MARCAS_CATEGORIAS.
Respaldo externo: <ruta real fuera del proyecto>.
Alcance: crear erp_catalogo_marca_imagenes y erp_catalogo_categoria_imagenes, sin migrar imagenes ni tocar otros modulos.
```

## Criterio de no ejecucion

No ejecutar si:

- no existe respaldo externo;
- la autorizacion no incluye el token;
- la autorizacion mezcla otros cambios no revisados;
- la auditoria previa muestra faltantes inesperados que no correspondan al plan de Catalogo vigente.
