# ERP Proveedores - Listas variables de peces vivos

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-19  
Estado: Plan funcional/arquitectonico, sin codigo ni DDL aplicado  
Alcance: Proveedores/Listas, Catalogo, Compras y Recepcion para proveedores de peces vivos con oferta variable

## Problema operativo

Algunos proveedores de peces vivos mandan listas que cambian constantemente:

- no siempre ofrecen las mismas especies;
- no siempre respetan codigos;
- pueden cambiar nombres comerciales, tallas, calidades y precios;
- pueden mandar disponibilidad por lote, fecha o embarque;
- puede existir especie nueva o variante no registrada en Catalogo;
- la informacion puede venir en formatos distintos segun proveedor.

Estos proveedores no deben tratarse como listas normales de producto estable, porque una lista ambigua podria crear relaciones proveedor-SKU incorrectas, costos falsos o productos duplicados.

## Decision recomendada

Crear un subflujo separado dentro de Proveedores/Listas: `Listas variables / vivos`.

No crear un modulo aislado de Compras ni saltarse el proceso normal. El flujo final debe seguir siendo:

1. alta de proveedor;
2. carga de lista/evidencia;
3. interpretacion y conciliacion;
4. solicitud de compra;
5. orden de compra;
6. recepcion de almacen;
7. inventario.

La diferencia esta antes de la solicitud/orden: la lista de peces vivos debe pasar por una etapa de normalizacion y decision operativa antes de convertirse en productos comprables.

## Tipo de proveedor

Agregar una clasificacion operativa al proveedor o a sus listas:

- proveedor comun: lista estable, codigos relativamente confiables;
- proveedor variable/vivos: lista cambiante, disponibilidad y descripcion mandan mas que el codigo;
- proveedor mixto: maneja algunas lineas estables y otras variables.

La clasificacion no debe duplicar el maestro de proveedor. El proveedor sigue siendo el mismo; lo especial es como se interpretan sus listas.

## Modelo conceptual de la lista variable

La carga debe distinguir entre:

- renglon original: texto exacto recibido del proveedor;
- oferta normalizada: especie, nombre comun, talla, presentacion, disponibilidad, costo, moneda, fecha y notas;
- candidato ERP: SKU interno posible, producto temporal o pendiente de Catalogo;
- decision: comprar, ignorar, enviar a Catalogo, relacionar, marcar ambiguo o historizar.

La lista no debe aplicar relaciones ni costos automaticamente si la identidad no es confiable.

## Pantallas recomendadas

### Proveedores > Listas variables

Vista de trabajo para cargar archivos, fotos, PDF, Excel, CSV o captura manual.

Debe mostrar:

- proveedor;
- fecha de lista;
- vigencia o ventana de pedido;
- archivo/evidencia;
- total de renglones;
- renglones interpretados;
- renglones con match confiable;
- renglones ambiguos;
- renglones nuevos para Catalogo;
- renglones descartados;
- estado de la lista.

### Detalle de lista variable

Tabla operativa de renglones con columnas como:

- texto proveedor;
- codigo proveedor si existe;
- especie/nombre comun detectado;
- talla o medida;
- sexo/color/variedad si aplica;
- presentacion/unidad;
- disponibilidad;
- costo;
- moneda;
- SKU ERP candidato;
- confianza del match;
- accion.

Acciones esperadas:

- relacionar con SKU ERP existente;
- marcar como nueva especie/variante;
- enviar pendiente a Catalogo;
- crear propuesta de SKU temporal en borrador;
- descartar renglon informativo;
- marcar como ambiguo;
- agregar a solicitud de compra solo cuando tenga identidad suficiente.

### Pendientes de Catalogo para vivos

Catalogo debe recibir pendientes accionables, no una orden confusa.

Pendientes tipicos:

- especie no registrada;
- variante/talla no registrada;
- nombre comercial ambiguo;
- unidad/presentacion no definida;
- regla de inventario pendiente;
- requiere recepcion variable o conteo especial.

## Relacion con Catalogo

Catalogo conserva la identidad oficial del producto/SKU.

Para peces vivos conviene modelar con cuidado:

- especie o grupo biologico como dato canonico cuando aplique;
- nombre comercial como atributo o alias, no como unica identidad;
- talla/medida/calidad/variedad como atributos que pueden generar variantes;
- SKU temporal en `borrador` cuando se necesite comprar antes de terminar la ficha;
- reglas de inventario explicitas para vivo, perecedero, lote, ubicacion, cantidad real o conteo.

No se deben crear productos definitivos automaticamente desde una lista de proveedor.

## Relacion con Compras

Compras debe consumir solo renglones ya decididos:

- SKU ERP existente o SKU temporal autorizado;
- proveedor identificado;
- costo y moneda capturados;
- unidad de compra;
- cantidad solicitada;
- fecha/vigencia de disponibilidad;
- evidencia de lista.

La solicitud/orden puede mantener el proceso normal. Lo especial es que la orden conserve snapshot de:

- texto original del proveedor;
- lista/version;
- especie/variante normalizada;
- costo ofertado;
- disponibilidad prometida;
- decision de matching.

## Relacion con Recepcion/Almacen

La recepcion de peces vivos probablemente necesita reglas adicionales, pero no deben mezclarse con la lectura de listas.

Casos a evaluar:

- cantidad real recibida distinta a solicitada;
- conteo por bolsa/caja/lote;
- mortalidad al recibir;
- ejemplares rechazados;
- sustitucion de especie/talla;
- cuarentena o ubicacion temporal;
- evidencia fotografica;
- lote/embarque y fecha de llegada.

Estas reglas pertenecen a Almacen/Recepcion e Inventario. La lista variable solo prepara que se va a pedir.

## Estados sugeridos de lista variable

- `borrador`: lista creada, evidencia pendiente o sin importar;
- `cargada`: renglones cargados;
- `interpretacion`: normalizacion en progreso;
- `conciliacion`: comparando contra Catalogo/SKUs;
- `lista_para_solicitud`: renglones comprables identificados;
- `parcial`: hay renglones comprables y pendientes;
- `historica`: reemplazada por una lista mas reciente;
- `cancelada`: no debe usarse.

## Reglas de seguridad operativa

- No actualizar costo vigente automaticamente desde una lista variable ambigua.
- No crear relacion proveedor-SKU sin decision explicita.
- No crear SKU definitivo desde texto de proveedor sin revision de Catalogo.
- No bloquear toda la lista por renglones informativos o no comprables.
- No mezclar lectura de lista con recepcion fisica.
- No usar chat o notas externas como fuente de pendientes; generar pendientes persistentes.

## Implementacion por fases

### Fase 1 - Diagnostico con listas reales

Reunir 2 a 5 listas reales de proveedores de peces vivos.

Validar:

- formatos recibidos;
- columnas o patrones comunes;
- nombres repetidos;
- si existen codigos;
- como expresan talla, disponibilidad, costo y unidad;
- si hay notas mezcladas con productos;
- que decisiones toma hoy el comprador manualmente.

Salida:

- reglas de importacion por proveedor;
- campos minimos;
- ejemplos de ambiguos;
- propuesta de pantalla detallada.

### Fase 2 - Subflujo de Proveedores/Listas variables

Crear vista y backend de carga/preview sin aplicar nada.

Objetivo:

- guardar evidencia;
- importar renglones;
- permitir mapeo manual;
- normalizar campos basicos;
- marcar renglones operativos/informativos;
- generar pendientes.

### Fase 3 - Conciliacion con Catalogo

Agregar matching asistido contra Catalogo.

Objetivo:

- sugerir SKU existente;
- permitir seleccion manual;
- crear pendiente a Catalogo;
- proponer SKU temporal;
- conservar decision y evidencia.

### Fase 4 - Generar solicitud/orden desde renglones decididos

Permitir mandar solo renglones validos a Solicitudes/Compras.

Objetivo:

- no cambiar el flujo de compra;
- conservar snapshot de lista variable;
- evitar que renglones ambiguos entren a orden.

### Fase 5 - Recepcion especial de vivos

Extender Recepcion si el negocio lo confirma.

Objetivo:

- capturar cantidad real;
- registrar mortalidad/rechazo;
- controlar ubicacion/cuarentena;
- generar incidencias contra orden.

