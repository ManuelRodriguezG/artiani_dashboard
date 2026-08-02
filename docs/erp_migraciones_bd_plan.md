# ERP - Modulo de migraciones y promocion de base de datos

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-30  
Estado: diseno inicial; no implementado; no ejecuta cambios en BD

## Proposito

Definir un modulo tecnico para preparar, comparar, respaldar y aplicar cambios entre ambientes de base de datos, especialmente de local hacia productivo.

El objetivo no es copiar una base completa sin criterio, sino construir una promocion controlada por tabla, con respaldo previo, dry-run, autorizacion explicita, bitacora y plan de reversa.

## Problema operativo

Durante la construccion del ERP se esta capturando informacion real y decisiva en local: catalogo, proveedores, costos, relaciones SKU proveedor, reglas de inventario, configuraciones y otros cimientos.

Esa informacion no debe perderse ni recapturarse cuando llegue el momento de llevar el sistema a productivo. Tampoco debe subirse todo sin control, porque productivo puede tener ventas, usuarios, clientes, movimientos o historial que no deben sobrescribirse.

## Decision recomendada

Crear un modulo tecnico `Migraciones BD` dentro de Administracion/SYS con tres capacidades separadas:

1. Inventario y comparacion de ambientes.
2. Generacion de paquetes de migracion.
3. Aplicacion autorizada con respaldo y bitacora.

La aplicacion real debe exigir:

- permiso fino;
- entorno destino configurado;
- respaldo externo verificable;
- dry-run previo;
- token o frase de autorizacion;
- ventana operativa documentada;
- bitacora de cada tabla, sentencia y resultado.

## Alcance funcional

### Comparacion

- Comparar esquema local vs destino.
- Detectar tablas faltantes.
- Detectar columnas faltantes o diferentes.
- Detectar indices faltantes o diferentes.
- Detectar conteos por tabla.
- Detectar maximos de claves primarias y posibles colisiones.
- Detectar tablas con columnas sensibles o historicas.

### Clasificacion de tablas

Cada tabla debe tener una politica de migracion:

- `schema_only`: solo estructura.
- `data_seed`: datos catalogo/semilla versionables.
- `data_merge`: insertar/actualizar registros con llave natural o identificador estable.
- `data_snapshot`: reemplazo completo permitido solo en tablas tecnicas o temporales.
- `local_only`: nunca migrar a productivo.
- `production_owned`: productivo manda; local no debe sobrescribir.
- `blocked`: requiere decision manual.

### Paquetes de migracion

Un paquete debe guardar:

- ambiente origen;
- ambiente destino;
- tablas incluidas;
- politica por tabla;
- SQL generado;
- resumen de riesgo;
- hash o firma del plan;
- usuario creador;
- estatus: borrador, revisado, autorizado, aplicado, fallido, cancelado;
- ruta de respaldo usada al aplicar.

### Aplicacion

La aplicacion real debe:

- bloquearse si no existe respaldo externo;
- bloquearse si el dry-run cambio despues de autorizar;
- ejecutar primero DDL seguro;
- ejecutar despues datos segun politica;
- registrar antes/despues en auditoria SYS;
- detenerse ante errores de integridad;
- conservar evidencia suficiente para reversa.

## Que si migrar desde local a productivo

Normalmente si conviene migrar:

- catalogos base del ERP;
- productos y SKUs maestros cuando productivo todavia no opera sobre ellos;
- marcas, categorias, unidades, claves fiscales y reglas de inventario;
- proveedores maestros ya depurados;
- relaciones SKU proveedor;
- listas/costos de proveedor si ya estan validados;
- parametros SYS no secretos;
- roles/permisos base;
- plantillas, configuraciones operativas y reglas de negocio versionadas;
- tablas staging solo si se usan como evidencia temporal autorizada.

## Que no migrar sin plan especifico

No conviene migrar de forma generica:

- sesiones;
- tokens;
- passwords o hashes si productivo ya tiene usuarios reales;
- auditoria como si fuera operacion productiva;
- ventas, caja, pagos, inventario real, movimientos, recepciones y documentos fiscales si productivo ya opera;
- clientes reales de productivo;
- archivos adjuntos sin reconciliar rutas fisicas;
- tablas legacy completas sin staging;
- cualquier tabla con claves autoincrementales que puedan colisionar sin llave natural.

## Arquitectura propuesta

### Controlador

`app/controladores/MigracionBd.php`

