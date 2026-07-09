# ERP Costos/Rentabilidad - Arranque

Documentacion IA: Codex GPT-5  
Fecha de arranque: 2026-06-23  
Modulo recomendado despues de: `docs/erp_inventario_existencias_arranque.md`

## Objetivo

Crear un modulo ERP nuevo para analizar utilidad real estimada por SKU, canal y escenario comercial, sin mezclarlo con Inventario, Ventas/ecommerce ni pantallas legacy de Costos/Utilidad.

Debe responder:

- Costo real del producto sin impuestos.
- Precio de venta sin impuestos.
- Margen bruto.
- Utilidad estimada despues de gastos operativos porcentuales.
- Precio minimo rentable.
- Escenarios de menudeo, mayoreo y alianzas.
- Productos con margen bajo o riesgo de perdida.
- Relacion read-only con inventario actual y valuacion.
- Relacion read-only con compras, XML y costos historicos.
- Recomendaciones para cerrar precios antes de usar Ventas/Pedidos/Mayoreo.

## Fronteras

- Inventario solo aporta saldos, disponibilidad, costo promedio y valuacion read-only.
- Compras/XML aporta evidencia historica de costo, diferencia de costo y ultimo costo documentado.
- Catalogo aporta SKU, precio general, impuestos y costo de referencia.
- Rentabilidad calcula y recomienda; no mueve inventario, no actualiza precios, no crea listas y no toca Ventas.
- Cualquier persistencia de escenarios, autorizaciones o precios finales requiere respaldo externo y autorizacion explicita.

## Auditoria inicial

| ID | Hallazgo | Resultado |
| --- | --- | --- |
| COST-H001 | `Costo.php` aplica acciones sobre historial de costos y puede actualizar precio por SKU en modelo legacy `Productos`. | Queda como legacy. |
| COST-H002 | `Costos.php` usa `ecom_productos` y calculos fijos `/1.16`. | No apto para ERP nuevo. |
| COST-H003 | `Utilidad.php` mezcla utilidad, proveedores, listas, pedidos, mayoreo y ordenes. | No apto como frontera de rentabilidad. |
| COST-H004 | `Utilidades.php` usa ecommerce y listas viejas para costo/precio. | Queda como referencia historica. |
| COST-H005 | `InventarioErp::valuacionInventario()` ya aporta valuacion bruta read-only. | Insumo valido, no calcula utilidad. |
| COST-H006 | `OrdenesCompraErp::cerrarCostos()` consolida costo de compra a costo referencia. | Insumo valido, no reemplaza rentabilidad. |
| COST-H007 | Catalogo ERP ya guarda precio general, fiscal e impuestos por SKU. | Insumo valido. |

## Tareas iniciales

| ID | Tarea | Alcance | Tipo | Autorizacion |
| --- | --- | --- | --- | --- |
| COST-T001 | Documentar arranque y frontera del modulo | Este documento | Docs | No |
| COST-T002 | Crear modulo read-only de analisis por SKU | `Rentabilidad.php`, `RentabilidadErp.php` | Codigo sin escritura BD | No |
| COST-T003 | Simular escenarios base | Menudeo, mayoreo, alianza | Codigo sin escritura BD | No |
| COST-T004 | Preparar UAT por SKU | Evidencia de costo/precio/margen/riesgo | Read-only | No |
| COST-T005 | Proponer esquema persistente de escenarios y aprobaciones | `RentabilidadEsquema.php` futuro | Diseno | No |
| COST-T006 | Ejecutar esquema o guardar configuraciones | Tablas nuevas o cambios de permisos | Escritura BD | Si |

## Implementacion inicial

Estado: esquema aplicado y primer snapshot persistente guardado.

- Controlador nuevo: `app/controladores/Rentabilidad.php`.
- Modelo nuevo: `app/modelos/RentabilidadErp.php`.
- Vista nueva: `app/vistas/paginas/apps/erp/rentabilidad/analisis.php`.
- JS nuevo: `public/assets/js/custom/apps/erp/rentabilidad/analisis.js`.
- Permisos propios:
  - `rentabilidad.ver`
  - `rentabilidad.snapshot`
  - `rentabilidad.configurar`
- Rutas read-only:
  - `/rentabilidad/analisis`
  - `/rentabilidad/escenarios_erp`
  - `/rentabilidad/analizar_erp`
  - `/rentabilidad/comparar_erp`
  - `/rentabilidad/detalle_sku_erp`
  - `/rentabilidad/recomendaciones_erp`
