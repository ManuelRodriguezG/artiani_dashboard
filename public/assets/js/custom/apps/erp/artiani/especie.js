/**
 * IA: Codex GPT-5 | Fecha: 2026-09-29
 * Proposito: renderizar una ficha independiente de especie Artiani.
 * Impacto: UI Conocimiento/Capacitacion; muestra detalle completo sin cargar el listado.
 * Contrato: read-only; consulta por slug desde `/artiani/especie_consultar_erp`.
 */
(function () {
    const ENDPOINT_CATALOGOS = "/artiani/catalogos_erp";
    const ENDPOINT_CONSULTAR = "/artiani/especie_consultar_erp";
    let catalogos = { grupos: {}, dificultades: {} };

    document.addEventListener("DOMContentLoaded", function () {
        cargarCatalogos().then(cargarFicha);
    });

    function cargarCatalogos() {
        return consultarJson(ENDPOINT_CATALOGOS).then(function (respuesta) {
            catalogos = respuesta.depurar || catalogos;
        }).catch(function () {});
    }

    function cargarFicha() {
        const slug = valor("artiani_ficha_slug");
        if (!slug) {
            renderError("No se recibio la ficha solicitada.");
            return;
        }
        consultarJson(ENDPOINT_CONSULTAR + "?slug=" + encodeURIComponent(slug)).then(function (respuesta) {
            const datos = respuesta.depurar || {};
            renderDetalle(datos.especie || {}, datos.productos_relacionados_pendientes || [], datos.productos_catalogo_candidatos || {}, datos.habitats_catalogo_analisis || {});
        }).catch(function (error) {
            renderError(error.message);
        });
    }

    function renderDetalle(especie, productosPendientes, productosCatalogo, habitatsCatalogo) {
        if (!especie.slug) {
            renderError("No se encontro la especie solicitada.");
            return;
        }
        setText("artiani_ficha_titulo", especie.nombre || "Ficha de especie");
        setText("artiani_ficha_subtitulo", especie.resumen || "Detalle operativo Artiani");
        const contenedor = document.getElementById("artiani_ficha_contenido");
        contenedor.innerHTML =
            '<div class="d-flex flex-stack gap-4 mb-6">' +
                '<div>' +
                    '<h2 class="fs-2 fw-bold mb-2">' + escapeHtml(especie.nombre) + '</h2>' +
                    '<div class="text-gray-700">' + escapeHtml(especie.resumen) + '</div>' +
                '</div>' +
                '<div class="text-end">' +
                    '<span class="badge badge-light-primary d-block mb-2">' + escapeHtml(nombreCatalogo(catalogos.grupos, especie.grupo)) + '</span>' +
                    '<span class="badge badge-light">' + escapeHtml(nombreCatalogo(catalogos.dificultades, especie.dificultad)) + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="border border-gray-200 rounded p-4 mb-6"><div class="fw-semibold mb-2">Perfil de cliente</div><div class="text-gray-700">' + escapeHtml(especie.perfil_cliente || "") + '</div></div>' +
            '<div class="row g-4 mb-6">' +
                bloque("Habitat", especie.habitat || [], "bi-house-heart") +
                bloque("Alimentacion", especie.alimentacion || [], "bi-cup-hot") +
                bloque("Cuidados", especie.cuidados || [], "bi-clipboard-check") +
                bloque("Preguntas clave", especie.preguntas_clave || [], "bi-question-circle") +
            '</div>' +
            renderEspecificaciones(especie.especificaciones || {}) +
            renderCriterioComercial(especie.criterio_comercial || {}) +
            renderHabitatsCatalogo(habitatsCatalogo) +
            renderVariantes(especie.variantes || []) +
            renderComparativa(especie.comparativa || []) +
            '<div class="border border-warning rounded p-5 mb-6">' +
                '<h3 class="fs-6 fw-bold mb-3"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Alertas de venta responsable</h3>' +
                renderListaSimple(especie.alertas || [], "warning") +
            '</div>' +
            renderProductosPorNecesidad(especie.productos_por_necesidad || []) +
            renderCandidatosCatalogo(productosCatalogo) +
            '<div class="border border-gray-200 rounded p-5">' +
                '<div class="d-flex flex-stack mb-3">' +
                    '<h3 class="fs-6 fw-bold mb-0">Productos relacionados por vincular</h3>' +
                    '<span class="badge badge-light-info">' + productosPendientes.length + ' pendientes</span>' +
                '</div>' +
                '<div class="d-flex flex-wrap gap-2">' + productosPendientes.map(function (item) {
                    return '<span class="badge badge-light-info">' + escapeHtml(item.necesidad) + '</span>';
                }).join("") + '</div>' +
            '</div>';
    }

    function renderHabitatsCatalogo(habitatsCatalogo) {
        const productos = habitatsCatalogo.productos || [];
        if (!habitatsCatalogo.disponible || !productos.length) {
            return "";
        }
        return '<div class="border border-gray-300 rounded p-5 mb-6">' +
            '<div class="d-flex flex-stack mb-4">' +
                '<div>' +
                    '<h3 class="fs-5 fw-bold mb-1">Jaulas del catalogo Artiani</h3>' +
                    '<div class="text-muted">' + escapeHtml(habitatsCatalogo.regla || "Clasificacion comercial por medidas detectadas.") + '</div>' +
                '</div>' +
                '<span class="badge badge-light-warning">' + productos.length + ' productos</span>' +
            '</div>' +
            '<div class="table-responsive">' +
                '<table class="table table-row-dashed align-middle mb-0">' +
                    '<thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Producto</th><th>Medidas</th><th>Nivel</th><th>Sirio</th><th>Ruso/chino</th><th>Uso</th></tr></thead>' +
                    '<tbody>' + productos.map(function (item) {
                        return '<tr>' +
                            '<td><div class="fw-semibold">' + escapeHtml(item.nombre) + '</div><div class="text-muted fs-8">' + escapeHtml(item.sku) + ' | ' + escapeHtml(item.estatus_producto) + '/' + escapeHtml(item.estatus_sku) + '</div></td>' +
                            '<td>' + escapeHtml(textoDimensiones(item)) + '</td>' +
                            '<td>' + badgeNivel(item.nivel) + '</td>' +
                            '<td>' + escapeHtml(item.sirio) + '</td>' +
                            '<td>' + escapeHtml(item.ruso_chino) + '</td>' +
                            '<td>' + escapeHtml(item.uso_recomendado) + '</td>' +
                        '</tr>';
                    }).join("") + '</tbody>' +
                '</table>' +
            '</div>' +
        '</div>';
    }

    function textoDimensiones(item) {
        const d = item.dimensiones || {};
        if (d.largo_cm && d.ancho_cm && d.alto_cm) {
            return d.largo_cm + " x " + d.ancho_cm + " x " + d.alto_cm + " cm";
        }
        if (d.largo_cm && d.ancho_cm) {
            return d.largo_cm + " x " + d.ancho_cm + " cm";
        }
        if (d.texto) {
            return d.texto;
        }
        return "Sin medidas detectadas";
    }

    function badgeNivel(nivel) {
        const mapa = {
            principal_recomendado_comercial: "badge-light-success",
            principal_compacto: "badge-light-primary",
            compacto_ruso_chino: "badge-light-info",
            temporal_o_inicial: "badge-light-warning",
            solo_temporal_traslado: "badge-light-danger",
            revision_manual: "badge-light"
        };
        return '<span class="badge ' + (mapa[nivel] || "badge-light") + '">' + escapeHtml(etiqueta(nivel)) + '</span>';
    }

    function renderEspecificaciones(especificaciones) {
        if (!Object.keys(especificaciones).length) {
            return "";
        }
        return '<div class="mb-6">' +
            '<h3 class="fs-5 fw-bold mb-4">Especificaciones operativas</h3>' +
            '<div class="row g-4">' +
                renderEspecificacionHabitat(especificaciones.habitat || {}) +
                renderEspecificacionRueda(especificaciones.rueda || {}) +
                renderEspecificacionDental(especificaciones.dental || {}) +
                renderEspecificacionAlimento(especificaciones.alimento || {}) +
            '</div>' +
            renderSustratos(especificaciones.sustrato || []) +
        '</div>';
    }

    function renderCriterioComercial(criterio) {
        if (!Object.keys(criterio).length) {
            return "";
        }
        return '<div class="border border-gray-300 rounded p-5 mb-6">' +
            '<div class="d-flex flex-stack mb-4">' +
                '<div>' +
                    '<h3 class="fs-5 fw-bold mb-1">Criterio comercial Artiani</h3>' +
                    '<div class="text-muted">' + escapeHtml(criterio.objetivo || "") + '</div>' +
                '</div>' +
                '<span class="badge badge-light-primary">Venta responsable</span>' +
            '</div>' +
            renderTablaComercialHabitat(criterio.habitat || []) +
            renderTablaComercialSimple("Rueda y ejercicio", criterio.rueda_y_ejercicio || [], ["producto", "criterio", "sirio", "ruso_chino"]) +
            renderTablaComercialSimple("Sustratos comerciales", criterio.sustratos_comerciales || [], ["tipo", "prioridad", "argumento"]) +
            renderTablaComercialSimple("Alimentos comerciales", criterio.alimentos_comerciales || [], ["tipo", "criterio", "venta"]) +
            renderTablaComercialSimple("Bebederos y accesorios", criterio.bebederos_y_accesorios || [], ["producto", "uso"]) +
        '</div>';
    }

    function renderTablaComercialHabitat(items) {
        if (!items.length) {
            return "";
        }
        return '<div class="mb-5">' +
            '<h4 class="fs-6 fw-bold mb-3">Hábitat según lo comercial</h4>' +
            '<div class="table-responsive">' +
                '<table class="table table-row-dashed align-middle mb-0">' +
                    '<thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Nivel</th><th>Sirio</th><th>Ruso/chino</th><th>Uso</th></tr></thead>' +
                    '<tbody>' + items.map(function (item) {
                        return '<tr><td class="fw-semibold">' + escapeHtml(item.nivel) + '</td><td>' + escapeHtml(item.sirio) + '</td><td>' + escapeHtml(item.ruso_chino) + '</td><td>' + escapeHtml(item.uso) + '</td></tr>';
                    }).join("") + '</tbody>' +
                '</table>' +
            '</div>' +
        '</div>';
    }

    function renderTablaComercialSimple(titulo, items, campos) {
        if (!items.length) {
            return "";
        }
        return '<div class="mb-5">' +
            '<h4 class="fs-6 fw-bold mb-3">' + escapeHtml(titulo) + '</h4>' +
            '<div class="table-responsive">' +
                '<table class="table table-row-dashed align-middle mb-0">' +
                    '<thead><tr class="text-muted fw-bold fs-8 text-uppercase">' + campos.map(function (campo) {
                        return '<th>' + escapeHtml(etiqueta(campo)) + '</th>';
                    }).join("") + '</tr></thead>' +
                    '<tbody>' + items.map(function (item) {
                        return '<tr>' + campos.map(function (campo, index) {
                            return '<td' + (index === 0 ? ' class="fw-semibold"' : '') + '>' + escapeHtml(item[campo] || "") + '</td>';
                        }).join("") + '</tr>';
                    }).join("") + '</tbody>' +
                '</table>' +
            '</div>' +
        '</div>';
    }

    function renderEspecificacionHabitat(habitat) {
        if (!Object.keys(habitat).length) {
            return "";
        }
        return '<div class="col-lg-6"><div class="border border-gray-200 rounded p-5 h-100">' +
            '<h4 class="fs-6 fw-bold mb-3"><i class="bi bi-rulers text-primary me-2"></i>Habitat</h4>' +
            renderDefiniciones(habitat) +
        '</div></div>';
    }

    function renderEspecificacionRueda(rueda) {
        if (!Object.keys(rueda).length) {
            return "";
        }
        return '<div class="col-lg-6"><div class="border border-gray-200 rounded p-5 h-100">' +
            '<h4 class="fs-6 fw-bold mb-3"><i class="bi bi-disc text-primary me-2"></i>Rueda</h4>' +
            renderDefiniciones(rueda) +
        '</div></div>';
    }

    function renderEspecificacionDental(dental) {
        if (!Object.keys(dental).length) {
            return "";
        }
        return '<div class="col-lg-6"><div class="border border-gray-200 rounded p-5 h-100">' +
            '<h4 class="fs-6 fw-bold mb-3"><i class="bi bi-gem text-primary me-2"></i>Desgaste dental</h4>' +
            '<div class="text-gray-700 mb-3">' + escapeHtml(dental.regla || "") + '</div>' +
            '<div class="fw-semibold mb-2">Productos</div>' + renderBadges(dental.productos || [], "light-info") +
            '<div class="fw-semibold mt-4 mb-2">Alertas</div>' + renderListaSimple(dental.alertas || [], "warning") +
        '</div></div>';
    }

    function renderEspecificacionAlimento(alimento) {
        if (!Object.keys(alimento).length) {
            return "";
        }
        return '<div class="col-lg-6"><div class="border border-gray-200 rounded p-5 h-100">' +
            '<h4 class="fs-6 fw-bold mb-3"><i class="bi bi-basket text-primary me-2"></i>Alimento</h4>' +
            renderDefiniciones({
                base: alimento.base || "",
                proteina_fibra: alimento.proteina_fibra || "",
                premios: alimento.premios || ""
            }) +
            '<div class="fw-semibold mt-4 mb-2">Preguntas al cliente</div>' + renderListaSimple(alimento.preguntas || [], "primary") +
        '</div></div>';
    }

    function renderSustratos(sustratos) {
        if (!sustratos.length) {
            return "";
        }
        return '<div class="border border-gray-200 rounded p-5 mt-4">' +
            '<h4 class="fs-6 fw-bold mb-4"><i class="bi bi-layers text-primary me-2"></i>Sustratos que puede manejar Artiani</h4>' +
            '<div class="table-responsive">' +
                '<table class="table table-row-dashed align-middle mb-0">' +
                    '<thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Tipo</th><th>Uso</th><th>Sirio</th><th>Ruso/chino</th><th>Alerta</th></tr></thead>' +
                    '<tbody>' + sustratos.map(function (item) {
                        return '<tr>' +
                            '<td class="fw-semibold">' + escapeHtml(item.tipo) + '</td>' +
                            '<td>' + escapeHtml(item.uso) + '</td>' +
                            '<td>' + escapeHtml(item.sirio) + '</td>' +
                            '<td>' + escapeHtml(item.ruso_chino) + '</td>' +
                            '<td class="text-warning">' + escapeHtml(item.alerta) + '</td>' +
                        '</tr>';
                    }).join("") + '</tbody>' +
                '</table>' +
            '</div>' +
        '</div>';
    }

    function renderDefiniciones(objeto) {
        const claves = Object.keys(objeto || {}).filter(function (clave) {
            return String(objeto[clave] || "").trim() !== "";
        });
        if (!claves.length) {
            return '<div class="text-muted">Sin informacion</div>';
        }
        return '<div class="d-flex flex-column gap-3">' + claves.map(function (clave) {
            return '<div><div class="fw-semibold text-gray-800">' + escapeHtml(etiqueta(clave)) + '</div><div class="text-gray-700">' + escapeHtml(objeto[clave]) + '</div></div>';
        }).join("") + '</div>';
    }

    function renderVariantes(variantes) {
        if (!variantes.length) {
            return "";
        }
        return '<div class="mb-6">' +
            '<h3 class="fs-5 fw-bold mb-4">Separacion por tipo</h3>' +
            '<div class="row g-4">' + variantes.map(function (item) {
                return '<div class="col-lg-6">' +
                    '<div class="border border-gray-200 rounded p-5 h-100">' +
                        '<h4 class="fs-6 fw-bold mb-2">' + escapeHtml(item.nombre) + '</h4>' +
                        '<div class="text-muted mb-4">' + escapeHtml(item.tamano || "") + '</div>' +
                        '<div class="fw-semibold mb-2">Conviene cuidar</div>' +
                        renderListaSimple(item.habitat_especifico || [], "primary") +
                        '<div class="fw-semibold mt-4 mb-2">Productos favorables</div>' +
                        renderBadges(item.productos_favorables || [], "light-info") +
                        '<div class="fw-semibold mt-4 mb-2">Evitar</div>' +
                        renderListaSimple(item.evitar || [], "warning") +
                        '<div class="border border-gray-200 rounded p-3 mt-4 text-gray-700">' + escapeHtml(item.mensaje_venta || "") + '</div>' +
                    '</div>' +
                '</div>';
            }).join("") + '</div>' +
        '</div>';
    }

    function renderComparativa(comparativa) {
        if (!comparativa.length) {
            return "";
        }
        return '<div class="border border-gray-200 rounded p-5 mb-6">' +
            '<h3 class="fs-5 fw-bold mb-4">Comparacion rapida</h3>' +
            '<div class="table-responsive">' +
                '<table class="table table-row-dashed align-middle mb-0">' +
                    '<thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Criterio</th><th>Hámster sirio</th><th>Hámster ruso/chino</th></tr></thead>' +
                    '<tbody>' + comparativa.map(function (fila) {
                        return '<tr><td class="fw-semibold">' + escapeHtml(fila.criterio) + '</td><td>' + escapeHtml(fila.hamster_sirio) + '</td><td>' + escapeHtml(fila.hamster_ruso_chino) + '</td></tr>';
                    }).join("") + '</tbody>' +
                '</table>' +
            '</div>' +
        '</div>';
    }

    function renderProductosPorNecesidad(items) {
        if (!items.length) {
            return "";
        }
        return '<div class="border border-gray-200 rounded p-5 mb-6">' +
            '<h3 class="fs-5 fw-bold mb-4">Productos por necesidad</h3>' +
            '<div class="table-responsive">' +
                '<table class="table table-row-dashed align-middle mb-0">' +
                    '<thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Necesidad</th><th>Sirio</th><th>Ruso/chino</th></tr></thead>' +
                    '<tbody>' + items.map(function (item) {
                        return '<tr><td class="fw-semibold">' + escapeHtml(item.necesidad) + '</td><td>' + escapeHtml(item.sirio || "") + '</td><td>' + escapeHtml(item.ruso_chino || "") + '</td></tr>';
                    }).join("") + '</tbody>' +
                '</table>' +
            '</div>' +
        '</div>';
    }

    function renderCandidatosCatalogo(productosCatalogo) {
        const grupos = productosCatalogo.grupos || [];
        if (!productosCatalogo.disponible || !grupos.length) {
            return '<div class="border border-gray-200 rounded p-5 mb-6"><h3 class="fs-5 fw-bold mb-2">Candidatos del catalogo</h3><div class="text-muted">Catalogo ERP no disponible o sin candidatos detectados para esta ficha.</div></div>';
        }
        return '<div class="border border-gray-200 rounded p-5 mb-6">' +
            '<div class="d-flex flex-stack mb-4">' +
                '<h3 class="fs-5 fw-bold mb-0">Candidatos del catalogo</h3>' +
                '<span class="badge badge-light-warning">Requieren revision humana</span>' +
            '</div>' +
            '<div class="d-flex flex-column gap-5">' + grupos.map(function (grupo) {
                return '<div>' +
                    '<div class="fw-semibold mb-2">' + escapeHtml(grupo.necesidad) + '</div>' +
                    (grupo.candidatos || []).length ? '<div class="row g-3">' + grupo.candidatos.map(function (item) {
                        return '<div class="col-md-6 col-xl-4">' +
                            '<div class="border border-gray-200 rounded p-3 h-100">' +
                                '<div class="fw-bold text-gray-900">' + escapeHtml(item.nombre) + '</div>' +
                                '<div class="text-muted fs-8">' + escapeHtml(item.sku || item.codigo_producto || "") + '</div>' +
                                '<div class="d-flex flex-wrap gap-2 mt-3">' +
                                    '<span class="badge badge-light">' + escapeHtml(item.estatus_sku) + '</span>' +
                                    '<span class="badge badge-light-info">' + escapeHtml(item.keyword) + '</span>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                    }).join("") + '</div>' : '<div class="text-muted fs-8">Sin candidatos detectados; revisar Catalogo ERP.</div>' +
                '</div>';
            }).join("") + '</div>' +
        '</div>';
    }

    function bloque(titulo, items, icono) {
        return '<div class="col-md-6">' +
            '<div class="border border-gray-200 rounded p-4 h-100">' +
                '<h3 class="fs-6 fw-bold mb-3"><i class="bi ' + icono + ' text-primary me-2"></i>' + escapeHtml(titulo) + '</h3>' +
                renderListaSimple(items, "primary") +
            '</div>' +
        '</div>';
    }

    function renderListaSimple(items, tipo) {
        if (!items.length) {
            return '<div class="text-muted">Sin informacion</div>';
        }
        return '<div class="d-flex flex-column gap-2">' + items.map(function (item) {
            return '<div class="d-flex gap-2"><i class="bi bi-check2 text-' + tipo + '"></i><span>' + escapeHtml(item) + '</span></div>';
        }).join("") + '</div>';
    }

    function renderBadges(items, clase) {
        if (!items.length) {
            return '<div class="text-muted">Sin informacion</div>';
        }
        return '<div class="d-flex flex-wrap gap-2">' + items.map(function (item) {
            return '<span class="badge badge-' + clase + '">' + escapeHtml(item) + '</span>';
        }).join("") + '</div>';
    }

    function renderError(mensaje) {
        const contenedor = document.getElementById("artiani_ficha_contenido");
        if (contenedor) {
            contenedor.innerHTML = '<div class="text-danger">' + escapeHtml(mensaje) + '</div>';
        }
    }

    function consultarJson(url) {
        return fetch(url, { method: "GET", credentials: "same-origin", headers: { "Accept": "application/json" } })
            .then(function (respuesta) {
                if (!respuesta.ok) {
                    throw new Error("No fue posible consultar Artiani");
                }
                return respuesta.json();
            });
    }

    function nombreCatalogo(catalogo, clave) {
        return (catalogo || {})[clave] || etiqueta(clave);
    }

    function etiqueta(valor) {
        return String(valor || "").replace(/_/g, " ").replace(/\b\w/g, function (letra) {
            return letra.toUpperCase();
        });
    }

    function valor(id) {
        const elemento = document.getElementById(id);
        return elemento ? String(elemento.value || "").trim() : "";
    }

    function setText(id, texto) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = String(texto || "");
        }
    }

    function escapeHtml(valor) {
        return String(valor == null ? "" : valor)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
})();
