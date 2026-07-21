<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: recuperar MySQL UAT POS solo con autorizacion explicita.
 * Impacto: puede respaldar carpeta data, intentar arranque controlado y validar disponibilidad.
 * Contrato: BLOQUEADO por defecto; no importa SQL ni modifica my.ini salvo fases futuras autorizadas.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$fase = "diagnostico";
$mysqlData = "C:\\xampp\\mysql\\data";
$mysqlBin = "C:\\xampp\\mysql\\bin";
$permitirCopiaData = false;
$permitirArranque = false;
$permitirRecovery = false;
$permitirAriaRepair = false;
$backupVerificado = "";
$confirmar = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--fase=") === 0) {
        $fase = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--mysql_data=") === 0) {
        $mysqlData = trim(substr($arg, 13), "\"' ");
    } elseif (strpos($arg, "--mysql_bin=") === 0) {
        $mysqlBin = trim(substr($arg, 12), "\"' ");
    } elseif ($arg === "--permitir_copia_data=1") {
        $permitirCopiaData = true;
    } elseif ($arg === "--permitir_arranque=1") {
        $permitirArranque = true;
    } elseif ($arg === "--permitir_recovery=1") {
        $permitirRecovery = true;
    } elseif ($arg === "--permitir_aria_repair=1") {
        $permitirAriaRepair = true;
    } elseif (strpos($arg, "--backup_verificado=") === 0) {
        $backupVerificado = trim(substr($arg, 21), "\"' ");
    } elseif (strpos($arg, "--confirmar=") === 0) {
        $confirmar = trim(substr($arg, 12), "\"' ");
    }
}

$mysqld = rtrim($mysqlBin, "\\/") . "\\mysqld.exe";
$mysqladmin = rtrim($mysqlBin, "\\/") . "\\mysqladmin.exe";
$ariaChk = rtrim($mysqlBin, "\\/") . "\\aria_chk.exe";
$myIni = rtrim($mysqlBin, "\\/") . "\\my.ini";
$mysqlPluginMai = rtrim($mysqlData, "\\/") . "\\mysql\\plugin.MAI";
$backupDataDir = rtrim(dirname($mysqlData), "\\/") . "\\data_pos_recovery_" . date("Ymd_His");

$validacion = validarEntradas($autorizar, $respaldo, $mysqlData, $mysqld, $mysqladmin, $ariaChk, $myIni, $mysqlPluginMai);
if (!$validacion["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecuto recuperacion MySQL. Falta autorizacion, respaldo o rutas validas.",
        "validacion" => $validacion,
        "requerido" => array(
            "--autorizar=MYSQL_UAT_POS_RECOVERY",
            "--respaldo=RUTA_SQL",
            "--fase=diagnostico|copia_data|aria_check_mysql_plugin|aria_check_mysql_system|aria_repair_mysql_plugin|arranque_normal|arranque_recovery_1",
            "--permitir_copia_data=1 solo para fase copia_data",
            "--permitir_aria_repair=1 solo para fase aria_repair_mysql_plugin",
            "--backup_verificado=RUTA_BACKUP_DATA_EXISTENTE solo para fase aria_repair_mysql_plugin",
            "--confirmar=REPARAR_ARIA solo para fase aria_repair_mysql_plugin",
            "--permitir_arranque=1 solo para fase arranque_normal",
            "--permitir_recovery=1 solo para fase arranque_recovery_1"
        )
    ));
}

if ($fase === "diagnostico") {
    responder(array(
        "ok" => true,
        "modo" => "diagnostico",
        "acciones" => array("validacion_entradas"),
        "validacion" => $validacion,
        "ping" => pingMysql($mysqladmin),
        "aria_check_mysql_plugin_sugerido" => '"' . $ariaChk . '" --check "' . $mysqlPluginMai . '"',
        "contrato" => contrato(false, false, false)
    ));
}

