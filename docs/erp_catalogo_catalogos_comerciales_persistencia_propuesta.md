# ERP Catalogo - Propuesta de persistencia para catalogos comerciales

Fecha: 2026-07-24
Proyecto: `C:\xampp\htdocs\panel_de_control`
Estado: DDL base aplicado y verificado para persistencia inicial.

## Objetivo

Convertir el MVP local de `Catalogos comerciales` en catalogos ERP formales, compartidos entre usuarios y recuperables desde cualquier equipo.

El MVP actual ya permite:

- consultar candidatos desde Catalogo ERP;
- seleccionar productos;
- ordenar tarjetas;
- configurar datos del material;
- configurar portada;
- copiar listado;
- imprimir/capturar;
- guardar borradores locales en navegador;
- exportar/importar JSON.

Lo que falta para produccion:

- guardar en base de datos;
- compartir entre usuarios;
- permisos propios;
- auditoria;
- estatus formales;
- versionado/exportaciones.

## Decision de ubicacion

La operacion pertenece a Comercial, porque produce material de venta.

La fuente de datos sigue siendo Catalogo ERP:

- productos;
- SKU;
- imagenes;
- categorias;
- marcas;
- presentaciones;
- paquetes;
- reglas comerciales visibles.

No debe depender del catalogo PDF legacy ni de tablas `ecom_*` como fuente principal.

## Estados propuestos

Estados de `erp_catalogo_comercial_catalogos.estatus`:

- `borrador`: armado interno en progreso.
- `revision`: listo para validacion comercial.
- `publicado`: material aprobado para uso comercial.
- `archivado`: ya no aparece en trabajo diario.

Regla:

- No usar `activo` como sinonimo de listo para vender.
- `publicado` significa listo para usarse como material comercial, no que el producto este vendible.

## Permisos propuestos

Modulo: `catalogos_comerciales`

- `catalogos_comerciales.ver`: ver listado, detalle y previsualizacion.
- `catalogos_comerciales.crear`: crear catalogos/borradores.
- `catalogos_comerciales.editar`: modificar portada, material, items y orden.
- `catalogos_comerciales.publicar`: cambiar de revision a publicado.
- `catalogos_comerciales.archivar`: archivar/restaurar catalogos.
- `catalogos_comerciales.exportar`: generar o descargar salidas.

Durante MVP se usa `catalogo.ver`, pero debe reemplazarse al formalizar.

## Tablas propuestas

### `erp_catalogo_comercial_catalogos`

Guarda el encabezado del catalogo comercial.

Columnas:

- `id_catalogo_comercial` BIGINT PK.
- `codigo` VARCHAR(40) NULL.
- `nombre` VARCHAR(120) NOT NULL.
- `titulo` VARCHAR(120) NOT NULL.
- `subtitulo` VARCHAR(180) NULL.
- `cta` VARCHAR(160) NULL.
- `plantilla` VARCHAR(30) NOT NULL DEFAULT `square`.
- `mostrar_precio` TINYINT(1) NOT NULL DEFAULT 1.
- `mostrar_marca` TINYINT(1) NOT NULL DEFAULT 1.
- `mostrar_categoria` TINYINT(1) NOT NULL DEFAULT 0.
- `mostrar_presentacion` TINYINT(1) NOT NULL DEFAULT 1.
- `mostrar_sku` TINYINT(1) NOT NULL DEFAULT 0.
- `mostrar_disponibilidad` TINYINT(1) NOT NULL DEFAULT 0.
- `portada_activa` TINYINT(1) NOT NULL DEFAULT 1.
- `portada_etiqueta` VARCHAR(80) NULL.
- `portada_descripcion` VARCHAR(255) NULL.
- `portada_nota` VARCHAR(180) NULL.
- `estatus` VARCHAR(30) NOT NULL DEFAULT `borrador`.
- `id_usuario_creacion` INT NULL.
- `id_usuario_actualizacion` INT NULL.
- `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP.
- `fecha_actualizacion` DATETIME NULL.

Indices:

- `idx_catalogo_comercial_estatus` (`estatus`).
- `idx_catalogo_comercial_nombre` (`nombre`).
- `idx_catalogo_comercial_codigo` (`codigo`).

### `erp_catalogo_comercial_items`

Guarda los productos/SKUs incluidos y su orden.

Columnas:

- `id_catalogo_item` BIGINT PK.
- `id_catalogo_comercial` BIGINT NOT NULL.
- `id_sku` BIGINT NOT NULL.
- `tipo_item` VARCHAR(30) NOT NULL DEFAULT `sku`.
- `posicion` INT NOT NULL DEFAULT 0.
- `titulo_override` VARCHAR(160) NULL.
- `descripcion_override` VARCHAR(255) NULL.
- `precio_texto_override` VARCHAR(80) NULL.
- `nota_item` VARCHAR(180) NULL.
- `estatus` TINYINT(1) NOT NULL DEFAULT 1.
- `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP.
- `fecha_actualizacion` DATETIME NULL.

