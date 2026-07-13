<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: validar estaticamente el DDL propuesto de Resurtido antes de autorizar su ejecucion.
 * Impacto: reduce riesgo de aplicar un SQL incompleto o destructivo para Almacen > Resurtido.
 * Contrato: read-only sobre archivos; no conecta BD, no ejecuta DDL, no escribe datos y no mueve inventario.
 */

$raiz = realpath(__DIR__ . "/../..");
$relativoSql = "docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql";
$pathSql = realpath($raiz . DIRECTORY_SEPARATOR . $relativoSql);
$bloqueos = array();
$avisos = array();

if (!$pathSql || strpos($pathSql, $raiz) !== 0 || !is_file($pathSql)) {
    responder(array(
        "ok" => false,
        "modo" => "almacen_resurtido_sql_static",
        "read_only" => true,
        "bloqueos" => array("No se encontro SQL propuesto: " . $relativoSql)
    ), 1);
}

$sql = file_get_contents($pathSql);
$normalizado = normalizarSql($sql);
$tablas = extraerTablas($sql);
$esperadas = tablasEsperadasResurtido();

foreach ($esperadas as $tabla => $reglas) {
    if (!isset($tablas[$tabla])) {
        $bloqueos[] = "Falta tabla esperada: {$tabla}";
        continue;
    }
    foreach ($reglas["columnas"] as $columna) {
        if (!in_array($columna, $tablas[$tabla]["columnas"], true)) {
            $bloqueos[] = "Falta columna {$tabla}.{$columna}";
        }
    }
    foreach ($reglas["indices"] as $indice) {
        if (strpos($tablas[$tabla]["cuerpo_normalizado"], strtolower($indice)) === false) {
            $bloqueos[] = "Falta indice/constraint en {$tabla}: {$indice}";
        }
    }
}

$prohibidas = array(
    "/\\bdrop\\s+(table|database|schema)\\b/i" => "DDL destructivo DROP",
    "/\\btruncate\\s+table\\b/i" => "DDL destructivo TRUNCATE",
    "/\\bdelete\\s+from\\b/i" => "DML destructivo DELETE",
    "/\\bupdate\\s+erp_/i" => "DML UPDATE no esperado",
    "/\\binsert\\s+into\\s+erp_/i" => "DML INSERT no esperado"
);
foreach ($prohibidas as $patron => $mensaje) {
    if (preg_match($patron, $sql)) {
        $bloqueos[] = "SQL contiene operacion no permitida en propuesta: {$mensaje}";
    }
}

foreach (array(
    "REFERENCES erp_almacenes (id_almacen)",
    "REFERENCES erp_catalogo_skus (id_sku)",
    "REFERENCES erp_inventario_existencias (id_existencia_inventario)",
    "REFERENCES erp_inventario_unidades (id_inventario_unidad)",
    "REFERENCES erp_inventario_movimientos (id_movimiento_inventario)"
) as $referencia) {
    if (strpos($normalizado, strtolower($referencia)) === false) {
        $avisos[] = "No se encontro referencia esperada: {$referencia}";
    }
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "almacen_resurtido_sql_static",
    "read_only" => true,
    "archivo" => array(
        "relativo" => $relativoSql,
        "tamano_bytes" => filesize($pathSql),
        "tablas_detectadas" => count($tablas)
    ),
    "tablas" => resumenTablas($tablas, $esperadas),
    "guardrails" => array(
        "no_conecta_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_escribe_bd" => true,
        "no_mueve_kardex" => true,
        "no_toca_pos_ecommerce" => true
    ),
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "siguiente_paso" => empty($bloqueos)
        ? "DDL propuesto pasa validacion estatica; aun requiere respaldo externo y autorizacion textual antes de ejecutarse."
        : "Corregir SQL propuesto antes de cualquier autorizacion de DDL."
), empty($bloqueos) ? 0 : 1);

function extraerTablas($sql) {
    $tablas = array();
    if (!preg_match_all('/CREATE\\s+TABLE\\s+IF\\s+NOT\\s+EXISTS\\s+([a-zA-Z0-9_]+)\\s*\\((.*?)\\)\\s*;/is', $sql, $matches, PREG_SET_ORDER)) {
        return $tablas;
    }
    foreach ($matches as $match) {
        $tabla = $match[1];
        $cuerpo = $match[2];
        $columnas = array();
        foreach (preg_split('/\\R/', $cuerpo) as $linea) {
            $linea = trim($linea);
            if ($linea === "" || preg_match('/^(PRIMARY|UNIQUE|KEY|CONSTRAINT)\\b/i', $linea)) {
                continue;
            }
            if (preg_match('/^([a-zA-Z0-9_]+)\\s+/i', $linea, $columna)) {
                $columnas[] = $columna[1];
            }
        }
        $tablas[$tabla] = array(
            "columnas" => $columnas,
            "cuerpo_normalizado" => normalizarSql($cuerpo)
        );
    }
    return $tablas;
}

