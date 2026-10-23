# ERP Produccion - Plan robusto para productos fabricados

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: Plan maestro inicial para ordenar fabricacion interna antes de uso real  
Relacionados:

- `docs/erp_plan_maestro_fundamentos.md`
- `docs/erp_produccion_materia_prima_arranque.md`
- `docs/erp_catalogo_venta_granel.md`
- `docs/erp_almacen_recepciones_tareas_vivas.md`
- `docs/erp_inventario_existencias_arranque.md`

## Objetivo

Diseñar un flujo ERP robusto para un negocio que no solo revende productos, sino que tambien fabrica, empaca o transforma productos propios.

Casos reales del negocio:

- Heno de alfalfa comprado como materia prima y empacado en presentaciones propias.
- Sustratos para reptiles comprados por presentacion/proveedor en litros, pero controlados y empacados por peso.
- Peceras fabricadas internamente con vidrio cortado, silicon, accesorios, mano de obra y posibles cargos de corte.

## Decision operativa: peceras y pedidos de vidrio

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-03  
Proposito: definir donde debe vivir el flujo para disenar una pecera, calcular piezas de vidrio y generar pedido al proveedor sin contaminar Compras ni Catalogo.  
Impacto: Produccion, Catalogo, Proveedores, Compras, Almacen, Inventario y Costos.

Decision:

- El flujo de peceras debe vivir en un modulo separado de `Produccion / Fabricacion`, no dentro de Compras como captura libre.
- Compras solo debe recibir el resultado formal: solicitud u orden de compra al proveedor de vidrio con piezas, medidas, cantidades, costos esperados y adjuntos.
- Catalogo debe conservar la identidad de materiales y productos: vidrio, silicon, perfiles, accesorios y peceras terminadas vendibles.
- Proveedores debe conservar la relacion con el proveedor de vidrio, unidad de compra, precio por m2, cargos de corte, espesores disponibles, minimos, condiciones y tiempos.
- Produccion debe calcular la lista de piezas necesarias segun medidas de la pecera, guardar la receta/proyecto y generar el requerimiento de compra.

Primera version recomendada:

- Crear una pantalla `Produccion > Peceras` o `Fabricacion > Peceras`.
- Capturar medidas generales: largo, ancho/fondo, alto, espesor de vidrio, cantidad de peceras y observaciones.
- Permitir definir el tipo de armado: base interior/exterior, tapa abierta/cerrada, refuerzos, divisiones u otras variantes futuras.
- Generar automaticamente piezas sugeridas: frente, fondo, laterales, base y refuerzos si aplica.
- Permitir editar cada pieza antes de mandar a pedir: nombre, largo, ancho, espesor, cantidad, canto/pulido, observaciones para proveedor.
- Calcular area por pieza y area total en m2 para estimar costo.
- Adjuntar o imprimir una hoja de cortes para proveedor.
- Generar una solicitud de compra hacia Compras con las piezas como partidas o como detalle tecnico adjunto, segun el nivel que soporte Compras en la fase inicial.

Regla importante:

- Una pecera personalizada o fabricada no debe nacer como compra directa de producto terminado si el negocio la fabrica internamente.
- La compra al proveedor es de vidrio cortado, materia prima, componentes o servicio de corte.
- La pecera terminada debe nacer despues por una orden de produccion o fabricacion, consumiendo vidrio/insumos y sumando costos.

Fases sugeridas:

1. Calculadora operativa de cortes sin afectar inventario.
2. Generacion de solicitud/orden de compra para proveedor de vidrio.
3. Recepcion de vidrio cortado en Almacen.
4. Orden de fabricacion que consume piezas, silicon y accesorios.
5. Alta de pecera terminada como producto vendible o pedido especial.

Pendientes de negocio antes de implementar:

- Confirmar si el proveedor cobra por m2, por pieza, por corte, por espesor o una combinacion.
- Confirmar si las medidas capturadas son exteriores o interiores.
- Confirmar regla de descuentos por espesor del vidrio y holguras por silicon.
- Confirmar si el vidrio cortado se quiere inventariar por pieza individual o solo como material consumido para una fabricacion especifica.
- Confirmar si las peceras se fabrican bajo pedido, para stock, o ambas.

## Decision operativa: peceras, terrarios y tortugueros fabricados internamente

Documentacion IA: Codex GPT-5  
Fecha: 2026-10-11  
Proposito: evitar que productos fabricados internamente contaminen Compras, cuentas por pagar o el catalogo vendible.  
Impacto: Produccion, Catalogo, Compras, Almacen, Inventario, Costos y Rentabilidad.

Decision:

- Las peceras, terrarios y tortugueros fabricados por el negocio no deben nacer como orden de compra de producto terminado.
- Compras solo debe usarse para adquirir materiales, componentes, insumos, servicios o cargos reales de terceros.
- El producto terminado fabricado debe ingresar a inventario por una orden de produccion/fabricacion, no por recepcion de compra.
- No se debe crear un proveedor ficticio para "pagarse a uno mismo" ni generar saldos pendientes por productos fabricados internamente.
- La fabricacion puede consumir materiales previamente comprados: vidrio, silicon, tapas, malla, perfiles, accesorios, empaques, etiquetas u otros insumos.
- Si aun no existe el modulo completo de Produccion, la alternativa temporal debe ser una entrada interna controlada de producto terminado con motivo claro, por ejemplo `fabricacion interna temporal`, sin cuenta por pagar y sin afectar Compras.

Regla principal:

> Compras compra lo externo. Produccion transforma. Almacen recibe o confirma fisicamente. Inventario registra existencias. Costos calcula el costo. Ventas vende solo productos autorizados por canal.

### Como evitar contaminar el catalogo

El catalogo ERP debe funcionar como maestro de articulos del negocio, pero no todos los articulos deben ser vendibles.

No se recomienda crear un catalogo separado aislado para materia prima, porque despues Compras, Almacen, Inventario, Produccion y Costos necesitaran referenciar los mismos SKUs. Lo correcto es un catalogo unico con clasificacion operativa clara.

Clasificacion recomendada:

