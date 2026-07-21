<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Manual Listas de precios ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-20.
      Proposito: manual operativo de Comercial/Listas de precios.
      Impacto: permite iniciar fase 1 con POS y preparar contrato ecommerce sin tocar reglas de backend.
      Contrato: vista read-only; no escribe BD ni ejecuta acciones comerciales.
    -->
    <style>
        .lp-manual-grid { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 16px; }
        .lp-manual-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .lp-step { border-left: 3px solid #3e97ff; padding-left: 14px; }
        .lp-step + .lp-step { margin-top: 18px; }
        .lp-kbd { display: inline-flex; align-items: center; border: 1px solid #e4e6ef; background: #f9f9f9; border-radius: 6px; padding: 2px 8px; font-size: 12px; color: #3f4254; }
        @media (max-width: 1200px) { .lp-manual-grid { grid-template-columns: 1fr; } }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Manual de Listas de precios</h1>
                                <span class="text-muted">Operacion fase 1 para crear listas, capturar precios y validar POS.</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/comercial/listas_precios"><i class="bi bi-arrow-left"></i> Volver a listas</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="lp-manual-grid">
                                <main class="d-flex flex-column gap-4">
                                    <section class="lp-manual-card p-5">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                                            <div>
                                                <div class="fw-bold fs-4 mb-1">Estado de fase 1</div>
                                                <div class="text-muted">El modulo ya puede iniciar uso controlado para POS con UAT y permisos.</div>
                                            </div>
                                            <span class="badge badge-light-success fs-8">Listo para piloto POS</span>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="alert alert-light-success h-100 mb-0">
                                                    <div class="fw-semibold mb-2">Puede operarse ya</div>
                                                    <div class="fs-8">Crear encabezado, capturar precios por SKU, prevalidar lote, guardar con auditoria, asignar por segmento CRM, asignar excepcion por cliente y previsualizar precio POS.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="alert alert-light-warning h-100 mb-0">
                                                    <div class="fw-semibold mb-2">Usar con control</div>
                                                    <div class="fs-8">Ecommerce debe esperar contrato de exposicion por canal. Granel/presentaciones ya esta contemplado en plan, pero no debe asumirse terminado para venta real fraccionaria.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <section class="lp-manual-card p-5">
                                        <div class="fw-bold fs-4 mb-4">Flujo recomendado</div>
                                        <div class="lp-step">
                                            <div class="fw-semibold">1. Crear encabezado</div>
                                            <div class="text-muted fs-8">Entra a <span class="lp-kbd">ERP / Comercial / Listas de precios</span>, presiona <span class="lp-kbd">Nueva lista</span>, captura codigo, nombre, vigencia y observaciones. Guarda antes de cargar productos.</div>
                                        </div>
                                        <div class="lp-step">
                                            <div class="fw-semibold">2. Definir alcance</div>
                                            <div class="text-muted fs-8">Selecciona canal y almacen. Para una lista base usa canal general y prioridad baja/base. Para POS por tienda usa canal POS y almacen. Dentro del mismo nivel, menor prioridad gana.</div>
                                        </div>
                                        <div class="lp-step">
                                            <div class="fw-semibold">3. Capturar productos y precios</div>
                                            <div class="text-muted fs-8">Busca SKUs, revisa costo, precio general, margen y riesgo. Usa sugeridos, CSV o captura manual. Los cambios quedan pendientes hasta prevalidar y guardar.</div>
                                        </div>
                                        <div class="lp-step">
                                            <div class="fw-semibold">4. Prevalidar antes de guardar</div>
                                            <div class="text-muted fs-8">Presiona <span class="lp-kbd">Prevalidar cambios</span>. Corrige errores y precios con perdida. Si hay margen bajo o sin costo, decide si procede comercialmente.</div>
                                        </div>
                                        <div class="lp-step">
                                            <div class="fw-semibold">5. Guardar lote</div>
                                            <div class="text-muted fs-8">Guarda cambios. La pantalla muestra primeros SKUs guardados y errores por fila/SKU; la auditoria formal conserva eventos por partida.</div>
                                        </div>
                                        <div class="lp-step">
                                            <div class="fw-semibold">6. Asignar comercialmente</div>
                                            <div class="text-muted fs-8">Usa segmento CRM como camino normal. Cliente directo queda para excepciones puntuales o acuerdos negociados.</div>
                                        </div>
                                        <div class="lp-step">
                                            <div class="fw-semibold">7. Revisar y activar</div>
                                            <div class="text-muted fs-8">Ejecuta revision, valida que no haya bloqueos y activa. Cambios futuros no modifican ventas pasadas porque POS guarda snapshot.</div>
                                        </div>
                                    </section>

                                    <section class="lp-manual-card p-5">
                                        <div class="fw-bold fs-4 mb-4">Prioridad del precio</div>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed mb-0">
                                                <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Orden</th><th>Origen</th><th>Uso</th></tr></thead>
                                                <tbody>
                                                <tr><td>1</td><td>Excepcion POS autorizada</td><td>Precio manual/descuento con permiso, motivo y auditoria.</td></tr>
                                                <tr><td>2</td><td>Cliente directo CRM</td><td>Acuerdo puntual por cliente.</td></tr>
                                                <tr><td>3</td><td>Lista default CRM</td><td>Condicion comercial predeterminada del cliente.</td></tr>
                                                <tr><td>4</td><td>Segmento CRM</td><td>Ruta recomendada para recurrentes, mayoristas o tipos de cliente.</td></tr>
                                                <tr><td>5</td><td>Canal / almacen</td><td>Precio por POS, tienda, almacen o canal.</td></tr>
                                                <tr><td>6</td><td>General ERP</td><td>Base comercial.</td></tr>
                                                <tr><td>7</td><td>Catalogo general</td><td>Fallback temporal.</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </section>

                                    <section class="lp-manual-card p-5">
                                        <div class="fw-bold fs-4 mb-4">UAT minimo antes de operar en tienda</div>
                                        <ol class="text-gray-700">
                                            <li>Crear lista POS para almacen de prueba.</li>
                                            <li>Asignar SKU conocido con precio distinto al catalogo.</li>
                                            <li>Previsualizar precio POS y confirmar origen, lista y snapshot.</li>
                                            <li>Asignar segmento CRM a la lista y probar cliente del segmento.</li>
                                            <li>Confirmar que cliente directo gana solo cuando exista excepcion.</li>
                                            <li>Hacer venta piloto autorizada y revisar `id_lista_precio`, `lista_precio_snapshot` y `regla_precio_origen` en detalle.</li>
                                            <li>Cambiar precio despues y confirmar que la venta pasada no cambia.</li>
                                        </ol>
                                    </section>

                                    <section class="lp-manual-card p-5">
                                        <div class="fw-bold fs-4 mb-4">Prueba previa a venta real</div>
                                        <div class="text-muted fs-8 mb-3">Antes de cobrar en POS, ejecuta un dry-run con los mismos datos del piloto. Debe devolver la lista esperada, origen, snapshot y confirmar que no creo ventas.</div>
                                        <div class="bg-light rounded p-4 fs-8 text-gray-700">
                                            <code>C:\xampp\php\php.exe storage\uat\uat_listas_precios_piloto_pos_readonly.php --id_lista_precio=2 --id_sku=1760 --id_cliente_crm=2 --id_almacen=5 --canal=pos --origen_esperado=lista_segmento_cliente</code>
                                        </div>
                                        <div class="alert alert-light-success py-3 mt-3 mb-0">
                                            <div class="fw-semibold">Resultado esperado</div>
                                            <div class="fs-8">`PASS_PILOTO_POS_LISTAS_PRECIOS`, precio aplicado mayor a cero, snapshot presente y baseline de ventas intacto.</div>
                                        </div>
                                        <div class="separator my-4"></div>
                                        <div class="fw-semibold mb-2">Despues de vender en UAT</div>
                                        <div class="text-muted fs-8 mb-3">Con el folio real, valida que la venta guardo el snapshot historico. Este comando solo lee la venta.</div>
                                        <div class="bg-light rounded p-4 fs-8 text-gray-700">
                                            <code>C:\xampp\php\php.exe storage\uat\uat_listas_precios_pos_venta_snapshot_readonly.php --folio=POS-... --id_lista_precio=2 --id_sku=1760 --origen_esperado=lista_segmento_cliente</code>
                                        </div>
                                    </section>
                                </main>

                                <aside class="d-flex flex-column gap-4">
                                    <section class="lp-manual-card p-5">
                                        <div class="fw-bold fs-5 mb-3">Semaforo de arranque</div>
                                        <div class="d-flex flex-column gap-2">
                                            <div class="d-flex justify-content-between"><span>CRUD de listas</span><span class="badge badge-light-success">listo</span></div>
                                            <div class="d-flex justify-content-between"><span>Mesa por SKU/margen</span><span class="badge badge-light-success">listo</span></div>
                                            <div class="d-flex justify-content-between"><span>Segmentos CRM</span><span class="badge badge-light-success">listo</span></div>
                                            <div class="d-flex justify-content-between"><span>Resolutor POS</span><span class="badge badge-light-success">listo UAT</span></div>
                                            <div class="d-flex justify-content-between"><span>Ecommerce</span><span class="badge badge-light-warning">contrato pendiente</span></div>
                                            <div class="d-flex justify-content-between"><span>Granel/presentaciones</span><span class="badge badge-light-warning">fase posterior</span></div>
                                        </div>
                                    </section>

                                    <section class="lp-manual-card p-5">
                                        <div class="fw-bold fs-5 mb-3">Reglas de oro</div>
                                        <ul class="text-gray-700 ps-4 mb-0">
                                            <li>POS no decide precios.</li>
                                            <li>No actives listas con perdida sin autorizacion comercial.</li>
                                            <li>Segmento antes que cliente directo.</li>
                                            <li>Cambiar lista no cambia ventas pasadas.</li>
                                            <li>Ecommerce no debe leer listas POS internas sin regla explicita.</li>
                                        </ul>
                                    </section>

                                    <section class="lp-manual-card p-5">
                                        <div class="fw-bold fs-5 mb-3">Que revisar si algo no cuadra</div>
                                        <div class="text-gray-700 fs-8">
                                            Revisa canal, almacen, vigencia, estatus activo, prioridad, cliente CRM, segmento default y si existe una asignacion directa que tape al segmento.
                                        </div>
                                    </section>
                                </aside>
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
