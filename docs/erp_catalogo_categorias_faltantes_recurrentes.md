# ERP Catalogo - Categorias faltantes recurrentes

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-17  
Proyecto: `C:\xampp\htdocs\panel_de_control`  
Estado: Preparado, sin aplicar cambios en BD

## Proposito

La clasificacion asistida de productos detecto grupos recurrentes que no tienen una categoria asignable suficientemente clara en el arbol `CAT16-*`.

Antes de seguir aplicando lotes automaticos, conviene resolver estas categorias para reducir asignaciones ambiguas.

## Auditoria

Categorias faltantes detectadas en lotes recientes:

- `Reptiles y tortugas / Reptiles generales / Terrarios generales`.
- `Pequenos mamiferos / Animales vivos`.
- `Acuario y peces / Decoracion y ambientacion / Raices y troncos`.
- `Reptiles y tortugas / Reptiles generales / Sustratos`.
- `Pequenos mamiferos / Habitat y jaulas generales`.

## Propuesta

Crear 5 categorias maestras activas, todas con `permite_productos=1`:

| Codigo | Ruta propuesta | Razon |
|---|---|---|
| `CAT16-REPTILES-GRAL-TERR-GENERALES` | `Reptiles y tortugas / Reptiles generales / Terrarios / Terrarios generales` | Clasificar terrarios que no deben ir por material. |
| `CAT16-MAMIFEROS-VIVOS` | `Pequenos mamiferos / Animales vivos` | Clasificar animales vivos sin forzar alimento por especie. |
| `CAT16-ACUARIO-DECO-RAICES-TRONCOS` | `Acuario y peces / Decoracion y ambientacion / Raices y troncos` | Separar maderas/raices de decoracion general. |
| `CAT16-REPTILES-GRAL-SUSTRATOS` | `Reptiles y tortugas / Reptiles generales / Sustratos` | Separar sustratos de decoracion general. |
| `CAT16-MAMIFEROS-HAB-GENERAL` | `Pequenos mamiferos / Habitat y jaulas generales` | Usar cuando la especie no esta clara o aplica a varios pequenos mamiferos. |

## Script preparado

```text
storage/uat/uat_catalogo_categorias_faltantes_recurrentes_apply.php
```

Contrato:

- Preview por defecto.
- No modifica productos.
- No modifica relaciones producto-categoria.
- No toca SKU, proveedores, inventario, ventas, ecommerce ni listas de precios.
- Para ejecutar requiere token y respaldo externo.

Token requerido:

```text
CATALOGO_CATEGORIAS_FALTANTES_RECURRENTES
```

Comando de aplicacion, solo despues de respaldo externo y autorizacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_categorias_faltantes_recurrentes_apply.php --execute --token=CATALOGO_CATEGORIAS_FALTANTES_RECURRENTES --respaldo=RUTA_RESPALDO_EXTERNO
```

## Decision recomendada

Aplicar estas 5 categorias antes de generar mas lotes asistidos.

Despues de aplicarlas, actualizar las reglas del sugeridor para que los casos de terrarios, animales vivos, raices/troncos, sustratos y jaulas generales apunten a las categorias nuevas.

## Actualizacion 2026-08-17 - Categorias aplicadas

Autorizacion recibida:

- Token: `CATALOGO_CATEGORIAS_FALTANTES_RECURRENTES`.
- Respaldo externo: `C:\xampp\panel_db_backups\catalogo_categorias_20260817_antes_categorias_faltantes_recurrentes.sql`.
- Proyecto aplicado: `C:\xampp\htdocs\panel_de_control`.

Resultado:

- Categorias definidas: 5.
- Categorias creadas: 5.
- Errores: 0.

Verificacion posterior:

- Las 5 categorias existen con `estatus=activa`.
- Todas tienen `permite_productos=1`.
- El preview posterior las reconoce como existentes.

Reglas read-only actualizadas:

- `terrario` apunta a `CAT16-REPTILES-GRAL-TERR-GENERALES`.
- `raiz` y `tronco` apuntan a `CAT16-ACUARIO-DECO-RAICES-TRONCOS`.
- `peat moss`, `fibra coco`, `chip coco` y `corteza` apuntan a `CAT16-REPTILES-GRAL-SUSTRATOS`.
- `jaula mamifero` apunta a `CAT16-MAMIFEROS-HAB-GENERAL`.
- Animales vivos de pequenos mamiferos suben a confianza media y apuntan a `CAT16-MAMIFEROS-VIVOS`, porque aun requieren distinguir ejemplar vivo vs alimento.
