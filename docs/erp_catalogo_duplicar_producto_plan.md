# ERP Catalogo - Plan para duplicar producto

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-19  
Estado: Fase 1 implementada  
Proyecto activo: `C:\xampp\htdocs\panel_de_control`

## Proposito

Agregar una funcion controlada para duplicar un producto ERP cuando el nuevo producto comparte gran parte de la configuracion del producto origen.

El objetivo no es crear copias exactas, sino acelerar altas similares sin generar duplicados peligrosos de identidad, imagenes, codigos, proveedores, costos, inventario o relaciones con otros modulos.

## Problema operativo

Hay productos que comparten:

- marca;
- categoria;
- unidad base;
- reglas fiscales;
- reglas de inventario;
- granel/presentaciones;
- estructura de variantes;
- configuracion general de SKU.

Capturarlos desde cero toma tiempo. Pero duplicar todo sin control puede causar errores graves:

- SKU o codigo interno duplicado;
- codigo de barras repetido;
- imagen incorrecta;
- proveedor equivocado;
- costo/precio heredado sin validacion;
- regla de inventario que no aplica;
- paquete o presentacion apuntando al SKU origen.

## Regla base

Duplicar producto debe crear un producto nuevo en estado `borrador` o `en_revision`, nunca como copia lista para operar sin revision.

El operador debe capturar obligatoriamente:

- codigo interno nuevo del producto;
- nombre nuevo del producto;
- al menos un SKU nuevo si se copia SKU;
- motivo/nota de duplicacion.

## Flujo UX recomendado

Entrada:

- Boton `Duplicar` dentro del modal de producto o en acciones del listado.
- Al presionarlo, abrir modal `Duplicar producto`.

Paso 1 - Producto destino:

- Producto origen visible en solo lectura.
- Campo requerido `Codigo nuevo`.
- Campo requerido `Nombre nuevo`.
- Campo opcional `Descripcion nueva`.
- Estado inicial:
  - recomendado: `borrador`;
  - opcion alternativa: `en_revision`.
- Nota requerida: por que se duplica.

Paso 2 - Selectores de copia:

Usar switches/checkboxes agrupados por seccion:

| Seccion | Default | Regla |
| --- | --- | --- |
| Marca | Activado | Copiable si el producto nuevo pertenece a la misma marca. |
| Categoria principal | Activado | Copiable; permitir cambiar antes de guardar. |
| Categorias secundarias | Activado | Copiable; util si comparte familias. |
| Descripcion base | Activado | Copiable pero editable. |
| SKU base | Activado | Requiere capturar SKU nuevo. |
| Unidad base y factor | Activado | Copiable; normalmente acelera productos similares. |
| Tipo de inventario | Activado | Copiable; validar si cambia de inventariable a servicio/kit. |
| Reglas fiscales | Opcional activado | Copiar solo si fiscalmente es equivalente; dejar en revision si hay duda. |
| Reglas de inventario | Opcional activado | Copiar flags generales; stock minimo/maximo/reorden deben poder excluirse. |
| Granel/fraccionable | Opcional activado | Copiar si la forma de venta es igual. |
| Recepcion variable | Opcional activado | Copiar si la recepcion fisica se maneja igual. |
| Proveedores | Desactivado | No copiar por defecto; si se copia, debe quedar como pendiente/revision. |
| Imagenes | Desactivado | No copiar por defecto; imagen distinta para producto distinto. |
| Codigos de barras | Desactivado/bloqueado | Nunca copiar codigos exactos. |
| Precio provisional | Desactivado | Precios pertenecen a Listas de precios/Ventas. |
| Costo referencia | Desactivado | Costos pertenecen a Proveedores/Compras/Costos. |
| Paquetes | Desactivado fase 1 | Requiere analisis porque componentes pueden apuntar al origen. |
| Presentaciones | Desactivado fase 1 | Requiere mapear SKU base/presentacion del nuevo producto. |

Paso 3 - Previsualizacion:

Antes de guardar, mostrar resumen:

- producto origen;
- producto nuevo;
- SKU(s) nuevos que se crearan;
- secciones copiadas;
- secciones omitidas;
- advertencias.

Paso 4 - Guardado:

- Crear producto nuevo.
- Crear SKU(s) nuevo(s) segun seleccion.
- Copiar solo las secciones autorizadas.
- Registrar auditoria explicita.
- Abrir automaticamente el producto nuevo al terminar.

## Que se debe copiar en fase 1

Fase 1 debe ser conservadora:

1. Producto maestro:
   - `tipo_producto`;
   - `id_marca_erp` si se selecciona;
   - `maneja_variantes`;
   - `descripcion` si se selecciona;
   - categorias seleccionadas.

