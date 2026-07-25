# ERP Ventas/POS - Configuracion de ticket e impresion

Documento vivo. Ultima actualizacion: 2026-07-24.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host local canonico: `http://panel.com.local/`.

## Objetivo

Dejar el ticket POS listo para operacion real con datos del negocio, logo, formato termico y preparacion para impresora local, sin mezclar el ticket historico de venta con la configuracion fisica de hardware.

## Separacion correcta

El ticket tiene tres capas distintas:

1. **Contenido legal/comercial**
   - Nombre comercial.
   - Razon social.
   - RFC.
   - Direccion fiscal o direccion de sucursal.
   - Telefonos, WhatsApp, sitio web, redes.
   - Leyendas: ticket no fiscal, cambios/devoluciones, garantias, horarios.
   - Folio POS, fecha, caja, turno, operador, cliente.

2. **Formato de impresion**
   - Ancho de papel: 58 mm, 80 mm u otro.
   - Numero aproximado de columnas: 32 para 58 mm, 42/48 para 80 mm segun fuente/driver.
   - Mostrar logo o texto de marca.
   - Cortar descripciones largas.
   - QR/codigo de barras futuro para consultar ticket.
   - Copias: cliente, caja, devolucion.

3. **Salida a impresora**
   - Navegador con `window.print()` para piloto.
   - Impresora instalada en Windows con nombre fijo para uso local.
   - En etapa avanzada: servicio local/bridge para ESC/POS, corte automatico, abrir cajon y evitar dialogo de impresion.

Estas capas no deben mezclarse. Cambiar una impresora no debe cambiar el contenido historico del ticket.

## Estado actual 2026-07-24

El ticket ya existe como read-only desde ventas confirmadas:

- Endpoint: `GET /ventas/ticket_venta_readonly_erp`.
- Vista: `/ventas/venta_detalle`.
- JS: `public/assets/js/custom/apps/erp/ventas/venta_detalle.js`.
- Script UAT: `storage/uat/uat_ventas_pos_ticket_formal_readonly.php`.

Formato actual:

- Texto monoespaciado.
- Ancho fijo actual: `42` columnas.
- Encabezado fijo: `ARTIANI ERP` y `TICKET POS`.
- Impresion actual: navegador con `window.print()`.
- No tiene configuracion formal de logo, RFC, razon social ni plantilla por sucursal/caja.
- No tiene bridge local para mandar directo a impresora.

