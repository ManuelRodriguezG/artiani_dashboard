# Sistema - Arquitectura modular principal

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-28  
Estado: documento rector vivo; no implica escrituras en BD.

## Proposito

El proyecto deja de entenderse como "solo ERP" y pasa a organizarse como un sistema empresarial modular. ERP sigue siendo un dominio central, pero no debe absorber responsabilidades propias de CRM, Ecommerce, SYS, POS, BI u otros dominios.

Esta separacion busca evitar que una pantalla o tabla crezca como solucion unica para ventas, clientes, inventario, comercio digital, seguridad y reportes.

## Principio rector

Cada modulo debe tener un dueno de dominio claro:

- ERP administra operacion interna: catalogo operativo, compras, proveedores, almacen, inventario, costos y procesos administrativos.
- CRM administra clientes: identidad, contactos, historial, segmentos, listas, recompensas, garantias relacionadas a cliente y relacion comercial.
- POS administra el punto de venta: caja, turno, cobro, ticket, atenciones, ventas de mostrador y consumo rapido de precios/clientes/inventario.
- Ecommerce administra venta digital: catalogo publicado, carrito web, pedidos web, clientes digitales y sincronizaciones externas.
- SYS administra plataforma: usuarios, roles, permisos, auditoria, configuracion, seguridad, notificaciones y parametros transversales.
- BI/REPORTES consolida analitica: tableros, KPIs, cortes historicos y consultas gerenciales.

Ningun modulo debe duplicar la verdad de otro. Si necesita datos, debe consumirlos mediante relacion explicita, snapshot o tabla de integracion.

## Agrupacion recomendada inicial

### SYS - Sistema, seguridad y plataforma

Incluye:

- Usuarios, roles y permisos.
- Auditoria.
- Sesion, CSRF y seguridad transversal.
- Notificaciones/alertas operativas.
- Parametros generales del sistema.
- Soporte tecnico y endpoints de esquema autorizados.

Archivos actuales relacionados:

- `app/controladores/Sistema.php`
- `app/modelos/SeguridadPermisos.php`
- `app/modelos/SeguridadEsquema.php`
- `app/core/Core.php`
- `app/core/SesionSeguridad.php`
- `docs/erp_notificaciones_alertas_trabajo.md`

Nota: aunque varios documentos digan ERP, estas capacidades pertenecen a SYS porque sirven a todos los dominios.

### ERP - Operacion interna

Incluye:

- Catalogo operativo maestro.
- Compras.
- Proveedores y listas de proveedor.
- Almacen.
- Inventario.
- Costos y rentabilidad operativa.
- Utilidad administrativa.
- Finanzas operativas futuras cuando se formalice.

Archivos actuales relacionados:

- `app/controladores/CatalogoErp.php`
- `app/controladores/Compra.php`
- `app/controladores/Proveedor.php`
- `app/controladores/Almacen.php`
- `app/controladores/Inventario.php`
- `app/controladores/Costo.php`
- `app/modelos/*Erp.php` y `*Esquema.php` de esos dominios.

Regla: ERP no debe ser dueno de clientes finales; puede relacionar documentos con clientes via CRM.

### CRM - Clientes y relacion comercial

Incluye:

- Identidad de cliente.
- Telefonos, correos, codigos, RFC e identificadores externos.
- Ficha completa.
- Contactos y direcciones.
- Segmentos, listas/precios asignados al cliente.
- Consentimientos.
- Historial de compra, garantias, apartados, devoluciones y postventa.
- Recompensas, niveles, cupones o monedero.
- Duplicados, fusion y auditoria de relacion con ecommerce/legacy.

Estado actual:

- Existe `app/modelos/Cliente.php`, pero apunta a `crm_clientes` y esta ligado al flujo legacy/ecommerce.
- Existe `app/controladores/Clientes.php`, pero hoy gestiona carga de fotos/imagenes de clientes-productos, no un CRM operativo.
- Existen tablas POS expandidas `erp_clientes` y `erp_clientes_identificadores`, utiles como fuente UAT/legacy, pero no son el dueno canonico.
- Existe modulo CRM formal:
  - `app/controladores/Crm.php`
  - `app/modelos/ClientesCrm.php`
  - `app/modelos/ClientesCrmEsquema.php`
  - vistas `app/vistas/paginas/apps/crm/clientes/*`
  - JS `public/assets/js/custom/apps/crm/clientes/*`
- Existen tablas canonicas `crm_clientes_*` para maestro, identificadores, contactos, direcciones, fiscales, segmentos, consentimientos, notas, eventos, vinculos externos y fusiones.
- La migracion masiva legacy esta pausada por decision operativa; lo ya migrado queda como historico/auditoria y no como base comercial limpia.

Decision recomendada:

- Mantener modulo CRM/Clientes formal y no ampliar el controlador legacy `Clientes.php` sin auditoria.
- Mantener POS consumiendo clientes por contrato, no como dueno del modelo.
- Auditar `crm_clientes` legacy solo para casos puntuales rescatables; no continuar migracion masiva sin nueva decision explicita.
- Priorizar clientes nuevos reales con identificador, contacto, permiso, consentimiento y ficha operativa.

### POS - Punto de venta

Incluye:

- Caja, terminal, turno y movimientos de caja.
- Venta POS, pagos, ticket y reimpresion.
- Atenciones compartidas.
- Pedidos/apartados operados desde mostrador.
- Consumo de cliente/precio/inventario mediante contratos.

Archivos actuales relacionados:

- `app/controladores/Ventas.php`
- `app/modelos/VentasErp.php`
- `app/modelos/VentasErpEsquema.php`
- `app/vistas/paginas/apps/erp/ventas/pos.php`
- `public/assets/js/custom/apps/erp/ventas/pos.js`

Regla: POS puede crear alta rapida express solo mediante contrato CRM autorizado; no debe convertirse en ficha completa de cliente.

### ECOMMERCE - Comercio digital y legado web

Incluye:

- Catalogo publicado en web.
- Pedidos ecommerce.
- Clientes digitales.
- Imagenes y contenido comercial web.
- Sincronizacion con ERP/CRM.

Archivos actuales relacionados:

- `app/vistas/paginas/apps/ecommerce/*`
- `public/assets/js/custom/apps/customers/*`
- Tablas `ecom_*` y tablas legacy relacionadas.

Regla: ecommerce no debe ser fuente operativa directa para ERP/CRM nuevo sin tabla de vinculacion, auditoria e incidencias.

### POSTVENTA - Garantias, devoluciones y servicio

Incluye:

- Reclamos de garantia.
- Devoluciones.
- Evidencia de cliente/producto.
- Resoluciones: cambio, reparacion, rechazo, nota, reembolso.
- Destino fisico del producto devuelto con Inventario/Almacen.

Documento relacionado:

- `docs/erp_garantias_plan.md`

Decision: puede iniciar como subdominio coordinado entre POS/Ventas, CRM, Catalogo, Inventario y Proveedores; si crece, conviene controlador/modelo propio.

### BI / Reportes

Incluye:

- Reportes gerenciales.
- KPIs por tienda, cliente, producto, proveedor, turno, utilidad, recompensas, devoluciones y garantias.
- Vistas consolidadas sin ser dueno de la captura.

Regla: reportes leen snapshots e historiales; no deben recalcular ventas pasadas con reglas actuales.

## Contratos transversales

- Una venta puede cerrarse sin cliente: `id_cliente=NULL` y snapshot `Publico general`.
- Si una venta usa cliente, debe guardar snapshot de nombre, identificador, lista, segmento y beneficios aplicados.
- CRM puede vincular clientes legacy/ecommerce, pero no sobrescribirlos sin auditoria.
- ERP provee inventario y catalogo; POS consume disponibilidad y registra salida autorizada.
- CRM provee identidad, lista y beneficios; POS consume resolucion final de precio/cliente.
- SYS provee permisos, auditoria y notificaciones para todos.
- BI lee datos historicos y snapshots, no gobierna reglas operativas.

## Fases recomendadas para CRM Clientes

### Fase 1 - Minimo para POS

- Venta a publico general.
- Busqueda express por telefono, correo, codigo o nombre.
- Alta rapida express con identificador unico.
- Duplicado por identificador bloqueado o sugerido.
- Snapshot de cliente/lista/precio en venta.
- Sin ficha completa dentro del cobro.

Estado 2026-06-29:

- Esquema CRM Clientes base creado.
- Permisos CRM base sembrados.
- Alta express CRM autorizada validada con cliente UAT.
- POS/Ventas ya puede guardar `id_cliente_crm` y snapshot CRM en ventas nuevas cuando consume cliente CRM.

### Fase 2 - CRM operativo

- Controlador y modelo CRM Clientes formal.
- Ficha completa fuera del POS.
- Contactos, direcciones, identificadores multiples y consentimiento.
- Historial de ventas, apartados, devoluciones y garantias.
- Deteccion/fusion de duplicados.
- Auditoria y permisos finos.

Estado 2026-06-29:

- Ficha CRM operativa disponible con identidad, complementos, eventos y vinculos.
- Complementos `contacto`, `direccion`, `fiscal`, `nota` y `consentimiento` tienen dry-run y apply autorizado preparado.
- La ficha calcula `calidad_operativa` y muestra pendientes de captura.
- La consola CRM tiene cola read-only de calidad para priorizar clientes incompletos.
- Crear complementos reales requiere token fuerte `CRM_CLIENTES_COMPLEMENTO` y respaldo valido.
- DDL de seguimiento/tareas CRM esta preparado como capa separada con token futuro `CRM_CLIENTES_SEGUIMIENTO_DDL`.

### Fase 3 - CRM comercial

