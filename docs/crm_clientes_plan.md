# CRM - Clientes plan maestro

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-29  
Estado: plan rector inicial del dominio CRM; no implica escrituras en BD.

## Proposito

Clientes deja de ser un campo auxiliar de ventas o ecommerce. CRM sera el dominio dueno de la identidad del cliente, su relacion comercial, historial, consentimiento, beneficios, garantias, apartados, devoluciones y reportes.

El POS debe vender rapido, pero no debe definir la arquitectura de clientes. POS consume CRM mediante busqueda, alta express autorizada y snapshot en venta.

## Decisiones base

- El cliente canonico vivira en CRM, no en Ventas ni en Ecommerce.
- Las tablas antiguas `crm_clientes` y los flujos de `Cliente.php`/`Clientes.php` se consideran legacy hasta auditarlos.
- Las tablas `erp_clientes` creadas para POS son base minima UAT, no modelo final completo.
- No se mezclaran clientes ecommerce/legacy/POS sin tabla de vinculos, auditoria y reglas de duplicados.
- Toda venta con cliente debe conservar snapshot historico, aunque el cliente cambie despues.
- Publico general no es un cliente real obligatorio; una venta puede tener `id_cliente=NULL`.

## Modelo canonico propuesto

### Identidad

- `crm_clientes_maestro`: identidad principal.
- `crm_clientes_identificadores`: telefono, correo, codigo, RFC, ecommerce_id u otros identificadores normalizados.
- `crm_clientes_contactos`: telefonos/correos/contactos secundarios.
- `crm_clientes_direcciones`: entrega, facturacion, fiscal, referencia.
- `crm_clientes_fiscales`: RFC, razon social, regimen, uso CFDI y datos fiscales versionables.

### Relacion comercial

- `crm_clientes_segmentos`: segmentos comerciales.
- `crm_clientes_segmentos_rel`: relacion cliente-segmento.
- `crm_clientes_condiciones`: lista default, credito futuro, restricciones y preferencias.
- `crm_clientes_consentimientos`: WhatsApp, correo, marketing, privacidad.

### Trazabilidad

- `crm_clientes_notas`: notas auditables.
- `crm_clientes_eventos`: historial de alta, compra, apartado, devolucion, garantia, fusion, bloqueo.
- `crm_clientes_vinculos_externos`: relacion explicita con legacy, ecommerce, POS UAT u otros sistemas.
- `crm_clientes_fusiones`: duplicados fusionados sin borrar historial.

### Crecimiento futuro

- Recompensas: cuentas, movimientos, reglas, cupones.
- Postventa: garantias y reclamos relacionados a cliente.
- Reportes: valor del cliente, frecuencia, recompra, devoluciones, garantias, segmentos.

## Contrato con POS

POS puede:

- vender a publico general;
- buscar por telefono, correo, codigo o nombre;
- crear alta express autorizada con identificador unico;
- mostrar cliente, lista y beneficios resueltos por backend;
- guardar snapshot en venta.

POS no debe:

- capturar ficha completa en cobro normal;
- decidir descuentos complejos en JS;
- fusionar duplicados;
- modificar datos sensibles sin permiso CRM;
- mezclar clientes ecommerce/legacy por coincidencia informal.

## Fases

### Fase 1 - Minimo POS

- Diagnostico read-only de tablas legacy y POS.
- Esquema CRM canonico propuesto sin ejecutar.
- Busqueda express por identificadores.
- Alta express con contrato CRM.
- Snapshot de cliente/lista/precio en venta.
- Relacion explicita entre `erp_clientes` UAT y CRM canonico si se migra.

### Fase 2 - CRM operativo

- Pantalla de listado y ficha completa.
- Contactos, direcciones, fiscales y consentimientos.
- Notas e historial.
- Deteccion de duplicados.
- Fusion controlada.
- Permisos `crm.ver`, `crm.crear`, `crm.editar`, `crm.fusionar`, `crm.auditoria`.

### Fase 3 - CRM comercial

- Segmentos.
- Condiciones comerciales y lista default.
- Campanas autorizadas por consentimiento.
- Promociones/cupones.
- Recompensas o monedero.

### Fase 4 - CRM avanzado

- Integracion ecommerce con vinculos externos.
- Scoring, frecuencia y alertas comerciales.
- Reportes de valor de cliente.
- Automatizaciones de seguimiento.
- Analisis de garantias/devoluciones por cliente.

## Politica de migracion

No se migran clientes legacy automaticamente.

Primero debe existir:

- conteo de fuentes;
- columnas disponibles;
- reglas de normalizacion;
- reporte de duplicados probables;
- propuesta de vinculos externos;
- respaldo externo;
- autorizacion textual.

## Auditoria inicial 2026-06-29

Fuentes detectadas en ambiente local:

- `crm_clientes`: fuente legacy con 244 clientes capturados por flujo anterior.
- `erp_clientes`: fuente POS/UAT minima con 1 cliente creado para validar cliente/precio.
- `erp_clientes_identificadores`: identificadores POS/UAT.
- `erp_ventas`: ventas nuevas con snapshot de cliente.
- `ecom_pedidos`: pedidos ecommerce/legacy que pueden contener relacion historica.

Resultado read-only inicial:

- Legacy `crm_clientes`: 244 registros.
- Legacy sin identificador util: 1 registro.
- Identificadores legacy duplicados detectados en muestra: 2.
- CRM canonico: tablas `crm_clientes_*` todavia no existen.
- Bloqueos correctos antes de migrar:
  - falta aplicar esquema CRM canonico;
  - falta tabla de vinculos externos;
  - faltan resolver o marcar duplicados de identificador.

Endpoints read-only preparados:

- `/crm/clientes_diagnostico_erp`
- `/crm/clientes_fuentes_auditar_erp`
- `/crm/clientes_migracion_plan_dryrun_erp`
- `/crm/clientes_duplicados_dryrun_erp`
- `/crm/clientes_migracion_preview_dryrun_erp`
- `/crm/clientes_buscar_express_dryrun_erp`
- `/crm/esquema_auditar_clientes_crm`
- `/crm/esquema_plan_clientes_crm`
- `/crm/esquema_actualizar_clientes_crm`

Pantalla read-only preparada:

- `/crm/clientes`

Esta pantalla consume diagnosticos, fuentes, duplicados, preview de migracion y plan de esquema. No ejecuta DDL ni migraciones.

Navegacion:

- El sidebar incluye grupo `CRM > Clientes`.
- El enlace depende de permiso `crm.ver`.
- Permisos `crm.*` sembrados el 2026-06-29 con token `CRM_PERMISOS_BASE` y respaldo externo previo.

Resultado post-apply permisos:

- `crm.ver`, `crm.crear`, `crm.editar`, `crm.fusionar`, `crm.auditoria` existen y estan activos.
- Rol `crm` creado si faltaba.
- Roles base vinculados: `direccion`, `administrador_erp`, `ventas`, `crm`.
- No se asignaron usuarios.
- No se tocaron clientes, ventas, POS, ecommerce ni esquema CRM.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.

Resultado post-apply esquema CRM:

- Esquema CRM Clientes creado el 2026-06-29 con token `CRM_CLIENTES_DDL_BASE`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- `ddl_pendientes=0` en `storage/uat/uat_crm_clientes_schema_readonly.php`.
- Tablas creadas y verificadas vacias: `crm_clientes_maestro`, `crm_clientes_identificadores`, `crm_clientes_contactos`, `crm_clientes_direcciones`, `crm_clientes_fiscales`, `crm_clientes_segmentos`, `crm_clientes_segmentos_rel`, `crm_clientes_consentimientos`, `crm_clientes_notas`, `crm_clientes_eventos`, `crm_clientes_vinculos_externos`, `crm_clientes_fusiones`.
- No se migraron clientes legacy, POS ni ecommerce.
- El plan de migracion sigue bloqueado por duplicados legacy, lo cual es correcto.

Scripts UAT/read-only preparados:

- `storage/uat/uat_crm_clientes_auditoria_readonly.php`
- `storage/uat/uat_crm_clientes_alta_rapida_dryrun_readonly.php`
- `storage/uat/uat_crm_clientes_listar_readonly.php`
- `storage/uat/uat_crm_clientes_ficha_readonly.php`
- `storage/uat/uat_crm_clientes_ficha_basica_dryrun_readonly.php`
- `storage/uat/uat_crm_clientes_ficha_basica_apply_authorized.php`
- `storage/uat/uat_crm_clientes_complemento_dryrun_readonly.php`
- `storage/uat/uat_crm_clientes_complemento_apply_authorized.php`
- `storage/uat/uat_crm_clientes_duplicado_revision_readonly.php`
- `storage/uat/uat_crm_permisos_readonly.php`
- `storage/uat/uat_crm_permisos_plan_readonly.php`
- `storage/uat/uat_crm_clientes_migracion_plan_readonly.php`
- `storage/uat/uat_crm_clientes_duplicados_readonly.php`
- `storage/uat/uat_crm_clientes_migracion_preview_readonly.php`
- `storage/uat/uat_crm_clientes_schema_readonly.php`
- `storage/uat/uat_crm_clientes_schema_apply_authorized.php`
- `storage/uat/uat_crm_clientes_alta_rapida_apply_authorized.php`
- `storage/uat/uat_crm_permisos_apply_authorized.php`

Regla: el primer apply futuro debe crear esquema CRM canonico; la migracion de clientes legacy sera un segundo apply separado, nunca mezclado con DDL.

Token reservado para DDL futuro:

- `CRM_CLIENTES_DDL_BASE`

Alcance del token: crear tablas canonicas `crm_clientes_*`. No autoriza migracion de clientes legacy, no autoriza vinculos externos reales y no toca ventas.

Token reservado para permisos:

- `CRM_PERMISOS_BASE`

Alcance del token: crear rol/permiso CRM y vincular permisos CRM a roles base `direccion`, `administrador_erp`, `ventas` y `crm`. No asigna usuarios ni toca clientes.

Documentos de operacion relacionados:

- `docs/crm_clientes_alta_express_solicitud_autorizacion.md`
- `docs/crm_clientes_ficha_basica_solicitud_autorizacion.md`
- `docs/crm_clientes_complementos_solicitud_autorizacion.md`
- `docs/crm_clientes_permisos_solicitud_autorizacion.md`
- `docs/crm_clientes_permisos_runbook_aplicacion.md`
- `docs/crm_clientes_permisos_plan_reversa.md`
- `docs/crm_clientes_schema_solicitud_autorizacion.md`
- `docs/crm_clientes_schema_runbook_aplicacion.md`
- `docs/crm_clientes_schema_plan_reversa.md`

Orden operativo recomendado:

1. Mantener migracion legacy bloqueada hasta resolver duplicados y reglas de vinculos externos.
2. Autorizar una alta express CRM controlada con token `CRM_CLIENTES_ALTA_EXPRESS` para validar escritura minima real.
3. Preparar migracion legacy como borrador/revision, separada de POS y con autorizacion propia.

Implementacion post-esquema:

- `ClientesCrm::altaRapidaDryRun` valida alta express contra `crm_clientes_*`.
- `ClientesCrm::altaRapidaCrearAutorizado` crea cliente express solo bajo flujo autorizado.
- `/crm/clientes_alta_rapida_dryrun_erp` expone dry-run CRM.
- `/crm/clientes_alta_rapida_crear_autorizado_erp` exige token `CRM_CLIENTES_ALTA_EXPRESS` y respaldo.
- `/ventas/pos_cliente_alta_rapida_dryrun_erp` ahora consume CRM canonico, no `erp_clientes`.
- `VentasErp` resuelve clientes POS contra `crm_clientes_maestro` e identificadores CRM para precio/prevalidacion.

Resultado post-apply alta express UAT:

- Alta express CRM creada el 2026-06-29 con token `CRM_CLIENTES_ALTA_EXPRESS`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Cliente creado:
  - `id_cliente_crm=1`
  - `codigo_cliente=CRM-POSUAT-20260628-0001`
  - `nombre_publico=Cliente Express UAT`
  - `calidad_datos=express`
- Identificador creado:
  - `tipo=telefono`
  - `valor_normalizado=3312345678`
  - `principal=1`
- Evento `alta_express` creado.
- No se creo consentimiento porque la autorizacion uso `consentimiento=0`.
- Verificacion posterior: el mismo identificador ya bloquea una segunda alta por coincidencia CRM.
- Conteos posteriores:
  - `crm_clientes_maestro=1`
  - `crm_clientes_identificadores=1`
  - `crm_clientes_eventos=1`
  - `crm_clientes_consentimientos=0`
  - `crm_clientes_vinculos_externos=0`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1

Resultado ficha CRM operativa:

- Ruta `/crm/cliente/{id_cliente_crm}` preparada para ficha completa fuera del POS.
- Endpoint `/crm/clientes_listar_erp` lista clientes CRM canonicos sin consultar legacy como fuente principal.
- Endpoint `/crm/cliente_consultar_erp?id_cliente_crm=...` consulta identidad, identificadores, contactos, direcciones, fiscales, consentimientos, notas, eventos y vinculos.
- Endpoint `/crm/cliente_basico_guardar_dryrun_erp` valida edicion basica sin escribir.
- Endpoint `/crm/cliente_basico_guardar_autorizado_erp` queda preparado con token `CRM_CLIENTES_FICHA_BASICA` y respaldo valido.
- Listado `/crm/clientes` ya muestra clientes CRM canonicos y enlace a ficha.
- Ficha del cliente UAT `id_cliente_crm=1` validada:
  - identificadores: 1
  - eventos: 1
  - contactos/direcciones/fiscales/notas/vinculos: 0
- Edicion basica queda en dry-run; el apply real sera otro flujo con respaldo, token y auditoria.
- Compuerta fuerte actual: guardar cambios reales de ficha basica requiere autorizacion `CRM_CLIENTES_FICHA_BASICA`.

Resultado complementos de ficha:

- Endpoint `/crm/cliente_complemento_guardar_dryrun_erp` valida contacto, direccion, fiscal o nota sin escribir.
- Endpoint `/crm/cliente_complemento_guardar_autorizado_erp` queda preparado con token `CRM_CLIENTES_COMPLEMENTO` y respaldo valido.
- Ficha `/crm/cliente/{id}` incluye formularios compactos para validar contacto, direccion, fiscal y nota.
- Scripts UAT dry-run validan los cuatro tipos de complemento.
- Script apply bloquea correctamente sin token/respaldo.
- Conteos posteriores a dry-run:
  - `crm_clientes_contactos=0`
  - `crm_clientes_direcciones=0`
  - `crm_clientes_fiscales=0`
  - `crm_clientes_notas=0`
  - `crm_clientes_eventos=1`
- Compuerta fuerte actual: crear complementos reales requiere autorizacion `CRM_CLIENTES_COMPLEMENTO`.

Resultado revision duplicados legacy:

- Endpoint `/crm/clientes_duplicado_revision_dryrun_erp?identificador=...` analiza un grupo duplicado sin marcar ni fusionar.
- Listado `/crm/clientes` permite abrir revision de un grupo duplicado desde la tabla.
- Script `storage/uat/uat_crm_clientes_duplicado_revision_readonly.php` preparado.
- Grupo `telefono:3322068429` revisado:
  - 14 items legacy.
  - recomendacion `revision_manual`.
  - motivo: nombres distintos o incompletos para el mismo identificador.
- Grupo `telefono:3338076456` revisado:
  - 2 items legacy.
  - recomendacion `revision_manual`.
  - motivo: nombres distintos o incompletos para el mismo identificador.
- Conteos posteriores:
  - `crm_clientes_maestro=1`
  - `crm_clientes_fusiones=0`
  - `crm_clientes_vinculos_externos=0`
- No se marco, no se fusiono y no se migro ningun cliente legacy.

Resultado borrador migracion legacy:

- Endpoint `/crm/clientes_migracion_borrador_dryrun_erp?offset=...&limite=...` preparado para simular lotes de migracion sin escribir.
- Script `storage/uat/uat_crm_clientes_migracion_borrador_readonly.php` preparado.
- Listado `/crm/clientes` incluye seccion "Borrador de lote legacy" con resumen y tabla de estados.
- Lote `offset=0`, `limite=5`:
  - total legacy: 244
  - migrables: 0
  - bloqueados por duplicado: 5
  - motivo principal: `telefono:3322068429` repetido; un registro tambien cruza con `telefono:3338076456`.
- Lote `offset=20`, `limite=5`:
  - total legacy: 244
  - migrables: 5
  - bloqueados por duplicado: 0
  - calidad propuesta: `basica`
  - vinculo externo propuesto con confianza `media`.
- Decision operativa: migracion real debe separar registros migrables de registros bloqueados por duplicado; los duplicados no deben entrar por apply masivo hasta tener resolucion manual o regla aprobada.
- Compuerta fuerte actual: migrar clientes legacy reales requiere autorizacion especifica distinta a alta express, ficha y complementos.
- Endpoint apply preparado: `/crm/clientes_migracion_aplicar_autorizado_erp`.
- Script apply preparado: `storage/uat/uat_crm_clientes_migracion_apply_authorized.php`.
- Apply bloquea correctamente sin token/respaldo.
- Reglas del apply:
  - solo acepta lote completo con estado `migrable_borrador`;
  - revalida identificadores existentes en CRM antes de insertar;
  - revalida que no exista vinculo externo activo para la misma fuente legacy;
  - crea cliente CRM, identificadores, vinculo externo y evento `migracion_legacy`;
  - no modifica tabla `crm_clientes` legacy;
  - no toca ventas, POS ni ecommerce.

Resultado migracion legacy autorizada lote 20-25:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=20`, `limite=5`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=21` -> `crm_clientes_maestro.id_cliente_crm=2`, `CRM-LEG-000021`
  - `crm_clientes.id_cliente=22` -> `crm_clientes_maestro.id_cliente_crm=3`, `CRM-LEG-000022`
  - `crm_clientes.id_cliente=23` -> `crm_clientes_maestro.id_cliente_crm=4`, `CRM-LEG-000023`
  - `crm_clientes.id_cliente=24` -> `crm_clientes_maestro.id_cliente_crm=5`, `CRM-LEG-000024`
  - `crm_clientes.id_cliente=25` -> `crm_clientes_maestro.id_cliente_crm=6`, `CRM-LEG-000025`
- Cada registro creo:
  - cliente canonico CRM;
  - identificador telefonico principal;
  - vinculo externo activo `legacy/crm_clientes/id_origen`;
  - evento `migracion_legacy`.
- Ficha verificada para `id_cliente_crm=2`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=6`
  - `crm_clientes_identificadores=6`
  - `crm_clientes_eventos=6`
  - `crm_clientes_vinculos_externos=5`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=5`
  - estado por fila: `bloqueado_vinculado`
- Ajuste tecnico posterior: el borrador ahora considera vinculos legacy activos e identificadores CRM activos para evitar mostrar como migrables registros ya migrados o ya existentes.
- Ajuste de calidad posterior: campos legacy de contacto solo se consideran migrables automaticamente si normalizan a `telefono` o `correo`; valores sueltos como `4` ya no se migran como `codigo` y pasan a revision.
- Siguiente lote candidato read-only:
  - `offset=25`, `limite=10`: `migrables=9`, `requieren_revision=1`; no aplicar completo porque incluye legacy `id_cliente=26` sin identificador util.
  - `offset=26`, `limite=9`: `migrables=9`, sin bloqueos ni revision; candidato limpio para siguiente autorizacion.

Resultado migracion legacy autorizada lote 27-36:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=26`, `limite=9`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=27` -> `crm_clientes_maestro.id_cliente_crm=7`, `CRM-LEG-000027`
  - `crm_clientes.id_cliente=28` -> `crm_clientes_maestro.id_cliente_crm=8`, `CRM-LEG-000028`
  - `crm_clientes.id_cliente=29` -> `crm_clientes_maestro.id_cliente_crm=9`, `CRM-LEG-000029`
  - `crm_clientes.id_cliente=30` -> `crm_clientes_maestro.id_cliente_crm=10`, `CRM-LEG-000030`
  - `crm_clientes.id_cliente=32` -> `crm_clientes_maestro.id_cliente_crm=11`, `CRM-LEG-000032`
  - `crm_clientes.id_cliente=33` -> `crm_clientes_maestro.id_cliente_crm=12`, `CRM-LEG-000033`
  - `crm_clientes.id_cliente=34` -> `crm_clientes_maestro.id_cliente_crm=13`, `CRM-LEG-000034`
  - `crm_clientes.id_cliente=35` -> `crm_clientes_maestro.id_cliente_crm=14`, `CRM-LEG-000035`
  - `crm_clientes.id_cliente=36` -> `crm_clientes_maestro.id_cliente_crm=15`, `CRM-LEG-000036`
