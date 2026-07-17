# ERP Proveedores - Pruebas reales

Estado: guia viva creada el 2026-06-14.

Objetivo:

- Validar el modulo de Proveedores con casos reales, paso por paso.
- Registrar que se probo, que fallo, que decision falta y que se puede continuar.
- Evitar pruebas enormes o cambios masivos mientras el flujo se estabiliza.

Reglas de prueba:

- Probar primero con 1 o 2 proveedores reales.
- No ejecutar migraciones masivas.
- No aplicar relaciones/costos en lote sin revisar primero el preview y confirmar conteos.
- No actualizar `costo_referencia` salvo desde el flujo controlado de preview/aplicar elegibles.
- No crear incidencias en lote.
- No cambiar reglas de bloqueo en Compras sin decision explicita.
- Anotar evidencia cuando algo no coincida con lo esperado.

## 1. Proveedores recomendados para probar

Usar al menos estos tipos de proveedor:

| Tipo de caso | Proveedor | Objetivo | Estado |
| --- | --- | --- | --- |
| Proveedor con lista XLSX |  | Probar carga, mapeo, importacion y limpieza | Pendiente |
| Proveedor con XLS legacy | SUNNY | Confirmar mensaje de conversion a XLSX/CSV | En prueba |
| Proveedor con datos completos |  | Validar ficha completa y preparacion | Pendiente |
| Proveedor con datos incompletos |  | Validar advertencias e incidencias | Pendiente |
| Proveedor frecuente de compras |  | Probar contrato con Solicitudes/Ordenes | Pendiente |

## 2. Maestro de proveedor

Checklist:

- [ ] Crear proveedor nuevo.
- [ ] Editar proveedor existente.
- [ ] Capturar nombre comercial.
- [ ] Capturar tipo de proveedor si aplica al maestro.
- [ ] Capturar origen.
- [ ] Revisar que el proveedor aparezca en listado.
- [ ] Filtrar por busqueda.
- [ ] Filtrar por estatus ERP.
- [ ] Filtrar por tipo proveedor.

Evidencia:

| Campo | Valor |
| --- | --- |
| Proveedor probado |  |
| Resultado esperado |  |
| Resultado real |  |
| Pendiente detectado |  |

## 2.1 Preparacion del proveedor

Objetivo:

- Usar la ficha 360 como tablero rapido para saber si el proveedor ya puede probarse con listas, matching, costos y Compras.

Checklist:

- [ ] Abrir ficha del proveedor.
- [ ] Entrar a la pestana `Preparacion`.
- [ ] Revisar porcentaje y estado: `Incompleto`, `En preparacion` o `Listo pruebas`.
- [ ] Confirmar conteos de fiscales, contactos, condiciones, documentos, listas, renglones, relaciones, costos vigentes e incidencias.
- [ ] Revisar que los puntos pendientes digan que accion sigue.
- [ ] Confirmar que la preparacion es solo lectura y no modifica datos.

Registro:

| Campo | Valor |
| --- | --- |
| Proveedor |  |
| Porcentaje preparacion |  |
| Estado mostrado |  |
| Principal pendiente |  |
| Conteo que no coincide |  |
| Resultado |  |

## 2.2 Estatus operativo del proveedor

Objetivo:

- Validar que el estatus del proveedor ayude a controlar operacion sin bloquear preparacion innecesariamente.

Estatus esperados:

| Estatus | Resultado esperado |
| --- | --- |
| `prospecto` | Permite preparar proveedor, listas y borradores |
| `en_revision` | Permite preparar proveedor, listas y borradores |
| `activo_compras` | Permite enviar orden si las partidas son validas |
| `suspendido` | Bloquea envio de orden; exige motivo al cambiar |
| `bloqueado` | Bloquea envio de orden; exige motivo al cambiar |
| `inactivo` | Bloquea envio de orden; exige motivo al cambiar |

Checklist:

- [ ] Abrir ficha del proveedor.
- [ ] Cambiar estatus a `en_revision`.
- [ ] Confirmar que no exige motivo.
- [ ] Crear o abrir orden en borrador.
- [ ] Confirmar que el borrador puede guardarse.
- [ ] Cambiar estatus a `suspendido`.
- [ ] Confirmar que exige motivo.
- [ ] Intentar enviar la orden.
- [ ] Confirmar que el servidor bloquea el envio por estatus del proveedor.
- [ ] Cambiar estatus a `activo_compras`.
- [ ] Confirmar que permite enviar si no hay otros bloqueos de orden.
- [ ] Revisar auditoria `proveedor_estatus_cambiar`.

Registro:

| Campo | Valor |
| --- | --- |
| Proveedor |  |
| Estatus inicial |  |
| Cambio a revision probado |  |
| Motivo requerido en suspendido |  |
| Orden borrador guardada |  |
| Envio bloqueado por suspendido |  |
| Envio permitido activo compras |  |
| Auditoria revisada |  |
| Resultado |  |

## 3. Datos fiscales, contactos y condiciones

Checklist:

- [ ] Agregar datos fiscales.
- [ ] Editar datos fiscales.
- [ ] Agregar contacto principal.
- [ ] Agregar contacto de compras/ordenes.
- [ ] Editar contacto.
- [ ] Agregar condiciones comerciales.
- [ ] Capturar moneda preferida.
- [ ] Capturar dias credito si aplica.
- [ ] Capturar minimo compra/unidades si aplica.
- [ ] Capturar dias entrega/surtido si aplica.

Validar:

- [ ] La ficha muestra los registros despues de guardar.
- [ ] No se duplican registros por guardar dos veces.
- [ ] Los campos obligatorios reales quedan claros.

## 4. Documentos y evidencia

Checklist:

- [ ] Registrar documento metadata.
- [ ] Subir archivo original de lista.
- [ ] Ver que el archivo quede ligado a la lista.
- [ ] Intentar subir archivo duplicado y confirmar mensaje.
- [ ] Confirmar que documentos sensibles no se muestran sin permiso.

Validar:

- [ ] El archivo queda como evidencia.
- [ ] No se pierde el nombre original.
- [ ] No se lee un archivo no soportado como si fuera tabla.

## 5. Listas de proveedor

Estados esperados:

- `borrador`: lista creada.
- `cargada`: archivo/renglones cargados.
- `en_validacion`: limpieza de renglones.
- `conciliacion`: matching contra Catalogo.
- `validada`: lista limpia como referencia.
- `aplicada`: ya genero relaciones o costos.
- `historica`: reemplazada por otra version.
- `cancelada`: no debe usarse.

Checklist:

- [ ] Crear lista nueva.
- [ ] Confirmar que inicia en `borrador`.
- [ ] Cargar archivo original.
- [ ] Confirmar que despues de importar renglones queda `cargada`.
- [ ] Revisar indicadores: renglones, relacionados, costos aplicados, sin identidad, sin costo, sin moneda.
- [ ] Intentar validar lista con pendientes y confirmar que lo bloquea con mensaje claro.
- [ ] Validar una lista solo cuando cumpla minimos.
- [ ] Marcar lista como aplicada solo despues de relacion/costo aplicado.
- [ ] Marcar lista historica cuando sea reemplazada.
- [ ] Cancelar lista de prueba si no debe usarse.

Evidencia:

| Campo | Valor |
| --- | --- |
| Proveedor |  |
| Lista/version |  |
| Archivo usado |  |
| Estatus inicial |  |
| Estatus final |  |
| Total renglones |  |
| Relacionados |  |
| Sin moneda |  |
| Sin costo |  |
| Resultado |  |

## 6. Carga de archivos de lista

Checklist XLSX/CSV:

- [ ] Subir archivo XLSX real.
- [ ] Ver vista previa.
- [ ] Confirmar encabezados detectados.
- [ ] Confirmar mapeo sugerido.
- [ ] Ajustar mapeo manualmente si hace falta.
- [ ] Importar renglones en lista vacia.
- [ ] Confirmar conteos de importacion.
- [ ] Confirmar que no aplica costos ni relaciones automaticamente.

Checklist XLS legacy:

- [ ] Subir archivo `.xls`.
- [ ] Confirmar que se guarda como evidencia.
- [ ] Confirmar que muestra aviso claro de que no se puede importar automaticamente.
- [ ] Confirmar que el aviso indica convertir a XLSX/CSV y volver a subir o asociar la version convertida.
- [ ] Confirmar que el boton `Importar renglones` queda deshabilitado.
- [ ] Convertir archivo a XLSX/CSV y repetir prueba.

Renglones basura:

- [ ] Confirmar que notas como `precios sujetos a cambio` no entren en nuevas importaciones.
- [ ] Si ya entraron, eliminarlas desde detalle.
- [ ] Confirmar que eliminar renglon no afecta Catalogo ni costos.

## 7. Renglones de lista

Checklist:

- [ ] Editar renglon importado.
- [ ] Elegir unidad compra desde selector.
- [ ] Capturar factor conversion.
- [ ] Capturar cantidad minima.
- [ ] Capturar moneda.
- [ ] Capturar si costo incluye impuestos.
- [ ] Buscar SKU ERP manualmente si matching automatico no basta.
- [ ] Seleccionar SKU ERP desde buscador.
- [ ] Guardar renglon y confirmar que queda `match_seleccionado`.

Notas:

- `ID SKU ERP`, `ID SKU proveedor` e `ID producto legacy` son campos tecnicos.
- El usuario no debe capturarlos manualmente.
- Matching, seleccion manual o migracion deben llenarlos.

Preparacion de lista:

- [ ] Abrir detalle de lista.
- [ ] Revisar badges de operativos, informativos, listo relacion, listo costo y completar compra.
- [ ] Usar filtro `Operativos` para revisar solo lo que puede afectar Compras.
- [ ] Usar filtro `Informativos` para confirmar que los productos no seleccionados quedan como evidencia y no bloquean.
- [ ] Confirmar que los renglones informativos no generen pendientes de costo/moneda/unidad.

## 8. Matching

Checklist:

- [ ] Ejecutar matching.
- [ ] Filtrar por `ambiguo`.
- [ ] Revisar renglones con varios candidatos.
- [ ] Ver todos los candidatos del renglon.
- [ ] Seleccionar el SKU correcto.
- [ ] Marcar como ambiguo si no hay seguridad.
- [ ] Marcar sin match si no existe SKU ERP.
- [ ] Rechazar candidato incorrecto.
- [ ] Confirmar que el detalle de lista refleja la decision.

Validar:

- [ ] Si hay `+N candidatos`, ahora se pueden revisar todos.
- [ ] La seleccion no crea relacion automaticamente.
- [ ] Aplicar relacion se hace con accion separada.

## 9. Aplicar relacion proveedor-SKU

Checklist individual:

- [ ] Seleccionar un renglon con SKU ERP correcto.
- [ ] Completar unidad compra.
- [ ] Completar factor.
- [ ] Completar cantidad minima.
- [ ] Aplicar relacion.
- [ ] Confirmar que el renglon queda `relacion_aplicada`.
- [ ] Confirmar que sube el indicador de relacionados.
- [ ] Confirmar que aparece como relacion proveedor-SKU.

No aplicar si:

- [ ] No estas seguro del SKU ERP.
- [ ] Falta unidad/factor.
- [ ] Falta cantidad minima.
- [ ] El producto debe revisarlo Catalogo primero.

Checklist en lote controlado:

- [ ] Abrir detalle de una lista real.
- [ ] Ejecutar `Preview relaciones`.
- [ ] Revisar incluidos y excluidos.
- [ ] Confirmar que los incluidos tienen SKU ERP confiable, unidad, factor y cantidad minima.
- [ ] Confirmar que los excluidos tienen motivo claro.
- [ ] Aplicar relaciones incluidas solo si el preview es correcto.
- [ ] Confirmar que no se aplicaron costos ni `costo_referencia`.
- [ ] Confirmar auditoria de aplicacion en lote.

Registro de prueba en lote:

| Campo | Valor |
| --- | --- |
| Proveedor |  |
| Lista |  |
| Incluidos preview |  |
| Excluidos preview |  |
| Relaciones aplicadas |  |
| Motivo si no se aplico |  |
| Resultado |  |

## 10. Costos

Checklist individual:

- [ ] Usar renglon con relacion aplicada.
- [ ] Confirmar costo positivo.
- [ ] Confirmar moneda.
- [ ] Confirmar unidad/factor.
- [ ] Aplicar costo vigente individual.
- [ ] Revisar historial de costos.
- [ ] Filtrar historial por SKU, lista, moneda u origen.
- [ ] Confirmar que el costo anterior se historiza si existia.

Checklist en lote controlado:

- [ ] Abrir detalle de lista con relaciones aplicadas.
- [ ] Ejecutar `Preview costos`.
- [ ] Revisar incluidos y excluidos.
- [ ] Confirmar que los incluidos tienen costo positivo, moneda, unidad/factor y relacion.
- [ ] Aplicar costos incluidos solo si el preview es correcto.
- [ ] Confirmar que se actualiza costo vigente/costo ultimo.
- [ ] Confirmar que no se actualiza `costo_referencia` en este paso.
- [ ] Revisar historial de costos despues de aplicar.

Checklist `costo_referencia` controlado:

- [ ] Ejecutar `Preview costo ref`.
- [ ] Revisar fuente de cada propuesta: `Compra real` o `Proveedor vigente`.
- [ ] Confirmar que costo proveedor/lista en moneda distinta de MXN queda bloqueado para `costo_referencia`.
- [ ] Confirmar que compra real en USD/EUR use el tipo de cambio de la orden y proponga costo en MXN.
- [ ] Confirmar que proveedor vigente solo llena SKUs sin costo referencia previo.
- [ ] Confirmar que compra real puede actualizar costo referencia porque viene de orden enviada/recibida.
- [ ] Confirmar que `costo_promedio_historico` se muestra como indicador calculado si aparece en la respuesta, pero no se guarda como columna formal.
- [ ] Aplicar elegibles solo si el preview coincide con lo esperado.
- [ ] Confirmar que no cambia precios de venta ni listas comerciales de Catalogo.

No hacer todavia:

- [ ] Definir proveedor preferido automaticamente.
- [ ] Convertir costos de proveedor/lista en moneda extranjera sin politica cambiaria.
- [ ] Guardar costo promedio historico como columna formal sin decision de Finanzas/Inventario.

## 11. Pendientes y resolucion

Checklist:

- [ ] Ejecutar Pendientes y resolucion.
- [ ] Filtrar por texto.
- [ ] Filtrar por severidad.
- [ ] Filtrar por tipo.
- [ ] Resolver pendiente editando renglon cuando corresponde.
- [ ] Abrir matching desde pendiente cuando corresponde.
- [ ] Enviar a Catalogo solo cuando no se pueda resolver en Proveedores.

Tipos a revisar:

- `proveedor_sku_sin_match`: no hay SKU ERP confiable; no obliga a comprar ni a completar costo/unidad. Solo se manda a Catalogo si se quiere evaluar para compra o crear SKU temporal.
- `proveedor_match_ambiguo`: hay varios posibles.
- `proveedor_unidad_factor_dudoso`: falta unidad/factor en un renglon que ya es operativo.
- `proveedor_costo_dudoso`: falta costo/moneda/impuestos en un renglon que ya es operativo.
- `proveedor_sku_sin_codigo_confiable`: Catalogo debe completar codigo.
- `proveedor_sku_fiscal_incompleto`: Catalogo debe completar fiscal.

## 12. Incidencias hacia Catalogo

Checklist:

- [ ] Crear incidencia individual desde pendiente.
- [ ] Confirmar que origen sea `proveedores`.
- [ ] Confirmar que referencia sea el renglon de lista.
- [ ] Confirmar que Catalogo puede verla.
- [ ] Confirmar que no se crean duplicadas por la misma huella.
- [ ] Para `proveedor_sku_sin_match`, crear SKU temporal desde Catalogo con unidad base explicita.
- [ ] Usar la tarjeta `Incidencias de calidad` en Catalogo.
- [ ] Abrir modal `Crear SKU temporal`.
- [ ] Confirmar que producto y SKU quedan en `borrador`.
- [ ] Confirmar que la incidencia queda ligada a `id_producto_erp` e `id_sku`.
- [ ] Volver a Proveedores y ejecutar matching para encontrar el SKU temporal.
- [ ] Confirmar que el candidato aparece con criterio `incidencia_catalogo_sku_temporal`.
- [ ] Confirmar que no se creo relacion proveedor-SKU ni costo automaticamente.