- UAT tecnico: `storage/uat/uat_rentabilidad_readonly.php`.
- UAT esquema dry-run: `storage/uat/uat_rentabilidad_schema_dryrun.php`.
- UAT esquema aplicado: `storage/uat/uat_rentabilidad_schema_apply_authorized.php`.
- UAT snapshot persistente: `storage/uat/uat_rentabilidad_snapshot_persistente.php`.
- UAT candado de escritura snapshot read-only: `storage/uat/uat_rentabilidad_snapshot_guard_write_gate_readonly.php`.
- UAT listado de snapshots: `storage/uat/uat_rentabilidad_snapshots_listar.php`.
- UAT vigencia snapshots: `storage/uat/uat_rentabilidad_snapshots_vigencia.php`.
- UAT auditoria escenarios comerciales read-only: `storage/uat/uat_rentabilidad_escenarios_auditoria_readonly.php`.
- UAT consistencia costos presentaciones: `storage/uat/uat_rentabilidad_presentaciones_costos_readonly.php`.
- UAT cierre comercial read-only: `storage/uat/uat_rentabilidad_cierre_comercial_readonly.php`.
- UAT datos base read-only: `storage/uat/uat_rentabilidad_datos_base_readonly.php`.
- UAT fiscal/XML read-only: `storage/uat/uat_rentabilidad_fiscal_xml_readonly.php`.
- UAT preflight fiscal read-only: `storage/uat/uat_rentabilidad_fiscal_preflight_readonly.php`.
- UAT matriz de escenarios read-only: `storage/uat/uat_rentabilidad_matriz_escenarios_readonly.php`.
- UAT canales recomendados read-only: `storage/uat/uat_rentabilidad_canales_recomendados_readonly.php`.
- UAT plan de cierre read-only: `storage/uat/uat_rentabilidad_plan_cierre_readonly.php`.
- UAT impacto de cierre read-only: `storage/uat/uat_rentabilidad_impacto_cierre_readonly.php`.
- UAT hallazgos de cierre read-only: `storage/uat/uat_rentabilidad_hallazgos_cierre_readonly.php`.
- UAT prioridades de cierre read-only: `storage/uat/uat_rentabilidad_prioridades_cierre_readonly.php`.
- UAT responsables de cierre read-only: `storage/uat/uat_rentabilidad_responsables_cierre_readonly.php`.
- UAT checklist de cierre read-only: `storage/uat/uat_rentabilidad_checklist_cierre_readonly.php`.
- UAT autorizaciones de cierre read-only: `storage/uat/uat_rentabilidad_autorizaciones_cierre_readonly.php`.
- UAT precios objetivo read-only: `storage/uat/uat_rentabilidad_precios_objetivo_readonly.php`.
- UAT preflight de aprobacion de precios read-only: `storage/uat/uat_rentabilidad_precios_aprobacion_preflight_readonly.php`.
- UAT preflight de aprobaciones internas read-only: `storage/uat/uat_rentabilidad_aprobaciones_preflight_readonly.php`.
- UAT candados de escritura de aprobaciones internas read-only: `storage/uat/uat_rentabilidad_aprobaciones_write_gate_readonly.php`.
- UAT listado de aprobaciones internas read-only: `storage/uat/uat_rentabilidad_aprobaciones_listar_readonly.php`.
- UAT paquete de autorizacion de aprobaciones read-only: `storage/uat/uat_rentabilidad_aprobaciones_paquete_autorizacion_readonly.php`.
- UAT ciclo de vida de aprobacion interna: `storage/uat/uat_rentabilidad_aprobacion_interna_lifecycle.php`.
- UAT sensibilidad read-only: `storage/uat/uat_rentabilidad_sensibilidad_readonly.php`.
- UAT tablero ejecutivo read-only: `storage/uat/uat_rentabilidad_tablero_ejecutivo_readonly.php`.
- UAT revision operativa read-only: `storage/uat/uat_rentabilidad_revision_operativa_readonly.php`.
- UAT workflow comercial read-only: `storage/uat/uat_rentabilidad_workflow_comercial_readonly.php`.
- UAT estado del modulo read-only: `storage/uat/uat_rentabilidad_estado_modulo_readonly.php`.
- UAT preflight de uso comercial read-only: `storage/uat/uat_rentabilidad_preflight_uso_comercial_readonly.php`.
- UAT plan de desbloqueo read-only: `storage/uat/uat_rentabilidad_plan_desbloqueo_readonly.php`.
- UAT auditoria final read-only: `storage/uat/uat_rentabilidad_auditoria_final_readonly.php`.
- UAT maestro read-only de aceptacion tecnica: `storage/uat/uat_rentabilidad_suite_readonly.php`.
- UAT dry-run de esquema aprobaciones comerciales: `storage/uat/uat_rentabilidad_aprobaciones_schema_dryrun.php`.
- UAT aplicador protegido de esquema aprobaciones comerciales: `storage/uat/uat_rentabilidad_aprobaciones_schema_apply_authorized.php`.
- UAT preflight de autorizacion para esquema aprobaciones: `storage/uat/uat_rentabilidad_aprobaciones_autorizacion_preflight_readonly.php`.
- UAT suite read-only de autorizacion de aprobaciones: `storage/uat/uat_rentabilidad_aprobaciones_autorizacion_suite_readonly.php`.
- UAT runbook read-only de autorizacion de aprobaciones: `storage/uat/uat_rentabilidad_aprobaciones_runbook_readonly.php`.
- UAT preflight read-only de respaldo externo para aprobaciones: `storage/uat/uat_rentabilidad_aprobaciones_respaldo_preflight_readonly.php`.
- UAT post-esquema read-only para aprobaciones comerciales: `storage/uat/uat_rentabilidad_aprobaciones_post_schema_readonly.php`.
- UAT ficha evidencia SKU read-only: `storage/uat/uat_rentabilidad_detalle_sku_readonly.php`.
- UAT semaforo cierre read-only: `storage/uat/uat_rentabilidad_semaforo_cierre_readonly.php`.
- UAT variacion de costos read-only: `storage/uat/uat_rentabilidad_variaciones_costos_readonly.php`.
- UAT preflight de recomendaciones read-only: `storage/uat/uat_rentabilidad_recomendaciones_preflight_readonly.php`.
- UAT candado de escritura recomendaciones read-only: `storage/uat/uat_rentabilidad_recomendaciones_guard_write_gate_readonly.php`.
- UAT candado de resolucion de recomendaciones read-only: `storage/uat/uat_rentabilidad_recomendacion_resolver_write_gate_readonly.php`.
- Acceso en sidebar: Rentabilidad > Analisis comercial, visible con `rentabilidad.ver`.
- Auditoria de esquema read-only: `/rentabilidad/esquema_auditar_erp`, requiere `sistema.soporte`.
- Guardado de snapshot: `/rentabilidad/snapshot_guardar_erp`, requiere `rentabilidad.snapshot` y CSRF.
- Listado de snapshots: `/rentabilidad/snapshots_erp`, requiere `rentabilidad.ver`.
- Auditoria read-only de vigencia de snapshots: `/rentabilidad/snapshots_vigencia_erp`, requiere `rentabilidad.ver`.
- Auditoria read-only de escenarios comerciales: `/rentabilidad/escenarios_auditar_erp`, requiere `rentabilidad.ver`.
- Auditoria read-only de costos de presentaciones: `/rentabilidad/costos_presentaciones_auditar_erp`, requiere `rentabilidad.ver`.
- Auditoria read-only de cierre comercial: `/rentabilidad/cierre_precios_auditar_erp`, requiere `rentabilidad.ver`.
- Auditoria read-only de datos base para cierre: `/rentabilidad/datos_base_auditar_erp`, requiere `rentabilidad.ver`.
- Auditoria read-only de evidencia fiscal XML: `/rentabilidad/fiscal_xml_auditar_erp`, requiere `rentabilidad.ver`.
- Preflight fiscal read-only: `/rentabilidad/fiscal_preflight_erp`, requiere `rentabilidad.ver`.
- Matriz read-only de escenarios por muestra: `/rentabilidad/matriz_escenarios_erp`, requiere `rentabilidad.ver`.
- Recomendacion read-only de canal por SKU: `/rentabilidad/canales_recomendados_erp`, requiere `rentabilidad.ver`.
- Plan read-only de cierre comercial: `/rentabilidad/plan_cierre_erp`, requiere `rentabilidad.ver`.
- Impacto read-only de cierre comercial: `/rentabilidad/impacto_cierre_erp`, requiere `rentabilidad.ver`.
- Hallazgos read-only de cierre comercial: `/rentabilidad/hallazgos_cierre_erp`, requiere `rentabilidad.ver`.
- Prioridades read-only de cierre comercial: `/rentabilidad/prioridades_cierre_erp`, requiere `rentabilidad.ver`.
- Responsables read-only de cierre comercial: `/rentabilidad/responsables_cierre_erp`, requiere `rentabilidad.ver`.
- Checklist read-only de cierre comercial: `/rentabilidad/checklist_cierre_erp`, requiere `rentabilidad.ver`.
- Paquete read-only de autorizaciones de cierre: `/rentabilidad/autorizaciones_cierre_erp`, requiere `rentabilidad.ver`.
- Simulador read-only de precios objetivo: `/rentabilidad/precios_objetivo_erp`, requiere `rentabilidad.ver`.
- Preflight read-only de aprobacion de precios: `/rentabilidad/precios_aprobacion_preflight_erp`, requiere `rentabilidad.ver`.
- Preflight read-only de aprobaciones comerciales internas: `/rentabilidad/aprobaciones_internas_preflight_erp`, requiere `rentabilidad.ver`.
- Listado read-only de aprobaciones comerciales internas: `/rentabilidad/aprobaciones_internas_listar_erp`, requiere `rentabilidad.ver`.
- Paquete read-only de autorizacion de aprobaciones comerciales internas: `/rentabilidad/aprobaciones_autorizacion_paquete_erp`, requiere `rentabilidad.ver`.
- Guardado de aprobacion comercial interna: `/rentabilidad/aprobacion_interna_guardar_erp`, requiere `rentabilidad.snapshot` y CSRF.
- Resolucion de aprobacion comercial interna: `/rentabilidad/aprobacion_interna_resolver_erp`, requiere `rentabilidad.snapshot` y CSRF.
- Simulador read-only de sensibilidad: `/rentabilidad/sensibilidad_erp`, requiere `rentabilidad.ver`.
- Tablero ejecutivo read-only: `/rentabilidad/tablero_ejecutivo_erp`, requiere `rentabilidad.ver`.
- Modo revision read-only: `/rentabilidad/revision_operativa_erp`, requiere `rentabilidad.ver`.
- Workflow comercial read-only: `/rentabilidad/workflow_comercial_erp`, requiere `rentabilidad.ver`.
- Estado read-only de madurez del modulo: `/rentabilidad/estado_modulo_erp`, requiere `rentabilidad.ver`.
- Preflight read-only de uso comercial: `/rentabilidad/preflight_uso_comercial_erp`, requiere `rentabilidad.ver`.
- Plan read-only de desbloqueo comercial: `/rentabilidad/plan_desbloqueo_erp`, requiere `rentabilidad.ver`.
- Auditoria final read-only de construccion y uso comercial: `/rentabilidad/auditoria_final_erp`, requiere `rentabilidad.ver`.
- Ficha read-only de evidencia por SKU: `/rentabilidad/detalle_sku_erp`, requiere `rentabilidad.ver`.
- Semaforo read-only de cierre por SKU: `/rentabilidad/semaforo_cierre_erp`, requiere `rentabilidad.ver`.
- Auditoria read-only de variacion de costos: `/rentabilidad/variaciones_costos_erp`, requiere `rentabilidad.ver`.
- Preflight read-only de recomendaciones persistentes: `/rentabilidad/recomendaciones_preflight_erp`, requiere `rentabilidad.ver`.
- Guardado de recomendaciones persistentes: `/rentabilidad/recomendaciones_guardar_erp`, requiere `rentabilidad.snapshot` y CSRF.
- Listado de recomendaciones persistentes: `/rentabilidad/recomendaciones_listar_erp`, requiere `rentabilidad.ver`.
- Resolucion de recomendaciones: `/rentabilidad/recomendacion_resolver_erp`, requiere `rentabilidad.snapshot` y CSRF.
- Auditoria dry-run de esquema para aprobaciones comerciales: `/rentabilidad/esquema_aprobaciones_auditar_erp`, requiere `rentabilidad.configurar`.

## Reglas de calculo inicial

- Costo preferido: costo promedio de inventario si existe stock valuado.
- Si el SKU tiene `factor_unidad_base > 1`, Rentabilidad evalua el costo comercial como `costo_promedio_unitario_inventario * factor_unidad_base`.
- Costo alterno: `erp_catalogo_skus.costo_referencia`.
- Precio base: precio general activo de `erp_catalogo_sku_precios`.
- Precio sin impuestos: si el SKU marca `incluye_impuestos=1`, se divide entre `1 + iva + ieps`.
- Margen bruto: `(precio_escenario_sin_impuestos - costo_real_sin_impuesto) / precio_escenario_sin_impuestos`.
- Utilidad estimada: margen bruto en importe menos gasto operativo y comision porcentual sobre precio.
- Precio minimo rentable: `costo / (1 - gasto_pct - comision_pct - margen_objetivo_pct)`.

## UAT propuesto

| ID | Caso | Evidencia |
| --- | --- | --- |
| UAT-COST-001 | SKU con precio, impuesto y costo promedio de inventario | Debe mostrar costo de origen `inventario_promedio`. |
| UAT-COST-002 | SKU sin stock pero con costo referencia | Debe usar `catalogo_referencia`. |
| UAT-COST-003 | SKU sin precio | Debe quedar en riesgo `Datos incompletos`. |
| UAT-COST-004 | SKU con descuento de mayoreo que deja utilidad negativa | Debe quedar en `Riesgo de perdida`. |
| UAT-COST-005 | SKU con fiscal incompleto | Debe mostrar hallazgo `fiscal_incompleto`. |

## Pendientes operativos

- El panel `Estado del modulo` resume la madurez de Rentabilidad en 5 componentes: escenarios comerciales, workflow comercial, aprobacion de precios, snapshots y paquete de autorizacion.
- Al 2026-06-23 el estado general sigue `bloqueado`: los escenarios estan listos, pero la aprobacion de precios y el paquete de autorizacion no deben cerrarse hasta completar datos/fiscal y preparar respaldo externo.
- El panel `Preflight uso comercial` es la barrera read-only antes de Ventas/Pedidos/Mayoreo/Alianzas: diagnostica preparacion por destino, pero no toca esos modulos ni publica precios.
- El panel `Plan de desbloqueo` ordena acciones por prioridad y responsable sugerido; actualmente la primera ruta critica es Catalogo/Fiscal por fiscal incompleto.
- El panel `Auditoria final` separa construccion del modulo y readiness comercial: actualmente la construccion queda `completo_readonly`, pero el uso comercial sigue `bloqueado`.
- Validar visualmente `/rentabilidad/analisis` con usuario que tenga `rentabilidad.ver`.
- Definir politica final de aprobacion: una recomendacion aprobada debe aplicar precio, crear tarea para Catalogo o quedar solo como evidencia directiva.
- Crear un snapshot vigente posterior a las correcciones de costos, si se decide conservar una foto limpia para cierre comercial.
- Completar datos fiscales faltantes antes de usar el analisis como base para publicar precios.

## Aprobacion comercial persistente interna

Estado: disenada en dry-run, no aplicada a BD.

Tablas propuestas:

- `erp_rentabilidad_aprobaciones_comerciales`: conserva folio, SKU, canal, costo, precio actual, minimo rentable, precio aprobado, margen, utilidad, escenario, dictamen, evidencia, bloqueos, alertas, estatus, comentario, respaldo externo y usuarios/fechas de aprobacion.
- `erp_rentabilidad_aprobaciones_bitacora`: registra crear/aprobar/rechazar/cancelar/marcar obsoleta/marcar revision con estatus anterior/nuevo, comentario, evidencia antes/despues, respaldo externo y usuario.

Reglas:

