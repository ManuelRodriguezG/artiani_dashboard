# ERP Ecommerce publico - Decision de activacion Fase 1

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-16  
Estado: paquete read-only para decidir autorizacion de DDL y primer lote.

## Estado verificado

- Host API correcto: `http://panel.com.local/ecommercePublico`
- Entorno local: MySQL y API responden.
- Contratos publicos: 9 endpoints Fase 1.
- DDL pendiente: 5 tablas `erp_ecommerce_*`.
- SKUs publicables Fase 1: conteo vivo; consultar `uat_ecommerce_publico_autorizacion_paquete_readonly.php` o `uat_ecommerce_publico_api_contracts_readonly.php`.
- Publicaciones activas: `0`.
- Senal frontend: `amarillo_mock_contratos`.

## Hashes de planes read-only

DDL Fase 1:

```text
sha256_sql=bebf5b2c35fe10f37b9a307a6f60f68a3340249f47d796f0ccfbca10e2d295ba
```

Configuracion publica ejemplo:

```text
sha256_sql=30df605be6e8ffea724fd9f8e72497b29e89fd5ec18d82a8670c295c1bd5f320
```

Primer SKU disponible sugerido:

```text
id_sku=1759
sku=TP-40372-100GR
nombre=Alimento churro blanco para peces 100 gr
disponibilidad_publica_sugerida=disponible
sha256_sql_borrador=6f8668cd4dfadcc287e8b2c77353c2a6580aae25e2f5fdc60cd79b08337bea8c
```

Segundo SKU disponible sugerido:

```text
id_sku=1757
sku=TP-40372-25GR
nombre=Alimento churro blanco para peces 25 gr
disponibilidad_publica_sugerida=disponible
sha256_sql_borrador=13b7974f4ad1de09def9b0ac4cb5094296832a9b9469d9c0f1d99cd51d819c1d
```

## Decision de primer lote

Comando para ver solo disponibles:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_lote_inicial_readonly.php --limite=12 --solo_disponibles=1
```

Resultado actual:

- total disponibles sugeridos: `2`;
- mascota inferida: `pez`;
- necesidad inferida: `alimento`;
- sin advertencias de agotados.

Comando para lote amplio:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_lote_inicial_readonly.php --limite=24
```

Resultado actual:

- total sugerido: `24`;
- incluye perro, gato, pez, ave y reptil;
- contiene muchos SKUs agotados;
- debe revisarse si se publican como `consultar disponibilidad` o se sustituyen.

## Recomendacion operativa

Para primera activacion real:

1. Aplicar DDL con respaldo externo y token.
2. Configurar WhatsApp, CORS y URL publica.
3. Crear borradores solo de los 2 SKUs disponibles.
4. Publicar 1 SKU disponible para validar green gate.
5. No publicar lote amplio hasta definir politica de agotados.

## Tokens de escritura

No ejecutar sin autorizacion explicita:

```text
ECOMMERCE_PUBLICO_DDL_FASE1
ECOMMERCE_PUBLICO_CONFIGURACION_FASE1
ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR
ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR
```

## Compuerta verde

El frontend externo solo debe pasar a datos reales cuando:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local
```

devuelva `ok=true`.

## Checklist final antes de apply

Paquete compacto de autorizacion:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_autorizacion_paquete_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=http://localhost:5173 --url=http://localhost:5173 --sku1=1759 --sku2=1757
```

Generar comandos exactos sin ejecutarlos:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=http://localhost:5173 --url=http://localhost:5173 --sku1=1759 --sku2=1757
```

Debe devolver `ok=true` antes de copiar cualquier comando `apply_authorized`.

No usar `REVISION_READONLY` como respaldo final para escritura; solo sirve para planes sin ejecutar.