No hacer todavia:

- [ ] Crear incidencias en lote.
- [ ] Cambiar severidades sin decision.
- [ ] Bloquear Compras por incidencias sin autorizacion.

## 13. Contrato con Compras

Checklist Solicitudes:

- [ ] Buscar SKU comprable por proveedor.
- [ ] Confirmar que usa relacion proveedor-SKU.
- [ ] Confirmar que muestra evidencia del costo: `Costo vigente` o `Costo ultimo`.
- [ ] Confirmar advertencias si falta costo vigente.
- [ ] Confirmar advertencias si falta unidad/factor.
- [ ] Confirmar que no bloquea innecesariamente.

Checklist Ordenes:

- [ ] Buscar SKU comprable por proveedor.
- [ ] Crear orden con producto relacionado.
- [ ] Confirmar que la partida muestra evidencia del costo: origen, moneda y vigencia si existen.
- [ ] Confirmar snapshot de costo, moneda, tipo de cambio e impuesto.
- [ ] Guardar orden en borrador.
- [ ] Reabrir orden y confirmar que la evidencia de costo se conserva.
- [ ] Enviar orden con advertencias.
- [ ] Confirmar que pide confirmacion si falta costo vigente o unidad/factor.
- [ ] Intentar enviar orden con costo unitario cero y confirmar que el servidor la bloquea.
- [ ] Intentar enviar orden en USD/EUR sin tipo de cambio positivo y confirmar que el servidor la bloquea.
- [ ] Intentar enviar orden con total cero y confirmar que el servidor la bloquea.
- [ ] Confirmar que el mensaje final muestra advertencias devueltas por servidor si existen.
- [ ] Confirmar auditoria `compras/orden_advertencias_operativas` cuando se envia con advertencias.
- [ ] Reabrir/ver orden enviada y confirmar que la evidencia de costo sigue igual aunque despues cambie el costo vigente del proveedor.
- [ ] Confirmar que al enviar la orden se consolida `costo_referencia` desde el costo comprometido de la orden, no desde recepcion de almacen.

Bloqueos minimos esperados al enviar orden:

- Moneda invalida.
- USD/EUR sin tipo de cambio positivo.
- Total menor o igual a cero.
- Partida con costo unitario menor o igual a cero.
- SKU ERP que no pertenezca al proveedor o relacion proveedor-SKU no activa.

Advertencias que no deben bloquear por ahora:

- Sin costo vigente autorizado, si la orden trae costo capturado.
- Unidad/factor incompleto.
- Fiscal SKU incompleto.

Validacion de snapshot de evidencia de costo:

| Campo | Valor |
| --- | --- |
| Proveedor probado |  |
| Orden de compra |  |
| SKU ERP |  |
| SKU proveedor |  |
| Costo mostrado antes de guardar |  |
| Fuente mostrada (`Costo vigente`/`Costo ultimo`) |  |
| Moneda mostrada |  |
| Vigencia mostrada |  |
| Evidencia conservada al reabrir borrador |  |
| Evidencia conservada al ver orden enviada |  |
| Advertencias servidor mostradas |  |
| Auditoria advertencias registrada |  |
| Bloqueo costo cero probado |  |
| Bloqueo moneda/tipo cambio probado |  |
| Resultado |  |

Preguntas a observar:

- Si los bloqueos minimos son suficientes o demasiado estrictos.
- Que advertencias solo deben pedir confirmacion.
- Que advertencias solo deben informar.
- Si el costo de referencia calculado al enviar coincide con lo que operacion considera el costo real comprometido.

## 14. Matriz de prueba rapida