- `producto_terminado`: vendible si esta autorizado por canal. Ejemplo: pecera 60x30x30 terminada.
- `materia_prima`: comprable e inventariable, no vendible normal. Ejemplo: vidrio 6 mm por m2.
- `componente`: pieza fisica consumible o integrable. Ejemplo: tapa, perfil, rampa, vidrio frontal cortado.
- `insumo`: consumible de produccion/empaque. Ejemplo: silicon, etiqueta, bolsa, caja.
- `servicio`: no genera stock, puede afectar costo. Ejemplo: corte de vidrio, pulido, maquila.
- `gasto`: no genera stock y normalmente no se capitaliza al producto, salvo politica contable posterior.

Banderas minimas deseadas por SKU/articulo:

- comprable;
- inventariable;
- vendible POS;
- vendible ecommerce;
- consumible en produccion;
- producible como resultado;
- requiere lote/caducidad, si aplica;
- unidad base;
- unidad de compra;
- unidad de produccion;
- controla costo;
- requiere receta/lista de materiales.

Con esto, materia prima e insumos viven en el ERP sin aparecer como productos normales de venta.

### Flujo robusto para fabricar productos propios

1. Catalogo define el producto terminado y los materiales/insumos.
2. Proveedores define relaciones, costos y listas para materiales o servicios externos.
3. Compras adquiere materiales, componentes, insumos o servicios reales.
4. Almacen recibe materiales fisicos y confirma cantidades reales.
5. Produccion crea una orden de fabricacion.
6. Produccion consume materiales/insumos.
7. Produccion genera entrada de producto terminado.
8. Inventario registra salida de materiales y entrada de producto terminado.
9. Costos calcula costo del producto fabricado.
10. Ventas/POS/Ecommerce venden solo productos terminados autorizados.

### Peceras, terrarios y tortugueros

Producto terminado:

- pecera modelo estandar;
- terrario modelo estandar;
- tortuguero modelo estandar;
- proyecto personalizado bajo pedido, si aplica.

Materiales/insumos frecuentes:

- vidrio por espesor y unidad tecnica;
- silicon;
- tapa;
- malla;
- perfiles;
- rampas;
- accesorios;
- empaque;
- etiqueta;
- servicio de corte o pulido.

Primera version recomendada:

- Mantener la calculadora de peceras como herramienta de Produccion.
- Persistir proyectos/perfiles de peceras antes de conectarlos a Compras.
- Permitir generar solicitud de compra solo para vidrio, materiales o servicios, no para la pecera terminada.
- Crear orden de produccion simple para fabricar y dar entrada al producto terminado.
- Permitir fabricacion para stock y fabricacion bajo pedido.

### Insumos y materia prima

No se recomienda crear un modulo aislado llamado solo "Insumos" separado del ERP. Lo recomendable es:

- Catalogo: define el articulo como materia prima, componente o insumo.
- Compras: lo compra cuando viene de proveedor externo.
- Almacen: lo recibe y controla fisicamente.
- Inventario: muestra su existencia y kardex.
- Produccion: lo consume.
- Costos: lo transfiere al producto terminado si corresponde.

El modulo/pantalla de "Insumos y materia prima" puede existir como vista filtrada de Catalogo/Inventario, pero usando los mismos IDs/SKUs maestros del ERP.

### Estados sugeridos de una orden de produccion

- `borrador`: se esta preparando receta/cantidad.
- `planeada`: lista para producir, sin consumir inventario.
- `en_proceso`: materiales reservados o consumo iniciado.
- `terminada`: producto terminado ingresado.
- `cancelada`: no se ejecuta.
- `cerrada`: revisada por costos/administracion, sin cambios operativos.

### Movimiento de inventario esperado

Al terminar produccion:

- salida: materia prima, componentes e insumos consumidos;
- entrada: producto terminado;
- referencia: folio de produccion;
- costo: acumulado desde materiales, insumos, merma, servicio y mano de obra si se decide controlar.

### Puente temporal permitido

Mientras Produccion completa no exista, se permite una entrada interna de producto terminado solo si:

- no genera proveedor;
- no genera cuenta por pagar;
- no se registra como compra;
- queda auditada con motivo `fabricacion interna temporal`;
- se captura observacion clara;
- idealmente se adjuntan notas/fotos/soporte;
- se documenta que el costo puede ser estimado y debera ajustarse cuando exista Costos/Produccion formal.

Este puente no debe usarse para materia prima comprada. La materia prima comprada debe entrar por Compras/Recepcion.

### Handoff / continuidad - Peceras

Fecha: 2026-08-03