Endpoints sugeridos:

- `index`
- `ambientes_listar`
- `ambiente_probar`
- `tablas_clasificar`
- `comparar_ambientes`
- `paquete_crear`
- `paquete_consultar`
- `paquete_sql_descargar`
- `paquete_autorizar`
- `paquete_aplicar`
- `respaldo_generar`
- `respaldo_validar`
- `historial_listar`

### Modelos

`app/modelos/MigracionesBd.php`

Responsable de:

- conexiones a ambientes;
- lectura de INFORMATION_SCHEMA;
- comparacion;
- generacion de SQL;
- validaciones de riesgo;
- aplicacion transaccional cuando sea posible;
- bitacora.

`app/modelos/MigracionesBdEsquema.php`

Responsable de crear:

- `sys_migraciones_ambientes`;
- `sys_migraciones_tablas_politicas`;
- `sys_migraciones_paquetes`;
- `sys_migraciones_paquete_tablas`;
- `sys_migraciones_paquete_sql`;
- `sys_migraciones_ejecuciones`;
- `sys_migraciones_ejecucion_detalle`.

### Vista y JS

Vista:

- `app/vistas/paginas/apps/erp/sistema/migraciones_bd.php`

JS:

- `public/assets/js/custom/apps/erp/sistema/migraciones_bd.js`

La UI debe ser una consola tecnica sobria con tabs:

- Ambientes.
- Politicas por tabla.
- Comparacion.
- Paquetes.
- Ejecuciones y respaldos.

## Configuracion de credenciales

No guardar credenciales productivas en vistas, JS ni documentacion.

Opciones recomendadas:

1. Archivo PHP fuera de `public` y no versionado, por ejemplo `app/config/migraciones_ambientes.local.php`.
2. Variables de entorno del servidor.
3. Tabla cifrada solo si se define antes una estrategia de llave fuera de la BD.

Para este proyecto, la primera fase debe usar archivo local no versionado con alias de ambiente y nunca devolver password al navegador.

## Permisos sugeridos

Agregar permisos finos:

- `migraciones.ver`: consultar ambientes, politicas, comparaciones e historial.
- `migraciones.preparar`: crear paquetes y dry-runs.
- `migraciones.aplicar`: aplicar paquetes autorizados.
- `migraciones.respaldos`: generar y validar respaldos.

El rol `administrador_erp` puede tener todos. El rol `soporte_sistema` puede tener ver/preparar/respaldos y aplicar solo si el dueno lo autoriza.

## Respaldo

Usar el estandar del proyecto:

```text
C:\xampp\panel_db_backups
```

Convencion:

```text
{base}_{proyecto}_{yyyymmdd_HHmmss}_antes_migracion_bd_{paquete}.sql
```

Ningun paquete debe aplicarse si:

- el respaldo no existe;
- el respaldo esta dentro del repo;
- el respaldo pesa `0`;
- el respaldo no corresponde al ambiente destino;
- el respaldo fue generado antes de que cambiara el plan autorizado.

## Riesgos principales

- Sobrescribir informacion productiva real con datos locales incompletos.
- Colisionar IDs autoincrementales.
- Romper foreign keys por migrar tablas fuera de orden.
- Migrar passwords, sesiones o datos sensibles accidentalmente.
- Generar una falsa sensacion de reversa si no se prueba restauracion.
- Copiar datos legacy que deberian pasar por staging y depuracion.

## Fase 1 recomendada

Implementar solo lectura y preparacion:

1. Crear esquema de tablas SYS para ambientes, politicas y paquetes.
2. Crear permisos `migraciones.*`.
3. Crear UI de consulta.
4. Comparar esquema local vs destino configurado.
5. Clasificar tablas con politica manual.
6. Generar SQL en dry-run descargable.
7. No aplicar aun cambios reales.

## Avance implementado - Fase 1 base

Fecha: 2026-07-30

Archivos creados:

- `app/controladores/MigracionBd.php`
- `app/modelos/MigracionesBd.php`
- `app/modelos/MigracionesBdEsquema.php`
- `app/vistas/paginas/apps/erp/sistema/migraciones_bd.php`
- `public/assets/js/custom/apps/erp/sistema/migraciones_bd.js`
- `app/config/migraciones_ambientes.example.php`

Archivos actualizados:

- `app/core/Core.php`: protege el controlador `MigracionBd`.
- `app/modelos/SeguridadEsquema.php`: agrega permisos `migraciones.*`.
- `app/vistas/includes/header/sidebar.php`: agrega acceso en Administracion.

