# ERP Proveedores - Avance y evidencia del modulo

Documentacion viva: Codex GPT-5  
Fecha base: 2026-06-11  
Ultima actualizacion: 2026-06-14
Estado: Modulo Proveedores ERP con maestro/listas/matching/costos/incidencias base implementados; migracion productivo convertida a listas borrador y evidencia.

## Lectura obligatoria

- `AGENTS.md`.
- `docs/erp_plan_maestro_fundamentos.md`.
- `docs/erp_ux_operativa.md`.
- `docs/ia_uso_modelos.md`.
- `docs/erp_catalogo_avance.md`.
- Este archivo.

## Proposito

Construir el modulo de Proveedores como el puente formal entre Catalogo y Compras.

El objetivo es cubrir maestro de proveedor, datos fiscales/comerciales, contactos, condiciones, listas de proveedor, relacion SKU proveedor, costos, unidades, evidencias, permisos y contrato con Compras.

Las listas de proveedor son una seccion critica porque ayudan a aprovechar costos y productos existentes sin recapturar todo, pero no son el modulo completo.

## Principios vigentes

- Proveedores administra identidad del proveedor, contactos, condiciones, listas, evidencia de costos y relacion SKU proveedor.
- Catalogo administra producto maestro, SKU ERP, fiscal, reglas, imagenes, codigos e incidencias de calidad.
- Compras consume relaciones activas y confiables; no debe crear relaciones o productos a ciegas.
- Una lista de proveedor no debe crear productos duplicados automaticamente.
- Un producto proveedor sin match debe generar incidencia o pendiente de conciliacion.
- Cualquier escritura masiva sobre datos reales requiere autorizacion explicita del dueno.
- Se puede reutilizar informacion existente de proveedores, listas, costos e historicos cuando sea util y confiable.
- No se debe heredar la estructura anterior como limite del ERP nuevo: el objetivo es un modulo robusto, auditable y escalable.
- La estructura nueva debe permitir delegar tareas por departamento con permisos, responsabilidades y auditoria claras.
- Migrar valor no significa migrar deuda tecnica: los datos heredados son insumo/evidencia, no necesariamente el modelo final.

## Archivos base detectados

- Controlador: `app/controladores/Proveedor.php`.
- Modelo legado/principal actual: `app/modelos/Proveedores.php`.
- Catalogo ERP relacionado: `app/controladores/CatalogoErp.php`, `app/modelos/CatalogoErpDatos.php`, `app/modelos/CatalogoErpEsquema.php`.
- Compras relacionado: `app/controladores/Compra.php`, modelos ERP de solicitudes, ordenes, XML y adjuntos.
- Vistas Proveedores: `app/vistas/paginas/apps/erp/proveedores`.
- JS Proveedores: `public/assets/js/custom/apps/erp/proveedores`.

No abrir archivos grandes sin buscar antes endpoint, metodo o vista exacta.

## Orden recomendado de implementacion

1. Esquema y auditoria tecnica.
2. Separacion legado vs ERP nuevo.
3. Proveedores maestros.
4. UI y permisos base.
5. Listas de proveedor.
6. Matching SKU proveedor contra Catalogo.
7. Costos, unidades, factores y moneda.
8. Incidencias hacia Catalogo.
9. Contrato con Compras.
10. Cierre y evidencia.

## Estado actual y siguiente paso

Estado actual: seguridad `proveedores.*` sembrada, esquema operativo base aplicado, auditoria de contrato limpia, ficha ERP operativa, listas versionadas disponibles y migracion selectiva desde `db/productivo` convertida como borrador/evidencia.

Siguiente paso: revisar los datos migrados en pantalla, resolver pendientes de calidad de lista y decidir el siguiente bloque operativo antes de aplicar relaciones o costos masivos.

Motivo:

- Ya quedo definido que se reutiliza informacion heredada como insumo/evidencia, no como modelo final obligatorio.
- Ya quedo definido el maestro robusto de proveedor: identidad, fiscales, comerciales, contactos, condiciones, estatus y auditoria.
- Ya quedo definida una matriz inicial de permisos propios y responsabilidades por departamento.
- Los permisos `proveedores.*` ya fueron agregados en codigo y sembrados en BD local.
- Los endpoints tecnicos nuevos ya usan `proveedores.auditoria` sin fallback a `compras.ver`.
- El esquema base ya existe para raiz puente, perfil, fiscales, contactos, condiciones, documentos, listas ERP, detalle de listas y costos proveedor-SKU.
- Ya quedo definido que las listas son evidencia formal/versionada y no actualizan Catalogo ni Compras sin conciliacion y autorizacion.
- El matching ya quedo definido como propuesta/revision con evidencia, sin crear productos ni relaciones automaticamente.
- Costos/unidades ya quedo definido: Compras guarda snapshot, pero Proveedores necesita moneda, vigencia y evidencia antes de ser fuente confiable.
- Incidencias hacia Catalogo ya quedo definido: reusar `erp_catalogo_incidencias_calidad`, pero falta permitir origen `proveedores`.
- Contrato con Compras ya quedo definido: Compras consume relaciones activas y guarda snapshot; Proveedores/Catalogo resuelven maestro y pendientes.
- El cierre documental ya quedo consolidado en este archivo.
- La lectura operativa del maestro ya existe por modelo/endpoints y pantalla. El siguiente paso recomendado es validar UI con datos reales antes de abrir escritura.

## Evidencia consolidada - esquema y flujo real

Tablas detectadas relacionadas con Proveedores:

- `erp_proveedores`: maestro minimo actual con `id_proveedor`, `proveedor`, `cuota`. Sirve como fuente de identidad basica, pero no cubre maestro robusto ERP.
- `erp_proveedores_listas`: encabezado simple de listas por proveedor con `id_lista_proveedor`, `id_proveedor`, `lista`, `estatus`, `fch_r`.
- `erp_proveedores_listas_productos`: detalle legado de lista con datos utiles como marca, codigos, SKU, existencia, nombre, descripcion, costo, precio sugerido, piezas por caja y rotacion.
- `erp_proveedores_listas_productos_historial_costos`: historico de costos asociado a listas/productos.
- `erp_proveedores_listas_productos_imagenes`: evidencia visual/importada de productos de proveedor.
- `erp_proveedores_listas_productos_revision`: revision/incidencias sobre productos de listas.
- `erp_proveedores_pedidos`: flujo legado de pedidos/proveedor.
- `ecom_productos_proveedores`: relacion heredada ecommerce/proveedor.
- `erp_catalogo_sku_proveedores`: relacion nueva mas cercana al contrato ERP entre SKU ERP y proveedor. Contiene `id_sku`, `id_proveedor`, `sku_proveedor`, `id_unidad_compra`, `factor_conversion`, `costo_ultimo`, `cantidad_minima`, `dias_entrega`, `es_preferido`, `estatus` y fechas.

Hallazgos importantes:

- Existe maestro de proveedor, pero es minimo y no tiene fiscales, contactos, condiciones, documentos, responsables ni auditoria operativa suficiente.
- Existen listas y detalles con datos aprovechables, pero el formato es legado y no conserva todo lo necesario para ERP robusto: moneda, vigencia, evidencia formal, estado de conciliacion, version y aprobacion.
- La relacion moderna SKU ERP/proveedor ya existe en `erp_catalogo_sku_proveedores`; debe ser tratada como contrato candidato del ERP nuevo.
- Catalogo ya toca costos/SKU proveedor con permisos `catalogo.costos`; Proveedores nuevo necesita permisos propios o una transicion documentada.
- El importador/flujo legado de listas no debe asumirse como base final: contiene comportamiento tecnico heredado y requiere redisenio antes de operar como ERP.
- No se detecto evidencia suficiente de datos fiscales/comerciales robustos, contactos, condiciones de pago, condiciones logisticas, documentos de proveedor ni autorizaciones por departamento.

## Evidencia consolidada - legado a ERP

Decision documentada: se reutiliza la informacion heredada de proveedores, listas, costos e historicos cuando sea util, pero no se hereda la estructura anterior como limite del ERP nuevo.

Clasificacion:

- Reutilizar como dato base: nombre de proveedor, registros existentes, SKUs proveedor, codigos, costos, existencias, piezas por caja, rotacion, historial e imagenes/evidencias si son confiables.
- Reutilizar como evidencia historica: listas antiguas, pedidos antiguos, historiales de costos, revisiones y relaciones ecommerce.
- Convertir a contrato ERP: relaciones confiables entre `id_sku`, `id_proveedor`, `sku_proveedor`, unidad, factor, costo, moneda, vigencia y evidencia.
- No usar como regla nueva sin autorizacion: `cuota`, estados legados, borrados/reemplazos automaticos, aplicacion automatica de costos y creacion automatica de productos.

Regla de transicion:

- Lo legado puede alimentar conciliacion.
- La conciliacion genera relaciones ERP auditables.
- Las relaciones ERP alimentan Compras.
- Catalogo conserva la autoridad sobre producto/SKU ERP.

## Evidencia consolidada - maestro de proveedores

El maestro robusto debe permitir operar por etapas y departamentos, sin obligar a que todo exista desde el alta inicial.

Estados propuestos para validar con negocio antes de implementar:

- `borrador`: captura inicial incompleta.
- `en_revision`: datos enviados para validacion.
- `activo_listas`: puede cargar y conciliar listas.
- `activo_compras`: puede usarse en ordenes de compra.
- `activo_pagos`: validado para flujo financiero.
- `suspendido`: bloqueado temporalmente para nuevas operaciones.
- `historico`: conserva evidencia sin operacion activa.
- `bloqueado`: no debe operar por decision administrativa.

Bloques funcionales del maestro:

- Datos generales: razon/nombre comercial, estatus, tipo, notas y origen.
- Datos fiscales: RFC, razon social, regimen, domicilio fiscal, uso fiscal si aplica y constancia/evidencia.
- Contactos: compras, ventas, cobranza, logistica y soporte.
- Condiciones comerciales: credito, dias, forma/metodo de pago, moneda preferida, minimo de compra, tiempos de entrega.
- Condiciones logisticas: cobertura, entregas, empaque, restricciones y tiempos.
- Documentos/evidencias: constancia fiscal, contratos, listas, archivos de soporte.
- Auditoria: quien captura, revisa, autoriza, suspende o reactiva.

Pendientes de decision del maestro:

- Si `cuota` se conserva, se renombra o se migra a condiciones comerciales.
- Que datos fiscales son obligatorios para `activo_compras` y cuales para `activo_pagos`.
- Que departamento autoriza cada estado.
- Si bancos/cuentas se manejan en Proveedores o en Finanzas.
- Si conviene extender `erp_proveedores` o crear tablas satelite ligadas al proveedor actual.

## Evidencia consolidada - UI y permisos

Estado actual:

- Los endpoints legacy de `Proveedor.php` siguen usando permisos de Compras como `compras.ver`, `compras.crear` y `compras.editar` hasta que exista reemplazo ERP nuevo.
- Los endpoints tecnicos nuevos de Proveedores usan `proveedores.auditoria`.
- Catalogo usa `catalogo.costos` para relaciones costo/SKU proveedor mientras se construyen endpoints propios de Proveedores.
- Ya existe set propio `proveedores.*` en codigo y BD local.

Vistas/areas necesarias:

- Listado de proveedores.
- Ficha 360 de proveedor.
- Alta/edicion por secciones.
- Contactos y condiciones.
- Listas de proveedor.
- Conciliacion SKU proveedor contra SKU ERP.
- Incidencias.
- Evidencias/documentos.
- Auditoria.

Permisos candidatos, sujetos a validacion antes de implementar:

- `proveedores.ver`.
- `proveedores.crear`.
- `proveedores.editar`.
- `proveedores.contactos`.
- `proveedores.condiciones`.
- `proveedores.fiscales`.
- `proveedores.finanzas`.
- `proveedores.documentos`.
- `proveedores.listas`.
- `proveedores.matching`.
- `proveedores.costos`.
- `proveedores.autorizar`.
- `proveedores.auditoria`.

Regla de transicion:

- Mantener flujo legado protegido como legado mientras se construye el modulo nuevo.
- No mezclar endpoints nuevos de Proveedores con permisos de Compras salvo transicion documentada.
- Registrar auditoria explicita en acciones sensibles: crear/editar proveedor, cambiar estatus, cargar lista, conciliar SKU, aplicar costo, subir/cancelar evidencia y autorizar proveedor.

## Evidencia consolidada - listas de proveedor

Las listas de proveedor deben funcionar como evidencia formal de costos, unidades, presentacion, moneda y vigencia. No deben actualizar Catalogo ni Compras automaticamente.

Datos heredados aprovechables:

- Proveedor y nombre de lista.
- SKU/codigo interno/codigo de barras de proveedor.
- Marca, nombre y descripcion.
- Costo, precio sugerido, existencia, piezas por caja y rotacion.
- Historial de costos, imagenes y revisiones cuando existan.

Faltantes para ERP robusto:

- Moneda y, si aplica, tipo de cambio de referencia.
- Vigencia desde/hasta.
- Fecha de emision de lista.
- Archivo original/evidencia y hash o identificador de carga.
- Version de lista.
- Usuario responsable de carga y validacion.
- Estado de conciliacion del encabezado y de cada partida.
- Relacion explicita a SKU ERP y a relacion SKU proveedor aprobada.
- Unidad de compra normalizada y factor de conversion.
- Indicador claro de costo con/sin impuestos.

Encabezado recomendado:

- Proveedor.
- Folio/version/nombre de lista.
- Origen: legado, Excel, portal, manual, XML u otro origen real validado.
- Moneda.
- Tipo de cambio, solo si negocio lo autoriza.
- Vigencia desde/hasta.
- Fecha de emision y fecha de carga.
- Archivo/evidencia.
- Usuario que carga y usuario que valida/aprueba.
- Estatus.
- Observaciones.
- Liga a lista legacy si viene de migracion.

Detalle recomendado:

- SKU proveedor.
- Descripcion proveedor.
- Marca proveedor.
- Codigo de barras y codigo interno.
- Unidad de compra capturada y unidad normalizada.
- Factor de conversion.
- Cantidad minima.
- Piezas por caja/empaque.
- Costo.
- Indicador de impuestos incluidos.
- Moneda.
- Existencia reportada por proveedor.
- Rotacion u otro dato heredado como referencia.
- SKU ERP relacionado, si ya fue conciliado.
- Relacion SKU proveedor aprobada, si ya existe.
- Estado de match.
- Criterio de match.
- Observaciones/incidencias.
- Liga a renglon legacy si viene de migracion.

Estados propuestos del encabezado:

- `cargada`.
- `en_validacion`.
- `en_conciliacion`.
- `con_incidencias`.
- `lista_para_aplicar`.
- `aplicada_parcial`.
- `aplicada`.
- `descartada`.
- `historica`.
- `vencida`.

Estados propuestos del detalle:

- `pendiente`.
- `match_exacto_pendiente`.
- `match_posible`.
- `relacionado`.
- `rechazado`.
- `sin_match`.
- `aplicado`.
- `descartado`.

Flujo recomendado:

1. Cargar lista como evidencia, sin afectar Catalogo ni Compras.
2. Normalizar columnas y capturar metadatos minimos.
3. Validar proveedor, moneda, vigencia, archivo, costo y renglones basicos.
4. Crear version historica, sin reemplazar silenciosamente listas anteriores.
5. Enviar renglones a conciliacion SKU proveedor contra SKU ERP.
6. Aplicar solo relaciones/costos aceptados y autorizados.
7. Dejar incidencias accionables para lo no conciliado.

Permisos relacionados:

- `proveedores.listas`: cargar y administrar listas.
- `proveedores.matching`: conciliar renglones contra SKU ERP.
- `proveedores.costos`: aplicar costos autorizados.
- `proveedores.autorizar`: aprobar lista oficial o aplicacion.
- `proveedores.auditoria`: consultar trazabilidad.

Incidencias esperadas:

- SKU proveedor vacio.
- Costo cero, negativo o invalido.
- Moneda faltante o no reconocida.
- Unidad desconocida.
- Factor faltante o dudoso.
- Duplicado dentro de la misma lista.
- Match multiple contra Catalogo.
- Sin match contra SKU ERP.
- Lista vencida.
- Evidencia faltante.

Decisiones pendientes para listas:

- Moneda obligatoria y moneda por defecto, si existe.
- Si el costo capturado incluye impuestos o debe separarse.
- Quien autoriza que una lista sea vigente/oficial.
- Politica de conservacion de archivo original.
- Si se permite lista sin vigencia.
- Si unidad/factor son obligatorios desde lista o hasta relacion SKU proveedor.

## Evidencia consolidada - matching SKU proveedor

Objetivo: relacionar productos/renglones de proveedor con SKU ERP sin crear duplicados ni perder evidencia.

Flujo real detectado:

- `Proveedores.php::listar_busqueda_productos()` busca primero en `erp_proveedores_listas_productos` por codigo de barras, codigo interno, SKU, nombre o descripcion dentro de una lista; despues agrega relaciones ERP existentes desde `erp_catalogo_sku_proveedores` para el proveedor de esa lista.
- `CatalogoErp.php` expone endpoints relacionados: `relaciones_proveedor_historicas`, `relaciones_proveedor_sincronizar`, `propuestas_costos_proveedor`, `propuestas_costos_aplicar` y `guardar_sku_proveedor`.
- Los endpoints que crean/sincronizan relaciones usan permiso actual `catalogo.costos`, no `proveedores.matching`.
- `CatalogoErpDatos::listarRelacionesProveedorHistoricas()` ya distingue coincidencias exactas por SKU, posibles coincidencias por nombre y productos sin coincidencia.
- `CatalogoErpDatos::sincronizarRelacionesProveedorHistoricas()` crea/actualiza `erp_catalogo_sku_proveedores` desde coincidencias exactas por SKU; tambien actualiza `costo_referencia` cuando hay proveedor preferido activo. Esto es escritura operativa y no debe ejecutarse sin autorizacion.
- `CatalogoErpDatos::guardarSkuProveedor()` guarda relacion SKU/proveedor con proveedor, unidad de compra, factor, costo, minimo, dias entrega, preferido y estatus.
- Compras consume solo relaciones activas de `erp_catalogo_sku_proveedores`: `SolicitudesCompraErp::buscarSkus()` y `OrdenesCompraErp::buscarSkus()` filtran por proveedor activo, SKU activo, `sp.estatus='activo'`, SKU ERP/nombre/SKU proveedor/codigos.
- XML de compras concilia conceptos contra detalle de orden por SKU o `sku_proveedor`; si hay multiples coincidencias devuelve resultado ambiguo.

Tipos de match documentados:

- Exacto: SKU de lista proveedor igual a SKU ERP o relacion SKU proveedor ya activa. Puede proponerse para revision, pero aplicar la relacion sigue requiriendo autorizacion.
- Posible: nombre, codigo, historial o descripcion sugieren coincidencia, pero requiere revision manual.
- Sin match: no hay SKU ERP confiable; debe generar pendiente/incidencia, no producto automatico.
- Rechazado: propuesta descartada con motivo y evidencia.
- Ambiguo: hay mas de una coincidencia posible; no debe aplicarse sin seleccion humana.

Prioridad recomendada para propuestas:

1. Relacion activa existente en `erp_catalogo_sku_proveedores`.
2. SKU proveedor exacto previamente validado para ese proveedor.
3. Codigo de barras/codigo principal activo en Catalogo.
4. SKU ERP exacto.
5. Coincidencia historica por lista/proveedor/ecommerce.
6. Nombre normalizado solo como candidato de revision.

Reglas de control:

- El match por nombre nunca debe crear producto, SKU ni relacion automaticamente.
- Una coincidencia historica ayuda a priorizar revision, pero no sustituye una relacion ERP validada.
- La pantalla debe mostrar evidencia antes de aplicar: proveedor, lista, renglon, SKU proveedor, nombre proveedor, costo, codigo, SKU ERP candidato, nombre ERP, unidad, historial y motivo del match.
- Crear o actualizar `erp_catalogo_sku_proveedores` requiere autorizacion explicita.
- Si no hay match confiable, el resultado debe convertirse en incidencia accionable hacia Catalogo o pendiente de Proveedores.
- La relacion aplicada debe registrar usuario, fecha, origen/evidencia y estado anterior/posterior.

Faltantes del ERP nuevo:

- Bandeja propia de matching en Proveedores.
- Permiso propio `proveedores.matching` y regla de transicion con `catalogo.costos`.
- Tabla o bitacora de propuestas/decisiones de matching, si se necesita conservar decision por renglon de lista.
- Estado de conciliacion por detalle de lista.
- Enlace claro entre evidencia de lista y relacion `erp_catalogo_sku_proveedores`.
- Motivos de rechazo/ambiguedad estandarizados.

Decisiones pendientes para matching:

- Si el permiso final para aplicar relaciones sera `proveedores.matching`, `proveedores.costos`, `catalogo.costos` o combinacion por etapa.
- Si las coincidencias exactas por SKU se pueden aprobar en lote o siempre deben revisarse una por una.
- Si el match por codigo de barras sera suficiente para propuesta exacta o solo posible.
- Donde vive la bitacora de decisiones: tabla nueva de Proveedores, incidencias de Catalogo o auditoria general.
- Que departamento resuelve ambiguedades: Catalogo, Compras o Proveedores.

## Evidencia consolidada - costos, unidades, factores y moneda

Objetivo: definir como se guardan y actualizan costos de proveedor sin contaminar inventario ni compras ya registradas.

Campos actuales detectados:

- `erp_catalogo_skus`: `id_unidad_base`, `factor_unidad_base`, `costo_referencia`. Este costo es referencia del SKU ERP, no evidencia formal de proveedor.
- `erp_catalogo_sku_proveedores`: `id_unidad_compra`, `factor_conversion`, `costo_ultimo`, `cantidad_minima`, `dias_entrega`, `es_preferido`, `estatus`. Es el contrato tecnico actual SKU/proveedor, pero no guarda moneda, vigencia, evidencia, origen de lista ni si el costo incluye impuestos.
- `erp_catalogo_sku_precios`: tiene `moneda`, `precio`, vigencias y lista de precio, pero corresponde a precio de venta/listas de precio, no costo proveedor.
- `erp_catalogo_sku_impuestos`: guarda fiscal del SKU (`iva_porcentaje`, `ieps_porcentaje`, `incluye_impuestos`), pero no resuelve si el costo de proveedor/lista viene con impuestos incluidos.
- `erp_proveedores_listas_productos`: legado con `costo`, `precio_sugerido`, `piezas_por_caja`, `incluye_impuesto` y otros datos utiles. No conserva moneda, vigencia formal, version de lista, evidencia ni unidad normalizada.
- `erp_compras_solicitudes_detalle`: guarda `costo_estimado`, `id_sku_erp`, `id_sku_proveedor`, `sku`, `cantidad` y `subtotal`. Es snapshot de solicitud, no costo maestro.
- `erp_compras_ordenes`: guarda `moneda`, `tipo_cambio`, `subtotal`, `impuestos`, `total`.
- `erp_compras_ordenes_detalle`: guarda snapshot operativo de la orden: `id_sku_proveedor`, `unidad`, `cantidad`, `costo_unitario`, `costo_unitario_incluye_impuesto`, `porcentaje_impuesto`, `subtotal`, `descuento`, `total`, fiscales y campos de revision.

Flujo real detectado:

- Catalogo permite capturar la relacion SKU/proveedor desde la ficha de producto con unidad de compra, factor, minimo, ultimo costo, dias de entrega y preferido. Usa permiso `catalogo.costos`.
- `CatalogoErpDatos::guardarSkuProveedor()` valida SKU, proveedor, unidad, factor y cantidad minima; guarda `costo_ultimo`, pero no moneda ni evidencia.
- `CatalogoErpDatos::sincronizarRelacionesProveedorHistoricas()` puede crear/actualizar relaciones desde listas heredadas y actualizar `erp_catalogo_skus.costo_referencia`. Es escritura operativa y requiere autorizacion expresa.
- `CatalogoErpDatos::sincronizarCostosProveedor()` aplica propuestas de costo desde listas heredadas y actualiza `costo_referencia` y `erp_catalogo_sku_proveedores`. Es escritura operativa y requiere autorizacion expresa.
- Solicitudes y ordenes de compra consumen `costo_ultimo` de la relacion proveedor-SKU como valor inicial, pero guardan su propio costo en detalle.
- `OrdenesCompraErp::validarDetalle()` recibe `costo_unitario`, `porcentaje_impuesto` y `costo_unitario_incluye_impuesto`; si el costo incluye IVA, calcula y guarda costo neto en `costo_unitario` y conserva bandera si la columna existe.
- La orden guarda `moneda` y `tipo_cambio` en encabezado. Eso protege el documento de compra como snapshot.
- Almacen, al recibir, debe actualizar `cantidad_recibida` en la orden y existencias/movimientos de inventario. Ya no debe actualizar `erp_catalogo_skus.costo_referencia` directamente; el costo debe consolidarse desde cierre de compra o flujo controlado de costos.

Definiciones operativas:

- Costo de lista: costo reportado por el proveedor en una lista/evidencia especifica. Requiere proveedor, lista, renglon, moneda, vigencia, unidad/factor y archivo/origen.
- Costo ultimo confiable: ultimo costo validado para una relacion SKU/proveedor. Puede vivir en `erp_catalogo_sku_proveedores.costo_ultimo`, pero necesita origen, fecha, moneda y evidencia para ser robusto.
- Costo referencia SKU: valor de apoyo en `erp_catalogo_skus.costo_referencia`; sirve para alertas/estimaciones, no debe reescribir historia de Compras.
- Costo de orden: snapshot en `erp_compras_ordenes_detalle.costo_unitario`, con moneda/tipo de cambio del encabezado. No debe cambiar por modificaciones futuras en proveedor, lista o Catalogo.
- Costo promedio: no se detecto como regla actual de Proveedores. Si se requiere, debe definirse con Inventario/Finanzas antes de implementar.

Reglas documentadas:

- Crear o editar orden de compra no debe afectar inventario.
- Inventario se afecta en recepcion.
- Costos de proveedor alimentan decision de compra, no deben reescribir historia.
- Una orden de compra debe usar snapshot de costo, moneda y tipo de cambio.
- Un costo heredado no debe considerarse vigente si no tiene proveedor, SKU ERP, moneda, unidad/factor y evidencia o criterio de confianza definido.
- `costo_referencia` puede cambiar por flujos controlados de Proveedores/Catalogo/Compras; no debe usarse como fuente historica de verdad.
- `costo_ultimo` sin moneda ni evidencia es util como referencia operativa, pero no alcanza para ERP robusto.

Faltantes del ERP nuevo:

- Moneda en costo proveedor y/o detalle de lista.
- Tipo de cambio cuando aplique.
- Vigencia de costo proveedor.
- Evidencia de origen: lista, archivo, renglon, XML, orden, recepcion o captura manual.
- Fecha de aplicacion y usuario que aprueba.
- Campo claro de costo con/sin impuestos en relacion proveedor/lista.
- Historial robusto de costos por proveedor-SKU con moneda, unidad, factor y evidencia.
- Politica de actualizacion de `costo_referencia` cuando cambia una lista, una relacion o una recepcion.

Decisiones pendientes para costos:

- Moneda obligatoria para costo proveedor y moneda por defecto si no viene en lista.
- Si `costo_ultimo` debe guardarse neto, bruto o ambos.
- Si el indicador de impuestos pertenece a lista, relacion SKU proveedor o ambos.
- Crear cierre de compra/costos para calcular `costo_referencia` desde compra terminada y no desde recepcion aislada.
- Si la aplicacion de costo requiere `proveedores.costos`, `catalogo.costos`, autorizacion de Compras o Finanzas.
- Si se necesita tabla nueva de historial/snapshot proveedor-SKU antes de migrar datos.
- Como manejar costos por empaque cuando `piezas_por_caja` difiere de `factor_conversion`.

Primeras tareas de codigo que requeririan autorizacion:

- Agregar auditoria solo lectura de relaciones proveedor-SKU sin moneda/evidencia/vigencia.
- Agregar vista o endpoint de consulta de costos proveedor sin aplicar cambios.
- Diseñar `ProveedoresEsquema.php` o ampliar `CatalogoErpEsquema.php` para historial de costos proveedor-SKU, sin ejecutar migracion.
- Separar permiso `proveedores.costos` de `catalogo.costos` en plan de seguridad.

## Evidencia consolidada - incidencias hacia Catalogo

Objetivo: que Proveedores pueda reportar huecos de Catalogo sin duplicar productos ni inventar reglas.

Contrato real detectado:

- Existe `erp_catalogo_incidencias_calidad` como bandeja general de calidad de Catalogo.
- La tabla soporta `tipo_incidencia`, `entidad_tipo`, `id_producto_erp`, `id_sku`, `id_referencia`, `referencia_tipo`, `origen`, `severidad`, `detalle_json`, `evidencia_json`, `propuesta_json`, `resolucion_json`, `estatus`, responsables y fechas.
- Tiene `huella` unica para evitar duplicados activos del mismo problema operativo.
- Catalogo ya tiene endpoints para listar y cambiar estatus: `incidencias_calidad` e `incidencia_calidad_estatus`.
- Catalogo ya sincroniza incidencias desde reglas/inventario, XML y Compras mediante `guardarIncidenciaCalidad()`.
- `guardarIncidenciaCalidad()` actualmente acepta origenes `catalogo`, `compra`, `xml`, `migracion` y `captura_manual`. No acepta `proveedores`; si se manda ese origen hoy, lo normaliza a `catalogo`.
- Existe `erp_proveedores_listas_productos_revision`, pero es una revision legacy/Compras para productos de lista. No tiene contrato rico de evidencia, propuesta, resolucion, origen, huella ni relacion directa con Catalogo.

Decision documentada:

- Reusar `erp_catalogo_incidencias_calidad` para incidencias que afecten el maestro Catalogo.
- No crear una bandeja paralela de incidencias de Catalogo dentro de Proveedores.
- Usar tablas/estados de Proveedores para conciliacion operativa interna, y escalar a Catalogo solo cuando se requiere decision sobre producto/SKU/codigo/fiscal/imagen/unidad/calidad.
- `erp_proveedores_listas_productos_revision` puede conservarse como evidencia legacy, pero no debe ser el contrato final de incidencias del ERP nuevo.

Incidencias esperadas desde Proveedores:

- `proveedor_sku_sin_match`: renglon de lista/proveedor sin SKU ERP confiable.
- `proveedor_match_ambiguo`: varias coincidencias posibles contra Catalogo.
- `proveedor_posible_duplicado`: producto proveedor parece duplicar producto/SKU existente.
- `proveedor_sku_sin_codigo_confiable`: SKU ERP relacionado no tiene codigo principal/codigo de barras confiable.
- `proveedor_sku_fiscal_incompleto`: SKU ERP comprable sin fiscal completo.
- `proveedor_unidad_factor_dudoso`: unidad/factor de compra no confiable o no normalizado.
- `proveedor_costo_dudoso`: costo fuera de rango, cero, negativo, sin moneda o sin evidencia.
- `proveedor_descripcion_imagen_conflicto`: descripcion, marca o imagen de proveedor no coincide con Catalogo.

Evidencia minima recomendada:

- `id_proveedor`.
- Nombre de proveedor.
- `id_lista_proveedor` o identificador de lista ERP nueva.
- `id_producto`/renglon de lista legacy si aplica.
- SKU proveedor, codigo barras, codigo interno, marca proveedor, nombre/descripcion proveedor.
- Costo, moneda, unidad, factor, vigencia y archivo/origen cuando existan.
- SKU ERP candidato o relacionado, si existe.
- Criterio de match y motivo de incidencia.
- Usuario/fecha que genera o sincroniza la incidencia.

Propuesta de mapeo:

- `origen`: `proveedores`.
- `entidad_tipo`: `proveedor_lista_renglon`, `proveedor_sku`, `sku` o `producto`, segun aplique.
- `id_referencia`: id del renglon/lista/relacion que origina el problema.
- `referencia_tipo`: tabla o contrato origen, por ejemplo `erp_proveedores_listas_productos` o `erp_catalogo_sku_proveedores`.
- `id_sku`: cuando ya existe SKU ERP afectado.
- `id_producto_erp`: cuando ya existe producto ERP afectado.
- `detalle_json`: motivo normalizado y datos operativos.
- `evidencia_json`: snapshot del renglon/lista/proveedor.
- `propuesta_json`: accion sugerida para Catalogo.

Acciones sugeridas:

- `buscar_o_vincular_sku_erp`.
- `crear_incidencia_migracion_catalogo` solo si el origen realmente es migracion.
- `capturar_codigo_principal`.
- `capturar_fiscal_sku`.
- `validar_unidad_factor`.
- `revisar_costo_y_moneda`.
- `validar_imagen_descripcion`.
- `descartar_con_motivo`.

Reglas de control:

- Proveedores no debe crear productos/SKU automaticamente por incidencia.
- Catalogo resuelve lo que afecta maestro producto/SKU/fiscal/codigo/imagen.
- Proveedores puede resolver pendientes de lista/matching cuando solo afectan relacion proveedor-SKU.
- Una incidencia resuelta o descartada debe conservar resolucion y usuario.
- Si una incidencia se detecta de nuevo, la huella debe evitar duplicado y refrescar evidencia si sigue abierta.

Primer ajuste de codigo recomendado, con autorizacion previa:

- Permitir `origen='proveedores'` en `CatalogoErpDatos::guardarIncidenciaCalidad()`.
- Crear un metodo de sincronizacion solo lectura/accion controlada para generar incidencias desde listas y matching de Proveedores.
- Exponerlo mediante endpoint protegido con permiso por definir (`proveedores.matching` o `proveedores.auditoria` para dry-run; `proveedores.autorizar` si crea incidencias reales).

Decisiones pendientes:

- Confirmar si el origen oficial sera `proveedores` o `proveedor`.
- Confirmar que tipos de incidencia deben bloquear Compras y cuales solo advierten.
- Confirmar si Proveedores puede crear incidencias reales directamente o solo propuestas/dry-run hasta autorizacion.
- Confirmar quien resuelve cada tipo: Catalogo, Proveedores, Compras o Finanzas.

## Evidencia consolidada - contrato con Compras

Objetivo: definir que datos entrega Proveedores a Compras para que Solicitudes y Ordenes trabajen con productos comprables y costos confiables.

Contrato real detectado:

- Solicitudes (`SolicitudesCompraErp::buscarSkus`) busca SKUs por proveedor en `erp_catalogo_sku_proveedores`.
- Ordenes (`OrdenesCompraErp::buscarSkus`) usa la misma base: `sp.id_proveedor`, `sp.estatus='activo'` y `s.estatus='activo'`.
- La busqueda permite encontrar por SKU ERP, nombre SKU, SKU proveedor y codigos activos de Catalogo.
- Solicitudes guarda snapshot en `erp_compras_solicitudes_detalle`: `id_sku_erp`, `id_sku_proveedor`, `sku`, `nombre_producto`, `cantidad`, `costo_estimado`, `subtotal`, observaciones.
- Ordenes guarda snapshot en `erp_compras_ordenes` y `erp_compras_ordenes_detalle`: proveedor, moneda, tipo de cambio, almacen, folio proveedor, cantidad, unidad, costo unitario, impuesto, descuento, total, SKU ERP, relacion SKU proveedor y datos fiscales JSON.
- Solicitudes y ordenes validan que un SKU ERP pertenezca al proveedor mediante una relacion activa en `erp_catalogo_sku_proveedores`; si no existe, rechazan la partida con mensaje de proveedor no correspondiente.
- Orden directa permite agregar productos con relacion activa; tambien conserva `producto_nuevo`/`requiere_revision` cuando una partida no tiene SKU ERP confiable.
- Generar orden desde solicitud vuelve a validar las partidas contra la relacion activa del proveedor.
- XML de compras puede conciliar contra detalle de orden por SKU o SKU proveedor; si no hay coincidencia o es ambigua, genera pendiente/incidencia, no relacion automatica confiable.

Contrato minimo que Compras debe recibir:

- `id_proveedor`.
- Proveedor activo para compras, cuando exista estado robusto.
- `id_sku_erp`.
- `id_sku_proveedor`.
- SKU ERP.
- SKU proveedor.
- Nombre SKU ERP.
- Unidad visible de compra/base.
- Factor de conversion cuando aplique.
- Cantidad minima.
- Costo vigente o ultimo costo confiable.
- Moneda y tipo de cambio de captura/documento.
- Indicador de costo con/sin impuestos.
- Estatus activo de la relacion.
- Evidencia/origen del costo y relacion, cuando el modelo lo soporte.

Responsabilidades por modulo:

- Proveedores: mantiene proveedor, condiciones, listas, evidencia, relaciones SKU proveedor, costos de proveedor y estado de confiabilidad.
- Catalogo: mantiene producto/SKU ERP, codigos, fiscal, unidades maestras, calidad e incidencias de maestro.
- Compras: consume relaciones activas y guarda snapshots en solicitud/orden; no corrige maestro proveedor ni crea relaciones confiables a escondidas.
- XML/Compras: puede sugerir coincidencias y generar incidencias, pero no debe convertir un concepto en maestro sin autorizacion.

Reglas documentadas:

- Compras no debe leer directamente listas viejas para operar.
- Compras no debe depender de `erp_proveedores_listas_productos` ni pedidos legacy como fuente operativa.
- Si el dato viene de legado, debe llegar a Compras ya validado, versionado o marcado como pendiente/incidencia.
- La orden conserva snapshot de costo, moneda, tipo de cambio, impuesto, unidad, SKU y relacion usada.
- Cambios futuros de costo/proveedor/lista no deben modificar ordenes ya guardadas.
- Una relacion proveedor-SKU inactiva no debe ser seleccionable para nuevas solicitudes u ordenes.
- Producto sugerido o nuevo debe escalar como pendiente de Catalogo/Proveedores, no convertirse automaticamente en producto/SKU ERP.

