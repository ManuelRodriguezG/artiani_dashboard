# ERP Catalogo - Plan de paquetes configurables

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-26  
Estado: Rediseño funcional; sin cambios de esquema aplicados  
Alcance: Catalogo ERP define paquetes; Ventas captura seleccion final; Almacen/Inventario consume o arma

## Objetivo

Diseñar un modulo nuevo de paquetes para el ERP, sin tomar como base el modulo legacy.

Un paquete puede ser:

- simple: incluye siempre los mismos SKUs y cantidades;
- configurable: incluye componentes fijos y grupos de opciones donde el cliente u operador elige productos;
- virtual: no tiene existencia propia y consume componentes al vender;
- prearmado: se arma fisicamente y genera existencia de SKU paquete;
- combo comercial: regla de venta/precio que puede no requerir SKU paquete;
- comprado cerrado: el proveedor lo vende como unidad cerrada.

## Regla principal

Catalogo define la receta y las opciones posibles. Catalogo no vende, no descuenta inventario, no arma fisicamente y no define precio final.

Ventas debe guardar la configuracion final elegida por el cliente. Almacen/Inventario debe consumir o preparar los SKUs realmente elegidos.

## Correccion sobre legacy

Existe legacy de paquetes:

- `ecom_paquetes`
- `ecom_paquetes_productos`
- `ecom_paquetes_imagenes`
- `app/controladores/Paquetes.php`
- `app/modelos/Paquete.php`
- `app/modelos/Paquetes.php`

Decision:

- No usar ese legacy como base del nuevo diseño.
- No migrar paquetes legacy automaticamente.
- Conservarlo solo como referencia historica si despues se quiere rescatar algun paquete puntual.
- El nuevo modulo se diseña desde cero para soportar paquetes configurables correctamente.

## Caso operativo traducido

Ejemplo conceptual: paquete de pecera equipada.

Componentes fijos:

- Pecera.

Grupos de seleccion:

- Grava:
  - opciones: grava negra, grava blanca, grava natural;
  - regla: elegir 1 o mas;
  - cantidad: configurable por opcion o cantidad total a distribuir.
- Filtro:
  - opciones: filtro cascada, filtro cabeza de poder;
  - regla: elegir 1;
  - cantidad: 1.

La venta final no debe guardar solo "paquete pecera". Debe guardar:

- SKU paquete;
- componente fijo usado;
- opcion de grava elegida y cantidad;
- opcion de filtro elegida y cantidad;
- snapshot de reglas/precios cuando Ventas lo implemente.

## Responsabilidades

Catalogo ERP:

- Crea el SKU paquete.
- Define componentes fijos.
- Define grupos de seleccion.
- Define opciones permitidas por grupo.
- Define minimo/maximo de opciones por grupo.
- Define si la cantidad por opcion es fija, editable o distribuida.
- Valida que no haya ciclos, componentes inactivos o grupos incompletos.

Ventas/Precios:

- Define precio por lista/canal.
- Captura la seleccion final del cliente.
- Calcula precio final, cargos extra o descuentos si aplican.
- Guarda snapshot de la configuracion elegida.

Almacen/Inventario:

- Arma paquetes fisicos cuando aplique.
- Consume componentes fijos y opciones elegidas.
- Genera existencia de SKU paquete si es prearmado.
- Controla etiquetas, lotes, caducidad y ubicacion.

Compras/Proveedores:

- Compra los componentes normalmente.
- Solo compra el paquete como SKU normal si el proveedor lo vende cerrado.

## Estructura propuesta

No aplicar sin respaldo externo y autorizacion.

### Encabezado del paquete

