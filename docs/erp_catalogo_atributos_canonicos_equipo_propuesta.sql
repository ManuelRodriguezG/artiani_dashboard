-- IA: Codex GPT-5
-- Fecha: 2026-08-10
-- Proposito: propuesta no aplicada para crear atributos canonicos de equipo electrico/filtracion en Catalogo ERP.
-- Impacto: si se autoriza, agrega registros maestros en erp_catalogo_atributos; no migra valores de SKUs.
-- Contrato: ejecutar solo con respaldo/autorizacion si se decide aplicar en BD.

INSERT INTO erp_catalogo_atributos
  (codigo, nombre, tipo_dato, unidad, configuracion_json, es_variante, estatus)
VALUES
  ('ATR-CONSUMO-ELECTRICO', 'consumo_electrico', 'numero', 'w', NULL, 0, 'activo'),
  ('ATR-CAUDAL', 'caudal', 'numero', 'l/h', NULL, 0, 'activo'),
  ('ATR-ALTURA-MAXIMA', 'altura_maxima', 'numero', 'm', NULL, 0, 'activo'),
  ('ATR-CAP-ACUARIO-MIN', 'capacidad_acuario_min', 'numero', 'l', NULL, 0, 'activo'),
  ('ATR-CAP-ACUARIO-MAX', 'capacidad_acuario_max', 'numero', 'l', NULL, 0, 'activo')
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  tipo_dato = VALUES(tipo_dato),
  unidad = VALUES(unidad),
  es_variante = VALUES(es_variante),
  estatus = VALUES(estatus);
