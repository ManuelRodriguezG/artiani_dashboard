# Artiani - Conocimiento, cuidado y asesoria animal

Documentacion IA: Codex GPT-5  
Fecha base: 2026-09-28  
Estado: Modulo nuevo, primera etapa Enciclopedia de especies

## Proposito

Artiani sera la base oficial de conocimiento para orientar clientes, capacitar personal y preparar un futuro agente de inteligencia artificial de atencion animal.

El modulo no debe limitarse a un catalogo de mascotas. Debe funcionar como conocimiento vivo de cuidado responsable, venta consultiva y contenido reutilizable para la pagina web.

## Nombre operativo

Nombre definido por el dueno:

**Artiani: modulo de conocimiento, cuidado y asesoria animal**

En interfaz se puede mostrar como `Artiani`, con subtitulo descriptivo.

## Primera etapa: Enciclopedia de especies

Objetivo:

- crear fichas estructuradas por especie o grupo de especies;
- documentar habitat, alimentacion, cuidados, preguntas clave y alertas;
- preparar productos relacionados sin vincular aun contra SKU;
- separar informacion para empleados, clientes, web y futuro agente IA.

Fichas iniciales incluidas en semilla PHP:

- Peces de agua dulce.
- Hamster, separado operativamente en hámster sirio y hámster ruso/chino.
- Cuyo.
- Erizo.
- Chinchilla.
- Huron.
- Serpientes.

## Decision operativa: hámster

Fecha: 2026-09-29

Para Artiani, `Hamster` queda como ficha padre y se separa en dos variantes comerciales:

- `Hámster sirio`.
- `Hámster ruso o chino`.

La separacion existe porque la recomendacion de productos cambia por talla y seguridad:

- Sirio: requiere habitat mas amplio, rueda grande, refugios/tuneles de entrada amplia y accesorios que no le queden chicos.
- Ruso/chino: requiere habitat seguro contra escapes, barrotes estrechos o paredes lisas, accesorios bajos y rueda mediana sin huecos donde pueda atorarse.

Regla comercial segura:

- Recomendar habitat individual como punto de partida.
- No vender ruedas pequenas para sirio si arquean la espalda.
- No recomendar jaulas con barrotes separados para ruso/chino.
- Incluir desgaste dental como necesidad permanente: mordederas, madera segura, juguetes para roer y alimento completo con textura adecuada.
- Explicar sustratos con criterio, no solo por nombre comercial: papel, aserrin prensado, sustrato de maiz y viruta deben evaluarse por polvo, aroma, humedad, suavidad y seguridad para pequeños mamiferos.
- Usar medidas operativas en la ficha: habitat de referencia 100 x 50 cm cuando sea posible, rueda 28-30 cm o mayor para sirio, rueda 20-25 cm para ruso y 25-28 cm si chino arquea espalda, sustrato 15-20 cm minimo practico e ideal 25 cm o mas si el habitat lo permite.
- Separar el ideal de bienestar del criterio comercial. La ficha debe clasificar productos como `comercial recomendado`, `comercial aceptable con advertencia` o `no recomendar como permanente`, porque el catalogo contiene productos de varios tamaños y usos.
- Las jaulas/habitats comerciales detectados como 80 cm, 100 cm y 120 cm deben evaluarse de forma distinta a jaulas mini; una jaula mini puede servir para traslado/temporal, no como vivienda definitiva.
- La ficha de hámster ahora analiza jaulas/habitats reales del Catalogo ERP en modo read-only, extrae medidas del nombre del SKU/producto cuando existen y clasifica: `principal_recomendado_comercial`, `principal_compacto`, `compacto_ruso_chino`, `temporal_o_inicial`, `solo_temporal_traslado` o `revision_manual`.
- Si un producto no tiene medidas detectables en el nombre, queda en `revision_manual`; no debe publicarse como recomendado hasta revisar ficha fisica o completar atributos en Catalogo ERP.
- Esferas de ejercicio: comercialmente existen, pero no deben presentarse como sustituto de rueda ni ejercicio principal; si se venden, explicar uso corto y supervisado.
- Productos del catalogo se muestran como candidatos read-only y requieren revision humana antes de vincularse formalmente a la ficha.

## Contratos operativos

- Artiani no sustituye diagnostico veterinario.
- No debe prometer disponibilidad, precio ni stock.
- No debe recomendar habitat, convivencia o equipo tecnico sin datos minimos de especie, tamano, edad y contexto.
- Los productos relacionados se deben vincular despues contra Catalogo ERP por SKU, categoria o etiqueta de uso.
- Las respuestas del futuro agente IA deben pedir datos faltantes antes de afirmar recomendaciones especificas.

## Arquitectura inicial implementada

- Controlador: `app/controladores/Artiani.php`.
- Modelo de conocimiento: `app/modelos/ArtianiConocimientoErp.php`.
- Esquema dry-run: `app/modelos/ArtianiConocimientoEsquema.php`.
- Vista: `app/vistas/paginas/apps/erp/artiani/enciclopedia.php`.
- JS: `public/assets/js/custom/apps/erp/artiani/enciclopedia.js`.
- Ruta principal: `/artiani`.
- Vistas principales:
  - `/artiani/enciclopedia`: listado y busqueda de fichas.
  - `/artiani/especie/{slug}`: detalle independiente de especie.
  - `/artiani/especie_nueva`: captura guiada de nueva ficha, aun sin guardado real.
- Endpoints read-only:
  - `/artiani/catalogos_erp`.
  - `/artiani/especies_listar_erp`.
  - `/artiani/especie_consultar_erp?slug=...`.
  - `/artiani/esquema_auditar_erp`.
  - `/artiani/esquema_plan_erp`.

## Esquema propuesto

El plan dry-run prepara:

- `artiani_especies`: ficha canonica de especie.
- `artiani_especies_productos`: relacion futura especie-necesidad-SKU.
- `artiani_especies_revision`: cola de revision para pendientes, alertas y mejoras.

No se ejecuto DDL ni escritura en BD durante esta etapa.

## Handoff / continuidad

Fecha: 2026-09-28

- Contexto actual: el modulo abre una Enciclopedia funcional con semilla PHP y filtros operativos.
- Cambios recientes: se preparo controlador, modelo, esquema dry-run, listado, detalle independiente, formulario de nueva ficha, JS, permisos base y menu. Se enriquecio la ficha de hámster con comparativa sirio vs ruso/chino, especificaciones de habitat/rueda/sustrato/dental/alimento, criterio comercial por niveles, candidatos read-only y analisis automatico de jaulas/habitats reales del Catalogo ERP.
- Decisiones: Artiani es modulo propio, no una pantalla dentro de Catalogo; Catalogo ERP se usara despues para productos relacionados.
- Pendientes: autorizar esquema, sembrar fichas reales en BD, crear editor de fichas, vincular productos relacionados contra SKUs y preparar salida publica para ecommerce.
- Impacta a: Catalogo ERP, CMS/frontend, Atencion comercial, capacitacion interna y futuro agente IA.
- Siguiente paso recomendado: revisar las fichas iniciales con experiencia del negocio y decidir campos obligatorios para el editor antes de autorizar tablas.
