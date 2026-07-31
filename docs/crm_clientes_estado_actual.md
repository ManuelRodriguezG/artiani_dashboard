# CRM Clientes - estado actual y continuidad

Fecha: 2026-07-30  
Proyecto: `C:\xampp\htdocs\panel_de_control`  
Host local canonico: `http://panel.com.local/`

## Estado aplicado

- CRM Clientes es el dueno canonico de clientes.
- POS/Ventas debe consumir CRM, no modelar clientes como telefono o nombre suelto.
- POS puede vender a publico general sin cliente.
- POS puede buscar/seleccionar cliente con `crm.pos.buscar`.
- POS puede validar alta express con `crm.pos.alta_express`.
- Rol `ventas` ya no tiene `crm.ver` ni `crm.crear` como permisos base.
- La ficha completa de cliente vive fuera de POS.
- Las escrituras CRM fuertes siguen requiriendo token y respaldo.

## Permisos CRM base y POS

Aplicado el 2026-07-30:

- Token: `CRM_POS_PERMISOS_FINOS`
- Respaldo: `C:\xampp\panel_db_backups\panel_de_control_artianilocal_2026-07-30_antes_crm_pos_permisos_finos.sql`
- Resultado:
  - `ventas`: `crm.pos.buscar`, `crm.pos.alta_express`
  - `crm`: permisos CRM completos y permisos POS finos
  - `administrador_erp`: permisos CRM completos y permisos POS finos
  - `direccion`: consulta/auditoria CRM

## Permisos por submodulo CRM

Aplicado el 2026-07-30:

- Token: `CRM_SUBMODULOS_PERMISOS`
- Respaldo: `C:\xampp\panel_db_backups\panel_de_control_artianilocal_2026-07-30_antes_crm_submodulos_permisos.sql`
- Resultado:
  - permisos creados o actualizados: 9
  - relaciones intentadas: 23
  - roles vinculados: `direccion`, `crm`, `administrador_erp`
  - no se retiraron permisos amplios existentes
  - no se tocaron clientes, POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy

Permisos sembrados:

- `crm.clientes.ver`
- `crm.clientes.editar`
- `crm.seguimiento.ver`
- `crm.seguimiento.operar`
- `crm.comercial.ver`
- `crm.comercial.operar`
- `crm.recompensas.ver`
- `crm.recompensas.operar`
- `crm.reportes.ver`

Verificacion posterior:

- `storage/uat/uat_crm_submodulos_permisos_readonly.php`
- permisos faltantes: ninguno
- relaciones faltantes por rol: ninguna
- `ventas` no recibio consola CRM por submodulo

## Codigo relevante

- `app/controladores/Crm.php`
- `app/modelos/ClientesCrm.php`
- `app/modelos/ClientesCrmEsquema.php`
- `app/modelos/SeguridadEsquema.php`
- `app/core/Controlador.php`
- `app/vistas/includes/header/sidebar.php`
- `app/vistas/paginas/apps/crm/clientes/listado.php`
- `app/vistas/paginas/apps/crm/clientes/ficha.php`
- `app/vistas/paginas/apps/crm/seguimiento/index.php`
- `app/vistas/paginas/apps/crm/comercial/index.php`
- `app/vistas/paginas/apps/crm/recompensas/index.php`
- `app/vistas/paginas/apps/crm/reportes/index.php`
- `public/assets/js/custom/apps/crm/clientes/listado.js`
- `public/assets/js/custom/apps/crm/clientes/ficha.js`
- `public/assets/js/custom/apps/crm/seguimiento/index.js`
- `public/assets/js/custom/apps/crm/comercial/index.js`
- `public/assets/js/custom/apps/crm/recompensas/index.js`
- `public/assets/js/custom/apps/crm/reportes/index.js`

## Scripts UAT recientes

- `storage/uat/uat_crm_pos_permisos_finos_readonly.php`
- `storage/uat/uat_crm_pos_permisos_finos_apply_authorized.php`
- `storage/uat/uat_crm_submodulos_permisos_readonly.php`
- `storage/uat/uat_crm_submodulos_permisos_apply_authorized.php`
- `storage/uat/uat_crm_modulos_readonly.php`
- `storage/uat/uat_crm_operativo_readiness_readonly.php`

## Verificacion modular 2026-07-30

Resultado de `storage/uat/uat_crm_modulos_readonly.php`:

- `ok=true`
- modo: read-only
- alcance: CRM modular con Clientes, Seguimiento, Comercial, Recompensas, Reportes y permisos POS finos
- archivos faltantes: ninguno
- metodos faltantes en `Crm.php`: ninguno
- permisos CRM/POS faltantes: ninguno
- roles esperados faltantes: ninguno
- relaciones faltantes por rol: ninguna

