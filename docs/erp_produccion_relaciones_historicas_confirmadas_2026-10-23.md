# Relaciones historicas confirmadas

Estas equivalencias fueron confirmadas por el dueno del negocio. Se conserva el codigo crudo del Excel para trazabilidad y se usa la relacion canonica para el analisis; no se crean perfiles nuevos ni se inventan medidas con esta lista.

| Codigo historico | Relacion canonica | Tratamiento |
|---|---|---|
| `TORO-H4040` | `PERA-H4040` | Es otra pecera base. El perfil `PERA-H4040` queda pendiente de receta/medidas si aun no existe. |
| `TORO-C4040` | `PERA-C4040` | Es el tortuguero de 3 mm; la pecera base `PERA-C4040` puede manejar otra variante, probablemente 6 mm. No se fusionan espesores automaticamente. |
| `Pec-10040` | `PERA-1004040` | Mismo perfil de pecera, con normalizacion de codigo. |
| `TORO-2040CL` | `PERA-C2040` | Pieza lateral del tortuguero; no es un perfil independiente. |
| `TORO-2040CFR` | `PERA-C2040` | Pieza frontal/respaldo del tortuguero; no es un perfil independiente. |
| `TORO-2040CP` | `PERA-C2040` | Pieza de piso del tortuguero; no es un perfil independiente. |
| `TORO-20HL` | Componente historico `TORO-20...` | Nomenclatura usada para identificar piezas; aparece junto a `TORO-2040`, pero sus medidas no deben fusionarse automaticamente con `PERA-C2040`. |
| `TORO-20HP` | Componente historico `TORO-20...` | Nomenclatura usada para identificar piezas; aparece junto a `TORO-2040`, pero sus medidas no deben fusionarse automaticamente con `PERA-C2040`. |

## Pendiente real

- Identificar a que perfil pertenecen exactamente las piezas `TORO-20HL` y `TORO-20HP`.
- Mantener separadas las variantes de 3 mm y 6 mm cuando el historial las muestre distintas.
