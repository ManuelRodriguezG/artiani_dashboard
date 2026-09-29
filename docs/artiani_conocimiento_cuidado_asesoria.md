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
- Hamster.
- Cuyo.
- Erizo.
- Chinchilla.
- Huron.
- Serpientes.

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
- Cambios recientes: se preparo controlador, modelo, esquema dry-run, listado, detalle independiente, formulario de nueva ficha, JS, permisos base y menu.
- Decisiones: Artiani es modulo propio, no una pantalla dentro de Catalogo; Catalogo ERP se usara despues para productos relacionados.
- Pendientes: autorizar esquema, sembrar fichas reales en BD, crear editor de fichas, vincular productos relacionados contra SKUs y preparar salida publica para ecommerce.
- Impacta a: Catalogo ERP, CMS/frontend, Atencion comercial, capacitacion interna y futuro agente IA.
- Siguiente paso recomendado: revisar las fichas iniciales con experiencia del negocio y decidir campos obligatorios para el editor antes de autorizar tablas.
