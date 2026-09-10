# Ecommerce Publico - Configuracion CMS de Busqueda

Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09

## Objetivo

Permitir que CMS/Ecommerce administre busqueda inteligente sin tocar codigo PHP.

La API publica ya soporta leer configuracion desde:

```text
erp_ecommerce_configuracion.clave = busqueda_inteligente_config
estatus = activo
valor = JSON
```

Si no existe esa configuracion, API usa defaults seguros en codigo.

## Vista interna disponible

```text
GET /cms/frontend/busqueda
```

Uso:

- Cargar la configuracion actual del buscador.
- Editar el JSON operativo.
- Formatear y validar JSON antes de publicar.
- Probar una busqueda real contra `GET /ecommercePublico/busqueda`.
- Publicar la clave `busqueda_inteligente_config`.

Endpoints internos:

```text
GET /cms/frontend_busqueda_manifest_erp
POST /cms/frontend_busqueda_publicar_erp
```

El POST requiere sesion, permiso `cms.publicar` o `catalogo.editar`, CSRF y registra auditoria explicita.

## Endpoint de diagnostico

```text
GET /ecommercePublico/busqueda_manifest
```

Campos clave:

```text
depurar.fuente
depurar.configuracion.sinonimos
depurar.configuracion.stopwords
depurar.configuracion.prioridad_terminos
depurar.configuracion.categorias_probables
depurar.configuracion.boosts
depurar.configuracion.mensajes
depurar.cms.clave_configuracion
```

## JSON editable sugerido

```json
{
  "sinonimos": {
    "pecera": "acuario",
    "peceras": "acuario",
    "cascada": "filtro",
    "bomba": "oxigenador",
    "bombas": "oxigenador",
    "comida": "alimento",
    "croqueta": "alimento",
    "croquetas": "alimento",
    "jaulita": "jaula",
    "transportadora": "kennel",
    "camita": "cama"
  },
  "stopwords": [
    "para",
    "de",
    "del",
    "la",
    "el",
    "los",
    "las",
    "con",
    "en",
    "un",
    "una",
    "por",
    "y",
    "producto",
    "productos"
  ],
  "prioridad_terminos": [
    "filtro",
    "alimento",
    "jaula",
    "cama",
    "kennel",
    "transportadora",
    "sustrato",
    "shampoo",
    "collar",
    "correa",
    "oxigenador"
  ],
  "categorias_probables": [
    {
      "cuando_terminos": ["filtro", "oxigenador"],
      "atributos": {
        "habitat": "acuario"
      },
      "nombre": "Filtracion y oxigenacion",
      "path_slug": "acuario-y-peces/equipamiento-tecnico/filtracion-y-oxigenacion",
      "url": "/categoria/acuario-y-peces/equipamiento-tecnico/filtracion-y-oxigenacion"
    }
  ],
  "boosts": {
    "nombre_principal": 60,
    "categoria_principal": 30,
    "nombre_termino": 12,
    "categoria_termino": 8,
    "marca_termino": 6,
    "sku_termino": 10,
    "categoria_probable": 40,
    "imagen": 3,
    "precio": 3
  },
  "mensajes": {
    "sin_resultados": "No encontramos una coincidencia exacta, pero estos productos o categorias pueden ayudarte a encontrar una opcion adecuada."
  }
}
```

## Reglas para CMS

- Validar que el JSON sea valido antes de publicar.
- No permitir slugs inventados: `categorias_probables[].path_slug` debe venir de `GET /ecommercePublico/categorias`.
- No permitir URLs externas en reglas de categoria.
- Publicar cambios como una sola configuracion activa.
- Guardar auditoria de usuario y fecha al modificar.
- Mantener historial o borradores antes de reemplazar reglas activas.
- No tocar productos, publicaciones, precios ni inventario desde esta pantalla.

## Prueba UAT

```text
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_busqueda_inteligente_readonly.php http://panel.com.local
```

Resultado esperado:

```text
ok=true
senal_frontend=busqueda_inteligente_lista
manifest.clave_configuracion=busqueda_inteligente_config
```
