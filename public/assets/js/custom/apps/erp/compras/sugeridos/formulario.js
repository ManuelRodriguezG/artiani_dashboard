"use strict";
(function () {
    var items = [];
    var candidatos = [];
    var schemaPendiente = false;
    var ocultarCeros = false;
    var puedeCrear = false;
    var puedeEditar = false;
    var modoLectura = false;
    var timer = null;
    var scanStream = null;
    var scanActivo = false;
    var scanTorchActivo = false;
    var scanCamaras = [];
    var scanCamaraSeleccionada = "";

    function esc(valor) {
        var d = document.createElement("div");
        d.textContent = valor == null ? "" : valor;
        return d.innerHTML;
    }

    function money(valor) {
        return "$" + Number(valor || 0).toFixed(2);
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-21
     * Proposito: separar resultados de busqueda contra partidas agregadas al sugerido.
     * Impacto: UX de Compras/Sugerido; el usuario decide que codigos del proveedor agregar.
     */
    function normalizarItemProveedor(x) {
        return {
            id_sku_erp: Number(x.id_sku_erp || x.id_sku || 0),
            id_sku_proveedor: Number(x.id_sku_proveedor || 0),
            sku_erp: x.sku_erp || "",
            sku_proveedor: x.sku_proveedor || x.sku_erp || "",
            nombre_erp: x.nombre_erp || "",
            nombre_proveedor: x.nombre_proveedor || x.nombre_erp || "",
            unidad_compra: x.unidad_compra || "",
            factor_conversion: Number(x.factor_conversion || 1),
            cantidad_minima: Number(x.cantidad_minima || 1),
            stock_minimo: Number(x.stock_minimo || 0),
            stock_maximo: x.stock_maximo === null ? null : Number(x.stock_maximo || 0),
            punto_reorden: Number(x.punto_reorden || 0),
            existencia_revisada: Number(x.existencia_revisada || 0),
            cantidad_sugerida: Number(x.cantidad_sugerida || 0),
            cantidad_solicitar: Number(x.cantidad_solicitar || x.cantidad_sugerida || 0),
            costo_estimado: Number(x.costo_estimado || x.costo_ultimo || 0),
            observaciones: ""
        };
    }

    function renderResultados() {
        var wrap = document.getElementById("sugerido_resultados_wrap");
        var body = document.getElementById("sugerido_resultados");
        if (!wrap || !body) { return; }
        wrap.classList.toggle("d-none", candidatos.length <= 0);
        body.innerHTML = candidatos.map(function (x, i) {
            var yaAgregado = items.some(function (item) {
                return Number(item.id_sku_proveedor || 0) === Number(x.id_sku_proveedor || 0);
            });
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(x.sku_proveedor || x.sku_erp) + "</div><div class=\"text-muted fs-8\">SKU ERP: " + esc(x.sku_erp || "-") + "</div></td>" +
                "<td>" + esc(x.nombre_proveedor || x.nombre_erp) + "<div class=\"text-muted fs-8\">" + esc(x.unidad_compra || "") + " | factor " + Number(x.factor_conversion || 1).toFixed(6) + "</div></td>" +
                "<td class=\"text-end fw-bold\">" + money(x.costo_estimado) + "</td>" +
                "<td class=\"text-end\"><button type=\"button\" class=\"btn btn-sm " + (yaAgregado ? "btn-light" : "btn-light-primary") + "\" data-sugerido-agregar=\"" + i + "\"" + (yaAgregado || modoLectura ? " disabled" : "") + ">" + (yaAgregado ? "Agregado" : "Agregar") + "</button></td>" +
                "</tr>";
        }).join("");
    }

    function agregarCandidato(indice) {
        var candidato = candidatos[Number(indice)];
        if (!candidato || modoLectura) { return; }
        var existe = items.some(function (item) {
            return Number(item.id_sku_proveedor || 0) === Number(candidato.id_sku_proveedor || 0);
        });
        if (existe) {
            renderResultados();
            return;
        }
        items.push(Object.assign({}, candidato));
        recalcular(false);
        renderResultados();
    }

    function post(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"},
            body: new URLSearchParams(data),
            credentials: "same-origin"
        }).then(function (r) { return r.json(); });
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-20
     * Proposito: calcular sugerido de compra desde existencia revisada sin leer ni modificar inventario.
     * Impacto: Compras/Sugerido; calculo visual replicado por backend al guardar.
     */
    function calcularCantidadSugerida(item) {
        var existencia = Math.max(0, Number(item.existencia_revisada || 0));
        var minimo = Math.max(0, Number(item.stock_minimo || 0));
        var maximo = item.stock_maximo === null || item.stock_maximo === "" ? null : Math.max(0, Number(item.stock_maximo || 0));
        var reorden = Math.max(0, Number(item.punto_reorden || 0));
        var factor = Math.max(0.000001, Number(item.factor_conversion || 1));
        var minimaCompra = Math.max(0, Number(item.cantidad_minima || 1));
        var necesidadBase = 0;

        if (maximo !== null && maximo > 0) {
            necesidadBase = Math.max(0, maximo - existencia);
        } else if (reorden > 0) {
            necesidadBase = Math.max(0, reorden - existencia);
        } else if (minimo > 0) {
            necesidadBase = Math.max(0, minimo - existencia);
        }
        if (necesidadBase <= 0) {
            return 0;
        }
        var cantidadCompra = Math.ceil((necesidadBase / factor) * 1000000) / 1000000;
        if (minimaCompra > 0 && cantidadCompra > 0 && cantidadCompra < minimaCompra) {
            cantidadCompra = minimaCompra;
        }
        return Number(cantidadCompra.toFixed(6));
    }

    function cargarCatalogos() {
        return fetch("/compra/sugeridos_catalogos_erp", {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) { throw new Error(r.mensaje || "No se pudieron cargar proveedores"); }
                var proveedores = r.depurar && Array.isArray(r.depurar.proveedores) ? r.depurar.proveedores : [];
                document.getElementById("sugerido_proveedor").innerHTML = "<option value=\"\">Seleccionar</option>" + proveedores.map(function (x) {
                    return "<option value=\"" + esc(x.id_proveedor) + "\">" + esc(x.proveedor) + "</option>";
                }).join("");
            });
    }

    function cargarSugerido() {
        var id = Number(document.getElementById("sugerido_id").value || 0);
        if (!id) {
            return fetch("/compra/sugerido_consultar_erp?id_sugerido_compra=0", {credentials: "same-origin"})
                .then(function (r) { return r.json(); })
                .then(function (r) {
                    schemaPendiente = !!(r.depurar && Number(r.depurar.schema_pendiente || 0) === 1);
                    document.getElementById("sugerido_alerta_schema").classList.toggle("d-none", !schemaPendiente);
                    render();
                });
        }
        return fetch("/compra/sugerido_consultar_erp?id_sugerido_compra=" + id, {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                schemaPendiente = !!(r.depurar && Number(r.depurar.schema_pendiente || 0) === 1);
                document.getElementById("sugerido_alerta_schema").classList.toggle("d-none", !schemaPendiente);
                var sugerido = r.depurar.sugerido || null;
                if (!sugerido) {
                    render();
                    return;
                }
                document.getElementById("sugerido_titulo").textContent = sugerido.folio || "Sugerido de compra";
                document.getElementById("sugerido_estado_texto").textContent = sugerido.estatus || "borrador";
                document.getElementById("sugerido_proveedor").value = sugerido.id_proveedor || "";
                document.getElementById("sugerido_observaciones").value = sugerido.observaciones || "";
                items = (r.depurar.detalle || []).map(function (x) {
                    x.id_sku_erp = Number(x.id_sku_erp || 0);
                    x.id_sku_proveedor = Number(x.id_sku_proveedor || 0);
                    x.factor_conversion = Number(x.factor_conversion || 1);
                    x.cantidad_minima = Number(x.cantidad_minima || 1);
                    x.stock_minimo = Number(x.stock_minimo || 0);
                    x.stock_maximo = x.stock_maximo === null ? null : Number(x.stock_maximo || 0);
                    x.punto_reorden = Number(x.punto_reorden || 0);
                    x.existencia_revisada = Number(x.existencia_revisada || 0);
                    x.cantidad_sugerida = Number(x.cantidad_sugerida || 0);
                    x.cantidad_solicitar = Number(x.cantidad_solicitar || 0);
                    x.costo_estimado = Number(x.costo_estimado || 0);
                    return x;
                });
                render();
            });
    }

    function consultarProveedor() {
        var proveedor = document.getElementById("sugerido_proveedor").value;
        var q = document.getElementById("sugerido_buscar").value.trim();
        if (!proveedor) {
            Swal.fire({text: "Selecciona un proveedor para buscar productos.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        if (q.length < 2) {
            candidatos = [];
            renderResultados();
            document.getElementById("sugerido_resumen").textContent = "Escribe al menos dos caracteres del SKU o producto del proveedor.";
            return;
        }
        document.getElementById("sugerido_resumen").textContent = "Buscando productos del proveedor...";
        fetch("/compra/sugeridos_productos_proveedor_erp?" + new URLSearchParams({id_proveedor: proveedor, q: q, limite: 80}), {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                candidatos = (r.depurar.items || []).map(normalizarItemProveedor);
                renderResultados();
                document.getElementById("sugerido_resumen").textContent = candidatos.length > 0
                    ? candidatos.length + " resultado(s). Agrega solo los productos que quieres incluir en el sugerido."
                    : "No se encontraron productos del proveedor con esa busqueda.";
            }).catch(function (e) {
                candidatos = [];
                renderResultados();
                Swal.fire({text: e.message || "No se pudieron consultar productos", icon: "error", confirmButtonText: "Aceptar"});
                document.getElementById("sugerido_resumen").textContent = "No se pudieron consultar productos.";
            });
    }

    function recalcular(reemplazarCantidadFinal) {
        items.forEach(function (item) {
            item.cantidad_sugerida = calcularCantidadSugerida(item);
            if (reemplazarCantidadFinal || Number(item.cantidad_solicitar || 0) <= 0) {
                item.cantidad_solicitar = item.cantidad_sugerida;
            }
        });
        render();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-27
     * Proposito: mostrar el valor aproximado levantado en mini inventarios sin afectar inventario real.
     * Impacto: UX Compras/Sugerido; calcula solo en pantalla con existencia revisada x costo estimado.
     */
    function actualizarResumen() {
        var totalPiezas = items.reduce(function (t, x) { return t + Number(x.cantidad_solicitar || 0); }, 0);
        var total = items.reduce(function (t, x) { return t + Number(x.cantidad_solicitar || 0) * Number(x.costo_estimado || 0); }, 0);
        var totalExistenciaRevisada = items.reduce(function (t, x) { return t + Number(x.existencia_revisada || 0); }, 0);
        var totalInventarioEstimado = items.reduce(function (t, x) {
            return t + Number(x.existencia_revisada || 0) * Number(x.costo_estimado || 0);
        }, 0);
        document.getElementById("sugerido_total_piezas").textContent = totalPiezas.toFixed(6);
        document.getElementById("sugerido_total").textContent = money(total);
        document.getElementById("sugerido_total_existencia_revisada").textContent = totalExistenciaRevisada.toFixed(6);
        document.getElementById("sugerido_total_inventario_estimado").textContent = money(totalInventarioEstimado);
        document.getElementById("sugerido_resumen").textContent = items.length + " productos consultados; " +
            items.filter(function (x) { return Number(x.cantidad_solicitar || 0) > 0; }).length + " con cantidad a solicitar.";
    }

    function actualizarSugeridoVisual(indice) {
        var nodo = document.querySelector("[data-sugerido-sugerida=\"" + indice + "\"]");
        if (nodo) {
            nodo.textContent = Number(items[indice].cantidad_sugerida || 0).toFixed(6);
        }
        actualizarResumen();
    }

    function render() {
        var visibles = items.filter(function (x) { return !ocultarCeros || Number(x.cantidad_solicitar || 0) > 0 || Number(x.cantidad_sugerida || 0) > 0; });
        var readonly = modoLectura ? " readonly disabled" : "";
        document.getElementById("sugerido_items").innerHTML = visibles.map(function (x) {
            var i = items.indexOf(x);
            var maximo = x.stock_maximo === null || x.stock_maximo === "" ? "-" : Number(x.stock_maximo || 0).toFixed(2);
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(x.sku_proveedor || x.sku_erp) + "</div><div class=\"text-muted fs-8\">SKU ERP: " + esc(x.sku_erp || "-") + "</div></td>" +
                "<td>" + esc(x.nombre_proveedor || x.nombre_erp) + "<div class=\"text-muted fs-8\">" + esc(x.unidad_compra || "") + " | factor " + Number(x.factor_conversion || 1).toFixed(6) + "</div></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end sugerido-cantidad-input\" inputmode=\"decimal\" data-sugerido-minimo=\"" + i + "\" value=\"" + Number(x.stock_minimo || 0) + "\"" + readonly + "></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end sugerido-cantidad-input\" inputmode=\"decimal\" data-sugerido-maximo=\"" + i + "\" value=\"" + (x.stock_maximo === null || x.stock_maximo === "" ? "" : Number(x.stock_maximo || 0)) + "\"" + readonly + "></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end sugerido-cantidad-input\" inputmode=\"decimal\" data-sugerido-reorden=\"" + i + "\" value=\"" + Number(x.punto_reorden || 0) + "\"" + readonly + "></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end sugerido-cantidad-input\" inputmode=\"decimal\" data-sugerido-existencia=\"" + i + "\" value=\"" + Number(x.existencia_revisada || 0) + "\"" + readonly + "></td>" +
                "<td class=\"text-end fw-bold\"><span class=\"sugerido-cantidad-readonly\" data-sugerido-sugerida=\"" + i + "\">" + Number(x.cantidad_sugerida || 0).toFixed(6) + "</span></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end sugerido-cantidad-final-input\" inputmode=\"decimal\" data-sugerido-cantidad=\"" + i + "\" value=\"" + Number(x.cantidad_solicitar || 0) + "\"" + readonly + "></td>" +
                "<td class=\"text-end\">" + money(x.costo_estimado) + "</td>" +
                "<td><input class=\"form-control form-control-sm\" data-sugerido-obs=\"" + i + "\" value=\"" + esc(x.observaciones || "") + "\"" + readonly + "></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"10\" class=\"text-center text-muted py-8\">Busca productos del proveedor y agrega solo los que quieres revisar.</td></tr>";

        actualizarResumen();
    }


    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-27
     * Proposito: buscar y agregar por codigo escaneado dentro del proveedor seleccionado.
     * Impacto: UX Compras/Sugerido; reutiliza endpoint de proveedor y no muestra productos fuera de la relacion activa.
     */
    function buscarCodigoEscaneadoSugerido(valor) {
        valor = String(valor || "").trim();
        var proveedor = document.getElementById("sugerido_proveedor").value;
        if (!valor || !proveedor) { return; }
        document.getElementById("sugerido_buscar").value = valor;
        document.getElementById("sugerido_resumen").textContent = "Codigo leido: " + valor + ". Buscando en proveedor...";
        fetch("/compra/sugeridos_productos_proveedor_erp?" + new URLSearchParams({id_proveedor: proveedor, q: valor, limite: 8}), {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                candidatos = (r.depurar.items || []).map(normalizarItemProveedor);
                renderResultados();
                if (candidatos.length === 1) {
                    agregarCandidato(0);
                    document.getElementById("sugerido_resumen").textContent = "Codigo leido. Producto agregado al sugerido.";
                } else if (candidatos.length > 1) {
                    document.getElementById("sugerido_resumen").textContent = "Codigo leido con varias coincidencias. Elige el producto correcto.";
                } else {
                    document.getElementById("sugerido_resumen").textContent = "Codigo leido sin coincidencias en este proveedor.";
                }
            }).catch(function (e) {
                Swal.fire({text: e.message || "No se pudo buscar el codigo", icon: "error", confirmButtonText: "Aceptar"});
            });
    }

    function prepararCamarasSugerido() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) { return Promise.resolve([]); }
        return navigator.mediaDevices.enumerateDevices().then(function (devices) {
            var videoDevices = devices.filter(function (device) { return device.kind === "videoinput"; });
            if (videoDevices.some(function (device) { return device.label; })) { return videoDevices; }
            return navigator.mediaDevices.getUserMedia({video: true, audio: false}).then(function (tmpStream) {
                tmpStream.getTracks().forEach(function (track) { track.stop(); });
                return navigator.mediaDevices.enumerateDevices().then(function (devicesAfterPermission) {
                    return devicesAfterPermission.filter(function (device) { return device.kind === "videoinput"; });
                });
            }).catch(function () { return videoDevices; });
        }).then(function (videoDevices) {
            scanCamaras = videoDevices || [];
            renderSelectorCamarasSugerido();
            return scanCamaras;
        });
    }

    function renderSelectorCamarasSugerido() {
        var select = document.getElementById("sugerido_scan_camera_device");
        var label = document.getElementById("sugerido_scan_camera_device_label");
        if (!select || !label || scanCamaras.length <= 1) {
            if (select) { select.classList.add("d-none"); }
            if (label) { label.classList.add("d-none"); }
            return;
        }
        select.innerHTML = scanCamaras.map(function (device, index) {
            return "<option value=\"" + esc(device.deviceId) + "\">" + esc(device.label || ("Camara " + (index + 1))) + "</option>";
        }).join("");
        if (scanCamaraSeleccionada) { select.value = scanCamaraSeleccionada; }
        select.classList.remove("d-none");
        label.classList.remove("d-none");
    }

    function elegirCamaraPreferidaSugerido() {
        if (scanCamaraSeleccionada) { return scanCamaraSeleccionada; }
        if (!scanCamaras.length) { return ""; }
        var candidatas = scanCamaras.map(function (device) { return {device: device, label: String(device.label || "").toLowerCase()}; });
        var trasera = candidatas.filter(function (item) { return !/(front|frontal|user|facetime|selfie|ultra|wide|gran angular|0\.5|macro)/i.test(item.label); });
        var principal = trasera.find(function (item) { return /(back|rear|environment|trasera|posterior|principal|main)/i.test(item.label); });
        if (principal) { return principal.device.deviceId; }
        if (trasera.length) { return trasera[trasera.length - 1].device.deviceId; }
        return scanCamaras[scanCamaras.length - 1].deviceId;
    }

    function restriccionesCamaraSugerido(deviceId) {
        var video = {width: {ideal: 1280}, height: {ideal: 720}, frameRate: {ideal: 30, max: 30}};
        if (deviceId) { video.deviceId = {exact: deviceId}; } else { video.facingMode = {ideal: "environment"}; }
        return {audio: false, video: video};
    }

    function scanTrackSugerido() {
        return scanStream ? scanStream.getVideoTracks()[0] : null;
    }

    function aplicarMejorasCamaraSugerido() {
        var track = scanTrackSugerido();
        if (!track || !track.getCapabilities) { return Promise.resolve(false); }
        var caps = track.getCapabilities();
        var advanced = [];
        if (caps.focusMode && caps.focusMode.indexOf("continuous") !== -1) { advanced.push({focusMode: "continuous"}); }
        if (!advanced.length) { return Promise.resolve(false); }
        return track.applyConstraints({advanced: advanced}).then(function () { return true; }).catch(function () { return false; });
    }

    function actualizarControlesCamaraSugerido() {
        var track = scanTrackSugerido();
        var caps = track && track.getCapabilities ? track.getCapabilities() : {};
        document.getElementById("sugerido_scan_focus").classList.toggle("d-none", !track);
        document.getElementById("sugerido_scan_stop").classList.toggle("d-none", !track);
        document.getElementById("sugerido_scan_torch").classList.toggle("d-none", !(caps && caps.torch));
    }

    function iniciarCamaraSugerido() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            document.getElementById("sugerido_scan_estado").textContent = "Este navegador no expone camara para la pagina actual.";
            return;
        }
        if (!("BarcodeDetector" in window)) {
            document.getElementById("sugerido_scan_estado").textContent = "Tu navegador no tiene lector nativo de codigos; usa busqueda manual o escaner USB.";
            return;
        }
        detenerCamaraSugerido(false);
        document.getElementById("sugerido_scan_estado").textContent = "Buscando camaras disponibles...";
        prepararCamarasSugerido().then(function () {
            var deviceId = elegirCamaraPreferidaSugerido();
            scanCamaraSeleccionada = deviceId;
            renderSelectorCamarasSugerido();
            return navigator.mediaDevices.getUserMedia(restriccionesCamaraSugerido(deviceId)).catch(function () {
                return navigator.mediaDevices.getUserMedia(restriccionesCamaraSugerido(""));
            });
        }).then(function (mediaStream) {
            scanStream = mediaStream;
            scanActivo = true;
            scanTorchActivo = false;
            var video = document.getElementById("sugerido_scan_video");
            video.srcObject = scanStream;
            document.getElementById("sugerido_scan_wrap").classList.remove("d-none");
            actualizarControlesCamaraSugerido();
            video.play().catch(function () {});
            aplicarMejorasCamaraSugerido().then(function (mejorado) {
                document.getElementById("sugerido_scan_estado").textContent = mejorado ? "Camara lista con enfoque continuo." : "Camara lista. Manten el codigo a distancia nitida.";
            });
            detectarLoopCamaraSugerido(new BarcodeDetector({formats: ["ean_13", "ean_8", "code_128", "code_39", "upc_a", "upc_e", "qr_code"]}));
        }).catch(function (error) {
            document.getElementById("sugerido_scan_estado").textContent = "No se pudo abrir la camara: " + error.message;
        });
    }

    function detectarLoopCamaraSugerido(detector) {
        var video = document.getElementById("sugerido_scan_video");
        if (!scanActivo || !video || video.readyState < 2) {
            if (scanActivo) { setTimeout(function () { detectarLoopCamaraSugerido(detector); }, 250); }
            return;
        }
        detector.detect(video).then(function (codigos) {
            if (codigos && codigos.length) {
                var valor = codigos[0].rawValue || "";
                if (valor) {
                    document.getElementById("sugerido_scan_estado").textContent = "Codigo leido: " + valor;
                    detenerCamaraSugerido(false);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById("sugerido_scan_modal")).hide();
                    buscarCodigoEscaneadoSugerido(valor);
                    return;
                }
            }
            if (scanActivo) { setTimeout(function () { detectarLoopCamaraSugerido(detector); }, 350); }
        }).catch(function () {
            if (scanActivo) { setTimeout(function () { detectarLoopCamaraSugerido(detector); }, 600); }
        });
    }

    function alternarLuzCamaraSugerido() {
        var track = scanTrackSugerido();
        if (!track) { return; }
        scanTorchActivo = !scanTorchActivo;
        track.applyConstraints({advanced: [{torch: scanTorchActivo}]}).then(function () {
            document.getElementById("sugerido_scan_torch").classList.toggle("btn-warning", scanTorchActivo);
            document.getElementById("sugerido_scan_torch").classList.toggle("btn-light-warning", !scanTorchActivo);
            document.getElementById("sugerido_scan_estado").textContent = scanTorchActivo ? "Luz encendida." : "Luz apagada.";
        }).catch(function () {
            scanTorchActivo = false;
            document.getElementById("sugerido_scan_estado").textContent = "Este dispositivo no permite controlar la luz desde el navegador.";
        });
    }

    function reiniciarCamaraConSeleccionSugerido() {
        var select = document.getElementById("sugerido_scan_camera_device");
        if (!select) { return; }
        scanCamaraSeleccionada = select.value;
        iniciarCamaraSugerido();
    }

    function mejorarEnfoqueCamaraSugerido() {
        aplicarMejorasCamaraSugerido().then(function (ok) {
            document.getElementById("sugerido_scan_estado").textContent = ok ? "Enfoque continuo solicitado." : "Este navegador no permite ajustar enfoque.";
        });
    }

    function detenerCamaraSugerido(actualizarTexto) {
        scanActivo = false;
        scanTorchActivo = false;
        if (scanStream) { scanStream.getTracks().forEach(function (track) { track.stop(); }); }
        scanStream = null;
        document.getElementById("sugerido_scan_wrap").classList.add("d-none");
        document.getElementById("sugerido_scan_focus").classList.add("d-none");
        document.getElementById("sugerido_scan_torch").classList.add("d-none");
        document.getElementById("sugerido_scan_stop").classList.add("d-none");
        document.getElementById("sugerido_scan_torch").classList.remove("btn-warning");
        document.getElementById("sugerido_scan_torch").classList.add("btn-light-warning");
        if (actualizarTexto !== false) { document.getElementById("sugerido_scan_estado").textContent = "Camara detenida."; }
    }

    function abrirEscanerSugerido() {
        if (modoLectura) { return; }
        if (!document.getElementById("sugerido_proveedor").value) {
            Swal.fire({text: "Selecciona un proveedor antes de escanear.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        document.getElementById("sugerido_scan_estado").textContent = "Abriendo camara...";
        bootstrap.Modal.getOrCreateInstance(document.getElementById("sugerido_scan_modal")).show();
    }
    function guardar(estatus) {
        if (schemaPendiente) {
            Swal.fire({text: "Primero hay que preparar el esquema de Sugerido de compra con respaldo externo y autorizacion.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        if (!puedeEditar && !puedeCrear) {
            Swal.fire({text: "No tienes permiso para guardar sugeridos", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        post("/compra/sugerido_guardar_erp", {
            id_sugerido_compra: document.getElementById("sugerido_id").value,
            id_proveedor: document.getElementById("sugerido_proveedor").value,
            observaciones: document.getElementById("sugerido_observaciones").value,
            estatus: estatus,
            items: JSON.stringify(items)
        }).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            Swal.fire({text: r.mensaje, icon: "success", confirmButtonText: "Aceptar"}).then(function () {
                if (r.depurar && r.depurar.id_sugerido_compra) {
                    window.location.href = "/compra/sugerido_compra/" + r.depurar.id_sugerido_compra;
                }
            });
        }).catch(function (e) {
            Swal.fire({text: e.message || "No se pudo guardar", icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function generarSolicitud() {
        var id = Number(document.getElementById("sugerido_id").value || 0);
        if (!id) {
            Swal.fire({text: "Guarda el sugerido antes de generar solicitud.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        post("/compra/sugerido_generar_solicitud_erp", {id_sugerido_compra: id}).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            window.location.href = "/compra/editar_solicitud/" + r.depurar.id_solicitud;
        }).catch(function (e) {
            Swal.fire({text: e.message || "No se pudo generar solicitud", icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        puedeCrear = Number(document.getElementById("sugerido_puede_crear").value || 0) === 1;
        puedeEditar = Number(document.getElementById("sugerido_puede_editar").value || 0) === 1;
        modoLectura = (document.getElementById("sugerido_modo").value || "editar") === "ver";
        document.getElementById("sugerido_guardar_borrador").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_marcar_lista").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_generar_solicitud").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_recalcular").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_buscar_productos").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_proveedor").disabled = modoLectura;
        document.getElementById("sugerido_observaciones").readOnly = modoLectura;
        document.getElementById("sugerido_buscar").readOnly = modoLectura;
        document.getElementById("sugerido_scan_camera_btn").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_scan_camera_btn").addEventListener("click", abrirEscanerSugerido);
        document.getElementById("sugerido_scan_start").addEventListener("click", iniciarCamaraSugerido);
        document.getElementById("sugerido_scan_camera_device").addEventListener("change", reiniciarCamaraConSeleccionSugerido);
        document.getElementById("sugerido_scan_focus").addEventListener("click", mejorarEnfoqueCamaraSugerido);
        document.getElementById("sugerido_scan_torch").addEventListener("click", alternarLuzCamaraSugerido);
        document.getElementById("sugerido_scan_stop").addEventListener("click", detenerCamaraSugerido);
        document.getElementById("sugerido_scan_modal").addEventListener("hidden.bs.modal", detenerCamaraSugerido);
        cargarCatalogos().then(cargarSugerido).catch(function (e) {
            document.getElementById("sugerido_resumen").textContent = e.message || "No se pudieron cargar proveedores.";
            Swal.fire({text: e.message || "No se pudieron cargar proveedores.", icon: "error", confirmButtonText: "Aceptar"});
        });

        document.getElementById("sugerido_proveedor").addEventListener("change", function () { if (!modoLectura) { items = []; candidatos = []; document.getElementById("sugerido_buscar").value = ""; renderResultados(); render(); } });
        document.getElementById("sugerido_buscar").addEventListener("keydown", function (e) {
            if (modoLectura) { return; }
            if (e.key === "Enter") {
                e.preventDefault();
                consultarProveedor();
            }
        });
        document.getElementById("sugerido_recalcular").addEventListener("click", function () { recalcular(true); });
        document.getElementById("sugerido_buscar_productos").addEventListener("click", consultarProveedor);
        document.getElementById("sugerido_limpiar_ceros").addEventListener("click", function () {
            ocultarCeros = !ocultarCeros;
            this.textContent = ocultarCeros ? "Mostrar todos" : "Ocultar ceros";
            render();
        });
        document.getElementById("sugerido_resultados").addEventListener("click", function (e) {
            var boton = e.target.closest("[data-sugerido-agregar]");
            if (boton) { agregarCandidato(boton.getAttribute("data-sugerido-agregar")); }
        });
        /**
         * IA: Codex GPT-5 | Fecha: 2026-08-27
         * Proposito: facilitar captura movil de cantidades seleccionando el valor completo al enfocar.
         * Impacto: UX Compras/Sugerido; permite reemplazar ceros/defaults sin pelear con el cursor.
         */
        document.getElementById("sugerido_items").addEventListener("focusin", function (e) {
            if (e.target.matches("[data-sugerido-minimo], [data-sugerido-maximo], [data-sugerido-reorden], [data-sugerido-existencia], [data-sugerido-cantidad]")) {
                e.target.select();
            }
        });
        document.getElementById("sugerido_items").addEventListener("input", function (e) {
            if (modoLectura) { return; }
            var existencia = e.target.getAttribute("data-sugerido-existencia");
            var cantidad = e.target.getAttribute("data-sugerido-cantidad");
            var obs = e.target.getAttribute("data-sugerido-obs");
            var minimo = e.target.getAttribute("data-sugerido-minimo");
            var maximo = e.target.getAttribute("data-sugerido-maximo");
            var reorden = e.target.getAttribute("data-sugerido-reorden");
            if (minimo !== null) {
                items[Number(minimo)].stock_minimo = Number(e.target.value || 0);
                items[Number(minimo)].cantidad_sugerida = calcularCantidadSugerida(items[Number(minimo)]);
                actualizarSugeridoVisual(Number(minimo));
            } else if (maximo !== null) {
                items[Number(maximo)].stock_maximo = e.target.value === "" ? null : Number(e.target.value || 0);
                items[Number(maximo)].cantidad_sugerida = calcularCantidadSugerida(items[Number(maximo)]);
                actualizarSugeridoVisual(Number(maximo));
            } else if (reorden !== null) {
                items[Number(reorden)].punto_reorden = Number(e.target.value || 0);
                items[Number(reorden)].cantidad_sugerida = calcularCantidadSugerida(items[Number(reorden)]);
                actualizarSugeridoVisual(Number(reorden));
            } else if (existencia !== null) {
                items[Number(existencia)].existencia_revisada = Number(e.target.value || 0);
                items[Number(existencia)].cantidad_sugerida = calcularCantidadSugerida(items[Number(existencia)]);
                actualizarSugeridoVisual(Number(existencia));
            } else if (cantidad !== null) {
                items[Number(cantidad)].cantidad_solicitar = Number(e.target.value || 0);
                actualizarResumen();
            } else if (obs !== null) {
                items[Number(obs)].observaciones = e.target.value;
            }
        });
        document.getElementById("sugerido_guardar_borrador").addEventListener("click", function () { guardar("borrador"); });
        document.getElementById("sugerido_marcar_lista").addEventListener("click", function () { guardar("lista"); });
        document.getElementById("sugerido_generar_solicitud").addEventListener("click", generarSolicitud);
        render();
    });
})();




