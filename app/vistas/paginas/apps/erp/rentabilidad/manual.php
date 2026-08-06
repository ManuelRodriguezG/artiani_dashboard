<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Manual de Rentabilidad ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    <div class="app-toolbar py-3 py-lg-6">
                        <div class="app-container container-fluid d-flex flex-stack">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Manual de Rentabilidad</h1>
                                <span class="text-muted">Guia operativa para revisar costos, margen, escenarios y aprobaciones</span>
                            </div>
                            <a class="btn btn-light-primary" href="/rentabilidad/analisis"><i class="bi bi-arrow-left"></i> Volver</a>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="row g-6">
                                <div class="col-xl-3">
                                    <div class="card"><div class="card-body d-flex flex-column gap-2">
                                        <a class="btn btn-sm btn-light text-start" href="#objetivo">Objetivo</a>
                                        <a class="btn btn-sm btn-light text-start" href="#vistas">Vistas</a>
                                        <a class="btn btn-sm btn-light text-start" href="#flujo">Flujo recomendado</a>
                                        <a class="btn btn-sm btn-light text-start" href="#campos">Campos clave</a>
                                        <a class="btn btn-sm btn-light text-start" href="#aprobaciones">Aprobaciones</a>
                                        <a class="btn btn-sm btn-light text-start" href="#reglas">Reglas</a>
                                    </div></div>
                                </div>
                                <div class="col-xl-9">
                                    <section class="card mb-6" id="objetivo"><div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">1. Objetivo</h2>
                                        <p class="text-gray-700 mb-0">Rentabilidad sirve para decidir si un SKU puede venderse por canal con utilidad real. Compara costo sin impuestos, precio sin impuestos, margen bruto, utilidad estimada, precio minimo rentable, inventario y evidencia de compras/XML.</p>
                                    </div></section>
                                    <section class="card mb-6" id="vistas"><div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">2. Vistas</h2>
                                        <div class="table-responsive"><table class="table align-middle gy-3"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Vista</th><th>Para que se usa</th></tr></thead><tbody>
                                            <tr><td>Resumen ejecutivo</td><td>Salud general, bloqueos y recomendaciones principales.</td></tr>
                                            <tr><td>SKU y escenarios</td><td>Comparar menudeo, mayoreo y alianza.</td></tr>
                                            <tr><td>Cierre comercial</td><td>Priorizar pendientes antes de cerrar precios.</td></tr>
                                            <tr><td>Aprobaciones</td><td>Preparar y consultar aprobaciones internas.</td></tr>
                                            <tr><td>Calidad de datos</td><td>Revisar fiscal/XML, costo base, variaciones y presentaciones.</td></tr>
                                            <tr><td>Historial</td><td>Consultar snapshots y vigencia.</td></tr>
                                        </tbody></table></div>
                                    </div></section>
                                    <section class="card mb-6" id="flujo"><div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">3. Flujo recomendado</h2>
                                        <ol class="text-gray-700 mb-0"><li>Filtra por SKU, proveedor, riesgo o canal.</li><li>Revisa Calidad cuando el SKU salga incompleto o con variacion.</li><li>Usa SKU y escenarios para comparar precio minimo rentable.</li><li>Pasa a Cierre para ver prioridad, responsable y checklist.</li><li>Usa Aprobaciones solo con evidencia suficiente.</li><li>Guarda snapshot o aprobacion solo con respaldo externo y autorizacion.</li></ol>
                                    </div></section>
                                    <section class="card mb-6" id="campos"><div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">4. Campos clave</h2>
                                        <p class="text-gray-700 mb-0">Costo, precio y minimo rentable se trabajan sin impuestos. La utilidad estimada descuenta gasto, comision y descuento del escenario. El riesgo indica si falta informacion, si hay margen bajo o si existe posible perdida.</p>
                                    </div></section>
                                    <section class="card mb-6" id="aprobaciones"><div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">5. Aprobaciones internas</h2>
                                        <p class="text-gray-700 mb-0">Una aprobacion interna guarda evidencia de decision por SKU y canal. No cambia Catalogo, no aplica precios, no toca Inventario y no publica en Ventas/ecommerce. Resolverla significa aprobar, rechazar o cancelar la evidencia interna.</p>
                                    </div></section>
                                    <section class="card mb-6" id="reglas"><div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">6. Reglas</h2>
                                        <ul class="text-gray-700 mb-0"><li>Consultar requiere <code>rentabilidad.ver</code>.</li><li>Guardar snapshot, recomendaciones o aprobaciones requiere <code>rentabilidad.snapshot</code>.</li><li>Configurar estructura requiere <code>rentabilidad.configurar</code>.</li><li>El modulo consulta Inventario, Compras/XML y Catalogo; no debe escribir en esos modulos.</li></ul>
                                    </div></section>
                                </div>
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
</body>
</html>
