# Uso de IA y modelos en este proyecto

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Guia operativa para elegir nivel de IA sin desperdiciar tokens

## Proposito

Este archivo define como decidir que tipo de IA usar segun la tarea. No fija nombres comerciales o versiones especificas como verdad permanente, porque los modelos disponibles pueden cambiar por cuenta, entorno o producto.

Regla principal:

- No escribir en los planes "usa Codex 5.3" o "usa Codex 5.5" como requisito obligatorio si ese nombre no esta confirmado en el entorno actual.
- Documentar el nivel de capacidad necesario: rapido, fuerte, largo contexto, implementacion, revision o planificacion.
- Si el entorno permite seleccionar modelo exacto, elegirlo en la configuracion de Codex o al iniciar la sesion, no dentro de cada tarea de negocio.

## Donde se define

### 1. `AGENTS.md`

Usar para reglas durables del repo:

- Como leer el proyecto.
- Que documentos revisar primero.
- Como ahorrar tokens.
- Que tipo de IA conviene por tipo de tarea.
- Que no se debe inventar.

### 2. Este archivo

Usar para detalle operativo:

- Que tareas requieren modelo fuerte.
- Que tareas pueden usar modelo rapido.
- Cuando dividir trabajo en subprompts.
- Como pedir contexto minimo.

### 3. Configuracion local de Codex

Si quieres fijar un modelo por defecto, hacerlo en la configuracion del entorno Codex cuando aplique, no en docs de negocio.

Motivo:

- La configuracion controla ejecucion real.
- Los documentos del proyecto deben seguir sirviendo aunque cambien los nombres de modelos.

## Context window y continuidad de trabajo

Fecha: 2026-06-26

La ventana de contexto es la cantidad maxima de conversacion, archivos y resultados que Codex puede mantener activos en un chat. Cuando el trabajo de un modulo dura muchas vueltas, Codex puede avisar limite de contexto o compactar lo anterior.

Regla del proyecto:

- El chat no es la fuente de verdad.
- La fuente de verdad debe ser el codigo, la BD y los documentos vivos del modulo.
- Cada modulo debe tener un documento de avance/handoff suficientemente claro para continuar en otro chat.

Al terminar tareas relevantes, documentar:

- que se hizo;
- por que se hizo asi;
- que archivos/endpoint/tablas quedaron involucrados;
- que no se debe tocar todavia;
- que sigue;
- que decisiones deben consultarse con el dueno.

Cuando se abra un chat nuevo para continuar un modulo, el prompt debe incluir:

```text
Lee AGENTS.md, docs/erp_plan_maestro_fundamentos.md, docs/ia_uso_modelos.md y el documento vivo del modulo.
Continua desde la seccion de handoff/pendientes. No asumas decisiones que no esten documentadas.
```

Si el trabajo afecta otro modulo, agregar una nota de handoff en el documento del modulo origen y preparar instrucciones concretas para el modulo destino.

## Niveles recomendados por tarea

### Nivel A: lectura rapida y busqueda puntual

Usar IA rapida o modelo economico cuando la tarea sea:

- Buscar archivos.
- Ubicar rutas/endpoints.
- Leer un fragmento pequeno.
- Hacer resumen de un archivo ya conocido.
- Revisar sintaxis simple.
- Actualizar checklist o notas.

Prompt recomendado:

```text
Usa el minimo contexto posible. Lee solo AGENTS.md y el archivo puntual indicado. No explores el repo completo.
```

### Nivel B: documentacion y planificacion modular

Usar IA media/fuerte cuando la tarea sea:

- Crear documentos de trabajo.
- Dividir tareas.
- Ordenar flujo de negocio.
- Definir permisos por seccion.
- Comparar reglas entre documentos.

Prompt recomendado:

```text
Lee AGENTS.md y el documento rector del modulo. No implementes codigo. Genera un plan puntual y marca dudas de negocio.
```

### Nivel C: implementacion de una tarea aislada

