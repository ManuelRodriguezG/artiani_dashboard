<?php

class RentabilidadErp extends CRUD {

    public function escenariosBase() {
        return $this->respuesta(false, "success", "Escenarios base consultados", array(
            "escenarios" => array(
                array("clave" => "menudeo", "nombre" => "Menudeo", "descuento_pct" => 0, "gasto_pct" => 18, "comision_pct" => 0, "margen_objetivo_pct" => 25),
                array("clave" => "mayoreo", "nombre" => "Mayoreo", "descuento_pct" => 12, "gasto_pct" => 10, "comision_pct" => 0, "margen_objetivo_pct" => 18),
                array("clave" => "alianza", "nombre" => "Alianza", "descuento_pct" => 8, "gasto_pct" => 12, "comision_pct" => 8, "margen_objetivo_pct" => 20)
            )
        ));
    }

    public function auditarEscenariosComerciales() {
        try {
            $db = $this->getConexion();
            $persistidos = array();
            $stmt = $db->query("SELECT id_escenario, clave, nombre, canal, descuento_pct, gasto_operativo_pct,
                    comision_pct, margen_objetivo_pct, estatus, fecha_actualizacion
                FROM erp_rentabilidad_escenarios
                ORDER BY FIELD(canal,'menudeo','mayoreo','alianza','liquidacion','otro'), clave");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $persistidos[$fila["clave"]] = $fila;
            }

            $resumen = array("semilla" => 0, "persistidos" => count($persistidos), "faltantes" => 0, "activos" => 0, "diferentes_default" => 0);
            $items = array();
            foreach ($this->escenariosSemilla() as $semilla) {
                $resumen["semilla"]++;
                $persistido = isset($persistidos[$semilla["clave"]]) ? $persistidos[$semilla["clave"]] : null;
                $diferencias = array();
                if (!$persistido) {
                    $resumen["faltantes"]++;
                    $estado = "faltante";
                } else {
                    if ($persistido["estatus"] === "activo") {
                        $resumen["activos"]++;
                    }
                    $mapa = array(
                        "descuento_pct" => "descuento_pct",
                        "gasto_pct" => "gasto_operativo_pct",
                        "comision_pct" => "comision_pct",
                        "margen_objetivo_pct" => "margen_objetivo_pct"
                    );
                    foreach ($mapa as $campoSemilla => $campoPersistido) {
                        if (abs(floatval($semilla[$campoSemilla]) - floatval($persistido[$campoPersistido])) > 0.0001) {
                            $diferencias[] = array(
                                "campo" => $campoSemilla,
                                "default" => floatval($semilla[$campoSemilla]),
                                "persistido" => floatval($persistido[$campoPersistido])
                            );
                        }
                    }
                    if (!empty($diferencias)) {
                        $resumen["diferentes_default"]++;
                    }
                    $estado = $persistido["estatus"] === "activo" ? "activo" : "no_activo";
                }
                $items[] = array(
                    "clave" => $semilla["clave"],
                    "nombre" => $semilla["nombre"],
                    "canal" => $semilla["canal"],
                    "estado" => $estado,
                    "default" => array(
                        "descuento_pct" => $semilla["descuento_pct"],
                        "gasto_pct" => $semilla["gasto_pct"],
                        "comision_pct" => $semilla["comision_pct"],
                        "margen_objetivo_pct" => $semilla["margen_objetivo_pct"]
                    ),
                    "persistido" => $persistido ? array(
                        "id_escenario" => intval($persistido["id_escenario"]),
                        "descuento_pct" => round(floatval($persistido["descuento_pct"]), 4),
                        "gasto_pct" => round(floatval($persistido["gasto_operativo_pct"]), 4),
                        "comision_pct" => round(floatval($persistido["comision_pct"]), 4),
                        "margen_objetivo_pct" => round(floatval($persistido["margen_objetivo_pct"]), 4),
                        "estatus" => $persistido["estatus"],
                        "fecha_actualizacion" => $persistido["fecha_actualizacion"]
                    ) : null,
                    "diferencias" => $diferencias
                );
            }

            return $this->respuesta(false, "success", "Escenarios comerciales auditados", array(
                "resumen" => $resumen,
                "items" => $items,
                "reglas" => array(
                    "Auditoria read-only: no siembra escenarios ni modifica porcentajes.",
                    "Los defaults siguen siendo la fuente de simulacion cuando no existe configuracion persistida activa.",
                    "Sembrar o editar escenarios requiere respaldo y autorizacion."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function analizarSkus($filtros = array()) {
        try {
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $canal = $this->opcion(isset($filtros["canal"]) ? $filtros["canal"] : "menudeo", array("menudeo", "mayoreo", "alianza"), "menudeo");
            $defaults = $this->defaultsEscenario($canal);
            $descuentoPct = $this->porcentaje($filtros, "descuento_pct", $defaults["descuento_pct"]);
            $gastoPct = $this->porcentaje($filtros, "gasto_pct", $defaults["gasto_pct"]);
            $comisionPct = $this->porcentaje($filtros, "comision_pct", $defaults["comision_pct"]);
            $margenObjetivoPct = $this->porcentaje($filtros, "margen_objetivo_pct", $defaults["margen_objetivo_pct"]);
            $riesgo = trim(isset($filtros["riesgo"]) ? $filtros["riesgo"] : "");
            $limite = max(10, min(500, intval(isset($filtros["limite"]) ? $filtros["limite"] : 300)));

            $items = array();
            foreach ($this->consultarFilasSku($termino, $limite) as $fila) {
                $item = $this->calcularItem($fila, $canal, $descuentoPct, $gastoPct, $comisionPct, $margenObjetivoPct);
                if ($riesgo !== "" && $item["riesgo_clave"] !== $riesgo) {
                    continue;
                }
                if (!$this->cumpleFiltrosOperacion($item, $filtros)) {
                    continue;
                }
                $items[] = $item;
            }
            $resumen = $this->resumen($items);
            return $this->respuesta(false, "success", "Analisis de rentabilidad consultado", array(
                "escenario" => array(
                    "canal" => $canal,
                    "descuento_pct" => $descuentoPct,
                    "gasto_pct" => $gastoPct,
                    "comision_pct" => $comisionPct,
                    "margen_objetivo_pct" => $margenObjetivoPct
                ),
                "resumen" => $resumen,
                "items" => $items,
                "reglas" => array(
                    "Consulta read-only: no modifica Inventario, Catalogo, Compras ni Ventas.",
                    "El costo preferido es costo promedio de inventario; si no existe, usa costo de referencia del SKU.",
                    "El precio sin impuestos se calcula desde el precio general y la configuracion fiscal del SKU.",
                    "Los escenarios son simulaciones; no crean listas ni actualizan precios."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function compararEscenariosSku($filtros = array()) {
        try {
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            if ($termino === "") {
                return $this->respuesta(true, "warning", "Indica un SKU o producto para comparar escenarios");
            }
            $filas = $this->consultarFilasSku($termino, 1, true);
            if (empty($filas)) {
                return $this->respuesta(true, "warning", "No se encontro SKU para comparar");
            }
            $fila = $filas[0];
            $escenarios = array();
            foreach (array("menudeo", "mayoreo", "alianza") as $canal) {
                $defaults = $this->defaultsEscenario($canal);
                $escenarios[] = $this->calcularItem(
                    $fila,
                    $canal,
                    $this->porcentaje($filtros, $canal . "_descuento_pct", $defaults["descuento_pct"]),
                    $this->porcentaje($filtros, $canal . "_gasto_pct", $defaults["gasto_pct"]),
                    $this->porcentaje($filtros, $canal . "_comision_pct", $defaults["comision_pct"]),
                    $this->porcentaje($filtros, $canal . "_margen_objetivo_pct", $defaults["margen_objetivo_pct"])
                );
            }
            return $this->respuesta(false, "success", "Comparacion de escenarios consultada", array(
                "sku" => array(
                    "id_sku" => intval($fila["id_sku"]),
                    "sku" => $fila["sku"],
                    "producto" => $fila["producto"]
                ),
                "escenarios" => $escenarios,
                "reglas" => array(
                    "Comparacion read-only: no guarda escenarios ni modifica precios.",
                    "Los porcentajes por canal son defaults operativos editables solo en la consulta."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function detalleSku($filtros = array()) {
        try {
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            if ($termino === "") {
                return $this->respuesta(true, "warning", "Indica un SKU para consultar evidencia");
            }

            $filas = $this->consultarFilasSku($termino, 1, true);
            if (empty($filas)) {
                return $this->respuesta(true, "warning", "No se encontro SKU para consultar evidencia");
            }

            $fila = $filas[0];
            $canal = $this->opcion(isset($filtros["canal"]) ? $filtros["canal"] : "menudeo", array("menudeo", "mayoreo", "alianza"), "menudeo");
            $defaults = $this->defaultsEscenario($canal);
            $item = $this->calcularItem(
                $fila,
                $canal,
                $this->porcentaje($filtros, "descuento_pct", $defaults["descuento_pct"]),
                $this->porcentaje($filtros, "gasto_pct", $defaults["gasto_pct"]),
                $this->porcentaje($filtros, "comision_pct", $defaults["comision_pct"]),
                $this->porcentaje($filtros, "margen_objetivo_pct", $defaults["margen_objetivo_pct"])
            );

            $escenarios = array();
            foreach (array("menudeo", "mayoreo", "alianza") as $canalEscenario) {
                $defaultsEscenario = $this->defaultsEscenario($canalEscenario);
                $escenarios[] = $this->calcularItem(
                    $fila,
                    $canalEscenario,
                    $defaultsEscenario["descuento_pct"],
                    $defaultsEscenario["gasto_pct"],
                    $defaultsEscenario["comision_pct"],
                    $defaultsEscenario["margen_objetivo_pct"]
                );
            }

            $datosBase = $this->consultarDatosBaseSku($fila["sku"], 20);
            $datosBaseSku = null;
            foreach ($datosBase as $datoBase) {
                if (strcasecmp($datoBase["sku"], $fila["sku"]) === 0) {
                    $datosBaseSku = $this->resumenDatosBaseDetalle($datoBase, $item);
                    break;
                }
            }

            $presentacionesRespuesta = $this->auditarCostosPresentaciones(array("q" => $fila["sku"], "limite" => 20));
            $presentaciones = empty($presentacionesRespuesta["error"]) ? $presentacionesRespuesta["depurar"] : array("total" => 0, "alertas" => 0, "items" => array());

            $fiscalRespuesta = $this->auditarFiscalXmlCierre(array("q" => $fila["sku"], "limite" => 20));
            $fiscalXml = empty($fiscalRespuesta["error"]) ? $fiscalRespuesta["depurar"] : array("total_fiscal_incompleto" => 0, "con_sugerencia_xml" => 0, "items" => array());
            $fiscalXml = $this->filtrarFiscalXmlDetalle($fiscalXml, $fila["sku"]);

            $snapshotsRespuesta = $this->auditarVigenciaSnapshots(array("q" => $fila["sku"], "limite" => 20));
            $snapshots = empty($snapshotsRespuesta["error"]) ? $snapshotsRespuesta["depurar"] : array("total" => 0, "desfasados" => 0, "items" => array());

            $filtrosSku = $filtros;
            $filtrosSku["q"] = $fila["sku"];
            $filtrosSku["limite"] = 20;
            $planRespuesta = $this->planCierreComercial($filtrosSku);
            $preflightRespuesta = $this->preflightAprobacionPrecios($filtrosSku);
            $recomendacionesRespuesta = $this->preflightRecomendaciones($filtrosSku);
            $estadoModuloRespuesta = $this->estadoModuloRentabilidad($filtrosSku);
            $dictamenCierre = $this->dictamenDetalleCierreSku(
                $item,
                empty($planRespuesta["error"]) ? $planRespuesta["depurar"] : array(),
                empty($preflightRespuesta["error"]) ? $preflightRespuesta["depurar"] : array(),
                empty($recomendacionesRespuesta["error"]) ? $recomendacionesRespuesta["depurar"] : array(),
                empty($estadoModuloRespuesta["error"]) ? $estadoModuloRespuesta["depurar"] : array()
            );

            return $this->respuesta(false, "success", "Evidencia de rentabilidad consultada", array(
                "sku" => array(
                    "id_sku" => intval($fila["id_sku"]),
                    "sku" => $fila["sku"],
                    "producto" => $fila["producto"]
                ),
                "escenario_activo" => $item,
                "escenarios" => $escenarios,
                "datos_base" => $datosBaseSku,
                "presentaciones" => $presentaciones,
                "fiscal_xml" => $fiscalXml,
                "snapshots" => $snapshots,
                "dictamen_cierre" => $dictamenCierre,
                "reglas" => array(
                    "Ficha read-only: no modifica Catalogo, Inventario, Compras, XML, snapshots ni recomendaciones.",
                    "La evidencia consolida calculo vigente, escenarios, datos base, XML fiscal, presentaciones, snapshots, preflight y dictamen de cierre.",
                    "Cualquier correccion derivada de esta ficha requiere respaldo externo y autorizacion."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function matrizEscenarios($filtros = array()) {
        try {
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $canales = array("menudeo", "mayoreo", "alianza");
            $resumenCanales = array();
            foreach ($canales as $canal) {
                $resumenCanales[$canal] = array(
                    "canal" => $canal,
                    "rentables" => 0,
                    "precaucion" => 0,
                    "bloqueados" => 0,
                    "perdida" => 0,
                    "utilidad_total" => 0,
                    "margen_promedio" => null
                );
            }

            $items = array();
            foreach ($this->consultarFilasSku($termino, $limite) as $fila) {
                $canalFiltro = $this->opcion(isset($filtros["canal"]) ? $filtros["canal"] : "menudeo", array("menudeo", "mayoreo", "alianza"), "menudeo");
                $defaultsFiltro = $this->defaultsEscenario($canalFiltro);
                $itemFiltro = $this->calcularItem(
                    $fila,
                    $canalFiltro,
                    $defaultsFiltro["descuento_pct"],
                    $defaultsFiltro["gasto_pct"],
                    $defaultsFiltro["comision_pct"],
                    $defaultsFiltro["margen_objetivo_pct"]
                );
                if (!$this->cumpleFiltrosOperacion($itemFiltro, $filtros)) {
                    continue;
                }
                $escenarios = array();
                $mejor = null;
                $bloqueosSku = 0;
                $margenSuma = array();
                foreach ($canales as $canal) {
                    $defaults = $this->defaultsEscenario($canal);
                    $item = $this->calcularItem(
                        $fila,
                        $canal,
                        $defaults["descuento_pct"],
                        $defaults["gasto_pct"],
                        $defaults["comision_pct"],
                        $defaults["margen_objetivo_pct"]
                    );
                    $escenario = array(
                        "canal" => $canal,
                        "precio" => $item["precio_escenario_sin_impuesto"],
                        "costo" => $item["costo_real_sin_impuesto"],
                        "margen" => $item["margen_bruto_pct"],
                        "utilidad" => $item["utilidad_estimada"],
                        "minimo" => $item["precio_minimo_rentable"],
                        "riesgo" => $item["riesgo_clave"],
                        "tipo" => $item["riesgo_tipo"]
                    );
                    $escenarios[$canal] = $escenario;
                    $resumenCanales[$canal]["utilidad_total"] += floatval($item["utilidad_estimada"]);
                    if ($item["margen_bruto_pct"] !== null) {
                        $margenSuma[$canal] = isset($margenSuma[$canal]) ? $margenSuma[$canal] + $item["margen_bruto_pct"] : $item["margen_bruto_pct"];
                    }
                    if (in_array($item["riesgo_clave"], array("incompleto"), true)) {
                        $resumenCanales[$canal]["bloqueados"]++;
                        $bloqueosSku++;
                    } elseif ($item["riesgo_clave"] === "perdida") {
                        $resumenCanales[$canal]["perdida"]++;
                        $bloqueosSku++;
                    } elseif ($item["riesgo_clave"] === "margen_bajo") {
                        $resumenCanales[$canal]["precaucion"]++;
                    } else {
                        $resumenCanales[$canal]["rentables"]++;
                    }
                    if ($item["riesgo_clave"] !== "incompleto" && ($mejor === null || $item["utilidad_estimada"] > $mejor["utilidad"])) {
                        $mejor = $escenario;
                    }
                }
                $items[] = array(
                    "id_sku" => intval($fila["id_sku"]),
                    "sku" => $fila["sku"],
                    "producto" => $fila["producto"],
                    "mejor_canal" => $mejor ? $mejor["canal"] : null,
                    "mejor_utilidad" => $mejor ? $mejor["utilidad"] : null,
                    "bloqueos_escenario" => $bloqueosSku,
                    "escenarios" => $escenarios
                );
            }

            foreach ($resumenCanales as $canal => $datos) {
                $margenTotal = 0;
                $margenConteo = 0;
                foreach ($items as $item) {
                    $margen = $item["escenarios"][$canal]["margen"];
                    if ($margen !== null) {
                        $margenTotal += $margen;
                        $margenConteo++;
                    }
                }
                $resumenCanales[$canal]["utilidad_total"] = round($datos["utilidad_total"], 6);
                $resumenCanales[$canal]["margen_promedio"] = $margenConteo > 0 ? round($margenTotal / $margenConteo, 2) : null;
            }

            usort($items, function ($a, $b) {
                if ($a["bloqueos_escenario"] !== $b["bloqueos_escenario"]) {
                    return $b["bloqueos_escenario"] - $a["bloqueos_escenario"];
                }
                return floatval($a["mejor_utilidad"]) < floatval($b["mejor_utilidad"]) ? 1 : -1;
            });

            return $this->respuesta(false, "success", "Matriz de escenarios consultada", array(
                "total_skus_evaluados" => count($items),
                "canales" => array_values($resumenCanales),
                "items" => array_slice($items, 0, $limite),
                "reglas" => array(
                    "Matriz read-only: no guarda escenarios ni actualiza precios.",
                    "Compara menudeo, mayoreo y alianza con defaults operativos actuales.",
                    "El mejor canal se elige por utilidad estimada, excluyendo escenarios incompletos."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function canalesRecomendados($filtros = array()) {
        try {
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $canales = array("menudeo", "mayoreo", "alianza");
            $resumen = array(
                "evaluados" => 0,
                "listos" => 0,
                "precaucion" => 0,
                "bloqueados" => 0,
                "menudeo" => 0,
                "mayoreo" => 0,
                "alianza" => 0,
                "utilidad_recomendada_total" => 0
            );
            $items = array();

            foreach ($this->consultarFilasSku($termino, $limite) as $fila) {
                $canalFiltro = $this->opcion(isset($filtros["canal"]) ? $filtros["canal"] : "menudeo", $canales, "menudeo");
                $defaultsFiltro = $this->defaultsEscenario($canalFiltro);
                $itemFiltro = $this->calcularItem(
                    $fila,
                    $canalFiltro,
                    $defaultsFiltro["descuento_pct"],
                    $defaultsFiltro["gasto_pct"],
                    $defaultsFiltro["comision_pct"],
                    $defaultsFiltro["margen_objetivo_pct"]
                );
                if (!$this->cumpleFiltrosOperacion($itemFiltro, $filtros)) {
                    continue;
                }

                $resumen["evaluados"]++;
                $escenarios = array();
                foreach ($canales as $canal) {
                    $defaults = $this->defaultsEscenario($canal);
                    $item = $this->calcularItem(
                        $fila,
                        $canal,
                        $defaults["descuento_pct"],
                        $defaults["gasto_pct"],
                        $defaults["comision_pct"],
                        $defaults["margen_objetivo_pct"]
                    );
                    $escenarios[$canal] = array(
                        "canal" => $canal,
                        "precio" => $item["precio_escenario_sin_impuesto"],
                        "utilidad" => $item["utilidad_estimada"],
                        "margen" => $item["margen_bruto_pct"],
                        "riesgo" => $item["riesgo_clave"],
                        "tipo" => $item["riesgo_tipo"]
                    );
                }

                $dictamen = $this->dictamenCanalRecomendado($escenarios);
                $resumen[$dictamen["estado"]]++;
                if ($dictamen["canal"] !== null) {
                    $resumen[$dictamen["canal"]]++;
                    $resumen["utilidad_recomendada_total"] += floatval($dictamen["utilidad"]);
                }

                $items[] = array(
                    "id_sku" => intval($fila["id_sku"]),
                    "sku" => $fila["sku"],
                    "producto" => $fila["producto"],
                    "canal_recomendado" => $dictamen["canal"],
                    "estado" => $dictamen["estado"],
                    "tipo" => $dictamen["tipo"],
                    "utilidad" => $dictamen["utilidad"],
                    "margen" => $dictamen["margen"],
                    "motivo" => $dictamen["motivo"],
                    "escenarios" => $escenarios
                );
            }

            usort($items, function ($a, $b) {
                $peso = array("bloqueados" => 0, "precaucion" => 1, "listos" => 2);
                if ($peso[$a["estado"]] !== $peso[$b["estado"]]) {
                    return $peso[$a["estado"]] - $peso[$b["estado"]];
                }
                return floatval($a["utilidad"]) < floatval($b["utilidad"]) ? 1 : -1;
            });
            $resumen["utilidad_recomendada_total"] = round($resumen["utilidad_recomendada_total"], 6);

            return $this->respuesta(false, "success", "Canales recomendados consultados", array(
                "resumen" => $resumen,
                "items" => array_slice($items, 0, $limite),
                "reglas" => array(
                    "Recomendacion read-only: no crea listas, no publica precios y no toca Ventas.",
                    "El canal recomendado maximiza utilidad entre canales sin perdida ni datos incompletos.",
                    "Si solo hay margen bajo, queda en precaucion; si no hay canal vendible, queda bloqueado."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function planCierreComercial($filtros = array()) {
        try {
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $filtrosAnalisis = $filtros;
            $filtrosAnalisis["limite"] = $limite;
            unset($filtrosAnalisis["riesgo"]);
            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }

            $canales = $this->canalesRecomendados($filtrosAnalisis);
            $sensibilidad = $this->sensibilidadRentabilidad($filtrosAnalisis);
            $variaciones = $this->variacionesCostos($filtrosAnalisis);
            $canalesPorSku = $this->indexarItemsPorSku(empty($canales["error"]) ? $canales["depurar"]["items"] : array());
            $sensibilidadPorSku = $this->indexarItemsPorSku(empty($sensibilidad["error"]) ? $sensibilidad["depurar"]["items"] : array());
            $variacionesPorSku = $this->indexarItemsPorSku(empty($variaciones["error"]) ? $variaciones["depurar"]["items"] : array());

            $grupos = array(
                "cerrar" => array("titulo" => "Listos para cierre", "tipo" => "success", "items" => array()),
                "completar_fiscal" => array("titulo" => "Completar fiscal", "tipo" => "warning", "items" => array()),
                "completar_datos" => array("titulo" => "Completar costo/precio", "tipo" => "danger", "items" => array()),
                "revisar_precio" => array("titulo" => "Revisar precio/margen", "tipo" => "danger", "items" => array()),
                "validar_costo" => array("titulo" => "Validar costo", "tipo" => "warning", "items" => array()),
                "revisar_canal" => array("titulo" => "Revisar canal", "tipo" => "info", "items" => array())
            );

            foreach (isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array() as $item) {
                $sku = $item["sku"];
                $canal = isset($canalesPorSku[$sku]) ? $canalesPorSku[$sku] : null;
                $sens = isset($sensibilidadPorSku[$sku]) ? $sensibilidadPorSku[$sku] : null;
                $variacion = isset($variacionesPorSku[$sku]) ? $variacionesPorSku[$sku] : null;
                $dictamen = $this->clasificarPlanCierreItem($item, $canal, $sens, $variacion);
                $grupos[$dictamen["grupo"]]["items"][] = array(
                    "sku" => $sku,
                    "producto" => $item["producto"],
                    "riesgo" => $item["riesgo_clave"],
                    "canal" => $canal ? $canal["canal_recomendado"] : null,
                    "costo" => $item["costo_real_sin_impuesto"],
                    "precio" => $item["precio_escenario_sin_impuesto"],
                    "utilidad" => $item["utilidad_estimada"],
                    "margen" => $item["margen_bruto_pct"],
                    "alertas_costo" => $variacion ? intval($variacion["alertas"]) : 0,
                    "vulnerable" => $sens ? !empty($sens["vulnerable"]) : false,
                    "siguiente_paso" => $dictamen["siguiente_paso"],
                    "motivo" => $dictamen["motivo"]
                );
            }

            $resumen = array("evaluados" => 0);
            foreach ($grupos as $clave => $grupo) {
                $itemsGrupo = $grupo["items"];
                $this->ordenarPlanCierreGrupo($itemsGrupo, $clave);
                $grupos[$clave]["total"] = count($itemsGrupo);
                $grupos[$clave]["items"] = array_slice($itemsGrupo, 0, 25);
                $resumen[$clave] = count($itemsGrupo);
                $resumen["evaluados"] += count($itemsGrupo);
            }

            return $this->respuesta(false, "success", "Plan de cierre comercial consultado", array(
                "resumen" => $resumen,
                "grupos" => $grupos,
                "reglas" => array(
                    "Plan read-only: no crea tareas, no guarda recomendaciones y no aplica precios.",
                    "Cada SKU aparece en una sola bandeja priorizada por bloqueo mas importante.",
                    "La bandeja de cierre requiere costo/precio, canal recomendado, costo validado, sensibilidad estable y fiscal completo."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function impactoCierreComercial($filtros = array()) {
        try {
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $filtrosAnalisis = $filtros;
            $filtrosAnalisis["limite"] = $limite;
            unset($filtrosAnalisis["riesgo"]);

            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }

            $canales = $this->canalesRecomendados($filtrosAnalisis);
            $sensibilidad = $this->sensibilidadRentabilidad($filtrosAnalisis);
            $variaciones = $this->variacionesCostos($filtrosAnalisis);
            $canalesPorSku = $this->indexarItemsPorSku(empty($canales["error"]) ? $canales["depurar"]["items"] : array());
            $sensibilidadPorSku = $this->indexarItemsPorSku(empty($sensibilidad["error"]) ? $sensibilidad["depurar"]["items"] : array());
            $variacionesPorSku = $this->indexarItemsPorSku(empty($variaciones["error"]) ? $variaciones["depurar"]["items"] : array());

            $grupos = array(
                "completar_datos" => $this->impactoGrupoBase("Completar costo/precio", "danger"),
                "revisar_precio" => $this->impactoGrupoBase("Revisar precio/margen", "danger"),
                "validar_costo" => $this->impactoGrupoBase("Validar costo", "warning"),
                "completar_fiscal" => $this->impactoGrupoBase("Completar fiscal", "warning"),
                "revisar_canal" => $this->impactoGrupoBase("Revisar canal", "info"),
                "cerrar" => $this->impactoGrupoBase("Listos para cierre", "success")
            );

            foreach (isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array() as $item) {
                $sku = $item["sku"];
                $canal = isset($canalesPorSku[$sku]) ? $canalesPorSku[$sku] : null;
                $sens = isset($sensibilidadPorSku[$sku]) ? $sensibilidadPorSku[$sku] : null;
                $variacion = isset($variacionesPorSku[$sku]) ? $variacionesPorSku[$sku] : null;
                $dictamen = $this->clasificarPlanCierreItem($item, $canal, $sens, $variacion);
                $clave = $dictamen["grupo"];
                $precio = floatval($item["precio_escenario_sin_impuesto"]);
                $precioMinimo = $item["precio_minimo_rentable"] === null ? null : floatval($item["precio_minimo_rentable"]);
                $deficitPrecio = $precioMinimo === null ? 0 : max(0, $precioMinimo - $precio);
                $utilidad = floatval($item["utilidad_estimada"]);

                $grupos[$clave]["skus"]++;
                if ($clave === "completar_datos") {
                    $grupos[$clave]["utilidad_no_confiable"] += $utilidad;
                } else {
                    $grupos[$clave]["utilidad_estimada"] += $utilidad;
                    $grupos[$clave]["utilidad_negativa"] += min(0, $utilidad);
                }
                $grupos[$clave]["deficit_precio"] += $deficitPrecio;
                $grupos[$clave]["valor_inventario"] += floatval($item["inventario"]["valor_total"]);
                if (count($grupos[$clave]["items"]) < 8) {
                    $grupos[$clave]["items"][] = array(
                        "sku" => $sku,
                        "producto" => $item["producto"],
                        "utilidad" => $item["utilidad_estimada"],
                        "valor_inventario" => $item["inventario"]["valor_total"],
                        "deficit_precio" => round($deficitPrecio, 6),
                        "siguiente_paso" => $dictamen["siguiente_paso"]
                    );
                }
            }

            $resumen = array(
                "evaluados" => 0,
                "utilidad_estimada" => 0,
                "utilidad_no_confiable" => 0,
                "utilidad_negativa" => 0,
                "deficit_precio" => 0,
                "valor_inventario" => 0
            );
            foreach ($grupos as $clave => $grupo) {
                $grupos[$clave]["utilidad_estimada"] = round($grupo["utilidad_estimada"], 6);
                $grupos[$clave]["utilidad_no_confiable"] = round($grupo["utilidad_no_confiable"], 6);
                $grupos[$clave]["utilidad_negativa"] = round($grupo["utilidad_negativa"], 6);
                $grupos[$clave]["deficit_precio"] = round($grupo["deficit_precio"], 6);
                $grupos[$clave]["valor_inventario"] = round($grupo["valor_inventario"], 6);
                $resumen["evaluados"] += intval($grupos[$clave]["skus"]);
                $resumen["utilidad_estimada"] += floatval($grupos[$clave]["utilidad_estimada"]);
                $resumen["utilidad_no_confiable"] += floatval($grupos[$clave]["utilidad_no_confiable"]);
                $resumen["utilidad_negativa"] += floatval($grupos[$clave]["utilidad_negativa"]);
                $resumen["deficit_precio"] += floatval($grupos[$clave]["deficit_precio"]);
                $resumen["valor_inventario"] += floatval($grupos[$clave]["valor_inventario"]);
            }
            foreach (array("utilidad_estimada", "utilidad_no_confiable", "utilidad_negativa", "deficit_precio", "valor_inventario") as $campo) {
                $resumen[$campo] = round($resumen[$campo], 6);
            }

            return $this->respuesta(false, "success", "Impacto de cierre comercial consultado", array(
                "resumen" => $resumen,
                "grupos" => $grupos,
                "reglas" => array(
                    "Impacto read-only: no crea tareas, no guarda recomendaciones y no aplica precios.",
                    "El deficit de precio suma la diferencia contra precio minimo rentable cuando el precio actual no alcanza.",
                    "La utilidad negativa se presenta separada para dimensionar riesgo economico inmediato."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function hallazgosCierreComercial($filtros = array()) {
        try {
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $filtrosAnalisis = $filtros;
            $filtrosAnalisis["limite"] = $limite;
            unset($filtrosAnalisis["riesgo"]);
            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }

            $hallazgos = array();
            $resumen = array(
                "evaluados" => 0,
                "hallazgos" => 0,
                "skus_con_hallazgos" => 0,
                "utilidad_confiable_en_hallazgos" => 0,
                "utilidad_no_confiable_en_hallazgos" => 0,
                "valor_inventario_en_hallazgos" => 0
            );

            foreach (isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array() as $item) {
                $resumen["evaluados"]++;
                $detalle = isset($item["hallazgos_detalle"]) ? $item["hallazgos_detalle"] : array();
                $precio = floatval($item["precio_escenario_sin_impuesto"]);
                $precioMinimo = $item["precio_minimo_rentable"] === null ? null : floatval($item["precio_minimo_rentable"]);
                if ($precioMinimo !== null && $precio > 0 && $precioMinimo > $precio + 0.01) {
                    $detalle[] = $this->hallazgo("COST-H107", "precio_bajo_minimo", "danger", "Precio actual menor al minimo rentable");
                }
                if (empty($detalle)) {
                    continue;
                }
                $resumen["skus_con_hallazgos"]++;
                $esConfiable = !in_array("sin_costo", $item["hallazgos"], true) && !in_array("sin_precio", $item["hallazgos"], true);
                $utilidad = floatval($item["utilidad_estimada"]);
                $valorInventario = floatval($item["inventario"]["valor_total"]);
                if ($esConfiable) {
                    $resumen["utilidad_confiable_en_hallazgos"] += $utilidad;
                } else {
                    $resumen["utilidad_no_confiable_en_hallazgos"] += $utilidad;
                }
                $resumen["valor_inventario_en_hallazgos"] += $valorInventario;

                foreach ($detalle as $h) {
                    $id = isset($h["id"]) ? $h["id"] : "COST-H000";
                    if (!isset($hallazgos[$id])) {
                        $hallazgos[$id] = array(
                            "id" => $id,
                            "clave" => isset($h["clave"]) ? $h["clave"] : "",
                            "mensaje" => isset($h["mensaje"]) ? $h["mensaje"] : "",
                            "tipo" => isset($h["tipo"]) ? $h["tipo"] : "info",
                            "skus" => 0,
                            "utilidad_confiable" => 0,
                            "utilidad_no_confiable" => 0,
                            "valor_inventario" => 0,
                            "items" => array()
                        );
                    }
                    $hallazgos[$id]["skus"]++;
                    $resumen["hallazgos"]++;
                    if ($esConfiable) {
                        $hallazgos[$id]["utilidad_confiable"] += $utilidad;
                    } else {
                        $hallazgos[$id]["utilidad_no_confiable"] += $utilidad;
                    }
                    $hallazgos[$id]["valor_inventario"] += $valorInventario;
                    if (count($hallazgos[$id]["items"]) < 8) {
                        $hallazgos[$id]["items"][] = array(
                            "sku" => $item["sku"],
                            "producto" => $item["producto"],
                            "riesgo" => $item["riesgo_clave"],
                            "utilidad" => $item["utilidad_estimada"],
                            "valor_inventario" => $item["inventario"]["valor_total"],
                            "recomendacion" => $item["recomendacion"]
                        );
                    }
                }
            }

            foreach ($hallazgos as $id => $h) {
                $hallazgos[$id]["utilidad_confiable"] = round($h["utilidad_confiable"], 6);
                $hallazgos[$id]["utilidad_no_confiable"] = round($h["utilidad_no_confiable"], 6);
                $hallazgos[$id]["valor_inventario"] = round($h["valor_inventario"], 6);
            }
            usort($hallazgos, function ($a, $b) {
                if (intval($a["skus"]) !== intval($b["skus"])) {
                    return intval($b["skus"]) - intval($a["skus"]);
                }
                return strcmp($a["id"], $b["id"]);
            });
            foreach (array("utilidad_confiable_en_hallazgos", "utilidad_no_confiable_en_hallazgos", "valor_inventario_en_hallazgos") as $campo) {
                $resumen[$campo] = round($resumen[$campo], 6);
            }

            return $this->respuesta(false, "success", "Hallazgos de cierre comercial consultados", array(
                "resumen" => $resumen,
                "hallazgos" => $hallazgos,
                "reglas" => array(
                    "Hallazgos read-only: no crea tareas ni modifica Catalogo, Inventario, Compras o Ventas.",
                    "Cada hallazgo conserva ID operativo para UAT y seguimiento manual.",
                    "COST-H107 se calcula como alerta de precio contra minimo rentable aunque no exista en el calculo base del SKU."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function prioridadesCierreComercial($filtros = array()) {
        try {
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $filtrosAnalisis = $filtros;
            $filtrosAnalisis["limite"] = $limite;
            unset($filtrosAnalisis["riesgo"]);

            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }
            $canales = $this->canalesRecomendados($filtrosAnalisis);
            $sensibilidad = $this->sensibilidadRentabilidad($filtrosAnalisis);
            $variaciones = $this->variacionesCostos($filtrosAnalisis);
            $canalesPorSku = $this->indexarItemsPorSku(empty($canales["error"]) ? $canales["depurar"]["items"] : array());
            $sensibilidadPorSku = $this->indexarItemsPorSku(empty($sensibilidad["error"]) ? $sensibilidad["depurar"]["items"] : array());
            $variacionesPorSku = $this->indexarItemsPorSku(empty($variaciones["error"]) ? $variaciones["depurar"]["items"] : array());

            $items = array();
            $resumen = array("evaluados" => 0, "prioridades" => 0, "alta" => 0, "media" => 0, "baja" => 0);
            foreach (isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array() as $item) {
                $resumen["evaluados"]++;
                $sku = $item["sku"];
                $canal = isset($canalesPorSku[$sku]) ? $canalesPorSku[$sku] : null;
                $sens = isset($sensibilidadPorSku[$sku]) ? $sensibilidadPorSku[$sku] : null;
                $variacion = isset($variacionesPorSku[$sku]) ? $variacionesPorSku[$sku] : null;
                $dictamen = $this->clasificarPlanCierreItem($item, $canal, $sens, $variacion);
                if ($dictamen["grupo"] === "cerrar") {
                    continue;
                }

                $precio = floatval($item["precio_escenario_sin_impuesto"]);
                $precioMinimo = $item["precio_minimo_rentable"] === null ? null : floatval($item["precio_minimo_rentable"]);
                $deficitPrecio = $precioMinimo === null ? 0 : max(0, $precioMinimo - $precio);
                $score = $this->scorePrioridadCierre($dictamen["grupo"], $item, $deficitPrecio, $variacion);
                $nivel = $score >= 1000 ? "alta" : ($score >= 650 ? "media" : "baja");
                $resumen["prioridades"]++;
                $resumen[$nivel]++;

                $items[] = array(
                    "sku" => $sku,
                    "producto" => $item["producto"],
                    "grupo" => $dictamen["grupo"],
                    "nivel" => $nivel,
                    "score" => round($score, 2),
                    "responsable_sugerido" => $this->responsablePrioridadCierre($dictamen["grupo"]),
                    "canal" => $canal ? $canal["canal_recomendado"] : null,
                    "utilidad" => $item["utilidad_estimada"],
                    "utilidad_confiable" => !in_array("sin_costo", $item["hallazgos"], true) && !in_array("sin_precio", $item["hallazgos"], true),
                    "deficit_precio" => round($deficitPrecio, 6),
                    "valor_inventario" => $item["inventario"]["valor_total"],
                    "alertas_costo" => $variacion ? intval($variacion["alertas"]) : 0,
                    "siguiente_paso" => $dictamen["siguiente_paso"]
                );
            }

            usort($items, function ($a, $b) {
                if (floatval($a["score"]) !== floatval($b["score"])) {
                    return floatval($a["score"]) < floatval($b["score"]) ? 1 : -1;
                }
                return strcmp($a["sku"], $b["sku"]);
            });

            return $this->respuesta(false, "success", "Prioridades de cierre comercial consultadas", array(
                "resumen" => $resumen,
                "items" => array_slice($items, 0, $limite),
                "reglas" => array(
                    "Ranking read-only: no crea tareas, no guarda recomendaciones y no aplica precios.",
                    "El score es operativo para ordenar revision; no es una regla contable ni autorizacion comercial.",
                    "La utilidad de SKUs con costo/precio incompleto se marca como no confiable."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function responsablesCierreComercial($filtros = array()) {
        try {
            $prioridades = $this->prioridadesCierreComercial($filtros);
            if (!empty($prioridades["error"])) {
                return $prioridades;
            }
            $grupos = array();
            $resumen = array("responsables" => 0, "prioridades" => 0, "alta" => 0, "media" => 0, "baja" => 0);
            foreach (isset($prioridades["depurar"]["items"]) ? $prioridades["depurar"]["items"] : array() as $item) {
                $responsable = trim(isset($item["responsable_sugerido"]) ? $item["responsable_sugerido"] : "Operacion");
                if (!isset($grupos[$responsable])) {
                    $grupos[$responsable] = array(
                        "responsable" => $responsable,
                        "skus" => 0,
                        "alta" => 0,
                        "media" => 0,
                        "baja" => 0,
                        "utilidad_confiable" => 0,
                        "utilidad_no_confiable" => 0,
                        "deficit_precio" => 0,
                        "valor_inventario" => 0,
                        "score_total" => 0,
                        "items" => array()
                    );
                }
                $nivel = isset($item["nivel"]) ? $item["nivel"] : "baja";
                $grupos[$responsable]["skus"]++;
                if (isset($grupos[$responsable][$nivel])) {
                    $grupos[$responsable][$nivel]++;
                    $resumen[$nivel]++;
                }
                if (!empty($item["utilidad_confiable"])) {
                    $grupos[$responsable]["utilidad_confiable"] += floatval($item["utilidad"]);
                } else {
                    $grupos[$responsable]["utilidad_no_confiable"] += floatval($item["utilidad"]);
                }
                $grupos[$responsable]["deficit_precio"] += floatval($item["deficit_precio"]);
                $grupos[$responsable]["valor_inventario"] += floatval($item["valor_inventario"]);
                $grupos[$responsable]["score_total"] += floatval($item["score"]);
                $resumen["prioridades"]++;
                if (count($grupos[$responsable]["items"]) < 8) {
                    $grupos[$responsable]["items"][] = array(
                        "sku" => $item["sku"],
                        "producto" => $item["producto"],
                        "grupo" => $item["grupo"],
                        "nivel" => $item["nivel"],
                        "score" => $item["score"],
                        "siguiente_paso" => $item["siguiente_paso"]
                    );
                }
            }

            foreach ($grupos as $responsable => $grupo) {
                foreach (array("utilidad_confiable", "utilidad_no_confiable", "deficit_precio", "valor_inventario", "score_total") as $campo) {
                    $grupos[$responsable][$campo] = round($grupo[$campo], 6);
                }
            }
            $items = array_values($grupos);
            usort($items, function ($a, $b) {
                if (intval($a["alta"]) !== intval($b["alta"])) {
                    return intval($b["alta"]) - intval($a["alta"]);
                }
                if (floatval($a["score_total"]) !== floatval($b["score_total"])) {
                    return floatval($a["score_total"]) < floatval($b["score_total"]) ? 1 : -1;
                }
                return strcmp($a["responsable"], $b["responsable"]);
            });
            $resumen["responsables"] = count($items);

            return $this->respuesta(false, "success", "Responsables de cierre comercial consultados", array(
                "resumen" => $resumen,
                "items" => $items,
                "reglas" => array(
                    "Resumen read-only: no asigna tareas ni modifica responsables reales.",
                    "El responsable es sugerido por bandeja operativa para facilitar seguimiento manual.",
                    "Usa el mismo ranking de prioridades de cierre para evitar criterios duplicados."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function checklistCierreComercial($filtros = array()) {
        try {
            $plan = $this->planCierreComercial($filtros);
            if (!empty($plan["error"])) {
                return $plan;
            }
            $grupos = isset($plan["depurar"]["grupos"]) ? $plan["depurar"]["grupos"] : array();
            $resPlan = isset($plan["depurar"]["resumen"]) ? $plan["depurar"]["resumen"] : array();
            $definicion = array(
                array("id" => "COST-CHK-001", "grupo" => "completar_datos", "titulo" => "Costo y precio completos", "responsable" => "Catalogo", "criterio" => "Sin SKUs en completar costo/precio."),
                array("id" => "COST-CHK-002", "grupo" => "revisar_precio", "titulo" => "Precio y margen rentables", "responsable" => "Direccion/Comercial", "criterio" => "Sin SKUs en revisar precio/margen."),
                array("id" => "COST-CHK-003", "grupo" => "validar_costo", "titulo" => "Costo validado contra evidencia", "responsable" => "Compras/Almacen", "criterio" => "Sin SKUs en validar costo."),
                array("id" => "COST-CHK-004", "grupo" => "completar_fiscal", "titulo" => "Fiscal completo", "responsable" => "Catalogo/Fiscal", "criterio" => "Sin SKUs en completar fiscal."),
                array("id" => "COST-CHK-005", "grupo" => "revisar_canal", "titulo" => "Canal comercial definido", "responsable" => "Direccion/Comercial", "criterio" => "Sin SKUs en revisar canal."),
                array("id" => "COST-CHK-006", "grupo" => "cerrar", "titulo" => "Candidatos a cierre", "responsable" => "Direccion", "criterio" => "SKUs sin bloqueos listos para snapshot o aprobacion.")
            );

            $checks = array();
            $resumen = array("evaluados" => intval(isset($resPlan["evaluados"]) ? $resPlan["evaluados"] : 0), "checks" => 0, "ok" => 0, "bloqueados" => 0, "informativos" => 0, "skus_bloqueados" => 0, "skus_listos" => 0);
            foreach ($definicion as $def) {
                $grupo = isset($grupos[$def["grupo"]]) ? $grupos[$def["grupo"]] : array("total" => 0, "items" => array());
                $total = intval(isset($grupo["total"]) ? $grupo["total"] : 0);
                $estado = $def["grupo"] === "cerrar" ? "info" : ($total > 0 ? "bloqueado" : "ok");
                $tipo = $estado === "ok" ? "success" : ($estado === "bloqueado" ? "danger" : "info");
                $resumen["checks"]++;
                if ($estado === "ok") {
                    $resumen["ok"]++;
                } elseif ($estado === "bloqueado") {
                    $resumen["bloqueados"]++;
                    $resumen["skus_bloqueados"] += $total;
                } else {
                    $resumen["informativos"]++;
                    $resumen["skus_listos"] += $total;
                }
                $checks[] = array(
                    "id" => $def["id"],
                    "titulo" => $def["titulo"],
                    "responsable" => $def["responsable"],
                    "criterio" => $def["criterio"],
                    "estado" => $estado,
                    "tipo" => $tipo,
                    "total" => $total,
                    "items" => isset($grupo["items"]) ? array_slice($grupo["items"], 0, 5) : array()
                );
            }

            return $this->respuesta(false, "success", "Checklist de cierre comercial consultado", array(
                "resumen" => $resumen,
                "checks" => $checks,
                "reglas" => array(
                    "Checklist read-only: no crea tareas, no guarda aprobaciones y no aplica precios.",
                    "Usa las mismas bandejas del plan de cierre para evitar criterios duplicados.",
                    "Un check en OK solo significa que la muestra filtrada no tiene ese bloqueo."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function autorizacionesCierreComercial($filtros = array()) {
        try {
            $checklist = $this->checklistCierreComercial($filtros);
            if (!empty($checklist["error"])) {
                return $checklist;
            }
            $responsables = $this->responsablesCierreComercial($filtros);
            $prioridades = $this->prioridadesCierreComercial($filtros);
            $fiscal = $this->auditarFiscalXmlCierre($filtros);
            $checks = isset($checklist["depurar"]["checks"]) ? $checklist["depurar"]["checks"] : array();
            $resChecklist = isset($checklist["depurar"]["resumen"]) ? $checklist["depurar"]["resumen"] : array();
            $checksPorId = array();
            foreach ($checks as $check) {
                $checksPorId[$check["id"]] = $check;
            }
            $prioridadesItems = empty($prioridades["error"]) && isset($prioridades["depurar"]["items"]) ? $prioridades["depurar"]["items"] : array();
            $responsablesItems = empty($responsables["error"]) && isset($responsables["depurar"]["items"]) ? $responsables["depurar"]["items"] : array();
            $fiscalDep = empty($fiscal["error"]) ? $fiscal["depurar"] : array("total_fiscal_incompleto" => 0, "con_sugerencia_xml" => 0);

            $acciones = array();
            $acciones[] = $this->autorizacionAccionBase(
                "AUTH-COST-001",
                "Guardar snapshot vigente de rentabilidad",
                intval(isset($resChecklist["skus_listos"]) ? $resChecklist["skus_listos"] : 0) > 0 ? "requiere_respaldo" : "bloqueada",
                "rentabilidad.snapshot",
                "Respaldo de tablas erp_rentabilidad_snapshots y erp_rentabilidad_snapshot_detalles.",
                "Conservar evidencia directiva de SKUs listos antes de Ventas/Pedidos/Mayoreo.",
                "Solo procede si existen candidatos en COST-CHK-006.",
                array("skus_listos" => intval(isset($resChecklist["skus_listos"]) ? $resChecklist["skus_listos"] : 0))
            );
            $acciones[] = $this->autorizacionAccionBase(
                "AUTH-COST-002",
                "Crear recomendaciones persistentes desde prioridades",
                !empty($prioridadesItems) ? "requiere_respaldo" : "bloqueada",
                "rentabilidad.snapshot",
                "Respaldo de erp_rentabilidad_recomendaciones.",
                "Convertir la revision en pendientes operativos auditables.",
                "No aplica precios; solo crea pendientes si se autoriza.",
                array("prioridades" => count($prioridadesItems))
            );
            $acciones[] = $this->autorizacionAccionBase(
                "AUTH-COST-003",
                "Aplicar datos fiscales sugeridos a Catalogo",
                intval(isset($fiscalDep["con_sugerencia_xml"]) ? $fiscalDep["con_sugerencia_xml"] : 0) > 0 ? "requiere_respaldo" : "bloqueada",
                "catalogo_productos.editar",
                "Respaldo de tablas fiscales de Catalogo ERP.",
                "Completar fiscal solo cuando exista sugerencia XML vinculada.",
                "Actualmente no se infiere fiscal por texto ni se aplica automaticamente.",
                array(
                    "fiscal_incompleto" => intval(isset($fiscalDep["total_fiscal_incompleto"]) ? $fiscalDep["total_fiscal_incompleto"] : 0),
                    "con_sugerencia_xml" => intval(isset($fiscalDep["con_sugerencia_xml"]) ? $fiscalDep["con_sugerencia_xml"] : 0)
                )
            );
            $acciones[] = $this->autorizacionAccionBase(
                "AUTH-COST-004",
                "Aplicar precios aprobados a Catalogo",
                "bloqueada",
                "catalogo_productos.editar",
                "Respaldo de precios de Catalogo ERP y evidencia de aprobacion comercial.",
                "Actualizar precio general o listas comerciales despues de aprobacion.",
                "Bloqueada hasta definir politica de aprobacion y no tocar Ventas/ecommerce todavia.",
                array("skus_revisar_precio" => intval(isset($checksPorId["COST-CHK-002"]["total"]) ? $checksPorId["COST-CHK-002"]["total"] : 0))
            );
            $acciones[] = $this->autorizacionAccionBase(
                "AUTH-COST-005",
                "Validar/corregir costos con evidencia",
                intval(isset($checksPorId["COST-CHK-003"]["total"]) ? $checksPorId["COST-CHK-003"]["total"] : 0) > 0 ? "requiere_respaldo" : "lista",
                "catalogo_productos.editar",
                "Respaldo de costos de Catalogo ERP y evidencia de compras/inventario/XML.",
                "Revisar costos con variacion antes de cerrar precios.",
                "No modifica Inventario; cualquier correccion de costo requiere autorizacion puntual.",
                array("skus_validar_costo" => intval(isset($checksPorId["COST-CHK-003"]["total"]) ? $checksPorId["COST-CHK-003"]["total"] : 0))
            );

            $resumen = array("acciones" => count($acciones), "bloqueadas" => 0, "requieren_respaldo" => 0, "listas" => 0);
            foreach ($acciones as $accion) {
                if ($accion["estado"] === "bloqueada") {
                    $resumen["bloqueadas"]++;
                } elseif ($accion["estado"] === "requiere_respaldo") {
                    $resumen["requieren_respaldo"]++;
                } else {
                    $resumen["listas"]++;
                }
            }

            return $this->respuesta(false, "success", "Paquete de autorizaciones de cierre consultado", array(
                "resumen" => $resumen,
                "acciones" => $acciones,
                "responsables" => $responsablesItems,
                "reglas" => array(
                    "Paquete read-only: no ejecuta respaldos, no escribe BD y no aplica cambios.",
                    "Cada accion requiere autorizacion explicita y respaldo externo antes de ejecutarse.",
                    "Ventas/ecommerce siguen fuera de alcance hasta cerrar Costos/Rentabilidad."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function preciosObjetivo($filtros = array()) {
        try {
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $canales = array("menudeo", "mayoreo", "alianza");
            $items = array();
            $resumen = array(
                "evaluados" => 0,
                "requieren_subir" => 0,
                "sin_precio" => 0,
                "sin_costo" => 0,
                "ya_viables" => 0
            );

            foreach ($this->consultarFilasSku($termino, $limite) as $fila) {
                $canalFiltro = $this->opcion(isset($filtros["canal"]) ? $filtros["canal"] : "menudeo", array("menudeo", "mayoreo", "alianza"), "menudeo");
                $defaultsFiltro = $this->defaultsEscenario($canalFiltro);
                $itemFiltro = $this->calcularItem(
                    $fila,
                    $canalFiltro,
                    $defaultsFiltro["descuento_pct"],
                    $defaultsFiltro["gasto_pct"],
                    $defaultsFiltro["comision_pct"],
                    $defaultsFiltro["margen_objetivo_pct"]
                );
                if (!$this->cumpleFiltrosOperacion($itemFiltro, $filtros)) {
                    continue;
                }
                $resumen["evaluados"]++;
                $escenarios = array();
                $requiereSubir = false;
                $bloqueado = false;
                $sinCosto = false;
                $sinPrecio = false;
                foreach ($canales as $canal) {
                    $defaults = $this->defaultsEscenario($canal);
                    $item = $this->calcularItem(
                        $fila,
                        $canal,
                        $defaults["descuento_pct"],
                        $defaults["gasto_pct"],
                        $defaults["comision_pct"],
                        $defaults["margen_objetivo_pct"]
                    );
                    $precioActual = floatval($item["precio_escenario_sin_impuesto"]);
                    $precioMinimo = $item["precio_minimo_rentable"] === null ? null : floatval($item["precio_minimo_rentable"]);
                    $delta = $precioMinimo === null ? null : max(0, $precioMinimo - $precioActual);
                    $deltaPct = ($delta !== null && $precioActual > 0) ? ($delta / $precioActual) * 100 : null;
                    $estado = "viable";
                    if (in_array("sin_precio", $item["hallazgos"], true)) {
                        $estado = "sin_precio";
                        $bloqueado = true;
                        $sinPrecio = true;
                    } elseif (in_array("sin_costo", $item["hallazgos"], true)) {
                        $estado = "sin_costo";
                        $bloqueado = true;
                        $sinCosto = true;
                    } elseif ($delta !== null && $delta > 0.01) {
                        $estado = "subir_precio";
                        $requiereSubir = true;
                    } elseif ($item["riesgo_clave"] === "margen_bajo") {
                        $estado = "vigilar_margen";
                    }
                    $escenarios[$canal] = array(
                        "canal" => $canal,
                        "precio_actual" => round($precioActual, 6),
                        "precio_minimo" => $precioMinimo === null ? null : round($precioMinimo, 6),
                        "delta" => $delta === null ? null : round($delta, 6),
                        "delta_pct" => $deltaPct === null ? null : round($deltaPct, 2),
                        "margen" => $item["margen_bruto_pct"],
                        "utilidad" => $item["utilidad_estimada"],
                        "estado" => $estado,
                        "riesgo" => $item["riesgo_clave"]
                    );
                }

                if ($bloqueado) {
                    if ($sinCosto) {
                        $resumen["sin_costo"]++;
                    }
                    if ($sinPrecio) {
                        $resumen["sin_precio"]++;
                    }
                } elseif ($requiereSubir) {
                    $resumen["requieren_subir"]++;
                } else {
                    $resumen["ya_viables"]++;
                }

                $items[] = array(
                    "id_sku" => intval($fila["id_sku"]),
                    "sku" => $fila["sku"],
                    "producto" => $fila["producto"],
                    "requiere_subir" => $requiereSubir,
                    "bloqueado" => $bloqueado,
                    "escenarios" => $escenarios
                );
            }

            usort($items, function ($a, $b) {
                if ($a["bloqueado"] !== $b["bloqueado"]) {
                    return $a["bloqueado"] ? 1 : -1;
                }
                if ($a["requiere_subir"] !== $b["requiere_subir"]) {
                    return $a["requiere_subir"] ? -1 : 1;
                }
                $deltaA = 0;
                $deltaB = 0;
                foreach ($a["escenarios"] as $esc) { $deltaA = max($deltaA, floatval($esc["delta"])); }
                foreach ($b["escenarios"] as $esc) { $deltaB = max($deltaB, floatval($esc["delta"])); }
                return $deltaA < $deltaB ? 1 : -1;
            });

            return $this->respuesta(false, "success", "Precios objetivo simulados", array(
                "resumen" => $resumen,
                "items" => array_slice($items, 0, $limite),
                "reglas" => array(
                    "Simulacion read-only: no actualiza precios ni crea recomendaciones.",
                    "Precio minimo rentable considera costo, gasto operativo, comision y margen objetivo del canal.",
                    "Los resultados con costo/precio incompleto no son candidatos para cierre."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function preflightAprobacionPrecios($filtros = array()) {
        try {
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $filtrosAnalisis = $filtros;
            $filtrosAnalisis["limite"] = $limite;
            unset($filtrosAnalisis["riesgo"]);

            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }
            $variaciones = $this->variacionesCostos($filtrosAnalisis);
            $variacionesPorSku = $this->indexarItemsPorSku(empty($variaciones["error"]) ? $variaciones["depurar"]["items"] : array());

            $resumen = array(
                "evaluados" => 0,
                "aprobables" => 0,
                "requieren_revision" => 0,
                "bloqueados" => 0,
                "subir_precio" => 0,
                "conservar_precio" => 0,
                "delta_total" => 0
            );
            $items = array();

            foreach (isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array() as $item) {
                $resumen["evaluados"]++;
                $variacion = isset($variacionesPorSku[$item["sku"]]) ? $variacionesPorSku[$item["sku"]] : null;
                $dictamen = $this->dictamenAprobacionPrecioItem($item, $variacion);
                $resumen[$dictamen["estado"]]++;
                if ($dictamen["accion_precio"] === "subir_precio") {
                    $resumen["subir_precio"]++;
                } else {
                    $resumen["conservar_precio"]++;
                }
                if ($dictamen["estado"] !== "bloqueados") {
                    $resumen["delta_total"] += floatval($dictamen["delta"]);
                }
                $items[] = array(
                    "sku" => $item["sku"],
                    "producto" => $item["producto"],
                    "estado" => $dictamen["estado"],
                    "tipo" => $dictamen["tipo"],
                    "accion_precio" => $dictamen["accion_precio"],
                    "precio_actual_sin_impuesto" => $dictamen["precio_actual"],
                    "precio_minimo_rentable" => $dictamen["precio_minimo"],
                    "precio_sugerido_sin_impuesto" => $dictamen["precio_sugerido"],
                    "delta" => $dictamen["delta"],
                    "margen" => $item["margen_bruto_pct"],
                    "utilidad" => $item["utilidad_estimada"],
                    "bloqueos" => $dictamen["bloqueos"],
                    "alertas" => $dictamen["alertas"],
                    "siguiente_paso" => $dictamen["siguiente_paso"]
                );
            }

            usort($items, function ($a, $b) {
                $peso = array("bloqueados" => 0, "requieren_revision" => 1, "aprobables" => 2);
                if ($peso[$a["estado"]] !== $peso[$b["estado"]]) {
                    return $peso[$a["estado"]] - $peso[$b["estado"]];
                }
                if (floatval($a["delta"]) !== floatval($b["delta"])) {
                    return floatval($a["delta"]) < floatval($b["delta"]) ? 1 : -1;
                }
                return strcmp($a["sku"], $b["sku"]);
            });
            $resumen["delta_total"] = round($resumen["delta_total"], 6);

            return $this->respuesta(false, "success", "Preflight de aprobacion de precios consultado", array(
                "resumen" => $resumen,
                "items" => array_slice($items, 0, $limite),
                "reglas" => array(
                    "Preflight read-only: no actualiza Catalogo, listas, Ventas ni ecommerce.",
                    "Bloquea aprobacion si faltan costo, precio, fiscal o costo promedio confiable.",
                    "Marca revision si hay perdida, margen bajo, precio bajo minimo o variacion de costo antes de publicar."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function preflightAprobacionesInternas($filtros = array()) {
        try {
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $filtrosAnalisis = $filtros;
            $filtrosAnalisis["limite"] = $limite;
            unset($filtrosAnalisis["riesgo"]);

            $analisis = $this->analizarSkus($filtrosAnalisis);
            $aprobacion = $this->preflightAprobacionPrecios($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }
            if (!empty($aprobacion["error"])) {
                return $aprobacion;
            }

            $itemsAnalisis = $this->indexarItemsPorSku(isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array());
            $schemaDisponible = $this->tablaExisteSimple("erp_rentabilidad_aprobaciones_comerciales")
                && $this->tablaExisteSimple("erp_rentabilidad_aprobaciones_bitacora");
            $escenario = isset($analisis["depurar"]["escenario"]) ? $analisis["depurar"]["escenario"] : array();
            $resumen = array(
                "evaluados" => 0,
                "creables" => 0,
                "schema_pendiente" => 0,
                "requieren_revision" => 0,
                "bloqueados" => 0,
                "evidencia_congelable" => 0,
                "schema_disponible" => $schemaDisponible ? 1 : 0
            );
            $items = array();

            foreach (isset($aprobacion["depurar"]["items"]) ? $aprobacion["depurar"]["items"] : array() as $preflight) {
                $resumen["evaluados"]++;
                $sku = $preflight["sku"];
                $item = isset($itemsAnalisis[$sku]) ? $itemsAnalisis[$sku] : array();
                $evidencia = $this->evidenciaAprobacionInterna($item, $preflight, $escenario);
                if (!empty($item)) {
                    $resumen["evidencia_congelable"]++;
                }

                $accion = "bloqueada";
                $estatusSugerido = "pendiente";
                $motivo = $preflight["siguiente_paso"];
                if ($preflight["estado"] === "aprobables") {
                    if ($schemaDisponible) {
                        $accion = "crear_aprobacion";
                        $resumen["creables"]++;
                        $motivo = "Puede crearse aprobacion interna como evidencia; no aplica precio.";
                    } else {
                        $accion = "schema_pendiente";
                        $resumen["schema_pendiente"]++;
                        $motivo = "El esquema de aprobaciones internas aun no esta aplicado; solo puede auditarse.";
                    }
                } elseif ($preflight["estado"] === "requieren_revision") {
                    $accion = "requiere_revision";
                    $estatusSugerido = "requiere_revision";
                    $resumen["requieren_revision"]++;
                } else {
                    $accion = "bloqueada";
                    $estatusSugerido = "requiere_revision";
                    $resumen["bloqueados"]++;
                }

                $items[] = array(
                    "id_sku" => isset($item["id_sku"]) ? intval($item["id_sku"]) : 0,
                    "sku" => $sku,
                    "producto" => $preflight["producto"],
                    "canal" => isset($escenario["canal"]) ? $escenario["canal"] : (isset($filtros["canal"]) ? $filtros["canal"] : "menudeo"),
                    "accion_preflight" => $accion,
                    "estado_precio" => $preflight["estado"],
                    "estatus_sugerido" => $estatusSugerido,
                    "precio_actual_sin_impuesto" => $preflight["precio_actual_sin_impuesto"],
                    "precio_minimo_rentable" => $preflight["precio_minimo_rentable"],
                    "precio_aprobado_sin_impuesto" => $preflight["precio_sugerido_sin_impuesto"],
                    "delta" => $preflight["delta"],
                    "bloqueos" => $preflight["bloqueos"],
                    "alertas" => $preflight["alertas"],
                    "evidencia" => $evidencia,
                    "siguiente_paso" => $motivo
                );
            }

            usort($items, function ($a, $b) {
                $peso = array("crear_aprobacion" => 0, "schema_pendiente" => 1, "requiere_revision" => 2, "bloqueada" => 3);
                if ($peso[$a["accion_preflight"]] !== $peso[$b["accion_preflight"]]) {
                    return $peso[$a["accion_preflight"]] - $peso[$b["accion_preflight"]];
                }
                if (floatval($a["delta"]) !== floatval($b["delta"])) {
                    return floatval($a["delta"]) < floatval($b["delta"]) ? 1 : -1;
                }
                return strcmp($a["sku"], $b["sku"]);
            });

            return $this->respuesta(false, "success", "Preflight de aprobaciones internas consultado", array(
                "resumen" => $resumen,
                "items" => array_slice($items, 0, $limite),
                "tablas_requeridas" => array(
                    "erp_rentabilidad_aprobaciones_comerciales",
                    "erp_rentabilidad_aprobaciones_bitacora"
                ),
                "reglas" => array(
                    "Preflight read-only: no crea aprobaciones, no guarda bitacora y no aplica precios.",
                    "Una aprobacion interna solo congela evidencia comercial dentro de Rentabilidad.",
                    "Si el esquema esta pendiente, los SKUs aprobables quedan como schema_pendiente hasta autorizacion de BD."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function guardarAprobacionInterna($datos, $idUsuario = 0) {
        $db = $this->getConexion();
        try {
            $autorizacion = $this->validarAutorizacionEscritura(
                $datos,
                "AUTORIZO CREAR APROBACION INTERNA",
                "crear aprobacion comercial interna"
            );
            if (!empty($autorizacion["error"])) {
                return $autorizacion;
            }
            if (!$this->schemaAprobacionesInternasDisponible()) {
                return $this->respuesta(true, "warning", "El esquema de aprobaciones internas no esta aplicado", array(
                    "tablas_requeridas" => array("erp_rentabilidad_aprobaciones_comerciales", "erp_rentabilidad_aprobaciones_bitacora")
                ));
            }

            $sku = trim(isset($datos["sku"]) ? $datos["sku"] : (isset($datos["q"]) ? $datos["q"] : ""));
            if ($sku === "") {
                return $this->respuesta(true, "warning", "Indica el SKU para crear la aprobacion interna");
            }
            $filtros = $datos;
            $filtros["q"] = $sku;
            $filtros["limite"] = 20;
            $preflight = $this->preflightAprobacionesInternas($filtros);
            if (!empty($preflight["error"])) {
                return $preflight;
            }
            $item = $this->buscarItemSku(isset($preflight["depurar"]["items"]) ? $preflight["depurar"]["items"] : array(), $sku);
            if (!$item) {
                return $this->respuesta(true, "warning", "No se encontro candidato de aprobacion para el SKU");
            }
            if ($item["accion_preflight"] !== "crear_aprobacion") {
                return $this->respuesta(true, "warning", "El SKU no es creable como aprobacion interna", array(
                    "sku" => $sku,
                    "accion_preflight" => $item["accion_preflight"],
                    "estado_precio" => $item["estado_precio"],
                    "siguiente_paso" => $item["siguiente_paso"]
                ));
            }

            $evidencia = isset($item["evidencia"]) ? $item["evidencia"] : array();
            $escenario = isset($evidencia["escenario"]) ? $evidencia["escenario"] : array();
            $folio = "APRC-" . date("Ymd-His");

            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO erp_rentabilidad_aprobaciones_comerciales
                (folio, id_sku, sku, producto, canal, costo_real_sin_impuesto, origen_costo,
                 precio_actual_sin_impuesto, precio_minimo_rentable, precio_aprobado_sin_impuesto,
                 margen_bruto_pct, utilidad_estimada, descuento_pct, gasto_operativo_pct,
                 comision_pct, margen_objetivo_pct, dictamen_json, evidencia_json, bloqueos_json,
                 alertas_json, estatus, comentario, respaldo_externo_ref, creado_por)
                VALUES (:folio, :id_sku, :sku, :producto, :canal, :costo, :origen,
                 :precio_actual, :precio_minimo, :precio_aprobado, :margen, :utilidad,
                 :descuento, :gasto, :comision, :margen_objetivo, :dictamen, :evidencia,
                 :bloqueos, :alertas, 'pendiente', :comentario, :respaldo, :usuario)");
            $stmt->execute(array(
                ":folio" => $folio,
                ":id_sku" => intval($item["id_sku"]),
                ":sku" => $item["sku"],
                ":producto" => $item["producto"],
                ":canal" => $item["canal"],
                ":costo" => $evidencia["costo_real_sin_impuesto"],
                ":origen" => $evidencia["origen_costo"],
                ":precio_actual" => $item["precio_actual_sin_impuesto"],
                ":precio_minimo" => $item["precio_minimo_rentable"],
                ":precio_aprobado" => $item["precio_aprobado_sin_impuesto"],
                ":margen" => $evidencia["margen_bruto_pct"],
                ":utilidad" => $evidencia["utilidad_estimada"],
                ":descuento" => isset($escenario["descuento_pct"]) ? $escenario["descuento_pct"] : 0,
                ":gasto" => isset($escenario["gasto_operativo_pct"]) ? $escenario["gasto_operativo_pct"] : 0,
                ":comision" => isset($escenario["comision_pct"]) ? $escenario["comision_pct"] : 0,
                ":margen_objetivo" => isset($escenario["margen_objetivo_pct"]) ? $escenario["margen_objetivo_pct"] : 0,
                ":dictamen" => json_encode(array("accion_preflight" => $item["accion_preflight"], "estado_precio" => $item["estado_precio"]), JSON_UNESCAPED_UNICODE),
                ":evidencia" => json_encode($evidencia, JSON_UNESCAPED_UNICODE),
                ":bloqueos" => json_encode($item["bloqueos"], JSON_UNESCAPED_UNICODE),
                ":alertas" => json_encode($item["alertas"], JSON_UNESCAPED_UNICODE),
                ":comentario" => trim(isset($datos["comentario"]) ? $datos["comentario"] : ""),
                ":respaldo" => $autorizacion["depurar"]["respaldo_externo_ref"],
                ":usuario" => intval($idUsuario) ?: null
            ));
            $idAprobacion = intval($db->lastInsertId());
            $this->insertarBitacoraAprobacion($db, $idAprobacion, "crear", null, "pendiente", "Aprobacion interna creada", null, array("folio" => $folio), $autorizacion["depurar"]["respaldo_externo_ref"], $idUsuario);
            $db->commit();

            return $this->respuesta(false, "success", "Aprobacion interna creada", array(
                "id_aprobacion" => $idAprobacion,
                "folio" => $folio,
                "sku" => $item["sku"],
                "respaldo_externo_ref" => $autorizacion["depurar"]["respaldo_externo_ref"]
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function resolverAprobacionInterna($datos, $idUsuario = 0) {
        $db = $this->getConexion();
        try {
            $autorizacion = $this->validarAutorizacionEscritura(
                $datos,
                "AUTORIZO RESOLVER APROBACION INTERNA",
                "resolver aprobacion comercial interna"
            );
            if (!empty($autorizacion["error"])) {
                return $autorizacion;
            }
            if (!$this->schemaAprobacionesInternasDisponible()) {
                return $this->respuesta(true, "warning", "El esquema de aprobaciones internas no esta aplicado", array(
                    "tablas_requeridas" => array("erp_rentabilidad_aprobaciones_comerciales", "erp_rentabilidad_aprobaciones_bitacora")
                ));
            }

            $id = intval(isset($datos["id_aprobacion"]) ? $datos["id_aprobacion"] : 0);
            $accion = $this->opcion(isset($datos["accion"]) ? $datos["accion"] : "", array("aprobar", "rechazar", "cancelar"), "");
            if ($id <= 0 || $accion === "") {
                return $this->respuesta(true, "warning", "Indica aprobacion y accion valida");
            }
            $nuevo = $accion === "aprobar" ? "aprobada" : ($accion === "rechazar" ? "rechazada" : "cancelada");
            $stmt = $db->prepare("SELECT * FROM erp_rentabilidad_aprobaciones_comerciales WHERE id_aprobacion=:id LIMIT 1");
            $stmt->execute(array(":id" => $id));
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                return $this->respuesta(true, "warning", "No se encontro la aprobacion interna");
            }
            if (!in_array($actual["estatus"], array("pendiente", "requiere_revision"), true)) {
                return $this->respuesta(true, "warning", "La aprobacion no esta en estado resoluble", array("estatus" => $actual["estatus"]));
            }

            $db->beginTransaction();
            $upd = $db->prepare("UPDATE erp_rentabilidad_aprobaciones_comerciales
                SET estatus=:estatus, aprobado_por=:usuario, fecha_aprobacion=NOW(),
                    comentario=:comentario, respaldo_externo_ref=:respaldo
                WHERE id_aprobacion=:id");
            $upd->execute(array(
                ":estatus" => $nuevo,
                ":usuario" => intval($idUsuario) ?: null,
                ":comentario" => trim(isset($datos["comentario"]) ? $datos["comentario"] : ""),
                ":respaldo" => $autorizacion["depurar"]["respaldo_externo_ref"],
                ":id" => $id
            ));
            $despues = $actual;
            $despues["estatus"] = $nuevo;
            $this->insertarBitacoraAprobacion($db, $id, $accion, $actual["estatus"], $nuevo, trim(isset($datos["comentario"]) ? $datos["comentario"] : ""), $actual, $despues, $autorizacion["depurar"]["respaldo_externo_ref"], $idUsuario);
            $db->commit();

            return $this->respuesta(false, "success", "Aprobacion interna resuelta", array(
                "id_aprobacion" => $id,
                "estatus" => $nuevo,
                "respaldo_externo_ref" => $autorizacion["depurar"]["respaldo_externo_ref"]
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function listarAprobacionesInternas($filtros = array()) {
        try {
            if (!$this->schemaAprobacionesInternasDisponible()) {
                return $this->respuesta(false, "info", "Esquema de aprobaciones internas pendiente", array(
                    "resumen" => array(
                        "schema_disponible" => 0,
                        "total" => 0,
                        "pendiente" => 0,
                        "aprobada" => 0,
                        "rechazada" => 0,
                        "cancelada" => 0,
                        "obsoleta" => 0,
                        "requiere_revision" => 0
                    ),
                    "items" => array(),
                    "reglas" => array(
                        "Listado read-only: no crea ni resuelve aprobaciones.",
                        "El esquema debe aplicarse antes de consultar aprobaciones persistentes."
                    )
                ));
            }

            $db = $this->getConexion();
            $limite = max(1, min(100, intval(isset($filtros["limite"]) ? $filtros["limite"] : 30)));
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $estatus = $this->opcion(isset($filtros["estatus"]) ? $filtros["estatus"] : "", array("", "pendiente", "aprobada", "rechazada", "cancelada", "obsoleta", "requiere_revision"), "");
            $canal = $this->opcion(isset($filtros["canal"]) ? $filtros["canal"] : "", array("", "menudeo", "mayoreo", "alianza", "otro"), "");
            $buscar = "%" . $termino . "%";

            $sql = "SELECT id_aprobacion, folio, id_sku, sku, producto, canal,
                    costo_real_sin_impuesto, origen_costo, precio_actual_sin_impuesto,
                    precio_minimo_rentable, precio_aprobado_sin_impuesto, margen_bruto_pct,
                    utilidad_estimada, estatus, comentario, respaldo_externo_ref,
                    creado_por, aprobado_por, fecha_registro, fecha_aprobacion, fecha_revision
                FROM erp_rentabilidad_aprobaciones_comerciales
                WHERE (:termino='' OR sku LIKE :buscar OR producto LIKE :buscar OR folio LIKE :buscar)
                  AND (:estatus='' OR estatus=:estatus)
                  AND (:canal='' OR canal=:canal)
                ORDER BY id_aprobacion DESC
                LIMIT " . $limite;
            $stmt = $db->prepare($sql);
            $stmt->execute(array(
                ":termino" => $termino,
                ":buscar" => $buscar,
                ":estatus" => $estatus,
                ":canal" => $canal
            ));
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resumen = array(
                "schema_disponible" => 1,
                "total" => count($items),
                "pendiente" => 0,
                "aprobada" => 0,
                "rechazada" => 0,
                "cancelada" => 0,
                "obsoleta" => 0,
                "requiere_revision" => 0
            );
            foreach ($items as $item) {
                if (isset($resumen[$item["estatus"]])) {
                    $resumen[$item["estatus"]]++;
                }
            }

            return $this->respuesta(false, "success", "Aprobaciones internas consultadas", array(
                "resumen" => $resumen,
                "items" => $items,
                "reglas" => array(
                    "Listado read-only: no crea aprobaciones, no resuelve estatus y no aplica precios.",
                    "Las aprobaciones internas son evidencia dentro de Rentabilidad; no modifican Catalogo ni Ventas."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function paqueteAutorizacionAprobaciones($filtros = array()) {
        try {
            require_once __DIR__ . "/RentabilidadEsquema.php";

            $limite = max(10, min(120, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $filtrosPaquete = $filtros;
            $filtrosPaquete["limite"] = $limite;

            $esquema = new RentabilidadEsquema();
            $schemaDry = $esquema->planAprobacionesComerciales(false);
            $preflight = $this->preflightAprobacionesInternas($filtrosPaquete);
            $estadoModulo = $this->estadoModuloRentabilidad($filtrosPaquete);
            $auditoriaFinal = $this->auditoriaFinalModulo($filtrosPaquete);
            $listado = $this->listarAprobacionesInternas(array(
                "q" => isset($filtros["q"]) ? $filtros["q"] : "",
                "canal" => isset($filtros["canal"]) ? $filtros["canal"] : "",
                "limite" => 30
            ));

            if (!empty($schemaDry["error"])) {
                return $schemaDry;
            }
            if (!empty($preflight["error"])) {
                return $preflight;
            }
            if (!empty($estadoModulo["error"])) {
                return $estadoModulo;
            }
            if (!empty($auditoriaFinal["error"])) {
                return $auditoriaFinal;
            }
            if (!empty($listado["error"])) {
                return $listado;
            }

            $schemaResumen = isset($schemaDry["depurar"]["resumen"]) ? $schemaDry["depurar"]["resumen"] : array();
            $preflightResumen = isset($preflight["depurar"]["resumen"]) ? $preflight["depurar"]["resumen"] : array();
            $estadoResumen = isset($estadoModulo["depurar"]["resumen"]) ? $estadoModulo["depurar"]["resumen"] : array();
            $auditoriaResumen = isset($auditoriaFinal["depurar"]["resumen"]) ? $auditoriaFinal["depurar"]["resumen"] : array();
            $listadoResumen = isset($listado["depurar"]["resumen"]) ? $listado["depurar"]["resumen"] : array();

            $schemaPendiente = intval(isset($schemaResumen["pendientes"]) ? $schemaResumen["pendientes"] : 0);
            $schemaDisponible = intval(isset($preflightResumen["schema_disponible"]) ? $preflightResumen["schema_disponible"] : 0);
            $creables = intval(isset($preflightResumen["creables"]) ? $preflightResumen["creables"] : 0);
            $bloqueados = intval(isset($preflightResumen["bloqueados"]) ? $preflightResumen["bloqueados"] : 0);
            $estado = $schemaDisponible ? "listo_para_operar_aprobaciones" : "requiere_autorizacion_esquema";
            if ($schemaDisponible && $creables <= 0) {
                $estado = $bloqueados > 0 ? "sin_candidatos_creables" : "sin_casos";
            }

            $acciones = array();
            if (!$schemaDisponible) {
                $acciones[] = array(
                    "id" => "AUTH-APROB-SCHEMA",
                    "prioridad" => "alta",
                    "estado" => "requiere_autorizacion",
                    "accion" => "Aplicar esquema de aprobaciones internas solo con respaldo externo y frase exacta.",
                    "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_schema_apply_authorized.php --execute --respaldo=RUTA_O_REFERENCIA --confirmar=\"AUTORIZO APLICAR ESQUEMA APROBACIONES INTERNAS\""
                );
            }
            $acciones[] = array(
                "id" => "AUTH-APROB-VALIDAR",
                "prioridad" => "media",
                "estado" => "pendiente",
                "accion" => "Ejecutar validacion posterior y UAT de ciclo de vida antes de crear aprobaciones reales.",
                "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobacion_interna_lifecycle.php"
            );
            $acciones[] = array(
                "id" => "AUTH-APROB-CREAR",
                "prioridad" => "media",
                "estado" => $schemaDisponible && $creables > 0 ? "habilitable" : "bloqueado",
                "accion" => "Crear aprobaciones internas solo para SKUs creables; no aplica precios ni toca Catalogo/Ventas.",
                "comando" => "Requiere frase AUTORIZO CREAR APROBACION INTERNA y respaldo externo."
            );

            return $this->respuesta(false, "success", "Paquete de autorizacion de aprobaciones consultado", array(
                "resumen" => array(
                    "estado" => $estado,
                    "schema_disponible" => $schemaDisponible,
                    "schema_pendiente" => $schemaPendiente,
                    "schema_existente" => intval(isset($schemaResumen["existentes"]) ? $schemaResumen["existentes"] : 0),
                    "aprobaciones_creables" => $creables,
                    "aprobaciones_bloqueadas" => $bloqueados,
                    "aprobaciones_guardadas" => intval(isset($listadoResumen["total"]) ? $listadoResumen["total"] : 0),
                    "estado_modulo" => isset($estadoResumen["estado_general"]) ? $estadoResumen["estado_general"] : "",
                    "construccion" => isset($auditoriaResumen["estado_construccion"]) ? $auditoriaResumen["estado_construccion"] : "",
                    "uso_comercial" => isset($auditoriaResumen["estado_uso_comercial"]) ? $auditoriaResumen["estado_uso_comercial"] : ""
                ),
                "schema" => array(
                    "tablas" => isset($schemaDry["depurar"]["tablas"]) ? $schemaDry["depurar"]["tablas"] : array(),
                    "resumen" => $schemaResumen
                ),
                "preflight" => array(
                    "resumen" => $preflightResumen,
                    "muestra" => array_slice(isset($preflight["depurar"]["items"]) ? $preflight["depurar"]["items"] : array(), 0, 8)
                ),
                "acciones" => $acciones,
                "autorizaciones_requeridas" => array(
                    "aplicar_esquema" => "AUTORIZO APLICAR ESQUEMA APROBACIONES INTERNAS",
                    "crear_aprobacion" => "AUTORIZO CREAR APROBACION INTERNA",
                    "resolver_aprobacion" => "AUTORIZO RESOLVER APROBACION INTERNA"
                ),
                "validaciones" => array(
                    "dryrun_schema" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_schema_dryrun.php",
                    "preflight_autorizacion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_autorizacion_preflight_readonly.php",
                    "suite_autorizacion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_autorizacion_suite_readonly.php",
                    "runbook_autorizacion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_runbook_readonly.php",
                    "preflight_respaldo" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_respaldo_preflight_readonly.php --respaldo=\"RUTA_O_REFERENCIA\"",
                    "post_schema_readonly" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_post_schema_readonly.php",
                    "suite_readonly" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_suite_readonly.php"
                ),
                "reglas" => array(
                    "Consulta read-only: no ejecuta DDL, no crea aprobaciones y no modifica precios.",
                    "Aplicar esquema solo habilita evidencia interna; no publica nada en Catalogo, Ventas, ecommerce, Pedidos ni Mayoreo.",
                    "Crear o resolver aprobaciones despues del esquema conserva candados de respaldo externo y frase exacta."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function sensibilidadRentabilidad($filtros = array()) {
        try {
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $costoAlzaPct = max(0, min(100, floatval(isset($filtros["costo_alza_pct"]) ? $filtros["costo_alza_pct"] : 5)));
            $precioBajaPct = max(0, min(100, floatval(isset($filtros["precio_baja_pct"]) ? $filtros["precio_baja_pct"] : 5)));
            $filtrosAnalisis = $filtros;
            $filtrosAnalisis["limite"] = $limite;
            unset($filtrosAnalisis["riesgo"]);
            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }

            $escenario = isset($analisis["depurar"]["escenario"]) ? $analisis["depurar"]["escenario"] : array();
            $gastoPct = floatval(isset($escenario["gasto_pct"]) ? $escenario["gasto_pct"] : 0);
            $comisionPct = floatval(isset($escenario["comision_pct"]) ? $escenario["comision_pct"] : 0);
            $resumen = array(
                "evaluados" => 0,
                "incompletos" => 0,
                "vulnerables" => 0,
                "resisten" => 0,
                "quiebre_costo" => 0,
                "quiebre_precio" => 0,
                "quiebre_combinado" => 0,
                "colchon_utilidad_total" => 0
            );
            $items = array();

            foreach (isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array() as $item) {
                $resumen["evaluados"]++;
                if ($item["costo_real_sin_impuesto"] <= 0 || $item["precio_escenario_sin_impuesto"] <= 0) {
                    $resumen["incompletos"]++;
                    continue;
                }

                $base = $this->simularSensibilidadItem(
                    $item["costo_real_sin_impuesto"],
                    $item["precio_escenario_sin_impuesto"],
                    $gastoPct,
                    $comisionPct
                );
                $costoAlza = $this->simularSensibilidadItem(
                    $item["costo_real_sin_impuesto"] * (1 + ($costoAlzaPct / 100)),
                    $item["precio_escenario_sin_impuesto"],
                    $gastoPct,
                    $comisionPct
                );
                $precioBaja = $this->simularSensibilidadItem(
                    $item["costo_real_sin_impuesto"],
                    $item["precio_escenario_sin_impuesto"] * (1 - ($precioBajaPct / 100)),
                    $gastoPct,
                    $comisionPct
                );
                $combinado = $this->simularSensibilidadItem(
                    $item["costo_real_sin_impuesto"] * (1 + ($costoAlzaPct / 100)),
                    $item["precio_escenario_sin_impuesto"] * (1 - ($precioBajaPct / 100)),
                    $gastoPct,
                    $comisionPct
                );

                $colchon = max(0, floatval($base["utilidad"]));
                $resumen["colchon_utilidad_total"] += $colchon;
                if ($costoAlza["estado"] !== "rentable") { $resumen["quiebre_costo"]++; }
                if ($precioBaja["estado"] !== "rentable") { $resumen["quiebre_precio"]++; }
                if ($combinado["estado"] !== "rentable") { $resumen["quiebre_combinado"]++; }
                $vulnerable = $combinado["estado"] !== "rentable";
                if ($vulnerable) {
                    $resumen["vulnerables"]++;
                } else {
                    $resumen["resisten"]++;
                }

                $items[] = array(
                    "sku" => $item["sku"],
                    "producto" => $item["producto"],
                    "costo" => $item["costo_real_sin_impuesto"],
                    "precio" => $item["precio_escenario_sin_impuesto"],
                    "base" => $base,
                    "costo_alza" => $costoAlza,
                    "precio_baja" => $precioBaja,
                    "combinado" => $combinado,
                    "vulnerable" => $vulnerable,
                    "colchon_utilidad" => round($colchon, 6),
                    "recomendacion" => $vulnerable
                        ? "No cerrar precio sin revisar costo/precio: el escenario combinado rompe rentabilidad."
                        : "Resiste el shock definido; conservar evidencia antes de publicar."
                );
            }

            usort($items, function ($a, $b) {
                if ($a["vulnerable"] !== $b["vulnerable"]) {
                    return $a["vulnerable"] ? -1 : 1;
                }
                return floatval($a["combinado"]["utilidad"]) < floatval($b["combinado"]["utilidad"]) ? -1 : 1;
            });
            $resumen["colchon_utilidad_total"] = round($resumen["colchon_utilidad_total"], 6);

            return $this->respuesta(false, "success", "Sensibilidad de rentabilidad consultada", array(
                "shock" => array(
                    "costo_alza_pct" => $costoAlzaPct,
                    "precio_baja_pct" => $precioBajaPct,
                    "gasto_pct" => $gastoPct,
                    "comision_pct" => $comisionPct
                ),
                "resumen" => $resumen,
                "items" => array_slice($items, 0, $limite),
                "reglas" => array(
                    "Simulacion read-only: no modifica costos, precios ni escenarios guardados.",
                    "El shock combinado aplica alza de costo y baja de precio al mismo tiempo.",
                    "Vulnerable significa que el shock combinado deja utilidad no positiva o margen bajo."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function tableroEjecutivo($filtros = array()) {
        try {
            $filtrosAnalisis = $filtros;
            unset($filtrosAnalisis["riesgo"]);
            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }
            $items = isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array();
            $escenario = isset($analisis["depurar"]["escenario"]) ? $analisis["depurar"]["escenario"] : array();
            $resumen = isset($analisis["depurar"]["resumen"]) ? $analisis["depurar"]["resumen"] : array();

            $metricas = array(
                "utilidad_estimada_total" => 0,
                "utilidad_negativa_total" => 0,
                "valor_inventario_en_riesgo" => 0,
                "valor_inventario_rentable" => 0,
                "skus_con_stock" => 0,
                "skus_sin_stock" => 0
            );
            $perdidas = array();
            $oportunidades = array();
            $inventarioRiesgo = array();
            $accionesPrecio = array();

            foreach ($items as $item) {
                $utilidad = floatval($item["utilidad_estimada"]);
                $valorInventario = floatval($item["inventario"]["valor_total"]);
                $disponible = floatval($item["inventario"]["disponible_total"]);
                $metricas["utilidad_estimada_total"] += $utilidad;
                if ($utilidad < 0) {
                    $metricas["utilidad_negativa_total"] += $utilidad;
                }
                if ($disponible > 0) {
                    $metricas["skus_con_stock"]++;
                } else {
                    $metricas["skus_sin_stock"]++;
                }
                if (in_array($item["riesgo_clave"], array("perdida", "margen_bajo", "incompleto"), true)) {
                    $metricas["valor_inventario_en_riesgo"] += $valorInventario;
                } else {
                    $metricas["valor_inventario_rentable"] += $valorInventario;
                }

                $base = array(
                    "sku" => $item["sku"],
                    "producto" => $item["producto"],
                    "riesgo" => $item["riesgo_clave"],
                    "costo" => $item["costo_real_sin_impuesto"],
                    "precio" => $item["precio_escenario_sin_impuesto"],
                    "margen" => $item["margen_bruto_pct"],
                    "utilidad" => $item["utilidad_estimada"],
                    "precio_minimo" => $item["precio_minimo_rentable"],
                    "valor_inventario" => $valorInventario,
                    "disponible" => $disponible,
                    "recomendacion" => $item["recomendacion"]
                );
                if ($item["riesgo_clave"] === "perdida") {
                    $perdidas[] = $base;
                }
                if ($item["riesgo_clave"] === "rentable" && $utilidad > 0) {
                    $oportunidades[] = $base;
                }
                if ($valorInventario > 0 && in_array($item["riesgo_clave"], array("perdida", "margen_bajo", "incompleto"), true)) {
                    $inventarioRiesgo[] = $base;
                }
                if ($item["precio_minimo_rentable"] !== null && floatval($item["precio_minimo_rentable"]) > floatval($item["precio_escenario_sin_impuesto"]) + 0.01) {
                    $base["delta"] = round(floatval($item["precio_minimo_rentable"]) - floatval($item["precio_escenario_sin_impuesto"]), 6);
                    $accionesPrecio[] = $base;
                }
            }

            usort($perdidas, function ($a, $b) { return $a["utilidad"] < $b["utilidad"] ? -1 : 1; });
            usort($oportunidades, function ($a, $b) { return $a["utilidad"] < $b["utilidad"] ? 1 : -1; });
            usort($inventarioRiesgo, function ($a, $b) { return $a["valor_inventario"] < $b["valor_inventario"] ? 1 : -1; });
            usort($accionesPrecio, function ($a, $b) { return $a["delta"] < $b["delta"] ? 1 : -1; });

            foreach ($metricas as $clave => $valor) {
                if (is_float($valor)) {
                    $metricas[$clave] = round($valor, 6);
                }
            }

            return $this->respuesta(false, "success", "Tablero ejecutivo consultado", array(
                "escenario" => $escenario,
                "resumen" => $resumen,
                "metricas" => $metricas,
                "perdidas" => array_slice($perdidas, 0, 10),
                "oportunidades" => array_slice($oportunidades, 0, 10),
                "inventario_riesgo" => array_slice($inventarioRiesgo, 0, 10),
                "acciones_precio" => array_slice($accionesPrecio, 0, 10),
                "reglas" => array(
                    "Tablero read-only: no guarda snapshots, no crea recomendaciones y no modifica precios.",
                    "Inventario en riesgo se calcula solo como exposicion economica, sin mover stock.",
                    "Acciones de precio son simulaciones contra precio minimo rentable del escenario activo."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function revisionOperativa($filtros = array()) {
        try {
            $filtrosAnalisis = $filtros;
            unset($filtrosAnalisis["riesgo"]);
            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }

            $items = isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array();
            $grupos = array(
                "perdidas" => array("titulo" => "Perdidas", "tipo" => "danger", "items" => array()),
                "subir_precio" => array("titulo" => "Subir precio", "tipo" => "warning", "items" => array()),
                "completar_costo" => array("titulo" => "Completar costo", "tipo" => "info", "items" => array()),
                "completar_precio" => array("titulo" => "Completar precio", "tipo" => "info", "items" => array()),
                "completar_fiscal" => array("titulo" => "Completar fiscal", "tipo" => "warning", "items" => array()),
                "inventario_expuesto" => array("titulo" => "Inventario expuesto", "tipo" => "danger", "items" => array()),
                "oportunidad_stock" => array("titulo" => "Oportunidad con stock", "tipo" => "success", "items" => array())
            );

            foreach ($items as $item) {
                $base = array(
                    "sku" => $item["sku"],
                    "producto" => $item["producto"],
                    "riesgo" => $item["riesgo_clave"],
                    "riesgo_tipo" => $item["riesgo_tipo"],
                    "costo" => $item["costo_real_sin_impuesto"],
                    "precio" => $item["precio_escenario_sin_impuesto"],
                    "margen" => $item["margen_bruto_pct"],
                    "utilidad" => $item["utilidad_estimada"],
                    "precio_minimo" => $item["precio_minimo_rentable"],
                    "delta" => $item["precio_minimo_rentable"] === null ? null : round(max(0, floatval($item["precio_minimo_rentable"]) - floatval($item["precio_escenario_sin_impuesto"])), 6),
                    "disponible" => $item["inventario"]["disponible_total"],
                    "valor_inventario" => $item["inventario"]["valor_total"],
                    "hallazgos" => $item["hallazgos"],
                    "recomendacion" => $item["recomendacion"]
                );

                if ($item["riesgo_clave"] === "perdida") {
                    $grupos["perdidas"]["items"][] = $base;
                }
                if ($base["delta"] !== null && $base["delta"] > 0.01) {
                    $grupos["subir_precio"]["items"][] = $base;
                }
                if (in_array("sin_costo", $item["hallazgos"], true)) {
                    $grupos["completar_costo"]["items"][] = $base;
                }
                if (in_array("sin_precio", $item["hallazgos"], true)) {
                    $grupos["completar_precio"]["items"][] = $base;
                }
                if (in_array("fiscal_incompleto", $item["hallazgos"], true)) {
                    $grupos["completar_fiscal"]["items"][] = $base;
                }
                if (floatval($base["valor_inventario"]) > 0 && in_array($item["riesgo_clave"], array("perdida", "margen_bajo", "incompleto"), true)) {
                    $grupos["inventario_expuesto"]["items"][] = $base;
                }
                if (floatval($base["disponible"]) > 0 && $item["riesgo_clave"] === "rentable") {
                    $grupos["oportunidad_stock"]["items"][] = $base;
                }
            }

            $ordenamientos = array(
                "perdidas" => "utilidad_asc",
                "subir_precio" => "delta_desc",
                "completar_costo" => "sku_asc",
                "completar_precio" => "sku_asc",
                "completar_fiscal" => "sku_asc",
                "inventario_expuesto" => "inventario_desc",
                "oportunidad_stock" => "utilidad_desc"
            );
            foreach ($grupos as $clave => $grupo) {
                $itemsGrupo = $grupo["items"];
                $this->ordenarRevisionGrupo($itemsGrupo, $ordenamientos[$clave]);
                $grupos[$clave]["total"] = count($itemsGrupo);
                $grupos[$clave]["items"] = array_slice($itemsGrupo, 0, 20);
            }

            return $this->respuesta(false, "success", "Revision operativa consultada", array(
                "total_skus_evaluados" => count($items),
                "grupos" => $grupos,
                "reglas" => array(
                    "Revision read-only: no crea pendientes, no guarda snapshots y no modifica precios.",
                    "Las bandejas son vistas operativas derivadas del analisis vigente.",
                    "Fiscal y catalogo incompletos se muestran como senal de calidad, no como tarea ejecutada desde Rentabilidad."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function workflowComercial($filtros = array()) {
        try {
            $preflight = $this->preflightRecomendaciones($filtros);
            $aprobacion = $this->preflightAprobacionPrecios($filtros);
            $prioridades = $this->prioridadesCierreComercial($filtros);
            $pendientes = $this->listarRecomendaciones(array("estatus" => "pendiente"));

            if (!empty($preflight["error"])) {
                return $preflight;
            }
            if (!empty($aprobacion["error"])) {
                return $aprobacion;
            }
            if (!empty($prioridades["error"])) {
                return $prioridades;
            }

            $resPreflight = isset($preflight["depurar"]["resumen"]) ? $preflight["depurar"]["resumen"] : array();
            $resAprobacion = isset($aprobacion["depurar"]["resumen"]) ? $aprobacion["depurar"]["resumen"] : array();
            $resPrioridades = isset($prioridades["depurar"]["resumen"]) ? $prioridades["depurar"]["resumen"] : array();
            $itemsPendientes = empty($pendientes["error"]) && isset($pendientes["depurar"]["items"]) ? $pendientes["depurar"]["items"] : array();

            $bandejas = array(
                "crear_pendientes" => array(
                    "titulo" => "Crear pendientes comerciales",
                    "estado" => intval(isset($resPreflight["creables"]) ? $resPreflight["creables"] : 0) > 0 ? "requiere_autorizacion" : "sin_casos",
                    "total" => intval(isset($resPreflight["creables"]) ? $resPreflight["creables"] : 0),
                    "permiso_requerido" => "rentabilidad.snapshot",
                    "siguiente_paso" => "Crear recomendaciones persistentes requiere respaldo externo y frase de autorizacion.",
                    "items" => array_slice(isset($preflight["depurar"]["items"]) ? $preflight["depurar"]["items"] : array(), 0, 8)
                ),
                "resolver_pendientes" => array(
                    "titulo" => "Resolver recomendaciones pendientes",
                    "estado" => count($itemsPendientes) > 0 ? "requiere_autorizacion" : "sin_casos",
                    "total" => count($itemsPendientes),
                    "permiso_requerido" => "rentabilidad.snapshot",
                    "siguiente_paso" => "Aprobar, rechazar o cancelar recomendaciones no aplica precios; solo deja evidencia comercial.",
                    "items" => array_slice($itemsPendientes, 0, 8)
                ),
                "aprobar_precios" => array(
                    "titulo" => "Aprobar precios como evidencia",
                    "estado" => intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0) > 0 ? "requiere_politica" : "bloqueado",
                    "total" => intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0),
                    "permiso_requerido" => "rentabilidad.snapshot",
                    "siguiente_paso" => "La aprobacion comercial puede construirse como evidencia interna, sin aplicar a Catalogo.",
                    "items" => array_slice(isset($aprobacion["depurar"]["items"]) ? $aprobacion["depurar"]["items"] : array(), 0, 8)
                ),
                "trabajo_prioritario" => array(
                    "titulo" => "Trabajo prioritario read-only",
                    "estado" => intval(isset($resPrioridades["prioridades"]) ? $resPrioridades["prioridades"] : 0) > 0 ? "activo" : "sin_casos",
                    "total" => intval(isset($resPrioridades["prioridades"]) ? $resPrioridades["prioridades"] : 0),
                    "permiso_requerido" => "rentabilidad.ver",
                    "siguiente_paso" => "Usar prioridades para ordenar revision; no crea tareas ni modifica datos.",
                    "items" => array_slice(isset($prioridades["depurar"]["items"]) ? $prioridades["depurar"]["items"] : array(), 0, 8)
                )
            );

            $resumen = array(
                "candidatos_creables" => intval(isset($resPreflight["creables"]) ? $resPreflight["creables"] : 0),
                "pendientes" => count($itemsPendientes),
                "aprobables" => intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0),
                "bloqueados_aprobacion" => intval(isset($resAprobacion["bloqueados"]) ? $resAprobacion["bloqueados"] : 0),
                "prioridades" => intval(isset($resPrioridades["prioridades"]) ? $resPrioridades["prioridades"] : 0),
                "requiere_autorizacion" => 0,
                "bloqueadas" => 0
            );
            foreach ($bandejas as $bandeja) {
                if ($bandeja["estado"] === "requiere_autorizacion") {
                    $resumen["requiere_autorizacion"]++;
                }
                if ($bandeja["estado"] === "bloqueado") {
                    $resumen["bloqueadas"]++;
                }
            }

            return $this->respuesta(false, "success", "Workflow comercial consultado", array(
                "resumen" => $resumen,
                "bandejas" => $bandejas,
                "reglas" => array(
                    "Workflow read-only: no crea pendientes, no resuelve recomendaciones, no guarda aprobaciones y no aplica precios.",
                    "Las bandejas que escriben datos quedan marcadas como requiere_autorizacion.",
                    "Aplicar precio a Catalogo o Ventas sigue fuera de este modulo."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function estadoModuloRentabilidad($filtros = array()) {
        try {
            $escenarios = $this->auditarEscenariosComerciales();
            $workflow = $this->workflowComercial($filtros);
            $aprobacion = $this->preflightAprobacionPrecios($filtros);
            $autorizaciones = $this->autorizacionesCierreComercial($filtros);
            $snapshots = $this->auditarVigenciaSnapshots(array(
                "q" => isset($filtros["q"]) ? $filtros["q"] : "",
                "limite" => isset($filtros["limite_snapshots"]) ? $filtros["limite_snapshots"] : 10
            ));

            foreach (array($escenarios, $workflow, $aprobacion, $autorizaciones, $snapshots) as $resultado) {
                if (!empty($resultado["error"])) {
                    return $resultado;
                }
            }

            $resEscenarios = isset($escenarios["depurar"]["resumen"]) ? $escenarios["depurar"]["resumen"] : array();
            $resWorkflow = isset($workflow["depurar"]["resumen"]) ? $workflow["depurar"]["resumen"] : array();
            $resAprobacion = isset($aprobacion["depurar"]["resumen"]) ? $aprobacion["depurar"]["resumen"] : array();
            $resAutorizaciones = isset($autorizaciones["depurar"]["resumen"]) ? $autorizaciones["depurar"]["resumen"] : array();
            $resSnapshots = isset($snapshots["depurar"]) ? $snapshots["depurar"] : array();

            $componentes = array(
                "escenarios_comerciales" => array(
                    "titulo" => "Escenarios comerciales",
                    "estado" => intval(isset($resEscenarios["faltantes"]) ? $resEscenarios["faltantes"] : 0) === 0
                        && intval(isset($resEscenarios["activos"]) ? $resEscenarios["activos"] : 0) >= 3 ? "listo" : "bloqueado",
                    "conteo" => intval(isset($resEscenarios["activos"]) ? $resEscenarios["activos"] : 0),
                    "detalle" => "Activos " . intval(isset($resEscenarios["activos"]) ? $resEscenarios["activos"] : 0)
                        . ", faltantes " . intval(isset($resEscenarios["faltantes"]) ? $resEscenarios["faltantes"] : 0)
                        . ", diferentes " . intval(isset($resEscenarios["diferentes_default"]) ? $resEscenarios["diferentes_default"] : 0),
                    "siguiente_paso" => "Mantener los escenarios como base del analisis."
                ),
                "workflow_comercial" => array(
                    "titulo" => "Workflow comercial",
                    "estado" => intval(isset($resWorkflow["requiere_autorizacion"]) ? $resWorkflow["requiere_autorizacion"] : 0) > 0 ? "requiere_autorizacion" : "listo",
                    "conteo" => intval(isset($resWorkflow["candidatos_creables"]) ? $resWorkflow["candidatos_creables"] : 0) + intval(isset($resWorkflow["pendientes"]) ? $resWorkflow["pendientes"] : 0),
                    "detalle" => "Candidatos " . intval(isset($resWorkflow["candidatos_creables"]) ? $resWorkflow["candidatos_creables"] : 0)
                        . ", pendientes " . intval(isset($resWorkflow["pendientes"]) ? $resWorkflow["pendientes"] : 0),
                    "siguiente_paso" => "Crear o resolver pendientes solo con respaldo externo y autorizacion."
                ),
                "aprobacion_precios" => array(
                    "titulo" => "Aprobacion de precios",
                    "estado" => intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0) > 0 ? "requiere_autorizacion" : "bloqueado",
                    "conteo" => intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0),
                    "detalle" => "Aprobables " . intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0)
                        . ", bloqueados " . intval(isset($resAprobacion["bloqueados"]) ? $resAprobacion["bloqueados"] : 0),
                    "siguiente_paso" => "Completar bloqueos de datos antes de tomar precios como cerrados."
                ),
                "snapshots" => array(
                    "titulo" => "Snapshots",
                    "estado" => intval(isset($resSnapshots["desfasados"]) ? $resSnapshots["desfasados"] : 0) > 0 ? "advertencia" : "listo",
                    "conteo" => intval(isset($resSnapshots["total"]) ? $resSnapshots["total"] : 0),
                    "detalle" => "Consultados " . intval(isset($resSnapshots["total"]) ? $resSnapshots["total"] : 0)
                        . ", desfasados " . intval(isset($resSnapshots["desfasados"]) ? $resSnapshots["desfasados"] : 0),
                    "siguiente_paso" => "Guardar snapshot nuevo solo cuando haya autorizacion y respaldo."
                ),
                "paquete_autorizacion" => array(
                    "titulo" => "Paquete de autorizacion",
                    "estado" => intval(isset($resAutorizaciones["bloqueadas"]) ? $resAutorizaciones["bloqueadas"] : 0) > 0 ? "bloqueado" : (
                        intval(isset($resAutorizaciones["requiere_respaldo"]) ? $resAutorizaciones["requiere_respaldo"] : 0) > 0 ? "requiere_autorizacion" : "listo"
                    ),
                    "conteo" => intval(isset($resAutorizaciones["requiere_respaldo"]) ? $resAutorizaciones["requiere_respaldo"] : 0),
                    "detalle" => "Requieren respaldo " . intval(isset($resAutorizaciones["requiere_respaldo"]) ? $resAutorizaciones["requiere_respaldo"] : 0)
                        . ", bloqueadas " . intval(isset($resAutorizaciones["bloqueadas"]) ? $resAutorizaciones["bloqueadas"] : 0),
                    "siguiente_paso" => "Usar el paquete como checklist antes de cualquier escritura comercial."
                )
            );

            $resumen = array(
                "componentes" => count($componentes),
                "listos" => 0,
                "requieren_autorizacion" => 0,
                "bloqueados" => 0,
                "advertencias" => 0,
                "estado_general" => "listo"
            );
            foreach ($componentes as $componente) {
                if ($componente["estado"] === "listo") {
                    $resumen["listos"]++;
                } elseif ($componente["estado"] === "requiere_autorizacion") {
                    $resumen["requieren_autorizacion"]++;
                } elseif ($componente["estado"] === "bloqueado") {
                    $resumen["bloqueados"]++;
                } elseif ($componente["estado"] === "advertencia") {
                    $resumen["advertencias"]++;
                }
            }
            if ($resumen["bloqueados"] > 0) {
                $resumen["estado_general"] = "bloqueado";
            } elseif ($resumen["requieren_autorizacion"] > 0) {
                $resumen["estado_general"] = "requiere_autorizacion";
            } elseif ($resumen["advertencias"] > 0) {
                $resumen["estado_general"] = "advertencia";
            }

            return $this->respuesta(false, "success", "Estado del modulo consultado", array(
                "resumen" => $resumen,
                "componentes" => $componentes,
                "reglas" => array(
                    "Estado read-only: no guarda snapshots, no crea recomendaciones y no modifica precios.",
                    "Un componente bloqueado indica que falta calidad de datos o politica antes de cerrar precios.",
                    "Un componente con autorizacion pendiente requiere respaldo externo y frase exacta antes de escribir BD."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function preflightUsoComercial($filtros = array()) {
        try {
            $filtrosBase = $filtros;
            $filtrosBase["limite"] = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));

            $canales = $this->canalesRecomendados($filtrosBase);
            $aprobacion = $this->preflightAprobacionPrecios($filtrosBase);
            $fiscal = $this->preflightFiscalCierre($filtrosBase);
            $recomendaciones = $this->preflightRecomendaciones($filtrosBase);

            foreach (array($canales, $aprobacion, $fiscal, $recomendaciones) as $resultado) {
                if (!empty($resultado["error"])) {
                    return $resultado;
                }
            }

            $itemsCanales = isset($canales["depurar"]["items"]) ? $canales["depurar"]["items"] : array();
            $itemsAprobacion = isset($aprobacion["depurar"]["items"]) ? $aprobacion["depurar"]["items"] : array();
            $resAprobacion = isset($aprobacion["depurar"]["resumen"]) ? $aprobacion["depurar"]["resumen"] : array();
            $resFiscal = isset($fiscal["depurar"]["resumen"]) ? $fiscal["depurar"]["resumen"] : array();
            $resRecomendaciones = isset($recomendaciones["depurar"]["resumen"]) ? $recomendaciones["depurar"]["resumen"] : array();

            $aprobacionPorSku = $this->indexarItemsPorSku($itemsAprobacion);
            $canalesResumen = array(
                "menudeo" => array("listos" => 0, "bloqueados" => 0, "muestra" => array()),
                "mayoreo" => array("listos" => 0, "bloqueados" => 0, "muestra" => array()),
                "alianza" => array("listos" => 0, "bloqueados" => 0, "muestra" => array())
            );

            foreach ($itemsCanales as $item) {
                $canal = isset($item["canal_recomendado"]) ? $item["canal_recomendado"] : null;
                if (!isset($canalesResumen[$canal])) {
                    continue;
                }
                $aprobacionSku = isset($aprobacionPorSku[$item["sku"]]) ? $aprobacionPorSku[$item["sku"]] : null;
                $aprobable = $aprobacionSku && isset($aprobacionSku["estado"]) && $aprobacionSku["estado"] === "aprobables";
                if ($item["estado"] === "listos" && $aprobable) {
                    $canalesResumen[$canal]["listos"]++;
                } else {
                    $canalesResumen[$canal]["bloqueados"]++;
                    if (count($canalesResumen[$canal]["muestra"]) < 5) {
                        $canalesResumen[$canal]["muestra"][] = array(
                            "sku" => $item["sku"],
                            "producto" => $item["producto"],
                            "estado_canal" => $item["estado"],
                            "estado_aprobacion" => $aprobacionSku && isset($aprobacionSku["estado"]) ? $aprobacionSku["estado"] : "sin_preflight",
                            "motivo" => $aprobacionSku && isset($aprobacionSku["siguiente_paso"]) ? $aprobacionSku["siguiente_paso"] : $item["motivo"]
                        );
                    }
                }
            }

            $destinos = array(
                "catalogo_precios" => array(
                    "titulo" => "Catalogo de precios",
                    "estado" => intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0) > 0 ? "requiere_autorizacion" : "bloqueado",
                    "listos" => intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0),
                    "bloqueados" => intval(isset($resAprobacion["bloqueados"]) ? $resAprobacion["bloqueados"] : 0),
                    "siguiente_paso" => "No aplicar ni publicar precios hasta que el preflight de aprobacion tenga SKUs aprobables."
                ),
                "menudeo" => $this->preflightDestinoUsoComercial("Menudeo", $canalesResumen["menudeo"]),
                "mayoreo_pedidos" => $this->preflightDestinoUsoComercial("Mayoreo/Pedidos", $canalesResumen["mayoreo"]),
                "alianzas" => $this->preflightDestinoUsoComercial("Alianzas", $canalesResumen["alianza"]),
                "catalogo_fiscal" => array(
                    "titulo" => "Catalogo fiscal",
                    "estado" => intval(isset($resFiscal["sin_evidencia"]) ? $resFiscal["sin_evidencia"] : 0) > 0 ? "bloqueado" : "listo",
                    "listos" => intval(isset($resFiscal["aplicable_xml"]) ? $resFiscal["aplicable_xml"] : 0) + intval(isset($resFiscal["captura_manual"]) ? $resFiscal["captura_manual"] : 0),
                    "bloqueados" => intval(isset($resFiscal["sin_evidencia"]) ? $resFiscal["sin_evidencia"] : 0),
                    "siguiente_paso" => "Completar fiscal en Catalogo antes de usar precios como fuente comercial."
                ),
                "pendientes_comerciales" => array(
                    "titulo" => "Pendientes comerciales",
                    "estado" => intval(isset($resRecomendaciones["creables"]) ? $resRecomendaciones["creables"] : 0) > 0 ? "requiere_autorizacion" : "listo",
                    "listos" => 0,
                    "bloqueados" => 0,
                    "requieren_autorizacion" => intval(isset($resRecomendaciones["creables"]) ? $resRecomendaciones["creables"] : 0),
                    "siguiente_paso" => "Crear pendientes requiere respaldo externo y frase de autorizacion."
                )
            );

            $resumen = array(
                "destinos" => count($destinos),
                "listos" => 0,
                "requieren_autorizacion" => 0,
                "bloqueados" => 0,
                "sin_casos" => 0,
                "estado_general" => "listo"
            );
            foreach ($destinos as $destino) {
                if ($destino["estado"] === "listo") {
                    $resumen["listos"]++;
                } elseif ($destino["estado"] === "requiere_autorizacion") {
                    $resumen["requieren_autorizacion"]++;
                } elseif ($destino["estado"] === "bloqueado") {
                    $resumen["bloqueados"]++;
                } elseif ($destino["estado"] === "sin_casos") {
                    $resumen["sin_casos"]++;
                }
            }
            if ($resumen["bloqueados"] > 0) {
                $resumen["estado_general"] = "bloqueado";
            } elseif ($resumen["requieren_autorizacion"] > 0) {
                $resumen["estado_general"] = "requiere_autorizacion";
            }

            return $this->respuesta(false, "success", "Preflight de uso comercial consultado", array(
                "resumen" => $resumen,
                "destinos" => $destinos,
                "reglas" => array(
                    "Preflight read-only: no toca Ventas, Pedidos, ecommerce, Catalogo ni listas de precios.",
                    "Un SKU requiere canal recomendado y aprobacion de precio antes de considerarse usable comercialmente.",
                    "Mayoreo/Pedidos y Alianzas quedan como destinos futuros; este modulo solo diagnostica preparacion."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function planDesbloqueoComercial($filtros = array()) {
        try {
            $checklist = $this->checklistCierreComercial($filtros);
            $responsables = $this->responsablesCierreComercial($filtros);
            $usoComercial = $this->preflightUsoComercial($filtros);
            $estadoModulo = $this->estadoModuloRentabilidad($filtros);

            foreach (array($checklist, $responsables, $usoComercial, $estadoModulo) as $resultado) {
                if (!empty($resultado["error"])) {
                    return $resultado;
                }
            }

            $checks = isset($checklist["depurar"]["checks"]) ? $checklist["depurar"]["checks"] : array();
            $destinos = isset($usoComercial["depurar"]["destinos"]) ? $usoComercial["depurar"]["destinos"] : array();
            $componentes = isset($estadoModulo["depurar"]["componentes"]) ? $estadoModulo["depurar"]["componentes"] : array();
            $responsablesItems = isset($responsables["depurar"]["items"]) ? $responsables["depurar"]["items"] : array();
            $resResponsables = isset($responsables["depurar"]["resumen"]) ? $responsables["depurar"]["resumen"] : array();

            $acciones = array();
            foreach ($checks as $check) {
                if ($check["estado"] !== "bloqueado") {
                    continue;
                }
                $acciones[] = array(
                    "id" => "UNLOCK-" . $check["id"],
                    "tipo" => "dato_bloqueante",
                    "prioridad" => intval($check["total"]) > 20 ? "alta" : "media",
                    "responsable" => $check["responsable"],
                    "titulo" => $check["titulo"],
                    "bloqueo_resuelve" => $check["criterio"],
                    "casos" => intval($check["total"]),
                    "siguiente_paso" => "Resolver los SKUs de este check antes de cerrar precios.",
                    "muestra" => isset($check["items"]) ? array_slice($check["items"], 0, 5) : array()
                );
            }

            foreach ($destinos as $clave => $destino) {
                if ($destino["estado"] !== "bloqueado") {
                    continue;
                }
                $acciones[] = array(
                    "id" => "UNLOCK-USO-" . strtoupper($clave),
                    "tipo" => "uso_comercial",
                    "prioridad" => $clave === "catalogo_fiscal" || $clave === "catalogo_precios" ? "alta" : "media",
                    "responsable" => $clave === "catalogo_fiscal" ? "Catalogo/Fiscal" : "Direccion/Comercial",
                    "titulo" => $destino["titulo"],
                    "bloqueo_resuelve" => "Destino comercial no usable con el filtro actual.",
                    "casos" => intval(isset($destino["bloqueados"]) ? $destino["bloqueados"] : 0),
                    "siguiente_paso" => $destino["siguiente_paso"],
                    "muestra" => isset($destino["muestra"]) ? $destino["muestra"] : array()
                );
            }

            foreach ($componentes as $clave => $componente) {
                if ($componente["estado"] !== "advertencia" && $componente["estado"] !== "requiere_autorizacion") {
                    continue;
                }
                $acciones[] = array(
                    "id" => "UNLOCK-MOD-" . strtoupper($clave),
                    "tipo" => "madurez_modulo",
                    "prioridad" => $componente["estado"] === "requiere_autorizacion" ? "media" : "baja",
                    "responsable" => "Direccion",
                    "titulo" => $componente["titulo"],
                    "bloqueo_resuelve" => $componente["detalle"],
                    "casos" => intval($componente["conteo"]),
                    "siguiente_paso" => $componente["siguiente_paso"],
                    "muestra" => array()
                );
            }

            usort($acciones, function ($a, $b) {
                $peso = array("alta" => 0, "media" => 1, "baja" => 2);
                if ($peso[$a["prioridad"]] !== $peso[$b["prioridad"]]) {
                    return $peso[$a["prioridad"]] - $peso[$b["prioridad"]];
                }
                if (intval($a["casos"]) !== intval($b["casos"])) {
                    return intval($b["casos"]) - intval($a["casos"]);
                }
                return strcmp($a["id"], $b["id"]);
            });

            $resumen = array(
                "acciones" => count($acciones),
                "alta" => 0,
                "media" => 0,
                "baja" => 0,
                "responsables" => intval(isset($resResponsables["responsables"]) ? $resResponsables["responsables"] : 0),
                "estado_general" => empty($acciones) ? "listo" : "bloqueado"
            );
            foreach ($acciones as $accion) {
                if (isset($resumen[$accion["prioridad"]])) {
                    $resumen[$accion["prioridad"]]++;
                }
            }

            return $this->respuesta(false, "success", "Plan de desbloqueo comercial consultado", array(
                "resumen" => $resumen,
                "acciones" => array_slice($acciones, 0, 30),
                "responsables" => $responsablesItems,
                "reglas" => array(
                    "Plan read-only: no crea tareas, no asigna responsables reales y no modifica Catalogo, Inventario ni Ventas.",
                    "Las acciones se derivan del checklist, preflight de uso comercial y estado del modulo.",
                    "La prioridad ordena desbloqueos para poder pasar de rentable a usable comercialmente."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function auditoriaFinalModulo($filtros = array()) {
        try {
            $escenarios = $this->auditarEscenariosComerciales();
            $presentaciones = $this->auditarCostosPresentaciones($filtros);
            $estado = $this->estadoModuloRentabilidad($filtros);
            $usoComercial = $this->preflightUsoComercial($filtros);
            $desbloqueo = $this->planDesbloqueoComercial($filtros);
            $aprobacion = $this->preflightAprobacionPrecios($filtros);

            foreach (array($escenarios, $presentaciones, $estado, $usoComercial, $desbloqueo, $aprobacion) as $resultado) {
                if (!empty($resultado["error"])) {
                    return $resultado;
                }
            }

            $resEscenarios = isset($escenarios["depurar"]["resumen"]) ? $escenarios["depurar"]["resumen"] : array();
            $resPresentaciones = isset($presentaciones["depurar"]) ? $presentaciones["depurar"] : array();
            $resEstado = isset($estado["depurar"]["resumen"]) ? $estado["depurar"]["resumen"] : array();
            $resUso = isset($usoComercial["depurar"]["resumen"]) ? $usoComercial["depurar"]["resumen"] : array();
            $resDesbloqueo = isset($desbloqueo["depurar"]["resumen"]) ? $desbloqueo["depurar"]["resumen"] : array();
            $resAprobacion = isset($aprobacion["depurar"]["resumen"]) ? $aprobacion["depurar"]["resumen"] : array();

            $criterios = array(
                array(
                    "id" => "AUD-COST-001",
                    "titulo" => "Escenarios comerciales configurados",
                    "estado" => intval(isset($resEscenarios["faltantes"]) ? $resEscenarios["faltantes"] : 0) === 0 ? "ok" : "bloqueado",
                    "detalle" => "Faltantes " . intval(isset($resEscenarios["faltantes"]) ? $resEscenarios["faltantes"] : 0)
                ),
                array(
                    "id" => "AUD-COST-002",
                    "titulo" => "Costos de presentaciones consistentes",
                    "estado" => intval(isset($resPresentaciones["alertas"]) ? $resPresentaciones["alertas"] : 0) === 0 ? "ok" : "bloqueado",
                    "detalle" => "Alertas " . intval(isset($resPresentaciones["alertas"]) ? $resPresentaciones["alertas"] : 0)
                ),
                array(
                    "id" => "AUD-COST-003",
                    "titulo" => "Paneles read-only de cierre disponibles",
                    "estado" => intval(isset($resDesbloqueo["acciones"]) ? $resDesbloqueo["acciones"] : 0) >= 0 ? "ok" : "bloqueado",
                    "detalle" => "Plan de desbloqueo consultable"
                ),
                array(
                    "id" => "AUD-COST-004",
                    "titulo" => "Preflight de aprobacion comercial",
                    "estado" => intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0) > 0 ? "ok" : "bloqueado_operativo",
                    "detalle" => "Aprobables " . intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0)
                        . ", bloqueados " . intval(isset($resAprobacion["bloqueados"]) ? $resAprobacion["bloqueados"] : 0)
                ),
                array(
                    "id" => "AUD-COST-005",
                    "titulo" => "Uso comercial listo",
                    "estado" => isset($resUso["estado_general"]) && $resUso["estado_general"] === "listo" ? "ok" : "bloqueado_operativo",
                    "detalle" => "Estado uso comercial " . (isset($resUso["estado_general"]) ? $resUso["estado_general"] : "-")
                ),
                array(
                    "id" => "AUD-COST-006",
                    "titulo" => "Estado general del modulo",
                    "estado" => isset($resEstado["estado_general"]) && $resEstado["estado_general"] === "bloqueado" ? "bloqueado_operativo" : "ok",
                    "detalle" => "Estado modulo " . (isset($resEstado["estado_general"]) ? $resEstado["estado_general"] : "-")
                )
            );

            $resumen = array(
                "criterios" => count($criterios),
                "ok" => 0,
                "bloqueados_tecnicos" => 0,
                "bloqueados_operativos" => 0,
                "estado_construccion" => "completo_readonly",
                "estado_uso_comercial" => isset($resUso["estado_general"]) ? $resUso["estado_general"] : "bloqueado"
            );
            foreach ($criterios as $criterio) {
                if ($criterio["estado"] === "ok") {
                    $resumen["ok"]++;
                } elseif ($criterio["estado"] === "bloqueado_operativo") {
                    $resumen["bloqueados_operativos"]++;
                } else {
                    $resumen["bloqueados_tecnicos"]++;
                }
            }
            if ($resumen["bloqueados_tecnicos"] > 0) {
                $resumen["estado_construccion"] = "requiere_correccion";
            }

            $siguiente = "Validar visualmente la pantalla y decidir si se autoriza capa persistente de aprobacion comercial interna.";
            if (intval(isset($resDesbloqueo["alta"]) ? $resDesbloqueo["alta"] : 0) > 0) {
                $siguiente = "Resolver primero acciones alta del plan de desbloqueo, empezando por fiscal/catalogo.";
            }

            return $this->respuesta(false, "success", "Auditoria final del modulo consultada", array(
                "resumen" => $resumen,
                "criterios" => $criterios,
                "siguiente_paso" => $siguiente,
                "reglas" => array(
                    "Auditoria read-only: no escribe BD, no aplica precios y no toca Ventas/ecommerce.",
                    "Completo read-only significa que el modulo consulta y diagnostica; no significa que los datos ya sean publicables.",
                    "El uso comercial queda bloqueado hasta liberar aprobacion, fiscal y plan de desbloqueo."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function recomendacionesOperativas($filtros = array()) {
        try {
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $items = array();
            foreach ($this->consultarFilasSku($termino, $limite) as $fila) {
                $defaults = $this->defaultsEscenario("menudeo");
                $item = $this->calcularItem(
                    $fila,
                    "menudeo",
                    $defaults["descuento_pct"],
                    $defaults["gasto_pct"],
                    $defaults["comision_pct"],
                    $defaults["margen_objetivo_pct"]
                );
                if (!$this->cumpleFiltrosOperacion($item, $filtros)) {
                    continue;
                }
                $items[] = $item;
            }

            $grupos = array(
                "cerrar_precio" => array("titulo" => "Cerrar precio antes de vender", "items" => array()),
                "completar_costo" => array("titulo" => "Completar costo", "items" => array()),
                "completar_fiscal" => array("titulo" => "Completar fiscal", "items" => array()),
                "revisar_margen" => array("titulo" => "Revisar margen", "items" => array()),
                "validar_inventario" => array("titulo" => "Validar inventario/costo promedio", "items" => array())
            );

            foreach ($items as $item) {
                $base = $this->resumenRecomendacionItem($item);
                if (in_array("sin_precio", $item["hallazgos"], true) || in_array("perdida_estimada", $item["hallazgos"], true)) {
                    $grupos["cerrar_precio"]["items"][] = $base;
                }
                if (in_array("sin_costo", $item["hallazgos"], true)) {
                    $grupos["completar_costo"]["items"][] = $base;
                }
                if (in_array("fiscal_incompleto", $item["hallazgos"], true)) {
                    $grupos["completar_fiscal"]["items"][] = $base;
                }
                if (in_array("margen_bajo", $item["hallazgos"], true)) {
                    $grupos["revisar_margen"]["items"][] = $base;
                }
                if (in_array("stock_sin_costo_promedio", $item["hallazgos"], true)) {
                    $grupos["validar_inventario"]["items"][] = $base;
                }
            }

            foreach ($grupos as $clave => $grupo) {
                $grupos[$clave]["total"] = count($grupo["items"]);
                $grupos[$clave]["items"] = array_slice($grupo["items"], 0, 25);
            }

            return $this->respuesta(false, "success", "Recomendaciones operativas consultadas", array(
                "total_skus_evaluados" => count($items),
                "grupos" => $grupos,
                "reglas" => array(
                    "Reporte read-only: no crea pendientes ni modifica precios.",
                    "Las recomendaciones persistentes requieren tablas de rentabilidad y autorizacion."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function auditarCierrePrecios($filtros = array()) {
        try {
            $filtrosAnalisis = $filtros;
            unset($filtrosAnalisis["riesgo"]);
            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }

            $items = isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array();
            $resumen = isset($analisis["depurar"]["resumen"]) ? $analisis["depurar"]["resumen"] : array();
            $presentaciones = $this->auditarCostosPresentaciones(array(
                "q" => isset($filtros["q"]) ? $filtros["q"] : "",
                "limite" => isset($filtros["limite"]) ? $filtros["limite"] : 120
            ));
            $snapshots = $this->auditarVigenciaSnapshots(array(
                "q" => isset($filtros["q"]) ? $filtros["q"] : "",
                "limite" => 10
            ));
            $recomendaciones = $this->listarRecomendaciones(array("estatus" => "pendiente"));

            $bloqueos = array(
                "sin_costo" => array("id" => "COST-H101", "titulo" => "Costos faltantes", "tipo" => "danger", "total" => 0, "skus" => array()),
                "sin_precio" => array("id" => "COST-H102", "titulo" => "Precios faltantes", "tipo" => "danger", "total" => 0, "skus" => array()),
                "fiscal_incompleto" => array("id" => "COST-H103", "titulo" => "Fiscal incompleto", "tipo" => "warning", "total" => 0, "skus" => array()),
                "perdida_estimada" => array("id" => "COST-H104", "titulo" => "Perdida estimada", "tipo" => "danger", "total" => 0, "skus" => array()),
                "margen_bajo" => array("id" => "COST-H105", "titulo" => "Margen bajo", "tipo" => "warning", "total" => 0, "skus" => array()),
                "stock_sin_costo_promedio" => array("id" => "COST-H106", "titulo" => "Stock sin costo promedio", "tipo" => "danger", "total" => 0, "skus" => array())
            );

            foreach ($items as $item) {
                foreach ($bloqueos as $clave => $bloqueo) {
                    if (in_array($clave, $item["hallazgos"], true)) {
                        $bloqueos[$clave]["total"]++;
                        if (count($bloqueos[$clave]["skus"]) < 5) {
                            $bloqueos[$clave]["skus"][] = array(
                                "sku" => $item["sku"],
                                "producto" => $item["producto"],
                                "riesgo" => $item["riesgo_clave"],
                                "recomendacion" => $item["recomendacion"]
                            );
                        }
                    }
                }
            }

            $alertasPresentaciones = !empty($presentaciones["error"]) ? null : intval($presentaciones["depurar"]["alertas"]);
            $snapshotsDesfasados = !empty($snapshots["error"]) ? null : intval($snapshots["depurar"]["desfasados"]);
            $pendientes = !empty($recomendaciones["error"]) ? null : count($recomendaciones["depurar"]["items"]);

            $bloqueosDuros = intval($bloqueos["sin_costo"]["total"])
                + intval($bloqueos["sin_precio"]["total"])
                + intval($bloqueos["perdida_estimada"]["total"])
                + intval($bloqueos["stock_sin_costo_promedio"]["total"])
                + intval($alertasPresentaciones);
            $alertas = intval($bloqueos["fiscal_incompleto"]["total"])
                + intval($bloqueos["margen_bajo"]["total"])
                + intval($snapshotsDesfasados)
                + intval($pendientes);

            $estado = "listo";
            $tipo = "success";
            $mensajeEstado = "Muestra lista para cierre comercial read-only";
            if ($bloqueosDuros > 0) {
                $estado = "bloqueado";
                $tipo = "danger";
                $mensajeEstado = "Hay bloqueos antes de cerrar precios";
            } elseif ($alertas > 0) {
                $estado = "precaucion";
                $tipo = "warning";
                $mensajeEstado = "Se puede revisar precio, pero hay alertas por resolver";
            }

            return $this->respuesta(false, "success", "Auditoria de cierre comercial consultada", array(
                "estado" => $estado,
                "tipo" => $tipo,
                "mensaje_estado" => $mensajeEstado,
                "resumen" => $resumen,
                "bloqueos_duros" => $bloqueosDuros,
                "alertas" => $alertas,
                "bloqueos" => array_values($bloqueos),
                "presentaciones" => array(
                    "alertas" => $alertasPresentaciones,
                    "total" => empty($presentaciones["error"]) ? intval($presentaciones["depurar"]["total"]) : null
                ),
                "snapshots" => array(
                    "desfasados" => $snapshotsDesfasados,
                    "total" => empty($snapshots["error"]) ? intval($snapshots["depurar"]["total"]) : null
                ),
                "recomendaciones_pendientes" => $pendientes,
                "reglas" => array(
                    "Auditoria read-only: no guarda snapshots, no crea recomendaciones y no modifica precios.",
                    "Bloquea cierre si faltan costo/precio, hay perdida estimada, stock sin costo promedio o presentaciones inconsistentes.",
                    "Marca precaucion si hay fiscal incompleto, margen bajo, snapshots desfasados o recomendaciones pendientes."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function semaforoCierre($filtros = array()) {
        try {
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $filtrosAnalisis = $filtros;
            $filtrosAnalisis["limite"] = $limite;
            unset($filtrosAnalisis["riesgo"]);
            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }

            $items = array();
            $resumen = array(
                "evaluados" => 0,
                "listos" => 0,
                "precaucion" => 0,
                "bloqueados" => 0,
                "valor_inventario_bloqueado" => 0,
                "valor_inventario_listo" => 0
            );

            foreach (isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array() as $item) {
                $resumen["evaluados"]++;
                $dictamen = $this->dictamenCierreItem($item);
                if ($dictamen["estado"] === "bloqueado") {
                    $resumen["bloqueados"]++;
                    $resumen["valor_inventario_bloqueado"] += floatval($item["inventario"]["valor_total"]);
                } elseif ($dictamen["estado"] === "precaucion") {
                    $resumen["precaucion"]++;
                } else {
                    $resumen["listos"]++;
                    $resumen["valor_inventario_listo"] += floatval($item["inventario"]["valor_total"]);
                }

                $items[] = array(
                    "sku" => $item["sku"],
                    "producto" => $item["producto"],
                    "estado" => $dictamen["estado"],
                    "tipo" => $dictamen["tipo"],
                    "bloqueos" => $dictamen["bloqueos"],
                    "alertas" => $dictamen["alertas"],
                    "siguiente_paso" => $dictamen["siguiente_paso"],
                    "costo" => $item["costo_real_sin_impuesto"],
                    "precio" => $item["precio_escenario_sin_impuesto"],
                    "precio_minimo" => $item["precio_minimo_rentable"],
                    "utilidad" => $item["utilidad_estimada"],
                    "margen" => $item["margen_bruto_pct"],
                    "disponible" => $item["inventario"]["disponible_total"],
                    "valor_inventario" => $item["inventario"]["valor_total"],
                    "riesgo" => $item["riesgo_clave"]
                );
            }

            usort($items, function ($a, $b) {
                $peso = array("bloqueado" => 0, "precaucion" => 1, "listo" => 2);
                if ($peso[$a["estado"]] !== $peso[$b["estado"]]) {
                    return $peso[$a["estado"]] - $peso[$b["estado"]];
                }
                if ($a["estado"] === "bloqueado") {
                    return floatval($a["utilidad"]) < floatval($b["utilidad"]) ? -1 : 1;
                }
                return strcmp($a["sku"], $b["sku"]);
            });

            $resumen["valor_inventario_bloqueado"] = round($resumen["valor_inventario_bloqueado"], 6);
            $resumen["valor_inventario_listo"] = round($resumen["valor_inventario_listo"], 6);

            return $this->respuesta(false, "success", "Semaforo de cierre consultado", array(
                "resumen" => $resumen,
                "items" => array_slice($items, 0, $limite),
                "reglas" => array(
                    "Semaforo read-only: no actualiza precios, no completa fiscal y no mueve inventario.",
                    "Bloqueado si falta costo/precio, hay perdida, stock sin costo promedio o precio bajo el minimo rentable.",
                    "Precaucion si hay fiscal incompleto o margen bajo; requiere validacion antes de publicar en Ventas."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function variacionesCostos($filtros = array()) {
        try {
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $umbralPct = max(1, min(100, floatval(isset($filtros["umbral_pct"]) ? $filtros["umbral_pct"] : 15)));
            $filtrosAnalisis = $filtros;
            $filtrosAnalisis["limite"] = $limite;
            unset($filtrosAnalisis["riesgo"]);
            $analisis = $this->analizarSkus($filtrosAnalisis);
            if (!empty($analisis["error"])) {
                return $analisis;
            }

            $resumen = array(
                "evaluados" => 0,
                "con_evidencia" => 0,
                "sin_evidencia" => 0,
                "alertas" => 0,
                "mayor_alerta_pct" => 0
            );
            $items = array();

            foreach (isset($analisis["depurar"]["items"]) ? $analisis["depurar"]["items"] : array() as $item) {
                $resumen["evaluados"]++;
                $comparaciones = array();
                $alertasItem = 0;
                $mayorPctItem = 0;
                $fuentes = array(
                    "ultimo_compra" => isset($item["compras"]["ultimo_costo"]) ? $item["compras"]["ultimo_costo"] : null,
                    "promedio_compras" => isset($item["compras"]["costo_promedio"]) ? $item["compras"]["costo_promedio"] : null,
                    "ultimo_xml" => isset($item["xml"]["ultimo_costo"]) ? $item["xml"]["ultimo_costo"] : null,
                    "proveedor" => isset($item["proveedor"]["costo_ultimo"]) ? $item["proveedor"]["costo_ultimo"] : null
                );
                foreach ($fuentes as $fuente => $costoEvidencia) {
                    $comparacion = $this->compararCostoEvidencia($item["costo_real_sin_impuesto"], $costoEvidencia, $fuente, $umbralPct);
                    if ($comparacion === null) {
                        continue;
                    }
                    if ($comparacion["alerta"]) {
                        $alertasItem++;
                    }
                    $mayorPctItem = max($mayorPctItem, abs(floatval($comparacion["diferencia_pct"])));
                    $comparaciones[] = $comparacion;
                }

                if (empty($comparaciones)) {
                    $resumen["sin_evidencia"]++;
                    continue;
                }
                $resumen["con_evidencia"]++;
                if ($alertasItem > 0) {
                    $resumen["alertas"]++;
                }
                $resumen["mayor_alerta_pct"] = max($resumen["mayor_alerta_pct"], $mayorPctItem);

                $items[] = array(
                    "sku" => $item["sku"],
                    "producto" => $item["producto"],
                    "costo_actual" => $item["costo_real_sin_impuesto"],
                    "origen_costo" => $item["origen_costo"],
                    "riesgo" => $item["riesgo_clave"],
                    "alertas" => $alertasItem,
                    "mayor_diferencia_pct" => round($mayorPctItem, 2),
                    "comparaciones" => $comparaciones,
                    "recomendacion" => $alertasItem > 0
                        ? "Validar costo contra evidencia historica antes de cerrar precio."
                        : "Costo alineado con evidencia disponible dentro del umbral."
                );
            }

            usort($items, function ($a, $b) {
                if ($a["alertas"] !== $b["alertas"]) {
                    return $b["alertas"] - $a["alertas"];
                }
                return floatval($a["mayor_diferencia_pct"]) < floatval($b["mayor_diferencia_pct"]) ? 1 : -1;
            });
            $resumen["mayor_alerta_pct"] = round($resumen["mayor_alerta_pct"], 2);

            return $this->respuesta(false, "success", "Variaciones de costo auditadas", array(
                "umbral_pct" => $umbralPct,
                "resumen" => $resumen,
                "items" => array_slice($items, 0, 80),
                "reglas" => array(
                    "Auditoria read-only: no actualiza costo referencia ni costo promedio.",
                    "Compara costo actual de rentabilidad contra compras, XML y proveedor cuando hay evidencia.",
                    "Una variacion mayor al umbral solo genera alerta para revision manual."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function auditarDatosBaseCierre($filtros = array()) {
        try {
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $defaults = $this->defaultsEscenario("menudeo");
            $items = array();
            $porSku = array();
            foreach ($this->consultarFilasSku($termino, $limite) as $fila) {
                $item = $this->calcularItem(
                    $fila,
                    "menudeo",
                    $defaults["descuento_pct"],
                    $defaults["gasto_pct"],
                    $defaults["comision_pct"],
                    $defaults["margen_objetivo_pct"]
                );
                if (!$this->cumpleFiltrosOperacion($item, $filtros)) {
                    continue;
                }
                $items[] = $item;
                $porSku[$item["sku"]] = $item;
            }

            $datosBase = $this->consultarDatosBaseSku($termino, $limite);
            $grupos = array(
                "costo" => array("titulo" => "Completar costo base", "tipo" => "danger", "items" => array()),
                "precio" => array("titulo" => "Completar precio general", "tipo" => "danger", "items" => array()),
                "fiscal" => array("titulo" => "Completar fiscal", "tipo" => "warning", "items" => array()),
                "margen" => array("titulo" => "Revisar margen/precio", "tipo" => "warning", "items" => array())
            );

            foreach ($datosBase as $fila) {
                $sku = $fila["sku"];
                $item = isset($porSku[$sku]) ? $porSku[$sku] : null;
                if (!$item) {
                    continue;
                }
                $faltantesFiscal = array();
                foreach (array("clave_producto_sat", "clave_unidad_sat", "objeto_impuesto", "iva_porcentaje", "ieps_porcentaje", "incluye_impuestos") as $campo) {
                    if ($fila[$campo] === null || trim(strval($fila[$campo])) === "") {
                        $faltantesFiscal[] = $campo;
                    }
                }

                $base = array(
                    "id_sku" => intval($fila["id_sku"]),
                    "sku" => $sku,
                    "producto" => $fila["producto"],
                    "costo_referencia" => round(floatval($fila["costo_referencia"]), 6),
                    "precio_general" => $fila["precio_general"] === null ? null : round(floatval($fila["precio_general"]), 6),
                    "ultimo_costo_compra" => $fila["ultimo_costo_compra"] === null ? null : round(floatval($fila["ultimo_costo_compra"]), 6),
                    "ultimo_costo_xml" => $fila["ultimo_costo_xml"] === null ? null : round(floatval($fila["ultimo_costo_xml"]), 6),
                    "costo_proveedor" => $fila["costo_proveedor"] === null ? null : round(floatval($fila["costo_proveedor"]), 6),
                    "proveedor" => $fila["proveedor"],
                    "margen_bruto_pct" => $item ? $item["margen_bruto_pct"] : null,
                    "utilidad_estimada" => $item ? $item["utilidad_estimada"] : null,
                    "riesgo" => $item ? $item["riesgo_clave"] : null,
                    "faltantes_fiscal" => $faltantesFiscal
                );

                if ($item && in_array("sin_costo", $item["hallazgos"], true)) {
                    $base["accion_sugerida"] = $this->sugerenciaCostoBase($base);
                    $grupos["costo"]["items"][] = $base;
                }
                if ($item && in_array("sin_precio", $item["hallazgos"], true)) {
                    $base["accion_sugerida"] = "Capturar precio general activo antes de simular cierre comercial.";
                    $grupos["precio"]["items"][] = $base;
                }
                if (!empty($faltantesFiscal)) {
                    $base["accion_sugerida"] = "Completar " . implode(", ", $faltantesFiscal) . " en ficha fiscal del SKU.";
                    $grupos["fiscal"]["items"][] = $base;
                }
                if ($item && (in_array("perdida_estimada", $item["hallazgos"], true) || in_array("margen_bajo", $item["hallazgos"], true))) {
                    $base["accion_sugerida"] = $item["recomendacion"];
                    $grupos["margen"]["items"][] = $base;
                }
            }

            foreach ($grupos as $clave => $grupo) {
                $grupos[$clave]["total"] = count($grupo["items"]);
                $grupos[$clave]["items"] = array_slice($grupo["items"], 0, 25);
            }

            return $this->respuesta(false, "success", "Datos base para cierre auditados", array(
                "total_skus_evaluados" => count($items),
                "grupos" => $grupos,
                "reglas" => array(
                    "Auditoria read-only: no modifica Catalogo, Compras, XML ni Ventas.",
                    "Costo puede sugerirse desde proveedor, compra o XML, pero la aplicacion requiere respaldo y autorizacion.",
                    "Fiscal incompleto bloquea precio sin impuestos confiable aunque el margen estimado sea positivo."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function auditarFiscalXmlCierre($filtros = array()) {
        try {
            $db = $this->getConexion();
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $limite = max(10, min(300, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $skusPermitidos = null;
            if ($this->tieneFiltrosOperacion($filtros)) {
                $filtrosAnalisis = $filtros;
                $filtrosAnalisis["limite"] = 300;
                unset($filtrosAnalisis["riesgo"]);
                $analisis = $this->analizarSkus($filtrosAnalisis);
                if (!empty($analisis["error"])) {
                    return $analisis;
                }
                $skusPermitidos = array();
                foreach ($analisis["depurar"]["items"] as $itemPermitido) {
                    $skusPermitidos[$itemPermitido["sku"]] = true;
                }
                if (empty($skusPermitidos)) {
                    return $this->respuesta(false, "success", "Evidencia fiscal XML auditada", array(
                        "total_fiscal_incompleto" => 0,
                        "con_sugerencia_xml" => 0,
                        "sin_sugerencia_xml" => 0,
                        "items" => array(),
                        "reglas" => array(
                            "Auditoria read-only: no copia XML al catalogo.",
                            "La sugerencia XML solo es evidencia; aplicar fiscal a Catalogo requiere autorizacion y respaldo.",
                            "Si el SKU no tiene concepto XML vinculado, no se infiere fiscal por texto."
                        )
                    ));
                }
            }
            $sql = "SELECT
                    s.id_sku,
                    s.sku,
                    COALESCE(s.nombre, p.nombre) producto,
                    imp.clave_producto_sat,
                    imp.clave_unidad_sat,
                    imp.objeto_impuesto,
                    imp.iva_porcentaje,
                    imp.ieps_porcentaje,
                    imp.incluye_impuestos,
                    x.uuid,
                    x.folio,
                    x.descripcion descripcion_xml,
                    x.clave_producto_sat xml_clave_producto_sat,
                    x.clave_unidad_sat xml_clave_unidad_sat,
                    x.objeto_impuesto xml_objeto_impuesto,
                    x.iva_porcentaje xml_iva_porcentaje,
                    x.ieps_porcentaje xml_ieps_porcentaje
                FROM erp_catalogo_skus s
                INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
                LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku
                LEFT JOIN (
                    SELECT c.id_sku_erp id_sku,
                        SUBSTRING_INDEX(GROUP_CONCAT(f.uuid ORDER BY f.fecha_emision DESC, c.id_documento_concepto DESC), ',', 1) uuid,
                        SUBSTRING_INDEX(GROUP_CONCAT(CONCAT(COALESCE(f.serie,''), COALESCE(f.folio,'')) ORDER BY f.fecha_emision DESC, c.id_documento_concepto DESC), ',', 1) folio,
                        SUBSTRING_INDEX(GROUP_CONCAT(c.descripcion ORDER BY f.fecha_emision DESC, c.id_documento_concepto DESC SEPARATOR '||'), '||', 1) descripcion,
                        SUBSTRING_INDEX(GROUP_CONCAT(c.clave_producto_sat ORDER BY f.fecha_emision DESC, c.id_documento_concepto DESC), ',', 1) clave_producto_sat,
                        SUBSTRING_INDEX(GROUP_CONCAT(c.clave_unidad_sat ORDER BY f.fecha_emision DESC, c.id_documento_concepto DESC), ',', 1) clave_unidad_sat,
                        SUBSTRING_INDEX(GROUP_CONCAT(c.objeto_impuesto ORDER BY f.fecha_emision DESC, c.id_documento_concepto DESC), ',', 1) objeto_impuesto,
                        SUBSTRING_INDEX(GROUP_CONCAT(c.iva_porcentaje ORDER BY f.fecha_emision DESC, c.id_documento_concepto DESC), ',', 1) iva_porcentaje,
                        SUBSTRING_INDEX(GROUP_CONCAT(c.ieps_porcentaje ORDER BY f.fecha_emision DESC, c.id_documento_concepto DESC), ',', 1) ieps_porcentaje
                    FROM erp_compras_documentos_fiscales_conceptos c
                    INNER JOIN erp_compras_documentos_fiscales f ON f.id_documento_fiscal=c.id_documento_fiscal
                    WHERE COALESCE(c.id_sku_erp,0)>0
                    GROUP BY c.id_sku_erp
                ) x ON x.id_sku=s.id_sku
                WHERE s.estatus <> 'fusionado'
                  AND (:termino='' OR s.sku LIKE :buscar OR s.nombre LIKE :buscar OR p.nombre LIKE :buscar)
                  AND (imp.id_sku IS NULL
                    OR TRIM(COALESCE(imp.clave_producto_sat,''))=''
                    OR TRIM(COALESCE(imp.clave_unidad_sat,''))=''
                    OR TRIM(COALESCE(imp.objeto_impuesto,''))=''
                    OR imp.iva_porcentaje IS NULL
                    OR imp.ieps_porcentaje IS NULL
                    OR imp.incluye_impuestos IS NULL)
                ORDER BY CASE WHEN x.id_sku IS NULL THEN 1 ELSE 0 END, s.sku ASC
                LIMIT " . $limite;
            $stmt = $db->prepare($sql);
            $stmt->execute(array(":termino" => $termino, ":buscar" => "%" . $termino . "%"));
            $items = array();
            $conSugerencia = 0;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                if ($skusPermitidos !== null && !isset($skusPermitidos[$fila["sku"]])) {
                    continue;
                }
                $faltantes = array();
                foreach (array("clave_producto_sat", "clave_unidad_sat", "objeto_impuesto", "iva_porcentaje", "ieps_porcentaje", "incluye_impuestos") as $campo) {
                    if ($fila[$campo] === null || trim(strval($fila[$campo])) === "") {
                        $faltantes[] = $campo;
                    }
                }
                $xmlCompleto = trim(strval($fila["xml_clave_producto_sat"])) !== ""
                    && trim(strval($fila["xml_clave_unidad_sat"])) !== ""
                    && trim(strval($fila["xml_objeto_impuesto"])) !== "";
                if ($xmlCompleto) {
                    $conSugerencia++;
                }
                $items[] = array(
                    "id_sku" => intval($fila["id_sku"]),
                    "sku" => $fila["sku"],
                    "producto" => $fila["producto"],
                    "faltantes" => $faltantes,
                    "tiene_sugerencia_xml" => $xmlCompleto,
                    "xml" => array(
                        "uuid" => $fila["uuid"],
                        "folio" => $fila["folio"],
                        "descripcion" => $fila["descripcion_xml"],
                        "clave_producto_sat" => $fila["xml_clave_producto_sat"],
                        "clave_unidad_sat" => $fila["xml_clave_unidad_sat"],
                        "objeto_impuesto" => $fila["xml_objeto_impuesto"],
                        "iva_porcentaje" => $fila["xml_iva_porcentaje"] === null ? null : round(floatval($fila["xml_iva_porcentaje"]), 4),
                        "ieps_porcentaje" => $fila["xml_ieps_porcentaje"] === null ? null : round(floatval($fila["xml_ieps_porcentaje"]), 4)
                    )
                );
            }

            return $this->respuesta(false, "success", "Evidencia fiscal XML auditada", array(
                "total_fiscal_incompleto" => count($items),
                "con_sugerencia_xml" => $conSugerencia,
                "sin_sugerencia_xml" => count($items) - $conSugerencia,
                "items" => $items,
                "reglas" => array(
                    "Auditoria read-only: no copia XML al catalogo.",
                    "La sugerencia XML solo es evidencia; aplicar fiscal a Catalogo requiere autorizacion y respaldo.",
                    "Si el SKU no tiene concepto XML vinculado, no se infiere fiscal por texto."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function preflightFiscalCierre($filtros = array()) {
        try {
            $auditoria = $this->auditarFiscalXmlCierre($filtros);
            if (!empty($auditoria["error"])) {
                return $auditoria;
            }

            $resumen = array(
                "evaluados" => intval(isset($auditoria["depurar"]["total_fiscal_incompleto"]) ? $auditoria["depurar"]["total_fiscal_incompleto"] : 0),
                "aplicable_xml" => 0,
                "captura_manual" => 0,
                "sin_evidencia" => 0,
                "campos_faltantes" => array(
                    "clave_producto_sat" => 0,
                    "clave_unidad_sat" => 0,
                    "objeto_impuesto" => 0,
                    "iva_porcentaje" => 0,
                    "ieps_porcentaje" => 0,
                    "incluye_impuestos" => 0
                )
            );
            $items = array();

            foreach (isset($auditoria["depurar"]["items"]) ? $auditoria["depurar"]["items"] : array() as $item) {
                foreach (isset($item["faltantes"]) ? $item["faltantes"] : array() as $campo) {
                    if (isset($resumen["campos_faltantes"][$campo])) {
                        $resumen["campos_faltantes"][$campo]++;
                    }
                }
                $xml = isset($item["xml"]) ? $item["xml"] : array();
                $tieneXml = !empty($item["tiene_sugerencia_xml"]);
                $tieneTasas = isset($xml["iva_porcentaje"]) || isset($xml["ieps_porcentaje"]);
                if ($tieneXml && $tieneTasas) {
                    $accion = "aplicable_xml";
                    $estado = "requiere_respaldo";
                    $tipo = "warning";
                    $siguiente = "Validar XML y autorizar aplicacion fiscal a Catalogo con respaldo externo.";
                } elseif ($tieneXml) {
                    $accion = "captura_manual";
                    $estado = "requiere_revision";
                    $tipo = "info";
                    $siguiente = "XML aporta claves SAT, pero faltan tasas o incluye impuestos; requiere captura manual.";
                } else {
                    $accion = "sin_evidencia";
                    $estado = "bloqueado";
                    $tipo = "danger";
                    $siguiente = "Completar fiscal manualmente; no hay XML vinculado suficiente para sugerir.";
                }
                $resumen[$accion]++;
                $items[] = array(
                    "sku" => $item["sku"],
                    "producto" => $item["producto"],
                    "accion" => $accion,
                    "estado" => $estado,
                    "tipo" => $tipo,
                    "faltantes" => isset($item["faltantes"]) ? $item["faltantes"] : array(),
                    "xml" => $xml,
                    "siguiente_paso" => $siguiente
                );
            }

            usort($items, function ($a, $b) {
                $peso = array("aplicable_xml" => 0, "captura_manual" => 1, "sin_evidencia" => 2);
                if ($peso[$a["accion"]] !== $peso[$b["accion"]]) {
                    return $peso[$a["accion"]] - $peso[$b["accion"]];
                }
                return strcmp($a["sku"], $b["sku"]);
            });

            return $this->respuesta(false, "success", "Preflight fiscal de cierre consultado", array(
                "resumen" => $resumen,
                "items" => $items,
                "reglas" => array(
                    "Preflight read-only: no actualiza Catalogo ni copia datos fiscales.",
                    "XML solo se considera aplicable si aporta claves SAT y tasas fiscales vinculadas al SKU.",
                    "Sin XML suficiente, Rentabilidad no infiere fiscal por descripcion; requiere captura manual."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function sembrarEscenariosBase($idUsuario = 0) {
        $db = $this->getConexion();
        try {
            $stmt = $db->prepare("INSERT INTO erp_rentabilidad_escenarios
                (clave, nombre, canal, descuento_pct, gasto_operativo_pct, comision_pct, margen_objetivo_pct, descripcion, estatus, creado_por)
                VALUES (:clave, :nombre, :canal, :descuento, :gasto, :comision, :margen, :descripcion, 'activo', :usuario)
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), canal=VALUES(canal), descuento_pct=VALUES(descuento_pct),
                    gasto_operativo_pct=VALUES(gasto_operativo_pct), comision_pct=VALUES(comision_pct),
                    margen_objetivo_pct=VALUES(margen_objetivo_pct), descripcion=VALUES(descripcion), estatus='activo'");
            foreach ($this->escenariosSemilla() as $escenario) {
                $stmt->execute(array(
                    ":clave" => $escenario["clave"],
                    ":nombre" => $escenario["nombre"],
                    ":canal" => $escenario["canal"],
                    ":descuento" => $escenario["descuento_pct"],
                    ":gasto" => $escenario["gasto_pct"],
                    ":comision" => $escenario["comision_pct"],
                    ":margen" => $escenario["margen_objetivo_pct"],
                    ":descripcion" => $escenario["descripcion"],
                    ":usuario" => intval($idUsuario) ?: null
                ));
            }
            return $this->respuesta(false, "success", "Escenarios base sembrados", array("total" => count($this->escenariosSemilla())));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function guardarSnapshot($datos, $idUsuario = 0) {
        $db = $this->getConexion();
        try {
            $canal = $this->opcion(isset($datos["canal"]) ? $datos["canal"] : "menudeo", array("menudeo", "mayoreo", "alianza"), "menudeo");
            $autorizacion = $this->validarAutorizacionEscritura(
                $datos,
                "AUTORIZO GUARDAR SNAPSHOT",
                "guardar snapshot de rentabilidad"
            );
            if (!empty($autorizacion["error"])) {
                return $autorizacion;
            }
            $respuesta = $this->analizarSkus($datos);
            if ($respuesta["error"]) {
                return $respuesta;
            }
            $depurar = $respuesta["depurar"];
            $items = isset($depurar["items"]) ? $depurar["items"] : array();
            if (empty($items)) {
                return $this->respuesta(true, "warning", "No hay SKUs para guardar snapshot");
            }
            $escenario = $depurar["escenario"];
            $folio = "RENT-" . date("Ymd-His");

            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO erp_rentabilidad_snapshots
                (folio, canal, descuento_pct, gasto_operativo_pct, comision_pct, margen_objetivo_pct,
                 filtros_json, resumen_json, estatus, creado_por)
                VALUES (:folio, :canal, :descuento, :gasto, :comision, :margen, :filtros, :resumen, 'cerrado', :usuario)");
            $stmt->execute(array(
                ":folio" => $folio,
                ":canal" => $canal,
                ":descuento" => $escenario["descuento_pct"],
                ":gasto" => $escenario["gasto_pct"],
                ":comision" => $escenario["comision_pct"],
                ":margen" => $escenario["margen_objetivo_pct"],
                ":filtros" => json_encode($datos, JSON_UNESCAPED_UNICODE),
                ":resumen" => json_encode($depurar["resumen"], JSON_UNESCAPED_UNICODE),
                ":usuario" => intval($idUsuario) ?: null
            ));
            $idSnapshot = intval($db->lastInsertId());

            $detalle = $db->prepare("INSERT INTO erp_rentabilidad_snapshot_detalle
                (id_snapshot, id_sku, sku, producto, costo_real_sin_impuesto, origen_costo,
                 precio_venta_sin_impuesto, precio_escenario_sin_impuesto, margen_bruto_pct,
                 utilidad_bruta, gastos_estimados, utilidad_estimada, utilidad_estimada_pct,
                 precio_minimo_rentable, cantidad_inventario, disponible_inventario, valor_inventario,
                 riesgo_clave, riesgo_tipo, hallazgos_json, evidencia_json, recomendacion)
                VALUES (:snapshot, :id_sku, :sku, :producto, :costo, :origen, :precio, :precio_escenario,
                 :margen, :utilidad_bruta, :gastos, :utilidad, :utilidad_pct, :precio_minimo,
                 :cantidad_inv, :disponible_inv, :valor_inv, :riesgo, :riesgo_tipo, :hallazgos, :evidencia, :recomendacion)");
            foreach ($items as $item) {
                $detalle->execute(array(
                    ":snapshot" => $idSnapshot,
                    ":id_sku" => $item["id_sku"],
                    ":sku" => $item["sku"],
                    ":producto" => $item["producto"],
                    ":costo" => $item["costo_real_sin_impuesto"],
                    ":origen" => $item["origen_costo"],
                    ":precio" => $item["precio_venta_sin_impuesto"],
                    ":precio_escenario" => $item["precio_escenario_sin_impuesto"],
                    ":margen" => $item["margen_bruto_pct"],
                    ":utilidad_bruta" => $item["utilidad_bruta"],
                    ":gastos" => $item["gastos_estimados"],
                    ":utilidad" => $item["utilidad_estimada"],
                    ":utilidad_pct" => $item["utilidad_estimada_pct"],
                    ":precio_minimo" => $item["precio_minimo_rentable"],
                    ":cantidad_inv" => $item["inventario"]["cantidad_total"],
                    ":disponible_inv" => $item["inventario"]["disponible_total"],
                    ":valor_inv" => $item["inventario"]["valor_total"],
                    ":riesgo" => $item["riesgo_clave"],
                    ":riesgo_tipo" => $item["riesgo_tipo"],
                    ":hallazgos" => json_encode($item["hallazgos_detalle"], JSON_UNESCAPED_UNICODE),
                    ":evidencia" => json_encode(array(
                        "inventario" => $item["inventario"],
                        "compras" => $item["compras"],
                        "xml" => $item["xml"],
                        "proveedor" => $item["proveedor"],
                        "fiscal" => $item["fiscal"]
                    ), JSON_UNESCAPED_UNICODE),
                    ":recomendacion" => $item["recomendacion"]
                ));
            }
            $db->commit();
            return $this->respuesta(false, "success", "Snapshot de rentabilidad guardado", array(
                "id_snapshot" => $idSnapshot,
                "folio" => $folio,
                "items" => count($items),
                "resumen" => $depurar["resumen"],
                "respaldo_externo_ref" => $autorizacion["depurar"]["respaldo_externo_ref"]
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function listarSnapshots($filtros = array()) {
        try {
            $db = $this->getConexion();
            $stmt = $db->prepare("SELECT id_snapshot, folio, canal, descuento_pct, gasto_operativo_pct,
                    comision_pct, margen_objetivo_pct, resumen_json, estatus, fecha_registro
                FROM erp_rentabilidad_snapshots
                ORDER BY id_snapshot DESC
                LIMIT 30");
            $stmt->execute();
            $items = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $fila["resumen"] = $fila["resumen_json"] ? json_decode($fila["resumen_json"], true) : array();
                unset($fila["resumen_json"]);
                $items[] = $fila;
            }
            return $this->respuesta(false, "success", "Snapshots consultados", array("items" => $items));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function auditarVigenciaSnapshots($filtros = array()) {
        try {
            $db = $this->getConexion();
            $limite = max(1, min(20, intval(isset($filtros["limite"]) ? $filtros["limite"] : 10)));
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $like = "%" . $termino . "%";
            $stmt = $db->prepare("SELECT id_snapshot, folio, canal, descuento_pct, gasto_operativo_pct,
                    comision_pct, margen_objetivo_pct, fecha_registro, estatus
                FROM erp_rentabilidad_snapshots
                WHERE estatus='cerrado'
                  AND (:termino='' OR EXISTS (
                      SELECT 1 FROM erp_rentabilidad_snapshot_detalle d
                      WHERE d.id_snapshot=erp_rentabilidad_snapshots.id_snapshot
                        AND (d.sku LIKE :like OR d.producto LIKE :like)
                  ))
                ORDER BY id_snapshot DESC
                LIMIT " . $limite);
            $stmt->execute(array(":termino" => $termino, ":like" => $like));
            $snapshots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = array();
            foreach ($snapshots as $snapshot) {
                $detalles = $this->detallesSnapshot($db, intval($snapshot["id_snapshot"]));
                $diferencias = array();
                foreach ($detalles as $detalle) {
                    $actual = $this->itemActualSnapshot($detalle["sku"], $snapshot);
                    if (!$actual) {
                        $diferencias[] = array(
                            "sku" => $detalle["sku"],
                            "tipo" => "sku_no_encontrado",
                            "mensaje" => "El SKU ya no aparece en el analisis actual"
                        );
                        continue;
                    }
                    $diff = $this->compararDetalleSnapshot($detalle, $actual);
                    if (!empty($diff)) {
                        $diferencias[] = array(
                            "sku" => $detalle["sku"],
                            "producto" => $detalle["producto"],
                            "riesgo_snapshot" => $detalle["riesgo_clave"],
                            "riesgo_actual" => $actual["riesgo_clave"],
                            "diferencias" => $diff
                        );
                    }
                }
                $items[] = array(
                    "id_snapshot" => intval($snapshot["id_snapshot"]),
                    "folio" => $snapshot["folio"],
                    "canal" => $snapshot["canal"],
                    "fecha_registro" => $snapshot["fecha_registro"],
                    "detalles" => count($detalles),
                    "diferencias_total" => count($diferencias),
                    "vigencia" => empty($diferencias) ? "vigente" : "desfasado",
                    "diferencias" => array_slice($diferencias, 0, 25)
                );
            }
            return $this->respuesta(false, "success", "Vigencia de snapshots auditada", array(
                "total" => count($items),
                "desfasados" => count(array_filter($items, function ($item) {
                    return isset($item["vigencia"]) && $item["vigencia"] === "desfasado";
                })),
                "items" => $items,
                "reglas" => array(
                    "Auditoria read-only: no modifica snapshots ni recomendaciones.",
                    "Un snapshot desfasado conserva valor historico, pero no debe usarse como precio vigente."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function auditarCostosPresentaciones($filtros = array()) {
        try {
            $db = $this->getConexion();
            $termino = trim(isset($filtros["q"]) ? $filtros["q"] : "");
            $limite = max(1, min(200, intval(isset($filtros["limite"]) ? $filtros["limite"] : 120)));
            $sql = "SELECT tr.id_sku_transformacion, tr.tipo_transformacion, tr.cantidad_origen, tr.unidades_resultado,
                    tr.merma_porcentaje, tr.estatus,
                    ori.id_sku id_sku_origen, ori.sku sku_origen, ori.nombre nombre_origen,
                    ori.costo_referencia costo_referencia_origen, ori.factor_unidad_base factor_origen,
                    res.id_sku id_sku_resultado, res.sku sku_resultado, res.nombre nombre_resultado,
                    res.costo_referencia costo_referencia_resultado, res.factor_unidad_base factor_resultado,
                    inv_ori.costo_promedio_inventario costo_inv_origen,
                    inv_res.costo_promedio_inventario costo_inv_resultado,
                    inv_res.cantidad_total cantidad_resultado
                FROM erp_catalogo_sku_transformaciones tr
                INNER JOIN erp_catalogo_skus ori ON ori.id_sku=tr.id_sku_origen
                INNER JOIN erp_catalogo_skus res ON res.id_sku=tr.id_sku_resultado
                LEFT JOIN (
                    SELECT id_sku_erp id_sku,
                        SUM(cantidad) cantidad_total,
                        CASE WHEN SUM(cantidad) > 0 THEN SUM(cantidad * costo_promedio) / SUM(cantidad) ELSE NULL END costo_promedio_inventario
                    FROM erp_inventario_existencias
                    GROUP BY id_sku_erp
                ) inv_ori ON inv_ori.id_sku=ori.id_sku
                LEFT JOIN (
                    SELECT id_sku_erp id_sku,
                        SUM(cantidad) cantidad_total,
                        CASE WHEN SUM(cantidad) > 0 THEN SUM(cantidad * costo_promedio) / SUM(cantidad) ELSE NULL END costo_promedio_inventario
                    FROM erp_inventario_existencias
                    GROUP BY id_sku_erp
                ) inv_res ON inv_res.id_sku=res.id_sku
                WHERE tr.estatus='activa'
                  AND (:termino='' OR ori.sku LIKE :buscar OR res.sku LIKE :buscar OR ori.nombre LIKE :buscar OR res.nombre LIKE :buscar)
                ORDER BY ori.sku, res.sku
                LIMIT " . $limite;
            $stmt = $db->prepare($sql);
            $stmt->execute(array(":termino" => $termino, ":buscar" => "%" . $termino . "%"));

            $items = array();
            $alertas = 0;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $item = $this->calcularConsistenciaPresentacion($fila);
                if ($item["estatus_consistencia"] !== "ok") {
                    $alertas++;
                }
                $items[] = $item;
            }
            return $this->respuesta(false, "success", "Consistencia de costos de presentaciones auditada", array(
                "total" => count($items),
                "alertas" => $alertas,
                "items" => $items,
                "reglas" => array(
                    "Auditoria read-only: no modifica Catalogo, Inventario ni Almacen.",
                    "Costo esperado = costo unitario origen * cantidad origen / unidades resultado, incluyendo merma si aplica.",
                    "Costo unitario origen usa inventario promedio si existe; si no, costo referencia dividido entre factor_unidad_base."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function preflightRecomendaciones($filtros = array()) {
        try {
            $canal = $this->opcion(isset($filtros["canal"]) ? $filtros["canal"] : "menudeo", array("menudeo", "mayoreo", "alianza"), "menudeo");
            $respuesta = $this->analizarSkus($filtros);
            if (!empty($respuesta["error"])) {
                return $respuesta;
            }

            $items = isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();
            $db = $this->getConexion();
            $existe = $db->prepare("SELECT id_recomendacion
                FROM erp_rentabilidad_recomendaciones
                WHERE id_sku=:sku AND canal=:canal AND estatus='pendiente'
                LIMIT 1");

            $resumen = array(
                "canal" => $canal,
                "evaluados" => count($items),
                "candidatos" => 0,
                "creables" => 0,
                "omitidas_pendientes" => 0,
                "perdida_estimada" => 0,
                "margen_bajo" => 0,
                "sin_precio" => 0,
                "delta_total" => 0
            );
            $candidatos = array();

            foreach ($items as $item) {
                if (!$this->esCandidatoRecomendacionPrecio($item)) {
                    continue;
                }
                $resumen["candidatos"]++;
                foreach (array("perdida_estimada", "margen_bajo", "sin_precio") as $hallazgo) {
                    if (in_array($hallazgo, $item["hallazgos"], true)) {
                        $resumen[$hallazgo]++;
                    }
                }

                $existe->execute(array(":sku" => $item["id_sku"], ":canal" => $canal));
                $pendiente = $existe->fetch(PDO::FETCH_ASSOC);
                $precioMinimo = $item["precio_minimo_rentable"] === null ? 0 : floatval($item["precio_minimo_rentable"]);
                $precioActual = floatval($item["precio_escenario_sin_impuesto"]);
                $precioRecomendado = max($precioActual, $precioMinimo);
                $delta = max(0, $precioRecomendado - $precioActual);
                $accion = $pendiente ? "omitir_pendiente" : "crear";
                if ($pendiente) {
                    $resumen["omitidas_pendientes"]++;
                } else {
                    $resumen["creables"]++;
                    $resumen["delta_total"] += $delta;
                }

                $candidatos[] = array(
                    "sku" => $item["sku"],
                    "producto" => $item["producto"],
                    "canal" => $canal,
                    "accion_preflight" => $accion,
                    "id_recomendacion_pendiente" => $pendiente ? intval($pendiente["id_recomendacion"]) : null,
                    "precio_actual_sin_impuesto" => round($precioActual, 6),
                    "precio_recomendado_sin_impuesto" => round($precioRecomendado, 6),
                    "delta" => round($delta, 6),
                    "motivo" => implode(",", $item["hallazgos"]),
                    "recomendacion" => $item["recomendacion"]
                );
            }

            $resumen["delta_total"] = round($resumen["delta_total"], 6);

            return $this->respuesta(false, "success", "Preflight de recomendaciones consultado", array(
                "resumen" => $resumen,
                "items" => array_slice($candidatos, 0, 120),
                "reglas" => array(
                    "Preflight read-only: no inserta recomendaciones, no resuelve pendientes y no modifica precios.",
                    "Usa la misma regla de candidato que el guardado real: perdida estimada, margen bajo o sin precio.",
                    "Una recomendacion pendiente del mismo SKU/canal se omite para evitar duplicados."
                )
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function guardarRecomendaciones($datos, $idUsuario = 0) {
        $db = $this->getConexion();
        try {
            $canal = $this->opcion(isset($datos["canal"]) ? $datos["canal"] : "menudeo", array("menudeo", "mayoreo", "alianza"), "menudeo");
            $autorizacion = $this->validarAutorizacionEscritura(
                $datos,
                "AUTORIZO CREAR RECOMENDACIONES",
                "crear recomendaciones persistentes"
            );
            if (!empty($autorizacion["error"])) {
                return $autorizacion;
            }
            $respuesta = $this->analizarSkus($datos);
            if ($respuesta["error"]) {
                return $respuesta;
            }
            $items = isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();
            $candidatos = array();
            foreach ($items as $item) {
                if ($this->esCandidatoRecomendacionPrecio($item)) {
                    $candidatos[] = $item;
                }
            }
            if (empty($candidatos)) {
                return $this->respuesta(true, "info", "No hay recomendaciones de precio para guardar");
            }

            $db->beginTransaction();
            $existe = $db->prepare("SELECT id_recomendacion FROM erp_rentabilidad_recomendaciones
                WHERE id_sku=:sku AND canal=:canal AND estatus='pendiente' LIMIT 1");
            $insertar = $db->prepare("INSERT INTO erp_rentabilidad_recomendaciones
                (id_sku, sku, canal, precio_actual_sin_impuesto, precio_recomendado_sin_impuesto,
                 motivo, estatus, comentario, creado_por)
                VALUES (:id_sku, :sku, :canal, :actual, :recomendado, :motivo, 'pendiente', :comentario, :usuario)");
            $creadas = 0;
            $omitidas = 0;
            foreach ($candidatos as $item) {
                $existe->execute(array(":sku" => $item["id_sku"], ":canal" => $canal));
                if ($existe->fetch(PDO::FETCH_ASSOC)) {
                    $omitidas++;
                    continue;
                }
                $precioMinimo = $item["precio_minimo_rentable"] === null ? 0 : floatval($item["precio_minimo_rentable"]);
                $precioActual = floatval($item["precio_escenario_sin_impuesto"]);
                $precioRecomendado = max($precioActual, $precioMinimo);
                $insertar->execute(array(
                    ":id_sku" => $item["id_sku"],
                    ":sku" => $item["sku"],
                    ":canal" => $canal,
                    ":actual" => $precioActual,
                    ":recomendado" => round($precioRecomendado, 6),
                    ":motivo" => implode(",", $item["hallazgos"]),
                    ":comentario" => $item["recomendacion"],
                    ":usuario" => intval($idUsuario) ?: null
                ));
                $creadas++;
            }
            $db->commit();
            return $this->respuesta(false, "success", "Recomendaciones guardadas", array(
                "canal" => $canal,
                "candidatos" => count($candidatos),
                "creadas" => $creadas,
                "omitidas_pendientes" => $omitidas,
                "respaldo_externo_ref" => $autorizacion["depurar"]["respaldo_externo_ref"]
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function validarAutorizacionEscritura($datos, $fraseEsperada, $accion) {
        $frase = trim(isset($datos["confirmar_autorizacion"]) ? $datos["confirmar_autorizacion"] : "");
        $respaldo = trim(isset($datos["respaldo_externo_ref"]) ? $datos["respaldo_externo_ref"] : "");
        if ($frase !== $fraseEsperada) {
            return $this->respuesta(true, "warning", "Autorizacion requerida para " . $accion, array(
                "frase_requerida" => $fraseEsperada
            ));
        }
        if (strlen($respaldo) < 8) {
            return $this->respuesta(true, "warning", "Indica la referencia del respaldo externo antes de " . $accion);
        }
        return $this->respuesta(false, "success", "Autorizacion validada", array(
            "respaldo_externo_ref" => $respaldo
        ));
    }

    private function esCandidatoRecomendacionPrecio($item) {
        $hallazgos = isset($item["hallazgos"]) && is_array($item["hallazgos"]) ? $item["hallazgos"] : array();
        return in_array("perdida_estimada", $hallazgos, true)
            || in_array("margen_bajo", $hallazgos, true)
            || in_array("sin_precio", $hallazgos, true);
    }

    public function listarRecomendaciones($filtros = array()) {
        try {
            $db = $this->getConexion();
            $estatus = $this->opcion(isset($filtros["estatus"]) ? $filtros["estatus"] : "pendiente", array("pendiente", "aprobada", "rechazada", "aplicada", "cancelada", "todas"), "pendiente");
            $sql = "SELECT r.*, s.nombre producto
                FROM erp_rentabilidad_recomendaciones r
                LEFT JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku
                WHERE (:estatus='todas' OR r.estatus=:estatus_filtro)
                ORDER BY FIELD(r.estatus,'pendiente','aprobada','rechazada','aplicada','cancelada'), r.id_recomendacion DESC
                LIMIT 100";
            $stmt = $db->prepare($sql);
            $stmt->execute(array(":estatus" => $estatus, ":estatus_filtro" => $estatus));
            return $this->respuesta(false, "success", "Recomendaciones consultadas", array(
                "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function resolverRecomendacion($datos, $idUsuario = 0) {
        $id = intval(isset($datos["id_recomendacion"]) ? $datos["id_recomendacion"] : 0);
        $accion = $this->opcion(isset($datos["accion"]) ? $datos["accion"] : "", array("aprobar", "rechazar", "cancelar"), "");
        $comentario = trim(isset($datos["comentario"]) ? $datos["comentario"] : "");
        if ($id <= 0 || $accion === "") {
            return $this->respuesta(true, "warning", "Selecciona una recomendacion y una accion valida");
        }
        $estatus = $accion === "aprobar" ? "aprobada" : ($accion === "rechazar" ? "rechazada" : "cancelada");
        $autorizacion = $this->validarAutorizacionEscritura(
            $datos,
            "AUTORIZO RESOLVER RECOMENDACION",
            "resolver recomendacion comercial"
        );
        if (!empty($autorizacion["error"])) {
            return $autorizacion;
        }
        $db = $this->getConexion();
        try {
            $stmt = $db->prepare("UPDATE erp_rentabilidad_recomendaciones
                SET estatus=:estatus, comentario=CONCAT(COALESCE(comentario,''), :comentario),
                    resuelto_por=:usuario, fecha_resolucion=NOW()
                WHERE id_recomendacion=:id AND estatus='pendiente'");
            $stmt->execute(array(
                ":estatus" => $estatus,
                ":comentario" => $comentario !== "" ? "\nResolucion: " . $comentario : "",
                ":usuario" => intval($idUsuario) ?: null,
                ":id" => $id
            ));
            if ($stmt->rowCount() <= 0) {
                return $this->respuesta(true, "warning", "La recomendacion ya no esta pendiente");
            }
            return $this->respuesta(false, "success", "Recomendacion " . $estatus, array(
                "id_recomendacion" => $id,
                "estatus" => $estatus,
                "respaldo_externo_ref" => $autorizacion["depurar"]["respaldo_externo_ref"]
            ));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function consultarDatosBaseSku($termino, $limite = 120) {
        $db = $this->getConexion();
        $limite = max(1, min(300, intval($limite)));
        $sql = "SELECT
                s.id_sku,
                s.sku,
                COALESCE(s.nombre, p.nombre) producto,
                s.costo_referencia,
                pr.precio precio_general,
                imp.clave_producto_sat,
                imp.clave_unidad_sat,
                imp.objeto_impuesto,
                imp.iva_porcentaje,
                imp.ieps_porcentaje,
                imp.incluye_impuestos,
                compra.ultimo_costo_compra,
                xml.ultimo_costo_xml,
                prov.costo_proveedor,
                prov.proveedor
            FROM erp_catalogo_skus s
            INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
            LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.estatus='activo'
            LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku
            LEFT JOIN (
                SELECT d.id_sku_erp id_sku,
                    SUBSTRING_INDEX(GROUP_CONCAT(ROUND(d.costo_unitario * CASE WHEN COALESCE(o.moneda,'MXN')<>'MXN' THEN COALESCE(NULLIF(o.tipo_cambio,0),1) ELSE 1 END, 6) ORDER BY o.fecha_orden DESC, d.id_detalle DESC), ',', 1) ultimo_costo_compra
                FROM erp_compras_ordenes_detalle d
                INNER JOIN erp_compras_ordenes o ON o.id_orden_compra=d.id_orden_compra
                WHERE COALESCE(o.estatus,'') <> 'cancelada' AND COALESCE(d.costo_unitario,0) > 0
                GROUP BY d.id_sku_erp
            ) compra ON compra.id_sku=s.id_sku
            LEFT JOIN (
                SELECT c.id_sku_erp id_sku,
                    SUBSTRING_INDEX(GROUP_CONCAT(ROUND(c.valor_unitario, 6) ORDER BY f.fecha_emision DESC, c.id_documento_concepto DESC), ',', 1) ultimo_costo_xml
                FROM erp_compras_documentos_fiscales_conceptos c
                INNER JOIN erp_compras_documentos_fiscales f ON f.id_documento_fiscal=c.id_documento_fiscal
                WHERE COALESCE(c.valor_unitario,0) > 0
                GROUP BY c.id_sku_erp
            ) xml ON xml.id_sku=s.id_sku
            LEFT JOIN (
                SELECT sp.id_sku,
                    SUBSTRING_INDEX(GROUP_CONCAT(ROUND(sp.costo_ultimo, 6) ORDER BY sp.es_preferido DESC, sp.fecha_actualizacion DESC, sp.id_sku_proveedor DESC), ',', 1) costo_proveedor,
                    SUBSTRING_INDEX(GROUP_CONCAT(prv.proveedor ORDER BY sp.es_preferido DESC, sp.fecha_actualizacion DESC, sp.id_sku_proveedor DESC), ',', 1) proveedor
                FROM erp_catalogo_sku_proveedores sp
                LEFT JOIN erp_proveedores prv ON prv.id_proveedor=sp.id_proveedor
                WHERE sp.estatus='activo' AND COALESCE(sp.costo_ultimo,0) > 0
                GROUP BY sp.id_sku
            ) prov ON prov.id_sku=s.id_sku
            WHERE s.estatus <> 'fusionado'
              AND (:termino='' OR s.sku LIKE :buscar OR s.nombre LIKE :buscar OR p.nombre LIKE :buscar)
            ORDER BY
                CASE WHEN s.costo_referencia<=0 THEN 0 ELSE 1 END,
                CASE WHEN pr.precio IS NULL OR pr.precio<=0 THEN 0 ELSE 1 END,
                s.sku ASC
            LIMIT " . $limite;
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":termino" => $termino, ":buscar" => "%" . $termino . "%"));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sugerenciaCostoBase($item) {
        if ($item["costo_proveedor"] !== null && $item["costo_proveedor"] > 0) {
            return "Validar costo proveedor $" . number_format($item["costo_proveedor"], 2, ".", ",") . " (" . ($item["proveedor"] ?: "proveedor") . ") para costo referencia.";
        }
        if ($item["ultimo_costo_compra"] !== null && $item["ultimo_costo_compra"] > 0) {
            return "Validar ultimo costo de compra $" . number_format($item["ultimo_costo_compra"], 2, ".", ",") . " para costo referencia.";
        }
        if ($item["ultimo_costo_xml"] !== null && $item["ultimo_costo_xml"] > 0) {
            return "Validar ultimo costo XML $" . number_format($item["ultimo_costo_xml"], 2, ".", ",") . " para costo referencia.";
        }
        return "Capturar costo referencia o relacionar proveedor/compra/XML antes de cerrar precio.";
    }

    private function resumenDatosBaseDetalle($fila, $item) {
        $faltantesFiscal = array();
        foreach (array("clave_producto_sat", "clave_unidad_sat", "objeto_impuesto", "iva_porcentaje", "ieps_porcentaje", "incluye_impuestos") as $campo) {
            if ($fila[$campo] === null || trim(strval($fila[$campo])) === "") {
                $faltantesFiscal[] = $campo;
            }
        }

        $sugerencias = array();
        if (in_array("sin_costo", $item["hallazgos"], true)) {
            $sugerencias[] = $this->sugerenciaCostoBase(array(
                "costo_proveedor" => $fila["costo_proveedor"] === null ? null : floatval($fila["costo_proveedor"]),
                "proveedor" => $fila["proveedor"],
                "ultimo_costo_compra" => $fila["ultimo_costo_compra"] === null ? null : floatval($fila["ultimo_costo_compra"]),
                "ultimo_costo_xml" => $fila["ultimo_costo_xml"] === null ? null : floatval($fila["ultimo_costo_xml"])
            ));
        }
        if (in_array("sin_precio", $item["hallazgos"], true)) {
            $sugerencias[] = "Capturar precio general activo antes de simular cierre comercial.";
        }
        if (!empty($faltantesFiscal)) {
            $sugerencias[] = "Completar " . implode(", ", $faltantesFiscal) . " en ficha fiscal del SKU.";
        }

        return array(
            "costo_referencia" => round(floatval($fila["costo_referencia"]), 6),
            "precio_general" => $fila["precio_general"] === null ? null : round(floatval($fila["precio_general"]), 6),
            "ultimo_costo_compra" => $fila["ultimo_costo_compra"] === null ? null : round(floatval($fila["ultimo_costo_compra"]), 6),
            "ultimo_costo_xml" => $fila["ultimo_costo_xml"] === null ? null : round(floatval($fila["ultimo_costo_xml"]), 6),
            "costo_proveedor" => $fila["costo_proveedor"] === null ? null : round(floatval($fila["costo_proveedor"]), 6),
            "proveedor" => $fila["proveedor"],
            "fiscal" => array(
                "clave_producto_sat" => $fila["clave_producto_sat"],
                "clave_unidad_sat" => $fila["clave_unidad_sat"],
                "objeto_impuesto" => $fila["objeto_impuesto"],
                "iva_porcentaje" => $fila["iva_porcentaje"] === null ? null : round(floatval($fila["iva_porcentaje"]), 4),
                "ieps_porcentaje" => $fila["ieps_porcentaje"] === null ? null : round(floatval($fila["ieps_porcentaje"]), 4),
                "incluye_impuestos" => $fila["incluye_impuestos"] === null ? null : intval($fila["incluye_impuestos"])
            ),
            "faltantes_fiscal" => $faltantesFiscal,
            "sugerencias" => $sugerencias
        );
    }

    private function filtrarFiscalXmlDetalle($fiscalXml, $sku) {
        $items = array();
        $conSugerencia = 0;
        foreach (isset($fiscalXml["items"]) ? $fiscalXml["items"] : array() as $item) {
            if (strcasecmp($item["sku"], $sku) !== 0) {
                continue;
            }
            if (!empty($item["tiene_sugerencia_xml"])) {
                $conSugerencia++;
            }
            $items[] = $item;
        }
        return array(
            "total_fiscal_incompleto" => count($items),
            "con_sugerencia_xml" => $conSugerencia,
            "sin_sugerencia_xml" => count($items) - $conSugerencia,
            "items" => $items,
            "reglas" => isset($fiscalXml["reglas"]) ? $fiscalXml["reglas"] : array()
        );
    }

    private function dictamenCierreItem($item) {
        $bloqueos = array();
        $alertas = array();
        $hallazgos = isset($item["hallazgos"]) ? $item["hallazgos"] : array();
        $precioMinimo = $item["precio_minimo_rentable"] === null ? null : floatval($item["precio_minimo_rentable"]);
        $precioActual = floatval($item["precio_escenario_sin_impuesto"]);

        if (in_array("sin_costo", $hallazgos, true)) {
            $bloqueos[] = array("id" => "COST-H101", "clave" => "sin_costo", "mensaje" => "Completar costo real antes de cerrar precio.");
        }
        if (in_array("sin_precio", $hallazgos, true)) {
            $bloqueos[] = array("id" => "COST-H102", "clave" => "sin_precio", "mensaje" => "Capturar precio general antes de simular venta.");
        }
        if (in_array("perdida_estimada", $hallazgos, true)) {
            $bloqueos[] = array("id" => "COST-H104", "clave" => "perdida_estimada", "mensaje" => "El escenario deja utilidad negativa.");
        }
        if (in_array("stock_sin_costo_promedio", $hallazgos, true)) {
            $bloqueos[] = array("id" => "COST-H106", "clave" => "stock_sin_costo_promedio", "mensaje" => "Hay stock sin costo promedio confiable.");
        }
        if ($precioMinimo !== null && $precioActual > 0 && $precioMinimo > $precioActual + 0.01) {
            $bloqueos[] = array("id" => "COST-H107", "clave" => "precio_bajo_minimo", "mensaje" => "Precio actual menor al minimo rentable.");
        }

        if (in_array("fiscal_incompleto", $hallazgos, true)) {
            $alertas[] = array("id" => "COST-H103", "clave" => "fiscal_incompleto", "mensaje" => "Completar fiscal antes de publicar precio final.");
        }
        if (in_array("margen_bajo", $hallazgos, true)) {
            $alertas[] = array("id" => "COST-H105", "clave" => "margen_bajo", "mensaje" => "Validar margen contra estrategia comercial.");
        }

        if (!empty($bloqueos)) {
            return array(
                "estado" => "bloqueado",
                "tipo" => "danger",
                "bloqueos" => $bloqueos,
                "alertas" => $alertas,
                "siguiente_paso" => $bloqueos[0]["mensaje"]
            );
        }
        if (!empty($alertas)) {
            return array(
                "estado" => "precaucion",
                "tipo" => "warning",
                "bloqueos" => $bloqueos,
                "alertas" => $alertas,
                "siguiente_paso" => $alertas[0]["mensaje"]
            );
        }
        return array(
            "estado" => "listo",
            "tipo" => "success",
            "bloqueos" => $bloqueos,
            "alertas" => $alertas,
            "siguiente_paso" => "SKU listo para cierre comercial read-only; confirmar politica antes de publicar."
        );
    }

    private function dictamenDetalleCierreSku($item, $plan, $preflight, $recomendaciones, $estadoModulo) {
        $base = $this->dictamenCierreItem($item);
        $sku = isset($item["sku"]) ? $item["sku"] : "";
        $planItem = $this->buscarItemSkuEnGrupos(isset($plan["grupos"]) ? $plan["grupos"] : array(), $sku);
        $preflightItem = $this->buscarItemSku(isset($preflight["items"]) ? $preflight["items"] : array(), $sku);
        $recomendacionItem = $this->buscarItemSku(isset($recomendaciones["items"]) ? $recomendaciones["items"] : array(), $sku);
        $resModulo = isset($estadoModulo["resumen"]) ? $estadoModulo["resumen"] : array();

        $bloqueos = isset($base["bloqueos"]) ? $base["bloqueos"] : array();
        $alertas = isset($base["alertas"]) ? $base["alertas"] : array();
        $estadoAprobacion = $preflightItem && isset($preflightItem["estado"]) ? $preflightItem["estado"] : null;
        if ($estadoAprobacion === "bloqueados") {
            $bloqueos[] = array("id" => "COST-H201", "clave" => "aprobacion_bloqueada", "mensaje" => "El preflight de aprobacion no permite cerrar este precio.");
        } elseif ($estadoAprobacion === "requieren_revision") {
            $alertas[] = array("id" => "COST-H202", "clave" => "aprobacion_revision", "mensaje" => "El precio requiere revision comercial antes de aprobarse.");
        }

        if ($recomendacionItem && isset($recomendacionItem["accion_preflight"]) && $recomendacionItem["accion_preflight"] === "crear") {
            $alertas[] = array("id" => "COST-H203", "clave" => "recomendacion_creable", "mensaje" => "Existe una recomendacion comercial creable; requiere respaldo y autorizacion para persistir.");
        }

        $estado = "listo";
        $tipo = "success";
        $siguientePaso = "SKU listo como evidencia read-only; no publicar ni aplicar precio sin politica final.";
        if (!empty($bloqueos)) {
            $estado = "bloqueado";
            $tipo = "danger";
            $siguientePaso = $bloqueos[0]["mensaje"];
        } elseif (!empty($alertas)) {
            $estado = $recomendacionItem ? "requiere_autorizacion" : "precaucion";
            $tipo = $recomendacionItem ? "warning" : "info";
            $siguientePaso = $alertas[0]["mensaje"];
        }

        return array(
            "estado" => $estado,
            "tipo" => $tipo,
            "grupo_plan" => $planItem && isset($planItem["grupo"]) ? $planItem["grupo"] : null,
            "plan_siguiente_paso" => $planItem && isset($planItem["siguiente_paso"]) ? $planItem["siguiente_paso"] : null,
            "aprobacion" => $preflightItem ? array(
                "estado" => isset($preflightItem["estado"]) ? $preflightItem["estado"] : null,
                "precio_actual" => isset($preflightItem["precio_actual_sin_impuesto"]) ? $preflightItem["precio_actual_sin_impuesto"] : null,
                "precio_minimo" => isset($preflightItem["precio_minimo_rentable"]) ? $preflightItem["precio_minimo_rentable"] : null,
                "precio_sugerido" => isset($preflightItem["precio_sugerido_sin_impuesto"]) ? $preflightItem["precio_sugerido_sin_impuesto"] : null,
                "delta" => isset($preflightItem["delta"]) ? $preflightItem["delta"] : null,
                "siguiente_paso" => isset($preflightItem["siguiente_paso"]) ? $preflightItem["siguiente_paso"] : null
            ) : null,
            "recomendacion_preflight" => $recomendacionItem ? array(
                "accion" => isset($recomendacionItem["accion_preflight"]) ? $recomendacionItem["accion_preflight"] : null,
                "precio_actual" => isset($recomendacionItem["precio_actual_sin_impuesto"]) ? $recomendacionItem["precio_actual_sin_impuesto"] : null,
                "precio_recomendado" => isset($recomendacionItem["precio_recomendado_sin_impuesto"]) ? $recomendacionItem["precio_recomendado_sin_impuesto"] : null,
                "delta" => isset($recomendacionItem["delta"]) ? $recomendacionItem["delta"] : null,
                "motivo" => isset($recomendacionItem["motivo"]) ? $recomendacionItem["motivo"] : null
            ) : null,
            "estado_modulo" => array(
                "estado_general" => isset($resModulo["estado_general"]) ? $resModulo["estado_general"] : null,
                "bloqueados" => isset($resModulo["bloqueados"]) ? intval($resModulo["bloqueados"]) : 0,
                "requieren_autorizacion" => isset($resModulo["requieren_autorizacion"]) ? intval($resModulo["requieren_autorizacion"]) : 0,
                "advertencias" => isset($resModulo["advertencias"]) ? intval($resModulo["advertencias"]) : 0
            ),
            "bloqueos" => $bloqueos,
            "alertas" => $alertas,
            "siguiente_paso" => $siguientePaso,
            "reglas" => array(
                "Dictamen read-only: consolida evidencia, no aprueba ni aplica precios.",
                "Persistir recomendaciones, snapshots o resoluciones requiere respaldo externo y frase de autorizacion."
            )
        );
    }

    private function buscarItemSku($items, $sku) {
        foreach ($items as $item) {
            if (isset($item["sku"]) && strcasecmp($item["sku"], $sku) === 0) {
                return $item;
            }
        }
        return null;
    }

    private function buscarItemSkuEnGrupos($grupos, $sku) {
        foreach ($grupos as $clave => $grupo) {
            foreach (isset($grupo["items"]) ? $grupo["items"] : array() as $item) {
                if (isset($item["sku"]) && strcasecmp($item["sku"], $sku) === 0) {
                    $item["grupo"] = $clave;
                    return $item;
                }
            }
        }
        return null;
    }

    private function preflightDestinoUsoComercial($titulo, $datos) {
        $listos = intval(isset($datos["listos"]) ? $datos["listos"] : 0);
        $bloqueados = intval(isset($datos["bloqueados"]) ? $datos["bloqueados"] : 0);
        $estado = "bloqueado";
        $siguientePaso = "No usar este destino comercial hasta liberar aprobacion de precios y datos bloqueantes.";
        if ($listos > 0 && $bloqueados === 0) {
            $estado = "listo";
            $siguientePaso = "Destino usable como evidencia read-only; no publicar sin autorizacion.";
        } elseif ($listos === 0 && $bloqueados === 0) {
            $estado = "sin_casos";
            $siguientePaso = "Sin SKUs recomendados para este destino con el filtro actual.";
        }
        return array(
            "titulo" => $titulo,
            "estado" => $estado,
            "listos" => $listos,
            "bloqueados" => $bloqueados,
            "muestra" => isset($datos["muestra"]) ? $datos["muestra"] : array(),
            "siguiente_paso" => $siguientePaso
        );
    }

    private function dictamenAprobacionPrecioItem($item, $variacion = null) {
        $bloqueos = array();
        $alertas = array();
        $hallazgos = isset($item["hallazgos"]) ? $item["hallazgos"] : array();
        $precioActual = floatval($item["precio_escenario_sin_impuesto"]);
        $precioMinimo = $item["precio_minimo_rentable"] === null ? null : floatval($item["precio_minimo_rentable"]);
        $precioSugerido = $precioMinimo === null ? $precioActual : max($precioActual, $precioMinimo);
        $delta = max(0, $precioSugerido - $precioActual);

        if (in_array("sin_costo", $hallazgos, true)) {
            $bloqueos[] = array("id" => "COST-H101", "clave" => "sin_costo", "mensaje" => "No aprobar precio sin costo real.");
        }
        if (in_array("sin_precio", $hallazgos, true)) {
            $bloqueos[] = array("id" => "COST-H102", "clave" => "sin_precio", "mensaje" => "No aplicar precio automatico sin politica de precio inicial.");
        }
        if (in_array("fiscal_incompleto", $hallazgos, true)) {
            $bloqueos[] = array("id" => "COST-H103", "clave" => "fiscal_incompleto", "mensaje" => "Completar fiscal antes de aprobar/publicar precio.");
        }
        if (in_array("stock_sin_costo_promedio", $hallazgos, true)) {
            $bloqueos[] = array("id" => "COST-H106", "clave" => "stock_sin_costo_promedio", "mensaje" => "Validar costo promedio antes de aprobar precio.");
        }

        if (in_array("perdida_estimada", $hallazgos, true)) {
            $alertas[] = array("id" => "COST-H104", "clave" => "perdida_estimada", "mensaje" => "Revisar precio sugerido porque el precio actual genera perdida.");
        }
        if (in_array("margen_bajo", $hallazgos, true)) {
            $alertas[] = array("id" => "COST-H105", "clave" => "margen_bajo", "mensaje" => "Validar margen contra estrategia comercial.");
        }
        if ($delta > 0.01) {
            $alertas[] = array("id" => "COST-H107", "clave" => "precio_bajo_minimo", "mensaje" => "Precio actual menor al minimo rentable; requiere aprobacion comercial.");
        }
        if ($variacion && intval(isset($variacion["alertas"]) ? $variacion["alertas"] : 0) > 0) {
            $alertas[] = array("id" => "COST-H108", "clave" => "variacion_costo", "mensaje" => "Validar costo contra evidencia historica antes de aprobar precio.");
        }

        $estado = "aprobables";
        $tipo = "success";
        $siguientePaso = $delta > 0.01 ? "Aprobable para subir precio tras confirmacion directiva." : "Aprobable para conservar precio vigente.";
        if (!empty($bloqueos)) {
            $estado = "bloqueados";
            $tipo = "danger";
            $siguientePaso = $bloqueos[0]["mensaje"];
        } elseif (!empty($alertas)) {
            $estado = "requieren_revision";
            $tipo = "warning";
            $siguientePaso = $alertas[0]["mensaje"];
        }

        return array(
            "estado" => $estado,
            "tipo" => $tipo,
            "accion_precio" => $delta > 0.01 ? "subir_precio" : "conservar_precio",
            "precio_actual" => round($precioActual, 6),
            "precio_minimo" => $precioMinimo === null ? null : round($precioMinimo, 6),
            "precio_sugerido" => round($precioSugerido, 6),
            "delta" => round($delta, 6),
            "bloqueos" => $bloqueos,
            "alertas" => $alertas,
            "siguiente_paso" => $siguientePaso
        );
    }

    private function compararCostoEvidencia($costoActual, $costoEvidencia, $fuente, $umbralPct) {
        if ($costoEvidencia === null || floatval($costoEvidencia) <= 0 || floatval($costoActual) <= 0) {
            return null;
        }
        $actual = floatval($costoActual);
        $evidencia = floatval($costoEvidencia);
        $diferencia = $actual - $evidencia;
        $diferenciaPct = ($diferencia / $evidencia) * 100;
        $alerta = abs($diferenciaPct) > floatval($umbralPct);
        return array(
            "fuente" => $fuente,
            "costo_evidencia" => round($evidencia, 6),
            "diferencia" => round($diferencia, 6),
            "diferencia_pct" => round($diferenciaPct, 2),
            "alerta" => $alerta,
            "tipo" => $alerta ? "warning" : "success"
        );
    }

    private function simularSensibilidadItem($costo, $precio, $gastoPct, $comisionPct) {
        $costo = floatval($costo);
        $precio = floatval($precio);
        $gastos = $precio * ((floatval($gastoPct) + floatval($comisionPct)) / 100);
        $utilidad = ($precio - $costo) - $gastos;
        $margen = $precio > 0 ? (($precio - $costo) / $precio) * 100 : null;
        $estado = "rentable";
        $tipo = "success";
        if ($precio <= 0 || $costo <= 0) {
            $estado = "incompleto";
            $tipo = "warning";
        } elseif ($utilidad <= 0) {
            $estado = "perdida";
            $tipo = "danger";
        } elseif ($margen !== null && $margen < 15) {
            $estado = "margen_bajo";
            $tipo = "warning";
        }
        return array(
            "costo" => round($costo, 6),
            "precio" => round($precio, 6),
            "margen" => $margen === null ? null : round($margen, 2),
            "utilidad" => round($utilidad, 6),
            "estado" => $estado,
            "tipo" => $tipo
        );
    }

    private function dictamenCanalRecomendado($escenarios) {
        $rentables = array();
        $precaucion = array();
        foreach ($escenarios as $canal => $escenario) {
            if ($escenario["riesgo"] === "rentable" || $escenario["riesgo"] === "revision") {
                $rentables[] = $escenario;
            } elseif ($escenario["riesgo"] === "margen_bajo") {
                $precaucion[] = $escenario;
            }
        }
        $candidatos = !empty($rentables) ? $rentables : $precaucion;
        if (empty($candidatos)) {
            return array(
                "canal" => null,
                "estado" => "bloqueados",
                "tipo" => "danger",
                "utilidad" => null,
                "margen" => null,
                "motivo" => "Sin canal vendible: hay perdida o datos incompletos en todos los escenarios."
            );
        }
        usort($candidatos, function ($a, $b) {
            return floatval($a["utilidad"]) < floatval($b["utilidad"]) ? 1 : -1;
        });
        $mejor = $candidatos[0];
        $estado = empty($rentables) ? "precaucion" : "listos";
        return array(
            "canal" => $mejor["canal"],
            "estado" => $estado,
            "tipo" => $estado === "listos" ? "success" : "warning",
            "utilidad" => $mejor["utilidad"],
            "margen" => $mejor["margen"],
            "motivo" => $estado === "listos"
                ? "Canal con mayor utilidad entre escenarios vendibles."
                : "Canal viable solo con precaucion por margen bajo."
        );
    }

    private function indexarItemsPorSku($items) {
        $mapa = array();
        foreach ($items as $item) {
            if (isset($item["sku"])) {
                $mapa[$item["sku"]] = $item;
            }
        }
        return $mapa;
    }

    private function tablaExisteSimple($tabla) {
        try {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
                return false;
            }
            $db = $this->getConexion();
            $stmt = $db->prepare("SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = :base AND TABLE_NAME = :tabla
                LIMIT 1");
            $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
            return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function schemaAprobacionesInternasDisponible() {
        return $this->tablaExisteSimple("erp_rentabilidad_aprobaciones_comerciales")
            && $this->tablaExisteSimple("erp_rentabilidad_aprobaciones_bitacora");
    }

    private function insertarBitacoraAprobacion($db, $idAprobacion, $accion, $estatusAnterior, $estatusNuevo, $comentario, $antes, $despues, $respaldo, $idUsuario) {
        $stmt = $db->prepare("INSERT INTO erp_rentabilidad_aprobaciones_bitacora
            (id_aprobacion, accion, estatus_anterior, estatus_nuevo, comentario,
             datos_antes_json, datos_despues_json, respaldo_externo_ref, usuario_id)
            VALUES (:id, :accion, :anterior, :nuevo, :comentario, :antes, :despues, :respaldo, :usuario)");
        $stmt->execute(array(
            ":id" => intval($idAprobacion),
            ":accion" => $accion,
            ":anterior" => $estatusAnterior,
            ":nuevo" => $estatusNuevo,
            ":comentario" => $comentario,
            ":antes" => $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE),
            ":despues" => $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE),
            ":respaldo" => $respaldo,
            ":usuario" => intval($idUsuario) ?: null
        ));
    }

    private function evidenciaAprobacionInterna($item, $preflight, $escenario) {
        if (empty($item)) {
            return array(
                "disponible" => false,
                "motivo" => "No se encontro item de analisis para congelar evidencia."
            );
        }
        return array(
            "disponible" => true,
            "costo_real_sin_impuesto" => $item["costo_real_sin_impuesto"],
            "origen_costo" => $item["origen_costo"],
            "precio_actual_sin_impuesto" => $preflight["precio_actual_sin_impuesto"],
            "precio_minimo_rentable" => $preflight["precio_minimo_rentable"],
            "precio_aprobado_sin_impuesto" => $preflight["precio_sugerido_sin_impuesto"],
            "margen_bruto_pct" => $item["margen_bruto_pct"],
            "utilidad_estimada" => $item["utilidad_estimada"],
            "escenario" => array(
                "canal" => isset($escenario["canal"]) ? $escenario["canal"] : $item["canal"],
                "descuento_pct" => isset($escenario["descuento_pct"]) ? $escenario["descuento_pct"] : 0,
                "gasto_operativo_pct" => isset($escenario["gasto_pct"]) ? $escenario["gasto_pct"] : 0,
                "comision_pct" => isset($escenario["comision_pct"]) ? $escenario["comision_pct"] : 0,
                "margen_objetivo_pct" => isset($escenario["margen_objetivo_pct"]) ? $escenario["margen_objetivo_pct"] : 0
            ),
            "hallazgos" => isset($item["hallazgos_detalle"]) ? $item["hallazgos_detalle"] : array(),
            "bloqueos" => isset($preflight["bloqueos"]) ? $preflight["bloqueos"] : array(),
            "alertas" => isset($preflight["alertas"]) ? $preflight["alertas"] : array(),
            "inventario" => isset($item["inventario"]) ? $item["inventario"] : array(),
            "compras" => isset($item["compras"]) ? $item["compras"] : array(),
            "xml" => isset($item["xml"]) ? $item["xml"] : array(),
            "fiscal" => isset($item["fiscal"]) ? $item["fiscal"] : array()
        );
    }

    private function impactoGrupoBase($titulo, $tipo) {
        return array(
            "titulo" => $titulo,
            "tipo" => $tipo,
            "skus" => 0,
            "utilidad_estimada" => 0,
            "utilidad_no_confiable" => 0,
            "utilidad_negativa" => 0,
            "deficit_precio" => 0,
            "valor_inventario" => 0,
            "items" => array()
        );
    }

    private function scorePrioridadCierre($grupo, $item, $deficitPrecio, $variacion) {
        $pesos = array(
            "revisar_precio" => 850,
            "validar_costo" => 780,
            "completar_datos" => 720,
            "completar_fiscal" => 560,
            "revisar_canal" => 520,
            "cerrar" => 100
        );
        $score = isset($pesos[$grupo]) ? $pesos[$grupo] : 400;
        $utilidad = floatval($item["utilidad_estimada"]);
        $valorInventario = floatval($item["inventario"]["valor_total"]);
        $score += max(0, -1 * $utilidad) * 1.2;
        $score += floatval($deficitPrecio) * 0.8;
        $score += $valorInventario * 0.08;
        if ($grupo !== "completar_datos") {
            $score += max(0, $utilidad) * 0.03;
        }
        if ($variacion !== null) {
            $score += intval($variacion["alertas"]) * 120;
        }
        return $score;
    }

    private function responsablePrioridadCierre($grupo) {
        $mapa = array(
            "completar_datos" => "Catalogo",
            "revisar_precio" => "Direccion/Comercial",
            "validar_costo" => "Compras/Almacen",
            "completar_fiscal" => "Catalogo/Fiscal",
            "revisar_canal" => "Direccion/Comercial",
            "cerrar" => "Direccion"
        );
        return isset($mapa[$grupo]) ? $mapa[$grupo] : "Operacion";
    }

    private function autorizacionAccionBase($id, $titulo, $estado, $permiso, $respaldo, $objetivo, $restriccion, $metricas) {
        return array(
            "id" => $id,
            "titulo" => $titulo,
            "estado" => $estado,
            "permiso_requerido" => $permiso,
            "respaldo_requerido" => $respaldo,
            "objetivo" => $objetivo,
            "restriccion" => $restriccion,
            "metricas" => $metricas
        );
    }

    private function clasificarPlanCierreItem($item, $canal, $sensibilidad, $variacion) {
        $hallazgos = isset($item["hallazgos"]) ? $item["hallazgos"] : array();
        $precioMinimo = $item["precio_minimo_rentable"] === null ? null : floatval($item["precio_minimo_rentable"]);
        $precio = floatval($item["precio_escenario_sin_impuesto"]);

        if (in_array("sin_costo", $hallazgos, true) || in_array("sin_precio", $hallazgos, true)) {
            return array(
                "grupo" => "completar_datos",
                "siguiente_paso" => "Completar costo y precio antes de cualquier cierre.",
                "motivo" => "Datos economicos incompletos."
            );
        }
        if (in_array("perdida_estimada", $hallazgos, true) || ($precioMinimo !== null && $precio > 0 && $precioMinimo > $precio + 0.01) || in_array("margen_bajo", $hallazgos, true)) {
            return array(
                "grupo" => "revisar_precio",
                "siguiente_paso" => "Ajustar precio, descuento o gasto del escenario antes de publicar.",
                "motivo" => "Margen o utilidad no soportan el cierre."
            );
        }
        if ($canal === null || empty($canal["canal_recomendado"]) || $canal["estado"] === "bloqueados") {
            return array(
                "grupo" => "revisar_canal",
                "siguiente_paso" => "Definir canal comercial viable antes de cerrar precio.",
                "motivo" => "No hay canal recomendado confiable."
            );
        }
        if (in_array("stock_sin_costo_promedio", $hallazgos, true) || ($variacion !== null && intval($variacion["alertas"]) > 0)) {
            return array(
                "grupo" => "validar_costo",
                "siguiente_paso" => "Validar costo contra inventario, compras, XML o proveedor.",
                "motivo" => "Costo con alerta o evidencia historica desalineada."
            );
        }
        if ($sensibilidad !== null && !empty($sensibilidad["vulnerable"])) {
            return array(
                "grupo" => "revisar_precio",
                "siguiente_paso" => "Aumentar colchon de margen antes de cerrar.",
                "motivo" => "El SKU no resiste el shock de sensibilidad."
            );
        }
        if (in_array("fiscal_incompleto", $hallazgos, true)) {
            return array(
                "grupo" => "completar_fiscal",
                "siguiente_paso" => "Completar fiscal para precio sin impuestos confiable.",
                "motivo" => "Fiscal incompleto."
            );
        }
        return array(
            "grupo" => "cerrar",
            "siguiente_paso" => "Candidato para snapshot o aprobacion de cierre comercial.",
            "motivo" => "Sin bloqueos del plan read-only."
        );
    }

    private function ordenarPlanCierreGrupo(&$items, $grupo) {
        usort($items, function ($a, $b) use ($grupo) {
            if ($grupo === "revisar_precio") {
                return floatval($a["utilidad"]) < floatval($b["utilidad"]) ? -1 : 1;
            }
            if ($grupo === "validar_costo") {
                if (intval($a["alertas_costo"]) !== intval($b["alertas_costo"])) {
                    return intval($b["alertas_costo"]) - intval($a["alertas_costo"]);
                }
            }
            return strcmp($a["sku"], $b["sku"]);
        });
    }

    private function ordenarRevisionGrupo(&$items, $modo) {
        usort($items, function ($a, $b) use ($modo) {
            if ($modo === "utilidad_asc") {
                return floatval($a["utilidad"]) < floatval($b["utilidad"]) ? -1 : 1;
            }
            if ($modo === "utilidad_desc") {
                return floatval($a["utilidad"]) < floatval($b["utilidad"]) ? 1 : -1;
            }
            if ($modo === "delta_desc") {
                return floatval($a["delta"]) < floatval($b["delta"]) ? 1 : -1;
            }
            if ($modo === "inventario_desc") {
                return floatval($a["valor_inventario"]) < floatval($b["valor_inventario"]) ? 1 : -1;
            }
            return strcmp($a["sku"], $b["sku"]);
        });
    }

    private function consultarFilasSku($termino, $limite = 300, $priorizarExacto = false) {
        $db = $this->getConexion();
        $limite = max(1, min(500, intval($limite)));
        $ordenExacto = $priorizarExacto ? "CASE WHEN UPPER(s.sku)=UPPER(:exacto) THEN 0 ELSE 1 END, " : "";
        $sql = "SELECT
                s.id_sku, s.sku, COALESCE(s.nombre, p.nombre) producto, s.costo_referencia, s.factor_unidad_base,
                COALESCE(pr.precio, 0) precio_venta, COALESCE(pr.moneda, 'MXN') moneda_precio,
                imp.iva_porcentaje, imp.ieps_porcentaje, imp.incluye_impuestos,
                inv.cantidad_total, inv.disponible_total, inv.apartada_total, inv.valor_total, inv.costo_promedio_inventario,
                compra.ultimo_costo_compra, compra.fecha_ultima_compra, compra.costo_promedio_compras,
                xml.ultimo_costo_xml, xml.fecha_ultimo_xml,
                prov.costo_ultimo_proveedor, prov.proveedor_preferido
            FROM erp_catalogo_skus s
            INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
            LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.estatus='activo'
            LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku
            LEFT JOIN (
                SELECT id_sku_erp id_sku,
                    SUM(cantidad) cantidad_total,
                    SUM(cantidad_disponible) disponible_total,
                    SUM(cantidad_apartada) apartada_total,
                    SUM(cantidad * costo_promedio) valor_total,
                    CASE WHEN SUM(cantidad) > 0 THEN SUM(cantidad * costo_promedio) / SUM(cantidad) ELSE NULL END costo_promedio_inventario
                FROM erp_inventario_existencias
                GROUP BY id_sku_erp
            ) inv ON inv.id_sku=s.id_sku
            LEFT JOIN (
                SELECT d.id_sku_erp id_sku,
                    SUBSTRING_INDEX(GROUP_CONCAT(ROUND(d.costo_unitario * CASE WHEN COALESCE(o.moneda,'MXN')<>'MXN' THEN COALESCE(NULLIF(o.tipo_cambio,0),1) ELSE 1 END, 6) ORDER BY o.fecha_orden DESC, d.id_detalle DESC), ',', 1) ultimo_costo_compra,
                    MAX(o.fecha_orden) fecha_ultima_compra,
                    CASE WHEN SUM(COALESCE(d.cantidad_recibida, d.cantidad, 0)) > 0 THEN
                        SUM(COALESCE(d.cantidad_recibida, d.cantidad, 0) * d.costo_unitario * CASE WHEN COALESCE(o.moneda,'MXN')<>'MXN' THEN COALESCE(NULLIF(o.tipo_cambio,0),1) ELSE 1 END)
                        / SUM(COALESCE(d.cantidad_recibida, d.cantidad, 0))
                    ELSE NULL END costo_promedio_compras
                FROM erp_compras_ordenes_detalle d
                INNER JOIN erp_compras_ordenes o ON o.id_orden_compra=d.id_orden_compra
                WHERE COALESCE(o.estatus,'') <> 'cancelada' AND COALESCE(d.costo_unitario,0) > 0
                GROUP BY d.id_sku_erp
            ) compra ON compra.id_sku=s.id_sku
            LEFT JOIN (
                SELECT c.id_sku_erp id_sku,
                    SUBSTRING_INDEX(GROUP_CONCAT(ROUND(c.valor_unitario, 6) ORDER BY f.fecha_emision DESC, c.id_documento_concepto DESC), ',', 1) ultimo_costo_xml,
                    MAX(f.fecha_emision) fecha_ultimo_xml
                FROM erp_compras_documentos_fiscales_conceptos c
                INNER JOIN erp_compras_documentos_fiscales f ON f.id_documento_fiscal=c.id_documento_fiscal
                WHERE COALESCE(c.valor_unitario,0) > 0
                GROUP BY c.id_sku_erp
            ) xml ON xml.id_sku=s.id_sku
            LEFT JOIN (
                SELECT sp.id_sku,
                    SUBSTRING_INDEX(GROUP_CONCAT(ROUND(sp.costo_ultimo, 6) ORDER BY sp.es_preferido DESC, sp.fecha_actualizacion DESC, sp.id_sku_proveedor DESC), ',', 1) costo_ultimo_proveedor,
                    SUBSTRING_INDEX(GROUP_CONCAT(prv.proveedor ORDER BY sp.es_preferido DESC, sp.fecha_actualizacion DESC, sp.id_sku_proveedor DESC), ',', 1) proveedor_preferido
                FROM erp_catalogo_sku_proveedores sp
                LEFT JOIN erp_proveedores prv ON prv.id_proveedor=sp.id_proveedor
                WHERE sp.estatus='activo' AND COALESCE(sp.costo_ultimo,0) > 0
                GROUP BY sp.id_sku
            ) prov ON prov.id_sku=s.id_sku
            WHERE s.estatus <> 'fusionado'
              AND (:termino='' OR s.sku LIKE :buscar OR s.nombre LIKE :buscar OR p.nombre LIKE :buscar)
            ORDER BY " . $ordenExacto . "COALESCE(inv.valor_total, 0) DESC, COALESCE(inv.disponible_total, 0) DESC, s.sku ASC
            LIMIT " . $limite;
        $stmt = $db->prepare($sql);
        $params = array(":termino" => $termino, ":buscar" => "%" . $termino . "%");
        if ($priorizarExacto) {
            $params[":exacto"] = $termino;
        }
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function detallesSnapshot($db, $idSnapshot) {
        $stmt = $db->prepare("SELECT id_snapshot_detalle, id_sku, sku, producto, costo_real_sin_impuesto,
                precio_escenario_sin_impuesto, margen_bruto_pct, utilidad_estimada,
                precio_minimo_rentable, riesgo_clave
            FROM erp_rentabilidad_snapshot_detalle
            WHERE id_snapshot=:snapshot
            ORDER BY id_snapshot_detalle");
        $stmt->execute(array(":snapshot" => intval($idSnapshot)));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function calcularConsistenciaPresentacion($fila) {
        $factorOrigen = max(1, floatval($fila["factor_origen"]));
        $factorResultado = max(1, floatval($fila["factor_resultado"]));
        $costoOrigenUnitario = $fila["costo_inv_origen"] === null
            ? (floatval($fila["costo_referencia_origen"]) / $factorOrigen)
            : floatval($fila["costo_inv_origen"]);
        $costoResultadoActual = $fila["costo_inv_resultado"] === null
            ? floatval($fila["costo_referencia_resultado"])
            : (floatval($fila["costo_inv_resultado"]) * $factorResultado);
        $cantidadOrigen = floatval($fila["cantidad_origen"]);
        $unidadesResultado = max(1, floatval($fila["unidades_resultado"]));
        $merma = max(0, floatval($fila["merma_porcentaje"]));
        $costoEsperado = $unidadesResultado > 0
            ? ($costoOrigenUnitario * $cantidadOrigen * (1 + ($merma / 100))) / $unidadesResultado
            : 0;
        $diferencia = $costoResultadoActual - $costoEsperado;
        $diferenciaPct = $costoEsperado > 0 ? ($diferencia / $costoEsperado) * 100 : null;
        $estatus = "ok";
        if ($costoOrigenUnitario <= 0 || $costoResultadoActual <= 0 || $costoEsperado <= 0) {
            $estatus = "incompleto";
        } elseif (abs($diferencia) > 0.05 && abs($diferenciaPct) > 5) {
            $estatus = "diferencia";
        }

        return array(
            "id_transformacion" => intval($fila["id_sku_transformacion"]),
            "tipo_transformacion" => $fila["tipo_transformacion"],
            "sku_origen" => $fila["sku_origen"],
            "sku_resultado" => $fila["sku_resultado"],
            "nombre_resultado" => $fila["nombre_resultado"],
            "cantidad_origen" => round($cantidadOrigen, 6),
            "unidades_resultado" => round($unidadesResultado, 6),
            "merma_porcentaje" => round($merma, 4),
            "costo_origen_unitario" => round($costoOrigenUnitario, 6),
            "costo_resultado_actual" => round($costoResultadoActual, 6),
            "costo_resultado_esperado" => round($costoEsperado, 6),
            "diferencia" => round($diferencia, 6),
            "diferencia_pct" => $diferenciaPct === null ? null : round($diferenciaPct, 2),
            "cantidad_resultado" => round(floatval($fila["cantidad_resultado"]), 4),
            "estatus_consistencia" => $estatus,
            "origen_costo_base" => $fila["costo_inv_origen"] === null ? "catalogo_referencia" : "inventario_promedio",
            "origen_costo_resultado" => $fila["costo_inv_resultado"] === null ? "catalogo_referencia" : "inventario_promedio"
        );
    }

    private function itemActualSnapshot($sku, $snapshot) {
        $respuesta = $this->analizarSkus(array(
            "q" => $sku,
            "canal" => $snapshot["canal"],
            "descuento_pct" => $snapshot["descuento_pct"],
            "gasto_pct" => $snapshot["gasto_operativo_pct"],
            "comision_pct" => $snapshot["comision_pct"],
            "margen_objetivo_pct" => $snapshot["margen_objetivo_pct"]
        ));
        if (!empty($respuesta["error"]) || empty($respuesta["depurar"]["items"])) {
            return null;
        }
        foreach ($respuesta["depurar"]["items"] as $item) {
            if ($item["sku"] === $sku) {
                return $item;
            }
        }
        return null;
    }

    private function compararDetalleSnapshot($detalle, $actual) {
        $diff = array();
        $campos = array(
            "costo_real_sin_impuesto" => "costo_real_sin_impuesto",
            "precio_escenario_sin_impuesto" => "precio_escenario_sin_impuesto",
            "margen_bruto_pct" => "margen_bruto_pct",
            "utilidad_estimada" => "utilidad_estimada",
            "precio_minimo_rentable" => "precio_minimo_rentable"
        );
        foreach ($campos as $campoDetalle => $campoActual) {
            $antes = $detalle[$campoDetalle] === null ? null : floatval($detalle[$campoDetalle]);
            $ahora = $actual[$campoActual] === null ? null : floatval($actual[$campoActual]);
            if ($antes === null && $ahora === null) {
                continue;
            }
            $tolerancia = in_array($campoDetalle, array("margen_bruto_pct"), true) ? 0.05 : 0.01;
            if ($antes === null || $ahora === null || abs($antes - $ahora) > $tolerancia) {
                $diff[] = array(
                    "campo" => $campoDetalle,
                    "snapshot" => $antes,
                    "actual" => $ahora
                );
            }
        }
        if ($detalle["riesgo_clave"] !== $actual["riesgo_clave"]) {
            $diff[] = array(
                "campo" => "riesgo_clave",
                "snapshot" => $detalle["riesgo_clave"],
                "actual" => $actual["riesgo_clave"]
            );
        }
        return $diff;
    }

    private function calcularItem($fila, $canal, $descuentoPct, $gastoPct, $comisionPct, $margenObjetivoPct) {
        $precioVenta = floatval($fila["precio_venta"]);
        $iva = $fila["iva_porcentaje"] === null ? null : floatval($fila["iva_porcentaje"]);
        $ieps = $fila["ieps_porcentaje"] === null ? null : floatval($fila["ieps_porcentaje"]);
        $incluye = $fila["incluye_impuestos"] === null ? null : intval($fila["incluye_impuestos"]);
        $tasa = max(0, ($iva === null ? 0 : $iva) + ($ieps === null ? 0 : $ieps)) / 100;
        $precioSinImpuesto = ($incluye === 1 && $tasa > 0) ? $precioVenta / (1 + $tasa) : $precioVenta;
        $precioEscenario = $precioSinImpuesto * (1 - ($descuentoPct / 100));

        $factorUnidad = max(1, floatval(isset($fila["factor_unidad_base"]) ? $fila["factor_unidad_base"] : 1));
        $costoInventarioUnitario = $fila["costo_promedio_inventario"] === null ? 0 : floatval($fila["costo_promedio_inventario"]);
        $costoInventario = $costoInventarioUnitario > 0 ? $costoInventarioUnitario * $factorUnidad : 0;
        $costoReferencia = floatval($fila["costo_referencia"]);
        $costoReal = $costoInventario > 0 ? $costoInventario : $costoReferencia;
        $origenCosto = $costoInventario > 0 ? "inventario_promedio" : ($costoReferencia > 0 ? "catalogo_referencia" : "sin_costo");

        $margenBrutoPct = $precioEscenario > 0 ? (($precioEscenario - $costoReal) / $precioEscenario) * 100 : null;
        $utilidadBruta = $precioEscenario - $costoReal;
        $gastosImporte = $precioEscenario * (($gastoPct + $comisionPct) / 100);
        $utilidadEstimada = $utilidadBruta - $gastosImporte;
        $utilidadEstimadaPct = $precioEscenario > 0 ? ($utilidadEstimada / $precioEscenario) * 100 : null;
        $denominador = 1 - (($gastoPct + $comisionPct + $margenObjetivoPct) / 100);
        $precioMinimo = $denominador > 0 ? $costoReal / $denominador : null;

        $hallazgosDetalle = array();
        if ($costoReal <= 0) { $hallazgosDetalle[] = $this->hallazgo("COST-H101", "sin_costo", "warning", "SKU sin costo real calculable"); }
        if ($precioVenta <= 0) { $hallazgosDetalle[] = $this->hallazgo("COST-H102", "sin_precio", "warning", "SKU sin precio general activo"); }
        if ($iva === null || $ieps === null || $incluye === null) { $hallazgosDetalle[] = $this->hallazgo("COST-H103", "fiscal_incompleto", "warning", "Impuestos incompletos para calcular precio sin impuestos"); }
        if ($precioEscenario > 0 && $utilidadEstimada < 0) { $hallazgosDetalle[] = $this->hallazgo("COST-H104", "perdida_estimada", "danger", "El escenario deja utilidad estimada negativa"); }
        if ($margenBrutoPct !== null && $margenBrutoPct < 15) { $hallazgosDetalle[] = $this->hallazgo("COST-H105", "margen_bajo", "warning", "Margen bruto menor a 15%"); }
        if (floatval($fila["cantidad_total"]) > 0 && $costoInventario <= 0) { $hallazgosDetalle[] = $this->hallazgo("COST-H106", "stock_sin_costo_promedio", "warning", "Hay stock con costo promedio de inventario en cero"); }
        $hallazgos = array_map(function ($item) { return $item["clave"]; }, $hallazgosDetalle);
        $riesgo = $this->riesgo($hallazgos, $margenBrutoPct, $utilidadEstimada);

        return array(
            "id_sku" => intval($fila["id_sku"]),
            "sku" => $fila["sku"],
            "producto" => $fila["producto"],
            "canal" => $canal,
            "costo_real_sin_impuesto" => round($costoReal, 6),
            "origen_costo" => $origenCosto,
            "precio_venta_sin_impuesto" => round($precioSinImpuesto, 6),
            "precio_escenario_sin_impuesto" => round($precioEscenario, 6),
            "margen_bruto_pct" => $margenBrutoPct === null ? null : round($margenBrutoPct, 2),
            "utilidad_bruta" => round($utilidadBruta, 6),
            "gastos_estimados" => round($gastosImporte, 6),
            "utilidad_estimada" => round($utilidadEstimada, 6),
            "utilidad_estimada_pct" => $utilidadEstimadaPct === null ? null : round($utilidadEstimadaPct, 2),
            "precio_minimo_rentable" => $precioMinimo === null ? null : round($precioMinimo, 6),
            "inventario" => array(
                "cantidad_total" => round(floatval($fila["cantidad_total"]), 4),
                "disponible_total" => round(floatval($fila["disponible_total"]), 4),
                "apartada_total" => round(floatval($fila["apartada_total"]), 4),
                "valor_total" => round(floatval($fila["valor_total"]), 6),
                "costo_promedio" => round($costoInventario, 6),
                "costo_promedio_unitario_inventario" => round($costoInventarioUnitario, 6),
                "factor_unidad_base" => round($factorUnidad, 6)
            ),
            "compras" => array(
                "ultimo_costo" => $fila["ultimo_costo_compra"] === null ? null : round(floatval($fila["ultimo_costo_compra"]), 6),
                "fecha_ultima" => $fila["fecha_ultima_compra"],
                "costo_promedio" => $fila["costo_promedio_compras"] === null ? null : round(floatval($fila["costo_promedio_compras"]), 6)
            ),
            "xml" => array(
                "ultimo_costo" => $fila["ultimo_costo_xml"] === null ? null : round(floatval($fila["ultimo_costo_xml"]), 6),
                "fecha_ultimo" => $fila["fecha_ultimo_xml"]
            ),
            "proveedor" => array(
                "costo_ultimo" => $fila["costo_ultimo_proveedor"] === null ? null : round(floatval($fila["costo_ultimo_proveedor"]), 6),
                "proveedor" => $fila["proveedor_preferido"]
            ),
            "fiscal" => array(
                "iva_porcentaje" => $iva,
                "ieps_porcentaje" => $ieps,
                "incluye_impuestos" => $incluye
            ),
            "hallazgos" => $hallazgos,
            "hallazgos_detalle" => $hallazgosDetalle,
            "riesgo_clave" => $riesgo["clave"],
            "riesgo_texto" => $riesgo["texto"],
            "riesgo_tipo" => $riesgo["tipo"],
            "recomendacion" => $this->recomendacion($riesgo["clave"], $precioMinimo, $precioEscenario)
        );
    }

    private function resumen($items) {
        $resumen = array("skus" => count($items), "perdida" => 0, "margen_bajo" => 0, "sin_costo" => 0, "sin_precio" => 0, "valor_inventario" => 0);
        foreach ($items as $item) {
            if (in_array("perdida_estimada", $item["hallazgos"], true)) { $resumen["perdida"]++; }
            if (in_array("margen_bajo", $item["hallazgos"], true)) { $resumen["margen_bajo"]++; }
            if (in_array("sin_costo", $item["hallazgos"], true)) { $resumen["sin_costo"]++; }
            if (in_array("sin_precio", $item["hallazgos"], true)) { $resumen["sin_precio"]++; }
            $resumen["valor_inventario"] += floatval($item["inventario"]["valor_total"]);
        }
        $resumen["valor_inventario"] = round($resumen["valor_inventario"], 6);
        return $resumen;
    }

    private function tieneFiltrosOperacion($filtros) {
        foreach (array("accion", "stock", "origen_costo", "proveedor") as $clave) {
            if (trim(isset($filtros[$clave]) ? strval($filtros[$clave]) : "") !== "") {
                return true;
            }
        }
        return false;
    }

    private function cumpleFiltrosOperacion($item, $filtros) {
        $accion = trim(isset($filtros["accion"]) ? strval($filtros["accion"]) : "");
        $stock = trim(isset($filtros["stock"]) ? strval($filtros["stock"]) : "");
        $origenCosto = trim(isset($filtros["origen_costo"]) ? strval($filtros["origen_costo"]) : "");
        $proveedor = trim(isset($filtros["proveedor"]) ? strval($filtros["proveedor"]) : "");

        if ($stock !== "") {
            $disponible = floatval($item["inventario"]["disponible_total"]);
            $valorInventario = floatval($item["inventario"]["valor_total"]);
            if ($stock === "con_stock" && $disponible <= 0) {
                return false;
            }
            if ($stock === "sin_stock" && $disponible > 0) {
                return false;
            }
            if ($stock === "con_valor" && $valorInventario <= 0) {
                return false;
            }
        }

        if ($origenCosto !== "" && $item["origen_costo"] !== $origenCosto) {
            return false;
        }

        if ($proveedor !== "") {
            $proveedorItem = isset($item["proveedor"]["proveedor"]) ? trim(strval($item["proveedor"]["proveedor"])) : "";
            if ($proveedorItem === "" || stripos($proveedorItem, $proveedor) === false) {
                return false;
            }
        }

        if ($accion === "") {
            return true;
        }

        $hallazgos = isset($item["hallazgos"]) ? $item["hallazgos"] : array();
        $deltaPrecio = $item["precio_minimo_rentable"] === null
            ? null
            : floatval($item["precio_minimo_rentable"]) - floatval($item["precio_escenario_sin_impuesto"]);

        if ($accion === "perdidas") {
            return $item["riesgo_clave"] === "perdida";
        }
        if ($accion === "subir_precio") {
            return $deltaPrecio !== null && $deltaPrecio > 0.01;
        }
        if ($accion === "completar_costo") {
            return in_array("sin_costo", $hallazgos, true);
        }
        if ($accion === "completar_precio") {
            return in_array("sin_precio", $hallazgos, true);
        }
        if ($accion === "completar_fiscal") {
            return in_array("fiscal_incompleto", $hallazgos, true);
        }
        if ($accion === "oportunidad_stock") {
            return floatval($item["inventario"]["disponible_total"]) > 0 && $item["riesgo_clave"] === "rentable";
        }

        return true;
    }

    private function resumenRecomendacionItem($item) {
        return array(
            "sku" => $item["sku"],
            "producto" => $item["producto"],
            "riesgo" => $item["riesgo_clave"],
            "riesgo_texto" => $item["riesgo_texto"],
            "costo" => $item["costo_real_sin_impuesto"],
            "precio" => $item["precio_escenario_sin_impuesto"],
            "margen" => $item["margen_bruto_pct"],
            "utilidad" => $item["utilidad_estimada"],
            "precio_minimo" => $item["precio_minimo_rentable"],
            "hallazgos" => $item["hallazgos"],
            "recomendacion" => $item["recomendacion"]
        );
    }

    private function hallazgo($id, $clave, $tipo, $mensaje) {
        return array("id" => $id, "clave" => $clave, "tipo" => $tipo, "mensaje" => $mensaje);
    }

    private function riesgo($hallazgos, $margenBrutoPct, $utilidadEstimada) {
        if (in_array("sin_costo", $hallazgos, true) || in_array("sin_precio", $hallazgos, true)) {
            return array("clave" => "incompleto", "texto" => "Datos incompletos", "tipo" => "warning");
        }
        if (in_array("perdida_estimada", $hallazgos, true)) {
            return array("clave" => "perdida", "texto" => "Riesgo de perdida", "tipo" => "danger");
        }
        if ($margenBrutoPct !== null && $margenBrutoPct < 15) {
            return array("clave" => "margen_bajo", "texto" => "Margen bajo", "tipo" => "warning");
        }
        if ($utilidadEstimada > 0) {
            return array("clave" => "rentable", "texto" => "Rentable estimado", "tipo" => "success");
        }
        return array("clave" => "revision", "texto" => "Revisar", "tipo" => "info");
    }

    private function recomendacion($riesgo, $precioMinimo, $precioEscenario) {
        if ($riesgo === "incompleto") {
            return "Completar costo, precio e impuestos antes de cerrar precio comercial.";
        }
        if ($riesgo === "perdida") {
            return $precioMinimo ? "Subir precio al menos a $" . number_format($precioMinimo, 2, ".", ",") . " sin impuestos o reducir descuento/gastos." : "Revisar estructura de gastos; el escenario no tiene denominador rentable.";
        }
        if ($riesgo === "margen_bajo") {
            return $precioMinimo && $precioMinimo > $precioEscenario ? "Precio cercano al minimo; validar mayoreo/alianza antes de vender." : "Mantener en vigilancia por margen bajo.";
        }
        return "Precio viable para el escenario; validar contra estrategia comercial antes de publicar en Ventas.";
    }

    private function defaultsEscenario($canal) {
        $mapa = array(
            "menudeo" => array("descuento_pct" => 0, "gasto_pct" => 18, "comision_pct" => 0, "margen_objetivo_pct" => 25),
            "mayoreo" => array("descuento_pct" => 12, "gasto_pct" => 10, "comision_pct" => 0, "margen_objetivo_pct" => 18),
            "alianza" => array("descuento_pct" => 8, "gasto_pct" => 12, "comision_pct" => 8, "margen_objetivo_pct" => 20)
        );
        return $mapa[$canal];
    }

    private function escenariosSemilla() {
        return array(
            array("clave" => "menudeo_base", "nombre" => "Menudeo base", "canal" => "menudeo", "descuento_pct" => 0, "gasto_pct" => 18, "comision_pct" => 0, "margen_objetivo_pct" => 25, "descripcion" => "Escenario operativo inicial para venta de mostrador o publico general."),
            array("clave" => "mayoreo_base", "nombre" => "Mayoreo base", "canal" => "mayoreo", "descuento_pct" => 12, "gasto_pct" => 10, "comision_pct" => 0, "margen_objetivo_pct" => 18, "descripcion" => "Escenario operativo inicial para volumen antes de definir listas formales."),
            array("clave" => "alianza_base", "nombre" => "Alianza base", "canal" => "alianza", "descuento_pct" => 8, "gasto_pct" => 12, "comision_pct" => 8, "margen_objetivo_pct" => 20, "descripcion" => "Escenario operativo inicial para alianzas con comision o costo comercial adicional.")
        );
    }

    private function porcentaje($datos, $campo, $default) {
        if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === "") {
            return floatval($default);
        }
        return max(0, min(95, floatval($datos[$campo])));
    }

    private function opcion($valor, $permitidos, $default) {
        return in_array($valor, $permitidos, true) ? $valor : $default;
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}
