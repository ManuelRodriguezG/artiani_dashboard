# ERP Catalogo - Auditoria del arbol transversal y Ecommerce

Documentacion IA: Codex GPT-5  
Fecha: 2026-10-23  
Proyecto: `C:\xampp\htdocs\panel_de_control`  
Estado: auditoria read-only y propuesta; no se aplican cambios de BD ni relaciones.

## Alcance y fuente de evidencia

Esta auditoria revisa el arbol de categorias, sus relaciones con productos y el contrato publico de Ecommerce para decidir si hacen falta categorias transversales.

Fuentes revisadas:

- `docs/erp_catalogo_avance.md`.
- `docs/erp_catalogo_categorias_arbol_1_6_refinado.md`.
- `docs/erp_catalogo_categorias_faltantes_recurrentes.md`.
- `docs/erp_catalogo_categorias_migracion_relaciones_plan.md`.
- `docs/erp_catalogo_categorias_sugeridas_auditoria.md`.
- `docs/erp_ecommerce_publico_seo_frontend.md`.
- `app/controladores/CatalogoErp.php`.
- `app/modelos/CatalogoErpDatos.php`.
- `app/modelos/CatalogoErpEsquema.php`.
- `app/modelos/CatalogoErpOrganizacion.php`.
- `app/controladores/EcommercePublico.php`.
- `app/modelos/EcommerceCatalogoPublico.php`.
- `app/vistas/paginas/apps/erp/catalogo/configuracion.php`.
- `public/assets/js/custom/apps/erp/catalogo/configuracion.js`.

El preflight read-only ejecutado el 2026-10-23 no pudo abrir la conexion MySQL del entorno actual: `CRUD::getConexion()` devolvio `null`. Por seguridad no se intentaron credenciales alternativas ni consultas directas. Las cifras de esta entrega son la ultima evidencia read-only documentada y deben reconfirmarse antes de preparar cualquier apply.

## Estado auditado del arbol

La evidencia documentada mas reciente reporta:

- 438 categorias totales.
- 192 categorias activas del arbol operativo `CAT16-*`.
- 246 categorias historicas o heredadas inactivadas, conservadas para trazabilidad.
- La auditoria estructural reporto 0 textos dañados, 0 padres inexistentes, 0 codigos duplicados, 0 nombres duplicados bajo el mismo padre y 0 rutas inconsistentes.
- El arbol operativo se organiza en seis raices: `Perros`, `Gatos`, `Acuario y peces`, `Reptiles y tortugas`, `Aves` y `Pequenos mamiferos`.
- Las cinco categorias faltantes recurrentes ya fueron creadas: terrarios generales, animales vivos de pequenos mamiferos, raices y troncos, sustratos de reptiles y habitat/jaulas generales de pequenos mamiferos.
- Al cierre del lote asistido 04: 221 relaciones producto-categoria, 155 productos con categoria principal y 1452 productos activos/no fusionados sin categoria principal.

Lectura operativa: el problema principal ya no es la integridad tecnica del arbol, sino decidir el alcance correcto de productos multi-especie y terminar la clasificacion con evidencia. La cifra de relaciones y productos sin categoria es historica; el siguiente paso obligatorio es volver a ejecutar los scripts read-only cuando la conexion este disponible.

## Actualizacion productiva 2026-10-24

La auditoria se repitio contra la conexion productiva usando solo consultas read-only.

### Conteos actuales

- 447 categorias totales: 372 maestras y 75 legadas/canal.
- 197 activas y 250 inactivas.
- 103 raices; 370 categorias permiten productos y 77 son estructurales.
- 0 textos dañados, 0 padres inexistentes, 0 codigos duplicados y 0 rutas inconsistentes.
- 1 nombre duplicado bajo el mismo padre: `Aves`; uno es historico inactivo (`CLAS-HIST-5`) y otro es el activo `CAT16-AVES`.
- 2200 relaciones producto-categoria y 1504 productos con categoria principal.
- 227 productos no fusionados sin categoria principal en la auditoria de relaciones; el auditor de productos activos excluye inactivos y reporta 1504 productos, de los cuales 56 no tienen categoria principal.
- No hay productos con multiples categorias principales.

