# ERP Catalogo - Arbol propuesto de categorias

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-13  
Modulo: ERP > Catalogo > Categorias  
Proyecto vigente: `C:\xampp\htdocs\panel_de_control`  
Estado: propuesta de organizacion; no aplicada en BD

## Objetivo

Ordenar las categorias de Catalogo ERP antes de seguir cargando productos, para evitar que queden mezcladas categorias por especie, categorias por uso, categorias heredadas de ecommerce y categorias duplicadas.

La estructura propuesta busca que una persona pueda clasificar productos sin tener que adivinar si debe usar `Perro`, `Perros y gatos`, `Alimentacion`, `Mamiferos roedores` o una categoria heredada suelta.

## Auditoria actual

Auditoria read-only usada:

- `storage/uat/uat_catalogo_categorias_maestro_readonly.php`
- `storage/uat/uat_catalogo_categorias_arbol_readonly.php`
- `storage/uat/uat_catalogo_categorias_resumen_markdown_readonly.php`

Resultado actual:

- Total categorias: 246.
- Categorias maestras: 171.
- Categorias heredadas/ecommerce: 75.
- Raices actuales: 96.
- Texto danado: 0.
- Padres inexistentes: 0.
- Codigos duplicados: 0.
- Nombres duplicados bajo el mismo padre: 0.
- Rutas inconsistentes: 0.

Hallazgo principal:

- La base ya no tiene problema estructural tecnico fuerte; el problema ahora es de taxonomia operativa.
- Hay categorias por especie o familia: `Perro`, `Gato`, `Acuario`, `Reptiles`, `Aves`, `Mamiferos roedores`, `Conejo`, `Cuyo`, `Hamster`, `Erizo`, `Chinchilla`, `Tortuga`, `Iguana`, etc.
- Hay categorias por uso: `Alimentacion`, `Habitat y descanso`, `Salud e higiene`, `Transporte, paseo y entrenamiento`, `Juego y enriquecimiento`, `Repuestos y accesorios`.
- Hay categorias combinadas: `Perros y gatos`, `Mamiferos roedores`.
- Hay categorias heredadas de ecommerce como raices, aunque ya tienen equivalentes dentro del arbol maestro.

## Decision recomendada

El arbol maestro de categorias debe usar una sola logica principal:

1. Primero la familia o tipo de mascota.
2. Despues el grupo operativo del producto.
3. Despues la subfamilia especifica si aplica.

Ejemplo:

```text
Perros
  Alimentacion
    Alimentos
    Premios y snacks
    Comederos y bebederos
  Habitat y descanso
    Camas, casas y colchones
  Salud e higiene
  Juego y enriquecimiento
  Transporte, paseo y entrenamiento
```

No conviene tener al mismo nivel `Perro`, `Alimentacion`, `Salud e higiene` y `Transportadoras para perro`, porque eso mezcla ejes distintos y vuelve confuso el alta de productos.

## Regla de clasificacion

Cada producto debe tener:

- Una categoria principal operativa: la ruta donde naturalmente vive el producto.
- Categorias secundarias opcionales: solo para aparecer en mas de un catalogo, busqueda o canal comercial.

Ejemplo:

- Una transportadora para perro:
  - Principal: `Perros / Transporte, paseo y entrenamiento / Transportadoras`
  - Secundaria opcional: `General multi-especie / Transporte / Transportadoras`

- Una transportadora que sirve para perro, gato y conejo:
  - Principal: `General multi-especie / Transporte / Transportadoras`
  - Secundarias opcionales: `Perros / Transporte...`, `Gatos / Transporte...`, `Pequenos mamiferos / Conejo / Transporte...`

Esta regla aprovecha que Catalogo ya maneja relacion producto-categoria y evita duplicar productos.

## Arbol maestro propuesto

Nota 2026-08-14:

- La version refinada y limpia del arbol 1-6 quedo separada en `docs/erp_catalogo_categorias_arbol_1_6_refinado.md`.
- Usar ese documento como referencia principal para continuar el ordenamiento.
- Este documento conserva comentarios y contexto de trabajo previo.

### 1. Perros

```text
Perros
  Alimentacion
    Alimentos
    Premios y snacks
    Comederos y bebederos
    Contenedores
    Tazones
  Salud e higiene
    Higiene y limpieza
    Prevencion y cuidado
    Antipulgas y control externo
  Habitat y descanso
    Camas y colchones
    Casas
    Jaulas
    Corrales y vallas
  Juego y enriquecimiento (Cómo que tipo de productos para enriquecimiento)
    Juguetes
  Transporte, paseo y entrenamiento
    Transportadoras
    Mochilas transportadoras
    Paseo y sujecion
    Entrenamiento
  Accesorios generales (Cómo que tipo de productos)
```

