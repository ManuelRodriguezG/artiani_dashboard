<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: entregar runbook read-only para autorizar Ventas/POS/Pedidos.
 * Impacto: ordena respaldo, DDL, semillas, turno y UAT sin ejecutar cambios.
 * Contrato: no escribe BD, no crea archivos y no modifica inventario.
 */

$respaldoEjemplo = "RUTA_O_REFERENCIA_RESPALDO";
$usuarioEjemplo = "ID_USUARIO_CAJERO";

echo json_encode(array(
    "ok" => true,
    "modo" => "read-only",
    "modulo" => "ventas_pos_pedidos",
    "objetivo" => "Pasar de POS dry-run a esquema operativo controlado por tienda/caja/terminal.",
    "precondiciones" => array(
        "Respaldo externo creado y verificable.",
        "Usuario ERP real identificado para cada caja.",
        "Autorizacion textual explicita del dueno antes de ejecutar DDL o semillas.",
        "No mezclar ventas ERP nuevas con ecommerce legacy."
    ),
    "orden" => array(
        array(
            "fase" => "validar_respaldo",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_respaldo_preflight_readonly.php --respaldo=\"" . $respaldoEjemplo . "\"",
            "escritura" => false,
            "criterio_ok" => "ok=true"
        ),
        array(
            "fase" => "paquete_autorizacion_dryrun",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_paquete_autorizacion_dryrun.php --id_usuario=" . $usuarioEjemplo,
            "escritura" => false,
            "criterio_ok" => "ok=true y listo_para_autorizacion_configuracion=true"
        ),
        array(
            "fase" => "preflight_autorizacion_base",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_base_autorizacion_preflight_readonly.php --respaldo=\"" . $respaldoEjemplo . "\" --id_usuario=" . $usuarioEjemplo,
            "escritura" => false,
            "criterio_ok" => "ok=true; base_total=11; semillas listas; sin bloqueos"
        ),
        array(
            "fase" => "compatibilidad_catalogo_inventario",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_base_compatibilidad_readonly.php",
            "escritura" => false,
            "criterio_ok" => "ok=true; sin columnas faltantes para venta POS base"
        ),
        array(
            "fase" => "guardrails_escritura",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_guardrails_readonly.php",
            "escritura" => false,
            "criterio_ok" => "ok=true; DDL/seed/turno/venta bloquean sin autorizacion valida"
        ),
        array(
            "fase" => "readiness_suite_base",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_base_readiness_suite_readonly.php --respaldo=\"" . $respaldoEjemplo . "\" --id_usuario=" . $usuarioEjemplo,
            "escritura" => false,
            "criterio_ok" => "ok=true; readiness base completo"
        ),
        array(
            "fase" => "aplicar_ddl_autorizado",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_schema_apply_authorized.php --autorizar=VENTAS_POS_DDL_BASE --respaldo=\"" . $respaldoEjemplo . "\"",
            "escritura" => true,
            "criterio_ok" => "modo=ddl_ejecutado"
        ),
        array(
            "fase" => "crear_cajas_terminales_asignaciones",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_seed_apply_authorized.php --autorizar=VENTAS_POS_SEED --respaldo=\"" . $respaldoEjemplo . "\" --id_usuario=" . $usuarioEjemplo,
            "escritura" => true,
            "criterio_ok" => "terminal_asignacion_actual_erp devuelve asignacion_activa=true para el cajero"
        ),
        array(
            "fase" => "uat_post_configuracion",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_post_config_readonly.php --id_usuario=" . $usuarioEjemplo . " --alcance=base",
            "escritura" => false,
            "criterio_ok" => "sin tablas pendientes y asignacion_activa=true; todavia puede faltar turno abierto"
        ),
        array(
            "fase" => "uat_general_readonly",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_dryrun_readonly.php --alcance=base",
            "escritura" => false,
            "criterio_ok" => "confirma catalogos, imagenes, prevalidacion y bloqueos operativos restantes"
        ),
        array(
            "fase" => "cliente_precio_apartado_readonly",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_cliente_precio_apartado_readonly.php --id_almacen=4 --id_sku=1113 --identificador=5550000000",
            "escritura" => false,
            "criterio_ok" => "contratos de cliente/precio y abono devuelven schema pendiente o bloqueos esperados sin escribir"
        ),
        array(
            "fase" => "turno_preflight_readonly",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_turno_preflight_readonly.php --id_usuario=" . $usuarioEjemplo . " --monto_inicial=500",
            "escritura" => false,
            "criterio_ok" => "puede_abrir_turno=true"
        ),
        array(
            "fase" => "apertura_turno_real",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_turno_apertura_apply_authorized.php --autorizar=VENTAS_POS_TURNO_APERTURA --respaldo=\"" . $respaldoEjemplo . "\" --id_usuario=" . $usuarioEjemplo . " --monto_inicial=500",
            "escritura" => true,
            "criterio_ok" => "turno abierto ligado a caja, usuario y almacen"
        ),
        array(
            "fase" => "venta_preflight_readonly",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_venta_preflight_readonly.php --id_usuario=" . $usuarioEjemplo,
            "escritura" => false,
            "criterio_ok" => "puede_vender_real=true y planes_salida con asignaciones"
        ),
        array(
            "fase" => "venta_real_con_kardex",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_venta_apply_authorized.php --autorizar=VENTAS_POS_VENTA_REAL --respaldo=\"" . $respaldoEjemplo . "\" --id_usuario=" . $usuarioEjemplo,
            "escritura" => true,
            "criterio_ok" => "venta, pago, salida inventario, kardex y trazabilidad creados en una transaccion"
        ),
        array(
            "fase" => "post_venta_readonly",
            "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_post_venta_readonly.php --folio=FOLIO_POS_GENERADO",
            "escritura" => false,
            "criterio_ok" => "ok=true; detalle, pagos, movimientos caja, kardex y trazabilidad cuadran"
        )
    ),
    "frase_autorizacion_sugerida" => "AUTORIZO CREAR ESQUEMA BASE ERP VENTAS POS PEDIDOS usando respaldo " . $respaldoEjemplo,
    "frase_autorizacion_expandida" => "AUTORIZO CREAR ESQUEMA EXPANDIDO ERP VENTAS POS PEDIDOS CLIENTES LISTAS ATENCIONES Y APARTADOS usando respaldo " . $respaldoEjemplo,
    "riesgos_controlados" => array(
        "El DDL esta separado de semillas.",
        "Las asignaciones no deben ejecutarse con id_usuario=0.",
        "La venta real queda bloqueada hasta tener caja, turno, esquema completo y autorizacion VENTAS_POS_VENTA_REAL.",
        "Las unidades abiertas siguen bloqueadas para ecommerce como unidad cerrada."
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
