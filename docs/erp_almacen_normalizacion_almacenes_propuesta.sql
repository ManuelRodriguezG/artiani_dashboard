-- ERP Almacen - Propuesta de normalizacion de erp_almacenes
-- Fecha: 2026-06-18
-- Estado: NO EJECUTAR sin confirmacion operativa, respaldo externo y evidencia antes/despues.
--
-- Objetivo:
-- - Hacer explicita la regla que hoy el codigo ya asume con COALESCE(estatus,'activo').
-- - Clasificar los almacenes para futuros permisos, reportes y filtros.
--
-- Decision pendiente:
-- - Confirmar si los tres almacenes son sucursales activas.
-- - Confirmar si alguno debe ser principal, transito, devoluciones, merma o inactivo.
-- - Confirmar datos de contacto si se quieren usar en Compras/Recepcion.
--
-- Tipos recomendados para un ERP robusto:
-- - principal: bodega central o matriz.
-- - sucursal: punto fisico operativo con inventario propio.
-- - transito: mercancia en movimiento, no disponible para venta.
-- - devoluciones: mercancia devuelta en evaluacion.
-- - merma: producto no vendible por dano/caducidad/perdida.
-- - cuarentena: recibido pero pendiente de revision/liberacion.
--
-- Recomendacion actual:
-- - Usar activo/sucursal para los 3 registros existentes porque parecen
--   direcciones fisicas operativas, no almacenes tecnicos.
-- - Crear almacenes tecnicos separados despues si se requiere transito,
--   devoluciones, merma o cuarentena.

SELECT id_almacen, almacen, ciudad, colonia, calle, numero_exterior,
       estatus, tipo_almacen, contacto_recepcion, telefono_recepcion, email_recepcion
FROM erp_almacenes
ORDER BY id_almacen;

-- Propuesta conservadora inicial:
-- Mantiene contactos en NULL y solo completa estatus/tipo.
-- Ejecutar solo si el dueno confirma que los 3 registros son sucursales activas.

UPDATE erp_almacenes
SET estatus = 'activo',
    tipo_almacen = 'sucursal'
WHERE id_almacen IN (1, 2, 3)
  AND estatus IS NULL
  AND tipo_almacen IS NULL;

SELECT id_almacen, almacen, estatus, tipo_almacen
FROM erp_almacenes
WHERE id_almacen IN (1, 2, 3)
ORDER BY id_almacen;
