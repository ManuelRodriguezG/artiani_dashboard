<?php

class Contabilidad extends Controlador {

  public function __construct() {
    $this->requerirSesion();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-29
   * Proposito: abrir el tablero mensual de conciliacion contable sin persistir datos.
   * Impacto: Finanzas/Contabilidad; ayuda a preparar informacion bancaria y CFDI para el contador.
   * Contrato: requiere finanzas.ver; el MVP procesa archivos en navegador y exporta CSV/JSON.
   */
  public function cierre_mensual() {
    $this->requerirPermiso("finanzas.ver");
    $this->vista("apps/erp/contabilidad/cierre_mensual");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-30
   * Proposito: importar una hoja XLSX de estado de cuenta para mapeo manual de columnas.
   * Impacto: Contabilidad; habilita estados de cuenta de Mercado Pago y bancos con muchas columnas.
   * Contrato: requiere finanzas.ver y archivo .xlsx; no persiste archivo ni datos, devuelve encabezados/filas.
   */
  public function importar_estado_cuenta_erp() {
    $this->requerirPermiso("finanzas.ver");
    if (!isset($_FILES["archivo"]) || !is_uploaded_file($_FILES["archivo"]["tmp_name"])) {
      echo json_encode(array("error" => true, "tipo" => "warning", "mensaje" => "Sube un archivo XLSX valido", "depurar" => array()));
      return;
    }

    $nombre = isset($_FILES["archivo"]["name"]) ? $_FILES["archivo"]["name"] : "";
    $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    if ($extension !== "xlsx") {
      echo json_encode(array("error" => true, "tipo" => "warning", "mensaje" => "Por ahora solo se acepta XLSX, CSV o TXT", "depurar" => array("archivo" => $nombre)));
      return;
    }

    $resultado = $this->leerXlsx($_FILES["archivo"]["tmp_name"]);
    echo json_encode($resultado);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-30
   * Proposito: leer la primera hoja XLSX usando ZipArchive/SimpleXML sin librerias externas.
   * Impacto: Importador temporal de Contabilidad.
   * Contrato: devuelve maximo 500 filas como matriz cruda y encabezado/valor; no interpreta columnas.
   */
  private function leerXlsx($rutaTemporal) {
    if (!class_exists("ZipArchive")) {
      return array("error" => true, "tipo" => "danger", "mensaje" => "PHP no tiene ZipArchive habilitado para leer XLSX", "depurar" => array());
    }

    $zip = new ZipArchive();
    if ($zip->open($rutaTemporal) !== true) {
      return array("error" => true, "tipo" => "danger", "mensaje" => "No pude abrir el archivo XLSX", "depurar" => array());
    }

    $sharedStrings = $this->xlsxSharedStrings($zip);
    $sheetXml = $zip->getFromName("xl/worksheets/sheet1.xml");
    if ($sheetXml === false) {
      $zip->close();
      return array("error" => true, "tipo" => "warning", "mensaje" => "El XLSX no contiene una primera hoja legible", "depurar" => array());
    }

    $xml = @simplexml_load_string($sheetXml);
    if (!$xml) {
      $zip->close();
      return array("error" => true, "tipo" => "warning", "mensaje" => "La primera hoja no tiene filas legibles", "depurar" => array());
    }

    $ns = "http://schemas.openxmlformats.org/spreadsheetml/2006/main";
    $sheet = $xml->children($ns);
    $rows = isset($sheet->sheetData->row) ? $sheet->sheetData->row : $xml->sheetData->row;
    if (!isset($rows)) {
      $zip->close();
      return array("error" => true, "tipo" => "warning", "mensaje" => "La primera hoja no tiene filas legibles", "depurar" => array());
    }

    $filas = array();
    foreach ($rows as $row) {
      $fila = array();
      $cells = isset($row->c) ? $row->c : $row->children($ns)->c;
      foreach ($cells as $cell) {
        $cellChildren = $cell->children($ns);
        $ref = (string) $cell["r"];
        $col = preg_replace("/[^A-Z]/", "", strtoupper($ref));
        $indice = $this->xlsxColumnaIndice($col);
        $tipo = (string) $cell["t"];
        $valor = isset($cellChildren->v) ? (string) $cellChildren->v : (isset($cell->v) ? (string) $cell->v : "");
        if ($tipo === "s") {
          $valor = isset($sharedStrings[intval($valor)]) ? $sharedStrings[intval($valor)] : "";
        } elseif ($tipo === "inlineStr") {
          $inline = isset($cellChildren->is) ? $cellChildren->is : $cell->is;
          $valor = isset($inline->t) ? (string) $inline->t : "";
        }
        $fila[$indice] = $valor;
      }
      if (!empty($fila)) {
        ksort($fila);
        $filas[] = $fila;
      }
      if (count($filas) > 500) {
        break;
      }
    }
    $zip->close();

    if (empty($filas)) {
      return array("error" => true, "tipo" => "warning", "mensaje" => "El XLSX no contiene datos", "depurar" => array());
    }

    $matriz = array();
    $maxColumnas = 0;
    foreach ($filas as $filaCruda) {
      if (!empty($filaCruda)) {
        $maxColumnas = max($maxColumnas, max(array_keys($filaCruda)) + 1);
      }
    }
    foreach ($filas as $filaCruda) {
      $linea = array();
      for ($i = 0; $i < $maxColumnas; $i++) {
        $linea[] = isset($filaCruda[$i]) ? $filaCruda[$i] : "";
      }
      $matriz[] = $linea;
    }

    $encabezados = array_values(array_map("trim", $filas[0]));
    $datos = array();
    for ($i = 1; $i < count($filas); $i++) {
      $item = array();
      foreach ($encabezados as $indice => $encabezado) {
        $clave = $encabezado !== "" ? $encabezado : "Columna " . ($indice + 1);
        $item[$clave] = isset($filas[$i][$indice]) ? $filas[$i][$indice] : "";
      }
      $datos[] = $item;
    }

    return array("error" => false, "tipo" => "success", "mensaje" => "XLSX leido para mapeo", "depurar" => array(
      "encabezados" => $encabezados,
      "filas" => $datos,
      "matriz" => $matriz,
      "total_filas" => count($matriz)
    ));
  }

  private function xlsxSharedStrings($zip) {
    $xmlTexto = $zip->getFromName("xl/sharedStrings.xml");
    if ($xmlTexto === false) {
      return array();
    }
    $xml = @simplexml_load_string($xmlTexto);
    if (!$xml) {
      return array();
    }
    $ns = "http://schemas.openxmlformats.org/spreadsheetml/2006/main";
    $shared = $xml->children($ns);
    $items = isset($shared->si) ? $shared->si : $xml->si;
    if (!isset($items)) {
      return array();
    }
    $strings = array();
    foreach ($items as $si) {
      $siChildren = $si->children($ns);
      if (isset($siChildren->t) || isset($si->t)) {
        $strings[] = isset($siChildren->t) ? (string) $siChildren->t : (string) $si->t;
        continue;
      }
      $partes = array();
      $runs = isset($siChildren->r) ? $siChildren->r : $si->r;
      if (isset($runs)) {
        foreach ($runs as $r) {
          $rChildren = $r->children($ns);
          $partes[] = isset($rChildren->t) ? (string) $rChildren->t : (isset($r->t) ? (string) $r->t : "");
        }
      }
      $strings[] = implode("", $partes);
    }
    return $strings;
  }

  private function xlsxColumnaIndice($columna) {
    $indice = 0;
    for ($i = 0; $i < strlen($columna); $i++) {
      $indice = ($indice * 26) + (ord($columna[$i]) - 64);
    }
    return max(0, $indice - 1);
  }
}
