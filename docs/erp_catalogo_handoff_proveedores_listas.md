# ERP Catalogo - Handoff a Proveedores/listas

Documentacion viva: Codex GPT-5  
Fecha: 2026-06-24  
Estado: Instrucciones para continuar fuera de Catalogo

## Proposito

Este documento prepara el traspaso desde Catalogo ERP hacia Proveedores/listas.

Catalogo ya debe conservar la identidad del producto y SKU. Proveedores/listas debe convertir esa identidad en relaciones comprables: proveedor, SKU proveedor, unidad de compra, factor de conversion, costo/lista vigente y evidencia.

## Regla principal

Proveedores/listas no debe crear productos duplicados si Catalogo ya tiene un SKU ERP candidato.

Si no hay certeza de coincidencia, debe generar incidencia o pendiente accionable para Catalogo, no inventar una relacion silenciosa.

## Entradas desde Catalogo

Proveedores/listas debe consumir o respetar:

- `erp_catalogo_productos`: producto maestro vigente.
- `erp_catalogo_skus`: SKU interno ERP.
- `erp_catalogo_unidades`: unidad base y unidades de compra.
- `erp_catalogo_sku_proveedores`: relacion proveedor-SKU ERP cuando ya exista.
- Incidencias de calidad de Catalogo si un SKU esta incompleto o ambiguo.

## Lo que debe resolver Proveedores/listas

1. Relacionar SKU proveedor con SKU ERP.
2. Guardar codigo/SKU usado por el proveedor.
3. Definir unidad de compra.
4. Definir factor compra -> unidad base inventario.
5. Guardar cantidad minima de compra si aplica.
6. Guardar dias de entrega si aplica.
7. Guardar costo de lista o costo vigente con evidencia.
8. Marcar proveedor preferido solo con criterio claro.
9. Detectar cuando un SKU proveedor representa varias variantes internas.
10. Generar incidencia cuando no pueda relacionar con confianza.

## Criterios de matching

### Match exacto

Usar cuando:

- SKU proveedor coincide exactamente con SKU ERP;
- proveedor esta identificado;
- unidad/factor no contradice la unidad base del SKU ERP;
- no hay otro SKU ERP vigente con la misma clave.

Accion:

- Puede sugerir relacion automatica.
- Si impacta costo o proveedor preferido, debe requerir permiso/confirmacion.

### Match por variante

Usar cuando:

- El proveedor usa un SKU general;
- Catalogo tiene variantes internas;
- la descripcion, color, medida u otro atributo permite identificar la variante.

Accion:

- Sugerir SKU interno especifico.
- Guardar evidencia de atributo usado para relacionar.
- Si la variante no es clara, no relacionar automaticamente.

### Match ambiguo

Usar cuando:

- El SKU proveedor coincide con mas de un SKU ERP;
- hay nombres parecidos pero sin atributo concluyente;
- el proveedor no distingue color/talla/presentacion;
- existen productos fusionados o descontinuados en el historial.

Accion:

- Crear pendiente de revision.
- No actualizar costo ni proveedor preferido.
- No crear producto nuevo hasta que Catalogo confirme.

### Sin match

Usar cuando:

- No hay SKU ERP candidato;
- la descripcion no permite inferir producto;
- la unidad o factor contradice Catalogo.

Accion:

- Crear incidencia hacia Catalogo con evidencia de lista/proveedor.
- Si se autoriza, Catalogo crea SKU temporal en `borrador`; Proveedores/listas relaciona despues.

## Costos

El costo pertenece a Proveedores/Compras/Costos, no al capturista normal de Catalogo.

Reglas:

- Catalogo puede mostrar costo de referencia provisional solo a perfiles con permiso.
- Proveedores/listas debe conservar costo con fuente, fecha, proveedor y lista.
- No sobrescribir costo de referencia si la fuente es ambigua.
- No mezclar precio de venta con costo de proveedor.
- Rentabilidad debe usar costo validado, no solo costo provisional de Catalogo.

## Unidad y factor de compra

Cada relacion proveedor-SKU debe poder responder:

- Que unidad compra el negocio al proveedor.
- Cuanto equivale esa unidad en unidad base del SKU ERP.

Ejemplos:

- SKU base en `kg`; proveedor vende `costal`; factor: `4`.
- SKU base en `pieza`; proveedor vende `caja`; factor: `10`.
- SKU base en `litro`; proveedor vende `garrafon`; factor: `20`.

Reglas:

- El factor debe ser configurable por relacion, no fijo por unidad.
- No asumir que todo `kg` usa el mismo factor.
- Factor debe ser mayor a cero.
- Si falta factor, Compras/Recepcion debe advertir antes de generar existencia.

## Impacto en Compras y Recepcion

Compras debe poder capturar:

- cantidad comprada en unidad proveedor;
- costo de proveedor;
- SKU proveedor;
- relacion al SKU ERP.

Recepcion/Almacen debe convertir:

- cantidad comprada x factor = cantidad en unidad base inventario.

Ejemplo:

- Compra: 5 costales.
- Factor: 4 kg por costal.
- Existencia esperada: 20 kg.

Si el factor no existe o es sospechoso, Recepcion no debe ingresar existencia definitiva sin advertencia.

## Variantes internas

Si un proveedor maneja un SKU general pero el negocio vende variantes internas:

- Proveedores/listas debe permitir relacionar el mismo SKU proveedor a varias variantes ERP cuando aplique.
- Compras debe advertir si la variante no viene definida.
- Recepcion debe permitir clasificar o distribuir cantidades entre variantes.
- Inventario final siempre debe quedar por SKU ERP interno real.

## Incidencias que Proveedores/listas debe devolver a Catalogo

- `proveedor_sku_sin_match`: no existe SKU ERP confiable.
- `proveedor_sku_ambiguo`: hay varios SKUs ERP candidatos.
- `proveedor_variante_no_identificada`: proveedor no distingue variante.
- `proveedor_unidad_factor_pendiente`: falta unidad o factor de compra.
- `proveedor_unidad_factor_conflicto`: unidad/factor contradice Catalogo o recepcion esperada.
- `proveedor_fiscal_sugerido`: lista/XML trae fiscal que puede completar Catalogo.

Cada incidencia debe incluir:

- proveedor;
- lista o fuente;
- SKU proveedor;
- descripcion proveedor;
- costo si existe y el perfil puede verlo;
- evidencia del problema;
- accion sugerida.

## Criterio de cierre para Proveedores/listas

Un SKU queda comprable cuando:

- SKU ERP existe y esta vigente;
- proveedor esta relacionado;
- SKU proveedor esta capturado si aplica;
- unidad de compra esta definida;
- factor compra -> unidad base es mayor a cero;
- costo/lista tiene fuente y fecha;
- variante esta resuelta si aplica;
- no hay incidencia critica abierta que impida compra/recepcion.