Faltantes actuales para contrato robusto:

- El catalogo de proveedores de Compras lista `erp_proveedores` sin filtrar por estado ERP robusto; hoy el maestro solo tiene datos minimos.
- `erp_catalogo_sku_proveedores` no guarda moneda, vigencia ni evidencia de costo.
- La busqueda muestra `costo_ultimo`, pero no muestra origen/fecha/evidencia del costo.
- Las solicitudes no guardan moneda; la orden si guarda moneda/tipo de cambio.
- El contrato aun vive tecnicamente bajo Catalogo (`catalogo.costos`) y no bajo permisos `proveedores.*`.
- Falta definir si Compras debe bloquear proveedor sin estado `activo_compras` cuando exista maestro robusto.

Tareas de codigo candidatas, con autorizacion previa:

- Crear un endpoint de Proveedores para exponer "SKUs comprables por proveedor" y migrar gradualmente las busquedas de Compras desde `Compra.php` hacia ese contrato.
- Agregar auditoria/dry-run de SKUs que Compras puede seleccionar pero carecen de moneda, vigencia o evidencia.
- Permitir origen `proveedores` en incidencias de Catalogo antes de sincronizar pendientes desde listas/matching.
- Agregar filtros de estado de proveedor cuando exista el maestro robusto.
- Extender relacion/historial proveedor-SKU con moneda, vigencia y evidencia antes de usar costos como fuente oficial.

Decisiones pendientes:

- Confirmar si Compras debe bloquear totalmente proveedores no `activo_compras` o solo advertir al inicio.
- Confirmar si solicitudes tambien deben capturar moneda o si la moneda se define hasta orden.
- Confirmar si Compras puede capturar producto sugerido en solicitudes o solo en ordenes/XML.
- Confirmar que permiso controla la exposicion de costos en Compras: `compras.ver`, `catalogo.costos`, `proveedores.costos` o combinacion.
- Confirmar si XML puede proponer relacion proveedor-SKU o solo incidencia de Catalogo/Proveedores.

## Evidencia consolidada - cierre

Checklist de cierre documental:

- Esquema auditado: completado.
- Legado separado de ERP nuevo: completado.
- Proveedores maestros: definido como modelo funcional, pendiente implementacion.
- Listas proveedor controladas: definido como evidencia versionada, pendiente implementacion.
- Matching SKU proveedor: definido con evidencia y autorizacion, pendiente implementacion.
- Costos/unidades/factores/moneda: auditado y definido; faltan campos/evidencia en contrato actual.
- Incidencias hacia Catalogo: definido que debe reutilizar `erp_catalogo_incidencias_calidad`; falta permitir origen `proveedores`.
- UI y permisos: matriz inicial definida; permisos reales `proveedores.*` pendientes de autorizacion.
- Contrato con Compras: validado sobre flujo real; Compras ya consume relaciones activas y guarda snapshot.
- Evidencia consolidada: todo quedo en este archivo y los auxiliares completados fueron eliminados.

Criterio final documentado:

- Compras debe poder seleccionar productos por proveedor usando relaciones confiables.
- Cualquier hueco debe generar incidencia accionable o pendiente de conciliacion.
- No debe haber recaptura innecesaria, duplicado automatico ni actualizacion masiva sin autorizacion.
- Lo heredado se reutiliza como evidencia/insumo, no como estructura final obligatoria.

## Proxima implementacion recomendada

Primera tarea sugerida: auditoria solo lectura de Proveedores.

Alcance recomendado:

- Crear una consulta/modelo o endpoint dry-run que reporte:
  - proveedores sin maestro robusto;
  - relaciones SKU-proveedor sin moneda/vigencia/evidencia;
  - listas legacy sin moneda;
  - renglones sin costo;
  - renglones sin match;
  - SKUs comprables sin fiscal/codigo/costo confiable.
- No modificar datos.
- No ejecutar migraciones.
- No aplicar costos.
- No cambiar permisos reales.
- Documentar resultados en este archivo antes de cualquier migracion.

Primer cambio de codigo pequeno que conviene autorizar por separado:

- Permitir `origen='proveedores'` en `CatalogoErpDatos::guardarIncidenciaCalidad()`.

Motivo:

- Hoy esa funcion normaliza origenes no permitidos a `catalogo`.
- Sin ese ajuste, las incidencias generadas desde Proveedores perderian trazabilidad de origen.
- Es un cambio pequeno y localizado, pero afecta contrato de incidencias; debe autorizarse antes de tocar codigo.

Estado: autorizado y aplicado el 2026-06-12. `CatalogoErpDatos::guardarIncidenciaCalidad()` ya acepta `proveedores` como origen valido. No se crearon incidencias, no se ejecutaron migraciones y no se modificaron datos.

Estado de auditoria dry-run: autorizado y aplicado el 2026-06-12.

Archivos modificados:

- `app/controladores/Proveedor.php`
- `app/modelos/Proveedores.php`
- `docs/erp_proveedores_avance.md`

Endpoint agregado:

- `Proveedor::auditoria_dry_run_erp()`
- Permiso temporal usado: `compras.ver`, por compatibilidad con el modulo actual. No se definieron permisos reales `proveedores.*`.

Alcance implementado:

- Consulta solo lectura sobre tablas existentes.
- Reporta si las tablas esperadas existen antes de contar.
- Cuenta proveedores legacy, listas legacy, renglones legacy, relaciones SKU-proveedor ERP y huecos tecnicos de Catalogo usados por Compras.
- Reporta renglones sin costo, sin SKU/nombre, sin match exacto contra SKU ERP, relaciones sin costo/unidad/factor, relaciones con SKU/proveedor invalido, SKUs comprables sin codigo principal y SKUs comprables con datos fiscales incompletos.
- Devuelve `sin_escrituras=true` y una lista de limitaciones del contrato actual.

Alcance no implementado:

- No crea incidencias.
- No modifica datos.
- No ejecuta migraciones.
- No aplica costos.
- No cambia permisos reales.
- No define reglas de negocio para moneda, vigencia, evidencia, proveedor preferido ni bloqueo de Compras.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.

Estado de pantalla de auditoria: autorizado y aplicado el 2026-06-12.

Archivos agregados/modificados:

- `app/controladores/Proveedor.php`
- `app/vistas/paginas/apps/erp/proveedores/auditoria.php`
- `public/assets/js/custom/apps/erp/proveedores/auditoria.js`
- `docs/erp_proveedores_avance.md`

Ruta de pantalla agregada:

- `/proveedor/auditoria_erp`

Alcance implementado:

- Pantalla operativa solo lectura para consumir `/proveedor/auditoria_dry_run_erp`.
- Muestra resumen, hallazgos, tablas detectadas y limitaciones del contrato.
- No agrega acciones de escritura, sincronizacion, migracion ni aplicacion de costos.
- No toca menus globales; queda disponible por ruta directa mientras se decide navegacion/permisos propios.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\auditoria.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\auditoria.js`: sin errores de sintaxis.

## Tablero operativo de tareas Proveedores

Objetivo del tablero: avanzar el modulo por tareas pequenas, verificables y con bajo riesgo, sin pedir consultas enormes ni mezclar decisiones de negocio con implementacion tecnica.

Reglas de uso:

- Trabajar una tarea por vez.
- Antes de tocar codigo, confirmar el alcance puntual de esa tarea.
- No ejecutar migraciones, escrituras masivas ni transformaciones de datos sin autorizacion explicita.
- Si una tarea descubre una decision de negocio no cerrada, documentarla y detener esa parte.
- Al terminar una tarea, registrar evidencia en este archivo y actualizar su estado.

Estados:

- `pendiente`: no iniciada.
- `autorizable`: se puede implementar cuando el dueno lo autorice.
- `en_revision`: requiere decision de negocio antes de implementar.
- `aplicada`: implementada y verificada.
- `bloqueada`: no puede avanzar sin dato externo, decision o cambio previo.

### Bloque P0 - Base segura y auditoria

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P0-01 | Consolidar docs de Proveedores en `erp_proveedores_avance.md` y eliminar auxiliares completados | aplicada | Mantener un solo documento vivo con evidencia | No, ya aplicado |
| P0-02 | Permitir origen `proveedores` en incidencias de Catalogo | aplicada | Ajuste localizado en `CatalogoErpDatos::guardarIncidenciaCalidad()` | Ya autorizado |
| P0-03 | Endpoint dry-run de auditoria Proveedores | aplicada | Conteos solo lectura de proveedores/listas/relaciones/huecos | Ya autorizado |
| P0-04 | Pantalla tecnica de auditoria | aplicada | Vista `/proveedor/auditoria_erp` sin acciones de escritura | Ya autorizado |
| P0-05 | Agregar muestras limitadas al dry-run | aplicada | Hasta 10 ejemplos por hallazgo para decidir limpieza/migracion/correccion | Solo lectura aplicada |
| P0-06 | Exportar auditoria a JSON/CSV tecnico | aplicada | Descarga JSON completa y CSV de hallazgos desde auditoria | Solo lectura aplicada |

Criterio de cierre P0:

- Auditoria disponible por endpoint y pantalla.
- Hallazgos visibles sin modificar datos.
- Evidencia documentada.

### Bloque P1 - Esquema planeado del maestro

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P1-01 | Crear `ProveedoresEsquema.php` en modo plan/auditoria | aplicada | Declarar tablas/columnas/indices propuestos sin ejecutar DDL | Ya autorizado |
| P1-02 | Definir extension de `erp_proveedores` vs tablas satelite | aplicada | Decidir si se amplia maestro actual o se agregan satelites | Decision tomada: raiz mejorada + satelites |
| P1-03 | Planear datos generales del proveedor | aplicada | Nombre comercial, codigo interno, tipo, clasificacion, responsable, notas, origen, estatus | Ya autorizado |
| P1-04 | Planear datos fiscales del proveedor | aplicada | RFC, razon social fiscal, regimen, domicilio, constancia/evidencia | Ya autorizado |
| P1-05 | Planear contactos por area | aplicada | Compras, ventas, cobranza, logistica, soporte | Ya autorizado |
| P1-06 | Planear condiciones comerciales/logisticas | aplicada | Credito, dias, moneda preferida, minimo, entrega, restricciones | Ya autorizado |
| P1-07 | Planear documentos/evidencias | aplicada | Constancia, contrato, listas, archivos de soporte | Ya autorizado |
| P1-08 | Auditar esquema planeado contra BD actual | aplicada | Endpoint o metodo de auditoria, sin aplicar cambios | Ya autorizado |

Criterio de cierre P1:

- Existe plan de esquema auditable.
- Estan separadas columnas obligatorias, opcionales y pendientes de decision.
- El esquema operativo base ya fue aplicado con autorizacion y auditoria posterior limpia.

### Bloque P2 - Permisos y seguridad

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P2-01 | Revisar permisos actuales en `SeguridadPermisos.php`, `SeguridadEsquema.php`, `Sistema.php` y `Proveedor.php` | aplicada | Auditoria de permisos, sin cambios | No |
| P2-02 | Definir matriz `proveedores.*` final | aplicada | Ver, crear, editar, contactos, fiscales, listas, matching, costos, autorizar, auditoria | Matriz final documentada |
| P2-03 | Planear migracion de permisos prestados `compras.*` | aplicada | Mapa temporal entre permisos actuales y nuevos | Plan documentado |
| P2-04 | Implementar permisos `proveedores.*` en esquema de seguridad | aplicada | Cambios en seguridad/roles y siembra autorizada | Codigo aplicado y plan ejecutado |
| P2-05 | Cambiar endpoints nuevos para usar permisos propios | aplicada | Solo modulo nuevo, sin romper legacy | Aplicado sin fallback temporal |
| P2-06 | Registrar auditoria explicita en acciones sensibles | aplicada | Crear/editar/estatus/listas/matching/costos/documentos | Plan documentado |

Criterio de cierre P2:

- Permisos propios definidos e implementados.
- Endpoints nuevos ya no dependen de `compras.*` salvo transicion documentada.
- Acciones sensibles quedan auditadas.

### Bloque P3 - Maestro operativo de proveedores

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P3-01 | Modelo de dominio para maestro Proveedores ERP | aplicada | Metodos de listar/consultar sin mezclar legacy | Solo lectura aplicada |
| P3-02 | Endpoint listar proveedores ERP | aplicada | Filtros, busqueda, estatus, paginacion simple | Solo lectura aplicada |
| P3-03 | Endpoint consultar proveedor ERP | aplicada | Ficha completa por proveedor | Solo lectura aplicada |
| P3-04 | Endpoint guardar datos generales | aplicada | Alta/edicion controlada de identidad y perfil | Escritura controlada aplicada |
| P3-05 | Endpoint guardar fiscales | aplicada | Datos fiscales y validaciones minimas sin SAT/archivo | Escritura controlada aplicada |
| P3-06 | Endpoint guardar contactos | aplicada | Contactos por area sin notificaciones automaticas | Escritura controlada aplicada |
| P3-07 | Endpoint guardar condiciones | aplicada | Comerciales/logisticas sin bloqueos automaticos | Escritura controlada aplicada |
| P3-08 | Endpoint cambiar estatus | aplicada | Estatus de proveedor y listas con auditoria; motivo obligatorio para estados restrictivos | Escritura controlada aplicada |
| P3-09 | Pantalla listado proveedores ERP | aplicada | UI nueva, no legacy gigante | Solo lectura aplicada |
| P3-10 | Pantalla ficha/edicion proveedor ERP | aplicada | Ficha 360 en tabs compactos con edicion de generales, fiscales, contactos, condiciones y documentos | Escritura controlada aplicada |
| P3-11 | Endpoint guardar documentos/evidencias metadata | aplicada | Registro de metadatos, referencias, sensibilidad, vigencia y estatus sin carga fisica | Escritura controlada aplicada; carga fisica pendiente |

Criterio de cierre P3:

- Se puede dar de alta y consultar un proveedor ERP robusto.
- Los datos legacy siguen disponibles, pero no dictan la estructura nueva.
- Cambios importantes quedan auditados.

### Bloque P4 - Listas de proveedor versionadas

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P4-01 | Plan de esquema para encabezado de listas ERP | aplicada | Proveedor, version, moneda, vigencia, evidencia, estatus | Esquema base aplicado |
| P4-02 | Plan de esquema para detalle de listas ERP | aplicada | SKU proveedor, descripcion, unidad, factor, costo, moneda, match | Esquema base aplicado |
| P4-03 | Auditoria de listas legacy para candidatos a migrar | aplicada | Conteos y muestras limitadas, sin migrar | Solo lectura aplicada |
| P4-04 | Pantalla listado de listas por proveedor | aplicada | Ver listas ERP por proveedor desde ficha 360 | Escritura controlada aplicada |
| P4-05 | Pantalla detalle de lista | aplicada | Renglones, costo reportado, SKU proveedor, unidad, factor y estado de conciliacion sin aplicar costos | Escritura controlada aplicada |
| P4-06 | Flujo carga lista como evidencia | aplicada | Encabezado versionado, carga fisica de archivo original y evidencia documental sin aplicar costos | Escritura controlada aplicada |
| P4-07 | Filtros operativos de detalle de lista | aplicada | Revisar renglones con/sin SKU, costo, unidad/factor, moneda y origen productivo | Solo UI aplicada |
| P4-08 | Guardar archivo original de lista | aplicada | Subir Excel/CSV/PDF/ZIP/TXT/CSV tal como lo envia el proveedor y ligarlo como evidencia | Escritura controlada aplicada |
| P4-09 | Vista previa de archivo de lista | aplicada | Leer muestra de XLS/XLSX/CSV/TXT sin importar renglones | Solo lectura aplicada |
| P4-10 | Mapeo de columnas proveedor a ERP | aplicada | Sugerir y permitir confirmar columnas antes de importar | UI aplicada |
| P4-11 | Importar lista normalizada en borrador | aplicada | Crear renglones desde vista previa solo en lista vacia, sin aplicar costos ni relaciones | Escritura controlada aplicada |
| P4-12 | Versionado/historial de listas | aplicada | Estados controlados, versionado y listas historicas/canceladas como evidencia | Escritura controlada aplicada |
| P4-15 | Aviso para archivos no importables | aplicada | Mensaje claro para `.xls` legacy u otros formatos no parseables | UI aplicada sin escritura |

Criterio de cierre P4:

- Las listas quedan como evidencia versionada.
- Ninguna lista actualiza Catalogo o Compras automaticamente.
- Cada renglon puede pasar a conciliacion.
- El archivo original del proveedor se conserva como evidencia, aunque despues se normalice a estructura ERP.

### Bloque P5 - Matching SKU proveedor contra Catalogo

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P5-01 | Endpoint de propuestas de matching dry-run | aplicada | Exacto/posible/sin match/ambiguo, sin escribir | Solo lectura aplicada |
| P5-02 | Pantalla bandeja de matching | aplicada | Modal con resumen, filtros, candidatos multiples y revision desde detalle de lista | Solo lectura aplicada |
| P5-03 | Guardar decision de matching por renglon | aplicada | Seleccionar candidato/rechazar/marcar ambiguo/sin match con auditoria | Escritura controlada aplicada; no aplica relacion |
| P5-04 | Aplicar relacion SKU-proveedor individual | aplicada | Crear/actualizar `erp_catalogo_sku_proveedores` sin aplicar costos | Escritura operativa aplicada |
| P5-05 | Aplicar relaciones exactas en lote | aplicada | Preview + aplicacion real con confirmacion; no aplica costos | Autorizado para relaciones |
| P5-06 | Generar pendiente/incidencia para sin match | aplicada | Dry-run propone pendiente y permite crear incidencia individual hacia Catalogo | Escritura controlada aplicada en P7 |
| P5-07 | Motivos estandar de rechazo/ambiguedad | aplicada | Motivos estandar en UI y validacion servidor para `ambiguo`, `sin_match`, `rechazado` | Escritura controlada aplicada |
| P5-08 | Solicitar/crear SKU ERP preliminar desde producto proveedor | aplicada | Proveedores escala incidencia y Catalogo crea producto/SKU temporal en borrador | Escritura controlada aplicada en Catalogo |

Criterio de cierre P5:

- Renglones de lista pueden convertirse en relacion confiable o pendiente accionable.
- No se crean productos/SKUs automaticamente.
- Cada decision conserva evidencia.
- Los productos nuevos deben pasar por SKU ERP preliminar/controlado antes de Compras formal, no por texto libre.
- El SKU preliminar se crea desde Catalogo, no desde Proveedores, y vuelve a Proveedores por matching.

### Bloque P6 - Costos, moneda, unidad, factor y evidencia

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P6-01 | Definir si se extiende `erp_catalogo_sku_proveedores` o se crea historial | aplicada | Historial `erp_proveedores_sku_costos` como fuente de verdad; `costo_ultimo` referencia operativa | Decision aceptada |
| P6-02 | Plan de esquema de historial costo proveedor-SKU | aplicada | Costo, moneda, unidad, factor, vigencia, origen, usuario | Plan existente sin migracion nueva |
| P6-03 | Endpoint consulta costos proveedor | aplicada | Solo lectura, por proveedor/SKU/lista | Solo lectura aplicada |
| P6-04 | Pantalla costos por proveedor/SKU | aplicada | Resumen, historial y filtro local desde ficha proveedor; sin aplicar costos | Solo lectura aplicada |
| P6-05 | Aplicar costo autorizado individual | aplicada | Historiza costo vigente anterior, crea/actualiza historial vigente y actualiza `costo_ultimo` | Escritura operativa aplicada |
| P6-06 | Aplicar costos en lote | aplicada | Preview + aplicacion real con confirmacion; no toca `costo_referencia` | Autorizado para costos vigentes |
| P6-07 | Politica de `costo_referencia`, costo promedio y moneda | aplicada | Compra real prioritaria, proveedor vigente solo referencia inicial, costo promedio calculado y moneda controlada | Probar con compras reales |

Criterio de cierre P6:

- Costo proveedor tiene moneda, vigencia, unidad/factor y evidencia.
- Compras usa costo como sugerencia/snapshot, no como historia mutable.

### Bloque P7 - Incidencias y pendientes accionables

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P7-01 | Endpoint dry-run de incidencias desde Proveedores | aplicada | Contar/proponer incidencias sin crearlas | Solo lectura aplicada |
| P7-02 | Crear incidencias reales desde listas/matching | aplicada parcial | Creacion individual desde propuesta recalculada, origen `proveedores` | Escritura controlada aplicada |
| P7-03 | Mapear tipos de incidencia finales | en_revision | Bloqueantes vs advertencias | Decision negocio |
| P7-04 | Pantalla de pendientes Proveedores | aplicada | Modal dry-run desde detalle de lista con ruta de resolucion | Solo lectura aplicada |
| P7-05 | Resolver/descartar pendientes con motivo | aplicada | Incidencias reales origen `proveedores` con cierre individual y motivo | Escritura controlada aplicada |
| P7-06 | Evitar duplicados por huella | aplicada | Usa huella SHA-256 y `ON DUPLICATE KEY UPDATE` | Escritura controlada aplicada |
| P7-07 | Guia de resolucion por tipo de pendiente | aplicada | Indica si se resuelve editando, con matching o enviando a Catalogo | Solo UI aplicada |

Criterio de cierre P7:

- Huecos de proveedor/lista/matching generan pendientes accionables.
- Catalogo recibe solo lo que afecta producto/SKU/fiscal/codigo/unidad/calidad.

### Bloque P8 - Contrato final con Compras

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P8-01 | Endpoint `skus_comprables_por_proveedor` en Proveedores | aplicada | Contrato solo lectura para Compras | Solo lectura aplicada |
| P8-02 | Migrar busquedas de Compras hacia contrato Proveedores | aplicada | Solicitudes y Ordenes consumen `skusComprablesParaComprasErp()` conservando endpoints | Probar con datos reales |
| P8-03 | Filtro proveedor `activo_compras` | aplicada | `suspendido`, `bloqueado` e `inactivo` bloquean envio de orden; preparacion no bloquea por si sola | Probar con datos reales |
| P8-04 | Validar relacion activa y costo confiable | aplicada | Bloqueos minimos en servidor + confirmaciones no bloqueantes para datos mejorables | Probar con orden real |
| P8-05 | Mostrar evidencia/origen de costo en Compras | aplicada | Busqueda y partidas muestran si el costo viene de historial vigente o costo ultimo | UI aplicada sin bloqueo nuevo |
| P8-06 | Mantener snapshots de orden sin cambios retroactivos | aplicada | Ordenes guardan snapshot de evidencia de costo en `evidencia_costo_json` | Probar con orden real enviada |

Criterio de cierre P8:

- Compras consume Proveedores como fuente confiable.
- Solicitudes/ordenes no dependen de listas legacy.
- Los documentos guardan snapshot y no cambian por actualizaciones futuras.

### Bloque P9 - Migracion controlada de legado

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P9-01 | Plan de migracion sin ejecutar | aplicada | Criterios de datos reutilizables, historicos, descartables y pasos con respaldo | Plan documental sin escritura |
| P9-02 | Dry-run de migracion proveedores | aplicada | Conteos, muestras y accion propuesta de proveedores legacy desde SQL productivo | Solo lectura aplicada |
| P9-03 | Dry-run de migracion listas | aplicada | Conteos, muestras y accion propuesta de listas/renglones candidatos desde SQL productivo | Solo lectura aplicada |
| P9-04 | Ejecutar migracion piloto limitada | aplicada | Tabla staging creada con respaldo previo | Ya autorizado |
| P9-05 | Validar resultados piloto | aplicada | Lote staging cargado y comparado contra destino | Ya autorizado |
| P9-06 | Convertir staging a tablas ERP oficiales | aplicada | Proveedores/perfiles, listas borrador y renglones evidencia, sin costos vigentes ni relaciones automaticas | Ya autorizado |
| P9-07 | Revisar pendientes post-migracion | aplicada | 2 renglones legacy con costo no positivo cerrados como historico no utilizable | Escritura controlada aplicada |
| P9-08 | Migracion completa por lotes futuros | aplicada parcial | Respaldo externo y lote staging cargado; sin candidatos nuevos para convertir ni pendientes staging abiertos | Conversion futura requiere nuevos candidatos |

Criterio de cierre P9:

- Datos utiles migrados o vinculados con evidencia.
- Legado conserva historia sin contaminar el flujo nuevo.

### Bloque P10 - Navegacion, cierre y pruebas

| ID | Tarea | Estado | Alcance | Requiere autorizacion |
| --- | --- | --- | --- | --- |
| P10-01 | Agregar rutas de Proveedores ERP al menu | aplicada | Grupo propio Proveedores en sidebar | Navegacion habilitada |
| P10-02 | Pruebas de flujo maestro-lista-matching-costo-compra | documentada | Checklist real agregado en `erp_proveedores_pruebas_reales.md`; ejecucion queda pendiente del usuario | Probar con datos reales |
| P10-03 | Revisar auditoria y permisos por rol | aplicada | Auditoria solo lectura de permisos `proveedores.*` por rol contra matriz base | Cambiar roles requiere autorizacion separada |
| P10-04 | Documentar cierre operativo | aplicada | Cierre operativo, pruebas reales y autorizaciones pendientes documentadas | No |
| P10-05 | Marcar legado como historico/no operativo | aplicada | Sidebar y pantallas legacy muestran aviso de transicion; no se borra ni bloquea | Ocultar/deshabilitar legacy queda como decision futura |

Criterio de cierre P10:

- El modulo queda navegable, probado y documentado.
- El flujo nuevo queda separado del legacy.
- Hay evidencia suficiente para delegar tareas por departamento.

## Evidencia P1-01 - esquema planeado de Proveedores

Estado: autorizado y aplicado el 2026-06-12.

Archivo agregado:

- `app/modelos/ProveedoresEsquema.php`

Alcance implementado:

- Modelo `ProveedoresEsquema` extendiendo `DBSchema`.
- Metodo `tablasProveedoresErp()` para listar las tablas del contrato planeado.
- Metodo `auditarProveedoresErp()` para comparar tablas, columnas e indices esperados contra la BD actual.
- Contrato auditable para:
  - `erp_proveedores` como raiz legacy conservada durante transicion.
  - `erp_proveedores_perfil`.
  - `erp_proveedores_fiscales`.
  - `erp_proveedores_contactos`.
  - `erp_proveedores_condiciones`.
  - `erp_proveedores_documentos`.
  - `erp_proveedores_listas_erp`.
  - `erp_proveedores_listas_detalle_erp`.
  - `erp_proveedores_sku_costos`.

Alcance no implementado:

- No ejecuta DDL.
- No crea tablas.
- No agrega columnas.
- No migra datos.
- No cambia permisos.
- No expone endpoint todavia.
- No decide de forma definitiva si el modelo final usara solo tablas satelite, extension de `erp_proveedores` o una combinacion; el contrato queda como propuesta auditable.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\ProveedoresEsquema.php`: sin errores de sintaxis.

## Evidencia P1-08 - auditoria de esquema planeado

Estado: autorizado y aplicado el 2026-06-12.

Archivos modificados:

- `app/controladores/Proveedor.php`
- `app/vistas/paginas/apps/erp/proveedores/auditoria.php`
- `public/assets/js/custom/apps/erp/proveedores/auditoria.js`
- `docs/erp_proveedores_avance.md`

Endpoint agregado:

- `/proveedor/esquema_auditar_erp`

Permiso temporal usado:

- `compras.ver`, por compatibilidad con el modulo actual. Los permisos `proveedores.*` siguen pendientes en `P2`.

Alcance implementado:

- Endpoint de solo lectura para invocar `ProveedoresEsquema::auditarProveedoresErp()`.
- Seccion "Esquema planeado" en `/proveedor/auditoria_erp`.
- Resumen de tablas faltantes, columnas faltantes e indices faltantes.
- Tabla por entidad propuesta con severidad y faltantes.

Alcance no implementado:

- No ejecuta DDL.
- No crea tablas.
- No agrega columnas.
- No migra datos.
- No cambia permisos reales.
- No agrega boton de actualizar esquema.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\auditoria.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\auditoria.js`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\ProveedoresEsquema.php`: sin errores de sintaxis.

## Evidencia P1-09 - ejecucion controlada de esquema Proveedores ERP

Estado: autorizado y aplicado en BD local el 2026-06-12.

Archivos modificados:

- `app/modelos/ProveedoresEsquema.php`.
- `app/controladores/Proveedor.php`.

Alcance implementado:

- Se agrego `ProveedoresEsquema::planActualizarProveedoresErp($ejecutar = false)`.
- Se agrego endpoint tecnico `/proveedor/esquema_actualizar_erp` protegido con `sistema.soporte`.
- Se aplico el esquema base autorizado para:
  - `erp_proveedores` con columnas puente `estatus_erp`, `origen_erp`, `fecha_actualizacion` e indice `idx_proveedor_estatus_erp`.
  - `erp_proveedores_perfil`.
  - `erp_proveedores_fiscales`.
  - `erp_proveedores_contactos`.
  - `erp_proveedores_condiciones`.
  - `erp_proveedores_documentos`.
  - `erp_proveedores_listas_erp`.
  - `erp_proveedores_listas_detalle_erp`.
  - `erp_proveedores_sku_costos`.

Reglas de seguridad aplicadas:

- Las columnas puente nuevas de `erp_proveedores` quedaron `NULL` por defecto para no inventar estados de negocio ni reclasificar proveedores existentes.
- No se migraron datos legacy a tablas nuevas.
- No se aplicaron costos.
- No se crearon relaciones SKU proveedor.
- No se tocaron endpoints de Compras/Catalogo.
- No se definieron catalogos cerrados de estatus, areas, tipos, fletes, pagos o sensibilidad documental.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\ProveedoresEsquema.php`: sin errores de sintaxis.
- Dry-run previo: `{"error":false,"tipo":"info","mensaje":"Plan de Proveedores ERP generado sin ejecutar","errores":0,"ejecutados":0,"total":13}`.
- Ejecucion autorizada: `{"error":false,"tipo":"success","mensaje":"Plan de Proveedores ERP ejecutado","errores":0,"ejecutados":12,"total":201}`.
- Dry-run posterior/idempotente: `{"error":false,"tipo":"info","mensaje":"Plan de Proveedores ERP generado sin ejecutar","errores":0,"ejecutados":0,"total":201}`.
- Auditoria posterior: `{"error":false,"tipo":"success","mensaje":"El esquema planeado de Proveedores ERP esta completo","resumen":{"tablas_faltantes":0,"columnas_faltantes":0,"indices_faltantes":0,"indices_con_columnas_distintas":0}}`.

Nota de lectura:

- Esta evidencia actualiza el estado operativo de P1-02 a P1-07. Las secciones anteriores conservan la decision y el alcance planeado; P1-09 confirma que el esquema base ya fue aplicado.

## Evidencia P1-02 - decision de estructura base

Estado: decision tomada el 2026-06-12.

Decision:

- Usar `erp_proveedores` como raiz estable del proveedor.
- Mejorar `erp_proveedores` solo con campos puente que sean utiles para compatibilidad operativa y filtros transversales.
- Crear tablas satelite para datos robustos del ERP: perfil, fiscales, contactos, condiciones, documentos, listas, detalles de lista e historial de costos.

Campos puente propuestos para `erp_proveedores`:

- `estatus_erp`: estado operativo base para filtrar/bloquear proveedor desde Compras/Catalogo cuando se autorice.
- `origen_erp`: identifica si el registro viene de legado, migracion, captura manual u otro origen autorizado.
- `fecha_actualizacion`: trazabilidad minima de cambios en la raiz.

Regla documentada:

- `erp_proveedores` no debe convertirse en una tabla gigante.
- Si un dato pertenece a una seccion delegable por departamento, debe vivir en tabla satelite.
- Si un dato es puente transversal, estable y necesario para compatibilidad con Compras/Catalogo, puede agregarse a `erp_proveedores` con autorizacion.

Archivo ajustado:

- `app/modelos/ProveedoresEsquema.php`

Alcance no implementado:

- No se ejecuto migracion.
- No se agregaron columnas reales.
- No se modificaron datos.
- No se cambiaron endpoints de Compras/Catalogo.

## Evidencia P1-03 - datos generales del proveedor

Estado: autorizado y aplicado en contrato planeado el 2026-06-12.

Decision:

- Datos generales quedan repartidos entre la raiz `erp_proveedores` y el satelite `erp_proveedores_perfil`.
- `erp_proveedores` conserva lo transversal y compatible con legacy: identidad base, estatus puente, origen puente y fecha de actualizacion.
- `erp_proveedores_perfil` concentra datos operativos generales que no son fiscales, contactos, condiciones, documentos, listas ni costos.

Campos generales planeados en `erp_proveedores`:

- `id_proveedor`.
- `proveedor`.
- `cuota`, solo como dato legado pendiente de clasificar.
- `estatus_erp`.
- `origen_erp`.
- `fecha_actualizacion`.

Campos generales planeados en `erp_proveedores_perfil`:

- `id_proveedor`.
- `nombre_comercial`.
- `nombre_corto`.
- `codigo_proveedor_erp`.
- `tipo_proveedor`.
- `clasificacion_operativa`.
- `origen`.
- `responsable_interno_id`.
- `notas`.
- `creado_por`.
- `revisado_por`.
- `autorizado_por`.
- `fecha_revision`.
- `fecha_autorizacion`.
- `fecha_registro`.
- `fecha_actualizacion`.

Reglas documentadas:

- La razon social fiscal, RFC, regimen y domicilio fiscal no pertenecen a datos generales; se cierran en `P1-04`.
- `codigo_proveedor_erp` se planea como codigo interno opcional, no como regla de negocio obligatoria todavia.
- `responsable_interno_id` queda como campo planeado para delegacion operativa; su uso real depende de permisos y roles de `P2`.
- `cuota` no se convierte en regla nueva hasta decidir si pertenece a condiciones comerciales o queda historica.

Archivo ajustado:

- `app/modelos/ProveedoresEsquema.php`

Alcance no implementado:

- No se ejecuto migracion.
- No se agregaron columnas reales.
- No se modificaron datos.
- No se definieron catalogos cerrados para `tipo_proveedor` ni `clasificacion_operativa`.

## Evidencia P1-04 - datos fiscales del proveedor

Estado: autorizado y aplicado en contrato planeado el 2026-06-12.

Decision:

- Los datos fiscales viven en `erp_proveedores_fiscales`.
- La razon social fiscal, RFC, regimen y domicilio fiscal no se mezclan con el nombre operativo del proveedor.
- La constancia fiscal se liga como evidencia mediante `id_documento_constancia`, sin definir todavia el flujo de carga de documentos.

Campos fiscales planeados:

- `id_proveedor_fiscal`.
- `id_proveedor`.
- `rfc`.
- `razon_social`.
- `regimen_fiscal`.
- `codigo_postal_fiscal`.
- `pais`.
- `estado`.
- `municipio`.
- `colonia`.
- `calle`.
- `numero_exterior`.
- `numero_interior`.
- `domicilio_fiscal`.
- `uso_cfdi_preferido`.
- `id_documento_constancia`.
- `fecha_constancia`.
- `validado_por`.
- `fecha_validacion`.
- `vigencia_desde`.
- `vigencia_hasta`.
- `estatus`.
- `fecha_registro`.
- `fecha_actualizacion`.

Indices planeados:

- `idx_proveedor_fiscal_proveedor`: `id_proveedor`, `estatus`.
- `idx_proveedor_fiscal_rfc`: `rfc`.
- `idx_proveedor_fiscal_cp`: `codigo_postal_fiscal`.
- `idx_proveedor_fiscal_vigencia`: `vigencia_desde`, `vigencia_hasta`.

Reglas documentadas:

- `rfc`, `razon_social`, `regimen_fiscal` y `codigo_postal_fiscal` son candidatos a ser obligatorios para proveedor fiscalmente validado, pero la obligatoriedad exacta para `activo_compras` y `activo_pagos` queda pendiente de decision.
- `uso_cfdi_preferido` queda como apoyo operativo, no como regla obligatoria todavia.
- `id_documento_constancia` depende de `P1-07` documentos/evidencias.
- Bancos/cuentas no se incluyen aqui. En un ERP robusto deben tratarse como dato financiero sensible: Proveedores puede guardar referencia/evidencia de datos de pago, pero la validacion, autorizacion, cambios y uso para pagos deben quedar bajo flujo y permisos de Finanzas.

Archivo ajustado:

- `app/modelos/ProveedoresEsquema.php`

Alcance no implementado:

- No se ejecuto migracion.
- No se agregaron columnas reales.
- No se modificaron datos.
- No se valido RFC contra SAT.
- No se definio flujo de carga de constancia.

## Evidencia P1-05 - contactos por area

Estado: autorizado y aplicado en contrato planeado el 2026-06-12.

Decision:

- Los contactos viven en `erp_proveedores_contactos`.
- Los contactos se organizan por area funcional: compras, ventas, cobranza, logistica, soporte u otra area que se autorice.
- Contactos no define condiciones de pago, bancos, autorizaciones financieras ni reglas fiscales.

Campos de contacto planeados:

- `id_contacto_proveedor`.
- `id_proveedor`.
- `area`.
- `nombre`.
- `puesto`.
- `correo`.
- `telefono`.
- `extension`.
- `celular`.
- `whatsapp`.
- `recibe_ordenes_compra`.
- `recibe_notificaciones`.
- `es_principal`.
- `prioridad`.
- `observaciones`.
- `estatus`.
- `creado_por`.
- `fecha_registro`.
- `fecha_actualizacion`.

Indices planeados:

- `idx_proveedor_contacto_proveedor`: `id_proveedor`, `estatus`.
- `idx_proveedor_contacto_area`: `id_proveedor`, `area`, `estatus`.
- `idx_proveedor_contacto_correo`: `correo`.

Reglas documentadas:

- Un proveedor puede tener varios contactos por area.
- `es_principal` y `prioridad` ayudan a elegir contacto preferente, pero no sustituyen reglas de autorizacion.
- `recibe_ordenes_compra` y `recibe_notificaciones` son permisos operativos de comunicacion, no permisos del sistema.
- Datos bancarios/cuentas no pertenecen a contactos; se trataran como dato financiero sensible en una tarea posterior coordinada con Finanzas.

Archivo ajustado:

- `app/modelos/ProveedoresEsquema.php`

Alcance no implementado:

- No se ejecuto migracion.
- No se agregaron columnas reales.
- No se modificaron datos.
- No se definio catalogo cerrado de areas.
- No se implemento envio de ordenes/notificaciones.

## Evidencia P1-06 - condiciones comerciales/logisticas

Estado: autorizado y aplicado en contrato planeado el 2026-06-12.

Decision:

- Las condiciones comerciales y logisticas viven en `erp_proveedores_condiciones`.
- Estas condiciones son referencia operativa para Compras y planeacion, pero no sustituyen autorizacion financiera.
- Bancos/cuentas siguen fuera de este bloque y se trataran como dato financiero sensible con Finanzas.

Campos de condiciones planeados:

- `id_condicion_proveedor`.
- `id_proveedor`.
- `moneda_preferida`.
- `requiere_orden_compra`.
- `forma_pago_preferida`.
- `metodo_pago_preferido`.
- `dias_credito`.
- `limite_credito`.
- `minimo_compra`.
- `minimo_unidades`.
- `tiempo_entrega_dias`.
- `dias_surtido`.
- `tipo_flete`.
- `cobertura_entrega`.
- `condiciones_pago`.
- `condiciones_logisticas`.
- `restricciones_operativas`.
- `observaciones`.
- `vigencia_desde`.
- `vigencia_hasta`.
- `autorizado_por`.
- `fecha_autorizacion`.
- `estatus`.
- `fecha_registro`.
- `fecha_actualizacion`.

Indices planeados:

- `idx_proveedor_condicion_proveedor`: `id_proveedor`, `estatus`.
- `idx_proveedor_condicion_moneda`: `moneda_preferida`.
- `idx_proveedor_condicion_vigencia`: `vigencia_desde`, `vigencia_hasta`.

Reglas documentadas:

- `forma_pago_preferida` y `metodo_pago_preferido` son referencia operativa, no autorizacion para pagar.
- `limite_credito`, `dias_credito` y `minimo_compra` son condiciones del proveedor; su impacto real en pagos/ordenes queda sujeto a autorizaciones de Compras/Finanzas.
- `requiere_orden_compra` ayuda a controlar comunicacion con proveedor, pero no crea por si solo una regla de bloqueo hasta que se defina con Compras.
- El historial se conserva por vigencia y estatus, no reemplazando silenciosamente condiciones previas.

Archivo ajustado:

- `app/modelos/ProveedoresEsquema.php`

Alcance no implementado:

- No se ejecuto migracion.
- No se agregaron columnas reales.
- No se modificaron datos.
- No se definieron catalogos cerrados para flete, forma/metodo de pago ni cobertura.
- No se implementaron bloqueos en Compras.

## Evidencia P1-07 - documentos/evidencias

Estado: autorizado y aplicado en contrato planeado el 2026-06-12.

Decision:

- Los documentos/evidencias viven en `erp_proveedores_documentos`.
- La tabla soporta constancias fiscales, contratos, listas, evidencias de costo, evidencias comerciales y evidencias financieras sensibles.
- Evidencia financiera sensible puede guardarse como documento controlado, pero no autoriza pagos ni sustituye el flujo de Finanzas.

Campos de documentos planeados:

- `id_documento_proveedor`.
- `id_proveedor`.
- `tipo_documento`.
- `nivel_sensibilidad`.
- `entidad_origen`.
- `id_referencia`.
- `referencia_tipo`.
- `referencia`.
- `archivo_nombre`.
- `archivo_ruta`.
- `archivo_tipo`.
- `archivo_tamano`.
- `archivo_hash`.
- `metadatos_json`.
- `vigencia_desde`.
- `vigencia_hasta`.
- `validado_por`.
- `fecha_validacion`.
- `estatus`.
- `creado_por`.
- `cancelado_por`.
- `fecha_cancelacion`.
- `motivo_cancelacion`.
- `fecha_registro`.
- `fecha_actualizacion`.

Indices planeados:

- `idx_proveedor_documento_proveedor`: `id_proveedor`, `estatus`.
- `idx_proveedor_documento_tipo`: `id_proveedor`, `tipo_documento`, `estatus`.
- `idx_proveedor_documento_hash`: `id_proveedor`, `archivo_hash`.
- `idx_proveedor_documento_referencia`: `referencia_tipo`, `id_referencia`.

Reglas documentadas:

- Todo documento debe conservar hash, usuario de carga y estatus.
- No debe borrarse evidencia operativa sin rastro; se cancela logicamente con usuario, fecha y motivo.
- `nivel_sensibilidad` separa evidencia normal de evidencia confidencial o financiera sensible.
- Una constancia fiscal se liga desde `erp_proveedores_fiscales.id_documento_constancia`.
- Una lista de proveedor se liga desde `erp_proveedores_listas_erp.id_documento_proveedor`.
- Evidencias de bancos/cuentas pueden almacenarse aqui como sensibles, pero alta/validacion/uso para pagos debe pertenecer a Finanzas.

Archivo ajustado:

- `app/modelos/ProveedoresEsquema.php`

Alcance no implementado:

- No se ejecuto migracion.
- No se agregaron columnas reales.
- No se modificaron datos.
- No se implemento carga de archivos.
- No se definio almacenamiento fisico final.
- No se implementaron permisos especiales para evidencia sensible.

## Evidencia P2-01 - auditoria de permisos actuales

Estado: aplicado como auditoria documental el 2026-06-12.

Archivos revisados:

- `app/modelos/SeguridadPermisos.php`.
- `app/modelos/SeguridadEsquema.php`.
- `app/controladores/Sistema.php`.
- `app/core/Core.php`.
- `app/controladores/Proveedor.php`.
- `app/controladores/CatalogoErp.php`.

Hallazgos:

- No existen permisos base `proveedores.*` en `SeguridadEsquema::permisosBaseERP()`.
- `Proveedor.php` esta protegido por sesion en constructor.
- `Proveedor.php` usa permisos prestados de Compras:
  - `compras.ver` para vistas, consultas, auditoria y endpoints de lectura.
  - `compras.crear` para altas/pedidos legacy.
  - `compras.editar` para edicion, carga de listas y cambios legacy.
- La pantalla tecnica nueva `/proveedor/auditoria_erp` y endpoints `/proveedor/auditoria_dry_run_erp`, `/proveedor/esquema_auditar_erp` usan `compras.ver` como transicion documentada.
- Relaciones proveedor-SKU y costos actuales viven parcialmente en Catalogo con `catalogo.costos`:
  - `CatalogoErp::guardar_sku_proveedor()`.
  - `CatalogoErp::relaciones_proveedor_sincronizar()`.
  - `CatalogoErp::propuestas_costos_aplicar()`.
- `Core.php` ya tiene auditoria explicita para varias acciones de Catalogo, Compras, Almacen e Inventario, incluyendo acciones relacionadas con proveedor-SKU en Catalogo.
- No hay auditoria explicita especifica para acciones nuevas de Proveedores; por ahora solo aplica auditoria generica para POST autenticados cuando corresponda.

Riesgos:

- Un usuario con `compras.editar` puede operar partes legacy de Proveedores aunque no deberia tener permisos finos de maestro, fiscales, documentos o costos.
- `catalogo.costos` mezcla mantenimiento de costos/catalogo con relaciones proveedor-SKU, lo cual no escala bien para delegar tareas por departamento.
- La evidencia sensible de proveedores requerira permisos separados antes de implementar carga/consulta de documentos.
- Cambiar permisos de golpe puede romper pantallas legacy; la transicion debe ser gradual.

Permisos candidatos para `P2-02`:

- `proveedores.ver`: consultar maestro, ficha y datos no sensibles.
- `proveedores.crear`: alta inicial de proveedor.
- `proveedores.editar`: editar datos generales.
- `proveedores.fiscales`: consultar/editar fiscales, segun rol.
- `proveedores.contactos`: administrar contactos.
- `proveedores.condiciones`: administrar condiciones comerciales/logisticas.
- `proveedores.documentos`: administrar documentos no sensibles.
- `proveedores.documentos_sensibles`: consultar/cargar evidencia confidencial o financiera sensible.
- `proveedores.listas`: cargar y administrar listas.
- `proveedores.matching`: conciliar SKU proveedor contra SKU ERP.
- `proveedores.costos`: administrar costos proveedor-SKU y evidencia de costo.
- `proveedores.autorizar`: autorizar estatus, condiciones, listas o costos segun flujo.
- `proveedores.auditoria`: consultar auditorias y dry-runs del modulo.

Permisos que deben coordinarse con otros modulos:

- `catalogo.costos`: sigue siendo necesario mientras Catalogo controle `erp_catalogo_sku_proveedores`.
- `compras.ver/crear/editar/aprobar`: debe consumir Proveedores, no administrar maestro robusto.
- `finanzas.ver/operar`: debe controlar validacion y uso de datos bancarios/cuentas para pagos.
- `auditoria.ver`: puede consultar trazabilidad general, pero no necesariamente documentos sensibles.

Regla de transicion recomendada:

- Mantener endpoints legacy de `Proveedor.php` con `compras.*` hasta reemplazarlos por flujo ERP nuevo.
- Nuevos endpoints ERP de Proveedores deben migrar a `proveedores.*` cuando se implemente `P2-04`.
- Endpoints de solo lectura tecnica pueden seguir temporalmente con `compras.ver` hasta que exista `proveedores.auditoria`.
- No exponer documentos sensibles con `compras.ver`.

Alcance no implementado:

- No se cambiaron permisos reales.
- No se modificaron roles.
- No se ejecuto `SeguridadEsquema`.
- No se tocaron endpoints.
- No se agregaron auditorias explicitas todavia.

## Evidencia P2-02 - matriz final de permisos Proveedores

Estado: decision documental aplicada el 2026-06-12.

Permisos finales propuestos:

| Permiso | Alcance |
| --- | --- |
| `proveedores.ver` | Consultar listado, ficha general y datos no sensibles del proveedor. |
| `proveedores.crear` | Crear proveedor y captura inicial general. |
| `proveedores.editar` | Editar datos generales no fiscales/no financieros. |
| `proveedores.fiscales` | Consultar y administrar datos fiscales y constancia fiscal. |
| `proveedores.contactos` | Administrar contactos por area. |
| `proveedores.condiciones` | Administrar condiciones comerciales/logisticas. |
| `proveedores.documentos` | Administrar documentos no sensibles: contratos, listas, evidencias operativas. |
| `proveedores.documentos_sensibles` | Consultar/cargar evidencia confidencial o financiera sensible. No autoriza pagos. |
| `proveedores.listas` | Cargar, consultar y administrar listas de proveedor como evidencia versionada. |
| `proveedores.matching` | Conciliar renglones/SKU proveedor contra SKU ERP. |
| `proveedores.costos` | Administrar costos proveedor-SKU, vigencias y evidencia de costo. |
| `proveedores.autorizar` | Autorizar estatus, condiciones, listas, relaciones o costos segun flujo aprobado. |
| `proveedores.auditoria` | Consultar auditorias, dry-runs y trazabilidad del modulo. |

Matriz sugerida por rol base:

| Rol base | Permisos proveedores sugeridos |
| --- | --- |
| `direccion` | `proveedores.ver`, `proveedores.fiscales`, `proveedores.condiciones`, `proveedores.documentos`, `proveedores.documentos_sensibles`, `proveedores.listas`, `proveedores.costos`, `proveedores.autorizar`, `proveedores.auditoria` |
| `administrador_erp` | Todos los permisos `proveedores.*` |
| `compras` | `proveedores.ver`, `proveedores.crear`, `proveedores.editar`, `proveedores.contactos`, `proveedores.condiciones`, `proveedores.documentos`, `proveedores.listas`, `proveedores.matching`, `proveedores.auditoria` |
| `catalogo_productos` | `proveedores.ver`, `proveedores.listas`, `proveedores.matching`, `proveedores.costos`, `proveedores.auditoria` |
| `finanzas_contabilidad` | `proveedores.ver`, `proveedores.fiscales`, `proveedores.condiciones`, `proveedores.documentos`, `proveedores.documentos_sensibles`, `proveedores.costos`, `proveedores.autorizar`, `proveedores.auditoria` |
| `almacen` | `proveedores.ver`, `proveedores.contactos` |
| `inventario` | `proveedores.ver` |
| `auditor` | `proveedores.ver`, `proveedores.auditoria`; documentos sensibles solo si Direccion lo autoriza |
| `solo_lectura` | `proveedores.ver` |
| `soporte_sistema` | `proveedores.auditoria` para soporte tecnico, sin documentos sensibles |

Reglas documentadas:

- `proveedores.documentos_sensibles` se separa de `proveedores.documentos` para proteger constancias, evidencia financiera sensible y documentos confidenciales.
- `proveedores.costos` no debe implicar permiso para pagar; costos proveedor-SKU y pagos son responsabilidades separadas.
- `proveedores.autorizar` no debe ser un permiso de edicion general; debe usarse para cambios de estado o aprobaciones puntuales.
- `proveedores.auditoria` permite ver auditorias/dry-runs, pero no necesariamente documentos sensibles.
- `compras.*` debe quedar para operar solicitudes y ordenes, no para administrar maestro robusto de Proveedores.
- `catalogo.costos` seguira existiendo durante transicion mientras algunos endpoints proveedor-SKU vivan en Catalogo.
- `finanzas.operar` sigue siendo el permiso de uso de pagos/saldos/cuentas; `proveedores.documentos_sensibles` solo cubre evidencia sensible del proveedor.

Nota historica:

- El alcance siguiente describe el estado al cerrar P2-02. P2-04 confirma que despues se agregaron y sembraron los permisos.

Alcance no implementado:

- No se agregaron permisos en `SeguridadEsquema.php`.
- No se sembraron permisos en BD.
- No se cambiaron roles reales.
- No se cambiaron endpoints de `Proveedor.php`.
- No se ejecuto migracion ni plan de seguridad.

## Evidencia P2-03 - plan de migracion de permisos prestados

Estado: plan documental aplicado el 2026-06-12.

Objetivo:

- Migrar gradualmente de permisos prestados `compras.*`/`catalogo.costos` hacia `proveedores.*`.
- Evitar romper pantallas legacy mientras se construye el flujo ERP nuevo.
- Separar lectura, escritura, fiscales, documentos sensibles, listas, matching, costos y auditoria.

Mapa de rutas nuevas ya creadas:

| Ruta/metodo | Permiso actual | Permiso destino |
| --- | --- | --- |
| `Proveedor::auditoria_erp` | `compras.ver` | `proveedores.auditoria` |
| `Proveedor::auditoria_dry_run_erp` | `compras.ver` | `proveedores.auditoria` |
| `Proveedor::esquema_auditar_erp` | `compras.ver` | `proveedores.auditoria` |

Mapa de maestro legacy actual:

| Grupo | Metodos actuales | Permiso actual | Destino recomendado |
| --- | --- | --- | --- |
| Alta legacy simple | `crear`, `registrar` | `compras.crear` | Mantener legacy temporal; nuevo flujo usara `proveedores.crear` |
| Consulta legacy | `listar`, `consultar` | `compras.ver` | Mantener legacy temporal; nuevo listado/ficha usara `proveedores.ver` |
| Edicion legacy de producto/lista | `consultar_producto_editar`, `actualizar_lista_producto`, `actualizar_portada` | `compras.editar` | Mantener legacy temporal; reemplazo futuro se divide en `proveedores.listas`, `proveedores.matching`, `proveedores.costos` |

Mapa de listas legacy:

| Grupo | Metodos actuales | Permiso actual | Destino recomendado |
| --- | --- | --- | --- |
| Vistas/listado de listas | `listas_mostrar`, `lista_productos`, `mostrar_lista_productos`, `listas_consultar`, `consultar_productos_lista`, `consultar_productos_proveedor_busqueda` | `compras.ver` | `proveedores.listas` para flujo nuevo; legacy temporal con `compras.ver` |
| Carga de listas | `cargar_lista`, `registrar_lista` | `compras.editar` | `proveedores.listas`; si aplica costos o relaciones, combinar con `proveedores.matching`/`proveedores.costos` |
| Listas mayoreo legacy | `listas_mayoreo_mostrar`, `cargar_lista_mayoreo`, `registrar_lista_mayoreo`, `listas_mayoreo_consultar` | `compras.ver`/`compras.editar` | Mantener como legacy separado hasta decidir si pertenece a Proveedores ERP |

Mapa de pedidos legacy:

| Grupo | Metodos actuales | Permiso actual | Destino recomendado |
| --- | --- | --- | --- |
| Vistas de pedidos | `mostrar_pedidos`, `nuevo_pedido`, `editar_pedido`, `pedido_productos` | `compras.ver`/`compras.crear`/`compras.editar` | Mantener legacy; compras ERP nuevo debe vivir en `Compra.php` |
| Operacion de pedidos legacy | `consultar_pedidos`, `registrar_pedido`, `actualizar_pedido`, `consulta_completa_pedido`, `consultar_pedido_productos_lista`, `generar_orden_de_compra` | `compras.*` | Mantener legacy o retirar gradualmente; no migrar a maestro Proveedores sin rediseño |

Mapa de usuarios/listas mayoreo legacy:

| Grupo | Metodos actuales | Permiso actual | Destino recomendado |
| --- | --- | --- | --- |
| Usuarios mayoreo | `usuarios_mayoreo_mostrar`, `usuario_mayoreo_listas`, `usuarios_mayoreo_consultar`, `listas_usuario_mayoreo_consultar`, `actualizar_estatus_lista_mayoreo_usuario_mayoreo` | `compras.ver`/`compras.editar` | Mantener fuera del flujo ERP Proveedores hasta decidir modulo responsable |
| Estatus mayoreo | `actualizar_estatus_lista_mayoreo` | `compras.editar` | Legacy temporal; no mezclar con estados ERP de proveedor |

Mapa de Catalogo relacionado con proveedor-SKU:

| Ruta/metodo | Permiso actual | Destino recomendado |
| --- | --- | --- |
| `CatalogoErp::propuestas_costos_proveedor` | `catalogo.ver` | Lectura futura puede moverse a `proveedores.costos` o quedar compartida con `catalogo.ver` |
| `CatalogoErp::relaciones_proveedor_historicas` | `catalogo.ver` | Lectura futura: `proveedores.matching` |
| `CatalogoErp::relaciones_proveedor_sincronizar` | `catalogo.costos` | Escritura futura: `proveedores.matching` + auditoria; mientras afecte SKU/costo, coordinar con `catalogo.costos` |
| `CatalogoErp::propuestas_costos_aplicar` | `catalogo.costos` | Futuro: `proveedores.costos` + autorizacion si aplica |
| `CatalogoErp::guardar_sku_proveedor` | `catalogo.costos` | Futuro: `proveedores.matching` o `proveedores.costos` segun si cambia relacion o costo |

Regla de transicion:

- No cambiar permisos de endpoints legacy hasta que exista pantalla/modelo ERP nuevo que los reemplace.
- Los endpoints nuevos de auditoria deben migrar primero a `proveedores.auditoria` cuando se siembren permisos.
- El primer flujo nuevo de maestro debe usar `proveedores.ver`, `proveedores.crear`, `proveedores.editar`, `proveedores.fiscales`, `proveedores.contactos`, `proveedores.condiciones`, `proveedores.documentos` y `proveedores.documentos_sensibles`.
- Matching y costos no deben migrarse desde Catalogo de golpe; primero crear endpoints Proveedores propios y dejar Catalogo como soporte durante transicion.
- Pedidos legacy de Proveedores no deben convertirse automaticamente en Compras ERP nuevo.

Nota historica:

- El alcance siguiente describe el estado al cerrar P2-03. P2-04 y P2-05 confirman que despues se sembraron permisos y se retiro el fallback de los endpoints nuevos.

Alcance no implementado:

- No se cambiaron permisos reales.
- No se modificaron endpoints.
- No se sembraron permisos en BD.
- No se tocaron roles.
- No se movio logica desde Catalogo a Proveedores.

## Evidencia P2-04 - permisos Proveedores en esquema de seguridad

Estado: autorizado, aplicado en codigo y sembrado en BD el 2026-06-12.

Archivo modificado:

- `app/modelos/SeguridadEsquema.php`

Alcance implementado:

- Se agregaron permisos `proveedores.*` a `SeguridadEsquema::permisosBaseERP()`.
- Se agrego asignacion base de permisos a `SeguridadEsquema::permisosPorRolBaseERP()`.
- La asignacion respeta la matriz documentada en `P2-02`.
- `proveedores.documentos_sensibles` queda restringido a Direccion, Administrador ERP y Finanzas en la asignacion base.
- Soporte solo recibe `proveedores.auditoria`, sin documentos sensibles.
- Solo lectura recibe `proveedores.ver`.

Permisos agregados al plan:

- `proveedores.ver`.
- `proveedores.crear`.
- `proveedores.editar`.
- `proveedores.fiscales`.
- `proveedores.contactos`.
- `proveedores.condiciones`.
- `proveedores.documentos`.
- `proveedores.documentos_sensibles`.
- `proveedores.listas`.
- `proveedores.matching`.
- `proveedores.costos`.
- `proveedores.autorizar`.
- `proveedores.auditoria`.

Ejecucion autorizada:

- Se ejecuto el plan de seguridad desde CLI con `SeguridadEsquema::planActualizarSeguridad(true)`.
- Resultado: `error=false`, `tipo=success`, mensaje `Plan de seguridad ejecutado`, total reportado `231`.
- Verificacion posterior: `13` permisos `proveedores.*` en `sys_permisos`.
- Verificacion posterior: `51` asignaciones de esos permisos en `sys_roles_permisos`.

Alcance no implementado:

- No se cambiaron endpoints legacy.
- No se movieron permisos de rutas antiguas de listas, pedidos, mayoreo o cargas legacy.
- No se tocaron usuarios manualmente fuera del plan base de seguridad.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\SeguridadEsquema.php`: sin errores de sintaxis.
- Siembra seguridad: `{"error":false,"tipo":"success","mensaje":"Plan de seguridad ejecutado","total":231}`.
- Conteo BD: `{"permisos":13,"asignaciones_roles":51}`.

## Evidencia P2-05 - endpoints nuevos con permiso propio

Estado: autorizado y aplicado sin fallback temporal el 2026-06-12.

Archivo modificado:

- `app/controladores/Proveedor.php`

Endpoints ajustados:

- `Proveedor::auditoria_erp()`.
- `Proveedor::auditoria_dry_run_erp()`.
- `Proveedor::esquema_auditar_erp()`.

Permiso destino:

- `proveedores.auditoria`.

Fallback temporal:

- Retirado despues de sembrar `proveedores.*` en BD.

Motivo del retiro:

- Los permisos `proveedores.*` ya existen en BD y estan asignados a roles base.
- `SeguridadPermisos::usuarioTienePermiso()` consulta permisos en BD, por lo que los endpoints nuevos pueden usar permiso propio.

Alcance implementado:

- Se retiro el helper privado `Proveedor::requerirPermisoProveedorTransicion()`.
- `Proveedor::auditoria_erp()`, `Proveedor::auditoria_dry_run_erp()` y `Proveedor::esquema_auditar_erp()` usan `proveedores.auditoria`.
- Se agrego `Proveedor::esquema_actualizar_erp()` con permiso `sistema.soporte` para ejecutar el plan tecnico de esquema cuando se autorice.
- Solo se tocaron endpoints nuevos de auditoria.

Alcance no implementado:

- No se tocaron endpoints legacy.
- No se cambio ninguna ruta de listas, pedidos, mayoreo o carga legacy.
- No se cambiaron endpoints legacy para seguir evitando mezcla de flujo viejo con ERP nuevo.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.

## Evidencia P2-06 - auditoria explicita en acciones sensibles

Estado: plan documental aplicado el 2026-06-12.

Objetivo:

- Definir que acciones nuevas de Proveedores deben tener auditoria explicita antes de implementar endpoints de escritura.
- Evitar que cambios sensibles queden solo en auditoria generica de POST.
- Preparar el contrato para `Core.php` y/o llamadas directas a `SesionSeguridad::registrarAuditoria()`.

Acciones nuevas que requieren auditoria explicita:

| Accion | Entidad | Motivo |
| --- | --- | --- |
| `proveedor_crear` | `erp_proveedores` / `erp_proveedores_perfil` | Alta de proveedor maestro. |
| `proveedor_editar_generales` | `erp_proveedores` / `erp_proveedores_perfil` | Cambia datos generales y responsables. |
| `proveedor_estatus_cambiar` | `erp_proveedores` | Puede habilitar o bloquear operacion. |
| `proveedor_fiscal_guardar` | `erp_proveedores_fiscales` | Impacta compras, XML, pagos y cumplimiento fiscal. |
| `proveedor_fiscal_validar` | `erp_proveedores_fiscales` | Marca datos fiscales como validados. |
| `proveedor_contacto_guardar` | `erp_proveedores_contactos` | Cambia canales oficiales de comunicacion. |
| `proveedor_contacto_cancelar` | `erp_proveedores_contactos` | Baja logica o historica de contacto. |
| `proveedor_condicion_guardar` | `erp_proveedores_condiciones` | Impacta credito, moneda, minimos y logistica. |
| `proveedor_condicion_autorizar` | `erp_proveedores_condiciones` | Convierte condiciones en vigentes/autorizadas. |
| `proveedor_documento_subir` | `erp_proveedores_documentos` | Carga evidencia o documento. |
| `proveedor_documento_cancelar` | `erp_proveedores_documentos` | Cancela evidencia sin borrado fisico obligatorio. |
| `proveedor_documento_sensible_consultar` | `erp_proveedores_documentos` | Acceso a evidencia confidencial/financiera sensible. |
| `proveedor_lista_cargar` | `erp_proveedores_listas_erp` | Carga lista como evidencia versionada. |
| `proveedor_lista_validar` | `erp_proveedores_listas_erp` | Valida encabezado/metadatos de lista. |
| `proveedor_lista_descartar` | `erp_proveedores_listas_erp` | Descarta lista con motivo. |
| `proveedor_matching_decidir` | `erp_proveedores_listas_detalle_erp` | Relaciona/rechaza/ambigua SKU proveedor contra SKU ERP. |
| `proveedor_sku_relacion_guardar` | `erp_catalogo_sku_proveedores` | Crea o actualiza relacion proveedor-SKU. |
| `proveedor_costo_guardar` | `erp_proveedores_sku_costos` | Registra costo proveedor-SKU. |
| `proveedor_costo_autorizar` | `erp_proveedores_sku_costos` | Convierte costo propuesto en vigente/autorizado. |
| `proveedor_incidencia_generar` | `erp_catalogo_incidencias_calidad` | Escala huecos hacia Catalogo. |

Datos minimos de auditoria por evento:

- `id_proveedor`.
- Entidad afectada.
- ID de entidad afectada.
- Resultado.
- Usuario.
- Datos antes, cuando exista.
- Datos despues, cuando exista.
- Motivo/observacion en cambios de estado, cancelacion o descarte.
- Origen de la accion: maestro, lista, matching, costo, documento o incidencia.

Reglas documentadas:

- Las acciones de consulta normal no requieren auditoria explicita, salvo documentos sensibles.
- Documentos sensibles deben auditar consulta, carga, validacion y cancelacion.
- Cambios de estatus, autorizaciones y cancelaciones deben registrar motivo.
- Matching y costos deben registrar evidencia/origen para no perder trazabilidad.
- Endpoints legacy de Proveedores no se auditan masivamente en esta fase; se migraran o retiraran gradualmente.

Aplicacion futura:

- Cuando existan endpoints nuevos, agregar la accion a `Core.php` si conviene auditoria explicita transversal.
- Para casos con datos antes/despues ricos, registrar desde el controlador/modelo con `SesionSeguridad::registrarAuditoria()`.
- No usar auditoria como sustituto de permisos; ambas capas son necesarias.

Alcance no implementado:

- No se modifico `Core.php`.
- No se agregaron llamadas nuevas a `SesionSeguridad::registrarAuditoria()`.
- No se crearon endpoints de escritura.
- No se modificaron datos.

## Evidencia P3-01/P3-02/P3-03 - lectura operativa del maestro Proveedores ERP

Estado: autorizado y aplicado en solo lectura el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoints agregados:

- `/proveedor/proveedores_listar_erp` con permiso `proveedores.ver`.
- `/proveedor/proveedor_consultar_erp` con permiso `proveedores.ver`.

Metodos agregados:

- `Proveedores::listarProveedoresErp($filtros)`.
- `Proveedores::consultarProveedorErp($id_proveedor, $incluir_sensibles = false)`.

Alcance implementado:

- Listado paginado de proveedores ERP usando `erp_proveedores` como raiz y `erp_proveedores_perfil`/`erp_proveedores_fiscales` como soporte.
- Filtros iniciales: busqueda, `estatus_erp`, `tipo_proveedor`, pagina y limite.
- Consulta de ficha con secciones de perfil, fiscales, contactos, condiciones, documentos, listas ERP y resumen de costos.
- SQL preparado para entradas de usuario.
- Sin escritura de datos.
- Sin formularios de alta/edicion.
- Sin migrar datos legacy a tablas nuevas.
- Sin definir estados finales de negocio.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- Prueba CLI solo lectura: `{"listado_error":false,"listado_total":23,"listado_registros":3,"consulta_id":18,"consulta_error":false,"consulta_tipo":"success"}`.

## Evidencia P3-09/P3-10 - pantalla de listado y ficha Proveedores ERP

Estado: autorizado y aplicado en solo lectura el 2026-06-12.

Archivos modificados/agregados:

- `app/controladores/Proveedor.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Ruta agregada:

- `/proveedor/mostrar_proveedores_erp` con permiso `proveedores.ver`.

Alcance implementado:

- Pantalla nueva de Proveedores ERP, separada de pantallas legacy grandes.
- Listado con busqueda, filtro libre por `estatus_erp`, filtro libre por `tipo_proveedor`, limite y paginacion.
- Tabla con proveedor, fiscal, tipo, estado, conteo de contactos y conteo de listas ERP.
- Modal de ficha 360 en tabs: general, fiscal/contactos, condiciones/documentos, listas/costos.
- Consumo de endpoints de lectura por GET para evitar CSRF de POST en consultas sin escritura.
- Boton hacia auditoria tecnica `/proveedor/auditoria_erp`.

Alcance no implementado:

- Menu/sidebar habilitado despues en `P10-01`.
- No se implementaron formularios de alta/edicion.
- No se guardan datos.
- No se migran datos legacy.
- No se definen estados finales ni catalogos cerrados.
- No se cambia el flujo legacy de listas/pedidos.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

## Evidencia P3-04 - guardado de datos generales Proveedores ERP

Estado: autorizado y aplicado el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_generales_guardar_erp`.

Permisos:

- Alta: `proveedores.crear`.
- Edicion: `proveedores.editar`.

Alcance implementado:

- Crear proveedor raiz en `erp_proveedores` con `proveedor`, `origen_erp` y `fecha_actualizacion`.
- Actualizar proveedor raiz existente solo en `proveedor`, `origen_erp` y `fecha_actualizacion`.
- Crear/actualizar perfil en `erp_proveedores_perfil` con nombre comercial, nombre corto, codigo ERP, tipo, clasificacion, origen, responsable interno y notas.
- Formulario UI para nuevo proveedor y editar generales desde la ficha.
- Envio POST con `_csrf` y header `X-CSRF-Token`.
- Auditoria explicita `proveedor_crear` / `proveedor_editar_generales` con `datos_antes` y `datos_despues`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.

Limites aplicados:

- No cambia `estatus_erp`.
- No modifica `cuota` legacy.
- No toca fiscales, contactos, condiciones, documentos, listas ni costos.
- No define catalogos cerrados de tipo/clasificacion/origen.
- No migra datos legacy.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se creo proveedor de prueba para no contaminar datos reales.

## Evidencia P3-05 - guardado de datos fiscales Proveedores ERP

Estado: autorizado y aplicado el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_fiscal_guardar_erp`.

Permiso:

- `proveedores.fiscales`.

Alcance implementado:

- Alta/edicion de registro fiscal en `erp_proveedores_fiscales`.
- Campos capturados: RFC, razon social, regimen fiscal, codigo postal fiscal, domicilio granular, domicilio completo, uso CFDI preferido, fecha de constancia, vigencia desde/hasta y estatus.
- Formulario fiscal dentro de la ficha Proveedores ERP.
- Boton para agregar fiscal y boton por renglon para editar fiscal existente.
- Envio POST con `_csrf` y header `X-CSRF-Token`.
- Auditoria explicita `proveedor_fiscal_guardar` con `datos_antes` y `datos_despues`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.

Limites aplicados:

- No valida RFC contra SAT.
- No marca fiscal como validado.
- No carga ni vincula archivo fisico de constancia.
- No define obligatoriedad para `activo_compras` ni `activo_pagos`.
- No modifica datos generales, contactos, condiciones, documentos, listas ni costos.
- No borra registros fiscales.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se creo registro fiscal de prueba para no contaminar datos reales.

## Evidencia P3-06 - guardado de contactos Proveedores ERP

Estado: autorizado y aplicado el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_contacto_guardar_erp`.

Permiso:

- `proveedores.contactos`.

Alcance implementado:

- Alta/edicion de contacto en `erp_proveedores_contactos`.
- Campos capturados: area, nombre, puesto, correo, telefono, extension, celular, WhatsApp, prioridad, observaciones y estatus.
- Flags operativos: principal, recibe ordenes de compra y recibe notificaciones.
- Formulario de contacto dentro de la ficha Proveedores ERP.
- Boton para agregar contacto y boton por renglon para editar contacto existente.
- Envio POST con `_csrf` y header `X-CSRF-Token`.
- Auditoria explicita `proveedor_contacto_guardar` con `datos_antes` y `datos_despues`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.

Limites aplicados:

- No envia correos, WhatsApp ni notificaciones.
- No manda ordenes de compra automaticamente.
- No borra contactos.
- No define catalogo cerrado de areas.
- No toca fiscales, condiciones, documentos, listas ni costos.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se creo contacto de prueba para no contaminar datos reales.

## Evidencia P3-07 - guardado de condiciones Proveedores ERP

Estado: autorizado y aplicado el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_condicion_guardar_erp`.

Permiso:

- `proveedores.condiciones`.

Alcance implementado:

- Alta/edicion de condiciones en `erp_proveedores_condiciones`.
- Campos capturados: moneda preferida, requiere OC, forma/metodo de pago, dias credito, limite credito, minimo compra, minimo unidades, tiempo entrega, dias surtido, tipo flete, cobertura, condiciones de pago, condiciones logisticas, restricciones, observaciones, vigencia y estatus.
- Formulario de condiciones dentro de la ficha Proveedores ERP.
- Boton para agregar condicion y boton por renglon para editar condicion existente.
- Envio POST con `_csrf` y header `X-CSRF-Token`.
- Auditoria explicita `proveedor_condicion_guardar` con `datos_antes` y `datos_despues`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.

Limites aplicados:

- No bloquea Compras.
- No autoriza pagos.
- No define condiciones como vigentes/autorizadas por flujo.
- No crea catalogos cerrados de moneda, flete, forma/metodo de pago ni cobertura.
- No toca fiscales, contactos, documentos, listas ni costos.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se creo condicion de prueba para no contaminar datos reales.

## Evidencia P3-11 - documentos/evidencias metadata Proveedores ERP

Estado: autorizado y aplicado el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_documento_guardar_erp`.

Permisos:

- `proveedores.documentos` para guardar documentos no sensibles.
- `proveedores.documentos_sensibles` adicional cuando `nivel_sensibilidad` es `sensible`, `confidencial`, `financiero`, `financiera`, `bancario` o `bancaria`.

Alcance implementado:

- Alta/edicion de metadatos en `erp_proveedores_documentos`.
- Campos capturados: tipo de documento, nivel de sensibilidad, entidad/origen, referencia, nombre/tipo/tamano/hash de archivo, metadatos JSON, vigencia y estatus.
- Validacion minima: requiere tipo, referencia o nombre de archivo.
- Validacion de JSON cuando se capturan metadatos.
- La consulta de ficha `/proveedor/proveedor_consultar_erp` ya filtra documentos sensibles si el usuario no tiene `proveedores.documentos_sensibles`.
- La edicion de un documento ya marcado como sensible tambien exige `proveedores.documentos_sensibles`, aunque el POST intente cambiar el nivel a uno no sensible.
- La consulta de ficha registra auditoria `proveedor_documento_sensible_consultar` cuando devuelve documentos sensibles.
- Formulario de documentos dentro de la ficha Proveedores ERP.
- Boton para agregar documento y boton por renglon para editar documento visible.
- Envio POST con `_csrf` y header `X-CSRF-Token`.
- Auditoria explicita `proveedor_documento_guardar` con `datos_antes` y `datos_despues`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.

Limites aplicados:

- No sube archivo fisico.
- No descarga ni expone rutas de archivo.
- No borra documentos ni archivos.
- No valida constancia fiscal contra SAT.
- No define catalogo cerrado de tipos de documento ni niveles de sensibilidad.
- No cambia estatus ERP del proveedor ni autoriza compras/pagos.
- No toca fiscales, contactos, condiciones, listas ni costos.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se creo documento de prueba para no contaminar datos reales.

## Evidencia P4-03 - auditoria de listas legacy candidatas a migracion

Estado: autorizado y aplicado en solo lectura el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/auditoria.php`.
- `public/assets/js/custom/apps/erp/proveedores/auditoria.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint usado:

- `/proveedor/auditoria_dry_run_erp`.

Permiso:

- `proveedores.auditoria`.

Alcance implementado:

- El dry-run conserva los conteos tecnicos existentes.
- Se agregaron muestras limitadas para decidir migracion/limpieza:
  - listas legacy con renglones;
  - listas sin proveedor valido;
  - renglones sin costo positivo;
  - renglones con SKU proveedor sin match exacto contra SKU ERP.
- La pantalla tecnica `/proveedor/auditoria_erp` muestra una tabla de muestras legacy.
- Las muestras estan limitadas a 10 por grupo.

Limites aplicados:

- No migra listas legacy.
- No crea encabezados ni renglones ERP nuevos.
- No modifica Catalogo, Compras ni costos.
- No crea incidencias.
- No exporta archivos.
- No define reglas de seleccion masiva ni criterios de autorizacion.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\auditoria.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\auditoria.js`: sin errores de sintaxis.
- No se modificaron datos.

## Evidencia P4-04/P4-06 - encabezado y evidencia de listas versionadas Proveedores ERP

Estado: autorizado y aplicado parcialmente el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_lista_guardar_erp`.

Permisos:

- `proveedores.listas` para crear/editar encabezados de lista ERP.
- `proveedores.documentos_sensibles` adicional si la lista referencia un documento sensible como evidencia.

Alcance implementado:

- Alta/edicion de encabezados versionados en `erp_proveedores_listas_erp`.
- Campos capturados: nombre de lista, version, origen, moneda, vigencia, fecha de emision, lista legacy origen, documento de evidencia, estatus y observaciones.
- La ficha 360 muestra listas ERP del proveedor y permite editar cada encabezado.
- Cada lista se guarda como registro independiente; no reemplaza silenciosamente versiones anteriores.
- Se valida que el documento de evidencia pertenezca al mismo proveedor.
- Envio POST con `_csrf` y header `X-CSRF-Token`.
- Auditoria explicita `proveedor_lista_guardar` con `datos_antes` y `datos_despues`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.

Limites aplicados:

- La carga fisica de archivo original de lista ya quedo cubierta despues por `/proveedor/proveedor_lista_archivo_subir_erp`.
- La importacion automatica de renglones ya quedo cubierta despues por vista previa/mapeo controlado; no aplica relaciones ni costos.
- La carga/importacion de renglones queda cubierta por P4-11 en borrador y sin aplicar relaciones/costos.
- No hace matching SKU proveedor contra SKU ERP.
- No actualiza `erp_catalogo_sku_proveedores`.
- No actualiza costos ni `costo_referencia`.
- No cambia Compras, Catalogo ni Almacen.
- No define catalogos cerrados de moneda, origen, estatus ni politica de autorizacion.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se creo lista de prueba para no contaminar datos reales.

## Evidencia P4-05 - detalle de listas Proveedores ERP

Estado: autorizado y aplicado el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoints agregados:

- `/proveedor/proveedor_lista_detalle_erp`.
- `/proveedor/proveedor_lista_detalle_guardar_erp`.

Permiso:

- `proveedores.listas`.

Alcance implementado:

- Consulta de hasta 500 renglones por lista ERP validando que la lista pertenezca al proveedor.
- Alta/edicion de renglones en `erp_proveedores_listas_detalle_erp`.
- Campos capturados: producto legacy, SKU ERP referencial, SKU proveedor referencial, SKU proveedor texto, codigos, marca, descripcion, unidad texto, unidad ID, factor, cantidad minima, costo reportado, moneda, bandera de impuestos, existencia reportada, estado/criterio de match y observaciones.
- Modal de detalle desde cada lista ERP.
- Modal de alta/edicion de renglon.
- Envio POST con `_csrf` y header `X-CSRF-Token`.
- Auditoria explicita `proveedor_lista_detalle_guardar` con `datos_antes` y `datos_despues`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.

Limites aplicados:

- No importa archivos ni procesa Excel/PDF/CSV.
- No crea productos ni SKU ERP.
- No crea ni actualiza `erp_catalogo_sku_proveedores`.
- No hace matching automatico.
- No aplica costos a Catalogo, Compras ni `costo_referencia`.
- No autoriza lista ni costo como oficial.
- No elimina renglones.
- No define catalogos cerrados de estado/criterio de match, unidad o moneda.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se creo renglon de lista de prueba para no contaminar datos reales.

## Evidencia P5-01/P5-02 parcial - matching dry-run SKU proveedor contra SKU ERP

Estado: autorizado y aplicado en solo lectura el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_lista_matching_dry_run_erp`.

Permiso:

- `proveedores.matching`.

Alcance implementado:

- Calcula propuestas de matching por renglones de lista ERP.
- Clasifica renglones como `relacionado`, `match_exacto_pendiente`, `match_posible`, `ambiguo` o `sin_match`.
- Prioriza relacion activa existente en `erp_catalogo_sku_proveedores` para el proveedor.
- Busca coincidencias exactas por `id_sku`, SKU ERP, SKU proveedor y codigos activos de SKU.
- Busca coincidencias posibles por nombre/SKU ERP con una heuristica simple de texto.
- Muestra resumen y candidatos desde el modal de detalle de lista.
- Devuelve `sin_escrituras=true`.

Limites aplicados:

- No guarda decisiones de matching.
- No actualiza `estado_match` en el renglon.
- No crea ni actualiza `erp_catalogo_sku_proveedores`.
- No crea productos ni SKU ERP.
- No aplica costos.
- No genera incidencias.
- No define reglas finales de aprobacion, rechazo o ambiguedad.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se escribieron propuestas ni relaciones.

## Evidencia P5-03 - decision de matching por renglon

Estado: autorizado y aplicado el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_lista_matching_decidir_erp`.

Permiso:

- `proveedores.matching`.

Alcance implementado:

- Guarda la decision de matching en el renglon de `erp_proveedores_listas_detalle_erp`.
- Permite seleccionar candidato, marcar ambiguo, marcar sin match o rechazar.
- Al seleccionar candidato guarda `id_sku`, `id_sku_proveedor` si existe, `estado_match`, `criterio_match` y observaciones.
- Estado UI nuevo: `match_seleccionado`, entendido como candidato elegido dentro de Proveedores y pendiente de aplicar como relacion oficial.
- Valida que el renglon pertenezca a la lista y al proveedor.
- Valida que el SKU ERP exista si se captura `id_sku`.
- Valida que `id_sku_proveedor` pertenezca al proveedor si se captura.
- Auditoria explicita `proveedor_matching_decidir` con `datos_antes` y `datos_despues`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.

Limites aplicados:

- No crea ni actualiza `erp_catalogo_sku_proveedores`.
- No aplica relaciones a Compras.
- No modifica costos.
- No crea productos ni SKU ERP.
- No genera incidencias.
- No autoriza la relacion como oficial.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se aplicaron relaciones SKU-proveedor.

## Evidencia P5-04 - aplicar relacion SKU-proveedor oficial

Estado: autorizado y aplicado el 2026-06-12.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_sku_relacion_aplicar_erp`.

Permiso:

- `proveedores.matching`.

Alcance implementado:

- Aplica una decision `match_seleccionado` como relacion oficial en `erp_catalogo_sku_proveedores`.
- Crea o actualiza la relacion por `id_sku` + `id_proveedor`.
- Guarda/actualiza `sku_proveedor`, `id_unidad_compra`, `factor_conversion`, `cantidad_minima` y `estatus='activo'`.
- Marca el renglon de lista como `relacion_aplicada`.
- Valida que el renglon pertenezca a la lista/proveedor.
- Valida que exista `id_sku` ERP.
- Exige unidad, factor y cantidad minima mayores a cero antes de aplicar.
- Bloquea la aplicacion si el `id_sku_proveedor` seleccionado pertenece a otro SKU ERP.
- Auditoria explicita `proveedor_sku_relacion_aplicar` con `datos_antes` y `datos_despues`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.

Limites aplicados:

- No actualiza `costo_ultimo`.
- No actualiza `erp_catalogo_skus.costo_referencia`.
- No marca proveedor preferido.
- No aplica costos a Compras.
- No crea productos ni SKU ERP.
- No importa listas masivas.
- No define politica de autorizacion de costos.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se aplico relacion real de prueba para no contaminar datos.

## Evidencia P6-01/P6-03/P6-04 - consulta de historial de costos proveedor-SKU

Estado: autorizado y aplicado en solo lectura el 2026-06-12.

Decision de arquitectura aceptada:

- `erp_proveedores_sku_costos` queda como fuente de verdad para historial de costos proveedor-SKU.
- `erp_catalogo_sku_proveedores.costo_ultimo` queda como referencia operativa futura, no como historial.
- `erp_catalogo_skus.costo_referencia` queda separado y requiere autorizacion posterior porque afecta Catalogo, Compras y Almacen.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_costos_erp`.

Permiso:

- `proveedores.costos`.

Alcance implementado:

- Consulta historial de costos por proveedor.
- Permite filtros tecnicos por `id_sku`, `id_sku_proveedor`, `id_lista_proveedor_erp`, `estatus` y limite.
- Devuelve `sin_escrituras=true`.
- Muestra historial en la ficha de proveedor para usuarios con permiso `proveedores.costos`.
- Muestra costo, moneda, SKU ERP, nombre SKU, origen/lista/version, vigencia y estatus.
- Conserva el resumen existente de costos/SKUs/vigencias.

Limites aplicados:

- No crea costos.
- No autoriza costos.
- No actualiza `erp_catalogo_sku_proveedores.costo_ultimo`.
- No actualiza `erp_catalogo_skus.costo_referencia`.
- No cambia proveedor preferido.
- No modifica Compras, Ordenes, Almacen ni Catalogo.
- No importa costos en lote.
- No calcula neto/bruto ni impuestos; solo muestra lo ya guardado en historial.

Siguiente alto de autorizacion despues de P6-05:

- P6-06, aplicar costos en lote, requiere pantalla de revision previa, conteos, criterios de exclusion y rollback operativo.
- P6-07 quedo cerrada despues: `costo_referencia` usa compra real como fuente prioritaria, proveedor vigente solo como referencia inicial y moneda no MXN de proveedor/lista queda bloqueada hasta politica cambiaria.

## Evidencia P6-05 - aplicar costo autorizado individual

Estado: autorizado y aplicado el 2026-06-12.

Decision de negocio aplicada:

- El historial `erp_proveedores_sku_costos` es la fuente de verdad del costo proveedor-SKU.
- Al aplicar un costo individual, el costo anterior vigente del mismo proveedor/SKU pasa a `historico`.
- El costo del renglon de lista se guarda o actualiza como `vigente`.
- `erp_catalogo_sku_proveedores.costo_ultimo` se actualiza como referencia operativa para Compras.
- `erp_catalogo_skus.costo_referencia` no se toca en este paso.
- La aplicacion masiva queda como tarea posterior con revision previa y autorizacion separada.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_costo_aplicar_erp`.

Permiso:

- `proveedores.costos`.

Alcance implementado:

- Aplica costo desde un renglon individual de lista ERP.
- Exige que el renglon pertenezca a la lista/proveedor.
- Exige relacion SKU-proveedor ya aplicada y activa.
- Exige costo positivo, moneda, unidad, factor y bandera de impuestos definida.
- Usa vigencia y documento de evidencia del encabezado de lista cuando existen.
- Crea o actualiza registro en `erp_proveedores_sku_costos` con estatus `vigente`.
- Manda costos vigentes anteriores del mismo proveedor/SKU a `historico`.
- Actualiza `erp_catalogo_sku_proveedores.costo_ultimo`.
- Registra auditoria explicita `proveedor_costo_aplicar` con `datos_antes` y `datos_despues`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.
- Boton individual en detalle de lista para aplicar costo vigente.

Limites aplicados:

- No actualiza `erp_catalogo_skus.costo_referencia`.
- No marca proveedor preferido.
- No cambia ordenes, solicitudes, recepciones ni inventario.
- No calcula impuestos; solo conserva si el costo capturado incluye impuestos.
- No crea productos ni SKU ERP.
- No aplica costos en lote.
- No importa archivos.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se aplico costo real de prueba para no contaminar datos.

## Evidencia P7-01/P7-04 parcial - dry-run de pendientes e incidencias Proveedores

Estado: autorizado y aplicado en solo lectura el 2026-06-13.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_incidencias_dry_run_erp`.

Permiso:

- `proveedores.auditoria`.

Alcance implementado:

- Genera propuestas de pendientes/incidencias desde renglones de lista ERP.
- Devuelve `sin_escrituras=true`.
- Propone `huella`, tipo, severidad, entidad, referencia, origen `proveedores`, detalle, evidencia y accion sugerida.
- Muestra modal de pendientes desde el detalle de lista.
- Resume por total y severidad.

Tipos detectados:

- `proveedor_sku_sin_match`.
- `proveedor_match_ambiguo`.
- `proveedor_unidad_factor_dudoso`.
- `proveedor_costo_dudoso`.
- `proveedor_sku_sin_codigo_confiable`.
- `proveedor_sku_fiscal_incompleto`.

Limites aplicados:

- No crea registros en `erp_catalogo_incidencias_calidad`.
- No modifica Catalogo.
- No crea productos ni SKU ERP.
- No bloquea Compras.
- No resuelve ni descarta pendientes.
- No define todavia la matriz final de severidades bloqueantes.
- No aplica sincronizacion masiva.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- `git diff --check`: sin errores reales; solo avisos CRLF/LF del entorno Windows.
- No se crearon incidencias reales.

Siguiente alto de autorizacion despues del dry-run:

- Crear incidencias reales en lote desde listas/matching requiere confirmar tipos finales, severidad, permisos y revision previa. La creacion individual quedo aplicada en P7-02 parcial.

## Evidencia P7-02/P7-06 - crear incidencia real individual hacia Catalogo

Estado: autorizado y aplicado el 2026-06-13.

Decision de responsabilidad:

- Proveedores autoriza escalar el pendiente.
- La incidencia real se guarda en `erp_catalogo_incidencias_calidad` con origen `proveedores`.
- Catalogo conserva la responsabilidad de resolver, descartar o corregir el maestro producto/SKU/fiscal/codigo/unidad.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/core/Core.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_incidencia_crear_erp`.

Permiso:

- `proveedores.autorizar`.

Alcance implementado:

- Crea o actualiza una incidencia real individual desde una propuesta del dry-run.
- El servidor recalcula el dry-run y solo permite crear incidencias que sigan vigentes.
- Usa `huella` SHA-256 para evitar duplicados.
- Usa `ON DUPLICATE KEY UPDATE` para refrescar evidencia si la incidencia abierta ya existe.
- Respeta incidencias `resuelta` o `descartada`: no las reabre automaticamente.
- Registra auditoria explicita `proveedor_incidencia_crear`.
- Ruta agregada a auditoria explicita en `Core.php` para evitar doble auditoria generica.
- Boton `Crear incidencia` visible solo con `proveedores.autorizar`.

Limites aplicados:

- No crea incidencias en lote.
- No resuelve incidencias.
- No cambia Catálogo maestro.
- No crea productos ni SKUs.
- No bloquea Compras automaticamente.
- No cambia costos, relaciones ni listas.
- No define todavia doble autorizacion por severidad.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\core\Core.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- No se creo incidencia real de prueba para no contaminar datos.

Siguiente alto de autorizacion:

- No avanzar todavia con incidencias en lote. Recomendacion: validar primero el flujo individual con casos reales y auditoria.
- Queda pendiente definir si severidades `bloqueante` deben requerir doble autorizacion o crear bloqueo operativo en Compras.

## Evidencia P7-04/P7-07 - pendientes con ruta de resolucion

Estado: autorizado y aplicado el 2026-06-14.

Motivo:

- El modal anterior mostraba pendientes y permitia crear incidencia, pero no explicaba claramente como resolver cada problema.
- Crear una incidencia no siempre es la solucion: algunos pendientes se corrigen en Proveedores editando el renglon, otros se resuelven con matching, y otros si deben escalarse a Catalogo.

Archivos modificados:

- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Alcance implementado:

- El boton del detalle de lista ahora dice `Resolver pendientes`.
- El modal ahora se presenta como `Pendientes y resolucion`.
- Cada pendiente muestra:
  - ruta de solucion;
  - detalle practico;
  - responsable sugerido;
  - botones disponibles segun permisos.
- Acciones disponibles:
  - `Abrir matching` para casos sin match o ambiguos.
  - `Editar renglon` para costo, moneda, impuestos, unidad o factor.
  - `Enviar a Catalogo` cuando el problema corresponde a Catalogo.

Mapa inicial de resolucion:

- `proveedor_sku_sin_match`: buscar/vincular SKU ERP; si no existe, enviar a Catalogo.
- `proveedor_match_ambiguo`: elegir candidato correcto o marcar sin match.
- `proveedor_unidad_factor_dudoso`: editar unidad/factor del renglon.
- `proveedor_costo_dudoso`: editar costo, moneda e impuestos.
- `proveedor_sku_sin_codigo_confiable`: enviar a Catalogo para completar codigo principal.
- `proveedor_sku_fiscal_incompleto`: enviar a Catalogo para completar datos fiscales del SKU.

Limites aplicados:

- No resuelve pendientes automaticamente.
- No descarta pendientes.
- No crea relaciones ni costos en lote.
- No cambia Catalogo maestro.
- No define bloqueos de Compras.
- No crea nuevas reglas de negocio fuera del mapa operativo documentado.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Definir si se implementa un flujo real de `resolver/descartar pendiente` con motivo y auditoria, o si primero se revisan casos reales usando editar/matching/enviar a Catalogo.

## Evidencia P4-07 - filtros operativos en detalle de lista

Estado: aplicado el 2026-06-14.

Motivo:

- Despues de migrar listas desde `db/productivo`, revisar miles de renglones sin filtros no es operativo.
- Antes de aplicar relaciones/costos o resolver pendientes, el usuario necesita separar casos por accion posible.

Archivos modificados:

- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Alcance implementado:

- En el modal de detalle de lista se agregaron filtros de revision:
  - todos;
  - con SKU ERP;
  - sin SKU ERP;
  - costo pendiente;
  - unidad/factor pendiente;
  - moneda pendiente;
  - origen productivo.
- Los filtros operan en cliente sobre los renglones ya cargados.
- Se muestra conteo `filtrados de total`.

Limites aplicados:

- No modifica datos.
- No aplica relaciones.
- No aplica costos.
- No crea incidencias.
- No cambia Catalogo ni Compras.

Verificacion pendiente/ejecutada:

- Pendiente probar visualmente con una lista real importada.

## Evidencia P4-08 - archivo original de lista como evidencia

Estado: autorizado y aplicado el 2026-06-14.

Motivo:

- Un ERP robusto no debe exigir que todos los proveedores manden la misma estructura de lista.
- El archivo original debe conservarse tal cual lo envia el proveedor como evidencia.
- La normalizacion a renglones ERP debe ser un paso posterior con vista previa y mapeo de columnas.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_lista_archivo_subir_erp`.

Permisos:

- `proveedores.listas`.
- `proveedores.documentos`.

Alcance implementado:

- El formulario de lista permite seleccionar archivo original.
- Al guardar lista con archivo:
  - primero guarda/actualiza encabezado de lista;
  - despues sube el archivo original;
  - crea documento en `erp_proveedores_documentos`;
  - guarda ruta interna, nombre, tipo MIME, tamano y hash SHA-256;
  - liga `erp_proveedores_listas_erp.id_documento_proveedor` al documento creado.
- Almacenamiento interno:
  - `storage/erp/proveedores/{id_proveedor}/listas/{id_lista_proveedor_erp}/`.
- Tipos permitidos:
  - PDF;
  - TXT;
  - CSV;
  - XLS;
  - XLSX;
  - ZIP.
- Tamano maximo:
  - 20 MB.

Limites aplicados:

- No lee columnas.
- No importa renglones.
- No aplica costos.
- No crea relaciones proveedor-SKU.
- No crea productos/SKUs en Catalogo.
- No reemplaza listas anteriores.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Implementar vista previa/mapeo de columnas para archivos XLS/XLSX/CSV antes de importar renglones normalizados.
- Definir si PDF queda solo como evidencia o si despues se permite captura/manual OCR asistido.

## Evidencia P4-09/P4-10 parcial - vista previa y mapeo sugerido de listas

Estado: autorizado y aplicado el 2026-06-14.

Motivo:

- Despues de guardar el archivo original, el siguiente paso seguro es ver su estructura sin importar datos.
- Cada proveedor puede mandar columnas distintas; por eso el ERP no debe asumir un formato unico.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_lista_archivo_preview_erp`.

Permiso:

- `proveedores.listas`.

Alcance implementado:

- Las listas con `id_documento_proveedor` muestran boton de vista previa.
- La vista previa lee el archivo original ligado a la lista.
- Para `CSV`/`TXT` lee muestra de filas.
- Para `XLSX` lee la primera hoja con lector ligero propio, sin PHPExcel.
- Para `XLS` legado conserva el archivo como evidencia; para vista previa/importacion automatica debe convertirse a `XLSX` o `CSV`.
- Limites de muestra:
  - hasta 25 filas;
  - hasta 40 columnas en Excel.
- Muestra:
  - resumen de archivo;
  - encabezados detectados;
  - filas de muestra;
  - mapeo sugerido por nombre de columna.

Campos con mapeo sugerido:

- SKU proveedor.
- Codigo de barras.
- Codigo interno.
- Marca.
- Descripcion.
- Costo.
- Moneda.
- Unidad.
- Factor.
- Incluye impuestos.
- Existencia reportada.

Limites aplicados:

- No importa renglones.
- No guarda mapeo.
- No aplica costos.
- No crea relaciones proveedor-SKU.
- No crea productos/SKUs en Catalogo.
- PDF/ZIP quedan como evidencia; no se parsean en esta etapa.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Implementar confirmacion editable del mapeo y despues importacion de renglones normalizados en borrador.
- La importacion debe generar conteos y pendientes, pero no aplicar relaciones ni costos automaticamente.

## Evidencia P4-10/P4-11 - mapeo confirmable e importacion en borrador

Estado: autorizado y aplicado el 2026-06-14.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_lista_archivo_importar_erp`.

Alcance implementado:

- El modal de vista previa permite ajustar el mapeo sugerido mediante selects.
- El usuario puede confirmar importacion desde la misma vista previa.
- La importacion crea renglones en `erp_proveedores_listas_detalle_erp`.
- Cada renglon entra con:
  - `estado_match='sin_match'`;
  - `criterio_match='importacion_lista_proveedor'`;
  - observacion de importacion desde archivo original.
- Se importan campos disponibles segun mapeo:
  - SKU proveedor;
  - codigo de barras;
  - codigo interno;
  - marca;
  - descripcion;
  - costo;
  - moneda;
  - unidad;
  - factor;
  - incluye impuestos;
  - existencia.

Barandales aplicados:

- Solo importa si la lista esta vacia.
- Requiere mapear al menos descripcion o identificador.
- Maximo 10000 filas por importacion.
- No aplica relaciones proveedor-SKU.
- No aplica costos vigentes.
- No actualiza `costo_referencia`.
- No crea productos/SKUs en Catalogo.
- No reemplaza renglones existentes.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Probar con una lista real antes de permitir reimportar, reemplazar o anexar renglones.
- Definir si se agrega una tabla de lotes de importacion para auditoria fina por archivo/importacion.
- Despues de pruebas, decidir si se permite importacion a listas no vacias con modo `anexar`, `reemplazar borrador` o `nueva version`.

## Correccion P4-10/P4-11 - prueba de importacion de lista

Estado: aplicado el 2026-06-14 despues de prueba real.

Problema reportado:

- Al importar renglones desde la vista previa, el navegador mostro `Unexpected end of JSON input`.
- La lista no cargo renglones.
- El mapeo no explicaba que significaba cada titulo.

Hallazgos:

- En access log se observo que `/proveedor/proveedor_lista_archivo_importar_erp` respondio `200` con cuerpo muy corto, equivalente a una respuesta invalida para el contrato JSON esperado.
- El archivo probado `Lista de precios OCEAN ABRIL 2026 (2).xlsx` no tiene encabezados en la primera fila.
- La fila real de encabezados contiene `MODELO`, `DESCRIPCION`, `PIEZAS P/CAJA`, `PRECIO MAYOREO`, `PRECIO PUBLICO`.
- Antes, la vista previa asumia la primera fila como encabezado, por eso no sugeria bien el mapeo.

Correcciones aplicadas:

- El parser ahora detecta la fila de encabezado mas probable dentro de las primeras filas del archivo.
- Para el archivo probado detecta como encabezados:
  - `MODELO`;
  - `DESCRIPCION`;
  - `PIEZAS P/CAJA`;
  - `PRECIO MAYOREO`;
  - `PRECIO PUBLICO`.
- El mapeo sugerido ahora puede proponer:
  - `MODELO` como SKU proveedor;
  - `DESCRIPCION` como descripcion;
  - `PIEZAS P/CAJA` como factor;
  - `PRECIO MAYOREO` como costo.
- El importador valida correctamente que un campo no este vacio antes de considerarlo mapeado.
- El importador captura `Throwable` para devolver JSON aun si ocurre un error interno de PHP.
- El controlador convierte respuestas invalidas en error JSON claro.
- El JS ya no intenta continuar si recibe `[]`, respuesta vacia o JSON con formato inesperado.
- La vista de mapeo ahora muestra ayuda debajo de cada campo para explicar que significa.

Verificacion ejecutada:

- Vista previa CLI sobre proveedor `8`, lista `11`: detecto encabezados reales y mapeo sugerido.
- Importacion CLI con mapeo vacio: devuelve JSON de advertencia `Mapea al menos descripcion o un identificador del producto`.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Correccion adicional por respuesta vacia:

- En segunda prueba real, la UI mostro `El servidor devolvio una respuesta no valida:` con respuesta vacia.
- Causa tecnica detectada: el `load()` de PHPExcel activa codigo legacy incompatible con PHP actual (`Array and string offset access syntax with curly braces is no longer supported`) y corta la respuesta antes de generar JSON.
- Se reemplazo la lectura automatica de `XLSX` por un lector ligero propio basado en `ZipArchive`/XML, usado tanto por vista previa como por importacion.
- El lector nuevo detecta la primera hoja, encabezado real y celdas compartidas sin tocar PHPExcel.
- El archivo probado quedo legible con encabezados `MODELO`, `DESCRIPCION`, `PIEZAS P/CAJA`, `PRECIO MAYOREO` y 228 filas detectadas para importacion.
- No se ejecuto la importacion real durante esta verificacion; solo se probo lectura sin insertar renglones.
- `XLS` antiguo queda pendiente como conversion recomendada a `XLSX`/`CSV` antes de importacion automatica.

## Correccion P4-11 - limpieza de renglones importados y archivos XLS legado

Estado: aplicado el 2026-06-14 despues de pruebas reales.

Problemas reportados:

- La importacion registro un renglon que era nota del proveedor: `*PRECIOS SUJETOS A CAMBIO SIN PREVIO AVISO`.
- Al cargar lista de proveedor SUNNY no se mostro tabla ni mapeo.

Hallazgos:

- El renglon basura quedo importado en la lista `11` con `id_lista_detalle_erp=9441`.
- El archivo de SUNNY es `.xls` binario real (`application/vnd.ms-excel`), no `XLSX` renombrado ni CSV.
- El soporte automatico actual de importacion segura cubre `XLSX`, `CSV` y `TXT`; `XLS` queda como evidencia para evitar reactivar PHPExcel legacy.

Correcciones aplicadas:

- Se agrego accion de eliminar renglon desde el detalle de lista.
- La eliminacion pide confirmacion, registra auditoria y no afecta Catalogo ni costos.
- La eliminacion se bloquea si el renglon ya tiene relacion/costo aplicado o costos ligados.
- El importador ahora omite notas comunes sin costo como:
  - `precios sujetos`;
  - `sujeto a cambio`;
  - `sin previo aviso`;
  - `lista de precios`;
  - `vigencia`;
  - `condiciones`;
  - `nota:`.
- La vista previa deshabilita el boton de importar cuando el archivo no es parseable.
- Para `XLS` legado muestra mensaje claro: convertir a `XLSX` o `CSV` para vista previa/importacion automatica.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- Vista previa CLI de SUNNY proveedor `1`, lista `12`: responde `parseable=false`, tipo `excel_legado`, con mensaje de conversion.

Pendiente recomendado:

- Evaluar si se instala/integra una libreria moderna para leer `.xls` legacy directamente, o si la politica operativa sera convertir listas `.xls` a `.xlsx`/`CSV` antes de importarlas.

## Decision P4-12 - estados controlados de listas de proveedor

Estado: aplicado el 2026-06-14.

Decision de negocio:

- No se agrego `tipo_lista` a Proveedores.
- La lista del proveedor representa costo/evidencia de compra, no estrategia comercial de venta.
- Listas de precio de venta como mayoreo, menudeo, liquidacion, ofertas o promociones deben implementarse despues en Catalogo/Precios, separadas del costo proveedor.

Estados controlados para `erp_proveedores_listas_erp.estatus`:

- `borrador`: lista creada, todavia sin uso operativo.
- `cargada`: archivo/renglones cargados.
- `en_validacion`: limpieza de renglones, costo, moneda y datos minimos.
- `conciliacion`: revision/matching contra SKU ERP.
- `validada`: lista revisada y confiable como referencia.
- `aplicada`: lista ya usada para relaciones y/o costos operativos.
- `historica`: lista reemplazada por otra version.
- `cancelada`: lista que no debe usarse.

Reglas aplicadas:

- Al crear lista nueva, si no se manda estatus, queda en `borrador`.
- Al importar renglones desde archivo, si estaba vacia/borrador/en_validacion, pasa a `cargada`.
- Cambiar estatus operativo usa endpoint separado y permiso `proveedores.autorizar`.
- Para marcar `validada`, la lista debe tener al menos un renglon operativo y esos renglones operativos no deben quedar sin identidad, sin costo o sin moneda.
- Para marcar `aplicada`, la lista debe tener relaciones o costos ya aplicados.
- La validacion no exige que todos los renglones esten enlazados a Catalogo, porque un producto proveedor puede existir sin estar comprado o dado de alta completo aun.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/proveedor_lista_estatus_erp`.

Relacion con P3-08:

- P3-08 inicio con estatus de listas y despues quedo cerrada con estatus final de proveedor en `proveedor_estatus_erp`.
- El bloqueo hacia Compras quedo acotado al envio de orden cuando el proveedor esta `suspendido`, `bloqueado` o `inactivo`.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

## Mejora P4-13 - matching guiado, filtros y ayuda operativa

Estado: aplicado el 2026-06-14.

Motivo:

- En matching, algunos renglones mostraban `+N candidatos` pero solo se podia seleccionar el primer candidato.
- El usuario necesitaba revisar cual SKU ERP corresponde antes de relacionarlo.
- Pendientes y resolucion puede crecer mucho y necesitaba filtros.
- El formulario de renglon mostraba IDs tecnicos que no son capturables de forma natural por operacion.
- El flujo de estatus necesitaba señales visibles de avance hacia validacion/aplicacion.

Alcance aplicado:

- Matching ahora muestra todos los candidatos devueltos para cada renglon, no solo el primero.
- Cada candidato tiene boton `Seleccionar este SKU`.
- Se agregaron filtros en matching por texto y estado.
- Pendientes y resolucion ahora tiene filtros por texto, severidad y tipo.
- El detalle de lista muestra resumen de avance:
  - renglones;
  - relacionados;
  - costos aplicados;
  - sin identidad;
  - sin costo;
  - sin moneda.
- `consultarListaDetalleErp()` devuelve `revision` con el resumen de validacion.
- Los IDs tecnicos del formulario de renglon se movieron a una seccion colapsable `Campos tecnicos`.
- Los IDs tecnicos quedan de solo lectura en la UI; normalmente los llena matching o migracion.

Decision operativa documentada:

- Un SKU proveedor relacionado con SKU ERP indica que la lista ya tiene una parte util como referencia.
- Una lista sin ningun match/relacion puede estar cargada, pero todavia no debe tomarse como referencia operativa fuerte.
- El estatus no queda totalmente abierto: se controla con estados definidos, pero el avance real se apoya en indicadores de revision.
- Validar no exige que todo este relacionado con Catalogo, pero si exige datos minimos limpios.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- Prueba CLI `consultarListaDetalleErp(8,11)`: devuelve `revision`; lista con 162 renglones, 0 sin identidad, 0 sin costo, 162 sin moneda, 0 relaciones aplicadas, no validable aun.

Pendiente siguiente recomendado:

- Agregar buscador de unidad de compra para no capturar `id_unidad_compra` manualmente.
- Agregar, si hace falta, buscador manual de SKU ERP para renglones que no tengan candidato suficiente en matching automatico.

## Evidencia P5-07 - motivos estandar de rechazo y ambiguedad

Fecha: 2026-06-15.

Objetivo:

- Evitar que decisiones de matching queden sin motivo operativo.
- Facilitar que otra persona entienda por que un renglon quedo `ambiguo`, `sin_match` o `rechazado`.

Implementado:

- En la UI de matching, las acciones:
  - `Ambiguo`;
  - `Sin match`;
  - `Rechazar`;
  ahora piden seleccionar un motivo estandar antes de guardar.
- Motivos para `ambiguo`:
  - hay varios candidatos posibles;
  - datos insuficientes;
  - requiere revision de Catalogo.
- Motivos para `sin_match`:
  - no existe SKU ERP confiable;
  - codigo/SKU proveedor no coincide;
  - parece producto nuevo.
- Motivos para `rechazado`:
  - candidato incorrecto;
  - producto no operativo;
  - informacion del proveedor insuficiente o incorrecta.
- El motivo se guarda en `observaciones` del renglon como evidencia.
- El servidor valida:
  - estados permitidos: `match_seleccionado`, `ambiguo`, `sin_match`, `rechazado`;
  - `ambiguo`, `sin_match` y `rechazado` requieren observacion/motivo.

Alcance:

- No crea tablas.
- No crea catalogo nuevo de motivos.
- No crea relaciones.
- No aplica costos.
- No cambia reglas de Compras.
- No bloquea listas por si solo.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.

Siguiente mejora posible:

- Si las pruebas reales piden mas control, convertir estos motivos en catalogo configurable por departamento.

## Mejora P4-14 - buscadores para renglones de lista proveedor

Estado: aplicado el 2026-06-14.

Motivo:

- El formulario de renglon mostraba campos tecnicos como `id_unidad_compra`, `id_sku`, `id_sku_proveedor` e `id_producto_legacy`.
- Operativamente no es razonable pedir que el usuario conozca esos IDs.

Alcance aplicado:

- Se agrego endpoint de catalogos para renglones:
  - `/proveedor/proveedor_lista_catalogos_erp`.
- Se agrego endpoint de busqueda de SKU ERP:
  - `/proveedor/proveedor_buscar_skus_erp`.
- El formulario de renglon ahora muestra `Unidad compra` como selector basado en `erp_catalogo_unidades`.
- El formulario de renglon ahora tiene buscador manual de SKU ERP por SKU, nombre o codigo principal.
- Al seleccionar SKU ERP desde el buscador:
  - llena `id_sku`;
  - limpia `id_sku_proveedor`;
  - marca `estado_match=match_seleccionado`;
  - marca `criterio_match=seleccion_manual_sku_erp`.
- Los IDs tecnicos quedan en seccion colapsable y de solo lectura.

Limites:

- Seleccionar SKU ERP manual no crea relacion proveedor-SKU todavia.
- Para crear/aplicar la relacion se sigue usando la accion de aplicar relacion, con validaciones de unidad, factor y cantidad minima.
- Si el SKU no existe, el camino recomendado sigue siendo mandar pendiente a Catalogo.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- Prueba CLI `catalogosListaDetalleErp()`: responde sin error y devuelve 10 unidades activas.
- Prueba CLI `buscarSkusErpParaLista('a')`: responde sin error y pide al menos dos caracteres.

Limites:

- No se ejecuto importacion real desde CLI para no escribir renglones sin confirmacion visual del usuario.
- El siguiente intento desde UI deberia mostrar error claro si algo falla.

## Evidencia P8-01 - contrato solo lectura SKUs comprables por proveedor

Estado: autorizado y aplicado en solo lectura el 2026-06-13.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/skus_comprables_por_proveedor_erp`.

Permiso:

- `proveedores.ver`.

Alcance implementado:

- Consulta SKUs comprables por proveedor desde relaciones activas en `erp_catalogo_sku_proveedores`.
- Filtra por proveedor, termino de busqueda y limite.
- Devuelve `sin_escrituras=true`.
- Incluye SKU ERP, nombre, SKU proveedor, unidad compra/base, factor, cantidad minima, dias entrega, preferido y estatus de relacion.
- Incluye costo vigente desde `erp_proveedores_sku_costos` cuando existe.
- Incluye `costo_ultimo` como respaldo operativo.
- Incluye moneda, vigencia, origen y referencia de lista del costo vigente cuando existe.
- Incluye indicadores de codigo principal activo y fiscal completo.
- Documenta que Compras debe usarlo como sugerencia y guardar snapshot propio.

Limites aplicados:

- No modifica Compras.
- No reemplaza `SolicitudesCompraErp::buscarSkus()`.
- No reemplaza `OrdenesCompraErp::buscarSkus()`.
- No cambia validaciones de solicitudes u ordenes.
- No actualiza costos, relaciones ni `costo_referencia`.
- No bloquea SKUs; solo expone indicadores para decidir reglas posteriores.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `git diff --check`: sin errores reales; solo avisos CRLF/LF del entorno Windows.
- No se ejecuto consulta real contra proveedor especifico para no asumir datos de prueba.

Siguiente alto de autorizacion:

- P8-02, migrar busquedas de Compras hacia este contrato, requiere pruebas comparativas y revision de impacto en solicitudes/ordenes.
- P8-03 ya define estatus final del proveedor para Compras; P8-04 aplica bloqueos minimos de envio y deja como advertencia lo no critico.

## Evidencia P8-02 preparatoria - comparativo contrato Proveedores vs busquedas Compras

Estado: aplicado en solo lectura el 2026-06-13. No migra Compras.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `docs/erp_proveedores_avance.md`.

Endpoint agregado:

- `/proveedor/compras_contrato_comparar_erp`.

Permiso:

- `proveedores.auditoria`.

Alcance implementado:

- Compara resultados del contrato nuevo de Proveedores contra las busquedas actuales de Solicitudes y Ordenes.
- Requiere `id_proveedor` y termino de busqueda de al menos dos caracteres.
- Devuelve `sin_escrituras=true`.
- Muestra conteos por fuente:
  - contrato Proveedores;
  - busqueda actual de Solicitudes;
  - busqueda actual de Ordenes.
- Reporta diferencias por `id_sku_proveedor` entre contrato nuevo y busquedas actuales.
- Agrega comparativo visible en la ficha del proveedor, dentro de Listas y costos.
- Sirve para evaluar impacto antes de P8-02 real.

Limites aplicados:

- No modifica `SolicitudesCompraErp::buscarSkus()`.
- No modifica `OrdenesCompraErp::buscarSkus()`.
- No cambia pantallas ni JS de Compras.
- No cambia validaciones de solicitud u orden.
- No escribe datos.
- No define bloqueos por fiscal/costo/codigo; solo expone comparativo.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- `git diff --check`: sin errores reales; solo avisos CRLF/LF del entorno Windows.
- No se ejecuto comparativo real porque requiere elegir proveedor y termino de busqueda representativos.

Siguiente alto de autorizacion:

- P8-02 real, cambiar busquedas de Compras para consumir el contrato de Proveedores, requiere revisar resultados comparativos con proveedores reales.

## Evidencia P8-02 real - busquedas Compras usando contrato Proveedores

Estado: autorizado y aplicado el 2026-06-13.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/modelos/SolicitudesCompraErp.php`.
- `app/modelos/OrdenesCompraErp.php`.
- `docs/erp_proveedores_avance.md`.

Alcance implementado:

- Se centralizo la busqueda de SKUs comprables en el contrato de Proveedores.
- `SolicitudesCompraErp::buscarSkus()` ahora consume `Proveedores::skusComprablesParaComprasErp(..., "solicitudes")`.
- `OrdenesCompraErp::buscarSkus()` ahora consume `Proveedores::skusComprablesParaComprasErp(..., "ordenes")`.
- Los endpoints publicos de Compras se conservan:
  - `/compra/solicitudes_buscar_skus_erp`.
  - `/compra/orden_buscar_skus_erp`.
- El JS de Compras no cambia porque se conserva la forma de respuesta esperada: `id_sku`, `id_producto_erp`, `sku`, `nombre`, `unidad`, `id_sku_proveedor`, `sku_proveedor`, `costo_ultimo`, `factor_conversion`, `cantidad_minima`, `es_preferido` y, para Ordenes, `iva_porcentaje`.
- El costo mostrado a Compras usa costo vigente de `erp_proveedores_sku_costos` cuando existe; si no existe, usa `erp_catalogo_sku_proveedores.costo_ultimo` como respaldo operativo.
- Se agrego `iva_porcentaje` al contrato de Proveedores para que Ordenes no pierda el calculo fiscal que ya utilizaba.

Limites aplicados:

- No se cambiaron pantallas ni JS de Compras.
- No se modificaron solicitudes u ordenes existentes.
- No se actualizo inventario.
- No se actualizo `erp_catalogo_skus.costo_referencia`.
- No se definieron bloqueos por fiscal incompleto, costo faltante o relacion no preferida.
- No se ejecutaron migraciones ni escrituras masivas.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\SolicitudesCompraErp.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\OrdenesCompraErp.php`: sin errores de sintaxis.
- No se ejecuto busqueda real porque requiere elegir proveedor y termino de busqueda representativos.

Siguiente alto de autorizacion:

- Probar manualmente Solicitudes y Ordenes con proveedores reales.
- Decidir si el siguiente bloque debe ser advertencias/bloqueos operativos en Compras cuando falte fiscal, costo vigente, unidad/factor o relacion activa.
- Decidir si se implementa importacion fisica de listas/documentos antes de acciones masivas.

## Evidencia P8-03 - advertencias operativas en Compras sin bloqueo

Estado: autorizado y aplicado el 2026-06-13.

Decision operativa registrada:

- Implementar primero advertencias visibles sin bloquear la operacion.
- Dejar para autorizacion posterior convertir advertencias en bloqueo o autorizacion especial.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js`.
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.
- `docs/erp_proveedores_avance.md`.

Alcance implementado:

- El contrato de Proveedores agrega `advertencias_operativas` al payload de busqueda para Compras.
- Se detectan advertencias por:
  - costo vigente faltante;
  - unidad o factor incompleto;
  - datos fiscales incompletos;
  - codigo principal faltante.
- Solicitudes muestra las advertencias en resultados de busqueda y partidas agregadas.
- Ordenes muestra las advertencias en resultados de busqueda y partidas agregadas.
- Ordenes recibe datos fiscales disponibles desde el contrato para alimentar el modal fiscal cuando existan.
- No se bloquea agregar, guardar, enviar solicitud ni guardar orden.

Limites aplicados:

- No se definieron bloqueos.
- No se cambio el flujo de autorizacion.
- No se cambio inventario.
- No se actualizo `costo_referencia`.
- No se hicieron escrituras masivas ni migraciones.
- No se cambio el endpoint publico de Compras.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\compras\solicitudes\formulario.js`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\compras\ordenes\formulario.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Despues de probar con datos reales, decidir cuales advertencias deben ser bloqueo, cuales deben pedir autorizacion y cuales deben quedar solo informativas.

## Politica operativa propuesta - advertencias, confirmaciones y bloqueos

Estado: documentada el 2026-06-13 como criterio de trabajo para Proveedores + Compras. No todos los puntos estan implementados.

Principio:

- No bloquear por datos incompletos si la operacion todavia puede continuar sin dano operativo.
- Bloquear o pedir autorizacion solo cuando el faltante puede provocar error real en compra, inventario, recepcion, factura o pago.
- Mantener Solicitudes como etapa flexible de intencion; endurecer controles conforme el flujo avanza hacia Orden, Recepcion y Pago.

Criterio por etapa:

| Validacion | Solicitud | Orden | Recepcion | Pago |
| --- | --- | --- | --- | --- |
| Relacion proveedor-SKU inexistente o inactiva | Advertir/no ofrecer en busqueda normal | Bloquea si se intenta enviar como SKU ERP del proveedor | Bloquear | N/A |
| Unidad/factor incompleto | Advertir | Confirmar antes de enviar | Bloquear o autorizacion especial | N/A |
| Costo vigente faltante | Advertir | Confirmar antes de enviar si hay costo capturado | Informar | Revisar contra factura |
| Costo unitario cero | Advertir si aparece en busqueda/lista | Bloquear envio de orden | Bloquear recepcion operativa | Bloquear conciliacion/pago si afecta factura |
| Moneda extranjera sin tipo de cambio | N/A | Bloquear envio de orden | Bloquear si falta conversion | Bloquear si afecta pago |
| Fiscal SKU incompleto | Advertir | Advertir | Advertir | Bloquear si afecta XML/factura |
| Codigo principal faltante | Advertir | Advertir | Advertir | N/A |
| Proveedor suspendido/no autorizado | Pendiente definir estados | Pendiente definir estados | Pendiente definir estados | Pendiente definir estados |
| Cuenta bancaria/documentos financieros | N/A | N/A | N/A | Bloquear cuando Finanzas lo implemente |

Regla aplicada ahora:

- Solicitudes: solo advertencias, sin confirmacion.
- Ordenes: advertencias visibles y confirmacion al enviar si hay `sin_costo_vigente` o `unidad_factor_incompleto`.
- Ordenes: bloqueo servidor al enviar si hay moneda invalida, moneda extranjera sin tipo de cambio, total cero o partidas con costo unitario cero.
- Ordenes: la relacion proveedor-SKU activa ya se valida en servidor cuando la partida usa SKU ERP.
- Fiscal incompleto y codigo principal faltante siguen como advertencias, sin confirmacion obligatoria.

Pendiente de autorizacion futura:

- Definir estados reales del proveedor para saber cuando bloquear compras.
- Convertir unidad/factor incompleto en bloqueo o autorizacion especial en recepcion.
- Definir regla para fiscal/XML/pago con Finanzas.

## Evidencia P8-04 base - confirmacion no bloqueante en Ordenes

Estado: autorizado y aplicado el 2026-06-13.

Archivo modificado:

- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.
- `docs/erp_proveedores_avance.md`.

Alcance implementado:

- Al enviar una orden (`estatus=enviada`), el JS revisa advertencias operativas de las partidas.
- Si detecta `sin_costo_vigente` o `unidad_factor_incompleto`, muestra confirmacion antes de guardar.
- La confirmacion permite volver a revisar o enviar de todos modos.
- Guardar borrador no pide confirmacion.
- No se bloquea la orden ni se requiere permiso nuevo.

Limites aplicados:

- No se implemento bloqueo real.
- No se agrego doble autorizacion.
- No se cambio servidor, permisos ni esquema.
- No se modificaron solicitudes u ordenes existentes.
- No se cambio recepcion, pagos ni XML.

Verificacion pendiente:

- Probar en UI con proveedor/SKU que tenga advertencias reales.
- Confirmar que la orden se envia solo despues de aceptar el modal.

## Evidencia P9-01 - reutilizacion productivo legacy documentada

Estado: documentado el 2026-06-13. No se ejecuto migracion.

Fuentes revisadas:

- `db/productivo/erp_proveedores.sql`.
- `db/productivo/erp_proveedores_listas.sql`.
- `db/productivo/erp_proveedores_listas_productos.sql`.

Hallazgos:

- `erp_proveedores.sql` contiene maestro simple con 23 proveedores y campos `id_proveedor`, `proveedor`, `cuota`.
- `erp_proveedores_listas.sql` contiene 10 listas con `id_lista_proveedor`, `id_proveedor`, `lista`, `estatus`, `fch_r`.
- `erp_proveedores_listas_productos.sql` contiene renglones de listas con marca, codigos, SKU proveedor, existencia, nombre, descripcion, costo, precio sugerido, piezas por caja, impuestos y estatus.

Decision registrada:

- Si se reutilizara informacion productiva legacy, debe hacerse con migracion selectiva y vista previa, no ejecutando los SQL directamente sobre la base actual.
- Los renglones legacy deben entrar primero como evidencia de lista, no como costos oficiales ni relaciones proveedor-SKU automaticas.
- El matching, las relaciones y costos deben aplicarse despues con revision individual o lote autorizado.

Siguiente paso futuro:

- Crear auditoria/importador dry-run para estos SQL o para tablas staging equivalentes.
- Mostrar conteos, duplicados, renglones sin costo, renglones sin SKU/codigo y posibles matches antes de migrar.

## Evidencia P9-02 - auditoria dry-run de SQL productivo

Estado: aplicado el 2026-06-13 en solo lectura. No importa datos.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/auditoria.php`.
- `public/assets/js/custom/apps/erp/proveedores/auditoria.js`.
- `docs/erp_proveedores_avance.md`.

Alcance implementado:

- La auditoria `/proveedor/auditoria_erp` ahora revisa archivos SQL en `db/productivo`.
- Archivos revisados:
  - `erp_proveedores.sql`;
  - `erp_proveedores_listas.sql`;
  - `erp_proveedores_listas_productos.sql`.
- El dry-run muestra:
  - existencia y tamano de cada archivo;
  - conteo de proveedores en SQL;
  - conteo de listas en SQL;
  - conteo de renglones en SQL;
  - renglones sin costo;
  - renglones sin SKU/nombre;
  - renglones sin codigo util;
  - muestras limitadas de proveedores, listas y renglones.

Limites aplicados:

- No ejecuta los SQL.
- No crea tablas staging.
- No importa proveedores, listas ni renglones.
- No aplica matching, relaciones ni costos.
- No actualiza `costo_referencia`.
- No modifica datos.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\auditoria.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\auditoria.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- El siguiente paso ya seria decidir si el importador debe trabajar leyendo directamente SQL, creando tablas staging temporales, o usando archivos normalizados CSV/Excel.

## Evidencia P9-03 ampliada - vista previa de migracion productivo

Estado: aplicado el 2026-06-13 en solo lectura. No importa datos.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/auditoria.php`.
- `public/assets/js/custom/apps/erp/proveedores/auditoria.js`.
- `docs/erp_proveedores_avance.md`.

Alcance implementado:

- La auditoria de productivo SQL ahora calcula una vista previa de migracion.
- Para proveedores, clasifica accion propuesta:
  - crear;
  - conservar/actualizar si ya existe mismo `id_proveedor`;
  - revisar si existe nombre similar con otro id.
- Para listas, clasifica accion propuesta:
  - crear en borrador;
  - existente si ya hay `id_lista_legacy`;
  - revisar si el proveedor origen no existe en el SQL.
- Para renglones, clasifica accion propuesta:
  - crear como evidencia;
  - existente si ya hay `id_producto_legacy`;
  - revisar si falta lista, costo o identificador util.
- Calcula conteos de renglones con match exacto de SKU ERP y sin match exacto.
- La UI muestra resumen de acciones y muestras limitadas.

Limites aplicados:

- No ejecuta importacion.
- No crea tablas staging.
- No inserta proveedores/listas/renglones.
- No aplica relaciones proveedor-SKU.
- No aplica costos vigentes.
- No actualiza `costo_referencia`.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\auditoria.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\auditoria.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Para importar datos reales se requiere decidir estrategia:
  - importador leyendo SQL directo;
  - tablas staging temporales;
  - conversion previa a CSV/Excel normalizado.
- Tambien requiere respaldo de base de datos antes de cualquier escritura.

## Evidencia P9-04 plan - contrato staging migracion productivo

Estado: planeado el 2026-06-13. No ejecutado.

Archivo modificado:

- `app/modelos/ProveedoresEsquema.php`.
- `docs/erp_proveedores_avance.md`.

Alcance implementado:

- Se agrego al contrato de esquema la tabla planeada `erp_proveedores_migracion_staging`.
- La tabla staging propuesta es generica para proveedores, listas y renglones.
- Campos planeados:
  - lote;
  - fuente;
  - tipo_registro;
  - id_origen;
  - id_padre_origen;
  - referencia;
  - payload_json;
  - hash_origen;
  - accion_propuesta;
  - estado_revision;
  - motivo_revision;
  - id_destino;
  - destino_tipo;
  - creado_por;
  - fechas.
- Indices planeados para lote/tipo, origen, hash y estado de revision.

Limites aplicados:

- No se ejecuto `esquema_actualizar_erp`.
- No se creo la tabla en base de datos.
- No se insertaron registros staging.
- No se importaron datos productivos.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\ProveedoresEsquema.php`: sin errores de sintaxis.
- `git diff --check`: sin errores reales; solo avisos CRLF/LF del entorno Windows.

Siguiente alto de autorizacion:

- Crear la tabla staging requiere autorizacion explicita de esquema y respaldo de base de datos previo.
- Despues de crear staging, el siguiente paso seria cargar un lote dry-run desde `db/productivo` sin tocar tablas oficiales.

## Evidencia P9-04 ejecucion - tabla staging creada

Estado: autorizado y aplicado el 2026-06-13.

Respaldo previo:

- `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260613_205904.sql`.

Tabla creada:

- `erp_proveedores_migracion_staging`.

Alcance ejecutado:

- Se creo la tabla staging con `CREATE TABLE IF NOT EXISTS`.
- Se confirmaron columnas con `SHOW COLUMNS`.
- La tabla queda preparada para recibir lotes de preview/migracion controlada de proveedores, listas y renglones.

Limites aplicados:

- No se cargaron registros staging.
- No se importaron proveedores, listas ni renglones.
- No se tocaron tablas oficiales de Proveedores ERP.
- No se aplicaron relaciones proveedor-SKU.
- No se aplicaron costos vigentes.
- No se actualizo `costo_referencia`.

Siguiente alto de autorizacion:

- Cargar el primer lote staging desde `db/productivo` requiere autorizacion porque ya escribira registros, aunque sean registros temporales/controlados.

## Evidencia P9-05 - primer lote staging cargado desde productivo

Estado: autorizado y aplicado el 2026-06-14.

Lote:

- `productivo_sql_20260614_050242`.

Alcance ejecutado:

- Se cargaron registros a `erp_proveedores_migracion_staging`.
- Fuentes:
  - `db/productivo/erp_proveedores.sql`;
  - `db/productivo/erp_proveedores_listas.sql`;
  - `db/productivo/erp_proveedores_listas_productos.sql`.
- Se agrego resumen de lotes staging en `/proveedor/auditoria_erp`.

Resultado del lote:

- Proveedores staging: 23.
- Listas staging: 10.
- Renglones staging: 9280.
- Total staging del lote: 9313.
- Registros a revisar: 2.
- Motivo de revision detectado: costo no positivo.
- Renglones a revisar:
  - `id_producto` 4993, referencia `OCL-28A`.
  - `id_producto` 4994, referencia `OCL-28W`.

Distribucion por accion:

- `proveedor / conservar_actualizar`: 23.
- `lista / crear_borrador`: 10.
- `renglon / crear_evidencia`: 9278.
- `renglon / revisar`: 2.

Limites aplicados:

- No se importaron datos a tablas oficiales.
- No se crearon proveedores nuevos en `erp_proveedores`.
- No se crearon listas ERP oficiales.
- No se crearon renglones ERP oficiales.
- No se aplico matching oficial.
- No se aplicaron relaciones proveedor-SKU.
- No se aplicaron costos vigentes.
- No se actualizo `costo_referencia`.

Verificacion ejecutada:

- Consulta por lote en `erp_proveedores_migracion_staging`.
- Consulta de registros con `accion_propuesta LIKE 'revisar%'`.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\auditoria.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\auditoria.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Convertir staging hacia tablas oficiales ERP requiere autorizacion explicita por tratarse de datos reales del modulo.
- Recomendacion: ejecutar primero conversion de proveedores/listas/renglones como borrador/evidencia, sin relaciones ni costos oficiales.

## Evidencia P9-06 - staging convertido a tablas ERP oficiales

Estado: autorizado y aplicado el 2026-06-14.

Respaldo previo:

- `C:\Users\aleja\Documents\RespaldosBD\panel\artianilocal_panel_20260613_210952.sql`.

Lote convertido:

- `productivo_sql_20260614_050242`.

Alcance ejecutado:

- Se convirtio el staging a tablas oficiales del modulo Proveedores ERP.
- Se conservaron/actualizaron 23 proveedores existentes y se creo/actualizo su perfil ERP.
- Se crearon 10 listas ERP oficiales con `origen='productivo_sql'` y `estatus='borrador'`.
- Se crearon 9278 renglones en `erp_proveedores_listas_detalle_erp` como evidencia de lista.
- Se dejaron 2 renglones pendientes en staging por costo no positivo.

Reglas respetadas:

- No se ejecutaron los SQL productivos directo sobre la base actual.
- No se sustituyeron tablas ERP nuevas por tablas legacy.
- No se aplicaron costos vigentes.
- No se crearon relaciones proveedor-SKU automaticamente.
- No se actualizo `erp_catalogo_skus.costo_referencia`.
- No se modifico Catalogo salvo consulta de SKU exacto para clasificar candidatos.

Resultado validado:

- Listas oficiales importadas: 10 en `borrador`.
- Renglones oficiales importados: 9278.
- Renglones con `id_sku` candidato exacto: 955.
- Renglones sin `id_sku`: 8323.
- Staging aplicado:
  - `proveedor / conservar_actualizar`: 23.
  - `lista / crear_borrador`: 10.
  - `renglon / crear_evidencia`: 9278.
- Staging pendiente:
  - `renglon / revisar`: 2.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `docs/erp_proveedores_avance.md`.

Verificacion ejecutada:

- Conversion CLI con `Proveedores::convertirLoteStagingProductivoErp('productivo_sql_20260614_050242', 0)`.
- Conteo directo por `origen='productivo_sql'` en `erp_proveedores_listas_erp`.
- Conteo directo de detalles con `id_producto_legacy IS NOT NULL`.
- Conteo directo por estado en `erp_proveedores_migracion_staging`.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.

Pendientes para la siguiente autorizacion:

- Revisar los 2 renglones legacy con costo no positivo: `OCL-28A` y `OCL-28W`.
- Revisar los 8323 renglones sin SKU ERP candidato antes de crear relaciones.
- Decidir si se agrega una pantalla/filtro operativo para listas importadas desde `productivo_sql`.
- Mantener costos vigentes y `costo_referencia` pendientes hasta regla autorizada.

## Evidencia P8-05 - checklist preparacion proveedor

Estado: aplicado el 2026-06-13 en solo lectura.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Alcance implementado:

- La ficha de proveedor ahora incluye pestaña `Preparacion`.
- El endpoint existente `/proveedor/proveedor_consultar_erp` devuelve `preparacion` en el payload.
- El checklist calcula porcentaje, estado y puntos de revision sin escribir datos.
- Puntos revisados:
  - datos fiscales;
  - contactos;
  - condiciones;
  - documentos metadata;
  - listas versionadas;
  - renglones de lista;
  - matching pendiente;
  - renglones sin costo;
  - relaciones proveedor-SKU;
  - costos vigentes;
  - incidencias pendientes.
- La UI muestra estado `listo_pruebas`, `en_preparacion` o `incompleto`.
- Cada punto muestra valor y accion sugerida.

Limites aplicados:

- No se ejecutaron migraciones.
- No se modificaron datos.
- No se crearon bloqueos.
- No se aplicaron relaciones ni costos.
- No se uso informacion productiva legacy todavia; solo quedo documentada como fase futura.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- `git diff --check`: sin errores reales; solo avisos CRLF/LF del entorno Windows.

Siguiente alto de autorizacion:

- Probar checklist con proveedores reales.
- Despues decidir si se implementa dry-run/importador legacy desde `db/productivo` o si primero se avanza con carga fisica de documentos/listas.

## Evidencia P10-01 - rutas Proveedores ERP en sidebar

Estado: autorizado y aplicado el 2026-06-12.

Archivo modificado:

- `app/vistas/includes/header/sidebar.php`.

Alcance implementado:

- Se agrego grupo propio `Proveedores` en el sidebar.
- Ruta `Maestro proveedores`: `/proveedor/mostrar_proveedores_erp` con permiso `proveedores.ver`.
- Ruta `Auditoria proveedores`: `/proveedor/auditoria_erp` con permiso `proveedores.auditoria`.
- El enlace legacy `Compras > Proveedores` se conserva apuntando a `/proveedor/listas_mostrar` con `compras.ver`.
- El sidebar refresca permisos desde BD al cargar, por lo que usa los permisos `proveedores.*` ya sembrados.

Alcance no implementado:

- No se retiraron pantallas legacy.
- No se cambiaron rutas de Compras.
- No se agregaron documentos/evidencias ni nuevas reglas de negocio.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\vistas\includes\header\sidebar.php`: sin errores de sintaxis.

## Siguiente tarea recomendada

Siguiente punto de avance recomendado: validar con datos reales el flujo individual de Proveedores y Compras ya conectado al contrato central, antes de acciones masivas, bloqueos reales, importacion de archivos o `costo_referencia`.

Motivo:

- El bloque `P1` ya tiene esquema base aplicado y auditoria posterior limpia.
- El bloque `P2` ya tiene permisos propios sembrados y endpoints tecnicos nuevos sin fallback a `compras.ver`.
- P3-01 a P3-03 ya permiten lectura por endpoint sin tocar datos.
- P3-09/P3-10 ya permiten revisar listado y ficha en UI.
- P3-04 ya permite alta/edicion de datos generales sin tocar estatus ni secciones sensibles.
- P3-05 ya permite alta/edicion fiscal sin validar SAT ni cargar constancia.
- P3-06 ya permite alta/edicion de contactos por area sin enviar notificaciones.
- P3-07 ya permite alta/edicion de condiciones sin activar bloqueos ni autorizaciones financieras.
- P3-11 ya permite alta/edicion de documentos/evidencias metadata sin subir archivo fisico.
- P4-03 ya muestra conteos y muestras limitadas de listas legacy candidatas, sin migrar datos.
- P4-04/P4-06 ya permiten registrar encabezados de listas ERP versionadas, ligar/cargar archivo original como evidencia y mantener separado el paso de importacion/aplicacion.
- P4-05 ya permite consultar/capturar renglones de lista como evidencia, sin matching ni costos oficiales.
- P5-01/P5-02 parcial ya permiten calcular y revisar matching dry-run sin escribir relaciones.
- P5-03 ya permite guardar decisiones sobre el renglon.
- P5-04 ya permite aplicar la relacion SKU-proveedor oficial sin tocar costos.
- P6-01 ya define historial `erp_proveedores_sku_costos` como fuente de verdad.
- P6-03/P6-04 ya permiten consultar resumen e historial de costos en ficha, con filtro local, sin aplicar costos.
- P6-05 ya permite aplicar costo individual como vigente y actualizar `costo_ultimo`, sin tocar `costo_referencia`.
- P7-01/P7-04 parcial ya permiten proponer pendientes/incidencias desde lista sin crear registros reales.
- P7-02/P7-06 ya permiten crear incidencia real individual desde una propuesta recalculada y evitar duplicados por huella.
- P8-01 ya expone un contrato solo lectura de SKUs comprables por proveedor desde Proveedores.
- P8-02 preparatoria ya permite comparar el contrato de Proveedores contra busquedas actuales de Solicitudes/Ordenes sin migrar.
- P8-02 real ya conecta las busquedas de Solicitudes y Ordenes al contrato central de Proveedores conservando endpoints y payload esperado por el JS.
- P8-03 ya muestra advertencias operativas y bloquea envio de orden solo para proveedor `suspendido`, `bloqueado` o `inactivo`.
- P8-04 ya pide confirmacion no bloqueante al enviar Ordenes con costo vigente faltante o unidad/factor incompleto y bloquea minimos criticos en servidor.

Orden recomendado inmediato:

1. Probar alta/edicion general, fiscal, contactos, condiciones, documentos metadata, encabezados de lista, renglones, matching dry-run, decision de matching, aplicacion de relacion, aplicacion individual de costo, consulta de historial de costos, dry-run de pendientes y creacion individual de incidencia con usuario que tenga permisos correspondientes.
2. Revisar auditoria generada para `proveedor_crear`, `proveedor_editar_generales`, `proveedor_fiscal_guardar`, `proveedor_contacto_guardar`, `proveedor_condicion_guardar`, `proveedor_documento_guardar`, `proveedor_lista_guardar`, `proveedor_lista_detalle_guardar`, `proveedor_matching_decidir`, `proveedor_sku_relacion_aplicar`, `proveedor_costo_aplicar` y `proveedor_incidencia_crear`.
3. Revisar muestras legacy en `/proveedor/auditoria_erp` para decidir si hay datos aprovechables.
4. Dejar carga fisica de archivos para documentos/listas como tarea posterior, despues de validar el flujo de encabezado y detalle.
5. Probar `/proveedor/skus_comprables_por_proveedor_erp`, `/proveedor/compras_contrato_comparar_erp`, `/compra/solicitudes_buscar_skus_erp` y `/compra/orden_buscar_skus_erp` con proveedores reales.
6. Detener antes de crear incidencias en lote, aplicar costos en lote, tocar `costo_referencia`, importacion masiva, bloqueos operativos o estatus/autorizaciones porque ahi si hay decisiones de negocio.

## Guia para pruebas reales de Proveedores

Objetivo: probar con pocos casos reales, sin cargar masivamente datos ni activar bloqueos definitivos.

Documento operativo separado:

- `docs/erp_pruebas_reales_modulos.md` concentra la guia viva de pruebas reales por modulo. La seccion Proveedores se genero desde esta guia para retomarla durante pruebas reales sin saturar el avance tecnico.

Preparacion recomendada:

1. Elegir 1 proveedor confiable con datos relativamente completos.
2. Elegir 1 proveedor con datos incompletos o lista dudosa.
3. Elegir 3 a 5 SKUs reales que se compren con frecuencia.
4. Usar un usuario con permisos `proveedores.*` y otro usuario con permisos de Compras para confirmar visibilidad por rol.

Flujo minimo de prueba:

1. Abrir `Proveedores > Maestro proveedores`.
2. Crear o editar datos generales del proveedor.
3. Registrar datos fiscales, contactos y condiciones.
4. Registrar documentos solo como metadata, sin archivo fisico.
5. Crear encabezado de lista versionada.
6. Capturar algunos renglones reales de lista.
7. Ejecutar matching dry-run.
8. Guardar decision de matching en un renglon.
9. Aplicar relacion proveedor-SKU para un caso confiable.
10. Aplicar costo individual como vigente para ese renglon.
11. Revisar historial de costos.
12. Probar `Pendientes` desde la lista y crear una incidencia individual si el caso lo amerita.
13. Crear una Solicitud de compra con ese proveedor y verificar advertencias.
14. Crear una Orden de compra y verificar advertencias.
15. Enviar Orden y confirmar que el modal aparece solo cuando hay costo vigente faltante o unidad/factor incompleto.

Evidencia que conviene anotar durante pruebas:

- Proveedor usado.
- Lista/version usada.
- SKU ERP y SKU proveedor.
- Costo capturado y moneda.
- Advertencias mostradas.
- Si la advertencia fue correcta, excesiva o faltante.
- Si Compras pudo avanzar sin friccion innecesaria.
- Si algun dato deberia bloquear en Orden, Recepcion o Pago.

No hacer todavia en pruebas iniciales:

- Ejecutar migraciones masivas.
- Aplicar relaciones/costos en lote sin preview y confirmacion.
- Crear incidencias en lote.
- Actualizar `costo_referencia` fuera del flujo controlado.
- Cambiar estados definitivos de proveedor.
- Bloquear Solicitudes u Ordenes por reglas nuevas.
- Migrar o transformar datos legacy masivamente.

## Ruta para terminar modulo Proveedores

Fase A - Validacion operativa con casos reales:

- Probar flujo individual completo de proveedor/lista/renglon/matching/relacion/costo.
- Confirmar que las advertencias de Compras son utiles y no estorban.
- Revisar auditoria de acciones criticas.
- Ajustar textos, columnas o visibilidad si una pantalla confunde al usuario.

Fase B - Archivos y evidencias:

- Implementar carga fisica de documentos del proveedor.
- Implementar carga fisica de archivo original de lista.
- Definir politica de almacenamiento, sensibilidad y retencion.
- Mantener metadata separada del archivo para auditoria.

Fase C - Importacion controlada de listas:

- Subir archivo original del proveedor como evidencia, sin exigir estructura unica.
- Crear encabezado de lista en borrador con tipo, version, vigencia, moneda y observaciones.
- Crear importador de listas con vista previa.
- Permitir mapeo de columnas porque cada proveedor puede enviar estructura distinta.
- Validar columnas, moneda, costos, unidad, factor y duplicados.
- Mostrar conteos antes de importar.
- Permitir importar en borrador sin aplicar relaciones ni costos automaticamente.
- Registrar pendientes por renglon sin bloquear toda la carga.

Fase D - Aplicacion masiva con revision:

- Aplicar matching en lote solo con vista previa y exclusiones.
- Aplicar costos en lote solo desde lista vigente revisada.
- Crear incidencias en lote solo con seleccion explicita.
- Registrar auditoria clara de usuario, conteos y criterios usados.

Fase E - Reglas operativas por departamento:

- Definir estados de proveedor para compras, pagos y bloqueo administrativo.
- Definir cuando Compras advierte, confirma, pide autorizacion o bloquea.
- Definir con Almacen que hacer si falta unidad/factor en recepcion.
- Definir con Finanzas que datos bloquean pago.

Fase F - Cierre ERP robusto:

- Documentar flujo final por departamento.
- Probar con 2 o 3 proveedores reales completos.
- Revisar datos legacy aprovechables y decidir migracion selectiva.
- Dejar pendientes de mejora separados de bloqueos para salida operativa.

Fase G - Migracion selectiva desde productivo legacy:

- Reutilizar informacion existente de `db/productivo` sin comenzar de cero.
- Fuentes detectadas:
  - `db/productivo/erp_proveedores.sql`;
  - `db/productivo/erp_proveedores_listas.sql`;
  - `db/productivo/erp_proveedores_listas_productos.sql`.
- Datos observados en revision inicial:
  - `erp_proveedores.sql`: maestro simple con `id_proveedor`, `proveedor`, `cuota`; contiene 23 proveedores en el dump.
  - `erp_proveedores_listas.sql`: listas por proveedor con `id_lista_proveedor`, `id_proveedor`, `lista`, `estatus`, `fch_r`; contiene 10 listas en el dump.
  - `erp_proveedores_listas_productos.sql`: renglones de listas con marca, codigos, SKU proveedor, existencia, nombre, descripcion, costo, precio sugerido, piezas por caja, impuestos y estatus; archivo grande candidato a migracion controlada.
- Enfoque recomendado:
  - Auditar primero conteos, duplicados, proveedores sin lista, listas sin proveedor, renglones sin costo y renglones sin codigo/SKU.
  - Mapear proveedores legacy contra `erp_proveedores` nuevo conservando identidad cuando sea confiable.
  - Migrar listas legacy hacia `erp_proveedores_listas_erp` como listas versionadas en estatus borrador/revision.
  - Migrar renglones hacia `erp_proveedores_listas_detalle_erp` como evidencia de lista, sin aplicar costos automaticamente.
  - Ejecutar matching dry-run despues de migrar renglones, no durante importacion ciega.
  - Aplicar relaciones y costos solo por revision individual o lote autorizado con vista previa.
- No hacer en esta fase sin autorizacion:
  - Ejecutar los SQL productivos directo sobre la base actual.
  - Reemplazar tablas ERP nuevas por tablas legacy.
  - Aplicar costos en lote automaticamente.
  - Actualizar `costo_referencia` fuera del flujo controlado.
  - Crear relaciones proveedor-SKU sin revisar matching.
  - Borrar o transformar datos legacy originales.

Siguiente implementable recomendado antes de nuevas reglas de negocio:

- Probar con datos reales el checklist de preparacion del proveedor y los indicadores de preparacion de lista; ajustar textos si confunden, sin cambiar reglas de negocio.

Pendientes documentados para retomar despues:

- P9 migracion selectiva productivo: auditar y preparar importacion controlada desde `db/productivo/erp_proveedores*.sql`, sin ejecutar SQL directo ni aplicar costos/relaciones automaticamente.
- P7 lote: crear varias incidencias reales desde propuestas del dry-run solo con revision previa, exclusiones, conteos y confirmacion.
- P7 bloqueos: definir si severidades `bloqueante` deben requerir doble autorizacion o crear bloqueo operativo en Compras.
- P6-06: probar aplicacion masiva de costos desde lista actual con preview, conteos y confirmacion antes de ampliar reglas.
- P6-07: probar con compras reales; queda pendiente solo politica cambiaria futura para costos proveedor/lista no MXN y decision financiera si algun dia se persiste costo promedio.
- P6/P7 autorizaciones: definir si acciones masivas requieren doble autorizacion o basta permiso `proveedores.costos`/`proveedores.autorizar`.
- P4/P3 archivos: implementar carga fisica de documentos/listas con politica de almacenamiento y retencion.
- P8 seguimiento: probar busquedas de Compras ya conectadas al contrato de Proveedores y decidir advertencias/bloqueos operativos.

Recomendacion:

- No importar renglones de lista desde archivos ni aplicar costos en lote hasta validar encabezados/detalle con datos reales.
- No tocar `costo_referencia` hasta autorizar regla y alcance de aplicacion.
- No crear incidencias en lote hasta revisar suficientes casos individuales.
- No definir estados finales (`activo_compras`, `activo_pagos`, etc.) hasta que el flujo de autorizacion por departamento quede acordado.

## Tareas implementables seguras

## Evidencia P8-06 - preparacion de listas para pruebas reales

Fecha: 2026-06-14.

Objetivo:

- Mejorar el checklist solo lectura de preparacion del proveedor para que ayude a probar listas reales sin modificar datos ni definir reglas nuevas.

Implementado:

- `Proveedores::preparacionProveedorErp()` ahora separa conteos de listas por avance operativo:
  - `listas_borrador`
  - `listas_cargadas`
  - `listas_validadas`
  - `listas_aplicadas`
- El checklist agrega puntos especificos para:
  - Listas cargadas.
  - Listas validadas.
  - Listas aplicadas.
  - Renglones relacionados.
  - Moneda en lista.
  - Costos aplicados desde lista.
- El conteo de costos aplicados se calcula desde `erp_proveedores_sku_costos` con `id_lista_detalle_erp`, no desde una columna inexistente en el detalle de lista.
- La ficha de proveedor muestra etiquetas de avance: listas, borrador, cargadas, validadas, aplicadas, renglones, relacionados, sin moneda y costos aplicados.

Alcance:

- Solo lectura.
- No ejecuta migraciones.
- No inserta, actualiza ni borra datos.
- No cambia reglas de compra; solo muestra diagnostico para decidir que falta revisar.

Utilidad para pruebas reales:

- Si una lista tiene renglones pero no moneda, se puede identificar antes de validar o aplicar costos.
- Si una lista tiene productos relacionados, ya puede servir como referencia parcial aunque no todos los productos del proveedor esten ligados a Catalogo.
- Si no hay relaciones ni costos aplicados, la lista sigue siendo material de trabajo del proveedor, no referencia operativa lista para Compras.
- Si hay incidencias pendientes, se deben revisar en Catalogo antes de considerar completo el proveedor.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- Prueba de lectura `consultarListaDetalleErp(8, 11)`: total 162, operativos 0, sin match informativo 162, `puede_validar = false`, `puede_aplicar = false`.

