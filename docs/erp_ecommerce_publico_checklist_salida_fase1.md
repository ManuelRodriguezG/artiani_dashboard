# ERP Ecommerce publico - Checklist para iniciar proyecto frontend externo

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-12  
Estado: checklist de salida; no autoriza DDL ni inicio automatico de tienda.

## Estado actual

El ERP ya tiene contratos API preparados para el ecommerce externo, pero todavia no esta listo para una tienda publica con productos reales porque falta aplicar DDL y crear publicaciones.

Resumen vivo:

- `docs/erp_ecommerce_publico_estado_actual.md`

Estado:

- Contratos API: listo para integracion tecnica read-only.
- Manifiesto `/ecommercePublico/contratos`: listo.
- Estado `/ecommercePublico/estado`: listo.
- Configuracion publica `/ecommercePublico/configuracion`: listo con defaults.
- Catalogo `/ecommercePublico/catalogo`: listo, pero devolvera vacio hasta publicar.
- Cotizacion dry-run: listo, pero sin publicaciones reales responde pendiente.
- Registro real de cotizacion: bloqueado.
- Guardado interno de publicaciones: bloqueado.
- DDL `erp_ecommerce_*`: pendiente de autorizacion.
- Publicaciones reales: pendientes.

## Semaforo de inicio del frontend externo

Comando oficial read-only:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_readiness_readonly.php
```

Comando completo de activacion/readiness:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_activacion_bundle_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND
```

Este bundle valida host HTTP real, contratos, DDL, configuracion, publicabilidad, lote inicial y bloqueos para pasar a datos reales. No escribe BD.

La salida `senal_frontend` puede ser:

- `amarillo_mock_contratos`: se puede iniciar frontend como maqueta tecnica/cliente API.
- `verde_datos_reales`: se puede integrar la vista externa con productos reales publicados.
- `rojo_bloqueado`: resolver contratos/API antes de iniciar.

### Amarillo para iniciar maqueta tecnica

Ya se puede iniciar el proyecto frontend externo si el objetivo es:

- crear estructura del proyecto;
- configurar variables de entorno;
- construir cliente API;
- consumir `/estado`, `/contratos` y `/configuracion`;
- preparar pantallas con estados vacios;
- usar mocks o respuestas vacias controladas;
- preparar carrito local y llamada a `cotizacion_dryrun`.

### Verde para integrar datos reales

Solo avanzar a datos reales cuando el semaforo diga `verde_datos_reales`.

Debe estar resuelto:

- DDL `erp_ecommerce_*` aplicado;
- `whatsapp_numero_principal` configurado;
- `cors_origenes_permitidos` configurado;
- primeras publicaciones internas creadas y publicadas;
- `/catalogo` con productos publicados;
- `/cotizacion_dryrun` con publicaciones reales;
- `cotizacion_registrar` todavia bloqueado salvo cambio posterior especifico de seguridad/CRM.

### Pendiente para datos reales

Esperar antes de prometer catalogo vivo real. Faltan:

- aplicar DDL `erp_ecommerce_*`;
- configurar `whatsapp_numero_principal`;
- configurar `cors_origenes_permitidos`;
- crear primeras publicaciones internas;
- validar `/catalogo` con productos publicados;
- validar `/cotizacion_dryrun` con publicaciones reales.

### Rojo para produccion

No publicar en produccion todavia si falta cualquiera de estos puntos:

- API key/firma HMAC si el ecommerce esta en otro dominio publico;
- rate limit;
- politica de contacto/seguimiento;
- pruebas visuales y contract tests;
- decision sobre productos agotados;
- decision sobre privacidad/consentimiento de contacto.

## Checklist tecnica antes de iniciar frontend

- [ ] Definir stack del frontend externo.
- [ ] Definir dominio local/dev del ecommerce.
- [ ] Definir `ERP_API_BASE_URL`.
- [ ] Confirmar si el frontend usara backend propio o sera SPA estatica.
- [ ] Si sera SPA estatica, no guardar secretos HMAC en navegador.
- [ ] Preparar cliente API con timeouts y manejo de errores.
- [ ] Consumir primero `/ecommercePublico/estado`.
- [ ] Si `ready=false`, mostrar estado de catalogo en preparacion.
- [ ] Consumir `/ecommercePublico/contratos` para verificar version.
- [ ] Consumir `/ecommercePublico/configuracion`.
- [ ] Preparar componentes para catalogo vacio.

## Checklist ERP antes de datos reales

- [ ] Ejecutar UAT read-only de contratos.
- [ ] Respaldar BD externamente.
- [ ] Autorizar DDL con token `ECOMMERCE_PUBLICO_DDL_FASE1`.
- [ ] Aplicar DDL solo con script autorizado.
- [ ] Validar `GET /ecommercePublico/estado`.
- [ ] Configurar `cors_origenes_permitidos`.
- [ ] Configurar `whatsapp_numero_principal`.
- [ ] Habilitar guardado interno de publicaciones.
- [ ] Crear primeras publicaciones en borrador.
- [ ] Publicar un lote pequeno de SKUs.
- [ ] Validar `GET /ecommercePublico/catalogo`.
- [ ] Validar `POST /ecommercePublico/cotizacion_dryrun`.
- [ ] Validar plan futuro de registro sin desbloquear escrituras:
      `C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_cotizacion_registro_plan_readonly.php --base=http://panel.com.local --origin=http://localhost:5173`

## Senal para iniciar vista externa con datos reales

Aviso recomendado:

```text
Ya puedes iniciar/integrar la vista del ecommerce externo con datos reales.
El ERP tiene DDL aplicado, CORS configurado, WhatsApp configurado y primeras publicaciones activas.
Usa docs/erp_ecommerce_publico_frontend_handoff.md y docs/erp_ecommerce_publico_api_contratos.md.
```

## Senal actual

La senal actual es:

```text
Puedes iniciar el proyecto frontend externo como maqueta tecnica/cliente API, pero aun no como catalogo vivo real.
```