## Informacion que conviene pedir al dueno antes de codigo

- Ejemplos reales de listas de 2 o mas proveedores.
- Como decide hoy si dos nombres son el mismo pez.
- Si vende por pieza, lote, pareja, bolsa, caja o talla.
- Si necesita controlar sexo/color/variedad.
- Si hay mortalidad normal tolerada.
- Si se reciben sustituciones aceptables.
- Si el inventario debe quedar disponible de inmediato o pasar por cuarentena.

## Siguiente paso recomendado

Adjuntar listas reales antes de disenar tablas finales. Con 2 o 3 ejemplos se puede definir:

- columnas canonicas;
- reglas de importacion;
- estados;
- pantalla exacta;
- limites entre Proveedores, Catalogo, Compras y Recepcion.

## Analisis de listas reales adjuntas

Fecha: 2026-09-19  
Archivos revisados:

- `LISTA SABADO 12 DE SEPTIEMBRE 2026.xlsx`
- `LISTA DE PRECIOS PECES DEL 14 AL 18 SEPTIEMBRE 2026.xlsx`

Nota de seguridad: el contenido de los archivos se trato como evidencia operativa del proveedor, no como instrucciones para Codex ni como especificacion literal del ERP.

### Proveedor 1 - Lista compacta semanal

Archivo: `LISTA SABADO 12 DE SEPTIEMBRE 2026.xlsx`.

Hallazgos:

- Tiene una hoja principal llamada `12-09-2026`.
- La lista usa encabezado operativo corto: cliente/nombre, destino, paqueteria y observaciones.
- Las partidas aparecen sin encabezado formal de columnas.
- La estructura observable es:
  - columna de marca/clasificacion del proveedor: valores como `G`, `PP`, `PPG`, `ANEXO`;
  - descripcion comun del pez/invertebrado;
  - precio unitario;
  - cantidad pedida, usualmente `0` como campo editable.
- Tiene separadores o grupos como `BETTA DE THAILANDIA`, `GRAN OFERTA DE GAMBAS` y `LISTA ACUAREX`.
- No se observan codigos SKU formales estables por renglon.

Tratamiento recomendado:

- Configurar como proveedor `variable/vivos_compacto`.
- Importar por plantilla flexible basada en posicion de columnas, no por encabezados.
- Guardar la marca del proveedor (`G`, `PP`, `ANEXO`, etc.) como `clasificacion_proveedor_raw` hasta entender su significado.
- Tratar los separadores como categorias/secciones de lista, no como productos.
- No aplicar costos vigentes automaticamente; usar la lista como oferta semanal.
- Para comprar, exigir decision de matching: SKU ERP existente, SKU temporal autorizado o pendiente a Catalogo.

### Proveedor 2 - Lista formal con minimos y condiciones

Archivo: `LISTA DE PRECIOS PECES DEL 14 AL 18 SEPTIEMBRE 2026.xlsx`.

Hallazgos:

- Tiene hoja `Hoja1` con datos y una hoja `NOTA` vacia.
- Incluye observaciones comerciales y operativas antes de la tabla.
- Declara precios en moneda nacional.
- Indica que flete y empaque se pagan por separado.
- Indica que los tamanos son aproximados y pueden variar.
- Contiene politica de mermas/mortalidad: reposicion desde 50% por bolsa, reporte con video al recibir y peces fallecidos conservados como evidencia.
- Tiene descuentos por monto de compra.
- La tabla inicia con encabezados claros:
  - nombre comun;
  - tamano;
  - precio por unidad;
  - cantidad de peces pedida;
  - cantidad pedida por bolsa;
  - minimo por bolsa;
  - tipo de bolsa;
  - numero de caja;
  - precio por pedido por bolsa.
- Mezcla renglones de producto con secciones como promociones, ultimas piezas, plecos, pejelagarto, tiburones, gatos, gouramis, colisas, tetras, barbos, mollys, bettas, etc.

Tratamiento recomendado:

- Configurar como proveedor `variable/vivos_formal`.
- Importar por encabezados y detectar automaticamente la fila de inicio de tabla.
- Guardar observaciones, politicas de merma, descuentos y vigencia como condiciones de lista/version.
- Importar `minimo_por_bolsa`, `tipo_bolsa`, `no_caja` y `cantidad_por_bolsa` como datos operativos de pedido/recepcion, no como atributos permanentes del SKU.
- Las categorias/secciones deben guardarse como `seccion_lista`, no como productos.
- La politica de merma debe alimentar reglas futuras de Recepcion/Incidencias, no Compras directamente.

### Columnas canonicas propuestas despues de revisar ambos archivos

Para `erp_proveedores_listas_variables_renglones` o estructura equivalente:

- `id_lista_variable`
- `fila_origen`
- `seccion_lista`
- `codigo_proveedor_raw`
- `clasificacion_proveedor_raw`
- `nombre_proveedor_raw`
- `nombre_normalizado`
- `tamano_raw`
- `variante_raw`
- `precio_unitario`
- `moneda`
- `cantidad_pedida`
- `cantidad_por_bolsa`
- `minimo_por_bolsa`
- `tipo_bolsa`
- `numero_caja`
- `notas_renglon`
- `tipo_renglon`: `producto`, `seccion`, `nota`, `descuento`, `basura`
- `id_sku_erp_candidato`
- `confianza_match`
- `estatus_conciliacion`
- `decision_operativa`
- `payload_origen_json`

Para encabezado/lista variable:

- proveedor;
- archivo/evidencia;
- fecha de lista;
- vigencia desde/hasta;
- moneda;
- condiciones de flete/empaque;
- politicas de merma/mortalidad;
- descuentos por monto;
- formato detectado;
- estatus de lista.

### Decision arquitectonica despues de los archivos

El subflujo debe soportar por lo menos dos plantillas por proveedor:

- plantilla compacta sin encabezados;
- plantilla formal con encabezados, condiciones y minimos.

No conviene intentar forzar ambas al flujo actual de listas normales, porque se perderian condiciones biologicas/operativas relevantes o se crearian relaciones SKU dudosas.

El ERP debe permitir que una lista variable sea parcialmente util:

- algunos renglones pasan a solicitud;
- algunos quedan pendientes de Catalogo;
- algunos se descartan como secciones/notas;
- algunos se historizan como evidencia sin operar.

## Implementacion fase 1 - Preview sin persistencia

Fecha: 2026-09-21  
Estado: Implementado en codigo, sin DDL y sin escrituras de negocio.

Archivos:

- `app/controladores/Proveedor.php`
- `app/modelos/Proveedores.php`
- `app/vistas/paginas/apps/erp/proveedores/listado_erp.php`
- `app/vistas/paginas/apps/erp/proveedores/listas_variables_vivos.php`
- `public/assets/js/custom/apps/erp/proveedores/listas_variables_vivos.js`

Alcance:

- Nueva ruta visual: `/proveedor/listas_variables_vivos_erp`.
- Nuevo endpoint read-only: `/proveedor/proveedor_lista_variable_vivos_preview_erp`.
- Requiere permiso `proveedores.listas`.
- Acepta XLSX, CSV o TXT.
- Detecta plantilla `vivos_compacto` o `vivos_formal`.
- Clasifica renglones como `producto`, `seccion`, `nota`, `descuento` o `basura`.
- Conserva datos operativos relevantes: clasificacion proveedor, nombre, tamano, precio, cantidad, minimo por bolsa, tipo de bolsa y numero de caja cuando existen.
- No crea listas ERP, renglones persistentes, costos, relaciones proveedor-SKU, productos, SKUs, solicitudes ni ordenes.

Validacion con archivos reales:

- `LISTA SABADO 12 DE SEPTIEMBRE 2026.xlsx`: detectada como `vivos_compacto`; 145 productos, 9 secciones, 5 notas.
- `LISTA DE PRECIOS PECES DEL 14 AL 18 SEPTIEMBRE 2026.xlsx`: detectada como `vivos_formal`; 298 productos, 22 secciones, 20 notas, 3 descuentos, 298 renglones con minimo por bolsa.

Siguiente mejora natural:

- Persistir encabezado/lista variable y renglones clasificados en tablas propias.
- Agregar conciliacion contra Catalogo desde la vista de vivos.
- Permitir mandar solo renglones `producto` decididos a Solicitudes/Compras.