Diagnostico UAT actual con `POS-20260724-000001`:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260724-000001 --compacto=1
```

Resultado:

- Ticket generado: si.
- Folio visible: si.
- Lineas: `27`.
- Hallazgo: `VENTAS-TICKET-001`, partida sin snapshot de garantia.

Interpretacion: no es problema de impresora. Es una observacion de contenido porque esa venta nacio como venta rapida sin SKU definitivo/garantia al momento del cobro. Para venta rapida clasificada, el ticket historico conserva el snapshot original y no debe inventar garantia despues.

## Modelo de configuracion recomendado

### Configuracion global de negocio

Tabla propuesta: `erp_empresa_configuracion` o usar una tabla transversal si ya se consolida `sys_configuracion_parametros`.

Campos recomendados:

- `nombre_comercial`.
- `razon_social`.
- `rfc`.
- `regimen_fiscal` opcional.
- `direccion_fiscal`.
- `telefono`.
- `whatsapp`.
- `email`.
- `sitio_web`.
- `logo_url` o `logo_archivo`.
- `leyenda_ticket_general`.
- `leyenda_no_fiscal`.
- `leyenda_devoluciones`.
- `leyenda_garantias`.

### Configuracion por sucursal/almacen

Debe vivir ligada a `erp_almacenes` o a `erp_sucursales` si se consolida el modulo de sucursales.

Campos recomendados:

- Nombre de sucursal para ticket.
- Direccion visible en ticket.
- Telefono local.
- Horario.
- Mensaje local.
- Si usa datos fiscales globales o datos propios.

### Configuracion por caja/terminal

Debe ligarse a `erp_pos_cajas` o `erp_pos_terminales`.

Campos recomendados:

- `ticket_ancho_mm`: `58`, `80`, `personalizado`.
- `ticket_columnas`: `32`, `42`, `48`.
- `ticket_fuente`: `monospace`, `condensed`.
- `mostrar_logo`: si/no.
- `logo_modo`: texto, imagen, ninguno.
- `impresora_nombre_windows`: nombre exacto instalado en Windows.
- `impresion_modo`: navegador, pdf, escpos_bridge.
- `corte_automatico`: si/no.
- `abrir_cajon`: si/no.
- `copias_venta`: 1/2.
- `copias_devolucion`: 1/2.

## Recomendacion para hardware

### Piloto rapido

Usar impresion del navegador:

- Instalar la impresora termica en Windows.
- Configurar tamano de papel en el driver.
- Desde Chrome/Edge seleccionar impresora y tamano.
- Activar margenes minimos o ninguno.
- Probar 58 mm y 80 mm con el mismo ticket.

Ventaja: rapido y sin servicio local.

Riesgo: puede abrir dialogo de impresion y depende del navegador.

### Operacion formal local

Agregar un servicio local de impresion:

- Servicio Windows o app local ligera.
- Recibe JSON del ticket desde el ERP local.
- Imprime por ESC/POS a la impresora configurada.
- Permite corte automatico y cajon de dinero.
- Guarda bitacora local de intentos de impresion.

Ventaja: rapido y profesional.

Riesgo: requiere instalar/actualizar componente local en cada PC/caja.

## Anchos recomendados

- 58 mm: usar `32` columnas, ticket mas corto y descripciones recortadas.
- 80 mm: usar `42` o `48` columnas, recomendado para POS principal por claridad.
- Etiquetas: no deben usar el ticket POS; requieren modulo/formato separado de etiquetado.

Recomendacion inicial: comprar/usar ticketera de 80 mm para caja principal. Da mas aire para SKU, cantidad, precio, garantia y leyendas.

## Reglas de ticket historico

- El ticket de una venta confirmada debe respetar snapshots historicos.
- No debe recalcular precio por listas actuales.
- No debe inventar garantia si no existia snapshot al cobrar.
- Si venta rapida se clasifica despues, el detalle puede quedar ligado al SKU real, pero el ticket debe conservar descripcion/precio originales.
- Las leyendas generales si pueden venir de configuracion actual al reimprimir, pero conviene guardar snapshot de configuracion en fase posterior para auditoria total.

## Que falta para produccion

1. Crear DDL de configuracion de ticket/empresa/sucursal/caja.
2. Crear modelo read-only que resuelva configuracion efectiva por almacen/caja/terminal.
3. Cambiar `formatearTicketVenta` para usar configuracion efectiva y ancho variable.
4. Crear pantalla `Ventas/POS > Configuracion ticket` o integrarla a `Configuracion POS`.
5. Crear preview 58 mm / 80 mm.
6. Crear UAT de ticket normal, venta rapida, devolucion y saldo CRM.
7. Preparar bridge local solo cuando ya se elija impresora/hardware.

## Siguiente autorizacion fuerte posible

```text
AUTORIZO PREPARAR DDL CONFIGURACION TICKET POS usando respaldo UAT POS vigente con token VENTAS_POS_TICKET_CONFIG_DDL para UAT POS
```

Esto solo debe preparar/auditar estructura. No debe cambiar ventas, caja, inventario, catalogo ni ecommerce.
## Preparacion DDL autorizada 2026-07-24

Autorizacion recibida:

```text
AUTORIZO PREPARAR DDL CONFIGURACION TICKET POS usando respaldo UAT POS vigente con token VENTAS_POS_TICKET_CONFIG_DDL para UAT POS
```

Cambios preparados en codigo, sin aplicar BD:

- Modelo esquema: `VentasErpEsquema::planActualizarTicketConfigPos(false)`.
- Auditoria esquema: `VentasErpEsquema::auditarTicketConfigPos()`.
- Endpoints soporte:
  - `GET/POST /ventas/esquema_auditar_ticket_config_pos`.
  - `POST /ventas/esquema_actualizar_ticket_config_pos`.
- Script UAT read-only: `storage/uat/uat_ventas_pos_ticket_config_schema_readonly.php`.
- SQL de propuesta: `docs/erp_ventas_pos_ticket_configuracion_schema_propuesta.sql`.

Resultado UAT read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_config_schema_readonly.php --compact=1
```

