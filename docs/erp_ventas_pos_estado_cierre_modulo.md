# ERP Ventas/POS - Estado de cierre del modulo

Documento vivo. Ultima actualizacion: 2026-07-19.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host canonico local: `http://panel.com.local/`.

## Decision vigente

POS esta listo para piloto controlado con condiciones.

Semaforo consolidado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_salida_operativa_readiness_readonly.php --id_usuario=1 --id_almacen=5 --id_caja=2 --id_terminal=2 --id_sku=1760 --id_atencion=2 --cantidad=1 --usuarios=1,2,3 --compact=1
```

Preflight compacto recomendado antes de iniciar un turno piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_preflight_compacto_readonly.php
```

Este comando resume si se puede iniciar piloto, condiciones, pasos de uso y acciones que deben mantenerse fuera del primer turno.

Guia humana del primer turno:

```text
docs/erp_ventas_pos_primer_turno_piloto_guia_operador.md
```

Postcheck compacto recomendado despues de cerrar el turno piloto:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario=1 --id_almacen=5 --id_sku=1760
```

Este comando confirma si reportes, ticket, trazabilidad, diferencias, evidencias y pendientes quedaron visibles sin mover datos.

Resultado vigente:

- `ok=true`.
- `decision=listo_para_piloto_controlado_con_condiciones`.
- `entorno_canonico_ok=true`.
- `bloqueos=[]`.
- `go_nogo_decision=apto_con_condiciones`.
- `multiusuario_listo=true`.
- Aviso vigente no bloqueante: usuario `3` muestra posible mojibake en nombre visible; corregir desde Seguridad/Usuarios con autorizacion antes de piloto amplio.
- Postcheck vigente: `postcheck_apto_con_observaciones`, con evidencias de caja e inventario pendiente visibles para administracion.

Semaforo de entorno canonico:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_entorno_canonico_readiness_readonly.php
```

Valida que `AGENTS.md`, esta hoja de cierre y los scripts Playwright POS usen `C:\xampp\htdocs\panel_de_control` y `http://panel.com.local/` como referencias vigentes.

## Alcance listo para piloto

- Venta POS normal con turno abierto.
- Caja/turnos con apertura y cierre manual desde UI.
- Cierre de caja permitido con diferencia; la diferencia queda para revision y reportes.
- Multiusuario controlado con usuarios `1,2,3` en almacen `5`, caja `2`, terminal `2`.
- Trazabilidad por operador: cada cobro conserva el usuario que ejecuta la venta.
- Identidad visual de operadores auditada; si un nombre aparece con caracteres raros, POS no debe corregirlo, se corrige en datos maestros de usuario.
- Ticket formal, detalle de venta, garantia snapshot y trazabilidad de inventario/kardex.
- Impresion directa de ticket desde POS, listado de ventas y detalle de venta; impresion de corte desde Caja/Turnos y Reportes POS.
- Scanner POS con camara para agregar productos a la cuenta actual.
- Checador de precios independiente, read-only.
- Reportes piloto de turnos, ventas, diferencias, evidencias y pendientes de inventario.
- Enlaces de navegacion entre POS, Caja/Turnos, Movimientos, Evidencias, Devoluciones, Reportes y Configuracion POS.

## Condiciones antes del primer piloto real

- Abrir turno desde `Ventas > Caja/Turnos`.
- Usar productos con existencia disponible o cargar/recibir inventario con autorizacion.
- Mantener identificado o resolver el pendiente `PINV-20260717-000001`.
- Cerrar o documentar administrativamente la evidencia historica `GASTO-UAT-001`.
- Iniciar con un turno corto, una sucursal y una caja.

## Mantener fuera del primer piloto

- Devoluciones reales.
- Descuentos libres sin politica.
- Apartados nuevos.
- Inventario pendiente productivo como operacion cotidiana.
- Reglas avanzadas de listas de precios sin UAT dedicada.

## Que significa piloto controlado

No significa que el POS este en produccion abierta para toda la empresa.

Significa que ya puede usarse en una prueba real de tienda con alcance limitado, operadores identificados, turno abierto, caja controlada, ticket, kardex y reportes posteriores.

## Siguiente autorizacion fuerte posible

Para resolver el pendiente de mini inventario vigente:

```text
AUTORIZO RESOLVER PENDIENTE INVENTARIO POS UAT REAL usando respaldo UAT POS vigente con token INVENTARIO_POS_PENDIENTE_RESOLVER_REAL id_usuario=1 folio=PINV-20260717-000001 cantidad_fisica=CONTEO_REAL decision=ajustar_a_conteo confirmacion="RESOLVER PENDIENTE" motivo="Resolver mini inventario POS pendiente"
```

Usar `cantidad_fisica` con el conteo real posterior a la venta pendiente.

## Siguiente trabajo sin escritura fuerte

- Revisar UX final de POS con navegador en `http://panel.com.local/ventas/pos`.
- Revisar que el menu muestre claramente `Caja y turnos`, `Reportes POS` y `Configuracion POS`.
- Probar scanner POS solo como UI si no se va a cobrar.
- Probar impresion de ticket/corte con impresora configurada en Windows cuando se instale hardware.
- Ejecutar semaforos read-only despues de cada ajuste visual.