### Raices activas

El arbol activo contiene las seis familias previstas, pero tambien una raiz adicional:

- `Acuario y peces`.
- `Aves`.
- `Gatos`.
- `Pequenos mamiferos`.
- `Perros`.
- `Reptiles, anfibios e invertebrados`.
- `Accesorios y productos generales`.

La raiz `Accesorios y productos generales` permite asignacion directa y tiene una categoria hija `Llaveros de mascotas` con 109 relaciones. Debe revisarse como rama comercial general/promocional, no mezclarse automaticamente con el arbol de cuidado de mascotas. No se recomienda inactivarla sin revisar sus relaciones.

### Hojas asignables activas sin relaciones

Se detectaron ocho hojas que pueden recibir productos pero hoy no tienen relaciones: plantas acuaticas, peceras para betta, sustratos y fondos de aves, contenedores de gatos, casas de gatos y premios de Chinchilla, Conejo y Cuyo. No son categorias faltantes por si mismas; son categorias validas que deben conservarse hasta confirmar si el inventario actual realmente las necesita.

### Oportunidades derivadas de productos actuales

| Oportunidad | Evidencia productiva | Decision |
| --- | --- | --- |
| Salud e higiene general multi-especie | `MS JABON ANTISEPTICO 125 ML para perros, gatos y pequeños mamíferos`; el sugeridor agrupa 24 productos en higiene/salud compartida | Crear como candidata transversal, sin aplicar; excluir medicamentos o tratamientos con especie/dosis especifica |
| Habitat y jaulas generales de pequeños mamíferos | `CAT16-MAMIFEROS-HAB-GENERAL` tiene 45 relaciones | Conservar; ya esta validada por uso real |
| Habitat y jaulas generales multi-especie | Transportadoras/jaulas con nombre generico y mascotas no especificadas | Evaluar despues; no ampliar la categoria de pequeños mamíferos silenciosamente |
| Transporte multi-especie | 3 productos activos sin principal en el grupo `Transportadoras`, incluyendo `Maletin mochila liu` | Candidata secundaria; primero confirmar especie, tipo de transporte y si es para perro/gato/varias especies |
| Alimentos vivos o deshidratados por uso | `Tenebrio`, `Zophoba`, larvas y alimento para grillos aparecen mezclados entre Aves, Reptiles, Acuario y pequeños mamíferos | No crear aun; separar alimento para la mascota de alimento para el insecto/alimento vivo y decidir si la transversalidad es comercial o solo atributo |
| Promocionales y regalos | 109 llaveros de mascotas fuera del arbol `CAT16-*` | Mantener una rama comercial separada; no crear mas categorias de mascota para estos productos |

## Productos que requieren aclaracion humana

Estos nombres no permiten decidir con seguridad la categoria principal solo con el texto. No se cambiaron.

