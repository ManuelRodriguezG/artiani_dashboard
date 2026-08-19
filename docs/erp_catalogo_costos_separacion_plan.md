# ERP Catalogo - Separacion de costos y costos derivados

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-18  
Proyecto: `C:\xampp\htdocs\panel_de_control`  
Estado: plan de arquitectura; no implica cambios de codigo ni BD

## Objetivo

Planear la salida gradual de todo lo que parezca captura o decision de costos dentro de Catalogo ERP.

Catalogo debe conservar la configuracion tecnica necesaria para que otros modulos calculen costos, pero no debe ser dueno del costo financiero, costo vigente, costo promedio, costo de proveedor ni costo comercial.

## Regla principal

Catalogo define relaciones y factores.

Rentabilidad/Costos calcula costos.

Listas de precios consulta costos en modo read-only para ayudar a calcular margen, pero no debe crear ni actualizar costos.

Compras/Proveedores/Inventario aportan evidencia de costo.

## Que si debe vivir en Catalogo

Catalogo debe guardar datos estructurales del SKU:

- SKU base y variantes.
- Unidad base.
- Factor/unidad operativa del SKU.
- Presentaciones comerciales.
- Apertura de empaques.
- Relacion entre SKU origen y SKU destino cuando hay conversion.
- Factor de conversion operativo.
- Reglas de granel/fraccionario.
- Relacion SKU-proveedor y factor compra -> inventario.

Estos datos no son costos; son el contrato para que otros modulos calculen correctamente.

## Que no debe vivir en Catalogo

No debe ser responsabilidad de Catalogo:

- costo vigente;
- costo promedio ponderado;
- ultimo costo de compra;
- costo de proveedor como dato financiero;
- margen;
- precio sugerido;
- precio minimo rentable;
- rentabilidad por canal;
- aprobacion de precio;
- valuacion de inventario.

Si hoy existen campos como `costo_referencia` en Catalogo, deben tratarse como legado o fallback temporal, no como fuente preferente.

## Caso: Presentaciones

Ejemplo:

- SKU origen: `SUET-COESS25KG`.
- SKU presentacion: `SUET-COESS1KG`.
- Factor de presentacion: `1 kg`.
- Origen fisico/comercial: se prepara desde un SKU base de mayor contenido.

Catalogo debe decir:

- de que SKU origen parte;
- que SKU destino o presentacion se genera;
- factor operativo;
- unidad base involucrada;
- si se vende como presentacion fija o preparada.

Rentabilidad debe calcular:

```text
costo_presentacion = costo_vigente_sku_origen * factor_consumido_destino / factor_contenido_origen
```

Si el SKU origen ya esta valuado por unidad base, entonces:

```text
costo_presentacion = costo_unitario_base_origen * cantidad_base_de_presentacion
```

Ejemplo:

- Costo vigente del SKU origen de 25 kg: `$500`.
- Costo por kg: `$500 / 25 = $20`.
- Presentacion 1 kg: costo derivado `$20`.
- Presentacion 3 kg: costo derivado `$60`.

El costo no se guarda en Catalogo. Se calcula read-only en Rentabilidad/Listas al momento de evaluar margen.

## Caso: Apertura de empaques

Ejemplo:

- SKU cerrado: costal 20 kg.
- SKU destino granel: kg abierto.
- Operacion: abrir empaque.

Catalogo debe decir:

- SKU origen cerrado;
- SKU destino abierto/granel;
- factor de conversion;
- unidad destino;
- si permite apertura.

Inventario/Almacen ejecuta la apertura real:

- baja unidad cerrada;
- alta existencia abierta;
- registra lote/caducidad/merma si aplica.

Rentabilidad calcula el costo del SKU abierto desde la evidencia de inventario/compra:

```text
costo_unitario_abierto = costo_unidad_cerrada / cantidad_base_generada
```

Si hay merma:

```text
costo_unitario_abierto = costo_unidad_cerrada / cantidad_base_util
```

Ejemplo:

- Costal cerrado costo `$400`.
- Apertura genera `20 kg`.
- Costo por kg abierto: `$20`.
- Si por merma quedan `19.5 kg`, costo por kg util: `$400 / 19.5 = $20.51`.

Catalogo no guarda ese costo; solo permite que el calculo exista de forma consistente.

## Caso: paquete o combo

Si un paquete combina varios SKUs, Catalogo define componentes y cantidades.

Rentabilidad calcula:

```text
costo_paquete = suma(costo_vigente_componente * cantidad_componente)
```

Si el paquete tiene opciones configurables, Rentabilidad debe calcular:

- costo minimo posible;
- costo maximo posible;
- costo de configuracion seleccionada cuando ya existe una venta/pedido real.

Listas de precios puede usar esos costos como referencia read-only para precio/margen.

## Modulo responsable por pregunta operativa

| Pregunta | Modulo responsable |
|---|---|
| Como se compone una presentacion | Catalogo |
| De que SKU se abre un empaque | Catalogo |
| Que factor convierte origen a destino | Catalogo |
| Cuanta existencia se genero realmente | Almacen/Inventario |
| Cual fue el costo real de entrada | Compras/Proveedores/XML/Inventario |
| Cual es el costo derivado de una presentacion | Rentabilidad/Costos |
| Cual es el margen con el precio actual | Rentabilidad/Listas de precios |
| Que precio debe venderse por canal | Listas de precios |

## Impacto en Listas de precios

Listas de precios debe poder mostrar costo estimado para margen, pero como lectura derivada.

Prioridad recomendada:

1. Costo promedio/valuacion de inventario del SKU si existe.
2. Si es presentacion, calcular desde SKU origen y factor de presentacion.
3. Si es apertura/granel, calcular desde SKU cerrado o existencia abierta segun inventario.
4. Ultima compra/XML/proveedor como evidencia cuando no hay inventario.
5. Catalogo `costo_referencia` solo como fallback temporal mientras se limpia el sistema.

Listas de precios no debe pedir al operador capturar costo del SKU en Catalogo.

## Plan de implementacion recomendado

### Paso 1 - Auditoria read-only

Auditar todos los lugares donde Catalogo muestra, guarda o valida costos.

Resultado esperado:

- lista de campos;
- endpoints;
- vistas;
- JS;
- dependencias de Listas/Rentabilidad.

### Paso 2 - Frontera visual

Quitar o mover visualmente campos de costo de Catalogo.

No borrar columnas todavia.

Catalogo puede mostrar un semaforo:

- `sin evidencia de costo`;
- `costo disponible en proveedor`;
- `costo disponible en inventario`;
- `costo derivable por presentacion`;
- `costo pendiente para rentabilidad`.

Pero no debe pedir capturar el costo como parte de editar producto.

### Paso 3 - Resolutor read-only de costo derivado

Crear o consolidar un servicio en Rentabilidad:

```text
resolverCostoVigenteSku(id_sku, contexto)
```

Debe devolver:

- costo;
- moneda;
- fuente;
- confianza;
- formula;
- SKU origen si aplica;
- factor usado;
- advertencias.

### Paso 4 - Integracion con Listas de precios

Listas de precios consulta el resolutor de costo.

La UI muestra:

- costo estimado;
- fuente del costo;
- margen calculado;
- alerta si el costo es derivado o incompleto.

No escribe costo.

### Paso 5 - Deprecar costo de Catalogo

Cuando Rentabilidad/Listas ya no dependan de `costo_referencia`, marcarlo como legado/fallback y quitarlo de los flujos operativos.

No eliminar columnas sin auditoria y respaldo externo.

## Decision recomendada

No resolver costos de presentaciones ni apertura dentro de Catalogo.

Catalogo debe quedar como contrato tecnico de conversion. El costo derivado debe calcularse en Rentabilidad/Costos y consumirse en Listas de precios en modo read-only para margen.

Esto permite vender por lista de precios sin obligar a la persona de Catalogo a conocer costos.
