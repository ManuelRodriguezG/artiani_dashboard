# ERP Catalogo - Clasificacion asistida lote 01

Documentacion IA: Codex GPT-5  
Fecha: 2026-08-15  
Proyecto: `C:\xampp\htdocs\panel_de_control`  
Estado: Read-only, sin aplicar cambios en BD

## Proposito

Este documento registra el primer lote de clasificacion asistida para productos sin categoria principal.

La intencion no es clasificar a ciegas, sino acelerar el trabajo con reglas trazables:

- confianza alta: candidato aplicable despues de autorizacion;
- confianza media: revisar antes de aplicar o usar como seleccion manual;
- confianza baja: no aplicar automaticamente;
- sin sugerencia: requiere reglas nuevas, decision humana o categoria faltante.

## Script generado

```text
storage/uat/uat_catalogo_clasificacion_sugerida_readonly.php
```

Contrato:

- Solo lectura.
- No escribe productos.
- No escribe categorias.
- No escribe relaciones producto-categoria.
- Devuelve categoria sugerida, confianza, motivo y categorias faltantes detectadas.

Comando usado:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_clasificacion_sugerida_readonly.php --limite=200
```

## Resultado del lote 01

Productos analizados: 200.

Resumen por confianza:

- Alta: 52.
- Media: 51.
- Baja: 19.
- Sin sugerencia: 78.

Categorias asignables activas disponibles: 135.

## Categorias faltantes detectadas

Estas no deben crearse sin revisar, pero el lote indica que podrian mejorar el arbol:

- `Reptiles y tortugas / Reptiles generales / Terrarios generales`: 10 coincidencias.
- `Pequenos mamiferos / Animales vivos`: 6 coincidencias.
- `Acuario y peces / Decoracion y ambientacion / Raices y troncos`: 4 coincidencias.
- `Reptiles y tortugas / Reptiles generales / Sustratos`: 4 coincidencias.
- `Pequenos mamiferos / Habitat y jaulas generales`: 3 coincidencias.

## Ejemplos de confianza alta

| Producto | Categoria sugerida | Motivo |
|---|---|---|
| `4067 GABINETE 60X23X65 CM P/AQUA PACK 4063` | `Acuario y peces / Peceras, acuarios y muebles / Bases y muebles` | Gabinete/mueble para pecera o acuario. |
| `PLANTA BOLSA 6 PZS` | `Acuario y peces / Decoracion y ambientacion / Plantas artificiales` | Planta artificial o decorativa para acuario. |
| `BOLSA C/2 MEDUSAS NADADORAS` | `Acuario y peces / Decoracion y ambientacion / Decoracion para peces` | Decoracion para acuario. |
| `POOP OFF RECOGEDOR DE HECES` | `Perros / Salud e higiene / Higiene y limpieza` | Higiene y limpieza para perro. |
| `CAT TOILET ECONOMICO` | `Gatos / Salud e higiene / Areneros` | Arenero/sanitario para gato. |
| `FILTRO CASCATTA 250 L/H` | `Acuario y peces / Equipamiento tecnico / Filtracion y oxigenacion` | Filtro/equipo de filtracion. |
| `AEREADOR 1"` | `Acuario y peces / Equipamiento tecnico / Filtracion y oxigenacion` | Oxigenacion/aereacion de acuario. |
| `Arenero tapado` | `Gatos / Salud e higiene / Areneros` | Arenero para gato. |
| `Alimento tropifit premium para hamster` | `Pequenos mamiferos / Hamster / Alimentacion / Alimentos` | Alimento para hamster. |
| `Alimento para canarios/periquitos/ninfas` | `Aves / Alimentacion / Alimentos` | Alimento para aves. |

## Observaciones de calidad

- Se corrigio la regla de `cama` para evitar falsos positivos dentro de palabras como `camarón` o `guacamaya`.
- La comparacion ahora usa tokens/palabras completas.
- Los productos multiespecie quedan normalmente en confianza media o baja.
- Los animales vivos y terrarios generales muestran huecos reales del arbol.