Categorias actuales relacionadas:

- `Perro / Alimento para perros`
- `Perro / Camas, casas y colchones para perros`
- `Perro / Casas para perros`
- `Perro / Dispensadores de agua y comida`
- `Perro / Jaulas, corrales y vallas para perros`
- `Perro / Juguetes para perros`
- `Perro / Mochilas transportadoras`
- `Perro / Platos, tazones y contenedores de alimento`
- `Perro / Salud e higiene para perros`
- `Perro / Transportadoras para perros`
- `Perro / Accesorios, entrenamiento, sujetadores para perro`

### 2. Gatos

```text
Gatos
  Alimentacion
    Alimentos
    Premios y snacks
    Comederos y bebederos
    Contenedores
    Tazones
  Salud e higiene
    Higiene y limpieza
    Areneros
    Accesorios sanitarios
  Habitat y descanso (Crees que se pueda aplicar algo aqui como contencion o algo para que sea otra subcategoria para casa, jaulas y corrales?)
    Camas y colchones
    Casas
    Jaulas
    Corrales y vallas
  Juego y enriquecimiento
    Juguetes
    Rascadores
  Transporte, paseo y entrenamiento
    Transportadoras
    Mochilas transportadoras
    Paseo y sujecion
    Entrenamiento
  Accesorios generales (Cómo que tipo de productos)
```

Categorias actuales relacionadas:

- `Gato / Alimento para gatos`
- `Gato / Areneros`
- `Gato / Camas, casas y colchones para gatos`
- `Gato / Jaulas, vallas y casas para gatos`
- `Gato / Juguetes para gatos`
- `Gato / Mochilas transportadoras`
- `Gato / Platos, tazones y contenedores de alimento`
- `Gato / Rascaderos para gato`
- `Gato / Salud e higiene para gatos`
- `Gato / Transportadoras para gatos`
- `Gato / Accesorios, entrenamiento, sujetadores para gato`

### 3. Acuario y peces

Nombre recomendado: `Acuario y peces`.

```text
Acuario y peces
  Peceras (tanques), acuarios y muebles
    Peceras
    Acuarios
    Tortugueros
    Peceras equipadas
    Peceras para betta
    Bases y muebles
  Equipamiento tecnico
    Filtracion y oxigenacion
    Bombas y circulacion
    Calefaccion
    Iluminacion
  Decoracion y ambientacion
    Decoracion para peces
    Sustratos y gravas
    Plantas artificiales
    Plantas naturales
  Alimentacion
    Alimentos para peces
    Alimentos para betta
    Alimentos de acuario
    Alimentos para tortugas
    Alimentos de fondo
    Alimentos para ajolotes
  Animales vivos y plantas naturales
    Peces
    Plantas acuaticas
  Repuestos, aditamentos y accesorios
    Repuestos para peceras
    Accesorios generales
    Aditamentos
```

Categorias actuales relacionadas:

- `Acuario / Peceras`
- `Acuario / Peceras / Peceras equipadas`
- `Acuario / Peceras / Peceras para bettas (beteras)`
- `Acuario / Bases para peceras`
- `Acuario / Filtracion y oxigenacion`
- `Acuario / Bombas sumergibles`
- `Acuario / Calefaccion para peces`
- `Acuario / Iluminacion para peces`
- `Acuario / Decoracion para peces`
- `Acuario / Alimentos para peces`
- `Acuario / Alimentos para pez betta`
- `Acuario / Alimentos de acuario`
- `Acuario / Animales y plantas vivas / Peces`
- `Acuario / Animales y plantas vivas / Plantas acuaticas`
- `Acuario / Repuestos y aditamentos para peceras`

Nota:

- `Alimento para tortugas` y `Tortugueros` aparecen actualmente bajo `Acuario`, pero para operacion limpia conviene moverlos a `Reptiles y tortugas / Tortugas`.

### 4. Reptiles y tortugas

```text
Reptiles 
  Reptiles generales
    Alimentos para reptiles
    Alimentos vivos
    Calefaccion
    Decoracion y aditamentos
    Terrarios
      Madera
      Aluminio
  Tortugas
    Alimentos para tortugas
    Tortugueros
    Aditamentos y accesorios
  Iguanas
    Alimentos para iguana
    Terrarios
      Madera
      Aluminio
  Transporte
    Transportadoras
```

Categorias actuales relacionadas:

- `Reptiles / Alimentos para reptiles`
- `Reptiles / Alimentos vivos`
- `Reptiles / Calefaccion para reptiles`
- `Reptiles / Decoracion y aditamentos para reptiles`
- `Reptiles / Terrarios`
- `Reptiles / Alimentos para Iguana`
- `Tortuga / Alimento para tortugas`
- `Tortuga / Tortugueros`
- `Reptiles / Aditamentos y accesorios para tortugueros`
- `Iguana / Alimentos para Iguana`

Decision recomendada:

- Mantener `Tortugas` e `Iguanas` como subgrupos dentro de `Reptiles y tortugas`, no como raices separadas.

### 5. Aves

```text
Aves
  Alimentacion
    Alimentos
    Alimento vivo y deshidratado
      Alimento vivo
      Alimento deshidratado
    Premios y snacks
    Aditamentos y suplementos (No sé como categorizar pero hay suplementos para postura, canto asi como aditamentos multivitaminicos generales o especificos tambien para canto, postura, etc)
    Bebederos y aditamentos para colibri
    Bebederos y comederos
      Bebederos
      Comederos
  Salud e higiene (hay algunos productos para limpieza del habitat preventivos para acaros, etc, asi como un protectos para acaros, sustratos, etc, no se como involucrar estos otros productos)
  Habitat
    Jaulas
  Juego y enriquecimiento
    Juguetes
  Transporte
    Transportadoras
```

Categorias actuales relacionadas:

- `Aves / Alimentos y aditamentos para pajaros`
- `Aves / Alimento y alimentadores para colibri`
- `Aves / Jaulas para pajaros`
- `Aves / Juguetes para pajaros`
- `Aves / Transportadoras mascoteras de plastico`

### 6. Pequenos mamiferos

Nombre recomendado: `Pequenos mamiferos`.

No usar `Mamiferos roedores` como nombre principal porque incluye especies que operativamente el negocio maneja por separado y porque no todos los casos se perciben comercialmente como roedores.

```text
Pequenos mamiferos
  Hamster
    Alimentacion
      Alimentos
      Premios y snacks
      Desgaste dental
      Alimento vivo y deshidratado
        Alimento vivo
        Alimento deshidratado
    Habitat y jaulas
    Accesorios y aditamentos
    Premios y desgaste dental
    Transporte
    Salud e higiene
      Sustratos
  Conejo
    Alimentacion
      Alimentos
      Premios y snacks
      Desgaste dental
    Habitat y jaulas
    Camas, casas y colchones
    Salud e higiene
      Sustratos
    Premios y desgaste dental
    Transporte
  Cuyo
    Alimentacion
      Alimentos
      Premios y snacks
      Desgaste dental
    Habitat y jaulas
    Salud e higiene
      Sustratos
    Premios y desgaste dental
    Transporte
  Erizo
    Alimentacion
      Alimentos
      Alimento vivo y deshidratado
        Alimento vivo
        Alimento deshidratado
    Habitat y jaulas
    Salud e higiene
      Sustratos
    Premios y desgaste dental
    Transporte
  Chinchilla
    Alimentacion
      Alimentos
      Premios y snacks
      Desgaste dental
    Salud e higiene
      Sustratos
    Premios y desgaste dental
    Transporte
  Huron
    Alimentacion
      Alimentos
    Habitat y jaulas
    Salud e higiene
      Sustratos
    Transporte
  Accesorios generales
```

Categorias actuales relacionadas:

- `Mamiferos roedores / ...`
- `Hamster / ...`
- `Conejo / ...`
- `Cuyo / ...`
- `Erizo / ...`
- `Chinchilla / ...`
- Categorias heredadas de `Huron`, que actualmente existen como ecommerce pero no estan bien integradas al arbol maestro.

Decision recomendada:

- Consolidar `Mamiferos roedores` como contenedor `Pequenos mamiferos`.
- Mantener especie como segundo nivel cuando el producto sea especifico.
- Usar `Pequenos mamiferos / Accesorios generales` solo para productos realmente multi-especie.

### 7. General multi-especie

```text
General multi-especie
  Alimentacion y comederos
  Salud e higiene
  Transporte y viaje
    Transportadoras
    Mochilas transportadoras
  Repuestos y accesorios
  Ofertas, kits y paquetes comerciales
```

Uso:

- Productos que aplican a varias especies y donde elegir una especie principal seria forzado.
- No debe convertirse en cajon de sastre.
- Si un producto esta pensado principalmente para perro o gato, debe clasificarse en perro o gato y agregarse aqui solo como categoria secundaria si conviene para catalogos comerciales.

Categorias actuales candidatas a integrarse:

- `Perros y gatos`
- `Transporte, paseo y entrenamiento`
- `Repuestos y accesorios`
- `Salud e higiene`
- `Alimentacion`
- `Habitat y descanso`
- `Juego y enriquecimiento`

## Categorias actuales que conviene dejar de usar como raiz

Estas categorias no deben borrarse de inmediato porque tienen productos y pueden servir como referencia historica, pero operativamente conviene dejar de usarlas como raiz principal:

- `Perros y gatos`
- `Mamiferos roedores`
- `Betta`
- `Tortuga`
- `Iguana`
- `Alimentacion`
- `Habitat y descanso`
- `Salud e higiene`
- `Transporte, paseo y entrenamiento`
- `Juego y enriquecimiento`
- `Repuestos y accesorios`
- Todas las raices heredadas `ECOM-CAT-*` que ya tengan equivalente maestro.

La accion recomendada no es borrar primero, sino:

1. Crear o ajustar arbol maestro limpio.
2. Reasignar productos hacia categorias principales.
3. Marcar categorias heredadas como no operativas o historicas cuando queden vacias.
4. Mantener trazabilidad de equivalencias para no perder contexto de migracion.

## Normalizacion de nombres

Reglas propuestas:

- Usar singular para familia animal: `Perros`, `Gatos`, `Aves` son aceptables como raiz comercial.
- Evitar nombres con lista de palabras mezcladas: cambiar `Accesorios, entrenamiento, sujetadores para perro` por subcategorias claras.
- Evitar especie dentro del nombre cuando ya vive dentro de la especie:
  - Malo: `Perro / Alimento para perros`.
  - Mejor: `Perros / Alimentacion / Alimentos`.
- Corregir doble espacio y nombres largos heredados:
  - `Alimento y alimentadores  para conejos` -> `Alimentacion`.
  - `Acuarios, nano, micro y megas con y sin mueble para peces` -> separar en `Peceras`, `Peceras equipadas`, `Bases y muebles`.
- Usar acentos en UI cuando el sistema ya esta en UTF-8.
- Mantener codigos internos estables hasta que se haga migracion formal.

## Propuesta de fases

### Fase 1 - Aprobacion documental

- Revisar este documento.
- Confirmar nombres de raices principales.
- Confirmar si `Pequenos mamiferos` reemplaza a `Mamiferos roedores`.
- Confirmar si `Acuario` cambia visualmente a `Acuario y peces`.

### Fase 2 - Mapa de equivalencias

Crear documento o script read-only que proponga equivalencias:

```text
Categoria actual -> Categoria destino propuesta
```

Ejemplos:

- `Alimento para perros` -> `Perros / Alimentacion / Alimentos`
- `Transportadoras para gatos` -> `Gatos / Transporte, paseo y entrenamiento / Transportadoras`
- `Alimentos para Iguana` -> `Reptiles y tortugas / Iguanas / Alimentacion`
- `Premios, snacks y desgaste de dientes para roedor` -> `Pequenos mamiferos / [especie] / Premios y desgaste dental`

### Fase 3 - Preparar DDL/DML propuesto, sin ejecutar

- No aplicar cambios todavia.
- Generar SQL idempotente de categorias faltantes.
- Generar SQL de reubicacion de relaciones producto-categoria con respaldo externo.
- Separar migracion de categorias maestras y baja/inactivacion de heredadas.

### Fase 4 - Aplicacion controlada

Solo con autorizacion:

- Respaldar fuera del proyecto.
- Crear categorias faltantes.
- Reasignar relaciones de productos.
- Validar conteos antes/despues.
- No borrar categorias con productos; primero dejarlas sin uso operativo.

## Criterios de cierre

La reorganizacion de categorias se considera lista cuando:

- Existen pocas raices principales, no 96.
- Cada producto tiene al menos una categoria principal clara.
- Las categorias heredadas de ecommerce ya no aparecen como opcion principal de captura.
- Las categorias por uso no compiten contra las categorias por especie.
- El CRUD de categorias permite ver jerarquia, crear hijas y marcar categorias como no operativas.
- El alta/edicion de producto permite buscar categorias por ruta completa.

## Pendientes de decision

- Nombre final de `Acuario` vs `Acuario y peces`.
- Nombre final de `Pequenos mamiferos` vs `Mamiferos pequenos`.
- Si `Betta` queda como subcategoria de `Acuario y peces` o como subgrupo destacado solo para ecommerce/catalogos comerciales.
- Si `Tortuga` queda dentro de `Reptiles y tortugas` o si se crea un subgrupo comercial adicional para tortugas acuaticas.
- Si `General multi-especie` debe mostrarse a usuarios operativos o solo usarse como categoria secundaria interna.