- `FOHRS-SAAL` - `Salvado natural`: confirmar si es alimento para aves, roedores, reptiles, insectos o insumo.
- `ECOM-1454` y `ECOM-1453` - `Jaula hamster indi/solaz`: parecen Hamster / Habitat y jaulas, pero confirmar que son jaulas y no modelos historicos.
- `ECOM-1441` - `Rocaliente para reptiles`: confirmar si es piedra calefactora, decoracion o accesorio electrico.
- `ECOM-1376` - `Comedero de aves exterior con tapa roja`: confirmar si es comedero general para aves o alimentador para fauna silvestre.
- `ECOM-1306` y `ECOM-1305` - `Shampoo para erizo` y `Antiacaros eri-clean`: confirmar si siguen vigentes y si requieren una subcategoria especifica de salud para Erizo.
- `ECOM-1166` - `Alimento mini palitos para tortuga`: confirmar especie de tortuga y si corresponde a alimento de tortugas acuaticas o terrestres.
- `ECOM-1139` - `Larva de mosca soldado para peces`: confirmar si es alimento directo para peces o alimento para criar/engordar otro animal.
- `ECOM-1092` - `Piedra de resina nucleo`: confirmar si es decoracion de acuario, refugio de reptil o producto ornamental general.
- `ECOM-1062` - `Arena de baño one para mamíferos`: confirmar si es para Chinchilla, Hamster u otra especie.
- `ECOM-1050` - `Casa igloo grande para cuyo`: confirmar que sea habitat de Cuyo y no casa multi-especie.
- `TMP-PROV-14485` - `Bonsai frondoso 22X5X13`: confirmar si es decoracion de acuario, planta artificial o decoracion general.
- `TMP-PROV-14268` - `Esponja Biogarden amarilla` y `TMP-PROV-14255` - `Material filtrante verde`: confirmar si son medios filtrantes para acuario y su funcion tecnica.
- `TMP-PROV-14237` - `Maternidad flotante`: confirmar si es maternidad para peces y si debe ir en accesorios de acuario.
- `TMP-COMP-OC29-INC228` - `Acriflavina 30 ml`: confirmar uso, especie objetivo y si se trata como tratamiento/insumo de acuario.
- `TMP-COMP-OC29-INC230` - `Alimento para tortuga bebe y anfibios`: confirmar si debe separarse entre tortugas y anfibios.
- `ECOM-200` y familia de productos `Poster ... por rollo`: confirmar si son fondos/posters para acuarios; el nombre comercial no describe suficientemente el uso.

El auditor tambien reporta 444 productos como `Revision manual`; esa cifra no significa que todos sean desconocidos. Incluye productos ya categorizados cuya regla automatica tiene confianza baja o media. Ejemplos: `Balon futbol americano`, `Jabon antiseptico multi-especie`, `Acriflavina`, `Esponja Biogarden`, `Material filtrante` y `Maternidad flotante`.

## Decision actualizada

1. No crear una categoria general por cada grupo de nombres ambiguos.
2. Crear primero, solo despues de confirmar muestras, una candidata `General multi-especie / Salud e higiene general`.
3. Conservar `Pequenos mamiferos / Habitat y jaulas generales` y evaluar una categoria general distinta para especies no contempladas.
4. Revisar la raiz `Accesorios y productos generales` y los 109 llaveros como rama comercial/promocional separada.
5. Resolver los 56 productos activos sin categoria principal por lotes humanos, comenzando por transportadoras, alimentos sin especie, habitat y productos tecnicos.
6. No cambiar slugs, rutas, relaciones ni estados de categorias hasta presentar el mapa producto-categoria y el impacto Ecommerce.

## Aclaraciones recibidas del dueno 2026-10-24

Las siguientes interpretaciones quedan confirmadas para la proxima propuesta de clasificacion. No implican cambios automaticos en BD:

| Producto o grupo | Interpretacion confirmada | Destino conceptual recomendado |
| --- | --- | --- |
| `Salvado natural` | Sustrato o alimento para criar/alimentar tenebrios y otro alimento vivo | Nueva categoria de insumos para alimento vivo; no clasificar como alimento de mascota sin confirmar el uso de venta |
| `Rocaliente para reptiles` | Calefaccion para reptiles | `Reptiles, anfibios e invertebrados / Reptiles generales / Calefaccion` |
| `Larva de mosca soldado para peces` | Alimento para peces | `Acuario y peces / Alimentacion / Alimentos para peces` |
| `Piedra de resina nucleo` | Decoracion para peceras | `Acuario y peces / Decoracion y ambientacion / Decoracion para peces` |
| `Maternidad flotante` | Aditamento para peceras | `Acuario y peces / Repuestos, aditamentos y accesorios / Aditamentos` |
| `Acriflavina` | Producto de acuario; puede ser acondicionador, tratamiento o medicamento | Requiere separar por funcion: acondicionamiento/tratamiento de agua; no dejarlo indefinidamente en `Aditamentos` |
| `Esponja Biogarden` | Material filtrante para peceras | `Acuario y peces / Equipamiento tecnico / Filtracion y oxigenacion` o una hoja especifica de medios filtrantes si el volumen lo justifica |
| `Material filtrante verde` | Material filtrante para peceras | `Acuario y peces / Equipamiento tecnico / Filtracion y oxigenacion` |
| `Bonsai frondoso` | Decoracion para peceras | `Acuario y peces / Decoracion y ambientacion / Decoracion para peces` |
| `Poster ... por rollo` | Decoracion/fondo para peceras | `Acuario y peces / Decoracion y ambientacion / Decoracion para peces`; evaluar hoja `Fondos y posters` si hay suficiente volumen |
| `Arena de baño para mamiferos` | Salud e higiene para pequenos mamiferos/roedores | Nueva hoja general de `Pequenos mamiferos / Salud e higiene / Sustratos y arenas` si aplica a varias especies |
| `Maletin mochila Liu` | Mochila transportadora para gatos o pequenos mamiferos/roedores | Categoria transversal de transporte solo si la ficha confirma ambas especies; de lo contrario asignar por especie |

## Categorias faltantes ajustadas

Las aclaraciones cambian la prioridad de las propuestas:

1. `Acuario y peces / Salud y acondicionamiento del agua`: candidata prioritaria para separar acriflavina, acondicionadores y tratamientos de los aditamentos mecanicos. La ficha de producto debe definir si se permite publicar como medicamento o solo como insumo/acondicionador.
2. `Acuario y peces / Equipamiento tecnico / Medios filtrantes`: candidata de baja urgencia; por ahora es suficiente usar `Filtracion y oxigenacion`, pero conviene separarla si las esponjas, canutillos y materiales filtrantes crecen.
3. `Acuario y peces / Decoracion y ambientacion / Fondos y posters`: candidata de baja urgencia; los posters confirmados pueden vivir temporalmente en `Decoracion para peces`.
4. `Pequenos mamiferos / Salud e higiene / Sustratos y arenas`: candidata real para arena de baño multi-roedor. No debe duplicarse por Hamster, Conejo, Cuyo, Chinchilla y Erizo si el producto no es exclusivo de una especie.
5. `Insumos para alimento vivo / Sustratos y alimento de crianza`: candidata transversal para salvado, alimento para grillos y materiales destinados a criar tenebrios/larvas. Debe mantenerse separada de alimentos para perros, peces o reptiles.
6. `General multi-especie / Transporte`: candidata para mochilas y transportadoras realmente compartidas por gatos y pequenos mamiferos. No crearla si cada modelo tiene una especie dominante clara.

Estas son propuestas de arquitectura, no altas autorizadas. Antes de crearlas se deben medir productos, SKUs, publicaciones Ecommerce y URLs afectadas.

## Revision de encaje en categorias existentes 2026-10-24

La aclaracion del dueno confirma que la mayoria de los productos no necesita una categoria nueva:

| Producto | Categoria existente suficiente | Decision |
| --- | --- | --- |
| `Rocaliente para reptiles` | `Reptiles, anfibios e invertebrados / Reptiles generales / Calefaccion` | Reasignar; no crear categoria |
| `Larva de mosca soldado para peces` | `Acuario y peces / Alimentacion / Alimentos para peces` | Reasignar; no crear categoria |
| `Piedra de resina nucleo` | `Acuario y peces / Decoracion y ambientacion / Decoracion para peces` | Reasignar; no crear categoria |
| `Maternidad flotante` | `Acuario y peces / Repuestos, aditamentos y accesorios / Aditamentos` | Reasignar; no crear categoria |
| `Esponja Biogarden` y `Material filtrante verde` | `Acuario y peces / Equipamiento tecnico / Filtracion y oxigenacion` | Reasignar; no crear categoria por ahora |
| `Bonsai frondoso` | `Acuario y peces / Decoracion y ambientacion / Decoracion para peces` | Reasignar; no crear categoria |
| `Poster ... por rollo` | `Acuario y peces / Decoracion y ambientacion / Decoracion para peces` | Reasignar; crear `Fondos y posters` solo si el volumen lo justifica |
| `Arena de baño para mamiferos` | Sustratos existentes de la especie, si la especie es exclusiva | Usar categoria existente cuando la ficha indique Hamster, Chinchilla, Cuyo, Erizo, etc. |
| `Maletin mochila Liu` | `Gatos / Transporte... / Mochilas transportadoras` o `Pequenos mamiferos / ... / Transporte`, segun especie dominante | No crear categoria mientras no se confirme que es realmente multi-especie |
| `Acriflavina` | `Acuario y peces / Repuestos, aditamentos y accesorios / Aditamentos` solo como ubicacion provisional | Revisar; una categoria de salud/acondicionamiento solo se justifica por volumen y politica de venta |
| `Salvado natural` | Ninguna categoria actual lo representa bien si es insumo para criar tenebrios | Mantener pendiente; crear `Insumos para alimento vivo` solo si hay mas productos de esa funcion |

Conclusion: de las once oportunidades revisadas, siete encajan directamente en categorias existentes, dos dependen de la especie o volumen, una puede permanecer provisionalmente en `Aditamentos` y solo una (`Salvado natural`) no tiene un encaje semantico correcto en el arbol actual.

## Modelo recomendado

La categoria principal debe responder donde buscaria normalmente el operador o el cliente. La arquitectura usa tres niveles de decision:

1. Especie o familia cuando el producto fue diseñado para ella.
2. Uso del producto cuando la familia es clara: alimentacion, habitat, salud, juego, transporte, equipamiento o repuestos.
3. Rama transversal solo cuando el producto aplica de forma real a varias especies o a mascotas no contempladas.

Marca, formato, material, talla, color, capacidad y otras diferencias no deben convertirse en categorias si son atributos filtrables o variantes.

Cada categoria debe indicar:

- `estructural`: organiza el arbol y no recibe productos.
- `operativa/asignable`: puede ser categoria principal o secundaria.
- `transversal`: aplica a varias especies y requiere alcance documentado.
- `solo historica`: conserva referencia, pero no aparece en captura operativa.

## Auditoria de oportunidades

| Candidata | Decision | Tipo | Alcance y ejemplos | Criterio de existencia |
| --- | --- | --- | --- | --- |
| Salud e higiene general | Crear solo en una rama transversal controlada | Operativa/transversal | Productos de higiene, desinfeccion, limpieza o cuidado aplicables a perro, gato, pequenos mamiferos, aves o reptiles sin formulacion exclusiva | Debe haber productos reales multi-especie y no existir una categoria especifica mas precisa |
| Habitat y jaulas generales para pequenos mamiferos | Conservar | Operativa | Jaulas, corrales, habitats y accesorios de contencion para especies pequenas no resueltas por Hamster, Conejo, Cuyo, Erizo, Chinchilla o Huron | Ya existe `CAT16-MAMIFEROS-HAB-GENERAL`; no cambiarle el significado |
| Habitat y jaulas generales multi-especie | Proponer para fase posterior | Operativa/transversal | Jaulas o habitats para mascota no contemplada, por ejemplo un primate pequeno, o productos explicitamente multi-especie | Crear solo con muestra de productos y cuando no sea posible asignar una especie sin forzarla |
| Accesorios generales | Conservar con uso restringido | Operativa/transversal | Ganchos, repuestos menores o accesorios sin uso dominante claro | No usar como cajon de sastre; requiere revision periodica y limite de productos |
| Transporte y manejo | Crear o normalizar como concepto transversal | Operativa/transversal | Transportadoras, jaulas de traslado, redes, guantes y elementos de manejo para varias especies | Separar transporte de paseo; no duplicar una transportadora multi-especie en cada raiz |
| Limpieza y mantenimiento | No crear como raiz independiente inicialmente | Estructural o secundaria | Sifones, cepillos, limpiadores y herramientas de mantenimiento, especialmente de acuario o habitat | Mantener bajo el uso o familia dominante; crear rama transversal solo si la navegacion lo justifica |
| Productos para mascotas no clasificadas por especie | Crear como bandeja de revision, no como destino automatico | Operativa provisional | Productos cuyo uso, especie o seguridad no estan confirmados | Solo permite contener pendientes; cada producto debe salir a una categoria definitiva |

