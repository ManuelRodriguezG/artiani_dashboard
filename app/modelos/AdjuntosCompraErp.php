<?php

class AdjuntosCompraErp extends CRUD {

    const TAMANO_MAXIMO = 15728640;

    public function listarAdjuntos($idOrden) {
        try {
            $db = $this->getConexion();
            $this->validarOrden($db, intval($idOrden), false);
            $stmt = $db->prepare("SELECT id_adjunto_orden, id_orden_compra,
                tipo_documento, referencia, archivo_nombre, archivo_tipo,
                archivo_tamano, archivo_hash, observaciones, estatus,
                creado_por, cancelado_por, fecha_cancelacion, fecha_registro
                FROM erp_compras_ordenes_adjuntos
                WHERE id_orden_compra=:id
                ORDER BY id_adjunto_orden DESC");
            $stmt->execute(array(":id" => intval($idOrden)));
            return $this->respuesta(false, "success", "Adjuntos consultados", $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function guardar($idOrden, $archivo, $datos, $idUsuario) {
        $db = $this->getConexion();
        $rutaAbsoluta = "";
        try {
            $idOrden = intval($idOrden);
            $this->validarArchivo($archivo);
            $mime = $this->detectarMime($archivo["tmp_name"]);
            $this->validarMime($mime);
            $hash = hash_file("sha256", $archivo["tmp_name"]);
            if (!$hash) {
                throw new Exception("No fue posible verificar el archivo");
            }

            $db->beginTransaction();
            $this->validarOrden($db, $idOrden, true);
            $stmt = $db->prepare("SELECT id_adjunto_orden
                FROM erp_compras_ordenes_adjuntos
                WHERE id_orden_compra=:orden AND archivo_hash=:hash
                AND estatus='activo' LIMIT 1");
            $stmt->execute(array(":orden" => $idOrden, ":hash" => $hash));
            if ($stmt->fetchColumn()) {
                throw new Exception("Este archivo ya esta adjunto a la orden");
            }

            $extension = $this->extensionSegura($archivo["name"], $mime);
            $directorio = $this->directorioOrden($idOrden);
            if (!is_dir($directorio) && !mkdir($directorio, 0770, true) && !is_dir($directorio)) {
                throw new Exception("No fue posible preparar el almacenamiento");
            }
            $nombreInterno = bin2hex(random_bytes(20)) . ($extension ? "." . $extension : "");
            $rutaAbsoluta = $directorio . DIRECTORY_SEPARATOR . $nombreInterno;
            if (!move_uploaded_file($archivo["tmp_name"], $rutaAbsoluta)) {
                throw new Exception("No fue posible guardar el archivo");
            }

            $rutaRelativa = "storage/erp/compras/ordenes/" . $idOrden . "/" . $nombreInterno;
            $stmt = $db->prepare("INSERT INTO erp_compras_ordenes_adjuntos
                (id_orden_compra, tipo_documento, referencia, archivo_nombre,
                 archivo_ruta, archivo_tipo, archivo_tamano, archivo_hash,
                 observaciones, estatus, creado_por)
                VALUES (:orden, :tipo, :referencia, :nombre, :ruta, :mime,
                        :tamano, :hash, :observaciones, 'activo', :usuario)");
            $stmt->execute(array(
                ":orden" => $idOrden,
                ":tipo" => $this->tipoDocumento(isset($datos["tipo_documento"]) ? $datos["tipo_documento"] : ""),
                ":referencia" => $this->texto($datos, "referencia", 150),
                ":nombre" => mb_substr(basename($archivo["name"]), 0, 255),
                ":ruta" => $rutaRelativa,
                ":mime" => $mime,
                ":tamano" => intval($archivo["size"]),
                ":hash" => $hash,
                ":observaciones" => $this->texto($datos, "observaciones"),
                ":usuario" => intval($idUsuario) ?: null
            ));
            $idAdjunto = intval($db->lastInsertId());
            $db->commit();
            return $this->respuesta(false, "success", "Archivo adjuntado", array(
                "id_orden_compra" => $idOrden,
                "id_adjunto_orden" => $idAdjunto,
                "archivo_nombre" => basename($archivo["name"])
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ($rutaAbsoluta && is_file($rutaAbsoluta)) {
                unlink($rutaAbsoluta);
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function cancelar($idOrden, $idAdjunto, $idUsuario) {
        $db = $this->getConexion();
        try {
            $db->beginTransaction();
            $this->validarOrden($db, intval($idOrden), true);
            $stmt = $db->prepare("SELECT * FROM erp_compras_ordenes_adjuntos
                WHERE id_adjunto_orden=:adjunto AND id_orden_compra=:orden
                AND estatus='activo' FOR UPDATE");
            $stmt->execute(array(":adjunto" => intval($idAdjunto), ":orden" => intval($idOrden)));
            $adjunto = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$adjunto) {
                throw new Exception("El adjunto no existe o ya fue cancelado");
            }
            $stmt = $db->prepare("UPDATE erp_compras_ordenes_adjuntos
                SET estatus='cancelado', cancelado_por=:usuario,
                    fecha_cancelacion=NOW(), archivo_ruta=NULL
                WHERE id_adjunto_orden=:adjunto");
            $stmt->execute(array(
                ":usuario" => intval($idUsuario) ?: null,
                ":adjunto" => intval($idAdjunto)
            ));
            $db->commit();

            $ruta = $this->resolverRuta(isset($adjunto["archivo_ruta"]) ? $adjunto["archivo_ruta"] : "");
            if ($ruta && is_file($ruta)) {
                unlink($ruta);
            }
            return $this->respuesta(false, "success", "Adjunto cancelado", array(
                "id_orden_compra" => intval($idOrden),
                "id_adjunto_orden" => intval($idAdjunto)
            ));
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    public function obtenerArchivo($idOrden, $idAdjunto) {
        try {
            $db = $this->getConexion();
            $this->validarOrden($db, intval($idOrden), false);
            $stmt = $db->prepare("SELECT id_adjunto_orden, archivo_nombre,
                archivo_ruta, archivo_tipo, archivo_tamano
                FROM erp_compras_ordenes_adjuntos
                WHERE id_adjunto_orden=:adjunto AND id_orden_compra=:orden
                AND estatus='activo'");
            $stmt->execute(array(":adjunto" => intval($idAdjunto), ":orden" => intval($idOrden)));
            $adjunto = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$adjunto) {
                throw new Exception("Adjunto no disponible");
            }
            $ruta = $this->resolverRuta($adjunto["archivo_ruta"]);
            if (!$ruta || !is_file($ruta)) {
                throw new Exception("El archivo fisico no esta disponible");
            }
            $adjunto["ruta_absoluta"] = $ruta;
            return $this->respuesta(false, "success", "Archivo localizado", $adjunto);
        } catch (Exception $e) {
            return $this->respuesta(true, "danger", $e->getMessage());
        }
    }

    private function validarOrden($db, $idOrden, $bloquear) {
        $stmt = $db->prepare("SELECT id_orden_compra, estatus
            FROM erp_compras_ordenes WHERE id_orden_compra=:id" .
            ($bloquear ? " FOR UPDATE" : ""));
        $stmt->execute(array(":id" => intval($idOrden)));
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orden) {
            throw new Exception("Orden de compra no encontrada");
        }
        if ($bloquear && $orden["estatus"] === "cancelada") {
            throw new Exception("La orden cancelada no admite cambios en adjuntos");
        }
        return $orden;
    }

    private function validarArchivo($archivo) {
        if (!is_array($archivo) || !isset($archivo["error"]) ||
            intval($archivo["error"]) !== UPLOAD_ERR_OK) {
            throw new Exception("Selecciona un archivo valido");
        }
        if (empty($archivo["tmp_name"]) || !is_uploaded_file($archivo["tmp_name"])) {
            throw new Exception("La carga del archivo no es valida");
        }
        $tamano = intval(isset($archivo["size"]) ? $archivo["size"] : 0);
        if ($tamano <= 0 || $tamano > self::TAMANO_MAXIMO) {
            throw new Exception("El archivo debe pesar entre 1 byte y 15 MB");
        }
    }

    private function detectarMime($ruta) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string) $finfo->file($ruta);
    }

    private function validarMime($mime) {
        $permitidos = array(
            "application/pdf", "application/xml", "text/xml", "text/plain",
            "text/csv", "image/jpeg", "image/png", "image/webp",
            "application/zip", "application/x-zip-compressed",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        );
        if (!in_array($mime, $permitidos, true)) {
            throw new Exception("Tipo de archivo no permitido: " . $mime);
        }
    }

    private function extensionSegura($nombre, $mime) {
        $mapa = array(
            "application/pdf" => "pdf", "application/xml" => "xml",
            "text/xml" => "xml", "text/plain" => "txt", "text/csv" => "csv",
            "image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp",
            "application/zip" => "zip", "application/x-zip-compressed" => "zip",
            "application/msword" => "doc",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document" => "docx",
            "application/vnd.ms-excel" => "xls",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" => "xlsx"
        );
        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        return isset($mapa[$mime]) ? $mapa[$mime] : preg_replace("/[^a-z0-9]/", "", $extension);
    }

    private function tipoDocumento($tipo) {
        $tipos = array("cotizacion", "factura", "comprobante_pago", "nota_credito", "orden_firmada", "otro");
        return in_array($tipo, $tipos, true) ? $tipo : "otro";
    }

    private function directorioOrden($idOrden) {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "storage" .
            DIRECTORY_SEPARATOR . "erp" . DIRECTORY_SEPARATOR . "compras" .
            DIRECTORY_SEPARATOR . "ordenes" . DIRECTORY_SEPARATOR . intval($idOrden);
    }

    private function resolverRuta($rutaRelativa) {
        $rutaRelativa = str_replace("\\", "/", trim((string) $rutaRelativa));
        $prefijo = "storage/erp/compras/ordenes/";
        if (strpos($rutaRelativa, $prefijo) !== 0 || strpos($rutaRelativa, "..") !== false) {
            return "";
        }
        $raiz = realpath(dirname(__DIR__, 2));
        if (!$raiz) {
            return "";
        }
        return $raiz . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $rutaRelativa);
    }

    private function texto($datos, $campo, $limite = 0) {
        $valor = trim(isset($datos[$campo]) ? (string) $datos[$campo] : "");
        return $limite > 0 ? mb_substr($valor, 0, $limite) : $valor;
    }

    private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
        return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    }
}
