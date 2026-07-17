# ERP Ecommerce publico - Orden de activacion autorizada

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-16  
Estado: plantilla previa a escrituras; no autoriza nada por si sola.

## Proposito

Este documento sirve para pasar de `amarillo_mock_contratos` a `verde_datos_reales` sin improvisar.

No ejecutar ningun `apply_authorized` sin:

- respaldo externo real;
- WhatsApp real;
- origen CORS real;
- URL publica/dev real del frontend;
- autorizacion explicita del dueno.

## Datos que necesito del dueno

```text
RESPALDO_EXTERNO=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql
WHATSAPP_NUMERO_RECIBIDO=3322068429
WHATSAPP_NUMERO_PRINCIPAL=523322068429
ORIGEN_FRONTEND=http://artiani.com.local
URL_FRONTEND=http://artiani.com.local
URL_FRONTEND_PRODUCCION_FUTURA=https://artiani.com.mx
SKU_1=1759
SKU_2=1757
```

Ruta estandar de respaldo:

```text
docs/erp_respaldo_bd_estandar.md
```

Sobre `ORIGEN_FRONTEND`:

- Es el origen CORS exacto desde donde el navegador del ecommerce llamara al ERP.
- Para pruebas locales de este proyecto sera `http://artiani.com.local`.
- Si se usa Vite puro sin virtual host, podria ser `http://localhost:5173`, pero no sera el valor inicial de esta activacion.
- En produccion sera el dominio real del ecommerce: `https://artiani.com.mx`.

Sobre `URL_FRONTEND`:

- Es la URL base publica del ecommerce para SEO, links y mensajes.
- En desarrollo puede ser igual a `ORIGEN_FRONTEND`.
- Para pruebas locales de esta activacion sera `http://artiani.com.local`.
- En produccion debe cambiarse a `https://artiani.com.mx`.

Sobre `WHATSAPP_NUMERO_PRINCIPAL`:

- El numero recibido fue `3322068429`.
- Para `wa.me` se configura en formato internacional sin `+`: `523322068429`.

No usar:

```text
RUTA_O_REFERENCIA
REVISION_READONLY
NUMERO_WHATSAPP
ORIGEN_FRONTEND
URL_FRONTEND
*
```

## Validacion previa obligatoria

Primero revisar semaforo completo:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_activacion_suite_readonly.php --base=http://panel.com.local --origin=ORIGEN_FRONTEND
```

Primero generar paquete de autorizacion:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_autorizacion_paquete_readonly.php --base=http://panel.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --whatsapp=523322068429 --cors=http://artiani.com.local --url=http://artiani.com.local --sku1=1759 --sku2=1757
```

Luego checklist:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --whatsapp=523322068429 --cors=http://artiani.com.local --url=http://artiani.com.local --sku1=1759 --sku2=1757
```

Ambos deben devolver `ok=true` antes de ejecutar escrituras.

## Texto de autorizacion DDL

Copiar de forma explicita:

```text
Autorizo aplicar DDL ecommerce publico Fase 1 con token ECOMMERCE_PUBLICO_DDL_FASE1 usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql. Confirmo que no se activara checkout, pagos online ni descuento de inventario.
```

Comando que se ejecutaria despues:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql
```

## Texto de autorizacion configuracion

Copiar de forma explicita:

```text
Autorizo guardar configuracion ecommerce publico Fase 1 con token ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql, WhatsApp 523322068429, CORS http://artiani.com.local y URL http://artiani.com.local.
```

Comando que se ejecutaria despues:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_configuracion_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --whatsapp=523322068429 --cors=http://artiani.com.local --url=http://artiani.com.local
```

## Texto de autorizacion borradores

Copiar de forma explicita:

```text
Autorizo crear borradores ecommerce publico Fase 1 con token ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR para SKU_1 y SKU_2 usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql. Confirmo que no se publican automaticamente ni se afecta inventario.
```

Comandos que se ejecutarian despues:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --id_sku=1759
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --id_sku=1757
```

## Texto de autorizacion publicacion inicial

Copiar de forma explicita:

```text
Autorizo publicar el primer borrador ecommerce publico Fase 1 con token ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR para SKU_1 usando respaldo C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql. Confirmo que revise slug, titulo, mascota, necesidad, precio y disponibilidad.
```

Comando que se ejecutaria despues:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_publicar_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --id_sku=1759 --confirmar_revision=1
```

## Verificacion posterior

Despues de cada escritura:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

El frontend externo solo pasa a datos reales cuando `green_gate` devuelve:

```text
ok=true
senal_frontend=verde_datos_reales
```

## Candados

- `cotizacion_registrar` sigue bloqueado.
- No se crea checkout.
- No se cobra online.
- No se descuenta inventario.
- No se usan tablas `ecom_*`.
- No se muestra stock exacto.