## Decision recomendada

No aplicar todo el lote automaticamente.

Ruta recomendada:

1. Preparar un script de aplicacion autorizada solo para `confianza=alta`.
2. Requerir token explicito antes de escribir en `erp_catalogo_producto_categorias`.
3. Mantener confianza media y baja como bandeja de revision.
4. Antes de aplicar medias/bajas, decidir si se crean categorias faltantes.

Token sugerido para futuro apply:

```text
CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_01
```


## Actualizacion 2026-08-15 - Apply preparado para altas lote 01

Contexto:

- El lote 01 read-only encontro 52 sugerencias de confianza alta.
- Se preparo aplicacion controlada para escribir solo esas relaciones como categoria principal.

Script preparado:

- `storage/uat/uat_catalogo_clasificacion_asistida_altas_lote_01_apply.php`.

Preview validado:

- Candidatos alta confianza: 52.
- Aplicables: 52.
- Omitidos por categoria previa: 0.
- Insertados en preview: 0.

Validacion:

- `C:\xampp\php\php.exe -l storage\uat\uat_catalogo_clasificacion_sugerida_readonly.php`: OK.
- `C:\xampp\php\php.exe -l storage\uat\uat_catalogo_clasificacion_asistida_altas_lote_01_apply.php`: OK.
- Ejecucion sin `--execute`: preview OK, sin cambios en BD.

Token requerido para aplicar:

- `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_01`.

Comando de aplicacion, solo despues de respaldo externo y autorizacion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_clasificacion_asistida_altas_lote_01_apply.php --execute --token=CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_01 --respaldo=RUTA_RESPALDO_EXTERNO
```

Alcance:

- Inserta en `erp_catalogo_producto_categorias` solo productos de alta confianza del lote 01.
- Solo si el producto sigue sin categoria principal.
- No crea categorias.
- No modifica productos.
- No toca SKU, proveedores, inventario, ventas ni ecommerce.

## Actualizacion 2026-08-15 - Altas lote 01 aplicadas

Autorizacion recibida:

- Token: `CATALOGO_CLASIFICACION_ASISTIDA_ALTAS_LOTE_01`.
- Respaldo externo generado: `C:\xampp\panel_db_backups\catalogo_producto_categorias_20260815_132438_pre_clasificacion_asistida_lote_01.sql`.
- Proyecto aplicado: `C:\xampp\htdocs\panel_de_control`.

Ejecucion:

- Primer intento: rollback automatico por diferencia de esquema real en `erp_catalogo_producto_categorias`; la tabla no tiene columnas `origen` ni `observaciones`.
- Ajuste de script: el insert quedo limitado a `id_producto_erp`, `id_categoria_erp` y `es_principal`, respetando el esquema actual.
- Segundo intento: aplicado correctamente.

Resultado:

- Candidatos alta confianza: 52.
- Aplicables: 52.
- Omitidos por categoria previa: 0.
- Relaciones insertadas: 52.

Verificacion posterior:

- Relaciones producto-categoria totales: 53.
- Productos con categoria principal: 53.
- Productos activos/no fusionados todavia sin categoria principal: 1554.

Categorias con mas asignaciones despues del lote:

- `CAT16-GATOS-SALUD-ARENEROS`: 14.
- `CAT16-AVES-ALIM-ALIMENTOS`: 8.
- `CAT16-ACUARIO-PECERAS-BASES`: 4.
- `CAT16-MAMIFEROS-HAMSTER-HAB`: 4.
- `CAT16-ACUARIO-EQUIP-FILTRACION`: 4.

Siguiente paso:

- Ejecutar un nuevo lote read-only sobre los productos que siguen sin categoria principal.
- No aplicar confianza media/baja sin revisar reglas o categorias faltantes.
