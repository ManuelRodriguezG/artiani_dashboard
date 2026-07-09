# ERP Garantias - Plan de permisos

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-27  
Estado: propuesta preparada; no aplicada a BD.

## Objetivo

Definir los permisos de Garantias ERP antes de aplicar semillas en Seguridad.

## Permisos

### `garantias.ver`

Permite:

- consultar politicas;
- consultar resolver SKU;
- consultar elegibilidad;
- ver reclamos;
- ver snapshots de garantia.

No permite:

- editar politicas;
- crear reclamos;
- resolver reclamos;
- cargar adjuntos.

### `garantias.politicas`

Permite:

- crear politicas;
- editar politicas;
- activar/desactivar politicas;
- crear reglas por SKU/producto/categoria/marca/proveedor.

Responsable sugerido:

- Catalogo productos.
- Administrador ERP.

### `garantias.reclamos.crear`

Permite:

- iniciar reclamos desde venta/ticket/cliente/unidad;
- capturar motivo inicial;
- generar folio de reclamo cuando se habilite escritura.

Responsable sugerido:

- Ventas/Postventa.

### `garantias.reclamos.resolver`

Permite:

- diagnosticar reclamos;
- cambiar estatus operativo;
- registrar decision;
- solicitar recepcion fisica a Almacen cuando aplique.

Responsable sugerido:

- Postventa.
- Administrador ERP.

### `garantias.autorizar`

Permite:

- aprobar excepciones;
- autorizar devoluciones sensibles;
- autorizar cambios fuera de politica;
- autorizar rechazos especiales.

Responsable sugerido:

- Direccion.
- Administrador ERP.

### `garantias.adjuntos`

Permite:

- cargar evidencias;
- cancelar adjuntos;
- consultar evidencia sensible del reclamo.

Responsable sugerido:

- Postventa.
- Administrador ERP.

### `garantias.reportes`

Permite:

- consultar indicadores de garantias;
- analizar reclamos por SKU, marca, proveedor, categoria y tiempos.

Responsable sugerido:

- Direccion.
- Auditor.
- Administrador ERP.

## Roles sugeridos

| Rol | Permisos |
| --- | --- |
| `direccion` | `garantias.ver`, `garantias.autorizar`, `garantias.reportes` |
| `administrador_erp` | todos |
| `catalogo_productos` | `garantias.ver`, `garantias.politicas` |
| `ventas` | `garantias.ver`, `garantias.reclamos.crear` |
| `almacen` | `garantias.ver` |
| `inventario` | `garantias.ver` |
| `compras` | `garantias.ver` |
| `auditor` | `garantias.ver`, `garantias.reportes` |
| `solo_lectura` | `garantias.ver` |

## Reglas

- Catalogo puede definir politica, pero no resolver reclamos de cliente.
- Ventas puede crear reclamo, pero no cambiar politicas.
- Almacen puede consultar reclamo, pero no decidir garantia comercial.
- Direccion autoriza excepciones.
- Auditor consulta reportes, no edita.

## Cierre

El plan queda listo cuando:

- permisos existen en `SeguridadEsquema.php`;
- semillas se aplican con flujo autorizado;
- roles reciben permisos esperados;
- endpoints dejan de bloquear por permiso inexistente.
