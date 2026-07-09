# ERP - Guia UX operativa

Documentacion IA: Codex GPT-5  
Fecha base: 2026-06-09  
Estado: Reglas UI/UX para pantallas operativas del ERP

## Proposito

Este archivo define criterios de interfaz para que el ERP sea funcional, intuitivo y usable por personal operativo. No se busca una UI bonita pero incomoda; se busca una herramienta clara, rapida y dificil de usar mal.

## Principio central

Cada control debe parecerse a la tarea real que el usuario esta haciendo.

- Si captura cantidades, el control debe facilitar cantidades.
- Si captura dinero, debe quedar claro si es con IVA, sin IVA, porcentaje o importe.
- Si captura estatus, debe mostrar opciones claras y restringidas.
- Si captura una accion repetitiva, debe reducir clics y errores.

## Cantidades

Problema detectado:

- Inputs numericos genericos con flechas nativas pueden subir/bajar decimales de forma poco intuitiva.
- Para personal operativo, esto se siente impreciso y puede generar errores.

Regla:

- No usar inputs numericos genericos como unica UX para cantidades importantes.
- Para cantidades enteras, usar control tipo stepper con boton `-`, campo central y boton `+`.
- Para cantidades decimales, definir el paso real antes de implementar: `0.01`, `0.001`, `0.000001` u otro segun unidad.
- Mostrar unidad junto al campo cuando aplique.
- Validar minimo, maximo y decimales permitidos en frontend y backend.

Tipos recomendados:

- Piezas/unidades enteras: stepper `- [cantidad] +` con paso `1`.
- Cajas/paquetes enteros: stepper con paso `1`.
- Peso/volumen: input decimal con unidad visible y paso definido.
- CFDI/XML: respetar precision del XML, pero mostrar una captura comprensible.
- Ajustes masivos: usar modal o configuracion clara antes de aplicar.
- En tablas de compras, la cantidad de cada partida debe usar botones laterales `-` y `+` cuando el flujo normal sea aumentar o disminuir unidades.

Comportamiento esperado:

- Boton `-` nunca baja de cero o del minimo permitido.
- Boton `+` incrementa con paso visible y esperado.
- El usuario puede escribir manualmente si necesita.
- Al perder foco, normalizar formato.
- Si el valor es invalido, mostrar mensaje claro.

## Dinero, costos y precios

Regla:

- Siempre dejar claro si el usuario captura precio sin IVA, precio con IVA, porcentaje o importe.
- Evitar columnas duplicadas ambiguas.
- Si un valor se calcula, indicarlo visualmente o bloquearlo segun el modo.
- Si ambos valores son editables, deben sincronizarse con una regla visible.

## Descuentos

Regla:

- No usar un input ambiguo de descuento sin tipo.
- El usuario debe elegir o ver si captura:
  - Porcentaje base 100.
  - Porcentaje decimal.
  - Importe monetario.
- Si SAT requiere importe, guardar/calcular importe final aunque la captura sea porcentaje.
- En tablas operativas, si un descuento masivo afecta una columna completa, preferir un control discreto en el encabezado de `Descuento` antes que un bloque separado que obligue a seleccionar/aplicar con varios pasos.
- Si el descuento del encabezado se escribe en porcentaje, debe mostrarse el simbolo `%` junto al campo y aplicarse de forma consistente a todas las partidas visibles del documento.

## Costos con o sin IVA

Regla:

- La tabla de partidas debe mostrar ambos valores cuando sean relevantes: `Costo sin impuestos` y `Costo neto`, para que el capturista vea el costo base y el costo final estimado.
- En compras con factura, el valor por defecto recomendado es capturar `Costo sin impuestos`, porque el XML/CFDI normalmente trae valor unitario antes de impuestos.
- Si el usuario necesita capturar un valor que ya incluye impuestos, debe poder cambiar el modo desde el encabezado de `Costo neto`.
- Solo una de las columnas de costo debe quedar editable a la vez; la otra se calcula visualmente para evitar doble captura y descuadres.
- Evitar el texto `sin IVA` cuando la regla realmente aplica a impuestos en general; preferir `sin impuestos`.

## Productos y partidas

Regla:

- Una misma tabla debe servir como centro operativo cuando sea posible.
- Productos ERP, XML, productos nuevos y cargos deben distinguirse con estado visual claro.
- No duplicar captura en tablas repetidas si se puede mostrar como pendiente/alerta.

## Permisos y acciones

Regla:

- Ocultar acciones que el usuario no puede ejecutar.
- Mostrar claramente cuando algo es solo lectura.
- Backend siempre valida permiso y estatus.

## Accesibilidad operativa

Reglas:

- Botones de accion frecuente deben tener texto claro e icono si ayuda.
- No depender solo de color para indicar estado.
- Mensajes de error deben decir que falta y como corregirlo.
- Controles repetitivos deben tener dimensiones estables para no mover la tabla.

## Cuando pedir IA experta en UI/UX

Usar un agente/modelo fuerte con enfoque UI/UX cuando la tarea implique:

- Rediseñar tabla operativa.
- Diseñar controles de cantidad, descuento, costos o fiscales.
- Reducir pasos de captura.
- Crear flujo para personal no tecnico.
- Convertir reglas complejas en una pantalla sencilla.

Prompt recomendado:

```text
Trabaja como especialista UI/UX operativo para ERP.
Objetivo: hacer esta pantalla intuitiva para personal operativo, reduciendo errores y pasos.
Usa docs/erp_ux_operativa.md, AGENTS.md y el documento puntual de la tarea.
No cambies reglas de negocio; traduce reglas existentes a controles claros.
```

## Aplicacion inmediata

En Compras > Solicitudes y Ordenes:

- Revisar campos de cantidad.
- Reemplazar inputs numericos genericos por stepper cuando la unidad sea entera.
- Definir paso decimal por unidad cuando no sea entera.
- Alinear validacion frontend/backend.
- Evitar que flechas nativas generen incrementos inesperados.
