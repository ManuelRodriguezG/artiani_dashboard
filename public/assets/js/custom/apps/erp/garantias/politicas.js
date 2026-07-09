/**
 * ERP Garantias - Politicas
 * Documentacion IA: Codex GPT-5
 * Version: 1.0
 * Descripcion: consulta politicas/reglas y ejecuta pruebas read-only/dry-run por SKU.
 */
(function () {
    const ENDPOINT_POLITICAS = "/garantias/politicas_listar_erp";
    const ENDPOINT_REGLAS = "/garantias/politicas_reglas_listar_erp";
    const ENDPOINT_RESOLVER = "/garantias/resolver_sku_erp";
    const ENDPOINT_SNAPSHOT = "/garantias/venta_snapshot_dryrun_erp";
    const ENDPOINT_BUSCAR_SKU = "/garantias/skus_buscar_erp";
    const ENDPOINT_COBERTURA = "/garantias/cobertura_skus_erp";
    const ENDPOINT_REGLA_DRYRUN = "/garantias/politica_regla_dryrun_erp";
    const ENDPOINT_REGLA_IMPACTO = "/garantias/politica_regla_impacto_erp";
    const ENDPOINT_POLITICA_DRYRUN = "/garantias/politica_dryrun_erp";
    const ENDPOINT_REFERENCIAS = "/garantias/referencias_buscar_erp";
    const ENDPOINT_POLITICA_GUARDAR = "/garantias/politica_guardar_erp";
    const ENDPOINT_REGLA_GUARDAR = "/garantias/politica_regla_guardar_erp";
    const ENDPOINT_POLITICA_ESTATUS = "/garantias/politica_estatus_erp";
    const ENDPOINT_REGLA_ESTATUS = "/garantias/politica_regla_estatus_erp";
    let politicasDisponibles = [];
    let reglasDisponibles = [];
    let firmaRevisionRegla = "";

    document.addEventListener("DOMContentLoaded", function () {
        enlazarEventos();
        cargarPanel();
    });

    /**
     * ERP Garantias - Politicas
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Conecta filtros y botones de pruebas controladas.
     */
    function enlazarEventos() {
        const refrescar = document.getElementById("garantias_refrescar");
        const filtroAmbito = document.getElementById("garantias_filtro_ambito");
        const filtroReglaEstatus = document.getElementById("garantias_filtro_regla_estatus");
        const resolver = document.getElementById("garantias_resolver_sku");
        const snapshot = document.getElementById("garantias_snapshot_sku");
        const buscarSku = document.getElementById("garantias_buscar_sku");
        const busquedaSku = document.getElementById("garantias_sku_busqueda");
        const validarRegla = document.getElementById("garantias_validar_regla");
        const impactoRegla = document.getElementById("garantias_impacto_regla");
        const validarPolitica = document.getElementById("garantias_politica_validar");
        const limpiarPolitica = document.getElementById("garantias_politica_limpiar");
        const guardarPolitica = document.getElementById("garantias_politica_guardar");
        const guardarRegla = document.getElementById("garantias_guardar_regla");
        const buscarReferencia = document.getElementById("garantias_buscar_referencia");
        const busquedaReferencia = document.getElementById("garantias_referencia_busqueda");
        const ambitoRegla = document.getElementById("garantias_dryrun_ambito");
        const tipoPolitica = document.getElementById("garantias_politica_tipo");

        if (refrescar) {
            refrescar.addEventListener("click", cargarPanel);
        }
        if (filtroAmbito) {
            filtroAmbito.addEventListener("change", cargarReglas);
        }
        if (filtroReglaEstatus) {
            filtroReglaEstatus.addEventListener("change", cargarReglas);
        }
        if (resolver) {
            resolver.addEventListener("click", resolverSku);
        }
        if (snapshot) {
            snapshot.addEventListener("click", snapshotSku);
        }
        if (buscarSku) {
            buscarSku.addEventListener("click", buscarSkus);
        }
        if (busquedaSku) {
            busquedaSku.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    buscarSkus();
                }
            });
        }
        if (validarRegla) {
            validarRegla.addEventListener("click", validarReglaDryRun);
        }
        if (impactoRegla) {
            impactoRegla.addEventListener("click", previsualizarImpactoRegla);
        }
        if (validarPolitica) {
            validarPolitica.addEventListener("click", validarPoliticaDryRun);
        }
        if (limpiarPolitica) {
            limpiarPolitica.addEventListener("click", limpiarFormularioPolitica);
        }
        if (guardarPolitica) {
            guardarPolitica.addEventListener("click", guardarPoliticaReal);
        }
        if (tipoPolitica) {
            tipoPolitica.addEventListener("change", actualizarAyudaTipoPolitica);
            actualizarAyudaTipoPolitica();
        }
        if (guardarRegla) {
            guardarRegla.addEventListener("click", guardarReglaReal);
        }
        if (buscarReferencia) {
            buscarReferencia.addEventListener("click", buscarReferenciasRegla);
        }
        if (busquedaReferencia) {
            busquedaReferencia.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    buscarReferenciasRegla();
                }
            });
        }
        if (ambitoRegla) {
            ambitoRegla.addEventListener("change", limpiarReferenciaRegla);
        }
        ["garantias_dryrun_politica", "garantias_dryrun_referencia", "garantias_dryrun_prioridad", "garantias_dryrun_canal"].forEach(function (id) {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener("change", invalidarRevisionRegla);
                input.addEventListener("input", invalidarRevisionRegla);
            }
        });
        document.addEventListener("click", function (event) {
            const boton = event.target.closest(".garantias-seleccionar-sku");
            if (boton) {
                seleccionarSku(boton);
                return;
            }
            const politica = event.target.closest(".garantias-cargar-politica");
            if (politica) {
                cargarPoliticaEnFormulario(politica);
                return;
            }
            const referencia = event.target.closest(".garantias-seleccionar-referencia");
            if (referencia) {
                seleccionarReferenciaRegla(referencia);
                return;
            }
            const regla = event.target.closest(".garantias-cargar-regla");
            if (regla) {
                cargarReglaEnFormulario(regla);
                return;
            }
            const estatusPolitica = event.target.closest(".garantias-estatus-politica");
            if (estatusPolitica) {
                cambiarEstatusPolitica(estatusPolitica);
                return;
            }
            const estatusRegla = event.target.closest(".garantias-estatus-regla");
            if (estatusRegla) {
                cambiarEstatusRegla(estatusRegla);
                return;
            }
            const filtroAmbitoResumen = event.target.closest("[data-garantia-filtro-ambito]");
            if (filtroAmbitoResumen) {
                const filtro = document.getElementById("garantias_filtro_ambito");
                if (filtro) {
                    filtro.value = filtroAmbitoResumen.getAttribute("data-garantia-filtro-ambito") || "";
                    cargarReglas();
                }
            }
        });
    }

    function cargarPanel() {
        cargarPoliticas();
        cargarReglas();
        cargarCobertura();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: explica el tipo de garantia seleccionado sin depender de documentacion externa.
     * Impacto: Garantias UI; ayuda al operador a configurar politicas sin cambiar persistencia.
     */
    function actualizarAyudaTipoPolitica() {
        const ayuda = document.getElementById("garantias_politica_tipo_ayuda");
        if (!ayuda) {
            return;
        }
        const textos = {
            sin_garantia: "El producto no ofrece garantia operativa; se usa para excluir categorias o SKUs.",
            garantia_tienda: "La tienda responde directamente con sus reglas internas.",
            garantia_proveedor: "La tienda recibe el caso, pero la resolucion depende del proveedor.",
            garantia_fabricante: "El cliente o la tienda siguen condiciones del fabricante.",
            cambio_inmediato: "Permite reemplazo rapido si cumple requisitos minimos.",
            reparacion: "El resultado esperado es diagnostico y reparacion antes de cambio o devolucion.",
            satisfaccion_limitada: "Politica comercial limitada; suele requerir autorizacion o condiciones especiales.",
            caducidad_calidad: "Aplica a problemas de caducidad, estado o calidad del producto."
        };
        ayuda.textContent = textos[valor("garantias_politica_tipo")] || "Selecciona el tipo de garantia.";
    }

    function cargarPoliticas() {
        consultarJson(ENDPOINT_POLITICAS)
            .then(function (respuesta) {
                if (respuesta.error) {
                    renderError("garantias_politicas_body", 6, respuesta.mensaje);
                    return;
                }
                const politicas = (((respuesta || {}).depurar || {}).politicas || []);
                politicasDisponibles = politicas;
                setText("garantias_total_politicas", politicas.filter(function (p) {
                    return p.estatus === "activa";
                }).length);
                renderOpcionesPoliticas(politicas);
                renderPoliticas(politicas);
            })
            .catch(function (error) {
                renderError("garantias_politicas_body", 6, error.message);
            });
    }

    function cargarReglas() {
        const params = new URLSearchParams();
        const estatus = valor("garantias_filtro_regla_estatus");
        if (estatus) {
            params.append("estatus", estatus);
        }
        const ambito = valor("garantias_filtro_ambito");
        if (ambito) {
            params.append("ambito", ambito);
        }

        consultarJson(ENDPOINT_REGLAS + "?" + params.toString())
            .then(function (respuesta) {
                if (respuesta.error) {
                    renderError("garantias_reglas_body", 7, respuesta.mensaje);
                    return;
                }
                const reglas = (((respuesta || {}).depurar || {}).reglas || []);
                reglasDisponibles = reglas;
                setText("garantias_total_reglas", reglas.length);
                renderReglas(reglas);
                renderResumenReglas(reglas);
            })
            .catch(function (error) {
                renderError("garantias_reglas_body", 7, error.message);
            });
    }

    function cargarCobertura() {
        consultarJson(ENDPOINT_COBERTURA)
            .then(function (respuesta) {
                if (respuesta.error) {
                    setText("garantias_cobertura_resumen", respuesta.mensaje || "No fue posible auditar cobertura");
                    return;
                }
                renderCobertura(respuesta.depurar || {});
            })
            .catch(function (error) {
                setText("garantias_cobertura_resumen", error.message);
            });
    }

    function resolverSku() {
        const idSku = numero(valor("garantias_sku_prueba"));
        const canal = valor("garantias_canal_prueba") || "pos";
        if (idSku <= 0) {
            renderResultado('<div class="text-warning fw-semibold">Indica un SKU ERP valido.</div>');
            return;
        }

        setText("garantias_estado_resolver", "Consultando");
        const params = new URLSearchParams();
        params.append("id_sku_erp", String(idSku));
        params.append("canal", canal);

        consultarJson(ENDPOINT_RESOLVER + "?" + params.toString())
            .then(function (respuesta) {
                renderResolver(respuesta);
            })
            .catch(function (error) {
                setText("garantias_estado_resolver", "Error");
                renderResultado('<div class="text-danger fw-semibold">' + escapeHtml(error.message) + '</div>');
            });
    }

    function snapshotSku() {
        const idSku = numero(valor("garantias_sku_prueba"));
        const canal = valor("garantias_canal_prueba") || "pos";
        if (idSku <= 0) {
            renderResultado('<div class="text-warning fw-semibold">Indica un SKU ERP valido.</div>');
            return;
        }

        setText("garantias_estado_resolver", "Simulando");
        const datos = new URLSearchParams();
        datos.append("items", JSON.stringify([{ id_sku_erp: idSku }]));
        datos.append("canal", canal);

        fetch(ENDPOINT_SNAPSHOT, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: datos.toString()
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error("No fue posible simular snapshot");
            }
            return respuesta.json();
        }).then(function (respuesta) {
            renderSnapshot(respuesta);
        }).catch(function (error) {
            setText("garantias_estado_resolver", "Error");
            renderResultado('<div class="text-danger fw-semibold">' + escapeHtml(error.message) + '</div>');
        });
    }

    function buscarSkus() {
        const termino = valor("garantias_sku_busqueda");
        const contenedor = document.getElementById("garantias_sku_resultados");
        if (termino.length < 2) {
            if (contenedor) {
                contenedor.innerHTML = '<div class="text-muted fs-7">Captura al menos dos caracteres.</div>';
            }
            return;
        }
        if (contenedor) {
            contenedor.innerHTML = '<div class="text-muted fs-7">Buscando...</div>';
        }

        const params = new URLSearchParams();
        params.append("q", termino);
        consultarJson(ENDPOINT_BUSCAR_SKU + "?" + params.toString())
            .then(function (respuesta) {
                if (respuesta.error) {
                    if (contenedor) {
                        contenedor.innerHTML = '<div class="text-danger fs-7">' + escapeHtml(respuesta.mensaje) + '</div>';
                    }
                    return;
                }
                renderResultadosSku((((respuesta || {}).depurar || {}).skus || []));
            })
            .catch(function (error) {
                if (contenedor) {
                    contenedor.innerHTML = '<div class="text-danger fs-7">' + escapeHtml(error.message) + '</div>';
                }
            });
    }

    function renderResultadosSku(skus) {
        const contenedor = document.getElementById("garantias_sku_resultados");
        if (!contenedor) {
            return;
        }
        if (!skus.length) {
            contenedor.innerHTML = '<div class="text-muted fs-7">Sin coincidencias.</div>';
            return;
        }
        contenedor.innerHTML = '<div class="border border-gray-300 rounded overflow-hidden">' + skus.map(function (sku) {
            return '' +
                '<button type="button" class="btn btn-light w-100 text-start rounded-0 border-bottom garantias-seleccionar-sku" ' +
                    'data-id="' + numero(sku.id_sku) + '" ' +
                    'data-sku="' + escapeHtml(sku.sku) + '" ' +
                    'data-nombre="' + escapeHtml(sku.sku_nombre || sku.producto || "") + '">' +
                    '<span class="fw-bold">' + escapeHtml(sku.sku) + '</span>' +
                    '<span class="text-muted ms-2">' + escapeHtml(sku.sku_nombre || "") + '</span>' +
                    '<span class="badge badge-light ms-2">' + escapeHtml(sku.estatus || "") + '</span>' +
                    '<div class="text-muted fs-8">' + escapeHtml(sku.producto || "") + '</div>' +
                '</button>';
        }).join("") + '</div>';
    }

    function seleccionarSku(boton) {
        const idSku = numero(boton.getAttribute("data-id"));
        const sku = boton.getAttribute("data-sku") || "";
        const nombre = boton.getAttribute("data-nombre") || "";
        const inputId = document.getElementById("garantias_sku_prueba");
        const inputBusqueda = document.getElementById("garantias_sku_busqueda");
        const seleccionado = document.getElementById("garantias_sku_seleccionado");
        const resultados = document.getElementById("garantias_sku_resultados");

        if (inputId) {
            inputId.value = String(idSku);
        }
        if (inputBusqueda) {
            inputBusqueda.value = sku;
        }
        if (seleccionado) {
            seleccionado.textContent = "Seleccionado: ID " + idSku + " - " + sku + (nombre ? " - " + nombre : "");
        }
        if (resultados) {
            resultados.innerHTML = "";
        }
        const ambitoDryRun = valor("garantias_dryrun_ambito");
        const referenciaDryRun = document.getElementById("garantias_dryrun_referencia");
        if (ambitoDryRun === "sku" && referenciaDryRun) {
            referenciaDryRun.value = String(idSku);
            setText("garantias_referencia_seleccionada", "Referencia seleccionada: ID " + idSku + " - " + sku + (nombre ? " - " + nombre : ""));
        }
        resolverSku();
    }

    /**
     * ERP Garantias - Politicas
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Busca referencias del ambito elegido para evitar captura manual de IDs internos.
     */
    function buscarReferenciasRegla() {
        const termino = valor("garantias_referencia_busqueda");
        const contenedor = document.getElementById("garantias_referencia_resultados");
        const ambito = valor("garantias_dryrun_ambito") || "sku";
        if (termino.length < 2) {
            if (contenedor) {
                contenedor.innerHTML = '<div class="text-muted fs-7">Captura al menos dos caracteres para buscar ' + escapeHtml(etiquetaAmbito(ambito).toLowerCase()) + '.</div>';
            }
            return;
        }
        if (contenedor) {
            contenedor.innerHTML = '<div class="text-muted fs-7">Buscando referencias...</div>';
        }

        const params = new URLSearchParams();
        params.append("ambito", ambito);
        params.append("q", termino);
        consultarJson(ENDPOINT_REFERENCIAS + "?" + params.toString())
            .then(function (respuesta) {
                if (respuesta.error) {
                    if (contenedor) {
                        contenedor.innerHTML = '<div class="text-danger fs-7">' + escapeHtml(respuesta.mensaje) + '</div>';
                    }
                    return;
                }
                renderReferenciasRegla((((respuesta || {}).depurar || {}).referencias || []), ambito);
            })
            .catch(function (error) {
                if (contenedor) {
                    contenedor.innerHTML = '<div class="text-danger fs-7">' + escapeHtml(error.message) + '</div>';
                }
            });
    }

    /**
     * ERP Garantias - Politicas
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Renderiza referencias candidatas para reglas por SKU, producto, categoria, marca o proveedor.
     */
    function renderReferenciasRegla(referencias, ambito) {
        const contenedor = document.getElementById("garantias_referencia_resultados");
        if (!contenedor) {
            return;
        }
        if (!referencias.length) {
            contenedor.innerHTML = '<div class="text-muted fs-7">Sin coincidencias para ' + escapeHtml(etiquetaAmbito(ambito).toLowerCase()) + '.</div>';
            return;
        }
        contenedor.innerHTML = '<div class="border border-gray-300 rounded overflow-hidden">' + referencias.map(function (referencia) {
            const etiqueta = (referencia.codigo ? referencia.codigo + " - " : "") + (referencia.nombre || "");
            return '' +
                '<button type="button" class="btn btn-light w-100 text-start rounded-0 border-bottom garantias-seleccionar-referencia" ' +
                    'data-id="' + numero(referencia.id_referencia) + '" ' +
                    'data-etiqueta="' + escapeHtml(etiqueta) + '">' +
                    '<span class="fw-bold">' + escapeHtml(etiqueta) + '</span>' +
                    '<span class="badge badge-light ms-2">' + escapeHtml(etiquetaAmbito(ambito)) + '</span>' +
                    '<div class="text-muted fs-8">' + escapeHtml(referencia.detalle || "") + '</div>' +
                '</button>';
        }).join("") + '</div>';
    }

    function seleccionarReferenciaRegla(boton) {
        const id = numero(boton.getAttribute("data-id"));
        const etiqueta = boton.getAttribute("data-etiqueta") || "";
        setValue("garantias_dryrun_referencia", id);
        setText("garantias_referencia_seleccionada", "Referencia seleccionada: ID " + id + " - " + etiqueta);
        invalidarRevisionRegla();
        const contenedor = document.getElementById("garantias_referencia_resultados");
        if (contenedor) {
            contenedor.innerHTML = "";
        }
    }

    function limpiarReferenciaRegla() {
        setValue("garantias_dryrun_referencia", "");
        setValue("garantias_referencia_busqueda", "");
        invalidarRevisionRegla();
        setText("garantias_referencia_seleccionada", "Selecciona una referencia para " + etiquetaAmbito(valor("garantias_dryrun_ambito")).toLowerCase() + ".");
        const contenedor = document.getElementById("garantias_referencia_resultados");
        if (contenedor) {
            contenedor.innerHTML = "";
        }
    }

    function validarReglaDryRun() {
        const resultado = document.getElementById("garantias_dryrun_regla_resultado");
        const datos = datosReglaFormulario();

        if (resultado) {
            resultado.innerHTML = '<span class="text-muted">Validando...</span>';
        }

        fetch(ENDPOINT_REGLA_DRYRUN, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: datos.toString()
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error("No fue posible validar la regla");
            }
            return respuesta.json();
        }).then(function (respuesta) {
            registrarRevisionRegla(respuesta, datos);
            renderReglaDryRun(respuesta);
        }).catch(function (error) {
            invalidarRevisionRegla();
            if (resultado) {
                resultado.innerHTML = '<span class="text-danger fw-semibold">' + escapeHtml(error.message) + '</span>';
            }
        });
    }

    function previsualizarImpactoRegla() {
        const resultado = document.getElementById("garantias_dryrun_regla_resultado");
        const datos = datosReglaFormulario();
        if (resultado) {
            resultado.innerHTML = '<span class="text-muted">Calculando impacto...</span>';
        }

        fetch(ENDPOINT_REGLA_IMPACTO, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: datos.toString()
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error("No fue posible calcular impacto");
            }
            return respuesta.json();
        }).then(function (respuesta) {
            registrarRevisionRegla(respuesta, datos);
            renderImpactoRegla(respuesta);
        }).catch(function (error) {
            invalidarRevisionRegla();
            if (resultado) {
                resultado.innerHTML = '<span class="text-danger fw-semibold">' + escapeHtml(error.message) + '</span>';
            }
        });
    }

    function validarPoliticaDryRun() {
        const resultado = document.getElementById("garantias_politica_resultado");
        const datos = datosPoliticaFormulario();
        if (resultado) {
            resultado.innerHTML = '<span class="text-muted">Validando...</span>';
        }

        fetch(ENDPOINT_POLITICA_DRYRUN, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: datos.toString()
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error("No fue posible validar la politica");
            }
            return respuesta.json();
        }).then(function (respuesta) {
            renderPoliticaDryRun(respuesta);
        }).catch(function (error) {
            if (resultado) {
                resultado.innerHTML = '<span class="text-danger fw-semibold">' + escapeHtml(error.message) + '</span>';
            }
        });
    }

    /**
     * ERP Garantias - Politicas
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Envia guardado operativo de politica con confirmacion y auditoria del controlador.
     */
    function guardarPoliticaReal() {
        const resultado = document.getElementById("garantias_politica_resultado");
        const datos = datosPoliticaFormulario();
        const editando = numero(valor("garantias_politica_id")) > 0;
        if (!confirmarGuardadoReal((editando ? "Se actualizara" : "Se creara") + " la politica de garantia. El cambio quedara auditado.")) {
            return;
        }
        if (resultado) {
            resultado.innerHTML = '<span class="text-muted">Guardando politica...</span>';
        }
        enviarGuardado(ENDPOINT_POLITICA_GUARDAR, datos)
            .then(function (respuesta) {
                renderGuardadoPolitica(respuesta);
                if (!respuesta.error) {
                    cargarPoliticas();
                }
            })
            .catch(function (error) {
                if (resultado) {
                    resultado.innerHTML = '<span class="text-danger fw-semibold">' + escapeHtml(error.message) + '</span>';
                }
            });
    }

    /**
     * ERP Garantias - Politicas
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Envia guardado operativo de regla con confirmacion y auditoria del controlador.
     */
    function guardarReglaReal() {
        const resultado = document.getElementById("garantias_dryrun_regla_resultado");
        const datos = datosReglaFormulario();
        const editando = numero(valor("garantias_regla_id")) > 0;
        if (!revisionReglaVigente(datos)) {
            if (resultado) {
                resultado.innerHTML = '<div class="alert alert-warning mb-0"><div class="fw-bold mb-1">Revisa antes de guardar</div><div>Ejecuta Validar o Impacto con la configuracion actual de la regla.</div></div>';
            }
            return;
        }
        if (!confirmarGuardadoReal((editando ? "Se actualizara" : "Se creara") + " una regla de asignacion. Revisa primero Validar o Impacto.")) {
            return;
        }
        if (resultado) {
            resultado.innerHTML = '<span class="text-muted">Guardando regla...</span>';
        }
        enviarGuardado(ENDPOINT_REGLA_GUARDAR, datos)
            .then(function (respuesta) {
                renderGuardadoRegla(respuesta);
                if (!respuesta.error) {
                    invalidarRevisionRegla();
                    cargarReglas();
                    cargarCobertura();
                }
            })
            .catch(function (error) {
                if (resultado) {
                    resultado.innerHTML = '<span class="text-danger fw-semibold">' + escapeHtml(error.message) + '</span>';
                }
            });
    }

    function enviarGuardado(url, datos) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: datos.toString()
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error("No fue posible guardar configuracion");
            }
            return respuesta.json();
        });
    }

    /**
     * ERP Garantias - Politicas
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Cambia estatus de politica con baja logica y refresco de listas.
     */
    function cambiarEstatusPolitica(boton) {
        const id = numero(boton.getAttribute("data-id"));
        const estatus = boton.getAttribute("data-estatus") || "";
        if (id <= 0 || !estatus) {
            return;
        }
        if (!confirmarGuardadoReal("Se " + (estatus === "activa" ? "reactivara" : "desactivara") + " la politica. El cambio quedara auditado.")) {
            return;
        }
        const datos = new URLSearchParams();
        datos.append("id_garantia_politica", String(id));
        datos.append("estatus", estatus);
        enviarGuardado(ENDPOINT_POLITICA_ESTATUS, datos)
            .then(function (respuesta) {
                if (respuesta.error) {
                    window.alert(respuesta.mensaje || "No fue posible cambiar estatus");
                    return;
                }
                cargarPoliticas();
                cargarReglas();
                cargarCobertura();
            })
            .catch(function (error) {
                window.alert(error.message);
            });
    }

    /**
     * ERP Garantias - Politicas
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Cambia estatus de regla con baja logica y refresco de cobertura.
     */
    function cambiarEstatusRegla(boton) {
        const id = numero(boton.getAttribute("data-id"));
        const estatus = boton.getAttribute("data-estatus") || "";
        if (id <= 0 || !estatus) {
            return;
        }
        if (!confirmarGuardadoReal("Se " + (estatus === "activa" ? "reactivara" : "desactivara") + " la regla. El cambio quedara auditado.")) {
            return;
        }
        const datos = new URLSearchParams();
        datos.append("id_regla_garantia", String(id));
        datos.append("estatus", estatus);
        enviarGuardado(ENDPOINT_REGLA_ESTATUS, datos)
            .then(function (respuesta) {
                if (respuesta.error) {
                    window.alert(respuesta.mensaje || "No fue posible cambiar estatus");
                    return;
                }
                cargarReglas();
                cargarCobertura();
            })
            .catch(function (error) {
                window.alert(error.message);
            });
    }

    function consultarJson(url) {
        return fetch(url, {
            method: "GET",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json"
            }
        }).then(function (respuesta) {
            if (!respuesta.ok) {
                throw new Error("No fue posible consultar Garantias");
            }
            return respuesta.json();
        });
    }

    function renderPoliticas(politicas) {
        const body = document.getElementById("garantias_politicas_body");
        if (!body) {
            return;
        }
        if (!politicas.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-10">Sin politicas configuradas</td></tr>';
            return;
        }
        body.innerHTML = politicas.map(function (politica) {
            const puedePoliticas = valor("garantias_puede_politicas") === "1";
            const siguienteEstatus = politica.estatus === "activa" ? "inactiva" : "activa";
            const iconoEstatus = politica.estatus === "activa" ? "bi-pause-circle" : "bi-play-circle";
            const accion = puedePoliticas
                ? '<button type="button" class="btn btn-sm btn-light-primary garantias-cargar-politica ms-1" data-id="' + numero(politica.id_garantia_politica) + '" title="Editar"><i class="bi bi-pencil-square"></i></button>' +
                  '<button type="button" class="btn btn-sm btn-light-warning garantias-estatus-politica ms-1" data-id="' + numero(politica.id_garantia_politica) + '" data-estatus="' + escapeHtml(siguienteEstatus) + '" title="' + escapeHtml(siguienteEstatus === "activa" ? "Reactivar" : "Desactivar") + '"><i class="bi ' + iconoEstatus + '"></i></button>'
                : '';
            return '' +
                '<tr>' +
                    '<td><div class="fw-bold text-gray-800">' + escapeHtml(politica.codigo) + '</div><div class="text-muted fs-7">' + escapeHtml(politica.nombre) + '</div></td>' +
                    '<td><span class="badge badge-light-primary">' + escapeHtml(politica.tipo_garantia) + '</span></td>' +
                    '<td>' + escapeHtml(politica.duracion_valor) + ' ' + escapeHtml(politica.unidad_duracion) + '</td>' +
                    '<td>' + renderRequisitos(politica) + '</td>' +
                    '<td><span class="badge badge-light">' + numero(politica.reglas_activas) + '</span></td>' +
                    '<td><span class="' + claseEstatus(politica.estatus) + '">' + escapeHtml(politica.estatus) + '</span> ' + accion + '</td>' +
                '</tr>';
        }).join("");
    }

    function renderOpcionesPoliticas(politicas) {
        const select = document.getElementById("garantias_dryrun_politica");
        if (!select) {
            return;
        }
        const activas = politicas.filter(function (politica) {
            return politica.estatus === "activa";
        });
        select.innerHTML = activas.map(function (politica) {
            return '<option value="' + numero(politica.id_garantia_politica) + '">' + escapeHtml(politica.codigo) + '</option>';
        }).join("");
        const tienda7 = activas.find(function (politica) {
            return politica.codigo === "GAR_TIENDA_7_DIAS_CAMBIO";
        });
        if (tienda7) {
            select.value = String(tienda7.id_garantia_politica);
        }
    }

    function renderReglas(reglas) {
        const body = document.getElementById("garantias_reglas_body");
        if (!body) {
            return;
        }
        if (!reglas.length) {
            body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-10">Sin reglas para los filtros seleccionados</td></tr>';
            return;
        }
        body.innerHTML = reglas.map(function (regla) {
            const puedePoliticas = valor("garantias_puede_politicas") === "1";
            const siguienteEstatus = regla.estatus === "activa" ? "inactiva" : "activa";
            const iconoEstatus = regla.estatus === "activa" ? "bi-pause-circle" : "bi-play-circle";
            const accion = puedePoliticas
                ? '<button type="button" class="btn btn-sm btn-light-primary garantias-cargar-regla ms-2" data-id="' + numero(regla.id_regla_garantia) + '" title="Editar"><i class="bi bi-pencil-square"></i></button>' +
                  '<button type="button" class="btn btn-sm btn-light-warning garantias-estatus-regla ms-1" data-id="' + numero(regla.id_regla_garantia) + '" data-estatus="' + escapeHtml(siguienteEstatus) + '" title="' + escapeHtml(siguienteEstatus === "activa" ? "Reactivar" : "Desactivar") + '"><i class="bi ' + iconoEstatus + '"></i></button>'
                : '';
            const referencia = regla.referencia_nombre || ("ID " + regla.id_referencia);
            const vigencia = (regla.vigencia_desde || "") || (regla.vigencia_hasta || "")
                ? escapeHtml(regla.vigencia_desde || "Inicio") + " / " + escapeHtml(regla.vigencia_hasta || "Abierta")
                : "Abierta";
            return '' +
                '<tr>' +
                    '<td><div class="fw-bold text-gray-800">' + escapeHtml(regla.codigo) + '</div><div class="text-muted fs-7">' + escapeHtml(regla.politica_nombre) + '</div></td>' +
                    '<td><span class="badge badge-light-info">' + escapeHtml(regla.ambito) + '</span></td>' +
                    '<td><div class="fw-semibold">' + escapeHtml(referencia) + '</div><div class="text-muted fs-7">ID ' + numero(regla.id_referencia) + '</div></td>' +
                    '<td>' + escapeHtml(regla.canal || "Todos") + '</td>' +
                    '<td>' + numero(regla.prioridad) + '</td>' +
                    '<td>' + vigencia + '</td>' +
                    '<td><span class="' + claseEstatus(regla.estatus) + '">' + escapeHtml(regla.estatus) + '</span>' + accion + '</td>' +
                '</tr>';
        }).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: resume reglas de garantia por ambito para acelerar revision operativa.
     * Impacto: Garantias UI; no escribe BD ni modifica reglas, solo cambia filtros visuales.
     */
    function renderResumenReglas(reglas) {
        const contenedor = document.getElementById("garantias_reglas_resumen");
        if (!contenedor) {
            return;
        }
        const ambitos = ["sku", "producto", "categoria", "marca", "proveedor"];
        const total = (reglas || []).length;
        const tarjetas = [['', 'Todas', total, 'badge-light-primary']].concat(ambitos.map(function (ambito) {
            return [ambito, etiquetaAmbito(ambito), reglas.filter(function (regla) { return regla.ambito === ambito; }).length, 'badge-light-info'];
        }));
        contenedor.innerHTML = tarjetas.map(function (tarjeta) {
            return '<button type="button" class="btn btn-sm ' + tarjeta[3] + '" data-garantia-filtro-ambito="' + escapeHtml(tarjeta[0]) + '">' +
                escapeHtml(tarjeta[1]) + ' <span class="badge badge-circle badge-light ms-2">' + numero(tarjeta[2]) + '</span></button>';
        }).join("");
    }

    function renderCobertura(depurar) {
        const resumen = depurar.resumen || {};
        const ejemplos = Array.isArray(depurar.ejemplos_sin_regla) ? depurar.ejemplos_sin_regla : [];
        const total = numero(resumen.total_skus_activos);
        const conRegla = numero(resumen.skus_con_regla);
        const sinRegla = numero(resumen.skus_sin_regla);
        const porcentaje = total > 0 ? Math.round((conRegla / total) * 100) : 0;
        setText("garantias_skus_sin_regla", sinRegla);
        setText("garantias_cobertura_resumen", porcentaje + "% cubierto. " + total + " SKUs activos; " + conRegla + " con regla aplicable; " + sinRegla + " sin regla aplicable.");

        const contenedor = document.getElementById("garantias_cobertura_ejemplos");
        if (!contenedor) {
            return;
        }
        if (!ejemplos.length) {
            contenedor.innerHTML = '<span class="badge badge-light-success">Cobertura completa</span>';
            return;
        }
        contenedor.innerHTML = '<span class="badge badge-light-warning">Pendientes ' + sinRegla + '</span>' + ejemplos.slice(0, 8).map(function (sku) {
            return '<span class="badge badge-light-warning">' + escapeHtml(sku.sku || ("ID " + sku.id_sku)) + '</span>';
        }).join("");
    }

    function renderReglaDryRun(respuesta) {
        const resultado = document.getElementById("garantias_dryrun_regla_resultado");
        if (!resultado) {
            return;
        }
        const depurar = (respuesta || {}).depurar || {};
        const bloqueos = Array.isArray(depurar.bloqueos) ? depurar.bloqueos : [];
        const normalizado = depurar.normalizado || {};
        if (bloqueos.length) {
            resultado.innerHTML = '<div class="alert alert-warning mb-0">' +
                '<div class="fw-bold mb-2">Regla bloqueada</div>' +
                bloqueos.map(function (bloqueo) {
                    return '<div>' + escapeHtml(bloqueo) + '</div>';
                }).join("") +
                '</div>';
            return;
        }
        const politica = politicasDisponibles.find(function (item) {
            return numero(item.id_garantia_politica) === numero(normalizado.id_garantia_politica);
        }) || {};
        resultado.innerHTML = '<div class="alert alert-success mb-0">' +
            '<div class="fw-bold mb-2">Regla valida en dry-run</div>' +
            '<div>Politica: ' + escapeHtml(politica.codigo || normalizado.id_garantia_politica) + '</div>' +
            '<div>Ambito: ' + escapeHtml(normalizado.ambito) + ' / Referencia ID ' + numero(normalizado.id_referencia) + '</div>' +
            '<div>Prioridad: ' + numero(normalizado.prioridad) + '</div>' +
            '<div class="text-muted fs-7 mt-2">No se guardo ninguna regla.</div>' +
            '</div>';
    }

    function renderImpactoRegla(respuesta) {
        const resultado = document.getElementById("garantias_dryrun_regla_resultado");
        if (!resultado) {
            return;
        }
        const depurar = (respuesta || {}).depurar || {};
        const bloqueos = Array.isArray(depurar.bloqueos) ? depurar.bloqueos : [];
        if (bloqueos.length) {
            resultado.innerHTML = '<div class="alert alert-warning mb-0">' +
                '<div class="fw-bold mb-2">Vista previa bloqueada</div>' +
                bloqueos.map(function (bloqueo) {
                    return '<div>' + escapeHtml(bloqueo) + '</div>';
                }).join("") +
                '</div>';
            return;
        }

        const impacto = depurar.impacto || {};
        const ejemplos = Array.isArray(impacto.ejemplos) ? impacto.ejemplos : [];
        const existentes = Array.isArray(impacto.reglas_existentes_mismo_ambito) ? impacto.reglas_existentes_mismo_ambito : [];
        const advertencias = Array.isArray(impacto.advertencias) ? impacto.advertencias : [];
        const claseImpacto = claseAlertaImpacto(impacto, existentes, advertencias);
        resultado.innerHTML = '<div class="alert ' + claseImpacto + ' mb-0">' +
            '<div class="fw-bold mb-2">Vista previa de impacto</div>' +
            '<div class="row g-3 mb-3">' +
                '<div class="col-md-4"><div class="text-muted fs-7">SKUs afectados</div><div class="fs-4 fw-bold">' + numero(impacto.skus_afectados) + '</div></div>' +
                '<div class="col-md-4"><div class="text-muted fs-7">Ya tenian regla</div><div class="fs-4 fw-bold">' + numero(impacto.skus_con_alguna_regla_actual) + '</div></div>' +
                '<div class="col-md-4"><div class="text-muted fs-7">Sin regla actual</div><div class="fs-4 fw-bold">' + numero(impacto.skus_sin_regla_actual) + '</div></div>' +
            '</div>' +
            renderSemaforoImpacto(impacto, existentes, advertencias) +
            renderListaImpacto("Ejemplos", ejemplos.map(function (sku) { return sku.sku + " - " + (sku.sku_nombre || sku.producto || ""); })) +
            renderListaImpacto("Reglas existentes mismo ambito", existentes.map(function (regla) { return regla.codigo + " / prioridad " + regla.prioridad + " / " + (regla.canal || "todos"); })) +
            renderListaImpacto("Advertencias", advertencias) +
            '<div class="text-muted fs-7 mt-3">No se guardo ninguna regla.</div>' +
            '</div>';
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: clasifica visualmente el riesgo de una regla antes de guardarla.
     * Impacto: Garantias UI; solo cambia presentacion del dry-run, sin persistencia.
     */
    function claseAlertaImpacto(impacto, existentes, advertencias) {
        if ((advertencias || []).length || numero(impacto.skus_con_alguna_regla_actual) > 0 || (existentes || []).length) {
            return "alert-warning";
        }
        if (numero(impacto.skus_afectados) > 50) {
            return "alert-primary";
        }
        return "alert-info";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: muestra señales de revision obligada para reglas de amplio alcance o con solapamiento.
     * Impacto: Garantias UI; ayuda a decidir sin guardar datos.
     */
    function renderSemaforoImpacto(impacto, existentes, advertencias) {
        const senales = [];
        if (numero(impacto.skus_afectados) > 50) {
            senales.push("Regla de alcance amplio");
        }
        if (numero(impacto.skus_con_alguna_regla_actual) > 0 || (existentes || []).length) {
            senales.push("Hay reglas existentes que revisar");
        }
        if ((advertencias || []).length) {
            senales.push("Tiene advertencias");
        }
        if (!senales.length) {
            senales.push("Impacto bajo segun dry-run");
        }
        return '<div class="d-flex flex-wrap gap-2 mb-3">' + senales.map(function (senal) {
            return '<span class="badge badge-light">' + escapeHtml(senal) + '</span>';
        }).join("") + '</div>';
    }

    function renderListaImpacto(titulo, items) {
        if (!Array.isArray(items) || !items.length) {
            return "";
        }
        return '<div class="mb-2"><div class="fw-semibold mb-1">' + escapeHtml(titulo) + '</div>' +
            items.slice(0, 8).map(function (item) {
                return '<span class="badge badge-light me-1 mb-1">' + escapeHtml(item) + '</span>';
            }).join("") +
            '</div>';
    }

    function renderPoliticaDryRun(respuesta) {
        const resultado = document.getElementById("garantias_politica_resultado");
        if (!resultado) {
            return;
        }
        const depurar = (respuesta || {}).depurar || {};
        const bloqueos = Array.isArray(depurar.bloqueos) ? depurar.bloqueos : [];
        const normalizado = depurar.normalizado || {};
        if (bloqueos.length) {
            resultado.innerHTML = '<div class="alert alert-warning mb-0">' +
                '<div class="fw-bold mb-2">Politica bloqueada</div>' +
                bloqueos.map(function (bloqueo) {
                    return '<div>' + escapeHtml(bloqueo) + '</div>';
                }).join("") +
                '</div>';
            return;
        }
        resultado.innerHTML = '<div class="alert alert-success mb-0">' +
            '<div class="fw-bold mb-2">Politica valida en dry-run</div>' +
            '<div>Codigo: ' + escapeHtml(normalizado.codigo) + '</div>' +
            '<div>Tipo: ' + escapeHtml(normalizado.tipo_garantia) + '</div>' +
            '<div>Duracion: ' + numero(normalizado.duracion_valor) + ' ' + escapeHtml(normalizado.unidad_duracion) + '</div>' +
            '<div class="text-muted fs-7 mt-2">No se guardo ninguna politica.</div>' +
            '</div>';
    }

    function renderGuardadoPolitica(respuesta) {
        const resultado = document.getElementById("garantias_politica_resultado");
        if (!resultado) {
            return;
        }
        const depurar = (respuesta || {}).depurar || {};
        if (respuesta && respuesta.error) {
            resultado.innerHTML = '<div class="alert alert-warning mb-0">' +
                '<div class="fw-bold mb-2">' + escapeHtml(respuesta.mensaje || "No se guardo politica") + '</div>' +
                renderDetalleGuardado(depurar) +
                '</div>';
            return;
        }
        resultado.innerHTML = '<div class="alert alert-success mb-0">' +
            '<div class="fw-bold mb-2">' + escapeHtml((respuesta || {}).mensaje || "Politica guardada") + '</div>' +
            '<div>ID politica: ' + numero(depurar.id_garantia_politica) + '</div>' +
            '</div>';
    }

    function renderGuardadoRegla(respuesta) {
        const resultado = document.getElementById("garantias_dryrun_regla_resultado");
        if (!resultado) {
            return;
        }
        const depurar = (respuesta || {}).depurar || {};
        if (respuesta && respuesta.error) {
            resultado.innerHTML = '<div class="alert alert-warning mb-0">' +
                '<div class="fw-bold mb-2">' + escapeHtml(respuesta.mensaje || "No se guardo regla") + '</div>' +
                renderDetalleGuardado(depurar) +
                '</div>';
            return;
        }
        resultado.innerHTML = '<div class="alert alert-success mb-0">' +
            '<div class="fw-bold mb-2">' + escapeHtml((respuesta || {}).mensaje || "Regla guardada") + '</div>' +
            '<div>ID regla: ' + numero(depurar.id_regla_garantia) + '</div>' +
            '</div>';
    }

    function renderDetalleGuardado(depurar) {
        const bloqueos = Array.isArray((depurar || {}).bloqueos) ? depurar.bloqueos : [];
        const partes = [];
        bloqueos.forEach(function (bloqueo) { partes.push(bloqueo); });
        if (!partes.length) {
            return "";
        }
        return '<div class="text-muted fs-7">' + partes.map(escapeHtml).join("<br>") + '</div>';
    }

    function datosPoliticaFormulario() {
        const datos = new URLSearchParams();
        datos.append("id_garantia_politica", valor("garantias_politica_id"));
        datos.append("codigo", valor("garantias_politica_codigo"));
        datos.append("nombre", valor("garantias_politica_nombre"));
        datos.append("descripcion", valor("garantias_politica_descripcion"));
        datos.append("tipo_garantia", valor("garantias_politica_tipo"));
        datos.append("duracion_valor", valor("garantias_politica_duracion"));
        datos.append("unidad_duracion", valor("garantias_politica_unidad"));
        datos.append("requiere_ticket", checked("garantias_req_ticket"));
        datos.append("requiere_cliente", checked("garantias_req_cliente"));
        datos.append("requiere_serie", checked("garantias_req_serie"));
        datos.append("requiere_lote", checked("garantias_req_lote"));
        datos.append("requiere_empaque", checked("garantias_req_empaque"));
        datos.append("requiere_diagnostico", checked("garantias_req_diagnostico"));
        datos.append("requiere_fotos", checked("garantias_req_fotos"));
        datos.append("requiere_autorizacion_supervisor", checked("garantias_req_autorizacion"));
        datos.append("requiere_validacion_proveedor", checked("garantias_req_proveedor"));
        datos.append("permite_cambio", checked("garantias_res_cambio"));
        datos.append("permite_reparacion", checked("garantias_res_reparacion"));
        datos.append("permite_devolucion_dinero", checked("garantias_res_devolucion"));
        datos.append("permite_nota_credito", checked("garantias_res_nota"));
        datos.append("permite_envio_proveedor", checked("garantias_res_proveedor"));
        return datos;
    }

    function datosReglaFormulario() {
        const datos = new URLSearchParams();
        datos.append("id_regla_garantia", valor("garantias_regla_id"));
        datos.append("id_garantia_politica", valor("garantias_dryrun_politica"));
        datos.append("ambito", valor("garantias_dryrun_ambito"));
        datos.append("id_referencia", valor("garantias_dryrun_referencia"));
        datos.append("prioridad", valor("garantias_dryrun_prioridad") || "100");
        datos.append("canal", valor("garantias_dryrun_canal"));
        return datos;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: genera una firma estable de la regla capturada para saber si fue validada antes de guardar.
     * Impacto: Garantias UI; previene guardado accidental de una regla modificada despues del dry-run.
     */
    function firmaDatosRegla(datos) {
        const partes = [];
        datos.forEach(function (value, key) {
            partes.push(key + "=" + value);
        });
        return partes.sort().join("&");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: conserva la firma de una regla solo cuando el dry-run no tiene bloqueos.
     * Impacto: Garantias UI; exige revisar la configuracion vigente antes de guardarla.
     */
    function registrarRevisionRegla(respuesta, datos) {
        const depurar = (respuesta || {}).depurar || {};
        const bloqueos = Array.isArray(depurar.bloqueos) ? depurar.bloqueos : [];
        firmaRevisionRegla = respuesta && respuesta.error === false && !bloqueos.length ? firmaDatosRegla(datos) : "";
    }

    function invalidarRevisionRegla() {
        firmaRevisionRegla = "";
    }

    function revisionReglaVigente(datos) {
        return firmaRevisionRegla !== "" && firmaRevisionRegla === firmaDatosRegla(datos);
    }

    function confirmarGuardadoReal(mensaje) {
        return window.confirm(mensaje);
    }

    function cargarPoliticaEnFormulario(boton) {
        const id = numero(boton.getAttribute("data-id"));
        const politica = politicasDisponibles.find(function (item) {
            return numero(item.id_garantia_politica) === id;
        });
        if (!politica) {
            return;
        }
        setValue("garantias_politica_id", numero(politica.id_garantia_politica));
        setValue("garantias_politica_codigo", politica.codigo || "");
        setValue("garantias_politica_nombre", politica.nombre || "");
        setValue("garantias_politica_descripcion", politica.descripcion || "");
        setValue("garantias_politica_tipo", politica.tipo_garantia || "sin_garantia");
        setValue("garantias_politica_duracion", numero(politica.duracion_valor));
        setValue("garantias_politica_unidad", politica.unidad_duracion || "dias");
        setChecked("garantias_req_ticket", numero(politica.requiere_ticket) === 1);
        setChecked("garantias_req_cliente", numero(politica.requiere_cliente) === 1);
        setChecked("garantias_req_serie", numero(politica.requiere_serie) === 1);
        setChecked("garantias_req_lote", numero(politica.requiere_lote) === 1);
        setChecked("garantias_req_empaque", numero(politica.requiere_empaque) === 1);
        setChecked("garantias_req_diagnostico", numero(politica.requiere_diagnostico) === 1);
        setChecked("garantias_req_fotos", numero(politica.requiere_fotos) === 1);
        setChecked("garantias_req_autorizacion", numero(politica.requiere_autorizacion_supervisor) === 1);
        setChecked("garantias_req_proveedor", numero(politica.requiere_validacion_proveedor) === 1);
        setChecked("garantias_res_cambio", numero(politica.permite_cambio) === 1);
        setChecked("garantias_res_reparacion", numero(politica.permite_reparacion) === 1);
        setChecked("garantias_res_devolucion", numero(politica.permite_devolucion_dinero) === 1);
        setChecked("garantias_res_nota", numero(politica.permite_nota_credito) === 1);
        setChecked("garantias_res_proveedor", numero(politica.permite_envio_proveedor) === 1);
        actualizarAyudaTipoPolitica();
        const resultado = document.getElementById("garantias_politica_resultado");
        if (resultado) {
            resultado.innerHTML = '<span class="text-muted">Politica cargada como base.</span>';
        }
    }

    /**
     * ERP Garantias - Politicas
     * Documentacion IA: Codex GPT-5
     * Version: 1.0
     * Carga una regla existente para editarla con el mismo guardado auditado.
     */
    function cargarReglaEnFormulario(boton) {
        const id = numero(boton.getAttribute("data-id"));
        const regla = reglasDisponibles.find(function (item) {
            return numero(item.id_regla_garantia) === id;
        });
        if (!regla) {
            return;
        }
        setValue("garantias_regla_id", numero(regla.id_regla_garantia));
        setValue("garantias_dryrun_politica", numero(regla.id_garantia_politica));
        setValue("garantias_dryrun_ambito", regla.ambito || "sku");
        setValue("garantias_dryrun_referencia", numero(regla.id_referencia));
        setValue("garantias_dryrun_prioridad", numero(regla.prioridad) || 100);
        setValue("garantias_dryrun_canal", regla.canal || "");
        setValue("garantias_referencia_busqueda", "");
        setText("garantias_referencia_seleccionada", "Referencia cargada: ID " + numero(regla.id_referencia) + " - " + (regla.referencia_nombre || regla.ambito || ""));
        const resultados = document.getElementById("garantias_referencia_resultados");
        if (resultados) {
            resultados.innerHTML = "";
        }
        const resultado = document.getElementById("garantias_dryrun_regla_resultado");
        if (resultado) {
            resultado.innerHTML = '<span class="text-muted">Regla cargada para edicion. Usa Validar o Impacto antes de guardar.</span>';
        }
        invalidarRevisionRegla();
    }

    function limpiarFormularioPolitica() {
        setValue("garantias_politica_id", "");
        setValue("garantias_regla_id", "");
        setValue("garantias_politica_codigo", "");
        setValue("garantias_politica_nombre", "");
        setValue("garantias_politica_descripcion", "");
        setValue("garantias_politica_tipo", "garantia_tienda");
        setValue("garantias_politica_duracion", "7");
        setValue("garantias_politica_unidad", "dias");
        ["garantias_req_cliente", "garantias_req_serie", "garantias_req_lote", "garantias_req_empaque", "garantias_req_diagnostico", "garantias_req_fotos", "garantias_req_autorizacion", "garantias_req_proveedor", "garantias_res_reparacion", "garantias_res_devolucion", "garantias_res_nota", "garantias_res_proveedor"].forEach(function (id) {
            setChecked(id, false);
        });
        setChecked("garantias_req_ticket", true);
        setChecked("garantias_res_cambio", true);
        actualizarAyudaTipoPolitica();
        const resultado = document.getElementById("garantias_politica_resultado");
        if (resultado) {
            resultado.innerHTML = '<span class="text-muted">Sin validacion ejecutada.</span>';
        }
        setText("garantias_referencia_seleccionada", "Referencia actual: ID " + (valor("garantias_dryrun_referencia") || "sin seleccionar"));
    }

    function renderResolver(respuesta) {
        const depurar = (respuesta || {}).depurar || {};
        const politica = depurar.politica || {};
        const regla = depurar.regla || {};
        const snapshot = depurar.snapshot_sugerido || {};
        setText("garantias_estado_resolver", respuesta && respuesta.error === false ? "Resuelto" : "Advertencia");
        renderResultado('' +
            '<div class="mb-3">' +
                '<div class="text-muted fs-7">Politica</div>' +
                '<div class="fw-bold text-gray-800">' + escapeHtml(politica.codigo || "SIN_GARANTIA") + '</div>' +
                '<div class="text-muted">' + escapeHtml(politica.nombre || "") + '</div>' +
            '</div>' +
            '<div class="row g-3">' +
                '<div class="col-6"><div class="text-muted fs-7">Origen</div><div class="fw-semibold">' + escapeHtml(regla.origen || regla.ambito || "sin regla") + '</div></div>' +
                '<div class="col-6"><div class="text-muted fs-7">Vence</div><div class="fw-semibold">' + escapeHtml(snapshot.fecha_vencimiento || "Sin vencimiento") + '</div></div>' +
            '</div>' +
            renderAlertas(depurar.alertas || [])
        );
    }

    function renderSnapshot(respuesta) {
        const depurar = (respuesta || {}).depurar || {};
        const snapshots = depurar.snapshots || [];
        const primero = snapshots.length ? ((snapshots[0].resultado || {}).depurar || {}) : {};
        const politica = primero.politica || {};
        const sugerido = primero.snapshot_sugerido || {};
        setText("garantias_estado_resolver", depurar.bloqueos && depurar.bloqueos.length ? "Bloqueado" : "Snapshot OK");
        renderResultado('' +
            '<div class="mb-3">' +
                '<div class="text-muted fs-7">Snapshot dry-run</div>' +
                '<div class="fw-bold text-gray-800">' + escapeHtml(politica.codigo || "SIN_GARANTIA") + '</div>' +
                '<div class="text-muted">' + escapeHtml(sugerido.resumen_ticket || "") + '</div>' +
            '</div>' +
            '<div class="row g-3">' +
                '<div class="col-6"><div class="text-muted fs-7">Inicio</div><div class="fw-semibold">' + escapeHtml(sugerido.fecha_inicio || "") + '</div></div>' +
                '<div class="col-6"><div class="text-muted fs-7">Vence</div><div class="fw-semibold">' + escapeHtml(sugerido.fecha_vencimiento || "Sin vencimiento") + '</div></div>' +
            '</div>' +
            renderAlertas(depurar.bloqueos || [])
        );
    }

    function renderRequisitos(politica) {
        const tags = [];
        if (numero(politica.requiere_ticket) === 1) tags.push("Ticket");
        if (numero(politica.requiere_cliente) === 1) tags.push("Cliente");
        if (numero(politica.requiere_serie) === 1) tags.push("Serie");
        if (numero(politica.requiere_lote) === 1) tags.push("Lote");
        if (numero(politica.requiere_empaque) === 1) tags.push("Empaque");
        if (numero(politica.requiere_diagnostico) === 1) tags.push("Diagnostico");
        if (numero(politica.requiere_fotos) === 1) tags.push("Fotos");
        if (!tags.length) {
            return '<span class="text-muted">Sin requisitos extra</span>';
        }
        return tags.map(function (tag) {
            return '<span class="badge badge-light me-1 mb-1">' + escapeHtml(tag) + '</span>';
        }).join("");
    }

    function renderAlertas(alertas) {
        if (!Array.isArray(alertas) || !alertas.length) {
            return '<div class="mt-4"><span class="badge badge-light-success">Sin alertas</span></div>';
        }
        return '<div class="mt-4">' + alertas.map(function (alerta) {
            return '<span class="badge badge-light-warning me-1 mb-1">' + escapeHtml(alerta) + '</span>';
        }).join("") + '</div>';
    }

    function renderResultado(html) {
        const contenedor = document.getElementById("garantias_resultado_prueba");
        if (contenedor) {
            contenedor.innerHTML = html;
        }
    }

    function renderError(idBody, columnas, mensaje) {
        const body = document.getElementById(idBody);
        if (body) {
            body.innerHTML = '<tr><td colspan="' + columnas + '" class="text-center text-danger py-10">' + escapeHtml(mensaje || "Error al consultar") + '</td></tr>';
        }
    }

    function claseEstatus(estatus) {
        return estatus === "activa" ? "badge badge-light-success" : "badge badge-light";
    }

    function etiquetaAmbito(ambito) {
        const etiquetas = {
            sku: "SKU",
            producto: "Producto",
            categoria: "Categoria",
            marca: "Marca",
            proveedor: "Proveedor"
        };
        return etiquetas[ambito] || "Referencia";
    }

    function valor(id) {
        const elemento = document.getElementById(id);
        return elemento ? String(elemento.value || "").trim() : "";
    }

    function setText(id, texto) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = String(texto);
        }
    }

    function setValue(id, texto) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.value = String(texto === null || texto === undefined ? "" : texto);
        }
    }

    function checked(id) {
        const elemento = document.getElementById(id);
        return elemento && elemento.checked ? "1" : "0";
    }

    function setChecked(id, activo) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.checked = Boolean(activo);
        }
    }

    function numero(valorEntrada) {
        const parsed = Number.parseInt(valorEntrada, 10);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function escapeHtml(valorEntrada) {
        return String(valorEntrada === null || valorEntrada === undefined ? "" : valorEntrada)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
})();
