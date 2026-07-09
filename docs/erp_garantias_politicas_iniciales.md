# ERP Garantias - Politicas iniciales sugeridas

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: propuesta operativa; no insertar sin revision.

## Objetivo

Definir un conjunto inicial de politicas de garantia para arrancar el modulo sin inventar reglas por producto.

Estas politicas son plantillas. La asignacion real debe hacerse por SKU, producto, categoria, marca o proveedor desde Catalogo/Garantias.

## Politicas sugeridas

### SIN_GARANTIA

Tipo:

- `sin_garantia`

Duracion:

- 0 dias.

Uso:

- Consumibles sin garantia posterior.
- Producto vendido como oferta final.
- Productos donde solo aplica aclaracion inmediata por error de venta.

Cobertura:

- Sin cambio.
- Sin reparacion.
- Sin devolucion por garantia.

Requisitos:

- Ticket solo para aclaraciones administrativas.

Notas:

- No significa que el producto pueda venderse sin cumplir ley o politicas comerciales.
- Solo indica que no hay garantia operativa posterior.

### GAR_TIENDA_7_DIAS_CAMBIO

Tipo:

- `garantia_tienda`

Duracion:

- 7 dias.

Uso:

- Productos donde la tienda acepta cambio por defecto evidente o error operativo.

Cobertura:

- Cambio de producto.
- Rechazo por mal uso.

Requisitos:

- Ticket.
- Producto completo.
- Empaque razonable si aplica.
- Diagnostico basico.

### GAR_TIENDA_30_DIAS_DIAGNOSTICO

Tipo:

- `garantia_tienda`

Duracion:

- 30 dias.

Uso:

- Productos de mayor valor donde la tienda valida defecto antes de decidir cambio/reparacion.

Cobertura:

- Diagnostico previo.
- Cambio si procede.
- Reparacion si procede.

Requisitos:

- Ticket.
- Cliente identificado recomendado.
- Evidencia/fotos si aplica.
- Diagnostico.
- Autorizacion de supervisor para excepciones.

### GAR_PROVEEDOR_SEGUN_POLITICA

Tipo:

- `garantia_proveedor`

Duracion:

- Segun proveedor; capturar valor real cuando se asigne.

Uso:

- Productos donde el proveedor/fabricante toma la decision final.

Cobertura:

- Envio a proveedor.
- Reposicion o nota segun respuesta proveedor.
- Rechazo si proveedor no valida.

Requisitos:

- Ticket.
- Cliente identificado.
- Serie/lote si aplica.
- Fotos/evidencia.
- Validacion de proveedor.

### GAR_FABRICANTE_SERIE

Tipo:

- `garantia_fabricante`

Duracion:

- Segun fabricante; capturar valor real cuando se asigne.

Uso:

- Equipos con serie de fabricante.

Cobertura:

- Reparacion.
- Reemplazo segun fabricante.
- Envio a proveedor/fabricante.

Requisitos:

- Ticket.
- Serie de fabricante obligatoria.
- Unidad vendida ligada a venta.
- Diagnostico.
- Evidencia.

### CADUCIDAD_CALIDAD_LIMITADA

Tipo:

- `caducidad_calidad`

Duracion:

- Variable segun lote/caducidad.

Uso:

- Productos perecederos o sensibles a caducidad/calidad.

Cobertura:

- Diagnostico.
- Cambio o nota si procede por defecto atribuible a tienda/proveedor.

Requisitos:

- Ticket.
- Lote.
- Fecha de caducidad.
- Evidencia/fotos.
- Revision de condiciones de almacenamiento.

## Reglas de asignacion recomendadas

- Empezar por categoria/marca para reducir captura.
- Usar regla por SKU solo cuando la politica realmente difiera.
- Usar regla por proveedor cuando la garantia dependa de proveedor/fabricante.
- Evitar politicas por producto si el producto tiene variantes/SKUs con garantias diferentes.
- Documentar excepciones con observaciones.

## Lo que no debe hacerse

- No capturar duraciones aproximadas sin fuente.
- No prometer garantia de proveedor si el proveedor no la respalda.
- No usar `sin_garantia` como forma de ocultar responsabilidades legales o comerciales.
- No permitir que POS cambie la politica en venta.

## Criterio para arrancar

Antes de habilitar el flujo:

1. Crear `SIN_GARANTIA`.
2. Crear una politica tienda basica.
3. Crear una politica proveedor.
4. Probar resolver SKU con politica directa y por categoria.
5. Validar ticket/snapshot en dry-run.
