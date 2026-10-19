# Auditoría de calidad de catálogos comerciales — 17 de octubre de 2026

IA: Codex GPT-6. Consulta del módulo en una transacción de sólo lectura. No se modificaron productos, precios, imágenes ni selecciones.

Fecha de lectura: 2026-10-17T22:43:47-06:00.

## Cómo leer los resultados

- Orden: SKUs guardados de mayor a menor, el mismo conteo utilizado en la lista del ERP. También se muestran productos maestros únicos, para distinguir las variantes.
- Incidencia esencial: sin precio vigente resuelto por Listas de precios, sin imagen, imagen local inexistente/ilegible, sin categoría principal, nombre vacío, selección que el visor omite por estado o inconsistencia foto/título comprobada visualmente.
- Cada SKU se cuenta una sola vez en «Con incidencia», aunque tenga varios faltantes. Un producto/SKU puede aparecer en varios catálogos: los totales de apariciones no son productos distintos.
- «Fotos» son imágenes de productos. Las portadas de los catálogos se revisan aparte.
- Marca, atributos de variante, publicación ecommerce y resolución menor a 300 px son observaciones de calidad separadas. No todas bloquean un catálogo comercial.
- Las variantes del mismo producto y los productos compartidos entre categorías no se consideran duplicados. Las coincidencias de nombre entre productos distintos requieren revisión humana.
- La existencia de ruta y las dimensiones no prueban que la foto corresponda al producto, sea nítida o esté bien recortada. La clasificación correcta y la calidad editorial requieren revisión visual. Se inspeccionó el caso 11392: nombre blanca y foto rosa, compartida con 11391; no se hizo inspección visual de todas las fotos. No se auditó inventario.

## Resumen global

- 50 catálogos; 1096 selecciones; 952 SKUs distintos y 763 productos maestros distintos.
- 37 catálogos tienen al menos una incidencia esencial; 230 SKUs distintos están afectados.
| Tipo | Apariciones en catálogos | SKUs distintos |
|---|---:|---:|
| Sin precio vigente | 251 | 222 |
| Sin marca registrada | 222 | 194 |
| Producto con variantes, SKU sin atributos de variante | 211 | 182 |
| Sin publicación ecommerce asociada | 80 | 55 |
| Nombre con caracteres dañados | 30 | 30 |
| Sin imagen registrada | 7 | 7 |
| SKU no activo | 4 | 4 |
| Foto rosa; título indica blanca (revisión visual de SKU 11392) | 2 | 1 |
| Seleccionado pero no visible por estado o referencia faltante | 1 | 1 |

## Orden completo de trabajo

| Orden | Código | Catálogo | SKUs | Productos | Sin precio | Sin foto | Archivo foto faltante/ilegible | Foto/color inconsistente confirmado | No visibles | Con incidencia | % |
|---:|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1 | CC-00014 | Jaulas para mamíferos roedores | 80 | 40 | 1 | 0 | 0 | 1 | 0 | 2 | 2.5% |
| 2 | CC-00016 | Alimentos de acuario | 67 | 44 | 9 | 0 | 0 | 0 | 0 | 9 | 13.4% |
| 3 | CC-00027 | Filtración y oxigenación para acuarios | 63 | 63 | 11 | 1 | 0 | 0 | 0 | 12 | 19.0% |
| 4 | CC-00005 | Jaulas hámster | 62 | 25 | 0 | 0 | 0 | 1 | 0 | 1 | 1.6% |
| 5 | CC-00008 | Areneros | 48 | 29 | 22 | 0 | 0 | 0 | 0 | 22 | 45.8% |
| 6 | CC-00001 | Llaveros mascotas 3d | 47 | 47 | 3 | 0 | 0 | 0 | 0 | 3 | 6.4% |
| 7 | CC-00049 | Camas y colchones para gatos | 43 | 19 | 31 | 0 | 0 | 0 | 0 | 31 | 72.1% |
| 8 | CC-00029 | Accesorios para acuarios | 39 | 39 | 7 | 0 | 0 | 0 | 0 | 7 | 17.9% |
| 9 | CC-00046 | Comederos y bebederos para gatos | 38 | 35 | 23 | 0 | 0 | 0 | 0 | 23 | 60.5% |
| 10 | CC-00007 | Terrarios | 36 | 36 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 11 | CC-00002 | Peceras | 33 | 33 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 12 | CC-00020 | Alimentos para peces | 31 | 18 | 2 | 0 | 0 | 0 | 0 | 2 | 6.5% |
| 13 | CC-00003 | Jaulas ave | 31 | 16 | 4 | 0 | 0 | 0 | 0 | 4 | 12.9% |
| 14 | CC-00040 | Accesorios para jaulas de aves | 28 | 27 | 24 | 0 | 0 | 0 | 0 | 24 | 85.7% |
| 15 | CC-00009 | Rascadores | 28 | 24 | 9 | 0 | 0 | 0 | 0 | 9 | 32.1% |
| 16 | CC-00030 | Aditamentos y acondicionadores para acuarios | 27 | 27 | 2 | 0 | 0 | 0 | 0 | 2 | 7.4% |
| 17 | CC-00041 | Juguetes para aves | 26 | 26 | 6 | 0 | 0 | 0 | 0 | 6 | 23.1% |
| 18 | CC-00047 | Juguetes para gatos | 26 | 25 | 18 | 0 | 0 | 0 | 0 | 18 | 69.2% |
| 19 | CC-00013 | Sustratos mamíferos roedores | 26 | 14 | 5 | 0 | 0 | 0 | 0 | 5 | 19.2% |
| 20 | CC-00022 | Decoración para acuarios | 24 | 24 | 7 | 0 | 0 | 0 | 0 | 7 | 29.2% |
| 21 | CC-00032 | Alimentos para aves | 23 | 22 | 3 | 0 | 0 | 0 | 0 | 3 | 13.0% |
| 22 | CC-00042 | Accesorios generales para aves | 23 | 22 | 17 | 0 | 0 | 0 | 0 | 17 | 73.9% |
| 23 | CC-00025 | Sustratos, gravas y arenas para acuarios | 23 | 9 | 3 | 0 | 0 | 0 | 0 | 3 | 13.0% |
| 24 | CC-00045 | Alimentos para gatos | 18 | 14 | 4 | 0 | 0 | 0 | 0 | 4 | 22.2% |
| 25 | CC-00026 | Calefacción para acuarios | 17 | 17 | 3 | 0 | 0 | 0 | 0 | 3 | 17.6% |
| 26 | CC-00011 | Bases y muebles para peceras | 17 | 16 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 27 | CC-00028 | Iluminación para acuarios | 17 | 16 | 2 | 0 | 0 | 0 | 0 | 2 | 11.8% |
| 28 | CC-00012 | Arenas para gatos | 13 | 13 | 2 | 0 | 0 | 0 | 0 | 2 | 15.4% |
| 29 | CC-00006 | Tortugueros | 12 | 12 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 30 | CC-00035 | Bebederos para aves | 12 | 12 | 8 | 0 | 0 | 0 | 0 | 8 | 66.7% |
| 31 | CC-00044 | Premios y snacks para gatos | 12 | 8 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 32 | CC-00034 | Bebederos y aditamentos para colibrí | 11 | 11 | 7 | 0 | 0 | 0 | 0 | 7 | 63.6% |
| 33 | CC-00010 | Bombas sumergibles | 10 | 10 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 34 | CC-00004 | Corrales metálicos | 9 | 9 | 0 | 0 | 0 | 0 | 1 | 1 | 11.1% |
| 35 | CC-00039 | Alimento vivo para aves | 9 | 2 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 36 | CC-00037 | Premios y snacks para aves | 8 | 7 | 5 | 0 | 0 | 0 | 0 | 5 | 62.5% |
| 37 | CC-00019 | Alimentos para betta | 7 | 7 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 38 | CC-00021 | Peces de acuario | 7 | 1 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 39 | CC-00033 | Suplementos y vitaminas para aves | 6 | 6 | 1 | 0 | 0 | 0 | 0 | 1 | 16.7% |
| 40 | CC-00038 | Alimento deshidratado para aves | 6 | 3 | 2 | 2 | 0 | 0 | 0 | 4 | 66.7% |
| 41 | CC-00036 | Comederos para aves | 5 | 5 | 1 | 0 | 0 | 0 | 0 | 1 | 20.0% |
| 42 | CC-00018 | Alimentos para ajolotes | 4 | 4 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 43 | CC-00024 | Raíces y troncos para acuarios | 4 | 4 | 1 | 4 | 0 | 0 | 0 | 4 | 100.0% |
| 44 | CC-00031 | Repuestos para peceras | 4 | 4 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 45 | CC-00048 | Interactivos y enriquecimiento para gatos | 4 | 4 | 3 | 0 | 0 | 0 | 0 | 3 | 75.0% |
| 46 | CC-00023 | Plantas artificiales para acuarios | 3 | 3 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 47 | CC-00043 | Transportadoras para aves | 3 | 3 | 3 | 0 | 0 | 0 | 0 | 3 | 100.0% |
| 48 | CC-00015 | Alimentadores para peces | 2 | 2 | 0 | 0 | 0 | 0 | 0 | 0 | 0.0% |
| 49 | CC-00017 | Alimentos de fondo para peces | 2 | 2 | 1 | 0 | 0 | 0 | 0 | 1 | 50.0% |
| 50 | CC-00050 | Tazones para gatos | 2 | 2 | 1 | 0 | 0 | 0 | 0 | 1 | 50.0% |

## Detalle para corregir en el orden solicitado

### 1. CC-00014 — Jaulas para mamíferos roedores