- Segmentos.
- Listas de precios por cliente, segmento, canal, sucursal y vigencia.
- Promociones/cupones.
- Recompensas o monedero.
- Campanas y consentimiento de contacto.

### Fase 4 - CRM avanzado

- Integracion ecommerce/legacy con tabla de vinculos externos.
- Scoring, frecuencia, recurrencia y alertas comerciales.
- Garantias/postventa por cliente.
- Reportes de valor de cliente, retencion, recompra y devoluciones.
- Automatizaciones y tareas comerciales.

## Pendientes de decision

- Politica fina de privacidad y consentimiento de contacto por canal.
- Reglas de fusion de duplicados.
- Alcance de recompensas: puntos, monedero, cupones o mezcla.
- Si se creara un dominio propio POSTVENTA separado para garantias/devoluciones o si iniciara coordinado por CRM/POS con modelo propio.
- Si se creara BI/REPORTES como modulo navegable separado o primero como vistas read-only por dominio.

## Handoff / continuidad

Fecha: 2026-06-28

- Contexto actual: POS ya tiene tablas minimas de cliente/lista para UAT, pero no existe CRM completo.
- Decision: Clientes debe ser CRM, no subfuncion de Ventas ni de Ecommerce.
- Riesgo: ampliar `Cliente.php`/`Clientes.php` legacy puede mezclar ecommerce, fotos y clientes operativos sin auditoria.
- Siguiente paso recomendado: crear `docs/crm_clientes_plan.md` o evolucionar `docs/erp_ventas_pos_clientes_plan.md` hacia CRM, y despues preparar `ClientesCrm.php`/`ClientesCrmEsquema.php` sin ejecutar DDL.

Actualizacion 2026-06-29:

- CRM Clientes ya tiene plan vivo en `docs/crm_clientes_plan.md`.
- CRM Clientes ya tiene controlador, modelo, esquema, vistas, JS, permisos y tablas canonicas.
- `erp_clientes` queda como fuente UAT/legacy, no como cliente canonico.
- POS debe continuar desde su propio plan consumiendo CRM, no definiendo clientes.
- Siguiente paso CRM: crear complementos reales para clientes nuevos con autorizacion fuerte o disenar tareas/seguimientos CRM persistentes con DDL separado.
- Seguimiento persistente debe vivir en CRM (`crm_clientes_interacciones`, `crm_clientes_tareas`) y solo integrarse con SYS Notificaciones despues de definir el contrato transversal.

Actualizacion 2026-06-30:

- El sidebar deja de presentar todo como una sola seccion `Operacion`.
- La navegacion principal se agrupa por dominios:
  - `ERP`: Catalogo, Rentabilidad, Compras, Proveedores, Almacen e Inventario;
  - `POS`: Ventas, POS y pedidos;
  - `CRM`: Clientes, Seguimiento, Comercial, Recompensas y Auditoria;
  - `Postventa`: Garantias y futuros flujos de servicio;
  - `Ecommerce`: comercio digital y legado web;
  - `Administracion`: SYS, usuarios, roles y seguridad.
- CRM Clientes queda dividido en pestanas operativas:
  - `Operacion`;
  - `Comercial`;
  - `Recompensas`;
  - `Clientes`;
  - `Auditoria legacy`.
- Mientras no existan pantallas dedicadas por submodulo, los accesos CRM del sidebar abren `/crm/clientes` con hash `#crm_tab_*`.
- Regla UX: ninguna capacidad con estado propio, saldo, movimientos o acciones frecuentes debe quedarse escondida como panel secundario. Debe subir a pestana o submodulo.
- CRM Seguimiento ya tiene esquema persistente aplicado:
  - `crm_clientes_interacciones`;
  - `crm_clientes_tareas`.
- Este avance solo habilita la base tecnica; crear interacciones o tareas reales requiere autorizacion fuerte separada por caso.
- `CRM > Seguimiento` ya cuenta con pantalla dedicada `/crm/seguimiento` en modo lectura:
  - tareas por estatus;
  - KPIs operativos;
  - interacciones recientes.
- `CRM > Recompensas` ya cuenta con pantalla dedicada `/crm/recompensas` en modo lectura:
  - programas;
  - cuentas;
  - saldos;
  - movimientos recientes.
- La pantalla dedicada confirma la regla modular: cuando una pestana de CRM adquiere flujo propio, sube a submodulo navegable.

Actualizacion 2026-07-23:

- `Administracion > Configuracion del sistema` queda como pantalla SYS read-only en `/sistema/configuracion`.
- La pantalla revisa entorno, URL base, conexion de BD activa e implicaciones de impresion POS sin exponer credenciales.
- Decision: la configuracion general de ambiente/BD pertenece a SYS; POS solo debe consumir configuracion de terminal, caja e impresion.
- Para tickets en productivo se recomienda un puente/agente local por terminal o sucursal, porque la impresora instalada vive en la computadora local aunque el sistema web corra en servidor productivo.
- Documento vivo especifico: `docs/sistema_configuracion_plan.md`.
