# CRM Clientes - solicitud de autorizacion recompensas

Fecha: 2026-06-30

## DDL recompensas

Token: `CRM_CLIENTES_RECOMPENSAS_DDL`

Endpoints:

```text
/crm/esquema_plan_clientes_recompensas_crm
/crm/esquema_actualizar_clientes_recompensas_crm
```

Alcance:

- Crea `crm_recompensas_programas`.
- Crea `crm_clientes_recompensas_cuentas`.
- Crea `crm_clientes_recompensas_movimientos`.

Fuera de alcance:

- No crea programas reales.
- No crea cuentas reales.
- No otorga puntos.
- No redime puntos.
- No modifica clientes.
- No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.

Frase sugerida:

```text
AUTORIZO CREAR ESQUEMA CRM RECOMPENSAS usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_RECOMPENSAS_DDL. Entiendo que solo crea tablas de programas, cuentas y movimientos de recompensas CRM, no crea programas reales, no crea cuentas, no otorga ni redime puntos, no modifica clientes, no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
```

## Movimiento de recompensas

Endpoint preparado:

```text
/crm/cliente_recompensa_movimiento_dryrun_erp
/crm/cliente_recompensa_movimiento_crear_autorizado_erp
```

Estado actual:

- Dry-run y apply manual/controlado preparados.
- No existe integracion POS/Ventas.
- No existe apply de movimientos con origen POS/Ventas hasta definir politica completa.

Scripts UAT:

```text
storage\uat\uat_crm_clientes_recompensas_movimiento_dryrun_readonly.php
storage\uat\uat_crm_clientes_recompensas_movimiento_apply_authorized.php
```

Token para apply:

```text
CRM_CLIENTES_RECOMPENSAS_MOVIMIENTO
```

Reglas del dry-run:

- Valida cliente CRM.
- Valida tipo: `acumulacion`, `redencion`, `ajuste`, `caducidad`.
- Valida puntos mayores a cero.
- Valida elegibilidad si existen condiciones comerciales.
- Bloquea redencion/caducidad sin saldo suficiente cuando ya existe cuenta.
- Advierte que clientes legacy no deben recibir recompensas sin revision puntual.
- Bloquea origen `pos` o `ventas` hasta que exista contrato formal de integracion.

Alcance del apply manual:

- Crea movimiento en `crm_clientes_recompensas_movimientos`.
- Actualiza `saldo_puntos` de `crm_clientes_recompensas_cuentas`.
- Registra evento CRM `recompensas_movimiento_aplicado` si existe `crm_clientes_eventos`.
- No conecta POS ni ventas.
- No modifica datos maestros del cliente.
- No toca ecommerce, garantias, apartados, devoluciones ni legacy.

Dry-run UAT validado:

- Fecha: 2026-06-30.
- Comando: `C:\xampp\php\php.exe storage\uat\uat_crm_clientes_recompensas_movimiento_dryrun_readonly.php --cliente=1 --programa=1 --tipo=acumulacion --puntos=10`.
- Cliente: `CRM-POSUAT-20260628-0001`.
- Programa: `PUNTOS_BASE`.
- Cuenta: `id_cliente_recompensa_cuenta=1`.
- Resultado: `puede_aplicar=true`, saldo resultante `10`.
- No escribe BD.

Frase sugerida:

```text
AUTORIZO CREAR MOVIMIENTO CRM RECOMPENSAS usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_RECOMPENSAS_MOVIMIENTO para cliente CRM [ID_CLIENTE_CRM], programa [ID_PROGRAMA_RECOMPENSA], tipo [TIPO] y puntos [PUNTOS]. Entiendo que crea un movimiento manual/controlado de recompensas CRM y actualiza el saldo de la cuenta, no conecta POS ni ventas, no modifica datos maestros del cliente, no toca ecommerce, garantias, apartados, devoluciones ni legacy.
```

Apply UAT autorizado:

- Fecha: 2026-06-30.
- Respaldo usado:
  - `C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_recompensas.sql`.
- Token:
  - `CRM_CLIENTES_RECOMPENSAS_MOVIMIENTO`.
