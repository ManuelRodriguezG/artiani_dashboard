# ERP Ecommerce publico - Activacion operativa Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-12  
Estado: runbook operativo; no ejecutar DDL sin autorizacion explicita.

## Objetivo

Activar la Fase 1 real del catalogo vivo conectado al ERP:

- crear esquema `erp_ecommerce_*`;
- configurar canal publico;
- crear primeras publicaciones;
- validar consumo por API;
- mantener cotizacion real bloqueada hasta politica final.

## Principio

No se activa ecommerce real por tener productos activos. Solo se muestran SKUs publicados desde `erp_ecommerce_publicaciones`.

## Paso 0 - Preflight read-only

Ejecutar:

```bash
php storage/uat/uat_ecommerce_publico_activacion_preflight_readonly.php --respaldo=RUTA_O_REFERENCIA
```

Debe devolver:

- `ok=true`
- respaldo valido;
- DDL pendiente identificado;
- contratos API en verde;
- SKUs publicables detectados;
- registro real de cotizacion bloqueado.

## Paso 1 - Respaldo

Antes de DDL:

- generar respaldo externo;
- confirmar ruta o referencia;
- no continuar sin respaldo legible o referencia externa valida.

## Paso 2 - Autorizar DDL

Token:

```text
ECOMMERCE_PUBLICO_DDL_FASE1
```

Comando:

```bash
php storage/uat/uat_ecommerce_publico_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 --respaldo=RUTA_O_REFERENCIA
```

No usar SQL manual salvo emergencia documentada.

## Paso 3 - Validar esquema

Ejecutar:

```bash
php storage/uat/uat_ecommerce_publico_schema_readonly.php --respaldo=RUTA_O_REFERENCIA
php storage/uat/uat_ecommerce_publico_api_contracts_readonly.php
```

Esperado despues de DDL:

- tablas faltantes `0`;
- API vivo;
- publicaciones todavia `0` si no se han creado;
- registro real de cotizacion sigue bloqueado.

## Paso 4 - Configuracion minima

Configurar en `erp_ecommerce_configuracion`:

- `moneda_default=MXN`
- `whatsapp_numero_principal`
- `whatsapp_mensaje_base`
- `cors_origenes_permitidos`
- `cotizacion_habilitada=1`
- `mostrar_stock_exacto=0`
- `modo_sin_stock=consultar`
- `texto_total_estimado=Total estimado sujeto a confirmacion`
- `url_sitio_publico`

No configurar secretos HMAC como claves publicas.

### Plan read-only de configuracion

Antes de escribir configuracion, generar el paquete revisable:

```bash
php storage/uat/uat_ecommerce_publico_configuracion_plan_readonly.php --whatsapp=NUMERO_WHATSAPP --cors=http://localhost:3000 --url=http://localhost:3000
```

El script:

- devuelve valores actuales si la tabla ya existe;
- propone `UPSERT` para las claves publicas;
- devuelve `sha256_sql`;
- reporta bloqueos si falta tabla, WhatsApp o CORS;
- no ejecuta SQL.

No usar `Access-Control-Allow-Origin: *`. Registrar origenes exactos separados por coma o linea.

### Aplicacion autorizada de configuracion

Despues de aplicar DDL y validar respaldo:

```bash
php storage/uat/uat_ecommerce_publico_configuracion_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND
```

Guardrails:

- requiere token `ECOMMERCE_PUBLICO_CONFIGURACION_FASE1`;
- requiere respaldo externo o referencia suficiente;
- no permite CORS con `*`;
- no guarda secretos HMAC;
- no toca inventario;
- no toca `ecom_*`.

## Paso 5 - Primer lote de publicaciones

Recomendacion:

- iniciar con 10 a 30 SKUs;
- solo SKUs con precio, imagen, categoria y presentacion clara;
- evitar granel/fraccionarios;
- incluir productos de perro/gato y alimento/higiene para probar filtros;
- dejar agotados como `consultar` o `agotado` segun politica.

