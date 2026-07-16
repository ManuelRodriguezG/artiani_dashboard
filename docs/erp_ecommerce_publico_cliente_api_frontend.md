# ERP Ecommerce publico - Cliente API frontend

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-15  
Estado: referencia para implementar cliente API en el proyecto ecommerce externo.

## Objetivo

Centralizar el consumo del ERP en una sola capa del frontend.

Reglas:

- No leer BD.
- No consumir tablas ni endpoints internos.
- No usar `ecom_*`.
- No enviar precios como fuente de verdad.
- No usar `/bootstrap`.
- No usar `cotizacion_registrar` en Fase 1.

## Tipos base

```ts
export type ErpApiMeta = {
  nombre: string;
  version: "fase1-2026-07-12";
  modo: "catalogo_vivo_readonly";
  fuente_verdad: "ERP";
  moneda_default: "MXN";
};

export type ErpResponse<T> = {
  error: boolean;
  tipo: "success" | "info" | "warning" | "danger" | string;
  mensaje: string;
  api: ErpApiMeta;
  depurar: T;
};

export type DisponibilidadPublica =
  | "disponible"
  | "pocas_piezas"
  | "consultar_disponibilidad"
  | "agotado";

export type ProductoCatalogo = {
  id_publicacion: number;
  id_producto_erp: number;
  id_sku: number;
  slug: string;
  sku: string;
  nombre: string;
  marca: string | null;
  categoria: string | null;
  presentacion: string | null;
  descripcion: string | null;
  imagen: string | null;
  precio: number | null;
  moneda: "MXN" | string | null;
  disponibilidad: DisponibilidadPublica;
  mascota_especie: string | null;
  necesidades: string[];
  permite_cotizacion: boolean;
  permite_whatsapp: boolean;
};

export type CatalogoParams = {
  q?: string;
  mascota?: string;
  necesidad?: string;
  marca?: number | string;
  categoria?: number | string;
  pagina?: number;
  limite?: number;
};

export type SeoPublico = {
  configurado: boolean;
  meta: {
    site_name: string;
    title_default: string;
    description_default: string;
    canonical_base: string;
    robots_default: string;
  };
  robots: {
    permitir_indexacion: boolean;
    robots_txt_sugerido: string;
    noindex_si_catalogo_vacio: boolean;
  };
  sitemap: {
    base_url_configurada: string;
    rutas_estaticas: Array<{ path: string; priority: string; changefreq: string }>;
    productos: Array<{ slug: string; path: string; title: string; description: string; image: string | null }>;
    filtros: Record<string, unknown[]>;
  };
  json_ld: Record<string, unknown>;
};

export type CotizacionDryRunPayload = {
  items: Array<{
    id_publicacion?: number;
    slug?: string;
    id_sku?: number;
    cantidad: number;
  }>;
  contacto?: {
    nombre?: string;
    telefono?: string;
    mensaje?: string;
  };
  utm?: Record<string, unknown>;
};
```

## Cliente

```ts
const API_VERSION = "fase1-2026-07-12";

export class ErpEcommerceApiError extends Error {
  status?: number;
  tipo?: string;
  functional: boolean;

  constructor(message: string, options: { status?: number; tipo?: string; functional?: boolean } = {}) {
    super(message);
    this.name = "ErpEcommerceApiError";
    this.status = options.status;
    this.tipo = options.tipo;
    this.functional = options.functional ?? false;
  }
}

type RequestOptions = {
  method?: "GET" | "POST";
  body?: unknown;
  timeoutMs?: number;
};

export function createErpEcommerceApi(config: { baseUrl: string; basePath?: string }) {
  const baseUrl = config.baseUrl.replace(/\/+$/, "");
  const basePath = config.basePath ?? "/ecommercePublico";

  async function request<T>(path: string, options: RequestOptions = {}): Promise<ErpResponse<T>> {
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), options.timeoutMs ?? 10000);

    try {
      const response = await fetch(`${baseUrl}${basePath}${path}`, {
        method: options.method ?? "GET",
        headers: {
          Accept: "application/json",
          ...(options.body ? { "Content-Type": "application/json" } : {}),
        },
        body: options.body ? JSON.stringify(options.body) : undefined,
        signal: controller.signal,
      });

      const data = (await response.json()) as ErpResponse<T>;

      if (!response.ok) {
        throw new ErpEcommerceApiError(data?.mensaje || "Error HTTP ERP ecommerce", {
          status: response.status,
          tipo: data?.tipo,
        });
      }

      if (data.api?.version !== API_VERSION) {
        throw new ErpEcommerceApiError("Version API ecommerce inesperada", { functional: true });
      }

      return data;
    } catch (error) {
      if (error instanceof ErpEcommerceApiError) throw error;
      throw new ErpEcommerceApiError(error instanceof Error ? error.message : "No se pudo conectar con ERP ecommerce");
    } finally {
      window.clearTimeout(timeout);
    }
  }

  return {
    getContratos: () => request<unknown>("/contratos"),
    getEstado: () => request<{
      ready: boolean;
      schema: { ddl_pendiente: boolean };
      publicaciones: { total_publicadas: number; catalogo_publico_vacio: boolean };
      seguridad: { post_dryrun_disponible: boolean; post_registro_bloqueado: boolean };
    }>("/estado"),
    getConfiguracion: () => request<{ configurado: boolean; configuracion: Record<string, string> }>("/configuracion"),
    getSeo: () => request<SeoPublico>("/seo"),
    getFiltros: () => request<{ mascotas: unknown[]; necesidades: unknown[]; marcas: unknown[]; categorias: unknown[] }>("/filtros"),
    getCatalogo: (params: CatalogoParams = {}) => {
      const query = new URLSearchParams();
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && String(value).trim() !== "") query.set(key, String(value));
      });
      return request<{ configurado: boolean; items: ProductoCatalogo[]; paginacion: { pagina: number; limite: number; total: number } }>(
        `/catalogo${query.toString() ? `?${query.toString()}` : ""}`,
      );
    },
    getProducto: (slug: string) => request<{ item: ProductoCatalogo | null }>(`/producto/${encodeURIComponent(slug)}`),
    getDisponibilidad: (input: { slug?: string; id_sku?: number }) => {
      const query = new URLSearchParams();
      if (input.slug) query.set("slug", input.slug);
      if (input.id_sku) query.set("id_sku", String(input.id_sku));
      return request<{ disponibilidad: DisponibilidadPublica; mostrar_cantidad_exacta?: false }>(`/disponibilidad?${query.toString()}`);
    },
    cotizacionDryRun: (payload: CotizacionDryRunPayload) =>
      request<{
        configurado: boolean;
        dry_run: true;
        no_escribe_bd: true;
        no_descuenta_inventario: true;
        lineas: unknown[];
        totales: { subtotal_estimado: number; total_estimado: number; moneda: string; texto: string };
        bloqueos: string[];
        whatsapp_preview: string;
      }>("/cotizacion_dryrun", { method: "POST", body: payload }),
  };
}
```

## Uso recomendado

```ts
export const erpApi = createErpEcommerceApi({
  baseUrl: import.meta.env.VITE_ERP_API_BASE_URL,
  basePath: import.meta.env.VITE_ERP_ECOMMERCE_BASE_PATH,
});
```

En UI:

- si `estado.depurar.ready=false`, mostrar catalogo en preparacion;
- si `catalogo.depurar.configurado=false`, mostrar estado vacio operativo;
- si `configuracion.depurar.configuracion.whatsapp_numero_principal` viene vacio, no hardcodear telefono;
- antes de abrir WhatsApp, llamar `cotizacionDryRun`.