- Ficha verificada para `id_cliente_crm=7`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=15`
  - `crm_clientes_identificadores=15`
  - `crm_clientes_eventos=15`
  - `crm_clientes_vinculos_externos=14`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=9`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=35`, `limite=10`
  - legacy `id_cliente` 37 al 46
  - `migrables=10`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Resultado migracion legacy autorizada lote 37-46:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=35`, `limite=10`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=37` -> `crm_clientes_maestro.id_cliente_crm=16`, `CRM-LEG-000037`
  - `crm_clientes.id_cliente=38` -> `crm_clientes_maestro.id_cliente_crm=17`, `CRM-LEG-000038`
  - `crm_clientes.id_cliente=39` -> `crm_clientes_maestro.id_cliente_crm=18`, `CRM-LEG-000039`
  - `crm_clientes.id_cliente=40` -> `crm_clientes_maestro.id_cliente_crm=19`, `CRM-LEG-000040`
  - `crm_clientes.id_cliente=41` -> `crm_clientes_maestro.id_cliente_crm=20`, `CRM-LEG-000041`
  - `crm_clientes.id_cliente=42` -> `crm_clientes_maestro.id_cliente_crm=21`, `CRM-LEG-000042`
  - `crm_clientes.id_cliente=43` -> `crm_clientes_maestro.id_cliente_crm=22`, `CRM-LEG-000043`
  - `crm_clientes.id_cliente=44` -> `crm_clientes_maestro.id_cliente_crm=23`, `CRM-LEG-000044`
  - `crm_clientes.id_cliente=45` -> `crm_clientes_maestro.id_cliente_crm=24`, `CRM-LEG-000045`
  - `crm_clientes.id_cliente=46` -> `crm_clientes_maestro.id_cliente_crm=25`, `CRM-LEG-000046`
- Ficha verificada para `id_cliente_crm=16`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=25`
  - `crm_clientes_identificadores=25`
  - `crm_clientes_eventos=25`
  - `crm_clientes_vinculos_externos=24`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=10`
  - estado por fila: `bloqueado_vinculado`
- Ajuste de calidad posterior: telefonos legacy con prefijo de tres o mas ceros se consideran sospechosos y pasan a revision, aunque tengan longitud numerica suficiente.
- Siguiente lote candidato read-only:
  - `offset=45`, `limite=10`: `migrables=9`, `requieren_revision=1`; no aplicar completo porque legacy `id_cliente=48` tiene telefono sospechoso `0000556644`.
  - `offset=47`, `limite=8`: `migrables=8`, sin bloqueos ni revision; candidato limpio para siguiente autorizacion.

Resultado migracion legacy autorizada lote 49-56:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=47`, `limite=8`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=49` -> `crm_clientes_maestro.id_cliente_crm=26`, `CRM-LEG-000049`
  - `crm_clientes.id_cliente=50` -> `crm_clientes_maestro.id_cliente_crm=27`, `CRM-LEG-000050`
  - `crm_clientes.id_cliente=51` -> `crm_clientes_maestro.id_cliente_crm=28`, `CRM-LEG-000051`
  - `crm_clientes.id_cliente=52` -> `crm_clientes_maestro.id_cliente_crm=29`, `CRM-LEG-000052`
  - `crm_clientes.id_cliente=53` -> `crm_clientes_maestro.id_cliente_crm=30`, `CRM-LEG-000053`
  - `crm_clientes.id_cliente=54` -> `crm_clientes_maestro.id_cliente_crm=31`, `CRM-LEG-000054`
  - `crm_clientes.id_cliente=55` -> `crm_clientes_maestro.id_cliente_crm=32`, `CRM-LEG-000055`
  - `crm_clientes.id_cliente=56` -> `crm_clientes_maestro.id_cliente_crm=33`, `CRM-LEG-000056`
- Ficha verificada para `id_cliente_crm=26`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=33`
  - `crm_clientes_identificadores=33`
  - `crm_clientes_eventos=33`
  - `crm_clientes_vinculos_externos=32`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=8`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=55`, `limite=10`
  - legacy `id_cliente` 57 al 66
  - `migrables=10`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Resultado migracion legacy autorizada lote 57-66:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=55`, `limite=10`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=57` -> `crm_clientes_maestro.id_cliente_crm=34`, `CRM-LEG-000057`
  - `crm_clientes.id_cliente=58` -> `crm_clientes_maestro.id_cliente_crm=35`, `CRM-LEG-000058`
  - `crm_clientes.id_cliente=59` -> `crm_clientes_maestro.id_cliente_crm=36`, `CRM-LEG-000059`
  - `crm_clientes.id_cliente=60` -> `crm_clientes_maestro.id_cliente_crm=37`, `CRM-LEG-000060`
  - `crm_clientes.id_cliente=61` -> `crm_clientes_maestro.id_cliente_crm=38`, `CRM-LEG-000061`
  - `crm_clientes.id_cliente=62` -> `crm_clientes_maestro.id_cliente_crm=39`, `CRM-LEG-000062`
  - `crm_clientes.id_cliente=63` -> `crm_clientes_maestro.id_cliente_crm=40`, `CRM-LEG-000063`
  - `crm_clientes.id_cliente=64` -> `crm_clientes_maestro.id_cliente_crm=41`, `CRM-LEG-000064`
  - `crm_clientes.id_cliente=65` -> `crm_clientes_maestro.id_cliente_crm=42`, `CRM-LEG-000065`
  - `crm_clientes.id_cliente=66` -> `crm_clientes_maestro.id_cliente_crm=43`, `CRM-LEG-000066`
- Ficha verificada para `id_cliente_crm=34`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=43`
  - `crm_clientes_identificadores=43`
  - `crm_clientes_eventos=43`
  - `crm_clientes_vinculos_externos=42`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=10`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=65`, `limite=10`: `migrables=9`, `requieren_revision=1`; no aplicar completo porque legacy `id_cliente=76` es `Default` sin identificador util.
  - `offset=65`, `limite=9`: `migrables=9`, sin bloqueos ni revision; candidato limpio para siguiente autorizacion.

Resultado migracion legacy autorizada lote 67-75:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=65`, `limite=9`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=67` -> `crm_clientes_maestro.id_cliente_crm=44`, `CRM-LEG-000067`
  - `crm_clientes.id_cliente=68` -> `crm_clientes_maestro.id_cliente_crm=45`, `CRM-LEG-000068`
  - `crm_clientes.id_cliente=69` -> `crm_clientes_maestro.id_cliente_crm=46`, `CRM-LEG-000069`
  - `crm_clientes.id_cliente=70` -> `crm_clientes_maestro.id_cliente_crm=47`, `CRM-LEG-000070`
  - `crm_clientes.id_cliente=71` -> `crm_clientes_maestro.id_cliente_crm=48`, `CRM-LEG-000071`
  - `crm_clientes.id_cliente=72` -> `crm_clientes_maestro.id_cliente_crm=49`, `CRM-LEG-000072`
  - `crm_clientes.id_cliente=73` -> `crm_clientes_maestro.id_cliente_crm=50`, `CRM-LEG-000073`
  - `crm_clientes.id_cliente=74` -> `crm_clientes_maestro.id_cliente_crm=51`, `CRM-LEG-000074`
  - `crm_clientes.id_cliente=75` -> `crm_clientes_maestro.id_cliente_crm=52`, `CRM-LEG-000075`
- Ficha verificada para `id_cliente_crm=44`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=52`
  - `crm_clientes_identificadores=52`
  - `crm_clientes_eventos=52`
  - `crm_clientes_vinculos_externos=51`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=9`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=75`, `limite=10`: `migrables=9`, `requieren_revision=1`; no aplicar completo porque legacy `id_cliente=81` no tiene identificador util.
  - `offset=75`, `limite=4`: `migrables=4`, sin bloqueos ni revision; candidato limpio para siguiente autorizacion.

Resultado migracion legacy autorizada lote 77-80:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=75`, `limite=4`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=77` -> `crm_clientes_maestro.id_cliente_crm=53`, `CRM-LEG-000077`
  - `crm_clientes.id_cliente=78` -> `crm_clientes_maestro.id_cliente_crm=54`, `CRM-LEG-000078`
  - `crm_clientes.id_cliente=79` -> `crm_clientes_maestro.id_cliente_crm=55`, `CRM-LEG-000079`
  - `crm_clientes.id_cliente=80` -> `crm_clientes_maestro.id_cliente_crm=56`, `CRM-LEG-000080`
- Ficha verificada para `id_cliente_crm=53`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=56`
  - `crm_clientes_identificadores=56`
  - `crm_clientes_eventos=56`
  - `crm_clientes_vinculos_externos=55`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=4`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - legacy `id_cliente=81` queda pendiente de revision manual por falta de identificador util.
  - `offset=80`, `limite=5`: `migrables=5`, sin bloqueos ni revision; candidato limpio para siguiente autorizacion.

Resultado migracion legacy autorizada lote 82-86:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=80`, `limite=5`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=82` -> `crm_clientes_maestro.id_cliente_crm=57`, `CRM-LEG-000082`
  - `crm_clientes.id_cliente=83` -> `crm_clientes_maestro.id_cliente_crm=58`, `CRM-LEG-000083`
  - `crm_clientes.id_cliente=84` -> `crm_clientes_maestro.id_cliente_crm=59`, `CRM-LEG-000084`
  - `crm_clientes.id_cliente=85` -> `crm_clientes_maestro.id_cliente_crm=60`, `CRM-LEG-000085`
  - `crm_clientes.id_cliente=86` -> `crm_clientes_maestro.id_cliente_crm=61`, `CRM-LEG-000086`
- Ficha verificada para `id_cliente_crm=57`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=61`
  - `crm_clientes_identificadores=61`
  - `crm_clientes_eventos=61`
  - `crm_clientes_vinculos_externos=60`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=5`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=85`, `limite=10`
  - legacy `id_cliente` 87 al 96
  - `migrables=10`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Resultado migracion legacy autorizada lote 87-96:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=85`, `limite=10`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=87` -> `crm_clientes_maestro.id_cliente_crm=62`, `CRM-LEG-000087`
  - `crm_clientes.id_cliente=88` -> `crm_clientes_maestro.id_cliente_crm=63`, `CRM-LEG-000088`
  - `crm_clientes.id_cliente=89` -> `crm_clientes_maestro.id_cliente_crm=64`, `CRM-LEG-000089`
  - `crm_clientes.id_cliente=90` -> `crm_clientes_maestro.id_cliente_crm=65`, `CRM-LEG-000090`
  - `crm_clientes.id_cliente=91` -> `crm_clientes_maestro.id_cliente_crm=66`, `CRM-LEG-000091`
  - `crm_clientes.id_cliente=92` -> `crm_clientes_maestro.id_cliente_crm=67`, `CRM-LEG-000092`
  - `crm_clientes.id_cliente=93` -> `crm_clientes_maestro.id_cliente_crm=68`, `CRM-LEG-000093`
  - `crm_clientes.id_cliente=94` -> `crm_clientes_maestro.id_cliente_crm=69`, `CRM-LEG-000094`
  - `crm_clientes.id_cliente=95` -> `crm_clientes_maestro.id_cliente_crm=70`, `CRM-LEG-000095`
  - `crm_clientes.id_cliente=96` -> `crm_clientes_maestro.id_cliente_crm=71`, `CRM-LEG-000096`
- Ficha verificada para `id_cliente_crm=62`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=71`
  - `crm_clientes_identificadores=71`
  - `crm_clientes_eventos=71`
  - `crm_clientes_vinculos_externos=70`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=10`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=95`, `limite=10`
  - legacy `id_cliente` 97 al 106
  - `migrables=10`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Resultado migracion legacy autorizada lote 97-106:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=95`, `limite=10`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=97` -> `crm_clientes_maestro.id_cliente_crm=72`, `CRM-LEG-000097`
  - `crm_clientes.id_cliente=98` -> `crm_clientes_maestro.id_cliente_crm=73`, `CRM-LEG-000098`
  - `crm_clientes.id_cliente=99` -> `crm_clientes_maestro.id_cliente_crm=74`, `CRM-LEG-000099`
  - `crm_clientes.id_cliente=100` -> `crm_clientes_maestro.id_cliente_crm=75`, `CRM-LEG-000100`
  - `crm_clientes.id_cliente=101` -> `crm_clientes_maestro.id_cliente_crm=76`, `CRM-LEG-000101`
  - `crm_clientes.id_cliente=102` -> `crm_clientes_maestro.id_cliente_crm=77`, `CRM-LEG-000102`
  - `crm_clientes.id_cliente=103` -> `crm_clientes_maestro.id_cliente_crm=78`, `CRM-LEG-000103`
  - `crm_clientes.id_cliente=104` -> `crm_clientes_maestro.id_cliente_crm=79`, `CRM-LEG-000104`
  - `crm_clientes.id_cliente=105` -> `crm_clientes_maestro.id_cliente_crm=80`, `CRM-LEG-000105`
  - `crm_clientes.id_cliente=106` -> `crm_clientes_maestro.id_cliente_crm=81`, `CRM-LEG-000106`
