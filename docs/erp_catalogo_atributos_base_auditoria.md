# ERP Catalogo - Auditoria read-only de atributos base

Fecha: 2026-08-08
Proyecto vigente: `C:\xampp\htdocs\panel_de_control`
Modulo: ERP > Catalogo
Estado: auditoria read-only, sin cambios de BD

## Objetivo

Auditar los atributos actuales del Catalogo ERP para preparar un vocabulario canonico de atributos y unidades de medida de atributos. Esto sirve como base antes de implementar limpieza de descripciones, sugerencias de atributos y futuro agente asesor.

## Script creado

- `storage/uat/uat_catalogo_atributos_base_readonly.php`

Contrato:

- Solo lectura.
- No modifica productos.
- No modifica SKUs.
- No modifica atributos.
- No modifica unidades.
- Imprime JSON con atributos, usos, muestras de valores y recomendacion de mapeo.

Ejecucion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_atributos_base_readonly.php
```

## Resultado general

- Atributos auditados: 31.
- Atributos canonicos dimensionales activos y fuertes:
  - `ATR-LARGO`: 686 SKUs;
  - `ATR-ALTO`: 610 SKUs;
  - `ATR-ANCHO`: 573 SKUs;
  - `ATR-DIAMETRO`: 133 SKUs.
- Atributos heredados inactivos sin uso:
  - Alto heredado ecommerce;
  - Ancho heredado ecommerce;
  - Diametro heredado ecommerce.
- Atributos heredados activos con mucho valor operativo:
  - `Medidas`: 890 SKUs;
  - `Contenido`: 341 SKUs;
  - `Capacidad`: 62 SKUs;
  - `Potencia`: 61 SKUs;
  - `Calibre`: 44 SKUs.

## Hallazgos principales

### 1. Medidas es el atributo mas importante a sanear

`Medidas` tiene 890 SKUs. Es un atributo heredado de texto que probablemente concentra valores que deberian dividirse en:

- largo;
- ancho;
- alto;
- diametro;
- grosor;
- medidas con mueble;
- medida unica segun producto.

Riesgo:

- No se puede migrar automaticamente en bloque porque algunas medidas son compuestas y otras son una sola dimension.

Decision recomendada:

- Mantener `Medidas` por ahora.
- Crear extractor read-only que detecte formatos como `30 x 20 x 40 cm`.
- Proponer candidatos a `largo`, `ancho`, `alto` solo cuando el patron sea claro.

### 2. Contenido necesita dividirse segun familia

`Contenido` tiene 341 SKUs y mezcla:

- gramos;
- kilogramos;
- litros;
- mililitros;
- piezas.

Tambien existen errores ortograficos activos:

- `Contendio`;
- `Contennido`;
- `Cotenido`;
- `Contenido pza`.

Decision recomendada:

- Definir canonicos:
  - `contenido_neto`;
  - `peso_contenido`;
  - `volumen_contenido`;
  - `contenido_piezas`.
- No migrar sin revisar categoria/familia.

### 3. Potencia debe mapearse a consumo electrico

`Potencia` tiene 61 SKUs y muestras como:

- `10 W`;
- `100 W`;
- `12 W`;
- `2.5 W`.

Decision recomendada:

- Crear o usar atributo canonico `consumo_electrico`.
- Unidad de atributo: `w`.
- Tipo: numero.
- Migrar solo valores claramente expresados en watts.

### 4. Subida puede ser altura maxima

`Subida` tiene 10 SKUs y muestras como:

- `1.2 m`;
- `2 m`;
- `4.5 m`.

Decision recomendada:

- Mapear a `altura_maxima` para bombas/filtros.
- Unidad esperada: `m` o `cm`.
- No aplicar a productos fuera de filtracion/bombeo.

### 5. Calibre y grosor deben separarse

`Calibre` tiene 44 SKUs con valores como:

- `3 mm`;
- `4 mm`;
- `6 mm`;
- `9 mm`.

`Grosor` tiene uso bajo pero es relevante para peceras/vidrio.

Decision recomendada:

- `calibre` queda como atributo tecnico/lista cuando representa calibre comercial.
- `grosor` o `grosor_vidrio` queda como numero + `mm`.
- En peceras/tortugueros, preferir `grosor_vidrio`.

### 6. Capacidad es ambigua

`Capacidad` tiene 62 SKUs y mezcla litros/mililitros.

Puede significar:

- capacidad de contenedor;
- capacidad de acuario;
- capacidad de bebedero/comedero;
- capacidad recomendada para un equipo.

Decision recomendada:

- No usar `capacidad` como destino directo para todo.
- Mapear por familia:
  - peceras: `litros_aproximados`;
  - filtros: `capacidad_acuario_min` / `capacidad_acuario_max`;
  - bebederos/dispensadores: `capacidad`;
  - recipientes: `capacidad_volumen`.

### 7. Peso y peso maximo no significan lo mismo

`Peso` tiene 18 SKUs y puede significar:

- peso fisico del producto;
- contenido de alimento;
- presentacion comercial.

`Peso maximo` tiene 19 SKUs y debe mapearse a:

- `peso_maximo_soportado`, unidad `kg`.

Decision recomendada:

- `Peso maximo` es candidato fuerte a canonico.
- `Peso` requiere categoria/familia antes de migrar.

## Matriz inicial de mapeo

| Atributo actual | Uso | Canonico sugerido | Unidad de atributo | Accion |
| --- | ---: | --- | --- | --- |
| Largo | 686 | `largo` | `cm` | conservar |
| Alto | 610 | `alto` | `cm` | conservar |
| Ancho | 573 | `ancho` | `cm` | conservar |
| Diametro | 133 | `diametro` | `cm` | conservar |
| Medidas | 890 | varios | `cm/mm` | extraer con reglas |
| Contenido | 341 | `contenido_neto` / `peso_contenido` / `volumen_contenido` | `g/kg/ml/l` | mapear por familia |
| Potencia | 61 | `consumo_electrico` | `w` | migrable si valor claro |
| Capacidad | 62 | varios | `l/ml` | mapear por familia |
| Calibre | 44 | `calibre` / `grosor_vidrio` | texto o `mm` | revisar por categoria |
| Peso | 18 | `peso_producto` / `peso_contenido` | `g/kg` | mapear por familia |
| Peso maximo | 19 | `peso_maximo_soportado` | `kg` | migrable si valor claro |
| Subida | 10 | `altura_maxima` | `m/cm` | aplicar a bombas/filtros |
| Color | 16 | `color` | sin unidad | conservar |
| Presentacion | 3 | `presentacion` | sin unidad | conservar; no confundir con presentaciones operativas |
| Diseno | 2 | `diseno` | sin unidad | conservar si se usa como variante |
| Absorcion | 2 | `absorcion` | revisar | revisar contexto |
| Longuitd | 1 | `largo` | `m/cm` | corregir/migrar |
| Longuitud | 2 | `largo` | `m/cm` | corregir/migrar |
| Contendio | 1 | `contenido_neto` | `g/kg/ml/l` | corregir/migrar |
| Contennido | 1 | `contenido_neto` | `g/kg/ml/l` | corregir/migrar |
| Cotenido | 1 | `contenido_neto` o `contenido_piezas` | variable | corregir/migrar |
| Atributo ecommerce | 8 | ninguno | ninguno | revisar valores antes de decidir |

## Saneamiento propuesto sin aplicar

Esta seccion define que se debe ordenar, pero no aplica cambios por si sola. Cualquier migracion de valores requiere script controlado, respaldo/autorizacion si modifica BD y validacion antes/despues.

### Grupo Medidas

Atributos involucrados:

- `Medidas` - 890 SKUs;
- `Medida` - 57 SKUs;
- `Medidas con mueble` - 1 SKU;
- `Longuitd` - 1 SKU;
- `Longuitud` - 2 SKUs;
- canonicos ya existentes: `Largo`, `Ancho`, `Alto`, `Diametro`.

Accion recomendada:

1. Mantener `Medidas` y `Medida` temporalmente.
2. Crear extractor read-only para detectar:
   - `L x A x H`;
   - `diametro`;
   - medidas unicas;
   - medidas con mueble.
3. Proponer valores a canonicos existentes cuando la confianza sea alta.
4. Corregir `Longuitd` y `Longuitud` hacia `Largo` solo si el valor representa longitud.
5. Inactivar atributos mal escritos solo cuando queden sin uso.

No recomendado:

- Fusionar `Medidas` completo a `Largo`, porque perderia informacion compuesta.
- Borrar atributos heredados fisicamente.

### Grupo Contenido

Atributos involucrados:

- `Contenido` - 341 SKUs;
- `Contendio` - 1 SKU;
- `Contennido` - 1 SKU;
- `Cotenido` - 1 SKU;
- `Contenido pza` - 1 SKU;
- `Cantidad` - 1 SKU con valor tipo `112 gr`.

Canonicos sugeridos:

- `contenido_neto`;
- `peso_contenido`;
- `volumen_contenido`;
- `contenido_piezas`.

Accion recomendada:

1. Corregir atributos mal escritos mediante migracion de valores, no solo renombrar.
2. Separar por unidad detectada:
   - `g`, `gr`, `kg` -> peso/contenido;
   - `ml`, `l`, `lts` -> volumen/contenido;
   - `pza`, `pzas`, `pz` -> piezas.
3. Usar categoria/familia para decidir destino final.

### Grupo Capacidad

Atributo involucrado:

- `Capacidad` - 62 SKUs.

Problema:

- Puede significar volumen del producto, volumen de contenedor, rango recomendado para acuario o capacidad de dispensador.

Accion recomendada:

1. No renombrar directamente.
2. Separar por familia:
   - filtros/equipo: `capacidad_acuario_min/max`;
   - peceras: `litros_aproximados`;
   - bebederos/comederos: `capacidad`;
   - contenedores: `capacidad_volumen`.

### Grupo Equipo electrico

Atributos involucrados:

- `Potencia` - 61 SKUs;
- `Subida` - 10 SKUs.

Canonicos sugeridos:

- `consumo_electrico` + `w`;
- `altura_maxima` + `m`;
- futuro: `caudal` + `l/h`.

Accion recomendada:

1. Crear o confirmar canonicos.
2. Migrar valores claros de `Potencia` a `consumo_electrico`.
3. Migrar valores claros de `Subida` a `altura_maxima`.
4. Extraer `caudal` desde descripciones/nombres cuando aparezca `l/h`.

### Grupo Vidrio/malla

Atributos involucrados:

- `Calibre` - 44 SKUs;
- `Grosor` - 1 SKU.

Canonicos sugeridos:

- `calibre`;
- `grosor`;
- `grosor_vidrio`;
- `calibre_malla`.

Accion recomendada:

1. En peceras/tortugueros, usar `grosor_vidrio` en `mm`.
2. En terrarios/malla, usar `calibre_malla` o `tipo_malla`.
3. No mezclar `calibre` con `grosor` sin revisar familia.

## Orden recomendado para saneamiento

1. Crear atributos canonicos faltantes en una propuesta, sin aplicar.
2. Hacer extractor read-only por grupo:
   - medidas;
   - contenido;
   - equipo electrico;
   - capacidad;
   - calibre/grosor.
3. Revisar muestras con el usuario.
4. Preparar migracion controlada por grupo.
5. Inactivar atributos mal escritos cuando no tengan uso.
6. Actualizar el boton futuro `Analizar descripcion` para que use solo canonicos.

Avance:

- Grupo `equipo electrico`: extractor preparado en `storage/uat/uat_catalogo_atributos_equipo_filtracion_readonly.php`.
- Documento especifico: `docs/erp_catalogo_atributos_equipo_filtracion_auditoria.md`.
- Estado: ejecutado el 2026-08-10.
- Resultado: 72 candidatos tecnicos; 61 sugerencias de `consumo_electrico` y 10 de `altura_maxima`.
- No se detecto `caudal` con evidencia fuerte; probablemente falta capturar ese dato desde fichas/descripciones/proveedores.

## Atributos canonicos prioritarios

### Basicos

- `color`
- `talla`
- `tamano`
- `material`
- `modelo`
- `diseno`
- `presentacion`
- `incluye`

### Dimensiones

- `largo` + `cm`
- `ancho` + `cm`
- `alto` + `cm`
- `diametro` + `cm`
- `grosor` + `mm`
- `grosor_vidrio` + `mm`
- `calibre`

### Contenido y peso

- `contenido_neto` + unidad variable controlada
- `peso_contenido` + `g/kg`
- `volumen_contenido` + `ml/l`
- `contenido_piezas`
- `peso_producto` + `g/kg`
- `peso_maximo_soportado` + `kg`

### Acuario/equipo

- `consumo_electrico` + `w`
- `caudal` + `l/h`
- `altura_maxima` + `m`
- `capacidad_acuario_min` + `l`
- `capacidad_acuario_max` + `l`
- `litros_aproximados` + `l`
- `tipo_filtracion`
- `tipo_agua`

## Reglas para el futuro boton Analizar descripcion

El analizador debe usar esta matriz como vocabulario inicial.

Reglas:

- Si detecta `W`, proponer `consumo_electrico`.
- Si detecta `l/h`, proponer `caudal`.
- Si detecta tres medidas con `x`, proponer `largo`, `ancho`, `alto`.
- Si detecta una sola medida en cama redonda, proponer `diametro`.
- Si detecta `peso maximo`, proponer `peso_maximo_soportado`.
- Si detecta `capacidad para acuario de 20 a 50 l`, proponer `capacidad_acuario_min=20` y `capacidad_acuario_max=50`.
- Si detecta `contenido 250 g`, proponer `contenido_neto` o `peso_contenido` segun familia.
- Si no hay atributo canonico claro, crear sugerencia `atributo_nuevo_sugerido` y no aplicarla automaticamente.

## Riesgos

- `Medidas` mezcla muchos formatos y requiere reglas cuidadosas.
- `Contenido` mezcla unidades y piezas.
- `Capacidad` es dependiente de categoria.
- Algunos productos no tienen categoria principal, lo que dificulta decidir mapeo.
- La tabla actual de atributos guarda `unidad` como texto, no como referencia fuerte a maestro de unidades de atributo.

## Siguiente paso recomendado

Crear una subtarea de auditoria read-only por familias:

1. Filtracion/equipo electrico.
2. Peceras/tortugueros/terrarios.
3. Alimentos/consumibles.
4. Jaulas/habitats/accesorios.
5. Camas/collares/transportadoras.
6. Areneros/comedores/bebederos/dispensadores.
7. Decoracion/sustratos/grava.

Salida esperada:

- atributos canonicos por familia;
- atributos heredados a mapear;
- reglas de extraccion desde descripcion;
- decision de si hace falta mejorar el CRUD de atributos antes de implementar sugerencias.
