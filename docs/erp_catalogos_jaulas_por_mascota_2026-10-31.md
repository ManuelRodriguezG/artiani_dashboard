# Catálogos de jaulas por mascota — 31 de octubre de 2026

IA: Codex GPT-6. Solicitud del dueño: crear catálogos específicos por mascota y detectar jaulas pendientes de categoría.

Se guardaron siete borradores usando `CatalogoErpDatos::guardarCatalogoComercial`; se releen por `consultarCatalogoComercial` y se verifica el conjunto exacto de SKUs, título y agrupación. Los 50 catálogos previos y sus ítems permanecen sin cambios. No se cambiaron productos, SKUs, relaciones de categorías, inventario, precios, costos ni código del módulo.

| Código | Catálogo | Productos | SKUs |
|---|---|---:|---:|
| CC-00051 | Catálogo - Jaulas para conejos | 9 | 21 |
| CC-00052 | Catálogo - Jaulas para cuyos | 9 | 21 |
| CC-00053 | Catálogo - Jaulas para erizos | 9 | 21 |
| CC-00054 | Catálogo - Jaulas para chinchillas | 7 | 15 |
| CC-00055 | Catálogo - Jaulas para hurones | 6 | 8 |
| CC-00056 | Catálogo - Jaulas para perros | 3 | 3 |
| CC-00057 | Catálogo - Jaulas para gatos | 1 | 1 |

Todos los seleccionados tienen foto y precio. Se mantienen variantes agrupadas y todos los SKUs. Las categorías de hábitat también contienen accesorios: se excluyeron de estas nuevas selecciones cinco productos distintos (esquinero, bebedero, esfera, tapa de corral y corral); no se corrigieron sus relaciones.

## Pendientes de clasificación

| SKU | Producto | Motivo |
|---|---|---|
| 10277 | Jaula amper chica 80 cm | Sólo categoría general |
| 10276 | Jaula para mamífero colores | Sólo categoría general |
| 10272 | Jaula para mamífero 3 niveles | Sólo categoría general |
| PPJCONEXG | Jaula para mamífero reja abajo | Sólo categoría general |
| 10273 | Jaula para roedor 5 niveles | Sólo categoría general |
| SH-230134 | Jaula casa alta para mascotas | Sin categoría específica de jaulas por mascota |
| SH-230601 | Jaula casa baja para mascotas | Sin categoría específica de jaulas por mascota |
| 10284 | Jaula amper grande 120cm | Sólo categoría general |
| 10278 | Jaula amper mediana 100cm | Sólo categoría general |
| 0080 | Jaula hamster solaz azul | Sin categoría específica de jaulas por mascota |
| 50110 | Jaula hamster indi verde | Sin categoría específica de jaulas por mascota |
| JAGA-JACHA046060 | Jaula galvanizada 4 panel de 60 x 60 cm 3 cm separacion con tapa y charola galvanizada | Sólo categoría general |

## Selección pendiente en catálogos preexistentes

| Catálogo | SKU | Nombre |
|---|---|---|
| Hámster | SP-3644 | Jaula hamster cafe 35 x26 x 33 cm |
| Hámster | SP-3645 | Jaula hamster rosa 36 x 26 x 33 cm |
| Hámster | DY415B | Jaula san diego para roedor (tubu) |
| Hámster | DYR3H | Jaula para mamíferos amsterdam III para huron |
| Aves | PPJAVEG | Jaula 2 aguas grande para aves |

No se dedujo compatibilidad por especie: se respetó la clasificación existente. Revisar DYR3H, cuyo nombre dice hurón y está también categorizado en Hámster.

## Presentación y continuidad

Portada de plantilla limpia por catálogo, fecha Octubre 2026, contacto comercial existente y aviso «Precios y disponibilidad sujetos a cambios sin previo aviso». Pendiente arte específico y logotipo; no se reutilizó la portada de hámster. Al añadir categorías a productos, recargar búsqueda y actualizar la selección del catálogo correspondiente: la selección guardada no incorpora SKUs automáticamente.

La comprobación visual en navegador quedó pendiente porque la sesión actual redirige a login. La persistencia sí se verificó con el modelo de consulta del editor.

Respaldo previo externo: `C:/xampp/panel_db_backups/productivo_panel_20261031_jaulas_por_mascota_045403.sql`. SHA-256: `f07ea95f8b95f16b2b6446be90ce5c6391cba18be15740bb933e7a6dde2cd3fb`. Incluye las tres tablas comerciales afectadas; sin DDL.

Reporte detallado, planes, snapshot y resultados en `C:/Users/aleja/.codex/visualizations/2026/10/15/01a140f4-02af-7a92-a436-b067bec4a771/jaulas_por_mascota_2026-10-31/`.


## Actualizacion 2026-11-01 — portadas recibidas

Se asignaron las seis portadas finales de conejos, cuyos, erizos, chinchillas (exclusivamente v2), hurones y perros. Solo queda pendiente CC-00057, Jaulas para gatos. Detalle, respaldo y verificaciones: `docs/erp_catalogos_comerciales_portadas_jaulas_2026-11-01.md`. Productos, categorias y selecciones sin modificaciones.
