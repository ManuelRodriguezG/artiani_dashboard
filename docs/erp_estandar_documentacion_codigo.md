# ERP - Estandar de documentacion de codigo

Documentacion IA: Codex GPT-5  
Fecha: 2026-06-24  
Estado: Norma transversal para codigo nuevo o modificado por IA

## Proposito

Este documento define como se debe documentar el codigo nuevo o modificado en el ERP para que otro desarrollador, operador tecnico o agente pueda entender por que existe una funcion y que impacto tiene.

La prioridad es documentar decisiones, contratos y efectos operativos. No se deben llenar los archivos con comentarios obvios que repitan cada linea.

## Regla estricta para codigo generado o modificado por IA

Cada funcion, metodo, endpoint o bloque funcional nuevo generado por IA debe incluir una nota breve con:

- IA: version/modelo usado.
- Fecha: fecha del cambio.
- Proposito: que hace.
- Impacto: que modulo o flujo afecta.
- Contrato: entrada/salida o regla relevante cuando aplique.

Para funciones existentes que se modifiquen de forma importante, agregar la nota si el cambio altera reglas de negocio, validaciones, permisos, datos, inventario, costos, fiscal, precios, auditoria o integracion con otro modulo.

## Formato PHP recomendado

```php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-24
 * Proposito: valida captura fiscal parcial sin bloquear datos maestros.
 * Impacto: Catalogo ERP; otros modulos siguen usando auditorias de completitud.
 */
private function ejemploFuncion($datos) {
  // codigo
}
```

## Formato JavaScript recomendado

```js
/**
 * IA: Codex GPT-5 | Fecha: 2026-06-24
 * Proposito: agrupa campos fiscales para evitar captura ambigua.
 * Impacto: UI de Catalogo ERP.
 */
function ejemploFuncion() {
    // codigo
}
```

## Documentacion viva obligatoria

Cuando el cambio afecte a otro modulo, ademas del comentario en codigo se debe actualizar el documento vivo correspondiente.

Ejemplos:

- Catalogo que afecta Compras: documentar en Catalogo y dejar instruccion para Compras.
- Catalogo que afecta Almacen/Inventario: documentar el contrato de unidad, factor, lote, caducidad o etiqueta.
- Precios, costos o rentabilidad: documentar que Catalogo no es dueno de costo real ni margen.
- Fiscal: documentar si la captura es parcial, validada o pendiente de validacion por area fiscal.

## Handoff obligatorio por modulo

Fecha: 2026-06-26

Ademas de documentar funciones nuevas, cada modulo debe conservar contexto operativo suficiente para continuar aunque el chat de Codex se compacte o se abra otro chat.

Cada documento vivo de modulo debe incluir, cuando aplique:

- estado actual del modulo;
- decisiones ya tomadas y razon operativa;
- cambios implementados recientemente;
- contratos que otros modulos deben respetar;
- pendientes y riesgos;
- instrucciones de continuidad para el siguiente chat/agente.

Formato recomendado:

```md
## Handoff / continuidad

Fecha: AAAA-MM-DD

- Contexto actual:
- Cambios recientes:
- Decisiones:
- Pendientes:
- Impacta a:
- Siguiente paso recomendado:
```

No usar el chat como unica fuente de contexto. Si una respuesta del chat aclara una regla reusable, debe migrarse al documento vivo correspondiente.

## Criterio de calidad

Un comentario es util si responde al menos una de estas preguntas:

- Por que existe esta funcion?
- Que flujo operativo protege?
- Que contrato esperan otros modulos?
- Que decision no debe cambiarse sin autorizacion?
- Que limitacion tecnica queda pendiente?

Un comentario no es util si solo describe lo evidente, por ejemplo "suma dos numeros" o "asigna variable".
