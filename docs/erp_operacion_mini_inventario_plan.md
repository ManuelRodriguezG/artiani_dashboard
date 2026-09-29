# ERP Operacion - Mini inventario operativo

Documentacion IA: Codex GPT-5  
Fecha: 2026-09-28  
Estado: version puente con mini inventarios locales personalizados, sin escrituras de BD

## Proposito

El negocio aun no puede operar con inventario formal al 100%. Este modulo puente ayuda a crear mini inventarios personalizados, agregar solo los productos que se quieren revisar, capturar cantidades listas para venta y generar tareas operativas sin afectar Inventario oficial.

## Decision operativa

- No crea Kardex.
- No modifica existencias.
- No reemplaza Almacen, Inventario ni Compras.
- Lee Catalogo ERP, reglas min/max/reorden, proveedor preferido y relaciones de preparacion/apertura cuando existen.
- No carga todo el catalogo al abrir; el usuario debe buscar por texto, proveedor, categoria o enfoque.
- Cada mini inventario local conserva nombre, estado, filtros, productos agregados, cantidades, responsables y notas.
- La captura queda en el navegador mediante `localStorage`.
- Las salidas operativas son CSV, texto copiable de tareas y borrador de compra agrupado por proveedor.

## Alcance fase 1

Ruta:

- `/operacion/mini_inventarios`
- `/operacion/mini_inventario`

Backend:

- `Operacion::mini_inventario`
- `Operacion::mini_inventarios`
- `Operacion::mini_inventario_catalogos_erp`
- `Operacion::mini_inventario_productos_erp`
- `OperacionMiniInventarioErp`

Frontend:

- `app/vistas/paginas/apps/erp/operacion/mini_inventario.php`
- `app/vistas/paginas/apps/erp/operacion/mini_inventarios.php`
- `public/assets/js/custom/apps/erp/operacion/mini_inventario.js`
- `public/assets/js/custom/apps/erp/operacion/mini_inventarios.js`

Permisos temporales:

- Puede acceder quien tenga alguno de:
  - `compras.ver`
  - `catalogo.ver`
  - `almacen.ver`
  - `inventario.ver`

## Reglas de sugerido

- Un mini inventario se crea manualmente con `Nuevo`.
- La vista `/operacion/mini_inventarios` funciona como bandeja para ver todos los mini inventarios locales y abrirlos en modo ver/editar.
- La vista `/operacion/mini_inventario#FOLIO_LOCAL` funciona como editor del mini inventario seleccionado.
- Crear `Nuevo` desde la bandeja abre `/operacion/mini_inventario#nuevo`; el nombre se captura en el editor y el usuario decide si guarda el borrador local.
- Los productos se agregan desde `Resultados para agregar`; no se agregan automaticamente todos los productos de una busqueda.
- Los mini inventarios pueden retomarse desde el selector superior mientras existan en el mismo navegador.
- Estados locales:
  - `Borrador`
  - `En revision`
  - `Listo para decision`
  - `Cerrado manual`
- `Listo venta` es captura operativa manual.
- Si `Listo venta` esta vacio, la pantalla puede mostrar existencia de sistema como referencia cuando se selecciona almacen, pero no la toma como verdad obligatoria.
- `Min` y `Max` pueden ajustarse dentro del mini inventario sin modificar Catalogo; sirven para decidir la necesidad operativa de ese conteo.
- `Cantidad` permite ajustar manualmente lo que se va a pedir/trabajar; si queda vacia usa la cantidad sugerida.
- Si un SKU tiene regla de presentacion, la accion sugerida es `Reempacar`.
- Si un SKU tiene regla de apertura de empaque, la accion sugerida es `Abrir empaque`.
- Para `Abrir empaque`, el editor calcula el plan por SKU origen: cantidad requerida, factor, empaques completos a abrir, salida generada y sobrante operativo.
- El sobrante operativo no se considera merma; queda como producto abierto disponible para venta suelta o revision operativa.
- Si genera etiqueta interna, puede sugerir `Etiquetar`.
- Si no hay relacion operativa clara y queda debajo del minimo/reorden, la accion automatica es `Comprar`.
- Si no hay relacion operativa clara y no hay faltante, queda sin accion o en `Revisar` si el usuario lo marca manualmente.
- Si el usuario selecciona `Comprar`, el modulo no genera solicitud automaticamente en fase 1; solo deja tarea/exportacion para alimentar Compras.

## Frontera con modulos formales

- Compras > Sugerido de compra por proveedor decide que pedir al proveedor.
- Operacion > Mini inventario decide que esta listo para venta y que tarea falta.
- Almacen > Preparacion/Empaque ejecuta preparaciones formales cuando ya se quiera afectar inventario.
- Almacen > Apertura de empaques ejecuta aperturas formales cuando ya se quiera afectar inventario.
- Inventario conserva saldos oficiales, Kardex y trazabilidad.

## UAT manual fase 1

1. Abrir `http://panel.com.local/operacion/mini_inventarios`.
2. Crear un mini inventario con `Nuevo`.
3. Validar que abre el editor en modo nuevo sin pedir nombre por alerta.
4. Poner nombre y estado en el editor.
5. Buscar por SKU, proveedor, categoria o enfoque.
6. Agregar solo los productos requeridos desde `Resultados para agregar`.
7. Capturar `Listo venta` para varios SKUs.
8. Cambiar accion a `Reempacar`, `Abrir empaque`, `Etiquetar`, `Comprar` o `Revisar`.
9. Ajustar `Min` y `Max` para validar que recalcula sugerido.
10. Capturar responsable y nota.
11. Ajustar `Cantidad` cuando el sugerido necesite modificarse.
12. En SKUs con `Abrir empaque`, validar `Plan de apertura de empaques`.
13. Guardar el mini inventario local.
14. Regresar a `Bandeja` y validar resumen por mini inventario.
15. Abrir de nuevo con `Ver / editar`.
16. Usar `Copiar tareas`.
17. Usar `Copiar compra` y validar agrupacion por proveedor.
18. Usar `CSV`.
19. Usar `Imprimir`.
20. Recargar pagina y validar que `Restaurar captura` conserva datos del navegador.

## Pendiente fase 2

Cuando el dueno autorice persistencia:

- Crear esquema con respaldo externo.
- Guardar revisiones por folio.
- Guardar responsables/estatus.
- Convertir tareas seleccionadas en:
  - pre-sugerido de compra;
  - borrador de preparacion;
  - borrador de apertura;
  - pendiente operativo/notificacion.