- Contexto actual: ya existe MVP operativo en `Produccion > Peceras y vidrio`.
- Cambios recientes: se creo controlador `Produccion`, vista `apps/erp/produccion/peceras.php`, JS `public/assets/js/custom/apps/erp/produccion/peceras.js`, ruta protegida en `Core.php` y entrada de sidebar.
- Actualizacion 2026-08-04: la calculadora ya agrega refuerzos superiores opcionales, tapa de vidrio en piezas divididas e importacion de paquetes JSON exportados.
- Actualizacion 2026-08-04 adicional: se agrego descuento de corte por defecto de 2 mm por medida generada, porque la maquina del proveedor puede variar aproximadamente +/- 2 mm y el negocio prefiere pedir cada pieza 2 mm menor.
- Actualizacion 2026-08-04 adicional: `filo_muerto` queda como acabado equivalente a vidrio entregado tal como sale del corte, segun definicion operativa del dueno.
- Actualizacion 2026-08-04 adicional: se agregaron vistas locales `Perfiles de peceras` y `Pedido multiple vidrio` para editar perfiles guardados, combinar varios perfiles en un pedido y estimar hojas por espesor usando area total mas merma.
- Actualizacion 2026-08-04 adicional: `Pedido multiple vidrio` ya permite elegir tipo de acomodo: `Filas con rotacion` o `Solo area + merma`.
- Actualizacion 2026-08-04 adicional: el acomodo por filas ya muestra una vista visual proporcional por hoja con piezas posicionadas.
- Actualizacion 2026-08-04 adicional: se ajusto el acomodo default a `Bandas corte ancho`, porque el proveedor alinea piezas hacia lo largo para que cada linea de corte cruce recta el lado angosto de la hoja.
- Actualizacion 2026-08-04 adicional: el pedido multiple detecta tipos de hoja por espesor y permite ajustar largo, ancho, merma y separacion por cada espesor detectado.
- Actualizacion 2026-08-06: el pedido multiple permite agregar piezas extra manuales o duplicar piezas calculadas como extra para aprovechar sobrantes de hoja; entran al mismo acomodo visual por espesor.
- Actualizacion 2026-08-06: el acomodo visual muestra desperdicio y aprovechamiento por hoja; el pedido multiple puede guardarse con desperdicio promedio para comparar pedidos historicos y cargar el mejor acomodo.
- Actualizacion 2026-10-22: se preparo persistencia en BD para perfiles, piezas y pedidos multiples; la UI dejo de leer/escribir `localStorage` y conserva JSON solo como importacion/exportacion tecnica.
- Funciona hoy: captura medidas, calcula piezas de vidrio, permite editar cortes, calcula m2/costo estimado, imprime hoja de pedido, copia texto, importa/exporta JSON, exporta CSV, consulta perfiles persistentes y arma pedidos multiples con estimacion por hojas.
- Limitacion actual: el acomodo por filas es una heuristica first-fit con rotacion; no hace optimizacion CAD/CAM ni nesting industrial exacto.
- Decision: no crea solicitud de compra real todavia porque Compras exige partidas fisicas con SKU ERP y relacion proveedor-SKU activa.
- Contrato con Compras: usar el boton `Texto para solicitud` o exportar CSV/JSON como paquete tecnico mientras no exista SKU/servicio formal de vidrio/corte configurado en Catalogo/Proveedores.
- Pendientes: definir SKU materia prima/servicio para vidrio, proveedor de vidrio, unidad/costo por m2 o corte, permisos `produccion.ver`/`produccion.operar`, aplicar el esquema en BD con respaldo y generar la solicitud formal.
- Preview generado: `docs/erp_produccion_pedidos_vidrio_preview_2026-10-22.md` y JSON tecnico asociado; contiene 66 bloques de hoja de pedidos candidatos, sin escribir en la base productiva.
- Comparacion read-only generada en `docs/erp_produccion_coincidencias_historicas_2026-10-22.md`: 62 bloques relacionan con un maestro por alias, 22 coinciden exactamente con la receta importada y 44 requieren revision por variantes o pedidos mixtos; 4 quedan sin relacion automatica.
- Agrupacion read-only generada en `docs/erp_produccion_variantes_por_perfil_2026-10-22.md`: 9 perfiles tienen bloques de un solo perfil listos para comparar; 22 bloques son mixtos y deben conservarse separados antes de proponer una receta completa.
- Recetas candidatas read-only generadas en `docs/erp_produccion_recetas_candidatas_2026-10-23.md`: se comparan las variantes más frecuentes contra los perfiles actuales y se marcan diferencias de espesor o medida sin modificar la receta vigente.
- Revision accionable actualizada en `docs/erp_produccion_revision_recetas_2026-10-23.md`: quedan 0 perfiles pendientes; las 5 diferencias detectadas fueron confirmadas y corregidas con los espesores oficiales del dueno.
- Correccion confirmada por el dueno aplicada el 2026-10-23: `PERA-4025=3 mm`, `PERA-6030=4 mm`, `PERA-8050=6 mm`, `PERA-C3050=3 mm` y `PERA-P5030=4 mm`, en el perfil y en todas sus piezas. Respaldo puntual: `C:\xampp\panel_db_backups\produccion_espesores_perfiles_20261023.json`.
- Siguiente paso recomendado: revisar el preview del historial de pedidos, confirmar que hojas se consideran pedidos validos y autorizar por separado la persistencia/importacion historica.

### Analisis del historial `Pedidos Peceras.xlsx`

Fecha: 2026-10-22

El archivo entregado se considera fuente historica, no una especificacion ejecutable. La lectura inicial encontro 35 hojas:

- 31 hojas con nombre `Pedido...`, tratadas como candidatas a pedidos historicos.
- `Piezas` y `Hoja 4`, tratadas como hojas maestras o de referencia porque contienen catalogos y ejemplos de piezas.
- `Copia de Pedido 16`, tratada como posible duplicado y no como pedido nuevo automatico.
- `Precios`, sin datos utiles para la importacion inicial.

Hallazgos de negocio y calidad de datos:

- Hay pedidos mixtos con vidrio de 3, 4 y 6 mm en la misma hoja; el importador debe separar la planeacion por espesor y por medida fisica de hoja.
- Se observan hojas de 3 mm y 4 mm de 2.6 x 1.8 m, hojas de 6 mm de 3.6 x 2.6 m y casos de 1.8 x 2.6 m. La orientacion se conserva como dato operativo, aunque el area fisica sea equivalente.
- Hay al menos 136 firmas distintas de pieza considerando codigo, espesor, largo, ancho y tipo. Esto confirma que un codigo historico puede tener variantes dimensionales y no debe fusionarse solo por texto.
- Los codigos aparecen con diferencias de mayusculas, prefijos y escritura (`PEC`, `Pec`, variantes `PERA`/`TORO`) y existen tipos como `Lateral`, `Latera`, `Laterales` y `Frontal/Respaldo`. El importador conservara el valor crudo y agregara una normalizacion para busqueda.
- Se detectaron formulas `#REF!` en `Pedido 2-3mm`, `Pedido 5` y `Pedido 25`. Los totales de esas hojas no deben tomarse de las formulas; se deben reconstruir con las filas de piezas y marcarse para revision.

Decision de importacion:

- Primero generar preview con hoja origen, bloque de hoja, espesor, dimensiones, piezas, codigo crudo, tipo crudo, medidas y formulas problematicas.
- Crear perfiles candidatos agrupando por codigo normalizado, espesor, piezas y medidas exactas; no sobrescribir ni fusionar variantes automaticamente.
- Guardar el pedido historico como evidencia independiente del perfil, para que el futuro planeador pueda comparar desperdicio, hojas usadas y acomodo por pedido.
- Permitir clasificar cada hoja como `pedido`, `maestra`, `duplicado` o `revisar` antes de insertar registros persistentes.
- No confiar en `Piezas` ni `Hoja 4` para cantidades de pedidos; se usaran como apoyo para proponer perfiles faltantes.