Decision tomada:

- El estado `validada` debe exigir moneda/costo/identidad solo en renglones operativos, no en todos los renglones informativos de la lista.

## Evidencia P8-07 - validacion por renglones operativos

Fecha: 2026-06-14.

Objetivo:

- Evitar que una lista de proveedor quede bloqueada solo porque contiene productos informativos, notas o productos que todavia no se van a comprar ni relacionar con Catalogo.

Decision operativa:

- Una lista puede contener renglones informativos como evidencia del proveedor.
- Solo los renglones operativos bloquean la validacion.
- Un renglon operativo es el que ya tiene SKU ERP, relacion SKU proveedor o estado de match seleccionado/aplicado.
- No se agregara una marca manual extra para decir "informativo" u "operativo".
- Un producto de proveedor que todavia no se va a comprar permanece como informativo/evidencia.
- Si un producto de proveedor se quiere evaluar para compra y no existe en Catalogo, primero debe mandarse a Catalogo como pendiente/alta temporal.
- Despues de que Catalogo cree o complete el producto/SKU temporal, Proveedores hace matching contra ese SKU ERP.
- A partir del matching, el renglon empieza a considerarse operativo y entonces debe completar identidad, costo y moneda para contar como referencia.
- Para marcar una lista como `validada`, debe existir al menos un renglon operativo y esos renglones operativos deben tener identidad, costo y moneda.
- Los renglones sin match quedan como evidencia y no bloquean por si solos.
- Para marcar `aplicada`, se mantiene la regla de que ya exista al menos una relacion o costo aplicado.

Implementado:

- `resumenValidacionListaProveedorErp()` ahora calcula:
  - `operativos`
  - `operativos_sin_identidad`
  - `operativos_sin_costo`
  - `operativos_sin_moneda`
  - `sin_match_informativo`
- `puede_validar` ahora depende de los renglones operativos, no de todos los renglones de la lista.
- La UI de detalle de lista muestra badges de renglones operativos y pendientes operativos.
- La UI de detalle de lista agrega filtros `Operativos` e `Informativos` para separar renglones que afectan Compras de renglones que solo quedan como evidencia.
- El mensaje para validar lista explica que los demas renglones quedan como evidencia del proveedor.

Alcance:

- No ejecuta migraciones.
- No aplica costos.
- No crea relaciones.
- Solo cambia la regla de validacion del estatus de lista y la forma de explicar el avance.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

## Evidencia P7-08 - pendientes solo operativos

Fecha: 2026-06-14.

Objetivo:

- Evitar que los renglones informativos de una lista generen ruido operativo de costo, moneda o unidad cuando todavia no se van a comprar.

Decision aplicada:

- Si un renglon no tiene SKU ERP confiable, el dry-run propone `proveedor_sku_sin_match`.
- Ese pendiente significa: revisar o solicitar alta temporal en Catalogo solo si se quiere evaluar para compra.
- Mientras no exista SKU ERP/matching operativo, no se generan pendientes de unidad, factor, costo, moneda, codigo o fiscal para ese renglon.
- Los pendientes de unidad/costo/fiscal/codigo se generan cuando el renglon ya es operativo.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_pruebas_reales.md`.

Alcance:

- No crea productos temporales.
- No crea incidencias automaticamente.
- No modifica Catalogo.
- Solo reduce ruido y mejora la ruta de resolucion para pruebas reales.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- Prueba de lectura `incidenciasListaDryRunErp(8, 11)`: total 162, todos `proveedor_sku_sin_match`; ya no genera ruido de costo/unidad/moneda para renglones informativos.

## Evidencia P7-09 - SKU temporal desde incidencia de Proveedores

Fecha: 2026-06-14.

Objetivo:

- Permitir que Catalogo cree un producto/SKU temporal controlado cuando Proveedores escala un renglon sin SKU ERP confiable y el negocio decide evaluarlo para compra.

Flujo aplicado:

1. Proveedores crea incidencia individual `proveedor_sku_sin_match`.
2. Catalogo revisa la incidencia.
3. Catalogo crea SKU temporal desde la incidencia usando unidad base explicita.
4. Producto y SKU quedan en `borrador`.
5. La incidencia queda en `en_revision` ligada a `id_producto_erp` e `id_sku`.
6. Proveedores vuelve a ejecutar matching y selecciona/aplica la relacion si corresponde.

Implementado:

- Endpoint `POST /catalogoerp/incidencia_proveedor_crear_sku_temporal`.
- Modelo `CatalogoErpDatos::crearSkuTemporalDesdeIncidenciaProveedor()`.
- Tarjeta ligera `Incidencias de calidad` en la vista de Catalogo.
- Modal `Crear SKU temporal` con unidad base explicita y precarga desde evidencia del renglon proveedor.
- Validaciones:
  - Solo acepta incidencias origen `proveedores`.
  - Solo acepta tipo `proveedor_sku_sin_match`.
  - Rechaza incidencias `resuelta` o `descartada`.
  - Rechaza incidencias que ya tengan `id_sku`.
  - Exige `id_unidad_base` activa.
  - Evita duplicar SKU o codigo principal existente.
- Auditoria explicita `crear_sku_temporal_desde_proveedor`.

Alcance:

- Crea datos maestros en Catalogo, pero en estado `borrador`.
- No activa el SKU.
- No crea relacion proveedor-SKU.
- No aplica costo.
- No actualiza `costo_referencia`.
- No modifica automaticamente el renglon de Proveedores; el match queda como paso posterior.

Archivos modificados:

- `app/controladores/CatalogoErp.php`.
- `app/modelos/CatalogoErpDatos.php`.
- `app/vistas/paginas/apps/erp/catalogo/productos.php`.
- `public/assets/js/custom/apps/erp/catalogo/productos.js`.
- `docs/erp_catalogo_avance.md`.
- `docs/erp_proveedores_pruebas_reales.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\CatalogoErp.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\CatalogoErpDatos.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\catalogo\productos.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\catalogo\productos.js`: sin errores de sintaxis.
- Prueba de validacion sin escritura con incidencia/unidad invalida: responde `warning` y no crea registros.
- Prueba de lectura `listarIncidenciasCalidad(estatus=abiertas, limite=5)`: responde sin error; total actual 0.

## Evidencia P5-09 - matching contra SKU temporal de Catalogo

Fecha: 2026-06-14.

Objetivo:

- Cerrar el retorno Catalogo -> Proveedores despues de crear un SKU temporal desde una incidencia de Proveedores.

Implementado:

- El matching dry-run de Proveedores ahora busca incidencias de Catalogo:
  - origen `proveedores`;
  - tipo `proveedor_sku_sin_match`;
  - referencia `erp_proveedores_listas_detalle_erp`;
  - misma referencia de renglon;
  - con `id_sku` ligado;
  - estatus abierto.
- Si encuentra un SKU ligado desde la incidencia, lo muestra como candidato con criterio `incidencia_catalogo_sku_temporal`.
- La UI indica que el candidato es `SKU temporal creado desde incidencia de Catalogo`.

Alcance:

- Solo lectura en matching dry-run.
- No aplica relacion proveedor-SKU.
- No aplica costo.
- No activa SKU.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- Prueba de lectura `matchingListaDryRunErp(8, 11)`: responde sin error; resumen actual 162 renglones, 15 relacionados, 2 posibles, 110 ambiguos, 35 sin match.

## Cierre P5-08 - SKU ERP preliminar desde producto proveedor

Fecha: 2026-06-15.

Estado:

- P5-08 queda aplicada.

Motivo:

- La tarea ya estaba implementada por el flujo P7-09/P5-09.
- La tabla principal seguia marcandola `en_revision`, pero el contrato operativo ya esta definido y aplicado.

Flujo cerrado:

1. Proveedores detecta renglon sin SKU ERP confiable.
2. Proveedores crea incidencia individual `proveedor_sku_sin_match`.
3. Catalogo revisa la incidencia.
4. Catalogo crea producto/SKU temporal en `borrador`.
5. La incidencia queda ligada al SKU temporal.
6. Proveedores vuelve a ejecutar matching.
7. Si corresponde, Proveedores aplica relacion proveedor-SKU como accion separada.

Regla vigente:

- Proveedores no crea SKUs automaticamente.
- Catalogo es responsable de crear/completar el SKU temporal.
- El SKU temporal no queda activo para venta por defecto.
- No se aplica relacion proveedor-SKU ni costo automaticamente.

Pruebas reales:

- Cubiertas en:
  - `docs/erp_catalogo_pruebas_reales.md`;
  - `docs/erp_proveedores_catalogo_pruebas_reales.md`;
  - `docs/erp_proveedores_pruebas_reales.md`.

## Evidencia P5-10 - indicador de compra para aplicar relacion

Fecha: 2026-06-14.

Objetivo:

- Ayudar a distinguir cuando un renglon ya tiene SKU ERP seleccionado pero todavia le falta unidad, factor o cantidad minima para aplicar relacion proveedor-SKU.

Implementado:

- En el detalle de lista, la columna de unidad muestra:
  - factor;
  - cantidad minima;
  - badge `Listo relacion` cuando tiene unidad/factor/cantidad minima;
  - badge `Completar compra` cuando falta alguno de esos datos.

Alcance:

- Solo UI.
- No cambia validaciones de servidor.
- No aplica relaciones ni costos.

Verificacion tecnica:

- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.

## Evidencia P5-11 - filtros de preparacion para relacion y costo

Fecha: 2026-06-14.

Objetivo:

- Facilitar la revision de listas antes de aplicar relaciones proveedor-SKU o costos, sin ejecutar cambios masivos.

Implementado:

- En el detalle de lista se agregaron filtros:
  - `Listo relacion`: renglones con SKU ERP seleccionado, unidad, factor y cantidad minima.
  - `Listo costo`: renglones con relacion proveedor-SKU ya aplicada, costo, moneda, unidad y factor.
- En el resumen del detalle se agregaron contadores:
  - `Listo relacion`.
  - `Listo costo`.
  - `Completar compra`.
- Se agregaron ayudas breves en los filtros del detalle para explicar que significa cada vista de revision sin invadir la pantalla.
- Se reforzaron las ayudas de los botones de estatus de lista (`validada`, `aplicada`, `historica`, `cancelada`) para explicar el efecto operativo antes de abrir la confirmacion.
- Se actualizo el versionado del JS de Proveedores para evitar cache viejo del navegador.

Alcance:

- Solo UI y filtrado local sobre la lista cargada en pantalla.
- No aplica relaciones.
- No aplica costos.
- No modifica Catalogo ni Compras.

Verificacion tecnica:

- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.

## Evidencia P5-12 - preview de relaciones proveedor-SKU en lote

Fecha: 2026-06-14.

Objetivo:

- Revisar que renglones de una lista podrian aplicar relacion proveedor-SKU en lote antes de autorizar una escritura real.

Implementado:

- Endpoint solo lectura `/proveedor/proveedor_sku_relaciones_lote_preview_erp`.
- Metodo `Proveedores::previewRelacionesSkuProveedorLoteErp()`.
- Evaluacion por renglon con los mismos criterios base de la aplicacion individual:
  - SKU ERP seleccionado y existente.
  - Estado `match_seleccionado`.
  - Unidad de compra, factor y cantidad minima completos.
  - Sin relacion aplicada previamente.
- Clasificacion de renglones:
  - incluidos para crear relacion;
  - incluidos para actualizar relacion existente del proveedor y SKU;
  - excluidos por falta de SKU, SKU invalido, estado no seleccionado, compra incompleta o relacion ya aplicada.
- Modal en detalle de lista `Preview relaciones` con:
  - resumen de conteos;
  - tabla de incluidos;
  - tabla de excluidos;
  - aviso claro de que no aplica cambios.

Alcance:

- Solo lectura.
- No crea ni actualiza `erp_catalogo_sku_proveedores`.
- No cambia estatus de renglon.
- No aplica costos ni toca `costo_referencia`.
- La aplicacion real en lote queda pendiente de autorizacion separada.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

## Evidencia P6-08 - preview de costos proveedor en lote

Fecha: 2026-06-14.

Objetivo:

- Revisar que renglones de una lista podrian aplicar costo vigente en lote antes de autorizar escrituras reales.

Implementado:

- Endpoint solo lectura `/proveedor/proveedor_costos_lote_preview_erp`.
- Metodo `Proveedores::previewCostosProveedorLoteErp()`.
- Evaluacion por renglon con criterios equivalentes a la aplicacion individual:
  - relacion proveedor-SKU aplicada;
  - relacion activa y correspondiente al SKU;
  - costo positivo;
  - moneda capturada;
  - unidad y factor completos;
  - bandera `costo_incluye_impuestos` definida.
- Clasificacion de renglones:
  - incluidos para crear costo vigente;
  - incluidos para actualizar costo vigente del mismo renglon;
  - excluidos por falta de relacion, relacion invalida/inactiva, costo incompleto, unidad/factor incompleto, impuestos sin definir o estado no aplicable.
- Modal en detalle de lista `Preview costos` con:
  - resumen de conteos;
  - tabla de incluidos;
  - tabla de excluidos;
  - aviso claro de que no aplica cambios ni toca `costo_referencia`.

Alcance:

- Solo lectura.
- No crea ni actualiza `erp_proveedores_sku_costos`.
- No historiza costos.
- No actualiza `erp_catalogo_sku_proveedores.costo_ultimo`.
- No toca `erp_catalogo_skus.costo_referencia`.
- La aplicacion real en lote queda pendiente de autorizacion separada.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

## Evidencia P5-13 - aplicacion real de relaciones proveedor-SKU en lote

Fecha: 2026-06-14.

Objetivo:

- Permitir aplicar en lote las relaciones proveedor-SKU que ya fueron revisadas por el preview, sin aplicar costos ni tocar `costo_referencia`.

Implementado:

- Endpoint POST `/proveedor/proveedor_sku_relaciones_lote_aplicar_erp`.
- Metodo `Proveedores::aplicarRelacionesSkuProveedorLoteErp()`.
- La aplicacion vuelve a evaluar cada renglon en servidor antes de escribir.
- Solo aplica renglones que siguen cumpliendo:
  - SKU ERP seleccionado y existente;
  - estado `match_seleccionado`;
  - unidad, factor y cantidad minima completos;
  - sin relacion ya aplicada en el renglon.
- La escritura se hace en transaccion:
  - crea o actualiza `erp_catalogo_sku_proveedores`;
  - marca el renglon como `relacion_aplicada`;
  - guarda criterio `relacion_sku_proveedor_lote_aplicada`.
- La UI agrega boton `Aplicar relaciones incluidas` dentro del modal `Preview relaciones`.
- El boton solo se habilita si el preview tiene renglones incluidos y muestra conteo.
- Antes de aplicar, muestra confirmacion final indicando que no se aplicaran costos ni `costo_referencia`.
- Al terminar, recarga el detalle de lista.

Alcance:

- Escritura operativa autorizada solo para relaciones proveedor-SKU.
- No aplica costos.
- No actualiza `erp_proveedores_sku_costos`.
- No actualiza `erp_catalogo_sku_proveedores.costo_ultimo`.
- No toca `erp_catalogo_skus.costo_referencia`.
- Si un renglon cambia entre preview y aplicacion, el servidor lo reevalua y solo aplica lo que siga siendo valido.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Probar con una lista real y revisar auditoria.
- No avanzar a aplicacion real de costos en lote hasta validar relaciones en lote con datos reales.
- Mantener `costo_referencia` pendiente de politica.

## Evidencia P6-09 - aplicacion real de costos proveedor en lote

Fecha: 2026-06-14.

Objetivo:

- Permitir aplicar en lote costos proveedor-SKU desde una lista ya revisada, sin tocar `costo_referencia`.

Implementado:

- Endpoint POST `/proveedor/proveedor_costos_lote_aplicar_erp`.
- Metodo `Proveedores::aplicarCostosProveedorLoteErp()`.
- La aplicacion vuelve a evaluar cada renglon en servidor antes de escribir.
- Solo aplica renglones que siguen cumpliendo:
  - relacion proveedor-SKU aplicada;
  - relacion activa y correspondiente al SKU;
  - costo positivo;
  - moneda capturada;
  - unidad y factor completos;
  - bandera `costo_incluye_impuestos` definida.
- La escritura se hace en transaccion:
  - historiza costos vigentes anteriores del proveedor/SKU;
  - crea o actualiza `erp_proveedores_sku_costos` como vigente;
  - actualiza `erp_catalogo_sku_proveedores.costo_ultimo`;
  - marca el renglon como `costo_aplicado`;
  - guarda criterio `costo_proveedor_lote_aplicado`.
- La UI agrega boton `Aplicar costos incluidos` dentro del modal `Preview costos`.
- El boton solo se habilita si el preview tiene renglones incluidos y muestra conteo.
- Antes de aplicar, muestra confirmacion final indicando que no se tocara `costo_referencia`.
- Al terminar, recarga detalle de lista e historial de costos.

Alcance:

- Escritura operativa autorizada para costos vigentes de proveedor.
- No actualiza `erp_catalogo_skus.costo_referencia`.
- No define politica de costo de venta, listas de precio, margen ni mayoreo/menudeo.
- Si un renglon cambia entre preview y aplicacion, el servidor lo reevalua y solo aplica lo que siga siendo valido.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Probar con una lista real y revisar historial/auditoria.
- Definir politica de `costo_referencia` antes de tocar Catalogo.
- Definir si una lista con relaciones y costos aplicados debe pasar automaticamente a `aplicada` o seguir requiriendo accion manual.

## Evidencia P6-10 - preview de costo_referencia

Fecha: 2026-06-14.

Objetivo:

- Revisar el impacto de actualizar `erp_catalogo_skus.costo_referencia` usando costos vigentes de proveedor, sin escribir en Catalogo.

Implementado:

- Endpoint solo lectura `/proveedor/proveedor_costo_referencia_preview_erp`.
- Metodo `Proveedores::previewCostoReferenciaListaProveedorErp()`.
- El preview toma el ultimo costo vigente por SKU dentro de la lista del proveedor.
- Compara:
  - `erp_catalogo_skus.costo_referencia` actual;
  - costo vigente del proveedor;
  - diferencia absoluta;
  - diferencia porcentual cuando existe costo actual.
- Muestra advertencias:
  - SKU sin `costo_referencia`;
  - cambio mayor o igual a 10%;
  - moneda distinta de MXN;
  - proveedor no marcado como preferido.
- Agrega boton `Preview costo ref` en el detalle de lista.
- Agrega modal con resumen y tabla de propuestas.

Alcance:

- Solo lectura.
- No actualiza `erp_catalogo_skus.costo_referencia`.
- No cambia costos de proveedor.
- No cambia precios de venta.
- No define todavia conversion de moneda ni regla de proveedor preferido.

Regla recomendada documentada:

- No aplicar automaticamente desde cualquier lista.
- Revisar proveedor preferido, moneda y variacion antes de autorizar.
- Mantener `erp_proveedores_sku_costos` como fuente historica de verdad; `costo_referencia` queda como referencia operativa de Catalogo.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Definir si se permite aplicar `costo_referencia` desde este preview.
- Definir si se exige proveedor preferido.
- Definir si moneda distinta de MXN se bloquea o requiere conversion.
- Definir si cambios mayores a cierto porcentaje requieren autorizacion adicional.

## Evidencia P6-11 - preview de costo_referencia con fuente real

Fecha: 2026-06-14.

Objetivo:

- Ajustar el preview de `costo_referencia` para que proponga el costo mas real posible sin escribir en Catalogo.

Implementado:

- `Proveedores::previewCostoReferenciaListaProveedorErp()` ahora busca si el SKU ya tiene una compra/recepcion real con:
  - `erp_compras_ordenes_detalle.id_sku_erp`;
  - `cantidad_recibida > 0`;
  - `costo_unitario > 0`;
  - orden no cancelada.
- Si existe compra/recepcion real, el preview propone ese costo como fuente sugerida `compra_real`.
- Si no existe compra/recepcion real, el preview conserva el costo vigente del proveedor como fuente sugerida `proveedor_vigente`.
- La modal muestra resumen separado:
  - propuestas con compra real;
  - propuestas solo con proveedor vigente.
- En cada renglon se muestra la fuente del costo:
  - `Compra real`: costo tomado de una orden ya recibida;
  - `Proveedor vigente`: costo tomado de la lista/costo vigente del proveedor porque no se encontro compra real.
- Cuando la fuente es proveedor vigente, se agrega advertencia para no confundirlo con costo historico comprado.

Alcance:

- Solo lectura.
- No actualiza `erp_catalogo_skus.costo_referencia`.
- No cambia costos de proveedor.
- No cambia precios de venta.
- No define todavia regla de aplicacion automatica.

Decision de negocio pendiente:

- Autorizar o no un flujo para aplicar `costo_referencia` desde este preview.
- Definir si el costo real comprado debe actualizarse automaticamente en recepcion o quedar como propuesta revisable.
- Comportamiento de Almacen revisado en P6-13: recepcion ya no debe actualizar `erp_catalogo_skus.costo_referencia` directamente.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Antes de crear boton de aplicacion real de `costo_referencia`, confirmar regla exacta:
  - aplicar solo seleccionados;
  - aplicar masivo con bloqueos por moneda/variacion;
  - o dejarlo como propuesta hasta que Catalogo tenga flujo formal de autorizacion.

## Evidencia P6-12 - aplicacion controlada de costo_referencia

Fecha: 2026-06-14.

Decision tomada para ERP robusto:

- `costo_referencia` no debe actualizarse desde cualquier lista de proveedor sin control.
- Se permite aplicar desde Proveedores solo cuando el servidor recalcula la propuesta y la marca como elegible.
- La fuente con mayor prioridad es compra/recepcion real.
- El costo vigente de proveedor solo puede llenar `costo_referencia` cuando el SKU no tenia costo previo.

Implementado:

- Endpoint POST `/proveedor/proveedor_costo_referencia_aplicar_erp`.
- Metodo `Proveedores::aplicarCostoReferenciaListaProveedorErp()`.
- El endpoint recalcula `previewCostoReferenciaListaProveedorErp()` antes de escribir.
- La aplicacion usa transaccion y bloquea cada SKU con `FOR UPDATE`.
- Actualiza `erp_catalogo_skus.costo_referencia` solo para propuestas elegibles.
- Registra auditoria explicita desde `Proveedor.php`.
- La modal `Preview costo ref` muestra:
  - propuestas elegibles;
  - propuestas bloqueadas;
  - fuente `Compra real` o `Proveedor vigente`;
  - boton `Aplicar elegibles`.

Reglas aplicadas:

- Elegible:
  - costo propuesto mayor a cero;
  - moneda MXN;
  - fuente `compra_real`, aunque ya exista costo;
  - fuente `proveedor_vigente` solo si el SKU no tenia `costo_referencia`.
- Bloqueado:
  - moneda distinta de MXN;
  - costo propuesto invalido;
  - proveedor vigente intentando reemplazar un `costo_referencia` existente.

Alcance:

- No cambia precios de venta.
- No cambia listas de precio de Catalogo.
- No calcula costo promedio.
- No define conversion de moneda.
- No cambia el comportamiento existente de Almacen, que debe revisarse aparte.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Implementado en P6-13: Almacen deja de actualizar `erp_catalogo_skus.costo_referencia` automaticamente al recibir mercancia.
- Definir politica de moneda/tipo de cambio para costos reales o listas en moneda distinta de MXN.

## Evidencia P6-07 cierre - costo promedio y moneda

Fecha: 2026-06-15.

Estado:

- P6-07 queda aplicada como politica operativa inicial.

Politica formal aplicada:

1. `costo_referencia` es referencia operativa de Catalogo, no historial contable.
2. La fuente prioritaria es compra real.
   - Si existe compra real recibida, el preview propone ese costo.
   - Si la orden real esta en moneda extranjera, se convierte a MXN con el `tipo_cambio` guardado en la orden.
   - El servidor solo aplica si el costo propuesto queda en MXN y es positivo.
3. Si no hay compra real, se puede usar costo vigente de proveedor solo como referencia inicial.
   - Solo llena `costo_referencia` si el SKU no tenia costo previo.
   - No reemplaza un costo existente sin revision manual futura.
4. Costos de proveedor/lista en moneda distinta de MXN quedan bloqueados para `costo_referencia`.
   - Se conservan en historial de proveedor.
   - No se convierten automaticamente hasta definir politica cambiaria para listas/proveedor.
5. `costo_promedio_historico` se mantiene como indicador calculado.
   - No se crea columna nueva.
   - No se persiste como dato formal.
   - Sirve para revision y pruebas reales antes de decidir si Finanzas/Inventario lo requieren como dato oficial.

Implementado/ajustado:

- `Proveedores::previewCostoReferenciaListaProveedorErp()` usa `costo_unitario_mxn` cuando la fuente es compra real.
- `Proveedores::ultimoCostoCompraRealSkuErp()` devuelve:
  - costo original de orden;
  - moneda original;
  - tipo de cambio;
  - `costo_unitario_mxn`.
- Nuevo helper `Proveedores::costoOrdenProveedorMxnErp()`.
- La aplicacion controlada de `costo_referencia` conserva bloqueos:
  - costo invalido;
  - costo no MXN;
  - proveedor vigente intentando reemplazar costo existente.

Alcance:

- No se ejecutaron migraciones.
- No se agregaron columnas.
- No se guardo `costo_promedio_historico` como campo formal.
- No se definio conversion automatica para listas/proveedor en USD/EUR.
- No se modificaron precios de venta.
- No se modificaron listas comerciales de Catalogo.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.

Pendiente futuro, no bloqueante para cerrar Proveedores:

- Definir con Finanzas/Inventario si se requiere persistir costo promedio historico.
- Definir politica cambiaria para listas/costos de proveedor en moneda extranjera:
  - tipo de cambio manual por lista;
  - tipo de cambio por fecha;
  - fuente oficial;
  - auditoria y autorizacion.

## Evidencia P6-13 - Almacen deja de actualizar costo_referencia

Fecha: 2026-06-14.

Decision tomada:

- Almacen no debe ser el modulo que decide costos de Catalogo.
- Almacen recibe mercancia, confirma cantidades, lotes, caducidades, ubicaciones, incidencias e inventario.
- El costo mas real debe consolidarse cuando la compra ya esta terminada/cerrada y no debe modificarse libremente.
- Para evitar autorizaciones excesivas, el cierre de compra puede calcular/proponer/aplicar costos bajo reglas claras, no pedir autorizacion por cada recepcion normal.

Implementado:

- Se ajusto `Almacenes.php`.
- La funcion antes llamada `sincronizar_detalle_orden_y_costo_sku()` ahora queda como `sincronizar_detalle_orden_desde_recepcion()`.
- Al guardar recepcion, Almacen sigue sincronizando `erp_compras_ordenes_detalle.cantidad_recibida`.
- Se elimino la actualizacion directa a `erp_catalogo_skus.costo_referencia` desde recepcion.

Alcance:

- No cambia recepcion fisica.
- No cambia inventario.
- No cambia movimientos, lotes, ubicaciones ni unidades.
- No cambia pagos, XML ni documentos.
- Evita que una recepcion parcial cambie costo de Catalogo antes de cerrar la compra.

Pendiente recomendado:

- Flujo de cierre de compra/costos iniciado en P6-14.
- Pendiente posterior:
  - definir si se necesita estatus formal `cerrada` en esquema;
  - guardar `costo_promedio` como indicador persistente si se autoriza;
  - ampliar reportes de auditoria de costos cerrados.

Archivos modificados:

- `app/modelos/Almacenes.php`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Almacenes.php`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Evaluar si el cierre de compra/costos necesita esquema formal adicional: estatus `cerrada`, tabla historica de cierres o campo persistente de costo promedio.

## Evidencia P6-14 - consolidacion automatica de costos al enviar compra

Fecha: 2026-06-14.

Objetivo:

- Consolidar el costo comprometido de compra al enviar la orden, sin crear un paso operativo adicional y sin usar Almacen como responsable de costos.

Implementado:

- Metodo `OrdenesCompraErp::cerrarCostos()`.
- Integracion automatica desde `/compra/orden_guardar_erp` cuando la orden pasa a `enviada`.
- Auditoria explicita `compras/orden_consolidar_costos`.
- La consolidacion recalcula en servidor desde el snapshot de la orden y no depende de valores mandados por la UI.

Reglas aplicadas:

- Se ejecuta automaticamente al enviar una orden.
- No se permite en ordenes `borrador` ni `cancelada`.
- Todas las partidas deben tener SKU ERP, cantidad comprometida y costo unitario valido.
- El costo usa el snapshot neto guardado en `erp_compras_ordenes_detalle.costo_unitario`; ese valor ya fue normalizado por la orden cuando el costo capturado incluia impuesto.
- Si la orden esta en moneda distinta de MXN, se usa el `tipo_cambio` guardado en la orden para convertir a MXN.
- Si una orden tiene varias partidas del mismo SKU, se calcula promedio ponderado de esa orden.
- Se actualiza `erp_catalogo_skus.costo_referencia` con el costo real calculado de la orden.
- Se calcula `costo_promedio_historico` como indicador en la respuesta, pero no se guarda en columna nueva.

Alcance:

- No crea migraciones.
- No cambia inventario.
- No cambia precios de venta.
- No crea estatus nuevo de orden.
- No persiste `costo_promedio` todavia.
- No modifica listas de proveedor.
- No agrega boton ni paso manual de cierre de costos.

Archivos modificados:

- `app/controladores/Compra.php`.
- `app/modelos/OrdenesCompraErp.php`.
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`.
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Compra.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\OrdenesCompraErp.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\compras\ordenes\formulario.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\compras\ordenes\formulario.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Definir si conviene agregar esquema/estatus formal de orden cerrada y/o tabla historica de cierres de costo.
- Definir si `costo_promedio_historico` debe guardarse como campo formal o mantenerse como reporte calculado.

## Evidencia P8-05 - evidencia de costo visible en Compras

Fecha: 2026-06-14.

Objetivo:

- Que Compras pueda ver de donde viene el costo usado al agregar productos del proveedor, sin depender de conocimiento tecnico ni revisar tablas.

Implementado:

- `Proveedores::skusComprablesParaComprasErp()` ahora entrega metadata de costo:
  - `fuente_costo`;
  - `origen_costo`;
  - `moneda_costo`;
  - `vigencia_desde`;
  - `vigencia_hasta`;
  - `id_costo_proveedor_sku`;
  - `id_lista_proveedor_erp`.
- La busqueda de productos en Ordenes muestra:
  - `Costo vigente` cuando viene de `erp_proveedores_sku_costos` vigente;
  - `Costo ultimo` cuando solo viene de `erp_catalogo_sku_proveedores.costo_ultimo`.
- La tabla de partidas de Ordenes conserva y muestra la misma evidencia mientras se prepara la orden.
- La busqueda y tabla de Solicitudes muestran la misma evidencia de costo.

Alcance:

- Solo UI/contrato de lectura.
- No cambia reglas de seleccion.
- No bloquea compras nuevas.
- No cambia precios, inventario ni costos historicos.
- No guarda metadata adicional en detalle de solicitud/orden; por ahora el snapshot formal sigue siendo costo, moneda/tipo de cambio de la orden y relacion SKU proveedor.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`.
- `app/vistas/paginas/apps/erp/compras/solicitudes/formulario.php`.
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.
- `public/assets/js/custom/apps/erp/compras/solicitudes/formulario.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\compras\ordenes\formulario.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\compras\solicitudes\formulario.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\compras\ordenes\formulario.js`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\compras\solicitudes\formulario.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Decidir si la metadata de evidencia de costo debe persistirse tambien dentro del snapshot de orden/solicitud o si basta con auditoria y costo consolidado al enviar.

## Evidencia P8-06 - snapshot de evidencia de costo en Ordenes

Fecha: 2026-06-14.

Decision:

- Persistir la evidencia de costo en la orden de compra, no en la solicitud.
- La solicitud conserva costo estimado y advertencias; la orden enviada conserva el documento comprometido y su evidencia de costo.

Implementado:

- Se agrego al plan de esquema `ComprasEsquema::planActualizarOrdenCompra()` la columna:
  - `erp_compras_ordenes_detalle.evidencia_costo_json TEXT NULL`.
- `OrdenesCompraErp` detecta si la columna existe antes de usarla.
- Al guardar detalle de orden, si existe la columna, guarda snapshot JSON con:
  - `id_costo_proveedor_sku`;
  - `id_lista_proveedor_erp`;
  - `fuente_costo`;
  - `origen_costo`;
  - `moneda_costo`;
  - `vigencia_desde`;
  - `vigencia_hasta`.
- La UI de Ordenes envia `evidencia_costo` dentro del payload de cada partida.
- Al consultar una orden, la UI puede volver a mostrar la evidencia guardada si existe `evidencia_costo_json`.

Alcance:

- Primero se preparo codigo sin depender de la columna.
- Despues de autorizacion, se ejecuto un cambio puntual de esquema solo para esta columna.
- No cambia Solicitudes.
- No cambia costos, precios, inventario ni `costo_referencia`.
- Si otra base aun no tiene la columna, el flujo sigue funcionando sin persistir esa metadata hasta ejecutar el cambio.

Ejecucion de esquema:

- Fecha de ejecucion: 2026-06-15.
- Respaldo previo externo:
  - `C:/Users/aleja/Documents/Respaldos_DB_panel/panel_artianilocal_20260615_063049_antes_evidencia_costo_json.sql`.
  - Tamano aproximado: 41.3 MB.
- DDL ejecutado de forma puntual, sin correr todo el plan amplio de Compras:
  - `ALTER TABLE erp_compras_ordenes_detalle ADD COLUMN evidencia_costo_json TEXT NULL;`.
- Verificacion posterior:
  - `erp_compras_ordenes_detalle.evidencia_costo_json`: existe.

Archivos modificados:

- `app/modelos/ComprasEsquema.php`.
- `app/modelos/OrdenesCompraErp.php`.
- `app/vistas/paginas/apps/erp/compras/ordenes/formulario.php`.
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\ComprasEsquema.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\OrdenesCompraErp.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\compras\ordenes\formulario.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\compras\ordenes\formulario.js`: sin errores de sintaxis.

Siguiente prueba recomendada:

- Crear o editar una orden en borrador con SKU relacionado desde Proveedores.
- Confirmar que en la partida se vea la evidencia de costo.
- Enviar la orden.
- Reabrir/ver la orden y confirmar que la evidencia se conserva como snapshot aunque despues cambie el costo vigente del proveedor.

Evidencia para pruebas reales:

- Se agrego checklist especifico en `docs/erp_proveedores_pruebas_reales.md`, seccion `13. Contrato con Compras`.
- La prueba cubre:
  - evidencia de costo visible en Solicitudes y Ordenes;
  - persistencia del snapshot al guardar y reabrir orden;
  - persistencia del snapshot al ver orden enviada;
  - revision de `costo_referencia` consolidado al enviar la orden.

## Evidencia P8-04 refuerzo - validacion servidor de advertencias en Ordenes

Fecha: 2026-06-15.

Objetivo:

- Evitar que las advertencias operativas dependan solo del JS del navegador.
- Mantener el criterio actual: no bloquear la orden sin decision operativa nueva.
- Registrar evidencia cuando una orden enviada trae puntos por revisar.

Implementado:

- `OrdenesCompraErp::guardar()` calcula advertencias operativas al enviar una orden.
- La validacion se hace despues de normalizar el detalle y antes de responder.
- El servidor revisa:
  - relacion proveedor-SKU activa;
  - costo vigente autorizado en `erp_proveedores_sku_costos`;
  - unidad de compra y factor en `erp_catalogo_sku_proveedores`;
  - datos fiscales minimos del SKU en el snapshot de la partida.
- `Compra::orden_guardar_erp()` cambia la respuesta a `warning` si hay advertencias, sin cancelar el envio.
- Se registra auditoria explicita `compras/orden_advertencias_operativas`.
- La UI de Ordenes muestra las advertencias devueltas por servidor en el mensaje final.

Alcance:

- No bloquea Solicitudes.
- No bloquea Ordenes.
- No crea permisos nuevos.
- No cambia estatus del proveedor.
- No cambia costos, precios, inventario ni `costo_referencia`.
- No sustituye la decision futura sobre que advertencias deben ser bloqueo real.

Archivos modificados:

- `app/controladores/Compra.php`.
- `app/modelos/OrdenesCompraErp.php`.
- `public/assets/js/custom/apps/erp/compras/ordenes/formulario.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Compra.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\OrdenesCompraErp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\compras\ordenes\formulario.js`: sin errores de sintaxis.

Siguiente prueba recomendada:

- Enviar una orden con SKU sin costo vigente autorizado y confirmar que:
  - la UI pide confirmacion antes de enviar;
  - el servidor devuelve advertencia;
  - la orden se envia;
  - la auditoria registra `orden_advertencias_operativas`.

## Evidencia P8-04 cierre - bloqueos minimos de envio de Ordenes

Fecha: 2026-06-15.

Objetivo:

- Aplicar una politica ERP conservadora: bloquear solo datos que pueden romper el compromiso operativo de compra.
- Mantener advertencias/confirmaciones para datos mejorables que no necesariamente impiden comprar.

Politica aplicada:

- Solicitudes siguen flexibles: muestran advertencias, no bloquean.
- Orden en borrador sigue flexible: permite preparar y corregir partidas.
- Orden al enviar bloquea:
  - moneda invalida;
  - moneda extranjera sin tipo de cambio positivo;
  - total de orden menor o igual a cero;
  - partidas con costo unitario menor o igual a cero.
- Orden al enviar conserva advertencias no bloqueantes para:
  - falta de costo vigente autorizado si hay costo capturado;
  - unidad/factor incompleto;
  - datos fiscales incompletos.
- La relacion proveedor-SKU activa ya se valida en servidor cuando la partida usa SKU ERP; si no pertenece al proveedor o no esta activa, no permite guardar/enviar como SKU ERP valido.

Archivo modificado:

- `app/modelos/OrdenesCompraErp.php`.

Verificacion ejecutada:

- `C:\xampp\php\php.exe -l app\modelos\OrdenesCompraErp.php`: sin errores de sintaxis.

Alcance:

- No cambia Solicitudes.
- No cambia recepcion de Almacen.
- No cambia pagos ni XML.
- No cambia permisos.
- No modifica datos existentes.
- No ejecuta migraciones.

Pendiente relacionado:

- Enviar una orden con unidad/factor incompleto en relacion proveedor-SKU y confirmar el mismo flujo.

## Evidencia P3-08/P8-03 - estatus final de proveedor y bloqueo minimo en Compras

Fecha: 2026-06-15.

Objetivo:

- Definir un estatus ERP simple para el proveedor sin volver invasivo el flujo.
- Permitir preparar proveedores incompletos sin bloquear solicitudes o borradores.
- Bloquear solo cuando el estatus representa riesgo operativo real.

Estatus operativos autorizados:

| Estatus | Uso | Efecto en Compras |
| --- | --- | --- |
| `prospecto` | Proveedor posible o recien capturado | No bloquea por si solo |
| `en_revision` | Datos en validacion o preparacion | No bloquea por si solo |
| `activo_compras` | Puede operar compras normalmente | No bloquea |
| `suspendido` | Pausa temporal por decision operativa | Bloquea envio de orden |
| `bloqueado` | No debe operar por decision administrativa | Bloquea envio de orden |
| `inactivo` | Ya no se usa operativamente | Bloquea envio de orden |

Implementado:

- Metodo `Proveedores::cambiarEstatusProveedorErp()`.
- Endpoint POST `/proveedor/proveedor_estatus_erp`.
- Permiso usado: `proveedores.autorizar`.
- Auditoria explicita: `proveedor_estatus_cambiar`.
- UI en ficha 360: boton `Cambiar estatus`.
- Motivo obligatorio para:
  - `suspendido`;
  - `bloqueado`;
  - `inactivo`.
- Colores de estatus en listado y ficha:
  - `activo_compras`: verde;
  - `en_revision`: azul;
  - `prospecto`: gris;
  - `suspendido`: amarillo;
  - `bloqueado`/`inactivo`: rojo.
- `OrdenesCompraErp::guardar()` valida al enviar:
  - si proveedor esta `suspendido`, `bloqueado` o `inactivo`, no permite enviar la orden.

Alcance:

- No bloquea solicitudes.
- No bloquea busqueda de proveedores.
- No bloquea guardar orden en borrador.
- No exige datos fiscales para `activo_compras`.
- No define `activo_pagos`; queda para Finanzas/Pagos.
- No cambia datos existentes masivamente.
- No ejecuta migraciones.

Razon operativa:

- Un proveedor incompleto puede estar en preparacion y aun asi servir para cotizar o armar borradores.
- Una orden enviada ya compromete compra; ahi si se respeta `suspendido`, `bloqueado` e `inactivo`.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/controladores/Proveedor.php`.
- `app/modelos/OrdenesCompraErp.php`.
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.
- `docs/erp_proveedores_pruebas_reales.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\OrdenesCompraErp.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Pendiente:

- Probar con proveedor real:
  - cambiar a `en_revision`;
  - crear borrador de orden;
  - cambiar a `suspendido`;
  - confirmar que permite guardar borrador pero bloquea envio.
- Definir despues `activo_pagos` y reglas financieras con Finanzas/Pagos.

## Evidencia P0-06 - exportacion tecnica de auditoria Proveedores

Fecha: 2026-06-15.

Objetivo:

- Permitir revisar fuera del sistema la auditoria dry-run de Proveedores sin ejecutar migraciones ni escrituras.
- Facilitar revision en Excel/Drive y conservar evidencia tecnica para decisiones futuras.

Implementado:

- Endpoint GET `/proveedor/auditoria_exportar_erp`.
- Formatos soportados:
  - `?formato=json`: exporta la respuesta completa de `auditoriaDryRunErp()`.
  - `?formato=csv`: exporta hallazgos principales con clave, severidad, estado, conteo, descripcion y motivo.
- Se agregaron botones `CSV` y `JSON` en `/proveedor/auditoria_erp`.

Alcance:

- Solo lectura.
- No modifica tablas.
- No crea incidencias.
- No ejecuta migraciones.
- Usa permiso existente `proveedores.auditoria`.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/vistas/paginas/apps/erp/proveedores/auditoria.php`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\auditoria.php`: sin errores de sintaxis.

## Evidencia P0-05 - muestras limitadas en auditoria dry-run

Fecha: 2026-06-15.

Objetivo:

- Mostrar ejemplos concretos de hallazgos de auditoria sin abrir consultas grandes ni modificar datos.
- Facilitar decisiones de limpieza, migracion o correccion operativa con muestras pequenas.

Implementado:

- `Proveedores::auditoriaDryRunErp()` ahora incluye `muestras_hallazgos`.
- Cada grupo de muestra usa `LIMIT 10`.
- Se agregaron muestras para:
  - proveedores sin nombre;
  - listas sin proveedor;
  - renglones legacy sin costo;
  - renglones legacy sin SKU ni nombre;
  - renglones legacy sin match exacto con SKU ERP;
  - relaciones SKU-proveedor sin costo;
  - relaciones sin unidad;
  - relaciones sin factor;
  - relaciones con SKU invalido;
  - relaciones con proveedor invalido;
  - SKUs comprables sin codigo principal;
  - SKUs comprables con fiscal incompleto.
- La pantalla `/proveedor/auditoria_erp` fusiona las muestras legacy existentes con las muestras por hallazgo.
- La tarjeta ahora se llama `Muestras de hallazgos`.

Alcance:

- Solo lectura.
- No crea incidencias.
- No corrige registros.
- No migra datos.
- No aplica costos ni relaciones.
- No ejecuta cambios de esquema.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/auditoria.php`.
- `public/assets/js/custom/apps/erp/proveedores/auditoria.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\auditoria.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\auditoria.js`: sin errores de sintaxis.
- Prueba de lectura desde `public`: `auditoriaDryRunErp()` responde `error=0` e incluye `muestras_hallazgos=1`.

## Evidencia P10-03 parcial - auditoria de permisos Proveedores por rol

Fecha: 2026-06-15.

Objetivo:

- Revisar si los permisos `proveedores.*` asignados en BD coinciden con la matriz base de Seguridad.
- Facilitar la delegacion por departamento sin modificar roles desde esta tarea.

Implementado:

- `Proveedores::auditoriaDryRunErp()` ahora incluye `permisos_roles`.
- El comparativo toma como base:
  - `SeguridadEsquema::permisosBaseERP()`;
  - `SeguridadEsquema::permisosPorRolBaseERP()`.
- La auditoria consulta en BD:
  - permisos `proveedores.*` existentes en `sys_permisos`;
  - permisos asignados por rol en `sys_roles_permisos`;
  - roles activos/inactivos en `sys_roles`.
- La pantalla `/proveedor/auditoria_erp` agrega tarjeta `Permisos Proveedores por rol`.
- La tarjeta muestra:
  - permisos base;
  - permisos existentes en BD;
  - permisos faltantes en BD;
  - roles con faltantes;
  - roles con permisos extra contra la base;
  - permisos sensibles asignados (`proveedores.documentos_sensibles`, `proveedores.autorizar`, `proveedores.costos`).

Alcance:

- Solo lectura.
- No crea permisos.
- No cambia roles.
- No asigna ni revoca permisos.
- No cambia visibilidad del sidebar.
- No cambia reglas de negocio ni bloqueos.

Uso recomendado:

1. Abrir `/proveedor/auditoria_erp`.
2. Revisar tarjeta `Permisos Proveedores por rol`.
3. Si aparece faltante o extra, decidir aparte si se corrige desde Seguridad.
4. No corregir permisos automaticamente desde Proveedores.

Archivos modificados:

- `app/modelos/Proveedores.php`.
- `app/vistas/paginas/apps/erp/proveedores/auditoria.php`.
- `public/assets/js/custom/apps/erp/proveedores/auditoria.js`.
- `docs/erp_proveedores_avance.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\auditoria.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\auditoria.js`: sin errores de sintaxis.
- Prueba de lectura desde `public`: `auditoriaDryRunErp()` responde `error=0` e incluye `permisos_roles=1`.

Siguiente alto de autorizacion:

- Corregir roles/permisos reales.
- Cambiar matriz base por departamento.
- Definir doble autorizacion para costos, listas o incidencias en lote.

## Evidencia P7-05 - resolver o descartar incidencias con motivo

Fecha: 2026-06-15.

Objetivo:

- Permitir cerrar incidencias reales creadas desde Proveedores sin borrar evidencia.
- Evitar que la pantalla de `Pendientes y resolucion` se quede solo en propuestas de dry-run.
- Mantener control: cierre individual, motivo obligatorio y auditoria explicita.

Implementado:

- Endpoint GET `/proveedor/proveedor_incidencias_listar_erp`.
- Endpoint POST `/proveedor/proveedor_incidencia_resolver_erp`.
- Metodo `Proveedores::listarIncidenciasProveedorErp()`.
- Metodo `Proveedores::resolverIncidenciaProveedorErp()`.
- La pantalla `Pendientes y resolucion` ahora consulta:
  - propuestas dry-run sin escritura;
  - incidencias reales ya creadas en `erp_catalogo_incidencias_calidad` con origen `proveedores`.
- Las incidencias reales abiertas muestran acciones:
  - `Resolver`;
  - `Descartar`.
- Ambas acciones exigen motivo en modal.
- El servidor valida:
  - incidencia existente;
  - `origen = proveedores`;
  - proveedor/lista correspondiente;
  - estatus final permitido: `resuelta` o `descartada`;
  - motivo no vacio;
  - no reabrir ni volver a cerrar incidencias ya finales.
- Se guarda `resolucion_json`, `resuelto_por`, `fecha_resolucion` y `fecha_actualizacion`.
- Se registra auditoria explicita `proveedor_incidencia_resolver`.

Alcance:

- Escritura individual y controlada sobre `erp_catalogo_incidencias_calidad`.
- No crea incidencias en lote.
- No crea productos/SKU.
- No aplica matching.
- No aplica relaciones proveedor-SKU.
- No aplica costos.
- No modifica `costo_referencia`.
- No cambia reglas de bloqueo de Compras.

Uso recomendado en pruebas reales:

1. Abrir proveedor.
2. Abrir una lista.
3. Entrar a `Resolver pendientes`.
4. Crear una incidencia individual si aplica.
5. Volver a abrir `Resolver pendientes`.
6. Confirmar que aparece como `Incidencia real`.
7. Resolver o descartar con motivo.
8. Confirmar que deja de contar como abierta y conserva evidencia.
9. Revisar auditoria `proveedor_incidencia_resolver`.

Archivos modificados:

- `app/controladores/Proveedor.php`.
- `app/modelos/Proveedores.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_avance.md`.
- `docs/erp_proveedores_pruebas_reales.md`.

Verificacion tecnica:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`: sin errores de sintaxis.
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`: sin errores de sintaxis.
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`: sin errores de sintaxis.

Siguiente alto de autorizacion:

- Incidencias en lote.
- Definir si alguna incidencia debe bloquear Compras.
- Definir si cerrar una incidencia debe cambiar estatus de lista o proveedor automaticamente.

## Evidencia P9-07 inicial - pendientes post-migracion revisados

Fecha: 2026-06-15.

Objetivo:

- Revisar los registros que quedaron fuera de la conversion oficial desde `db/productivo` sin corregir datos ni forzar importacion.

Consulta solo lectura ejecutada:

- Tabla revisada: `erp_proveedores_migracion_staging`.
- Filtro: `accion_propuesta LIKE 'revisar%'`.
- Resultado: 2 renglones pendientes.

Pendientes detectados:

| Staging | Lote | Referencia | Motivo | Destino propuesto |
| --- | --- | --- | --- | --- |
| 5026 | `productivo_sql_20260614_050242` | `OCL-28A` | Costo no positivo | `erp_proveedores_listas_detalle_erp` |
| 5027 | `productivo_sql_20260614_050242` | `OCL-28W` | Costo no positivo | `erp_proveedores_listas_detalle_erp` |

Detalle operativo:

- `OCL-28A`: `FOCO T5 DE REPUESTO 28W ACTINIA`, costo `0`.
- `OCL-28W`: `FOCO T5 DE REPUESTO 10000K`, costo `0`.
- Ambos vienen de `db/productivo/erp_proveedores_listas_productos.sql`.
- En esta revision inicial ambos quedaron con `estado_revision=pendiente`.
- Ambos conservan `payload_json` y `hash_origen` en staging.

Alcance:

- No se modifico staging.
- No se crearon renglones ERP oficiales.
- No se aplicaron costos.
- No se aplicaron relaciones.

Resolucion posterior:

- El 2026-06-15 se autorizo cerrarlos como `historico_no_utilizable`.
- No se capturo costo real porque no deben usarse como referencia operativa.
- Ver evidencia `P9-07 cierre - pendientes legacy sin costo`.

## Evidencia P9-01 - plan de migracion sin ejecutar

Fecha: 2026-06-15.

Objetivo:

- Dejar un plan claro para reutilizar datos de `db/productivo` sin comenzar de cero y sin contaminar el ERP nuevo.
- Separar datos que pueden migrarse, datos que deben quedar como evidencia historica y datos que requieren decision antes de entrar al flujo operativo.

Fuentes legacy consideradas:

- `db/productivo/erp_proveedores.sql`.
- `db/productivo/erp_proveedores_listas.sql`.
- `db/productivo/erp_proveedores_listas_productos.sql`.

Regla principal:

- La informacion util del proveedor se reutiliza.
- La estructura legacy no manda sobre el ERP nuevo.
- Ningun dato legacy debe activar costos, relaciones SKU-proveedor, `costo_referencia`, Compras o Catalogo sin pasar por validacion.

Datos que pueden pasar al ERP nuevo:

- Proveedores:
  - nombre legacy como `proveedor`/nombre comercial inicial;
  - codigo o referencia legacy como evidencia;
  - cuota u otros campos simples como observacion si no existe campo ERP equivalente claro.
- Listas:
  - encabezado de lista como lista ERP en `borrador`;
  - archivo/origen como evidencia;
  - version/lote de migracion como trazabilidad;
  - proveedor relacionado solo si existe match confiable.
- Renglones:
  - SKU proveedor/codigo/nombre/descripcion;
  - costo como dato de lista, no como costo vigente;
  - moneda solo si esta explicita o fue definida con autorizacion;
  - unidad/factor solo si esta claro; si no, queda pendiente.

Datos que deben quedar historicos/evidencia:

- Listas antiguas sin proveedor confiable.
- Renglones sin costo positivo.
- Renglones sin SKU, codigo o nombre suficiente.
- Relaciones inferidas solo por texto o coincidencias debiles.
- Costos sin moneda, unidad/factor o evidencia clara.
- Campos legacy que no tienen significado ERP confirmado.

Datos que no deben migrarse automaticamente:

- Costos vigentes.
- `costo_referencia`.
- Relaciones `erp_catalogo_sku_proveedores`.
- SKUs ERP nuevos.
- Datos fiscales del producto.
- Codigos principales de Catalogo.
- Estados finales de proveedor/lista.
- Bloqueos de Compras.

Flujo recomendado para migracion futura:

1. Generar respaldo externo de base de datos antes de cualquier escritura.
2. Ejecutar dry-run sobre archivos `db/productivo`.
3. Cargar o actualizar staging con lote identificable.
4. Revisar resumen y muestras.
5. Convertir solo registros elegibles a tablas ERP oficiales:
   - proveedores;
   - listas en borrador;
   - renglones como evidencia.
6. Revisar pendientes post-migracion.
7. Resolver renglones:
   - editar datos;
   - hacer matching;
   - enviar incidencia a Catalogo;
   - descartar con motivo si no sirve.
8. Aplicar relaciones/costos solo despues de preview y confirmacion.

Autorizaciones obligatorias antes de ejecutar:

- Crear respaldo externo.
- Ejecutar carga staging masiva.
- Convertir staging a tablas ERP oficiales.
- Corregir datos en lote.
- Descartar registros heredados en lote.
- Aplicar relaciones/costos en lote fuera del flujo ya controlado con preview y confirmacion.
- Aplicar relaciones/costos automaticamente como parte de una migracion.

Estado actual:

- Ya existe migracion piloto desde `db/productivo` a staging.
- Ya existe conversion a proveedores/listas/renglones ERP oficiales como borrador/evidencia.
- Los 2 renglones staging con costo no positivo documentados en P9-07 quedaron cerrados como historico no utilizable.
- La migracion completa por lotes futuros sigue pendiente de autorizacion expresa.
- Se agrego preflight P9-08 solo lectura en auditoria para revisar candidatos, existentes, pendientes y pasos antes de autorizar escrituras.

Alcance de esta tarea:

- Solo documentacion.
- No se ejecutaron comandos de base de datos.
- No se modifico staging.
- No se importaron archivos.
- No se tocaron costos, relaciones ni Catalogo.

## Evidencia P9-08 preflight - migracion completa por lotes

Fecha: 2026-06-15.

Objetivo:

- Preparar la siguiente decision de migracion completa sin ejecutar migraciones.
- Mostrar si los SQL de `db/productivo` tienen candidatos nuevos, registros ya migrados/existentes y registros que requieren revision.
- Separar claramente pasos listos de pasos que requieren autorizacion.

Implementado:

- `Proveedores::preflightMigracionCompletaProveedores()` calcula un resumen solo lectura.
- La pantalla `/proveedor/auditoria_erp` muestra:
  - estado del preflight;
  - candidatos nuevos;
  - existentes/ya migrados;
  - registros que requieren revision;
  - riesgos detectados;
  - pasos previos para migrar.
- El preflight marca como autorizacion requerida:
  - respaldo externo;
  - carga staging por lote;
  - conversion staging a ERP oficial;
  - correccion o descarte en lote.

Alcance:

- Solo lectura.
- No ejecuta SQL productivo.
- No carga staging.
- No convierte registros.
- No aplica costos, relaciones, `costo_referencia`, Catalogo ni Compras.

Siguiente decision:

- Con el respaldo y staging ya ejecutados, no convertir este lote porque no contiene candidatos nuevos.

## Evidencia P9-08 - respaldo externo y lote staging autorizado

Fecha: 2026-06-15.

Autorizacion:

- El dueno autorizo generar respaldo externo y cargar un nuevo lote staging controlado.

Respaldo generado:

- Carpeta externa: `C:\Users\aleja\Documents\Respaldos_panel_bd`.
- Archivo: `panel_bd_20260615_002210.sql`.
- Tamano verificado: `18595999` bytes.

Lote staging cargado:

- Lote: `productivo_sql_20260615_082236`.
- Fuente: `db/productivo/erp_proveedores*.sql`.
- Metodo: `Proveedores::cargarStagingProductivoSqlProveedores(0)`.

Resultado:

| Tipo | Accion propuesta | Total |
| --- | --- | ---: |
| proveedor | conservar_actualizar | 23 |
| lista | existente | 10 |
| renglon | existente | 9278 |
| renglon | revisar | 2 |

Renglones a revisar:

| Tipo | ID origen | Referencia | Motivo |
| --- | ---: | --- | --- |
| renglon | 4993 | `OCL-28A` | Costo no positivo |
| renglon | 4994 | `OCL-28W` | Costo no positivo |

Interpretacion:

- El lote confirma que la informacion productiva relevante ya esta migrada como proveedores/listas/renglones ERP de evidencia.
- No hay candidatos nuevos para convertir a tablas oficiales en este lote.
- Los 2 pendientes son los mismos renglones legacy con costo no positivo ya detectados antes.

No se ejecuto:

- No se convirtio staging a tablas ERP oficiales.
- No se aplicaron costos vigentes.
- No se crearon relaciones proveedor-SKU.
- No se actualizo `costo_referencia`.
- No se modifico Catalogo ni Compras.

Siguiente decision:

- Resolver los 2 renglones pendientes como evidencia descartada/corregida con motivo, o conservarlos como historico no utilizable.
- No conviene ejecutar conversion oficial de este lote porque no contiene candidatos nuevos.

## Evidencia P9-07 cierre - pendientes legacy sin costo

Fecha: 2026-06-15.

Decision autorizada:

- `OCL-28A` y `OCL-28W` quedan como historico no utilizable para operacion.
- Motivo: costo no positivo en origen legacy.
- No deben alimentar costos, Compras, matching automatico ni `costo_referencia`.

Registros cerrados:

| Staging | Lote | ID origen | Referencia | Estado final |
| ---: | --- | ---: | --- | --- |
| 5026 | `productivo_sql_20260614_050242` | 4993 | `OCL-28A` | `historico_no_utilizable` |
| 5027 | `productivo_sql_20260614_050242` | 4994 | `OCL-28W` | `historico_no_utilizable` |
| 14339 | `productivo_sql_20260615_082236` | 4993 | `OCL-28A` | `historico_no_utilizable` |
| 14340 | `productivo_sql_20260615_082236` | 4994 | `OCL-28W` | `historico_no_utilizable` |

Verificacion:

- Pendientes staging abiertos con `accion_propuesta LIKE 'revisar%'` y `estado_revision='pendiente'`: `0`.
- No existen renglones oficiales en `erp_proveedores_listas_detalle_erp` para `id_producto_legacy` 4993/4994.

Alcance aplicado:

- Solo se actualizo `erp_proveedores_migracion_staging`.
- No se borraron registros.
- No se crearon renglones ERP oficiales.
- No se aplicaron costos.
- No se crearon relaciones proveedor-SKU.
- No se modifico Catalogo, Compras ni `costo_referencia`.

Mejora adicional:

- La auditoria de staging ahora separa estado de revision y ya no muestra historicos no utilizables como pendientes abiertos.

## Evidencia P10-04 - cierre operativo y pruebas reales Proveedores

Fecha: 2026-06-15.

Objetivo:

- Dejar claro el estado operativo del modulo para seguir pruebas reales sin perder contexto.
- Separar lo listo para probar de lo que todavia requiere decision/autorizacion.

Listo para pruebas reales:

- Maestro de proveedores ERP:
  - listado;
  - ficha 360;
  - datos generales;
  - fiscales;
  - contactos;
  - condiciones;
  - documentos metadata.
- Listas de proveedor:
  - encabezado versionado;
  - archivo original como evidencia;
  - vista previa/mapeo;
  - importacion en borrador;
  - limpieza de renglones;
  - estados controlados;
  - filtros operativos.
- Matching:
  - dry-run;
  - candidatos multiples;
  - seleccion manual;
  - decision por renglon;
  - aplicacion individual de relacion;
  - aplicacion en lote con preview/confirmacion.
- Costos proveedor:
  - costo individual vigente;
  - historial/historizacion;
  - costos en lote con preview/confirmacion;
  - sin tocar `costo_referencia` salvo flujo controlado.
- Incidencias:
  - dry-run de pendientes;
  - creacion individual hacia Catalogo;
  - deduplicacion por huella.
- Catalogo puente:
  - incidencia origen `proveedores`;
  - SKU temporal en Catalogo cuando se decide evaluar un producto nuevo;
  - regreso a Proveedores para matching.
- Compras:
  - busqueda desde contrato de Proveedores;
  - evidencia de costo visible;
  - snapshot de evidencia en orden;
  - advertencias UI;
  - advertencias servidor/auditoria sin bloqueo;
  - consolidacion de `costo_referencia` al enviar orden.
- Almacen:
  - ya no actualiza `costo_referencia` directamente al recibir.

Pendiente de prueba real prioritaria:

1. Probar proveedor real con lista importada.
2. Aplicar relacion individual y en lote con casos confiables.
3. Aplicar costo individual y en lote con moneda/costo/factor claros.
4. Crear solicitud y orden desde proveedor con costo vigente.
5. Enviar orden y validar:
   - advertencias;
   - snapshot de evidencia;
   - auditoria;
   - consolidacion de `costo_referencia`.
6. Probar un producto sin match:
   - crear incidencia individual;
   - crear SKU temporal en Catalogo;
   - volver a Proveedores y relacionar.

Actualizacion de pruebas reales:

- `docs/erp_proveedores_pruebas_reales.md` queda alineado con el estado actual del modulo.
- La guia ya distingue:
  - preparacion del proveedor en ficha 360;
  - preparacion de lista con renglones operativos/informativos;
  - aplicacion individual de relacion;
  - aplicacion en lote de relaciones con preview/confirmacion;
  - aplicacion individual de costo;
  - aplicacion en lote de costos con preview/confirmacion;
  - preview/aplicacion elegible de `costo_referencia`;
  - pruebas de evidencia de costo en Compras;
  - pruebas de incidencias reales con cierre por motivo.
- Las pruebas siguen marcando como pendientes las decisiones que no deben inventarse: politica cambiaria futura para costos de proveedor/lista no MXN, incidencias en lote y decision financiera si algun dia se persiste costo promedio.
- El reporte de preparacion por proveedor/lista ya existe como lectura; queda pendiente probarlo con datos reales y ajustar textos si algun mensaje confunde al operador.
- Se agrego incidencia real de prueba para SUNNY con archivo `.xls` legacy: se conserva como evidencia, pero requiere conversion a `.xlsx`/`CSV` o decision posterior de integrar lector `.xls` moderno.

## Evidencia P4-15 - aviso claro para archivos no importables

Fecha: 2026-06-15.

Objetivo:

- Evitar confusion cuando una lista queda guardada como evidencia, pero no puede mapearse/importarse automaticamente.
- Cubrir el caso real de SUNNY con archivo `.xls` legacy.

Implementado:

- El modal `Vista previa de lista` ahora muestra una alerta visible cuando el archivo no es parseable.
- Para `.xls` legacy, la alerta indica:
  - el archivo quedo guardado como evidencia;
  - debe convertirse a `.xlsx` o `CSV`;
  - despues debe volver a subirse/asociarse para mapear columnas e importar renglones.
- El boton `Importar renglones` queda deshabilitado cuando `preview.parseable=false`.

Alcance:

- Solo UI y documentacion.
- No integra lector `.xls`.
- No importa renglones.
- No modifica datos, relaciones, costos ni Catalogo.

Archivos modificados:

- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.
- `docs/erp_proveedores_pruebas_reales.md`.
- `docs/erp_proveedores_avance.md`.

Prueba real agregada:

- En `docs/erp_proveedores_pruebas_reales.md`, checklist `XLS legacy`, validar que el aviso explique conversion y que el boton de importar quede deshabilitado.

## Mejora P4-06 - carga de archivo y estatus en una sola transaccion

Fecha: 2026-06-15.

Objetivo:

- Evitar que una lista quede con documento/archivo ligado pero sin cambio de estatus cuando se guarda archivo original como evidencia.

Implementado:

- `Proveedores::subirArchivoListaProveedorErp()` ahora actualiza `id_documento_proveedor` y el estatus `cargada` dentro de la misma transaccion.
- Si falla el guardado o la actualizacion, se revierte la transaccion y se elimina el archivo fisico temporal guardado por la operacion.

Alcance:

- No cambia reglas de negocio.
- No importa renglones.
- No aplica relaciones, costos ni `costo_referencia`.
- Solo hace mas consistente la escritura controlada de archivo original de lista.

Archivo modificado:

- `app/modelos/Proveedores.php`.

## Mejora P6-04 - filtro local de historial de costos

Fecha: 2026-06-15.

Objetivo:

- Facilitar la revision de costos proveedor-SKU dentro de la ficha del proveedor durante pruebas reales.
- Evitar que el usuario tenga que revisar manualmente un historial largo para encontrar un SKU, lista, moneda, origen o estatus.

Implementado:

- En la pestaña `Listas y costos`, el bloque `Costos` agrega filtro local de historial.
- El filtro busca sobre los registros ya cargados por `/proveedor/proveedor_costos_erp`.
- Busca por SKU ERP, nombre de SKU, SKU proveedor, codigos, lista, version, origen, moneda, estatus, vigencia y costo.

Alcance:

- Solo UI/lectura.
- No ejecuta escrituras.
- No cambia costos vigentes.
- No aplica relaciones, listas ni `costo_referencia`.

Archivos modificados:

- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`.
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`.

## Actualizacion P5-02 - bandeja de matching cerrada para pruebas

Fecha: 2026-06-15.

Estado:

- P5-02 queda aplicada como bandeja de revision.

Capacidades disponibles:

- Modal `Matching` desde detalle de lista.
- Resumen por estado: relacionado, exacto pendiente, posible, ambiguo y sin match.
- Filtro por texto y estado.
- Visualizacion de todos los candidatos devueltos por renglon.
- Seleccion de candidato como paso separado de aplicar relacion oficial.
- Motivos estandar para marcar `ambiguo`, `sin_match` o `rechazado`.

Alcance:

- La bandeja permite revisar y decidir matching.
- Aplicar relacion oficial sigue siendo una accion separada.
- No crea productos/SKU.
- No aplica costos ni `costo_referencia`.

## Evidencia P10-05 - separar Proveedores legacy y marcar pantallas historicas

Fecha: 2026-06-15.

Objetivo:

- Evitar que el flujo viejo de Proveedores se confunda con el ERP nuevo.
- Mantener acceso al legacy mientras se termina la validacion real y no se decide ocultarlo/deshabilitarlo.

Implementado inicialmente:

- En `app/vistas/includes/header/sidebar.php`, el enlace antiguo dentro de `Compras` cambia de `Proveedores` a `Proveedores legacy`.
- El grupo nuevo `Proveedores` conserva:
  - `Maestro proveedores`;
  - `Auditoria proveedores`.

Implementado en cierre P10-05:

- Se agrego un aviso comun en las pantallas legacy principales de Proveedores.
- El aviso explica que la vista se conserva como consulta historica/transicion.
- El aviso dirige el flujo operativo nuevo hacia `ERP > Proveedores > Maestro proveedores`.
- El aviso aclara que estas pantallas no deben usarse para definir reglas nuevas de Catalogo, Compras, costos o matching.

Pantallas marcadas:

- `app/vistas/paginas/apps/erp/proveedores/agregar_proveedor.php`.
- `app/vistas/paginas/apps/erp/proveedores/listas_mostrar.php`.
- `app/vistas/paginas/apps/erp/proveedores/lista_productos.php`.
- `app/vistas/paginas/apps/erp/proveedores/lista_producto_editar.php`.
- `app/vistas/paginas/apps/erp/proveedores/listas_cargar.php`.
- `app/vistas/paginas/apps/erp/proveedores/listas_mayoreo_mostrar.php`.
- `app/vistas/paginas/apps/erp/proveedores/listas_mayoreo_cargar.php`.
- `app/vistas/paginas/apps/erp/proveedores/pedidos/mostrar.php`.
- `app/vistas/paginas/apps/erp/proveedores/pedidos/crear.php`.
- `app/vistas/paginas/apps/erp/proveedores/pedidos/editar.php`.
- `app/vistas/paginas/apps/erp/proveedores/pedidos/pedido_productos.php`.

Archivo comun creado:

- `app/vistas/paginas/apps/erp/proveedores/_aviso_legacy.php`.

Alcance:

- No elimina rutas.
- No cambia permisos.
- No bloquea pantallas legacy.
- No migra datos.
- No modifica reglas de negocio.

Pendiente de decision:

- Definir si mas adelante el legacy se oculta, se deja solo lectura o se mueve a una seccion historica despues de pruebas reales.

Pendiente con autorizacion/decision:

- Estados finales de proveedor y si bloquean Compras.
- Convertir advertencias en bloqueos reales o autorizaciones especiales.
- Resolver/descartar pendientes con motivo como flujo formal.
- Incidencias en lote.
- Migracion completa por lotes futuros.
- Evaluar en futuro si se requiere esquema formal para:
  - costo promedio historico persistente;
  - estatus de orden cerrada.
- Politica cambiaria futura para listas/costos de proveedor no MXN.
- Marcar pantallas legacy como historicas/no operativas.

No hacer sin nueva autorizacion:

- Migrar datos masivamente.
- Borrar/archivar registros legacy.
- Cambiar estados de proveedor para bloquear operacion.
- Cambiar reglas de Compra/Recepcion/Pago a bloqueo duro.
- Ejecutar esquemas amplios.
- Modificar precios de venta o listas comerciales de Catalogo.

## Alto de autorizacion actual - siguientes decisiones Proveedores

Fecha: 2026-06-15.

Estado:

- Las tareas seguras pendientes de UI, lectura, evidencia y documentacion quedaron avanzadas.
- Lo que sigue puede cambiar reglas operativas, permisos, datos maestros, costos o migraciones.
- No conviene seguir implementando sin elegir el siguiente bloque.

Opciones recomendadas en orden:

1. Definir advertencias vs bloqueos para Compras. Aplicado como bloqueos minimos el 2026-06-15.
   - Impacta P7-03, P8-03 y P8-04.
   - Criterio aplicado: mantener advertencias por defecto y bloquear solo problemas operativos fuertes.
   - Bloqueos aplicados al enviar orden: relacion proveedor-SKU invalida, moneda invalida, moneda extranjera sin tipo de cambio, total cero y costo unitario cero.

2. Definir estatus final del proveedor. Aplicado el 2026-06-15.
   - Impacta P3-08 y P8-03.
   - Criterio aplicado: separar estatus informativo del proveedor de permisos reales de compra/pago.
   - `suspendido`, `bloqueado` e `inactivo` bloquean envio de orden; `prospecto`, `en_revision` y `activo_compras` no bloquean por si solos.

3. Definir SKU preliminar desde producto proveedor hacia Catalogo. Aplicado el 2026-06-14 y cerrado en P5-08.
   - Impacta P5-08.
   - Criterio aplicado: no crear SKU ERP automaticamente desde Proveedores; enviar incidencia a Catalogo y que Catalogo cree el SKU temporal cuando se decida evaluar/comprar.

4. Definir politica formal de costo promedio y moneda. Aplicado el 2026-06-15.
   - Impacta P6-07.
   - Criterio aplicado: `costo_referencia` como referencia operativa, compra real prioritaria, proveedor vigente solo referencia inicial.
   - Compra real en moneda extranjera usa el tipo de cambio guardado en la orden para proponer MXN.
   - Costo proveedor/lista en moneda no MXN queda bloqueado para `costo_referencia` hasta politica cambiaria.
   - `costo_promedio_historico` queda como indicador calculado, no persistente.

5. Definir futuro de legacy y migracion completa.
   - Impacta P9-08 y P10-05.
   - Recomendacion inicial: conservar legacy visible como historico durante pruebas reales; migrar por lotes solo con respaldo externo previo y revision de staging.

Siguiente paso recomendado:

- Definir futuro de legacy y migracion completa, porque ya quedaron cerradas las decisiones operativas principales de Proveedores.
- Mantener `activo_pagos` y reglas financieras fuera de Proveedores hasta revisar Finanzas/Pagos.

No se ejecuto:

- No se hicieron migraciones.
- No se tocaron datos masivos.
- No se cambiaron permisos reales.
- No se oculto ni bloqueo legacy.
- No se crearon reglas nuevas de negocio sin autorizacion.

## Propuesta de politica P6-07 - costo_referencia mas real posible

Fecha: 2026-06-14.

Contexto:

- El dueno plantea que `costo_referencia` deberia representar el costo mas real posible.
- El costo mas confiable no es necesariamente el de una lista de proveedor, sino el costo realmente comprado/recibido.
- Si todavia no existe compra real, se puede usar el mejor costo disponible como referencia inicial, pero debe poder reemplazarse despues por evidencia mas fuerte.

Politica recomendada:

1. Fuente mas confiable: costo real comprado/recibido.
   - Cuando exista recepcion/compra real validada, ese dato debe tener prioridad sobre listas de proveedor.
   - No debe perderse el historial de costos por proveedor.

2. Fuente intermedia: costo vigente autorizado de proveedor.
   - Si no hay compra real, usar costo vigente autorizado desde `erp_proveedores_sku_costos`.
   - Debe tener proveedor, SKU, moneda, unidad/factor, evidencia/lista y usuario que lo aplico.

3. Fuente inicial: primer costo confiable disponible.
   - Si un SKU nunca se ha comprado y no tiene `costo_referencia`, se puede proponer el primer costo vigente autorizado como referencia inicial.
   - Debe quedar marcado como referencia inicial, no como costo real comprado.

4. Reemplazo posterior:
   - Cuando llegue una compra/recepcion real, el sistema debe poder proponer reemplazar `costo_referencia`.
   - El costo anterior queda como referencia historica, no se borra.

5. Costo promedio:
   - Puede ser util como indicador financiero/inventario, pero no conviene mezclarlo sin definirlo con Compras/Almacen/Finanzas.
   - Si se implementa, debe ser otro indicador o regla clara: promedio simple, promedio ponderado por cantidad, promedio ultimas compras, etc.

Regla operativa inicial sugerida:

- `costo_referencia` puede actualizarse desde:
  1. costo de compra/recepcion real validada, si existe;
  2. si no existe compra real, costo vigente autorizado de proveedor;
  3. si hay varios costos vigentes, preferir proveedor marcado como preferido;
  4. si no hay proveedor preferido, dejar como propuesta y requerir seleccion humana.
- Cambios grandes, por ejemplo mayor o igual a 10%, deben advertirse antes de aplicar.
- Moneda distinta de MXN debe bloquear aplicacion hasta definir conversion.

Pendiente de politica complementaria:

- Almacen/recepcion queda resuelto en P6-13: no actualiza automaticamente `costo_referencia`.
- Implementar cierre de compra/costos como origen formal de costo real.
- Confirmar si `costo_promedio` sera un indicador separado.
- Mejorar la aplicacion desde Proveedores para seleccion puntual/manual si las pruebas reales lo piden; hoy existe aplicacion controlada de elegibles.

Sin tocar datos ni esquema:

- Crear pantallas/consultas solo lectura de proveedores, listas y renglones heredados. Estado: primera pantalla de auditoria aplicada en `/proveedor/auditoria_erp`; faltan pantallas de detalle por proveedor/lista/renglon si se autorizan.
- Crear auditorias tecnicas de cobertura: proveedores sin fiscal, listas sin moneda, renglones sin costo, renglones sin match. Estado: primera auditoria dry-run aplicada y expuesta en UI tecnica.
- Documentar endpoints actuales y proponer nuevos endpoints sin implementarlos.
- Preparar modelos `*Esquema.php` solo como plan/auditoria, sin ejecutar actualizacion.
- Agregar validaciones de UI no destructivas cuando el flujo nuevo ya este autorizado.

## Tareas que requieren autorizacion explicita

- Ejecutar migraciones o crear/modificar tablas.
- Migrar datos de listas heredadas a nuevas tablas ERP.
- Aplicar costos en lote a `erp_catalogo_sku_proveedores` fuera del flujo ya autorizado/controlado.
- Actualizar `erp_catalogo_skus.costo_referencia` fuera del flujo controlado de P6-12 o sin auditoria.
- Cambiar permisos reales o roles.
- Sustituir endpoints legacy por endpoints nuevos.
- Borrar, archivar o transformar datos historicos.
- Definir valores por defecto de negocio: moneda, impuestos, vigencia, estatus, autorizadores.

## Cierre del dia - 2026-06-14

Estado al cerrar:

- Proveedores ya cuenta con maestro, ficha 360, listas versionadas, carga/vista previa/importacion de listas, matching, relacion individual proveedor-SKU, costo individual vigente, incidencias hacia Catalogo, SKU temporal desde Catalogo y contrato con Compras.
- La migracion desde `db/productivo/erp_proveedores*.sql` ya fue convertida a listas ERP en borrador como evidencia reutilizable.
- La UI del detalle de listas ya separa renglones informativos vs operativos y muestra preparacion para relacion/costo.
- Quedo implementado el preview de relaciones proveedor-SKU en lote, sin escrituras.

Pendientes recomendados para retomar manana:

1. Probar en UI el boton `Preview relaciones` con listas reales importadas desde productivo.
2. Revisar si los renglones incluidos/excluidos del preview coinciden con lo esperado.
3. Probar aplicacion real en lote de relaciones solo cuando los incluidos sean confiables.
4. Probar en UI `Preview costos` y aplicacion real en lote con listas ya relacionadas.
5. Probar `Preview costo ref` y aplicar solo elegibles cuando el preview coincida con lo esperado.
6. Probar politica P6-07 con compras reales: compra real en moneda extranjera debe proponer costo en MXN con el tipo de cambio de la orden; proveedor/lista no MXN sigue bloqueado para `costo_referencia` hasta politica cambiaria.
7. Probar flujo Proveedores -> Catalogo:
   - crear pendiente desde producto proveedor sin match;
   - crear SKU temporal en Catalogo;
   - volver a Proveedores y confirmar matching contra SKU temporal.
8. Probar flujo Proveedores -> Compras:
   - SKU con relacion activa;
   - SKU sin costo vigente;
   - SKU con unidad/factor incompleto;
   - confirmar advertencias no bloqueantes.
9. Revisar los 2 renglones legacy pendientes por costo no positivo documentados en P9-06.
10. Revisar documentos de pruebas reales:
    - `docs/erp_proveedores_pruebas_reales.md`;
    - `docs/erp_catalogo_pruebas_reales.md`;
    - `docs/erp_proveedores_catalogo_pruebas_reales.md`;
    - `docs/erp_pruebas_reales_modulos.md`.

Tareas que no conviene hacer sin nueva autorizacion:

- Aplicar relaciones/costos en lote sin preview y confirmacion.
- Actualizar `erp_catalogo_skus.costo_referencia` fuera del flujo controlado.
- Crear incidencias en lote.
- Definir bloqueos operativos en Compras.
- Cambiar estados finales del proveedor por departamento.
- Ejecutar migraciones o cambios de esquema nuevos.

Texto sugerido para commit:

`Avanza Proveedores ERP: listas, matching y preview lote`

Respaldo de base de datos generado:

- Fecha/hora: 2026-06-14 01:19.
- Base local respaldada: `artianilocal`.
- Carpeta externa al proyecto: `C:\Users\aleja\Documents\Respaldos_DB_panel`.
- Archivo: `panel_artianilocal_20260614_011927.sql`.
- Tamano aproximado: 18.6 MB.

## Documentos de trabajo

Los archivos de trabajo se usan solo mientras una tarea esta activa. Al completarse, la evidencia se integra aqui y el auxiliar se elimina.

| Orden | Bloque | Estado | Evidencia |
| --- | --- | --- | --- |
| 1 | Esquema y auditoria tecnica | Consolidado | Seccion "Evidencia consolidada - esquema y flujo real". |
| 2 | Separacion legado vs ERP nuevo | Consolidado | Seccion "Evidencia consolidada - legado a ERP". |
| 3 | Proveedores maestros | Consolidado | Seccion "Evidencia consolidada - maestro de proveedores". |
| 4 | UI y permisos base | Consolidado | Seccion "Evidencia consolidada - UI y permisos". |
| 5 | Listas de proveedor | Consolidado | Seccion "Evidencia consolidada - listas de proveedor". |
| 6 | Matching SKU proveedor contra Catalogo | Consolidado | Seccion "Evidencia consolidada - matching SKU proveedor". |
| 7 | Costos, unidades, factores y moneda | Consolidado | Seccion "Evidencia consolidada - costos, unidades, factores y moneda". |
| 8 | Incidencias hacia Catalogo | Consolidado | Seccion "Evidencia consolidada - incidencias hacia Catalogo". |
| 9 | Contrato con Compras | Consolidado | Seccion "Evidencia consolidada - contrato con Compras". |
| 10 | Cierre y evidencia | Consolidado | Seccion "Evidencia consolidada - cierre". |

## Regla de cierre documental

Al terminar cada documento de trabajo:

- Integrar evidencia en este archivo.
- Registrar decisiones y verificaciones.
- Dejar pendientes reales separados.
- Eliminar el documento auxiliar completado.
- Mantener solo este archivo vivo cuando todos los auxiliares esten consolidados.

No avanzar a otro punto si el actual deja una decision pendiente que cambia estructura, permisos, datos maestros o reglas de negocio.

## Cierre operativo para cambio de modulo - 2026-06-15

Estado del modulo Proveedores:

- Maestro ERP de proveedores implementado con ficha 360.
- Datos generales, fiscales, contactos, condiciones y documentos metadata disponibles.
- Listas versionadas con archivo/evidencia, vista previa, mapeo e importacion en borrador.
- Detalle de listas con renglones operativos/informativos, matching y filtros.
- Relacion proveedor-SKU individual y en lote con preview/confirmacion.
- Costos proveedor-SKU individuales y en lote con historial; `costo_ultimo` como referencia operativa.
- `costo_referencia` con preview/aplicacion controlada: compra real prioritaria, proveedor vigente solo referencia inicial, moneda proveedor/lista no MXN bloqueada hasta politica cambiaria.
- Incidencias hacia Catalogo, resolucion/descarte con motivo y SKU temporal desde Catalogo.
- Contrato con Compras para busqueda de SKUs comprables por proveedor, evidencia de costo y snapshots.
- Proveedores legacy marcados como historicos/de transicion.
- Migracion productivo revisada: respaldo externo creado, staging cargado, sin candidatos nuevos y pendientes legacy cerrados como historico no utilizable.

Pendiente principal:

- Ejecutar pruebas reales documentadas en `docs/erp_proveedores_pruebas_reales.md`, especialmente seccion 20.
- Ajustar UX/textos si las pruebas reales muestran confusion.
- No ejecutar conversiones legacy nuevas salvo que existan candidatos nuevos y respaldo previo.

Modulo recomendado para continuar:

- **Compras ERP**.

Motivo:

- Proveedores ya entrega relaciones, costos, evidencia y bloqueos minimos.
- El siguiente riesgo operativo vive en Compras: solicitudes, ordenes, autorizaciones, snapshots, envio, recepcion posterior y cierre de costo real.
- Compras es el consumidor natural de Proveedores; probarlo despues ayuda a validar que el trabajo de Proveedores realmente escala hacia operacion.

Contexto sugerido para nuevo chat:

```text
Estoy continuando el ERP despues de cerrar la base del modulo Proveedores.
Lee primero AGENTS.md, docs/erp_plan_maestro_fundamentos.md, docs/erp_ux_operativa.md, docs/ia_uso_modelos.md, docs/erp_proveedores_avance.md, docs/erp_proveedores_pruebas_reales.md, docs/erp_compras_vision_operativa.md y docs/erp_compras_plan_modulo.md.

