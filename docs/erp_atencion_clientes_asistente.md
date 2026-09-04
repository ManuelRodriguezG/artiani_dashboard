# ERP - Asesor comercial de prospectos

Documentacion IA: Codex GPT-5  
Fecha base: 2026-09-04  
Estado: Primera herramienta interna read-only corregida a enfoque comercial

## Proposito

Este modulo ayuda al dueno y al equipo a responder preguntas reales de prospectos de clientes con criterio comercial y conocimiento de producto, sin improvisar stock, precios ni promociones.

La primera etapa no envia mensajes, no conecta WhatsApp/Facebook, no consulta inventario y no escribe historial. Solo genera respuestas sugeridas editables para uso interno.

No es una bandeja de soporte ni un modulo de atencion postventa. Su funcion es ayudar a convertir preguntas amplias o tecnicas en respuestas utiles, asesoradas y accionables.

## Decisiones operativas

- El asistente pertenece funcionalmente a CRM/Prospectos, pero puede ser usado por ventas.
- Usa permisos existentes `crm.ver`, `crm.seguimiento.ver`, `ventas.ver` o `ventas.operar` para evitar migracion de permisos en esta etapa.
- Las respuestas deben ser amables, profesionales, claras, breves y comercialmente utiles.
- Si el prospecto pregunta por una especie o proyecto, la respuesta debe orientar por grupos de producto y pedir datos tecnicos clave.
- Toda respuesta que mencione catalogo, pagina o producto debe pedir confirmacion de disponibilidad y precio actualizado.
- Mayoreo se ofrece como revision condicionada por producto, cantidad y disponibilidad.
- El sistema no debe afirmar existencia, precio, promocion ni fecha de entrega sin consulta real posterior.

## Ejemplo rector

Prospecto:

```text
Disculpe tendras terrarios y todo lo que necesito para una piton bola?
```

Respuesta esperada:

```text
Hola, si podemos revisar opciones para armar o completar un habitat para piton bola. Normalmente se revisan terrario, sustrato, refugios, bebedero, calefaccion, control de temperatura/humedad, pinzas y accesorios de seguridad o decoracion. Para recomendarte bien, dime que tamano o edad tiene, si ya cuentas con terrario y que medidas tiene. Con eso revisamos que productos manejamos, disponibilidad y precio actualizado.
```

## Categorias base

- Acuario y peces.
- Perros.
- Gatos.
- Reptiles y terrarios.
- Conejos y pequenos mamiferos.
- Aves.
- Complementarios.

## Contrato actual

- Controlador: `Atencion`.
- Vista: `apps/erp/atencion/asistente`.
- Modelo: `AtencionClienteErp`.
- JS: `public/assets/js/custom/apps/erp/atencion/asistente.js`.
- Endpoints:
  - `GET /atencion/catalogos_erp`
  - `GET /atencion/respuesta_sugerida_erp`

## Pendientes

- Crear permisos finos `atencion.ver`, `atencion.operar`, `atencion.configurar` cuando el modulo deje de ser solo read-only.
- Conectar catalogo ERP para sugerir productos reales sin prometer stock.
- Conectar inventario/precios para confirmacion operativa con permisos.
- Guardar historial de conversaciones y seguimientos en CRM.
- Integrar canales externos como WhatsApp, Messenger o formularios web.
- Crear biblioteca editable de plantillas y respuestas aprobadas.

## Handoff / continuidad

Fecha: 2026-09-04

- Contexto actual: el dueno del negocio necesita generar respuestas acertadas para prospectos segun producto, mascota, especie o proyecto del cliente.
- Cambios recientes: se preparo un asesor interno read-only con reglas locales y escenario especifico para piton bola.
- Decisiones: no conectar IA externa ni canales externos hasta tener permisos, historial y politica de datos.
- Pendientes: evolucionar a modulo persistente con plantillas administrables e integracion con catalogo/inventario.
- Impacta a: CRM, Ventas, Catalogo ERP, Ecommerce y eventualmente Notificaciones.
- Siguiente paso recomendado: validar textos reales con el equipo y despues definir esquema de plantillas/historial.
