# ERP Catalogo - Handoff a Ventas para paquetes configurables

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: Contrato de diseño; pendiente de implementar en Ventas  
Origen: Catalogo ERP > Paquetes configurables

## Proposito

Definir que debe considerar Ventas cuando Catalogo ERP tenga paquetes simples o configurables.

Catalogo no vende ni calcula precio final. Catalogo entrega la receta y las opciones posibles. Ventas captura la configuracion final elegida por el cliente y guarda el snapshot operativo.

## Datos que Ventas debe leer de Catalogo

Para cada SKU paquete:

- `erp_catalogo_sku_paquetes`
  - tipo de paquete;
  - modo de disponibilidad;
  - si permite configuracion del cliente;
  - si requiere armado en almacen.
- `erp_catalogo_sku_paquete_componentes`
  - componentes fijos;
  - cantidad de cada componente fijo.
- `erp_catalogo_sku_paquete_grupos`
  - grupos seleccionables;
  - minimo y maximo de selecciones;
  - modo de cantidad.
- `erp_catalogo_sku_paquete_grupo_opciones`
  - opciones SKU disponibles por grupo;
  - cantidad default;
  - limites de cantidad;
  - si permite cantidad editable.

## Reglas de captura en Ventas

- Si el paquete es simple, Ventas puede agregarlo sin pedir opciones.
- Si el paquete es configurable, Ventas debe abrir configurador antes de agregarlo al documento.
- No permitir confirmar si falta cumplir `min_selecciones` de un grupo obligatorio.
- No permitir elegir mas de `max_selecciones`.
- No permitir opciones inactivas, fusionadas o descontinuadas.
- Si una opcion permite cantidad editable, validar minimo, maximo y decimales segun unidad.

## Snapshot obligatorio

Ventas debe guardar en el documento de venta:

- SKU paquete vendido.
- Version/snapshot de componentes fijos.
- Grupos disponibles al momento de vender.
- Opciones elegidas por el cliente.
- Cantidad final por opcion.
- Precio aplicado por lista/canal cuando exista modulo de precios.

Razon:

- Si Catalogo cambia la receta despues, la venta historica debe conservar que se vendio realmente.

## Precio

Catalogo no define precio final.

Opciones futuras para Precios/Ventas:

- precio fijo del paquete;
- precio base + extras por opcion;
- suma de componentes con descuento;
- precio por lista/canal;
- promocion temporal.

No resolver esto en Catalogo.

## Impacto en Inventario

Ventas no debe consumir inventario por si sola si el flujo operativo exige Almacen/Inventario.

Debe enviar a Almacen/Inventario:

- SKU paquete;
- componentes fijos;
- opciones elegidas;
- cantidades finales;
- si se consume al vender o se requiere preparacion/armado.

## Pendiente para el chat de Ventas

Cuando se trabaje Ventas:

1. Agregar lectura de receta de paquete desde Catalogo.
2. Diseñar configurador de paquete.
3. Definir estructura de snapshot en partidas de venta.
4. Definir validacion de disponibilidad por componentes/opciones.
5. Definir contrato con Inventario para consumir los SKUs elegidos.