if ($fase === "copia_data") {
    if (!$permitirCopiaData) {
        bloquearFase("La fase copia_data requiere --permitir_copia_data=1");
    }
    if (file_exists($backupDataDir)) {
        bloquearFase("La carpeta destino de respaldo ya existe: " . $backupDataDir);
    }
    copiarDirectorio($mysqlData, $backupDataDir);
    responder(array(
        "ok" => true,
        "modo" => "copia_data_realizada",
        "origen" => $mysqlData,
        "destino" => $backupDataDir,
        "contrato" => contrato(true, false, false)
    ));
}

if ($fase === "aria_check_mysql_plugin") {
    $resultado = ejecutarAriaChk($ariaChk, array("--check", $mysqlPluginMai));
    responder(array(
        "ok" => $resultado["ok"],
        "modo" => "aria_check_mysql_plugin",
        "resultado" => $resultado,
        "contrato" => contrato(false, false, false)
    ));
}

if ($fase === "aria_check_mysql_system") {
    $archivos = glob(rtrim($mysqlData, "\\/") . "\\mysql\\*.MAI") ?: array();
    $resultados = array();
    $todoOk = true;
    foreach ($archivos as $archivo) {
        $resultado = ejecutarAriaChk($ariaChk, array("--check", $archivo));
        $resultados[] = array(
            "archivo" => $archivo,
            "resultado" => $resultado
        );
        if (!$resultado["ok"]) {
            $todoOk = false;
        }
    }
    responder(array(
        "ok" => $todoOk,
        "modo" => "aria_check_mysql_system",
        "archivos_revisados" => count($archivos),
        "resultados" => $resultados,
        "contrato" => contrato(false, false, false)
    ));
}

if ($fase === "aria_repair_mysql_plugin") {
    if (!$permitirAriaRepair) {
        bloquearFase("La fase aria_repair_mysql_plugin requiere --permitir_aria_repair=1");
    }
    if ($confirmar !== "REPARAR_ARIA") {
        bloquearFase("La fase aria_repair_mysql_plugin requiere --confirmar=REPARAR_ARIA");
    }
    $backupOk = validarBackupVerificado($backupVerificado, $mysqlData);
    if (!$backupOk["ok"]) {
        responder(array(
            "ok" => false,
            "modo" => "bloqueado",
            "mensaje" => "La reparacion Aria requiere respaldo data verificado y separado del data vivo.",
            "backup_verificado" => $backupOk
        ));
    }
    $resultado = ejecutarAriaChk($ariaChk, array("--recover", $mysqlPluginMai));
    responder(array(
        "ok" => $resultado["ok"],
        "modo" => "aria_repair_mysql_plugin",
        "backup_verificado" => $backupOk,
        "resultado" => $resultado,
        "contrato" => array_merge(contrato(false, false, false), array(
            "reparo_aria" => true,
            "archivo_reparado" => $mysqlPluginMai,
            "requiere_respaldo_data_previo" => true,
        ))
    ));
}

if ($fase === "arranque_normal") {
    if (!$permitirArranque) {
        bloquearFase("La fase arranque_normal requiere --permitir_arranque=1");
    }
    $cmd = '"' . $mysqld . '" --defaults-file="' . $myIni . '"';
    pclose(popen('start "" /B ' . $cmd, "r"));
    sleep(3);
    responder(array(
        "ok" => true,
        "modo" => "arranque_normal_intentado",
        "ping" => pingMysql($mysqladmin),
        "contrato" => contrato(false, true, false)
    ));
}

if ($fase === "arranque_recovery_1") {
    if (!$permitirRecovery) {
        bloquearFase("La fase arranque_recovery_1 requiere --permitir_recovery=1");
    }
    $cmd = '"' . $mysqld . '" --defaults-file="' . $myIni . '" --innodb-force-recovery=1';
    pclose(popen('start "" /B ' . $cmd, "r"));
    sleep(3);
    responder(array(
        "ok" => true,
        "modo" => "arranque_recovery_1_intentado",
        "ping" => pingMysql($mysqladmin),
        "contrato" => contrato(false, true, true)
    ));
}

bloquearFase("Fase no soportada: " . $fase);

