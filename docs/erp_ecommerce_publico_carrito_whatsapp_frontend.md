# ERP Ecommerce publico - Carrito local y WhatsApp

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: guia para frontend externo; no crea pedidos ni aparta inventario.

## Objetivo

Implementar un carrito local tipo cotizacion para Fase 1.

Reglas:

- El carrito vive en frontend/localStorage.
- El carrito no descuenta inventario.
- El carrito no crea pedido.
- El carrito no cobra.
- Antes de WhatsApp, llamar `POST /cotizacion_dryrun` para recalcular y `POST /cotizacion_preflight` para validar contacto/consentimientos.
- El mensaje WhatsApp debe usar datos recalculados por ERP.

## Tipos

```ts
export type CartItem = {
  id_publicacion: number;
  id_sku: number;
  slug: string;
  nombre: string;
  imagen: string | null;
  presentacion: string | null;
  cantidad: number;

  // Solo cache visual, no fuente de verdad.
  precio?: number | null;
  moneda?: string | null;
  disponibilidad?: string | null;
};

export type CartState = {
  version: 1;
  items: CartItem[];
  updatedAt: string;
};
```

## localStorage

Key sugerida:

```ts
const CART_KEY = "erp_ecommerce_quote_cart_v1";
```

Funciones:

```ts
export function loadCart(): CartState {
  try {
    const raw = localStorage.getItem(CART_KEY);
    if (!raw) return { version: 1, items: [], updatedAt: new Date().toISOString() };
    const parsed = JSON.parse(raw) as CartState;
    return {
      version: 1,
      items: Array.isArray(parsed.items) ? parsed.items : [],
      updatedAt: parsed.updatedAt || new Date().toISOString(),
    };
  } catch {
    return { version: 1, items: [], updatedAt: new Date().toISOString() };
  }
}

export function saveCart(cart: CartState) {
  localStorage.setItem(CART_KEY, JSON.stringify({ ...cart, updatedAt: new Date().toISOString() }));
}

export function addToCart(cart: CartState, item: CartItem): CartState {
  const current = cart.items.find((line) => line.id_publicacion === item.id_publicacion);
  const items = current
    ? cart.items.map((line) =>
        line.id_publicacion === item.id_publicacion
          ? { ...line, cantidad: Math.min(999, Number(line.cantidad || 0) + Number(item.cantidad || 1)) }
          : line,
      )
    : [...cart.items, { ...item, cantidad: Math.max(1, Number(item.cantidad || 1)) }];

  return { version: 1, items, updatedAt: new Date().toISOString() };
}

export function updateQuantity(cart: CartState, id_publicacion: number, cantidad: number): CartState {
  const qty = Math.max(0, Math.min(999, Number(cantidad || 0)));
  const items = qty <= 0
    ? cart.items.filter((line) => line.id_publicacion !== id_publicacion)
    : cart.items.map((line) => line.id_publicacion === id_publicacion ? { ...line, cantidad: qty } : line);

  return { version: 1, items, updatedAt: new Date().toISOString() };
}
```

## Payload dry-run

No enviar precios:

```ts
export function cartToDryRunPayload(cart: CartState) {
  return {
    items: cart.items.map((item) => ({
      id_publicacion: item.id_publicacion,
      slug: item.slug,
      id_sku: item.id_sku,
      cantidad: item.cantidad,
    })),
  };
}
```

## Flujo WhatsApp

1. Leer configuracion: `GET /configuracion`.
2. Si `whatsapp_numero_principal` viene vacio, no abrir WhatsApp.
3. Enviar carrito a `POST /cotizacion_dryrun`.
4. Capturar contacto minimo y aceptacion de contacto por WhatsApp.
5. Enviar carrito/contacto a `POST /cotizacion_preflight`.
6. Si `depurar.bloqueos` trae elementos, mostrar observaciones.
7. Usar `depurar.whatsapp.url` si viene disponible; si no, construir link `wa.me` con `depurar.whatsapp.mensaje`.

```ts
export function buildWhatsAppUrl(phone: string, message: string): string | null {
  const normalized = String(phone || "").replace(/\D+/g, "");
  if (!normalized) return null;
  return `https://wa.me/${normalized}?text=${encodeURIComponent(message)}`;
}
```

## Validaciones UI

Antes de mostrar boton WhatsApp activo:

- carrito con al menos un item;
- configuracion con `whatsapp_numero_principal`;
- `cotizacion_dryrun.depurar.dry_run=true`;
- `cotizacion_dryrun.depurar.no_escribe_bd=true`;
- `cotizacion_dryrun.depurar.no_descuenta_inventario=true`;
- `cotizacion_dryrun.depurar.frontend.puede_continuar_preflight=true`;
- `cotizacion_dryrun.depurar.frontend.cta_principal.endpoint_siguiente="/ecommercePublico/cotizacion_preflight"`;
- `cotizacion_dryrun.depurar.frontend.guardrails_ui.no_usar_precio_local_como_total=true`;
- `cotizacion_preflight.depurar.preflight=true`;
- `cotizacion_preflight.depurar.no_escribe_bd=true`;
- `cotizacion_preflight.depurar.listo_para_whatsapp=true`;
- `cotizacion_preflight.depurar.folio_no_persistido=true`;
- `whatsapp.url` no vacio.

## Mensajes UI

Si WhatsApp no esta configurado:

```text
Cotizacion por WhatsApp pendiente de configuracion.
```

Si dry-run tiene bloqueos:

```text
Revisa estas observaciones antes de enviar la cotizacion.
```

Si todo esta listo:

```text
Enviar cotizacion por WhatsApp
```

## Prohibido

- Llamar `cotizacion_registrar`.
- Decir `pedido confirmado`.
- Decir `compra finalizada`.
- Decir `pago realizado`.
- Mostrar stock exacto.
- Usar precios locales para total final.