2. SKU base o SKU elegido:
   - nombre SKU como sugerencia editable;
   - `tipo_inventario`;
   - `id_unidad_base`;
   - `factor_unidad_base`;
   - `permite_venta_sin_existencia` solo si se selecciona;
   - estatus inicial `borrador` o `en_revision`.

3. Fiscal:
   - `erp_catalogo_sku_impuestos` si se selecciona;
   - recomendado dejar alerta visual: "Fiscal copiado, validar si aplica".

4. Reglas de inventario:
   - `controla_inventario`;
   - lote/caducidad/serie;
   - estrategia de salida;
   - granel/fraccionable;
   - recepcion variable;
   - etiquetado;
   - excluir por default stock minimo, maximo y reorden, salvo checkbox especifico.

5. Variantes:
   - fase 1: copiar atributos/valores solo si se duplican todos los SKUs y se captura transformacion de SKU.
   - si se duplica solo un SKU, no copiar matriz de variantes.

## Que no se debe copiar nunca automaticamente

- `id_producto_erp`, `id_sku`, `id_imagen_erp` y cualquier llave primaria.
- `codigo_producto` exacto.
- `sku` exacto.
- `codigo_barras` exacto o codigos internos escaneables.
- movimientos, existencias, recepciones, compras, ventas.
- vinculos ecommerce.
- incidencias de calidad.
- fusiones.
- auditoria historica.
- imagenes como portada sin seleccion explicita.
- costos reales o precios finales.

## Proveedores

Regla recomendada:

- No copiar proveedores por default.
- Si el operador activa `Copiar proveedores`, la UI debe explicar que:
  - no se esta validando costo;
  - el SKU proveedor puede no aplicar al producto nuevo;
  - la relacion quedara en `en_revision` o equivalente operativo si existe estatus suficiente.

Si el esquema actual solo permite `activo`/`inactivo` en `erp_catalogo_sku_proveedores`, recomendacion:

- fase 1: no copiar proveedores;
- fase 2: agregar flujo de "sugerir proveedor" o incidencia de revision, no relacion activa ciega.

## Imagenes

Regla recomendada:

- No copiar imagenes por default.
- Si se habilita en fase posterior, debe ser una accion separada:
  - copiar como `referencia`;
  - no marcar portada automaticamente;
  - pedir confirmacion manual imagen por imagen.

Motivo:

- En productos distintos, una imagen heredada puede ser mas peligrosa que no tener imagen.
- Una imagen incorrecta puede afectar ventas, ecommerce y picking visual.

## Paquetes y presentaciones

No incluir en fase 1.

Riesgo:

- Presentaciones y paquetes referencian SKUs concretos.
- Si se copian sin mapear origen/destino, el paquete nuevo podria consumir componentes del producto anterior.

Fase posterior:

- permitir duplicar receta solo cuando exista un mapa completo:
  - SKU origen => SKU destino;
  - componente origen => componente destino o conservar componente compartido.

## Backend propuesto

Endpoint:

```text
POST /catalogoerp/duplicar_producto
```

Permiso:

```text
catalogo.editar
```

Modelo:

```php
CatalogoErpDatos::duplicarProducto($datos, $idUsuario)
```

Contrato de entrada:

```json
{
  "id_producto_origen": 123,
  "codigo_producto": "NUEVO-CODIGO",
  "nombre_producto": "Nuevo producto",
  "nota_duplicacion": "Alta similar por configuracion comun",
  "estatus": "borrador",
  "opciones": {
    "marca": true,
    "categoria_principal": true,
    "categorias_secundarias": true,
    "descripcion": true,
    "sku_base": true,
    "fiscal": true,
    "reglas_inventario": true,
    "stock_reorden": false,
    "proveedores": false,
    "imagenes": false
  },
  "skus": [
    {
      "id_sku_origen": 456,
      "sku_nuevo": "NUEVO-SKU",
      "nombre_sku": "Nuevo SKU"
    }
  ]
}
```

Respuesta:

```json
{
  "error": false,
  "tipo": "success",
  "mensaje": "Producto duplicado en borrador",
  "depurar": {
    "id_producto_origen": 123,
    "id_producto_erp": 789,
    "skus_creados": 1,
    "opciones_copiadas": []
  }
}
```

## Validaciones obligatorias

