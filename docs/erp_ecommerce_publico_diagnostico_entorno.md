# ERP Ecommerce publico - Diagnostico de entorno local

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-16  
Estado: guia read-only para distinguir fallas de XAMPP/MySQL de fallas del API ecommerce.

## Objetivo

Antes de probar el frontend externo contra `http://panel.com.local/ecommercePublico`, validar que el entorno local este sano.

## Comando principal

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_entorno_readonly.php --base=http://panel.com.local
```

Este script:

- no inicia servicios;
- no repara tablas;
- no escribe BD;
- prueba conexion TCP a MySQL `127.0.0.1:3306`;
- prueba `GET /ecommercePublico/estado`;
- lee logs locales de MySQL y Apache para diagnostico.

## Interpretacion

Si devuelve `ok=false` con:

- `mysql_no_acepta_conexion_tcp_3306`;
- `api_http_no_responde_json`;
- `mysql_log_indica_tabla_sistema_corrupta`;

entonces no interpretar fallas de smoke, lote inicial, publicaciones o frontend como errores del contrato ecommerce. Primero resolver XAMPP/MySQL.

Si el API responde JSON y MySQL acepta conexion, pero aparecen advertencias historicas del log, se pueden tratar como seguimiento tecnico y no como bloqueo inmediato de contratos.

## Estado observado

El entorno local reporto:

```text
mysql_no_acepta_conexion_tcp_3306
api_http_no_responde_json
mysql_log_indica_tabla_sistema_corrupta
```

El log de MySQL indica corrupcion en tabla de sistema `mysql.plugin`.

## Guardrails

- No ejecutar reparaciones sobre tablas de sistema sin autorizacion explicita.
- No aplicar DDL ecommerce mientras MySQL no este sano.
- No publicar SKUs ni registrar configuracion mientras el entorno este en rojo.
- Los fixtures del frontend no dependen de MySQL y no prueban datos reales.

## Continuacion cuando el entorno este sano

Ejecutar en orden:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_entorno_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_api_contracts_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_package_readonly.php --base=http://panel.com.local
```

Despues de eso, seguir el runbook:

```text
docs/erp_ecommerce_publico_activacion_operativa.md
```