Salida resumida:

- `read_only`: true.
- Tablas faltantes: `2`.
- Columnas faltantes: `48`.
- Indices faltantes: `6`.
- Pasos DDL generados: `4`.
- No escribe BD, no crea venta, no mueve caja/inventario, no configura impresora y no imprime.

DDL preparada:

1. Crear `erp_empresa_configuracion` para datos formales del negocio: nombre comercial, razon social, RFC, direccion, contacto, logo y leyendas.
2. Crear `erp_pos_ticket_configuracion` para formato por almacen/caja/terminal: 58/80 mm, columnas, logo, modo navegador/bridge, impresora Windows, copias, corte y cajon.
3. Agregar `erp_ventas.ticket_config_snapshot` para que futuras ventas puedan conservar la configuracion usada al cobrar.
4. Agregar indice `idx_ventas_ticket_fecha` para consultas por almacen/caja/fecha.

Siguiente autorizacion para aplicar estructura:

```text
AUTORIZO APLICAR DDL CONFIGURACION TICKET POS usando respaldo UAT POS vigente con token VENTAS_POS_TICKET_CONFIG_DDL confirmacion="APLICAR CONFIG TICKET POS" para UAT POS
```

Despues de aplicar DDL, las tareas correctas son:

1. Sembrar configuracion inicial de empresa/ticket con datos reales o temporales autorizados.
2. Resolver configuracion efectiva por almacen/caja/terminal.
3. Adaptar `formatearTicketVenta()` para usar ancho, logo/texto y leyendas configurables.
4. Crear pantalla de configuracion/preview antes de conectar impresora fisica.
5. Probar ticket 80 mm y, si se requiere, 58 mm.
## Aplicacion DDL autorizada 2026-07-24

Autorizacion recibida:

```text
AUTORIZO APLICAR DDL CONFIGURACION TICKET POS usando respaldo UAT POS vigente con token VENTAS_POS_TICKET_CONFIG_DDL confirmacion="APLICAR CONFIG TICKET POS" para UAT POS
```

Ejecucion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_config_schema_apply_authorized.php --autorizar=VENTAS_POS_TICKET_CONFIG_DDL --respaldo="UAT POS vigente" --confirmacion="APLICAR CONFIG TICKET POS"
```

Resultado:

- `ok`: true.
- Tablas creadas: `erp_empresa_configuracion`, `erp_pos_ticket_configuracion`.
- Columna agregada: `erp_ventas.ticket_config_snapshot`.
- Indice agregado: `erp_ventas.idx_ventas_ticket_fecha`.
- No creo ventas.
- No modifico importes.
- No movio caja ni inventario.
- No configuro impresora del sistema operativo.
- No imprimio tickets.

Post-auditoria read-only:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_config_schema_readonly.php --compact=1
```

Resultado post-auditoria:

- Tablas faltantes: `0`.
- Columnas faltantes: `0`.
- Indices faltantes: `0`.
- Pasos DDL pendientes: `0`.

Siguiente autorizacion recomendada:

```text
AUTORIZO SEMBRAR CONFIGURACION BASE TICKET POS usando respaldo UAT POS vigente con token VENTAS_POS_TICKET_CONFIG_SEED id_usuario=1 nombre_comercial="ARTIANI" ticket_ancho_mm=80 ticket_columnas=42 impresion_modo=navegador para UAT POS
```

