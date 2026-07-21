<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Manual Ventas y POS ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-20.
      Proposito: manual operativo completo de Ventas y POS para capacitacion.
      Impacto: explica uso por pestana, roles, pasos, atajos, controles y limites operativos.
      Contrato: vista informativa; no consulta BD, no escribe BD y no invoca endpoints.
    -->
    <style>
        .pos-manual-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .pos-manual-nav { display: flex; flex-wrap: wrap; gap: 8px; }
        .pos-manual-nav .btn { border-radius: 8px; font-weight: 700; }
        .pos-manual-step { display: grid; grid-template-columns: 42px 1fr; gap: 12px; }
        .pos-manual-icon { width: 42px; height: 42px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: #f1f7ff; color: #1b84ff; }
        .pos-manual-list li + li { margin-top: 8px; }
        .pos-manual-section { scroll-margin-top: 95px; }
        .pos-manual-kbd { display: inline-flex; align-items: center; border: 1px solid #d9dde8; border-bottom-width: 2px; border-radius: 6px; padding: 1px 6px; color: #3f4254; background: #fff; font-size: .72rem; font-weight: 800; }
        .pos-manual-check { border-left: 3px solid #1b84ff; background: #f8fbff; border-radius: 8px; }
        .pos-manual-warning { border-left: 3px solid #f6c000; background: #fffaf0; border-radius: 8px; }
        .pos-manual-danger { border-left: 3px solid #f1416c; background: #fff5f8; border-radius: 8px; }
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
                        <div class="app-container container-fluid">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                                <div>
                                    <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Manual Ventas y POS</h1>
                                    <span class="text-muted">Guia completa para aprender a usar ventas, POS, caja, pedidos, devoluciones, evidencias, reportes y configuracion.</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> Abrir POS</a>
                                    <a class="btn btn-light-primary" href="/ventas/caja_turnos"><i class="bi bi-calculator"></i> Caja y turnos</a>
                                    <a class="btn btn-light" href="/ventas/reportes"><i class="bi bi-bar-chart"></i> Reportes</a>
                                </div>
                            </div>
                            <div class="pos-manual-card p-3">
                                <div class="fw-bold mb-2">Indice del modulo</div>
                                <div class="pos-manual-nav">
                                    <a class="btn btn-sm btn-light-primary" href="#manual-resumen"><i class="bi bi-compass"></i> Resumen</a>
                                    <a class="btn btn-sm btn-light" href="#manual-arranque"><i class="bi bi-clipboard-check"></i> Checklist arranque</a>
                                    <a class="btn btn-sm btn-light" href="#manual-pos"><i class="bi bi-shop-window"></i> POS</a>
                                    <a class="btn btn-sm btn-light" href="#manual-tablero"><i class="bi bi-receipt-cutoff"></i> Ventas</a>
                                    <a class="btn btn-sm btn-light" href="#manual-checador"><i class="bi bi-upc-scan"></i> Checador</a>
                                    <a class="btn btn-sm btn-light" href="#manual-pedidos"><i class="bi bi-journal-bookmark"></i> Pedidos</a>
                                    <a class="btn btn-sm btn-light" href="#manual-devoluciones"><i class="bi bi-arrow-counterclockwise"></i> Devoluciones</a>
                                    <a class="btn btn-sm btn-light" href="#manual-caja"><i class="bi bi-calculator"></i> Caja</a>
                                    <a class="btn btn-sm btn-light" href="#manual-movimientos"><i class="bi bi-cash-stack"></i> Movimientos</a>
                                    <a class="btn btn-sm btn-light" href="#manual-evidencias"><i class="bi bi-file-earmark-check"></i> Evidencias</a>
                                    <a class="btn btn-sm btn-light" href="#manual-reportes"><i class="bi bi-bar-chart"></i> Reportes</a>
                                    <a class="btn btn-sm btn-light" href="#manual-config"><i class="bi bi-gear"></i> Configuracion</a>
                                    <a class="btn btn-sm btn-light" href="#manual-inventario-pendiente"><i class="bi bi-exclamation-triangle"></i> Inventario pendiente</a>
                                    <a class="btn btn-sm btn-light" href="#manual-glosario"><i class="bi bi-book"></i> Glosario</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="row g-4">
                                <div class="col-xl-8">
                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-resumen">
                                        <h2 class="fw-bold fs-4 mb-4">Como se debe entender el modulo</h2>
                                        <p class="text-muted mb-4">Ventas y POS no es una sola pantalla. Es un conjunto de herramientas conectadas: el POS cobra, Caja controla el dinero, Pedidos administra apartados, Devoluciones corrige ventas con trazabilidad, Evidencias respalda movimientos delicados y Reportes permite revisar la operacion.</p>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="pos-manual-check p-3 h-100">
                                                    <div class="fw-bold">Regla principal</div>
                                                    <div class="text-muted fs-7">Toda venta real debe quedar con folio, operador, caja/turno, metodo de pago, ticket, inventario/kardex cuando aplica y trazabilidad.</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pos-manual-warning p-3 h-100">
                                                    <div class="fw-bold">Operacion controlada</div>
                                                    <div class="text-muted fs-7">Si algo no cuadra, no se borra ni se corrige a mano para esconderlo. Se registra la diferencia, evidencia o pendiente y se revisa.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-arranque">
                                        <h2 class="fw-bold fs-4 mb-4">Checklist para empezar a usar POS</h2>
                                        <p class="text-muted">Antes de vender de forma real, el POS necesita varias piezas listas. No todas se configuran en Ventas: algunas viven en Catalogo, Comercial/Listas de precios, Inventario, Postventa/Garantias, CRM, Seguridad y Configuracion POS.</p>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="pos-manual-check p-3 h-100">
                                                    <div class="fw-bold mb-2">1. Catalogo ERP</div>
                                                    <ul class="pos-manual-list text-muted mb-0">
                                                        <li>SKU activo, nombre claro, codigo de barras si aplica e imagen.</li>
                                                        <li>Producto marcado si controla inventario.</li>
                                                        <li>Unidad de venta: pieza, granel o ambas si el producto lo permite.</li>
                                                        <li>Regla de granel solo en productos realmente fraccionables.</li>
                                                        <li>Garantia/politica de postventa asociada cuando aplique.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pos-manual-check p-3 h-100">
                                                    <div class="fw-bold mb-2">2. Listas de precios</div>
                                                    <ul class="pos-manual-list text-muted mb-0">
                                                        <li>Precio publico vigente para POS.</li>
                                                        <li>Precios por presentacion o granel cuando aplique.</li>
                                                        <li>Listas especiales solo si ya estan asignadas a cliente, sucursal o segmento.</li>
                                                        <li>Precio manual/descuento solo con politica y autorizacion.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pos-manual-check p-3 h-100">
                                                    <div class="fw-bold mb-2">3. Inventario/Existencias</div>
                                                    <ul class="pos-manual-list text-muted mb-0">
                                                        <li>Almacen de la tienda ligado al POS.</li>
                                                        <li>Existencia disponible para venta normal.</li>
                                                        <li>Unidades cerradas disponibles si se venderan piezas fisicas completas.</li>
                                                        <li>Unidades abiertas disponibles solo para granel permitido.</li>
                                                        <li>Pendientes POS visibles y resolubles en Inventario/Existencias.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pos-manual-warning p-3 h-100">
                                                    <div class="fw-bold mb-2">4. Inventario pendiente/negativo</div>
                                                    <ul class="pos-manual-list text-muted mb-0">
                                                        <li>No conviene activarlo producto por producto de forma manual para todo el catalogo.</li>
                                                        <li>Lo correcto es una politica por canal POS, sucursal/almacen y alcance: global controlado, categoria, marca o SKU.</li>
                                                        <li>Debe tener limite de cantidad, limite de monto, motivo obligatorio, permiso supervisor y alerta a Inventario.</li>
                                                        <li>Ecommerce no debe usar unidades abiertas ni inventario pendiente como unidad cerrada.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pos-manual-check p-3 h-100">
                                                    <div class="fw-bold mb-2">5. Caja, turnos y pagos</div>
                                                    <ul class="pos-manual-list text-muted mb-0">
                                                        <li>Tienda, caja y terminal configuradas.</li>
                                                        <li>Usuarios asignados a la caja/sucursal correcta.</li>
                                                        <li>Metodos de pago activos: efectivo, tarjeta, transferencia, saldo cliente.</li>
                                                        <li>Turno abierto antes de cobrar.</li>
                                                        <li>Cierre con monto contado real, aunque haya diferencia.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pos-manual-check p-3 h-100">
                                                    <div class="fw-bold mb-2">6. CRM y clientes</div>
                                                    <ul class="pos-manual-list text-muted mb-0">
                                                        <li>Cliente mostrador disponible para ventas rapidas.</li>
                                                        <li>Alta o busqueda rapida de cliente cuando se quiera historial.</li>
                                                        <li>Saldos a favor ligados al cliente CRM.</li>
                                                        <li>Listas de precios o beneficios futuros asignados desde CRM/Comercial.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pos-manual-check p-3 h-100">
                                                    <div class="fw-bold mb-2">7. Garantias, devoluciones y evidencias</div>
                                                    <ul class="pos-manual-list text-muted mb-0">
                                                        <li>Garantia snapshot guardada al vender cuando el producto aplique.</li>
                                                        <li>Devoluciones con decision de inventario: disponible, cuarentena, merma o inspeccion.</li>
                                                        <li>Reembolsos/saldos a favor con decision financiera.</li>
                                                        <li>Evidencia obligatoria para reembolsos, gastos o diferencias sensibles.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="pos-manual-check p-3 h-100">
                                                    <div class="fw-bold mb-2">8. Reportes y seguimiento</div>
                                                    <ul class="pos-manual-list text-muted mb-0">
                                                        <li>Ventas por turno, caja, operador, metodo de pago y sucursal.</li>
                                                        <li>Diferencias de caja visibles.</li>
                                                        <li>Pendientes de inventario abiertos y resueltos.</li>
                                                        <li>Evidencias pendientes de revision.</li>
                                                        <li>Productos con venta frecuente sin existencia real para corregir inventario.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="pos-manual-danger p-3 mt-4">
                                            <div class="fw-bold">Regla para activar venta con existencia negativa</div>
                                            <div class="text-muted fs-7">No se debe activar como interruptor libre para todo sin limites. La configuracion recomendada es una politica general controlada para POS por sucursal/canal, con limites de cantidad e importe, permiso supervisor, motivo obligatorio y alerta a Inventario. Despues se pueden excluir productos delicados o limitar por categoria/SKU.</div>
                                        </div>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-pos">
                                        <h2 class="fw-bold fs-4 mb-4">POS: vender y cobrar en mostrador</h2>
                                        <p class="text-muted">Esta es la pantalla principal para atender clientes. Sirve para buscar productos, crear cuentas, agregar pagos, prevalidar y cobrar.</p>
                                        <h3 class="fw-bold fs-6 mt-5">Flujo normal</h3>
                                        <div class="pos-manual-step mb-3"><div class="pos-manual-icon"><i class="bi bi-door-open fs-4"></i></div><div><div class="fw-bold">1. Confirmar turno abierto</div><div class="text-muted">El POS debe estar ligado a una sucursal, almacen, caja y turno. Si no hay turno abierto, Cobrar se bloquea.</div></div></div>
                                        <div class="pos-manual-step mb-3"><div class="pos-manual-icon"><i class="bi bi-person-check fs-4"></i></div><div><div class="fw-bold">2. Confirmar operador</div><div class="text-muted">Revisa que aparezca tu usuario. Si otra persona esta logueada, no cobres con esa sesion.</div></div></div>
                                        <div class="pos-manual-step mb-3"><div class="pos-manual-icon"><i class="bi bi-search fs-4"></i></div><div><div class="fw-bold">3. Buscar o escanear</div><div class="text-muted">Usa el buscador, SKU, codigo o camara. Si hay coincidencias parecidas, confirma nombre, imagen y presentacion.</div></div></div>
                                        <div class="pos-manual-step mb-3"><div class="pos-manual-icon"><i class="bi bi-cart-check fs-4"></i></div><div><div class="fw-bold">4. Revisar carrito</div><div class="text-muted">Valida cantidad, precio, unidad, descuento, cliente y total antes de cobrar.</div></div></div>
                                        <div class="pos-manual-step mb-3"><div class="pos-manual-icon"><i class="bi bi-cash-stack fs-4"></i></div><div><div class="fw-bold">5. Capturar pago</div><div class="text-muted">Usa pagos rapidos o agrega pagos mixtos. Ejemplo: parte efectivo y parte transferencia.</div></div></div>
                                        <div class="pos-manual-step mb-3"><div class="pos-manual-icon"><i class="bi bi-shield-check fs-4"></i></div><div><div class="fw-bold">6. Prevalidar</div><div class="text-muted">Prevalidar revisa reglas pero no vende. Sirve para detectar falta de turno, pago incompleto o inventario insuficiente.</div></div></div>
                                        <div class="pos-manual-step"><div class="pos-manual-icon"><i class="bi bi-cash-coin fs-4"></i></div><div><div class="fw-bold">7. Cobrar</div><div class="text-muted">Cobrar es real: registra venta, caja, ticket, garantia snapshot y kardex/inventario cuando corresponde.</div></div></div>
                                        <h3 class="fw-bold fs-6 mt-5">Botones del POS</h3>
                                        <ul class="pos-manual-list text-muted">
                                            <li><strong>Prevalidar:</strong> revisa si se puede cobrar. Prevalidar no vende ni descuenta inventario.</li>
                                            <li><strong>Ticket:</strong> antes de cobrar es vista previa; despues de venta muestra ticket real.</li>
                                            <li><strong>Cliente:</strong> selecciona cliente CRM para historial, saldo, listas de precios y condiciones futuras.</li>
                                            <li><strong>Autorizar:</strong> precio manual o descuento con motivo y folio de autorizacion.</li>
                                            <li><strong>Atenciones:</strong> cuentas creadas por otro operador para tomarlas y cobrarlas.</li>
                                            <li><strong>Mas:</strong> acciones avanzadas como simulaciones o inventario pendiente. No son flujo normal de cajero.</li>
                                            <li><strong>Cobrar:</strong> confirma la venta real.</li>
                                        </ul>
                                        <h3 class="fw-bold fs-6 mt-5">Atajos visibles</h3>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="pos-manual-kbd">F2 / Ctrl+K buscar</span>
                                            <span class="pos-manual-kbd">F3 camara</span>
                                            <span class="pos-manual-kbd">Alt+1 efectivo</span>
                                            <span class="pos-manual-kbd">Alt+2 tarjeta</span>
                                            <span class="pos-manual-kbd">Alt+3 transferencia</span>
                                            <span class="pos-manual-kbd">F6 monto pago</span>
                                            <span class="pos-manual-kbd">F9 prevalidar</span>
                                            <span class="pos-manual-kbd">Ctrl+Enter cobrar</span>
                                        </div>
                                        <div class="pos-manual-danger p-3 mt-5">
                                            <div class="fw-bold">No hacer en POS sin autorizacion</div>
                                            <div class="text-muted fs-7">No vender con inventario pendiente como rutina, no cambiar precios sin motivo, no usar devoluciones reales desde POS normal y no cobrar en una caja/sucursal que no corresponda.</div>
                                        </div>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-tablero">
                                        <h2 class="fw-bold fs-4 mb-4">Tablero de ventas: consultar y auditar ventas</h2>
                                        <p class="text-muted">Sirve para revisar ventas ya registradas. No es para capturar una venta nueva; esa operacion se hace en POS.</p>
                                        <h3 class="fw-bold fs-6">Que revisar</h3>
                                        <ul class="pos-manual-list text-muted">
                                            <li>Folio POS, fecha y hora.</li>
                                            <li>Sucursal, caja, turno y operador que cobro.</li>
                                            <li>Cliente, telefono o cliente CRM si aplica.</li>
                                            <li>Total, pagos, cambio y saldo.</li>
                                            <li>Detalle de productos, cantidades, precios, descuentos y garantias.</li>
                                            <li>Ticket formal y trazabilidad de inventario/kardex.</li>
                                        </ul>
                                        <h3 class="fw-bold fs-6 mt-4">Uso recomendado</h3>
                                        <ol class="text-muted">
                                            <li>Filtra por fecha, folio, operador o sucursal.</li>
                                            <li>Abre el detalle de una venta cuando haya duda.</li>
                                            <li>Verifica el ticket si el cliente lo solicita.</li>
                                            <li>Si requiere devolucion, pasa al modulo Devoluciones; no edites la venta original.</li>
                                        </ol>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-checador">
                                        <h2 class="fw-bold fs-4 mb-4">Checador de precios: consulta sin vender</h2>
                                        <p class="text-muted">El checador es read-only. Sirve para responder rapido a un cliente cuanto cuesta un producto y si hay disponibilidad, sin abrir carrito ni afectar caja.</p>
                                        <ul class="pos-manual-list text-muted">
                                            <li>Busca por nombre, SKU o codigo de barras.</li>
                                            <li>Puede usar camara para leer codigo.</li>
                                            <li>Muestra precio y disponibilidad segun catalogo/inventario configurado.</li>
                                            <li>No crea venta, no reserva, no descuenta inventario y no abre caja.</li>
                                        </ul>
                                        <div class="pos-manual-check p-3 mt-4">
                                            <div class="fw-bold">Cuando usarlo</div>
                                            <div class="text-muted fs-7">Cuando alguien solo pregunta precio, cuando quieres validar un codigo de barras o cuando un empleado necesita consultar sin permiso de cobro.</div>
                                        </div>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-pedidos">
                                        <h2 class="fw-bold fs-4 mb-4">Pedidos y apartados</h2>
                                        <p class="text-muted">Pedidos/apartados se usan cuando el cliente no liquida o no se entrega todo de inmediato. Separan la promesa al cliente de la venta inmediata.</p>
                                        <h3 class="fw-bold fs-6">Flujo de apartado</h3>
                                        <ol class="text-muted">
                                            <li>Selecciona cliente o captura datos minimos.</li>
                                            <li>Agrega productos y valida disponibilidad/reserva segun politica.</li>
                                            <li>Captura anticipo si aplica.</li>
                                            <li>Registra abonos posteriores con referencia.</li>
                                            <li>Cuando quede liquidado y listo, entrega el apartado.</li>
                                            <li>Si se cancela, registra decision financiera: saldo a favor, reembolso o politica definida.</li>
                                        </ol>
                                        <h3 class="fw-bold fs-6 mt-4">Compromiso</h3>
                                        <p class="text-muted">La fecha compromiso solo aplica a pedidos o apartados porque hay una promesa futura: entrega, liquidacion o seguimiento. En venta normal no se usa porque la entrega ocurre al cobrar.</p>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-devoluciones">
                                        <h2 class="fw-bold fs-4 mb-4">Devoluciones y reversas</h2>
                                        <p class="text-muted">Devoluciones no deben improvisarse. Una devolucion puede afectar caja, saldo del cliente, inventario disponible, cuarentena y garantias.</p>
                                        <h3 class="fw-bold fs-6">Flujo recomendado</h3>
                                        <ol class="text-muted">
                                            <li>Localiza la venta por folio o detalle.</li>
                                            <li>Selecciona la partida exacta y cantidad a devolver.</li>
                                            <li>Define el motivo.</li>
                                            <li>Decide inventario: regresar a disponible, cuarentena, merma o pendiente de inspeccion.</li>
                                            <li>Decide dinero: reembolso caja, saldo a favor CRM, mixto o sin devolucion monetaria segun politica.</li>
                                            <li>Si sale dinero de caja, registra evidencia.</li>
                                            <li>Si el producto queda en cuarentena, Inventario/Almacen debe resolver destino final.</li>
                                        </ol>
                                        <div class="pos-manual-danger p-3 mt-4">
                                            <div class="fw-bold">Regla importante</div>
                                            <div class="text-muted fs-7">Nunca borres una venta para simular una devolucion. La venta original debe conservarse y la reversa debe quedar trazada.</div>
                                        </div>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-caja">
                                        <h2 class="fw-bold fs-4 mb-4">Caja y turnos: abrir y cerrar caja</h2>
                                        <p class="text-muted">Caja y turnos controla el dinero fisico y operativo. El POS cobra dentro de un turno; si no hay turno abierto, no debe cobrar.</p>
                                        <h3 class="fw-bold fs-6">Abrir turno</h3>
                                        <ol class="text-muted">
                                            <li>Entra a Ventas y POS > Caja y turnos.</li>
                                            <li>Confirma sucursal, almacen, caja y terminal.</li>
                                            <li>Cuenta efectivo inicial real.</li>
                                            <li>Captura monto inicial y observacion si aplica.</li>
                                            <li>Prevalida apertura.</li>
                                            <li>Confirma apertura real.</li>
                                            <li>Desde ese momento el POS ya puede cobrar en ese turno.</li>
                                        </ol>
                                        <h3 class="fw-bold fs-6 mt-4">Cerrar turno</h3>
                                        <ol class="text-muted">
                                            <li>Cuenta el efectivo real en caja.</li>
                                            <li>Revisa el corte esperado.</li>
                                            <li>Captura monto contado real, aunque no cuadre.</li>
                                            <li>Agrega observaciones claras si hay diferencia.</li>
                                            <li>Prevalida cierre.</li>
                                            <li>Confirma cierre real.</li>
                                            <li>Revisa reportes/diferencias despues del cierre.</li>
                                        </ol>
                                        <div class="pos-manual-warning p-3 mt-4">
                                            <div class="fw-bold">Diferencias de caja</div>
                                            <div class="text-muted fs-7">La caja puede cerrar con sobrante o faltante. Esa diferencia es informacion valiosa para control interno; no se debe manipular el cierre para dejarlo en cero.</div>
                                        </div>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-movimientos">
                                        <h2 class="fw-bold fs-4 mb-4">Movimientos caja</h2>
                                        <p class="text-muted">Muestra entradas y salidas ligadas a caja/turno: ventas, anticipos, abonos, gastos, reembolsos y otros movimientos autorizados.</p>
                                        <ul class="pos-manual-list text-muted">
                                            <li>Usalo para explicar por que cambio el efectivo esperado.</li>
                                            <li>Revisa folio, tipo de movimiento, responsable, monto y referencia.</li>
                                            <li>Un gasto directo de caja debe tener motivo y responsable.</li>
                                            <li>Un reembolso debe estar ligado a devolucion o decision financiera.</li>
                                        </ul>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-evidencias">
                                        <h2 class="fw-bold fs-4 mb-4">Evidencias caja</h2>
                                        <p class="text-muted">Evidencias respaldan movimientos sensibles. No sustituyen la venta, devolucion o movimiento de caja; los complementan.</p>
                                        <h3 class="fw-bold fs-6">Ejemplos de evidencia</h3>
                                        <ul class="pos-manual-list text-muted">
                                            <li>Ticket firmado por cliente para reembolso.</li>
                                            <li>Referencia externa de transferencia.</li>
                                            <li>Comprobante de gasto de caja.</li>
                                            <li>Correccion o reemplazo de evidencia.</li>
                                        </ul>
                                        <h3 class="fw-bold fs-6 mt-4">Revision</h3>
                                        <ol class="text-muted">
                                            <li>Consulta evidencias pendientes.</li>
                                            <li>Revisa que correspondan al movimiento correcto.</li>
                                            <li>Aprueba, rechaza o solicita correccion.</li>
                                            <li>Si hay correccion, conserva historial; no se borra la evidencia previa.</li>
                                        </ol>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-reportes">
                                        <h2 class="fw-bold fs-4 mb-4">Reportes POS</h2>
                                        <p class="text-muted">Reportes sirve para revisar la operacion. No debe mover caja ni inventario. Su trabajo es mostrar informacion para tomar decisiones.</p>
                                        <h3 class="fw-bold fs-6">Que se debe poder revisar</h3>
                                        <ul class="pos-manual-list text-muted">
                                            <li>Ventas por fecha, sucursal, caja, turno, operador y metodo de pago.</li>
                                            <li>Totales cobrados, efectivo esperado, monto contado y diferencias.</li>
                                            <li>Productos vendidos, cantidades, descuentos y precios manuales.</li>
                                            <li>Pedidos/apartados, anticipos, abonos y entregas.</li>
                                            <li>Devoluciones, reembolsos, saldos a favor y cuarentena.</li>
                                            <li>Pendientes de inventario generados por ventas autorizadas con faltante.</li>
                                            <li>Evidencias pendientes o rechazadas.</li>
                                        </ul>
                                        <h3 class="fw-bold fs-6 mt-4">Como usar un reporte</h3>
                                        <ol class="text-muted">
                                            <li>Elige rango de fechas.</li>
                                            <li>Filtra por sucursal/caja/turno si buscas una operacion especifica.</li>
                                            <li>Compara esperado contra contado si revisas caja.</li>
                                            <li>Abre detalle cuando veas diferencias, descuentos o reembolsos.</li>
                                            <li>Exporta o anota evidencia si necesitas seguimiento administrativo.</li>
                                        </ol>
                                        <div class="pos-manual-check p-3 mt-4">
                                            <div class="fw-bold">Sobre crear reportes</div>
                                            <div class="text-muted fs-7">En esta etapa los reportes son vistas operativas ya preparadas. Si necesitas un reporte nuevo, se define como requerimiento: objetivo, filtros, columnas, totales, permisos y fuente de datos.</div>
                                        </div>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-config">
                                        <h2 class="fw-bold fs-4 mb-4">Configuracion POS</h2>
                                        <p class="text-muted">Configuracion define como se conecta el POS con la tienda real. El cajero no deberia elegir libremente donde vende; debe entrar ya con la configuracion que le corresponde.</p>
                                        <h3 class="fw-bold fs-6">Elementos principales</h3>
                                        <ul class="pos-manual-list text-muted">
                                            <li><strong>Sucursal/tienda:</strong> punto fisico de venta.</li>
                                            <li><strong>Almacen:</strong> inventario que se descuenta cuando se vende en esa tienda.</li>
                                            <li><strong>Caja:</strong> contenedor operativo del dinero.</li>
                                            <li><strong>Terminal:</strong> equipo o punto desde donde se opera.</li>
                                            <li><strong>Usuario asignado:</strong> operadores permitidos para esa caja/terminal/sucursal.</li>
                                            <li><strong>Politicas:</strong> reglas como descuentos, precio manual, inventario pendiente o permisos especiales.</li>
                                        </ul>
                                        <h3 class="fw-bold fs-6 mt-4">Buenas practicas</h3>
                                        <ul class="pos-manual-list text-muted">
                                            <li>Configura antes de abrir turno.</li>
                                            <li>No cambies caja o almacen durante una venta.</li>
                                            <li>Si un usuario cobra, el folio debe conservar ese usuario como operador.</li>
                                            <li>Si varios operadores usan la misma caja/turno, cada cobro debe quedar con el usuario que lo hizo.</li>
                                            <li>Los cambios de configuracion deben quedar auditados.</li>
                                        </ul>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-inventario-pendiente">
                                        <h2 class="fw-bold fs-4 mb-4">Inventario pendiente POS</h2>
                                        <p class="text-muted">Inventario pendiente se usa cuando se autoriza una venta aunque el ERP no tenga existencia suficiente. El sistema permite vender solo bajo politica y genera una alerta para Inventario/Existencias.</p>
                                        <h3 class="fw-bold fs-6">Que pasa al vender con pendiente</h3>
                                        <ol class="text-muted">
                                            <li>POS cobra la venta con autorizacion.</li>
                                            <li>Si habia parte disponible, esa parte sale con kardex normal.</li>
                                            <li>La parte faltante crea un expediente `PINV`.</li>
                                            <li>Inventario recibe la alerta y debe hacer conteo fisico.</li>
                                            <li>Al resolver, el sistema regulariza inventario y reconoce la salida de la venta pendiente.</li>
                                        </ol>
                                        <h3 class="fw-bold fs-6 mt-4">Como entender la formula de resolucion</h3>
                                        <p class="text-muted">La cantidad fisica que capturas es lo que contaste ahora, despues de que la venta ya ocurrio. Por eso el sistema suma la cantidad vendida pendiente para reconstruir cuantas piezas debian existir antes de registrar la salida.</p>
                                        <div class="pos-manual-check p-3">
                                            <div class="fw-bold">Ejemplo</div>
                                            <div class="text-muted fs-7">Si el pendiente vendido es `1` y cuentas `10` piezas fisicas actuales, el sistema calcula `10 + 1 = 11`. Propone ajustar a 11 antes de salida, registrar la salida de 1 por la venta pendiente y dejar disponible final 10.</div>
                                        </div>
                                        <h3 class="fw-bold fs-6 mt-4">Previsualizar vs resolver</h3>
                                        <ul class="pos-manual-list text-muted">
                                            <li><strong>Previsualizar resolucion:</strong> no mueve inventario; solo muestra la propuesta y la formula.</li>
                                            <li><strong>Resolver:</strong> accion real; puede crear kardex, ajustar inventario, cerrar el pendiente y cerrar la notificacion.</li>
                                        </ul>
                                        <h3 class="fw-bold fs-6 mt-4">Token, respaldo y confirmacion</h3>
                                        <ul class="pos-manual-list text-muted">
                                            <li><strong>Token:</strong> escribe `INVENTARIO_POS_PENDIENTE_RESOLVER_REAL`. Es un candado tecnico para esta accion.</li>
                                            <li><strong>Respaldo:</strong> escribe la referencia del respaldo vigente autorizado. En UAT se puede usar `UAT POS vigente`.</li>
                                            <li><strong>Confirmacion:</strong> escribe exactamente `RESOLVER PENDIENTE`. Evita cierres por accidente.</li>
                                        </ul>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4 pos-manual-section" id="manual-glosario">
                                        <h2 class="fw-bold fs-4 mb-4">Glosario operativo</h2>
                                        <ul class="pos-manual-list text-muted">
                                            <li><strong>Prevalidar:</strong> revisar si una accion se puede ejecutar. No escribe datos.</li>
                                            <li><strong>Cobrar:</strong> confirmar venta real y mover caja/inventario segun reglas.</li>
                                            <li><strong>Turno:</strong> periodo de operacion de caja desde apertura hasta cierre.</li>
                                            <li><strong>Caja:</strong> unidad de control del dinero.</li>
                                            <li><strong>Terminal:</strong> punto/equipo configurado para operar POS.</li>
                                            <li><strong>Stock:</strong> disponibilidad del SKU en la sucursal/almacen.</li>
                                            <li><strong>Pieza:</strong> venta de unidad cerrada completa.</li>
                                            <li><strong>Granel:</strong> venta por cantidad fraccionaria cuando el SKU lo permite.</li>
                                            <li><strong>Inventario pendiente:</strong> venta autorizada con faltante controlado que genera tarea para Inventario/Existencias.</li>
                                            <li><strong>Cuarentena:</strong> inventario separado que no debe venderse hasta inspeccion/destino final.</li>
                                            <li><strong>Saldo a favor:</strong> dinero reconocido al cliente para uso posterior.</li>
                                            <li><strong>Evidencia:</strong> comprobante o respaldo de un movimiento sensible.</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="col-xl-4">
                                    <div class="pos-manual-card p-5 mb-4">
                                        <h2 class="fw-bold fs-4 mb-4">Ruta diaria recomendada</h2>
                                        <ol class="text-muted mb-0">
                                            <li>Entrar con usuario propio.</li>
                                            <li>Abrir turno en Caja y turnos.</li>
                                            <li>Vender desde POS.</li>
                                            <li>Usar Tablero si necesitas consultar venta.</li>
                                            <li>Registrar devoluciones solo si estan autorizadas.</li>
                                            <li>Subir/revisar evidencias si hubo reembolso, gasto o diferencia.</li>
                                            <li>Cerrar turno con monto contado real.</li>
                                            <li>Revisar Reportes POS.</li>
                                        </ol>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4">
                                        <h2 class="fw-bold fs-4 mb-4">Atajos POS</h2>
                                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Buscar</span><strong>F2 / Ctrl+K</strong></div>
                                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Camara</span><strong>F3</strong></div>
                                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Efectivo</span><strong>Alt+1</strong></div>
                                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Tarjeta</span><strong>Alt+2</strong></div>
                                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Transferencia</span><strong>Alt+3</strong></div>
                                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Monto pago</span><strong>F6</strong></div>
                                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Prevalidar</span><strong>F9</strong></div>
                                        <div class="d-flex justify-content-between py-2"><span>Cobrar</span><strong>Ctrl+Enter</strong></div>
                                    </div>

                                    <div class="pos-manual-card p-5 mb-4">
                                        <h2 class="fw-bold fs-4 mb-4">No hacer</h2>
                                        <ul class="pos-manual-list text-muted mb-0">
                                            <li>No cobrar con usuario de otra persona.</li>
                                            <li>No cambiar sucursal/caja para vender donde no corresponde.</li>
                                            <li>No borrar ventas para corregir errores.</li>
                                            <li>No usar inventario pendiente como rutina.</li>
                                            <li>No ajustar caja para que cuadre artificialmente.</li>
                                            <li>No devolver dinero sin evidencia cuando la politica lo requiera.</li>
                                            <li>No regresar producto a disponible si debe ir a cuarentena.</li>
                                        </ul>
                                    </div>

                                    <div class="alert alert-light-info border border-info">
                                        <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> Compromiso</div>
                                        <div class="fs-7">Solo aplica a pedidos o apartados. En venta normal no se captura porque el producto se entrega al cobrar.</div>
                                    </div>
                                    <div class="alert alert-light-warning border border-warning">
                                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Stock / pieza / granel</div>
                                        <div class="fs-7">Stock es lo disponible en la sucursal. Pieza vende unidades cerradas. Granel solo aparece cuando el SKU permite cantidad fraccionaria.</div>
                                    </div>
                                    <div class="alert alert-light-success border border-success">
                                        <div class="fw-bold mb-1"><i class="bi bi-check-circle me-1"></i> Caja con diferencia</div>
                                        <div class="fs-7">Se puede cerrar aunque no cuadre. La diferencia se revisa en reportes y no debe esconderse.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?= include_once '../app/vistas/includes/footer/footer.php'; ?>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
</body>
</html>