| Caso | Proveedor | Lista | Resultado esperado | Resultado real | Estado |
| --- | --- | --- | --- | --- | --- |
| Crear lista |  |  | Estado borrador |  | Pendiente |
| Cargar XLSX |  |  | Vista previa y mapeo |  | Pendiente |
| Importar XLSX |  |  | Renglones cargados |  | Pendiente |
| Cargar XLS | SUNNY |  | Mensaje convertir XLSX/CSV |  | Pendiente |
| Eliminar basura |  |  | Renglon eliminado sin afectar Catalogo |  | Pendiente |
| Matching ambiguo |  |  | Ver todos los candidatos |  | Pendiente |
| Seleccion manual SKU |  |  | Match seleccionado |  | Pendiente |
| Aplicar relacion |  |  | Relacion aplicada |  | Pendiente |
| Aplicar relaciones lote |  |  | Preview revisado y relaciones aplicadas sin costos |  | Pendiente |
| Aplicar costo |  |  | Costo vigente |  | Pendiente |
| Aplicar costos lote |  |  | Preview revisado y costos vigentes aplicados sin costo_referencia |  | Pendiente |
| Preview costo ref |  |  | Elegibles/bloqueados claros por fuente y moneda |  | Pendiente |
| Pendiente Catalogo |  |  | Incidencia origen proveedores |  | Pendiente |
| Solicitud compra |  |  | Advertencias correctas |  | Pendiente |
| Orden compra |  |  | Confirmacion si falta dato critico |  | Pendiente |
| Evidencia costo orden |  |  | Snapshot conservado al reabrir/ver orden |  | Pendiente |
| Costo referencia al enviar |  |  | Se actualiza desde costo comprometido de la orden |  | Pendiente |

## 15. Incidencias reales de Proveedores

Objetivo:

- Validar que una incidencia enviada a Catalogo desde Proveedores se pueda cerrar con motivo sin perder evidencia.

Checklist:

- [ ] Abrir proveedor real.
- [ ] Abrir lista real.
- [ ] Entrar a `Resolver pendientes`.
- [ ] Crear una incidencia individual desde una propuesta que realmente requiera Catalogo.
- [ ] Volver a abrir `Resolver pendientes`.
- [ ] Confirmar que aparece como `Incidencia real`.
- [ ] Probar filtro por texto/SKU/tipo/severidad.
- [ ] Resolver una incidencia con motivo.
- [ ] Confirmar que queda `resuelta` y conserva evidencia.
- [ ] Descartar otra incidencia con motivo cuando no aplica.
- [ ] Confirmar que queda `descartada` y conserva evidencia.
- [ ] Revisar auditoria `proveedor_incidencia_resolver`.
- [ ] Confirmar que no se crearon relaciones, costos ni cambios de Catalogo automaticamente.

Registro:

| Campo | Valor |
| --- | --- |
| Proveedor probado |  |
| Lista probada |  |
| Incidencia creada |  |
| Incidencia resuelta |  |
| Motivo usado |  |
| Incidencia descartada |  |
| Motivo usado |  |
| Auditoria revisada |  |
| Resultado |  |

## 16. Registro de hallazgos

Usar esta tabla cada vez que una prueba revele algo:

| Fecha | Proveedor/lista | Pantalla | Que paso | Impacto | Decision requerida | Estado |
| --- | --- | --- | --- | --- | --- | --- |
| 2026-06-15 | SUNNY / lista `.xls` legacy | Proveedores > Listas > Vista previa archivo | El archivo `.xls` binario legacy se guarda como evidencia, pero no se puede mapear/importar automaticamente en esta etapa. | No permite cargar renglones hasta convertir el archivo a `.xlsx` o `CSV`; no afecta Catalogo, costos ni Compras. | Definir despues si se integra lector `.xls` moderno o si la politica operativa sera convertir archivos legacy antes de importar. | Abierta para prueba real |

## 17. Pendientes para seguir construyendo

- Mejorar lectura de archivos `.xls` legacy con libreria moderna o politica de conversion.
- Probar aplicacion masiva de relaciones con casos reales antes de ampliar excepciones.
- Probar aplicacion masiva de costos con casos reales antes de ampliar reglas.
- Probar politica de moneda/tipo de cambio para `costo_referencia` con compra real en moneda extranjera.
- Decidir despues con Finanzas/Inventario si se guarda costo promedio historico como dato formal o se mantiene como reporte calculado.
- Definir estatus finales del proveedor y si bloquean busqueda, solicitud u orden.
- Probar reporte de preparacion por proveedor/lista con datos reales y ajustar mensajes si confunden.

