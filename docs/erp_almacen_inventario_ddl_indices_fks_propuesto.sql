-- ERP Almacen/Inventario - DDL propuesto para indices y FKs
-- Fecha: 2026-06-18
-- Estado: NO EJECUTAR sin autorizacion, respaldo externo y auditoria de orfandades inmediata.
--
-- Reglas:
-- - Ejecutar por bloques pequenos.
-- - Repetir consultas de orfandades justo antes.
-- - Usar RESTRICT para conservar historial operativo.
-- - No usar CASCADE salvo decision explicita del dueno.
-- - En MySQL/MariaDB el DDL hace autocommit; no confiar en ROLLBACK para revertir.

USE artianilocal;

-- =========================================================
-- Bloque 0 - Auditoria previa obligatoria
-- =========================================================

SELECT 'recepciones_sin_orden' revision, COUNT(*) total
FROM erp_almacen_recepciones r
LEFT JOIN erp_compras_ordenes o ON o.id_orden_compra = r.id_orden_compra
WHERE o.id_orden_compra IS NULL
UNION ALL
SELECT 'recepciones_sin_almacen', COUNT(*)
FROM erp_almacen_recepciones r
LEFT JOIN erp_almacenes a ON a.id_almacen = r.id_almacen
WHERE r.id_almacen IS NOT NULL AND a.id_almacen IS NULL
UNION ALL
SELECT 'detalle_sin_recepcion', COUNT(*)
FROM erp_almacen_recepciones_detalle d
LEFT JOIN erp_almacen_recepciones r ON r.id_recepcion_almacen = d.id_recepcion_almacen
WHERE r.id_recepcion_almacen IS NULL
UNION ALL
SELECT 'detalle_sin_oc_detalle', COUNT(*)
FROM erp_almacen_recepciones_detalle d
LEFT JOIN erp_compras_ordenes_detalle od ON od.id_detalle = d.id_orden_compra_detalle
WHERE d.id_orden_compra_detalle IS NOT NULL AND od.id_detalle IS NULL
UNION ALL
SELECT 'detalle_sku_inexistente', COUNT(*)
FROM erp_almacen_recepciones_detalle d
LEFT JOIN erp_catalogo_skus s ON s.id_sku = d.id_sku_erp
WHERE d.id_sku_erp IS NOT NULL AND s.id_sku IS NULL
UNION ALL
SELECT 'lotes_sin_recepcion', COUNT(*)
FROM erp_almacen_recepciones_lotes l
LEFT JOIN erp_almacen_recepciones r ON r.id_recepcion_almacen = l.id_recepcion_almacen
WHERE r.id_recepcion_almacen IS NULL
UNION ALL
SELECT 'lotes_sin_detalle', COUNT(*)
FROM erp_almacen_recepciones_lotes l
LEFT JOIN erp_almacen_recepciones_detalle d ON d.id_recepcion_detalle = l.id_recepcion_detalle
WHERE d.id_recepcion_detalle IS NULL
UNION ALL
SELECT 'lotes_sku_inexistente', COUNT(*)
FROM erp_almacen_recepciones_lotes l
LEFT JOIN erp_catalogo_skus s ON s.id_sku = l.id_sku_erp
WHERE l.id_sku_erp IS NOT NULL AND s.id_sku IS NULL
UNION ALL
SELECT 'existencias_sku_inexistente', COUNT(*)
FROM erp_inventario_existencias e
LEFT JOIN erp_catalogo_skus s ON s.id_sku = e.id_sku_erp
WHERE s.id_sku IS NULL
UNION ALL
SELECT 'existencias_sin_almacen', COUNT(*)
FROM erp_inventario_existencias e
LEFT JOIN erp_almacenes a ON a.id_almacen = e.id_almacen_clave
WHERE a.id_almacen IS NULL
UNION ALL
SELECT 'movimientos_sku_inexistente', COUNT(*)
FROM erp_inventario_movimientos m
LEFT JOIN erp_catalogo_skus s ON s.id_sku = m.id_sku_erp
WHERE m.id_sku_erp IS NOT NULL AND s.id_sku IS NULL
UNION ALL
SELECT 'movimientos_sin_existencia', COUNT(*)
FROM erp_inventario_movimientos m
LEFT JOIN erp_inventario_existencias e ON e.id_existencia_inventario = m.id_existencia_inventario
WHERE m.id_existencia_inventario IS NOT NULL AND e.id_existencia_inventario IS NULL
UNION ALL
SELECT 'movimientos_sin_lote', COUNT(*)
FROM erp_inventario_movimientos m
LEFT JOIN erp_almacen_recepciones_lotes l ON l.id_recepcion_lote = m.id_recepcion_lote
WHERE m.id_recepcion_lote IS NOT NULL AND l.id_recepcion_lote IS NULL
UNION ALL
SELECT 'ubicaciones_sin_almacen', COUNT(*)
FROM erp_almacen_ubicaciones u
LEFT JOIN erp_almacenes a ON a.id_almacen = u.id_almacen_clave
WHERE a.id_almacen IS NULL;