Esta semilla debe crear una configuracion inicial editable para piloto. Los datos fiscales reales, logo definitivo e impresora Windows pueden completarse despues desde UI o con otra autorizacion puntual.
## Semilla base autorizada 2026-07-24

Autorizacion recibida:

```text
AUTORIZO SEMBRAR CONFIGURACION BASE TICKET POS usando respaldo UAT POS vigente con token VENTAS_POS_TICKET_CONFIG_SEED id_usuario=1 nombre_comercial="ARTIANI" ticket_ancho_mm=80 ticket_columnas=42 impresion_modo=navegador para UAT POS
```

Ejecucion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_config_seed_authorized.php --autorizar=VENTAS_POS_TICKET_CONFIG_SEED --respaldo="UAT POS vigente" --id_usuario=1 --nombre_comercial="ARTIANI" --ticket_ancho_mm=80 --ticket_columnas=42 --impresion_modo=navegador
```

Resultado:

- Empresa principal creada: `id_empresa_configuracion=1`, `nombre_comercial=ARTIANI`.
- Configuracion global creada: `id_ticket_configuracion=1`.
- Formato: `80mm`, `42` columnas, fuente monoespaciada.
- Impresion: `navegador`.
- Leyenda general: `Gracias por su compra.`
- Leyenda no fiscal: `Ticket no fiscal. Conserve este comprobante.`
- No creo ventas, no movio caja/inventario, no configuro impresora del sistema operativo y no imprimio.

## Resolutor efectivo conectado 2026-07-24

Se agrego resolutor read-only en `VentasErp`:

- `ticketConfiguracionEfectivaReadOnly()`.
- `configuracionTicketParaVenta()`.
- `resolverConfiguracionTicketPos()`.
- `normalizarConfiguracionTicketPos()`.

Regla operativa:

1. Si una venta tiene `ticket_config_snapshot`, se respeta ese snapshot historico.
2. Si no tiene snapshot, el ticket usa configuracion efectiva actual por terminal/caja/almacen/global.
3. La configuracion global sembrada aplica como fallback para cualquier POS.
4. No se guarda snapshot todavia al cobrar; esa es la siguiente tarea para ventas futuras.

UAT read-only de ticket formal:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260724-000001 --compacto=1
```

Resultado observado:

- Encabezado visible: `ARTIANI`.
- Ancho efectivo: `42` columnas.
- Leyendas vienen de configuracion base.
- Hallazgo esperado: `VENTAS-TICKET-001` por venta rapida historica sin snapshot de garantia.

Script nuevo para validar configuracion efectiva:

```powershell
C:\xampp\php\php.exe storage\uat\uat_ventas_pos_ticket_config_efectiva_readonly.php --compact=1
```

Siguiente tarea sin DDL:

- Guardar `ticket_config_snapshot` al confirmar ventas nuevas para que el ticket historico no cambie si despues se modifican datos del negocio, leyendas o ancho.

Siguiente tarea con UI:

- Crear pantalla de configuracion de ticket con preview 80mm/58mm, logo, datos fiscales, leyendas e impresora Windows.
## Ajuste impresion navegador 80mm 2026-07-24

Hallazgo operativo reportado:

- Al imprimir desde POS/PDF en una impresora 80mm, el navegador estaba usando comportamiento de hoja carta.
- Se veian margenes aproximados de 1.5 cm por lado.
- El PDF salia muy largo porque no tenia tamano de pagina termico definido.

Ajuste aplicado sin tocar BD:

- `public/assets/js/custom/apps/erp/ventas/pos.js`.
- `public/assets/js/custom/apps/erp/ventas/venta_detalle.js`.

Cambio tecnico:

- La ventana de impresion ahora genera documento termico con `@page` dinamico.
- Papel CSS: `80mm x alto_estimado_por_lineas`.
- Margen de pagina: `0`.
- Area util del ticket: `76mm`.
- Fuente monoespaciada: `10.5px`.
- Alto estimado: minimo `80mm`, maximo `900mm`, calculado por cantidad de lineas.

