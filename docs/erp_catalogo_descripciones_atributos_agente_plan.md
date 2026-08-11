# ERP Catalogo - Limpieza de descripciones, atributos sugeridos y base para agente

Fecha: 2026-08-08
Proyecto vigente: `C:\xampp\htdocs\panel_de_control`
Modulo: ERP > Catalogo
Estado: plan funcional, sin cambios de BD

## Contexto

El catalogo conserva descripciones heredadas de productos con HTML incrustado, estilos, saltos artificiales y en algunos casos caracteres danados por codificacion. Esas descripciones tienen valor operativo porque incluyen datos que pueden convertirse en atributos estructurados:

- caudal: `500 l/h`;
- consumo electrico: `5 w`;
- capacidad recomendada: `20-50 litros`;
- medidas: largo, ancho, alto;
- color, talla, material;
- especie o uso recomendado;
- contenido incluido;
- compatibilidad o restricciones.

La intencion no debe ser solo limpiar texto para que se vea bonito. El objetivo robusto es convertir informacion semiestructurada en datos utiles para catalogo, filtros, fichas comerciales, POS, ecommerce y un futuro agente asesor de clientes.

## Decision recomendada

Separar el trabajo en tres capas:

1. Limpieza textual segura.
2. Extraccion de atributos candidatos.
3. Curacion humana y aprendizaje operativo para agente.

No se recomienda que una IA escriba automaticamente atributos definitivos en el producto sin revision. La IA debe proponer candidatos con evidencia, confianza y fuente. Catalogo decide si acepta, corrige o descarta.

## Capa 1 - Limpieza textual segura

Objetivo:

- Quitar HTML innecesario.
- Conservar significado.
- Reparar saltos de linea utiles.
- Detectar caracteres danados.
- Generar una descripcion limpia editable.

Reglas:

- No borrar la descripcion original sin respaldo/versionado.
- No convertir automaticamente informacion tecnica en texto comercial exagerado.
- No inventar beneficios.
- Mantener unidades tal como aparezcan, normalizando formato cuando sea claro.
- Si hay codificacion danada, marcar incidencia antes de sobrescribir.

Campos sugeridos a futuro:

- `descripcion_original` o historial de descripcion;
- `descripcion_limpia`;
- `descripcion_comercial`;
- `descripcion_tecnica`;
- `descripcion_revision_estado`.

Si no se quiere modificar esquema todavia, se puede empezar con una cola de auditoria read-only que muestre original vs propuesta.

## Capa 2 - Extraccion de atributos candidatos

Objetivo:

- Leer la descripcion limpia/original.
- Detectar patrones estructurables.
- Proponer atributos para el producto o SKU.

Ejemplos de candidatos:

- `caudal`: 500 `l/h`;
- `consumo`: 5 `w`;
- `capacidad_acuario_min`: 20 `l`;
- `capacidad_acuario_max`: 50 `l`;
- `largo`: 35 `cm`;
- `ancho`: 27 `cm`;
- `alto`: 50 `cm`;
- `color`: rosa;
- `talla`: chica;
- `material`: poliester;
- `incluye`: bebedero 60 ml, rueda 13 cm, plataforma;
- `especie_recomendada`: perro, gato, hamster, conejo.

Cada atributo sugerido debe guardar:

- producto/SKU origen;
- nombre de atributo sugerido;
- valor sugerido;
- unidad sugerida;
- texto fuente exacto o fragmento fuente;
- confianza;
- tipo de regla que lo detecto;
- estado: pendiente, aceptado, corregido, descartado;
- usuario revisor;
- fecha revision.

## Capa 3 - Curacion y base para agente

Objetivo:

- Convertir experiencia operativa del negocio en conocimiento reutilizable.
- Alimentar un agente futuro sin depender solo de texto libre.

El agente futuro no debe aprender directamente de descripciones sucias. Debe apoyarse en:

- atributos confirmados;
- recomendaciones operativas confirmadas;
- compatibilidades confirmadas;
- preguntas frecuentes por categoria;
- reglas comerciales;
- advertencias de uso;
- equivalencias entre productos;
- experiencias reales documentadas por el equipo.

Ejemplo:

Producto filtro:

- Atributo confirmado: caudal 500 l/h.
- Atributo confirmado: consumo 5 w.
- Atributo confirmado: recomendado para 20-50 l.
- Conocimiento operativo: para acuarios con mucha carga biologica, sugerir rango menor.
- Pregunta frecuente: si sirve para pecera de 60 l.
- Respuesta sugerida: podria quedar justo; revisar cantidad de peces y tipo de filtracion.

## Flujo operativo propuesto

1. Auditoria de descripciones:
   - productos con HTML;
   - productos con estilos incrustados;
   - productos con caracteres danados;
   - productos con descripcion vacia;
   - productos con descripcion muy larga;
   - productos con posibles atributos detectables.

2. Vista de limpieza:
   - columna descripcion original;
   - columna descripcion limpia propuesta;
   - boton aceptar limpieza;
   - boton editar antes de aceptar;
   - boton descartar.

3. Vista de atributos sugeridos:
   - atributo;
   - valor;
   - unidad;
   - evidencia;
   - confianza;
   - accion: aceptar, corregir, descartar.

4. Aplicacion controlada:
   - la limpieza aceptada actualiza descripcion limpia/comercial;
   - los atributos aceptados se agregan al modelo de atributos del catalogo;
   - los descartes alimentan reglas para no repetir malas sugerencias.

5. Base de conocimiento:
   - lo confirmado pasa a una ficha de conocimiento por producto/categoria;
   - no se expone al cliente hasta tener revision humana.

## Tipos de atributos prioritarios por categoria

Filtros y bombas:

- caudal;
- consumo;
- altura maxima;
- capacidad de acuario recomendada;
- tipo de filtracion;
- repuesto compatible;
- uso interno/externo.

Acuarios y peceras:

- largo;
- ancho;
- alto;
- litros aproximados;
- material;
- incluye tapa;
- incluye luz;
- grosor vidrio.

Alimento:

- especie;
- etapa;
- presentacion;
- peso/contenido;
- tipo: hojuelas, pellet, granulo, extruido;
- beneficio principal.

Camas, collares, accesorios:

- talla;
- medidas;
- color;
- material;
- especie recomendada;
- peso/tamano recomendado.

Jaulas y habitats:

- medidas;
- especie recomendada;
- niveles;
- accesorios incluidos;
- material;
- color.

## Arquitectura recomendada

Primera fase sin migraciones:

- Crear reporte/auditoria en Catalogo para leer descripciones y proponer limpieza/atributos.
- Guardar resultados solo en memoria o documento de diagnostico.
- Validar con productos reales antes de disenar tablas.

Segunda fase con esquema autorizado:

- Tabla de cola de limpieza de descripciones.
- Tabla de atributos sugeridos por IA/reglas.
- Tabla de decisiones de revision.
- Tabla de conocimiento operativo por producto/categoria.

Tercera fase:

- Integrar con ecommerce/catalogos comerciales.
- Integrar con agente asesor.
- Permitir que preguntas/respuestas reales generen nuevas sugerencias de conocimiento.

## Riesgos

- Sobrescribir descripcion original y perder informacion.
- Aceptar atributos incorrectos por leer mal la descripcion.
- Confundir texto comercial con dato tecnico.
- Crear demasiados atributos sin catalogo controlado.
- Usar conocimiento no revisado para responder clientes.
- Propagar errores de codificacion a fichas nuevas.

## Criterios de calidad

- Ninguna descripcion original se pierde.
- Toda sugerencia tiene evidencia.
- Todo atributo aceptado tiene unidad cuando aplique.
- Las sugerencias de baja confianza quedan para revision manual.
- El agente solo usa datos confirmados o responde con cautela.
- Las decisiones del usuario mejoran futuras sugerencias.

## Decision 2026-08-08 - Primero unidades y atributos base

Antes de analizar descripciones con IA/reglas, Catalogo debe tener un vocabulario base de unidades y atributos. Esto evita que cada analisis cree nombres distintos para lo mismo.

Auditoria read-only relacionada:

- `docs/erp_catalogo_atributos_base_auditoria.md`
- `docs/erp_catalogo_categorias_sugeridas_auditoria.md`
- `storage/uat/uat_catalogo_atributos_base_readonly.php`
- `storage/uat/uat_catalogo_categorias_sugeridas_readonly.php`

