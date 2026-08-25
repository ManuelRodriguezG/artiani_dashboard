<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-22
 * Proposito: auditar el contrato read-only de RentabilidadErp::resolverCostoVigenteSku para SKUs normales y derivados.
 * Impacto: protege Listas de precios para consumir costo vigente trazable sin escribir precios ni modificar ventas.
 * Contrato: solo lectura; no escribe BD, no crea fixtures, no actualiza Catalogo, Listas ni Ventas.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

class UatRentabilidadResolverCostoContratoReader extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$lector = new UatRentabilidadResolverCostoContratoReader();
$db = $lector->db();
$modelo = new RentabilidadErp();
$fallas = array();
$casos = array();

$camposContrato = array(
    "id_sku", "sku", "tipo_resolucion", "costo", "moneda", "fuente", "confianza", "formula",
    "id_sku_origen", "sku_origen", "factor_usado", "merma_porcentaje", "componentes",
    "advertencias", "bloqueos", "siguiente_paso"
);

registrarCaso($casos, $fallas, "sku_normal_proveedor", buscarSkuProveedor($db), array(), function ($r) {
    return $r["fuente"] === "proveedor_relacion" && floatval($r["costo"]) > 0;
}, "SKU normal debe usar proveedor_relacion cuando hay costo proveedor vigente", $camposContrato, $modelo);

registrarCaso($casos, $fallas, "presentacion_derivada", buscarSkuPorCodigo($db, "TP-40372-500GR", buscarSkuPresentacion($db)), array(), function ($r) {
    return $r["fuente"] === "derivado_presentacion" && floatval($r["costo"]) > 0 && !empty($r["sku_origen"]);
}, "Presentacion debe derivar costo desde SKU origen con formula", $camposContrato, $modelo);

registrarCaso($casos, $fallas, "granel_unidad_base", buscarSkuPorCodigo($db, "TP-40372", buscarSkuConFactor($db)), array("tipo" => "granel"), function ($r) {
    return $r["tipo_resolucion"] === "granel" && floatval($r["costo"]) > 0 && $r["formula"] === "costo_sku / factor_unidad_base";
}, "Granel debe devolver costo por unidad base", $camposContrato, $modelo);

registrarCaso($casos, $fallas, "apertura_sin_confirmar", buscarSkuPorCodigo($db, "TP-40372", buscarSkuCualquiera($db)), array("tipo" => "apertura_empaque"), function ($r) {
    return $r["fuente"] === "sin_costo" && contieneBloqueo($r, "COST-DER-007") && $r["siguiente_paso"] === "Resolver en Almacen/Tienda.";
}, "Apertura sin confirmacion debe quedar pendiente de Almacen/Tienda", $camposContrato, $modelo);

registrarCaso($casos, $fallas, "apertura_receta_catalogo", buscarSkuAperturaRecetaCatalogo($db), array("tipo" => "apertura_empaque"), function ($r) {
    return $r["fuente"] === "derivado_apertura_receta" && floatval($r["costo"]) > 0 && !empty($r["sku_origen"]) && in_array($r["formula"], array("costo_origen / (factor_conversion * (1 - merma))", "costo_origen / (factor_efectivo_apertura * (1 - merma))"), true);
}, "Apertura con receta activa en Catalogo debe calcular costo teorico antes de apertura fisica", $camposContrato, $modelo);

registrarCaso($casos, $fallas, "apertura_granel_factor_inferido", buscarSkuPorCodigo($db, "NUAN-ARMG25K-GRANEL", null), array("tipo" => "apertura_empaque"), function ($r) {
    return $r["fuente"] === "derivado_apertura_receta" && floatval($r["costo"]) > 0 && floatval($r["factor_usado"]) > 1 && contieneAdvertencia($r, "COST-DER-013") && $r["formula"] === "costo_origen / (factor_efectivo_apertura * (1 - merma))";
}, "Apertura granel con receta factor 1 debe inferir cantidad util del SKU granel/origen", $camposContrato, $modelo);

