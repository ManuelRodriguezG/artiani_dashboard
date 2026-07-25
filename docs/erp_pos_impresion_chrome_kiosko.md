# ERP POS - Impresion directa con Chrome kiosko

Documento vivo. Ultima actualizacion: 2026-07-24.

Proyecto canonico: `C:\xampp\htdocs\panel_de_control`.

Host local canonico: `http://panel.com.local/`.

## Objetivo

Permitir que una computadora de caja imprima tickets POS en una impresora termica USB usando Chrome o Edge con `--kiosk-printing`, sin instalar todavia un bridge ESC/POS.

## Alcance

Esta configuracion sirve para piloto operativo. El POS sigue corriendo en el navegador y llama `window.print()`, pero Chrome/Edge omite el dialogo de impresion cuando se abre con `--kiosk-printing`.

No controla corte automatico, cajon de dinero ni reintentos avanzados. Para eso se requiere el bridge local ESC/POS.

## Requisitos por computadora

1. Windows.
2. Google Chrome o Microsoft Edge instalado.
3. Impresora termica USB instalada en Windows.
4. Impresora termica configurada como predeterminada.
5. Tamano de papel del driver configurado como `80mm`, `Receipt`, `POS`, `Roll Paper` o equivalente.
6. Host `panel.com.local` apuntando al servidor correcto.
7. Acceso al POS: `http://panel.com.local/ventas/pos`.

## Archivo de arranque

Usar:

```text
tools\windows\abrir_pos_chrome_kiosko.bat
```

El archivo intenta abrir:

```text
http://panel.com.local/ventas/pos
```

con:

```text
--kiosk-printing --app=http://panel.com.local/ventas/pos
```

Primero busca Chrome. Si no existe, intenta Edge.

## Prueba recomendada

1. Ejecutar `tools\windows\abrir_pos_chrome_kiosko.bat`.
2. Iniciar sesion en el POS si el sistema lo solicita.
3. Abrir el modal de ticket desde POS.
4. Presionar `Prueba 80mm`.
5. Confirmar que el ticket se imprime sin dialogo.
6. Revisar:
   - Texto legible.
   - Sin formato carta.
   - Margenes laterales aceptables.
   - No corta lineas importantes.

## Si sigue saliendo carta o PDF largo

Revisar en Windows:

1. Configuracion de impresoras.
2. Seleccionar la termica.
3. Preferencias de impresion.
4. Tamano de papel: `80mm` o rollo termico.
5. Margenes: ninguno/minimos si el driver lo permite.
6. Definir como predeterminado.

Chrome usa la impresora y preferencias del sistema. Si Windows mantiene papel carta, el POS no puede corregirlo por completo desde JavaScript.

## Camino formal posterior

Cuando el piloto quede estable, preparar modo `escpos_bridge`:

- Servicio local en `127.0.0.1`.
- Impresion directa por USB/Windows/ESC-POS.
- Corte automatico.
- Apertura de cajon.
- Bitacora local de intentos y errores.
- Configuracion por caja/terminal en `erp_pos_ticket_configuracion`.