- Movimiento creado:
  - `id_cliente_recompensa_movimiento=1`;
  - cuenta `id_cliente_recompensa_cuenta=1`;
  - cliente CRM `1`;
  - programa `1`;
  - tipo `acumulacion`;
  - puntos `10`;
  - saldo resultante `10`;
  - origen `crm/uat_manual/UAT-CRM-RECOMPENSAS-001`.
- Verificacion post-apply:
  - `crm_recompensas_programas`: `1`;
  - `crm_clientes_recompensas_cuentas`: `1`;
  - `crm_clientes_recompensas_movimientos`: `1`;
  - saldo de cuenta `10.000000`.
- Alcance respetado:
  - no conecta POS ni ventas;
  - no modifica datos maestros del cliente;
  - no toca ecommerce, garantias, apartados, devoluciones ni legacy.

Decision pendiente:

- Definir si los puntos se acumulan por monto pagado, margen, SKU, categoria, frecuencia, campana o regla manual.
- Definir si ventas/POS aplicara puntos en tiempo real o mediante proceso posterior.
- Definir caducidad y redenciones antes de crear apply real.

## Programa de recompensas

Endpoints preparados:

```text
/crm/cliente_recompensa_programa_dryrun_erp
/crm/cliente_recompensa_programa_crear_autorizado_erp
```

Scripts UAT:

```text
storage\uat\uat_crm_clientes_recompensas_programa_dryrun_readonly.php
storage\uat\uat_crm_clientes_recompensas_programa_apply_authorized.php
storage\uat\uat_crm_clientes_recompensas_post_apply_readonly.php
```

Token para apply:

```text
CRM_CLIENTES_RECOMPENSAS_PROGRAMA
```

Alcance del apply:

- Crea un registro en `crm_recompensas_programas`.
- Guarda reglas JSON normalizadas.
- No crea cuentas de clientes.
- No crea movimientos.
- No otorga ni redime puntos.
- No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.

Reglas validadas:

- Codigo unico de 3 a 60 caracteres en mayusculas, numeros, guion o guion bajo.
- Tipo permitido: `puntos`, `monedero`, `niveles`, `mixto`.
- Estatus permitido: `activo`, `pausado`, `inactivo`.
- Acumulacion permitida por `monto_pagado`, `margen`, `sku`, `categoria`, `manual` o `campana`.
- Redencion en `pendiente`, `descuento_pos`, `monedero` o `manual`.
- Redencion activa exige valor de punto.

Frase sugerida:

```text
AUTORIZO CREAR PROGRAMA CRM RECOMPENSAS usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_RECOMPENSAS_PROGRAMA para codigo [CODIGO], nombre [NOMBRE] y tipo [TIPO]. Entiendo que solo crea un programa de recompensas CRM con reglas JSON, no crea cuentas, no crea movimientos, no otorga ni redime puntos, no modifica clientes, no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
```

Dry-run UAT validado:

- Fecha: 2026-06-30.
- Comando: `C:\xampp\php\php.exe storage\uat\uat_crm_clientes_recompensas_programa_dryrun_readonly.php`.
- Programa candidato: `PUNTOS_BASE`.
- Resultado: `puede_crear=true`, sin bloqueos.
- No escribe BD.

Apply UAT autorizado:

- Fecha: 2026-06-30.
- Respaldo usado:
  - `C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_recompensas.sql`.
- Token:
  - `CRM_CLIENTES_RECOMPENSAS_PROGRAMA`.
- Programa creado:
  - `id_programa_recompensa=1`;
  - codigo `PUNTOS_BASE`;
  - nombre `Programa base de puntos`;
  - tipo `puntos`;
  - estatus `activo`.
- Verificacion post-apply:
  - `crm_recompensas_programas`: `1`;
  - `crm_clientes_recompensas_cuentas`: `0`;
  - `crm_clientes_recompensas_movimientos`: `0`.
- Alcance respetado:
  - no crea cuentas;
  - no crea movimientos;
  - no otorga ni redime puntos;
  - no modifica clientes;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.