```sql
CREATE TABLE erp_catalogo_sku_paquetes (
  id_paquete BIGINT NOT NULL AUTO_INCREMENT,
  id_sku_paquete BIGINT NOT NULL,
  tipo_paquete VARCHAR(30) NOT NULL DEFAULT 'simple',
  modo_disponibilidad VARCHAR(30) NOT NULL DEFAULT 'por_componentes',
  permite_configuracion_cliente TINYINT(1) NOT NULL DEFAULT 0,
  permite_desarmar TINYINT(1) NOT NULL DEFAULT 0,
  requiere_armado_almacen TINYINT(1) NOT NULL DEFAULT 0,
  observaciones TEXT NULL,
  estatus VARCHAR(30) NOT NULL DEFAULT 'activo',
  creado_por INT NULL,
  actualizado_por INT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_paquete),
  UNIQUE KEY idx_catalogo_paquete_sku (id_sku_paquete),
  KEY idx_catalogo_paquete_tipo (tipo_paquete),
  KEY idx_catalogo_paquete_estatus (estatus),
  CONSTRAINT fk_catalogo_paquete_sku
    FOREIGN KEY (id_sku_paquete) REFERENCES erp_catalogo_skus (id_sku)
);
```

Valores:

- `tipo_paquete`: `simple`, `configurable`, `virtual`, `prearmado`, `combo`, `comprado_cerrado`.
- `modo_disponibilidad`: `por_componentes`, `por_existencia_armada`, `mixto`.

### Componentes fijos

```sql
CREATE TABLE erp_catalogo_sku_paquete_componentes (
  id_componente BIGINT NOT NULL AUTO_INCREMENT,
  id_paquete BIGINT NOT NULL,
  id_sku_componente BIGINT NOT NULL,
  cantidad DECIMAL(18,6) NOT NULL,
  id_unidad INT NULL,
  factor_conversion DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
  orden INT NOT NULL DEFAULT 0,
  estatus VARCHAR(30) NOT NULL DEFAULT 'activo',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_componente),
  KEY idx_catalogo_paquete_componente_paquete (id_paquete),
  KEY idx_catalogo_paquete_componente_sku (id_sku_componente),
  CONSTRAINT fk_catalogo_paquete_componente_paquete
    FOREIGN KEY (id_paquete) REFERENCES erp_catalogo_sku_paquetes (id_paquete),
  CONSTRAINT fk_catalogo_paquete_componente_sku
    FOREIGN KEY (id_sku_componente) REFERENCES erp_catalogo_skus (id_sku),
  CONSTRAINT fk_catalogo_paquete_componente_unidad
    FOREIGN KEY (id_unidad) REFERENCES erp_catalogo_unidades (id_unidad)
);
```

### Grupos de seleccion

```sql
CREATE TABLE erp_catalogo_sku_paquete_grupos (
  id_grupo BIGINT NOT NULL AUTO_INCREMENT,
  id_paquete BIGINT NOT NULL,
  codigo VARCHAR(80) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion VARCHAR(255) NULL,
  min_selecciones INT NOT NULL DEFAULT 1,
  max_selecciones INT NOT NULL DEFAULT 1,
  modo_cantidad VARCHAR(30) NOT NULL DEFAULT 'cantidad_fija',
  cantidad_total_grupo DECIMAL(18,6) NULL,
  obligatorio TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  estatus VARCHAR(30) NOT NULL DEFAULT 'activo',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_grupo),
  UNIQUE KEY idx_catalogo_paquete_grupo_codigo (id_paquete, codigo),
  KEY idx_catalogo_paquete_grupo_paquete (id_paquete),
  CONSTRAINT fk_catalogo_paquete_grupo_paquete
    FOREIGN KEY (id_paquete) REFERENCES erp_catalogo_sku_paquetes (id_paquete)
);
```

Valores:

- `modo_cantidad = cantidad_fija`: cada opcion usa su cantidad default.
- `modo_cantidad = cantidad_editable`: el cliente/operador puede ajustar cantidad dentro de limites.
- `modo_cantidad = distribuir_total`: el grupo tiene una cantidad total y se reparte entre opciones elegidas.

### Opciones de grupo

