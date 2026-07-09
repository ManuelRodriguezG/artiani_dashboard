-- ERP Almacen Configuracion - DDL propuesto
-- Fecha: 2026-06-21
-- Clave: ALM-CFG-001
--
-- NO EJECUTAR sin:
-- 1) respaldo externo con mysqldump;
-- 2) autorizacion explicita;
-- 3) evidencia antes/despues.

ALTER TABLE erp_almacenes
  ADD COLUMN codigo_almacen VARCHAR(40) NULL AFTER id_almacen,
  ADD COLUMN nombre_comercial VARCHAR(150) NULL AFTER almacen,
  ADD COLUMN permite_recepcion TINYINT(1) NOT NULL DEFAULT 1 AFTER tipo_almacen,
  ADD COLUMN permite_venta TINYINT(1) NOT NULL DEFAULT 0 AFTER permite_recepcion,
  ADD COLUMN permite_preparacion TINYINT(1) NOT NULL DEFAULT 0 AFTER permite_venta,
  ADD COLUMN permite_ajustes TINYINT(1) NOT NULL DEFAULT 1 AFTER permite_preparacion,
  ADD COLUMN es_tecnico TINYINT(1) NOT NULL DEFAULT 0 AFTER permite_ajustes,
  ADD COLUMN orden INT NOT NULL DEFAULT 100 AFTER es_tecnico,
  ADD COLUMN observaciones TEXT NULL AFTER orden,
  ADD COLUMN fecha_actualizacion DATETIME NULL AFTER observaciones;

ALTER TABLE erp_almacenes
  ADD UNIQUE KEY uk_erp_almacenes_codigo (codigo_almacen),
  ADD KEY idx_erp_almacenes_estatus_tipo (estatus, tipo_almacen),
  ADD KEY idx_erp_almacenes_orden (orden);

-- Semilla sugerida para datos actuales. Ajustar nombres/codigos si el dueno prefiere otra nomenclatura.
-- Decision operativa 2026-06-21:
-- - Francisco Javier Mina 1105 ya no existe.
-- - San Jose 1727 ya no existe.
-- - Francisco Javier Mina 967 es local/punto de venta de acuario.
-- - Francisco Javier Mina 971 tiene local frontal de mascotas y bodega trasera en la misma direccion.
-- - Se conserva id_almacen=3 como bodega trasera porque ya tiene recepciones/existencias/movimientos.
UPDATE erp_almacenes
SET codigo_almacen='MINA1105-BAJA',
    nombre_comercial='Francisco Javier Mina 1105',
    tipo_almacen='sucursal',
    estatus='inactivo',
    permite_recepcion=0,
    permite_venta=0,
    permite_preparacion=0,
    permite_ajustes=0,
    es_tecnico=0,
    orden=900,
    observaciones='Almacen historico inactivo: direccion ya no existe operativamente.',
    fecha_actualizacion=NOW()
WHERE id_almacen=1;

UPDATE erp_almacenes
SET codigo_almacen='SANJOSE1727-BAJA',
    nombre_comercial='San Jose 1727',
    tipo_almacen='sucursal',
    estatus='inactivo',
    permite_recepcion=0,
    permite_venta=0,
    permite_preparacion=0,
    permite_ajustes=0,
    es_tecnico=0,
    orden=910,
    observaciones='Almacen historico inactivo: direccion ya no existe operativamente.',
    fecha_actualizacion=NOW()
WHERE id_almacen=2;

UPDATE erp_almacenes
SET codigo_almacen='BOD971',
    almacen='Francisco Javier Mina 971 - Bodega trasera',
    nombre_comercial='Bodega trasera Mina 971',
    tipo_almacen='bodega',
    estatus='activo',
    permite_recepcion=1,
    permite_venta=0,
    permite_preparacion=1,
    permite_ajustes=1,
    es_tecnico=0,
    orden=30,
    observaciones='Bodega en la parte trasera de Francisco Javier Mina 971. Comparte direccion con local frontal de mascotas, pero maneja existencia separada.',
    fecha_actualizacion=NOW()
WHERE id_almacen=3;

INSERT INTO erp_almacenes
  (codigo_almacen, almacen, nombre_comercial, pais, ciudad, colonia, codigo_postal, calle, numero_exterior,
   estatus, tipo_almacen, permite_recepcion, permite_venta, permite_preparacion, permite_ajustes, es_tecnico,
   orden, observaciones)
VALUES
  ('ACUARIO967', 'Francisco Javier Mina 967 - Acuario', 'Acuario Mina 967', 'Mexico', 'Guadalajara', 'Oblatos', '44700',
   'Francisco Javier Mina', '967', 'activo', 'punto_venta', 1, 1, 0, 1, 0, 10,
   'Local de acuario con venta y stock propio.')
ON DUPLICATE KEY UPDATE
  almacen=VALUES(almacen),
  nombre_comercial=VALUES(nombre_comercial),
  estatus=VALUES(estatus),
  tipo_almacen=VALUES(tipo_almacen),
  permite_recepcion=VALUES(permite_recepcion),
  permite_venta=VALUES(permite_venta),
  permite_preparacion=VALUES(permite_preparacion),
  permite_ajustes=VALUES(permite_ajustes),
  es_tecnico=VALUES(es_tecnico),
  orden=VALUES(orden),
  observaciones=VALUES(observaciones),
  fecha_actualizacion=NOW();

INSERT INTO erp_almacenes
  (codigo_almacen, almacen, nombre_comercial, pais, ciudad, colonia, codigo_postal, calle, numero_exterior,
   estatus, tipo_almacen, permite_recepcion, permite_venta, permite_preparacion, permite_ajustes, es_tecnico,
   orden, observaciones)
VALUES
  ('MASCOTAS971', 'Francisco Javier Mina 971 - Mascotas frontal', 'Mascotas Mina 971', 'Mexico', 'Guadalajara', 'Oblatos', '44700',
   'Francisco Javier Mina', '971', 'activo', 'punto_venta', 1, 1, 0, 1, 0, 20,
   'Local frontal de accesorios/mascotas con venta y stock propio. Comparte direccion con bodega trasera.')
ON DUPLICATE KEY UPDATE
  almacen=VALUES(almacen),
  nombre_comercial=VALUES(nombre_comercial),
  estatus=VALUES(estatus),
  tipo_almacen=VALUES(tipo_almacen),
  permite_recepcion=VALUES(permite_recepcion),
  permite_venta=VALUES(permite_venta),
  permite_preparacion=VALUES(permite_preparacion),
  permite_ajustes=VALUES(permite_ajustes),
  es_tecnico=VALUES(es_tecnico),
  orden=VALUES(orden),
  observaciones=VALUES(observaciones),
  fecha_actualizacion=NOW();

-- Recomendacion posterior, no incluida en esta fase:
-- crear tabla de alcance por usuario/almacen para permisos finos multi-sucursal.
