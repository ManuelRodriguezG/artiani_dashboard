# ERP Ecommerce publico - Checklist activacion partner

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-25  
Estado: checklist operativo; no autoriza escrituras por si solo.

## Objetivo

Activar un partner/mayorista para consumir catalogo ecommerce sin romper el frontend propio Artiani y sin exponer el ERP a consumo abierto.

## Datos requeridos del partner

- nombre comercial;
- razon social opcional;
- contacto responsable;
- email;
- telefono;
- dominio local/pruebas;
- dominio produccion;
- si tendra backend propio;
- si solo sera frontend estatico;
- productos o categorias autorizadas;
- politica de precio: publico, mayoreo, consultar;
- limite esperado de trafico.

## Orden correcto

1. Validar que Artiani sigue verde.
2. Validar plan partner read-only.
3. Crear respaldo externo en `C:\xampp\panel_db_backups`.
4. Aplicar DDL canales API con autorizacion textual.
5. Crear canal `artiani_web`.
6. Crear canal partner en `borrador`.
7. Asociar productos al canal partner.
8. Emitir credencial una sola vez.
9. Probar modo observacion de autenticacion.
10. Probar solo lectura.
11. Activar logs/rate limit.
12. Evaluar `cotizacion_registrar`.

## Comandos read-only

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_entregable_gate_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus_preview=415,866,386,1138 --min_publicadas=2 --min_preview=6
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_partner_api_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --partner_origin=https://partner.example.com
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_hmac_contract_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_api_apply_guard_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_seed_plan_readonly.php --base=http://panel.com.local --artiani_origin=http://artiani.com.local --artiani_prod=https://artiani.com.mx --partner_codigo=partner_mayoreo_001 --partner_origin=https://partner.example.com
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_seed_apply_guard_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canal_allowlist_plan_readonly.php --canal=partner_mayoreo_001 --publicaciones=1,2 --modo_precio=publico
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canal_allowlist_apply_guard_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_credencial_plan_readonly.php --canal=partner_mayoreo_001 --modo=hmac
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_credencial_emitir_apply_guard_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_auth_observacion_readonly.php --method=GET --path=/ecommercePublico/catalogo --query=limite=2 --origin=http://artiani.com.local
```

## Texto de autorizacion futuro para DDL

```text
AUTORIZO APLICAR DDL CANALES API ECOMMERCE usando respaldo C:\xampp\panel_db_backups\[ARCHIVO].sql con token ECOMMERCE_PUBLICO_CANALES_API_DDL. Entiendo que solo crea tablas de canales, credenciales, allowlist y logs; no genera secretos, no publica productos, no habilita partners, no registra cotizaciones, no toca inventario ni legacy ecom_*.
```

Comando apply autorizado futuro:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_api_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CANALES_API_DDL --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
```

## Texto de autorizacion futuro para semillas

```text
AUTORIZO SEMBRAR CANALES API ECOMMERCE usando respaldo C:\xampp\panel_db_backups\[ARCHIVO].sql con token ECOMMERCE_PUBLICO_CANALES_SEED para artiani_web y partner_mayoreo_001. Entiendo que solo crea/actualiza canales, deja el partner en borrador, no genera credenciales, no asigna productos, no registra cotizaciones, no toca inventario ni legacy ecom_*.
```

Comando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_seed_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CANALES_SEED --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql --artiani_origin=http://artiani.com.local --artiani_prod=https://artiani.com.mx --partner_codigo=partner_mayoreo_001 --partner_origin=https://partner.example.com
```

## Texto de autorizacion futuro para allowlist

```text
AUTORIZO ASIGNAR ALLOWLIST ECOMMERCE PARTNER usando respaldo C:\xampp\panel_db_backups\[ARCHIVO].sql con token ECOMMERCE_PUBLICO_CANAL_ALLOWLIST para canal partner_mayoreo_001 y publicaciones [IDS]. Entiendo que solo asigna publicaciones existentes al canal, no activa el partner, no genera credenciales, no publica productos nuevos, no registra cotizaciones, no toca inventario ni legacy ecom_*.
```

Comando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canal_allowlist_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CANAL_ALLOWLIST --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql --canal=partner_mayoreo_001 --publicaciones=1,2 --modo_precio=publico
```

## Texto de autorizacion futuro para credencial

```text
AUTORIZO EMITIR CREDENCIAL API ECOMMERCE usando respaldo C:\xampp\panel_db_backups\[ARCHIVO].sql con token ECOMMERCE_PUBLICO_CREDENCIAL_EMITIR para canal partner_mayoreo_001. Entiendo que el secreto se muestra una sola vez, no debe pegarse en JavaScript publico, no activa el partner, no asigna productos, no habilita cotizacion_registrar, no toca inventario ni legacy ecom_*.
```

Comando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_credencial_emitir_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CREDENCIAL_EMITIR --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql --canal=partner_mayoreo_001 --modo=hmac
```

## Guardrails

- No dar secreto a frontend publico.
- No habilitar todos los productos por defecto.
- No mostrar stock exacto.
- No exponer costos/proveedores.
- No permitir ventas automaticas.
- No descontar inventario desde API.
- No activar registro de cotizaciones sin rate limit/logs.
- No mezclar con `ecom_*`.
