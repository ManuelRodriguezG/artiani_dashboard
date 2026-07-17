# ERP Ecommerce publico - Solicitud de autorizacion DDL Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-11  
Estado: solicitud preparada; no autoriza ni ejecuta cambios por si sola.

## Proposito

Preparar la autorizacion formal para crear el esquema minimo de ecommerce publico Fase 1: publicaciones de catalogo, configuracion, cotizaciones y eventos. Este paquete no crea checkout, no cobra online, no descuenta inventario y no toca tablas legacy `ecom_*`.

## Alcance solicitado

Crear, si no existen, las tablas:

- `erp_ecommerce_publicaciones`
- `erp_ecommerce_configuracion`
- `erp_ecommerce_cotizaciones`
- `erp_ecommerce_cotizaciones_detalle`
- `erp_ecommerce_cotizaciones_eventos`

El DDL se genera desde `app/modelos/EcommercePublicoEsquema.php`.

## Fuera de alcance

- Migrar datos desde `ecom_*`.
- Crear publicaciones automaticamente.
- Registrar carritos/cotizaciones reales.
- Crear pedidos, ventas, atenciones POS o apartados.
- Reservar o descontar inventario.
- Modificar clientes CRM.
- Configurar pasarela de pago.

## Requisitos antes de autorizar

1. Respaldo externo legible o referencia externa verificable.
2. Revision read-only del plan con:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_schema_readonly.php --respaldo=RUTA_O_REFERENCIA
```

3. Confirmacion textual del dueno del proyecto con el token:

```text
ECOMMERCE_PUBLICO_DDL_FASE1
```

4. Aplicacion solo mediante script bloqueado por defecto:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 --respaldo=RUTA_O_REFERENCIA
```

## Riesgo operativo

Riesgo bajo si se ejecuta despues de respaldo: son tablas nuevas y no alteran tablas existentes. Aun asi, se exige respaldo porque habilita nueva superficie de captura futura y porque el esquema queda persistente en BD.

## Decision recomendada

Autorizar solo despues de validar la pantalla interna read-only `/ecommercePublico/publicaciones` y confirmar que la Fase 1 seguira como catalogo vivo/cotizacion por WhatsApp, sin checkout ni inventario automatico.

