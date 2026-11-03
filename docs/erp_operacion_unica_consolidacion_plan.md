# ERP - Plan de operación única y consolidación temporal

Fecha de corte: 2026-11-02  
Proyecto: `C:\xampp\htdocs\panel_de_control`  
Host: `http://panel.com.local/`

## Objetivo

Operar temporalmente como un solo negocio y un solo almacén operativo general mientras se regulariza el inventario. Más adelante se podrá dividir nuevamente en Bodega, Mascotas y Acuario mediante traspasos y saldos auditados.

La consolidación es operativa y gradual. No se deben borrar almacenes, ventas, movimientos, pendientes ni reservas históricas.

## Regla de diseño

- Un almacén operativo general para nuevas ventas, compras y recepción durante la etapa de consolidación.
- Los almacenes anteriores se conservan para historial y se desactivan para nueva operación cuando la transición sea autorizada.
- Cada venta conserva su almacén de referencia y cada partida conserva su almacén propio.
- No se suman existencias automáticamente entre almacenes sin conciliación.
- Los reportes deben distinguir la operación consolidada de los datos históricos.

## Auditoría read-only 2026-11-02

Almacenes actuales:

- `id_almacen=4`: Acuario Mina 967, activo, permite venta.
- `id_almacen=5`: Mascotas Mina 971, activo, permite venta.
- `id_almacen=3`: Bodega trasera Mina 971, activo, no permite venta.
- `id_almacen=1` y `2`: inactivos.

Existencias actuales:

- Almacén `3`: 18 registros, cantidad total `109.4500`, disponible `109.4500`.
- Almacén `5`: 4 registros, cantidad total `1019.0000`, disponible `1019.0000`.
- Almacén `4`: no devolvió existencias actuales en la auditoría.

Ventas agrupadas:

- Almacén `4`: 12 ventas históricas, total `2865.00`.
- Almacén `5`: 11 ventas históricas, total `2415.00`.
- Almacén `5`: 30 ventas POS pagadas, total `8850.00`.
- También existen ventas POS canceladas/devueltas y pedidos históricos en el almacén `5`.

Cajas:

- Caja `1`, Acuario: activa, `regularizacion_activa`, alertas activas.
- Caja `2`, Mascotas: activa, `regularizacion_activa`, alertas activas.
- Caja `3`: inactiva, caja UAT histórica.

Turnos:

- 32 turnos cerrados en caja `2` / almacén `5`.
- No se detectó turno abierto en el resumen consultado.

Pendientes de inventario:

- Almacén `4`: 4 pendientes en `pendiente_revision`, cantidad pendiente `5`.
- Almacén `5`: 2 pendientes en `pendiente_revision`, cantidad pendiente `3`.
- Almacén `5`: 5 pendientes cancelados y 3 resueltos, conservados por trazabilidad.

Reservas:

- Solo se encontraron reservas históricas en almacenes `3` y `5`, sin saldo pendiente en el resumen.

## Fases de implementación

### Fase 1 - Auditoría y decisión

- Confirmar si se reutilizará un almacén existente o se creará `Operación general`.
- Recomendar un almacén nuevo si se desea separar claramente el saldo consolidado del historial anterior.
- Revisar pendientes abiertos, reservas, ventas históricas y turnos antes de cualquier cambio.
- No aplicar todavía configuración ni movimientos.

### Fase 2 - Preparación del almacén general

- Crear o designar el almacén operativo único.
- Configurar permisos de recepción, venta, ajustes y consulta.
- Definirlo como almacén predeterminado del POS, compras y recepción.
- Mantener los almacenes anteriores activos solo para consulta durante la transición.

### Fase 3 - Operación piloto sin afectar existencias

- Configurar las cajas operativas en `piloto_sin_inventario`.
- Registrar ventas, pagos, clientes, operadores, tickets y reportes.
- No modificar existencias ni generar kardex.
- No crear alertas de inventario pendiente.
- Sí crear incidencias de venta rápida para Catalogo y solicitudes de clasificación.
- Conservar el almacén general como referencia de la venta.

### Fase 4 - Consolidación de inventario

- Levantar conteos físicos.
- Conciliar cantidades de Bodega y Mascotas.
- Resolver pendientes abiertos antes de sumar saldos.
- Crear entradas o ajustes autorizados en el almacén general, con referencia y evidencia.
- No sumar automáticamente cantidades de almacenes sin revisar duplicados, lotes o unidades abiertas.

### Fase 5 - Regularización activa