function tablasEsperadasResurtido() {
    return array(
        "erp_inventario_politicas_almacen_sku" => array(
            "columnas" => array("id_politica_almacen_sku", "id_almacen", "id_sku_erp", "stock_minimo", "stock_maximo", "punto_reorden", "cantidad_sugerida", "estatus"),
            "indices" => array("PRIMARY KEY (id_politica_almacen_sku)", "UNIQUE KEY uk_inv_politica_almacen_sku", "CONSTRAINT fk_inv_pol_almacen", "CONSTRAINT fk_inv_pol_sku")
        ),
        "erp_almacen_resurtidos" => array(
            "columnas" => array("id_resurtido_almacen", "folio", "tipo_documento", "id_almacen_solicitante", "id_almacen_origen", "id_almacen_transito", "estatus", "fecha_solicitud", "fecha_autorizacion", "fecha_preparacion", "fecha_envio", "fecha_recepcion", "fecha_cierre"),
            "indices" => array("PRIMARY KEY (id_resurtido_almacen)", "UNIQUE KEY uk_alm_resurtido_folio", "CONSTRAINT fk_res_alm_solicitante", "CONSTRAINT fk_res_alm_origen", "CONSTRAINT fk_res_alm_transito")
        ),
        "erp_almacen_resurtido_detalle" => array(
            "columnas" => array("id_resurtido_detalle", "id_resurtido_almacen", "id_sku_erp", "id_producto", "sku", "nombre_producto", "unidad_base", "cantidad_solicitada", "cantidad_autorizada", "cantidad_preparada", "cantidad_enviada", "cantidad_recibida", "cantidad_diferencia", "estatus"),
            "indices" => array("PRIMARY KEY (id_resurtido_detalle)", "CONSTRAINT fk_res_det_resurtido", "CONSTRAINT fk_res_det_sku")
        ),
        "erp_almacen_resurtido_preparacion" => array(
            "columnas" => array("id_resurtido_preparacion", "id_resurtido_almacen", "id_resurtido_detalle", "id_existencia_origen", "id_inventario_unidad", "id_almacen_origen", "ubicacion_origen_id", "id_sku_erp", "lote", "fecha_caducidad", "cantidad_preparada", "cantidad_unidad_antes", "cantidad_unidad_despues", "estado_fisico_unidad", "estatus"),
            "indices" => array("PRIMARY KEY (id_resurtido_preparacion)", "CONSTRAINT fk_res_prep_resurtido", "CONSTRAINT fk_res_prep_detalle", "CONSTRAINT fk_res_prep_existencia", "CONSTRAINT fk_res_prep_unidad")
        ),
        "erp_almacen_resurtido_envios" => array(
            "columnas" => array("id_resurtido_envio", "id_resurtido_almacen", "id_resurtido_preparacion", "id_movimiento_salida", "id_movimiento_transito_entrada", "id_existencia_transito", "id_inventario_unidad", "cantidad_enviada", "estatus"),
            "indices" => array("PRIMARY KEY (id_resurtido_envio)", "CONSTRAINT fk_res_env_resurtido", "CONSTRAINT fk_res_env_preparacion", "CONSTRAINT fk_res_env_mov_salida", "CONSTRAINT fk_res_env_mov_transito")
        ),
        "erp_almacen_resurtido_recepciones" => array(
            "columnas" => array("id_resurtido_recepcion", "id_resurtido_almacen", "id_resurtido_envio", "id_almacen_destino", "ubicacion_destino_id", "id_movimiento_transito_salida", "id_movimiento_entrada_destino", "id_existencia_destino", "id_inventario_unidad", "lote_recibido", "fecha_caducidad_recibida", "cantidad_recibida", "estatus"),
            "indices" => array("PRIMARY KEY (id_resurtido_recepcion)", "CONSTRAINT fk_res_rec_resurtido", "CONSTRAINT fk_res_rec_envio", "CONSTRAINT fk_res_rec_almacen", "CONSTRAINT fk_res_rec_mov_transito", "CONSTRAINT fk_res_rec_mov_entrada")
        ),
        "erp_almacen_resurtido_diferencias" => array(
            "columnas" => array("id_resurtido_diferencia", "id_resurtido_almacen", "id_resurtido_detalle", "id_resurtido_envio", "id_resurtido_recepcion", "tipo_diferencia", "severidad", "id_sku_erp", "id_inventario_unidad", "cantidad_esperada", "cantidad_recibida", "cantidad_diferencia", "lote_esperado", "lote_recibido", "fecha_caducidad_esperada", "fecha_caducidad_recibida", "estatus"),
            "indices" => array("PRIMARY KEY (id_resurtido_diferencia)", "CONSTRAINT fk_res_dif_resurtido", "CONSTRAINT fk_res_dif_detalle", "CONSTRAINT fk_res_dif_envio", "CONSTRAINT fk_res_dif_recepcion", "CONSTRAINT fk_res_dif_sku")
        )
    );
}

function resumenTablas($tablas, $esperadas) {
    $resumen = array();
    foreach ($esperadas as $tabla => $reglas) {
        $detectada = isset($tablas[$tabla]);
        $resumen[$tabla] = array(
            "detectada" => $detectada,
            "columnas_detectadas" => $detectada ? count($tablas[$tabla]["columnas"]) : 0,
            "columnas_esperadas_minimas" => count($reglas["columnas"])
        );
    }
    return $resumen;
}

function normalizarSql($texto) {
    return strtolower(preg_replace('/\\s+/', ' ', trim((string) $texto)));
}

function responder($datos, $codigoSalida) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($codigoSalida);
}