- Es evidencia interna de Rentabilidad; no aplica precios a Catalogo.
- No toca Ventas, ecommerce, Pedidos ni Mayoreo.
- No toca Inventario.
- La ejecucion del esquema requiere respaldo externo y autorizacion explicita.
- El primer paso construido es solo auditoria dry-run: `/rentabilidad/esquema_aprobaciones_auditar_erp`.
- El preflight funcional construido es read-only: `/rentabilidad/aprobaciones_internas_preflight_erp`; muestra candidatos, bloqueos y evidencia congelable sin crear aprobaciones.
- El paquete de autorizacion construido es read-only: `/rentabilidad/aprobaciones_autorizacion_paquete_erp`; consolida esquema pendiente, preflight, estado del modulo, auditoria final, acciones, frases y comandos sin ejecutar DDL.
- Los endpoints de guardado/resolucion ya tienen candados: requieren frase exacta, respaldo externo y esquema aplicado antes de escribir.
- El aplicador protegido del esquema queda en `storage/uat/uat_rentabilidad_aprobaciones_schema_apply_authorized.php`; por defecto corre dry-run y solo ejecuta con `--execute`, `--respaldo=...` y `--confirmar="AUTORIZO APLICAR ESQUEMA APROBACIONES INTERNAS"`.
- El UAT de ciclo de vida queda preparado en `storage/uat/uat_rentabilidad_aprobacion_interna_lifecycle.php`; por defecto solo hace preflight/listado y despues del esquema puede crear/resolver con flags explicitos.
- El preflight de autorizacion queda preparado en `storage/uat/uat_rentabilidad_aprobaciones_autorizacion_preflight_readonly.php`; consolida esquema pendiente, candados, comando requerido y validaciones posteriores sin ejecutar DDL.
- La suite de autorizacion queda preparada en `storage/uat/uat_rentabilidad_aprobaciones_autorizacion_suite_readonly.php`; encadena dry-run, paquete, preflight, listado y candados para decidir si ya se puede solicitar autorizacion.
- El runbook de autorizacion queda preparado en `storage/uat/uat_rentabilidad_aprobaciones_runbook_readonly.php`; ordena precheck, respaldo, validacion de respaldo, aplicacion, post-schema, lifecycle, suite general y criterio de rollback sin ejecutar comandos.
- El preflight de respaldo queda preparado en `storage/uat/uat_rentabilidad_aprobaciones_respaldo_preflight_readonly.php`; valida referencia minima y, si es ruta local, existencia, lectura y tamano mayor a cero.
- El UAT post-esquema queda preparado en `storage/uat/uat_rentabilidad_aprobaciones_post_schema_readonly.php`; hoy debe reportar `pendiente_autorizacion_esquema` y despues de aplicar tablas debe reportar `schema_aplicado_validado_readonly`.

Respaldo externo previo preparado:

- `C:\xampp\panel_db_backups\artianilocal_panel_20260624_antes_rentabilidad_aprobaciones_schema.sql`
- Tamano validado por preflight: `27126519` bytes.
- Estado: respaldo existente, legible y apto como referencia previa; aun no se aplica esquema.

## Validacion tecnica

Fecha: 2026-06-23.

