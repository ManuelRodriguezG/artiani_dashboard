<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: prevalidar lista de precios MAYOREO_PRUBA con margen bruto objetivo.
 * Impacto: calcula precios sugeridos sin escribir listas, detalles, ventas ni auditoria.
 * Contrato: read-only; calcula en bloque desde costo de proveedor vigente/relacion y omite SKUs sin costo confiable.
 */

$args = isset($argv) ? $argv : array();
$codigo = "MAYOREO_PRUBA";
$nombre = "mayoreo_pruba";
$canal = "mayoreo";
$margen = 20.0;
$redondeo = 0.01;
$limite = 0;
$incluirItems = false;

foreach ($args as $arg) {
    if (strpos($arg, "--codigo=") === 0) {
        $codigo = strtoupper(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--nombre=") === 0) {
        $nombre = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--canal=") === 0) {
        $canal = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--margen=") === 0) {
        $margen = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--redondeo=") === 0) {
        $redondeo = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--limite=") === 0) {
        $limite = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--incluir_items=") === 0) {
        $incluirItems = intval(trim(substr($arg, 16), "\"' ")) === 1;
    }
}

$_SERVER["SERVER_NAME"] = "panel.com.local";
chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

class MayoreoPrubaPreflightRentabilidad extends RentabilidadErp {
    private $conexionForzada = null;

    public function __construct() {
        parent::__construct();
        $this->conexionForzada = abrirConexionMayoreoPruba();
    }

    protected function getConexion() {
        $db = parent::getConexion();
        return $db ?: $this->conexionForzada;
    }

    public function conexionPreflight() {
        return $this->getConexion();
    }
}

if (php_sapi_name() === "cli" && basename(isset($argv[0]) ? $argv[0] : "") === basename(__FILE__)) {
    $modelo = new MayoreoPrubaPreflightRentabilidad();
    $db = $modelo->conexionPreflight();
    if (!$db) {
        $db = abrirConexionMayoreoPruba();
    }

    $salida = prepararPreciosMayoreoPruba($db, $modelo, $codigo, $nombre, $canal, $margen, $redondeo, $limite, $incluirItems);
    $salida["modo"] = "read-only";
    $salida["guardrails"] = array(
        "no_escribe_bd" => true,
        "no_crea_lista" => true,
        "no_crea_detalles" => true,
        "no_activa_lista" => true
    );

    echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(!empty($salida["ok"]) ? 0 : 1);
}

function prepararPreciosMayoreoPruba($db, $modelo, $codigo, $nombre, $canal, $margen, $redondeo, $limite = 0, $incluirItems = true) {
    if (!$db) {
        global $MAYOREO_PRUBA_CONEXION_ERROR;
        return array(
            "ok" => false,
            "mensaje" => "No hay conexion MySQL disponible para preflight",
            "diagnostico" => array(
                "mysql_host_definido" => defined("MYSQLHOST"),
                "mysql_port_definido" => defined("MYSQLPORT"),
                "mysql_base_definida" => defined("MYSQLBASE"),
                "pdo_mysql" => extension_loaded("pdo_mysql"),
                "error_conexion" => $MAYOREO_PRUBA_CONEXION_ERROR
            )
        );
    }
    if ($margen <= 0 || $margen >= 95) {
        return array("ok" => false, "mensaje" => "Margen invalido; usa un porcentaje entre 0 y 95");
    }
    if ($redondeo <= 0) {
        $redondeo = 0.01;
    }

    $existente = buscarLista($db, $codigo);
    $skus = listarSkusOperativosConCostoProveedor($db, $limite);
    $items = array();
    $omitidos = array();
    $resumenFuentes = array();

    foreach ($skus as $sku) {
        $costoValor = round(floatval(isset($sku["costo"]) ? $sku["costo"] : 0), 6);
        $fuente = isset($sku["fuente"]) && trim((string) $sku["fuente"]) !== "" ? $sku["fuente"] : "sin_costo";
        if ($costoValor <= 0) {
            $omitidos[] = array(
                "id_sku" => intval($sku["id_sku"]),
                "sku" => $sku["sku"],
                "producto" => $sku["producto"],
                "motivo" => "sin_costo_comercial",
                "fuente" => $fuente
            );
            continue;
        }
        if (!isset($resumenFuentes[$fuente])) {
            $resumenFuentes[$fuente] = 0;
        }
        $resumenFuentes[$fuente]++;
        $precio = redondearPrecio($costoValor / (1 - ($margen / 100)), $redondeo);
        $margenReal = $precio > 0 ? round((($precio - $costoValor) / $precio) * 100, 4) : null;
        $items[] = array(
            "id_sku" => intval($sku["id_sku"]),
            "sku" => $sku["sku"],
            "producto" => $sku["producto"],
            "id_producto_erp" => intval($sku["id_producto_erp"]),
            "costo" => $costoValor,
            "precio" => $precio,
            "margen_bruto_pct" => $margenReal,
            "fuente" => $fuente,
            "formula" => isset($sku["formula"]) ? $sku["formula"] : ""
        );
    }

    return array(
        "ok" => true,
        "mensaje" => "Preflight de lista mayoreo_pruba calculado",
        "lista" => array(
            "codigo" => $codigo,
            "nombre" => $nombre,
            "canal" => $canal,
            "estatus_sugerido" => "borrador",
            "margen_objetivo_bruto_pct" => $margen,
            "formula_precio" => "precio = costo / (1 - margen_bruto)",
            "redondeo" => $redondeo,
            "existente" => $existente
        ),
        "resumen" => array(
            "skus_operativos" => count($skus),
            "precios_generables" => count($items),
            "omitidos_sin_costo" => count($omitidos),
            "fuentes" => $resumenFuentes,
            "criterio_costo" => "proveedor_vigente_o_relacion_proveedor",
            "usa_catalogo_referencia" => false
        ),
        "muestras" => array(
            "precios" => array_slice($items, 0, 20),
            "omitidos" => array_slice($omitidos, 0, 20)
        ),
        "items" => $incluirItems ? $items : array(),
        "omitidos" => $incluirItems ? $omitidos : array(),
        "siguiente_paso" => "Si el resumen es correcto, ejecutar el apply autorizado con respaldo externo vigente."
    );
}

function abrirConexionMayoreoPruba() {
    global $MAYOREO_PRUBA_CONEXION_ERROR;
    try {
        $dsn = "mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE;
        return new PDO($dsn, MYSQLUSER, MYSQLPASS, array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ));
    } catch (Exception $e) {
        $MAYOREO_PRUBA_CONEXION_ERROR = get_class($e) . ": " . substr($e->getMessage(), 0, 180);
        return null;
    }
}

