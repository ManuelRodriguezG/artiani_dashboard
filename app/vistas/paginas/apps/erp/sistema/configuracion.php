<?php
$diagnostico = isset($datos["diagnostico"]["depurar"]) ? $datos["diagnostico"]["depurar"] : array();
$ambiente = isset($diagnostico["ambiente"]) ? $diagnostico["ambiente"] : array();
$baseDatos = isset($diagnostico["base_datos"]) ? $diagnostico["base_datos"] : array();
$impresion = isset($diagnostico["impresion"]) ? $diagnostico["impresion"] : array();
$pendientes = isset($diagnostico["pendientes"]) ? $diagnostico["pendientes"] : array();
$parametros = isset($diagnostico["parametros"]) ? $diagnostico["parametros"] : array();
$requiereEsquema = !empty($diagnostico["requiere_esquema"]);
$tipoAmbiente = isset($ambiente["tipo"]) ? $ambiente["tipo"] : "";
$badgeAmbiente = $tipoAmbiente === "local" ? "badge-light-primary" : "badge-light-warning";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Configuracion del sistema</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Configuracion del sistema</h1>
                                <span class="text-muted">Entorno, base de datos activa y preparacion de impresion POS</span>
                            </div>
                            <span class="badge <?= htmlspecialchars($badgeAmbiente) ?> fs-7 text-uppercase"><?= htmlspecialchars($tipoAmbiente) ?></span>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-primary d-flex align-items-start gap-4">
                                <i class="bi bi-info-circle fs-2"></i>
                                <div>
                                    <div class="fw-bold mb-1">Esta consola es de revision segura.</div>
                                    <div>No guarda credenciales ni cambia la base de datos. Sirve para confirmar que el sistema esta usando el entorno correcto antes de operar POS, tickets o produccion.</div>
                                </div>
                            </div>
                            <?php if ($requiereEsquema): ?>
                                <div class="alert alert-warning d-flex align-items-start gap-4">
                                    <i class="bi bi-database-add fs-2"></i>
                                    <div>
                                        <div class="fw-bold mb-1">Falta aplicar el esquema de configuracion.</div>
                                        <div>Ya existe la pantalla, pero para guardar valores configurables se requieren las tablas SYS de parametros e historial.</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="row g-5">
                                <div class="col-xl-4">
                                    <div class="card h-100">
                                        <div class="card-header border-0 pt-6">
                                            <div class="card-title">
                                                <h2 class="fw-bold mb-0">Entorno</h2>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="d-flex justify-content-between border-bottom py-3">
                                                <span class="text-muted">Host</span>
                                                <span class="fw-semibold"><?= htmlspecialchars(isset($ambiente["server_name"]) ? $ambiente["server_name"] : "") ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between border-bottom py-3">
                                                <span class="text-muted">Protocolo</span>
                                                <span class="fw-semibold"><?= htmlspecialchars(isset($ambiente["protocolo"]) ? $ambiente["protocolo"] : "") ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between border-bottom py-3">
                                                <span class="text-muted">URL base</span>
                                                <span class="fw-semibold text-end"><?= htmlspecialchars(isset($ambiente["ruta_url"]) ? $ambiente["ruta_url"] : "") ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between py-3">
                                                <span class="text-muted">Zona horaria</span>
                                                <span class="fw-semibold"><?= htmlspecialchars(isset($ambiente["timezone"]) ? $ambiente["timezone"] : "") ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="card h-100">
                                        <div class="card-header border-0 pt-6">
                                            <div class="card-title">
                                                <h2 class="fw-bold mb-0">Base de datos</h2>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="d-flex justify-content-between border-bottom py-3">
                                                <span class="text-muted">Estado</span>
                                                <?php if (!empty($baseDatos["conectada"])): ?>
                                                    <span class="badge badge-light-success">Conectada</span>
                                                <?php else: ?>
                                                    <span class="badge badge-light-danger">Sin conexion</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex justify-content-between border-bottom py-3">
                                                <span class="text-muted">Host</span>
                                                <span class="fw-semibold"><?= htmlspecialchars(isset($baseDatos["host"]) ? $baseDatos["host"] : "") ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between border-bottom py-3">
                                                <span class="text-muted">Base</span>
                                                <span class="fw-semibold"><?= htmlspecialchars(isset($baseDatos["base"]) ? $baseDatos["base"] : "") ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between border-bottom py-3">
                                                <span class="text-muted">Usuario</span>
                                                <span class="fw-semibold"><?= htmlspecialchars(isset($baseDatos["usuario"]) ? $baseDatos["usuario"] : "") ?></span>
                                            </div>
                                            <div class="pt-3 text-muted fs-8"><?= htmlspecialchars(isset($baseDatos["mensaje"]) ? $baseDatos["mensaje"] : "") ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="card h-100">
                                        <div class="card-header border-0 pt-6">
                                            <div class="card-title">
                                                <h2 class="fw-bold mb-0">Tickets POS</h2>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="d-flex align-items-center gap-3 mb-4">
                                                <span class="symbol symbol-45px">
                                                    <span class="symbol-label bg-light-primary"><i class="bi bi-printer fs-2 text-primary"></i></span>
                                                </span>
                                                <div>
                                                    <div class="fw-bold">Impresora local</div>
                                                    <div class="text-muted fs-8"><?= htmlspecialchars(isset($impresion["estado"]) ? $impresion["estado"] : "") ?></div>
                                                </div>
                                            </div>
                                            <p class="text-gray-700 mb-0"><?= htmlspecialchars(isset($impresion["recomendacion"]) ? $impresion["recomendacion"] : "") ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mt-6">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title">
                                        <h2 class="fw-bold mb-0">Parametros configurables</h2>
                                    </div>
                                    <div class="card-toolbar">
                                        <button type="button" class="btn btn-primary" id="sys_config_guardar"<?= $requiereEsquema ? ' disabled' : '' ?>>
                                            <i class="bi bi-save"></i>
                                            Guardar configuracion
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="row g-5" id="sys_config_parametros">
                                        <?php if (empty($parametros)): ?>
                                            <div class="col-12 text-muted py-6">Sin parametros configurables cargados.</div>
                                        <?php endif; ?>
                                        <?php foreach ($parametros as $parametro): ?>
                                            <div class="col-md-6 col-xl-4">
                                                <div class="border rounded p-5 h-100">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span class="badge badge-light text-uppercase"><?= htmlspecialchars($parametro["grupo"]) ?></span>
                                                        <span class="text-muted fs-8"><?= htmlspecialchars($parametro["tipo_dato"]) ?></span>
                                                    </div>
                                                    <label class="form-label fw-bold"><?= htmlspecialchars($parametro["clave"]) ?></label>
                                                    <?php if ($parametro["clave"] === "sistema.ambiente_operativo"): ?>
                                                        <select class="form-select form-select-solid sys-config-input" data-clave="<?= htmlspecialchars($parametro["clave"]) ?>">
                                                            <option value="local"<?= $parametro["valor"] === "local" ? " selected" : "" ?>>Local</option>
                                                            <option value="productivo"<?= $parametro["valor"] === "productivo" ? " selected" : "" ?>>Productivo</option>
                                                            <option value="pruebas"<?= $parametro["valor"] === "pruebas" ? " selected" : "" ?>>Pruebas</option>
                                                        </select>
                                                    <?php elseif ($parametro["clave"] === "pos.impresion.modo"): ?>
                                                        <select class="form-select form-select-solid sys-config-input" data-clave="<?= htmlspecialchars($parametro["clave"]) ?>">
                                                            <option value="puente_local"<?= $parametro["valor"] === "puente_local" ? " selected" : "" ?>>Puente local</option>
                                                            <option value="navegador"<?= $parametro["valor"] === "navegador" ? " selected" : "" ?>>Navegador</option>
                                                            <option value="desactivada"<?= $parametro["valor"] === "desactivada" ? " selected" : "" ?>>Desactivada</option>
                                                        </select>
                                                    <?php else: ?>
                                                        <input class="form-control form-control-solid sys-config-input" data-clave="<?= htmlspecialchars($parametro["clave"]) ?>" value="<?= htmlspecialchars($parametro["valor"]) ?>"<?= $parametro["tipo_dato"] === "numero" ? ' type="number" min="0"' : '' ?>>
                                                    <?php endif; ?>
                                                    <div class="text-muted fs-8 mt-2"><?= htmlspecialchars($parametro["descripcion"]) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mt-5">
                                        <label class="form-label">Motivo del cambio</label>
                                        <input class="form-control form-control-solid" id="sys_config_motivo" placeholder="Ej. Preparacion de pruebas en tienda / ajuste de impresora POS">
                                    </div>
                                </div>
                            </div>
                            <div class="card mt-6">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title">
                                        <h2 class="fw-bold mb-0">Siguiente configuracion recomendada</h2>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="row g-4">
                                        <?php foreach ($pendientes as $pendiente): ?>
                                            <div class="col-md-4">
                                                <div class="border rounded p-5 h-100">
                                                    <i class="bi bi-check2-circle fs-2 text-primary"></i>
                                                    <div class="fw-semibold mt-3"><?= htmlspecialchars($pendiente) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card mt-6">
                                <div class="card-body">
                                    <div class="fw-bold mb-2">Decision operativa</div>
                                    <div class="text-gray-700">
                                        La configuracion general pertenece a Administracion/SYS. POS consumira la configuracion de impresion, pero no debe decidir la base de datos ni el entorno del sistema. Para imprimir tickets desde productivo, la ruta sana es un puente local por terminal o sucursal que se comunique con el navegador/POS y use la impresora instalada en esa computadora.
                                    </div>
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
<script src="/assets/js/custom/apps/erp/sistema/configuracion.js?v=20260723-configurable1"></script>
</body>
</html>
