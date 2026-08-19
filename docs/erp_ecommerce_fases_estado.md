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

## CMS ecommerce - estado paralelo

Avance 2026-08-13:

- Modulo `CMS` separado del modulo Ecommerce.
- CMS Contenido en UI funcional con persistencia interna de bloques y publicaciones por slot.
- CMS Frontend con pantallas `Plantillas`, `Componentes` y `Activaciones`.
- Respaldo externo generado:
  - `C:\xampp\panel_db_backups\artianilocal_panel_20260812_094259_antes_cms_ecommerce_persistencia.sql`.
- DDL base aplicado:
  - 5 tablas CMS Contenido.
  - 6 tablas CMS Frontend.
  - 11 tablas totales.
- Semilla estructural aplicada:
  - plantilla `artiani_default`;
  - tema `wokiee_artiani`;
  - slots, layouts, componentes, plantillas de vista, secciones y activaciones base.
- Endpoints internos de manifest ya leen estructura desde BD semilla:
  - `/cms/contenido_admin_manifest_erp`;
  - `/cms/frontend_admin_manifest_erp`.
- Endpoints POST de contenido activos con CSRF, permisos y auditoria:
  - `/cms/contenido_bloque_guardar_erp`;
  - `/cms/contenido_bloque_estatus_erp`;
  - `/cms/contenido_publicacion_guardar_erp`;
  - `/cms/contenido_publicacion_estatus_erp`.
- Endpoints publicos de contenido leen BD publicada/vigente y usan fallback default/read-only si no hay publicaciones.
- UAT transaccional disponible:
  - `storage/uat/uat_cms_publico_bd_temporal_rollback.php`;
  - inserta contenido temporal publicado, valida `fuente=bd_publicada`, confirma bloqueo server-side de un hero sin alt text y ejecuta rollback.

Siguiente paso CMS:

1. Definir media estructurada y rutas publicas de imagenes.
2. Ampliar sanitizacion HTML y reglas de media.
3. Completar renderer frontend para `plantilla_vista + contenido.slots`.

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

## Ajuste operativo 2026-08-06 - Diagnostico de publicacion

Motivo:

- El boton Publicar podia parecer que no hacia nada cuando el backend bloqueaba la accion por reglas de publicabilidad.
- El usuario necesita ver por que un producto no aparece en ecommerce sin revisar archivos ni respuestas tecnicas.

Cambios:

- Panel `/ecommercePublico/control` agrega franja visible `ecom_ctl_diagnostico`.
- Al seleccionar un producto, el editor muestra diagnostico previo:
  - listo para publicar;
  - sin precio;
  - sin imagen;
  - sin categoria;
  - granel bloqueado;
  - SKU inactivo/no encontrado;
  - requiere confirmar agotado;
  - slug/titulo requerido;
  - no hay borrador o solo se publica desde borrador.
- Al publicar, pausar, reactivar o guardar, el panel traduce `depurar.bloqueos_publicacion` en mensajes legibles.
- En acciones por lote, el panel resume cuantos SKUs se procesaron y lista los no procesados con sus bloqueos.
- Se mantiene la regla operativa: productos a granel/fraccionarios no deben publicarse ni aparecer en API publica.

Verificacion pendiente:

- Confirmar visualmente en navegador que la franja de diagnostico se vea bien en desktop y que el mensaje permanezca despues de publicar.

## Ajuste UX 2026-08-11 - Preparacion y publicacion sin stock

Motivo:

- En una lista grande, el boton para preparar/controlar un producto podia parecer que no hacia nada porque el editor quedaba fuera de foco.
- La decision de publicar un producto agotado existia, pero estaba como control global de lote y no era clara al revisar un producto individual.

Cambios:

- Panel `/ecommercePublico/control` separa visualmente la zona de lista y la zona `Preparacion ecommerce`.
- Al presionar `Preparar`, el panel:
  - resalta la fila activa;
  - actualiza el estado a `Preparando producto`;
  - desplaza la vista al editor;
  - muestra badge del SKU seleccionado.
- Si el SKU esta agotado, el editor muestra una confirmacion local:
  - `Publicar aunque no haya stock`.
