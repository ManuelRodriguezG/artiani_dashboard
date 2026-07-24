# Sistema - Configuracion general y entorno

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-23  
Estado: consola configurable con esquema SYS acotado.

## Proposito

Centralizar en SYS/Administracion la revision de entorno, base de datos activa y preparacion de impresion POS.

## Decision

- La configuracion general pertenece a SYS, no a POS ni a ERP.
- POS puede consumir configuracion de ticket/terminal, pero no debe decidir la base de datos ni el ambiente.
- La seleccion actual de base de datos depende de `SERVER_NAME` en `app/config/mysql.php`.
- La UI muestra diagnostico saneado: host, ambiente, URL, BD activa y estado de conexion.
- La UI permite guardar parametros operativos no sensibles en `sys_configuracion_parametros`.
- No se muestran contrasenas ni se guardan credenciales desde la pantalla general.

## Impresion de tickets

Para pruebas locales, la impresion directa debe resolverse en la computadora donde esta instalada la impresora.

Para productivo, la opcion recomendada es un puente/agente local por terminal o sucursal:

- el sistema web corre en productivo;
- la terminal local mantiene instalada la impresora de tickets;
- POS solicita impresion al puente local;
- el puente local imprime y devuelve estado;
- cada intento debe quedar auditable cuando se implemente el flujo real.

## Cambios aplicados

- `app/modelos/SistemaConfiguracion.php`: diagnostico saneado de entorno/BD/tickets.
- `app/modelos/SistemaConfiguracionEsquema.php`: tablas de parametros e historial.
- `app/controladores/Sistema.php`: ruta `/sistema/configuracion`.
- `app/vistas/paginas/apps/erp/sistema/configuracion.php`: consola read-only.
- `public/assets/js/custom/apps/erp/sistema/configuracion.js`: guardado AJAX de parametros.
- `app/vistas/includes/header/sidebar.php`: Administracion queda como grupo con Configuracion, Usuarios y Notificaciones.

## Esquema aplicado

Tablas:

- `sys_configuracion_parametros`
- `sys_configuracion_historial`

Regla: la tabla guarda configuracion operativa y tecnica no sensible. Las credenciales de conexion siguen fuera de la UI.

Aplicacion 2026-07-23:

- Respaldo previo: `C:\xampp\panel_db_backups\artianilocal_panel_20260723_171000_antes_sistema_configuracion.sql`
- Validacion respaldo: archivo existente, tamano `30669797` bytes.
- DDL aplicado en base local `artianilocal`.
- Semillas cargadas: 9 parametros iniciales.
- Prueba de guardado sin cambios: `total_actualizados=0`, respuesta correcta.

## Pendientes

- Definir archivo/perfil explicito de entorno antes de permitir cambios desde UI.
- Disenar tablas de parametros SYS si se requiere persistir configuraciones no sensibles.
- Disenar conector local de impresora POS y prueba de impresion.
- Separar credenciales sensibles de configuracion navegable.

## Handoff / continuidad

Fecha: 2026-07-23

- Contexto actual: el negocio esta probando en local y necesita preparar impresion de tickets y futura operacion productiva.
- Decision: SYS sera el punto de entrada para configuracion general; POS solo consumira configuracion de terminal/ticket.
- Riesgo: editar credenciales desde UI sin separarlas podria exponer secretos o romper ambientes.
- Siguiente paso recomendado: preparar plan tecnico del puente local de impresion POS y, aparte, plan de perfiles de entorno sin ejecutar cambios de BD.