Indices:

- `idx_catalogo_comercial_item_catalogo` (`id_catalogo_comercial`, `posicion`).
- `idx_catalogo_comercial_item_sku` (`id_sku`).
- `idx_catalogo_comercial_item_unico` (`id_catalogo_comercial`, `id_sku`, `tipo_item`).

Regla:

- No guardar costos.
- El precio debe resolverse desde Ventas/Listas de precios cuando exista ese contrato.
- `precio_texto_override` solo debe usarse como texto comercial controlado, no como costo ni precio oficial.

### `erp_catalogo_comercial_eventos`

Guarda eventos de negocio del catalogo comercial.

Columnas:

- `id_evento` BIGINT PK.
- `id_catalogo_comercial` BIGINT NOT NULL.
- `evento` VARCHAR(40) NOT NULL.
- `estatus_anterior` VARCHAR(30) NULL.
- `estatus_nuevo` VARCHAR(30) NULL.
- `detalle_json` TEXT NULL.
- `id_usuario` INT NULL.
- `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP.

Indices:

- `idx_catalogo_comercial_evento_catalogo` (`id_catalogo_comercial`, `fecha_registro`).
- `idx_catalogo_comercial_evento_evento` (`evento`).

Eventos esperados:

- `creado`;
- `actualizado`;
- `items_actualizados`;
- `en_revision`;
- `publicado`;
- `archivado`;
- `restaurado`;
- `exportado`.

## DDL propuesto

No aplicar sin autorizacion explicita.

```sql
CREATE TABLE IF NOT EXISTS `erp_catalogo_comercial_catalogos` (
  `id_catalogo_comercial` BIGINT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(40) NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `titulo` VARCHAR(120) NOT NULL,
  `subtitulo` VARCHAR(180) NULL,
  `cta` VARCHAR(160) NULL,
  `plantilla` VARCHAR(30) NOT NULL DEFAULT 'square',
  `mostrar_precio` TINYINT(1) NOT NULL DEFAULT 1,
  `mostrar_marca` TINYINT(1) NOT NULL DEFAULT 1,
  `mostrar_categoria` TINYINT(1) NOT NULL DEFAULT 0,
  `mostrar_presentacion` TINYINT(1) NOT NULL DEFAULT 1,
  `mostrar_sku` TINYINT(1) NOT NULL DEFAULT 0,
  `mostrar_disponibilidad` TINYINT(1) NOT NULL DEFAULT 0,
  `portada_activa` TINYINT(1) NOT NULL DEFAULT 1,
  `portada_etiqueta` VARCHAR(80) NULL,
  `portada_descripcion` VARCHAR(255) NULL,
  `portada_nota` VARCHAR(180) NULL,
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador',
  `id_usuario_creacion` INT NULL,
  `id_usuario_actualizacion` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_catalogo_comercial`),
  KEY `idx_catalogo_comercial_estatus` (`estatus`),
  KEY `idx_catalogo_comercial_nombre` (`nombre`),
  KEY `idx_catalogo_comercial_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_catalogo_comercial_items` (
  `id_catalogo_item` BIGINT NOT NULL AUTO_INCREMENT,
  `id_catalogo_comercial` BIGINT NOT NULL,
  `id_sku` BIGINT NOT NULL,
  `tipo_item` VARCHAR(30) NOT NULL DEFAULT 'sku',
  `posicion` INT NOT NULL DEFAULT 0,
  `titulo_override` VARCHAR(160) NULL,
  `descripcion_override` VARCHAR(255) NULL,
  `precio_texto_override` VARCHAR(80) NULL,
  `nota_item` VARCHAR(180) NULL,
  `estatus` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_catalogo_item`),
  KEY `idx_catalogo_comercial_item_catalogo` (`id_catalogo_comercial`, `posicion`),
  KEY `idx_catalogo_comercial_item_sku` (`id_sku`),
  UNIQUE KEY `idx_catalogo_comercial_item_unico` (`id_catalogo_comercial`, `id_sku`, `tipo_item`),
  CONSTRAINT `fk_catalogo_comercial_item_catalogo` FOREIGN KEY (`id_catalogo_comercial`) REFERENCES `erp_catalogo_comercial_catalogos` (`id_catalogo_comercial`),
  CONSTRAINT `fk_catalogo_comercial_item_sku` FOREIGN KEY (`id_sku`) REFERENCES `erp_catalogo_skus` (`id_sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_catalogo_comercial_eventos` (
  `id_evento` BIGINT NOT NULL AUTO_INCREMENT,
  `id_catalogo_comercial` BIGINT NOT NULL,
  `evento` VARCHAR(40) NOT NULL,
  `estatus_anterior` VARCHAR(30) NULL,
  `estatus_nuevo` VARCHAR(30) NULL,
  `detalle_json` TEXT NULL,
  `id_usuario` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evento`),
  KEY `idx_catalogo_comercial_evento_catalogo` (`id_catalogo_comercial`, `fecha_registro`),
  KEY `idx_catalogo_comercial_evento_evento` (`evento`),
  CONSTRAINT `fk_catalogo_comercial_evento_catalogo` FOREIGN KEY (`id_catalogo_comercial`) REFERENCES `erp_catalogo_comercial_catalogos` (`id_catalogo_comercial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Endpoints propuestos

Controlador temporal: `Catalogoerp`.

Rutas:

- `catalogos_comerciales_listar`
- `catalogos_comerciales_consultar`
- `catalogos_comerciales_guardar`
- `catalogos_comerciales_items_guardar`
- `catalogos_comerciales_estatus`
- `catalogos_comerciales_exportar`

Permisos:

- listar/consultar: `catalogos_comerciales.ver`;
- guardar/items: `catalogos_comerciales.crear` o `catalogos_comerciales.editar`;
- publicar: `catalogos_comerciales.publicar`;
- archivar/restaurar: `catalogos_comerciales.archivar`;
- exportar: `catalogos_comerciales.exportar`.

## Flujo recomendado

1. Aplicar DDL base autorizada.
2. Agregar auditoria de esquema en `CatalogoErpEsquema`.
3. Crear modelo de datos para catalogos comerciales.
4. Cambiar MVP local para cargar/guardar contra BD.
5. Mantener importar/exportar JSON como herramienta auxiliar.
6. Agregar listado de catalogos guardados.
7. Agregar estados y permisos propios.
8. Evaluar exportacion visual formal.

## Criterio de autorizacion

Para aplicar esta fase, usar una autorizacion explicita similar a:

`Autorizo aplicar DDL de Catalogos comerciales con token CATALOGO_COMERCIAL_PERSISTENCIA_DDL. Respaldo externo: C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_antes_catalogos_comerciales.sql. Alcance: crear tablas erp_catalogo_comercial_catalogos, erp_catalogo_comercial_items y erp_catalogo_comercial_eventos; sin publicar enlaces, sin exportacion automatica y sin tocar Ventas.`

## Aplicacion autorizada 2026-07-24

Autorizacion recibida:

- Token: `CATALOGO_COMERCIAL_PERSISTENCIA_DDL`.
- Confirmacion: `APLICAR CATALOGOS COMERCIALES`.
- Respaldo externo: `C:\xampp\panel_db_backups\artianilocal_panel_20260724_antes_catalogos_comerciales.sql`.

Tablas aplicadas:

- `erp_catalogo_comercial_catalogos`.
- `erp_catalogo_comercial_items`.
- `erp_catalogo_comercial_eventos`.

Validacion:

- El apply acotado se ejecuto sobre `C:\xampp\htdocs\panel_de_control`.
- Auditoria puntual posterior confirmo las tres tablas como existentes.

Fuera de alcance cumplido:

- No se publicaron enlaces.
- No se agrego exportacion automatica.
- No se toco Ventas.
- No se migro ningun borrador local.

## Preparacion de apply acotado 2026-07-24

Se preparo script bloqueado:

- `storage/uat/uat_catalogo_comercial_persistencia_schema_apply_authorized.php`

Ruta de respaldos detectada:

- `C:\xampp\panel_db_backups`

Patron historico observado:

- `artianilocal_panel_YYYYMMDD_antes_<alcance>.sql`

Comando de apply autorizado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_comercial_persistencia_schema_apply_authorized.php --autorizar=CATALOGO_COMERCIAL_PERSISTENCIA_DDL --confirmacion="APLICAR CATALOGOS COMERCIALES" --respaldo="C:\xampp\panel_db_backups\artianilocal_panel_YYYYMMDD_antes_catalogos_comerciales.sql"
```

Validacion:

- El script permanece bloqueado sin token, confirmacion exacta y respaldo externo legible.
- `CatalogoErpEsquema::planActualizarCatalogosComerciales(false)` devuelve 3 pasos en dry-run.
- No usa el actualizador general del catalogo.