- Cambiar el almacén general a operación con inventario.
- Activar alertas de faltantes y mínimos.
- Permitir inventario pendiente únicamente con política autorizada.
- Revisar alertas por almacén general y generar solicitudes de compra cuando corresponda.

### Fase 6 - División futura

- Crear o reactivar Bodega, Mascotas y Acuario.
- Definir productos y cantidades por ubicación.
- Ejecutar traspasos desde el almacén general.
- Activar mínimos, alertas, cajas y permisos por ubicación.
- Comparar reportes consolidados contra reportes por ubicación.

## Riesgos que deben evitarse

- No cambiar directamente `id_almacen` de ventas históricas.
- No copiar existencias de un almacén a otro.
- No activar alertas de inventario mientras la caja esté en modo piloto sin inventario.
- No cancelar ventas históricas usando reversa POS.
- No cerrar pendientes históricos borrándolos; deben conservar su estado y evidencia.
- No desactivar cajas con turnos abiertos.

## Próximo paso seguro

Preparar una auditoría detallada de consolidación por SKU que compare almacenes `3`, `4` y `5`, incluyendo:

- cantidad disponible;
- cantidad apartada;
- unidades abiertas/cerradas;
- pendientes de inventario;
- reservas;
- ventas históricas por partida;
- duplicados o conflictos de SKU.

Esta auditoría será read-only. Después se solicitará autorización separada para configurar el almacén operativo general y, posteriormente, para ajustar saldos.

## Comparación agregada por SKU 2026-11-02

La lectura por SKU confirmó que no es seguro sumar existencias automáticamente:

- Hay existencias actuales en Bodega (`3`) y Mascotas (`5`) para SKUs distintos.
- Acuario (`4`) tiene pendientes de inventario y ventas históricas, pero no filas de existencia actuales en el resumen consultado.
- Existen SKUs con ventas históricas en un almacén y existencia actual en otro; eso debe tratarse como regularización, no como suma automática.
- Hay partidas `VENTA-RAPIDA` sin `id_sku_erp`; deben seguir su flujo de Catalogo antes de poder consolidarse como SKU normal.
- Los pendientes cancelados y resueltos se conservaron en la lectura, pero no deben contarse como pendientes abiertos.

Ejemplos de relaciones que requieren revisión antes de consolidar:

- `TP-40372-25GR` tiene existencia en Bodega y también pendientes históricos en Acuario.
- `TP-40372-500GR` tiene existencia sin saldo disponible en Bodega y Mascotas, además de ventas históricas en Acuario.
- `FOHRS-PAFAKG` tiene ventas históricas y pendientes en Mascotas, pero requiere conservar la trazabilidad de sus partidas rápidas relacionadas.
- Varias ventas históricas rápidas de Acuario y Mascotas aún no tienen SKU definitivo.

Conclusión del corte:

- Primero debe regularizarse el catálogo de ventas rápidas y resolver o clasificar los pendientes abiertos.
- Después se puede preparar una tabla de equivalencias SKU/almacén para el almacén general.
- Todavía no se debe ejecutar una suma masiva de existencias ni cambiar las cajas a una nueva ubicación.

## Auditoría integrada de Compras y Recepción 2026-11-02

Hallazgos del código:

- Las solicitudes de compra ya tienen `id_almacen_destino`.
- Las órdenes de compra ya tienen `id_almacen_destino` y validan que el almacén esté activo y permita recepción antes de enviarse.
- La orden de compra no afecta existencias ni kardex al crearse o editarse.
- Al enviar una orden se prepara una recepción de almacén; el efecto físico queda en el flujo de Recepción/Almacén.
- Las recepciones guardan su propio `id_almacen`, por lo que no debe cambiarse el almacén solo desde Compras después de iniciar una recepción.
- El flujo de compras preoperativas puede generar incidencias por SKU y almacén sin crear existencias oficiales.

Datos actuales encontrados:

- Hay 6 solicitudes en borrador sin almacén destino.
- Hay 2 solicitudes en borrador hacia Bodega y 1 solicitud con orden generada hacia Bodega.
- Hay 7 órdenes en borrador sin almacén destino por un total aproximado de `22931.34`.
- Hay 2 órdenes en borrador hacia Bodega, 1 orden parcial y recepciones parciales/recibidas en Bodega.
- La orden `OC-2026-000020` está parcial hacia Bodega y no debe redirigirse automáticamente.

Decisión técnica:

- Las órdenes antiguas sin almacén destino deben revisarse antes de enviarse.
- Las órdenes ya enviadas, parciales o recibidas conservarán su almacén original.
- Las nuevas solicitudes y órdenes podrán usar `Operación general` como destino predeterminado.
- Las recepciones abiertas deben terminarse en su almacén original o cancelarse formalmente; no se moverán por actualización directa.
- Compras, Recepción y POS se ajustarán en el mismo proyecto, pero cada cambio productivo tendrá su propia validación y auditoría.

Próxima tarea segura:

- Preparar una vista read-only de transición que liste solicitudes, órdenes y recepciones con su almacén actual y marque cuáles pueden migrarse a `Operación general` y cuáles requieren conservarse o revisarse manualmente.

## Avance implementado 2026-11-02: auditoría visible de transición

Se agregó una pantalla de solo lectura en Compras:

- Ruta: `/compra/auditoria_transicion_almacen`.
- Endpoint GET: `/compra/auditoria_transicion_almacen_erp`.
- Acceso: sidebar y búsqueda global bajo `Compras > Transición almacén único`.
- Modelo: `OrdenesCompraErp::auditoriaTransicionAlmacenReadOnly()`.

La pantalla muestra:

- almacenes registrados y sus capacidades de recepción/venta;
- solicitudes, órdenes y recepciones con folio, estatus y destino actual;
- clasificación `Pendiente de destino`, `Revisar antes de continuar` o `Conservar origen`;
- resumen de conteos y motivo de cada clasificación.

Contrato de seguridad de esta fase:

- No ejecuta INSERT, UPDATE, DELETE ni DDL.
- No mueve inventario, no cambia recepciones y no altera turnos o cajas.
- Los registros sin destino solo quedan identificados; no se asigna automáticamente un almacén.
- Los documentos con flujo iniciado conservan su origen para proteger trazabilidad.

Verificaciones ejecutadas:

- `php -l app/modelos/OrdenesCompraErp.php` correcto.
- `php -l app/controladores/Compra.php` correcto.
- `node --check public/assets/js/custom/apps/erp/compras/auditoria_transicion_almacen.js` correcto.

Siguiente autorización requerida:

- Definir formalmente el almacén/operación general que recibirá documentos nuevos.
- Después preparar una configuración controlada para POS, Compras y Recepción; no aplicar todavía una migración masiva de documentos ni de existencias.

## Preflight read-only actualizado 2026-11-02

Se ejecutó `storage/uat/uat_almacen_consolidado_readonly.php` contra la base operativa productiva. La lectura confirma:

- Almacenes activos: Bodega `3`, Acuario `4` y Mascotas `5`.
- Existencias actuales: Bodega `3` y Mascotas `5`; Acuario no tiene filas actuales en el resumen consultado.
- Ventas históricas/operativas: referencias en Acuario `4` y Mascotas `5`.
- Recepciones: referencias en Bodega `3`.
- Movimientos de inventario: referencias en Bodega `3`, Mascotas `5` y registros históricos en `1`.
- Incidencias operativas: referencias en Bodega `3`.

También se corrigió el contador read-only del script para que `filas` sea la suma real de los grupos por almacén. Antes mostraba `0` aunque el desglose sí tuviera filas; la corrección no modifica datos productivos.

Conclusión operativa: todavía no es seguro elegir un almacén existente como “General” por simple renombrado. La decisión debe ser entre una vista consolidada virtual o un nuevo almacén general con corte físico autorizado; ambos caminos deben conservar la trazabilidad histórica.

## Decisión del dueño 2026-11-02: operación centralizada temporal

La operación diaria debe funcionar como un solo negocio. Durante la etapa de regularización:

- POS no debe pedir al operador que elija entre Bodega, Acuario y Mascotas en cada venta.
- Compras nuevas y recepciones nuevas deben dirigirse al almacén operativo central.
- Las alertas de inventario pendiente deben concentrarse en ese almacén central.
- El POS debe tomar el almacén central desde la configuración de la caja/terminal, no desde una selección manual repetitiva.
- Los documentos históricos, ventas anteriores, recepciones existentes y movimientos previos conservarán su almacén original.
- La división futura en Bodega, Mascotas y Acuario se hará después mediante existencias iniciales, traspasos controlados o cortes físicos autorizados; no mediante actualización masiva de IDs históricos.

### Diseño elegido

Se utilizará un almacén operativo general dedicado, preferentemente nuevo, con permisos de recepción, venta, preparación y ajuste según el rol. Esto evita mezclar el saldo central futuro con los saldos físicos históricos de los tres almacenes actuales.

