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