## Recomendacion para las dos categorias prioritarias

### Salud e higiene general

No conviene crear `Salud para perro`, `Salud para gato`, `Salud general` y equivalentes sin una regla de alcance. La recomendacion es:

- Mantener las categorias especificas cuando el producto sea formulado o diseñado para una especie.
- Crear, si la evidencia lo confirma, `General multi-especie / Salud e higiene general` como categoria asignable y transversal.
- Usarla para higiene, limpieza o cuidado de uso comun; no para medicamentos, suplementos o tratamientos que exijan especie, dosis o indicacion veterinaria.
- No reasignar productos existentes por coincidencia de palabras como `salud`, `higiene`, `limpieza` o `desinfectante`.
- Antes del alta, presentar muestras, categoria actual, categoria propuesta, productos afectados y si la asignacion seria principal o secundaria.

Decision provisional: `crear` como candidata controlada, pendiente de evidencia read-only; no crearla aun en BD.

### Habitat y jaulas generales

La categoria existente `Pequenos mamiferos / Habitat y jaulas generales` es correcta y debe conservarse. Resuelve el caso de una jaula que no debe forzarse a Conejo, Hamster u otra especie pequena.

No debe reutilizarse para monos, changuitos u otras mascotas no contempladas porque eso ampliaria silenciosamente su significado. Para esos productos se recomienda una categoria hermana en una rama transversal, por ejemplo `General multi-especie / Habitat y jaulas generales`, solo si existen productos concretos y si el negocio acepta publicar esa navegacion.

Reglas:

- Jaula claramente para una especie: categoria de esa especie.
- Jaula para varias especies pequenas sin dominante: `Pequenos mamiferos / Habitat y jaulas generales`.
- Habitat para mascota no contemplada o multi-especie amplia: rama transversal general, con evidencia.
- Material de la jaula, numero de niveles y medidas: atributos, no categorias.

Decision provisional: `conservar` la categoria de pequenos mamiferos; `crear` la categoria transversal solo despues del preflight y la autorizacion de propuesta.

## Reglas para no duplicar el arbol

- Una categoria transversal no reemplaza automaticamente las categorias por especie.
- La categoria principal expresa el uso comercial dominante; una secundaria puede apoyar navegacion alternativa.
- Un mismo producto no debe recibir varias categorias principales.
- No usar `Accesorios generales` o `Productos no clasificados` para ocultar una decision pendiente.
- Una categoria estructural puede tener hijas asignables, pero no debe recibir productos directamente.
- Toda propuesta debe incluir razon de negocio, alcance, ejemplos, contraejemplos, categoria padre, tipo, `permite_productos`, categoria principal/secundaria y regla de salida si deja de ser necesaria.

## Impacto en Ecommerce y SEO

El contrato actual deriva el `path_slug` jerarquico desde `ruta` y expone categorias publicas mediante `/ecommercePublico/categorias`; las URLs canonicas del frontend son `/categoria/{path_slug}`. Solo se muestran categorias activas con publicaciones publicadas y productos/SKUs vigentes, incluyendo ancestros con descendientes publicables.