function buscarLista($db, $codigo) {
    $stmt = $db->prepare("SELECT id_lista_precio, codigo, nombre, canal, estatus FROM erp_listas_precios WHERE UPPER(codigo)=UPPER(:codigo) LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function listarSkusOperativos($db, $limite = 0) {
    $sql = "SELECT s.id_sku, s.sku, COALESCE(s.nombre, p.nombre) producto, p.id_producto_erp
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        WHERE s.estatus='activo' AND p.estatus='activo'
        ORDER BY p.nombre ASC, s.sku ASC";
    if (intval($limite) > 0) {
        $sql .= " LIMIT " . intval($limite);
    }
    $stmt = $db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listarSkusOperativosConCostoProveedor($db, $limite = 0) {
    $sql = "SELECT s.id_sku, s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) producto, p.id_producto_erp,
            CASE
                WHEN COALESCE(pc.costo_unitario,0)>0 THEN pc.costo_unitario
                WHEN COALESCE(rc.costo_unitario,0)>0 THEN rc.costo_unitario
                ELSE 0
            END costo,
            CASE
                WHEN COALESCE(pc.costo_unitario,0)>0 THEN 'proveedor_relacion'
                WHEN COALESCE(rc.costo_unitario,0)>0 THEN 'proveedor_relacion_legacy'
                ELSE 'sin_costo'
            END fuente,
            CASE
                WHEN COALESCE(pc.costo_unitario,0)>0 THEN 'costo proveedor vigente * tipo_cambio / factor_conversion'
                WHEN COALESCE(rc.costo_unitario,0)>0 THEN 'costo ultimo relacion proveedor / factor_conversion'
                ELSE ''
            END formula
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN (
            SELECT c.id_sku,
                CAST(SUBSTRING_INDEX(GROUP_CONCAT(ROUND(
                    (c.costo * CASE WHEN COALESCE(c.moneda,'MXN')<>'MXN' THEN COALESCE(NULLIF(c.tipo_cambio_referencia,0),1) ELSE 1 END)
                    / CASE WHEN COALESCE(c.factor_conversion,0)>0 THEN c.factor_conversion ELSE 1 END
                , 6) ORDER BY
                    CASE WHEN c.vigencia_desde IS NULL OR c.vigencia_desde='' THEN 1 ELSE 0 END,
                    c.vigencia_desde DESC,
                    c.fecha_actualizacion DESC,
                    c.id_costo_proveedor_sku DESC SEPARATOR '|#|'), '|#|', 1) AS DECIMAL(18,6)) costo_unitario
            FROM erp_proveedores_sku_costos c
            WHERE c.estatus='vigente'
                AND COALESCE(c.costo,0)>0
                AND (c.vigencia_hasta IS NULL OR c.vigencia_hasta='' OR c.vigencia_hasta>=CURRENT_DATE)
            GROUP BY c.id_sku
        ) pc ON pc.id_sku=s.id_sku
        LEFT JOIN (
            SELECT sp.id_sku,
                CAST(SUBSTRING_INDEX(GROUP_CONCAT(ROUND(
                    sp.costo_ultimo / CASE WHEN COALESCE(sp.factor_conversion,0)>0 THEN sp.factor_conversion ELSE 1 END
                , 6) ORDER BY sp.es_preferido DESC, sp.fecha_actualizacion DESC, sp.id_sku_proveedor DESC SEPARATOR '|#|'), '|#|', 1) AS DECIMAL(18,6)) costo_unitario
            FROM erp_catalogo_sku_proveedores sp
            WHERE sp.estatus='activo' AND COALESCE(sp.costo_ultimo,0)>0
            GROUP BY sp.id_sku
        ) rc ON rc.id_sku=s.id_sku
        WHERE s.estatus='activo' AND p.estatus='activo'
        ORDER BY p.nombre ASC, s.sku ASC";
    if (intval($limite) > 0) {
        $sql .= " LIMIT " . intval($limite);
    }
    $stmt = $db->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function redondearPrecio($precio, $redondeo) {
    $precio = floatval($precio);
    $redondeo = floatval($redondeo);
    if ($redondeo <= 0) {
        return round($precio, 2);
    }
    return round(ceil(($precio - 0.000001) / $redondeo) * $redondeo, 2);
}