| Verificacion | Resultado |
| --- | --- |
| `C:\xampp\php\php.exe -l app\controladores\Rentabilidad.php` | OK |
| `C:\xampp\php\php.exe -l app\modelos\RentabilidadErp.php` | OK |
| `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\rentabilidad\analisis.php` | OK |
| `node --check public\assets\js\custom\apps\erp\rentabilidad\analisis.js` | OK |
| `C:\xampp\php\php.exe -l storage\uat\uat_rentabilidad_readonly.php` | OK |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_readonly.php` | Posterior a correccion `TP-40372`: `ok=true`, `skus=300`, `perdida=5`, `margen_bajo=3`, `sin_costo=130`, `sin_precio=3`, `valor_inventario=7782.58`, `primer_sku=TP-40372` |
| `C:\xampp\php\php.exe -l app\modelos\RentabilidadEsquema.php` | OK |
| `C:\xampp\php\php.exe -l storage\uat\uat_rentabilidad_schema_dryrun.php` | OK |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_schema_dryrun.php` | `ok=true`, `total=4`, `existentes=0`, `pendientes=4`, `errores=0` |
| Respaldo externo previo | `C:\xampp\panel_db_backups\artianilocal_panel_20260623_antes_rentabilidad_schema.sql` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_schema_apply_authorized.php` | `ok=true`, esquema aplicado |
| Dry-run posterior | `ok=true`, `total=4`, `existentes=4`, `pendientes=0`, `errores=0` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_snapshot_persistente.php` historico autorizado | `ok=true`, snapshot `RENT-20260622-221611`, `items=5`, `perdida=1`, `valor_inventario=14743.6828` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_snapshot_persistente.php` actual sin `--execute` | `ok=true`, `modo=preflight`; no siembra escenarios ni guarda snapshot |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_snapshot_guard_write_gate_readonly.php` | `ok=true`; rechaza guardado sin frase de autorizacion y rechaza guardado sin referencia de respaldo externo |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_snapshots_listar.php` | `ok=true`, `total=1`, primer folio `RENT-20260622-221611` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_snapshots_vigencia.php` | `ok=true`, `total=1`, `desfasados=1`; snapshot `RENT-20260622-221611` difiere de calculo actual por correccion de presentaciones |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_escenarios_auditoria_readonly.php` | `ok=true`; `3` escenarios persistidos, `3` activos, `0` faltantes, `0` diferencias contra defaults |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_presentaciones_costos_readonly.php` | Posterior a correcciones: `ok=true`, `total=8`, `alertas=0`; `TP-40372` y `TP-40352` consistentes |
| Respaldo externo previo permisos | `C:\xampp\panel_db_backups\artianilocal_panel_20260623_antes_rentabilidad_permisos.sql` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_permisos_aplicar.php` | `ok=true`, permisos `rentabilidad.configurar`, `rentabilidad.snapshot`, `rentabilidad.ver` |
| Respaldo externo previo recomendaciones | `C:\xampp\panel_db_backups\artianilocal_panel_20260623_antes_rentabilidad_recomendaciones.sql` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_recomendaciones_persistentes.php` historico autorizado | Primera corrida: `ok=true`, `candidatos=1`, `creadas=1`, pendiente `TP-40372-100GR` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_recomendaciones_persistentes.php` historico autorizado | Segunda corrida: `ok=true`, `creadas=0`, `omitidas_pendientes=1`; no duplica pendiente |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_recomendaciones_persistentes.php` actual sin `--execute` | `ok=true`, `modo=preflight`; no escribe BD; `TP-40372` queda con `0` candidatos despues de correcciones |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_recomendaciones_preflight_readonly.php` | `ok=true`; general `120` evaluados, `7` candidatos, `7` creables, `0` omitidos, delta `4981.701754`; `TP-40372` y `TP-40352` sin candidatos |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_recomendaciones_guard_write_gate_readonly.php` | `ok=true`; rechaza guardado sin frase de autorizacion y rechaza guardado sin referencia de respaldo externo |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_recomendacion_resolver_write_gate_readonly.php` | `ok=true`; rechaza resolver recomendacion sin frase de autorizacion y rechaza resolver sin referencia de respaldo externo |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_estado_modulo_readonly.php` | `ok=true`; general `bloqueado` con `5` componentes, `1` listo, `1` requiere autorizacion, `2` bloqueados, `1` advertencia; `TP-40372` tambien bloqueado por aprobacion/autorizacion |
| Respaldo externo previo correccion `TP-40372` | `C:\xampp\panel_db_backups\artianilocal_panel_20260623_antes_tp40372_costos_presentaciones.sql` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_tp40372_costos_reparar.php --execute` | Corrige costo por factor: costal `737.068966`, kg `184.267242`, 500g `92.133621`, 100g `18.426724`, 50g `9.213362`, 25g `4.606681` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_recomendaciones_obsoletas_readonly.php` | Auditoria read-only de recomendaciones pendientes obsoletas despues de cambios de costo |
| Respaldo externo previo cancelar recomendacion obsoleta | `C:\xampp\panel_db_backups\artianilocal_panel_20260623_antes_cancelar_recomendacion_tp40372_100gr.sql` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_recomendaciones_obsoletas_cancelar.php --execute` | `ok=true`, `obsoletas_detectadas=1`, `actualizadas=1`, cancela recomendacion obsoleta `TP-40372-100GR` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_recomendaciones_obsoletas_readonly.php` posterior | `ok=true`, `total_pendientes=0`, sin obsoletas ni vigentes |
| Respaldo externo previo correccion `TP-40352` | `C:\xampp\panel_db_backups\artianilocal_panel_20260623_antes_tp40352_costo_base.sql` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_tp40352_costo_base_reparar.php --execute` | Corrige costo base `TP-40352` a `760` desde presentacion 500 g: `95 / 0.5 * 4` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_tp40352_costos_readonly.php` posterior | `ok=true`; `TP-40352` costo `760`, `TP-40352-500GR` costo `95`, auditoria presentaciones `alertas=0` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_cierre_comercial_readonly.php` | `ok=true`; general `bloqueado`, `bloqueos_duros=138`, presentaciones `0`; `TP-40372` y `TP-40352` en `precaucion` solo por fiscal/snapshot aplicable |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_datos_base_readonly.php` | `ok=true`; muestra 120: costo `47`, precio `2`, fiscal `120`, margen `0`; `TP-40372` y `TP-40352` solo fiscal |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_fiscal_xml_readonly.php` | `ok=true`; muestra 120 fiscal incompleto sin sugerencia XML vinculada; `TP-40372` y `TP-40352` sin XML fiscal util |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_fiscal_preflight_readonly.php` | `ok=true`; general `120` evaluados, `0` XML aplicable, `0` captura parcial, `120` sin evidencia; `TP-40372` y `TP-40352` sin evidencia fiscal XML |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_matriz_escenarios_readonly.php` | `ok=true`; muestra 120: menudeo/mayoreo/alianza con `68` rentables, `48` bloqueados, `4` perdida por canal; `TP-40372` y `TP-40352` rentables en los 3 canales |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_canales_recomendados_readonly.php` | `ok=true`; muestra `120`: `68` listos, `52` bloqueados, canal recomendado `menudeo` para los `68`; `TP-40372` y `TP-40352` recomiendan `menudeo` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_plan_cierre_readonly.php` | `ok=true`; muestra `120`: `59` completar fiscal, `48` completar datos, `8` revisar precio, `5` validar costo, `0` listos; `TP-40372` y `TP-40352` quedan solo en completar fiscal |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_impacto_cierre_readonly.php` | `ok=true`; muestra `120`: utilidad confiable `20290.3982`, utilidad no confiable `12534.46`, utilidad negativa `-1688.18`, deficit precio `5232`, inventario `7782.58`; `TP-40372` utilidad `1229.550817` bloqueada por fiscal |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_hallazgos_cierre_readonly.php` | `ok=true`; muestra `120` evaluados, `185` hallazgos, `120` SKUs con hallazgos; `COST-H103` fiscal incompleto afecta los `120`; `COST-H101` afecta `47`; `COST-H107` afecta `8` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_prioridades_cierre_readonly.php` | `ok=true`; muestra `120` prioridades: `6` alta, `57` media, `57` baja; primer SKU `1330` en revisar precio, score `2853.36`; `TP-40372` prioridad media por fiscal, `TP-40352` baja por fiscal |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_responsables_cierre_readonly.php` | `ok=true`; muestra `4` responsables y `120` prioridades: Direccion/Comercial `8`, Catalogo `48`, Compras/Almacen `5`, Catalogo/Fiscal `59`; `TP-40372` y `TP-40352` quedan en Catalogo/Fiscal |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_checklist_cierre_readonly.php` | `ok=true`; muestra general `6` checks: `1` OK, `4` bloqueados, `1` informativo, `120` SKUs bloqueados, `0` listos; `TP-40372` y `TP-40352` solo bloqueados por fiscal |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_autorizaciones_cierre_readonly.php` | `ok=true`; paquete general `5` acciones: `3` bloqueadas, `2` requieren respaldo, `0` listas; `TP-40372` y `TP-40352` mantienen aplicar precios bloqueado y validar costo listo |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_precios_objetivo_readonly.php` | `ok=true`; muestra 120: `18` requieren subir precio, `45` sin costo, `3` sin precio, `54` viables; `TP-40372` y `TP-40352` viables sin delta |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_precios_aprobacion_preflight_readonly.php` | `ok=true`; general `120` evaluados, `0` aprobables, `0` en revision, `120` bloqueados por politica conservadora; `TP-40372` y `TP-40352` bloqueados por fiscal |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_preflight_readonly.php` | `ok=true`; general `120` evaluados, `0` creables, `120` bloqueados, `120` con evidencia congelable, schema pendiente; `TP-40372` `5` evaluados, `0` creables, `5` bloqueados |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_write_gate_readonly.php` | `ok=true`; crear/resolver rechazan sin frase; crear rechaza sin respaldo; con frase y respaldo rechaza por esquema de aprobaciones pendiente; no escribe BD |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_listar_readonly.php` | `ok=true`; listado responde schema pendiente sin error, `schema_disponible=0`, `total=0`, sin escribir BD |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_paquete_autorizacion_readonly.php` | `ok=true`; paquete read-only para `TP-40372`, estado `requiere_autorizacion_esquema`, schema pendiente, frases y acciones visibles sin escribir BD |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobacion_interna_lifecycle.php` | `ok=true`; modo preflight para `TP-40372`, `5` evaluados, `0` creables, `5` bloqueados, `schema_disponible=0`, listado antes/despues en `0` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_sensibilidad_readonly.php` | `ok=true`; shock base costo `+5%`, precio `-5%`; muestra `120`: `48` incompletos, `4` vulnerables, `68` resisten; `TP-40372` y `TP-40352` resisten |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_tablero_ejecutivo_readonly.php` | `ok=true`; general utilidad estimada `75354.5682`, utilidad negativa `-2000.13`; `TP-40372` utilidad `1229.5508`, `TP-40352` utilidad `1272.9` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_revision_operativa_readonly.php` | `ok=true`; general `5` perdidas, `21` subir precio, `130` completar costo, `3` completar precio, `9` oportunidad con stock; `TP-40372` oportunidad con stock `3`, `TP-40352` sin bloqueos economicos |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_workflow_comercial_readonly.php` | `ok=true`; general `7` candidatos creables, `0` pendientes, `0` aprobables, `120` bloqueados para aprobacion, `120` prioridades; crear pendientes requiere autorizacion |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_preflight_uso_comercial_readonly.php` | `ok=true`; general `bloqueado`: `6` destinos, `0` listos, `1` requiere autorizacion, `3` bloqueados, `2` sin casos; `TP-40372` bloqueado para catalogo/precio, menudeo y fiscal aunque sea rentable |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_plan_desbloqueo_readonly.php` | `ok=true`; general `9` acciones, `4` alta, `4` media, `1` baja, `4` responsables; primera accion `UNLOCK-USO-CATALOGO_FISCAL` con `120` casos; `TP-40372` tiene `5` acciones y primera ruta fiscal con `5` casos |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_auditoria_final_readonly.php` | `ok=true`; general y `TP-40372` con construccion `completo_readonly`, uso comercial `bloqueado`, `6` criterios, `3` OK, `0` bloqueos tecnicos, `3` bloqueos operativos |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_suite_readonly.php` | `ok=true`; valida 12 rutas logicas clave; `120` SKUs analizados, `4` perdida, `TP-40372` costo `737.0688`, presentaciones `0` alertas, aprobables `0`, aprobaciones internas creables `0`, schema aprobaciones `0`, paquete autorizacion `requiere_autorizacion_esquema`, construccion `completo_readonly`, uso comercial `bloqueado` |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_schema_dryrun.php` | `ok=true`; dry-run sin ejecutar; propone `2` tablas de aprobaciones comerciales, `0` existentes, `2` pendientes, `0` ejecutadas, `0` errores |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_schema_apply_authorized.php` | `ok=true`; modo dry-run, `2` tablas pendientes, `0` ejecutadas |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_schema_apply_authorized.php --execute` | `ok=false`; bloquea ejecucion por falta de respaldo/frase exacta, `0` ejecutadas |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_schema_apply_authorized.php --execute --respaldo=uat-readonly-respaldo` | `ok=false`; bloquea ejecucion por falta de frase exacta, `0` ejecutadas |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_autorizacion_preflight_readonly.php` | `ok=true`; consolida autorizacion pendiente: `2` tablas pendientes, `0` existentes, `0` aprobaciones creables actuales, `5` bloqueadas para `TP-40372`, candados activos y comando requerido |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_autorizacion_suite_readonly.php` | `ok=true`; estado `listo_para_solicitar_autorizacion`, valida dry-run, paquete, preflight, listado y candados sin ejecutar DDL ni crear aprobaciones |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_runbook_readonly.php` | `ok=true`; runbook de 7 pasos con precheck, respaldo, validacion de respaldo, comando autorizado, post-schema, lifecycle, suite general y criterio de rollback; no ejecuta comandos ni escribe BD |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_respaldo_preflight_readonly.php` | `ok=false`; sin referencia de respaldo, no escribe BD ni crea archivos |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_respaldo_preflight_readonly.php --respaldo="C:\xampp\panel_db_backups\artianilocal_panel_20260624_antes_rentabilidad_aprobaciones_schema.sql"` | `ok=true`; respaldo existe, es legible y mide `27126519` bytes |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_aprobaciones_post_schema_readonly.php` | `ok=true`; estado actual `pendiente_autorizacion_esquema`, schema pendiente, listado sin schema, candado de escritura activo sin frase; no ejecuta DDL ni crea aprobaciones |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_detalle_sku_readonly.php` | `ok=true`; ficha exacta por SKU con 3 escenarios, datos base, fiscal/XML, presentaciones, snapshots y dictamen de cierre; `TP-40372` rentable costo `737.0688` pero dictamen `bloqueado` por aprobacion/fiscal, `TP-40352` rentable costo `760` tambien bloqueado por aprobacion/fiscal, `0080` incompleto |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_semaforo_cierre_readonly.php` | `ok=true`; muestra general `120` evaluados, `56` bloqueados, `64` precaucion; `TP-40372` y `TP-40352` sin bloqueos pero en precaucion por fiscal; accion `subir_precio` devuelve `9` bloqueados |
| `C:\xampp\php\php.exe storage\uat\uat_rentabilidad_variaciones_costos_readonly.php` | `ok=true`; muestra `120` evaluados, `71` con evidencia historica, `49` sin evidencia, `5` alertas por variacion mayor a `15%`; `TP-40372` alineado, `TP-40352` sin evidencia historica enlazada |

## Permisos y roles

| Permiso | Uso |
| --- | --- |
| `rentabilidad.ver` | Consultar analisis, comparaciones, recomendaciones read-only y snapshots. |
| `rentabilidad.snapshot` | Guardar snapshots de analisis. |
| `rentabilidad.configurar` | Auditar esquema/configuracion tecnica de Rentabilidad. |

Asignacion base aplicada:

| Rol | Permisos de Rentabilidad |
| --- | --- |
| `direccion` | `rentabilidad.ver`, `rentabilidad.snapshot` |
| `administrador_erp` | `rentabilidad.ver`, `rentabilidad.snapshot`, `rentabilidad.configurar` |
| `compras` | `rentabilidad.ver` |
| `catalogo_productos` | `rentabilidad.ver`, `rentabilidad.snapshot` |
| `finanzas_contabilidad` | `rentabilidad.ver`, `rentabilidad.snapshot` |
| `auditor` | `rentabilidad.ver` |
| `solo_lectura` | `rentabilidad.ver` |
| `soporte_sistema` | `rentabilidad.configurar` |

## Evidencia UAT read-only

| Caso | SKU | Resultado |
| --- | --- | --- |
| Primer SKU con inventario/valor | `TP-40372` | Riesgo `rentable`, costo comercial `737.0688`, costo unitario inventario `184.2672`, factor `4`, precio escenario `2100`, margen `64.9`, hallazgo `fiscal_incompleto`. |
| Presentacion corregida | `TP-40372-100GR` | Riesgo `rentable`, costo `18.4267`, precio escenario `85`, margen `78.32`, hallazgo `fiscal_incompleto`. |
| SKU sin costo | `0080` | Riesgo `incompleto`, costo `0`, precio escenario `690`, hallazgos `sin_costo`, `fiscal_incompleto`. |
| SKU sin precio | `10350` | Riesgo `incompleto`, costo `0`, precio escenario `0`, hallazgos `sin_costo`, `sin_precio`, `fiscal_incompleto`. |

### Comparacion de escenarios

SKU probado: `TP-40372-100GR`.

| Canal | Precio sin impuestos | Utilidad estimada | Margen bruto | Riesgo |
| --- | --- | --- | --- | --- |
| Menudeo | `85` | `51.2733` | `78.32%` | `rentable` |
| Mayoreo | `74.8` | `48.8933` | `75.37%` | `rentable` |
| Alianza | `78.2` | `44.1333` | `76.44%` | `rentable` |

