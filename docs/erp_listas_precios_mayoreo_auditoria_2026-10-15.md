# Auditoría de precios MAYOREO

Fecha de revisión: 2026-10-15. Alcance: ERP > Comercial > Listas de precios. Consultas read-only; no se crearon, activaron, modificaron ni asignaron listas, precios, productos o datos productivos.

## Resultado ejecutivo

- SKUs activos auditados: 1740.
- Sin costo usable: 611.
- Sin precio público usable: 185.
- Precio público debajo del costo: 12.
- Propuestas bajo el margen mínimo del grupo: 3.
- Tabla completa: `erp_listas_precios_mayoreo_auditoria_2026-10-15.csv`.

## Jerarquía de costo

Se usa costo promedio de inventario; después costo vigente de proveedor; después última compra; finalmente costo de catálogo como fallback histórico. El catálogo no basta para aprobación automática. La consulta también identifica SKUs derivados por transformación y los deja en revisión.

## Políticas comerciales

| Grupo | Conservador | Competitivo | Estratégico | Margen mínimo | Política |
|---|---:|---:|---:|---:|---|
| Distribución estándar | 10% | 15% | 20% | 18% | Propuesta base; validar contra competencia y rotación |
| Alta competencia / margen ajustado | 5% | 8% | 12% | 15% | Descuento controlado solo con costo y público confirmados |
| Marca / especialidad / valor protegido | 5% | 8% | 12% | 25% | Evitar descuento agresivo; requiere criterio comercial |
| Derivado / presentación / paquete | 5% | 8% | 12% | 20% | Validar equivalencia, factor y merma antes de aprobar |

El precio sugerido competitivo es el mayor entre el precio público con descuento y el piso que conserva el margen mínimo del grupo. Si supera al público, queda como alerta.

## Estados

- Proteger precio por marca o especialidad: 669.
- Costo pendiente de validar: 611.
- Recomendado para mayoreo: 317.
- Precio público posiblemente incorrecto: 131.
- No recomendable para mayoreo: 12.

## Agrupación

### Grupos comerciales
- Distribución estándar: 1049.
- Marca / especialidad / valor protegido: 669.
- Alta competencia / margen ajustado: 22.

### Proveedores
- Sin proveedor identificado: 722.
- HOBBY PET: 218.
- ACUARIO ARBOLEDAS: 196.
- AQUAKRILL: 165.
- SUNNY: 148.
- PET GLASS: 66.
- KROKETILANDIA: 61.
- MUNDO MASCOTA: 34.
- OCEAN AQUA: 29.
- ALAMAZONAS: 19.
- PETPLANET: 18.
- TERRARIOS MADERA: 16.
- BASES HERRERIA: 12.
- EL HAMSTER FELIZ: 6.
- ALIMAÑAS: 5.
- JAULAS GALVANIZADAS: 5.
- AGRICOLA DEL CENTRO: 4.
- TRONCOS: 3.
- SUPERVET: 2.
- Artemia salina de guadalajara: 2.
- MAS MATERIALES: 2.
- Nutrigan mex: 1.
- FORRAJERA 19 HERMANOS: 1.
- ALIMENTOS BALANCEADOS: 1.
- MARROQUIN: 1.

### Marcas
- Sin dato: 441.
- FANCY PETS: 217.
- SUNNY: 184.
- SEVENPET: 104.
- ARTIANI: 85.
- LOMAS: 66.
- PERRUCHES: 61.
- REDKITE: 53.
- NUPEC: 43.
- AQUA KRIL: 41.
- SAKURA PETTO: 33.
- ROOMMIE: 33.
- BIOMAA: 31.
- OCEAN AQUA: 29.
- Tropical: 21.
- CAGELAND: 20.
- Stefanplast: 19.
- HOBBY PET: 19.
- ECO PET: 15.
- ALAMAZONAS: 15.
- EXO TERRA: 15.
- Pedros: 13.
- MUNDO MASCOTA: 11.
- ECOFOUNT: 10.
- PETSPICK: 10.

### Categorías
- Camas y colchones: 121.
- Alimentos: 118.
- Llaveros de mascotas: 110.
- Accesorios generales: 103.
- Juguetes: 101.
- Habitat y jaulas generales: 70.
- Higiene y limpieza: 65.
- Filtracion y oxigenacion: 62.
- Alimentos de acuario: 61.
- Areneros: 59.
- Sin dato: 59.
- Sustratos: 42.
- Prevencion y cuidado: 41.
- Comederos y bebederos: 41.
- Calefaccion: 41.
- Jaulas: 36.
- Premios y snacks: 35.
- Peceras: 34.
- Terrarios generales: 32.
- Aditamentos: 27.
- Rascadores: 26.
- Transportadoras: 25.
- Accesorios y aditamentos: 23.
- Sustratos, gravas y arenas: 22.
- Paseo y sujecion: 22.

## Pendientes

- Completar precios públicos ausentes o incongruentes.
- Confirmar rotación/importancia comercial y competencia real; no se usa como dato automático porque no está presente de forma confiable en esta consulta.
- Validar marcas de especialidad, exclusividad, paquetes, presentaciones, aperturas, recetas, factores y mermas.
- Definir mínimos de compra por grupo/SKU.
- Mantener ecommerce fuera de MAYOREO hasta autorización expresa.

## Control de cambios

- Cambios en base de datos: ninguno.
- Listas, precios, productos y asignaciones creados o modificados: ninguno.
- Respaldo externo requerido antes de cualquier escritura futura: `C:\xampp\panel_db_backups`.
- Autorizador: pendiente.