- Si el usuario intenta publicar un agotado sin marcar esa confirmacion, el panel muestra diagnostico legible antes de enviar el cambio.
- El checkbox superior queda reservado para acciones por lote: `Permitir agotados en lote`.

Reglas conservadas:

- Publicar sin stock no descuenta inventario, no crea pedido y no modifica existencias.
- Los productos publicados sin stock pueden habilitar cotizacion/WhatsApp si la publicacion tiene `permite_cotizacion` y `permite_whatsapp`.
- WhatsApp trabaja como pre-cotizacion: abre mensaje con referencia preliminar, pero no genera pedido real ni aparta inventario.
- Productos a granel/fraccionarios siguen bloqueados para ecommerce.
- El backend sigue siendo la autoridad final de publicabilidad.

Ajuste complementario:

- La vista tecnica `/ecommercePublico/publicaciones` tambien permite confirmar agotados:
  - por producto: `Publicar aunque no haya stock`;
  - por lote: `Permitir agotados en lote`.
- El lote de `publicaciones_lote_publicar_erp` ya reenvia `confirmar_agotado` al publicar cada borrador.

## Ajuste UX 2026-08-11 - Lista compacta y preparacion visible

Motivo:

- En listas grandes, preparar un producto no debe obligar al usuario a desplazarse hasta abajo para encontrar el editor.
- La tabla debe ser una zona acotada de trabajo, no una pagina interminable.

Cambios:

- `/ecommercePublico/control`:
  - reduce la altura de la tabla a una zona con scroll interno;
  - mantiene encabezados visibles;
  - deja la preparacion antes de la lista en pantallas medianas/chicas;
  - deja de forzar `scrollIntoView` al dar clic en `Preparar`.
- `/ecommercePublico/publicaciones`:
  - mueve `Preparacion de publicacion` arriba de la tabla;
  - convierte la tabla en scroll interno de altura reducida;
  - mantiene encabezados fijos;
  - mantiene la preparacion como panel sticky en escritorio.

Resultado esperado:

- Al dar `Preparar`, el producto se carga en el panel visible cercano sin recorrer toda la lista.
- Para navegar muchos SKUs, se usan filtros, limite de resultados y scroll interno de tabla.

## Ajuste operativo 2026-08-11 - Taxonomia controlada en publicaciones

Motivo:

- `mascota` y `necesidades` no deben capturarse como texto libre porque errores de escritura rompen filtros, rutas y recomendaciones.
- Las categorias ecommerce deben venir de la categoria principal del Catalogo ERP, no capturarse manualmente en publicaciones.
- `presentacion_publica` podia confundirse con pieza/caracteristica estructural.

Regla:

- Categorias:
  - se leen desde `erp_catalogo_categorias` por la relacion principal del producto;
  - el panel ecommerce solo las muestra como dato vivo del Catalogo ERP;
  - si una categoria esta mal, se corrige en Catalogo ERP, no en ecommerce.
- Mascota:
  - se captura con checkboxes controlados y puede tener mas de una opcion;
  - valores permitidos: `perro`, `gato`, `pez`, `ave`, `reptil`, `roedor`, `otra`;
  - valores no reconocidos enviados por POST se normalizan a `otra`;
  - sin DDL nuevo, se guarda temporalmente en `mascota_especie` como lista separada por coma;
  - API publica entrega `mascotas` como arreglo y conserva `mascota_especie` como valor principal legacy.
- Necesidades:
  - se capturan con checkboxes controlados: `alimento`, `premio`, `higiene`, `salud`, `paseo`, `habitat`, `juguete`, `estetica`;
  - el backend descarta necesidades fuera de esa lista.
- Presentacion comercial opcional:
  - sirve solo como texto visible tipo `2 kg`, `12 pzas`, `paquete`, etc.;
  - no reemplaza caracteristicas, inventario, unidad base ni atributos del Catalogo ERP.

Cambios:

- `/ecommercePublico/control` reemplaza inputs abiertos de mascota/necesidades por checkboxes.
- `/ecommercePublico/publicaciones` queda alineado con los mismos controles.
- `publicaciones_preparar_erp` expone `taxonomia_publicacion` para alimentar la UI.
- Guardado de borrador y curaduria normaliza mascota/necesidades antes de persistir.
- Filtros publicos por `mascota` buscan dentro de publicaciones con una o varias mascotas.