- Ficha verificada para `id_cliente_crm=72`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=81`
  - `crm_clientes_identificadores=81`
  - `crm_clientes_eventos=81`
  - `crm_clientes_vinculos_externos=80`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=10`
  - estado por fila: `bloqueado_vinculado`
- Ajuste de calidad posterior: telefonos legacy menores a 10 digitos se consideran incompletos y pasan a revision; ejemplo detectado `crm_clientes.id_cliente=109` con `640211569`.
- Siguiente lote candidato read-only:
  - `offset=105`, `limite=10`: `migrables=9`, `requieren_revision=1`; no aplicar completo porque legacy `id_cliente=109` tiene telefono incompleto.
  - `offset=105`, `limite=2`: `migrables=2`, legacy `id_cliente` 107 al 108, sin bloqueos ni revision.
  - `offset=108`, `limite=7`: `migrables=7`, legacy `id_cliente` 110 al 116, sin bloqueos ni revision.

Resultado migracion legacy autorizada lote 107-108:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=105`, `limite=2`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=107` -> `crm_clientes_maestro.id_cliente_crm=82`, `CRM-LEG-000107`
  - `crm_clientes.id_cliente=108` -> `crm_clientes_maestro.id_cliente_crm=83`, `CRM-LEG-000108`
- Ficha verificada para `id_cliente_crm=82`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=83`
  - `crm_clientes_identificadores=83`
  - `crm_clientes_eventos=83`
  - `crm_clientes_vinculos_externos=82`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=2`
  - estado por fila: `bloqueado_vinculado`

Resultado migracion legacy autorizada lote 110-116:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=108`, `limite=7`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=110` -> `crm_clientes_maestro.id_cliente_crm=84`, `CRM-LEG-000110`
  - `crm_clientes.id_cliente=111` -> `crm_clientes_maestro.id_cliente_crm=85`, `CRM-LEG-000111`
  - `crm_clientes.id_cliente=112` -> `crm_clientes_maestro.id_cliente_crm=86`, `CRM-LEG-000112`
  - `crm_clientes.id_cliente=113` -> `crm_clientes_maestro.id_cliente_crm=87`, `CRM-LEG-000113`
  - `crm_clientes.id_cliente=114` -> `crm_clientes_maestro.id_cliente_crm=88`, `CRM-LEG-000114`
  - `crm_clientes.id_cliente=115` -> `crm_clientes_maestro.id_cliente_crm=89`, `CRM-LEG-000115`
  - `crm_clientes.id_cliente=116` -> `crm_clientes_maestro.id_cliente_crm=90`, `CRM-LEG-000116`
- Ficha verificada para `id_cliente_crm=84`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=90`
  - `crm_clientes_identificadores=90`
  - `crm_clientes_eventos=90`
  - `crm_clientes_vinculos_externos=89`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=7`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=115`, `limite=10`: `migrables=9`, `requieren_revision=1`; no aplicar completo porque legacy `id_cliente=118` no tiene identificador util.
  - `offset=115`, `limite=1`: `migrables=1`, legacy `id_cliente=117`, sin bloqueos ni revision.
  - `offset=117`, `limite=8`: `migrables=8`, legacy `id_cliente` 119 al 126, sin bloqueos ni revision.

Resultado migracion legacy autorizada lote 119-126:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=117`, `limite=8`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=119` -> `crm_clientes_maestro.id_cliente_crm=91`, `CRM-LEG-000119`
  - `crm_clientes.id_cliente=120` -> `crm_clientes_maestro.id_cliente_crm=92`, `CRM-LEG-000120`
  - `crm_clientes.id_cliente=121` -> `crm_clientes_maestro.id_cliente_crm=93`, `CRM-LEG-000121`
  - `crm_clientes.id_cliente=122` -> `crm_clientes_maestro.id_cliente_crm=94`, `CRM-LEG-000122`
  - `crm_clientes.id_cliente=123` -> `crm_clientes_maestro.id_cliente_crm=95`, `CRM-LEG-000123`
  - `crm_clientes.id_cliente=124` -> `crm_clientes_maestro.id_cliente_crm=96`, `CRM-LEG-000124`
  - `crm_clientes.id_cliente=125` -> `crm_clientes_maestro.id_cliente_crm=97`, `CRM-LEG-000125`
  - `crm_clientes.id_cliente=126` -> `crm_clientes_maestro.id_cliente_crm=98`, `CRM-LEG-000126`
- Ficha verificada para `id_cliente_crm=91`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=98`
  - `crm_clientes_identificadores=98`
  - `crm_clientes_eventos=98`
  - `crm_clientes_vinculos_externos=97`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=8`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=125`, `limite=10`
  - legacy `id_cliente` 127 al 136
  - `migrables=10`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Resultado migracion legacy autorizada lote 127-136:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=125`, `limite=10`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=127` -> `crm_clientes_maestro.id_cliente_crm=99`, `CRM-LEG-000127`
  - `crm_clientes.id_cliente=128` -> `crm_clientes_maestro.id_cliente_crm=100`, `CRM-LEG-000128`
  - `crm_clientes.id_cliente=129` -> `crm_clientes_maestro.id_cliente_crm=101`, `CRM-LEG-000129`
  - `crm_clientes.id_cliente=130` -> `crm_clientes_maestro.id_cliente_crm=102`, `CRM-LEG-000130`
  - `crm_clientes.id_cliente=131` -> `crm_clientes_maestro.id_cliente_crm=103`, `CRM-LEG-000131`
  - `crm_clientes.id_cliente=132` -> `crm_clientes_maestro.id_cliente_crm=104`, `CRM-LEG-000132`
  - `crm_clientes.id_cliente=133` -> `crm_clientes_maestro.id_cliente_crm=105`, `CRM-LEG-000133`
  - `crm_clientes.id_cliente=134` -> `crm_clientes_maestro.id_cliente_crm=106`, `CRM-LEG-000134`
  - `crm_clientes.id_cliente=135` -> `crm_clientes_maestro.id_cliente_crm=107`, `CRM-LEG-000135`
  - `crm_clientes.id_cliente=136` -> `crm_clientes_maestro.id_cliente_crm=108`, `CRM-LEG-000136`
- Ficha verificada para `id_cliente_crm=99`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=108`
  - `crm_clientes_identificadores=108`
  - `crm_clientes_eventos=108`
  - `crm_clientes_vinculos_externos=107`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=10`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=135`, `limite=10`: `migrables=9`, `requieren_revision=1`, `sin_identificador=1`.
  - legacy `id_cliente=140` queda pendiente de revision por no tener identificador util.
  - Para continuar sin mezclar revision manual, partir autorizacion en `offset=135`, `limite=3` para legacy `id_cliente` 137 al 139, y despues `offset=139`, `limite=6` para legacy `id_cliente` 141 al 146.

Resultado migracion legacy autorizada lote 137-139:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=135`, `limite=3`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=137` -> `crm_clientes_maestro.id_cliente_crm=109`, `CRM-LEG-000137`
  - `crm_clientes.id_cliente=138` -> `crm_clientes_maestro.id_cliente_crm=110`, `CRM-LEG-000138`
  - `crm_clientes.id_cliente=139` -> `crm_clientes_maestro.id_cliente_crm=111`, `CRM-LEG-000139`
- Ficha verificada para `id_cliente_crm=109`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=111`
  - `crm_clientes_identificadores=111`
  - `crm_clientes_eventos=111`
  - `crm_clientes_vinculos_externos=110`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=3`
  - estado por fila: `bloqueado_vinculado`
- Pendiente de revision manual:
  - `crm_clientes.id_cliente=140`: sin identificador util.
- Siguiente lote candidato read-only:
  - `offset=139`, `limite=6`
  - legacy `id_cliente` 141 al 146
  - `migrables=6`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Resultado migracion legacy autorizada lote 141-146:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=139`, `limite=6`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=141` -> `crm_clientes_maestro.id_cliente_crm=112`, `CRM-LEG-000141`
  - `crm_clientes.id_cliente=142` -> `crm_clientes_maestro.id_cliente_crm=113`, `CRM-LEG-000142`
  - `crm_clientes.id_cliente=143` -> `crm_clientes_maestro.id_cliente_crm=114`, `CRM-LEG-000143`
  - `crm_clientes.id_cliente=144` -> `crm_clientes_maestro.id_cliente_crm=115`, `CRM-LEG-000144`
  - `crm_clientes.id_cliente=145` -> `crm_clientes_maestro.id_cliente_crm=116`, `CRM-LEG-000145`
  - `crm_clientes.id_cliente=146` -> `crm_clientes_maestro.id_cliente_crm=117`, `CRM-LEG-000146`
- Ficha verificada para `id_cliente_crm=112`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=117`
  - `crm_clientes_identificadores=117`
  - `crm_clientes_eventos=117`
  - `crm_clientes_vinculos_externos=116`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=6`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=145`, `limite=10`
  - legacy `id_cliente` 147 al 156
  - `migrables=10`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Resultado migracion legacy autorizada lote 147-156:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=145`, `limite=10`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=147` -> `crm_clientes_maestro.id_cliente_crm=118`, `CRM-LEG-000147`
  - `crm_clientes.id_cliente=148` -> `crm_clientes_maestro.id_cliente_crm=119`, `CRM-LEG-000148`
  - `crm_clientes.id_cliente=149` -> `crm_clientes_maestro.id_cliente_crm=120`, `CRM-LEG-000149`
  - `crm_clientes.id_cliente=150` -> `crm_clientes_maestro.id_cliente_crm=121`, `CRM-LEG-000150`
  - `crm_clientes.id_cliente=151` -> `crm_clientes_maestro.id_cliente_crm=122`, `CRM-LEG-000151`
  - `crm_clientes.id_cliente=152` -> `crm_clientes_maestro.id_cliente_crm=123`, `CRM-LEG-000152`
  - `crm_clientes.id_cliente=153` -> `crm_clientes_maestro.id_cliente_crm=124`, `CRM-LEG-000153`
  - `crm_clientes.id_cliente=154` -> `crm_clientes_maestro.id_cliente_crm=125`, `CRM-LEG-000154`
  - `crm_clientes.id_cliente=155` -> `crm_clientes_maestro.id_cliente_crm=126`, `CRM-LEG-000155`
  - `crm_clientes.id_cliente=156` -> `crm_clientes_maestro.id_cliente_crm=127`, `CRM-LEG-000156`