Ejemplos de problemas que se quieren evitar:

- `litros/hora`, `l/h`, `L x hora` como tres atributos distintos;
- `watts`, `w`, `consumo` sin unidad consistente;
- `medida`, `tamano`, `largo x ancho x alto` sin separacion tecnica;
- `acuario recomendado`, `capacidad recomendada`, `litros recomendados` como campos duplicados;
- `color`, `colores`, `diseno` mezclados sin criterio.

Regla:

- Las unidades y atributos base son catalogos maestros controlados.
- La IA no crea atributos definitivos; propone contra el vocabulario base.
- Si detecta algo que no existe, lo manda como `atributo_nuevo_sugerido` para revision.
- Catalogo acepta, corrige o descarta.

## Unidades de medida para atributos

Estas unidades no describen necesariamente como se compra, vende o inventaria el SKU. Describen caracteristicas tecnicas del producto.

Ejemplo:

- Un filtro se vende por `pza`, pero su atributo `caudal` usa `l/h`.
- Una pecera se vende por `pza`, pero sus atributos `largo`, `ancho`, `alto` usan `cm`.
- Una cama se vende por `pza`, pero su atributo `diametro` usa `cm`.
- Un alimento puede venderse por presentacion, pero su atributo `contenido_neto` usa `g` o `kg`.

Regla:

- La unidad operativa del SKU pertenece a compra/venta/inventario.
- La unidad del atributo pertenece a la ficha tecnica y a busqueda/comparacion.
- La IA debe proponer valores contra unidades de atributo, no crear unidades operativas nuevas.

Masa/peso:

- `kg` - kilogramo;
- `g` - gramo;
- `mg` - miligramo.

Volumen/capacidad:

- `l` - litro;
- `ml` - mililitro.

Longitud/dimensiones:

- `m` - metro;
- `cm` - centimetro;
- `mm` - milimetro.

Electricidad/rendimiento:

- `w` - watt;
- `v` - volt;
- `l/h` - litros por hora;
- `gph` - galones por hora, solo si aparece en productos importados;
- `hz` - hertz.

Tiempo:

- `dia`;
- `mes`;
- `anio`.

Porcentaje:

- `%` - porcentaje.

Sin unidad:

- texto;
- lista;
- booleano;
- relacion a otro producto;
- rango compuesto.

Estas opciones sirven para atributos como color, material, tipo de filtracion, lavable, compatibilidad o repuesto compatible.

## Atributos base recomendados

Esta lista no busca cubrir todos los atributos posibles del mundo. Es una base inicial ajustada al tipo de productos que maneja el negocio segun el catalogo actual: acuario, peces, reptiles, aves, hamsters/cuyos/conejos, perros, gatos, camas, transportadoras, jaulas, areneros, alimentos, sustratos y decoracion.

### Atributos generales

- `color` - lista o texto corto;
- `talla` - lista o texto corto;
- `tamano` - lista controlada: mini, chico, mediano, grande, extra grande, etc.;
- `material` - texto/lista;
- `modelo` - texto;
- `serie` - texto;
- `compatibilidad` - texto;
- `incluye` - lista/texto;
- `contenido_neto` - numero + unidad;
- `pais_origen` - texto/lista;
- `uso_recomendado` - texto;
- `especie_recomendada` - lista;
- `etapa_vida` - lista: cachorro, adulto, senior, cria, etc.

### Dimensiones y capacidad fisica

- `largo` - numero + unidad;
- `ancho` - numero + unidad;
- `alto` - numero + unidad;
- `diametro` - numero + unidad;
- `circunferencia` - numero + unidad;
- `peso_producto` - numero + unidad;
- `peso_maximo_soportado` - numero + unidad;
- `capacidad_volumen` - numero + unidad;
- `grosor` - numero + unidad;
- `calibre` - texto o numero segun categoria;

### Acuario, filtracion y equipo

