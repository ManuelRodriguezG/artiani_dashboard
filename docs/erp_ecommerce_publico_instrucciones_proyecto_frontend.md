# ERP Ecommerce publico - Instrucciones para proyecto frontend externo

> Documento historico. La guia viva para iniciar el nuevo frontend es:
> `docs/erp_ecommerce_publico_instrucciones_frontend_nuevo_proyecto.txt`.
> El prompt corto para arrancar el otro proyecto es:
> `docs/erp_ecommerce_publico_prompt_inicio_frontend.txt`.

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: referencia historica; no usar como fuente principal si contradice la guia nueva.

## Objetivo del proyecto frontend

Construir una vista ecommerce publica separada del ERP, orientada a mascotas, que consuma contratos API del ERP:

- catalogo de productos publicados;
- filtros por mascota/necesidad;
- detalle de producto;
- carrito local tipo cotizacion;
- validacion dry-run del carrito;
- envio por WhatsApp cuando el ERP tenga configuracion.

No construir checkout ni pasarela en Fase 1.

## Variables de entorno

```env
ERP_API_BASE_URL=http://panel.com.local
ERP_ECOMMERCE_BASE_PATH=/ecommercePublico
ERP_ECOMMERCE_API_VERSION=fase1-2026-07-12
ERP_ECOMMERCE_API_KEY=
ERP_ECOMMERCE_API_SECRET=
```

Produccion debe cambiar `ERP_API_BASE_URL` al dominio real del ERP.

## Cliente API recomendado

Crear una capa `erpEcommerceApi` con metodos:

- `getEstado()`
- `getContratos()`
- `getConfiguracion()`
- `getFiltros()`
- `getCatalogo(params)`
- `getProducto(slug)`
- `getDisponibilidad({id_sku, slug})`
- `cotizacionDryRun(payload)`

Reglas:

- Manejar `error=true` como error funcional.
- Manejar `tipo=info` como estado de preparacion, no como fallo fatal.
- Validar `api.version`.
- No depender de campos fuera de `depurar`.
- No leer tablas ni endpoints internos del ERP.

## Flujo inicial de la app

1. Cargar `getEstado()`.
2. Si `ready=false`, permitir catalogo vacio y mostrar estado de preparacion.
3. Cargar `getConfiguracion()`.
4. Cargar `getFiltros()`.
5. Cargar `getCatalogo()`.
6. Renderizar grilla o estado vacio.
7. Permitir carrito local.
8. Antes de WhatsApp, llamar `cotizacionDryRun(payload)`.
9. Usar `whatsapp_preview` o construir mensaje con datos recalculados.

## Rutas sugeridas del frontend

- `/` catalogo principal.
- `/producto/:slug` detalle.
- `/cotizacion` carrito/cotizacion.
- `/estado` opcional para diagnostico interno del frontend.

## Estados de interfaz obligatorios

- API no disponible.
- API disponible pero `ready=false`.
- Catalogo configurado pero sin productos publicados.
- Producto no encontrado.
- Producto agotado.
- Cotizacion dry-run con bloqueos.
- WhatsApp no configurado.

## Filtros Fase 1

- Busqueda libre.
- Mascota/especie.
- Necesidad.
- Marca.
- Categoria.

No filtrar por stock exacto.

## Carrito

El carrito vive localmente hasta que el ERP autorice registro real.

Campos minimos por item:

- `id_publicacion`
- `id_sku`
- `slug`
- `nombre`
- `cantidad`

No confiar en:

- precio guardado en localStorage;
- disponibilidad guardada en localStorage;
- total calculado solo por frontend.

Siempre revalidar con:

```http
POST /ecommercePublico/cotizacion_dryrun
```

## WhatsApp

Mientras `whatsapp_numero_principal` venga vacio:

- no hardcodear numero;
- mostrar "WhatsApp pendiente de configuracion" o canal temporal de pruebas;
- no marcar pedido como recibido.

Cuando venga configurado:

- usar total recalculado por dry-run;
- incluir texto "total estimado sujeto a confirmacion";
- no decir "pedido confirmado";
- no decir "pagado".

## Seguridad

Fase 1:

- CORS abierto para `http://artiani.com.local` y cerrado para origenes no configurados.
- API key/HMAC documentado pero no requerido.
- Si el frontend corre en otro host/puerto, el navegador puede bloquear llamadas directas hasta que el ERP tenga ese origen exacto configurado.
- Para desarrollo temprano usar mocks, fixture JSON o proxy local del framework; no interpretar el bloqueo CORS como fallo del endpoint si el smoke HTTP responde JSON.

Produccion:

- No usar secretos en navegador.
- Si hay firma HMAC, implementarla en backend del ecommerce.
- No usar `Access-Control-Allow-Origin: *`.
- No habilitar POST real sin rate limit.

## No hacer

- No construir checkout.
- No integrar pasarela.
- No descontar inventario desde frontend.
- No leer `ecom_*`.
- No leer tablas directas del ERP.
- No mostrar stock exacto.
- No vender granel como unidad cerrada.
- No crear cliente CRM automaticamente desde la web.

## Documentos base

- `docs/erp_ecommerce_publico_frontend_handoff.md`
- `docs/erp_ecommerce_publico_api_contratos.md`
- `docs/erp_ecommerce_publico_checklist_salida_fase1.md`
- `docs/erp_ecommerce_catalogo_vivo_fase1.md`
