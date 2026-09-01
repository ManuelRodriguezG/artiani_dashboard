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
   * Proposito: leer hojas XLSX usando ZipArchive/SimpleXML sin librerias externas.
   * Impacto: Importador temporal de Contabilidad.
   * Contrato: devuelve maximo 500 filas de la hoja con mas datos como matriz cruda; no interpreta columnas.
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
    $dateStyles = $this->xlsxDateStyles($zip);
    $hojas = $this->xlsxHojas($zip);
    $mejorHoja = null;
    $filas = array();

    foreach ($hojas as $hoja) {
      $sheetXml = $zip->getFromName($hoja["ruta"]);
      if ($sheetXml === false) {
        continue;
      }
      $lectura = $this->xlsxLeerFilasHoja($sheetXml, $sharedStrings, $dateStyles, 500);
      if ($mejorHoja === null || $lectura["celdas"] > $mejorHoja["celdas"]) {
        $mejorHoja = array(
          "nombre" => $hoja["nombre"],
          "ruta" => $hoja["ruta"],
          "celdas" => $lectura["celdas"],
          "filas" => count($lectura["filas"])
        );
        $filas = $lectura["filas"];
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
      "total_filas" => count($matriz),
      "hoja" => $mejorHoja ? $mejorHoja["nombre"] : "Hoja 1",
      "hojas" => $hojas
    ));
  }

  private function xlsxLeerFilasHoja($sheetXml, $sharedStrings, $dateStyles, $maxFilas) {
    $dom = new DOMDocument();
    if (!@$dom->loadXML($sheetXml)) {
      return array("filas" => array(), "celdas" => 0);
    }
    $xpath = new DOMXPath($dom);
    $rows = $xpath->query("//*[local-name()='sheetData']/*[local-name()='row']");
    if (!$rows || $rows->length === 0) {
      return array("filas" => array(), "celdas" => 0);
    }

    $filas = array();
    $celdasConValor = 0;
    foreach ($rows as $row) {
      if (count($filas) >= intval($maxFilas)) {
        break;
      }
      $fila = array();
      $cells = $xpath->query("./*[local-name()='c']", $row);
      $fallbackIndice = 0;
      foreach ($cells as $cell) {
        $ref = $cell->getAttribute("r");
        $col = preg_replace("/[^A-Z]/", "", strtoupper($ref));
        $indice = $col !== "" ? $this->xlsxColumnaIndice($col) : $fallbackIndice;
        $tipo = $cell->getAttribute("t");
        $style = $cell->hasAttribute("s") ? intval($cell->getAttribute("s")) : -1;
        $valor = $this->xlsxValorCeldaDom($xpath, $cell, $tipo, $style, $sharedStrings, $dateStyles);
        $fila[$indice] = $valor;
        if (trim((string) $valor) !== "") {
          $celdasConValor++;
        }
        $fallbackIndice = $indice + 1;
      }
      if (!empty($fila)) {
        ksort($fila);
        $filas[] = $fila;
      }
    }

    return array("filas" => $filas, "celdas" => $celdasConValor);
  }

  private function xlsxValorCeldaDom($xpath, $cell, $tipo, $style, $sharedStrings, $dateStyles) {
    $valorNode = $xpath->query("./*[local-name()='v']", $cell)->item(0);
    $valor = $valorNode ? $valorNode->nodeValue : "";
    if ($tipo === "s") {
      return isset($sharedStrings[intval($valor)]) ? $sharedStrings[intval($valor)] : "";
    }
    if ($tipo === "inlineStr") {
      $partes = array();
      foreach ($xpath->query(".//*[local-name()='is']//*[local-name()='t']", $cell) as $texto) {
        $partes[] = $texto->nodeValue;
      }
      return implode("", $partes);
    }
    if ($tipo === "b") {
      return $valor === "1" ? "TRUE" : "FALSE";
    }
    if ($style >= 0 && isset($dateStyles[$style]) && is_numeric($valor)) {
      return $this->xlsxFechaSerial($valor);
    }
    return $valor;
  }

  private function xlsxValorCelda($cell, $tipo, $style, $sharedStrings, $dateStyles) {
    $ns = "http://schemas.openxmlformats.org/spreadsheetml/2006/main";
    $cellChildren = $cell->children($ns);
    $valor = isset($cellChildren->v) ? (string) $cellChildren->v : (isset($cell->v) ? (string) $cell->v : "");
    if ($tipo === "s") {
      return isset($sharedStrings[intval($valor)]) ? $sharedStrings[intval($valor)] : "";
    }
    if ($tipo === "inlineStr") {
      $inline = isset($cellChildren->is) ? $cellChildren->is : $cell->is;
      return $this->xlsxTextoRico($inline);
    }
    if ($tipo === "b") {
      return $valor === "1" ? "TRUE" : "FALSE";
    }
    if ($style >= 0 && isset($dateStyles[$style]) && is_numeric($valor)) {
      return $this->xlsxFechaSerial($valor);
    }
    return $valor;
  }

  private function xlsxTextoRico($node) {
    if (!$node) {
      return "";
    }
    if (isset($node->t)) {
      return (string) $node->t;
    }
    $partes = array();
    if (isset($node->r)) {
      foreach ($node->r as $run) {
        if (isset($run->t)) {
          $partes[] = (string) $run->t;
        }
      }
    }
    return implode("", $partes);
  }

  private function xlsxFechaSerial($valor) {
    $dias = intval(floor(floatval($valor)));
    if ($dias <= 0) {
      return (string) $valor;
    }
    $timestamp = strtotime("1899-12-30 +" . $dias . " days");
    return $timestamp ? date("Y-m-d", $timestamp) : (string) $valor;
  }

  private function xlsxDateStyles($zip) {
    $stylesXml = $zip->getFromName("xl/styles.xml");
    if ($stylesXml === false) {
      return array();
    }
    $xml = @simplexml_load_string($stylesXml);
    if (!$xml) {
      return array();
    }
    $ns = "http://schemas.openxmlformats.org/spreadsheetml/2006/main";
    $style = $xml->children($ns);
    $formatos = array();
    $numFmts = isset($style->numFmts->numFmt) ? $style->numFmts->numFmt : $xml->numFmts->numFmt;
    if (isset($numFmts)) {
      foreach ($numFmts as $fmt) {
        $id = intval((string) $fmt["numFmtId"]);
        $code = strtolower((string) $fmt["formatCode"]);
        if (preg_match('/(^|[^a-z])([dmya]|yy|yyyy|mmm|mmmm)([^a-z]|$)/', $code) || strpos($code, "dd") !== false || strpos($code, "mm") !== false || strpos($code, "yyyy") !== false) {
          $formatos[$id] = true;
        }
      }
    }
    foreach (array(14, 15, 16, 17, 22, 27, 30, 36, 50, 57) as $id) {
      $formatos[$id] = true;
    }
    $dateStyles = array();
    $cellXfs = isset($style->cellXfs->xf) ? $style->cellXfs->xf : $xml->cellXfs->xf;
    if (isset($cellXfs)) {
      $index = 0;
      foreach ($cellXfs as $xf) {
        $numFmtId = intval((string) $xf["numFmtId"]);
        if (isset($formatos[$numFmtId])) {
          $dateStyles[$index] = true;
        }
        $index++;
      }
    }
    return $dateStyles;
  }

  private function xlsxHojas($zip) {
    $fallback = array(array("nombre" => "Hoja 1", "ruta" => "xl/worksheets/sheet1.xml"));
    $workbookXml = $zip->getFromName("xl/workbook.xml");
    $relsXml = $zip->getFromName("xl/_rels/workbook.xml.rels");
    if ($workbookXml === false || $relsXml === false) {
      return $fallback;
    }
    $workbook = @simplexml_load_string($workbookXml);
    $rels = @simplexml_load_string($relsXml);
    if (!$workbook || !$rels) {
      return $fallback;
    }
    $ns = "http://schemas.openxmlformats.org/spreadsheetml/2006/main";
    $relNs = "http://schemas.openxmlformats.org/officeDocument/2006/relationships";
    $wb = $workbook->children($ns);
    $sheets = isset($wb->sheets->sheet) ? $wb->sheets->sheet : $workbook->sheets->sheet;
    if (!isset($sheets)) {
      return $fallback;
    }
    $relMap = array();
    foreach ($rels->Relationship as $rel) {
      $attrs = $rel->attributes();
      $id = isset($attrs["Id"]) ? (string) $attrs["Id"] : "";
      $target = isset($attrs["Target"]) ? (string) $attrs["Target"] : "";
      if ($id !== "" && $target !== "") {
        $relMap[$id] = $this->xlsxRutaTarget($target);
      }
    }
    $hojas = array();
    foreach ($sheets as $sheet) {
      $attrs = $sheet->attributes();
      $relAttrs = $sheet->attributes($relNs);
      $id = isset($relAttrs["id"]) ? (string) $relAttrs["id"] : "";
      if ($id === "" || !isset($relMap[$id])) {
        continue;
      }
      $hojas[] = array(
        "nombre" => isset($attrs["name"]) ? (string) $attrs["name"] : "Hoja " . (count($hojas) + 1),
        "ruta" => $relMap[$id]
      );
    }
    return !empty($hojas) ? $hojas : $fallback;
  }

  private function xlsxRutaTarget($target) {
    $target = str_replace("\\", "/", (string) $target);
    if (strpos($target, "/") === 0) {
      $target = ltrim($target, "/");
    } elseif (strpos($target, "xl/") !== 0) {
      $target = "xl/" . ltrim($target, "/");
    }
    $partes = array();
    foreach (explode("/", $target) as $parte) {
      if ($parte === "" || $parte === ".") {
        continue;
      }
      if ($parte === "..") {
        array_pop($partes);
        continue;
      }
      $partes[] = $parte;
    }
    return implode("/", $partes);
  }

  private function xlsxSharedStrings($zip) {
    $xmlTexto = $zip->getFromName("xl/sharedStrings.xml");
    if ($xmlTexto === false) {
      return array();
    }
    $dom = new DOMDocument();
    if (!@$dom->loadXML($xmlTexto)) {
      return array();
    }
    $xpath = new DOMXPath($dom);
    $items = $xpath->query("//*[local-name()='si']");
    $strings = array();
    foreach ($items as $si) {
      $partes = array();
      foreach ($xpath->query(".//*[local-name()='t']", $si) as $texto) {
        $partes[] = $texto->nodeValue;
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