Impacto en el modulo:

- El importador debe alimentar `erp_produccion_peceras` y sus piezas, y conservar el pedido completo en `erp_produccion_pedidos_vidrio` con sus items.
- La planeacion futura debe recalcular el acomodo visual por espesor y medida de hoja a partir de las piezas, no copiar ciegamente el porcentaje historico.
- La calculadora propone medidas historicas de hoja por espesor: 260 x 180 cm para 3/4 mm y 360 x 260 cm para 6 mm; cada grupo sigue siendo editable antes de guardar el pedido.
- Para 6 mm la calculadora prueba automaticamente ambas hojas historicas (`260 x 180` y `360 x 260`) con las piezas expandidas y el acomodo seleccionado; recomienda la de menor desperdicio geométrico y usa menos hojas como desempate, mostrando ambas alternativas en el detalle visual.
- Objetivo operativo confirmado por el dueno: usar el historial real de pedidos como referencia para comparar configuraciones de hojas, piezas, orientacion, separacion y desperdicio; generar una vista por hoja que pueda contrastarse durante la llamada con el proveedor y acercarse progresivamente al resultado de su maquina.
- La calibracion historica debe conservar diferencias entre peceras base `PERA` y tortugueros `TORO`, y debe respetar la receta real de cada geometria.
- Correccion aplicada: `C` se interpreta como cubo. Los perfiles `PERA-C...` fueron restaurados a sus piezas historicas; donde existen, se conservan laterales, frontales/respaldo y piso con sus cantidades originales. No se deben convertir automaticamente en hexagonales.
- Las `variantes_historicas` actualmente son firmas por pieza (tipo, medida y espesor), no siempre recetas completas de una pecera. Se muestran para comparacion y no sustituyen automaticamente la receta activa; para habilitar una seleccion segura se debe agrupar primero cada pedido historico completo por hoja y perfil.
- El analisis del historial confirma que el volumen o los m2 de una hoja no predicen por si solos el resultado: hay piezas largas, piezas de anchos distintos, mezclas de espesores y hojas de 2.6 x 1.8 m y 3.6 x 2.6 m. El planeador debe expandir cada cantidad historica a piezas individuales, acomodarlas por bandas y calcular desperdicio sobre la hoja realmente ocupada.
- La comparacion futura tendra tres metricas: area teorica de piezas contra area de hojas, desperdicio geométrico del acomodo visual y diferencia de configuracion contra el pedido historico. El porcentaje de area sera informativo, pero la decision de mejor acomodo dependera de si las piezas caben en bandas rectas con separacion y orientacion validas.
- Las diferencias de 2 mm se conservaran como regla de calculo del pedido actual; no se aplicaran retroactivamente a medidas historicas sin una decision del usuario.

Revision inicial de identificadores:

- Se detectaron 55 codigos de pecera normalizados en las hojas de pedido.
- El campo `codigo` del perfil sera el identificador maestro corregido por el dueno; el codigo que aparezca en el Excel se conservara como dato historico durante la importacion.
- El codigo se muestra en Perfiles, se puede buscar y aparece al elegir perfiles para un pedido multiple.
- El texto tecnico al proveedor seguira mostrando medidas, espesor, cantidades, piezas y acomodo; el codigo solo funcionara como referencia interna.
- Lista inicial para correccion manual: `Pec-10030`, `Pec-10040`, `Pec-10050`, `Pec-12240`, `Pec-12250`, `Pec-1515`, `Pec-2020`, `Pec-3020`, `Pec-3040`, `Pec-3050`, `Pec-4025`, `Pec-4040`, `Pec-4060`, `Pec-5030`, `Pec-5050`, `Pec-5060`, `Pec-6040`, `Pec-604035`, `Pec-6050`, `Pec-6060`, `Pec-8035`, `Pec-8050`, `Pec-B10025`, `Pec-C3030`, `Pec-C4040`, `Pec-C4050`, `Pec-C4060`, `Pec-p5030`, `Pec-p5050`, `Pec-p5060`, `Pec-T3020`, `Pec-T6030`, `PERA-B4020D`, `PERA-B4020L`, `PERA-C2020L`, `PERA-C2020P`, `PERA-C2040L`, `PERA-C2040P`, `PERA-C5040`, `PERA-CT2012L`, `PERA-CT2012P`, `PERA-H3850`, `TORO-2040CFR`, `TORO-2040CL`, `TORO-2040CP`, `TORO-20HL`, `TORO-20HP`, `TORO-4020CFR`, `TORO-4020CL`, `TORO-4020CP`, `TORO-C4040`, `TORO-CA2040FR`, `TORO-CA2040L`, `TORO-CA2040P`, `TORO-H4040`.

Codigo maestro recibido del dueno:

- Se recibio la lista original de peceras con prefijo `PERA`, separada de los tortugueros `TORO` que se revisaran despues.
- Relaciones de alta confianza por correspondencia directa: `Pec-1515` -> `PERA-1515`; `Pec-2020` -> `PERA-2020`; `Pec-3020` -> `PERA-3020`; `Pec-3040` -> `PERA-3040`; `Pec-4025` -> `PERA-4025`; `Pec-4040` -> `PERA-4040`; `Pec-C3030` -> `PERA-C3030`; `Pec-C4040` -> `PERA-C4040`; `Pec-C4050` -> `PERA-C4050`; `Pec-C4060` -> `PERA-C4060`; `Pec-5050` -> `PERA-5050`; `Pec-5060` -> `PERA-5060`; `Pec-6050` -> `PERA-6050`; `Pec-6060` -> `PERA-6060`; `Pec-8035` -> `PERA-8035`; `Pec-8050` -> `PERA-8050`; `Pec-10030` -> `PERA-10030`; `Pec-10050` -> `PERA-10050`; `Pec-p5030` -> `PERA-P5030`; `Pec-p5050` -> `PERA-P5050`; `Pec-p5060` -> `PERA-P5060`; `PERA-C5040` -> `PERA-C5040`; `PERA-H3850` -> `PERA-H3850`.
- Relaciones confirmadas adicionales: `PERA-B4020D/L` -> `PERA-B4020`; `PERA-C2020L/P` -> `PERA-C2222`; `PERA-C2040L/P` -> `PERA-C2240`; `PERA-CT2012L/P` -> `PERA-C2212`; `Pec-604035` -> `PERA-6035`.
- Relaciones confirmadas por el dueno: `Pec-3050` -> `PERA-C3050`; `Pec-4060` -> `PERA-C4060`; `Pec-12240` -> `PERA-12040`; `Pec-12250` -> `PERA-12050`; `Pec-B10025` -> `PERA-B10025`; `Pec-T3020` -> `TORO-3020`; `Pec-6040` -> `PERA-6040`.
- Relacion confirmada adicional: `Pec-T6030` -> `TORO-6030`.
- Los codigos originales que aun no aparezcan en el historial se conservaran como perfiles maestros sin consumo historico hasta que exista un pedido que los identifique.