Pendiente recomendado:

- En una fase posterior, migrar de `mascota_especie` CSV temporal a `mascotas_json` o tabla relacional `erp_ecommerce_publicacion_mascotas`.

## Ajuste operativo 2026-08-11 - Descripcion ERP como fallback ecommerce

Motivo:

- Si un producto del Catalogo ERP ya tiene descripcion, ecommerce debe reutilizarla temporalmente para no mostrar fichas vacias.
- La descripcion ecommerce curada sigue teniendo prioridad cuando exista.

Regla:

- Al preparar una publicacion, `descripcion_publica` se sugiere desde:
  - publicacion ecommerce existente, si ya tiene `descripcion_publica`;
  - si no, `erp_catalogo_productos.descripcion`.
- Al guardar borrador o curaduria, si `descripcion_publica` llega vacia, se rellena con la descripcion del Catalogo ERP cuando exista.
- En API publica, `item.descripcion` sale de:
  - `erp_ecommerce_publicaciones.descripcion_publica`;
  - si esta vacia, `erp_catalogo_productos.descripcion`.

Guardrail:

- Esto no modifica la descripcion del Catalogo ERP.
- Solo evita que ecommerce nazca vacio mientras se redacta copy especifico.

## Ajuste API 2026-08-10 - Consentimientos preflight carrito

Motivo:

- Frontend publico envia aceptaciones dentro de `contacto`:
  - `contacto.acepta_whatsapp`;
  - `contacto.acepta_politicas`.
- La API solo leia los nombres legacy en raiz:
  - `acepta_contacto_whatsapp`;
  - `politicas_aceptadas`.

Decision:

- `POST /ecommercePublico/cotizacion_preflight` acepta ambos contratos.
- Contrato recomendado para frontend nuevo:

```json
{
  "contacto": {
    "nombre": "Cliente",
    "telefono": "3322068429",
    "mensaje": "",
    "acepta_whatsapp": true,
    "acepta_politicas": true
  }
}
```

- Si `contacto.acepta_politicas=true`, la API lo normaliza como `["aviso_privacidad", "terminos_cotizacion"]`.
- Si viene telefono valido, `acepta_whatsapp=true` y `acepta_politicas=true`, `cotizacion_preflight` no debe devolver:
  - `contacto_telefono_recomendado`;
  - `aceptacion_whatsapp_recomendada`;
  - `politicas_aceptadas_no_informadas`.
- Se conserva el modo preflight: no registra cotizacion, no crea pedido y no descuenta inventario.

## Propuesta 2026-08-10 - CMS ligero para contenido frontend

Motivo:

- Frontend necesita contenido editable desde panel ERP: banner principal, banners de categoria, bloques de home, textos promocionales, colecciones destacadas y contenido por plantilla.
- Hoy `/ecommercePublico/bootstrap` y `/ecommercePublico/secciones` entregan bloques generados desde catalogo, pero no existe un sistema editorial para gobernar layout/contenido desde el panel.
- Conviene resolverlo antes de que frontend deje banners, textos y estructura quemados en codigo.

Decision recomendada:

- Crear un CMS ecommerce ligero y headless: el ERP administra contenido y frontend solo consume JSON.
- No convertir el ERP en constructor visual completo tipo page builder pesado en la primera etapa.
- Separar tres conceptos:
  - `plantilla`: define slots disponibles, por ejemplo `home.hero`, `home.destacados`, `categoria.banner`;
  - `bloque`: contenido editable, por ejemplo banner, carrusel, coleccion, texto, CTA, imagen;
  - `publicacion`: instancia de un bloque colocada en un slot, con orden, vigencia, canal y estatus.

Contrato propuesto para frontend:

- `GET /ecommercePublico/contenido_manifest`
  - Plantillas disponibles, slots soportados, tipos de bloque y reglas visuales.
- `GET /ecommercePublico/contenido_pagina?pagina=home&plantilla=artiani_default`
  - Devuelve estructura editorial lista para renderizar por frontend.
