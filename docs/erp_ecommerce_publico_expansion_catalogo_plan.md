# ERP Ecommerce publico - Plan de expansion de catalogo

Documentacion IA: Codex GPT-5  
Fecha: 2026-07-18  
Estado: auditoria read-only para crecer el catalogo vivo despues del primer lote.

## Objetivo

Definir como ampliar productos publicados sin romper las reglas de Fase 1:

- no publicar todo SKU activo automaticamente;
- no mostrar stock exacto;
- no descontar inventario;
- no vender granel/fraccionarios;
- no usar legacy `ecom_*`;
- no prometer disponibilidad cuando el ERP dice agotado.

## Comando de auditoria

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_catalogo_readonly.php --limite=20 --pool=1500 --solo_disponibles=1
```

Para revisar tambien agotados publicables:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_catalogo_readonly.php --limite=20 --pool=1500
```

## Resultado actual

Despues de excluir las publicaciones existentes:

```text
candidatos_evaluados_sin_publicacion=1490
disponible=1
pocas_piezas=3
consultar_disponibilidad=0
agotado=1486
```

Esto significa que el catalogo tiene muchos productos aptos en calidad minima de ficha, pero la expansion inmediata con disponibilidad real es pequena.

## Candidatos disponibles actuales

- `SAL-50L` - Lampara 50 cm led con tapa - disponible - sugerido `pez/habitat`.
- `SCF-800` - Filtro de canastilla presurizado 960 l/hr - pocas piezas - sugerido `pez/habitat`.
- `SHF-600` - Filtro de cascada 650 l/hr - pocas piezas - sugerido `pez/habitat`.
- `SP-2823` - Jaula para aves tipo cilindro - pocas piezas - apta para ave/habitat.

## Paquete read-only para pasar a borradores

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_publicacion_paquete_readonly.php --skus=415,866,386,1138 --base=http://panel.com.local
```

Resultado actual:

```text
total_skus=4
listos_sin_revision=4
requieren_revision=0
bloqueos_revision=[]
```

El paquete genera comandos `apply_authorized`, pero no los ejecuta. Guardar borradores requiere autorizacion explicita, respaldo externo y token `ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR`.

## Checklist apply read-only

Compuerta consolidada:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_bundle_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138 --min_actual=2 --min_objetivo=6
```

Resultado actual:

```text
ok=true
senal_actual=verde_datos_reales
senal_expansion=lista_para_autorizacion
```

Checklist detallado:

```bash
C:\xampp\php\php.exe storage\uat\uat_ecommerce_publico_expansion_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=C:\xampp\panel_db_backups\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138
```

Resultado actual:

```text
ok=true
listos_para_borrador=4
publicaciones_esperadas_si_se_publican_todos=6
bloqueos=[]
```

Runbook completo:

```text
docs/erp_ecommerce_publico_expansion_6_productos_runbook.md
```

## Decision recomendada

No publicar masivamente agotados como si fueran disponibles.

Orden recomendado:

1. Revisar los 4 candidatos con disponibilidad positiva.
2. Confirmar que la metadata sugerida `pez/habitat` y `ave/habitat` es comercialmente correcta.
3. Publicarlos como borrador y revisar texto/imagen antes de publicarlos.
4. Mantener agotados fuera del catalogo salvo que el negocio quiera una seccion de consulta o productos bajo pedido.
5. Si se decide mostrar agotados, usar badge `Agotado` o `Consultar disponibilidad`, nunca `Disponible`.

## Implicacion para frontend

El frontend debe estar listo para un catalogo inicial pequeno:

- filtros con pocas opciones;
- resultados vacios por mascota/necesidad;
- mensajes de catalogo en crecimiento;
- carrito local limitado a productos `permite_cotizacion=true`;
- dry-run siempre obligatorio antes de WhatsApp.

## Guardrails

La auditoria no escribe BD, no crea publicaciones, no mueve inventario y no toca legacy `ecom_*`.
