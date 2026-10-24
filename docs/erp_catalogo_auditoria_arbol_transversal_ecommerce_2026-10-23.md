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