Instruccion para prueba real:

1. En POS, cobra o abre un ticket existente.
2. Presiona `Imprimir ticket`.
3. En el dialogo del navegador, selecciona la impresora termica 80mm.
4. Si aparece tamano de papel, elegir `80mm`, `Receipt`, `POS`, `Roll Paper` o el tamano personalizado de la impresora.
5. Margenes: `Ninguno` o `Minimos`.
6. Escala: `100%`.
7. Desactivar encabezados y pies del navegador.
8. Si se imprime como PDF, verificar que el tamano de pagina no sea `Carta`; debe respetar CSS o elegir 80mm/manual.

Nota importante:

El navegador no siempre puede controlar al 100% el driver termico si Windows/Chrome tienen guardado papel `Carta`. Para operacion formal, el driver de la impresora debe tener un tamano 80mm/rollo configurado como predeterminado. El bridge ESC/POS futuro eliminara este dialogo y reducira dependencia del PDF/navegador.
## Ajuste legibilidad y ruta a impresion directa 2026-07-24

Ajuste posterior al primer piloto de impresion:

- Se restauro fuente de ticket a `12px` porque `10.5px` era demasiado pequeno para mostrador.
- Se mantuvo `@page 80mm` para evitar hoja carta.
- El area util ahora usa `80mm` con padding minimo `1mm 1.5mm`.

Opciones para imprimir directo a USB en Windows:

### Opcion 1 - Piloto rapido: Chrome/Edge con kiosko de impresion

Funciona si:

- La impresora termica esta instalada en Windows.
- La impresora esta como predeterminada o Chrome recuerda esa impresora.
- El papel del driver esta configurado como 80mm/rollo, no carta.

Atajo sugerido para abrir POS:

```text
chrome.exe --kiosk-printing --app=http://panel.com.local/ventas/pos
```

Con `--kiosk-printing`, cuando el sistema llama `window.print()`, Chrome intenta imprimir sin dialogo en la impresora predeterminada. Es util para piloto, pero depende del navegador/driver y no controla corte automatico ni cajon de dinero.

### Opcion 2 - Operacion formal: bridge local ESC/POS

Es la ruta recomendada para POS robusto:

- Un servicio local en la PC de caja escucha solo en `127.0.0.1`.
- El ERP envia JSON del ticket al bridge.
- El bridge imprime por Windows/USB o ESC/POS.
- Permite corte automatico, abrir cajon, reintentos y bitacora local.
- Evita dialogo del navegador y reduce problemas de papel carta/PDF.

Regla de seguridad:

- El navegador no debe imprimir directo a USB por JavaScript puro; los navegadores lo bloquean por seguridad.
- Si se requiere impresion directa formal, debe existir modo `escpos_bridge` configurado en `erp_pos_ticket_configuracion` y una app/servicio local autorizado.

Siguiente tarea tecnica recomendada:

1. Detectar/registrar nombre exacto de impresora Windows en configuracion POS.
2. Crear endpoint read-only que entregue ticket en JSON estructurado para bridge.
3. Preparar bridge local minimo para Windows USB.
4. Agregar boton de prueba `Imprimir prueba 80mm`.
5. Activar `impresion_modo=escpos_bridge` solo cuando el piloto imprima correctamente.

## Chrome kiosko piloto 2026-07-24

Se agrego guia operativa para instalar/usar cada computadora de caja con Chrome/Edge en modo `--kiosk-printing`:

- `docs/erp_pos_impresion_chrome_kiosko.md`.
- `tools/windows/abrir_pos_chrome_kiosko.bat`.

Tambien se agrego en POS el boton `Prueba 80mm` dentro del modal de ticket. Esta prueba no crea venta ni escribe BD; solo manda un ticket muestra a la misma funcion de impresion termica del POS.
