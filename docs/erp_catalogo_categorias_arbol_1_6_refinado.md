# ERP Catalogo - Arbol refinado 1-6

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-14  
Modulo: ERP > Catalogo > Categorias  
Proyecto vigente: `C:\xampp\htdocs\panel_de_control`  
Estado: propuesta documental; no aplicada en BD

## Objetivo

Definir una estructura clara para las seis categorias principales del catalogo, usando como base los movimientos y comentarios hechos en `docs/erp_catalogo_categorias_arbol_propuesto.md`.

En esta fase no se resuelve la categoria multi-especie. Primero se ordenan las raices principales para que el alta y edicion de productos sea mas facil.

## Raices principales

```text
Perros
Gatos
Acuario y peces
Reptiles y tortugas
Aves
Pequenos mamiferos
```

## Reglas de uso

- La categoria principal debe responder: "donde buscaria normalmente este producto un operador o cliente".
- Se permiten nombres repetidos bajo distintas raices, por ejemplo `Alimentos`, `Juguetes`, `Transportadoras`.
- Por eso la UI debe mostrar siempre la ruta completa.
- Las categorias heredadas no deben borrarse todavia; primero se mapearan a una categoria destino.
- Los productos multi-especie se resolveran despues con categoria secundaria o grupo especial, no en esta primera limpieza.

## 1. Perros

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
  Juego y enriquecimiento
    Juguetes
    Mordederas
    Interactivos y entrenamiento mental
  Transporte, paseo y entrenamiento
    Transportadoras
    Mochilas transportadoras
    Paseo y sujecion
    Entrenamiento
  Accesorios generales
```

Notas:

- `Juego y enriquecimiento` incluye juguetes, mordederas, pelotas, tapetes olfativos, dispensadores de premio y productos para estimular conducta o actividad.
- `Accesorios generales` debe usarse poco. Sirve para accesorios que no sean claramente alimento, salud, habitat, juego, paseo o transporte.
- Collares, correas, pecheras, arneses y sujetadores van en `Transporte, paseo y entrenamiento / Paseo y sujecion`.
- Dispensadores, platos, tazones y contenedores van en `Alimentacion`.

## 2. Gatos

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
    Control de olores y arenas
  Habitat y descanso
    Camas y colchones
    Casas
    Contencion
      Jaulas
      Corrales y vallas
  Juego y enriquecimiento
    Juguetes
    Rascadores
    Interactivos y enriquecimiento
  Transporte, paseo y entrenamiento
    Transportadoras
    Mochilas transportadoras
    Paseo y sujecion
    Entrenamiento
  Accesorios generales
```

Notas:

- Para tu duda de jaulas/corrales/vallas, `Contencion` es un buen nombre operativo. Evita meter todo en descanso.
- Areneros, palas, tapetes sanitarios, filtros, arenas y control de olor van en `Salud e higiene`.
- Rascadores viven mejor en `Juego y enriquecimiento` porque cumplen funcion conductual.
- Accesorios generales queda para productos raros que no entren claramente en las secciones anteriores.

## 3. Acuario y peces

```text
Acuario y peces
  Peceras, acuarios y muebles
    Peceras
    Acuarios
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
    Sustratos, gravas y arenas
    Plantas artificiales
  Alimentacion
    Alimentos para peces
    Alimentos para betta
    Alimentos de acuario
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

Notas:

- `Tortugueros` y `Alimentos para tortugas` deben moverse a `Reptiles y tortugas / Tortugas`, aunque antes estuvieran en acuario.
- `Betta` no conviene como raiz separada para catalogo operativo. Se integra en `Acuario y peces`.
- Plantas naturales viven mejor en `Animales vivos y plantas naturales`; plantas artificiales quedan en decoracion.
- `Sustratos, gravas y arenas` queda en decoracion porque normalmente se compra para ambientacion del acuario.

## 4. Reptiles y tortugas

```text
Reptiles y tortugas
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
  Accesorios generales
```

Notas:

- La raiz debe llamarse `Reptiles y tortugas`, no solo `Reptiles`, para que tortuga no quede escondida.
- `Terrarios / Madera` y `Terrarios / Aluminio` son validos si realmente quieres clasificar por material. Si luego se vuelve atributo, se podria dejar solo `Terrarios`.
- `Alimentos vivos` queda en reptiles generales si aplica a varias especies.
- Iguana queda como subgrupo, no como raiz.

## 5. Aves

```text
Aves
  Alimentacion
    Alimentos
    Alimento vivo y deshidratado
      Alimento vivo
      Alimento deshidratado
    Premios y snacks
    Suplementos y vitaminas
      Generales
      Postura
      Canto
    Bebederos y aditamentos para colibri
    Bebederos y comederos
      Bebederos
      Comederos
  Salud e higiene
    Limpieza de habitat
    Prevencion y control de acaros
    Sustratos y fondos
  Habitat
    Jaulas
    Accesorios para jaulas
  Juego y enriquecimiento
    Juguetes
  Transporte
    Transportadoras
  Accesorios generales
```

Notas:

- Suplementos de postura, canto y multivitaminicos viven mejor en `Alimentacion / Suplementos y vitaminas`.
- Productos para acaros, limpieza del habitat y preventivos van en `Salud e higiene`.
- Sustratos de jaula van en `Salud e higiene / Sustratos y fondos`.
- Colibri queda separado porque comercialmente suele tener bebederos y aditamentos especificos.

## 6. Pequenos mamiferos

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
    Salud e higiene
      Sustratos
    Transporte
  Conejo
    Alimentacion
      Alimentos
      Premios y snacks
      Desgaste dental
    Habitat y jaulas
    Camas, casas y colchones
    Salud e higiene
      Sustratos
    Transporte
  Cuyo
    Alimentacion
      Alimentos
      Premios y snacks
      Desgaste dental
    Habitat y jaulas
    Salud e higiene
      Sustratos
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
    Transporte
  Chinchilla
    Alimentacion
      Alimentos
      Premios y snacks
      Desgaste dental
    Habitat y jaulas
    Salud e higiene
      Sustratos
    Transporte
  Huron
    Alimentacion
      Alimentos
      Premios y snacks
    Habitat y jaulas
    Salud e higiene
      Sustratos
    Transporte
  Accesorios generales
```

Notas:

- `Mamiferos roedores` debe reemplazarse conceptualmente por `Pequenos mamiferos`.
- Conejo, cuyo, hamster, erizo, chinchilla y huron quedan como subgrupos, no como raices.
- `Premios y desgaste dental` no debe repetirse fuera de alimentacion; dentro de alimentacion queda separado como `Premios y snacks` y `Desgaste dental`.
- `Sustratos` van en `Salud e higiene` porque se relacionan con limpieza, cama y control del habitat.
- `Huron` debe integrarse aunque hoy venga principalmente de categorias heredadas.

## Decisiones pendientes

- Confirmar si `Acuario` se renombra visualmente a `Acuario y peces`.
- Confirmar si `Mamiferos roedores` se reemplaza por `Pequenos mamiferos`.
- Confirmar si `Terrarios / Madera` y `Terrarios / Aluminio` seran categorias o atributos.
- Confirmar si `Contencion` se usa tambien en perros o solo en gatos.
- Preparar mapa de equivalencias `categoria actual -> categoria destino propuesta`.

## Siguiente paso

Crear un mapa read-only de equivalencias para revisar antes de modificar datos:

```text
Categoria actual
Categoria destino propuesta
Motivo
Riesgo
Conteo de productos afectados
```