## Cuenta de recompensas por cliente

Endpoints preparados:

```text
/crm/cliente_recompensa_cuenta_dryrun_erp
/crm/cliente_recompensa_cuenta_crear_autorizado_erp
```

Scripts UAT:

```text
storage\uat\uat_crm_clientes_recompensas_cuenta_dryrun_readonly.php
storage\uat\uat_crm_clientes_recompensas_cuenta_apply_authorized.php
```

Token para apply:

```text
CRM_CLIENTES_RECOMPENSAS_CUENTA
```

Alcance del apply:

- Crea una cuenta en `crm_clientes_recompensas_cuentas`.
- La cuenta nace con saldo de puntos `0`.
- La cuenta nace con equivalente monetario `0`.
- Puede guardar nivel inicial si se indica.
- Crea evento CRM `recompensas_cuenta_creada` si existe `crm_clientes_eventos`.
- No crea movimientos.
- No otorga ni redime puntos.
- No modifica datos maestros del cliente.
- No toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.

Reglas validadas:

- Cliente CRM existente y activo.
- Cliente legacy queda bloqueado sin revision puntual.
- Programa de recompensas existente y activo.
- No permite duplicar cuenta para el mismo cliente/programa.
- Si existen condiciones comerciales, respeta `permite_recompensas` y `bloqueo_comercial`.

Dry-run UAT validado:

- Fecha: 2026-06-30.
- Comando: `C:\xampp\php\php.exe storage\uat\uat_crm_clientes_recompensas_cuenta_dryrun_readonly.php --cliente=1 --programa=1`.
- Cliente: `CRM-POSUAT-20260628-0001`, `Cliente Express UAT`.
- Programa: `PUNTOS_BASE`.
- Resultado: `puede_crear=true`, sin bloqueos.
- Aviso: condiciones comerciales finas pendientes.
- No escribe BD.

Frase sugerida:

```text
AUTORIZO CREAR CUENTA CRM RECOMPENSAS usando respaldo [RUTA_RESPALDO] con token CRM_CLIENTES_RECOMPENSAS_CUENTA para cliente CRM [ID_CLIENTE_CRM] y programa [ID_PROGRAMA_RECOMPENSA]. Entiendo que solo crea una cuenta de recompensas CRM con saldo cero, no crea movimientos, no otorga ni redime puntos, no modifica datos maestros del cliente, no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
```

Apply UAT autorizado:

- Fecha: 2026-06-30.
- Respaldo usado:
  - `C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_recompensas.sql`.
- Token:
  - `CRM_CLIENTES_RECOMPENSAS_CUENTA`.
- Cuenta creada:
  - `id_cliente_recompensa_cuenta=1`;
  - cliente CRM `1`;
  - programa `1`;
  - saldo inicial `0`.
- Verificacion post-apply:
  - `crm_recompensas_programas`: `1`;
  - `crm_clientes_recompensas_cuentas`: `1`;
  - `crm_clientes_recompensas_movimientos`: `0`.
- Alcance respetado:
  - no crea movimientos;
  - no otorga ni redime puntos;
  - no modifica datos maestros del cliente;
  - no toca POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.

## Ejecucion DDL autorizada

Fecha: 2026-06-30

Respaldo externo generado:

```text
C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_recompensas.sql
```

Script aplicado:

```text
C:\xampp\php\php.exe storage\uat\uat_crm_clientes_recompensas_schema_apply_authorized.php --autorizar=CRM_CLIENTES_RECOMPENSAS_DDL --respaldo="C:\respaldos\panel_artianilocal_2026-06-30_antes_crm_recompensas.sql"
```

Resultado:

- `crm_recompensas_programas` creada y con `0` filas.
- `crm_clientes_recompensas_cuentas` creada y con `0` filas.
- `crm_clientes_recompensas_movimientos` creada y con `0` filas.
- No se crearon programas reales.
- No se crearon cuentas.
- No se otorgaron ni redimieron puntos.
- No se modificaron clientes, POS, ventas, ecommerce, garantias, apartados, devoluciones ni legacy.
