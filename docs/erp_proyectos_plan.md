# ERP - Modulo de proyectos y tareas

Documentacion IA: Codex GPT-5  
Fecha base: 2026-07-24  
Estado: Plan inicial pendiente de autorizacion de implementacion  
Relacionados: `AGENTS.md`, `docs/erp_plan_maestro_fundamentos.md`, `docs/erp_notificaciones_alertas_trabajo.md`, `docs/erp_ux_operativa.md`

## Proposito

Crear un modulo interno para organizar proyectos, objetivos, tareas y pendientes del negocio y de la construccion del ERP/POS.

El modulo debe reemplazar el uso de WhatsApp personal, memoria de chat o notas dispersas como fuente principal de pendientes. La informacion debe quedar persistente, asignable, filtrable y trazable dentro del panel.

## Interpretacion de la referencia Metronic

La plantilla Metronic incluye un modulo visual de Projects con secciones como:

- My Projects.
- View Project.
- Targets.
- Budget.
- Users.
- Files.
- Activity.
- Settings.

Para este negocio no conviene copiarlo literalmente. La seccion `Budget` no debe ser el centro del modulo, porque el objetivo inicial no es controlar inversion por proyecto. La idea util es reutilizar la estructura visual para convertirla en control operativo:

- proyectos como contenedores de trabajo;
- objetivos como metas o entregables;
- tareas como acciones concretas;
- usuarios como responsables;
- archivos como evidencias o documentos;
- actividad como historial;
- configuracion como categorias, permisos y reglas.

## Principio operativo

Un proyecto no debe ser solo una carpeta. Debe responder:

- que se quiere lograr;
- por que importa;
- quien es responsable;
- que tareas faltan;
- que esta bloqueado;
- que decisiones estan pendientes;
- que modulo del ERP se afecta;
- que evidencia o documento respalda el avance;
- que debe aparecer en notificaciones cuando requiere accion.

## Tipos de proyecto recomendados

- `construccion_erp`: tareas para construir, corregir o cerrar modulos del sistema.
- `operacion_negocio`: actividades del negocio no necesariamente tecnicas.
- `mejora_proceso`: mejoras internas de flujo, control o documentacion.
- `incidencia`: problemas que requieren seguimiento hasta resolverse.
- `implementacion_modulo`: proyecto formal para arrancar un modulo nuevo.

## Estados recomendados de proyecto

- `borrador`: capturado pero aun no listo para trabajar.
- `activo`: en ejecucion.
- `pausado`: detenido por decision del negocio.
- `bloqueado`: no puede avanzar sin resolver una dependencia.
- `cerrado`: objetivo cumplido.
- `cancelado`: ya no aplica.

## Estados recomendados de tarea

- `pendiente`: aun no se inicia.
- `en_proceso`: alguien ya la esta atendiendo.
- `en_revision`: requiere validacion del dueno o responsable.
- `bloqueada`: requiere informacion, decision, modulo o autorizacion.
- `completada`: terminada y validada.
- `descartada`: ya no aplica, con motivo obligatorio.

## Entidades propuestas

### `erp_proyectos`

Contenedor principal.

Campos minimos:

- `id_proyecto`.
- `folio`.
- `nombre`.
- `descripcion`.
- `tipo`.
- `modulo_relacionado`.
- `estatus`.
- `prioridad`.
- `id_responsable`.
- `creado_por`.
- `fecha_inicio`.
- `fecha_objetivo`.
- `fecha_cierre`.
- `fecha_registro`.
- `fecha_actualizacion`.

### `erp_proyecto_objetivos`

Metas o entregables del proyecto.

Campos minimos:

- `id_objetivo`.
- `id_proyecto`.
- `titulo`.
- `descripcion`.
- `estatus`.
- `prioridad`.
- `orden`.
- `fecha_objetivo`.
- `fecha_cierre`.

### `erp_proyecto_tareas`

Trabajo accionable.

Campos minimos:

- `id_tarea`.
- `id_proyecto`.
- `id_objetivo`.
- `titulo`.
- `descripcion`.
- `estatus`.
- `prioridad`.
- `id_responsable`.
- `area_responsable`.
- `modulo_relacionado`.
- `origen`.
- `url_contexto`.
- `requiere_autorizacion`.
- `fecha_vencimiento`.
- `fecha_cierre`.
- `creado_por`.
- `fecha_registro`.
- `fecha_actualizacion`.

### `erp_proyecto_comentarios`

Seguimiento humano y decisiones.

Campos minimos:

- `id_comentario`.
- `id_proyecto`.
- `id_tarea`.
- `tipo`.
- `comentario`.
- `creado_por`.
- `fecha_registro`.

### `erp_proyecto_adjuntos`

Evidencias, documentos, capturas, respaldos o archivos de referencia.

Campos minimos:

- `id_adjunto`.
- `id_proyecto`.
- `id_tarea`.
- `nombre_original`.
- `ruta_archivo`.
- `tipo_mime`.
- `tamano_bytes`.
- `estatus`.
- `creado_por`.
- `fecha_registro`.

### `erp_proyecto_eventos`

Historial propio del modulo. No reemplaza la auditoria SYS; sirve para linea de tiempo operativa del proyecto.

Campos minimos:

- `id_evento`.
- `id_proyecto`.
- `id_tarea`.
- `tipo`.
- `descripcion`.
- `datos_json`.
- `creado_por`.
- `fecha_registro`.

## Integracion con notificaciones

Cuando una tarea tenga responsable, fecha o prioridad alta, el modulo debe poder crear o actualizar una notificacion en `erp_notificaciones`.

Reglas:

- `modulo_origen`: `proyectos`.
- `entidad_origen`: `erp_proyecto_tareas`.
- `id_entidad_origen`: `id_tarea`.
- `area_responsable`: area de la tarea.
- `permiso_requerido`: permiso del modulo o `proyectos.ver`.
- `url_accion`: ruta a la tarea o proyecto.
- `payload_json.huella`: huella estable por tarea.

Las notificaciones se deben resolver cuando la tarea pase a `completada`, `descartada` o `cancelada`.

## Permisos propuestos

- `proyectos.ver`: consultar proyectos y tareas visibles.
- `proyectos.crear`: crear proyectos y tareas.
- `proyectos.editar`: editar datos, objetivos y tareas.
- `proyectos.asignar`: cambiar responsables.
- `proyectos.cerrar`: cerrar tareas, objetivos o proyectos.
- `proyectos.auditoria`: ver actividad completa y eventos.
- `proyectos.configurar`: administrar tipos, categorias o reglas.

Roles recomendados:

- `administrador_erp`: todos los permisos.
- `soporte_sistema`: ver, editar, asignar, cerrar, auditoria y configurar.
- roles operativos: al menos `proyectos.ver`; permisos de crear/editar segun responsabilidad.

## Pantallas recomendadas

### Bandeja de proyectos

Equivalente operativo de `My Projects`.

Debe mostrar:

- proyectos activos;
- avance por tareas;
- pendientes vencidas;
- tareas bloqueadas;
- responsable;
- modulo relacionado;
- prioridad;
- acceso rapido a crear tarea.

### Vista de proyecto

Debe funcionar como centro de trabajo.

Secciones:

- resumen;
- objetivos;
- tareas;
- actividad;
- archivos;
- configuracion.

### Tareas

Equivalente operativo de `Targets`.

Debe priorizar:

- busqueda rapida;
- filtros por responsable, estado, prioridad, modulo y vencimiento;
- cambio de estado simple;
- asignacion;
- comentarios cortos;
- enlace a entidad o documento relacionado.

### Mi trabajo

Bandeja personal por usuario.

Debe listar:

- tareas asignadas a mi;
- vencidas;
- de alta prioridad;
- bloqueadas;
- en revision del dueno.

### Captura rapida

Formulario ligero para convertir notas sueltas en tareas.

Campos:

- titulo;
- descripcion;
- proyecto;
- modulo relacionado;
- prioridad;
- responsable;
- fecha;
- origen: WhatsApp, chat IA, operacion, UAT, cliente, auditoria.

## Relacion con documentos vivos

El modulo de proyectos no debe reemplazar documentos rectores como `docs/erp_compras_vision_operativa.md`.

Regla:

- la tarea vive en BD para ejecucion;
- la decision reusable vive en `docs/`;
- la auditoria registra quien hizo cambios sensibles;
- la notificacion avisa trabajo activo.

Cuando una tarea cierre una decision importante, debe actualizar el documento vivo del modulo correspondiente.

## Fases recomendadas

### Fase 1 - Base operativa

- Crear `Proyecto.php`.
- Crear `ProyectosErp.php`.
- Crear `ProyectosEsquema.php`.
- Agregar permisos en `SeguridadEsquema.php`.
- Agregar controlador protegido en `Core.php`.
- Agregar menu en sidebar.
- Crear bandeja de proyectos y tareas.
- Crear endpoints de listar, consultar, guardar, cambiar estatus y asignar.
- Integrar notificaciones al crear/asignar/vencer/cerrar tareas.

### Fase 2 - Captura rapida y continuidad IA

- Formulario de captura rapida.
- Campo `origen` para WhatsApp, chat IA o UAT.
- Relacion opcional con documento vivo o ruta local.
- Bandeja `Mi trabajo`.
- Filtros de tareas por modulo ERP.

### Fase 3 - Actividad, archivos y cierre robusto

- Adjuntos.
- Comentarios.
- Eventos.
- Cierre con motivo o evidencia.
- Reporte de avance por modulo.
- Exportacion o resumen para siguiente chat/agente.

### Fase 4 - Automatizaciones

- Generar tareas desde incidencias de modulos.
- Crear tareas desde UAT o errores detectados.
- Plantillas de proyecto para nuevos modulos.
- Dependencias entre tareas.

## Riesgos y decisiones pendientes

- Definir si tareas personales muy simples tambien entran al ERP o si solo se aceptan tareas ligadas a proyecto.
- Definir si habra proyectos privados o todo sera visible por permisos/roles.
- Definir si se permitiran tareas sin responsable.
- Definir si tareas vencidas generaran notificacion diaria o solo contador persistente.
- Definir politica de adjuntos antes de subir archivos grandes.

## Recomendacion inicial

Construir primero una version simple pero real:

- proyectos;
- objetivos;
- tareas;
- responsables;
- estados;
- prioridades;
- comentarios basicos;
- notificaciones;
- bandeja `Mi trabajo`.

No iniciar con presupuesto/inversion. Si despues se requiere costo de proyecto, debe agregarse como extension financiera, no como nucleo del modulo.

## Handoff / continuidad

Fecha: 2026-07-24

- Contexto actual: el dueno necesita dejar de depender de WhatsApp personal y memoria de chat para recordar pendientes de construccion del ERP y actividades del negocio.
- Decision: adaptar el concepto de Projects de Metronic a un modulo operativo de proyectos/tareas, sin centrarlo en presupuesto.
- Cambios implementados: se preparo esqueleto MVC con controlador `Proyecto`, modelos `ProyectosErp` y `ProyectosEsquema`, vista `apps/erp/proyectos/listado`, JS de bandeja, permisos base y menu lateral.
- Regla aplicada: no se precargan tareas ni avances de Compras, POS, Catalogo, TMS ni ningun otro modulo; cada modulo agregara sus tareas cuando el dueno lo indique.
- Pendientes: aplicar esquema de BD de Proyectos y sincronizar permisos base con respaldo/autorizacion antes de uso real.
- Impacta a: Seguridad, Notificaciones, todos los modulos ERP que generen pendientes, documentacion viva.
- Siguiente paso recomendado: ejecutar auditoria/dry-run de esquema y luego aplicar DDL con respaldo externo si el dueno autoriza.