Conclusion UAT: despues de corregir factor de presentaciones, el SKU ya no requiere ajuste de precio por margen; conserva pendiente fiscal incompleto antes de cerrar precios comerciales.

### Recomendaciones operativas read-only

Muestra UAT: 120 SKUs evaluados.

| Grupo | Total | Primer SKU |
| --- | --- | --- |
| Cerrar precio antes de vender | `7` | `10020` |
| Completar costo | `47` | `0080` |
| Completar fiscal | `120` | `TP-40372` |
| Revisar margen | `3` | `10020` |
| Validar inventario/costo promedio | `0` | - |

Conclusion UAT: antes de activar persistencia de recomendaciones, el mayor bloqueo operativo es fiscal incompleto en la muestra completa y costos faltantes en parte importante del catalogo. Rentabilidad ya puede priorizar trabajo sin crear pendientes persistentes.

### Evidencia por SKUs de Inventario

| SKU | Origen costo | Disponible | Riesgo |
| --- | --- | --- | --- |
| `SAL-50L` | `inventario_promedio` | `5` | `rentable` |
| `TP-7838` | `inventario_promedio` | `5` | `rentable` |
| `SHF-600` | `inventario_promedio` | `3` | `rentable` |
| `SP-2823` | `inventario_promedio` | `1` | `rentable` |

## Hallazgos operativos del modulo

| ID | Clave | Significado |
| --- | --- | --- |
| COST-H101 | `sin_costo` | SKU sin costo real calculable. |
| COST-H102 | `sin_precio` | SKU sin precio general activo. |
| COST-H103 | `fiscal_incompleto` | Impuestos incompletos para calcular precio sin impuestos. |
| COST-H104 | `perdida_estimada` | El escenario deja utilidad estimada negativa. |
| COST-H105 | `margen_bajo` | Margen bruto menor a 15%. |
| COST-H106 | `stock_sin_costo_promedio` | Hay stock con costo promedio de inventario en cero. |
| COST-H107 | `presentacion_costo_inconsistente` | Costo actual de presentacion difiere del costo esperado por transformacion/factor. |
| COST-H108 | `presentacion_costo_origen_incompleto` | No se puede calcular costo esperado porque el SKU origen no tiene costo base confiable. |

## Auditoria de costos de presentaciones

Estado: implementada auditoria read-only para reglas en `erp_catalogo_sku_transformaciones`.

Regla:

- Costo esperado resultado = `costo unitario origen * cantidad_origen / unidades_resultado`.
- Si el origen tiene inventario valuado, usa costo promedio de inventario.
- Si no tiene inventario, usa `costo_referencia / factor_unidad_base`.
- Si hay merma configurada, se incluye en el costo esperado.

Evidencia actual:

| Resultado | Detalle |
| --- | --- |
| Reglas auditadas | `8` |
| Alertas | `0` |
| `TP-40372` | Consistente en granel -> 500g/100g/50g/25g y reempaque 500g -> presentaciones. |
| `TP-40352` | Consistente en granel -> 500g despues de completar costo base. |

## Semaforo de cierre comercial

Estado: implementado read-only para decidir si una muestra de SKUs puede usarse para cerrar precios antes de Ventas/Pedidos/Mayoreo.

Reglas:

- `bloqueado`: existen costos faltantes, precios faltantes, perdida estimada, stock sin costo promedio o presentaciones inconsistentes.
- `precaucion`: no hay bloqueos duros, pero hay fiscal incompleto, margen bajo, snapshot desfasado aplicable o recomendaciones pendientes.
- `listo`: no hay bloqueos ni alertas detectadas en la muestra.
- El semaforo no guarda snapshot, no crea recomendaciones, no actualiza Catalogo y no toca Ventas.

Evidencia actual:

| Muestra | Estado | Bloqueos duros | Alertas | Presentaciones | Snapshot |
| --- | --- | --- | --- | --- | --- |
| General `300` SKUs | `bloqueado` | `138` | `304` | `0` alertas | `1` desfasado |
| `TP-40372` | `precaucion` | `0` | `6` | `0` alertas | `1` desfasado aplicable |
| `TP-40352` | `precaucion` | `0` | `2` | `0` alertas | `0` desfasados aplicables |

## Auditoria de datos base para cierre

Estado: implementada read-only para priorizar correcciones previas a cerrar precio.

Grupos:

- Completar costo base: SKUs sin costo real ni referencia suficiente.
- Completar precio general: SKUs sin precio activo para simular venta.
- Completar fiscal: SKUs sin clave SAT, objeto impuesto, IVA/IEPS o bandera de impuestos incluidos.
- Revisar margen/precio: SKUs con perdida estimada o margen bajo.

Evidencia actual:

| Muestra | Costo | Precio | Fiscal | Margen |
| --- | --- | --- | --- | --- |
| General `120` SKUs | `47` | `2` | `120` | `0` |
| `TP-40372` | `0` | `0` | `5` | `0` |
| `TP-40352` | `0` | `0` | `2` | `0` |

## Evidencia fiscal XML

Estado: implementada auditoria read-only de XML fiscal vinculado a SKU ERP.

Resultado:

- La muestra general tiene `120` SKUs con fiscal incompleto y `0` sugerencias XML vinculadas utiles dentro de esa muestra.
- `TP-40372` tiene `5` SKUs fiscales incompletos y `0` sugerencias XML vinculadas.
- `TP-40352` tiene `2` SKUs fiscales incompletos y `0` sugerencias XML vinculadas.
- No se infiere fiscal por texto/descripciones; aplicar datos fiscales a Catalogo requiere respaldo externo y autorizacion.

## Matriz de escenarios

Estado: implementada read-only para comparar menudeo, mayoreo y alianza en una muestra de SKUs.

Reglas:

- Usa los defaults operativos actuales de cada canal.
- Calcula utilidad, margen, precio, costo y riesgo por canal.
- El mejor canal se elige por utilidad estimada, excluyendo escenarios incompletos.
- No guarda escenarios y no actualiza precios.

Evidencia actual:

| Muestra | Menudeo | Mayoreo | Alianza |
| --- | --- | --- | --- |
| General `120` SKUs | `68` rentables, `48` bloqueados, `4` perdida | `68` rentables, `48` bloqueados, `4` perdida | `68` rentables, `48` bloqueados, `4` perdida |
| `TP-40372` | `5` rentables | `5` rentables | `5` rentables |
| `TP-40352` | `2` rentables | `2` rentables | `2` rentables |

## Precios objetivo

Estado: implementado simulador read-only de precio minimo rentable por canal.

Reglas:

- Precio minimo rentable considera costo, gasto operativo, comision y margen objetivo del canal.
- SKU sin costo o sin precio queda como no candidato de cierre.
- Delta positivo indica que el precio actual no alcanza el minimo rentable del canal.
- No crea recomendaciones persistentes y no actualiza Catalogo/Ventas.

Evidencia actual:

| Muestra | Requieren subir | Sin costo | Sin precio | Viables |
| --- | --- | --- | --- | --- |
| General `120` SKUs | `18` | `45` | `3` | `54` |
| `TP-40372` | `0` | `0` | `0` | `5` |
| `TP-40352` | `0` | `0` | `0` | `2` |

## Tablero ejecutivo

Estado: implementado read-only para resumir decision comercial sin escribir Catalogo, Inventario ni Ventas.

Componentes:

- Utilidad estimada total y utilidad negativa total.
- Valor de inventario rentable y valor de inventario expuesto.
- Ranking de perdidas.
- Ranking de oportunidades por utilidad.
- Ranking de inventario expuesto.
- Ranking de acciones de precio por delta contra minimo rentable.

Evidencia actual:

| Muestra | Utilidad estimada | Utilidad negativa | Inventario rentable | Primer accion precio |
| --- | --- | --- | --- | --- |
| General `300` SKUs | `75354.5682` | `-2000.13` | `7782.58` | `5413382969059` |
| `TP-40372` | `1229.5508` | `0` | `3689.951` | - |
| `TP-40352` | `1272.9` | `0` | `0` | - |

## Modo revision

Estado: implementado read-only para navegar prioridades de trabajo dentro de Rentabilidad.

Bandejas:

- Perdidas.
- Subir precio.
- Completar costo.
- Completar precio.
- Completar fiscal.
- Inventario expuesto.
- Oportunidad con stock.

Reglas:

- No crea tareas persistentes.
- No guarda snapshots.
- No actualiza precios.
- No toca Catalogo, Inventario ni Ventas.
- Las bandejas se derivan del analisis vigente y del escenario seleccionado.

Evidencia actual:

| Muestra | Perdidas | Subir precio | Completar costo | Completar precio | Oportunidad stock |
| --- | --- | --- | --- | --- | --- |
| General `300` SKUs | `5` | `21` | `130` | `3` | `9` |
| `TP-40372` | `0` | `0` | `0` | `0` | `3` |
| `TP-40352` | `0` | `0` | `0` | `0` | `0` |

## Incidente cerrado: costo base faltante `TP-40352`

Hallazgo: `TP-40352` tenia costo referencia `0`, pero su presentacion `TP-40352-500GR` tenia costo `95`. La transformacion consume `0.5` unidades de origen para producir `1` unidad de 500 g, con merma `0%`.

Correccion aplicada:

- Costo unitario origen inferido: `95 / 0.5 = 190`.
- Factor del SKU base: `4`.
- Costo comercial del costal/base: `190 * 4 = 760`.
- Se actualizo solo `erp_catalogo_skus.costo_referencia` de `TP-40352` a `760`.
- No se modifico Inventario ni movimientos porque no existen existencias ni kardex para `TP-40352`/`TP-40352-500GR`.

Evidencia:

- Respaldo: `C:\xampp\panel_db_backups\artianilocal_panel_20260623_antes_tp40352_costo_base.sql`.
- UAT diagnostico: `storage/uat/uat_rentabilidad_tp40352_costos_readonly.php`.
- UAT reparacion: `storage/uat/uat_rentabilidad_tp40352_costo_base_reparar.php`.
- Auditoria posterior: `TP-40352 -> TP-40352-500GR` queda `ok`, costo origen unitario `190`, costo resultado esperado `95`, costo resultado actual `95`.

## Incidente cerrado: costo de presentaciones `TP-40372`

Hallazgo: las presentaciones preparadas desde `TP-40372` estaban costeadas como si el costo del costal completo de 4 kg (`737.068966`) fuera costo por kg.

Correccion aplicada:

- Inventario base queda valuado por kg: `737.068966 / 4 = 184.267242`.
- Rentabilidad evalua el SKU comercial `TP-40372` multiplicando costo unitario de inventario por `factor_unidad_base=4`.
- Presentaciones corregidas:
  - `TP-40372-500GR`: `92.133621`.
  - `TP-40372-100GR`: `18.426724`.
  - `TP-40372-50GR`: `9.213362`.
  - `TP-40372-25GR`: `4.606681`.
- Almacen normaliza recepciones futuras por `factor_unidad_base` antes de guardar existencia/kardex.

Evidencia:

- Respaldo: `C:\xampp\panel_db_backups\artianilocal_panel_20260623_antes_tp40372_costos_presentaciones.sql`.
- UAT reparacion: `storage/uat/uat_rentabilidad_tp40372_costos_reparar.php`.
- UAT auditoria: `storage/uat/uat_rentabilidad_tp40372_costos_readonly.php`.

Riesgo residual cerrado: la recomendacion persistente historica para `TP-40372-100GR` creada antes de corregir costos fue cancelada con respaldo previo.

## Esquema aplicado con respaldo

Archivo: `docs/erp_costos_rentabilidad_schema_propuesta.sql`.

Este esquema fue aplicado con respaldo externo y autorizacion. Su objetivo es preparar:

- escenarios persistentes por canal;
- snapshots de analisis por corrida;
- detalle de resultados por SKU;
- hallazgos persistentes para seguimiento;
- recomendaciones/aprobaciones antes de publicar precios en Ventas o listas comerciales.

Aplicacion tecnica:

- Modelo preparado: `app/modelos/RentabilidadEsquema.php`.
- Ruta preparada solo auditoria: `/rentabilidad/esquema_auditar_erp`.
- No existe ruta web de ejecucion DDL en este corte; la aplicacion fue por UAT CLI autorizado.
- Respaldo externo previo: `C:\xampp\panel_db_backups\artianilocal_panel_20260623_antes_rentabilidad_schema.sql`.
- Tablas aplicadas:
  - `erp_rentabilidad_escenarios`
  - `erp_rentabilidad_snapshots`
  - `erp_rentabilidad_snapshot_detalle`
  - `erp_rentabilidad_recomendaciones`

## Persistencia inicial

### Escenarios base sembrados

| Clave | Canal | Descuento | Gasto | Comision | Margen objetivo |
| --- | --- | --- | --- | --- | --- |
| `menudeo_base` | menudeo | `0%` | `18%` | `0%` | `25%` |
| `mayoreo_base` | mayoreo | `12%` | `10%` | `0%` | `18%` |
| `alianza_base` | alianza | `8%` | `12%` | `8%` | `20%` |

### Primer snapshot persistente

| Campo | Valor |
| --- | --- |
| Folio | `RENT-20260622-221611` |
| Filtro | `TP-40372` |
| Canal | `menudeo` |
| SKUs | `5` |
| Perdida | `1` |
| Margen bajo | `1` |
| Sin costo | `0` |
| Sin precio | `0` |
| Valor inventario | `14743.6828` |

### Vigencia de snapshots

Estado actual: el snapshot `RENT-20260622-221611` conserva valor historico, pero esta desfasado frente al calculo actual despues de corregir factores de presentaciones `TP-40372`.

Principales diferencias detectadas:

| SKU | Snapshot | Actual |
| --- | --- | --- |
| `TP-40372-25GR` | costo `17.658919`, margen `29.36%` | costo `4.6067`, margen `81.57%` |
| `TP-40372-100GR` | costo `73.7069`, riesgo `perdida` | costo `18.4267`, riesgo `rentable` |
| `TP-40372-50GR` | costo `4.606681`, margen `89.76%` | costo `9.213362`, margen `79.53%` |

Regla operativa: un snapshot desfasado no debe usarse para cerrar precio vigente. Cancelar o marcar snapshots como supersedidos requiere respaldo y autorizacion.

### Candado de escritura de snapshots

Se agrego validacion server-side para que `/rentabilidad/snapshot_guardar_erp` rechace cualquier escritura si no trae:

- `respaldo_externo_ref`: referencia o ruta del respaldo externo.
- `confirmar_autorizacion`: frase exacta `AUTORIZO GUARDAR SNAPSHOT`.

La UI pide ambos datos antes de mandar el POST. El UAT `uat_rentabilidad_snapshot_persistente.php` quedo en modo seguro por defecto: sin `--execute` solo consulta auditoria/analisis read-only.

## Recomendaciones persistentes

Estado: implementado flujo inicial sin aplicar precios.

Reglas:

- Se crean recomendaciones solo para SKUs con `perdida_estimada`, `margen_bajo` o `sin_precio`.
- No duplica una recomendacion pendiente para el mismo SKU/canal.
- El guardado exige referencia de respaldo externo y frase exacta `AUTORIZO CREAR RECOMENDACIONES`.
- La resolucion exige referencia de respaldo externo y frase exacta `AUTORIZO RESOLVER RECOMENDACION`.
- Aprobar/rechazar/cancelar solo cambia el estatus de la recomendacion; no actualiza Catalogo ni Ventas.
- Aplicar precio queda fuera de este corte.
- El UAT `uat_rentabilidad_recomendaciones_persistentes.php` quedo en modo seguro por defecto: sin `--execute` solo consulta preflight.

Evidencia UAT:

| Campo | Valor |
| --- | --- |
| Filtro | `TP-40372` |
| Canal | `menudeo` |
| Candidatos | `1` |
| Creadas | `1` |
| SKU pendiente | `TP-40372-100GR` |
| Segunda corrida | `0` creadas, `1` omitida por pendiente existente |

### Preflight de recomendaciones

Se agrego preflight read-only para simular el guardado de recomendaciones persistentes antes de pedir autorizacion de escritura.

Ruta: `/rentabilidad/recomendaciones_preflight_erp`.

Reglas:

- Usa la misma regla de candidato que el guardado real: `perdida_estimada`, `margen_bajo` o `sin_precio`.
- Detecta recomendaciones pendientes del mismo SKU/canal para evitar duplicados.
- Calcula precio recomendado como el mayor entre precio actual de escenario y precio minimo rentable.
- No inserta recomendaciones, no resuelve pendientes y no modifica precios.

Resultado observado:

| Muestra | Evaluados | Candidatos | Creables | Omitidos | Delta total |
| --- | --- | --- | --- | --- | --- |
| General | `120` | `7` | `7` | `0` | `4981.701754` |
| `TP-40372` | `5` | `0` | `0` | `0` | `0` |
| `TP-40352` | `2` | `0` | `0` | `0` | `0` |
| Accion `subir_precio` | `9` | `5` | `5` | `0` | `4981.701754` |

### Candado de escritura de recomendaciones

Se agrego validacion server-side para que `/rentabilidad/recomendaciones_guardar_erp` rechace cualquier escritura si no trae:

- `respaldo_externo_ref`: referencia o ruta del respaldo externo.
- `confirmar_autorizacion`: frase exacta `AUTORIZO CREAR RECOMENDACIONES`.

La UI pide ambos datos antes de mandar el POST. La validacion importante queda en backend para cubrir llamadas manuales, scripts o pantallas futuras.

## Punto donde se requerira autorizacion

Se necesitara autorizacion antes de:

- Actualizar precios de Catalogo o alimentar Ventas/Mayoreo.
- Completar datos fiscales en Catalogo desde captura manual o XML.
- Crear flujo de aplicacion de recomendaciones aprobadas.
- Definir si una recomendacion aprobada puede aplicar precio en Catalogo, crear tarea para Catalogo o solo quedar como evidencia directiva.
- Cancelar o marcar como supersedido un snapshot historico desfasado.
- Guardar un nuevo snapshot vigente posterior a las correcciones de costos.

## Filtros operativos read-only

Se agregaron filtros transversales para trabajar la muestra del modulo sin tocar Catalogo, Inventario, Compras ni Ventas:

- Accion: perdidas, subir precio, completar costo, completar precio, completar fiscal y oportunidad con stock.
- Stock: con stock, sin stock y con valor de inventario.
- Origen de costo: inventario promedio, catalogo referencia y sin costo.
- Proveedor preferido: busqueda textual sobre el proveedor asociado al SKU.

Endpoints cubiertos por los filtros:

- `/rentabilidad/analizar_erp`
- `/rentabilidad/tablero_ejecutivo_erp`
- `/rentabilidad/revision_operativa_erp`
- `/rentabilidad/workflow_comercial_erp`
- `/rentabilidad/recomendaciones_erp`
- `/rentabilidad/recomendaciones_preflight_erp`
- `/rentabilidad/matriz_escenarios_erp`
- `/rentabilidad/canales_recomendados_erp`
- `/rentabilidad/plan_cierre_erp`
- `/rentabilidad/impacto_cierre_erp`
- `/rentabilidad/hallazgos_cierre_erp`
- `/rentabilidad/prioridades_cierre_erp`
- `/rentabilidad/responsables_cierre_erp`
- `/rentabilidad/checklist_cierre_erp`
- `/rentabilidad/autorizaciones_cierre_erp`
- `/rentabilidad/precios_objetivo_erp`
- `/rentabilidad/precios_aprobacion_preflight_erp`
- `/rentabilidad/sensibilidad_erp`
- `/rentabilidad/semaforo_cierre_erp`
- `/rentabilidad/variaciones_costos_erp`
- `/rentabilidad/cierre_precios_auditar_erp`
- `/rentabilidad/datos_base_auditar_erp`
- `/rentabilidad/fiscal_xml_auditar_erp`
- `/rentabilidad/fiscal_preflight_erp`

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_filtros_operativos_readonly.php`.

Resultado observado:

| Filtro | Resultado |
| --- | --- |
| Stock `con_stock` | `9` SKUs |
| Stock `sin_stock` | `291` SKUs |
| Origen `inventario_promedio` | `9` SKUs |
| Accion `subir_precio` en revision | `21` SKUs evaluados, `21` en bandeja subir precio |
| `TP-40372` con stock | `3` SKUs |
| `TP-40352` con stock | `0` SKUs |
| Matriz con stock | `9` SKUs evaluados |

Regla: estos filtros solo segmentan evidencia y simulaciones. No aplican precios, no corrigen Catalogo y no mueven inventario.

## Ficha de evidencia por SKU

Se agrego una ficha read-only por SKU desde la tabla principal del analisis.

Ruta: `/rentabilidad/detalle_sku_erp`.

Incluye:

- Calculo activo del SKU: costo real, origen de costo, precio, margen, utilidad, minimo rentable y riesgo.
- Comparacion menudeo, mayoreo y alianza con defaults operativos.
- Datos base: costo referencia, precio general, ultimo costo de compra, ultimo costo XML, proveedor y faltantes fiscales.
- Evidencia fiscal/XML exacta del SKU consultado.
- Presentaciones relacionadas para auditar conversiones y costos de familia.
- Vigencia de snapshots relacionados.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_detalle_sku_readonly.php`.