- `GET /ecommercePublico/contenido_pagina?pagina=categoria&categoria=peces`
  - Devuelve banner/copy/bloques especificos de categoria.
- El contenido debe integrarse tambien en `/ecommercePublico/bootstrap` para primer render.
- Nombre recomendado para el primer render:
  - usar `GET /ecommercePublico/configuracion_inicial`;
  - mantener `GET /ecommercePublico/bootstrap` solo como alias legacy, porque el nombre puede confundirse con Bootstrap CSS.

Tipos de bloque iniciales:

- `hero_banner`: imagen desktop/mobile, titulo, subtitulo, CTA, url destino.
- `category_banner`: banner por categoria, descripcion corta y CTA.
- `product_collection`: coleccion manual o dinamica por categoria, mascota, necesidad, marca o etiqueta.
- `promo_strip`: franja simple de promocion/aviso.
- `content_html_safe`: texto editorial sanitizado para politicas o bloques informativos.
- `image_card_grid`: cuadricula de tarjetas con imagen, titulo y enlace.

Panel interno recomendado:

- Vista interna separada: `/cms/contenido`.
- Funciones:
  - elegir plantilla activa;
  - ver slots disponibles;
  - crear/editar banners;
  - subir/seleccionar imagen desktop y mobile;
  - ordenar bloques;
  - programar vigencia;
  - pausar/publicar contenido;
  - previsualizar JSON consumido por frontend.

Sobre escanear la plantilla:

- Se puede hacer, pero no conviene intentar clonar todo el HTML/CSS desde backend.
- Lo recomendable es que frontend entregue un mapa de slots de la plantilla:

```json
{
  "plantilla": "artiani_default",
  "slots": [
    {"codigo": "home.hero", "tipos": ["hero_banner"], "max_bloques": 1},
    {"codigo": "home.destacados", "tipos": ["product_collection"], "max_bloques": 3},
    {"codigo": "categoria.banner", "tipos": ["category_banner"], "max_bloques": 1}
  ]
}
```

- El ERP guarda contenido para esos slots; frontend decide como renderizarlos respetando su plantilla.

Guardrails:

- Frontend no debe leer archivos internos del ERP.
- El CMS no debe modificar catalogo, precios, inventario ni publicaciones de producto.
- Banners/contenido publicados deben salir solo por API publica.
- Imagenes deben tener version desktop/mobile y texto alternativo.
- El primer entregable puede ser read-only/manifest antes de habilitar escrituras reales.

Fase sugerida:

- Tratarlo como Fase 7 adelantada: `SEO y contenido / CMS ecommerce`.
- Primer paso: disenar esquema y contrato read-only.
- Segundo paso: endpoint publico de contenido con datos mock/default.
- Tercer paso: panel interno para capturar contenido.
- Cuarto paso: escrituras autorizadas, subida de imagenes y publicacion real.

## Avance CMS ligero 2026-08-10

Estado:

- Iniciada Fase 7 adelantada en modo seguro/read-only.
- No se crearon tablas ni se habilitaron escrituras.
- No se toca catalogo, precios, inventario ni publicaciones de producto.

Endpoints publicos agregados:

- `GET /ecommercePublico/contenido_manifest`
  - expone plantilla `artiani_default`;
  - slots soportados: `home.hero`, `home.promo`, `home.categorias`, `home.destacados`, `categoria.banner`, `categoria.productos`, `catalogo.encabezado`;
  - tipos de bloque: `hero_banner`, `category_banner`, `product_collection`, `promo_strip`, `image_card_grid`, `content_html_safe`.
- `GET /ecommercePublico/contenido_pagina?pagina=home`
  - devuelve estructura default para home con hero, promo, categorias y colecciones dinamicas.
- `GET /ecommercePublico/contenido_pagina?pagina=categoria&categoria=peces`
  - devuelve banner default de categoria y coleccion dinamica por categoria.

Integracion:

- `GET /ecommercePublico/configuracion_inicial` ahora incluye links a:
  - `contenido_manifest`;
  - `contenido_pagina?pagina=home`;
  - `contenido_pagina?pagina=categoria&categoria={slug_categoria}`.
- `frontend_handoff`, `contratos`, `catalogo_manifest` y `fase_2_checklist` ya exponen los endpoints de contenido.