80 SKUs guardados / 40 productos; 2 SKUs con incidencia esencial (2.5%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 11; Foto rosa; título indica blanca (revisión visual de SKU 11392): 1; Sin marca registrada: 2; Sin precio vigente: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| 11390 | 1600 | 11390 SEVENPET MANSION AZUL 47.5X29.5X31.5 CM (12) | — | Producto con variantes, SKU sin atributos de variante |
| 11391 | 1600 | 11391 SEVENPET MANSION ROSA 47.5X29.5X31.5 CM (12) | — | Producto con variantes, SKU sin atributos de variante |
| 11392 | 1600 | 11392 SEVENPET MANSION BLANCA 47.5X29.5X31.5 CM (12) | Foto rosa; título indica blanca (revisión visual de SKU 11392) | Producto con variantes, SKU sin atributos de variante |
| 11396 | 1599 | 11396 SEVENPET MANSION AZUL 60X36X42 CM (4) | — | Producto con variantes, SKU sin atributos de variante |
| 11397 | 1599 | 11397 SEVENPET MANSION ROSA 60X36X42 CM (4) | — | Producto con variantes, SKU sin atributos de variante |
| 11398 | 1599 | 11398 SEVENPET MANSION BLANCA 60X36X42 CM (4) | — | Producto con variantes, SKU sin atributos de variante |
| JAGA-JACHA046060 | 1164 | Jaula galvanizada 4 panel de 60 x 60 cm 3 cm separacion con tapa y charola galvanizada | — | Sin marca registrada |
| SP-3674 | 1446 | Jaula plastica para conejo rosa 45.0 x 30.0 x 25.0 cm | — | Producto con variantes, SKU sin atributos de variante |
| SP-3674-AL | 1446 | Jaula para mamífero despar azul | — | Producto con variantes, SKU sin atributos de variante |
| SP-3674-BO | 1446 | Jaula para mamífero despar blanca | — | Producto con variantes, SKU sin atributos de variante |
| SP-3674-MO | 1446 | Jaula para mamífero despar morada | — | Producto con variantes, SKU sin atributos de variante |
| SP-3674-VE | 1446 | Jaula para mamífero despar verde | — | Producto con variantes, SKU sin atributos de variante |
| PPJCONEXG | 336 | Jaula para conejos extra grande | — | Sin marca registrada |
| 10276 | 334 | Jaula conejera 58 x 32 x 32 cm | Sin precio vigente | — |

### 2. CC-00016 — Catálogo - Alimentos de acuario

67 SKUs guardados / 44 productos; 9 SKUs con incidencia esencial (13.4%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 29; Sin publicación ecommerce asociada: 16; Sin marca registrada: 2; Sin precio vigente: 9.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| TP-40372 | 50 | Alimento tropical koi & goldfish basic sticks 4 kg | — | Producto con variantes, SKU sin atributos de variante |
| TP-40372-100GR | 50 | Alimento churro blanco para peces 100 gr | — | Producto con variantes, SKU sin atributos de variante |
| TP-40372-25GR | 50 | Alimento churro blanco para peces 25 gr | — | Producto con variantes, SKU sin atributos de variante |
| TP-40372-500GR | 50 | Alimento churro blanco para peces 500 gr | — | Producto con variantes, SKU sin atributos de variante |
| TP-40372-50GR | 50 | Alimento churro blanco para peces 50 gr | — | Producto con variantes, SKU sin atributos de variante |
| TP-40311 | 1017 | Alimento tropical pond sticks mixed 4 kg | — | Producto con variantes, SKU sin atributos de variante |
| TP-40311-100GR | 1017 | Alimento tropical pond sticks mixed 100 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-40311-25GR | 1017 | Alimento tropical pond sticks mixed 25 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-40311-500GR | 1017 | Alimento tropical pond sticks mixed 500 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-40311-50GR | 1017 | Alimento tropical pond sticks mixed 50 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-40311-GRANEL | 1017 | Alimento tropical pond sticks mixed 1 kg GRANEL | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-40352 | 1016 | Alimento tropical koi & goldfish colour sticks 4 kg | — | Producto con variantes, SKU sin atributos de variante |
| TP-40352-500GR | 1016 | Alimento churro rojo para peces 500 gr | — | Producto con variantes, SKU sin atributos de variante |
| WA001 | 1490 | Hojuelas basicas 10 gr | — | Producto con variantes, SKU sin atributos de variante |
| WA002 | 1490 | Hojuelas basicas 20 gr | — | Producto con variantes, SKU sin atributos de variante |
| WA003 | 1490 | Hojuelas basicas 30 gr | — | Producto con variantes, SKU sin atributos de variante |
| WA004 | 1490 | Hojuelas basicas 100 gr | — | Producto con variantes, SKU sin atributos de variante |
| Q817 | 76 | Super flakes 150 gr | — | Producto con variantes, SKU sin atributos de variante |
| TP-76700 | 1622 | ALIMENTO TROPICAL FLAKES MIX 5KG. | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-76700-100GR | 1622 | ALIMENTO TROPICAL FLAKES MIX 100 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-76700-25GR | 1622 | ALIMENTO TROPICAL FLAKES MIX 25 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-76700-500GR | 1622 | ALIMENTO TROPICAL FLAKES MIX 500 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-76700-50GR | 1622 | ALIMENTO TROPICAL FLAKES MIX 50 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-76700-GRANEL | 1622 | ALIMENTO TROPICAL FLAKES MIX 1KG GRANEL | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| ARSAGU-ARIN | 1576 | Artemia instantanea | — | Sin marca registrada |
| ARSAGU-MASO | 1577 | Mix artemia sin oxigeno | — | Sin marca registrada |
| TP-40372-GRANEL | 50 | Alimento tropical koi & goldfish basic sticks 1 kg | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-40352-100G | 1016 | Alimento tropical koi & goldfish colour sticks 100 gr | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-40352-25G | 1016 | Alimento tropical koi & goldfish colour sticks 25 gr | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-40352-50G | 1016 | Alimento tropical koi & goldfish colour sticks 50 gr | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-40352-GRANEL | 1016 | Alimento tropical koi & goldfish colour sticks 1 kg GRANEL | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| WAT638 | 628 | Alimento para peces de fondo 70 gr | Sin precio vigente | — |
| Q818 | 1001 | Alimento en hojuela super flakes 500 gr | Sin precio vigente | — |
| Q118 | 929 | Alimento pleco sticks 160 gr | Sin precio vigente | — |
| Q117 | 928 | Alimento pleco sticks 80 gr | Sin precio vigente | — |

### 3. CC-00027 — Catálogo - Filtración y oxigenación para acuarios

63 SKUs guardados / 63 productos; 12 SKUs con incidencia esencial (19.0%).

Portada: ok; activa.

Conteos: Nombre con caracteres dañados: 1; Sin marca registrada: 5; Sin precio vigente: 11; Sin imagen registrada: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| HA-118C | 958 | Piedra aereador tortuga 6 � 8 cm | — | Nombre con caracteres dañados |
| XY-2836 | 669 | Filtro de esponja | — | Sin marca registrada |
| XY-2891 | 668 | Filtro de esponja de esquina | — | Sin marca registrada |
| FP40N | 291 | Filtro placa 40cm | — | Sin marca registrada |
| 4263 | 783 | Bomba de aire doble salida aqua | Sin precio vigente | — |
| OCI-2500 | 1633 | FILTRO INTERNO 2500 L/H | Sin precio vigente | — |
| 1106 | 293 | Filtro interno bioesf-carbon- esponja 450 l/h 70 cm | Sin precio vigente | — |
| 1107 | 294 | Filtro interno bioesf-carbon-esponja 720 l/h 115 cm | Sin precio vigente | — |
| FL7785 | 453 | Filtro interno aquajet mini 25 l | Sin precio vigente | Sin marca registrada |
| FL8415 | 279 | Piedra aireadora 30 cm | Sin precio vigente | — |
| HA-203C | 959 | Piedra aereador circular 7.5 cm | Sin precio vigente | — |
| FL8414 | 278 | Piedra aireadora 25 cm | Sin precio vigente | — |
| FL8411 | 280 | Piedra aireadora 10 cm | Sin precio vigente | — |
| FL8412 | 281 | Piedra aireadora 15 cm | Sin precio vigente | — |
| FL8413 | 282 | Piedra aireadora 20 cm | Sin precio vigente | — |
| 1124 | 1592 | 1124 FILTRO CASCATTA 250 L/H 40-80LTS (24) | Sin imagen registrada | Sin marca registrada |

### 4. CC-00005 — Jaulas hámster

62 SKUs guardados / 25 productos; 1 SKUs con incidencia esencial (1.6%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 12; Foto rosa; título indica blanca (revisión visual de SKU 11392): 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| 11332 | 1448 | Jaula extravaganza azul 27 x 21 x 27 cm | — | Producto con variantes, SKU sin atributos de variante |
| 11333 | 1448 | Jaula extravaganza rosa 27 x 21 x 27 cm | — | Producto con variantes, SKU sin atributos de variante |
| 11334 | 1448 | Jaula extravaganza cafe 27 x 21 x 27 cm | — | Producto con variantes, SKU sin atributos de variante |
| 11354 | 1449 | Jaula town house III rosa 27 x 21 x 45 cm | — | Producto con variantes, SKU sin atributos de variante |
| 11353 | 1449 | Jaula town house III azul 27 x 21 x 45 cm | — | Producto con variantes, SKU sin atributos de variante |
| 11355 | 1449 | Jaula town house III cafe 27 x 21 x 45 cm | — | Producto con variantes, SKU sin atributos de variante |
| 11398 | 1599 | 11398 SEVENPET MANSION BLANCA 60X36X42 CM (4) | — | Producto con variantes, SKU sin atributos de variante |
| 11397 | 1599 | 11397 SEVENPET MANSION ROSA 60X36X42 CM (4) | — | Producto con variantes, SKU sin atributos de variante |
| 11396 | 1599 | 11396 SEVENPET MANSION AZUL 60X36X42 CM (4) | — | Producto con variantes, SKU sin atributos de variante |
| 11392 | 1600 | 11392 SEVENPET MANSION BLANCA 47.5X29.5X31.5 CM (12) | Foto rosa; título indica blanca (revisión visual de SKU 11392) | Producto con variantes, SKU sin atributos de variante |
| 11391 | 1600 | 11391 SEVENPET MANSION ROSA 47.5X29.5X31.5 CM (12) | — | Producto con variantes, SKU sin atributos de variante |
| 11390 | 1600 | 11390 SEVENPET MANSION AZUL 47.5X29.5X31.5 CM (12) | — | Producto con variantes, SKU sin atributos de variante |

### 5. CC-00008 — Areneros

48 SKUs guardados / 29 productos; 22 SKUs con incidencia esencial (45.8%).

Portada: ok; activa.

Conteos: Nombre con caracteres dañados: 1; Producto con variantes, SKU sin atributos de variante: 16; Sin marca registrada: 11; Sin precio vigente: 22.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| 7687 | 644 | Arenero clean cat con pala 48 � 38 � 18.5 cm | — | Nombre con caracteres dañados |
| MP080403 | 1474 | Arenero chico con marco | — | Producto con variantes, SKU sin atributos de variante |
| MP080403-RO | 1474 | Arenero plus chico rojo | — | Producto con variantes, SKU sin atributos de variante |
| MP080404 | 589 | Arenero grande con marco | — | Sin marca registrada |
| MP070801 | 1456 | Arenero chico con tapa | — | Producto con variantes, SKU sin atributos de variante |
| MP070801-AL | 1456 | Arenero tapado azul | — | Producto con variantes, SKU sin atributos de variante |
| MP070801-RO | 1456 | Arenero tapado roja | — | Producto con variantes, SKU sin atributos de variante |
| 7649 | 110 | Condocat casa 50 x 50 x 52 cm | — | Sin marca registrada |
| SH-230133-RA | 1476 | Arenero aqua | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| SH-230133-VE | 1476 | Arenero aqua verde | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| ARO-43 | 43 | Arenero blak azul | Sin precio vigente | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ARO-43MO | 43 | Arenero blak morado | Sin precio vigente | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| 98708 | 702 | Arenero inteligente cathy azul | Sin precio vigente | — |
| 98675 | 36 | Arenero cathy linea ecológica gris | Sin precio vigente | — |
| 98681 | 36 | Arenero cathy | Sin precio vigente | — |
| 98649 | 1470 | Arenero cathy easy clean azul | Sin precio vigente | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| 98686 | 1470 | Arenero cathy easy clean | Sin precio vigente | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ARO-05 | 108 | Arenero charola negra grande | Sin precio vigente | Sin marca registrada |
| 8003507966253 | 212 | Arenero cornic | Sin precio vigente | — |
| SH-2217 | 641 | Arenero Dublín | Sin precio vigente | — |
| ARO-42G | 1183 | Arenero eston gris | Sin precio vigente | Sin marca registrada |
| SH-230718-BO | 1480 | Arenero frida azul blanco | Sin precio vigente | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| SH-230718-GS | 1480 | Arenero frida grisrosa | Sin precio vigente | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| SH-230718-VE | 1480 | Arenero frida amarilloverde | Sin precio vigente | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| 800350797763 | 1479 | Arenero furba triple | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| 800350797764 | 1479 | Arenero furba triple verde | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| 98006 | 713 | Arenero furfa triple grande | Sin precio vigente | — |
| 7681 | 33 | Arenero deluxe II rosa con pala 47 x 39 x 18.5 cm | Sin precio vigente | — |
| 7684 | 33 | Arenero deluxe II azul con pala 47 x 39 x 18.5 cm | Sin precio vigente | — |
| 19120 | 35 | Arenero italiano cafe | Sin precio vigente | — |

### 6. CC-00001 — Llaveros mascotas 3d

47 SKUs guardados / 47 productos; 3 SKUs con incidencia esencial (6.4%).

Portada: ok; activa.

Conteos: Sin marca registrada: 47; Sin precio vigente: 3.
Posibles coincidencias de nombre entre productos distintos (no confirmado): [{"nombre_normalizado": "llavero3ddecocodriloarticulado", "ids_productos": [1269, 1365]}, {"nombre_normalizado": "llavero3ddechihuahuaarticuladoblancoconcafe", "ids_productos": [1211, 1252]}].

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| LLA-04BB | 1251 | Llavero 3D de cocker spaniel articulado beige | — | Sin marca registrada |
| LLA-04C | 1205 | Llavero 3D de cocker spaniel articulado café | — | Sin marca registrada |
| LLA-04CC | 1250 | Llavero 3D de cocker spaniel articulado café claro | — | Sin marca registrada |
| LLA-04CB | 1206 | Llavero 3D de cocker spaniel articulado café con blanco | — | Sin marca registrada |
| LLA-04NE | 1313 | Llavero 3D de cocker spaniel articulado negro | — | Sin marca registrada |
| LLA-25 | 1269 | Llavero 3D de cocodrilo articulado | — | Sin marca registrada |
| LLA-35 | 1365 | Llavero 3D de cocodrilo articulado | — | Sin marca registrada |
| LLA-42 | 1372 | Llavero 3D de cuervo articulado | — | Sin marca registrada |
| LLA-20 | 1254 | Llavero 3D de corgi galés articulado | — | Sin marca registrada |
| LLA-30 | 1274 | Llavero 3D de cuyo articulado | — | Sin marca registrada |
| LLA-32OM | 1295 | Llavero 3D de dalmata articulado ojo manchado | Sin precio vigente | Sin marca registrada |
| LLA-32 | 1294 | Llavero 3D de dalmata articulado ojos negros | Sin precio vigente | Sin marca registrada |
| LLA-32ON | 1296 | Llavero 3D de dalmata articulado orejas negras | Sin precio vigente | Sin marca registrada |
| LLA-23 | 1267 | Llavero 3D de dragon barbudo (pogona) articulado beige | — | Sin marca registrada |
| LLA-31 | 1275 | Llavero 3D de erizo articulado | — | Sin marca registrada |
| LLA-15 | 1233 | Llavero 3D de frensh poodle articulado | — | Sin marca registrada |
| LLA-15BE | 1305 | Llavero 3D de frensh poodle articulado beige | — | Sin marca registrada |
| LLA-15NE | 1312 | Llavero 3D de frensh poodle articulado negro | — | Sin marca registrada |
| LLA-15BN | 1292 | Llavero 3D de frensh poodle articulado oreja negras | — | Sin marca registrada |
| LLA-14 | 1226 | Llavero 3D de gato articulado | — | Sin marca registrada |
| LLA-14BE | 1228 | Llavero 3D de gato articulado beige | — | Sin marca registrada |
| LLA-14B | 1255 | Llavero 3D de gato articulado blanco | — | Sin marca registrada |
| LLA-13G | 1225 | Llavero 3D de bulldog frances articulado gris manchado | — | Sin marca registrada |
| LLA-13GB | 1243 | Llavero 3D de bulldog frances articulado gris pechito blanco | — | Sin marca registrada |
| LLA-13NC | 1288 | Llavero 3D de bulldog frances articulado negro manchas cafe | — | Sin marca registrada |
| LLA-13NPB | 1289 | Llavero 3D de bulldog frances articulado negro pecho blanco | — | Sin marca registrada |
| LLA-34 | 1314 | Llavero 3D de bulldog ingles articulado cafe con blanco | — | Sin marca registrada |
| LLA-43 | 1373 | Llavero 3D de cacatua articulado | — | Sin marca registrada |
| LLA-46 | 1377 | Llavero 3D de cardenal pecho rojo articulado | — | Sin marca registrada |
| LLA-06NC | 1212 | Llavero 3D de Chihuahua articulado negro con cafe | — | Sin marca registrada |
| LLA-06 | 1210 | Llavero 3D de Chihuahua articulado blanco | — | Sin marca registrada |
| LLA-06BC | 1211 | Llavero 3D de Chihuahua articulado blanco con cafe | — | Sin marca registrada |
| LLA-06CC | 1252 | Llavero 3D de Chihuahua articulado blanco con cafe | — | Sin marca registrada |
| LLA-06BN | 1253 | Llavero 3D de Chihuahua articulado blanco con negro | — | Sin marca registrada |
| LLA-06C | 1213 | Llavero 3D de Chihuahua articulado cafe claro | — | Sin marca registrada |
| LLA-10 | 1218 | Llavero 3D de beagle articulado | — | Sin marca registrada |
| LLA-08 | 1216 | Llavero 3D de boxer articulado | — | Sin marca registrada |
| LLA-27 | 1271 | Llavero 3D de buho articulado | — | Sin marca registrada |
| LLA-12 | 1221 | Llavero 3D de bull terrier articulado | — | Sin marca registrada |
| LLA-12BM | 1223 | Llavero 3D de bull terrier articulado blanco con mancha | — | Sin marca registrada |
| LLA-12CG | 1222 | Llavero 3D de bull terrier articulado gris con cafe | — | Sin marca registrada |
| LLA-13 | 1224 | Llavero 3D de bulldog frances articulado | — | Sin marca registrada |
| LLA-13B | 1245 | Llavero 3D de bulldog frances articulado blanco | — | Sin marca registrada |
| LLA-13BN | 1246 | Llavero 3D de bulldog frances articulado blanco mnchas negras | — | Sin marca registrada |
| LLA-13BCC | 1290 | Llavero 3D de bulldog frances articulado blanco toque cafe | — | Sin marca registrada |
| LLA-13CF | 1287 | Llavero 3D de bulldog frances articulado cafe | — | Sin marca registrada |
| LLA-13GBN | 1244 | Llavero 3D de bulldog frances articulado gris con blanco manchas negras | — | Sin marca registrada |

### 7. CC-00049 — Catálogo - Camas y colchones para gatos

43 SKUs guardados / 19 productos; 31 SKUs con incidencia esencial (72.1%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 26; Nombre con caracteres dañados: 1; Sin precio vigente: 31; Sin marca registrada: 1.
Posibles coincidencias de nombre entre productos distintos (no confirmado): [{"nombre_normalizado": "camaantiestresgrandeparamascotas", "ids_productos": [1454, 1455]}].

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| FL9861 | 1536 | Tapete refrescante extra chico aqua cool | — | Producto con variantes, SKU sin atributos de variante |
| FL9862 | 1536 | Tapete refrescante 40 � 50 cm aqua cool | — | Nombre con caracteres dañados |
| FL9863 | 1536 | Tapete refrescante mediano aqua cool | — | Producto con variantes, SKU sin atributos de variante |
| PI0101-0009 | 1455 | Cama antiestrés grande para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0101-0024 | 1455 | Cama antiestrés grande para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0101-0043 | 1455 | Cama antiestrés grande gris | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0101-0044 | 1455 | Cama antiestrés grande morada | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0101-0045 | 1455 | Cama antiestrés grande para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0101-0049 | 1455 | Cama antiestrés grande para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0101-0054 | 1455 | Cama antiestrés grande para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0101-0333 | 1455 | Cama antiestrés grande para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0101-0051 | 1454 | Cama antiestrés grande para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0102-0074 | 1454 | Cama antiestrés grande para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0101-0039 | 1452 | Cama antiestrés mediana para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0102-0068-LU | 1452 | Cama antiestrés mediana para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0102-0073 | 1452 | Cama antiestrés mediana para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0102-0073-MUL | 1452 | Cama antiestrés mediana para mascotas | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0103-0082 | 1451 | Cama day mediana azul | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0103-0085 | 1451 | Cama day mediana gris | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0103-0088 | 1451 | Cama day mediana morada | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0103-0091 | 1451 | Cama day mediana mostaza | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0103-0094 | 1451 | Cama day mediana roja | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0103-0097 | 1451 | Cama day mediana rosa | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| CAEL-02 | 1390 | Cama elevanda portatil y transpirable chica | Sin precio vigente | — |
| CAEL-01 | 1389 | Cama elevanda portatil y transpirable extrachica | Sin precio vigente | — |
| CAEL-03 | 1391 | Cama elevanda portatil y transpirable mediana | Sin precio vigente | — |
| PI0107-0101-FRUTTI | 1504 | Cama fruti kiwi grande | Sin precio vigente | — |
| PI0107-0102-FRUTTI | 1505 | Cama fruti limon chica | Sin precio vigente | — |
| PI0107-0112 | 1506 | Cama fruti toronja mediana | Sin precio vigente | — |
| PI0107-0101 | 1450 | Cama ruy chica galaxia | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0107-0102 | 1450 | Cama ruy chica multicolor | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0208-0185 | 1453 | Cama ruy grande galaxia | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0208-0189 | 1453 | Cama ruy grande multicolor | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0304-0151 | 606 | Casa cama perrito para mascotas | Sin precio vigente | Sin marca registrada |

### 8. CC-00029 — Catálogo - Accesorios para acuarios

39 SKUs guardados / 39 productos; 7 SKUs con incidencia esencial (17.9%).

Portada: ok; activa.

Conteos: Sin marca registrada: 8; Sin publicación ecommerce asociada: 8; Sin precio vigente: 7.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| PRIVO-CUNANI | 1679 | Cueva resina mini triangullo 10 x 7 x 4 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUNACH | 1680 | Cueva resina chica 12 x 10 x 6 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUNANA | 1683 | Cueva resina plana  22 x 16 x 6 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUNAGD | 1684 | Cueva resina grande 20 x 18 x 9 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUNAIA | 1681 | Cueva resina intermedia 14 x 12 x 7 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUNAMD | 1682 | Cueva resina mediana 18 x 15 x 8 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUTROCH | 1685 | Cueva tronco chico 14 x 6 x 5 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUTROMD | 1686 | Cueva tronco mediano 16 x 7.5 x 5 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| KIPH-01 | 789 | Kit medidor de ph para acuario | Sin precio vigente | — |
| FL8415 | 279 | Piedra aireadora 30 cm | Sin precio vigente | — |
| FL8414 | 278 | Piedra aireadora 25 cm | Sin precio vigente | — |
| FL8411 | 280 | Piedra aireadora 10 cm | Sin precio vigente | — |
| FL8412 | 281 | Piedra aireadora 15 cm | Sin precio vigente | — |
| FL8413 | 282 | Piedra aireadora 20 cm | Sin precio vigente | — |
| OC-500RP | 1632 | PROPELA OC-500 | Sin precio vigente | — |

### 9. CC-00046 — Catálogo - Comederos y bebederos para gatos

38 SKUs guardados / 35 productos; 23 SKUs con incidencia esencial (60.5%).

Portada: ok; activa.

Conteos: Sin precio vigente: 23; Sin marca registrada: 4.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| SH-230152 | 269 | Bebedero automático aguacate | Sin precio vigente | — |
| SH-230124 | 268 | Bebedero automático organic | Sin precio vigente | — |
| 8003507043763-BEBE | 1499 | Bebedero dispensador gri chico | Sin precio vigente | — |
| 8003507046788 | 639 | Bebedero dispensador 6,5 l | Sin precio vigente | — |
| SH-220906 | 658 | Bebedero nebul | Sin precio vigente | — |
| SH-230726-02 | 1501 | Bebedero y comedero avoca | Sin precio vigente | Sin marca registrada |
| SH-230907 | 265 | Bebedero y Comedero boreal | Sin precio vigente | — |
| SH-220313 | 660 | Doggy comedero doble con bebedero | Sin precio vigente | — |
| SH-230908 | 654 | Perla fuente de flor con comedero (rosa) | Sin precio vigente | — |
| SH-230415 | 659 | Mendel bebedero comedero | Sin precio vigente | — |
| SH-220308 | 661 | Pegaso comedero come lento interactivo | Sin precio vigente | — |
| 8003507043763-COME | 1498 | Comedero dispensador gri chico | Sin precio vigente | — |
| 8003507056795 | 640 | Comedero dispensador (gri) 6,5 kg | Sin precio vigente | — |
| SH-230730 | 218 | Bahia comedero y bebedero doble | Sin precio vigente | — |
| SH-230726-01 | 1500 | Dispensador gard azul | Sin precio vigente | Sin marca registrada |
| SH-230147 | 98 | Cometa bebedero semiautomatico | Sin precio vigente | — |
| SH-230148 | 260 | Triafan bebedero y comedero semiautomatico | Sin precio vigente | — |
| 1264 | 107 | Plato retarda ingesta 750 ml 20 x 6 cm | Sin precio vigente | — |
| SHPL-138 | 551 | Tazon circulo duke | Sin precio vigente | Sin marca registrada |
| 98850 | 715 | Tazon comida lenta cuenco chico | Sin precio vigente | — |
| 98880 | 716 | Tazon comida lenta cuenco grande | Sin precio vigente | — |
| SH-230909 | 266 | Tazón doble jake de cerámica | Sin precio vigente | — |
| 50488 | 590 | Comedero forma de gato 14 x 5 cm | Sin precio vigente | Sin marca registrada |

### 10. CC-00007 — Terrarios

36 SKUs guardados / 36 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.

Conteos: Nombre con caracteres dañados: 1; Sin marca registrada: 12.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| TERAL-15 | 1443 | Terrario torno puertas deslizables 40 � 30 � 60cm | — | Nombre con caracteres dañados |
| TEMA-T3030 | 1556 | Terrario 30 x 30 x 30 cm de madera con malla de tela | — | Sin marca registrada |
| TEMA-M3030 | 1557 | Terrario 30 x 30 x 30 cm de madera con malla metálica | — | Sin marca registrada |
| TEMA-T6060 | 998 | Terrario bellin 60 x 30 x 60 cm de madera con malla de tela | — | Sin marca registrada |
| TEMA-T3050 | 1061 | Terrario blim 30 x 30 x 50 cm de madera con malla de tela | — | Sin marca registrada |
| TEMA-M3050 | 1062 | Terrario blim 30 x 30 x 50 cm de madera con malla metálica | — | Sin marca registrada |
| TEMA-T4060 | 1311 | Terrario deli 40 x 40 x 60 cm de madera con malla de tela | — | Sin marca registrada |
| TEMA-T4090 | 997 | Terrario deyma 40 x 40 x 90 cm de madera con malla de tela | — | Sin marca registrada |
| TEMA-T50120 | 1063 | Terrario magno 50 x 50 x 120 cm de madera con malla de tela | — | Sin marca registrada |
| TEMA-M50120 | 1060 | Terrario magno 50 x 50 x 120 cm de madera con malla metálica | — | Sin marca registrada |
| TEMA-T604060 | 1310 | Terrario majes 60 x 40 x 60 cm de madera con malla de tela | — | Sin marca registrada |
| TEMA-M10080 | 1104 | Terrario toca 100 x 40 x 80 cm de madera con malla metálica | — | Sin marca registrada |
| TEMA-M8060 | 1103 | Terrario delmy 80 x 40 x 60 cm de madera con malla metálica | — | Sin marca registrada |

### 11. CC-00002 — Peceras

33 SKUs guardados / 33 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.

Conteos: Sin marca registrada: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| PERM-01 | 1102 | Pecera imen con mueble con marcos aleman | — | Sin marca registrada |

### 12. CC-00020 — Catálogo - Alimentos para peces

31 SKUs guardados / 18 productos; 2 SKUs con incidencia esencial (6.5%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 16; Sin publicación ecommerce asociada: 7; Sin marca registrada: 2; Sin precio vigente: 2.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| TP-40372 | 50 | Alimento tropical koi & goldfish basic sticks 4 kg | — | Producto con variantes, SKU sin atributos de variante |
| TP-40372-100GR | 50 | Alimento churro blanco para peces 100 gr | — | Producto con variantes, SKU sin atributos de variante |
| TP-40372-25GR | 50 | Alimento churro blanco para peces 25 gr | — | Producto con variantes, SKU sin atributos de variante |
| TP-40372-500GR | 50 | Alimento churro blanco para peces 500 gr | — | Producto con variantes, SKU sin atributos de variante |
| TP-40372-50GR | 50 | Alimento churro blanco para peces 50 gr | — | Producto con variantes, SKU sin atributos de variante |
| WA001 | 1490 | Hojuelas basicas 10 gr | — | Producto con variantes, SKU sin atributos de variante |
| WA002 | 1490 | Hojuelas basicas 20 gr | — | Producto con variantes, SKU sin atributos de variante |
| WA003 | 1490 | Hojuelas basicas 30 gr | — | Producto con variantes, SKU sin atributos de variante |
| WA004 | 1490 | Hojuelas basicas 100 gr | — | Producto con variantes, SKU sin atributos de variante |
| TP-76700 | 1622 | ALIMENTO TROPICAL FLAKES MIX 5KG. | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-76700-100GR | 1622 | ALIMENTO TROPICAL FLAKES MIX 100 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-76700-25GR | 1622 | ALIMENTO TROPICAL FLAKES MIX 25 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-76700-500GR | 1622 | ALIMENTO TROPICAL FLAKES MIX 500 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-76700-50GR | 1622 | ALIMENTO TROPICAL FLAKES MIX 50 gr | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| TP-76700-GRANEL | 1622 | ALIMENTO TROPICAL FLAKES MIX 1KG GRANEL | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| ARSAGU-ARIN | 1576 | Artemia instantanea | — | Sin marca registrada |
| ARSAGU-MASO | 1577 | Mix artemia sin oxigeno | — | Sin marca registrada |
| TP-40372-GRANEL | 50 | Alimento tropical koi & goldfish basic sticks 1 kg | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| WAT638 | 628 | Alimento para peces de fondo 70 gr | Sin precio vigente | — |

### 13. CC-00003 — Jaulas ave

31 SKUs guardados / 16 productos; 4 SKUs con incidencia esencial (12.9%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 20; Sin precio vigente: 4; Nombre con caracteres dañados: 6; Sin marca registrada: 4.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| T189-AL | 1463 | Jaula mini casa azul para pajaro | — | Producto con variantes, SKU sin atributos de variante |
| T189-AO | 1463 | Jaula mini casa naranja para pajaro | — | Producto con variantes, SKU sin atributos de variante |
| T189 | 1463 | Jaula aves cubo 23 x 23 x 28 cm | — | Producto con variantes, SKU sin atributos de variante |
| T189-VE | 1463 | Jaula mini casa verde para pajaro | — | Producto con variantes, SKU sin atributos de variante |
| T181 | 1462 | Jaula aves curva 23 x 17 x 28 cm | — | Producto con variantes, SKU sin atributos de variante |
| T181-AO | 1462 | Jaula estik amarilla para pajaros | — | Producto con variantes, SKU sin atributos de variante |
| T181-RO | 1462 | Jaula estik roja para pajaros | — | Producto con variantes, SKU sin atributos de variante |
| T181-VE | 1462 | Jaula estik verde para pajaros | — | Producto con variantes, SKU sin atributos de variante |
| B19-AL | 1465 | Jaula baral azul para pajaro | — | Producto con variantes, SKU sin atributos de variante |
| B19 | 1465 | Jaula baral amarilla  para pajaro | — | Producto con variantes, SKU sin atributos de variante |
| B19-MO | 1465 | Jaula baral morada para pajaro | — | Producto con variantes, SKU sin atributos de variante |
| B19-RO | 1465 | Jaula para pajaro cuadrada chica | — | Producto con variantes, SKU sin atributos de variante |
| B19-VE | 1465 | Jaula baral verde para pajaro | — | Producto con variantes, SKU sin atributos de variante |
| B10 | 1461 | Jaula aves colonial 30 x 23 x 29 cm | — | Producto con variantes, SKU sin atributos de variante |
| B10-AL | 1461 | Jaula colonial  azul para pajaro | — | Producto con variantes, SKU sin atributos de variante |
| B10-RO | 1461 | Jaula colonial roja para pajaro | — | Producto con variantes, SKU sin atributos de variante |
| B10-AO | 1461 | Jaula colonial amarilla para pajaro | — | Producto con variantes, SKU sin atributos de variante |
| 315A-AL | 1464 | Jaula esfera azul para pajaro | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| 315A-AO | 1464 | Jaula esfera amarilla para pajaro | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| 315A-BO | 1464 | jaula esfera blanca para pajaro | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| SP-2823 | 1042 | Jaula para aves (maxi) tipo cilindro monte verde 33 � 56 cm | — | Nombre con caracteres dañados |
| SP-2824 | 1009 | Jaula para aves tipo cilindro monte verde 33 � 70 cm | — | Nombre con caracteres dañados |
| SP-2825 | 1008 | Jaula para aves (orbe) tipo cilindro monte verde 42 � 70 cm | — | Nombre con caracteres dañados |
| PJ-01 | 327 | Jaula para pájaro casa grande | — | Sin marca registrada |
| PPJLORO | 328 | Jaula grande para loros | — | Sin marca registrada |
| 422 | 656 | Jaula aves (doble linaje) con división 58 � 40 � 40 cm | Sin precio vigente | Nombre con caracteres dañados |
| PPJAVEXG | 329 | Jaula para aves extragrande | — | Sin marca registrada |
| JAGA-PA90403D | 1333 | Jaula sefra 2 divisiones con 3 separacioens 90 x 40 x 40 cm | — | Sin marca registrada |
| SP-2819 | 1036 | Jaula para aves (monarca) monte verde sunny 46 � 36 � 66 cm | — | Nombre con caracteres dañados |
| SP-2820 | 1010 | Jaula para aves (mayor) monte verde 46 � 36 � 92 cm | — | Nombre con caracteres dañados |

### 14. CC-00040 — Catálogo - Accesorios para jaulas de aves

28 SKUs guardados / 27 productos; 24 SKUs con incidencia esencial (85.7%).

Portada: ok; activa.

Conteos: Sin marca registrada: 23; Producto con variantes, SKU sin atributos de variante: 4; Sin precio vigente: 24; Sin publicación ecommerce asociada: 7.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| NIIXCA | 1411 | Nido para aves de ixtle casa | — | Sin marca registrada |
| NIIXES | 1410 | Nido para aves de ixtle esfera | — | Sin marca registrada |
| ALA-0012 | 1550 | Palo Plastico Paradero Rustico 30cm (2piezas) | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ALA-0012-1P | 1550 | Palo Plastico Paradero Rustico 30cm (1 pieza) | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| CUCO-LIANA | 1689 | Coco lianas 100 x 2.5cm | Sin precio vigente | Sin marca registrada; Sin publicación ecommerce asociada |
| CUCO-CUVACH | 1695 | Cueva de chica 15cm de diametro x 5cm altura | Sin precio vigente | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| CUCO-CUVAEXGR | 1692 | Cueva extragrande 30cm de diametro x 12cm altura | Sin precio vigente | Sin marca registrada |
| CUCO-CUVAGR | 1693 | Cueva grande 25.5 cm de diametro x 12cm altura | Sin precio vigente | Sin marca registrada |
| CUCO-CUVAMED | 1694 | Cueva mediana 17 cm de diametro x 9.5 cm altura | Sin precio vigente | Sin marca registrada |
| CUCO-CUVACUG | 1690 | Cueva coco cuadrada grande 23 x 20 x 9cm | Sin precio vigente | Sin marca registrada |
| CUCO-CUVACUM | 1691 | Cueva coco cuadrada mediana 18 x 15 x 8cm | Sin precio vigente | Sin marca registrada |
| ART.NR1717078 | 190 | Dispensador de yute para nidos de aves | Sin precio vigente | Sin marca registrada |
| 052110 | 1657 | Juguete aros de tela con madera para aves 32 x 10 cm | Sin precio vigente | Sin publicación ecommerce asociada |
| 051953 | 1668 | Juguete colgante de algodon dos puntas para aves | Sin precio vigente | Sin publicación ecommerce asociada |
| 052066 | 1656 | Juguete colgante madera colores con nudo para aves 8 x 11cm | Sin precio vigente | Sin publicación ecommerce asociada |
| 051991 | 1663 | Juguete escalera de nudo colgante para aves y roedores | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| 052035 | 1666 | Juguete largo de yute de tres descansos para aves 45 x 15cm | Sin precio vigente | Sin publicación ecommerce asociada |
| NIDO-04 | 1382 | Nido casa chica para jaula de pajaros | Sin precio vigente | Sin marca registrada |
| NIDO-09 | 1387 | Nido casa mediana para jaula de pajaros | Sin precio vigente | Sin marca registrada |
| NIDO-03 | 1381 | Nido cilindro para jaula de pajaros | Sin precio vigente | Sin marca registrada |
| NIDO-01 | 1379 | NIdo de casa doble piso | Sin precio vigente | Sin marca registrada |
| NIDO-07 | 1385 | Nido esfera de yute chico para jaula de pajaros | Sin precio vigente | Sin marca registrada |
| NIDO-08 | 1386 | Nido esfera de yute mediano para jaula de pajaros | Sin precio vigente | Sin marca registrada |
| NIDO-02 | 1380 | Nido externo para jaula de pajaros | Sin precio vigente | Sin marca registrada |
| NIDO-05 | 1383 | Nido rectangular grande con conpuerta para jaula de pajaros | Sin precio vigente | Sin marca registrada |
| NIDO-06 | 1384 | Nido rectangular mediano con conpuerta para jaula de pajaros | Sin precio vigente | Sin marca registrada |
| 8413856514812 | 222 | Palo plastico 25 cm | Sin precio vigente | Sin marca registrada |
| CUCO-HOJA | 1688 | Pared, hoja natural de coco 52 x 32 cm | Sin precio vigente | Sin marca registrada; Sin publicación ecommerce asociada |

### 15. CC-00009 — Rascadores

28 SKUs guardados / 24 productos; 9 SKUs con incidencia esencial (32.1%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 3; Sin precio vigente: 9; Sin marca registrada: 6.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| PI0710-0262 | 1483 | Rascadero mei mediano gris | — | Producto con variantes, SKU sin atributos de variante |
| PI0709-0345 | 232 | Rascadero colgante chango | Sin precio vigente | Sin marca registrada |
| PI0709-0346 | 667 | Rascadero colgante elefante | Sin precio vigente | Sin marca registrada |
| PI0709-0347 | 238 | Rascadero colgante jirafa | Sin precio vigente | Sin marca registrada |
| PI0709-0348 | 176 | Rascadero colgante león | Sin precio vigente | Sin marca registrada |
| RAS-02 | 977 | Rascadero colgante mini pig | Sin precio vigente | Sin marca registrada |
| RAS-02VA | 976 | Rascadero colgante vaca | Sin precio vigente | Sin marca registrada |
| RAS-12 | 45 | Rascadero mei grande negro | Sin precio vigente | — |
| PI0710-0270 | 1483 | Rascadero mei mediano cafe | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| PI0710-0275 | 1483 | Rascadero mei mediano negro | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |

### 16. CC-00030 — Catálogo - Aditamentos y acondicionadores para acuarios

27 SKUs guardados / 27 productos; 2 SKUs con incidencia esencial (7.4%).

Portada: ok; activa.

Conteos: Sin precio vigente: 2.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| Q218 | 512 | Pentabiocare acondicionador  45 ml | Sin precio vigente | — |
| Q407 | 398 | Permaseptic 45 ml biomaa | Sin precio vigente | — |

### 17. CC-00041 — Catálogo - Juguetes para aves

26 SKUs guardados / 26 productos; 6 SKUs con incidencia esencial (23.1%).

Portada: ok; activa.

Conteos: Nombre con caracteres dañados: 15; Sin precio vigente: 6; Sin publicación ecommerce asociada: 5; Producto con variantes, SKU sin atributos de variante: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| SP-2852 | 1322 | Juguete para aves (3 esferas) monte verde 4 � 15 cm | — | Nombre con caracteres dañados |
| SP-2851 | 1325 | Juguete para aves (4 esferas) monte verde 4 � 23 cm | — | Nombre con caracteres dañados |
| SP-2865 | 1326 | Juguete para aves (4 espejos) monte verde 5.5 � 3.5 � 14 cm | — | Nombre con caracteres dañados |
| SP-2867 | 1315 | Juguete para aves (casa espejo) monte verde 9.2 � 5.9 cm | — | Nombre con caracteres dañados |
| SP-2857 | 1318 | Juguete para aves (circulos) monte verde 11 � 8 cm | — | Nombre con caracteres dañados |
| SP-2849 | 1324 | Juguete para aves monte verde 4.5 � 21 cm | — | Nombre con caracteres dañados |
| SP-2854 | 1320 | Juguete para aves (haro) monte verde 6 � 15 cm | — | Nombre con caracteres dañados |
| SP-2850 | 1319 | Juguete para aves (aros) monte verde 7.5 � 35 cm | — | Nombre con caracteres dañados |
| SP-2861 | 1327 | Juguete para aves (rosa espejo) monte verde 8 � 10.3 � 22 cm | — | Nombre con caracteres dañados |
| SP-2853 | 1321 | Juguete para aves (esfera) monte verde 4 � 4 cm | — | Nombre con caracteres dañados |
| SP-2856 | 1317 | Juguete para aves (espejo circular) monte verde 9 � 9 cm | — | Nombre con caracteres dañados |
| SP-2866 | 1328 | Juguete para aves espejo monte verde 7.8 � 11 cm | — | Nombre con caracteres dañados |
| SP-2859 | 1316 | Juguete para aves (flor comedero) monte verde 7 � 6.7 � 14.5 cm | — | Nombre con caracteres dañados |
| SP-2858 | 1323 | Juguete para aves (llaves) monte verde 6 � 15.5 cm | — | Nombre con caracteres dañados |
| 052110 | 1657 | Juguete aros de tela con madera para aves 32 x 10 cm | Sin precio vigente | Sin publicación ecommerce asociada |
| SP-2855 | 978 | Juguete para aves (canasta) monte verde 7.5 � 7.5 � 26 cm | — | Nombre con caracteres dañados |
| 051953 | 1668 | Juguete colgante de algodon dos puntas para aves | Sin precio vigente | Sin publicación ecommerce asociada |
| 052066 | 1656 | Juguete colgante madera colores con nudo para aves 8 x 11cm | Sin precio vigente | Sin publicación ecommerce asociada |
| FL9190 | 685 | Juguete tenis colgante para ave | Sin precio vigente | — |
| 051991 | 1663 | Juguete escalera de nudo colgante para aves y roedores | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| 052035 | 1666 | Juguete largo de yute de tres descansos para aves 45 x 15cm | Sin precio vigente | Sin publicación ecommerce asociada |

### 18. CC-00047 — Catálogo - Juguetes para gatos

26 SKUs guardados / 25 productos; 18 SKUs con incidencia esencial (69.2%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 1; Sin precio vigente: 18; Sin marca registrada: 3; Sin publicación ecommerce asociada: 5.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| FL8262-MO | 1486 | Juguete para gato cañatira morada | — | Producto con variantes, SKU sin atributos de variante |
| 13505 | 1658 | Juguete caña de pesca para gato negro | Sin precio vigente | Sin marca registrada; Sin publicación ecommerce asociada |
| FL8261 | 561 | Juguete para gatos caña de pescar catch | Sin precio vigente | — |
| FL8231 | 709 | Juguete pescado para gato ecoplay | Sin precio vigente | Sin marca registrada |
| 032440 | 1661 | Juguete chillon caballito de mar para mascotas | Sin precio vigente | Sin publicación ecommerce asociada |
| 037094 | 1660 | Juguete chillon de refresco para mascotas | Sin precio vigente | Sin publicación ecommerce asociada |
| FL9704 | 1356 | Juguete collar puntero laser para gato | Sin precio vigente | — |
| WO-128 | 383 | Juguete con sonido circulo feliz para gato | Sin precio vigente | Sin marca registrada |
| FL9226 | 900 | Juguete hongo travieso para gato | Sin precio vigente | — |
| SH-230113 | 531 | Juguete interactivo wave para gato | Sin precio vigente | — |
| FL8265 | 982 | Juguete pelota enjaulada sound | Sin precio vigente | — |
| 27684 | 1652 | Juguete peluche de jirafa para gato | Sin precio vigente | Sin publicación ecommerce asociada |
| 20251 | 1646 | Juguete pulpo de peluche para gato | Sin precio vigente | Sin publicación ecommerce asociada |
| FL8260 | 980 | Juguete raton arcoiris catch | Sin precio vigente | — |
| FL8255 | 981 | Juguete raton mini catch | Sin precio vigente | — |
| FL8281 | 192 | Juguete torre play | Sin precio vigente | — |
| FL3916 | 901 | Pasto para gatos | Sin precio vigente | — |
| SH-230104 | 451 | Tunel de tela 4 salidas colores | Sin precio vigente | — |
| SH-230105 | 452 | Tunel de tela 5 salidas colores | Sin precio vigente | — |

### 19. CC-00013 — Sustratos mamíferos roedores

26 SKUs guardados / 14 productos; 5 SKUs con incidencia esencial (19.2%).

Portada: ok; activa.

Conteos: Sin marca registrada: 4; Producto con variantes, SKU sin atributos de variante: 15; Nombre con caracteres dañados: 5; Sin precio vigente: 5; Sin publicación ecommerce asociada: 4.
Posibles coincidencias de nombre entre productos distintos (no confirmado): [{"nombre_normalizado": "sustratodevirutanatural250gr", "ids_productos": [1286, 1408]}].

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| SUSMA-03 | 1404 | Sustrato de maiz para roedores de 1 kg | — | Sin marca registrada |
| SUSP-08 | 120 | Sustrato de papel cafe para roedores de 300 gr | — | Producto con variantes, SKU sin atributos de variante |
| 417368 | 1283 | Sustrado de papel �ber blue/white paper bedding paper 36l | — | Producto con variantes, SKU sin atributos de variante; Nombre con caracteres dañados |
| SUSP-06 | 1283 | Sustrato de papel azul con blanco para roedores de 300 gr | — | Producto con variantes, SKU sin atributos de variante |
| 413285 | 1284 | Sustrato de papel �ber natural paper bedding 28l | — | Nombre con caracteres dañados |
| 416361 | 1282 | Sustrato de papel �ber pink/white paper bedding paper 36l | — | Producto con variantes, SKU sin atributos de variante; Nombre con caracteres dañados |
| SUSP-07 | 1282 | Sustrato de papel rosa con blanco para roedores de 300 gr | — | Producto con variantes, SKU sin atributos de variante |
| VITA-BOSA | 1408 | Sustrato de viruta natural | — | Sin marca registrada |
| VITA-BOSA-1KG | 1408 | Sustrato de viruta natural 1 kg | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| 222009 | 1286 | Sustrato de viruta natural �ber pine bedding 141l | — | Nombre con caracteres dañados |
| 222009-KG | 1286 | Sustrato de viruta natural libre de polvo 1 kg | — | Producto con variantes, SKU sin atributos de variante |
| 215032 | 1285 | Sustrato de viruta natural libre de polvo �ber pine bedding 24l | — | Nombre con caracteres dañados |
| KMITA10 | 532 | Sustrato Organico para roedores agranel 10 KG | — | Producto con variantes, SKU sin atributos de variante |
| KMITA10-KG | 532 | Sustrato Organico para roedores 1 KG | — | Producto con variantes, SKU sin atributos de variante |
| FL4006 | 358 | Polvo para baño chinchilla y mamíferos pequeños 1.5 kg | Sin precio vigente | — |
| CL005 | 1334 | Sustrato de papel para roedores expandible 450 gr | Sin precio vigente | — |
| 417566 | 1283 | Sustrato papel blue/white paper bedding 56 litros | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| 416569 | 1282 | Sustrato papel pink/white paper bedding 56 litros | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| VITA-BOSA-250G | 1408 | Sustrato de viruta natural 250 gr | Sin precio vigente | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| 222009-250GR | 1286 | Sustrato de viruta natural 250 gr | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| KMITA10-GRANEL | 532 | Sustrato Organico para roedores agranel 1 kg | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| 415562 | 1627 | Sustrato papel confeti paper bedding 56 litros | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |

### 20. CC-00022 — Catálogo - Decoración para acuarios

24 SKUs guardados / 24 productos; 7 SKUs con incidencia esencial (29.2%).

Portada: ok; activa.

Conteos: Sin marca registrada: 21; Sin publicación ecommerce asociada: 8; Sin precio vigente: 7.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| PBC | 1573 | Píedra mar blanca | — | Sin marca registrada |
| PDM | 1574 | Arpilla piedra de mar | — | Sin marca registrada |
| CUV-01F | 964 | Cueva de resina roctu fuego | — | Sin marca registrada |
| CUV-02V | 966 | Cueva de resina rocu | — | Sin marca registrada |
| CUV-02N | 967 | Cueva de resina rocu deser | — | Sin marca registrada |
| CUV-02 | 965 | Cueva de resina rocu fuego | — | Sin marca registrada |
| PRIVO-CUNANI | 1679 | Cueva resina mini triangullo 10 x 7 x 4 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUNACH | 1680 | Cueva resina chica 12 x 10 x 6 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUNANA | 1683 | Cueva resina plana  22 x 16 x 6 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUNAGD | 1684 | Cueva resina grande 20 x 18 x 9 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUNAIA | 1681 | Cueva resina intermedia 14 x 12 x 7 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUNAMD | 1682 | Cueva resina mediana 18 x 15 x 8 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUTROCH | 1685 | Cueva tronco chico 14 x 6 x 5 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| PRIVO-CUTROMD | 1686 | Cueva tronco mediano 16 x 7.5 x 5 cm | — | Sin marca registrada; Sin publicación ecommerce asociada |
| FIPI-03 | 972 | Piedra de resina risco | Sin precio vigente | Sin marca registrada |
| FIPI-03N | 973 | Piedra de resina risco nara | Sin precio vigente | Sin marca registrada |
| FIPI-03V | 974 | Piedra de resina risco vol | Sin precio vigente | Sin marca registrada |
| FIPI-02 | 970 | Piedra de resina roca filt | Sin precio vigente | Sin marca registrada |
| FIPI-02F | 971 | Piedra de resina roca filt fuego | Sin precio vigente | Sin marca registrada |
| FIPI-01 | 968 | Piedra de resina roca mini | Sin precio vigente | Sin marca registrada |
| FIPI-01N | 969 | Piedra de resina roca mini deser | Sin precio vigente | Sin marca registrada |

### 21. CC-00032 — Catálogo - Alimentos para aves

23 SKUs guardados / 22 productos; 3 SKUs con incidencia esencial (13.0%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 2; Sin marca registrada: 1; Sin precio vigente: 3.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| ALA-0019 | 1553 | Mosco Seco Deshidratado (Alimento Mascotas) - 1 kg | — | Producto con variantes, SKU sin atributos de variante |
| ALA-0019-30G | 1553 | Mosco Seco Deshidratado (Alimento Mascotas) - 30 g | — | Producto con variantes, SKU sin atributos de variante |
| ALA-0007 | 1303 | Panizo en Espiga Rama Natural Spray Millet - 1 kg | — | Sin marca registrada |
| FL4037 | 494 | Barrita para aves canoras alta en proteina 2 pzas | Sin precio vigente | — |
| FL4011 | 552 | Mezcla de semillas para loritos y ninfas 500 gr | Sin precio vigente | — |
| FL4012 | 91 | Mezcla de semillas para canarios y finches 500 gr | Sin precio vigente | — |

### 22. CC-00042 — Catálogo - Accesorios generales para aves

23 SKUs guardados / 22 productos; 17 SKUs con incidencia esencial (73.9%).

Portada: ok; activa.

Conteos: Sin marca registrada: 17; Producto con variantes, SKU sin atributos de variante: 4; Sin precio vigente: 17; Sin publicación ecommerce asociada: 5.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| NIIXCA | 1411 | Nido para aves de ixtle casa | — | Sin marca registrada |
| NIIXES | 1410 | Nido para aves de ixtle esfera | — | Sin marca registrada |
| ALA-0012 | 1550 | Palo Plastico Paradero Rustico 30cm (2piezas) | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ALA-0012-1P | 1550 | Palo Plastico Paradero Rustico 30cm (1 pieza) | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| CUCO-LIANA | 1689 | Coco lianas 100 x 2.5cm | Sin precio vigente | Sin marca registrada; Sin publicación ecommerce asociada |
| 52208 | 766 | Comedero Para aves con Enganche De Alambre | Sin precio vigente | — |
| CUCO-CUVACH | 1695 | Cueva de chica 15cm de diametro x 5cm altura | Sin precio vigente | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| CUCO-CUVAEXGR | 1692 | Cueva extragrande 30cm de diametro x 12cm altura | Sin precio vigente | Sin marca registrada |
| CUCO-CUVAGR | 1693 | Cueva grande 25.5 cm de diametro x 12cm altura | Sin precio vigente | Sin marca registrada |
| CUCO-CUVAMED | 1694 | Cueva mediana 17 cm de diametro x 9.5 cm altura | Sin precio vigente | Sin marca registrada |
| CUCO-CUVACUG | 1690 | Cueva coco cuadrada grande 23 x 20 x 9cm | Sin precio vigente | Sin marca registrada |
| CUCO-CUVACUM | 1691 | Cueva coco cuadrada mediana 18 x 15 x 8cm | Sin precio vigente | Sin marca registrada |
| ART.NR1717078 | 190 | Dispensador de yute para nidos de aves | Sin precio vigente | Sin marca registrada |
| 051953 | 1668 | Juguete colgante de algodon dos puntas para aves | Sin precio vigente | Sin publicación ecommerce asociada |
| 051991 | 1663 | Juguete escalera de nudo colgante para aves y roedores | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| 052035 | 1666 | Juguete largo de yute de tres descansos para aves 45 x 15cm | Sin precio vigente | Sin publicación ecommerce asociada |
| NIDO-01 | 1379 | NIdo de casa doble piso | Sin precio vigente | Sin marca registrada |
| 8413856514812 | 222 | Palo plastico 25 cm | Sin precio vigente | Sin marca registrada |
| 8413856516502 | 224 | Palo plastico 13 cm | Sin precio vigente | Sin marca registrada |
| 51461 | 223 | Palo de plástico para jualas de aves mediano | Sin precio vigente | Sin marca registrada |
| CUCO-HOJA | 1688 | Pared, hoja natural de coco 52 x 32 cm | Sin precio vigente | Sin marca registrada; Sin publicación ecommerce asociada |

### 23. CC-00025 — Catálogo - Sustratos, gravas y arenas para acuarios

23 SKUs guardados / 9 productos; 3 SKUs con incidencia esencial (13.0%).

Portada: ok; activa.

Conteos: Sin marca registrada: 5; Producto con variantes, SKU sin atributos de variante: 16; Sin publicación ecommerce asociada: 8; Sin precio vigente: 3.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| arena silica | 240 | Arena silice blanca por costal | — | Sin marca registrada |
| GRA-01 | 241 | Grava cacahuate por costal | — | Sin marca registrada |
| GRAVA-14 | 1516 | Grava para acuario espesor de 3 mm estandar color azul cielo | — | Producto con variantes, SKU sin atributos de variante |
| GRAVA-25 | 1516 | Grava para acuario espesor 3mm estandar color rojo | — | Producto con variantes, SKU sin atributos de variante |
| GRAVA-32 | 1516 | Grava para acuario espesor de 3 mm estandar color negro | — | Producto con variantes, SKU sin atributos de variante |
| PPGRAN10K | 242 | Grava natural grano de oro 10 kg | — | Sin marca registrada |
| GRAVA-1 | 1515 | Grava para acuario espesor de 3 mm color confetti neon | — | Producto con variantes, SKU sin atributos de variante |
| GRAVA-1-GRANEL | 1515 | Grava para acuario espesor de 3 mm color confetti neon 1 kg GRANEL | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| GRAVA-2 | 1515 | Grava para acuario espesor de 3 mm color fiusha neon | — | Producto con variantes, SKU sin atributos de variante |
| GRAVA-2-GRANEL | 1515 | Grava para acuario espesor de 3 mm color fiusha neon 1 kg GRANEL | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| GRAVA-4 | 1515 | Grava para acuario espesor de 3 mm color naranja neon | — | Producto con variantes, SKU sin atributos de variante |
| GRAVA-4-GRANEL | 1515 | Grava para acuario espesor de 3 mm color naranja neon 1 kg GRANEL | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| GRAVA-5 | 1515 | Grava para acuario espesor de 3 mm color saturno neon | — | Producto con variantes, SKU sin atributos de variante |
| GRAVA-5-GRANEL | 1515 | Grava para acuario espesor de 3 mm color saturno neon 1 kg GRANEL | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| GRAVA-6 | 1515 | Grava para acuario espesor de 3 mm color verde neon | — | Producto con variantes, SKU sin atributos de variante |
| GRAVA-6-GRANEL | 1515 | Grava para acuario espesor de 3 mm color verde neon 1 kg GRANEL | — | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| PBC | 1573 | Píedra mar blanca | — | Sin marca registrada |
| PDM | 1574 | Arpilla piedra de mar | — | Sin marca registrada |
| GRAVA-14-GRANEL | 1516 | Grava para acuario espesor de 3 mm estandar color azul cielo 1 kg GRANEL | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| GRAVA-25-GRANEL | 1516 | Grava para acuario espesor 3mm estandar color rojo 1 kg GRANEL | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |
| GRAVA-32-GRANEL | 1516 | Grava para acuario espesor de 3 mm estandar color negro 1 kg GRANEL | Sin precio vigente | Producto con variantes, SKU sin atributos de variante; Sin publicación ecommerce asociada |

### 24. CC-00045 — Catálogo - Alimentos para gatos

18 SKUs guardados / 14 productos; 4 SKUs con incidencia esencial (22.2%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 8; Sin precio vigente: 4; Sin publicación ecommerce asociada: 3; SKU no activo: 3.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| 610877 | 1124 | Lata Nupec Felino Hairball (Gato bola de pelo) con Pescado Alimento húmedo 100 gr | — | Producto con variantes, SKU sin atributos de variante |
| NUEC-CLERFEHALL | 1124 | Cluster Rack Nupec Alimento Húmedo Felino Hairball (Gato bola de pelo) con Pescado 16 Latas 100 gr c/u | — | Producto con variantes, SKU sin atributos de variante |
| 619875 | 1122 | Lata Nupec Felino Indoor (Gato interior) con Carne y Pollo Alimento húmedo 100 gr | — | Producto con variantes, SKU sin atributos de variante |
| NUEC-CLERFEOR | 1122 | Cluster Rack Nupec Alimento Húmedo Felino Indoor (Gato interior) con Carne y Pollo 16 Latas 100 gr c/u | — | Producto con variantes, SKU sin atributos de variante |
| 610879 | 1123 | Lata Nupec Felino Urinary (Gato urinario) con Pescado y Arándanos Alimento húmedo 100 gr | — | Producto con variantes, SKU sin atributos de variante |
| NUEC-CLERFEURRY | 1123 | Cluster Rack Nupec Alimento Húmedo Felino Urinary (Gato problema urinario) con Pescado y Arándanos 16 Latas 100 gr c/u | — | Producto con variantes, SKU sin atributos de variante |
| LAT-07 | 1126 | Lata Nupec Felino Kitten (Gatito) con Carne y Pollo Alimento húmedo | — | Producto con variantes, SKU sin atributos de variante |
| NUEC-CLERFEEN | 1126 | Cluster Rack Nupec Alimento Húmedo KITTEN (Gatito) con Carne y Pollo 16 Latas 100 gr c/u | — | Producto con variantes, SKU sin atributos de variante |
| ALTODOPLUS-CARTUN | 1640 | Alimento sobre minino plus carnitas de atún 85 g | Sin precio vigente | Sin publicación ecommerce asociada; SKU no activo |
| ALTODOPLUS-PORAS | 1643 | Alimento sobre minino plus pollo en caldo con verduras 85 g | Sin precio vigente | Sin publicación ecommerce asociada; SKU no activo |
| ALTODOPLUS-SALCHA | 1644 | Alimento sobre minino plus salmón a la plancha 85 g | Sin precio vigente | Sin publicación ecommerce asociada; SKU no activo |
| CGDCA | 1149 | Diamond Croqueta Maintenance Cat Costal Adultos 18.14 kg (40 LBS) | Sin precio vigente | — |

### 25. CC-00026 — Catálogo - Calefacción para acuarios

17 SKUs guardados / 17 productos; 3 SKUs con incidencia esencial (17.6%).

Portada: ok; activa.

Conteos: Sin publicación ecommerce asociada: 1; Sin precio vigente: 3.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| OCT-50 | 1635 | TERMOSTATO ECONOMICO 50W | — | Sin publicación ecommerce asociada |
| FL8601 | 152 | Calentador sumergible con termostato ecothermal 25 w | Sin precio vigente | — |
| FL8602 | 154 | Calentador sumergible con termostato ecothermal 50 w | Sin precio vigente | — |
| FL8603 | 518 | Calentador sumergible con termostato ecothermal 75 w | Sin precio vigente | — |

### 26. CC-00011 — Bases y muebles para peceras

17 SKUs guardados / 16 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.

Conteos: Sin marca registrada: 14; Producto con variantes, SKU sin atributos de variante: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| BAROSA-PD4040 | 129 | Base cubo 40 x 40 x 70 cm altura | — | Sin marca registrada |
| BAROSA-PD3030 | 202 | Base cubo 30 x 30 x 70 cm altura | — | Sin marca registrada |
| BAROSA-PD2020 | 125 | base cubo mini 22 x 22 x 70 cm altura | — | Sin marca registrada |
| BAROSA-PDH3838 | 134 | Base hexagonal 70 cm altura | — | Sin marca registrada |
| BAROSA-PDP5237 | 127 | Base panorámica  4 patas 52 x 37 x 70 cm altura | — | Sin marca registrada |
| BAROSA-5PDP5237 | 126 | Base panoramica 5 patas 52 x 37 x 70 cm altura | — | Sin marca registrada |
| BAROSA-P100 | 130 | Base rectangular 1 m x 26 cm x 70 cm altura | — | Sin marca registrada |
| BAROSA-P112P12030 | 128 | Base rectangular 120 x 30 x 70 cm altura | — | Sin marca registrada |
| BAROSA-PD4020 | 593 | Base rectangular 40 x 20 x 70 cm altura | — | Sin marca registrada |
| BAROSA-P112P6030 | 133 | Base rectangular 60 x 30 x 70 cm altura | — | Sin marca registrada |
| BAROSA-PD3015 | 1444 | Base rectangular de herreria para pecera de 30 x 15 cm con diseño de lineas rectas | — | Sin marca registrada |
| BAROSA-PD3015H | 1444 | Base rectangular de herreria para pecera de 30 x 15 x 70 cm altura diseño de hondas | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| BAROSA-PD5026 | 131 | Base rectangular para pecera 40lts 50 x 26 x 70 cm altura | — | Sin marca registrada |
| PERM-01 | 1102 | Pecera imen con mueble con marcos aleman | — | Sin marca registrada |

### 27. CC-00028 — Catálogo - Iluminación para acuarios

17 SKUs guardados / 16 productos; 2 SKUs con incidencia esencial (11.8%).

Portada: ok; activa.

Conteos: Sin precio vigente: 2; Producto con variantes, SKU sin atributos de variante: 2.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| FL7980 | 1523 | Lampara negra led nano 1.5 w | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| FL7981 | 1523 | Lampara blanca led nano 1.5 w | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |

### 28. CC-00012 — Arenas para gatos

13 SKUs guardados / 13 productos; 2 SKUs con incidencia esencial (15.4%).

Portada: ok; activa.

Conteos: Sin precio vigente: 2.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| SP-3739 | 1624 | Arena para gatos aroma lavanda (bolsa 20kg) bentonita super aglutinante y 99% libre de polvo | Sin precio vigente | — |
| SP-3738 | 1625 | Arena para gatos sin aroma (bolsa 20kg) bentonita super aglutinante y 99% libre de polvo | Sin precio vigente | — |

### 29. CC-00006 — Tortugueros

12 SKUs guardados / 12 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.


| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|

### 30. CC-00035 — Catálogo - Bebederos para aves

12 SKUs guardados / 12 productos; 8 SKUs con incidencia esencial (66.7%).

Portada: ok; activa.

Conteos: Sin precio vigente: 8.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| 8413856510371 | 651 | Bebedero tubular 2 | Sin precio vigente | — |
| 8413856511378 | 652 | Bebedero tubular 3 | Sin precio vigente | — |
| 8413856510173 | 258 | Bebebero tubo no.4 85 ml | Sin precio vigente | — |
| 8413856516373 | 257 | Bebedero tubo no.5 225 ml | Sin precio vigente | — |
| 8413856519275 | 655 | Bebedero tubular 6 | Sin precio vigente | — |
| 8413856520370B | 675 | Bebedero tubular 7 | Sin precio vigente | — |
| 8413856520400 | 676 | Bebedero tubular 8 | Sin precio vigente | — |
| 8413856520509 | 677 | Bebedero tubular 9 | Sin precio vigente | — |

### 31. CC-00044 — Catálogo - Premios y snacks para gatos

12 SKUs guardados / 8 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 5.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| USA654 | 1141 | Bote churu 4 sabores 50 pzs x 14 gr 700 gr | — | Producto con variantes, SKU sin atributos de variante |
| USA654-TUNA | 1141 | Churu premios para gato receta de atún | — | Producto con variantes, SKU sin atributos de variante |
| USA654-TUNACLAM | 1141 | Churu premios para gato receta de atún con sabor a almeja | — | Producto con variantes, SKU sin atributos de variante |
| USA654-TUNACRAB | 1141 | Churu premios para gato receta de atún con sabor a cangrejo | — | Producto con variantes, SKU sin atributos de variante |
| USA654-TUNASCALLOP | 1141 | Churu premios para gato receta de atún con vieras | — | Producto con variantes, SKU sin atributos de variante |

### 32. CC-00034 — Catálogo - Bebederos y aditamentos para colibrí

11 SKUs guardados / 11 productos; 7 SKUs con incidencia esencial (63.6%).

Portada: ok; activa.

Conteos: Sin precio vigente: 7.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| FL9231 | 840 | Bebedero de vidrio para colibri 590 ml | Sin precio vigente | — |
| FL9243 | 838 | Bebedero ch para colibries 125 ml | Sin precio vigente | — |
| FL9221 | 690 | Bebedero para colibri con nectar 500 ml | Sin precio vigente | — |
| FL9252 | 740 | Bebedero tulipan para colibri 90 ml | Sin precio vigente | — |
| FL9216 | 237 | Néctar liquido para colibri 500 ml | Sin precio vigente | — |
| FL9218 | 235 | Nectar liquido para colibri 1.5 litros | Sin precio vigente | — |
| FL9228 | 227 | Nectar sin color para colibri 1.5 litros | Sin precio vigente | — |

### 33. CC-00010 — Bombas sumergibles

10 SKUs guardados / 10 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.


| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|

### 34. CC-00004 — Corrales metálicos

9 SKUs guardados / 9 productos; 1 SKUs con incidencia esencial (11.1%).

Portada: ok; activa.

Conteos: Sin marca registrada: 3; Seleccionado pero no visible por estado o referencia faltante: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| JAGA-CO046040 | 1331 | Corral galvanizado 4 panel de 60 x 40 cm 3 cm y 1.5 cm separacion | — | Sin marca registrada |
| JAGA-CO066040 | 1165 | Corral galvanizado 6 panel de 60 x 40 cm 3 cm y 1.5 cm separacion | — | Sin marca registrada |
| JAM-04 | 1517 | Jaula corral gala 4 paneles con tapa | — | Sin marca registrada |
| CORRA-08 | 1330 | No visible en el visor | Seleccionado pero no visible por estado o referencia faltante | — |

### 35. CC-00039 — Catálogo - Alimento vivo para aves

9 SKUs guardados / 2 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.

Conteos: Sin marca registrada: 9; Producto con variantes, SKU sin atributos de variante: 9.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| ALI-TEBRIO1K | 604 | Alimento vivo larva tenebrio (gusano de harina) 1 kg | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ALI-TEBRIO1K-1000P | 604 | Alimento vivo larva tenebrio (gusano de harina) 1000 pzs | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ALI-TEBRIO1K-100P | 604 | Alimento vivo larva tenebrio (gusano de harina) 100 pzs | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ALI-TEBRIO1K-25P | 604 | Alimento vivo larva tenebrio (gusano de harina) 25 pzs | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ALI-TEBRIO1K-50P | 604 | Alimento vivo larva tenebrio (gusano de harina) 50 pzs | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ALI-TEBRIO1K-PZA | 604 | Alimento vivo larva tenebrio (gusano de harina) pieza | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ALI-ZOBA1000PZ | 1563 | Alimento vivo larva de zophoba (gusano rey) millar | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ALI-ZOBA1KG | 1563 | Alimento vivo larva de zophoba (gusano rey) 1 kg | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| ALI-ZOBAPZ | 1563 | Alimento vivo larva de zophoba (gusano rey) pieza | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |

### 36. CC-00037 — Catálogo - Premios y snacks para aves

8 SKUs guardados / 7 productos; 5 SKUs con incidencia esencial (62.5%).

Portada: ok; activa.

Conteos: Producto con variantes, SKU sin atributos de variante: 2; Sin precio vigente: 5.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| ALA-0019 | 1553 | Mosco Seco Deshidratado (Alimento Mascotas) - 1 kg | — | Producto con variantes, SKU sin atributos de variante |
| ALA-0019-30G | 1553 | Mosco Seco Deshidratado (Alimento Mascotas) - 30 g | — | Producto con variantes, SKU sin atributos de variante |
| FL4032 | 194 | Barrita de semillas para canarios y finches 2 pz | Sin precio vigente | — |
| FL4036 | 490 | Barrita de mijo para aves 1 pz | Sin precio vigente | — |
| FL4031 | 187 | Barrita de semillas para loritos y ninfas 2 pz | Sin precio vigente | — |
| FL4033 | 1497 | Barrita de semillas para loros y cacatuas 1 pz | Sin precio vigente | — |
| FL4037 | 494 | Barrita para aves canoras alta en proteina 2 pzas | Sin precio vigente | — |

### 37. CC-00019 — Catálogo - Alimentos para betta

7 SKUs guardados / 7 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.


| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|

### 38. CC-00021 — Catálogo - Peces de acuario

7 SKUs guardados / 1 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.

Conteos: Sin marca registrada: 7; Producto con variantes, SKU sin atributos de variante: 2.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| PEZMN-01 | 1533 | Pez monja neón roja | — | Sin marca registrada |
| PEZMN-02 | 1533 | Pez monja neón verde | — | Sin marca registrada |
| PEZMN-03 | 1533 | Pez monja neón rosa | — | Sin marca registrada |
| PEZMN-04-AL | 1533 | Pez monja azul | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| PEZMN-04-AO | 1533 | Pez monja neón amarilla | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| PEZMN-05 | 1533 | Pez monja blanca | — | Sin marca registrada |
| PEZMN-06 | 1533 | Pez monja negra | — | Sin marca registrada |

### 39. CC-00033 — Catálogo - Suplementos y vitaminas para aves

6 SKUs guardados / 6 productos; 1 SKUs con incidencia esencial (16.7%).

Portada: ok; activa.

Conteos: Sin precio vigente: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| 7503003098508 | 331 | Multivitamínico pajaro 60 ml | Sin precio vigente | — |

### 40. CC-00038 — Catálogo - Alimento deshidratado para aves

6 SKUs guardados / 3 productos; 4 SKUs con incidencia esencial (66.7%).

Portada: ok; activa.

Conteos: Sin marca registrada: 4; Producto con variantes, SKU sin atributos de variante: 6; Sin precio vigente: 2; Sin imagen registrada: 2.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| 24A07 | 1554 | Tenebrio Molitor (Gusano De La Harina) Deshidratado - Dehydrated mealworms - 500g | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| 24A07-30G | 1554 | Tenebrio Molitor (Gusano De La Harina) Deshidratado - Dehydrated mealworms - 30 g | — | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| FL4046 | 1524 | Redkite larva de mosca soldado 30 gr | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| FL4070 | 1524 | Reptilia larva de mosca soldado 30 gr | Sin precio vigente | Producto con variantes, SKU sin atributos de variante |
| 26A31 | 1552 | Larva de Mosca Soldado Negro Deshidratada (Hermetia illucens) - Black Soldier Fly Larvae - 500g | Sin imagen registrada | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |
| 26A31-30G | 1552 | Larva de Mosca Soldado Negro Deshidratada (Hermetia illucens) - Black Soldier Fly Larvae - 30 g | Sin imagen registrada | Sin marca registrada; Producto con variantes, SKU sin atributos de variante |

### 41. CC-00036 — Catálogo - Comederos para aves

5 SKUs guardados / 5 productos; 1 SKUs con incidencia esencial (20.0%).

Portada: ok; activa.

Conteos: Sin precio vigente: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| 52208 | 766 | Comedero Para aves con Enganche De Alambre | Sin precio vigente | — |

### 42. CC-00018 — Catálogo - Alimentos para ajolotes

4 SKUs guardados / 4 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.


| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|

### 43. CC-00024 — Catálogo - Raíces y troncos para acuarios

4 SKUs guardados / 4 productos; 4 SKUs con incidencia esencial (100.0%).

Portada: ok; activa.

Conteos: Sin imagen registrada: 4; Sin marca registrada: 4; Sin precio vigente: 1; Sin publicación ecommerce asociada: 1; SKU no activo: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| JIR-CH | 1586 | Raíz tronco de manzanita chico | Sin imagen registrada | Sin marca registrada |
| JIR-MD | 1585 | Raíz tronco de manzanita mediano | Sin imagen registrada | Sin marca registrada |
| JIR-MINI | 1587 | Raíz tronco de manzanita mini | Sin imagen registrada | Sin marca registrada |
| JIR-GD | 1584 | Raíz tronco de manzanita grande | Sin precio vigente; Sin imagen registrada | Sin marca registrada; Sin publicación ecommerce asociada; SKU no activo |

### 44. CC-00031 — Catálogo - Repuestos para peceras

4 SKUs guardados / 4 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.


| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|

### 45. CC-00048 — Catálogo - Interactivos y enriquecimiento para gatos

4 SKUs guardados / 4 productos; 3 SKUs con incidencia esencial (75.0%).

Portada: ok; activa.

Conteos: Sin precio vigente: 3; Sin publicación ecommerce asociada: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| 21445 | 1655 | Catnip tubular para gatos 40 ml | Sin precio vigente | Sin publicación ecommerce asociada |
| SH-230102 | 372 | Tunel de tela 2 salidas | Sin precio vigente | — |
| SH-230106 | 371 | Tunel de tela 6 salidas | Sin precio vigente | — |

### 46. CC-00023 — Catálogo - Plantas artificiales para acuarios

3 SKUs guardados / 3 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.


| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|

### 47. CC-00043 — Catálogo - Transportadoras para aves

3 SKUs guardados / 3 productos; 3 SKUs con incidencia esencial (100.0%).

Portada: ok; activa.

Conteos: Sin precio vigente: 3; Sin marca registrada: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| MOC-08 | 1027 | Maletin mochila huella chica | Sin precio vigente | Sin marca registrada |
| SH-220339 | 623 | Mochila liberty para mascotas | Sin precio vigente | — |
| 96190 | 714 | Transportadora mini gulliver | Sin precio vigente | — |

### 48. CC-00015 — Catálogo - Alimentadores para peces

2 SKUs guardados / 2 productos; 0 SKUs con incidencia esencial (0.0%).

Portada: ok; activa.


| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|

### 49. CC-00017 — Catálogo - Alimentos de fondo para peces

2 SKUs guardados / 2 productos; 1 SKUs con incidencia esencial (50.0%).

Portada: ok; activa.

Conteos: Sin precio vigente: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| WAT638 | 628 | Alimento para peces de fondo 70 gr | Sin precio vigente | — |

### 50. CC-00050 — Catálogo - Tazones para gatos

2 SKUs guardados / 2 productos; 1 SKUs con incidencia esencial (50.0%).

Portada: ok; activa.

Conteos: Sin precio vigente: 1; Sin marca registrada: 1; Sin publicación ecommerce asociada: 1.

| SKU | Producto ID | Nombre | Incidencias esenciales | Observaciones |
|---|---:|---|---|---|
| 26669 | 1654 | Tazon chico plato morza | Sin precio vigente | Sin marca registrada; Sin publicación ecommerce asociada |