- `caudal` - numero + `l/h`;
- `consumo_electrico` - numero + `w`;
- `voltaje` - numero + `v`;
- `frecuencia` - numero + `hz`;
- `altura_maxima` - numero + unidad;
- `capacidad_acuario_min` - numero + `l`;
- `capacidad_acuario_max` - numero + `l`;
- `tipo_filtracion` - lista: interna, externa, cascada, canister, cabeza de poder, esponja;
- `tipo_agua` - lista: dulce, marina, ambas;
- `repuesto_compatible` - texto/relacion futura.
- `capacidad_recomendada` no debe quedar como texto generico si se puede separar en minimo y maximo.
- `litros_recomendados` no debe ser atributo independiente si significa lo mismo que rango de capacidad de acuario.

### Peceras, tortugueros y terrarios

- `litros_aproximados` - numero + `l`;
- `grosor_vidrio` - numero + `mm`;
- `calibre_malla` - texto/lista;
- `tipo_tapa` - lista/texto;
- `incluye_mueble` - booleano;
- `incluye_luz` - booleano;
- `incluye_filtro` - booleano;
- `tipo_malla` - lista/texto;
- `tipo_habitat` - lista: acuario, tortuguero, terrario, paludario.

### Alimento y consumibles

- `presentacion` - texto/lista;
- `peso_contenido` - numero + unidad;
- `tipo_alimento` - lista: hojuelas, pellet, granulo, extruido, congelado, liofilizado;
- `especie_objetivo` - lista;
- `beneficio_principal` - texto/lista;
- `sabor` - texto/lista;
- `tamano_grano` - texto/lista;
- `proteina` - numero + `%`, solo cuando el producto lo indique;
- `grasa` - numero + `%`, solo cuando el producto lo indique;
- `fibra` - numero + `%`, solo cuando el producto lo indique.

### Habitat, jaulas y accesorios

- `niveles` - numero;
- `puertas` - numero;
- `accesorios_incluidos` - lista/texto;
- `rueda_diametro` - numero + unidad;
- `bebedero_capacidad` - numero + unidad;
- `tipo_cierre` - texto/lista;
- `plegable` - booleano;

### Camas, ropa, correas y collares

- `talla` - lista;
- `cuello_min` - numero + unidad;
- `cuello_max` - numero + unidad;
- `pecho_min` - numero + unidad;
- `pecho_max` - numero + unidad;
- `largo_mascota` - numero + unidad;
- `relleno` - texto/lista;
- `lavable` - booleano;
- `impermeable` - booleano.

### Areneros, comederos, bebederos y dispensadores

- `capacidad` - numero + `ml`, `l`, `g` o `kg` segun aplique;
- `tipo_entrada` - lista/texto;
- `tapado` - booleano;
- `con_filtro_olor` - booleano;
- `antiderrame` - booleano;
- `automatico` - booleano.

### Decoracion, sustratos y grava

- `granulometria` - numero/texto + unidad;
- `color` - lista/texto;
- `material` - lista/texto;
- `tipo_sustrato` - lista: grava, arena, fibra, corteza, peat moss, coco, piedra;
- `volumen_contenido` - numero + `l`;
- `peso_contenido` - numero + `kg` o `g`.

## Atributos observados que requieren saneamiento

En el catalogo actual ya aparecen atributos utiles pero ambiguos o heredados:

- `Capacidad`;
- `Potencia`;
- `Grosor`;
- `Calibre`;
- `Cantidad`;
- `Peso`;
- `Peso maximo`;
- `Medida` / `Medidas`;
- `Contenido` y variantes con errores de escritura;
- `Longuitd` / `Longuitud`.

Decision:

- No deben borrarse sin auditoria.
- Deben mapearse a atributos canonicos cuando sea claro.
- Si un atributo heredado tiene valores en SKUs, se migra con tarea controlada.
- Si no tiene uso, se puede inactivar despues de validar.

Ejemplos de mapeo esperado:

- `Potencia` -> `consumo_electrico` si el valor esta en `w`;
- `Capacidad` -> `capacidad_volumen`, `capacidad_acuario_min/max` o `capacidad` segun categoria;
- `Grosor` -> `grosor` o `grosor_vidrio`;
- `Calibre` -> `calibre` o `calibre_malla`;
- `Medidas` -> `largo`, `ancho`, `alto` cuando el formato lo permita;
- `Peso maximo` -> `peso_maximo_soportado`;
- `Contenido` -> `contenido_neto`, `peso_contenido` o `volumen_contenido`.

