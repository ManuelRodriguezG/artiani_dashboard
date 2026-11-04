# Encargo visual: Blog camaleon Artiani

IA: Codex GPT-6. Fecha: 2026-11-04. Solicitado por el dueño desde CMS/BLOG para el chat existente **Estrategia y Produccion de Contenido…**. Autorizado enviar el paquete y solicitar produccion visual para revision; no registrar/publicar en CMS ni modificar catalogo.

## Fuente de trabajo

Carpeta compartida: `C:/xampp/htdocs/panel_de_control/storage/editorial/blog_camaleon/`.

- `articulo.html`: borrador y fuentes compactas; titulo Camaleón: lo esencial para preparar su terrario.
- `revision.json`: decisiones del dueño, SEO, candidatos, condiciones y briefs.
- `manifest_referencias_contenido.json`: nueve fotografias originales, IDs, archivos, dimensiones, SHA256 y condiciones por producto.
- `fichas_teral12_campanas.json`: objetos completos de cuatro fichas, descripciones y especificaciones.
- `catalogo_publico_consultado.json` y `catalogo_publico_complemento.json`: tarjetas completas para los demas candidatos.

El dueño desconoce la especie mas atendida y eligio una guia general para camaleones arboricolas comunes; no elaborar una receta universal de parametros.

## Fotografias reales disponibles

Todas900x900; conservar originales. No sustituir un producto por una reinterpretacion de su forma. Abrir/ver las referencias antes de generar.

| Archivo dentro de referencias_catalogo/ | Publicacion | Uso |
|---|---:|---|
| TERAL-12.webp | 269 | Terrario principal, identidad visual prioritaria |
| CAM-01.webp | 2174 | Campana con ficha mas completa |
| CAM-02.webp | 2175 | Alternativa; no mostrar junto a todas como montaje obligatorio |
| CAM-03.webp | 2176 | Alternativa doble foco; datos electricos pendientes |
| HPT2186.webp | 191 | UVB compacto13W; cobertura por validar |
| HPT2187.webp | 2053 | UVB compacto26W; cobertura por validar |
| HPT2110.webp | 1733 | Sun Glo60W; montaje termico por validar |
| FL2152.webp | 193 | Calcio con minerales; formula/plan pendientes |
| FL2151.webp | 310 | Calcio con D3; no intercambiar formulas ni inventar dosis |

TERAL-12 es fabricacion Artiani,50x40x80cm publicados, marcos aluminio, cristal6mm, puertas deslizables, malla metalica en ambos costados y arriba. Confirmado por dueño, ficha y fotografia. Los Magno y demas modelos de madera siguen siendo de madera: no convertirlos en aluminio ni mezclarlos con Ecuatorial. El aluminio evita pudricion de madera; no prometer inmunidad a corrosion ni impermeabilidad del conjunto. No se ha confirmado drenaje incorporado, cierre antiescape o equipos incluidos.

CAM-01 declara E26,max150W,110-120V. Eso no define la potencia que necesita un camaleon ni se aplica a CAM-02/CAM-03. Campana, foco de calor, UVB y termostato tienen funciones diferentes. Una fotografia no certifica montaje electrico o exposicion UVB. Los suplementos son referencias opcionales; no convertirlos en compra obligatoria.

## Entrega solicitada: cuatro imagenes para revision

1. **camaleon-portada.webp —1600x1200px,4:3.** Portada comercial/editorial atractiva basada en TERAL-12 real, con estructura completa reconocible, malla lateral/superior y puertas correctas. Follaje y ramas ilustrativos, luz natural. Si aparece un camaleon, identificar la especie ilustrativa y no representar un adulto encajado en este recinto ni afirmar que sus medidas sirven para cualquier adulto. Sin logo/etiqueta inventados. ALT sugerido: Terrario Ecuatorial de aluminio, vidrio y malla con ramas y follaje para un montaje de camaleón.
2. **camaleon-terrario-ventilacion.webp —1536x1024px,3:2.** Explicar visualmente la estructura real y las tres zonas de ventilacion de TERAL-12. Conservar proporciones del producto. Puede ser una composicion de foto real y detalles; no inventar paneles extra. ALT: Malla metálica en los costados y techo del Terrario Ecuatorial TERAL-12.
3. **camaleon-iluminacion-calor.webp —1536x1024px,3:2.** Diferenciar calor y UVB en una composicion didactica. Usar CAM-01 real y, si corresponde, comparar los focos fotografiados sin implicar montaje conjunto, potencia adecuada o cobertura validada. No combinar tres campanas sobre un solo terrario ni dibujar conexiones, distancias o lecturas universales. Si no hay evidencia para una instalacion, mostrar productos separados como alternativas. ALT: Campana y focos para explicar las funciones de calor e iluminación UVB.
4. **camaleon-hidratacion-decoracion.webp —1536x1024px,3:2.** Gotas sobre hojas, ramas firmes y ventilacion. Usar como entorno la estructura real si se ve el recinto. Plantas, medidores, goteo o salida de agua genericos son ilustrativos, no productos confirmados ni componentes incluidos. No mostrar deposito encharcado ni humedad permanente. ALT: Gotas de agua sobre hojas y ramas en un hábitat ventilado para camaleón.

## Formato y composicion

- La portada usa4:3 porque `public/assets/css/app.css` del frontend aplica `aspect-ratio:4/3` al cover. Mantener producto y sujeto completos en zona central segura; nada esencial pegado al borde. Los apoyos conservan3:2 y su proporcion al renderizar.
- Entregar WebP RGB, sin animacion, cada archivo menor a2MB (limite2097152bytes del upload Blog). Objetivo250–600KB si la calidad lo permite. Exportar exactamente las dimensiones indicadas y conservar el original de generacion por separado.
- Evitar texto incrustado; titulos, explicaciones y etiquetas deben poder colocarse desde HTML. Si se necesita una anotacion tecnica, entregar version limpia y texto separado, no valores inventados.
- No precios, ofertas, stock, entrega inmediata, promesas medicas, dosis o porcentajes universales. Disponibilidad se consulta al API al mostrar tarjetas.
- Si algun producto no puede reproducirse fielmente, conservar su fotografia real en una composicion o señalar la limitacion antes de sustituirlo por otro modelo.
- No preparar pines/coordenadas hasta tener imagen final revisada; no fijarlos sobre un futuro recorte. No atribuir drenaje, soporte o equipo que no aparecen en ficha.

## Cierre de entrega

Guardar en `C:/xampp/htdocs/panel_de_control/storage/editorial/blog_camaleon/imagenes_revision/`. Entregar una previsualizacion para el dueño y un manifest con archivo, dimensiones, peso, ALT, caption, productos identificables e IDs, elementos ilustrativos y limitaciones. Caption base: Montaje ilustrativo; decoración y equipos se seleccionan por separado según especie y talla.

Dejar el resultado y rutas documentados en este paquete compartido para que CMS/BLOG pueda recuperarlos por lectura. El envio a este chat no autoriza por si mismo mensajes de vuelta: usar la documentacion compartida salvo que exista autorizacion directa del dueño para responder a otro chat. No cargar imagenes a Media ni registrar/publicar articulo. Indicar al final que sigue.
