<?php

include_once "../app/helpers/PHPExcel-1.8/Classes/PHPExcel.php";

class Archivos extends Controlador {

  public function __construct() {
    
  }

  public function index() {
    
  }

  public function inventariosicar() {
    var_dump($_FILES);
    $fileTmpPath = $_FILES["file"]["tmp_name"];
    $fileName = $_FILES['file']['name'];
    $fileSize = $_FILES['file']['size'];
    $fileType = $_FILES['file']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
//    $allowedfileExtensions = array('jpg', 'gif', 'png', 'zip', 'txt', 'xls', 'doc');
//    if (in_array($fileExtension, $allowedfileExtensions)) {
//      
//    }
    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
    $dest_path = "docs/inventariosicar/" . $newFileName;

    $e = move_uploaded_file($fileTmpPath, $dest_path);
    var_dump($dest_path);
    var_dump($newFileName);
    var_dump($fileTmpPath);
    var_dump($e);

    $inputFileType = PHPExcel_IOFactory::identify($dest_path);
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    $objPHPExcel = $objReader->load($dest_path);
    $sheet = $objPHPExcel->getSheet(0);
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();
    var_dump($highestRow);
    $archivo = $this->modelo("Archivo");
    $producto = $this->modelo("Productos");
    var_dump($highestRow);
    for ($row = 6; $row <= $highestRow; $row++) {
      $archivo->setClave($sheet->getCell("A" . $row)->getValue());
      $archivo->setDescripcion($sheet->getCell("D" . $row)->getValue());
      $archivo->setPrecio_unitario(str_replace(array("$", " "), "", $sheet->getCell("I" . $row)->getValue()));
      $archivo->setExistencia($sheet->getCell("J" . $row)->getValue());
      $archivo->setTotal($sheet->getCell("K" . $row)->getValue());
      $respuesta = $archivo->carga_inventario_sicar();
      var_dump($respuesta);
      //registrar producto semilla
      $producto->setCodigo_barras_base($sheet->getCell("A" . $row)->getValue());
      var_dump($sheet->getCell("A" . $row)->getValue());
      $respuesta_registro_producto = $producto->consultar_registro_codigo_barras();
      if ($respuesta_registro_producto['error'] == true) {
        //registrar producto
        $producto->setNombre($sheet->getCell("D" . $row)->getValue());
        $producto->setSku('NULL');
        $producto->setDisponible(0);
        $producto->setPrecio_base('0');
        $producto->setEstatus(1);
        $producto->setCodigo_barras_base($sheet->getCell("A" . $row)->getValue());
        $producto->setDescripcion($sheet->getCell("D" . $row)->getValue());
        $producto->setExistencia($sheet->getCell("J" . $row)->getValue());
        $respuesta_producto = $producto->registrar();
      } else {
        //actualizar producto existencia
        $producto->setExistencia($sheet->getCell("J" . $row)->getValue());
        $respuesta_producto = $producto->actualizar_existencia();
        var_dump("actualizar existencia");
        var_dump($respuesta_producto);
      }
      var_dump($respuesta_registro_producto);
    }
    unlink($dest_path);
//    echo ($sheet->getCell("A6")->getValue());
  }

  public function invsicar() {
    $this->vista("inventario/sicar");
  }

}
