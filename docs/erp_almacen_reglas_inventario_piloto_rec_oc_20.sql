-- ERP Almacen - Piloto de reglas lote/caducidad para REC-OC-20
-- Fecha: 2026-06-18
-- Estado: APLICADO para 5 SKUs piloto con respaldo externo.
-- Respaldo externo: artianilocal_panel_20260619_almacen_reglas_piloto_rec_oc_20.sql

-- 1) Preview antes
SELECT
    s.id_sku,
    s.sku,
    s.nombre,
    r.controla_inventario,
    r.requiere_lote,
    r.requiere_caducidad,
    r.requiere_serie,
    r.estrategia_salida,
    r.dias_alerta_caducidad,
    r.dias_minimos_recepcion
FROM erp_catalogo_sku_reglas_inventario r
INNER JOIN erp_catalogo_skus s
    ON s.id_sku = r.id_sku
WHERE s.sku IN ('TP-7838', 'TP-7840', 'SFF-03', 'SFF-303', 'TP-40372')
ORDER BY s.sku;

-- 2) Conteo de alcance esperado
SELECT COUNT(*) AS skus_piloto
FROM erp_catalogo_skus
WHERE sku IN ('TP-7838', 'TP-7840', 'SFF-03', 'SFF-303', 'TP-40372');

-- 3) UPDATE aplicado despues de respaldo externo y autorizacion
-- SQL ejecutado:
/*
UPDATE erp_catalogo_sku_reglas_inventario r
INNER JOIN erp_catalogo_skus s
    ON s.id_sku = r.id_sku
SET r.requiere_lote = 1,
    r.requiere_caducidad = 1,
    r.requiere_serie = 0,
    r.estrategia_salida = 'FEFO',
    r.dias_alerta_caducidad = 90,
    r.dias_minimos_recepcion = 30,
    r.fecha_actualizacion = NOW()
WHERE s.sku IN ('TP-7838', 'TP-7840', 'SFF-03', 'SFF-303', 'TP-40372');
*/

-- 4) Preview despues
SELECT
    s.id_sku,
    s.sku,
    s.nombre,
    r.controla_inventario,
    r.requiere_lote,
    r.requiere_caducidad,
    r.requiere_serie,
    r.estrategia_salida,
    r.dias_alerta_caducidad,
    r.dias_minimos_recepcion,
    r.fecha_actualizacion
FROM erp_catalogo_sku_reglas_inventario r
INNER JOIN erp_catalogo_skus s
    ON s.id_sku = r.id_sku
WHERE s.sku IN ('TP-7838', 'TP-7840', 'SFF-03', 'SFF-303', 'TP-40372')
ORDER BY s.sku;