registrarCaso($casos, $fallas, "apertura_confirmada", buscarSkuAperturaConfirmadaConCosto($db), array("tipo" => "apertura_empaque"), function ($r) {
    return $r["fuente"] === "apertura_confirmada" && floatval($r["costo"]) > 0;
}, "Apertura confirmada con costo real debe usar fuente apertura_confirmada", $camposContrato, $modelo);

registrarCaso($casos, $fallas, "apertura_confirmada_sin_costo_con_receta", buscarSkuAperturaConfirmada($db), array("tipo" => "apertura_empaque"), function ($r) {
    return $r["fuente"] === "derivado_apertura_receta" && floatval($r["costo"]) > 0 && contieneAdvertencia($r, "COST-DER-012") && empty($r["bloqueos"]);
}, "Apertura confirmada sin costo real debe usar receta teorica y advertir correccion operativa", $camposContrato, $modelo);

registrarCaso($casos, $fallas, "paquete_completo", buscarPaqueteCompleto($db, $modelo), array("tipo" => "paquete_combo"), function ($r) {
    return in_array($r["fuente"], array("paquete_componentes", "paquete_rango"), true) && empty($r["bloqueos"]) && floatval($r["costo"]) > 0;
}, "Paquete completo debe sumar componentes sin bloqueos", $camposContrato, $modelo);

registrarCaso($casos, $fallas, "paquete_componente_sin_costo", buscarSkuPorCodigo($db, "PER-05-01", buscarPaqueteConBloqueo($db, $modelo)), array("tipo" => "paquete_combo"), function ($r) {
    return in_array($r["fuente"], array("paquete_componentes", "paquete_rango"), true) && contieneBloqueo($r, "COST-DER-005") && $r["siguiente_paso"] === "Completar receta en Catalogo.";
}, "Paquete con componente sin costo debe devolver bloqueo accionable", $camposContrato, $modelo);

registrarCaso($casos, $fallas, "derivado_sin_factor", buscarSkuDerivadoSinFactor($db), array("tipo" => "presentacion"), function ($r) {
    return $r["fuente"] === "sin_costo" && contieneBloqueo($r, "COST-DER-002") && $r["siguiente_paso"] === "Completar factor de conversion en Catalogo.";
}, "Derivado sin factor debe pedir completar factor en Catalogo", $camposContrato, $modelo);

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "rentabilidad_resolver_costo_vigente_contrato_readonly",
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_escribe_precios_listas" => true,
        "no_modifica_ventas_pasadas" => true
    ),
    "fallas" => $fallas,
    "casos" => $casos
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function registrarCaso(&$casos, &$fallas, $nombre, $sku, $contexto, $validador, $descripcion, $camposContrato, $modelo) {
    if (!$sku || intval($sku["id_sku"]) <= 0) {
        $casos[$nombre] = array("estado" => "skip_sin_muestra", "descripcion" => $descripcion);
        return;
    }
    $respuesta = $modelo->resolverCostoVigenteSku(intval($sku["id_sku"]), $contexto);
    $r = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $faltantes = camposFaltantes($r, $camposContrato);
    $okContrato = empty($faltantes);
    $okRegla = $okContrato && $validador($r);
    $casos[$nombre] = array(
        "estado" => $okRegla ? "ok" : "fail",
        "descripcion" => $descripcion,
        "id_sku" => intval($sku["id_sku"]),
        "sku" => isset($r["sku"]) ? $r["sku"] : $sku["sku"],
        "tipo_resolucion" => isset($r["tipo_resolucion"]) ? $r["tipo_resolucion"] : null,
        "costo" => isset($r["costo"]) ? $r["costo"] : null,
        "fuente" => isset($r["fuente"]) ? $r["fuente"] : null,
        "confianza" => isset($r["confianza"]) ? $r["confianza"] : null,
        "formula" => isset($r["formula"]) ? $r["formula"] : null,
        "sku_origen" => isset($r["sku_origen"]) ? $r["sku_origen"] : null,
        "bloqueos" => isset($r["bloqueos"]) ? $r["bloqueos"] : array(),
        "siguiente_paso" => isset($r["siguiente_paso"]) ? $r["siguiente_paso"] : null,
        "faltantes_contrato" => $faltantes
    );
    if (!$okRegla) {
        $fallas[] = array("id" => "COST-CONTRACT-" . strtoupper($nombre), "mensaje" => $descripcion, "caso" => $casos[$nombre]);
    }
}