Compatibilidad con el modelo actual:

- Código operativo: `OPERACION-GENERAL`.
- Nombre visible: `Operación General`.
- Tipo técnico: `principal`, porque es el tipo válido que el modelo actual acepta para un almacén central.
- No se agregará un tipo técnico nuevo solo para esta etapa.
- Estatus: `activo`.
- Capacidades iniciales: recepción, venta, preparación y ajustes habilitados; las acciones seguirán protegidas por permisos.

### Pendiente antes de aplicar

1. Confirmar el nombre y código del almacén operativo general.
2. Preparar DDL/semilla o alta controlada del almacén general, con respaldo externo vigente.
3. Configurar cajas y terminales para abrir turnos en ese almacén sin selector operativo obligatorio.
4. Configurar Compras y Recepción para usarlo como destino predeterminado.
5. Ejecutar una prueba UAT read-only/prevalidación y después un ciclo autorizado de recepción, venta, alerta y cierre.

Esta preparación todavía no cambia la base productiva. La autorización fuerte será necesaria al crear o activar el almacén general y al cambiar la configuración productiva de cajas, POS, Compras y Recepción.

### Secuencia técnica de aplicación cuando exista autorización

La aplicación se hará en pasos separados y auditables:

1. Alta del almacén mediante `Almacenes::guardar_almacen_configuracion()` con tipo `principal` y capacidades operativas.
2. Validación read-only de que el almacén quedó activo, vendible y receptor.
3. Crear o reconfigurar una caja central mediante `VentasErp::configuracionCajaGuardarReal()`; no se moverá una caja con dependencias sin una validación específica.
4. Crear o reconfigurar una terminal mediante `VentasErp::configuracionTerminalGuardarReal()`; no se moverá una terminal con turno abierto.
5. Vincular usuarios autorizados mediante el flujo de asignación POS, sin cambiar contraseñas ni roles.
6. Configurar POS, Compras y Recepción para proponer el almacén central en operaciones nuevas.
7. Ejecutar UAT controlada: apertura, venta, alerta o recepción, cierre y consulta de trazabilidad.

No se ejecutará una actualización masiva de `id_almacen` en ventas, compras, recepciones, movimientos, pendientes o reservas históricas.

## Preflight de operación general preparado

Se agregó el diagnóstico read-only:

- `storage/uat/uat_operacion_general_preflight_readonly.php`

El script valida, sin escribir:

- si ya existe el código `OPERACION-GENERAL`;
- columnas necesarias para almacén, cajas, terminales y turnos;
- cajas y terminales configuradas, con su estatus real;
- turnos abiertos en el almacén general si ya existiera;
- solicitudes y órdenes activas sin destino.

No crea ni configura nada. Su resultado será la evidencia previa para una única autorización de alta/configuración productiva.

## Alta aplicada 2026-11-02

Con autorización explícita del dueño se creó el almacén central:

- ID: `6`.
- Código: `OPERACION-GENERAL`.
- Nombre: `Operación General`.
- Tipo: `principal`.
- Estatus: `activo`.
- Recepción, venta, preparación y ajustes: habilitados.
- No se movieron existencias ni se actualizaron ventas, compras, recepciones o movimientos históricos.

Respaldo externo previo:

- Archivo: `C:\xampp\panel_db_backups\productivo_panel_20261102_234926_antes_operacion_general.sql`.
- SHA-256: `A5EE87508CDBA109056FD283DF9CA852F602E982E72F781874D873E6ADBA2A68`.

Postcheck read-only confirmado: el almacén `6` existe y permanece activo. Las cajas y terminales existentes siguen vinculadas a los almacenes `4` y `5`; aún no se han movido. El siguiente cambio requiere autorización separada para configurar la caja/terminal central y los valores predeterminados de POS, Compras y Recepción.

## Dry-run de caja central 2026-11-02

Se validó sin escritura la propuesta para el almacén `6`:

- Código: `CJ-OPERACION-GENERAL-01`.
- Nombre: `Caja central Operacion General`.
- Efectivo, tarjeta y transferencia: habilitados.
- Resultado: `Caja POS valida para alta futura`.
- Bloqueos: ninguno.

La terminal no se puede validar todavía porque el flujo exige que exista primero una caja real a la cual vincularla. Por eso la próxima aplicación debe crear la caja central, ejecutar postcheck y solo después crear la terminal.