## 18. Pantallas legacy

Objetivo:

- Confirmar que las pantallas viejas no se confunden con el flujo ERP nuevo durante pruebas reales.

Checklist:

- [ ] Abrir `Compras > Proveedores legacy`.
- [ ] Confirmar que aparece aviso de pantalla legacy/historica.
- [ ] Abrir una lista legacy.
- [ ] Confirmar que aparece el mismo aviso.
- [ ] Abrir una pantalla de pedidos legacy si aplica.
- [ ] Confirmar que aparece el mismo aviso.
- [ ] Confirmar que el aviso no bloquea la consulta ni cambia datos.
- [ ] Confirmar que el flujo nuevo sigue visible en `ERP > Proveedores > Maestro proveedores`.

Registro:

| Campo | Valor |
| --- | --- |
| Pantalla legacy probada |  |
| Aviso visible |  |
| Texto entendible |  |
| Consulta legacy sigue funcionando |  |
| Flujo nuevo ubicado |  |
| Resultado |  |

## 19. Preflight migracion productivo P9-08

Objetivo:

- Revisar si conviene autorizar una nueva migracion por lote desde `db/productivo`, sin escribir datos todavia.

Checklist:

- [ ] Abrir `ERP > Proveedores > Auditoria proveedores`.
- [ ] Revisar `Productivo legacy SQL`.
- [ ] Confirmar que existen los tres archivos: proveedores, listas y renglones.
- [ ] Revisar `Vista previa de migracion`.
- [ ] Revisar `Lotes staging cargados`.
- [ ] Revisar `Preflight P9-08 migracion por lotes`.
- [ ] Confirmar candidatos nuevos.
- [ ] Confirmar registros existentes/ya migrados.
- [ ] Confirmar registros que requieren revision.
- [ ] Leer riesgos antes de autorizar cualquier escritura.
- [ ] Confirmar que no se aplicaron costos, relaciones, Catalogo ni Compras.

Registro:

| Campo | Valor |
| --- | --- |
| Estado preflight |  |
| Candidatos nuevos |  |
| Ya migrados/existentes |  |
| Requieren revision |  |
| Riesgo principal |  |
| Autorizar respaldo externo |  |
| Autorizar staging nuevo |  |
| Resultado |  |

Evidencia de lote P9-08 cargado:

| Campo | Valor |
| --- | --- |
| Respaldo externo | `C:\Users\aleja\Documents\Respaldos_panel_bd\panel_bd_20260615_002210.sql` |
| Lote staging | `productivo_sql_20260615_082236` |
| Proveedores staging | 23 |
| Listas staging | 10 |
| Renglones existentes | 9278 |
| Renglones a revisar | 2 |
| Pendientes | `OCL-28A`, `OCL-28W` por costo no positivo |
| Conversion oficial | No ejecutada |

Cierre posterior:

| Campo | Valor |
| --- | --- |
| Pendientes staging abiertos | 0 |
| `OCL-28A` | `historico_no_utilizable` |
| `OCL-28W` | `historico_no_utilizable` |
| Motivo | Costo no positivo; no usar para costos, Compras, matching automatico ni `costo_referencia` |

## 20. Flujo operativo recomendado para cerrar Proveedores

Objetivo:

- Validar el modulo completo con una lista real antes de pasar a otro modulo.
- Detectar ajustes de UX, datos o reglas sin inventar politicas nuevas.

### 20.1 Lista real de proveedor

Checklist:

- [ ] Abrir `ERP > Proveedores > Maestro proveedores`.
- [ ] Seleccionar un proveedor real con lista cargada.
- [ ] Abrir detalle de lista.
- [ ] Confirmar renglones operativos vs informativos.
- [ ] Confirmar que renglones informativos no bloquean operacion.
- [ ] Revisar filtros de lista: operativos, informativos, sin match, listo relacion, listo costo.
- [ ] Revisar matching exacto, ambiguo y sin match.
- [ ] Confirmar que el operador entiende que IDs tecnicos no se capturan manualmente.

Registro:

| Campo | Valor |
| --- | --- |
| Proveedor |  |
| Lista |  |
| Renglones operativos |  |
| Renglones informativos |  |
| Matching claro |  |
| Ajuste UX detectado |  |
| Resultado |  |