Catalogo maestro de tortugueros recibido:

- `TORO-2020`, `TORO-3020`, `TORO-C2212`, `TORO-C2222`, `TORO-C3020`, `TORO-C3030`, `TORO-4025`, `TORO-5030`, `TORO-C4020`, `TORO-C5040`, `TORO-6030`, `TORO-6040`, `TORO-604035`, `TORO-8035`, `TORO-8050`, `TORO-10030`, `TORO-10030CONTOR`, `TORO-10030ELEV`, `TORO-1004040`, `TORO-H3820`, `TORO-H3840`.
- Regla operativa confirmada: los codigos historicos con prefijo o marcador `T` pueden pertenecer a tortugueros; no deben asignarse a peceras PERA sin validar medidas y tipo de producto.

Activacion e importacion autorizada:

- Fecha: 2026-10-22.
- Respaldo previo: `C:\xampp\panel_db_backups\productivo_artianicom_sys_panel_20261022_antes_produccion_peceras_perfiles.sql`.
- SHA-256 del respaldo: `7ED50BB929602C50EED79C8B82C010083FB937F38BD7192C7B20AB73E5DA7B4B`.
- Se activaron las tablas persistentes de Produccion/Peceras y Pedidos de vidrio, incluida la columna `codigo`.
- Se importaron 36 perfiles con piezas historicas; la verificacion confirmo 36 perfiles y 36 con piezas asociadas.
- Correccion posterior: los registros `TORO-3020` y `TORO-6030` no son perfiles de vidrio independientes; se archivaron como perfiles y quedaron relacionados con sus bases `PERA-3020` y `PERA-6030`. La verificacion actual confirma 35 perfiles base `PERA` activos y ningun `TORO` activo como perfil de vidrio.
- `PERA-6030` se creo a partir de las piezas historicas que estaban bajo `TORO-6030`.
- Los codigos derivados `TORO` no se importaran como perfiles operativos de vidrio; se usaran como productos terminados relacionados con una pecera base.
- Relaciones confirmadas por el dueno para completar perfiles faltantes: `PERA-C4020` usa las piezas historicas `TORO-4020CL`, `TORO-4020CFR` y `TORO-4020CP`; `PERA-1004040` usa `Pec-10040`; `PERA-H3820` usa `TORO-3820HL` y `TORO-3820HP`.
- Estos tres perfiles quedaron creados con sus piezas persistentes y los alias historicos en observaciones; `TORO` y `Pec` se conservan como referencias de origen, no como perfiles independientes de vidrio.
- No se importaran como perfiles operativos los codigos `PERA` sin historial suficiente; permanecen en el preview para completar medidas antes de usarlos.
- Payload de importacion conservado en `docs/erp_produccion_perfiles_import_payload_2026-10-22.json`.

## Diagnostico de fondo

El modulo de Compras no debe limitarse a comprar productos terminados vendibles.

En una empresa bien estructurada, Compras puede comprar:

- productos terminados para reventa;
- materia prima;
- componentes;
- insumos de empaque;
- herramientas o consumibles;
- servicios;
- cargos relacionados a una compra;
- gastos.

La diferencia no esta en si se compra o no; la diferencia esta en que efecto tiene cada partida:

- inventario de producto terminado;
- inventario de materia prima;
- inventario de componente;
- costo/cargo sin stock;
- gasto operativo;
- costo acumulable a produccion.

## Decision principal

El ERP debe evolucionar de "comprar productos de venta" a "comprar articulos y servicios del negocio".

Catalogo ERP no debe ser solo catalogo de venta. Debe convertirse en maestro de articulos/SKUs del ERP:

- SKU vendible;
- SKU comprable;
- SKU inventariable;
- SKU producible;
- SKU materia prima;
- SKU componente;
- SKU insumo de empaque;
- SKU servicio/cargo/gasto.

La venta al cliente se controla por canales y banderas de venta, no por existencia o ausencia en Catalogo.

## Tipos recomendados de SKU

### Producto terminado

Producto listo para vender.

Ejemplos:

- bolsa de heno de 500 g;
- sustrato empacado de 1 kg;
- pecera 60 x 40 x 40 cm.

Reglas:

- vendible: si;
- inventariable: si;
- producible: normalmente si;
- comprable: solo si el proveedor lo entrega ya listo para vender.

### Materia prima

Material principal que se transforma.

Ejemplos:

- heno de alfalfa materia prima;
- peatmoss materia prima;
- fibra de coco materia prima;
- vidrio por m2.

Reglas:

- comprable: si;
- inventariable: si;
- vendible tienda/ecommerce: no;
- producible: no como resultado final, pero si consumible por produccion;
- puede requerir recepcion variable.

### Componente

Parte que se usa dentro de un producto fabricado.

Ejemplos:

- vidrio cortado frontal;
- vidrio lateral;
- tapa;
- perfil;
- accesorio incluido.

Reglas:

- comprable o producible;
- inventariable si se controla fisicamente;
- consumible por produccion;
- no vendible salvo que se autorice como refaccion.

### Insumo de empaque

Material usado para empacar producto terminado.

Ejemplos:

- bolsa;
- etiqueta;
- caja;
- sello;
- empaque protector.

Reglas:

- comprable: si;
- inventariable: recomendable si impacta costo o disponibilidad;
- consumible por produccion/empaque;
- no vendible normal.

### Servicio/cargo