Capacidades disponibles:

- consola `/migracionBd`;
- diagnostico de BD local activa;
- listado saneado de ambientes;
- clasificacion sugerida por tabla;
- comparacion local contra destino configurado;
- SQL dry-run para tablas, columnas, indices no primarios y llaves foraneas faltantes;
- esquema tecnico `sys_migraciones_*` en dry-run/aplicacion controlada por endpoint de soporte.
- acceso transicional con `sistema.soporte` mientras los permisos `migraciones.*` no esten sembrados en BD.
- edicion UI de politicas sugeridas por tabla.
- perfil read-only de datos por tabla para decidir migracion de informacion.
- orden sugerido de migracion por dependencias de llaves foraneas.
- resumen ejecutivo de decision por politica, riesgo y candidatas de datos.
- manifiesto JSON portable de preparacion.
- guardado de politicas si el esquema tecnico ya existe; si no existe, bloquea con advertencia.
- creacion de paquete dry-run con codigo y hash de plan; persiste solo si el esquema tecnico ya existe.

Restricciones vigentes:

- no aplica migraciones reales;
- no genera respaldos todavia;
- no persiste paquetes mientras no exista `sys_migraciones_*`;
- no migra datos;
- no muestra passwords;
- requiere crear `app/config/migraciones_ambientes.local.php` a partir del ejemplo para comparar contra destino externo.

## Avance implementado - Preparacion de paquetes

Fecha: 2026-07-30

Se agrego:

- tabla tecnica `sys_migraciones_paquete_tablas` al plan de esquema;
- endpoint `MigracionBd::politicas_guardar`;
- endpoint `MigracionBd::paquete_dry_run_crear`;
- auditoria explicita para ambos endpoints;
- UI editable para politica por tabla;
- seleccion de tablas para paquete dry-run;
- generacion de codigo y `hash_plan` para detectar cambios posteriores.

## Avance implementado - Preflight de activacion

Fecha: 2026-07-31

Se agrego:

- `MigracionesBd::validarRespaldo`;
- `MigracionesBd::preflightActivacion`;
- endpoints `respaldo_validar` y `activacion_preflight`;
- pestaña UI `Activacion`;
- comando sugerido de `mysqldump`;
- texto de autorizacion para activar `sys_migraciones_*`;
- runbook `docs/erp_migraciones_bd_runbook_activacion.md`.
- proteccion de `MigracionBd::esquema_actualizar` para que `ejecutar=1` exija respaldo valido, token `MIGRACIONES_BD_SCHEMA` y confirmacion literal.
- botones UI de dry-run/aplicacion protegida del esquema tecnico.

Regla vigente para activacion:

- El preflight no crea respaldos.
- El preflight no ejecuta DDL.
- El preflight valida que el respaldo no sea placeholder, exista si es ruta local, sea legible, pese mas de `0` y no este dentro del repo.
- La aplicacion real del esquema tecnico requiere respaldo externo y autorizacion explicita.
- Aunque la UI envie `ejecutar=1`, el backend bloquea la aplicacion si falta respaldo/token/confirmacion.

Regla vigente para paquetes:

- Sin esquema tecnico aplicado, el paquete dry-run se genera como temporal y no queda persistido.
- Con esquema tecnico aplicado, se guardan encabezado, tablas incluidas y SQL generado.
- Ningun endpoint de esta fase ejecuta SQL sobre el destino.

## Avance implementado - Comparacion de indices

Fecha: 2026-07-31

Se agrego:

- lectura de indices desde `INFORMATION_SCHEMA.STATISTICS`;
- totales de indices en diagnostico local;
- comparacion de indices no primarios faltantes en destino;
- SQL dry-run `ALTER TABLE ... ADD KEY/UNIQUE KEY` para indices faltantes;
- visualizacion de indices faltantes en la pestaña Comparacion.

Restriccion:

- No genera modificaciones para `PRIMARY KEY`.
- No modifica indices diferentes; solo reporta/genera indices faltantes no primarios.
- No ejecuta DDL.

## Avance implementado - Comparacion de llaves foraneas

Fecha: 2026-07-31

Se agrego:

- lectura de FKs desde `INFORMATION_SCHEMA.KEY_COLUMN_USAGE` y `REFERENTIAL_CONSTRAINTS`;
- totales de FKs en diagnostico local;
- comparacion de FKs faltantes en destino;
- SQL dry-run `ALTER TABLE ... ADD CONSTRAINT ... FOREIGN KEY`;
- visualizacion de FKs faltantes en la pestaña Comparacion.

Restriccion:

- Las FKs se marcan con riesgo `alto`, porque pueden fallar si los datos destino no cumplen integridad.
- No se modifican ni eliminan FKs diferentes.
- No se ejecuta DDL.

## Avance implementado - Perfil de datos por tabla

Fecha: 2026-08-01

Se agrego:

- `MigracionesBd::perfilarTablasDatos`;
- endpoint `MigracionBd::tablas_perfil_datos`;
- pestaña UI `Perfil datos`;
- deteccion de PK por tabla;
- deteccion de indices unicos;
- candidatos de llave natural por nombres como `sku`, `codigo`, `uuid`, `rfc`, `folio`, `clave`, `correo`;
- columnas de fecha;
- columnas sensibles por nombres como password, token, session, hash o secret;
- riesgos `sin_pk`, `sin_llave_natural_clara`, `columnas_sensibles` y `propiedad_productivo`.

Uso operativo:

- Tablas con `data_merge` necesitan PK o llave natural clara antes de migrar datos.
- Tablas con columnas sensibles no deben migrarse sin revision puntual.
- Tablas `production_owned` deben respetarse cuando productivo empiece a operar ventas, inventario, caja, clientes o auditoria real.

## Avance implementado - Orden de migracion por dependencias

Fecha: 2026-08-01

Se agrego:

- `MigracionesBd::ordenarTablasPorDependencias`;
- endpoint `MigracionBd::tablas_orden_migracion`;
- pestaña UI `Orden`;
- orden topologico de tablas segun llaves foraneas;
- reporte de tablas pendientes si existen ciclos o dependencias no resolubles;
- visualizacion de nivel, dependencias y dependientes.

Uso operativo:

- El orden sugerido sirve para cargas futuras de datos, no para decidir por si solo que tablas migran.
- Las tablas referenciadas por FK deben cargarse antes que sus dependientes.
- Si existen ciclos, la migracion de datos requerira estrategia especial: desactivar FKs temporalmente, carga en dos fases o normalizacion previa, siempre con respaldo y autorizacion.

## Avance implementado - Resumen de decision

Fecha: 2026-08-01

Se agrego:

- `MigracionesBd::resumenDecisionMigracion`;
- endpoint `MigracionBd::resumen_decision`;
- pestaña UI `Resumen`;
- conteos por politica;
- conteos por riesgo;
- listado corto de candidatas para datos;
- listado corto de tablas bloqueadas/productivo;
- listado corto de tablas sensibles;
- listado corto de tablas sin llave clara.

Uso operativo:

- Usar este resumen antes de preparar un paquete real.
- Priorizar `data_seed` y `data_merge` sin columnas sensibles y con PK/llave natural clara.
- Resolver o excluir tablas con `sin_llave_natural_clara` antes de intentar merge.
- Revisar manualmente cualquier tabla con columnas sensibles aunque tenga politica sugerida migrable.

## Avance implementado - Manifiesto de preparacion

Fecha: 2026-08-01

Se agrego:

- `MigracionesBd::generarManifiestoPreparacion`;
- endpoint `MigracionBd::manifiesto_preparacion`;
- pestaña UI `Manifiesto`;
- JSON portable con origen, destino opcional, estado de esquema tecnico, resumen de decision, perfil de datos y orden de migracion;
- hash SHA-256 del manifiesto;
- nombre sugerido para archivo JSON;
- boton para copiar manifiesto;
- boton para descargar el manifiesto como archivo `.json` local.

Uso operativo:

- Generar manifiesto antes de aplicar esquema tecnico o preparar paquetes persistidos.
- Usarlo como evidencia de revision en cambios de ambiente.
- Si se selecciona destino, el manifiesto intenta incluir comparacion; si el destino no conecta, registra el error en el JSON sin aplicar cambios.
- La descarga desde navegador es solo evidencia operativa; no persiste ni aplica nada en la base de datos.

## Avance implementado - Descarga local de artefactos dry-run

Fecha: 2026-08-01

Se agrego:

- boton para descargar el manifiesto JSON generado;
- boton para descargar el SQL dry-run o paquete dry-run generado;
- nombres de archivo con timestamp para facilitar resguardo y comparacion;
- generacion de archivos en el navegador usando el contenido visible de la pestaña.