- Producto origen existe y no esta `fusionado`.
- Codigo nuevo no existe.
- SKU nuevo no existe.
- Codigo de barras no se copia.
- Si se copia fiscal, validar que el SKU origen tiene fila fiscal.
- Si se copian reglas de inventario, crear reglas para cada SKU destino.
- Si se copian categorias, validar que siguen activas y permiten productos.
- Si se pide proveedor en fase 1, bloquear o convertirlo en pendiente; no insertar relacion activa ciega.
- Usar transaccion completa.
- Registrar auditoria con origen, destino y opciones copiadas.

## UI propuesta

Ubicacion:

- Boton `Duplicar` en modal de producto, junto a acciones principales.
- En listado podria agregarse despues, pero el modal da mas contexto y reduce errores.

Controles:

- Secciones con borde y titulo:
  - Producto maestro;
  - SKU;
  - Fiscal;
  - Inventario y granel;
  - Excluir por seguridad.
- Tooltips/ayuda breve por cada selector.
- Preview antes de guardar.

Textos sugeridos:

- `No se copiaran codigos de barras, imagenes ni relaciones operativas.`
- `El producto nuevo se creara en borrador para que puedas revisarlo antes de usarlo.`
- `Copiar fiscal solo si el producto nuevo comparte la misma naturaleza fiscal.`

## Auditoria

Accion:

```text
catalogo.duplicar_producto
```

Datos a registrar:

- `id_producto_origen`;
- `id_producto_destino`;
- usuario;
- opciones copiadas;
- SKUs origen/destino;
- resultado;
- mensaje.

## Plan de implementacion

1. Agregar documento de plan y aprobar alcance.
2. Agregar endpoint `duplicar_producto` en `CatalogoErp`.
3. Agregar metodo `duplicarProducto` en `CatalogoErpDatos`.
4. Implementar fase 1:
   - producto maestro;
   - categorias;
   - un SKU seleccionado;
   - fiscal opcional;
   - reglas inventario opcional;
   - sin imagenes/proveedores/paquetes/presentaciones.
5. Agregar modal UI con preview.
6. Validar PHP/JS.
7. Probar duplicacion controlada con un producto real de bajo riesgo.
8. Documentar resultado en `docs/erp_catalogo_avance.md`.

## Criterio de cierre fase 1

- Se puede duplicar un producto creando codigo y SKU nuevos.
- No se duplican codigos de barras.
- No se duplican imagenes.
- No se duplican proveedores activos.
- No se tocan existencias, movimientos, compras ni ventas.
- El nuevo producto queda en `borrador` o `en_revision`.
- Se abre el producto nuevo al terminar.
- Queda auditoria explicita.

## Implementacion fase 1

Fecha: 2026-07-19

Implementado en:

- `app/controladores/Catalogoerp.php`
- `app/modelos/CatalogoErpDatos.php`
- `app/vistas/paginas/apps/erp/catalogo/productos.php`
- `public/assets/js/custom/apps/erp/catalogo/productos.js`

Alcance real:

- Boton `Duplicar` dentro del modal de producto.
- Modal `Duplicar producto` con:
  - codigo nuevo;
  - nombre nuevo;
  - SKU origen;
  - SKU nuevo;
  - nombre SKU nuevo;
  - estado inicial `borrador` o `en_revision`;
  - nota requerida;
  - selectores de copia.
- Endpoint `POST /catalogoerp/duplicar_producto`.
- Metodo `CatalogoErpDatos::duplicarProducto`.
- Transaccion completa.
- Auditoria `catalogo.duplicar_producto`.

Copiable en fase 1:

- marca;
- categoria principal;
- categorias secundarias;
- descripcion;
- fiscal;
- reglas de inventario/granel/recepcion variable;
- stock minimo/maximo/reorden solo si se activa expresamente.

Siempre copiado por necesidad tecnica fase 1:

- unidad base;
- factor unidad base;
- tipo inventario.

Siempre omitido:

- imagenes;
- codigos de barras;
- proveedores;
- costos del origen;
- precios del origen;
- paquetes;
- presentaciones;
- compras;
- ventas;
- existencias;
- movimientos.

Nota tecnica:

- El alta normal de SKU crea un registro provisional de precio general y costo de referencia en `0`.
- Esto no significa que se copien costo o precio del producto origen.
- La definicion formal de precios por canal/lista queda fuera de Catalogo y debe resolverse en Ventas/Listas de precios.

Prueba recomendada:

1. Abrir un producto de bajo riesgo.
2. Presionar `Duplicar`.
3. Capturar codigo nuevo y SKU nuevo con prefijo claro de prueba.
4. Dejar estado `borrador`.
5. Desactivar `stock minimo/maximo/reorden` salvo que se quiera validar ese caso.
6. Guardar.
7. Confirmar que se abre el producto nuevo.
8. Revisar que no tenga imagenes, proveedores ni codigos de barras heredados.