Estado Proveedores:
- Maestro/ficha 360/listas/matching/costos/incidencias/contrato con Compras ya estan implementados.
- Legacy de Proveedores quedo marcado como historico.
- Migracion desde db/productivo quedo auditada, con respaldo externo y staging; no hay candidatos nuevos para convertir.
- Pendientes legacy OCL-28A/OCL-28W quedaron como historico no utilizable.
- Quedan pruebas reales documentadas en docs/erp_proveedores_pruebas_reales.md.

Objetivo del nuevo trabajo:
- Continuar con Compras ERP usando Proveedores como fuente confiable de proveedor-SKU, costos y evidencia.
- Auditar flujo real de solicitudes y ordenes.
- Validar bloqueos minimos, advertencias, snapshots, permisos, auditoria y relacion con Almacen/recepcion.
- No inventar reglas de negocio; preguntar cuando una decision afecte estados, dinero, inventario, permisos o datos maestros.
- No ejecutar migraciones ni escrituras masivas sin autorizacion.
```

Texto sugerido para commit:

`Cierra base Proveedores ERP y prepara pruebas reales`

## Avance tecnico 2026-06-16 - Incidencia de proveedor con notificacion a Catalogo

Documentacion IA: Codex GPT-5

Archivos:

- `app/modelos/Proveedores.php`
- `app/modelos/CatalogoErpDatos.php`
- `app/modelos/NotificacionesErp.php`
- `docs/erp_notificaciones_alertas_trabajo.md`

Implementacion:

- Cuando Proveedores crea una incidencia real desde un renglon de lista/matching sin SKU ERP confiable, tambien crea/actualiza una notificacion operativa para Catalogo.
- Tipo de notificacion: `proveedor_producto_pendiente_alta_catalogo`.
- Responsable: `catalogo`.
- Permiso visible: `catalogo.editar`.
- Ruta operativa: `/catalogoerp`.
- La incidencia conserva la informacion base del proveedor: proveedor, lista, renglon, SKU proveedor, descripcion y accion sugerida.
- Cuando Catalogo crea el producto/SKU temporal desde esa incidencia, la alerta de Catalogo se resuelve y se crea una notificacion para Proveedores.
- Tipo de notificacion de seguimiento: `catalogo_sku_temporal_creado_proveedor_matching`.
- Responsable: `proveedores`.
- Permiso visible: `proveedores.matching`.
- Ruta operativa: `/proveedor/mostrar_proveedores_erp`.
- Cuando Proveedores aplica la relacion proveedor-SKU, individual o en lote, el seguimiento queda resuelto.

Regla operativa confirmada:

- Proveedores puede pedir a Catalogo crear o revisar un SKU ERP usando la evidencia del proveedor.
- Catalogo sigue siendo responsable de crear el producto/SKU ERP.
- Proveedores sigue siendo responsable del matching proveedor-SKU ERP.
- Compras solo queda destrabado cuando existe SKU ERP y relacion proveedor-SKU utilizable.
- Compras no debe quedar forzado a inventar datos maestros; si necesita comprar un producto nuevo, debe generar/usar este flujo de pendiente.

Verificacion:

- `C:\xampp\php\php.exe -l app\modelos\NotificacionesErp.php`
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`
- `C:\xampp\php\php.exe -l app\modelos\CatalogoErpDatos.php`

Prueba real pendiente:

- Crear incidencia desde un producto proveedor sin match.
- Confirmar que aparece notificacion para Catalogo.
- Crear SKU temporal desde Catalogo.
- Confirmar que la notificacion de Catalogo se resuelve.
- Confirmar que aparece seguimiento para Proveedores.
- Vincular el producto proveedor al SKU temporal.
- Confirmar que el seguimiento para Proveedores queda resuelto.
- Volver a probar la orden de compra.

## Avance tecnico 2026-06-16 - Enviar renglon preciso de lista a Catalogo

Documentacion IA: Codex GPT-5

Archivos:

- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`

Implementacion:

- Se agrego buscador directo dentro del modal `Detalle de lista` para filtrar por SKU proveedor, codigo de barras, codigo interno, marca, descripcion, unidad, moneda, estado, criterio u observaciones.
- Se agrego accion por renglon `Enviar este producto a Catalogo` para atacar un SKU preciso desde la lista del proveedor.
- La accion aparece para usuarios con `proveedores.autorizar` cuando el renglon no tiene SKU ERP o esta en estado `sin_match`/`ambiguo`.
- Si el renglon no tiene SKU ERP, se genera incidencia `proveedor_sku_sin_match`.
- Si el renglon esta ambiguo, se genera incidencia `proveedor_match_ambiguo`.
- La incidencia usa el flujo existente de Proveedores -> Catalogo -> Notificaciones, por lo que Catalogo recibe la alerta y puede crear/revisar SKU ERP.

Motivo:

- `Resolver pendientes` sigue siendo util como auditoria masiva, pero no debe ser el unico camino operativo.
- Para Compras y Proveedores es mas practico ubicar un SKU especifico del proveedor y enviarlo a Catalogo desde el renglon exacto.

Verificacion:

- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`

Prueba real pendiente:

- Abrir una lista de proveedor con muchos renglones.
- Buscar un SKU proveedor especifico desde el nuevo buscador.
- Usar la accion de enviar a Catalogo en ese renglon.
- Confirmar que se crea la incidencia/notificacion para Catalogo con la informacion de ese producto preciso.

Correccion posterior:

- Error detectado en prueba: `La propuesta ya no esta vigente en el dry-run`.
- Causa: el envio directo desde renglon seguia dependiendo de que la propuesta existiera en el dry-run masivo de incidencias.
- Ajuste: `Proveedores::crearIncidenciaProveedorErp()` ahora puede construir la incidencia directa desde el renglon validado de la lista cuando no aparece en el dry-run.
- La ruta directa solo permite tipos controlados para este caso:
  - `proveedor_sku_sin_match` si el renglon no tiene SKU ERP o esta marcado sin match.
  - `proveedor_match_ambiguo` si el renglon esta marcado ambiguo.
- No se relajan reglas para otros tipos de incidencia.

Verificacion adicional:

- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`

Correccion posterior 2:

- Error detectado en prueba: `There is no active transaction`.
- Causa probable: el entorno PDO/MySQL reporto la transaccion como no activa al momento de confirmar la creacion de incidencia/notificacion.
- Ajuste final: `crearIncidenciaProveedorErp()` ya no usa transaccion explicita para esta creacion puntual y valida que exista `id_incidencia_calidad` despues del INSERT/UPDATE.
- Si no puede confirmar el registro real en Catalogo, responde error en vez de mostrar exito.
- No cambia la regla de negocio ni el esquema; evita que el flujo indique exito sin que la incidencia quede visible en Catalogo.

Correccion posterior 3:

- Error detectado en prueba: `There is no active transaction` al crear SKU temporal desde Catalogo.
- Ajuste: `CatalogoErpDatos::crearSkuTemporalDesdeIncidenciaProveedor()` valida que el producto, SKU e incidencia actualizada existan antes de responder exito.
- Ajuste final: se elimino la transaccion explicita de esta creacion puntual y se mantiene confirmacion posterior de producto, SKU e incidencia.
- Si no puede confirmar el SKU temporal creado, responde error en vez de mostrar exito.

Verificacion adicional:

- `C:\xampp\php\php.exe -l app\modelos\CatalogoErpDatos.php`

Correccion posterior 4:

- Error detectado en prueba: al resolver una incidencia desde `Proveedores > Pendientes y resolucion`, el modal pedia motivo pero no permitia escribirlo.
- Ajuste: el modal de resolucion/descartado ahora usa un `textarea` HTML propio con foco inicial y validacion manual del motivo.
- No cambia reglas de negocio; solo corrige captura de evidencia para cerrar incidencias.

Hallazgo operativo SFF-303:

- Proveedor/lista ya tienen el renglon `SFF-303` relacionado con SKU ERP.
- Catalogo ya tiene producto/SKU temporal creado para `SFF-303`.
- La relacion proveedor-SKU esta activa.
- Compras no lo muestra todavia porque las busquedas de solicitudes/ordenes solo aceptan SKU ERP con `estatus='activo'`.
- En este caso el SKU ERP quedo en `borrador`, por eso no es comprable aun.

Siguiente validacion recomendada:

- En `Catalogo ERP`, buscar `SFF-303`.
- Abrir detalle del producto.
- Entrar a la pestana `SKUs`.
- Editar el SKU `SFF-303`.
- Cambiar `Estado` de `Borrador` a `Activo`.
- Guardar SKU.
- Volver a probar en Compras si ya aparece para solicitud/orden.

Decision operativa viva:

- No se debe activar automaticamente todo SKU temporal creado desde proveedor.
- El SKU temporal sirve para destrabar el alta y el matching.
- La activacion a `activo` debe ser una validacion de Catalogo cuando el producto ya puede usarse operativamente en Compras.
- Si este paso se vuelve repetitivo, se puede agregar despues una accion explicita `Activar para compras` con validaciones minimas, permiso de Catalogo y evidencia.

Verificacion adicional:

- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`

## Avance tecnico 2026-07-16 - Matching masivo conservador

Documentacion IA: Codex GPT-5

Archivos:

- `app/controladores/Proveedor.php`
- `app/modelos/Proveedores.php`
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`

Implementacion:

- Se agrego endpoint `proveedor_lista_matching_masivo_erp`.
- Se agrego boton `Seleccionar confiables` dentro del modal `Matching dry-run`.
- El boton solo se habilita cuando el dry-run encuentra candidatos elegibles.
- El servidor recalcula el matching antes de escribir; no confia solo en el conteo del navegador.
- La seleccion masiva solo guarda `match_seleccionado`.
- No aplica relaciones proveedor-SKU.
- No aplica costos.
- No toca `costo_referencia`.

Regla de seguridad:

- Elegibles:
  - `relacionado` con un unico candidato por relacion activa proveedor-SKU.
  - `match_exacto_pendiente` con un unico candidato por SKU/codigo exacto.
  - `match_exacto_pendiente` con un unico candidato creado desde incidencia de Catalogo.
- Excluidos:
  - `ambiguo`.
  - `sin_match`.
  - `match_posible` por nombre/descripcion.
  - renglones ya en `match_seleccionado`.
  - renglones ya en `relacion_aplicada` o `costo_aplicado`.
  - cualquier renglon sin candidato unico.

Finalidad:

- Reducir captura repetitiva cuando una lista nueva tiene muchos matches obvios.
- Mantener revision manual para casos dudosos.
- Conservar el flujo robusto: primero seleccionar matching, despues revisar/aplicar relaciones en lote, despues costos si corresponde.

Verificacion:

- `C:\xampp\php\php.exe -l app\controladores\Proveedor.php`
- `C:\xampp\php\php.exe -l app\modelos\Proveedores.php`
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\erp\proveedores\listado_erp.php`
- `node --check public\assets\js\custom\apps\erp\proveedores\listado_erp.js`

Prueba real pendiente:

- Cargar una lista nueva de proveedor.
- Abrir `Matching dry-run`.
- Confirmar conteo de `Seleccionar confiables`.
- Ejecutar seleccion masiva.
- Confirmar que solo cambia a `match_seleccionado`.
- Confirmar que ambiguos/posibles/sin match quedan sin seleccion automatica.
- Ejecutar `Preview relaciones` y aplicar solo si el preview es correcto.

## Avance tecnico 2026-07-20 - Recarga conservadora de listas de proveedor

Documentacion IA: Codex GPT-5

Archivo:

- `app/modelos/Proveedores.php`

Hallazgo en prueba real:

- En la lista de proveedor PET GLASS / ARTIANI TERRARIOS se cargo un archivo nuevo con renglones adicionales.
- La lista si apuntaba al archivo nuevo, pero la vista previa solo mostraba una muestra corta y los productos agregados estaban al final.
- La lista ya tenia renglones importados; el importador anterior rechazaba cualquier importacion si la lista no estaba vacia.

Implementacion:

- La vista previa de archivos `CSV/TXT/XLSX` dejo de usar una muestra corta fija; el limite se controla desde la UI.
- La importacion de archivo actualizado ya no bloquea por tener renglones previos.
- Si la lista ya tiene detalle, el importador anexa solo renglones nuevos.
- La deteccion de duplicados usa la mejor clave disponible en este orden: `sku_proveedor`, `codigo_barras`, `codigo_interno` y, solo si no hay identificadores, `descripcion_proveedor`.
- No se borran renglones existentes.
- No se sobrescriben costos, matches, relaciones proveedor-SKU ni `costo_referencia`.

Prueba real pendiente:

- En Proveedores, abrir la lista de PET GLASS / ARTIANI TERRARIOS.
- Entrar a `Vista previa archivo` y confirmar que aparecen los renglones nuevos del archivo reciente.
- Revisar el mapeo de columnas.
- Ejecutar importacion.
- Confirmar que se agregan los renglones nuevos y que los 30 renglones previos quedan intactos.
- Confirmar que los renglones nuevos quedan en `sin_match` para continuar con matching/revision normal.

## Correccion 2026-07-20 - Preview dinamico y duplicados por mapeo

Hallazgo posterior:

- Al recargar la lista, el archivo nuevo podia mapear `CODIGO` hacia `codigo_interno` y no hacia `sku_proveedor`.
- Los renglones previos tenian el mismo valor en `sku_proveedor`/`codigo_interno`, pero la comparacion anterior revisaba solo una clave prioritaria.
- Esto permitio insertar renglones repetidos cuando el mismo identificador entraba por otra columna.

Ajuste:

- La deduplicacion ahora compara identificadores fuertes de forma cruzada:
  - `sku_proveedor`
  - `codigo_barras`
  - `codigo_interno`
  - clave generica `identificador`
- `descripcion_proveedor` solo se usa como respaldo si no hay ningun identificador fuerte.
- La vista previa ahora tiene selector de filas: `100`, `500`, `1000`.
- El endpoint de preview recibe `limite_preview` con tope de `1000` para no saturar navegador.
- La importacion tecnica sube el maximo a `50000` filas para listas grandes.

Pendiente con autorizacion:

- Limpiar los renglones duplicados que se insertaron en la prueba de PET GLASS, conservando los renglones originales y dejando solo los productos realmente nuevos.

Limpieza autorizada y ejecutada:

- Se eliminaron 30 renglones duplicados de la lista PET GLASS / ARTIANI TERRARIOS.
- Rango eliminado: `id_lista_detalle_erp` `10943` a `10972`.
- Validacion previa: no tenian `id_sku`, `id_sku_proveedor` ni costos ligados.
- Resultado: lista `14` quedo con 34 renglones, conservando 30 originales y 4 nuevos (`10973` a `10976`).

## Avance tecnico 2026-07-20 - Manual operativo dentro de Proveedores

Archivos:

- `app/controladores/Proveedor.php`
- `app/vistas/paginas/apps/erp/proveedores/manual_erp.php`
- `app/vistas/includes/header/sidebar.php`
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`

Implementacion:

- Se agrego la ruta `/proveedor/manual_erp` con permiso `proveedores.ver`.
- Se agrego acceso en el sidebar de Proveedores como `Manual de uso`.
- Se agrego boton `Manual` en la pantalla principal de Proveedores ERP.
- El manual cubre flujo recomendado, maestro, listas, mapeo, matching, incidencias a Catalogo, costos, uso en Compras, errores comunes y checklist operativo.

Finalidad:

- Que el usuario pueda aprender y operar el modulo sin depender del chat.
- Conservar una guia viva dentro del ERP mientras se siguen realizando pruebas reales.

## Correccion 2026-07-20 - Matching exacto por codigo interno contra SKU ERP

Hallazgo en prueba real:

- Proveedor `24`, lista `15`, Terrarios madera.
- Los renglones importados traen el identificador del producto en `codigo_interno`.
- El buscador manual encontraba SKUs porque buscaba el termino contra `erp_catalogo_skus.sku`.
- El matching dry-run solo comparaba `codigo_interno` contra `erp_catalogo_sku_codigos.codigo`, no contra el SKU principal.
- Resultado anterior: productos con SKU ERP exacto podian caer como ambiguos por coincidencia de nombre.

Ajuste:

- `candidatosSkuExactoErp` ahora compara `sku_proveedor`, `codigo_barras` y `codigo_interno` contra:
  - `erp_catalogo_skus.sku`
  - `erp_catalogo_sku_codigos.codigo`
- No crea relaciones.
- No aplica costos.
- No modifica renglones; solo mejora el calculo del dry-run.

Validacion local:

- Dry-run proveedor `24`, lista `15` despues del ajuste:
  - `total`: 30
  - `match_exacto_pendiente`: 10
  - `ambiguo`: 20
  - `sin_match`: 0
- Los 20 ambiguos restantes no tienen SKU ERP exacto `TEMA...` detectado en Catalogo local; solo coinciden por nombre/descripcion.

## Avance tecnico 2026-07-20 - Completar compra en lote y ayuda de campos

Archivos:

- `app/controladores/Proveedor.php`
- `app/modelos/Proveedores.php`
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`
- `public/assets/js/custom/apps/erp/proveedores/listado_erp.js`

Implementacion:

- Se agrego endpoint `proveedor_lista_detalle_compra_lote_erp`.
- Se agrego boton `Completar compra` en el detalle de lista.
- El lote puede aplicarse a:
  - renglones filtrados con compra pendiente;
  - todos los renglones filtrados.
- Modo recomendado: `Solo completar campos vacios`.
- Modo alterno: sobrescribir campos existentes si se autoriza desde la UI.
- No aplica relacion proveedor-SKU.
- No aplica costos.
- No actualiza `costo_referencia`.
- No modifica Catalogo.

Campos aclarados en UI:

- `Presentacion del proveedor`: texto de referencia como viene en la lista, por ejemplo pieza, caja o paquete.
- `Unidad de compra ERP`: unidad formal para Compras.
- `Factor de conversion`: cuantas unidades base contiene la unidad de compra.
- `Compra minima`: cantidad minima que el proveedor permite comprar de esa presentacion.
- `Moneda`: moneda del costo reportado, por ejemplo `MXN` peso mexicano, `USD` dolar.
- `Costo incluye impuestos`: `Si` equivale a `1`; `No` equivale a `0`.
- `Existencia reportada`: stock informado por el proveedor; no afecta inventario propio.

Finalidad:

- Evitar capturar de uno por uno unidad/factor/minimo/moneda/impuestos cuando una lista grande comparte condiciones de compra.
- Preparar renglones con `match_seleccionado` para que luego puedan entrar al lote de aplicar relaciones.

## Correccion 2026-07-20 - Recarga de lista actualiza costos de renglones existentes

Hallazgo:

- Al recargar una lista del mismo proveedor con los mismos SKUs/codigos, el importador evitaba duplicados pero tambien omitia cambios utiles.
- Caso real: lista `15` Terrario madera, recargada para corregir precios con impuestos.

Ajuste:

- Cuando un renglon importado ya existe por identificador fuerte, ya no se omite automaticamente.
- Se compara si cambiaron datos variables de la lista:
  - `marca_proveedor`
  - `descripcion_proveedor`
  - `unidad_compra_texto`
  - `factor_conversion`
  - `costo`
  - `moneda`
  - `costo_incluye_impuestos`
  - `existencia_reportada`
- Si hay diferencias, se actualiza el detalle de la lista.
- Si no hay diferencias, se omite como existente sin cambios.

Regla operativa:

- Actualizar el renglon de lista no aplica automaticamente el costo vigente del proveedor.
- No cambia relaciones proveedor-SKU.
- No actualiza `costo_referencia`.
- Para que el costo corregido sea usado operativamente, despues debe revisarse/aplicarse desde el flujo de costos correspondiente.

## Correccion 2026-07-20 - Cierre de incidencia Catalogo al aplicar relacion

Hallazgo:

- En el flujo Proveedores -> Catalogo -> Proveedores, crear un SKU temporal desde Catalogo dejaba la incidencia `proveedor_sku_sin_match` en `en_revision`.
- Al aplicar la relacion proveedor-SKU, Proveedores resolvia la notificacion de seguimiento, pero no cerraba la incidencia de Catalogo.
- Caso de referencia: SKU `ALA-0003`, incidencia `6`, renglon proveedor `11009`.

Contrato corregido:

- `en_revision` significa que Catalogo ya tomo la incidencia y creo/relaciono un candidato, pero falta cerrar el matching desde Proveedores.
- La incidencia debe pasar a `resuelta` cuando el renglon de proveedor queda en `relacion_aplicada` con `id_sku` e `id_sku_proveedor`.
- No se cierra al solo editar el producto o activar el maestro.

Implementacion:

- `Proveedores::aplicarRelacionSkuProveedorErp()` ahora llama `resolverIncidenciaCatalogoProveedorPorRelacion()`.
- `Proveedores::aplicarRelacionesSkuProveedorLoteErp()` tambien cierra incidencias en lote.
- La resolucion queda en `erp_catalogo_incidencias_calidad.resolucion_json` con proveedor, lista, renglon, SKU ERP y relacion proveedor-SKU.

Siguiente paso operativo para el usuario:

1. Volver a Proveedores, lista `Alamazonas` ID `16`.
2. Ejecutar matching.
3. Verificar que el renglon `11009` proponga el candidato `ALA-0003` por `incidencia_catalogo_sku_temporal`.
4. Seleccionar el candidato.
5. Completar unidad de compra, factor y compra minima si estan vacios.
6. Aplicar relacion.
7. Regresar a Catalogo y confirmar que la incidencia desaparece de abiertas.

## Correccion 2026-07-21 - SKU proveedor en relaciones desde codigo interno

Hallazgo:

- En algunas listas el codigo real del proveedor no queda mapeado como `sku_proveedor`, sino como `codigo_interno`.
- El matching podia encontrar el SKU ERP correcto porque ya compara `sku_proveedor`, `codigo_barras` y `codigo_interno` contra SKU/codigos de Catalogo.
- El problema estaba al aplicar la relacion: `erp_catalogo_sku_proveedores.sku_proveedor` se guardaba solo desde `sku_proveedor`.
- Si `sku_proveedor` venia vacio, la relacion proveedor-SKU quedaba creada pero sin clave operativa del proveedor.

Ajuste aplicado:

- Al crear o actualizar una relacion proveedor-SKU desde Proveedores, el identificador se toma con esta prioridad:
  1. `sku_proveedor`
  2. `codigo_interno`
  3. `codigo_barras`
- Esto aplica tanto para relacion individual como para relaciones en lote.

Impacto esperado:

- Las nuevas relaciones ya deben conservar el codigo que usa el proveedor aunque haya venido en `codigo_interno`.
- El matching posterior podra reconocer relaciones activas por ese codigo proveedor.
- No se modificaron datos existentes de base de datos en esta correccion.

Pendiente con autorizacion:

- Revisar y, si se autoriza, corregir relaciones ya creadas con `sku_proveedor` vacio tomando el valor desde el renglon de lista relacionado.

## Correccion 2026-07-21 - Estado intermedio en matching masivo con relacion existente

Hallazgo:

- En lista `13` (`Lista sunny acuario`) se detectaron `720` renglones.
- `661` estaban `sin_match`, por lo que aun no tienen SKU ERP y no pueden avanzar a relacion/costo.
- `59` ya tenian `id_sku_proveedor`, es decir, ya habia relacion proveedor-SKU.
- De esos `59`, `55` quedaron con `estado_match = match_seleccionado`.
- Ese estado era correcto para un candidato SKU ERP aun no relacionado, pero no para un renglon que ya trae una relacion proveedor-SKU existente.

Causa:

- El matching masivo guardaba todos los candidatos confiables como `match_seleccionado`.
- Cuando el candidato ya era una relacion activa de `erp_catalogo_sku_proveedores`, el sistema debia considerarlo operativo como `relacion_aplicada`.
- Por eso no aparecian en `Preview relaciones` y tampoco avanzaban bien a costos: estaban relacionados en datos, pero no en estado operativo.

Ajuste aplicado:

- Matching individual:
  - Si se selecciona un candidato con `id_sku` e `id_sku_proveedor`, el renglon queda como `relacion_aplicada`.
  - Si solo se selecciona `id_sku`, queda como `match_seleccionado` para aplicar relacion despues.
- Matching masivo:
  - Si el candidato ya trae `id_sku_proveedor`, queda como `relacion_aplicada`.
  - Si solo trae SKU ERP exacto, queda como `match_seleccionado`.
- Preview/aplicacion de costos:
  - Ahora permite continuar cuando el renglon tiene `id_sku` + `id_sku_proveedor` validos aunque haya quedado historicamente como `match_seleccionado`.

Resultado esperado para lista `13`:

- Con la nueva regla, `57` renglones quedan listos para preview/aplicacion de costos.
- `661` siguen pendientes de matching/alta en Catalogo.
- `2` siguen excluidos por costo o moneda incompleta.

Nota operativa:

- No se hizo escritura masiva para cambiar estados historicos.
- La UI y el servidor ahora deben permitir avanzar sin depender de una limpieza manual previa.

## Correccion 2026-07-21 - Validacion de lista no debe bloquear por relaciones sin costo

Hallazgo:

- Despues de aplicar costos en lista `13`, al intentar pasar la lista a `validada` el sistema respondia que necesitaba al menos un renglon operativo.
- La lista si tenia renglones operativos y costos aplicados.
- El bloqueo real venia de `2` renglones en `relacion_aplicada` sin moneda/impuestos, mientras `55` costos ya estaban vigentes y operativos.

Causa:

- La validacion trataba toda relacion proveedor-SKU como operativa obligatoria.
- Eso hacia que una relacion sin costo/moneda bloqueara la validacion de toda la lista, aunque no se hubiera aplicado como costo vigente.

Regla corregida:

- Para validar una lista de proveedor debe existir al menos un costo vigente operativo:
  - `id_sku`
  - `id_sku_proveedor`
  - costo positivo
  - moneda definida
  - estatus de costo `vigente`
- Las relaciones sin costo vigente quedan como pendientes o evidencia, pero no bloquean validar la lista completa.

Resultado esperado:

- Lista `13` puede pasar a `validada` porque tiene `55` costos vigentes operativos.
- Los renglones relacionados sin moneda/costo siguen visibles para correccion posterior.

## Correccion 2026-07-21 - Detalle de lista cortaba renglones en listas grandes

Hallazgo:

- En lista `12` (`Lista sunny Veterinaria`) la ultima recarga si actualizo los renglones.
- La base tiene `745` renglones para la lista.
- La vista de detalle consultaba solo `500` renglones.
- Por eso algunos productos importados no aparecian en el buscador de la lista aunque existian en base de datos.

Evidencia:

- Renglones finales existentes en base:
  - `SP-2827`
  - `SP-2826`
  - `SP-2825`
  - `SP-2824`
  - `SP-2823`
- Todos estaban fuera del primer bloque de 500 renglones.

Ajuste aplicado:

- El detalle de lista ahora carga hasta `5000` renglones.
- Tambien se subio a `5000` el limite de los procesos masivos principales relacionados con:
  - preview/aplicacion de relaciones proveedor-SKU
  - preview/aplicacion de costos
  - preview de costo referencia

Pendiente recomendado:

- Si las listas reales superan 5000 renglones, implementar paginacion/busqueda del lado servidor para el detalle de lista.

## Correccion 2026-07-21 - Identificadores numericos importados con .0 no hacian matching

Hallazgo:

- Caso real en lista `12` (`Lista sunny Veterinaria`):
  - SKU ERP en Catalogo: `417368` (`id_sku = 1379`).
  - Relacion proveedor-SKU activa: `id_sku_proveedor = 658`, `sku_proveedor = 417368`, `costo_ultimo = 269`.
  - Renglon importado desde la lista: `id_lista_detalle_erp = 10832`, `sku_proveedor = 417368.0`, `codigo_interno = 417368.0`, costo `277 MXN`, estado `sin_match`.
- Compras mostraba `269` porque no habia costo vigente en `erp_proveedores_sku_costos` para ese SKU; solo existia el respaldo `costo_ultimo`.
- El costo nuevo `277` estaba en la lista, pero no se habia relacionado ni aplicado porque el identificador venia con `.0`.

Causa:

- Excel entrega algunos codigos numericos como decimales (`417368.0`).
- Catalogo y la relacion proveedor-SKU usan `417368`.
- El matching exacto comparaba texto literal, por lo que `417368.0` no coincidia contra `417368`.

Ajuste aplicado:

- Se agrego normalizacion de identificadores de proveedor:
  - `417368.0` se trata como `417368`.
  - Solo se limpia el sufijo decimal `.0...` cuando el valor es entero numerico.
  - No se modifican codigos alfanumericos reales.
- La normalizacion aplica en:
  - importacion de lista
  - claves de deduplicacion
  - matching contra relaciones proveedor-SKU
  - matching contra SKU ERP/codigos de Catalogo
  - escritura de nuevas relaciones proveedor-SKU

Pendiente operativo:

- Para el caso historico `417368.0`, volver a ejecutar matching en lista `12` y aplicar costo vigente.
- Despues de aplicar costo, Compras debe tomar `277 MXN` desde `erp_proveedores_sku_costos` vigente en lugar del respaldo `269`.