Restriccion:

- La descarga no crea registros nuevos en BD.
- La descarga no ejecuta SQL.
- El SQL descargado sigue siendo material de revision y debe pasar por respaldo, autorizacion y aplicacion controlada antes de usarse.

## Avance implementado - Flujo de aplicacion controlada de paquetes

Fecha: 2026-08-01

Se agrego:

- `MigracionesBd::preflightPaqueteAplicacion`;
- `MigracionesBd::aplicarPaqueteControlado`;
- endpoints `MigracionBd::paquete_preflight` y `MigracionBd::paquete_aplicar`;
- seccion UI para codigo de paquete, token, confirmacion y simulacion/aplicacion;
- bitacora preparada en `sys_migraciones_ejecuciones` y `sys_migraciones_ejecucion_detalle`;
- bandera local `_opciones.aplicacion_real_habilitada` en `app/config/migraciones_ambientes.local.php`, documentada en el archivo ejemplo.

Compuertas vigentes para ejecutar realmente un paquete:

- esquema tecnico `sys_migraciones_*` aplicado;
- paquete persistido;
- destino configurado;
- respaldo valido;
- permiso `migraciones.aplicar`;
- token `MIGRACIONES_BD_APLICAR`;
- confirmacion literal con codigo de paquete y destino;
- bandera local `aplicacion_real_habilitada=true`.

Restriccion:

- Por defecto la bandera local queda en `false`.
- La simulacion no ejecuta SQL.
- La aplicacion real debe mantenerse apagada hasta que exista respaldo probado y se haya revisado el paquete.

## Avance implementado - Generacion controlada de respaldo local

Fecha: 2026-08-01

Se agrego:

- `MigracionesBd::generarRespaldoLocal`;
- endpoint `MigracionBd::respaldo_generar`;
- boton UI `Generar respaldo`;
- token `MIGRACIONES_BD_RESPALDO`;
- confirmacion literal `AUTORIZO GENERAR RESPALDO MIGRACIONES BD`;
- uso de `mysqldump` con ruta configurable;
- escritura en la ruta estandar `C:\xampp\panel_db_backups`;
- hash SHA-256 y tamaño del archivo generado;
- auditoria explicita de intentos de respaldo.

Restriccion:

- El respaldo no modifica la base de datos.
- La ruta de respaldo no puede estar dentro del repo.
- La respuesta nunca debe devolver password.
- El respaldo debe validarse antes de activar esquema tecnico o aplicar paquetes.

Decision operativa:

- Mientras productivo sea solo copia de revision, local puede ser la base candidata oficial.
- Antes de activar productivo real, se debe tomar respaldo externo, comparar esquema y clasificar tablas.
- Despues de que productivo reciba ventas, caja, inventario, clientes o movimientos reales, esas tablas se consideran `production_owned` y no deben reemplazarse desde local.

## Fase 2 recomendada

Agregar respaldos y aplicacion controlada:

1. Generar respaldo destino con `mysqldump`.
2. Validar respaldo.
3. Autorizar paquete con token.
4. Aplicar solo paquetes cuyo hash no cambio.
5. Registrar ejecucion y detalle.
6. Auditar post-aplicacion.

## Fase 3 recomendada

Agregar reglas avanzadas:

- llaves naturales por tabla;
- merge por columnas especificas;
- mascaramiento de datos sensibles;
- deteccion de dependencias por foreign keys;
- simulacion de conteos antes/despues;
- plan de reversa asistido;
- exportacion de paquetes versionables.

## Handoff / continuidad

Fecha: 2026-07-30

- Contexto actual: el dueno quiere construir informacion real en local y despues promoverla a productivo sin recaptura ni copia indiscriminada.
- Decision: crear modulo SYS de migraciones/promocion con politicas por tabla, dry-run, respaldo y autorizacion.
- Cambios recientes: Fase 1 base implementada en codigo, sin aplicacion real ni migracion de datos.
- Pendiente: aplicar esquema `sys_migraciones_*` con respaldo externo si se autoriza; configurar destino local no versionado; persistir politicas y paquetes.
- Impacta a: Catalogo, Proveedores, Compras, Inventario, Seguridad, Sistema y futuras cargas masivas.
- Siguiente paso recomendado: generar respaldo externo, validar preflight en UI y pedir autorizacion literal antes de aplicar el esquema tecnico.