function camposFaltantes($r, $campos) {
    $faltantes = array();
    foreach ($campos as $campo) {
        if (!array_key_exists($campo, $r)) {
            $faltantes[] = $campo;
        }
    }
    return $faltantes;
}

function contieneBloqueo($r, $id) {
    $bloqueos = isset($r["bloqueos"]) && is_array($r["bloqueos"]) ? $r["bloqueos"] : array();
    foreach ($bloqueos as $bloqueo) {
        if (isset($bloqueo["id"]) && $bloqueo["id"] === $id) {
            return true;
        }
    }
    return false;
}

function contieneAdvertencia($r, $id) {
    $advertencias = isset($r["advertencias"]) && is_array($r["advertencias"]) ? $r["advertencias"] : array();
    foreach ($advertencias as $advertencia) {
        if (isset($advertencia["id"]) && $advertencia["id"] === $id) {
            return true;
        }
    }
    return false;
}

function buscarSkuPorCodigo($db, $codigo, $fallback) {
    $stmt = $db->prepare("SELECT id_sku, sku FROM erp_catalogo_skus WHERE sku=:sku LIMIT 1");
    $stmt->execute(array(":sku" => $codigo));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : $fallback;
}

function buscarSkuProveedor($db) {
    if (tablaExisteUat($db, "erp_proveedores_sku_costos")) {
        $sql = "SELECT s.id_sku, s.sku
            FROM erp_catalogo_skus s
            INNER JOIN erp_proveedores_sku_costos c ON c.id_sku=s.id_sku
            WHERE s.estatus='activo' AND c.estatus='vigente' AND COALESCE(c.costo,0)>0
            ORDER BY c.fecha_actualizacion DESC, c.id_costo_proveedor_sku DESC
            LIMIT 1";
        $row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }
    if (tablaExisteUat($db, "erp_catalogo_sku_proveedores")) {
        $sql = "SELECT s.id_sku, s.sku
            FROM erp_catalogo_skus s
            INNER JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku
            WHERE s.estatus='activo' AND sp.estatus='activo' AND COALESCE(sp.costo_ultimo,0)>0
            ORDER BY sp.es_preferido DESC, sp.fecha_actualizacion DESC, sp.id_sku_proveedor DESC
            LIMIT 1";
        return $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

function buscarSkuPresentacion($db) {
    if (!tablaExisteUat($db, "erp_catalogo_sku_presentaciones")) {
        return null;
    }
    $sql = "SELECT s.id_sku, s.sku
        FROM erp_catalogo_sku_presentaciones p
        INNER JOIN erp_catalogo_skus s ON s.id_sku=p.id_sku_presentacion
        WHERE p.estatus='activa' AND COALESCE(p.factor_salida_base,0)>0
        ORDER BY p.id_sku_presentacion_regla DESC
        LIMIT 1";
    return $db->query($sql)->fetch(PDO::FETCH_ASSOC);
}

function buscarSkuConFactor($db) {
    $sql = "SELECT id_sku, sku FROM erp_catalogo_skus WHERE estatus='activo' AND COALESCE(factor_unidad_base,0)>1 ORDER BY id_sku ASC LIMIT 1";
    return $db->query($sql)->fetch(PDO::FETCH_ASSOC);
}

function buscarSkuCualquiera($db) {
    return $db->query("SELECT id_sku, sku FROM erp_catalogo_skus WHERE estatus='activo' ORDER BY id_sku ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

function buscarSkuAperturaConfirmadaConCosto($db) {
    if (!tablaExisteUat($db, "erp_almacen_aperturas_empaque") || !tablaExisteUat($db, "erp_almacen_apertura_resultados")) {
        return null;
    }
    $sql = "SELECT s.id_sku, s.sku
        FROM erp_almacen_apertura_resultados r
        INNER JOIN erp_almacen_aperturas_empaque a ON a.id_apertura_empaque=r.id_apertura_empaque
        INNER JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku_resultado
        WHERE a.estatus='confirmada' AND COALESCE(r.costo_unitario,0)>0 AND COALESCE(r.costo_total,0)>0
        ORDER BY a.fecha_apertura DESC, a.id_apertura_empaque DESC
        LIMIT 1";
    return $db->query($sql)->fetch(PDO::FETCH_ASSOC);
}

function buscarSkuAperturaRecetaCatalogo($db) {
    if (!tablaExisteUat($db, "erp_catalogo_sku_aperturas_empaque")) {
        return null;
    }
    $sql = "SELECT s.id_sku, s.sku
        FROM erp_catalogo_sku_aperturas_empaque ae
        INNER JOIN erp_catalogo_skus s ON s.id_sku=ae.id_sku_destino
        WHERE ae.estatus='activo' AND COALESCE(ae.factor_conversion,0)>0
        ORDER BY ae.id_apertura_empaque DESC
        LIMIT 1";
    return $db->query($sql)->fetch(PDO::FETCH_ASSOC);
}

function buscarSkuAperturaConfirmada($db) {
    if (!tablaExisteUat($db, "erp_almacen_aperturas_empaque") || !tablaExisteUat($db, "erp_almacen_apertura_resultados")) {
        return null;
    }
    $sql = "SELECT s.id_sku, s.sku
        FROM erp_almacen_apertura_resultados r
        INNER JOIN erp_almacen_aperturas_empaque a ON a.id_apertura_empaque=r.id_apertura_empaque
        INNER JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku_resultado
        WHERE a.estatus='confirmada'
        ORDER BY a.fecha_apertura DESC, a.id_apertura_empaque DESC
        LIMIT 1";
    return $db->query($sql)->fetch(PDO::FETCH_ASSOC);
}

function buscarPaqueteCompleto($db, $modelo) {
    if (!tablaExisteUat($db, "erp_catalogo_sku_paquetes")) {
        return null;
    }
    $rows = $db->query("SELECT p.id_sku_paquete id_sku, s.sku FROM erp_catalogo_sku_paquetes p INNER JOIN erp_catalogo_skus s ON s.id_sku=p.id_sku_paquete WHERE p.estatus='activo' LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $resp = $modelo->resolverCostoVigenteSku(intval($row["id_sku"]), array("tipo" => "paquete_combo"));
        $r = isset($resp["depurar"]) ? $resp["depurar"] : array();
        if (floatval(isset($r["costo"]) ? $r["costo"] : 0) > 0 && empty($r["bloqueos"])) {
            return $row;
        }
    }
    return null;
}

function buscarPaqueteConBloqueo($db, $modelo) {
    if (!tablaExisteUat($db, "erp_catalogo_sku_paquetes")) {
        return null;
    }
    $rows = $db->query("SELECT p.id_sku_paquete id_sku, s.sku FROM erp_catalogo_sku_paquetes p INNER JOIN erp_catalogo_skus s ON s.id_sku=p.id_sku_paquete WHERE p.estatus='activo' LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $resp = $modelo->resolverCostoVigenteSku(intval($row["id_sku"]), array("tipo" => "paquete_combo"));
        $r = isset($resp["depurar"]) ? $resp["depurar"] : array();
        if (contieneBloqueo($r, "COST-DER-005")) {
            return $row;
        }
    }
    return null;
}

function buscarSkuDerivadoSinFactor($db) {
    if (tablaExisteUat($db, "erp_catalogo_sku_presentaciones")) {
        $sql = "SELECT s.id_sku, s.sku
            FROM erp_catalogo_sku_presentaciones p
            INNER JOIN erp_catalogo_skus s ON s.id_sku=p.id_sku_presentacion
            WHERE p.estatus='activa' AND COALESCE(p.factor_salida_base,0)<=0
            LIMIT 1";
        $row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }
    if (tablaExisteUat($db, "erp_catalogo_sku_transformaciones")) {
        $sql = "SELECT s.id_sku, s.sku
            FROM erp_catalogo_sku_transformaciones t
            INNER JOIN erp_catalogo_skus s ON s.id_sku=t.id_sku_resultado
            WHERE t.estatus='activa' AND (COALESCE(t.cantidad_origen,0)<=0 OR COALESCE(t.unidades_resultado,0)<=0)
            LIMIT 1";
        return $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }
    return null;
}

function tablaExisteUat($db, $tabla) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}