- Ficha verificada para `id_cliente_crm=118`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=127`
  - `crm_clientes_identificadores=127`
  - `crm_clientes_eventos=127`
  - `crm_clientes_vinculos_externos=126`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=10`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=155`, `limite=10`
  - legacy `id_cliente` 157 al 166
  - `migrables=10`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Resultado migracion legacy autorizada lote 157-166:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=155`, `limite=10`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=157` -> `crm_clientes_maestro.id_cliente_crm=128`, `CRM-LEG-000157`
  - `crm_clientes.id_cliente=158` -> `crm_clientes_maestro.id_cliente_crm=129`, `CRM-LEG-000158`
  - `crm_clientes.id_cliente=159` -> `crm_clientes_maestro.id_cliente_crm=130`, `CRM-LEG-000159`
  - `crm_clientes.id_cliente=160` -> `crm_clientes_maestro.id_cliente_crm=131`, `CRM-LEG-000160`
  - `crm_clientes.id_cliente=161` -> `crm_clientes_maestro.id_cliente_crm=132`, `CRM-LEG-000161`
  - `crm_clientes.id_cliente=162` -> `crm_clientes_maestro.id_cliente_crm=133`, `CRM-LEG-000162`
  - `crm_clientes.id_cliente=163` -> `crm_clientes_maestro.id_cliente_crm=134`, `CRM-LEG-000163`
  - `crm_clientes.id_cliente=164` -> `crm_clientes_maestro.id_cliente_crm=135`, `CRM-LEG-000164`
  - `crm_clientes.id_cliente=165` -> `crm_clientes_maestro.id_cliente_crm=136`, `CRM-LEG-000165`
  - `crm_clientes.id_cliente=166` -> `crm_clientes_maestro.id_cliente_crm=137`, `CRM-LEG-000166`
- Ficha verificada para `id_cliente_crm=128`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=137`
  - `crm_clientes_identificadores=137`
  - `crm_clientes_eventos=137`
  - `crm_clientes_vinculos_externos=136`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=10`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=165`, `limite=10`
  - legacy `id_cliente` 167 al 176
  - `migrables=10`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Resultado migracion legacy autorizada lote 167-176:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=165`, `limite=10`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=167` -> `crm_clientes_maestro.id_cliente_crm=138`, `CRM-LEG-000167`
  - `crm_clientes.id_cliente=168` -> `crm_clientes_maestro.id_cliente_crm=139`, `CRM-LEG-000168`
  - `crm_clientes.id_cliente=169` -> `crm_clientes_maestro.id_cliente_crm=140`, `CRM-LEG-000169`
  - `crm_clientes.id_cliente=170` -> `crm_clientes_maestro.id_cliente_crm=141`, `CRM-LEG-000170`
  - `crm_clientes.id_cliente=171` -> `crm_clientes_maestro.id_cliente_crm=142`, `CRM-LEG-000171`
  - `crm_clientes.id_cliente=172` -> `crm_clientes_maestro.id_cliente_crm=143`, `CRM-LEG-000172`
  - `crm_clientes.id_cliente=173` -> `crm_clientes_maestro.id_cliente_crm=144`, `CRM-LEG-000173`
  - `crm_clientes.id_cliente=174` -> `crm_clientes_maestro.id_cliente_crm=145`, `CRM-LEG-000174`
  - `crm_clientes.id_cliente=175` -> `crm_clientes_maestro.id_cliente_crm=146`, `CRM-LEG-000175`
  - `crm_clientes.id_cliente=176` -> `crm_clientes_maestro.id_cliente_crm=147`, `CRM-LEG-000176`