Resultado observado:

| SKU | Riesgo | Costo | Escenarios | Fiscal exacto | Presentaciones | Snapshots desfasados |
| --- | --- | --- | --- | --- | --- | --- |
| `TP-40372` | `rentable` | `737.0688` | `3` | `1` pendiente | `7`, alertas `0` | `1` |
| `TP-40372-500GR` | `rentable` | `92.133621` | `3` | `1` pendiente | `4`, alertas `0` | `1` |
| `TP-40352` | `rentable` | `760` | `3` | `1` pendiente | `1`, alertas `0` | `0` |
| `0080` | `incompleto` | `0` | `3` | `1` pendiente | `0`, alertas `0` | `0` |

Regla: la ficha consolida evidencia para decidir, pero no aplica precios, no completa fiscal y no mueve inventario.

## Semaforo de cierre por SKU

Se agrego un semaforo read-only para priorizar cierre comercial antes de usar Ventas/Pedidos/Mayoreo.

Ruta: `/rentabilidad/semaforo_cierre_erp`.

Estados:

- `bloqueado`: falta costo/precio, hay perdida estimada, stock sin costo promedio o precio menor al minimo rentable.
- `precaucion`: no hay bloqueo economico duro, pero falta fiscal o hay margen bajo.
- `listo`: sin bloqueos ni alertas en el escenario evaluado.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_semaforo_cierre_readonly.php`.

Resultado observado:

| Muestra | Evaluados | Listos | Precaucion | Bloqueados | Primer paso |
| --- | --- | --- | --- | --- | --- |
| General | `120` | `0` | `64` | `56` | `El escenario deja utilidad negativa.` |
| `TP-40372` | `5` | `0` | `5` | `0` | `Completar fiscal antes de publicar precio final.` |
| `TP-40352` | `2` | `0` | `2` | `0` | `Completar fiscal antes de publicar precio final.` |
| Accion `subir_precio` | `9` | `0` | `0` | `9` | `El escenario deja utilidad negativa.` |

Regla: el semaforo no publica precios; solo ordena decisiones y evidencia.

## Variacion de costos contra evidencia

Se agrego auditoria read-only para detectar diferencias entre el costo usado por Rentabilidad y la evidencia historica disponible.

Ruta: `/rentabilidad/variaciones_costos_erp`.

Fuentes comparadas cuando existen:

- Ultimo costo de compra.
- Promedio de compras.
- Ultimo costo XML.
- Costo proveedor preferido.

Umbral operativo default: `15%` de diferencia contra la fuente de evidencia. El umbral puede consultarse con `umbral_pct`, pero no modifica configuraciones ni datos.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_variaciones_costos_readonly.php`.

Resultado observado:

| Muestra | Evaluados | Con evidencia | Sin evidencia | Alertas | Mayor diferencia |
| --- | --- | --- | --- | --- | --- |
| General | `120` | `71` | `49` | `5` | `25.55%` |
| `TP-40372` | `5` | `1` | `4` | `0` | `0%` |
| `TP-40352` | `2` | `0` | `2` | `0` | `0%` |
| Stock disponible | `9` | `7` | `2` | `5` | `25.55%` |

Regla: una variacion solo genera alerta de revision; aplicar costos a Catalogo o Inventario requiere respaldo y autorizacion.

## Sensibilidad de rentabilidad

Se agrego simulador read-only para evaluar fragilidad de margen antes de cerrar precios.

Ruta: `/rentabilidad/sensibilidad_erp`.

Shock default:

- Costo `+5%`.
- Precio `-5%`.
- Combinado: costo `+5%` y precio `-5%`.

Parametros opcionales de consulta:

- `costo_alza_pct`
- `precio_baja_pct`

Estados:

- `vulnerable`: el shock combinado deja utilidad no positiva o margen bajo.
- `resiste`: el SKU conserva rentabilidad bajo el shock definido.
- `incompleto`: no hay costo o precio confiable para simular.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_sensibilidad_readonly.php`.

Resultado observado:

| Muestra | Evaluados | Incompletos | Vulnerables | Resisten | Quiebre combinado |
| --- | --- | --- | --- | --- | --- |
| General | `120` | `48` | `4` | `68` | `4` |
| `TP-40372` | `5` | `0` | `0` | `5` | `0` |
| `TP-40352` | `2` | `0` | `0` | `2` | `0` |
| Stock disponible | `9` | `0` | `0` | `9` | `0` |
| `TP-40372`, shock fuerte `+20%/-10%` | `5` | `0` | `0` | `5` | `0` |

Regla: la sensibilidad no cambia costos, precios ni escenarios; solo evita cerrar precios con margen demasiado fragil.

## Canal recomendado por SKU

Se agrego recomendacion read-only de canal comercial por SKU para decidir donde conviene vender antes de tocar Ventas/Pedidos/Mayoreo.

Ruta: `/rentabilidad/canales_recomendados_erp`.

Reglas:

- Evalua menudeo, mayoreo y alianza con defaults operativos.
- Elige el canal con mayor utilidad entre escenarios vendibles.
- Si todos los canales tienen perdida o datos incompletos, queda `bloqueados`.
- Si solo existe margen bajo, queda `precaucion`.
- No crea listas, no publica precios y no toca Ventas.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_canales_recomendados_readonly.php`.

Resultado observado:

| Muestra | Evaluados | Listos | Precaucion | Bloqueados | Menudeo | Mayoreo | Alianza |
| --- | --- | --- | --- | --- | --- | --- | --- |
| General | `120` | `68` | `0` | `52` | `68` | `0` | `0` |
| `TP-40372` | `5` | `5` | `0` | `0` | `5` | `0` | `0` |
| `TP-40352` | `2` | `2` | `0` | `0` | `2` | `0` | `0` |
| Stock disponible | `9` | `9` | `0` | `0` | `9` | `0` | `0` |

Regla: con los defaults actuales, menudeo domina por utilidad; cambiar descuentos/gastos de canal puede cambiar el dictamen.

## Plan de cierre comercial

Se agrego un tablero read-only que consolida los bloqueos principales para cerrar precios por SKU antes de usar Ventas/Pedidos/Mayoreo.

Ruta: `/rentabilidad/plan_cierre_erp`.

Bandejas:

- Completar costo/precio.
- Revisar precio/margen.
- Validar costo.
- Completar fiscal.
- Revisar canal.
- Listos para cierre.

Prioridad:

1. Datos incompletos de costo/precio.
2. Perdida, margen bajo o precio menor al minimo rentable.
3. Canal no vendible.
4. Variacion de costo o stock sin costo promedio.
5. Sensibilidad vulnerable.
6. Fiscal incompleto.
7. Cierre.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_plan_cierre_readonly.php`.

Resultado observado:

| Muestra | Evaluados | Completar fiscal | Completar datos | Revisar precio | Validar costo | Listos |
| --- | --- | --- | --- | --- | --- | --- |
| General | `120` | `59` | `48` | `8` | `5` | `0` |
| `TP-40372` | `5` | `5` | `0` | `0` | `0` | `0` |
| `TP-40352` | `2` | `2` | `0` | `0` | `0` | `0` |
| Stock disponible | `9` | `4` | `0` | `0` | `5` | `0` |
| Accion `subir_precio` | `9` | `0` | `1` | `8` | `0` | `0` |

Regla: el plan solo prioriza trabajo y evidencia. No crea tareas, no guarda recomendaciones y no aplica precios.

## Impacto de cierre comercial

Se agrego medicion read-only para dimensionar por dinero las bandejas del plan de cierre.

Ruta: `/rentabilidad/impacto_cierre_erp`.

Metricas:

- Utilidad estimada confiable: solo SKUs con costo/precio suficientes.
- Utilidad no confiable: utilidad calculada en SKUs con datos incompletos; no debe usarse para publicar precios.
- Utilidad negativa: suma de utilidades negativas en SKUs con base economica suficiente.
- Deficit precio: diferencia acumulada contra precio minimo rentable cuando el precio actual no alcanza.
- Valor inventario: valor read-only asociado a la bandeja.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_impacto_cierre_readonly.php`.

Resultado observado:

| Muestra | Evaluados | Utilidad confiable | Utilidad no confiable | Utilidad negativa | Deficit precio | Inventario |
| --- | --- | --- | --- | --- | --- | --- |
| General | `120` | `20290.3982` | `12534.46` | `-1688.18` | `5232` | `7782.58` |
| `TP-40372` | `5` | `1229.550817` | `0` | `0` | `0` | `3689.951` |
| `TP-40352` | `2` | `1272.9` | `0` | `0` | `0` | `0` |
| Stock disponible | `9` | `2514.0582` | `0` | `0` | `0` | `7782.58` |
| Accion `subir_precio` | `9` | `-700.85` | `-159.14` | `-1688.18` | `5232` | `0` |

Regla: el impacto no reemplaza la validacion SKU por SKU. Si falta costo/precio, el dinero queda marcado como no confiable.

## Hallazgos de cierre comercial

Se agrego tablero read-only de hallazgos por ID operativo para UAT y seguimiento manual.

Ruta: `/rentabilidad/hallazgos_cierre_erp`.

IDs activos:

- `COST-H101`: sin costo.
- `COST-H102`: sin precio.
- `COST-H103`: fiscal incompleto.
- `COST-H104`: perdida estimada.
- `COST-H105`: margen bajo.
- `COST-H106`: stock sin costo promedio.
- `COST-H107`: precio menor al minimo rentable.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_hallazgos_cierre_readonly.php`.

Resultado observado:

| Hallazgo | SKUs | Utilidad confiable | Utilidad no confiable | Inventario |
| --- | --- | --- | --- | --- |
| `COST-H103` fiscal incompleto | `120` | `20290.3982` | `12534.46` | `7782.58` |
| `COST-H101` sin costo | `47` | `0` | `12693.6` | `0` |
| `COST-H107` precio bajo minimo | `8` | `-700.85` | `0` | `0` |
| `COST-H104` perdida estimada | `4` | `-1688.18` | `0` | `0` |
| `COST-H102` sin precio | `3` | `0` | `-159.14` | `0` |
| `COST-H105` margen bajo | `3` | `-1683.19` | `0` | `0` |

Regla: un SKU puede aparecer en mas de un hallazgo. El conteo de hallazgos sirve para causa raiz; el plan de cierre sirve para una sola bandeja priorizada por SKU.

## Prioridades de cierre comercial

Se agrego ranking read-only por SKU para ordenar la revision operativa.

Ruta: `/rentabilidad/prioridades_cierre_erp`.

Criterios del score:

- Peso base por bandeja del plan de cierre.
- Mayor peso a perdida estimada y deficit contra precio minimo rentable.
- Peso adicional por inventario involucrado y alertas de costo.
- La utilidad positiva solo suma cuando el SKU tiene costo/precio confiable.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_prioridades_cierre_readonly.php`.