Validaciones de sintaxis:

- `C:\xampp\php\php.exe -l app\controladores\Crm.php`: OK
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\crm\comercial\index.php`: OK
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\crm\reportes\index.php`: OK
- `node --check public\assets\js\custom\apps\crm\comercial\index.js`: OK
- `node --check public\assets\js\custom\apps\crm\reportes\index.js`: OK

## Siguiente trabajo recomendado

1. Probar visualmente con usuarios reales:
   - rol `ventas`: debe ver POS/Ventas, no consola CRM completa;
   - rol `crm`: debe ver CRM Clientes, Seguimiento, Comercial y Recompensas;
   - rol `direccion`: debe ver CRM en lectura.
2. Continuar CRM operativo:
   - pulir pantallas dedicadas por submodulo;
   - `CRM > Comercial` ya existe como ruta propia para resumen y tipos de cliente;
   - `CRM > Reportes` ya existe como tablero propio read-only.
3. Antes de conectar POS con recompensas:
   - definir contrato de acumulacion/redencion;
   - exigir snapshot de venta y evento CRM;
   - mantener escritura con token/respaldo hasta cerrar UAT.

## Avance UI 2026-07-30

- `CRM > Seguimiento`: pantalla dedicada existente.
- `CRM > Seguimiento`: ahora refresca permisos, muestra paneles operativos de dry-run para registrar interaccion y validar cambio de estatus de tarea.
- `CRM > Comercial`: pantalla dedicada creada; consume resumen comercial y catalogo de segmentos/tipos de cliente.
- `CRM > Recompensas`: pantalla dedicada existente.
- `CRM > Reportes`: pantalla dedicada creada; consume `clientes_reportes_operativos_erp` en modo read-only.
- `CRM > Clientes`: queda visualmente enfocado en busqueda, listado canonico, calidad operativa y acceso a ficha.
- `CRM > Ficha`: permisos finos alineados; `crm.clientes.editar` ya habilita formularios igual que `crm.editar`.
- `CRM > Ficha`: POST dry-run ya envia `X-CSRF-Token`.
- `CRM > Ficha`: calidad operativa muestra accesos directos a Contacto, Consentimiento o Fiscal segun pendientes detectados.
- `CRM > Clientes`: deja de cargar automaticamente Seguimiento, Comercial, Recompensas y Reportes; ahora muestra accesos a sus pantallas dedicadas.
- No hubo escritura de BD en esta fase; los paneles nuevos de Seguimiento solo llaman endpoints dry-run.

Validacion posterior del ajuste Seguimiento:

- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\crm\seguimiento\index.php`: OK
- `node --check public\assets\js\custom\apps\crm\seguimiento\index.js`: OK
- `C:\xampp\php\php.exe -l app\vistas\paginas\apps\crm\clientes\ficha.php`: OK
- `node --check public\assets\js\custom\apps\crm\clientes\ficha.js`: OK
- `storage/uat/uat_crm_modulos_readonly.php`: `ok=true`
- `storage/uat/uat_crm_operativo_readiness_readonly.php --cliente=1`: `ok=true`, sin escritura detectada

Resultado readiness cliente CRM `1`:

- codigo: `CRM-POSUAT-20260628-0001`
- nombre: `Cliente Express UAT`
- calidad operativa: `45/100`, nivel `basica_pos`
- puede usarse en POS: si
- puede contactarse: no
- apto comercial: no
- pendientes:
  - agregar contacto util;
  - marcar permiso de contacto operativo cuando el cliente lo autorice.

## Complementos UAT preparados 2026-07-30

Scripts ajustados:

- `storage/uat/uat_crm_clientes_complemento_dryrun_readonly.php`
- `storage/uat/uat_crm_clientes_complemento_apply_authorized.php`

Campos soportados para contacto:

- `etiqueta`
- `principal`
- `permite_contacto`

Campos soportados para consentimiento:

- `otorgado`
- `medio`
- `evidencia`

Dry-run ejecutados para cliente CRM `1`:

- contacto WhatsApp `3312345678`, principal, permite contacto: `puede_guardar=true`
- consentimiento `contacto_operativo`, otorgado, medio `whatsapp`: `puede_guardar=true`

No hubo escritura de BD.

Siguiente separacion recomendada:

- probar visualmente `CRM > Clientes`, `CRM > Comercial`, `CRM > Seguimiento`, `CRM > Recompensas` y `CRM > Reportes` con roles reales;
- retirar HTML oculto de transicion en Clientes despues de validar UAT visual;
- antes de campanas/recompensas comerciales, completar contacto y consentimiento UAT de un cliente real o UAT;
- autorizar una interaccion o tarea UAT real solo cuando se quiera validar escritura operativa de Seguimiento.