Flujo:

1. Revisar `/ecommercePublico/publicaciones`.
2. Usar `Preparar` sobre SKU candidato.
3. Generar plan read-only de guardado.
4. Crear publicacion como `borrador` cuando se habilite guardado.
5. Revisar slug/titulo/mascota/necesidades.
6. Cambiar a `publicado` en una accion posterior, no automatica.
7. Validar API.

### Plan read-only de una publicacion

Antes de habilitar guardado real:

```bash
php storage/uat/uat_ecommerce_publico_publicacion_plan_readonly.php --id_sku=1291
```

El script:

- valida que el SKU exista y sea publicable;
- normaliza slug, titulo, mascota y necesidades;
- fuerza `estatus_publicacion=borrador`;
- genera SQL `INSERT ... ON DUPLICATE KEY UPDATE`;
- devuelve `sha256_sql`;
- reporta bloqueos si falta DDL o si se intenta planear como `publicado`;
- no ejecuta SQL.

### Guardado autorizado como borrador

Cuando ya exista DDL, respaldo y decision operativa, guardar un SKU como borrador con:

```bash
php storage/uat/uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=RUTA_O_REFERENCIA --id_sku=1291
```

Guardrails:

- requiere token `ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR`;
- requiere respaldo externo o referencia suficiente;
- fuerza `estatus_publicacion=borrador`;
- no publica automaticamente;
- no toca inventario;
- no toca `ecom_*`;
- no modifica precio ni imagen del Catalogo ERP.

Publicar un borrador debe ser otra accion posterior, con revision de slug, titulo, mascota, necesidades, disponibilidad y politica de agotados.

### Lote inicial sugerido read-only

Antes de activar guardado real se puede generar una propuesta:

```bash
php storage/uat/uat_ecommerce_publico_lote_inicial_readonly.php --limite=30
```

El script:

- lista SKUs publicables;
- evalua un pool amplio de candidatos antes de elegir el lote;
- prioriza disponibilidad publica, perro/gato, necesidades de rotacion, marca, precio y diversidad;
- prepara slug sugerido;
- propone mascota/necesidades si se pueden inferir;
- muestra precio vivo y disponibilidad publica sugerida;
- devuelve advertencias cuando el lote contiene agotados o taxonomia incompleta;
- sugiere `estatus_publicacion=borrador`;
- no escribe BD.

Usar este lote como punto de partida, no como publicacion automatica. Si aparecen SKUs agotados, sustituirlos o publicarlos como `Consultar disponibilidad` solo si operativamente conviene.

## Paso 6 - Validar API con datos reales

Endpoints:

- `GET /ecommercePublico/estado`
- `GET /ecommercePublico/configuracion`
- `GET /ecommercePublico/filtros`
- `GET /ecommercePublico/catalogo`
- `GET /ecommercePublico/producto/{slug}`
- `GET /ecommercePublico/disponibilidad?slug=...`
- `POST /ecommercePublico/cotizacion_dryrun`

No usar `cotizacion_registrar` todavia.

## Paso 7 - Senal para iniciar vista externa con datos reales

Enviar al dueno/proyecto frontend:

```text
Ya puedes iniciar/integrar la vista del ecommerce externo con datos reales.
El ERP tiene DDL aplicado, CORS configurado, WhatsApp configurado y primeras publicaciones activas.
Usa docs/erp_ecommerce_publico_frontend_handoff.md y docs/erp_ecommerce_publico_instrucciones_proyecto_frontend.md.
```

## Fuera de alcance en esta activacion

- Checkout.
- Pasarela.
- Pago online.
- Registro autoservicio de clientes.
- Mascotas del cliente.
- Recompensas.
- Reserva/apartado automatico.
- Descuento de inventario desde web.
- Conversion automatica a POS/Pedidos.
