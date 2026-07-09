-- ERP - Propuesta de esquema para etiquetas, series y trazabilidad por unidad
-- Fecha: 2026-06-18
-- Estado: NO EJECUTAR sin respaldo externo, preview y autorizacion explicita.

-- 1) Preview recomendado antes de DDL.
SHOW COLUMNS FROM erp_catalogo_sku_reglas_inventario;
SHOW COLUMNS FROM erp_inventario_unidades;
SHOW INDEX FROM erp_inventario_unidades;

-- 2) Catalogo: separar serie real de etiqueta de trazabilidad interna.
ALTER TABLE erp_catalogo_sku_reglas_inventario
  ADD COLUMN requiere_serie_fabricante TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_serie,
  ADD COLUMN generar_etiqueta_interna TINYINT(1) NOT NULL DEFAULT 0 AFTER requiere_serie_fabricante,
  ADD COLUMN requiere_escaneo_venta TINYINT(1) NOT NULL DEFAULT 0 AFTER generar_etiqueta_interna,
  ADD COLUMN prefijo_etiqueta_interna VARCHAR(30) NULL AFTER requiere_escaneo_venta,
  ADD COLUMN plantilla_etiqueta VARCHAR(80) NULL AFTER prefijo_etiqueta_interna,
  ADD COLUMN tipo_etiqueta_seguridad VARCHAR(40) NULL AFTER plantilla_etiqueta,
  ADD COLUMN instrucciones_etiquetado TEXT NULL AFTER tipo_etiqueta_seguridad;

-- 3) Inventario: separar codigo unico, serie fabricante y etiqueta interna.
ALTER TABLE erp_inventario_unidades
  ADD COLUMN id_sku_erp BIGINT NULL AFTER id_producto,
  ADD COLUMN tipo_identidad VARCHAR(30) NOT NULL DEFAULT 'etiqueta_interna' AFTER codigo_unico,
  ADD COLUMN serie_fabricante VARCHAR(120) NULL AFTER tipo_identidad,
  ADD COLUMN codigo_etiqueta_interna VARCHAR(120) NULL AFTER serie_fabricante,
  ADD COLUMN estado_etiqueta VARCHAR(30) NOT NULL DEFAULT 'pendiente_impresion' AFTER estatus,
  ADD COLUMN fecha_impresion DATETIME NULL AFTER estado_etiqueta,
  ADD COLUMN impreso_por INT NULL AFTER fecha_impresion,
  ADD COLUMN fecha_etiquetado DATETIME NULL AFTER impreso_por,
  ADD COLUMN etiquetado_por INT NULL AFTER fecha_etiquetado,
  ADD COLUMN origen_tipo VARCHAR(50) NULL AFTER etiquetado_por,
  ADD COLUMN origen_id INT NULL AFTER origen_tipo,
  ADD COLUMN origen_detalle_id INT NULL AFTER origen_id;

-- 4) Indices sugeridos.
CREATE INDEX idx_inventario_unidad_sku_erp
  ON erp_inventario_unidades (id_sku_erp);

CREATE INDEX idx_inventario_unidad_serie_fabricante
  ON erp_inventario_unidades (serie_fabricante);

CREATE INDEX idx_inventario_unidad_etiqueta_interna
  ON erp_inventario_unidades (codigo_etiqueta_interna);

CREATE INDEX idx_inventario_unidad_origen
  ON erp_inventario_unidades (origen_tipo, origen_id, origen_detalle_id);

CREATE INDEX idx_inventario_unidad_estado_etiqueta
  ON erp_inventario_unidades (estado_etiqueta);

-- 5) Unicos sugeridos por politica.
-- Revisar duplicados antes de crear. MySQL/MariaDB permite multiples NULL en unique.
CREATE UNIQUE INDEX uk_inventario_unidad_serie_fabricante
  ON erp_inventario_unidades (serie_fabricante);

CREATE UNIQUE INDEX uk_inventario_unidad_etiqueta_interna
  ON erp_inventario_unidades (codigo_etiqueta_interna);

ALTER TABLE erp_inventario_unidades
  ADD CONSTRAINT fk_inv_unidad_sku
  FOREIGN KEY (id_sku_erp)
  REFERENCES erp_catalogo_skus (id_sku)
  ON UPDATE RESTRICT
  ON DELETE RESTRICT;

-- 6) Migracion conceptual posterior, si se aprueba.
-- Para SKUs donde actualmente requiere_serie=1 por serie real:
-- UPDATE erp_catalogo_sku_reglas_inventario
-- SET requiere_serie_fabricante = 1
-- WHERE requiere_serie = 1;

-- Para productos que la empresa quiere etiquetar sin serie real:
-- UPDATE erp_catalogo_sku_reglas_inventario r
-- INNER JOIN erp_catalogo_skus s ON s.id_sku = r.id_sku
-- SET r.generar_etiqueta_interna = 1,
--     r.requiere_escaneo_venta = 1,
--     r.prefijo_etiqueta_interna = 'ART'
-- WHERE s.sku IN ('SKU-PILOTO');

-- Aplicado en local el 2026-06-18 con respaldo externo:
-- artianilocal_panel_20260619_antes_etiquetas_series_schema.sql
