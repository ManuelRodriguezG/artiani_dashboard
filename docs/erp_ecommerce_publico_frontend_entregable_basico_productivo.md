# ERP Ecommerce publico - Entregable basico frontend productivo

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-26  
Estado: guia de salida para frontend externo; no activa checkout ni escrituras publicas.

## Objetivo

Tener un frontend publico basico, lanzable despues de que el ERP quede listo para consumo productivo.

El entregable basico no es un ecommerce completo. Es:

- catalogo vivo;
- busqueda;
- filtros;
- ficha de producto;
- carrito local tipo cotizacion;
- validacion `cotizacion_dryrun`;
- envio por WhatsApp;
- politicas publicas;
- pantalla de facturacion por folio sin POST real todavia;
- navegacion por mascota/necesidad;
- base local/mock de analytics.

## API base local

```text
ERP_API_BASE_URL=http://panel.com.local
ERP_ECOMMERCE_BASE_PATH=/ecommercePublico
```

## API base productiva futura

La URL final del frontend sera:

```text
https://artiani.com.mx
```

Antes de publicar, el ERP debe permitir CORS para ese origen exacto y configurar `url_sitio_publico=https://artiani.com.mx`.

## Endpoints obligatorios

```http
GET /ecommercePublico/estado
GET /ecommercePublico/contratos
GET /ecommercePublico/configuracion
GET /ecommercePublico/seo
GET /ecommercePublico/filtros
GET /ecommercePublico/politicas
GET /ecommercePublico/politica/{slug}
GET /ecommercePublico/taxonomia_mascotas
GET /ecommercePublico/catalogo
GET /ecommercePublico/producto/{slug}
GET /ecommercePublico/disponibilidad
POST /ecommercePublico/cotizacion_dryrun
```

Bloqueado en Fase 1:

```http
POST /ecommercePublico/cotizacion_registrar
POST /ecommercePublico/facturacion_solicitar
POST /ecommercePublico/evento_navegacion
POST /ecommercePublico/busqueda_registrar
```

## Pantallas minimas

- `/` o `/catalogo`: catalogo usable como primera pantalla.
- `/producto/:slug`: ficha de producto.
- `/cotizacion`: carrito/cotizacion local.
- `/politicas`: indice de politicas.
- `/politicas/:slug`: politica individual.
- `/facturacion`: solicitud visual por folio, sin POST real.
- `/estado`: diagnostico basico opcional para soporte.

## Componentes minimos

- buscador;
- filtros por mascota/necesidad/marca/categoria;
- selector visual de mascota/necesidad desde `taxonomia_mascotas`;
- tarjeta de producto;
- badge de disponibilidad;
- drawer o pagina de carrito;
- resumen de total estimado;
- boton WhatsApp;
- estados de carga/error/vacio;
- aviso de cotizacion sujeta a confirmacion;
- bloque de politicas/privacidad visible.

## Reglas de UX

- La primera pantalla debe ser el catalogo usable, no una landing generica.
- Usar lenguaje de cotizacion, no de compra finalizada.
- Mostrar “total estimado sujeto a confirmacion”.
- No mostrar stock exacto.
- No decir “pagado”, “pedido confirmado” ni “compra finalizada”.
- Si WhatsApp no esta configurado, mostrar estado claro y no abrir `wa.me`.
- Si `ready=false`, mostrar catalogo en preparacion o modo mantenimiento amigable.

## Pruebas desde ERP

Base cimentada para frontend basico, sin produccion:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_base_cimentada_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --min_publicadas=2 --min_preview=6 --skus_preview=415,866,386,1138
```

Senal esperada:

```text
senal_base_ecommerce=verde_base_cimentada_frontend_basico
```

Contrato completo:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_contract_shape_readonly.php
```

Entregable local:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_entregable_gate_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus_preview=415,866,386,1138 --min_publicadas=2 --min_preview=6
```

Salida productiva futura:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_productivo_gate_readonly.php --base=http://panel.com.local --origin=https://artiani.com.mx --url=https://artiani.com.mx --min_publicadas=6
```

Politicas/taxonomia:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_experiencia_cliente_http_readonly.php --base=http://panel.com.local
```

Paquete de handoff:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_frontend_package_readonly.php --base=http://panel.com.local
```

## Criterio local verde

Primero debe existir base cimentada:

```text
senal_base_ecommerce=verde_base_cimentada_frontend_basico
```

Despues el entregable local:

```text
senal_entregable_frontend=verde_entregable_frontend
```

Debe validar:

- API ready;
- DDL base aplicado;
- CORS local `http://artiani.com.local`;
- WhatsApp configurado;
- catalogo con productos reales;
- `cotizacion_dryrun` funcional;
- politicas funcionales;
- taxonomia mascotas funcional;
- preview de 6 tarjetas posible.

## Criterio productivo verde

```text
senal_productivo_frontend=verde_productivo_frontend_basico
```

Debe validar:

- origen productivo HTTPS;
- CORS exacto para `https://artiani.com.mx`;
- `url_sitio_publico=https://artiani.com.mx`;
- minimo de productos publicados definido;
- WhatsApp configurado;
- stock exacto oculto;
- politicas y taxonomia disponibles;
- `cotizacion_dryrun` funcional;
- `cotizacion_registrar` sigue bloqueado en Fase 1.

## Lo que sigue fuera del basico

- checkout;
- pasarela;
- pagos online;
- pedido confirmado;
- descuento de inventario;
- registro autoservicio de clientes;
- POST real de facturacion;
- POST real de tracking;
- panel ERP real de analytics;
- mascotas registradas;
- recompensas y recomendaciones avanzadas.