Riesgos concretos:

- Renombrar una categoria cambia potencialmente el `path_slug` y la URL canonica aunque el ID ERP permanezca.
- Mover una categoria cambia todos los segmentos de su ruta y puede afectar breadcrumbs, enlaces internos, sitemap, canonical, analytics y landings.
- Inactivar una categoria puede quitarla del arbol publico y dejar URLs sin equivalente.
- Cambiar relaciones producto-categoria puede cambiar conteos, visibilidad y la categoria usada por el breadcrumb del producto.
- La capa publica calcula slugs; no debe asumirse que el cambio de nombre conserva automaticamente aliases historicos.

Politica requerida antes de cualquier alta, renombre, movimiento u ocultamiento que llegue a Ecommerce:

1. Capturar ruta actual, `path_slug`, URL canonica, publicaciones, productos directos y descendientes.
2. Definir si la categoria es solo ERP o visible en Ecommerce.
3. Generar URL nueva y comparar con la anterior.
4. Preparar redireccion aprobada 301/308 o 410 cuando corresponda; nunca redirigir todo a home.
5. Validar breadcrumb, sitemap, canonical, contenido editorial e imagenes.
6. Publicar cambios en orden controlado y verificar respuestas HTTP.

Las redirecciones deben usar la mesa SEO y el contrato de `erp_ecommerce_seo_redirecciones`; no deben crearse automaticamente por cambiar un nombre de categoria. Las tablas SEO y el flujo de persistencia siguen sujetos a sus propios planes y autorizaciones.

## Orden de implementacion

### Fase 0 - Preflight read-only

- Restablecer conexion de lectura en un entorno autorizado.
- Ejecutar auditoria de maestro, arbol, relaciones, publicaciones, rutas publicas y redirecciones.
- Obtener conteos actuales por categoria: productos directos, principales, secundarios, activos, publicados y descendientes.
- Exportar evidencia externa sin modificar la BD.

### Fase 1 - Propuesta

- Revisar la muestra de productos candidatos a salud, habitat, transporte y accesorios.
- Separar especie, uso y atributos.
- Preparar mapa categoria actual -> categoria propuesta con riesgo y conteo.
- Marcar cada categoria como conservar, crear, fusionar, mover, estructural, operativa o no recomendable.

### Fase 2 - Autorizacion

- Validar con el dueno las categorias nuevas y su alcance.
- Autorizar por separado altas, cambios de nombre, movimientos, inactivaciones y relaciones producto-categoria.
- Definir si cada categoria sera visible en Ecommerce.

### Fase 3 - Respaldo y aplicacion controlada

- Generar respaldo externo en `C:\xampp\panel_db_backups`.
- Aplicar primero altas de categorias sin tocar relaciones.
- Aplicar despues cambios de relaciones por lotes revisables; no borrar historial.
- Inactivar u ocultar antes que eliminar.

### Fase 4 - Validacion

- Repetir auditoria estructural y de relaciones.
- Confirmar que no existan productos con multiples principales.
- Validar selectores ERP, breadcrumbs, `/ecommercePublico/categorias`, catalogo por `path_slug`, sitemap, canonical y redirecciones.
- Documentar resultados y pendientes en `docs/erp_catalogo_avance.md`.

## No autorizado en esta entrega

- No se crearon categorias.
- No se cambiaron nombres, padres, slugs, estatus ni rutas.
- No se modificaron relaciones producto-categoria.
- No se aplicaron migraciones SEO, redirecciones ni DDL.
- No se actualizaron reglas de clasificacion automatica.

## Siguiente entregable recomendado

Crear un preflight read-only especifico para categorias transversales que entregue muestras agrupadas por categoria actual, nombre de producto, SKU, especie inferida solo como sugerencia, publicaciones Ecommerce, `path_slug` y riesgo SEO. Ese resultado debe presentarse para autorizacion antes de cualquier apply.
