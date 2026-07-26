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
9. Probar solo lectura.
10. Activar logs/rate limit.
11. Evaluar `cotizacion_registrar`.

## Comandos read-only

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_entregable_gate_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus_preview=415,866,386,1138 --min_publicadas=2 --min_preview=6
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_partner_api_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --partner_origin=https://partner.example.com
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_hmac_contract_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_api_apply_guard_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_seed_plan_readonly.php --base=http://panel.com.local --artiani_origin=http://artiani.com.local --artiani_prod=https://artiani.com.mx --partner_codigo=partner_mayoreo_001 --partner_origin=https://partner.example.com
```

## Texto de autorizacion futuro para DDL

```text
AUTORIZO APLICAR DDL CANALES API ECOMMERCE usando respaldo C:\xampp\panel_db_backups\[ARCHIVO].sql con token ECOMMERCE_PUBLICO_CANALES_API_DDL. Entiendo que solo crea tablas de canales, credenciales, allowlist y logs; no genera secretos, no publica productos, no habilita partners, no registra cotizaciones, no toca inventario ni legacy ecom_*.
```

Comando apply autorizado futuro:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_canales_api_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CANALES_API_DDL --respaldo=C:\xampp\panel_db_backups\[ARCHIVO].sql
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