### 20.2 Relacion proveedor-SKU

Checklist:

- [ ] Abrir `Matching dry-run`.
- [ ] Revisar conteo de `Seleccionar confiables`.
- [ ] Ejecutar `Seleccionar confiables` solo si el conteo corresponde a matches exactos o relaciones activas.
- [ ] Confirmar que los renglones elegibles quedan en `match_seleccionado`.
- [ ] Confirmar que `ambiguo`, `sin_match` y `match_posible` quedan para revision manual.
- [ ] Aplicar una relacion individual proveedor-SKU con un producto seguro.
- [ ] Confirmar unidad de compra.
- [ ] Confirmar factor de conversion.
- [ ] Confirmar cantidad minima.
- [ ] Confirmar que no se aplican costos en este paso.
- [ ] Ejecutar `Preview relaciones`.
- [ ] Revisar incluidos y excluidos.
- [ ] Aplicar lote solo si el preview es confiable.
- [ ] Confirmar auditoria y conteos.

Registro:

| Campo | Valor |
| --- | --- |
| Matching masivo confiable probado |  |
| Renglones seleccionados masivamente |  |
| Ambiguos/posibles conservados para revision |  |
| Relacion individual probada |  |
| Preview lote revisado |  |
| Relaciones aplicadas |  |
| Excluidos correctos |  |
| Auditoria revisada |  |
| Resultado |  |

### 20.3 Costos de proveedor

Checklist:

- [ ] Aplicar costo individual desde lista relacionada.
- [ ] Confirmar que se historiza costo anterior si existia.
- [ ] Confirmar que `erp_catalogo_sku_proveedores.costo_ultimo` queda como referencia operativa.
- [ ] Confirmar que no cambia `costo_referencia`.
- [ ] Ejecutar `Preview costos`.
- [ ] Aplicar lote solo si los incluidos tienen costo, moneda, unidad/factor y relacion.
- [ ] Revisar historial de costos despues de aplicar.

Registro:

| Campo | Valor |
| --- | --- |
| Costo individual aplicado |  |
| Historial correcto |  |
| Preview costos revisado |  |
| Costos lote aplicados |  |
| `costo_referencia` sin cambio indebido |  |
| Resultado |  |

### 20.4 Compras con proveedor real

Checklist:

- [ ] Crear solicitud u orden con un SKU relacionado.
- [ ] Confirmar que la busqueda usa contrato de Proveedores.
- [ ] Confirmar evidencia de costo: origen, moneda y vigencia.
- [ ] Guardar orden en borrador.
- [ ] Reabrir y confirmar snapshot.
- [ ] Probar bloqueo por costo unitario cero.
- [ ] Probar bloqueo por moneda invalida o moneda extranjera sin tipo de cambio.
- [ ] Probar bloqueo por proveedor suspendido/bloqueado/inactivo.
- [ ] Probar bloqueo por relacion proveedor-SKU no activa.
- [ ] Confirmar advertencias no bloqueantes para datos mejorables.

Registro:

| Campo | Valor |
| --- | --- |
| Solicitud/orden |  |
| SKU probado |  |
| Evidencia costo visible |  |
| Snapshot conservado |  |
| Bloqueos minimos correctos |  |
| Advertencias correctas |  |
| Resultado |  |

### 20.5 `costo_referencia`

Checklist:

- [ ] Ejecutar `Preview costo ref`.
- [ ] Confirmar elegibles y bloqueados.
- [ ] Confirmar que proveedor vigente solo propone costo inicial si no hay `costo_referencia`.
- [ ] Confirmar que compra real tiene prioridad si existe.
- [ ] Confirmar que moneda proveedor/lista no MXN queda bloqueada hasta politica cambiaria.
- [ ] Aplicar solo elegibles.
- [ ] Confirmar que no cambia precios de venta ni listas comerciales de Catalogo.

Registro:

| Campo | Valor |
| --- | --- |
| Preview ejecutado |  |
| Elegibles |  |
| Bloqueados |  |
| Aplicados |  |
| Moneda extranjera revisada |  |
| Resultado |  |
