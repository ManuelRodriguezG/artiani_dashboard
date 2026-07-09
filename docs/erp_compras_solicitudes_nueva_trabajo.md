# ERP Compras - Solicitudes: nueva solicitud

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Subdocumento puntual de `erp_compras_solicitudes_trabajo.md`  
Aplica a: captura inicial de necesidad de compra

## Proposito

Definir la experiencia de crear una solicitud de compra. Una nueva solicitud registra una necesidad; no compra, no paga y no afecta inventario.

## Archivos de trabajo

- `app/vistas/paginas/apps/erp/compras/solicitudes/formulario.php`
- `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js`
- `app/controladores/Compra.php`
- `app/modelos/SolicitudesCompraErp.php`
- `app/modelos/ComprasEsquema.php` si falta estructura.

## Cabecera requerida

- Solicitante.
- Area/departamento si aplica.
- Prioridad.
- Proveedor sugerido opcional.
- Almacen destino sugerido.
- Fecha requerida.
- Motivo/observaciones.

## Detalle requerido

- Producto/SKU ERP si existe.
- Producto nuevo/propuesto si no existe.
- Descripcion libre.
- Cantidad.
- Unidad.
- Costo estimado opcional.
- Motivo por partida opcional.

## Flujo esperado

1. Usuario abre nueva solicitud.
2. Captura cabecera.
3. Agrega productos ERP o productos propuestos.
4. Guarda borrador o envia a aprobacion.
5. Si hay productos nuevos, se generan pendientes para Catalogo cuando corresponda.

## Permisos

- Abrir/ver formulario: `compras.ver` o `compras.crear`.
- Crear/guardar: `compras.crear`.
- Consultar productos/SKUs: `catalogo.ver` o consulta limitada por Compras.
- Enviar a aprobacion: `compras.crear` o `compras.editar`, segun regla final.

## Reglas

- No afecta inventario.
- No crea orden automaticamente.
- No crea producto maestro automaticamente.
- Puede guardarse como `borrador`.
- Para enviar a aprobacion debe tener datos minimos.

## Criterios de terminado

- Se puede crear solicitud con productos ERP.
- Se puede crear solicitud con producto propuesto.
- Guardar borrador conserva captura.
- Enviar a aprobacion valida datos minimos.
- Backend valida permisos y estado.

## Prompt puntual

```text
Trabaja solo en Compras > Solicitudes > Nueva solicitud.
Objetivo: implementar/ajustar captura de cabecera y partidas con productos ERP o propuestos, guardado borrador y envio a aprobacion.
No crear orden, no afectar inventario, no crear producto maestro.
Criterio: la solicitud se guarda y cambia a pendiente solo con datos minimos validos.
```