No crea stock fisico, pero puede afectar costo.

Ejemplos:

- corte de vidrio;
- flete;
- maquila externa;
- mano de obra externa;
- cargo especial del proveedor.

Reglas:

- comprable: si;
- inventariable: no;
- puede capitalizarse al costo de una recepcion/produccion;
- puede ser gasto si no se asigna a producto.

### Gasto

Compra que no entra a inventario ni costo de producto.

Ejemplos:

- papeleria;
- mantenimiento general;
- renta;
- servicios administrativos.

Reglas:

- comprable: si;
- inventariable: no;
- afecta finanzas/gastos, no inventario.

## Flujo objetivo

### 1. Catalogo ERP

Catalogo define el articulo/SKU y sus reglas:

- tipo de SKU;
- unidad base;
- comprable;
- vendible;
- producible;
- inventariable;
- consumible en produccion;
- canales permitidos;
- si requiere lote/caducidad/serie/etiqueta;
- si requiere recepcion variable;
- si usa receta/lista de materiales.

Catalogo no crea stock ni costos reales por si solo.

### 2. Proveedores / Relaciones proveedor-SKU

Un proveedor puede vender:

- materia prima;
- componente;
- producto terminado;
- insumo;
- servicio/cargo.

La relacion proveedor-SKU debe poder decir:

- proveedor;
- SKU interno;
- SKU/prod proveedor;
- unidad en la que se compra;
- factor si es fijo;
- si la recepcion requiere captura real;
- costo esperado;
- minimo de compra;
- evidencia/lista vigente.

### 3. Compras

Compras registra la obligacion real con proveedor.

Una orden puede contener:

- partidas inventariables;
- partidas de materia prima;
- componentes;
- insumos;
- cargos o servicios;
- gastos.

Compras no debe decidir el producto terminado fabricado. Compra lo que se pidio.

Ejemplo heno:

- Compra: 5 unidades fisicas esperadas de heno materia prima.
- Costo: por kg o por unidad segun proveedor.
- Recepcion captura peso real.

Ejemplo vidrio:

- Compra: vidrio o piezas cortadas.
- Costo base: por m2.
- Cargo: corte de vidrio.
- Adjuntos: plano, medidas o lista de cortes.

### 4. Almacen / Recepcion

Recepcion confirma lo fisico:

- productos terminados comprados;
- materia prima;
- componentes;
- insumos.

No crea stock para servicios, cargos o gastos.

Si aplica recepcion variable:

- captura unidades fisicas;
- captura cantidad real en unidad base;
- genera existencia real.

### 5. Inventario

Inventario muestra y controla:

- materia prima disponible;
- componentes disponibles;
- insumos disponibles;
- producto terminado disponible;
- kardex;
- lotes;
- unidades fisicas;
- etiquetas.

Inventario no produce, no compra y no vende.

### 6. Produccion / Empaque

Modulo dueño de transformar materiales en producto terminado.

Debe registrar:

- folio de produccion;
- producto terminado a producir;
- cantidad a producir;
- materia prima consumida;
- componentes consumidos;
- insumos de empaque consumidos;
- merma;
- observaciones;
- responsable;
- entrada de producto terminado;
- etiquetas del producto terminado.

Movimientos:

- salida de materia prima;
- salida de componentes/insumos;
- entrada de producto terminado;
- todos ligados al mismo folio.

### 7. Costos

Costos calcula el costo del producto terminado.

Primera version:

- costo materia prima consumida;
- costo componentes;
- costo empaque;
- cargos asignados;
- merma.

Version posterior:

- mano de obra;
- indirectos;
- energia;
- depreciacion;
- costo estandar vs costo real.

### 8. Ventas / POS / Ecommerce

Ventas solo vende lo que Catalogo autorice como vendible por canal.

Reglas:

- materia prima no se vende en tienda normal;
- componentes no se venden salvo refaccion autorizada;
- producto terminado si se vende;
- mayoreo puede tener SKUs o canal separado;
- ecommerce solo publica producto terminado estable.

## Casos del negocio

### Heno de alfalfa

Recomendacion:

- `ALIHA-MP`: materia prima, kg, recepcion variable, no vendible.
- `ALIHA-500G`: producto terminado, pza, vendible.
- `ALIHA-1KG`: producto terminado, pza, vendible.
- `ALIHA-PACA-MAYOREO`: opcional, canal mayoreo/especial, no tienda normal.

Flujo:

- Compra materia prima.
- Recepcion pesa real.
- Produccion empaca.
- Ventas vende bolsas.

### Sustratos

Recomendacion:

- Materia prima por tipo: peatmoss, fibra de coco, mezcla, etc.
- Unidad base interna: `kg`, si se empaca y vende por peso.
- Presentacion proveedor en litros queda como dato operativo/proveedor, no unidad principal de inventario si el negocio controla por peso.

Flujo:

- Compra presentacion proveedor.
- Recepcion pesa real.
- Produccion empaca por gramos/kg.
- Ventas vende producto empacado.

### Peceras

Recomendacion inicial:

- `VIDRIO-6MM-MP`: materia prima/componente en `m2`.
- `CORTE-VIDRIO`: servicio/cargo de compra, no inventariable.
- `SILICON-MP` o `SILICON-INS`: insumo.
- `PECERA-60X40X40`: producto terminado.

Si el proveedor ya entrega piezas cortadas:

- Opcion inicial robusta:
  - comprar vidrio por `m2`;
  - registrar cargo de corte;
  - adjuntar/observar medidas;
  - recibir inventario de vidrio/componente en m2;
  - produccion consume m2 segun receta.

- Opcion avanzada:
  - registrar piezas cortadas como componentes/unidades fisicas con medidas;
  - largo, alto, espesor, m2 calculado;
  - cada pieza puede rastrearse hasta una pecera fabricada.

Recomendacion para empezar:

- No iniciar con despiece pieza por pieza si todavia no es indispensable.
- Empezar con m2 + cargo de corte + receta por modelo de pecera.
- Evolucionar a piezas individuales cuando el control de merma/corte lo justifique.

## Orden recomendado de implementacion

### Fase 0 - Diagnostico y frontera

Objetivo:

- Saber que ya existe y que falta sin tocar BD.

Tareas:

- Auditar Catalogo: tipos actuales de SKU y reglas.
- Auditar Compras: tipos de partida actuales.
- Auditar Recepcion: que partidas generan stock y cuales no.
- Auditar Preparacion/Empaque actual.
- Auditar Costos: costo promedio, cargos y costo real.

Cierre:

- Documento de brechas por modulo.

### Fase 1 - Catalogo maestro de articulos

Objetivo:

- Que el ERP pueda distinguir materia prima, producto terminado, componente, insumo, servicio y gasto.

Tareas:

- Definir tipos de SKU.
- Definir banderas:
  - comprable;
  - vendible;
  - producible;
  - consumible_produccion;
  - inventariable;
  - canal tienda/ecommerce/mayoreo.
- Proponer DDL si falta estructura.
- No migrar masivamente sin plan.

Cierre:

- Catalogo puede clasificar articulos sin mezclarlos con ecommerce.

### Fase 2 - Compras ampliado

Objetivo:

- Que Compras pueda comprar todo lo que la empresa necesita sin forzar que todo sea producto terminado.

Tareas:

- Permitir partidas:
  - inventariable;
  - servicio/cargo;
  - gasto;
  - materia prima;
  - componente;
  - insumo.
- Definir que partidas van a Recepcion.
- Definir que cargos afectan costo de recepcion o produccion.
- Validar XML/CFDI para conceptos no inventariables.

Cierre:

- OC puede mezclar materiales y cargos sin crear stock incorrecto.

### Fase 3 - Recepcion de materia prima/componentes

Objetivo:

- Que Almacen reciba correctamente materiales comprados.

Tareas:

- Usar recepcion variable cuando aplique.
- No recibir servicios/cargos como stock.
- Generar existencias de materia prima/componentes.
- Etiquetar unidades fisicas si aplica.

Cierre:

- Materia prima entra a inventario real.

### Fase 4 - Produccion / Empaque V1

Objetivo:

- Transformar materia prima en producto terminado con kardex.

Tareas:

- Crear orden/preparacion de produccion.
- Seleccionar SKU terminado.
- Consumir materia prima.
- Consumir insumos opcionales.
- Capturar merma.
- Generar entrada de producto terminado.
- Generar etiquetas.

Cierre:

- Producto terminado nace por produccion, no por compra ficticia.

### Fase 5 - Costeo V1

Objetivo:

- Transferir costo real de materiales al producto terminado.

Tareas:

- Calcular costo de materia prima consumida.
- Sumar insumos.
- Sumar cargos asignados.
- Calcular costo por unidad terminada.
- Documentar merma.

Cierre:

- Producto terminado tiene costo razonable para ventas/rentabilidad.

### Fase 6 - Ventas por canal

Objetivo:

- Vender solo productos autorizados.

Tareas:

- POS ve producto terminado.
- Ecommerce ve producto terminado publicable.
- Mayoreo puede ver SKUs especiales.
- Materia prima queda oculta de venta normal.

Cierre:

- El negocio se presenta formalmente sin exponer materia prima como producto de tienda.

## Hallazgos esperados

Usar IDs:

- `PROD-H###` para Produccion.
- `CAT-PROD-H###` para Catalogo.
- `COM-PROD-H###` para Compras.
- `COST-PROD-H###` para Costos.

## Reglas de trabajo

- No aplicar migraciones sin respaldo externo y autorizacion.
- No mover inventario sin folio y UAT.
- No crear productos terminados desde Compras.
- No vender materia prima en canales normales.
- No usar Catalogo como lugar donde se crea stock.
- No mezclar ERP nuevo con legacy.
- Explicar decisiones de ERP robusto antes de implementarlas.

## Prompts sugeridos por modulo

### Catalogo

```text
Trabaja en ERP > Catalogo para preparar maestro de articulos para produccion/manufactura ligera.

Lee:
- AGENTS.md
- docs/erp_plan_maestro_fundamentos.md
- docs/erp_produccion_manufactura_ligera_plan.md
- docs/erp_produccion_materia_prima_arranque.md
- app/controladores/CatalogoErp.php
- app/modelos/CatalogoErpDatos.php
- app/modelos/CatalogoErpEsquema.php

Objetivo:
Auditar y proponer como distinguir materia prima, producto terminado, componente, insumo, servicio y gasto.

Reglas:
- No crear stock desde Catalogo.
- No publicar materia prima en ventas/ecommerce.
- No aplicar DDL sin respaldo y autorizacion.

Criterio:
Documento de brechas, propuesta de campos/DDL y orden de implementacion.
```

### Compras

```text
Trabaja en ERP > Compras para ampliar compras a materia prima, componentes, insumos, servicios, cargos y gastos.

Lee:
- AGENTS.md
- docs/erp_plan_maestro_fundamentos.md
- docs/erp_produccion_manufactura_ligera_plan.md
- docs/erp_compras_vision_operativa.md
- app/controladores/Compra.php
- app/modelos/OrdenesCompraErp.php
- app/modelos/ComprasEsquema.php

Objetivo:
Auditar si Compras puede comprar articulos no vendibles y cargos sin generar stock incorrecto.

Reglas:
- Compras registra obligacion con proveedor.
- Compras no produce producto terminado.
- Servicios/cargos no entran a inventario como stock.
- No aplicar DDL sin respaldo y autorizacion.

Criterio:
Flujo propuesto para OC con materia prima + cargo de corte/flete/servicio.
```

### Almacen / Recepcion

```text
Trabaja en ERP > Almacen > Recepciones para recibir materia prima/componentes.

Lee:
- AGENTS.md
- docs/erp_produccion_manufactura_ligera_plan.md
- docs/erp_almacen_recepciones_tareas_vivas.md
- app/controladores/Almacen.php
- app/modelos/Almacenes.php

Objetivo:
Validar recepcion de materia prima con cantidad real variable y recepcion de componentes/insumos sin tocar producto terminado.

Reglas:
- Recepcion crea existencia de lo fisico recibido.
- No recibe servicios/cargos como stock.
- No produce producto terminado.
- Respaldo antes de UAT con escrituras.
```

### Produccion / Empaque

