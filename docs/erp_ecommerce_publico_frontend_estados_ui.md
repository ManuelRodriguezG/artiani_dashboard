# ERP Ecommerce publico - Estados UI Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: matriz de estados para frontend externo.

## Objetivo

Evitar que el frontend trate estados operativos como errores fatales.

## Matriz

| Situacion | Senal API | UI recomendada | Accion usuario |
| --- | --- | --- | --- |
| API no responde | fetch falla o timeout | Pantalla de servicio no disponible | Reintentar |
| Host incorrecto | HTML/login en lugar de JSON | Error tecnico de configuracion | Revisar `ERP_API_BASE_URL` |
| API viva, sin DDL | `/estado depurar.schema.ddl_pendiente=true` | Catalogo en preparacion | Ver informacion general sin productos |
| Catalogo no configurado | `/catalogo depurar.configurado=false` | Catalogo en preparacion | Sin accion de compra |
| Catalogo vacio | `items=[]` con `configurado=true` | Sin resultados | Cambiar filtros o consultar WhatsApp |
| Filtros vacios | arrays vacios | Ocultar filtros no disponibles | Usar buscador |
| Producto no encontrado | `producto.depurar.item=null` | Producto no disponible | Volver al catalogo |
| Disponibilidad consultar | `consultar_disponibilidad` | Badge "Consultar disponibilidad" | Permitir cotizacion si producto lo permite |
| Agotado | `agotado` | Badge "Agotado" | No prometer entrega inmediata |
| WhatsApp no configurado | `whatsapp_numero_principal=""` | CTA deshabilitado o "Cotizacion pendiente" | Mostrar contacto alterno solo si negocio lo define |
| Dry-run con bloqueos | `depurar.bloqueos.length>0` | Mostrar observaciones | Ajustar carrito |
| Registro real bloqueado | `cotizacion_registrar error=true` | No usar endpoint | Mantener flujo WhatsApp |
| CORS cerrado | navegador bloquea, smoke HTTP OK | Usar proxy dev o esperar config | No cambiar API base a rutas falsas |

## Textos recomendados

- Catalogo en preparacion: `Estamos preparando el catalogo en linea. Puedes consultarnos por WhatsApp.`
- Total estimado: `Total estimado sujeto a confirmacion.`
- Disponibilidad consultar: `Consultar disponibilidad`
- Agotado: `Agotado`
- Pocas piezas: `Pocas piezas`

## Textos prohibidos en Fase 1

- `Compra finalizada`
- `Pedido confirmado`
- `Pago realizado`
- `Inventario apartado`
- `Stock exacto: N`

## Transicion de mock a datos reales

Usar fixtures solo cuando:

```text
senal_frontend=amarillo_mock_contratos
```

Cambiar a datos reales cuando:

```text
uat_ecommerce_publico_green_gate_readonly.php -> ok=true
```