Resultado observado:

| Muestra | Evaluados | Prioridades | Alta | Media | Baja | Primer SKU |
| --- | --- | --- | --- | --- | --- | --- |
| General | `120` | `120` | `6` | `57` | `57` | `1330`, revisar precio, score `2853.36` |
| `TP-40372` | `5` | `5` | `0` | `1` | `4` | `TP-40372`, completar fiscal, score `869.63` |
| `TP-40352` | `2` | `2` | `0` | `0` | `2` | `TP-40352`, completar fiscal, score `593.78` |
| Accion `subir_precio` | `9` | `9` | `5` | `4` | `0` | `1330`, revisar precio, score `2853.36` |

Regla: el score solo ordena revision. No crea tareas, no aprueba precios y no reemplaza UAT por SKU.

## Responsables de cierre comercial

Se agrego resumen read-only por responsable sugerido para dimensionar la cola de trabajo sin crear tareas persistentes.

Ruta: `/rentabilidad/responsables_cierre_erp`.

Mapeo sugerido:

- Completar datos: `Catalogo`.
- Revisar precio/canal: `Direccion/Comercial`.
- Validar costo: `Compras/Almacen`.
- Completar fiscal: `Catalogo/Fiscal`.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_responsables_cierre_readonly.php`.

Resultado observado:

| Responsable | SKUs | Alta | Media | Baja | Utilidad confiable | No confiable | Deficit precio |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Direccion/Comercial | `8` | `4` | `4` | `0` | `-700.85` | `0` | `4952.807018` |
| Catalogo | `48` | `1` | `47` | `0` | `0` | `12534.46` | `279.192982` |
| Compras/Almacen | `5` | `1` | `4` | `0` | `1290.4225` | `0` | `0` |
| Catalogo/Fiscal | `59` | `0` | `2` | `57` | `19700.8257` | `0` | `0` |

Regla: el responsable es sugerido por bandeja. No asigna usuarios, no genera tareas y no sustituye una decision operativa del dueno.

## Checklist de cierre comercial

Se agrego checklist read-only para validar condiciones de cierre antes de pedir autorizaciones operativas.

Ruta: `/rentabilidad/checklist_cierre_erp`.

Checks:

- `COST-CHK-001`: costo y precio completos.
- `COST-CHK-002`: precio y margen rentables.
- `COST-CHK-003`: costo validado contra evidencia.
- `COST-CHK-004`: fiscal completo.
- `COST-CHK-005`: canal comercial definido.
- `COST-CHK-006`: candidatos a cierre.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_checklist_cierre_readonly.php`.

Resultado observado:

| Muestra | Evaluados | Checks OK | Bloqueados | Informativos | SKUs bloqueados | Listos |
| --- | --- | --- | --- | --- | --- | --- |
| General | `120` | `1` | `4` | `1` | `120` | `0` |
| `TP-40372` | `5` | `4` | `1` | `1` | `5` | `0` |
| `TP-40352` | `2` | `4` | `1` | `1` | `2` | `0` |
| Accion `subir_precio` | `9` | `3` | `2` | `1` | `9` | `0` |

Regla: el checklist no crea tareas, no guarda aprobaciones y no aplica precios. Un check OK solo significa que la muestra filtrada no tiene ese bloqueo.

## Paquete de autorizacion de cierre

Se agrego paquete read-only para preparar la siguiente autorizacion sin ejecutar escrituras.

Ruta: `/rentabilidad/autorizaciones_cierre_erp`.

Acciones preparadas:

- `AUTH-COST-001`: guardar snapshot vigente de rentabilidad.
- `AUTH-COST-002`: crear recomendaciones persistentes desde prioridades.
- `AUTH-COST-003`: aplicar datos fiscales sugeridos a Catalogo.
- `AUTH-COST-004`: aplicar precios aprobados a Catalogo.
- `AUTH-COST-005`: validar/corregir costos con evidencia.

Evidencia UAT read-only: `storage/uat/uat_rentabilidad_autorizaciones_cierre_readonly.php`.

Resultado observado:

| Muestra | Acciones | Bloqueadas | Requieren respaldo | Listas | Lectura operativa |
| --- | --- | --- | --- | --- | --- |
| General | `5` | `3` | `2` | `0` | Hay prioridades y costos por validar, pero no hay SKUs listos para snapshot. |
| `TP-40372` | `5` | `3` | `1` | `1` | Costo listo, fiscal bloquea cierre y precios siguen bloqueados. |
| `TP-40352` | `5` | `3` | `1` | `1` | Costo listo, fiscal bloquea cierre y precios siguen bloqueados. |
| Accion `subir_precio` | `5` | `3` | `1` | `1` | Precios aprobados siguen bloqueados hasta definir politica comercial. |

Regla: este paquete no guarda snapshots, no crea recomendaciones, no aplica fiscal y no aplica precios. Solo enumera que autorizacion, permiso y respaldo se requeririan para cada siguiente paso.

## Preflight de aprobacion de precios

Se agrego preflight read-only para separar precio rentable de precio publicable/aprobable.

Ruta: `/rentabilidad/precios_aprobacion_preflight_erp`.

Politica conservadora actual:

- Bloquea aprobacion si falta costo real, precio base, fiscal completo o costo promedio confiable.
- Marca revision si hay perdida estimada, margen bajo, precio actual menor al minimo rentable o variacion de costo contra evidencia.
- Calcula precio sugerido como el mayor entre precio actual sin impuesto y precio minimo rentable.
- No aplica precios, no crea aprobaciones, no actualiza Catalogo y no toca Ventas/ecommerce.

Resultado observado:

| Muestra | Evaluados | Aprobables | Revision | Bloqueados | Subir precio | Conservar |
| --- | --- | --- | --- | --- | --- | --- |
| General | `120` | `0` | `0` | `120` | `9` | `111` |
| `TP-40372` | `5` | `0` | `0` | `5` | `0` | `5` |
| `TP-40352` | `2` | `0` | `0` | `2` | `0` | `2` |
| Accion `subir_precio` | `9` | `0` | `0` | `9` | `9` | `0` |

Lectura operativa: `TP-40372` y `TP-40352` ya no estan bloqueados por costo ni por precio rentable; siguen bloqueados para aprobacion/publicacion por fiscal incompleto. Mientras fiscal siga incompleto, no conviene permitir aplicacion de precios al Catalogo.

## Preflight fiscal de cierre

Se agrego preflight fiscal read-only para convertir el bloqueo fiscal en trabajo operativo antes de pedir autorizacion sobre Catalogo.

Ruta: `/rentabilidad/fiscal_preflight_erp`.

Clasificacion:

- `aplicable_xml`: existe XML vinculado con claves SAT y tasas fiscales suficientes; aun asi requiere respaldo y autorizacion para aplicar.
- `captura_manual`: hay evidencia parcial, pero faltan tasas o definicion de impuestos.
- `sin_evidencia`: no hay XML fiscal suficiente; Rentabilidad no debe inferir fiscal por descripcion.

Resultado observado:

| Muestra | Evaluados | XML aplicable | Captura manual | Sin evidencia | Campos faltantes |
| --- | --- | --- | --- | --- | --- |
| General | `120` | `0` | `0` | `120` | los 6 campos fiscales en los `120` |
| `TP-40372` | `5` | `0` | `0` | `5` | los 6 campos fiscales en los `5` |
| `TP-40352` | `2` | `0` | `0` | `2` | los 6 campos fiscales en los `2` |
| Accion `completar_fiscal` | `120` | `0` | `0` | `120` | los 6 campos fiscales en los `120` |

Lectura operativa: el bloqueo fiscal actual no se resuelve aplicando XML desde Rentabilidad porque no hay evidencia XML util vinculada. La salida correcta es completar fiscal en Catalogo o mejorar la vinculacion fiscal/XML con respaldo y autorizacion.

## Workflow comercial de Rentabilidad

Se agrego mesa read-only para ordenar el trabajo propio del modulo sin invadir Catalogo, Inventario ni Ventas.

Ruta: `/rentabilidad/workflow_comercial_erp`.

Bandejas:

- `crear_pendientes`: candidatos para recomendaciones persistentes; requiere respaldo externo y autorizacion.
- `resolver_pendientes`: aprobar/rechazar/cancelar recomendaciones ya creadas; solo evidencia comercial, no aplica precios.
- `aprobar_precios`: aprobacion interna de precios; por ahora bloqueada mientras no exista politica y datos publicables.
- `trabajo_prioritario`: ranking read-only de revision operativa.

Resultado observado:

| Muestra | Candidatos creables | Pendientes | Aprobables | Bloq. aprobacion | Prioridades | Estado clave |
| --- | --- | --- | --- | --- | --- | --- |
| General | `7` | `0` | `0` | `120` | `120` | crear pendientes requiere autorizacion |
| `TP-40372` | `0` | `0` | `0` | `5` | `5` | sin pendientes creables |
| Accion `subir_precio` | `5` | `0` | `0` | `9` | `9` | crear pendientes requiere autorizacion |

Regla: el workflow no crea pendientes, no resuelve recomendaciones, no guarda aprobaciones y no aplica precios. Solo prepara la siguiente decision de autorizacion dentro de Rentabilidad.

## Escenarios comerciales

Se agrego auditoria read-only de escenarios comerciales persistidos.

Ruta: `/rentabilidad/escenarios_auditar_erp`.

Resultado observado:

| Escenario | Estado | Diferencias |
| --- | --- | --- |
| `menudeo_base` | `activo` | `0` |
| `mayoreo_base` | `activo` | `0` |
| `alianza_base` | `activo` | `0` |

Resumen: `3` escenarios semilla, `3` persistidos, `3` activos, `0` faltantes y `0` diferencias contra defaults. No se requiere autorizacion para sembrar escenarios en este momento.
