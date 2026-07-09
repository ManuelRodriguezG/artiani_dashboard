# ERP Catalogo - Handoff a Almacen/Inventario para paquetes y recepcion variable

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: Contrato de diseño; pendiente de implementar en Almacen/Inventario  
Origen: Catalogo ERP > Paquetes configurables y recepcion variable

## Proposito

Definir que debe considerar Almacen/Inventario por los cambios planeados en Catalogo:

- paquetes simples/configurables;
- cantidad variable en recepcion.

Catalogo solo define reglas maestras. Almacen/Inventario ejecuta movimientos, existencias, armado, desarmado y capturas reales.

## Recepcion variable

Campos propuestos en `erp_catalogo_sku_reglas_inventario`:

- `requiere_cantidad_variable_recepcion`
- `requiere_unidades_fisicas_recepcion`
- `tolerancia_recepcion_porcentaje`
- `nota_recepcion_variable`

Reglas para Recepcion:

- Si `requiere_cantidad_variable_recepcion = 1`, no cerrar recepcion solo con cantidad teorica de Compras.
- Capturar cantidad real recibida en unidad base del SKU.
- Si `requiere_unidades_fisicas_recepcion = 1`, capturar desglose por bulto/unidad fisica.
- Comparar cantidad esperada vs cantidad real.
- Si excede tolerancia, generar incidencia de recepcion.
- La existencia debe generarse en unidad base, no en unidades operativas como saco, paca, costal o bolsa.

### Contrato calculo esperado vs real

Catalogo no debe crear unidades operativas como costal, saco, paca o bolsa. La conversion teorica de compra vive en la relacion SKU-proveedor:

- `erp_catalogo_sku_proveedores.id_unidad_compra`
- `erp_catalogo_sku_proveedores.factor_conversion`

Recepcion debe calcular:

```txt
cantidad_esperada_base = cantidad_recibida_compra * factor_conversion
```

Despues debe capturar:

```txt
cantidad_real_base
```

Si `requiere_cantidad_variable_recepcion = 1`, la existencia final debe generarse con `cantidad_real_base`, no con `cantidad_esperada_base`.

Si `requiere_unidades_fisicas_recepcion = 1`, Recepcion debe capturar renglones fisicos:

```txt
unidad_fisica 1 -> cantidad_real_base
unidad_fisica 2 -> cantidad_real_base
...
total_real_base = suma de unidades fisicas
```

La unidad formal sigue siendo la unidad base del SKU, por ejemplo `kg`, `l`, `m` o `pza`.

### Tolerancia e incidencia

Si `tolerancia_recepcion_porcentaje` tiene valor:

```txt
diferencia = abs(cantidad_real_base - cantidad_esperada_base)
porcentaje_diferencia = diferencia / cantidad_esperada_base * 100
```

Si `porcentaje_diferencia > tolerancia_recepcion_porcentaje`, Recepcion debe:

- permitir documentar la diferencia;
- generar incidencia accionable;
- no ocultar la diferencia como si fuera recepcion normal.

Si la cantidad esperada es cero o no se puede calcular, no debe aplicar porcentaje; debe generar incidencia de configuracion o captura incompleta.

### Campos que Almacen debe leer de Catalogo

Desde SKU:

- `erp_catalogo_skus.id_unidad_base`
- `erp_catalogo_skus.factor_unidad_base`

Desde proveedor/SKU proveedor:

- `erp_catalogo_sku_proveedores.id_unidad_compra`
- `erp_catalogo_sku_proveedores.factor_conversion`

Desde reglas de inventario:

- `requiere_cantidad_variable_recepcion`
- `requiere_unidades_fisicas_recepcion`
- `tolerancia_recepcion_porcentaje`
- `nota_recepcion_variable`

### Ejemplo TP-40372

Configuracion esperada:

- Unidad base SKU: `kg`.
- Unidad/factor de proveedor: unidad de compra configurable con `factor_conversion = 4`.
- Compra/recepcion fisica: `5` unidades de compra.

Calculo:

```txt
cantidad_esperada_base = 5 * 4 = 20 kg
```

Si el SKU no requiere cantidad variable:

- la recepcion podria proponer `20 kg` como existencia base.

Si el SKU requiere cantidad variable:

- recepcion debe pedir cantidad real;
- si se capturan `19.7 kg`, existencia final debe ser `19.7 kg`;
- la diferencia contra `20 kg` se evalua contra tolerancia.

## Paquetes simples

Un paquete simple tiene componentes fijos.

Almacen/Inventario debe:

- recibir SKU paquete si ya viene comprado cerrado;
- armar SKU paquete si se prepara internamente;
- descontar componentes fijos cuando se arma o vende segun modalidad;
- generar existencia del SKU paquete si es prearmado.

## Paquetes configurables

Un paquete configurable tiene:

- componentes fijos;
- grupos de seleccion;
- opciones elegidas por cliente/operador.

Almacen/Inventario no debe consumir una receta generica incompleta.

Debe recibir desde Ventas o Preparacion:

- SKU paquete;
- componentes fijos;
- opciones elegidas;
- cantidad final por opcion;
- modo de consumo: venta, preparacion o armado.

## Reglas de armado

- Si `requiere_armado_almacen = 1`, el paquete debe pasar por preparacion/armado antes de generar existencia terminada.
- Si `modo_disponibilidad = por_componentes`, puede validarse disponibilidad por componentes/opciones.
- Si `modo_disponibilidad = por_existencia_armada`, debe existir stock del SKU paquete.
- Si `modo_disponibilidad = mixto`, primero usar existencia armada y despues evaluar componentes si el flujo lo permite.

## Trazabilidad

Si un componente tiene lote, caducidad, serie o etiqueta:

- el armado debe seleccionar unidades/lotes concretos;
- la salida del paquete debe conservar trazabilidad hacia los componentes usados;
- no basta con descontar cantidad agregada sin detalle cuando la regla del SKU exige trazabilidad.

## Pendiente para el chat de Almacen/Inventario

Cuando se trabaje Almacen/Inventario:

1. Leer reglas de recepcion variable desde Catalogo.
2. Ajustar recepcion para captura real por unidad base.
3. Diseñar detalle de unidades fisicas/bultos si aplica.
4. Diseñar armado/desarmado de paquetes.
5. Diseñar consumo de componentes fijos y opciones elegidas.
6. Definir movimientos de inventario para paquete armado y componentes consumidos.
7. Generar incidencias por tolerancia o configuracion incompleta.
