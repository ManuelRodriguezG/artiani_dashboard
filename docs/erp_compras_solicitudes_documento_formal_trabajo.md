# ERP Compras - Solicitudes: documento formal

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Subdocumento puntual de `erp_compras_solicitudes_trabajo.md`  
Aplica a: archivo formal PDF o HTML imprimible desde ver solicitud

## Proposito

Generar un documento presentable de la solicitud con logotipo e identidad del negocio, util para aprobacion, archivo, auditoria o comunicacion interna.

## Formato esperado

- PDF recomendado o HTML imprimible.
- Logotipo del negocio.
- Nombre del negocio.
- Folio/id de solicitud.
- Fecha de creacion.
- Estatus.
- Solicitante.
- Area/departamento.
- Prioridad.
- Fecha requerida.
- Proveedor sugerido.
- Almacen destino sugerido.
- Motivo/observaciones.
- Tabla de partidas.
- Productos nuevos/propuestos marcados.
- Totales estimados si se capturan.
- Bloque de aprobacion/rechazo.
- Orden relacionada si existe.
- Pie con fecha de generacion.

## Permisos

- Generar/ver archivo: `compras.ver`.
- Mostrar costos estimados: `compras.ver` o permiso mas restrictivo si el dueno decide.
- Ver auditoria completa: permiso auditor/soporte si se crea.

## Reglas

- No modifica datos.
- Refleja el estado actual de la solicitud.
- Si esta en `borrador`, marcar como borrador.
- Si esta aprobada/rechazada/cancelada, mostrar datos del estado.
- Si tiene orden, mostrar referencia.

## Criterios de terminado

- Desde ver solicitud se puede descargar/imprimir archivo formal.
- El archivo se ve presentable y tiene logotipo.
- Respeta permisos de costos/informacion sensible.

## Prompt puntual

```text
Trabaja solo en Compras > Solicitudes > Documento formal.
Objetivo: generar PDF o HTML imprimible desde ver solicitud con logotipo, folio, estatus, solicitante, partidas, proveedor/almacen sugeridos, aprobacion y orden relacionada si existe.
No modificar datos al generar el archivo.
Criterio: el archivo es presentable y respeta permisos de costos.
```
