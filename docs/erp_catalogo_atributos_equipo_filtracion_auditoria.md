# ERP Catalogo - Auditoria de atributos para equipo electrico y filtracion

Fecha: 2026-08-10
Proyecto vigente: `C:\xampp\htdocs\panel_de_control`
Modulo: ERP > Catalogo
Estado: auditoria read-only ejecutada, sin cambios de BD

## Objetivo

Preparar el primer bloque de saneamiento de atributos tecnicos del catalogo para productos de filtracion, bombeo, oxigenacion, iluminacion, calefaccion y equipo electrico.

Este bloque es prioritario porque contiene atributos claros que despues ayudan a:

- mostrar fichas tecnicas mas limpias;
- comparar productos;
- sugerir productos a clientes;
- alimentar un futuro agente asesor con datos estructurados;
- evitar que la descripcion libre sea la unica fuente tecnica del producto.

## Script creado

- `storage/uat/uat_catalogo_atributos_equipo_filtracion_readonly.php`

Contrato:

- Solo lectura.
- No modifica productos.
- No modifica SKUs.
- No modifica atributos.
- No modifica categorias.
- No modifica unidades.
- Detecta candidatos desde atributos actuales, nombre y descripcion.
- Imprime JSON con candidatos, evidencia y confianza.

Ejecucion:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_atributos_equipo_filtracion_readonly.php
```

## Ejecucion y resultado

El script fue validado con:

```powershell
C:\xampp\php\php.exe -l storage\uat\uat_catalogo_atributos_equipo_filtracion_readonly.php
```

Resultado:

- Sin errores de sintaxis.

El script se ejecuto correctamente cuando MariaDB local quedo disponible:

```powershell
C:\xampp\php\php.exe storage\uat\uat_catalogo_atributos_equipo_filtracion_readonly.php
```

Resultado resumido:

- SKUs revisados: 1825.
- Candidatos tecnicos detectados: 72.
- Sugerencias `consumo_electrico`: 61.
- Sugerencias `altura_maxima`: 10.
- Sugerencias `caudal`: 0 con evidencia fuerte.
- No se aplico ningun cambio de BD.

Observacion importante:

- El extractor corrigio `Subida: 90 cm` como `altura_maxima = 0.9 m`, no `90 m`.
- `Capacidad` sigue siendo atributo ambiguo y no debe migrarse en este bloque salvo que el contexto sea claramente acuario/equipo.
- La falta de `caudal` indica que el dato `l/h` probablemente no esta capturado de forma consistente en atributos/descripciones actuales.

## Atributos canonicos objetivo

### `consumo_electrico`

Unidad de atributo:

- `w`

Origen probable:

- atributo heredado `Potencia`;
- descripcion con textos como `5 W`, `10 watts`, `2.5 W`.

Regla:

- Es migrable con alta confianza cuando el valor tiene numero + watts.
- No debe llamarse `Potencia` en el vocabulario canonico si operativamente lo que interesa al cliente es consumo electrico.

### `caudal`

Unidad de atributo:

- `l/h`

Origen probable:

- descripcion de filtros, bombas, cascadas, cabezas de poder y canisters.

Regla:

- Es candidato cuando aparece como `500 l/h`, `500 lt/h`, `litros por hora`.
- No inferir caudal desde el nombre si no hay unidad clara.

### `altura_maxima`

Unidad de atributo:

- `m`

Origen probable:

- atributo heredado `Subida`;
- descripcion con textos como `subida 1.2 m`, `altura maxima 2 m`.

Regla:

- Aplicar principalmente a bombas, filtros y equipo que impulse agua.
- No usar para dimensiones fisicas del producto.

### `capacidad_acuario_min`

Unidad de atributo:

- `l`

Origen probable:

- descripcion con rango recomendado, por ejemplo `para acuarios de 20 a 50 litros`.

Regla:

- Solo aplica cuando el texto indique recomendacion de acuario/pecera, no capacidad fisica del producto.

### `capacidad_acuario_max`

Unidad de atributo:

- `l`

Origen probable:

- descripcion con rango o recomendacion maxima, por ejemplo `hasta 80 litros`.

Regla:

- Si solo hay un valor `hasta X litros`, capturarlo como maximo con confianza media/baja y revision humana.

## Atributos heredados involucrados

| Atributo actual | Canonico sugerido | Unidad | Riesgo |
| --- | --- | --- | --- |
| `Potencia` | `consumo_electrico` | `w` | Bajo si el valor tiene watts |
| `Subida` | `altura_maxima` | `m` | Bajo en bombas/filtros |
| `Capacidad` | `capacidad_acuario_min/max` o `capacidad_volumen` | `l/ml` | Alto; depende de familia |

## Decision de arquitectura

Catalogo no debe escribir atributos definitivos desde descripcion sin revision humana.

El flujo recomendado es:

1. Detectar candidatos read-only.
2. Mostrar evidencia: atributo actual, fragmento de descripcion o nombre.
3. Asignar confianza.
4. Permitir aceptar, corregir o descartar.
5. Al aceptar, guardar contra el vocabulario canonico.
6. Mantener historial de origen para saber si vino de descripcion, migracion o captura manual.

## Reglas para el futuro agente asesor

Estos atributos son buenos para recomendaciones porque son comparables:

- `caudal` permite sugerir filtros segun litros del acuario.
- `consumo_electrico` permite comparar gasto electrico.
- `altura_maxima` ayuda a elegir bombas segun necesidad de elevacion.
- `capacidad_acuario_min/max` permite evitar recomendar equipo insuficiente o excesivo.

El agente no debe depender de descripciones libres cuando exista atributo estructurado.

## Pendiente inmediato

1. Revisar los candidatos de alta confianza.
2. Crear atributos canonicos faltantes si se autoriza.
3. Preparar migracion controlada solo para valores obvios:
   - `Potencia` con watts;
   - `Subida` con metros;
   - rangos de capacidad con litros y contexto de acuario.
4. Crear extractor separado para `caudal`, revisando descripciones legacy, fichas o datos de proveedor si Catalogo no lo tiene.

## Verificacion de atributos canonicos

Se creo y ejecuto:

- `storage/uat/uat_catalogo_atributos_canonicos_readonly.php`

Resultado:

- No existen todavia:
  - `consumo_electrico`;
  - `caudal`;
  - `altura_maxima`;
  - `capacidad_acuario_min`;
  - `capacidad_acuario_max`.

Propuesta no aplicada:

- `docs/erp_catalogo_atributos_canonicos_equipo_propuesta.sql`

Esta propuesta solo crea maestros de atributos. No migra valores de SKUs.

## Aplicacion autorizada

Se aplico:

- `storage/uat/uat_catalogo_atributos_canonicos_equipo_apply.php`

Resultado:

- Se crearon/actualizaron cinco atributos canonicos:
  - `consumo_electrico`;
  - `caudal`;
  - `altura_maxima`;
  - `capacidad_acuario_min`;
  - `capacidad_acuario_max`.

Se creo y ejecuto preview:

- `storage/uat/uat_catalogo_atributos_equipo_migracion_preview.php`

Resultado previo:

- `Potencia` -> `consumo_electrico`: 61 filas validas, 61 insertables, 0 conflictos.
- `Subida` -> `altura_maxima`: 10 filas validas, 10 insertables, 0 conflictos.

Se aplico migracion no destructiva:

- `storage/uat/uat_catalogo_atributos_equipo_migracion_apply.php`

Resultado:

- `consumo_electrico`: 61 valores insertados/actualizados.
- `altura_maxima`: 10 valores insertados/actualizados.
- Omitidos: 0.
- No se borraron atributos heredados `Potencia` ni `Subida`.

Verificacion posterior:

- Preview post-migracion: 0 insertables, 61 existentes para `consumo_electrico`, 10 existentes para `altura_maxima`, 0 conflictos.

## Aplicacion de caudal

Se creo y ejecuto auditoria especifica:

- `storage/uat/uat_catalogo_atributos_caudal_readonly.php`

Resultado read-only:

- Filas revisadas por posibles textos relacionados: 45.
- Caudales detectados con evidencia explicita: 3.
- Insertables antes de aplicar: 3.
- Conflictos: 0.
- Sin valor extraible: 42.

Los 3 caudales detectados fueron:

| SKU | Producto | Caudal |
| --- | --- | ---: |
| `1106` | Filtro interno bioesf-carbon- esponja 450 l/h 70 cm | 450 l/h |
| `1107` | Filtro interno bioesf-carbon-esponja 720 l/h 115 cm | 720 l/h |
| `fil` | Canister filtro extremo 800 l/h sunny | 800 l/h |

Se aplico migracion no destructiva:

- `storage/uat/uat_catalogo_atributos_caudal_apply.php`

Resultado:

- `caudal`: 3 valores insertados/actualizados.
- Omitidos: 42 por no tener evidencia explicita de caudal.
- No se inventaron caudales desde modelo, SKU o capacidad del acuario.

Verificacion posterior:

- Read-only post-apply: 3 `ya_existe`, 0 insertables, 0 conflictos.

Decision:

- Los productos omitidos deben completarse desde ficha tecnica, proveedor o captura manual.
- No se debe inferir que `Filtro Cascada 600` significa `600 l/h` sin evidencia documental, porque el numero puede ser modelo, version comercial o capacidad sugerida.

## Retiro operativo de atributos heredados

Se audito si `Potencia` y `Subida` podian retirarse de operacion:

- `storage/uat/uat_catalogo_atributos_heredados_equipo_retiro_readonly.php`

Resultado:

- `Potencia`: 61 usos, 61 con `consumo_electrico`, 0 sin destino canonico.
- `Subida`: 10 usos, 10 con `altura_maxima`, 0 sin destino canonico.
- Decision: ambos inactivables.

Se aplico:

- `storage/uat/uat_catalogo_atributos_heredados_equipo_retiro_apply.php`

Resultado:

- `Potencia` quedo `inactivo`.
- `Subida` quedo `inactivo`.
- No se borraron registros de `erp_catalogo_sku_atributos`.
- Los valores historicos se conservan para trazabilidad.

Verificacion posterior:

- `Potencia`: estatus `inactivo`, 61 usos historicos, 61 cubiertos por `consumo_electrico`.
- `Subida`: estatus `inactivo`, 10 usos historicos, 10 cubiertos por `altura_maxima`.

## Fuera de alcance

- No limpiar descripciones todavia.
- No crear atributos nuevos todavia.
- No migrar valores.
- No modificar productos.
- No modificar categorias.
- No alimentar automaticamente al agente.
