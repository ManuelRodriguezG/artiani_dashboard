# ERP POS - Venta controlada con inventario pendiente y checador de precios

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-09  
Estado: plan funcional, sin DDL ni escritura BD  
Modulos: POS, Inventario/Existencias, Notificaciones, Catalogo, Reportes

## Objetivo

Permitir que el negocio siga vendiendo mientras se regulariza el inventario, sin esconder el problema operativo.

La venta sin existencia no debe ser una excepcion invisible. Debe crear una deuda operativa trazable para Inventario/Existencias, asignada por tienda/SKU, con alerta, seguimiento y cierre por conteo express.

## Principio

No se debe llamar simplemente `venta negativa`.

Nombre operativo recomendado:

- `venta_con_existencia_pendiente`
- `alerta_inventario_express`
- `conteo_express_postventa`

Esto evita normalizar la falta de control. El sistema permite vender porque el negocio lo necesita, pero convierte cada caso en una tarea medible.

## Regla de negocio propuesta

Cuando POS vende un SKU que controla inventario y la tienda no tiene disponible suficiente:

1. POS permite vender solo si el SKU, tienda y usuario cumplen politica autorizada.
2. La venta se cobra y se registra normalmente.
3. El inventario queda negativo o con faltante pendiente, segun el modo tecnico elegido.
4. Se crea alerta operativa para Inventario/Existencias.
5. La alerta obliga a hacer conteo express del SKU en esa tienda.
6. Al capturar conteo real, Inventario aplica ajuste con kardex.
7. La alerta se cierra cuando el saldo queda validado y sin inconsistencia.

Ejemplo:

- Disponible antes: `0`.
- Venta POS: `1`.
- Saldo tecnico queda: `-1`.
- Inventario cuenta fisicamente: `5`.
- Ajuste express debe llevar existencia a `5`.
- En terminos reales, antes de vender habia `6`, se vendio `1`, quedan `5`.

## Politicas obligatorias

No se debe activar globalmente sin control.

Debe configurarse por:

- tienda/almacen;
- SKU o familia;
- canal: solo POS, nunca ecommerce al inicio;
- rol/permiso;
- monto/cantidad maxima;
- ventana temporal;
- motivo obligatorio;
- reporte gerencial.

Politica inicial recomendada:

- Canal permitido: `pos`.
- Ecommerce: bloqueado.
- Unidad fisica cerrada: no vender como cerrada si no esta identificada.
- Unidad abierta: solo granel si SKU lo permite.
- Productos con caducidad/lote sensible: requieren conteo express prioritario.
- Productos de alto valor: requieren autorizacion supervisor.
- Productos seriados/etiquetados: no deben venderse negativos sin seleccionar unidad fisica.

## Diseno tecnico recomendado

### Opcion A - Permitir saldo negativo directo

POS descuenta de una existencia creada o existente, dejando `cantidad` y `cantidad_disponible` negativas.

Ventajas:

- Mas simple.
- El diagnostico actual `INV-DIAG-NEG` ya detecta negativos.
- Se ve claramente el problema.

Riesgos:

- Puede contaminar reportes de disponibilidad.
- Requiere reglas muy claras para no vender indefinidamente.
- Si hay lote/caducidad, el negativo puede quedar sin lote real.

### Opcion B - Venta con faltante pendiente

POS registra la venta y crea un registro de faltante operativo ligado a venta/SKU/tienda, sin necesariamente bajar una existencia real negativa.

Ventajas:

- Mas limpio contablemente.
- Se puede resolver contra conteo express.
- Mejor para reportes y auditoria.

Riesgos:

- Requiere mas DDL y logica.
- La venta debe marcarse como `inventario_pendiente` hasta resolver.

### Recomendacion

Usar enfoque mixto y controlado:

- Para arranque UAT: permitir negativo solo en SKUs autorizados y tienda autorizada.
- En paralelo crear tabla de pendientes para que el problema no dependa solo del saldo negativo.
- El saldo negativo dispara diagnostico y la tabla permite seguimiento, SLA y responsable.