function validarEntradas($autorizar, $respaldo, $mysqlData, $mysqld, $mysqladmin, $ariaChk, $myIni, $mysqlPluginMai) {
    $bloqueos = array();
    if ($autorizar !== "MYSQL_UAT_POS_RECOVERY") {
        $bloqueos[] = "Token de autorizacion invalido";
    }
    if (!is_file($respaldo) || filesize($respaldo) <= 0) {
        $bloqueos[] = "Respaldo SQL no existe o esta vacio";
    }
    if (!is_dir($mysqlData)) {
        $bloqueos[] = "mysql data no existe";
    }
    if (!is_file($mysqld)) {
        $bloqueos[] = "mysqld.exe no existe";
    }
    if (!is_file($mysqladmin)) {
        $bloqueos[] = "mysqladmin.exe no existe";
    }
    if (!is_file($ariaChk)) {
        $bloqueos[] = "aria_chk.exe no existe";
    }
    if (!is_file($myIni)) {
        $bloqueos[] = "my.ini no existe";
    }
    if (!is_file($mysqlPluginMai)) {
        $bloqueos[] = "mysql.plugin MAI no existe";
    }
    return array(
        "ok" => empty($bloqueos),
        "bloqueos" => $bloqueos,
        "respaldo_bytes" => is_file($respaldo) ? filesize($respaldo) : 0,
        "mysql_data" => $mysqlData,
        "mysqld" => $mysqld,
        "mysqladmin" => $mysqladmin,
        "aria_chk" => $ariaChk,
        "my_ini" => $myIni
    );
}

function pingMysql($mysqladmin) {
    $output = array();
    $codigo = 1;
    exec('"' . $mysqladmin . '" ping -h 127.0.0.1 -u root 2>&1', $output, $codigo);
    return array(
        "ok" => $codigo === 0,
        "exit_code" => $codigo,
        "output" => $output
    );
}

function copiarDirectorio($origen, $destino) {
    if (!is_dir($origen)) {
        throw new RuntimeException("Origen no existe: " . $origen);
    }
    mkdir($destino, 0777, true);
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($origen, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        $target = $destino . DIRECTORY_SEPARATOR . $items->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0777, true);
            }
        } else {
            copy($item->getPathname(), $target);
        }
    }
}

function ejecutarAriaChk($ariaChk, $argumentos) {
    $cmd = '"' . $ariaChk . '"';
    foreach ($argumentos as $argumento) {
        $cmd .= ' "' . $argumento . '"';
    }
    $cmd .= " 2>&1";
    $output = array();
    $codigo = 1;
    exec($cmd, $output, $codigo);
    return array(
        "ok" => $codigo === 0,
        "exit_code" => $codigo,
        "output" => $output
    );
}

function validarBackupVerificado($backupVerificado, $mysqlData) {
    $realBackup = realpath($backupVerificado);
    $realData = realpath($mysqlData);
    $bloqueos = array();
    if ($backupVerificado === "" || $realBackup === false || !is_dir($realBackup)) {
        $bloqueos[] = "backup_verificado no existe o no es carpeta";
    }
    if ($realBackup !== false && $realData !== false && strtolower($realBackup) === strtolower($realData)) {
        $bloqueos[] = "backup_verificado no puede ser la carpeta data viva";
    }
    if ($realBackup !== false && !is_file($realBackup . DIRECTORY_SEPARATOR . "mysql" . DIRECTORY_SEPARATOR . "plugin.MAI")) {
        $bloqueos[] = "backup_verificado no contiene mysql\\plugin.MAI";
    }
    return array(
        "ok" => empty($bloqueos),
        "ruta" => $backupVerificado,
        "realpath" => $realBackup,
        "bloqueos" => $bloqueos
    );
}

function contrato($copioData, $intentoArranque, $usoRecovery) {
    return array(
        "copio_data" => $copioData,
        "intento_arranque" => $intentoArranque,
        "uso_recovery" => $usoRecovery,
        "no_importa_sql" => true,
        "no_modifica_my_ini" => true,
        "no_elimina_archivos" => true
    );
}

function bloquearFase($mensaje) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => $mensaje
    ));
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