-- =========================================================
-- Bloque 1 - Indices de soporte faltantes
-- =========================================================

ALTER TABLE erp_almacen_recepciones_detalle
  ADD INDEX idx_recepcion_detalle_oc_detalle (id_orden_compra_detalle);

ALTER TABLE erp_almacen_recepciones_lotes
  ADD INDEX idx_recepcion_lote_almacen (id_almacen);

ALTER TABLE erp_inventario_existencias
  ADD INDEX idx_existencia_almacen_clave (id_almacen_clave);

ALTER TABLE erp_inventario_movimientos
  ADD INDEX idx_inventario_mov_existencia (id_existencia_inventario),
  ADD INDEX idx_inventario_mov_recepcion_lote (id_recepcion_lote);

-- =========================================================
-- Bloque 2 - FKs Recepciones
-- =========================================================

ALTER TABLE erp_almacen_recepciones
  ADD CONSTRAINT fk_alm_rec_oc
    FOREIGN KEY (id_orden_compra)
    REFERENCES erp_compras_ordenes (id_orden_compra)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT fk_alm_rec_almacen
    FOREIGN KEY (id_almacen)
    REFERENCES erp_almacenes (id_almacen)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE erp_almacen_recepciones_detalle
  ADD CONSTRAINT fk_alm_rec_det_rec
    FOREIGN KEY (id_recepcion_almacen)
    REFERENCES erp_almacen_recepciones (id_recepcion_almacen)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT fk_alm_rec_det_oc_det
    FOREIGN KEY (id_orden_compra_detalle)
    REFERENCES erp_compras_ordenes_detalle (id_detalle)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT fk_alm_rec_det_sku
    FOREIGN KEY (id_sku_erp)
    REFERENCES erp_catalogo_skus (id_sku)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE erp_almacen_recepciones_lotes
  ADD CONSTRAINT fk_alm_rec_lote_rec
    FOREIGN KEY (id_recepcion_almacen)
    REFERENCES erp_almacen_recepciones (id_recepcion_almacen)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT fk_alm_rec_lote_det
    FOREIGN KEY (id_recepcion_detalle)
    REFERENCES erp_almacen_recepciones_detalle (id_recepcion_detalle)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT fk_alm_rec_lote_sku
    FOREIGN KEY (id_sku_erp)
    REFERENCES erp_catalogo_skus (id_sku)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT fk_alm_rec_lote_almacen
    FOREIGN KEY (id_almacen)
    REFERENCES erp_almacenes (id_almacen)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

-- =========================================================
-- Bloque 3 - FKs Inventario/Ubicaciones
-- =========================================================

ALTER TABLE erp_inventario_existencias
  ADD CONSTRAINT fk_inv_exist_sku
    FOREIGN KEY (id_sku_erp)
    REFERENCES erp_catalogo_skus (id_sku)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT fk_inv_exist_almacen
    FOREIGN KEY (id_almacen_clave)
    REFERENCES erp_almacenes (id_almacen)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE erp_inventario_movimientos
  ADD CONSTRAINT fk_inv_mov_sku
    FOREIGN KEY (id_sku_erp)
    REFERENCES erp_catalogo_skus (id_sku)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT fk_inv_mov_exist
    FOREIGN KEY (id_existencia_inventario)
    REFERENCES erp_inventario_existencias (id_existencia_inventario)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT fk_inv_mov_lote
    FOREIGN KEY (id_recepcion_lote)
    REFERENCES erp_almacen_recepciones_lotes (id_recepcion_lote)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  ADD CONSTRAINT fk_inv_mov_almacen
    FOREIGN KEY (id_almacen)
    REFERENCES erp_almacenes (id_almacen)
    ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE erp_almacen_ubicaciones
  ADD CONSTRAINT fk_ubicacion_almacen_clave
    FOREIGN KEY (id_almacen_clave)
    REFERENCES erp_almacenes (id_almacen)
    ON UPDATE RESTRICT ON DELETE RESTRICT;