```text
Trabaja en ERP > Produccion / Empaque para fabricar producto terminado desde materia prima.

Lee:
- AGENTS.md
- docs/erp_produccion_manufactura_ligera_plan.md
- docs/erp_produccion_materia_prima_arranque.md
- docs/erp_almacen_preparacion_empaque_diseno.md
- app/controladores/Almacen.php
- app/modelos/Almacenes.php
- app/modelos/InventarioErp.php

Objetivo:
Diseñar V1 de orden/preparacion de produccion: salida de materia prima + entrada de producto terminado + etiquetas + merma.

Reglas:
- No comprar producto terminado fabricado internamente.
- Todo debe dejar kardex.
- No tocar Ventas/ecommerce.
- No aplicar DDL sin respaldo y autorizacion.
```

### Costos

```text
Trabaja en ERP > Costos para costear producto fabricado.

Lee:
- AGENTS.md
- docs/erp_produccion_manufactura_ligera_plan.md
- docs/erp_costos_rentabilidad_arranque.md

Objetivo:
Diseñar costeo V1 para producto terminado: materia prima consumida + empaque + cargos asignados + merma.

Reglas:
- No inventar utilidad desde Inventario.
- No cambiar costos historicos sin respaldo.
- Separar costo real, costo promedio y precio de venta.
```

## Handoff / continuidad

### Integracion Produccion - Proyectos

Fecha: 2026-10-22

- El catalogo de tareas de Proyectos ya reconoce `modulo_relacionado=produccion`.
- Proyectos debe usarse para responsables, fechas, bloqueos, comentarios y notificaciones de hitos; Produccion conserva medidas, piezas, acomodo, hojas y desperdicio.
- Hitos sugeridos: validar ficha tecnica, revisar acomodo/desperdicio, confirmar extras, preparar pedido de vidrio, recibir/verificar cortes, ensamblar/curar e inspeccionar/liberar.
- No generar tareas automaticas desde el calculador mientras no exista una orden de fabricacion persistente con identificador enlazable; el perfil y el pedido de vidrio ya cuentan con contrato de persistencia preparado.
- Siguiente paso recomendado: diseñar la entidad persistente de orden de fabricacion y su relacion opcional con `erp_proyectos`/`erp_proyecto_tareas`, sin duplicar piezas ni estados tecnicos.

Fecha: 2026-06-27

- Contexto actual: el negocio fabrica/empaqueta algunos productos y necesita ERP formal para comprar materia prima, producir y vender producto terminado.
- Decision: Catalogo ERP debe evolucionar a maestro de articulos, no solo catalogo de venta.
- Primer modulo recomendado: Catalogo, porque todos los demas necesitan saber si un SKU es materia prima, producto terminado, componente, insumo, servicio o gasto.
- Segundo modulo recomendado: Compras ampliado, para poder comprar materiales y cargos sin simular producto terminado.
- Tercer modulo recomendado: Produccion/Empaque, apoyandose en lo ya hecho en Almacen.
- Riesgo principal: seguir comprando o recibiendo todo como si fuera producto vendible; eso distorsiona inventario, costos y ventas.

## Decision de fase temporal - Produccion diferida

Fecha: 2026-06-27  
Estado: Documentado para retomar despues

Contexto:

- El objetivo inmediato del negocio es controlar productos comprados/vendidos, estabilizar inventario y preparar venta en linea.
- Construir Produccion/Manufactura ligera completa ahora puede retrasar demasiado la salida operativa.
- El negocio necesita manejar algunos productos propios ya empacados como stock vendible antes de tener recetas, consumo de materia prima, merma y costeo formal.

Decision:

- Produccion/Manufactura ligera queda como fase posterior.
- En fase 1 se permite una solucion temporal controlada para producto terminado propio:
  - registrar stock de producto terminado ya empacado mediante entrada interna documentada;
  - usar un proveedor interno/controlado o tipo de origen equivalente, por ejemplo `Produccion interna`;
  - no mezclar esta entrada con compras reales a proveedores externos;
  - no simular que se compro de nuevo la materia prima;
  - dejar evidencia/observacion que explique que el producto terminado proviene de empaque/fabricacion interna temporal.

Objetivo de la solucion temporal:

- Tener existencias vendibles para POS/ecommerce.
- Evitar bloquear el avance por no tener aun Produccion formal.
- Mantener trazabilidad minima y evitar inventario ficticio sin origen.

Reglas temporales:

- Solo aplica a producto terminado propio ya empacado o fabricado fisicamente.
- No debe usarse para crear materia prima.
- No debe usarse para esconder faltantes de inventario.
- No debe generar cuenta por pagar real si no hay proveedor externo.
- El costo puede ser estimado/documentado hasta que exista Costos/Produccion formal.
- Usar folios, notas o referencias claras como `produccion interna temporal`, `entrada interna producto terminado` o equivalente.
- Cuando exista Produccion formal, estas entradas temporales deben quedar historicas; no se deben reescribir sin respaldo y autorizacion.

Frontera de fase 1:

- Catalogo: productos terminados propios pueden existir y ser vendibles.
- Inventario: puede tener stock de producto terminado propio por entrada interna controlada.
- Ventas/ecommerce: pueden vender productos terminados propios.
- Compras: sigue controlando proveedores externos y productos comprados; no debe forzarse todavia a manejar recetas de produccion.

Fase 2 futura:

- Crear modulo formal `Produccion / Empaque`.
- Registrar materia prima, componentes e insumos.
- Crear recetas/listas de materiales.
- Consumir materia prima y generar producto terminado.
- Calcular costo real del producto terminado.
- Registrar merma, etiquetas y trazabilidad completa.

Riesgo aceptado:

- Durante fase 1, el costo de producto terminado propio puede ser aproximado.
- La trazabilidad de materia prima hacia producto terminado sera limitada.
- Este riesgo se acepta para poder iniciar control de inventario y venta en linea mas rapido.

Criterio para retomar Produccion formal:

- Cuando Catalogo, Inventario, Recepciones, Ventas/POS y ecommerce base esten operando.
- Cuando el volumen de producto propio haga necesario costear materia prima, merma, empaque y mano de obra.
- Cuando el negocio quiera saber utilidad real por producto fabricado.