Usar IA fuerte cuando la tarea sea:

- Modificar controlador/modelo/vista/JS.
- Cambiar persistencia puntual.
- Ajustar permisos.
- Implementar una pantalla.
- Conectar frontend con backend.

Prompt recomendado:

```text
Trabaja solo en la tarea indicada. Usa AGENTS.md, el checklist de avance y el subdocumento puntual. Lee archivos de codigo solo cuando sean necesarios para implementar. No cambies modulos no relacionados.
```

### Nivel D: esquema, migraciones y reglas criticas

Usar el modelo mas fuerte disponible y con buen contexto cuando la tarea involucre:

- Cambios de esquema.
- Migracion de datos.
- Datos fiscales.
- Costos.
- Pagos/notas.
- Inventario.
- Permisos/auditoria.
- Flujo entre Compras, Catalogo, Finanzas y Almacen.

Prompt recomendado:

```text
Usa razonamiento profundo. Antes de editar, audita esquema/modelos/controladores relacionados. No ejecutes migraciones ni elimines campos sin plan y confirmacion.
```

### Nivel E: revision final o auditoria

Usar IA fuerte cuando la tarea sea:

- Revisar bugs.
- Detectar riesgos.
- Validar permisos.
- Comparar implementacion contra plan.
- Revisar si se afecta inventario, finanzas o catalogo.

Prompt recomendado:

```text
Haz revision tipo code review. Prioriza bugs, riesgos, permisos faltantes, reglas rotas y pruebas pendientes. No refactorices.
```

## Reglas para ahorrar tokens

- Empezar siempre por `AGENTS.md`.
- Para Compras, leer el checklist de avance del area activa.
- Leer solo el subdocumento puntual de la tarea.
- Buscar simbolos con `rg` o busqueda puntual antes de abrir archivos completos.
- No leer `dbesquema/artianilocal.sql` salvo busqueda puntual de tabla/campo.
- No abrir vistas heredadas gigantes si el JS o controlador resuelven la duda.
- No pedir "revisa todo el proyecto" para una tarea pequena.

## Como escribir prompts eficientes

Estructura recomendada:

```text
Trabaja solo en [Modulo > Seccion > Tarea].
Lee:
- AGENTS.md
- docs/ia_uso_modelos.md si necesitas decidir nivel de IA
- [checklist de avance]
- [subdocumento puntual]

Objetivo:
[resultado concreto]

Archivos esperados:
[rutas]

No tocar:
[limites]

Criterio de aceptacion:
[como sabemos que termino]
```

## Reglas sobre nombres de modelos

- Si el usuario pide un modelo exacto y el entorno lo ofrece, respetar esa preferencia.
- Si el nombre del modelo no esta confirmado, no inventar capacidades.
- Si se necesita saber el modelo "mejor" o "mas nuevo", consultar documentacion oficial actual o configuracion disponible.
- En documentos del negocio, preferir "modelo fuerte", "modelo rapido", "largo contexto" o "revision profunda" sobre nombres/versiones concretas.

## Aplicacion a Compras > Solicitudes

Para el checklist `docs/erp_compras_solicitudes_avance.md`:

- Tarea 1 Esquema: Nivel D.
- Tarea 2 Estados/permisos: Nivel D.
- Tarea 3 Nueva solicitud: Nivel C.
- Tarea 4 Editar solicitud: Nivel C.
- Tarea 5 Ver solicitud: Nivel C.
- Tarea 6 Documento formal: Nivel C.
- Tarea 7 Listado: Nivel C.
- Tarea 8 Generar orden: Nivel D.
- Tarea 9 Diferencias solicitud vs orden: Nivel D.
- Tarea 10 Pendientes Catalogo: Nivel D.

## Nota final

La decision de modelo debe ayudar a gastar mejor los tokens, no sustituir buenos prompts. Un modelo fuerte con contexto mal dirigido desperdicia mas que un modelo rapido con documentos puntuales bien elegidos.