- Ficha verificada para `id_cliente_crm=138`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=147`
  - `crm_clientes_identificadores=147`
  - `crm_clientes_eventos=147`
  - `crm_clientes_vinculos_externos=146`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=10`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=175`, `limite=10`
  - legacy `id_cliente` 177 al 186
  - `migrables=10`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Resultado migracion legacy autorizada lote 177-186:

- Fecha: 2026-06-29.
- Token usado: `CRM_CLIENTES_MIGRACION_LEGACY`.
- Respaldo usado como referencia: `C:\respaldos\panel_artianilocal_2026-06-29_antes_crm_permisos.sql`.
- Lote autorizado: `offset=175`, `limite=10`.
- Registros legacy migrados:
  - `crm_clientes.id_cliente=177` -> `crm_clientes_maestro.id_cliente_crm=148`, `CRM-LEG-000177`
  - `crm_clientes.id_cliente=178` -> `crm_clientes_maestro.id_cliente_crm=149`, `CRM-LEG-000178`
  - `crm_clientes.id_cliente=179` -> `crm_clientes_maestro.id_cliente_crm=150`, `CRM-LEG-000179`
  - `crm_clientes.id_cliente=180` -> `crm_clientes_maestro.id_cliente_crm=151`, `CRM-LEG-000180`
  - `crm_clientes.id_cliente=181` -> `crm_clientes_maestro.id_cliente_crm=152`, `CRM-LEG-000181`
  - `crm_clientes.id_cliente=182` -> `crm_clientes_maestro.id_cliente_crm=153`, `CRM-LEG-000182`
  - `crm_clientes.id_cliente=183` -> `crm_clientes_maestro.id_cliente_crm=154`, `CRM-LEG-000183`
  - `crm_clientes.id_cliente=184` -> `crm_clientes_maestro.id_cliente_crm=155`, `CRM-LEG-000184`
  - `crm_clientes.id_cliente=185` -> `crm_clientes_maestro.id_cliente_crm=156`, `CRM-LEG-000185`
  - `crm_clientes.id_cliente=186` -> `crm_clientes_maestro.id_cliente_crm=157`, `CRM-LEG-000186`
- Ficha verificada para `id_cliente_crm=148`:
  - identificadores: 1
  - eventos: 1
  - vinculos externos: 1
- Conteos posteriores:
  - `crm_clientes_maestro=157`
  - `crm_clientes_identificadores=157`
  - `crm_clientes_eventos=157`
  - `crm_clientes_vinculos_externos=156`
  - `crm_clientes` legacy sigue en 244
  - `erp_clientes` sigue en 1
- Borrador posterior del mismo lote:
  - `migrables=0`
  - `bloqueados_vinculo=10`
  - estado por fila: `bloqueado_vinculado`
- Siguiente lote candidato read-only:
  - `offset=185`, `limite=10`
  - legacy `id_cliente` 187 al 196
  - `migrables=10`
  - sin bloqueos, sin revision, sin identificadores faltantes.

Decision operativa: pausar migracion legacy masiva

- Fecha: 2026-06-29.
- Decision del dueno: los clientes legacy no eran una base comercial formal; muchos pedidos se cerraban por WhatsApp desde carrito informal y el contacto util quedaba fuera del registro legacy.
- Criterio: no invertir mas tiempo en migrar clientes antiguos salvo casos puntuales con contacto realmente rescatable o trazabilidad necesaria.
- Estado de lo ya migrado:
  - queda como historico/auditoria CRM con vinculo externo legacy;
  - no debe tratarse como base comercial limpia;
  - no debe alimentar campanas, recompensas ni segmentacion sin revision posterior.
- Nuevo enfoque:
  - CRM empieza desde cero para clientes reales nuevos;
  - POS debe consumir CRM canonico;
  - POS debe poder vender a publico general sin cliente;
  - cuando el cliente quiera identificarse, POS debe buscar por telefono/correo/codigo/nombre y crear alta express CRM con identificador unico;
  - venta real debe guardar `id_cliente_crm` y snapshot de cliente CRM.

Preparacion POS/CRM verificada:

- `erp_ventas` ya tiene columnas CRM:
  - `id_cliente_crm`
  - `cliente_codigo_snapshot`
  - `cliente_origen_snapshot`
  - `cliente_snapshot`
- `erp_ventas_excepciones_comerciales` ya tiene columnas CRM:
  - `id_cliente_crm`
  - `cliente_codigo_snapshot`
  - `cliente_nombre_snapshot`
  - `cliente_identificador_snapshot`
  - `cliente_origen_snapshot`
- Tablas base presentes:
  - CRM: `crm_clientes_maestro`, `crm_clientes_identificadores`, `crm_clientes_contactos`, `crm_clientes_consentimientos`, `crm_clientes_eventos`
  - POS/Ventas: `erp_pos_cajas`, `erp_pos_turnos`, `erp_ventas`, `erp_ventas_detalle`, `erp_ventas_pagos`
- Pendiente tecnico inmediato:
  - dejar de mostrar alta express como solo dry-run en POS;
  - conectar boton POS de alta express a endpoint autorizado cuando el usuario confirme;
  - validar que confirmacion real guarde snapshot CRM en `erp_ventas`;
  - si no existe confirmacion real autorizada, implementarla despues de auditar flujo de caja/inventario.

Avance POS/CRM sin escritura:

- Fecha: 2026-06-29.
- `public/assets/js/custom/apps/erp/ventas/pos.js` ya conserva `cliente_crm` por cuenta local POS.
- La UI puede seleccionar un cliente CRM encontrado por resolucion/alta dry-run y guardarlo en la cuenta activa.
- Si el cajero cambia manualmente nombre o telefono, la seleccion CRM se limpia para evitar cobrar con cliente equivocado.
- Las prevalidaciones de precio, excepcion, folio, atencion y confirmacion dry-run ya envian el cliente CRM seleccionado como `id_cliente` al contrato actual de `VentasErp`, que internamente resuelve contra `crm_clientes_maestro`.
- `VentasErp::confirmarVentaPosReal` ya consulta `crm_clientes_maestro` al confirmar venta normal y arma snapshot CRM cuando `id_cliente` corresponde a cliente CRM seleccionado.
- El boton POS `Cobrar` ya esta conectado a `/ventas/pos_confirmar_erp` con confirmacion del operador; no usa tokens CRM en navegador.
- Pendiente:
  - renombrar contrato backend de `id_cliente` a `id_cliente_crm` en una refactorizacion controlada;
  - ejecutar UAT controlado de cobro real con cliente CRM, caja y turno abiertos;
  - verificar en BD que `erp_ventas.id_cliente_crm`, `cliente_codigo_snapshot`, `cliente_origen_snapshot` y `cliente_snapshot` quedan poblados;
  - cerrar alta express real desde POS con confirmacion operativa, no con token expuesto al navegador.

Avance CRM ficha robusta sin escritura:

- Fecha: 2026-06-29.
- Se agrega evaluacion `calidad_operativa` en la ficha CRM canonica.
- La evaluacion no escribe BD y no migra legacy; solo interpreta la ficha consultada.
- Criterios iniciales:
  - nombre publico definido;
  - identificador activo para busqueda rapida POS/CRM;
  - contacto util: telefono, WhatsApp o correo;
  - permiso operativo de contacto;
  - consentimiento comercial/operativo cuando aplique;
  - direcciones, datos fiscales y notas como enriquecimiento;
  - aviso cuando el cliente proviene de legacy o tiene vinculo legacy.
- Niveles:
  - `incompleta`: no debe usarse como cliente confiable;
  - `basica_pos`: puede identificarse en POS, pero requiere enriquecer ficha;
  - `operativa`: sirve para venta, postventa y contacto controlado;
  - `comercial`: puede entrar a procesos comerciales siempre que no dependa de legacy sin revision.
- La vista de ficha muestra puntaje, banderas POS/contacto/comercial, pendientes, avisos y fortalezas.
- La consola de Clientes se reenfoca a clientes nuevos reales; legacy queda visible solo como auditoria historica y no como objetivo de migracion masiva.
- El listado principal de clientes canonicos agrega `calidad_operativa_resumen` sin cargar ficha completa:
  - bandera POS si tiene identificador activo;
  - bandera Contacto si tiene contacto util y permiso operativo;
  - bandera Comercial si ademas tiene consentimiento y no depende de legacy;
  - pendiente principal para priorizar captura o revision.
- `consentimiento` queda integrado como complemento CRM:
  - dry-run desde ficha sin escribir BD;
  - apply preparado por `/crm/cliente_complemento_guardar_autorizado_erp`;
  - requiere token fuerte `CRM_CLIENTES_COMPLEMENTO` y respaldo valido;
  - registra evento `complemento_creado` igual que contacto, direccion, fiscal y nota.
- Se agrega cola read-only de calidad operativa:
  - endpoint `/crm/clientes_calidad_cola_erp`;
  - vista en consola CRM Clientes;
  - resume pendientes de POS, contacto, permiso, consentimiento y revision legacy;
  - no crea tareas persistentes, no escribe BD y no migra legacy.

Avance CRM seguimiento/tareas sin escritura:

- Fecha: 2026-06-29.
- Se prepara DDL separado para seguimiento operativo:
  - `crm_clientes_interacciones`;
  - `crm_clientes_tareas`.
- Endpoints preparados:
  - plan read-only `/crm/esquema_plan_clientes_seguimiento_crm`;
  - apply autorizado `/crm/esquema_actualizar_clientes_seguimiento_crm`.
- Token fuerte aplicado: `CRM_CLIENTES_SEGUIMIENTO_DDL`.
- Alcance del token:
  - solo crea tablas de seguimiento;
  - no crea tareas reales;
  - no modifica clientes;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- Documento de autorizacion:
  - `docs/crm_clientes_seguimiento_schema_solicitud_autorizacion.md`.
- Siguiente fase despues de DDL:
  - crear dry-run de tarea desde cola de calidad;
  - crear apply autorizado para una tarea puntual;
  - enlazar tareas con notificaciones SYS solo cuando el dominio de notificaciones lo permita.

Aplicacion DDL CRM seguimiento:

- Fecha: 2026-06-30.
- Respaldo generado y usado:
  - `C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_seguimiento.sql`.
- Script:
  - `storage/uat/uat_crm_clientes_seguimiento_schema_apply_authorized.php`.
- Token usado:
  - `CRM_CLIENTES_SEGUIMIENTO_DDL`.
- Resultado:
  - `crm_clientes_interacciones` existe con `0` filas;
  - `crm_clientes_tareas` existe con `0` filas.
- Alcance respetado:
  - no crea tareas reales;
  - no crea interacciones reales;
  - no modifica clientes;
  - no crea notificaciones SYS;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- Verificacion:
  - ficha CRM read-only de cliente `1` responde correctamente;
  - listado CRM read-only responde correctamente;
  - apply es idempotente: una segunda ejecucion detecta tablas existentes y mantiene `0` filas.
- Siguiente paso CRM:
  - ejecutar dry-run de una interaccion real o tarea de seguimiento para cliente nuevo/UAT;
  - apply real requiere autorizacion fuerte separada con token `CRM_CLIENTES_INTERACCION` o `CRM_CLIENTES_TAREA`.

Avance CRM tareas dry-run:

- Fecha: 2026-06-30.
- Se agrega contrato dry-run para tarea de seguimiento:
  - endpoint `/crm/cliente_tarea_dryrun_erp`;
  - metodo `ClientesCrm::tareaSeguimientoDryRun`.
- La cola de calidad permite validar una tarea sugerida por cliente sin crearla.
- Reglas:
  - valida cliente CRM;
  - valida tipo, prioridad, titulo y fecha si se envia;
  - avisa si `crm_clientes_tareas` aun no existe;
  - no inserta BD;
  - no crea notificaciones SYS.
- Este dry-run queda listo antes del DDL para probar contrato y UX sin autorizacion fuerte.

Avance CRM tareas apply preparado:

- Fecha: 2026-06-30.
- Se prepara apply autorizado para crear una tarea CRM real:
  - endpoint `/crm/cliente_tarea_crear_autorizado_erp`;
  - metodo `ClientesCrm::tareaSeguimientoCrearAutorizado`;
  - token fuerte `CRM_CLIENTES_TAREA`.
- Reglas del apply:
  - requiere respaldo valido;
  - requiere tabla `crm_clientes_tareas` creada previamente;
  - reusa `tareaSeguimientoDryRun`;
  - crea solo una tarea pendiente;
  - registra evento `tarea_creada` si existe `crm_clientes_eventos`;
  - no modifica datos del cliente;
  - no crea notificaciones SYS;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- Documento de autorizacion:
  - `docs/crm_clientes_tareas_solicitud_autorizacion.md`.

Preparacion UAT CRM tareas:

- Fecha: 2026-06-30.
- Se agregan scripts:
  - `storage/uat/uat_crm_clientes_tarea_dryrun_readonly.php`;
  - `storage/uat/uat_crm_clientes_tarea_apply_authorized.php`.
- Validaciones:
  - el apply rechaza placeholders en respaldo y datos operativos;
  - no se crea tarea cuando la autorizacion trae `[ID_CLIENTE_CRM]`, `[TIPO]`, `[PRIORIDAD]` o `[TITULO]`;
  - dry-run UAT para cliente `1`, tipo `calidad_datos`, prioridad `normal`, titulo `Completar contacto CRM UAT` devuelve `puede_guardar=true`;
  - no se escribio BD.
- Siguiente paso:
  - recibir autorizacion fuerte con valores concretos para crear una tarea real;
  - si el respaldo viene como placeholder, generar respaldo externo nuevo antes del apply.

Avance CRM bandeja de tareas read-only:

- Fecha: 2026-06-30.
- Se agrega listado read-only de tareas:
  - endpoint `/crm/clientes_tareas_listar_erp`;
  - metodo `ClientesCrm::tareasSeguimientoListar`;
  - panel `Tareas de seguimiento` en consola CRM Clientes.
- Si `crm_clientes_tareas` no existe, la bandeja muestra aviso de DDL pendiente.
- Si existe, lista tareas por prioridad, vencimiento y cliente.
- No crea, cierra, cancela, reasigna ni modifica tareas.

Avance CRM ciclo de vida de tareas:

- Fecha: 2026-06-30.
- Se agrega contrato para cambiar estatus de una tarea existente:
  - endpoint dry-run `/crm/cliente_tarea_estatus_dryrun_erp`;
  - metodo `ClientesCrm::tareaEstatusDryRun`;
  - endpoint apply `/crm/cliente_tarea_estatus_autorizado_erp`;
  - metodo `ClientesCrm::tareaEstatusAutorizado`;
  - token fuerte `CRM_CLIENTES_TAREA_ESTATUS`.
- Estatus destino iniciales:
  - `en_proceso`;
  - `cerrada`;
  - `cancelada`.
- Reglas:
  - requiere tabla `crm_clientes_tareas`;
  - requiere tarea existente y no cerrada/cancelada previamente;
  - cierre/cancelacion requieren resultado;
  - actualiza solo la tarea indicada;
  - registra evento `tarea_estatus` si existe `crm_clientes_eventos`;
  - no modifica datos del cliente;
  - no crea interacciones automaticamente;
  - no crea notificaciones SYS;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- Documento de autorizacion actualizado:
  - `docs/crm_clientes_tareas_solicitud_autorizacion.md`.

Preparacion UAT CRM estatus de tareas:

- Fecha: 2026-06-30.
- Se agregan scripts:
  - `storage/uat/uat_crm_clientes_tarea_estatus_dryrun_readonly.php`;
  - `storage/uat/uat_crm_clientes_tarea_estatus_apply_authorized.php`.
- Validaciones:
  - el apply rechaza placeholders en respaldo y datos operativos;
  - dry-run contra tarea inexistente `999999` bloquea correctamente con `Tarea CRM no encontrada`;
  - bandeja read-only confirma `0` tareas y `requiere_ddl_seguimiento=false`;
  - verificador `storage/uat/uat_crm_clientes_seguimiento_post_apply_readonly.php` confirma `0` tareas y `0` interacciones, global y para cliente `1`;
  - no se escribio BD.
- Siguiente paso:
  - crear una tarea real con autorizacion `CRM_CLIENTES_TAREA`;
  - luego cambiar estatus con autorizacion `CRM_CLIENTES_TAREA_ESTATUS`.

Avance UX CRM Seguimiento dedicado:

- Fecha: 2026-06-30.
- Se crea pantalla dedicada:
  - ruta `/crm/seguimiento`;
  - vista `app/vistas/paginas/apps/crm/seguimiento/index.php`;
  - JS `public/assets/js/custom/apps/crm/seguimiento/index.js`.
- El sidebar `CRM > Seguimiento` ahora apunta a `/crm/seguimiento`.
- Capacidades read-only:
  - KPIs de tareas pendientes, vencidas y alta prioridad;
  - filtro de tareas por estatus;
  - listado de tareas con enlace a ficha del cliente;
  - interacciones recientes.
- Reglas:
  - no crea tareas;
  - no cambia estatus;
  - no crea interacciones;
  - no modifica clientes;
  - no crea notificaciones SYS.
- Siguiente paso UX:
  - cuando exista al menos una tarea real, validar ciclo visual `pendiente -> en_proceso -> cerrada`;
  - despues disenar acciones controladas con dry-run visible antes de aplicar.

Avance UX CRM Recompensas dedicado:

- Fecha: 2026-06-30.
- Se crea pantalla dedicada:
  - ruta `/crm/recompensas`;
  - vista `app/vistas/paginas/apps/crm/recompensas/index.php`;
  - JS `public/assets/js/custom/apps/crm/recompensas/index.js`.
- El sidebar `CRM > Recompensas` ahora apunta a `/crm/recompensas`.
- Se agrega endpoint read-only:
  - `/crm/clientes_recompensas_detalle_erp`;
  - metodo `ClientesCrm::detalleRecompensasClientes`.
- Capacidades read-only:
  - KPIs de programas, cuentas, saldo total y movimientos;
  - tabla de programas;
  - tabla de cuentas con enlace a ficha de cliente;
  - movimientos recientes;
  - elegibilidad y bloqueos.
- Verificacion CLI:
  - programas activos `1`;
  - cuentas activas `1`;
  - movimientos aplicados `1`;
  - saldo total `10`;
  - `no_escribe_bd=true`.
- Reglas:
  - no otorga puntos;
  - no redime puntos;
  - no crea cuentas;
  - no modifica clientes;
  - no conecta POS/Ventas.
- Siguiente paso UX:
  - antes de conectar POS, disenar acciones con dry-run visible para acumulacion/redencion;
  - mantener apply real detras de autorizacion fuerte `CRM_CLIENTES_RECOMPENSAS_MOVIMIENTO`.

Avance CRM interacciones operativas:

- Fecha: 2026-06-30.
- Se agrega contrato para registrar interacciones reales del cliente:
  - endpoint dry-run `/crm/cliente_interaccion_dryrun_erp`;
  - metodo `ClientesCrm::interaccionDryRun`;
  - endpoint read-only `/crm/clientes_interacciones_listar_erp`;
  - metodo `ClientesCrm::interaccionesListar`;
  - ficha CRM muestra y valida interacciones desde Historial.
- Tipos iniciales:
  - contacto, seguimiento, postventa, comercial, garantia, apartado, devolucion, calidad_datos, otro.
- Canales iniciales:
  - WhatsApp, telefono, correo, presencial, sistema, otro.
- Resultados iniciales:
  - registrado, contactado, sin_respuesta, pendiente, resuelto, no_procede, otro.
- Apply autorizado preparado:
  - endpoint `/crm/cliente_interaccion_crear_autorizado_erp`;
  - metodo `ClientesCrm::interaccionCrearAutorizada`;
  - token fuerte `CRM_CLIENTES_INTERACCION`.
- Reglas del apply:
  - requiere respaldo valido;
  - requiere tabla `crm_clientes_interacciones` creada previamente con `CRM_CLIENTES_SEGUIMIENTO_DDL`;
  - reusa `interaccionDryRun`;
  - crea solo una interaccion;
  - registra evento `interaccion_creada` si existe `crm_clientes_eventos`;
  - no modifica datos del cliente;
  - no crea, cierra ni reasigna tareas;
  - no crea notificaciones SYS;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- Documento de autorizacion:
  - `docs/crm_clientes_interacciones_solicitud_autorizacion.md`.

Preparacion UAT CRM interacciones:

- Fecha: 2026-06-30.
- Se agregan scripts:
  - `storage/uat/uat_crm_clientes_interaccion_dryrun_readonly.php`;
  - `storage/uat/uat_crm_clientes_interaccion_apply_authorized.php`.
- Validaciones:
  - el apply rechaza placeholders en respaldo y datos operativos;
  - no se crea interaccion cuando la autorizacion trae `[ID_CLIENTE_CRM]`, `[TIPO]`, `[CANAL]` o `[RESUMEN]`;
  - dry-run UAT para cliente `1`, tipo `seguimiento`, canal `whatsapp`, resumen `UAT seguimiento CRM sin escritura` devuelve `puede_guardar=true`;
  - no se escribio BD.
- Siguiente paso:
  - recibir autorizacion fuerte con valores concretos para crear una interaccion real;
  - si el respaldo viene como placeholder, generar respaldo externo nuevo antes del apply.

Avance CRM comercial base:

- Fecha: 2026-06-30.
- Se separa la capa comercial de Clientes sin moverla a POS:
  - CRM define segmento, condiciones y elegibilidad del cliente;
  - Ventas/POS solo debe consumir condiciones aprobadas;
  - listas de precios siguen perteneciendo a Ventas/ERP, aunque CRM pueda guardar una preferencia/default por cliente.
- Se prepara DDL comercial separado:
  - tabla propuesta `crm_clientes_condiciones`;
  - endpoint plan `/crm/esquema_plan_clientes_comercial_crm`;
  - endpoint apply `/crm/esquema_actualizar_clientes_comercial_crm`;
  - token fuerte `CRM_CLIENTES_COMERCIAL_DDL`.
- La tabla propuesta permite:
  - lista default de referencia;
  - credito futuro controlado;
  - permisos de recompensas;
  - permisos de garantia extendida;
  - bloqueo comercial;
  - preferencias JSON;
  - restricciones operativas.
- Se agrega resumen comercial read-only:
  - endpoint `/crm/clientes_comercial_resumen_erp`;
  - metodo `ClientesCrm::resumenComercialClientes`;
  - mide clientes con segmento default, lista default, segmentos activos, relaciones activas y condiciones comerciales;
  - no escribe BD.
- Se prepara asignacion de segmento:
  - dry-run `/crm/cliente_segmento_dryrun_erp`;
  - apply `/crm/cliente_segmento_asignar_autorizado_erp`;
  - token fuerte `CRM_CLIENTES_SEGMENTO`.
- Reglas de asignacion:
  - requiere cliente CRM existente;
  - requiere segmento CRM activo;
  - bloquea relacion duplicada activa;
  - puede marcar principal;
  - puede actualizar `id_segmento_default`;
  - registra evento `segmento_asignado` si existe `crm_clientes_eventos`;
  - no modifica listas de precios;
  - no crea recompensas;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- Documento de autorizacion:
  - `docs/crm_clientes_comercial_solicitud_autorizacion.md`.

Avance CRM reportes operativos read-only:

- Fecha: 2026-06-30.
- Se agrega reporte operativo sin escritura:
  - endpoint `/crm/clientes_reportes_operativos_erp`;
  - metodo `ClientesCrm::reportesOperativosClientes`;
  - panel `Reportes CRM` en consola Clientes.
- Indicadores:
  - clientes identificables para POS;
  - clientes contactables operativos;
  - clientes aptos para campanas;
  - clientes pendientes de contacto;
  - clientes pendientes de consentimiento;
  - clientes legacy fuera de campanas;
  - bloqueos comerciales;
  - elegibles para recompensas;
  - elegibles para garantia extendida.
- Reglas:
  - no escribe BD;
  - no crea tareas;
  - no modifica clientes;
  - no usa clientes legacy como base de campanas;
  - tolera falta de `crm_clientes_condiciones` y muestra condiciones simuladas hasta aplicar DDL comercial.

Avance CRM preferencias de contacto:

- Fecha: 2026-06-30.
- Se prepara contrato de preferencias sin escritura:
  - endpoint dry-run `/crm/cliente_preferencias_dryrun_erp`;
  - metodo `ClientesCrm::preferenciasContactoDryRun`;
  - ficha CRM muestra/valida preferencias en la pestana fiscal/comercial.
- Se prepara apply autorizado:
  - endpoint `/crm/cliente_preferencias_guardar_autorizado_erp`;
  - metodo `ClientesCrm::preferenciasContactoGuardarAutorizado`;
  - token fuerte `CRM_CLIENTES_PREFERENCIAS`.
- Las preferencias viven en `crm_clientes_condiciones.preferencias`:
  - canal preferido;
  - canales permitidos;
  - horario de contacto;
  - frecuencia;
  - temas de interes;
  - no contactar y motivo.
- Reglas:
  - preferencias no equivalen a consentimiento legal;
  - no modifican contactos;
  - no crean campanas;
  - no tocan POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy;
  - apply real requiere `crm_clientes_condiciones`, por tanto depende de `CRM_CLIENTES_COMERCIAL_DDL`.
- Documento de autorizacion actualizado:
  - `docs/crm_clientes_comercial_solicitud_autorizacion.md`.

Avance CRM recompensas base:

- Fecha: 2026-06-30.
- Se prepara subdominio CRM Recompensas sin escritura real:
  - endpoint plan `/crm/esquema_plan_clientes_recompensas_crm`;
  - endpoint apply protegido `/crm/esquema_actualizar_clientes_recompensas_crm`;
  - token fuerte `CRM_CLIENTES_RECOMPENSAS_DDL`.
- Tablas propuestas:
  - `crm_recompensas_programas`;
  - `crm_clientes_recompensas_cuentas`;
  - `crm_clientes_recompensas_movimientos`.
- Se agrega resumen read-only:
  - endpoint `/crm/clientes_recompensas_resumen_erp`;
  - metodo `ClientesCrm::resumenRecompensasClientes`;
  - panel `Recompensas CRM` en consola Clientes.
- Se prepara dry-run de movimiento:
  - endpoint `/crm/cliente_recompensa_movimiento_dryrun_erp`;
  - metodo `ClientesCrm::recompensaMovimientoDryRun`;
  - valida acumulacion, redencion, ajuste y caducidad;
  - no crea cuenta, no crea movimiento y no cambia saldo.
- Reglas:
  - legacy no debe recibir recompensas sin revision puntual;
  - condiciones comerciales pueden bloquear recompensas;
  - POS/Ventas no se conectan aun;
  - no hay apply real de movimiento hasta definir politica de puntos.
- Documento de autorizacion:
  - `docs/crm_clientes_recompensas_solicitud_autorizacion.md`.

Aplicacion DDL CRM recompensas:

- Fecha: 2026-06-30.
- Respaldo generado y usado:
  - `C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_recompensas.sql`.
- Script:
  - `storage/uat/uat_crm_clientes_recompensas_schema_apply_authorized.php`.
- Resultado:
  - `crm_recompensas_programas` existe con `0` filas;
  - `crm_clientes_recompensas_cuentas` existe con `0` filas;
  - `crm_clientes_recompensas_movimientos` existe con `0` filas.
- Alcance respetado:
  - no crea programas reales;
  - no crea cuentas;
  - no otorga ni redime puntos;
  - no modifica clientes;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- Siguiente paso CRM:
  - definir politica operativa de recompensas antes de permitir apply real de movimientos;
  - mantener POS/Ventas solo como consumidor futuro hasta que exista politica aprobada.

Avance CRM programa recompensas:

- Fecha: 2026-06-30.
- Se prepara contrato para crear programas de recompensas:
  - endpoint dry-run `/crm/cliente_recompensa_programa_dryrun_erp`;
  - endpoint apply protegido `/crm/cliente_recompensa_programa_crear_autorizado_erp`;
  - token fuerte `CRM_CLIENTES_RECOMPENSAS_PROGRAMA`.
- Validaciones:
  - codigo unico y normalizado;
  - tipo `puntos`, `monedero`, `niveles` o `mixto`;
  - estatus `activo`, `pausado` o `inactivo`;
  - reglas JSON normalizadas para acumulacion, redencion, caducidad y restricciones.
- Alcance:
  - crea solo `crm_recompensas_programas`;
  - no crea cuentas;
  - no crea movimientos;
  - no otorga ni redime puntos;
  - no modifica clientes;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- Proxima autorizacion fuerte:
  - crear programa base de recompensas cuando se defina codigo, nombre, tipo y reglas.
- Scripts UAT:
  - `storage/uat/uat_crm_clientes_recompensas_programa_dryrun_readonly.php`;
  - `storage/uat/uat_crm_clientes_recompensas_programa_apply_authorized.php`;
  - `storage/uat/uat_crm_clientes_recompensas_post_apply_readonly.php`.
- Dry-run validado:
  - programa candidato `PUNTOS_BASE`;
  - acumulacion propuesta: 1 punto por cada 10 de monto pagado;
  - redencion pendiente, sin conectar POS;
  - resultado sin bloqueos y sin escritura BD.
- Apply autorizado:
  - respaldo `C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_recompensas.sql`;
  - token `CRM_CLIENTES_RECOMPENSAS_PROGRAMA`;
  - creado `PUNTOS_BASE` como `id_programa_recompensa=1`.
- Verificacion:
  - programas: `1`;
  - cuentas: `0`;
  - movimientos: `0`.

Avance CRM cuenta recompensas:

- Fecha: 2026-06-30.
- Se prepara contrato para crear cuentas de recompensas por cliente:
  - endpoint dry-run `/crm/cliente_recompensa_cuenta_dryrun_erp`;
  - endpoint apply protegido `/crm/cliente_recompensa_cuenta_crear_autorizado_erp`;
  - token fuerte `CRM_CLIENTES_RECOMPENSAS_CUENTA`.
- Validaciones:
  - cliente CRM activo;
  - programa activo;
  - cliente legacy bloqueado sin revision puntual;
  - no duplicar cuenta cliente/programa;
  - condiciones comerciales respetadas si existe `crm_clientes_condiciones`.
- Alcance:
  - crea cuenta con saldo cero;
  - no crea movimientos;
  - no otorga ni redime puntos;
  - no modifica datos maestros de cliente;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
- Scripts UAT:
  - `storage/uat/uat_crm_clientes_recompensas_cuenta_dryrun_readonly.php`;
  - `storage/uat/uat_crm_clientes_recompensas_cuenta_apply_authorized.php`.
- Dry-run validado:
  - cliente `id_cliente_crm=1`;
  - programa `id_programa_recompensa=1`;
  - resultado sin bloqueos;
  - aviso de condiciones comerciales finas pendientes;
  - sin escritura BD.
- Apply autorizado:
  - respaldo `C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_recompensas.sql`;
  - token `CRM_CLIENTES_RECOMPENSAS_CUENTA`;
  - creada cuenta `id_cliente_recompensa_cuenta=1` para cliente `1` y programa `1`.
- Verificacion:
  - programas: `1`;
  - cuentas: `1`;
  - movimientos: `0`.
- Proxima autorizacion fuerte:
  - permitir primer movimiento de prueba controlado, solo si se define si sera acumulacion manual UAT o ajuste administrativo.

Avance CRM movimientos recompensas:

- Fecha: 2026-06-30.
- Se prepara contrato para movimientos manuales/controlados:
  - endpoint dry-run `/crm/cliente_recompensa_movimiento_dryrun_erp`;
  - endpoint apply protegido `/crm/cliente_recompensa_movimiento_crear_autorizado_erp`;
  - token fuerte `CRM_CLIENTES_RECOMPENSAS_MOVIMIENTO`.
- Validaciones:
  - cliente CRM activo;
  - programa activo;
  - cuenta existente para cliente/programa;
  - puntos mayores a cero;
  - saldo suficiente para `redencion` y `caducidad`;
  - origen `pos` y `ventas` bloqueados hasta contrato formal de integracion.
- Alcance:
  - crea movimiento en `crm_clientes_recompensas_movimientos`;
  - actualiza saldo de cuenta;
  - no conecta POS ni ventas;
  - no modifica datos maestros de cliente;
  - no toca ecommerce, garantias, apartados, devoluciones ni legacy.
- Scripts UAT:
  - `storage/uat/uat_crm_clientes_recompensas_movimiento_dryrun_readonly.php`;
  - `storage/uat/uat_crm_clientes_recompensas_movimiento_apply_authorized.php`.
- Dry-run validado:
  - cliente `id_cliente_crm=1`;
  - programa `id_programa_recompensa=1`;
  - cuenta `id_cliente_recompensa_cuenta=1`;
  - movimiento `acumulacion` de `10` puntos;
  - saldo resultante `10`;
  - sin escritura BD.
- Apply autorizado:
  - respaldo `C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_recompensas.sql`;
  - token `CRM_CLIENTES_RECOMPENSAS_MOVIMIENTO`;
  - creado movimiento `id_cliente_recompensa_movimiento=1`;
  - tipo `acumulacion`;
  - puntos `10`;
  - saldo resultante `10`.
- Verificacion:
  - programas: `1`;
  - cuentas: `1`;
  - movimientos: `1`;
  - saldo de cuenta `10.000000`.
- Proxima autorizacion fuerte:
  - preparar redencion/caducidad solo como dry-run, o cerrar Recompensas UAT y pasar a ficha/UX/reportes antes de conectar POS.

Avance CRM saldos/cuenta corriente:

- Fecha: 2026-07-06.
- Se prepara subdominio CRM Saldos sin escritura real:
  - endpoint plan `/crm/esquema_plan_clientes_saldos_crm`;
  - endpoint apply protegido `/crm/esquema_actualizar_clientes_saldos_crm`;
  - token fuerte `CRM_CLIENTES_SALDOS_DDL`.
- Tablas propuestas:
  - `crm_clientes_saldos_cuentas`;
  - `crm_clientes_saldos_movimientos`.
- Uso esperado:
  - saldo favor de cancelaciones POS;
  - consumos de saldo en ventas futuras;
  - cargos, ajustes y correcciones autorizadas;
  - trazabilidad por origen.
- Reglas:
  - no crear saldos anonimos;
  - no usar recompensas/monedero para saldo favor;
  - POS/Ventas solo consumen ledger despues de cliente CRM ligado y decision financiera autorizada;
  - no mover caja ni inventario desde el DDL.
- Scripts UAT:
  - `storage/uat/uat_crm_clientes_saldos_schema_readonly.php`;
  - `storage/uat/uat_crm_clientes_saldos_schema_apply_authorized.php`.
- Read-only validado:
  - `ddl_total=2`;
  - `ddl_pendientes=2`;
  - no escritura BD.
- Guardrail validado:
  - apply sin token/respaldo bloqueado correctamente.
- Documento de autorizacion:
  - `docs/crm_clientes_saldos_solicitud_autorizacion.md`.
- Aplicacion DDL CRM saldos:
  - fecha `2026-07-06`;
  - respaldo/referencia `UAT POS vigente`;
  - token `CRM_CLIENTES_SALDOS_DDL`;
  - `crm_clientes_saldos_cuentas` existe con `0` filas;
  - `crm_clientes_saldos_movimientos` existe con `0` filas;
  - auditor posterior `ddl_pendientes=0`;
  - no se crearon cuentas ni movimientos.
- Siguiente paso:
  - preparar dry-run de movimiento de saldo favor ligado a cliente CRM;
  - bloquear saldos anonimos;
  - no usar recompensas como saldo favor.
- Aplicacion de primer saldo favor POS:
  - fecha `2026-07-06`;
  - cliente CRM `157`;
  - cuenta `crm_clientes_saldos_cuentas.id_cliente_saldo_cuenta=1`;
  - movimiento `CRM-SAL-20260706-000001`;
  - origen `ventas_pos/pedido_cancelado_decision_financiera/PFIN-20260706-000001`;
  - referencia `APT-20260706-000002`;
  - saldo disponible MXN `100`;
  - no movio caja, inventario ni recompensas.
- Lector read-only:
  - `storage/uat/uat_crm_clientes_saldos_cliente_readonly.php`.

Avance UX/UI CRM modular:

- Fecha: 2026-06-30.
- Decision:
  - CRM no debe crecer como una sola ventana de clientes;
  - el menu CRM debe agrupar capacidades por intencion operativa;
  - las pantallas deben separar lectura, operacion diaria, auditoria y configuracion.
- Primera mejora aplicada en `/crm/clientes`:
  - busqueda express queda global arriba;
  - debajo se agregan pestanas de trabajo:
    - `Operacion`: calidad operativa y tareas;
    - `Comercial`: condiciones, segmentos y reportes;
    - `Recompensas`: programas, cuentas, saldos y movimientos;
    - `Clientes`: listado canonico;
    - `Auditoria legacy`: fuentes, duplicados, preview/borrador legacy y esquema.
- Mejora aplicada en ficha `/crm/cliente/{id}`:
  - se agrega pestana `Recompensas`;
  - muestra resumen, cuentas, saldo y ultimos movimientos del cliente;
  - consume la ficha CRM read-only, no otorga ni redime puntos;
  - mantiene POS/Ventas fuera del flujo de recompensas hasta contrato posterior.
- Verificacion UAT read-only:
  - script `storage/uat/uat_crm_clientes_ficha_recompensas_readonly.php`;
  - cliente `id_cliente_crm=1`;
  - recompensas disponibles;
  - cuentas `1`;
  - movimientos `1`;
  - saldo `10`.
- Criterio UX:
  - una pestana = una intencion de trabajo;
  - no mezclar legacy con operacion normal;
  - no esconder busqueda de cliente dentro de auditoria;
  - no colocar acciones de escritura fuerte en vistas de lectura sin dry-run y confirmacion;
  - la ficha completa vive fuera de POS y puede tener sus propias pestanas internas.
- Menu CRM recomendado:
  - `CRM > Clientes`: busqueda, listado canonico y ficha;
  - `CRM > Seguimiento`: tareas, interacciones, pendientes y proximos contactos;
  - `CRM > Comercial`: segmentos, preferencias, condiciones, listas futuras y elegibilidad;
  - `CRM > Recompensas`: programas, cuentas, saldos, movimientos, reglas y reportes;
  - `CRM > Postventa`: garantias, apartados/devoluciones relacionados al cliente cuando sus modulos esten listos;
  - `CRM > Auditoria`: legacy, duplicados, migraciones pausadas, vinculos externos y calidad de datos;
  - `CRM > Reportes`: valor de cliente, frecuencia, recompra, contacto, garantias y recompensas.
- Implementacion provisional en sidebar:
  - se agregan accesos `Clientes`, `Seguimiento`, `Comercial`, `Recompensas` y `Auditoria`;
  - mientras no existan pantallas dedicadas, cada acceso abre `/crm/clientes` en la pestana correspondiente con hash `#crm_tab_*`;
  - el JS de la consola CRM respeta el hash de entrada y conserva la ultima pestana usada;
  - no se crean rutas vacias ni pantallas falsas: la separacion visual queda lista para evolucionar por submodulos.