```sql
CREATE TABLE erp_catalogo_sku_paquete_grupo_opciones (
  id_opcion BIGINT NOT NULL AUTO_INCREMENT,
  id_grupo BIGINT NOT NULL,
  id_sku_opcion BIGINT NOT NULL,
  cantidad_default DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
  cantidad_minima DECIMAL(18,6) NULL,
  cantidad_maxima DECIMAL(18,6) NULL,
  id_unidad INT NULL,
  factor_conversion DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
  permite_cantidad_editable TINYINT(1) NOT NULL DEFAULT 0,
  orden INT NOT NULL DEFAULT 0,
  estatus VARCHAR(30) NOT NULL DEFAULT 'activo',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_opcion),
  KEY idx_catalogo_paquete_opcion_grupo (id_grupo),
  KEY idx_catalogo_paquete_opcion_sku (id_sku_opcion),
  CONSTRAINT fk_catalogo_paquete_opcion_grupo
    FOREIGN KEY (id_grupo) REFERENCES erp_catalogo_sku_paquete_grupos (id_grupo),
  CONSTRAINT fk_catalogo_paquete_opcion_sku
    FOREIGN KEY (id_sku_opcion) REFERENCES erp_catalogo_skus (id_sku),
  CONSTRAINT fk_catalogo_paquete_opcion_unidad
    FOREIGN KEY (id_unidad) REFERENCES erp_catalogo_unidades (id_unidad)
);
```

## Validaciones

- El SKU paquete no puede ser componente fijo ni opcion de si mismo.
- Un paquete activo debe tener al menos un componente fijo activo o un grupo activo con opciones activas.
- No permitir ciclos entre paquetes.
- Cantidad de componente fijo mayor a cero.
- Factor de conversion mayor a cero.
- No permitir componentes/opciones con SKU inactivo, fusionado o descontinuado sin autorizacion.
- En cada grupo, `min_selecciones >= 0`.
- En cada grupo, `max_selecciones >= min_selecciones`.
- Si el grupo es obligatorio, `min_selecciones >= 1`.
- Un grupo activo debe tener al menos tantas opciones activas como `min_selecciones`.
- Si `permite_cantidad_editable=1`, validar cantidad minima/maxima.
- Si `modo_cantidad=distribuir_total`, `cantidad_total_grupo > 0`.

## UX propuesta

No saturar el modal principal.

Recomendacion:

- Agregar pestaña `Paquetes` solo cuando el SKU sea tipo `kit` o se active "Configurar como paquete".
- Dividir la pantalla en:
  - Encabezado del paquete.
  - Componentes fijos.
  - Grupos de seleccion.
  - Opciones del grupo seleccionado.
  - Alertas de calidad.
- Mostrar avisos claros:
  - "Catalogo define receta; Ventas guarda seleccion final."
  - "Almacen consume o arma los SKUs elegidos."
  - "Este grupo no tiene suficientes opciones activas."
  - "Este paquete no tiene componentes ni grupos."
- No mostrar costos ni precios en esta pestaña.

## Contrato hacia Ventas

Ventas debe recibir desde Catalogo:

- SKU paquete.
- Componentes fijos.
- Grupos activos.
- Opciones activas por grupo.
- Minimo/maximo de seleccion.
- Reglas de cantidad.

Ventas debe guardar en el documento:

- SKU paquete vendido.
- Opciones elegidas.
- Cantidad elegida por opcion.
- Snapshot de la configuracion usada.

## Contrato hacia Almacen/Inventario

Almacen/Inventario debe recibir desde Ventas o Preparacion:

- SKU paquete.
- Componentes fijos.
- Opciones elegidas.
- Cantidades finales.

Debe consumir exactamente esos SKUs, no una receta generica incompleta.

## Orden de implementacion

1. Aprobar este rediseño como modelo nuevo, sin migrar legacy.
2. Ajustar `CatalogoErpEsquema` al modelo de cuatro tablas: paquete, componentes fijos, grupos y opciones.
3. Proponer DDL final y detenerse antes de ejecutar.
4. Implementar esquema solo con respaldo externo y autorizacion.
5. Implementar modelo de lectura/escritura con comentarios de codigo segun `docs/erp_estandar_documentacion_codigo.md`.
6. Implementar UI de componentes fijos.
7. Implementar UI de grupos/opciones.
8. Agregar auditoria de calidad de paquetes.
9. Documentar handoff a Ventas para configuracion final elegida por cliente.
10. Documentar handoff a Almacen/Inventario para armado/desarmado y consumo de componentes elegidos.

## Criterio de cierre

- Paquete simple puede configurarse con componentes fijos.
- Paquete configurable puede configurarse con grupos y opciones.
- Catalogo no ejecuta venta, precio ni inventario.
- Ventas e Inventario quedan con contrato claro.
- No se aplica migracion sin respaldo externo y autorizacion.
