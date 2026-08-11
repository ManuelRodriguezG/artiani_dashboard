# ERP Catalogo - Auditoria read-only de categorias sugeridas

Fecha: 2026-08-08
Proyecto vigente: `C:\xampp\htdocs\panel_de_control`
Modulo: ERP > Catalogo
Estado: auditoria read-only, sin cambios de BD

## Objetivo

Generar una primera propuesta de categorias/familias reales para Catalogo ERP a partir de productos actuales, nombres y categorias existentes. La finalidad es ordenar el catalogo para trabajo operativo, fichas comerciales, ecommerce futuro y agente asesor.

## Script creado

- `storage/uat/uat_catalogo_categorias_sugeridas_readonly.php`

Contrato:

- Solo lectura.
- No modifica productos.
- No modifica categorias.
- No modifica relaciones producto-categoria.
- Genera resumen por categoria sugerida y muestras de productos.

Ejecucion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_categorias_sugeridas_readonly.php
```

## Resultado general

- Productos auditados: 1487.
- Productos sin categoria principal: 189.
- Productos con regla no concluyente: 484.

La cantidad alta de `Revision manual` indica que las reglas deben mejorar por familia antes de automatizar cualquier asignacion.

## Resumen de categorias sugeridas

| Categoria sugerida | Productos | Sin categoria principal |
| --- | ---: | ---: |
| Revision manual | 484 | 63 |
| Acuario / Peceras, tortugueros y terrarios | 151 | 26 |
| Roedores / Jaulas y habitats | 108 | 19 |
| Accesorios generales / Varios | 107 | 14 |
| Alimentacion / Comederos, bebederos y dispensadores | 68 | 1 |
| Acuario / Filtracion y oxigenacion | 67 | 2 |
| Aves / Alimento y suplementos | 62 | 26 |
| Perro / Alimento, premios y snacks | 62 | 1 |
| Perro / Camas, casas y descanso | 61 | 6 |
| Acuario / Iluminacion y calefaccion | 51 | 0 |
| Gato / Areneros e higiene | 48 | 1 |
| Transportadoras / Transportadoras y mochilas | 42 | 0 |
| Acuario / Decoracion y sustratos | 36 | 16 |
| Acuario / Alimentos para peces | 35 | 3 |
| Perro / Correas, collares y entrenamiento | 32 | 1 |
| Gato / Rascadores y juguetes | 26 | 0 |
| Perros y gatos / Higiene y salud | 24 | 0 |
| Reptiles / Alimentos y presas | 17 | 7 |
| Roedores / Alimento y sustratos | 5 | 2 |
| Aves / Jaulas y accesorios | 1 | 1 |

## Hallazgos importantes

### 1. La categoria sugerida no debe aplicarse automaticamente

Hay falsos positivos claros:

- `Mix artemia sin oxigeno` puede caer en `Filtracion y oxigenacion` por la palabra `oxigeno`, pero realmente puede ser alimento/insumo.
- Animales vivos como `Chinchilla macho`, `Erizo`, `Rata`, `Raton`, `Cuyo` no deben caer en `Jaulas y habitats`; requieren una familia separada.
- Algunos alimentos de roedores/reptiles/peces comparten palabras como `alimento`, `larva`, `grillo`, `artemia`, y la especie objetivo debe decidir la categoria.

Decision:

- Las sugerencias son bandeja de trabajo, no asignacion directa.
- Se debe generar una vista de revision por lotes con aceptar/corregir/descartar.

### 2. Falta categoria para animales vivos

El catalogo contiene productos/entidades vendibles como:

- Chinchilla;
- Erizo;
- Rata;
- Raton;
- Cuyo;
- Peces de agua dulce.

Decision recomendada:

- Crear familia/categoria separada:
  - `Animales vivos / Peces`;
  - `Animales vivos / Roedores y pequenos mamiferos`;
  - `Animales vivos / Reptiles`, si aplica.

Regla:

- No mezclar animales vivos con jaulas, alimentos o habitats.

### 3. Acuario debe ser una familia fuerte

El catalogo muestra mucho volumen en:

- peceras;
- tortugueros;
- terrarios;
- filtracion;
- oxigenacion;
- iluminacion;
- calefaccion;
- decoracion;
- sustratos;
- alimentos para peces.

Arbol sugerido:

- `Acuario`
  - `Peceras y acuarios`
  - `Peceras equipadas`
  - `Tortugueros`
  - `Terrarios y paludarios`
  - `Filtracion y oxigenacion`
  - `Iluminacion`
  - `Calefaccion y termometros`
  - `Decoracion`
  - `Sustratos y gravas`
  - `Alimentos para peces`
  - `Repuestos y accesorios tecnicos`

### 4. Reptiles requiere separar alimento vivo/seco de habitat

Arbol sugerido:

- `Reptiles`
  - `Alimentos y presas`
  - `Insectos deshidratados`
  - `Alimento vivo`
  - `Terrarios`
  - `Sustratos`
  - `Calefaccion e iluminacion`
  - `Accesorios`

### 5. Roedores y pequenos mamiferos necesita orden propio

Arbol sugerido:

- `Roedores y pequenos mamiferos`
  - `Hamster`
    - `Jaulas`
    - `Accesorios`
    - `Alimentos`
    - `Higiene`
  - `Cuyo`
    - `Alimentos`
    - `Accesorios`
  - `Conejo`
    - `Jaulas`
    - `Alimentos`
    - `Accesorios`
  - `Erizo`
    - `Alimentos`
    - `Higiene`
    - `Accesorios`
  - `Chinchilla`
    - `Alimentos`
    - `Accesorios`

Nota:

- Si se decide vender animales vivos desde el ERP, deben estar en `Animales vivos`, no en accesorios.

### 6. Perro y gato deben separarse aunque existan productos comunes

Arbol sugerido:

- `Perro`
  - `Alimento`
  - `Premios y snacks`
  - `Camas y descanso`
  - `Correas, collares y arneses`
  - `Juguetes`
  - `Higiene y salud`
  - `Transportadoras`

- `Gato`
  - `Alimento`
  - `Arenas y areneros`
  - `Rascadores`
  - `Juguetes`
  - `Camas y descanso`
  - `Higiene y salud`
  - `Transportadoras`

- `Perros y gatos`
  - `Comederos y bebederos`
  - `Dispensadores`
  - `Higiene compartida`
  - `Transportadoras compartidas`

Decision:

- Usar `Perros y gatos` solo cuando el producto realmente sea transversal.
- Si el producto esta disenado para perro o gato, asignarlo a su familia.

### 7. Alimentacion como familia transversal debe usarse con cuidado

Productos como comederos, bebederos y dispensadores no son alimento. Conviene usar:

- `Alimentacion / Comederos, bebederos y dispensadores`

Pero alimentos deben vivir preferentemente bajo la especie:

- `Perro / Alimento`;
- `Gato / Alimento`;
- `Acuario / Alimentos para peces`;
- `Reptiles / Alimentos y presas`;
- `Aves / Alimento y suplementos`;
- `Roedores / Alimentos`.

## Propuesta de arbol inicial

### Raices maestras

- `Acuario`
- `Reptiles`
- `Aves`
- `Roedores y pequenos mamiferos`
- `Perro`
- `Gato`
- `Perros y gatos`
- `Animales vivos`
- `Transportadoras`
- `Alimentacion`
- `Accesorios generales`

### Criterio de categoria principal

- La categoria principal debe responder: ¿donde buscaria este producto un operador o cliente?
- Las categorias secundarias pueden soportar navegacion alternativa.
- No usar categorias demasiado genericas como principal si existe una categoria especifica.

Ejemplo:

- `Arenero`: principal `Gato / Arenas y areneros`, no `Salud e higiene`.
- `Filtro interno`: principal `Acuario / Filtracion y oxigenacion`.
- `Cama perro`: principal `Perro / Camas y descanso`.
- `Tenebrio deshidratado`: principal segun uso real: `Reptiles / Alimentos y presas` o categoria transversal de alimento vivo/seco si aplica.

## Relacion con atributos y agente

Las categorias canonicas ayudan a decidir atributos esperados:

- Filtros: caudal, consumo electrico, altura maxima, capacidad recomendada.
- Peceras: largo, ancho, alto, litros aproximados, grosor vidrio.
- Alimentos: contenido neto, especie objetivo, tipo alimento, etapa.
- Camas: diametro/largo/ancho, talla, material, lavable.
- Areneros: largo, ancho, alto, tapado, filtro olor.
- Jaulas: largo, ancho, alto, niveles, puertas, accesorios incluidos.

Para el agente futuro, una buena categoria principal es tan importante como los atributos. La categoria define el contexto de recomendacion.

## Siguiente paso recomendado

1. Refinar reglas de sugerencia por familia.
2. Separar `Animales vivos`.
3. Generar bandeja de productos sin categoria principal con sugerencia.
4. Crear plan de categorias canonicas antes de aplicar cambios.
5. No crear ni reasignar categorias automaticamente sin revision.
