<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Listas de precios ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-15.
      Proposito: convertir Listas de precios en una mesa operativa Comercial con productos, margen y alcance.
      Impacto: elimina accesos visuales a Ventas/POS/Checador dentro de esta vista y enfoca el flujo en listas.
      Contrato: backend guarda/resuelve precios; POS solo consume precio resuelto y snapshot.
    -->
    <style>
        .lp-shell { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 16px; }
        .lp-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .lp-listas { max-height: 680px; overflow: auto; }
        .lp-productos { max-height: 560px; overflow: auto; }
        .lp-productos thead th { position: sticky; top: 0; z-index: 2; background: #fff; box-shadow: 0 1px 0 #edf0f5; }
        .lp-lista-item { border: 1px solid #edf0f5; border-radius: 8px; cursor: pointer; }
        .lp-lista-item.active { border-color: #3e97ff; background: #f1f7ff; }
        .lp-price-input { min-width: 120px; }
        .lp-suggested { min-height: 18px; }
        .lp-row-dirty { background: #fff8dd; }
        .lp-row-selected { background: #f1f7ff; }
        .lp-side { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 16px; }
        .lp-flow { display: grid; grid-template-columns: repeat(5, minmax(150px, 1fr)); gap: 10px; }
        .lp-flow-step { border: 1px solid #edf0f5; border-radius: 8px; background: #fff; padding: 12px; text-align: left; min-height: 82px; }
        .lp-flow-step.is-ready { border-color: #50cd89; background: #f4fbf7; }
        .lp-flow-step.is-active { border-color: #3e97ff; box-shadow: 0 0 0 2px rgba(62, 151, 255, .08); }
        .lp-flow-step .lp-flow-index { width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #f1f3f7; color: #5e6278; font-weight: 700; font-size: 11px; }
        .lp-flow-step.is-ready .lp-flow-index { background: #50cd89; color: #fff; }
        .lp-editor-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
        .lp-editor-tab { border: 1px solid #e4e6ef; background: #fff; border-radius: 8px; padding: 10px 14px; color: #5e6278; font-weight: 600; }
        .lp-editor-tab.is-active { border-color: #3e97ff; background: #f1f7ff; color: #1b84ff; }
        .lp-editor-tab .badge { margin-left: 6px; }
        .lp-editor-panel[hidden] { display: none !important; }
        .lp-side.lp-tabs-mode { display: block; }
        .lp-side.lp-tabs-mode > aside { display: flex !important; }
        @media (max-width: 1400px) {
            .lp-shell, .lp-side { grid-template-columns: 1fr; }
        }
        @media (max-width: 1200px) {
            .lp-flow { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 576px) {
            .lp-flow { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    <div class="app-toolbar py-3 py-lg-5">
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">ERP / Comercial</div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Editor de lista de precios</h1>
                                <span class="text-muted">Crea o edita una lista concreta con productos, margen, alcance y asignaciones.</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/comercial/listas_precios"><i class="bi bi-arrow-left"></i> Listado</a>
                                <a class="btn btn-light-info" href="/comercial/listas_precios_manual"><i class="bi bi-question-circle"></i> Manual</a>
                                <button class="btn btn-light" id="lp_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                <button class="btn btn-primary" id="lp_nueva" type="button"><i class="bi bi-plus-lg"></i> Nueva lista</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="lp_alerta" class="mb-4"></div>
                            <section class="lp-card p-4 mb-4" id="lp_fase1">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="fw-bold fs-5">Arranque fase 1</div>
                                        <div class="text-muted fs-8">Semaforo read-only para comenzar pruebas operativas reales en POS.</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge badge-light" id="lp_fase1_estado">Sin validar</span>
                                        <button class="btn btn-sm btn-light-primary" id="lp_fase1_recargar" type="button"><i class="bi bi-shield-check"></i> Validar arranque</button>
                                    </div>
                                </div>
                                <div id="lp_fase1_readiness" class="mt-3 text-muted fs-7">Cargando semaforo de fase 1...</div>
                            </section>
                            <section class="lp-card p-4 mb-4" id="lp_flujo_operativo">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <div class="fw-bold fs-5">Flujo para crear una lista</div>
                                        <div class="text-muted fs-8">Trabaja de izquierda a derecha: encabezado, precios, alcance, clientes/segmentos y revision final.</div>
                                    </div>
                                    <span class="badge badge-light-primary" id="lp_flujo_estado">Sin lista</span>
                                </div>
                                <div class="lp-flow">
                                    <button class="lp-flow-step" type="button" data-lp-flujo="encabezado" data-lp-scroll="#lp_lista_codigo">
                                        <span class="lp-flow-index">1</span>
                                        <div class="fw-semibold mt-2">Encabezado</div>
                                        <div class="text-muted fs-8" id="lp_flujo_encabezado">Captura codigo y nombre.</div>
                                    </button>
                                    <button class="lp-flow-step" type="button" data-lp-flujo="productos" data-lp-scroll="#lp_producto_q">
                                        <span class="lp-flow-index">2</span>
                                        <div class="fw-semibold mt-2">Productos</div>
                                        <div class="text-muted fs-8" id="lp_flujo_productos">Carga SKUs y precios.</div>
                                    </button>
                                    <button class="lp-flow-step" type="button" data-lp-flujo="alcance" data-lp-scroll="#lp_lista_canal">
                                        <span class="lp-flow-index">3</span>
                                        <div class="fw-semibold mt-2">Alcance</div>
                                        <div class="text-muted fs-8" id="lp_flujo_alcance">Define canal y prioridad.</div>
                                    </button>
                                    <button class="lp-flow-step" type="button" data-lp-flujo="asignacion" data-lp-scroll="#lp_segmentos_crm">
                                        <span class="lp-flow-index">4</span>
                                        <div class="fw-semibold mt-2">Asignacion</div>
                                        <div class="text-muted fs-8" id="lp_flujo_asignacion">Usa segmentos o excepcion por cliente.</div>
                                    </button>
                                    <button class="lp-flow-step" type="button" data-lp-flujo="revision" data-lp-scroll="#lp_revision">
                                        <span class="lp-flow-index">5</span>
                                        <div class="fw-semibold mt-2">Revision</div>
                                        <div class="text-muted fs-8" id="lp_flujo_revision">Prevalida antes de activar.</div>
                                    </button>
                                </div>
                            </section>

                            <section class="lp-card p-4 mb-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <div class="fw-bold fs-5">Seccion de trabajo</div>
                                        <div class="text-muted fs-8">El editor muestra una parte a la vez para que no pierdas contexto.</div>
                                    </div>
                                    <span class="badge badge-light-primary" id="lp_editor_tab_estado">Encabezado</span>
                                </div>
                                <div class="lp-editor-tabs">
                                    <button class="lp-editor-tab is-active" type="button" data-lp-editor-tab="encabezado"><i class="bi bi-card-text"></i> Encabezado</button>
                                    <button class="lp-editor-tab" type="button" data-lp-editor-tab="productos"><i class="bi bi-tags"></i> Productos <span class="badge badge-light" id="lp_tab_productos_count">0</span></button>
                                    <button class="lp-editor-tab" type="button" data-lp-editor-tab="alcance"><i class="bi bi-bullseye"></i> Alcance</button>
                                    <button class="lp-editor-tab" type="button" data-lp-editor-tab="asignacion"><i class="bi bi-people"></i> Clientes/Segmentos</button>
                                    <button class="lp-editor-tab" type="button" data-lp-editor-tab="revision"><i class="bi bi-shield-check"></i> Revision</button>
                                </div>
                            </section>

                            <div class="lp-shell">
                                <aside class="lp-card p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="fw-bold fs-5">Listas</div>
                                        <span class="badge badge-light-primary" id="lp_kpi_total">0</span>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-12">
                                            <input class="form-control form-control-solid" id="lp_filtro_q" placeholder="Buscar lista">
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select form-select-solid" id="lp_filtro_estatus">
                                                <option value="">Estatus</option>
                                                <option value="borrador">Borrador</option>
                                                <option value="activa">Activa</option>
                                                <option value="pausada">Pausada</option>
                                                <option value="cancelada">Cancelada</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select form-select-solid" id="lp_filtro_canal">
                                                <option value="">Canal</option>
                                                <option value="general">General</option>
                                                <option value="pos">POS</option>
                                                <option value="pedido_tienda">Pedido tienda</option>
                                                <option value="ecommerce">Ecommerce</option>
                                                <option value="mayoreo">Mayoreo</option>
                                            </select>
                                        </div>
                                        <div class="col-7">
                                            <input class="form-control form-control-solid" id="lp_filtro_almacen" inputmode="numeric" placeholder="Almacen">
                                        </div>
                                        <div class="col-5">
                                            <button class="btn btn-light-primary w-100" id="lp_filtrar" type="button"><i class="bi bi-funnel"></i></button>
                                        </div>
                                    </div>
                                    <div class="lp-listas" id="lp_listas"></div>
                                </aside>

                                <main class="d-flex flex-column gap-4">
                                    <section class="lp-card p-4 lp-editor-panel" data-lp-editor-panel="encabezado">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                                            <div>
                                                <div class="fw-bold fs-5" id="lp_titulo_lista">Nueva lista</div>
                                                <div class="text-muted fs-8" id="lp_subtitulo_lista">Guarda el encabezado antes de capturar precios.</div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button class="btn btn-light-warning" id="lp_pausar" type="button"><i class="bi bi-pause-circle"></i> Pausar</button>
                                                <button class="btn btn-success" id="lp_activar" type="button"><i class="bi bi-check2-circle"></i> Activar</button>
                                                <button class="btn btn-primary" id="lp_guardar_lista" type="button"><i class="bi bi-save"></i> Guardar lista</button>
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <input type="hidden" id="lp_lista_id">
                                            <div class="col-lg-2">
                                                <label class="form-label text-muted fs-8 text-uppercase">Codigo</label>
                                                <input class="form-control form-control-solid" id="lp_lista_codigo" placeholder="LP-POS-01">
                                            </div>
                                            <div class="col-lg-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Nombre</label>
                                                <input class="form-control form-control-solid" id="lp_lista_nombre" placeholder="Lista mostrador">
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="form-label text-muted fs-8 text-uppercase">Vigencia inicio</label>
                                                <input class="form-control form-control-solid" id="lp_lista_inicio" type="date">
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="form-label text-muted fs-8 text-uppercase">Vigencia fin</label>
                                                <input class="form-control form-control-solid" id="lp_lista_fin" type="date">
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="form-label text-muted fs-8 text-uppercase">Estatus</label>
                                                <select class="form-select form-select-solid" id="lp_lista_estatus">
                                                    <option value="borrador">Borrador</option>
                                                    <option value="activa">Activa</option>
                                                    <option value="pausada">Pausada</option>
                                                    <option value="cancelada">Cancelada</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Observaciones</label>
                                                <input class="form-control form-control-solid" id="lp_lista_observaciones" placeholder="Motivo comercial, temporada o condicion interna">
                                            </div>
                                        </div>
                                    </section>

                                    <div class="lp-side lp-tabs-mode">
                                        <section class="lp-card p-4 lp-editor-panel" data-lp-editor-panel="productos">
                                            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                                                <div>
                                                    <div class="fw-bold fs-5">Productos y precios</div>
                                                    <div class="text-muted fs-8">Costo de referencia, precio general, precio de lista y margen estimado.</div>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <input class="form-control form-control-solid w-250px" id="lp_producto_q" placeholder="SKU o producto">
                                                    <select class="form-select form-select-solid w-175px" id="lp_producto_solo">
                                                        <option value="todos">Todos</option>
                                                        <option value="con_precio">Con precio</option>
                                                        <option value="sin_precio">Sin precio</option>
                                                        <option value="margen_bajo">Margen bajo</option>
                                                        <option value="perdida">Perdida</option>
                                                        <option value="sin_costo">Sin costo</option>
                                                        <option value="modificados">Modificados</option>
                                                    </select>
                                                    <div class="input-group input-group-solid w-125px">
                                                        <input class="form-control" id="lp_margen_minimo" inputmode="decimal" value="15" title="Margen minimo para filtro y alertas">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                    <button class="btn btn-light-primary" id="lp_productos_buscar" type="button"><i class="bi bi-search"></i></button>
                                                    <button class="btn btn-light" id="lp_exportar_csv" type="button"><i class="bi bi-download"></i></button>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mb-4">
                                                <span class="badge badge-light">Visibles <span id="lp_res_productos">0</span></span>
                                                <span class="badge badge-light-info">Seleccionados <span id="lp_res_seleccionados">0</span></span>
                                                <span class="badge badge-light-warning">Margen bajo <span id="lp_res_margen_bajo">0</span></span>
                                                <span class="badge badge-light-danger">Perdida <span id="lp_res_perdida">0</span></span>
                                                <span class="badge badge-light-danger">Sin costo <span id="lp_res_sin_costo">0</span></span>
                                                <span class="badge badge-light">Sin precio <span id="lp_res_sin_precio">0</span></span>
                                                <span class="badge badge-light-primary">Cambios <span id="lp_res_cambios">0</span></span>
                                            </div>
                                            <div class="alert alert-light py-3 mb-4" id="lp_cambios_barra">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                    <div>
                                                        <div class="fw-semibold">Sin cambios pendientes</div>
                                                        <div class="text-muted fs-8">Captura precios o aplica sugeridos para prevalidar el lote.</div>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <button class="btn btn-sm btn-light-primary" id="lp_ver_modificados" type="button"><i class="bi bi-list-check"></i> Ver modificados</button>
                                                        <button class="btn btn-sm btn-light-primary" id="lp_prevalidar_cambios_top" type="button"><i class="bi bi-shield-check"></i> Prevalidar</button>
                                                        <button class="btn btn-sm btn-light-danger" id="lp_limpiar_cambios_top" type="button"><i class="bi bi-arrow-counterclockwise"></i> Limpiar</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                                                <div class="d-flex flex-wrap gap-2 align-items-end">
                                                    <div>
                                                        <label class="form-label text-muted fs-8 text-uppercase">Aplicar a</label>
                                                        <select class="form-select form-select-solid w-175px" id="lp_accion_alcance">
                                                            <option value="seleccionados">Seleccionados</option>
                                                            <option value="visibles">Visibles</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="form-label text-muted fs-8 text-uppercase">Margen objetivo</label>
                                                        <div class="input-group input-group-solid w-175px">
                                                            <input class="form-control" id="lp_margen_objetivo" inputmode="decimal" value="35">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-light" id="lp_sugerir_margen" type="button"><i class="bi bi-lightbulb"></i> Calcular sugeridos</button>
                                                    <button class="btn btn-light-primary" id="lp_usar_sugeridos" type="button"><i class="bi bi-check2-square"></i> Usar sugeridos</button>
                                                    <button class="btn btn-light-warning" id="lp_sugerir_pendientes" type="button"><i class="bi bi-magic"></i> Sugerir pendientes</button>
                                                    <button class="btn btn-light-primary" id="lp_usar_pendientes" type="button"><i class="bi bi-check2-square"></i> Usar pendientes</button>
                                                    <button class="btn btn-light" id="lp_aplicar_margen" type="button"><i class="bi bi-percent"></i> Aplicar margen</button>
                                                    <button class="btn btn-light" id="lp_copiar_general" type="button"><i class="bi bi-arrow-down-square"></i> Copiar general</button>
                                                    <div>
                                                        <label class="form-label text-muted fs-8 text-uppercase">Redondeo</label>
                                                        <select class="form-select form-select-solid w-150px" id="lp_redondeo_modo">
                                                            <option value="entero">Entero</option>
                                                            <option value="medio">0.50</option>
                                                            <option value="noventa">.90</option>
                                                        </select>
                                                    </div>
                                                    <button class="btn btn-light" id="lp_redondear" type="button"><i class="bi bi-arrow-up-right-circle"></i> Redondear</button>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2 align-items-end">
                                                    <div>
                                                        <label class="form-label text-muted fs-8 text-uppercase">Copiar lista ID</label>
                                                        <input class="form-control form-control-solid w-150px" id="lp_copiar_lista_id" inputmode="numeric" placeholder="Origen">
                                                    </div>
                                                    <button class="btn btn-light-info" id="lp_comparar_lista" type="button"><i class="bi bi-columns-gap"></i> Comparar</button>
                                                    <button class="btn btn-light-primary" id="lp_aplicar_comparacion" type="button"><i class="bi bi-check2-square"></i> Usar diferencias</button>
                                                    <button class="btn btn-light" id="lp_copiar_lista" type="button"><i class="bi bi-copy"></i> Copiar lista</button>
                                                    <button class="btn btn-light-danger" id="lp_limpiar_cambios" type="button"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                    <button class="btn btn-light-primary" id="lp_prevalidar_cambios" type="button"><i class="bi bi-shield-check"></i> Prevalidar cambios</button>
                                                    <button class="btn btn-primary" id="lp_guardar_cambios" type="button"><i class="bi bi-save2"></i> Guardar cambios <span class="badge badge-light ms-2" id="lp_cambios_count">0</span></button>
                                                </div>
                                            </div>
                                            <div class="border rounded p-3 mb-4" id="lp_comparacion_resultado">
                                                <div class="text-muted fs-7">Compara contra otra lista para revisar diferencias antes de copiar precios.</div>
                                            </div>
                                            <div class="border rounded p-3 mb-4" id="lp_lote_prevalidacion">
                                                <div class="text-muted fs-7">Los cambios pendientes se prevalidan antes de guardar.</div>
                                            </div>
                                            <div class="border rounded p-3 mb-4">
                                                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
                                                    <div class="min-w-250px flex-grow-1">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Importar CSV</label>
                                                        <input class="form-control form-control-solid" id="lp_importar_csv" type="file" accept=".csv,text/csv">
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <button class="btn btn-light-primary" id="lp_importar_prevalidar" type="button"><i class="bi bi-file-earmark-check"></i> Prevalidar</button>
                                                        <button class="btn btn-light" id="lp_importar_aplicar" type="button"><i class="bi bi-check2-circle"></i> Aplicar importacion</button>
                                                    </div>
                                                </div>
                                                <div id="lp_importar_resultado" class="text-muted fs-7 mt-3">CSV esperado: id_sku o sku, y precio_lista.</div>
                                            </div>
                                            <div class="table-responsive lp-productos">
                                                <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                    <thead>
                                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                        <th class="w-40px text-center">
                                                            <input class="form-check-input" type="checkbox" id="lp_productos_select_all" title="Seleccionar visibles">
                                                        </th>
                                                        <th>Producto</th>
                                                        <th>Unidad</th>
                                                        <th class="text-end">Costo</th>
                                                        <th class="text-end">General</th>
                                                        <th class="text-end">Lista</th>
                                                        <th class="text-end">Margen / utilidad</th>
                                                        <th class="text-end">Accion</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="lp_productos"></tbody>
                                                </table>
                                            </div>
                                        </section>

                                        <aside class="d-flex flex-column gap-4">
                                            <section class="lp-card p-4 lp-editor-panel" data-lp-editor-panel="alcance">
                                                <div class="fw-bold fs-5 mb-3">Alcance</div>
                                                <div class="text-muted fs-8 mb-3">El alcance define donde puede competir esta lista; cliente y segmento se asignan en el bloque siguiente.</div>
                                                <div class="d-flex flex-wrap gap-2 mb-3">
                                                    <button class="btn btn-sm btn-light-primary" type="button" data-lp-alcance="general"><i class="bi bi-globe"></i> General</button>
                                                    <button class="btn btn-sm btn-light" type="button" data-lp-alcance="pos"><i class="bi bi-shop"></i> POS</button>
                                                    <button class="btn btn-sm btn-light" type="button" data-lp-alcance="ecommerce"><i class="bi bi-bag"></i> Ecommerce</button>
                                                    <button class="btn btn-sm btn-light" type="button" data-lp-alcance="mayoreo"><i class="bi bi-box-seam"></i> Mayoreo</button>
                                                </div>
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Canal</label>
                                                        <select class="form-select form-select-solid" id="lp_lista_canal">
                                                            <option value="general">General</option>
                                                            <option value="pos">POS</option>
                                                            <option value="pedido_tienda">Pedido tienda</option>
                                                            <option value="ecommerce">Ecommerce</option>
                                                            <option value="mayoreo">Mayoreo</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Almacen</label>
                                                        <input class="form-control form-control-solid" id="lp_lista_almacen" inputmode="numeric" placeholder="Todos">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Prioridad</label>
                                                        <input class="form-control form-control-solid" id="lp_lista_prioridad" inputmode="numeric" value="100">
                                                    </div>
                                                </div>
                                                <div id="lp_alcance_resumen" class="alert alert-light py-3 mt-3 mb-0 fs-7">Define canal, almacen y prioridad para esta lista.</div>
                                                <div class="border rounded p-3 mt-3" id="lp_prioridad_guia">
                                                    <div class="fw-semibold fs-8 text-muted text-uppercase mb-2">Guia de resolucion</div>
                                                    <div class="d-flex flex-column gap-2 fs-8">
                                                        <div class="d-flex justify-content-between gap-2"><span>1. Cliente directo</span><span class="badge badge-light-warning">excepcion</span></div>
                                                        <div class="d-flex justify-content-between gap-2"><span>2. Lista default CRM</span><span class="badge badge-light">cliente</span></div>
                                                        <div class="d-flex justify-content-between gap-2"><span>3. Segmento CRM</span><span class="badge badge-light-success">recomendado</span></div>
                                                        <div class="d-flex justify-content-between gap-2"><span>4. Canal / almacen</span><span class="badge badge-light-primary">alcance</span></div>
                                                        <div class="d-flex justify-content-between gap-2"><span>5. General ERP</span><span class="badge badge-light">base</span></div>
                                                    </div>
                                                    <div class="alert alert-light-primary py-2 mt-3 mb-0 fs-8" id="lp_prioridad_resumen">Dentro del mismo nivel, menor prioridad gana.</div>
                                                </div>
                                            </section>

                                            <section class="lp-card p-4 lp-editor-panel" data-lp-editor-panel="asignacion">
                                                <div class="fw-bold fs-5 mb-1">Clientes y segmentos</div>
                                                <div class="text-muted fs-8 mb-3">La asignacion directa a cliente es una excepcion; para miles de clientes debe usarse segmento/tipo CRM.</div>
                                                <div class="alert alert-light-success py-3 mb-3 fs-7">
                                                    Segmentos CRM activo: vincula esta lista a tipos de cliente para evitar asignaciones cliente por cliente.
                                                </div>
                                                <div class="border rounded p-3 mb-4" id="lp_segmentos_bloque">
                                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                                        <div>
                                                            <div class="fw-semibold fs-7">Segmentos CRM</div>
                                                            <div class="text-muted fs-8">Camino recomendado para recurrentes, mayoristas o grupos comerciales.</div>
                                                        </div>
                                                        <span class="badge badge-light-success">recomendado</span>
                                                    </div>
                                                    <div id="lp_segmentos_preparacion" class="mb-3"></div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div class="fw-semibold fs-8 text-muted text-uppercase">Tipos de cliente</div>
                                                        <button class="btn btn-sm btn-light-primary" id="lp_segmentos_recargar" type="button"><i class="bi bi-arrow-clockwise"></i></button>
                                                    </div>
                                                    <div id="lp_segmentos_crm" class="mb-4 text-muted fs-7">Cargando segmentos...</div>
                                                    <div class="row g-3">
                                                        <input type="hidden" id="lp_seg_asig_id">
                                                        <input type="hidden" id="lp_seg_id">
                                                        <div class="col-12">
                                                            <label class="form-label text-muted fs-8 text-uppercase">Segmento seleccionado</label>
                                                            <div class="input-group input-group-solid">
                                                                <input class="form-control" id="lp_seg_nombre" readonly placeholder="Selecciona un segmento CRM">
                                                                <button class="btn btn-light" id="lp_seg_nuevo" type="button"><i class="bi bi-plus-circle"></i></button>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label text-muted fs-8 text-uppercase">Canal</label>
                                                            <select class="form-select form-select-solid" id="lp_seg_canal">
                                                                <option value="general">General</option>
                                                                <option value="pos">POS</option>
                                                                <option value="pedido_tienda">Pedido tienda</option>
                                                                <option value="ecommerce">Ecommerce</option>
                                                                <option value="mayoreo">Mayoreo</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label text-muted fs-8 text-uppercase">Almacen</label>
                                                            <input class="form-control form-control-solid" id="lp_seg_almacen" inputmode="numeric" placeholder="Todos">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label text-muted fs-8 text-uppercase">Prioridad</label>
                                                            <input class="form-control form-control-solid" id="lp_seg_prioridad" inputmode="numeric" value="100">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label text-muted fs-8 text-uppercase">Estatus</label>
                                                            <select class="form-select form-select-solid" id="lp_seg_estatus">
                                                                <option value="activo">Activo</option>
                                                                <option value="pausado">Pausado</option>
                                                                <option value="cancelado">Cancelado</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label text-muted fs-8 text-uppercase">Inicio</label>
                                                            <input class="form-control form-control-solid" id="lp_seg_inicio" type="date">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label text-muted fs-8 text-uppercase">Fin</label>
                                                            <input class="form-control form-control-solid" id="lp_seg_fin" type="date">
                                                        </div>
                                                        <div class="col-6">
                                                            <button class="btn btn-light-primary w-100" id="lp_seg_validar" type="button"><i class="bi bi-diagram-3"></i> Validar segmento</button>
                                                        </div>
                                                        <div class="col-6">
                                                            <button class="btn btn-light w-100" id="lp_seg_guardar" type="button" disabled title="Validando segmentos/listas"><i class="bi bi-save"></i> Guardar vinculo</button>
                                                        </div>
                                                    </div>
                                                    <div id="lp_segmento_dryrun" class="mt-3"></div>
                                                    <div class="separator my-4"></div>
                                                    <div class="fw-semibold fs-7 mb-2">Vinculos por segmento</div>
                                                    <div id="lp_asignaciones_segmentos" class="text-muted fs-7">Selecciona una lista para ver segmentos vinculados.</div>
                                                </div>
                                                <div class="border rounded p-3" id="lp_clientes_excepcion_bloque">
                                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                                        <div>
                                                            <div class="fw-semibold fs-7">Excepcion por cliente</div>
                                                            <div class="text-muted fs-8">Usar solo para acuerdos puntuales; para volumen conviene segmento CRM.</div>
                                                        </div>
                                                        <span class="badge badge-light-warning">excepcion</span>
                                                    </div>
                                                    <div class="row g-3">
                                                        <input type="hidden" id="lp_asig_id">
                                                        <div class="col-12">
                                                            <label class="form-label text-muted fs-8 text-uppercase">Buscar cliente</label>
                                                            <div class="input-group input-group-solid">
                                                                <input class="form-control" id="lp_cliente_q" placeholder="Nombre, codigo, telefono o correo">
                                                                <button class="btn btn-light-primary" id="lp_cliente_buscar" type="button"><i class="bi bi-search"></i></button>
                                                            </div>
                                                        </div>
                                                        <div class="col-7">
                                                            <label class="form-label text-muted fs-8 text-uppercase">Cliente seleccionado</label>
                                                            <input class="form-control form-control-solid" id="lp_asig_cliente" inputmode="numeric" placeholder="id_cliente_crm">
                                                        </div>
                                                        <div class="col-5">
                                                            <label class="form-label text-muted fs-8 text-uppercase">Prioridad</label>
                                                            <input class="form-control form-control-solid" id="lp_asig_prioridad" inputmode="numeric" value="1">
                                                        </div>
                                                        <div class="col-12">
                                                            <button class="btn btn-light-primary w-100" id="lp_guardar_asig" type="button"><i class="bi bi-person-check"></i> Asignar cliente</button>
                                                        </div>
                                                    </div>
                                                    <div id="lp_clientes_resultados" class="mt-3"></div>
                                                    <div class="separator my-4"></div>
                                                    <div class="fw-semibold fs-7 mb-2">Clientes con excepcion</div>
                                                    <div id="lp_asignaciones" class="text-muted fs-7">Selecciona una lista para ver clientes.</div>
                                                </div>
                                            </section>

                                            <section class="lp-card p-4 lp-editor-panel" data-lp-editor-panel="revision">
                                                <div class="fw-bold fs-5 mb-3">Vista previa POS</div>
                                                <div class="row g-3">
                                                    <div class="col-7">
                                                        <label class="form-label text-muted fs-8 text-uppercase">SKU ID</label>
                                                        <input class="form-control form-control-solid" id="lp_preview_sku" inputmode="numeric" placeholder="id_sku">
                                                    </div>
                                                    <div class="col-5">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Cantidad</label>
                                                        <input class="form-control form-control-solid" id="lp_preview_cantidad" inputmode="decimal" value="1">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Almacen prueba</label>
                                                        <input class="form-control form-control-solid" id="lp_preview_almacen" inputmode="numeric" placeholder="Ej. 5">
                                                    </div>
                                                    <div class="col-12">
                                                        <button class="btn btn-light-primary w-100" id="lp_preview_btn" type="button"><i class="bi bi-calculator"></i> Resolver precio</button>
                                                    </div>
                                                </div>
                                                <div id="lp_preview_resultado" class="mt-3 text-muted fs-7">Selecciona un SKU para previsualizar.</div>
                                            </section>

                                            <section class="lp-card p-4 lp-editor-panel" data-lp-editor-panel="revision">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="fw-bold fs-5">Revision</div>
                                                    <button class="btn btn-sm btn-light-primary" id="lp_revision_btn" type="button"><i class="bi bi-shield-check"></i></button>
                                                </div>
                                                <div id="lp_revision" class="text-muted fs-7">Guarda o selecciona una lista para revisar activacion.</div>
                                            </section>

                                            <section class="lp-card p-4 lp-editor-panel" data-lp-editor-panel="revision">
                                                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                                                    <div>
                                                        <div class="fw-bold fs-5">Auditoria</div>
                                                        <div class="text-muted fs-8">Eventos comerciales, motivos y cambios de la lista.</div>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <select class="form-select form-select-sm form-select-solid w-125px" id="lp_auditoria_tipo">
                                                            <option value="">Todo</option>
                                                            <option value="lista">Lista</option>
                                                            <option value="precio">Precios</option>
                                                            <option value="segmento">Segmentos</option>
                                                            <option value="cliente">Clientes</option>
                                                        </select>
                                                        <button class="btn btn-sm btn-light-primary" id="lp_auditoria_btn" type="button"><i class="bi bi-clock-history"></i></button>
                                                    </div>
                                                </div>
                                                <div id="lp_auditoria" class="text-muted fs-7">Sin eventos cargados.</div>
                                            </section>
                                        </aside>
                                    </div>
                                </main>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/ventas/listas_precios.js?v=20260719-flujo-guiado1"></script>
</body>
</html>
