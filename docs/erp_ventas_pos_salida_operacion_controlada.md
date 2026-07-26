# ERP Ventas/POS - Salida a operacion controlada

Documento vivo. Ultima actualizacion: 2026-07-25.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host canonico local: `http://panel.com.local/`.

## Actualizacion 2026-07-25

El POS quedo nuevamente en semaforo ampliado verde despues de limpiar copy operativo y actualizar checks read-only de UX/impresion.

Estado:

- `pos_apto_para_piloto_controlado_con_condiciones`.
- Bloqueos tecnicos: `0`.
- Ticket/preview/cobro usan lenguaje operativo; los mensajes internos quedan para modales administrativos o validaciones de supervisor.
- Impresion desde navegador sigue soportada; impresion directa silenciosa por Chrome queda como fase posterior de hardware, no como bloqueo para piloto.

Antes de iniciar un turno real de tienda, confirmar:

- turno abierto desde `Ventas > Caja y turnos`;
- ticket configurado en `Ventas > Configuracion POS > Ticket y negocio`;
- producto con existencia disponible o politica autorizada de inventario pendiente;
- responsables definidos para revisar pendientes de Inventario/Existencias, Catalogo y Caja/Evidencias;
- cierre de turno obligatorio aunque exista diferencia.

## Decision

POS puede iniciar operacion controlada, no produccion abierta.

Operacion controlada significa:

- una sucursal;
- una caja;
- un turno corto;
- usuarios identificados;
- productos con existencia disponible;
- ticket visible;
- kardex y caja trazables;
- cierre con diferencia permitida y reportada.

## Semaforos requeridos

Antes de iniciar:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_operacion_basica_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --usuarios=1,2,3 --compact=1
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_plan_accion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_siguiente_piloto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --monto_inicial=500 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_paquete_autorizacion_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760 --cantidad=1 --precio=295 --monto_inicial=500 --cantidad_fisica=CONTEO_REAL --monto_contado=MONTO_CONTADO_REAL
```

Resultados esperados vigentes:

- `scripts_total=30`.
- Semaforo de arranque local: `uat_ventas_pos_arranque_local_readonly.php`, decision vigente `listo_para_arrancar_al_abrir_turno`.
- `bloqueos_total=0`.
- `pendientes_total=4`.
- `acciones_total=6`.
- `siguiente_piloto`: recomendado si se requiere usar SKU alternativo con stock/precio.
- `pasos_total=6`.

## Permitir en primer uso

- Venta POS normal con stock disponible.
- Busqueda por texto, SKU o scanner POS.
- Pago simple.
- Ticket visible e impresion si Windows/impresora ya estan configurados.
- Cierre manual de turno.
- Diferencia de caja registrada, aunque no sea cero.
- Revision posterior en Reportes POS.

## No permitir en primer uso

- Devoluciones reales.
- Apartados nuevos.
- Inventario pendiente como rutina.
- Descuentos libres sin politica.
- Cambios manuales de precio sin autorizacion.
- Correcciones manuales de caja para cuadrar.
- Mezclar ecommerce como sustituto de POS.
- Vender productos sin configuracion minima de catalogo/precio/inventario.

## Pendientes vigentes

Estos pendientes no indican falla del POS; indican tareas operativas o administrativas antes de ampliar uso:

- `PINV-20260717-000001`: mini inventario pendiente.
- `GASTO-UAT-001`: evidencia de caja pendiente.
- SKU `1760` sin disponible en almacen `5`.
- No hay turno abierto fuera de operacion.

## Ruta humana recomendada

1. Revisar `docs/erp_ventas_pos_primer_turno_piloto_guia_operador.md`.
2. Ejecutar semaforos read-only.
3. Resolver o documentar pendientes.
4. Cargar stock o elegir SKU con existencia disponible.
5. Abrir turno desde `Ventas > Caja y turnos`.
6. Vender desde `Ventas > POS`.
7. Ver/imprimir ticket.
8. Cerrar turno desde `Ventas > Caja y turnos`.
9. Ejecutar postcheck.
10. Revisar Reportes POS.

## Postcheck posterior

Despues del cierre:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_pendientes_piloto_readonly.php --id_almacen=5 --id_sku=1760 --usuarios=1,2,3
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1
```

Debe poder responder:

- quien abrio turno;
- quien cobro;
- que SKU se vendio;
- que folio POS se genero;
- que ticket se emitio;
- que movimiento de caja se registro;
- que kardex se genero;
- cuanto se esperaba en caja;
- cuanto se conto;
- que diferencia quedo;
- que pendientes siguen abiertos.

## Criterio para ampliar piloto

Se puede ampliar a mas horas, usuarios o productos solo si:

- no quedan bloqueos en semaforos;
- el operador puede abrir, vender, cobrar, consultar ticket y cerrar sin ayuda tecnica;
- los reportes muestran ventas, caja y diferencias;
- inventario entiende y resuelve los pendientes que POS genera;
- caja/evidencias tiene responsable administrativo;
- no se esta usando inventario pendiente como forma normal de vender.

## Criterio para detener

Detener el uso y revisar si ocurre cualquiera de estos casos:

- venta confirmada sin ticket;
- venta confirmada sin movimiento de caja cuando el pago debia mover caja;
- venta confirmada sin kardex para producto inventariable con stock disponible;
- turno no puede cerrarse;
- diferencia de caja repetida sin explicacion;
- operador visual incorrecto;
- POS permite vender desde almacen/caja no asignado;
- scanner agrega producto incorrecto sin confirmacion cuando hay multiples coincidencias.