## Flujo deseado en el modal de producto

La descripcion puede tener una accion `Analizar descripcion`.

Comportamiento esperado:

1. El usuario pega o conserva una descripcion.
2. Presiona `Analizar descripcion`.
3. El sistema muestra:
   - descripcion limpia sugerida;
   - atributos detectados que coinciden con el vocabulario base;
   - atributos nuevos sugeridos;
   - fragmento de evidencia;
   - nivel de confianza.
4. El usuario decide:
   - aceptar descripcion limpia;
   - editar descripcion limpia;
   - aceptar atributos seleccionados;
   - corregir atributos;
   - descartar sugerencias.

Reglas:

- No guardar cambios automaticamente al presionar analizar.
- No tocar atributos existentes sin confirmacion.
- Si una sugerencia puede ser de producto maestro o de SKU, mostrar advertencia.
- Si el dato afecta inventario, compras, precios, garantia o seguridad, no aplicarlo desde descripcion sin revision adicional.

## Plan por etapas

### Etapa 0 - Curar unidades y atributos base

- Auditar unidades existentes contra la lista base recomendada.
- Auditar atributos existentes contra la lista base recomendada.
- Detectar faltantes, duplicados, nombres ambiguos y atributos demasiado genericos.
- Definir atributos canonicos por categoria.
- Clasificar atributos en:
  - generales;
  - tecnicos;
  - variantes;
  - comerciales;
  - conocimiento operativo para agente.
- Definir unidad esperada por atributo cuando aplique.
- Definir si el atributo vive a nivel producto maestro o SKU.
- No crear ni modificar maestros sin revisar impacto y, si hay cambios de BD/datos, con respaldo/autorizacion.

Salida esperada de la etapa 0:

- matriz de atributos canonicos por familia de producto;
- atributos existentes que se conservan;
- atributos existentes que se renombran o migran;
- atributos nuevos propuestos;
- atributos que no deben usarse para IA/agente;
- reglas de unidad por atributo.

Avance documentado:

- `docs/erp_catalogo_atributos_base_auditoria.md`: auditoria base de atributos existentes y saneamiento propuesto.
- `docs/erp_catalogo_categorias_sugeridas_auditoria.md`: categorias sugeridas por tipo de producto.
- `docs/erp_catalogo_atributos_equipo_filtracion_auditoria.md`: primer extractor especifico para equipo electrico y filtracion.
- `storage/uat/uat_catalogo_atributos_equipo_filtracion_readonly.php`: script read-only para detectar candidatos como consumo electrico, caudal, altura maxima y capacidad recomendada.

### Etapa 1 - Auditoria de descripciones

- Contar productos con HTML.
- Contar productos con estilos `<span style>`, `<p>`, `<br>`, entidades HTML.
- Contar productos con caracteres danados.
- Detectar patrones tecnicos frecuentes.
- Seleccionar muestra de productos por categoria.

### Etapa 2 - Prototipo de limpieza

- Generar funcion de limpieza segura:
  - convertir HTML a texto con saltos legibles;
  - quitar estilos;
  - normalizar espacios;
  - preservar listas e incluidos.
- Mostrar original vs limpio.
- No guardar automaticamente.

### Etapa 3 - Prototipo de extraccion

- Reglas simples por expresiones:
  - medidas;
  - litros;
  - watts;
  - l/h;
  - cm;
  - ml;
  - color;
  - talla;
  - incluye.
- Generar candidatos con confianza.

### Etapa 4 - Curacion en Catalogo

- Crear vista de revision de descripciones.
- Crear vista de atributos sugeridos.
- Aceptar/corregir/descartar.
- Registrar decision.

### Etapa 5 - Base de agente

- Crear modelo de conocimiento operativo.
- Separar dato tecnico, recomendacion, advertencia, pregunta frecuente y experiencia.
- Hacer que el agente futuro consulte solo conocimiento aprobado.

## Proxima decision requerida

Definir si la primera implementacion sera:

- solo auditoria y reporte sin guardar nada;
- auditoria con cola persistente;
- limpieza directa con revision manual antes de guardar.

Recomendacion: empezar con auditoria y reporte sin guardar nada, luego avanzar a cola persistente.