## Tablas sugeridas

### `erp_pos_politicas_venta_inventario`

Define quien puede vender sin existencia.

Campos sugeridos:

- `id_politica`
- `id_almacen`
- `id_sku_erp` nullable
- `familia` nullable
- `canal`
- `permite_venta_sin_existencia`
- `cantidad_maxima_negativa`
- `monto_maximo`
- `requiere_autorizacion`
- `permiso_requerido`
- `estatus`
- `fecha_inicio`
- `fecha_fin`
- `creado_por`
- `datos_snapshot`

### `erp_ventas_inventario_pendientes`

Registra cada deuda operativa nacida en POS.

Campos sugeridos:

- `id_pendiente`
- `folio`
- `id_venta`
- `id_venta_detalle`
- `id_almacen`
- `id_sku_erp`
- `sku_snapshot`
- `cantidad_vendida`
- `cantidad_disponible_previa`
- `faltante_estimado`
- `tipo_origen`: `venta_sin_existencia`
- `estatus`: `pendiente`, `en_revision`, `resuelto`, `descartado`
- `id_notificacion`
- `id_conteo_express`
- `creado_por`
- `resuelto_por`
- `fecha_registro`
- `fecha_resolucion`
- `datos_snapshot`

### `erp_inventario_conteos_express`

Puede ser tabla nueva o extension del conteo fisico actual.

Campos sugeridos:

- `id_conteo_express`
- `folio`
- `id_pendiente`
- `id_almacen`
- `id_sku_erp`
- `cantidad_sistema_antes`
- `cantidad_fisica_contada`
- `cantidad_objetivo`
- `diferencia`
- `id_movimiento_inventario`
- `estatus`
- `contado_por`
- `validado_por`
- `fecha_conteo`
- `fecha_validacion`
- `observaciones`
- `datos_snapshot`

## Alertas

Cada venta con faltante debe crear notificacion en `erp_notificaciones`.

Propuesta:

- `tipo`: `inventario_express_postventa`
- `modulo_origen`: `ventas_pos`
- `entidad_origen`: `erp_ventas_inventario_pendientes`
- `area_responsable`: `inventario`
- `permiso_requerido`: `inventario.operar`
- `prioridad`: `alta`
- `url_accion`: `/inventario/productos_existencias?sku=...&almacen=...`
- `titulo`: `Conteo express requerido`
- `descripcion`: `Venta POS sin existencia disponible en tienda`
- `payload_json`: venta, SKU, tienda, cantidad, usuario, disponibilidad previa

Reglas de cierre:

- No cerrar por leer.
- No cerrar por comentario.
- Se cierra solo cuando:
  - hay conteo express validado;
  - existe movimiento de ajuste o justificacion documentada;
  - saldo queda sin negativo para ese SKU/tienda o el faltante queda explicado.

## Impacto en POS

Cambios esperados:

- En prevalidacion, si falta existencia:
  - mostrar aviso claro: `Venta permitida con inventario pendiente`.
  - exigir motivo o autorizacion si politica lo requiere.
  - marcar partida con badge.
- En cobro real:
  - registrar venta;
  - registrar salida negativa o pendiente;
  - crear alerta;
  - dejar trazabilidad en detalle de venta.

No debe permitir:

- ecommerce con venta cerrada desde unidad abierta;
- venta negativa de unidad fisica cerrada sin unidad;
- venta negativa de productos seriados/etiquetados;
- apartados/reservas sobre existencia inexistente sin politica separada.

## Impacto en Inventario/Existencias

Agregar una bandeja o filtro:

- `Alertas express`
- `Negativos`
- `Pendientes por POS`
- `Conteo express`

Flujo visual:

1. Inventario abre alerta.
2. Ve SKU, tienda, venta y cantidad faltante.
3. Cuenta producto fisicamente.
4. Captura cantidad encontrada.
5. Sistema calcula ajuste necesario.
6. Confirma con motivo.
7. Se crea kardex.
8. Se cierra alerta.

