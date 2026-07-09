# ERP - Gastos y cargos asociados a compras

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-17  
Estado: Documento vivo para retomar en Finanzas, Costos e Inventario.

## Objetivo

Definir como debe comportarse el ERP cuando una compra incluye conceptos que aumentan el monto a pagar, pero no son productos inventariables: flete, envio, empaque, maniobra, seguro, comisiones, servicios, ajustes u otros cargos.

Estos conceptos si importan para administracion y contabilidad, pero no siempre deben entrar a almacen ni modificar inventario directamente.

## Regla operativa actual en Compras

En Ordenes de Compra se permiten partidas no inventariables con `tipo_item`:

- `cargo`
- `servicio`
- `adicional`
- `no_inventariable`

Reglas actuales:

- Se pueden guardar en borrador.
- Pueden enviarse sin SKU ERP.
- Suman al subtotal, impuestos y total de la orden.
- No generan pendiente de alta de producto.
- No deben crear detalle de recepcion de almacen.
- No deben generar existencia, lote, caducidad ni codigo unico.

Evidencia:

- `OC-2026-000024` incluye `Empaque y maniobra` como `cargo`.
- `REC-OC-24` fue corregida para conservar solo partidas fisicas inventariables.

## Diferencia por tipo

### Cargo

Costo accesorio de la compra cobrado por proveedor o marketplace.

Ejemplos:

- Envio.
- Flete.
- Maniobra.
- Empaque.
- Seguro de traslado.
- Comision.
- Cargo por manejo.

### Servicio

Trabajo o actividad prestada por tercero, relacionada o no con productos.

Ejemplos:

- Instalacion.
- Reparacion.
- Maquila.
- Personalizacion.
- Armado.
- Corte.

### Adicional

Concepto flexible o temporal cuando aun no se sabe si es cargo, servicio o ajuste.

Ejemplos:

- Diferencia de cotizacion.
- Ajuste especial.
- Concepto no identificado en factura.
- Otros de factura.

### No inventariable

Objeto fisico o consumible que se compra para operacion interna, pero no se controla como inventario vendible.

Ejemplos:

- Papeleria.
- Material de limpieza.
- Herramienta menor.
- Insumo de empaque interno.
- Consumible operativo.

## Tratamiento recomendado en un ERP robusto

Un ERP bien estructurado separa tres preguntas:

1. Se paga?
2. Se recibe en almacen?
3. Afecta costo de inventario?

Un cargo puede responder:

- Se paga: si.
- Se recibe en almacen: no.
- Afecta costo de inventario: depende de la politica contable/costos.

## Politicas posibles de costo

### 1. Gasto administrativo directo

El cargo se registra como gasto del periodo.

Uso recomendado:

- Paqueteria ocasional.
- Comision bancaria.
- Servicio no relacionado directamente con mercancia.
- Gastos de operacion interna.

Impacto:

- No modifica costo unitario de productos.
- Se refleja en reportes de gastos/finanzas.

### 2. Costo capitalizable o prorrateado al inventario

El cargo se distribuye entre productos comprados para formar costo real de adquisicion.

Uso recomendado:

- Flete de importacion.
- Seguro de traslado de mercancia.
- Maniobra necesaria para traer producto vendible.
- Gastos que forman parte del costo de poner el inventario disponible para venta.

Impacto:

- No se recibe como producto.
- Se reparte sobre productos inventariables.
- Puede aumentar costo promedio/ultimo costo/costo real de recepcion.

Formas de prorrateo futuras:

- Por importe de producto.
- Por cantidad.
- Por peso/volumen.
- Manual por partida.

### 3. Cargo no prorrateado pero visible en rentabilidad

El cargo no modifica costo unitario de inventario, pero si se considera en rentabilidad de la compra o pedido.

Uso recomendado:

- Analisis interno de utilidad real por compra.
- Casos donde contablemente es gasto, pero comercialmente se quiere medir margen neto.

## Decisiones pendientes para Finanzas/Costos

Cuando se construya el modulo correspondiente, definir:

- Catalogo de tipos de gasto/cargo.
- Cuenta contable o categoria administrativa.
- Si el cargo es deducible/requiere factura.
- Si tiene IVA acreditable.
- Si debe prorratearse a productos.
- Metodo de prorrateo por defecto.
- Si Compras puede elegir politica o solo Finanzas/Costos.
- Si el XML debe sugerir automaticamente tratamiento de cargos.
- Reportes:
  - gastos por proveedor;
  - gastos por orden;
  - costo real de compra;
  - diferencia entre costo de producto y costo landed/prorrateado;
  - margen bruto antes y despues de cargos.

## Implementacion futura sugerida

Crear una estructura independiente de detalle de inventario, por ejemplo:

- `erp_compras_ordenes_cargos_costos`
- `erp_compras_cargos_tipos`
- `erp_costos_prorrateos_compra`

Campos candidatos:

- `id_orden_compra`
- `id_detalle_orden` si nace de una partida no inventariable
- `tipo_cargo`
- `descripcion`
- `monto`
- `porcentaje_impuesto`
- `total`
- `tratamiento`: `gasto`, `prorrateo_inventario`, `rentabilidad`
- `metodo_prorrateo`: `importe`, `cantidad`, `peso`, `volumen`, `manual`
- `estatus`: `pendiente`, `validado_finanzas`, `aplicado_costos`, `cancelado`
- `creado_por`
- `validado_por`

## Regla para no olvidar

Compras captura el cargo para que el total de la factura/orden cuadre.  
Almacen no recibe cargos.  
Finanzas valida el gasto y su documento.  
Costos decide si se prorratea al inventario o queda como gasto/rentabilidad.