UAT:

- `storage/uat/uat_ecommerce_publico_contenido_readonly.php`
  - valida manifest;
  - valida home con slots principales;
  - valida categoria con banner;
  - valida guardrails read-only.

Siguiente paso recomendado:

1. Convertir `/cms` en flujo operativo de constructor de paginas ecommerce: Home primero, despues Categoria, Producto, Carrito, Header y Footer.
2. Agregar edicion directa de secciones Home desde el constructor: imagen, titulo, texto, CTA, visibilidad, orden y vigencia.
3. Mantener `/cms/contenido`, `/cms/json`, `/cms/slots` y plantillas tecnicas como vistas avanzadas.
4. Cuando Home sea operable desde CMS, conectar el frontend externo a `contenido_manifest`, `contenido_pagina` y `configuracion_inicial`.

## Ajuste UX publicaciones 2026-08-12

Problema detectado:

- La tabla de `/ecommercePublico/publicaciones` solo usaba `limite`; no tenia `pagina`.
- Al cambiar el selector de cantidad se recargaba la tabla completa y se perdian los productos seleccionados porque la seleccion vivia solo en los checkboxes visibles.

Cambios aplicados:

- `GET /ecommercePublico/publicaciones_auditar_erp` ahora acepta `pagina` y devuelve `paginacion` con `pagina`, `limite`, `offset`, `total`, `total_paginas`, `tiene_anterior` y `tiene_siguiente`.
- La tabla interna de publicaciones ya muestra resumen `Mostrando X-Y de Z productos`.
- Se agregaron botones `Anterior` y `Siguiente`.
- La seleccion de productos para lote queda persistente en JS aunque se cambie de pagina o de limite.
- Se agrego boton `Limpiar seleccion`.

Guardrails:

- El cambio no publica productos por si solo.
- El endpoint sigue siendo de auditoria interna protegida y read-only.
- No se modifica inventario, precios, catalogo base ni publicaciones durante la paginacion.

## Ajuste visibilidad publicaciones 2026-08-12

Necesidad:

- Desde el panel se debe poder decidir si un producto publicado muestra o no disponibilidad publica, precio y acciones.
- La decision no debe quedar fija en frontend ni forzada por JS.

Cambios aplicados:

- En `/ecommercePublico/publicaciones`, la ficha de preparacion ahora incluye controles de visibilidad:
  - `mostrar_precio`;
  - `mostrar_disponibilidad`;
  - `permite_cotizacion`;
  - `permite_whatsapp`;
  - `destacado`.
- El JS ya envia esos valores reales al guardar borrador o curaduria.
- Si `mostrar_disponibilidad=0`, la API publica no muestra el tipo real de disponibilidad del producto y responde `consultar_disponibilidad`.

Guardrails:

- Ocultar disponibilidad no modifica inventario.
- Ocultar precio no modifica listas de precio ERP.
- Estos campos pertenecen a la curaduria de publicacion ecommerce, no al catalogo base.

## Configuracion masiva publicaciones 2026-08-12

Necesidad:

- Acelerar la curaduria inicial del ecommerce aplicando reglas de visibilidad a muchos productos seleccionados.
- Evitar editar producto por producto cuando la politica es comun por grupo, busqueda, categoria o seleccion manual.

Cambios aplicados:

- Se agrego endpoint interno `POST /ecommercePublico/publicaciones_lote_configuracion_erp`.
- Requiere permiso `catalogo.editar`, CSRF y token `ECOMMERCE_PUBLICO_LOTE_CONFIGURACION`.
- La pantalla `/ecommercePublico/publicaciones` ahora tiene panel `Configuracion masiva`.
- Campos masivos soportados:
  - `mostrar_precio`;
  - `mostrar_disponibilidad`;
  - `permite_cotizacion`;
  - `permite_whatsapp`;
  - `destacado`.
- Cada campo permite tres estados: sin cambio, si, no.
- Puede crear borrador si el SKU seleccionado todavia no tiene publicacion.
- Si ya existe publicacion, actualiza curaduria sin cambiar estatus publicado/borrador/pausado.

Guardrails:

- No publica automaticamente.
- No toca inventario.
- No toca precios ERP.
- No toca imagenes, marca ni categoria del catalogo base.
- No usa ni modifica legacy `ecom_*`.

## Ajuste UX editor publicaciones 2026-08-12

Problema detectado:

- El panel de preparacion de producto estaba fijo con `position: sticky`.
- Al editar/preparar un producto, el panel quedaba encima del flujo y dificultaba seguir usando la tabla.
- No existia accion clara para cerrar la edicion y volver al trabajo masivo.

Cambios aplicados:

- Se removio el comportamiento sticky del panel `ecom_preview_publicacion`.
- El editor ahora muestra boton `Cerrar edicion`.
- Al cerrar, vuelve el mensaje inicial sin limpiar la seleccion masiva de productos.

Guardrails:

- Cerrar el editor no guarda cambios.
- Cerrar el editor no limpia productos seleccionados.
- No modifica publicaciones, inventario ni catalogo base.

## Ajuste borradores incompletos 2026-08-15

Problema detectado:

- La configuracion masiva podia reportar lote procesado, pero no dejaba productos en `borrador` si el SKU aun tenia bloqueos de publicabilidad.
- Bloqueos como precio faltante, imagen faltante o categoria principal faltante estaban impidiendo crear borrador.
- Operativamente eso frenaba la preparacion, porque el borrador debe servir para curar/configurar antes de publicar.

Decision:

- Crear borrador y publicar son etapas separadas.
- Un producto puede quedar en `borrador` aunque no sea publicable todavia.
- La publicacion real sigue bloqueada hasta resolver requisitos de Fase 1.

Cambios aplicados:

- `planGuardarPublicacion` ahora separa:
  - `bloqueos_validacion_borrador`;
  - `bloqueos_publicabilidad`.
- `guardarPublicacionBorradorAutorizada` solo bloquea por errores de validacion de borrador.
- Faltantes de precio, imagen, categoria o regla de granel quedan como pendientes de publicabilidad.
- La configuracion masiva muestra resumen de `OK`, errores y advertencias de pendientes.

Guardrails:

- Un borrador incompleto no se publica automaticamente.
- Publicar sigue validando precio, imagen, categoria y reglas de no granel.
- No se toca inventario, precios ERP, imagenes ni categoria base.

## Categorias publicas jerarquicas 2026-08-15

Necesidad frontend:

- Construir mega menu de categorias, home "Comprar por categoria" y landings SEO `/categoria/{slug}` sin leer archivos internos ni tablas ERP.
- Dejar de depender de fallbacks locales para `/categoria/aves`, `/marca/{slug}` y rutas limpias.

Cambios aplicados:

- Nuevo endpoint publico `GET /ecommercePublico/categorias`.
- `GET /ecommercePublico/navegacion` ahora incluye `categorias_arbol` para mega menu.
- `GET /ecommercePublico/catalogo` acepta:
  - `categoria=526`;
  - `categoria_slug=jaulas`;
  - `incluir_hijos=1`.
- `GET /ecommercePublico/catalogo_manifest` anuncia `categoria_slug`, `incluir_hijos` y endpoint `/categorias`.

Contrato categoria:

- `id`
- `parent_id`
- `nombre`
- `nombre_completo`
- `slug_publico`
- `nivel`
- `orden`
- `total_productos`
- `visible_frontend`
- `imagen_menu`
- `imagen_card`
- `imagen_banner`
- `descripcion_corta`
- `seo_title`
- `seo_description`
- `url`
- `api_catalogo`
- `children` en arbol

Reglas:

- Solo deriva de publicaciones ecommerce `publicado`.
- Excluye granel/fraccionario bloqueado.
- No muestra stock exacto.
- Incluye categorias padre cuando tienen hijos con productos publicados.
- Si no existe columna padre real, reconstruye jerarquia por `ruta`.
- Slug publico se genera estable desde nombre; si hay duplicado se agrega el id.

Pendiente CMS:

- Persistir por categoria imagenes y textos reales:
  - `imagen_menu`;
  - `imagen_card`;
  - `imagen_banner`;
  - `descripcion_corta`;
  - `seo_title`;
  - `seo_description`;
  - `orden`;
  - destacado en home.