- Decision UX adicional:
  - `Recompensas` se separa de `Comercial` porque ya tiene programa, cuenta, saldo y movimientos propios;
  - `Comercial` conserva condiciones, segmentos, preferencias y reportes comerciales;
  - la siguiente evolucion natural es una pantalla dedicada `CRM > Recompensas`, antes de conectar acumulacion/redencion automatica desde POS.
- Regla de crecimiento:
  - cuando una pestana acumule mas de 3 paneles operativos o requiera acciones propias frecuentes, debe convertirse en submenu CRM dedicado;
  - `Recompensas CRM` ya debe evolucionar hacia submenu propio antes de conectarse a POS.

Documento de autorizacion pendiente:

- `docs/crm_clientes_migracion_legacy_solicitud_autorizacion.md`
- `docs/crm_clientes_complementos_solicitud_autorizacion.md`
- `docs/crm_clientes_seguimiento_schema_solicitud_autorizacion.md`
- `docs/crm_clientes_tareas_solicitud_autorizacion.md`
- `docs/crm_clientes_interacciones_solicitud_autorizacion.md`
- `docs/crm_clientes_comercial_solicitud_autorizacion.md`
- `docs/crm_clientes_recompensas_solicitud_autorizacion.md`

## Handoff / continuidad

Fecha: 2026-06-29

- Contexto actual: POS ya tiene tablas minimas `erp_clientes` y `erp_clientes_identificadores`, mas un cliente UAT.
- Decision: CRM sera el dueno canonico y POS sera consumidor.
- Implementacion inicial esperada: controlador `Crm`, modelos `ClientesCrm` y `ClientesCrmEsquema`, solo read-only/plan.
- Pendiente: construir vistas CRM despues de cerrar esquema y permisos.

## Nota de contrato POS 2026-06-29

- El telefono `5550000000` existe en `erp_clientes` POS/UAT, pero no en CRM canonico.
- El telefono `3312345678` existe en CRM canonico como `CRM-POSUAT-20260628-0001`, `id_cliente_crm=1`.
- POS/Ventas debe guardar `id_cliente_crm` y snapshot de cliente CRM en ventas, excepciones comerciales, apartados y postventa.
- `erp_clientes` queda como fuente UAT/legacy; no debe usarse para listas, recompensas, garantias personalizadas ni historiales nuevos sin migracion/vinculo autorizado.
