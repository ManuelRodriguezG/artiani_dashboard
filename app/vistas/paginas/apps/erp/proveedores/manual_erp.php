<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Manual Proveedores ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Manual de uso - Proveedores ERP</h1>
                            <span class="text-muted">Guia operativa para administrar proveedores, listas, matching y costos sin ensuciar Catalogo ni Compras.</span>
                        </div>
                        <div class="d-flex gap-3">
                            <a class="btn btn-light" href="/proveedor/mostrar_proveedores_erp">
                                <i class="bi bi-arrow-left"></i> Volver a proveedores
                            </a>
                        </div>
                    </div>
                </div>

                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="row g-6">
                            <div class="col-xl-3">
                                <div class="card position-sticky" style="top: 90px;">
                                    <div class="card-body">
                                        <div class="fw-bold text-uppercase text-muted fs-8 mb-4">Contenido</div>
                                        <div class="d-grid gap-2">
                                            <a class="btn btn-sm btn-light text-start" href="#objetivo">Objetivo</a>
                                            <a class="btn btn-sm btn-light text-start" href="#flujo">Flujo recomendado</a>
                                            <a class="btn btn-sm btn-light text-start" href="#maestro">Maestro proveedor</a>
                                            <a class="btn btn-sm btn-light text-start" href="#listas">Listas de proveedor</a>
                                            <a class="btn btn-sm btn-light text-start" href="#matching">Matching</a>
                                            <a class="btn btn-sm btn-light text-start" href="#catalogo">Incidencias a Catalogo</a>
                                            <a class="btn btn-sm btn-light text-start" href="#costos">Costos</a>
                                            <a class="btn btn-sm btn-light text-start" href="#compras">Uso en Compras</a>
                                            <a class="btn btn-sm btn-light text-start" href="#errores">Errores comunes</a>
                                            <a class="btn btn-sm btn-light text-start" href="#checklist">Checklist</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-9">
                                <section class="card mb-6" id="objetivo">
                                    <div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">1. Objetivo del modulo</h2>
                                        <p class="text-gray-700 mb-3">
                                            Proveedores ERP administra la informacion operativa del proveedor: identidad, fiscales, contactos, condiciones, documentos, listas, productos de proveedor, matching contra Catalogo y costos aplicables.
                                        </p>
                                        <div class="alert alert-primary mb-0">
                                            La lista del proveedor es evidencia y fuente de referencia. No debe crear productos, relaciones ni costos definitivos sin revision.
                                        </div>
                                    </div>
                                </section>

                                <section class="card mb-6" id="flujo">
                                    <div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">2. Flujo recomendado</h2>
                                        <div class="table-responsive">
                                            <table class="table table-row-dashed align-middle gy-4">
                                                <thead>
                                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                        <th>Paso</th>
                                                        <th>Accion</th>
                                                        <th>Resultado esperado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr><td>1</td><td>Crear o revisar proveedor</td><td>Proveedor activo con datos basicos confiables.</td></tr>
                                                    <tr><td>2</td><td>Agregar fiscales, contactos y condiciones</td><td>Compras sabe con quien tratar y bajo que condiciones.</td></tr>
                                                    <tr><td>3</td><td>Cargar lista o evidencia</td><td>Archivo original guardado y vista previa disponible.</td></tr>
                                                    <tr><td>4</td><td>Mapear columnas e importar renglones</td><td>Productos del proveedor quedan como detalle de lista.</td></tr>
                                                    <tr><td>5</td><td>Limpiar renglones que no son productos</td><td>La lista queda operativa y sin notas basura.</td></tr>
                                                    <tr><td>6</td><td>Hacer matching contra Catalogo</td><td>Se seleccionan coincidencias confiables.</td></tr>
                                                    <tr><td>7</td><td>Crear incidencia a Catalogo cuando falta SKU ERP</td><td>Catalogo recibe el pendiente con datos base del proveedor.</td></tr>
                                                    <tr><td>8</td><td>Aplicar relaciones y costos</td><td>Compras puede usar proveedor, SKU proveedor y costo vigente.</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </section>

                                <section class="card mb-6" id="maestro">
                                    <div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">3. Maestro de proveedor</h2>
                                        <p class="text-gray-700">Usa el maestro para mantener la informacion estable del proveedor. No mezcles aqui datos que cambian por lista, como costos por producto.</p>
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <div class="border rounded p-4 h-100">
                                                    <div class="fw-bold mb-2">Datos generales</div>
                                                    <ul class="mb-0 text-gray-700">
                                                        <li>Nombre comercial y razon social.</li>
                                                        <li>Tipo de proveedor.</li>
                                                        <li>Estatus ERP.</li>
                                                        <li>Observaciones operativas.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="border rounded p-4 h-100">
                                                    <div class="fw-bold mb-2">Datos complementarios</div>
                                                    <ul class="mb-0 text-gray-700">
                                                        <li>Datos fiscales.</li>
                                                        <li>Contactos por area.</li>
                                                        <li>Condiciones comerciales y logisticas.</li>
                                                        <li>Documentos y evidencias.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="card mb-6" id="listas">
                                    <div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">4. Listas de proveedor</h2>
                                        <p class="text-gray-700">
                                            Una lista representa lo que el proveedor ofrece en un momento: productos, codigos, costos, moneda, unidad, factor, existencia reportada y archivo original.
                                        </p>
                                        <div class="alert alert-info">
                                            Puedes recargar una lista actualizada. El sistema debe anexar solo renglones nuevos y omitir productos ya existentes por identificadores como SKU proveedor, codigo de barras o codigo interno.
                                        </div>
                                        <h3 class="fw-bold fs-5 mt-5 mb-3">Como cargar una lista</h3>
                                        <ol class="text-gray-700">
                                            <li>Abre el proveedor.</li>
                                            <li>Entra a la seccion de listas.</li>
                                            <li>Crea o edita una lista.</li>
                                            <li>Sube el archivo original que recibiste o preparaste.</li>
                                            <li>Abre la vista previa.</li>
                                            <li>Elige cuantas filas mostrar: 100, 500 o 1000.</li>
                                            <li>Mapea las columnas correctas.</li>
                                            <li>Importa renglones.</li>
                                        </ol>
                                        <h3 class="fw-bold fs-5 mt-5 mb-3">Columnas importantes</h3>
                                        <div class="table-responsive">
                                            <table class="table table-row-dashed gy-3">
                                                <tbody>
                                                    <tr><td class="fw-bold">SKU proveedor</td><td>Codigo principal que usa el proveedor para identificar el producto.</td></tr>
                                                    <tr><td class="fw-bold">Codigo interno</td><td>Otro codigo del proveedor o de la lista. Si es el unico codigo, puede ayudar a evitar duplicados.</td></tr>
                                                    <tr><td class="fw-bold">Codigo de barras</td><td>EAN/UPC o codigo escaneable si el proveedor lo entrega.</td></tr>
                                                    <tr><td class="fw-bold">Descripcion</td><td>Nombre o descripcion del producto segun proveedor.</td></tr>
                                                    <tr><td class="fw-bold">Costo</td><td>Costo reportado por proveedor. No es costo definitivo hasta aplicar flujo de costos.</td></tr>
                                                    <tr><td class="fw-bold">Moneda</td><td>MXN, USD u otra moneda de la lista.</td></tr>
                                                    <tr><td class="fw-bold">Unidad y factor</td><td>Como compra el proveedor: pieza, caja, paquete y conversion a unidad base.</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </section>

                                <section class="card mb-6" id="matching">
                                    <div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">5. Matching contra Catalogo</h2>
                                        <p class="text-gray-700">
                                            Matching relaciona un renglon de proveedor con un SKU ERP existente. Sirve para que Compras pueda elegir productos del proveedor sin crear duplicados en Catalogo.
                                        </p>
                                        <div class="row g-4">
                                            <div class="col-md-4">
                                                <div class="border rounded p-4 h-100">
                                                    <div class="fw-bold text-success mb-2">Match confiable</div>
                                                    <p class="mb-0 text-gray-700">Coincide por relacion activa o codigo exacto. Puede seleccionarse con matching masivo si el sistema lo considera unico.</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="border rounded p-4 h-100">
                                                    <div class="fw-bold text-warning mb-2">Ambiguo</div>
                                                    <p class="mb-0 text-gray-700">Hay varios candidatos. Requiere revision manual antes de seleccionar.</p>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="border rounded p-4 h-100">
                                                    <div class="fw-bold text-muted mb-2">Sin match</div>
                                                    <p class="mb-0 text-gray-700">No hay SKU ERP claro. Puede requerir incidencia hacia Catalogo.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <h3 class="fw-bold fs-5 mt-5 mb-3">Regla practica</h3>
                                        <p class="text-gray-700 mb-0">
                                            No todos los productos del proveedor tienen que existir en Catalogo. Solo se manda a Catalogo lo que realmente se necesita operar, comprar, analizar o vender.
                                        </p>
                                    </div>
                                </section>

                                <section class="card mb-6" id="catalogo">
                                    <div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">6. Incidencias hacia Catalogo</h2>
                                        <p class="text-gray-700">
                                            Cuando un producto del proveedor no existe en Catalogo y se necesita para operar, genera una incidencia desde el renglon del proveedor. La incidencia arrastra informacion base: proveedor, lista, codigo, descripcion, marca, costo y evidencia.
                                        </p>
                                        <div class="alert alert-warning mb-0">
                                            Catalogo decide si crea un SKU temporal, completa datos faltantes y activa el SKU. Proveedores no debe crear productos definitivos automaticamente.
                                        </div>
                                    </div>
                                </section>

                                <section class="card mb-6" id="costos">
                                    <div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">7. Costos de proveedor</h2>
                                        <p class="text-gray-700">
                                            El costo de una lista es referencia de proveedor. Para que Compras lo use mejor, debe estar relacionado con SKU ERP y SKU proveedor, tener moneda, unidad/factor y evidencia.
                                        </p>
                                        <ul class="text-gray-700 mb-0">
                                            <li>Aplicar relacion no significa aplicar costo.</li>
                                            <li>Aplicar costo no cambia compras ya realizadas.</li>
                                            <li>`costo_referencia` es una referencia, no historia contable.</li>
                                            <li>El costo mas confiable a futuro vendra de compras enviadas/terminadas y no de una lista sin validar.</li>
                                        </ul>
                                    </div>
                                </section>

                                <section class="card mb-6" id="compras">
                                    <div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">8. Como llega a Compras</h2>
                                        <p class="text-gray-700">
                                            Compras consume relaciones activas proveedor-SKU y costos aplicados. Cuando una orden se envia, sus costos quedan como snapshot de esa compra y no deben modificarse directamente.
                                        </p>
                                        <div class="table-responsive">
                                            <table class="table table-row-dashed gy-3">
                                                <tbody>
                                                    <tr><td class="fw-bold">Proveedor activo</td><td>Permite operar con ese proveedor.</td></tr>
                                                    <tr><td class="fw-bold">SKU ERP activo</td><td>Permite pedir/comprar producto dentro del ERP.</td></tr>
                                                    <tr><td class="fw-bold">Relacion proveedor-SKU</td><td>Permite ubicar el SKU del proveedor y su informacion de compra.</td></tr>
                                                    <tr><td class="fw-bold">Costo vigente</td><td>Ayuda a precargar costo, pero Compras guarda su propio costo de orden.</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </section>

                                <section class="card mb-6" id="errores">
                                    <div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">9. Errores comunes y como resolverlos</h2>
                                        <div class="accordion" id="proveedores_manual_errores">
                                            <div class="accordion-item">
                                                <h3 class="accordion-header">
                                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#err_preview">
                                                        La vista previa no muestra todos los productos
                                                    </button>
                                                </h3>
                                                <div id="err_preview" class="accordion-collapse collapse show" data-bs-parent="#proveedores_manual_errores">
                                                    <div class="accordion-body text-gray-700">
                                                        Cambia el selector de filas a 500 o 1000. Si aun falta informacion, revisa si el archivo trae varias hojas o renglones ocultos.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <h3 class="accordion-header">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#err_columnas">
                                                        Se importo el codigo en una columna distinta
                                                    </button>
                                                </h3>
                                                <div id="err_columnas" class="accordion-collapse collapse" data-bs-parent="#proveedores_manual_errores">
                                                    <div class="accordion-body text-gray-700">
                                                        Revisa el mapeo antes de importar. Si el proveedor solo manda una columna llamada CODIGO, normalmente debe mapearse como SKU proveedor o codigo interno, pero debe ser consistente.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <h3 class="accordion-header">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#err_basura">
                                                        Se importaron notas o renglones que no son productos
                                                    </button>
                                                </h3>
                                                <div id="err_basura" class="accordion-collapse collapse" data-bs-parent="#proveedores_manual_errores">
                                                    <div class="accordion-body text-gray-700">
                                                        Usa la accion de eliminar renglon antes de aplicar relaciones o costos. Si ya tiene relacion o costo aplicado, requiere revision antes de limpiar.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="accordion-item">
                                                <h3 class="accordion-header">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#err_compras">
                                                        Compras no encuentra el producto
                                                    </button>
                                                </h3>
                                                <div id="err_compras" class="accordion-collapse collapse" data-bs-parent="#proveedores_manual_errores">
                                                    <div class="accordion-body text-gray-700">
                                                        Revisa que el proveedor este activo, que el SKU ERP exista y este activo, que exista relacion proveedor-SKU activa y que el producto no este solo como incidencia pendiente.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="card mb-6" id="checklist">
                                    <div class="card-body">
                                        <h2 class="fw-bold fs-4 mb-4">10. Checklist antes de considerar una lista lista para operar</h2>
                                        <div class="row g-3">
                                            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">Proveedor correcto y activo.</label></div></div>
                                            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">Archivo original guardado como evidencia.</label></div></div>
                                            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">Columnas mapeadas correctamente.</label></div></div>
                                            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">Renglones basura eliminados.</label></div></div>
                                            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">Matches confiables seleccionados.</label></div></div>
                                            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">Incidencias enviadas a Catalogo si faltan SKUs.</label></div></div>
                                            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">Relaciones proveedor-SKU aplicadas cuando corresponde.</label></div></div>
                                            <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label">Costos revisados antes de usarse como referencia.</label></div></div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script>var hostUrl = "assets/";</script>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
</body>
</html>