## Reportes gerenciales

Indicadores necesarios:

- ventas con inventario pendiente por tienda;
- usuarios que generan mas pendientes;
- SKUs con mas pendientes;
- tiempo promedio de cierre;
- pendientes vencidos;
- diferencias encontradas por conteo express;
- impacto en margen/costo cuando faltaba costo real.

Esto sirve para medir si el proceso esta mejorando o si la operacion sigue evitando ordenar el inventario.

## Checador de precios con camara

Si se puede hacer.

Modulo propuesto:

- Ruta: `/ventas/checador_precios` o `/catalogo/checador_precios`.
- Uso: celular, tablet o equipo de piso.
- Entrada:
  - busqueda manual por SKU/nombre/codigo;
  - escaneo con camara de codigo de barras/QR;
  - escaneo de etiqueta interna ERP si existe.
- Salida:
  - imagen del producto;
  - nombre corto;
  - SKU;
  - precio POS vigente;
  - promociones/lista si aplica;
  - disponibilidad por tienda actual;
  - aviso si requiere validacion de inventario;
  - boton opcional: `Solicitar conteo express` si el precio existe pero stock esta dudoso.

Tecnologia recomendada:

- Primero usar `BarcodeDetector` nativo del navegador cuando este disponible.
- Si el navegador no lo soporta, usar fallback con libreria JS de escaneo.
- Mantener busqueda manual siempre visible para no depender de la camara.
- Requiere HTTPS o entorno seguro para camara en celular.

Privacidad/seguridad:

- La camara solo corre en el navegador.
- No se debe grabar video.
- Solo se toma el codigo detectado y se manda al backend.

## Fases

### Fase 1 - Diseno y auditoria

- Auditar POS actual para permitir faltantes solo bajo politica.
- Auditar notificaciones para crear alerta desde venta.
- Auditar inventario para bandeja de negativos/pendientes.
- Definir DDL.
- Sin escritura real.

### Fase 2 - Checador de precios read-only

- Vista movil simple.
- Busqueda manual.
- Escaneo camara si esta disponible.
- Consulta precio/disponibilidad.
- No vende, no mueve inventario.

### Fase 3 - Venta con inventario pendiente dry-run

- Prevalidar venta con faltante.
- Mostrar politica y bloqueos.
- Simular alerta.
- No cobrar real todavia.

### Fase 4 - Venta real controlada

- Aplicar solo en una tienda/SKU de UAT.
- Crear alerta real.
- Dejar pendiente operativo.
- Kardex de salida segun decision tecnica.

### Fase 5 - Conteo express y cierre

- Bandeja en Inventario.
- Captura de conteo.
- Ajuste con kardex.
- Cierre de alerta.
- Reporte gerencial.

## Autorizaciones futuras

Primero preparar:

```text
AUTORIZO PREPARAR DDL VENTA INVENTARIO PENDIENTE POS usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_DDL para UAT POS/Inventario
```

Para checador read-only:

```text
AUTORIZO PREPARAR CHECADOR PRECIOS POS READONLY sin escritura BD para UAT POS
```

Para venta real con pendiente:

```text
AUTORIZO EJECUTAR UAT VENTA POS CON INVENTARIO PENDIENTE usando respaldo UAT POS vigente con token VENTAS_POS_INVENTARIO_PENDIENTE_REAL id_usuario=1 id_almacen=5 id_sku=SKU cantidad=CANTIDAD motivo="UAT venta con inventario pendiente"
```

## Decision recomendada

Avanzar primero con el checador de precios read-only y el DDL de pendientes, antes de activar ventas negativas reales.

Razon:

- El checador ayuda inmediatamente en tienda.
- El DDL deja trazabilidad lista.
- Activar negativos sin bandeja de cierre puede crear desorden nuevo encima del desorden actual.
