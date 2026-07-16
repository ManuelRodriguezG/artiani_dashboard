# ERP Ecommerce publico - Catalogo vivo Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-11  
Estado: diseno tecnico inicial; no implica escrituras en BD ni cambios de codigo productivo.

## Proposito

Iniciar la capa publica de ecommerce como catalogo vivo conectado al ERP, no como checkout completo. La fase 1 debe permitir que clientes encuentren productos reales para sus mascotas, armen un carrito/cotizacion y lo envien por WhatsApp, mientras el cierre comercial sigue en POS/Pedidos.

El ERP sigue siendo fuente de verdad. El ecommerce publico consume contratos/API del ERP y no debe leer ni escribir tablas internas directamente salvo mediante una fachada backend controlada.

## Auditoria inicial read-only

Consultas locales read-only ejecutadas el 2026-07-11:

- `ecom_productos`: 1803 registros.
- `ecom_productos_imagenes`: 1640 registros.
- `ecom_carrito`: 2203 registros.
- `ecom_carrito_items`: 3083 registros.
- `ecom_pedidos`: 293 registros.
- `ecom_pedidos_productos`: 614 registros.
- `erp_catalogo_productos`: 1535 registros.
- `erp_catalogo_skus`: 1752 registros.
- `erp_catalogo_imagenes`: 1535 registros.
- `erp_catalogo_canales_vinculos`: 1741 registros, todos `canal=ecommerce`, `estatus=migrado`.
- `erp_catalogo_sku_precios`: 1752 registros.
- `erp_inventario_existencias`: 18 registros.
- `erp_pos_atenciones`: 1 registro.
- `crm_clientes_maestro`: 157 registros.
- `crm_clientes_identificadores`: 157 registros.

Resumen operativo:

- Catalogo ERP ya tiene base migrada suficiente para publicar una primera vitrina.
- Hay 1510 productos ERP activos y 1731 SKUs activos.
- Los 1731 SKUs activos tienen precio general activo.
- 1411 productos activos tienen imagen activa.
- Solo 10 SKUs tienen existencia disponible en inventario actual, por lo que disponibilidad publica debe ser prudente y no cuantitativa.
- Hay 7 SKUs fraccionarios; no deben publicarse para venta online a granel hasta definir presentacion ecommerce clara.
- `ecom_*` conserva historico y datos utiles de migracion, pero no debe ser fuente nueva.

## Decision arquitectonica

Crear una fachada nueva para ecommerce publico, separada de `CatalogoErp`, `Ventas` legacy y modelos `ecom_*`.

Nombre recomendado:

- Controlador publico: `EcommercePublico`.
- Modelo de lectura: `EcommerceCatalogoPublico`.
- Modelo de esquema/plan: `EcommercePublicoEsquema`.

Esta fachada puede vivir dentro del MVC actual al inicio, pero con contratos preparados para que un frontend separado los consuma despues.

## Flujo ERP -> Ecommerce: publicacion, no copia de catalogo

La Fase 1 no debe duplicar el catalogo ERP como una copia independiente. El ERP sigue siendo la fuente viva de producto, SKU, precio, imagen, marca, categoria e inventario.

`erp_ecommerce_publicaciones` funciona como capa de publicacion/curaduria:

- decide si un SKU se muestra o no en ecommerce;
- define estado publico: borrador, publicado, pausado, oculto, agotado manual;
- guarda slug y textos publicos cuando se quiera ajustar la presentacion comercial;
- guarda metadata ecommerce: especie de mascota, necesidades, destacado, orden, flags de mostrar precio/disponibilidad;
- no reemplaza el maestro ERP.

Datos que deben leerse vivos desde ERP al consultar ecommerce:

- precio vigente desde listas/precios ERP;
- imagen activa desde catalogo ERP;
- marca y categoria desde catalogo ERP;
- nombre/base SKU desde catalogo ERP, salvo override publico;
- disponibilidad desde inventario ERP traducida a estado publico simple.

Datos que puede sobreescribir ecommerce:

- `titulo_publico`;
- `descripcion_publica`;
- `presentacion_publica`;
- `slug`;
- `mascota_especie`;
- `necesidades_json`;
- `destacado`, `orden`;
- flags de visibilidad/cotizacion.

Regla de actualizacion:

- Si cambia el precio en ERP, ecommerce debe reflejarlo al consultar el catalogo.
- Si cambia la imagen activa en ERP, ecommerce debe reflejarla al consultar el catalogo.
- Si cambia marca/categoria/presentacion base en ERP, ecommerce debe reflejarlo salvo que exista override publico.
- Si se pausa una publicacion ecommerce, el producto deja de mostrarse aunque siga activo en ERP.
- Si se desactiva producto/SKU en ERP, no debe mostrarse aunque la publicacion siga marcada como publicada.

Las cotizaciones si deben guardar snapshot de nombre, precio, presentacion y disponibilidad al momento de enviarse, porque representan lo que el cliente vio en ese instante. Ese snapshot no convierte la cotizacion en venta ni aparta inventario.

Mas adelante puede existir cache o indice publico por rendimiento, pero debe tratarse como derivado regenerable, no como fuente de verdad.

## 1. Publicacion de productos ERP hacia ecommerce

No usar `estatus='activo'` como publicacion. Activo significa que el maestro ERP vive; publicable significa que el negocio decide mostrarlo.

Tabla propuesta:

`erp_ecommerce_publicaciones`

Campos recomendados:

- `id_publicacion`
- `id_producto_erp`
- `id_sku`
- `canal`: `catalogo_publico`, futuro `marketplace`, `mayoreo`, etc.
- `estatus_publicacion`: `borrador`, `publicado`, `pausado`, `oculto`, `agotado_manual`
- `slug`
- `titulo_publico`
- `descripcion_publica`
- `presentacion_publica`
- `mascota_especie`: perro, gato, ave, pez, reptil, roedor, multiple, otra.
- `necesidades_json`: alimento, premio, higiene, salud, paseo, habitat, juguete, entrenamiento, viaje, estetica, etc.
- `orden`
- `destacado`
- `permite_cotizacion`
- `permite_whatsapp`
- `mostrar_precio`
- `mostrar_disponibilidad`
- `fecha_publicacion`
- `fecha_actualizacion`
- `creado_por`, `actualizado_por`

Reglas:

- Publicacion debe ser por SKU vendible, no solo por producto maestro.
- Un producto con variantes puede tener varias publicaciones o una publicacion agrupada con variantes, segun UX.
- Para Fase 1, publicar solo SKUs cerrados, con precio, imagen y presentacion clara.
- SKUs con `permite_venta_fraccionaria=1` quedan bloqueados para cotizacion web salvo que exista SKU/presentacion ecommerce cerrada.
- No usar `erp_catalogo_canales_vinculos` como publicacion; esa tabla sirve para vinculos/migracion/sincronizacion.

## 2. Endpoint/API read-only de catalogo publico

Endpoints sugeridos:

- `GET /ecommercepublico/catalogo`
- `GET /ecommercepublico/producto/{slug}`
- `GET /ecommercepublico/filtros`

Contrato de listado:

```json
{
  "items": [
    {
      "id_publicacion": 1,
      "id_producto_erp": 10,
      "id_sku": 20,
      "slug": "alimento-perro-adulto-2kg",
      "nombre": "Alimento perro adulto",
      "marca": "Marca",
      "categoria": "Alimentacion / Alimentos",
      "presentacion": "Bolsa 2 kg",
      "imagen": "/media/...",
      "precio": 295.00,
      "moneda": "MXN",
      "disponibilidad": "disponible",
      "mascota_especie": ["perro"],
      "necesidades": ["alimento"]
    }
  ],
  "paginacion": {"pagina": 1, "limite": 24, "total": 100}
}
```

Filtros Fase 1:

- texto libre;
- especie/tipo de mascota;
- necesidad;
- categoria;
- marca;
- disponibilidad simple;
- rango de precio opcional.

No exponer:

- costos;
- proveedor;
- stock exacto;
- lotes;
- ubicaciones;
- reglas internas de inventario;
- IDs legacy `ecom_*`.

## 3. Endpoint/API read-only de disponibilidad publica

Endpoint sugerido:

- `GET /ecommercepublico/disponibilidad?id_sku=...`

Estados publicos:

- `disponible`
- `pocas_piezas`
- `consultar_disponibilidad`
- `agotado`

Regla recomendada inicial:

- Si el SKU no controla inventario o permite venta sin existencia: `consultar_disponibilidad`.
- Si no existe publicacion activa: no responder detalle publico.
- Si existencia disponible cerrada/publicable es 0: `agotado` o `consultar_disponibilidad` segun politica de canal.
- Si existencia disponible esta debajo del umbral publico: `pocas_piezas`.
- Si supera umbral: `disponible`.
- Nunca mostrar cantidad exacta en Fase 1.

Tabla opcional de politica:

`erp_ecommerce_disponibilidad_politicas`

Campos:

- `id_politica`
- `canal`
- `id_almacen`
- `umbral_pocas_piezas`
- `modo_sin_stock`: `agotado`, `consultar`, `ocultar`
- `mostrar_cantidad_exacta`: siempre `0` en Fase 1
- `estatus`

Si no se crea tabla al inicio, usar constantes/configuracion en modelo y documentar el default.

## 4. Carrito tipo cotizacion sin cobro

El carrito web Fase 1 es intencion comercial, no venta ni apartado.

Puede vivir primero en navegador/localStorage y validarse contra backend antes de WhatsApp. Si se registra internamente, debe ir a tablas propias, no a `ecom_carrito`.

Tablas propuestas:

`erp_ecommerce_cotizaciones`

- `id_cotizacion`
- `folio`
- `origen`: `web_publica`
- `estatus`: `borrador`, `enviada_whatsapp`, `recibida`, `tomada`, `convertida`, `descartada`, `expirada`
- `id_cliente_crm` nullable
- `nombre_contacto`
- `telefono_contacto`
- `correo_contacto`
- `canal_contacto_preferido`
- `mensaje_cliente`
- `subtotal_estimado`
- `total_estimado`
- `utm_json`
- `ip_hash`
- `user_agent_hash`
- `fecha_expiracion`
- `fecha_registro`
- `fecha_actualizacion`

`erp_ecommerce_cotizaciones_detalle`

- `id_cotizacion_detalle`
- `id_cotizacion`
- `id_publicacion`
- `id_producto_erp`
- `id_sku`
- `sku_snapshot`
- `nombre_snapshot`
- `presentacion_snapshot`
- `precio_snapshot`
- `moneda_snapshot`
- `cantidad`
- `disponibilidad_snapshot`
- `subtotal`
- `estatus`

Reglas:

- Recalcular precio y disponibilidad en backend antes de registrar/enviar.
- No aceptar precio enviado por JS como verdad.
- No reservar ni descontar inventario.
- No exigir login.
- Telefono debe normalizarse para futuro vinculo CRM, pero no crear cliente CRM automaticamente sin regla aprobada.

## 5. Envio por WhatsApp

Fase 1 debe abrir WhatsApp con mensaje estructurado y folio de cotizacion si existe.

Mensaje recomendado:

```text
Hola, quiero cotizar estos productos:

Folio: WEB-20260711-000001
1. Producto / Presentacion - Cant. 2 - $295.00
2. Producto / Presentacion - Cant. 1 - $120.00

Total estimado: $710.00 MXN
Mi nombre: ...
Mi telefono: ...
```

Reglas:

- El link WhatsApp no cobra ni confirma pedido.
- El mensaje debe decir `total estimado` y `sujeto a confirmacion`.
- Usar numero de negocio configurable, no hardcodeado.
- Registrar evento `enviada_whatsapp` si se crea cotizacion.

Configuracion requerida:

`erp_ecommerce_configuracion`

- `clave`: `whatsapp_numero_principal`, `mensaje_base`, `url_sitio_publico`, `almacen_disponibilidad_default`, etc.
- `valor`
- `estatus`

## 6. Registro interno del carrito/prospecto

Recomendacion:

- Crear cotizacion interna propia.
- Vincular a CRM solo si hay identificador claro y consentimiento/uso definido.
- Crear `crm_clientes_interacciones` o evento CRM futuro solo despues de definir contrato de seguimiento ecommerce.
- No insertar en `ecom_carrito`, `ecom_pedidos` ni `erp_pos_atenciones` como primer destino.

Conversion futura:

- Cotizacion web -> atencion POS o pedido ERP preliminar.
- La conversion debe revalidar precio, disponibilidad, cliente y reglas comerciales.
- La conversion puede quedar como accion interna tomada por vendedor/caja, nunca automatica desde WhatsApp.

## 7. Plan de pantallas del sitio publico

Primera pantalla usable, no landing generica:

- Home/catalogo: buscador grande, accesos por mascota y necesidad, grilla de productos.
- Listado filtrado: filtros laterales o superiores, chips de especie/necesidad, orden por relevancia.
- Detalle de producto: imagen, marca, categoria, presentacion, precio, disponibilidad simple, variantes si aplica, boton agregar a cotizacion.
- Carrito/cotizacion: cantidades, subtotal estimado, datos minimos de contacto, boton WhatsApp.
- Resultado de envio: folio, resumen y llamada a continuar por WhatsApp.

Enfoque mascotas:

- Navegacion primaria por `Perro`, `Gato`, `Peces`, `Aves`, `Roedores`, `Reptiles`, `Otros`.
- Navegacion por necesidad: alimento, premios, higiene, salud, habitat, paseo, juguetes, entrenamiento, estetica.
- Preparar metadata futura para especie, etapa de vida, tamano, raza y condicion, pero no bloquear Fase 1 por tener todo perfecto.

## 8. Tablas/columnas faltantes

Faltan para Fase 1:

- `erp_ecommerce_publicaciones`
- `erp_ecommerce_cotizaciones`
- `erp_ecommerce_cotizaciones_detalle`
- `erp_ecommerce_cotizaciones_eventos`
- `erp_ecommerce_configuracion`

Opcionales en Fase 1:

- `erp_ecommerce_disponibilidad_politicas`
- `erp_ecommerce_publicacion_taxonomia`

No modificar todavia:

- `ecom_productos`
- `ecom_carrito`
- `ecom_pedidos`
- `erp_pos_atenciones`
- `erp_ventas`
- `crm_clientes_maestro`

## 9. Fuera de alcance Fase 1

- Checkout completo.
- Pasarela de pago.
- Descuento de inventario desde web.
- Reserva automatica.
- Venta a granel online.
- Publicacion automatica de todo SKU activo.
- Clientes/mascotas autoservicio.
- Recompensas automaticas.
- Campanas/automatizaciones.
- Sincronizacion bidireccional con ecommerce legacy.
- Conversion automatica a venta.
- Mostrar stock exacto.

## Orden recomendado de implementacion

1. Preparar `EcommercePublicoEsquema` con plan read-only de DDL.
2. Preparar auditoria de publicabilidad: SKUs activos con precio, imagen, presentacion, no fraccionarios y con categoria/marca.
3. Crear pantalla interna ERP de publicaciones para seleccionar SKUs publicables.
4. Crear endpoints publicos read-only de catalogo, detalle, filtros y disponibilidad.
5. Crear frontend publico minimo con catalogo, detalle y carrito local.
6. Preparar dry-run de cotizacion: recalcula precios y disponibilidad sin escribir.
7. Autorizar DDL de cotizaciones/publicaciones con respaldo externo.
8. Habilitar registro de cotizacion y evento WhatsApp.
9. Preparar bandeja interna de cotizaciones para seguimiento y conversion manual futura.

## Guardrails

- Todo endpoint publico debe devolver solo datos publicables.
- Todo POST publico debe tener rate limit/captcha o mitigacion equivalente antes de produccion.
- No depender de sesion ERP para navegacion publica.
- No exponer permisos ni rutas internas.
- No abrir `CatalogoErp` ni `Ventas` a publico.
- No usar `ecom_*` como fuente nueva.
- No escribir BD sin respaldo externo y autorizacion explicita.

## Handoff

Siguiente chat recomendado:

```text
Trabaja en ERP > Ecommerce publico Fase 1.
Usa AGENTS.md y docs/erp_ecommerce_catalogo_vivo_fase1.md.
Objetivo: preparar solo auditoria read-only y plan DDL para publicaciones/cotizaciones ecommerce.
No ejecutar DDL, no registrar cotizaciones reales, no tocar ecom_* ni POS.
```

## Avance 2026-07-11 - Auditoria read-only y plan DDL preparados

Archivos creados:

- `app/controladores/EcommercePublico.php`
- `app/modelos/EcommerceCatalogoPublico.php`
- `app/modelos/EcommercePublicoEsquema.php`

Endpoints internos preparados:

- `GET /ecommercePublico/publicaciones_auditar_erp`
- `GET /ecommercePublico/esquema_auditar_ecommerce_publico`
- `GET /ecommercePublico/esquema_plan_ecommerce_publico`

Reglas de seguridad actuales:

- Los tres endpoints requieren `catalogo.ver`.
- No ejecutan DDL.
- No crean publicaciones.
- No registran cotizaciones.
- No tocan `ecom_*`.
- No mueven inventario ni crean ventas/POS.

Resultado validado por CLI read-only:

- Auditoria de publicabilidad:
  - `skus_total=1752`
  - `skus_activos_producto_activo=1724`
  - `skus_con_precio=1731`
  - `skus_con_imagen=1544`
  - `skus_con_categoria=1597`
  - `skus_con_marca=754`
  - `skus_fraccionarios=7`
  - `skus_ya_publicados=0`
  - `skus_publicables_fase_1=1363`
- Auditoria de esquema ecommerce publico:
  - `tablas_faltantes=5`
- Plan DDL:
  - `ddl_total=5`
  - `ddl_pendientes=5`
  - `read_only=true`

Validaciones tecnicas:

- `php -l app/modelos/EcommerceCatalogoPublico.php`: sin errores.
- `php -l app/modelos/EcommercePublicoEsquema.php`: sin errores.
- `php -l app/controladores/EcommercePublico.php`: sin errores.

Siguiente paso recomendado:

1. Revisar auditoria desde sesion ERP con usuario que tenga `catalogo.ver`.
2. Preparar pantalla interna `ERP > Ecommerce publico > Publicaciones` en modo read-only.
3. Despues pedir autorizacion fuerte para aplicar DDL con respaldo externo.

## Avance 2026-07-11 - Pantalla interna read-only de publicaciones

Archivos agregados/actualizados:

- `app/vistas/paginas/apps/erp/ecommerce/publicaciones.php`
- `public/assets/js/custom/apps/erp/ecommerce/publicaciones.js`
- `app/controladores/EcommercePublico.php`
- `app/vistas/includes/header/sidebar.php`

Ruta interna:

- `/ecommercePublico/publicaciones`

Navegacion:

- Se agrega acceso en `ERP > Catalogo > Ecommerce publico`.
- Permiso requerido: `catalogo.ver`.

Alcance de la pantalla:

- Muestra KPIs de publicabilidad.
- Lista SKUs candidatos con dictamen `Publicable` o bloqueos: sin precio, sin imagen, sin categoria o granel bloqueado.
- Muestra disponibilidad publica sugerida sin cantidades exactas.
- Muestra auditoria/plan DDL de Fase 1 sin ejecutar SQL.

Guardrails mantenidos:

- No crea publicaciones.
- No ejecuta DDL.
- No registra cotizaciones.
- No toca `ecom_*`.
- No reserva ni descuenta inventario.
- No convierte a venta, pedido, apartado ni atencion POS.

Validaciones tecnicas:

- `php -l app/controladores/EcommercePublico.php`: sin errores.
- `php -l app/vistas/paginas/apps/erp/ecommerce/publicaciones.php`: sin errores.
- `node --check public/assets/js/custom/apps/erp/ecommerce/publicaciones.js`: sin errores.
- `php -l app/vistas/includes/header/sidebar.php`: sin errores.

Siguiente paso recomendado:

1. Probar visualmente `/ecommercePublico/publicaciones` con sesion autorizada.
2. Ajustar criterios de publicabilidad si el dueno decide exigir marca, especie de mascota o necesidad antes de publicar.
3. Preparar solicitud de autorizacion DDL para `erp_ecommerce_*` solo despues de validar la pantalla read-only.

## Avance 2026-07-11 - Paquete de autorizacion DDL preparado

Archivos agregados:

- `docs/erp_ecommerce_publico_schema_solicitud_autorizacion.md`
- `docs/erp_ecommerce_publico_schema_runbook_aplicacion.md`
- `docs/erp_ecommerce_publico_schema_plan_reversa.md`
- `storage/uat/uat_ecommerce_publico_schema_readonly.php`
- `storage/uat/uat_ecommerce_publico_schema_apply_authorized.php`

Token de autorizacion propuesto:

- `ECOMMERCE_PUBLICO_DDL_FASE1`

Alcance:

- El preflight read-only valida respaldo, auditoria de tablas, plan DDL y publicabilidad.
- El script apply queda bloqueado por defecto.
- El apply exige token textual y respaldo valido.
- No se ejecuta DDL en esta fase de preparacion.

Guardrails:

- No toca `ecom_*`.
- No crea publicaciones.
- No registra cotizaciones reales.
- No mueve inventario.
- No crea checkout ni pasarela.
- No convierte nada a POS/Pedidos.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l storage\uat\uat_ecommerce_publico_schema_readonly.php`: sin errores.
- `C:\xampp\php\php.exe -l storage\uat\uat_ecommerce_publico_schema_apply_authorized.php`: sin errores.
- `uat_ecommerce_publico_schema_readonly.php --respaldo=RESPALDO_EXTERN0_ECOM_FASE1`:
  - `ok=true`
  - `modo=read-only`
  - `tablas_faltantes=5`
  - `ddl.total=5`
  - `ddl.pendientes=5`
  - `skus_publicables_fase_1=1363`
- `uat_ecommerce_publico_schema_apply_authorized.php` sin token:
  - `ok=false`
  - `modo=bloqueado`
  - no ejecuto DDL.

## Avance 2026-07-11 - Contratos publicos read-only preparados

Endpoints publicos agregados en `EcommercePublico`:

- `GET /ecommercePublico/catalogo`
- `GET /ecommercePublico/producto/{slug}`
- `GET /ecommercePublico/filtros`
- `GET /ecommercePublico/disponibilidad?id_sku=...`
- `GET /ecommercePublico/disponibilidad?slug=...`

Contrato:

- No requieren sesion ERP.
- Solo leen publicaciones con `estatus_publicacion='publicado'`.
- Si `erp_ecommerce_publicaciones` aun no existe, responden vacio/configurado=false.
- No leen `ecom_*` como fuente publica.
- No exponen cantidades exactas de inventario.
- No descuentan, reservan, apartan ni crean cotizaciones.

Datos publicos previstos:

- imagen;
- nombre publico;
- marca;
- categoria;
- presentacion;
- precio visible si `mostrar_precio=1`;
- disponibilidad simple si `mostrar_disponibilidad=1`;
- mascota/especie;
- necesidades;
- flags de cotizacion/WhatsApp.

Validaciones tecnicas:

- `C:\xampp\php\php.exe -l app\controladores\EcommercePublico.php`: sin errores.
- `C:\xampp\php\php.exe -l app\modelos\EcommerceCatalogoPublico.php`: sin errores.
- Prueba CLI `catalogoPublico` sin esquema aplicado:
  - `error=false`
  - `tipo=info`
  - `configurado=false`
  - `items=[]`
- Prueba CLI `filtrosPublicos` sin esquema aplicado:
  - filtros vacios.
- Prueba CLI `disponibilidadPublica` sin esquema aplicado:
  - `disponibilidad=consultar_disponibilidad`.

## Avance 2026-07-12 - Preparacion read-only de publicaciones

Endpoint interno agregado:

- `GET /ecommercePublico/publicaciones_preparar_erp?id_sku=...`

Alcance:

- Requiere `catalogo.ver`.
- Consulta un SKU activo del Catalogo ERP.
- Devuelve una propuesta de publicacion sin guardar:
  - slug sugerido;
  - titulo publico;
  - presentacion publica;
  - especie de mascota inferida si es posible;
  - necesidades inferidas si es posible;
  - precio vivo desde ERP;
  - imagen viva desde ERP;
  - disponibilidad publica sugerida;
  - bloqueos de publicabilidad.

Pantalla actualizada:

- `app/vistas/paginas/apps/erp/ecommerce/publicaciones.php`
- `public/assets/js/custom/apps/erp/ecommerce/publicaciones.js`

Ahora cada SKU candidato tiene boton `Preparar`, que muestra una vista previa read-only de la ficha ecommerce. No crea registros y no requiere que el esquema `erp_ecommerce_*` exista.

Validacion CLI:

- Se preparo una publicacion de prueba para `id_sku=1291`.
- Resultado:
  - `error=false`
  - `publicable_fase_1=true`
  - `slug=aceite-de-salmon-pza-fl3627`
  - `precio=220`
  - `disponibilidad_publica_sugerida=agotado`
  - `necesidades=["alimento"]`

Decision operativa:

- Un SKU puede ser publicable aunque su disponibilidad publica sea `agotado`; esto permite mostrar catalogo vivo sin vender ni apartar. Si el negocio prefiere ocultar agotados, se debe resolver como politica de publicacion/disponibilidad, no como regla de catalogo maestro.

## Correccion 2026-07-12 - ERP como proveedor API, no como sitio publico

Decision corregida:

- El proyecto ecommerce publico se trabajara aparte.
- Este ERP no debe construir la vista publica final.
- Este ERP debe preparar contratos/API y administracion interna para que el proyecto ecommerce consulte informacion viva.

Se retira el alcance de vista publica dentro del ERP:

- No usar `/ecommercePublico` como tienda.
- No crear vistas publicas finales dentro de `app/vistas/paginas/ecommerce`.
- No crear JS de tienda publica dentro de `public/assets/js/custom/ecommerce`.

Lo que si corresponde a este sistema:

- Endpoints publicos read-only para catalogo, producto, filtros y disponibilidad.
- Endpoints internos protegidos para auditar y preparar publicaciones.
- Esquema `erp_ecommerce_*` para publicaciones, configuracion, cotizaciones y eventos cuando sea autorizado.
- Contratos estables para que el frontend ecommerce separado consuma informacion viva desde ERP.

Endpoints que debe consumir el proyecto ecommerce:

- `GET /ecommercePublico/catalogo`
- `GET /ecommercePublico/producto/{slug}`
- `GET /ecommercePublico/filtros`
- `GET /ecommercePublico/configuracion`
- `GET /ecommercePublico/seo`
- `GET /ecommercePublico/disponibilidad?id_sku=...`
- `GET /ecommercePublico/disponibilidad?slug=...`

Siguiente paso recomendado:

1. Refinar contratos JSON para el ecommerce separado.
2. Preparar CORS/token/rate limit para consumo externo seguro.
3. Autorizar/aplicar DDL `erp_ecommerce_*` con respaldo externo cuando el dueno lo indique.
4. Habilitar guardado interno de publicaciones en borrador/publicado con permiso `catalogo.editar`.

## Avance 2026-07-12 - Manifiesto de contratos API

Endpoint agregado:

- `GET /ecommercePublico/contratos`

Documento agregado:

- `docs/erp_ecommerce_publico_api_contratos.md`

Alcance:

- Describe rutas publicas read-only que consumira el proyecto ecommerce externo.
- Define parametros de catalogo, producto, filtros y disponibilidad.
- Define shape de item de catalogo.
- Documenta estados de disponibilidad publica.
- Documenta guardrails y seguridad futura: CORS, API key/firma, rate limit y captcha para POST futuros.

Decision:

- El frontend ecommerce no debe leer tablas internas.
- El frontend ecommerce debe consumir contratos publicados por `EcommercePublico`.
- Este ERP no construye la tienda visual, solo administra/publica datos vivos.

## Avance 2026-07-12 - Estado/readiness del API

Endpoint agregado:

- `GET /ecommercePublico/estado`

Alcance:

- Reporta si el esquema de publicaciones/configuracion existe.
- Reporta cuantas publicaciones estan en `publicado`.
- Reporta cuantos SKUs son publicables en Fase 1.
- Reporta pendientes de seguridad antes de produccion.
- No expone stock exacto, costos, proveedores ni datos internos.

Tambien se agregaron metadatos `api` a las respuestas del modelo:

- `api.nombre`
- `api.version`
- `api.modo`
- `api.fuente_verdad`
- `api.moneda_default`

Validacion CLI:

- `estadoApiPublica()`:
  - `error=false`
  - `api.version=fase1-2026-07-12`
  - `ready=false`
  - `ddl_pendiente=true`
  - `publicadas=0`
  - `publicables=1363`
- `catalogoPublico()`:
  - conserva `api.version=fase1-2026-07-12`
  - `configurado=false` mientras no exista esquema aplicado.

## Avance 2026-07-12 - Configuracion publica para frontend externo

Endpoint agregado:

- `GET /ecommercePublico/configuracion`

Alcance:

- Devuelve configuracion publica del canal ecommerce.
- Si `erp_ecommerce_configuracion` aun no existe, responde `configurado=false`.
- No hardcodea numero WhatsApp.
- No expone secretos ni claves internas.

Claves publicables:

- `moneda_default`
- `whatsapp_numero_principal`
- `whatsapp_mensaje_base`
- `cors_origenes_permitidos`
- `cotizacion_habilitada`
- `mostrar_stock_exacto`
- `modo_sin_stock`
- `texto_total_estimado`
- `url_sitio_publico`

## Avance 2026-07-12 - Headers API y CORS restringido

Se agrego un responder comun para endpoints publicos de `EcommercePublico`:

- `Content-Type: application/json; charset=utf-8`
- `X-ERP-Ecommerce-API-Version: fase1-2026-07-12`
- `X-ERP-Ecommerce-Mode: catalogo-vivo-readonly`
- `Vary: Origin`

CORS:

- Cerrado por defecto.
- Solo responde `Access-Control-Allow-Origin` si `HTTP_ORIGIN` coincide exactamente con una entrada de `cors_origenes_permitidos`.
- `cors_origenes_permitidos` se lee desde `erp_ecommerce_configuracion` cuando exista.
- Permite `GET, OPTIONS`.
- Headers permitidos previstos: `Content-Type`, `X-Ecommerce-Api-Key`, `X-Ecommerce-Signature`.

Guardrail:

- No se configura `*` como origen.
- No se habilitan POST publicos.
- No se autentica todavia con API key; solo se reserva el contrato de headers para una fase posterior.

Validacion CLI:

- `php -l app/controladores/EcommercePublico.php`: sin errores.
- `php -l app/modelos/EcommerceCatalogoPublico.php`: sin errores.
- `origenCorsPermitido('https://tienda.example.com')` sin configuracion:
  - `false`
- `configuracionPublica()` incluye:
  - `cors_origenes_permitidos`

## Avance 2026-07-12 - Contrato de autenticacion futura

Se agrego al manifiesto `GET /ecommercePublico/contratos` la seccion `autenticacion_futura`.

Estado actual:

- No requerida para GET read-only en Fase 1/local.
- No se guarda ni expone ningun secreto.
- No se rompe el consumo actual de contratos.

Modo recomendado para produccion:

- API key publica para identificar canal.
- Firma HMAC-SHA256 con secreto privado.
- Headers:
  - `X-Ecommerce-Api-Key`
  - `X-Ecommerce-Timestamp`
  - `X-Ecommerce-Nonce`
  - `X-Ecommerce-Signature`

String canonico sugerido:

```text
HTTP_METHOD
REQUEST_PATH
QUERY_STRING_ORDENADO
X_ECOMMERCE_TIMESTAMP
X_ECOMMERCE_NONCE
SHA256_BODY_HEX
```

Guardrails:

- No exponer secretos en `configuracion`.
- No loggear secretos ni firma completa.
- Activar antes de POST publicos o dominios expuestos.
- Mantener CORS restringido aunque exista firma.

Validacion CLI:

- `php -l app/modelos/EcommerceCatalogoPublico.php`: sin errores.
- `php -l app/controladores/EcommercePublico.php`: sin errores.
- `contratosApiPublicos()`:
  - `autenticacion_futura.estado_actual=no_requerida_en_fase1_readonly`
  - `autenticacion_futura.modo_recomendado=api_key_hmac_sha256`
  - headers publicados: `X-Ecommerce-Api-Key`, `X-Ecommerce-Timestamp`, `X-Ecommerce-Nonce`, `X-Ecommerce-Signature`
- `estadoApiPublica()`:
  - `autenticacion_activa=false`
  - `autenticacion_modo_futuro=api_key_hmac`

## Avance 2026-07-12 - Cotizacion dry-run para ecommerce externo

Endpoint agregado:

- `POST /ecommercePublico/cotizacion_dryrun`

Alcance:

- Recibe un carrito del ecommerce externo.
- Recalcula productos publicados desde ERP.
- Ignora cualquier precio enviado por el frontend.
- Devuelve lineas, totales estimados, bloqueos y preview de WhatsApp.

Guardrails:

- No guarda BD.
- No crea cotizacion real.
- No crea pedido, venta ni atencion POS.
- No aparta ni descuenta inventario.
- Si `erp_ecommerce_publicaciones` aun no existe, responde `configurado=false`.

Body sugerido:

```json
{
  "items": [
    {"id_publicacion": 1, "cantidad": 2},
    {"slug": "producto-publico", "cantidad": 1}
  ],
  "contacto": {
    "nombre": "Cliente",
    "telefono": "5555555555",
    "mensaje": "Quiero confirmar disponibilidad"
  },
  "utm": {}
}
```

Validacion CLI:

- `php -l app/controladores/EcommercePublico.php`: sin errores.
- `php -l app/modelos/EcommerceCatalogoPublico.php`: sin errores.
- `cotizacionDryRun()` sin esquema aplicado:
  - `error=false`
  - `tipo=info`
  - `configurado=false`
  - `dry_run=true`
  - `bloqueos=["esquema_publicaciones_pendiente"]`
- `contratosApiPublicos()`:
  - `endpoints=7`
  - incluye `cotizacion_dryrun`.

## Avance 2026-07-12 - Registro real de cotizacion reservado y bloqueado

Endpoint reservado:

- `POST /ecommercePublico/cotizacion_registrar`

Estado:

- Bloqueado en Fase 1.
- No escribe BD.
- No registra cotizacion real.
- No crea pedido, venta ni atencion POS.
- No aparta ni descuenta inventario.

Motivo:

- El ecommerce externo ya puede conocer el contrato futuro sin inventar otro endpoint.
- La persistencia queda condicionada a DDL autorizado, autenticacion activa, rate limit y politica de seguimiento.

Requisitos de activacion:

- Aplicar DDL `erp_ecommerce_*` con respaldo externo.
- Activar API key o firma HMAC.
- Definir rate limit/captcha si aplica.
- Definir politica de contacto y seguimiento CRM.
- Configurar `whatsapp_numero_principal`.

Validacion CLI:

- `php -l app/controladores/EcommercePublico.php`: sin errores.
- `php -l app/modelos/EcommerceCatalogoPublico.php`: sin errores.
- `cotizacionRegistrarBloqueada()`:
  - `error=true`
  - `tipo=warning`
  - `bloqueado=true`
  - `no_escribe_bd=true`
  - conserva resumen del body sin persistir.
- `contratosApiPublicos()`:
  - `endpoints=8`
  - incluye `cotizacion_registrar`.

## Avance 2026-07-12 - UAT read-only de contratos API

Script agregado:

- `storage/uat/uat_ecommerce_publico_api_contracts_readonly.php`

Alcance:

- Valida manifiesto de contratos.
- Valida estado/readiness.
- Valida configuracion publica.
- Valida catalogo publico en modo controlado.
- Valida disponibilidad sin cantidad exacta.
- Valida cotizacion dry-run.
- Valida que registro real de cotizacion siga bloqueado.
- Valida que guardado interno de publicacion siga bloqueado.

Guardrails:

- No escribe BD.
- No ejecuta DDL.
- No toca `ecom_*`.
- No mueve inventario.
- No registra cotizaciones.

Validacion CLI:

- `php -l storage/uat/uat_ecommerce_publico_api_contracts_readonly.php`: sin errores.
- Resultado UAT:
  - `ok=true`
  - `modo=read-only`
  - `api.version=fase1-2026-07-12`
  - `endpoints_total=9`
  - `ready=false`
  - `ddl_pendiente=true`
  - `publicadas=0`
  - `publicables=1363`
  - `registro_cotizacion_bloqueado=true`
  - `guardado_publicacion_bloqueado=true`

## Avance 2026-07-12 - Handoff para frontend ecommerce externo

Documento agregado:

- `docs/erp_ecommerce_publico_frontend_handoff.md`

Incluye:

- variables sugeridas para el proyecto ecommerce;
- orden recomendado de consumo de endpoints;
- readiness esperado;
- ejemplos de catalogo, item y cotizacion dry-run;
- reglas de configuracion publica;
- reglas de CORS/autenticacion futura;
- UAT read-only para validar contratos;
- lista de cosas que el frontend externo no debe hacer.

Decision reforzada:

- El ecommerce visual vive fuera del ERP.
- El ERP solo expone informacion viva y contratos controlados.

## Avance 2026-07-12 - Checklist e instrucciones para iniciar frontend externo

Documentos agregados:

- `docs/erp_ecommerce_publico_checklist_salida_fase1.md`
- `docs/erp_ecommerce_publico_instrucciones_frontend_nuevo_proyecto.txt`
- `docs/erp_ecommerce_publico_prompt_inicio_frontend.txt`

Estado comunicado:

- Ya se puede iniciar el proyecto frontend externo como maqueta tecnica/cliente API.
- Aun no se debe tratar como catalogo vivo real con productos publicados, porque falta DDL y publicaciones.

La senal actual para el dueno del proyecto:

```text
Puedes iniciar el proyecto frontend externo como maqueta tecnica/cliente API, pero aun no como catalogo vivo real.
```

La senal futura esperada:

```text
Ya puedes iniciar/integrar la vista del ecommerce externo con datos reales.
El ERP tiene DDL aplicado, CORS configurado, WhatsApp configurado y primeras publicaciones activas.
```

## Avance 2026-07-12 - Runbook y preflight de activacion operativa

Archivos agregados:

- `docs/erp_ecommerce_publico_activacion_operativa.md`
- `storage/uat/uat_ecommerce_publico_activacion_preflight_readonly.php`

Alcance:

- Define pasos para activar Fase 1 real.
- Valida respaldo externo antes de solicitar DDL.
- Valida DDL pendiente y contratos API.
- Valida SKUs publicables para lote inicial.
- Confirma que cotizacion real y guardado real de publicaciones siguen bloqueados antes de activacion.

Guardrails:

- No ejecuta DDL.
- No crea publicaciones.
- No registra cotizaciones.
- No mueve inventario.

Validacion CLI:

- `php -l storage/uat/uat_ecommerce_publico_activacion_preflight_readonly.php`: sin errores.
- Preflight con `--respaldo=RESPALDO_EXTERN0_ECOM_FASE1`:
  - `ok=true`
  - `modo=read-only`
  - `tablas_faltantes=5`
  - `ddl_total=5`
  - `ddl_pendiente=true`
  - `api.version=fase1-2026-07-12`
  - `endpoints_total=9`
  - `ready=false`
  - `whatsapp_configurado=false`
  - `cors_configurado=false`
  - `skus_publicables_fase_1=1363`
  - `registro_real_bloqueado=true`
  - `guardado_real_bloqueado=true`

Decision:

- El preflight esta listo para solicitar autorizacion DDL con respaldo externo.
- Aun no se debe avisar inicio de vista con datos reales porque faltan DDL, CORS, WhatsApp y publicaciones activas.

## Avance 2026-07-12 - Lote inicial sugerido read-only

Script agregado:

- `storage/uat/uat_ecommerce_publico_lote_inicial_readonly.php`

Alcance:

- Propone un lote inicial de SKUs publicables.
- Genera slug sugerido.
- Sugiere mascota y necesidades cuando se pueden inferir.
- Devuelve precio vivo, imagen/categoria/marca y disponibilidad publica sugerida.
- Sugiere crear como `borrador` cuando se habilite guardado.

Guardrails:

- No escribe BD.
- No crea publicaciones.
- No mueve inventario.
- No toca `ecom_*`.
- No publica automaticamente.

Validacion CLI:

- `php -l storage/uat/uat_ecommerce_publico_lote_inicial_readonly.php`: sin errores.
- `uat_ecommerce_publico_lote_inicial_readonly.php --limite=10`:
  - `ok=true`
  - `modo=read-only`
  - `total_lote=10`
  - `skus_publicables_fase_1=1363`
  - `estatus_sugerido=borrador`

Observacion operativa:

- El lote inicial por orden actual sale sesgado a productos de acuario/pez y con disponibilidad `agotado`.
- Antes de activar publicaciones reales conviene ajustar criterio de seleccion comercial: mezclar perro/gato, alimento/higiene, disponibilidad consultable/disponible y productos con alta rotacion.

## Avance 2026-07-12 - Guardado interno de publicaciones reservado y bloqueado

Endpoint interno reservado:

- `POST /ecommercePublico/publicaciones_guardar_erp`

Seguridad:

- Requiere `catalogo.editar`.
- Al ser POST autenticado, queda sujeto a CSRF del Core.

Estado:

- Bloqueado en Fase 1.
- No escribe BD.
- No crea publicacion.
- No publica SKUs.

Motivo:

- La pantalla interna ya puede tener un contrato futuro claro para guardar borradores/publicados.
- La accion real queda condicionada a DDL aplicado, politica de publicacion y auditoria explicita.

Campos futuros esperados:

- `id_sku`
- `estatus_publicacion`
- `slug`
- `titulo_publico`
- `descripcion_publica`
- `presentacion_publica`
- `mascota_especie`
- `necesidades`
- `destacado`
- `orden`
- `permite_cotizacion`
- `permite_whatsapp`
- `mostrar_precio`
- `mostrar_disponibilidad`

Requisitos de activacion:

- Aplicar DDL `erp_ecommerce_*` con respaldo externo.
- Validar pantalla read-only de publicaciones.
- Confirmar politica de publicacion por SKU.
- Definir si productos agotados se muestran, se ocultan o pasan a consultar.
- Mantener permiso `catalogo.editar`.
- Agregar auditoria explicita al guardar.
