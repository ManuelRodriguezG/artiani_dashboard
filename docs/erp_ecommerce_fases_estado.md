# ERP Ecommerce - Fases y estado vivo

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-04  
Estado: documento vivo para continuar este chat por fases sin revolver modulos.

## Regla de trabajo

- Este documento marca en que fase vamos, que falta y cuando pasar a la siguiente.
- Frontend externo debe consumir API; no debe leer archivos internos del ERP.
- Las escrituras de BD, DDL, migraciones o publicaciones masivas requieren autorizacion explicita, respaldo y token correspondiente.
- No tocar inventario, precios ERP, imagenes ERP ni legacy `ecom_*` desde ecommerce publico.

## Fases acordadas

1. Panel Ecommerce / Publicaciones.
2. API de catalogo mas robusta.
3. Carrito / cotizacion avanzada.
4. Analytics ecommerce.
5. Clientes / mascotas.
6. Partners / API dinamica.
7. SEO y contenido.
8. Panel de operacion ecommerce.

## Fase actual

Fase 1: Panel Ecommerce / Publicaciones.

Objetivo:

- Controlar que productos se muestran en ecommerce Artiani.
- Buscar por SKU, nombre, marca y categoria.
- Filtrar por estatus, disponibilidad publica y granel.
- No publicar productos a granel/fraccionarios en ecommerce.
- Guardar borradores, editar curaduria, publicar, pausar y reactivar.
- Soportar acciones por lote con permiso, token, CSRF y auditoria.
- Mantener el API publico consumible por frontend externo.

## Estado encontrado

Ya existe base operativa:

- Vista interna: `/ecommercePublico/control`.
- Vista tecnica: `/ecommercePublico/publicaciones`.
- Endpoints internos:
  - `GET /ecommercePublico/publicaciones_auditar_erp`;
  - `GET /ecommercePublico/publicaciones_readiness_erp`;
  - `GET /ecommercePublico/publicaciones_preparar_erp`;
  - `POST /ecommercePublico/publicaciones_guardar_borrador_erp`;
  - `POST /ecommercePublico/publicaciones_guardar_curaduria_erp`;
  - `POST /ecommercePublico/publicaciones_estatus_erp`;
  - `POST /ecommercePublico/publicaciones_lote_estatus_erp`;
  - `POST /ecommercePublico/publicaciones_lote_borrador_erp`;
  - `POST /ecommercePublico/publicaciones_lote_publicar_erp`.
- UAT existente: `storage/uat/uat_ecommerce_publico_panel_publicaciones_readonly.php`.
- API publica actual en verde con datos reales y 6 publicaciones.

## Avance 2026-08-04

Se agrega estado formal de fase:

- Endpoint interno:
  - `GET /ecommercePublico/publicaciones_fase_estado_erp`.
- Panel `/ecommercePublico/control` muestra si la Fase 1 esta en progreso o lista para cierre.
- Criterios de salida quedan expuestos por API interna:
  - panel de control disponible;
  - panel de publicaciones disponible;
  - auditoria de publicabilidad disponible;
  - filtros de gobierno disponibles;
  - acciones por lote disponibles;
  - escrituras protegidas por permiso, token y auditoria;
  - API publica en `verde_datos_reales`;
  - minimo 6 publicaciones reales;
  - SKUs publicables detectados;
  - granel filtrable;
  - cero publicaciones activas a granel.

## Criterio para pasar a Fase 2

La Fase 1 puede cerrarse cuando:

- `GET /ecommercePublico/publicaciones_fase_estado_erp` devuelva `puede_pasar_a_fase_2=true`.
- `storage/uat/uat_ecommerce_publico_panel_publicaciones_readonly.php` devuelva `ok=true`.
- `storage/uat/uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local` devuelva `ok=true`.
- El usuario confirme que el panel de control le sirve operativamente para publicar, pausar y revisar productos.

## Que sigue en Fase 1

1. Validar endpoint de fase y UAT del panel.
2. Revisar si el panel necesita reglas masivas mas finas:
   - ocultar por categoria completa;
   - ocultar por marca;
   - ocultar por texto/SKU;
   - regla permanente para granel.
3. Cerrar Fase 1 o dejar pendientes operativos.

## Validacion 2026-08-04

Comandos ejecutados:

```bash
C:\xampp\php\php.exe -l app\controladores\EcommercePublico.php
C:\xampp\php\php.exe -l app\modelos\EcommerceCatalogoPublico.php
node --check public\assets\js\custom\apps\erp\ecommerce\control.js
C:\xampp\php\php.exe -l storage\uat\uat_ecommerce_publico_panel_publicaciones_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_panel_publicaciones_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

Resultado:

- Sintaxis PHP/JS sin errores.
- `uat_ecommerce_publico_panel_publicaciones_readonly.php` devuelve `ok=true`.
- `fase_1.estado=lista_para_cierre_operativo`.
- `fase_1.puede_pasar_a_fase_2=true`.
- `publicaciones_granel_activas=0`.
- Publicados encontrados: 6.
- Green gate ecommerce publico devuelve `ok=true`.
- Senal frontend: `verde_datos_reales`.

Decision recomendada:

- Fase 1 queda tecnicamente lista para revision operativa del usuario.
- Si el panel actual permite publicar, pausar, reactivar y filtrar como se necesita, pasar a Fase 2.
- Regla confirmada por el usuario: productos a granel no se publican en ecommerce.
- Si se requieren reglas permanentes por categoria o marca antes de cerrar, dejarlas como subfase 1.1.

## Fase 2 - API de catalogo mas robusta

Estado: iniciada el 2026-08-04.

Objetivo preliminar:

- fortalecer filtros, secciones, paginacion, ordenamientos y metadatos para que frontend pueda construir vistas mas completas sin pedir archivos internos.

Primer entregable:

- `GET /ecommercePublico/catalogo_manifest`.
- `GET /ecommercePublico/catalogo` ahora incluye `depurar.fase_2`.
- `GET /ecommercePublico/producto/{slug}` ahora incluye `depurar.fase_2`.

Contrato:

- expone fase `fase_2_api_catalogo_robusta`;
- lista parametros soportados;
- lista ordenamientos;
- expone endpoints relacionados;
- entrega ejemplos de consumo;
- incluye preview de catalogo, estado vacio, filtros y navegacion;
- en `catalogo`, expone links de paginacion, chips de filtros activos, url para limpiar filtros y banderas UI;
- en `producto/{slug}`, expone links, resumen UI, CTA, disponibilidad UI, SEO compacto y guardrails;
- en `filtros`, expone `depurar.fase_2.facetados` con URL, chip, contador, parametro y grupo;
- en `navegacion`, expone `depurar.fase_2` con resumen de grupos, chips para home, links relacionados y banderas UI;
- en `secciones`, cada bloque expone `url_catalogo`, `frontend`, estado vacio, CTA ver todo y guardrails;
- en `bootstrap`, expone `depurar.fase_2` con mapa de primer render, resumen y banderas UI para home;
- en `busqueda_sugerencias`, expone `depurar.fase_2` con orden de grupos, links, estado vacio, UI y guardrails;
- en `seo`, expone `depurar.fase_2` con archivos sugeridos, canonical, rutas por tipo, JSON-LD y guardrails;
- en `frontend_handoff`, expone `depurar.fase_2`, ejemplos de `catalogo_manifest` y `seo`, y contratos UI consolidados para que frontend no lea archivos internos;
- en `disponibilidad`, expone `depurar.fase_2` con estado, CTA, links de dry-run/preflight, confirmacion operativa y guardrails;
- en `cotizacion_dryrun`, expone `depurar.fase_2` con resumen de carrito, flujo, limites, UI y guardrails;
- en `cotizacion_preflight`, expone `depurar.fase_2` con embudo carrito-contacto-confirmacion-WhatsApp, CTA y guardrails;
- `GET /ecommercePublico/fase_2_checklist` expone cierre de Fase 2 con endpoints obligatorios, orden de integracion, escenarios de prueba y criterios de pase a Fase 3;
- OpenAPI read-only declara Fase 2 de catalogo robusto, `no_granel=true` y bloque `depurar.fase_2` en handoff;
- mantiene guardrail `no_granel=true`.

## Validacion Fase 2 - 2026-08-05

Comandos ejecutados:

```bash
C:\xampp\php\php.exe -l app\modelos\EcommerceCatalogoPublico.php
C:\xampp\php\php.exe -l storage\uat\uat_ecommerce_publico_catalogo_robusto_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_catalogo_robusto_readonly.php --base=http://panel.com.local --limite=3
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

Resultado:

- Sintaxis PHP sin errores.
- Contrato shape devuelve `ok=true` con 23 endpoints publicos esperados.
- Catalogo robusto devuelve `senal_frontend=catalogo_robusto_listo`.
- Smoke HTTP completo devuelve `ok=true`.
- Green gate devuelve `ok=true`, `puede_integrar_datos_reales=true` y 6 publicaciones reales.
- `frontend.filtros_activos` no cuenta `limite` como filtro visual.
- Se mantiene `no_granel=true` en `catalogo`, `catalogo_manifest` y UAT.
- Producto real por HTTP expone breadcrumbs, relacionados, acciones, SEO y bloque `fase_2`.
- `filtros` y `navegacion` por HTTP exponen `fase_2` con guardrail `no_granel=true`.
- `secciones` y `bootstrap` por HTTP exponen `fase_2`; secciones directas incluyen metadata frontend por bloque.
- `busqueda_sugerencias` por HTTP expone `fase_2`, links de resultados y guardrail `no_granel=true`.
- `seo` por HTTP expone `fase_2`, canonical y guardrail `no_granel=true`.
- `frontend_handoff` por HTTP expone `fase_2`, ejemplos de manifest/SEO y `no_requiere_filesystem=true`.
- `disponibilidad`, `cotizacion_dryrun` y `cotizacion_preflight` exponen `fase_2` para UI de carrito/cotizacion sin persistencia real.
- `fase_2_checklist` por HTTP expone endpoints obligatorios, escenarios de prueba, criterios de pase y guardrail `no_granel=true`.
- OpenAPI read-only genera especificacion con Fase 2 y guardrail `no_granel=true`.

Siguiente tarea recomendada en Fase 2:

- Esperar revision de frontend externo contra `GET /ecommercePublico/fase_2_checklist`; si no hay bloqueos de integracion, pasar a Fase 3: carrito/cotizacion avanzada y persistencia autorizada.
