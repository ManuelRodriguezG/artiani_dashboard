<?php

************Cantidad productos visitados************

SELECT
	COUNT( ecomp.id_producto ) AS cantidad,
	bisc.tipo,
	ecomp.nombre,
	ecomp.id_producto 
FROM
	bi_seguimiento_consumibles bisc
	INNER JOIN ecom_productos ecomp ON ecomp.id_producto = bisc.identificador 
WHERE
	bisc.tipo = "producto" 
GROUP BY
	ecomp.id_producto
	ORDER BY cantidad DESC

        
************Cantidad productos visitados ultimos 7 días************
        
SELECT
	COUNT( ecomp.id_producto ) AS cantidad,
	bisc.tipo,
	ecomp.nombre,
	ecomp.id_producto
FROM
	bi_seguimiento_consumibles bisc
	INNER JOIN ecom_productos ecomp ON ecomp.id_producto = bisc.identificador 
WHERE
	bisc.tipo = "producto" AND  (bisc.fch_r BETWEEN date_add(NOW(), INTERVAL -7 DAY) AND NOW())
GROUP BY
	ecomp.id_producto
	ORDER BY cantidad DESC
	limit 10
        
        
************Cantidad categorias visitadas ultimos 7 dias************
        
SELECT
	COUNT( ecomp.id_categoria ) AS cantidad,
	bisc.tipo,
	ecomp.categoria,
	ecomp.id_categoria
FROM
	bi_seguimiento_consumibles bisc
	INNER JOIN ecom_categorias ecomp ON ecomp.id_categoria = bisc.identificador 
WHERE
	bisc.tipo = "categoria" AND  (bisc.fch_r BETWEEN date_add(NOW(), INTERVAL -7 DAY) AND NOW())
GROUP BY
	ecomp.id_categoria
	ORDER BY cantidad DESC
	limit 10
        
Cantidad de clasificaciones visitadas ultimos 7 días
        
SELECT
	COUNT( ecomp.id_clasificacion ) AS cantidad,
	bisc.tipo,
	ecomp.clasificacion,
	ecomp.id_clasificacion
FROM
	bi_seguimiento_consumibles bisc
	INNER JOIN ecom_clasificaciones ecomp ON ecomp.id_clasificacion = bisc.identificador 
WHERE
	bisc.tipo = "clasificacion" AND  (bisc.fch_r BETWEEN date_add(NOW(), INTERVAL -7 DAY) AND NOW())
GROUP BY
	ecomp.id_clasificacion
	ORDER BY cantidad DESC
	limit 10

        
************Cantidad mascas visitadas************
        
SELECT
	COUNT( ecomp.id_marca ) AS cantidad,
	bisc.tipo,
	ecomp.marca,
	ecomp.id_marca
FROM
	bi_seguimiento_consumibles bisc
	INNER JOIN ecom_marcas ecomp ON ecomp.id_marca = bisc.identificador 
WHERE
	bisc.tipo = "marca" 
GROUP BY
	ecomp.id_marca
	ORDER BY cantidad DESC

************Costo - precio base************
        
SELECT
	ecomp.id_producto,
	ecomp.sku,
	ecomp.codigo_interno,
	ecomp.nombre,
	ecomp.precio_base,
	erpplp.costo,
	erpp.proveedor
FROM
	ecom_productos ecomp 
	INNER JOIN erp_proveedores_listas_productos erpplp ON erpplp.sku = ecomp.sku
	INNER JOIN erp_proveedores_listas erppl ON erppl.id_lista_proveedor = erpplp.id_lista_proveedor
	INNER JOIN erp_proveedores erpp ON erpp.id_proveedor = erppl.id_proveedor
WHERE
	ecomp.id_producto = 30
        
        
************ Franjas catalogo **************
SELECT ecomc.id_categoria, ecomc.categoria, ecomcpc.url_imagen_categoria_catalogo FROM `ecom_categorias` ecomc
LEFT JOIN ecom_categoria_producto_catalogo ecomcpc ON ecomcpc.id_categoria = ecomc.id_categoria
        
********** Init variables nueva base de datos **********
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for erp_unidad_venta
-- ----------------------------
DROP TABLE IF EXISTS `erp_unidad_venta`;
CREATE TABLE `erp_unidad_venta`  (
  `id_unidad_venta` int NOT NULL AUTO_INCREMENT,
  `unidad_venta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `abreviatura` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id_unidad_venta`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of erp_unidad_venta
-- ----------------------------
INSERT INTO `erp_unidad_venta` VALUES (1, 'Pieza', 'PZA');
INSERT INTO `erp_unidad_venta` VALUES (2, 'Metros', 'Mts');
INSERT INTO `erp_unidad_venta` VALUES (3, 'Kilogramos', 'Kg');
INSERT INTO `erp_unidad_venta` VALUES (4, 'Litros', 'L');

SET FOREIGN_KEY_CHECKS = 1;


-- ----------------------------
-- Table structure for erp_unidades_compra
-- ----------------------------
DROP TABLE IF EXISTS `erp_unidades_compra`;
CREATE TABLE `erp_unidades_compra`  (
  `id_unidad_compra` int NOT NULL AUTO_INCREMENT,
  `unidad_compra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `abreviatura` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id_unidad_compra`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of erp_unidades_compra
-- ----------------------------
INSERT INTO `erp_unidades_compra` VALUES (1, 'Pieza', 'PZA');
INSERT INTO `erp_unidades_compra` VALUES (2, 'Caja', 'CAJA');

SET FOREIGN_KEY_CHECKS = 1;


-- ----------------------------
-- Table structure for erp_unidades_compra_venta
-- ----------------------------
DROP TABLE IF EXISTS `erp_unidades_compra_venta`;
CREATE TABLE `erp_unidades_compra_venta`  (
  `id_unidad_compra_venta` int NOT NULL AUTO_INCREMENT,
  `id_unidad_compra` int NULL DEFAULT NULL,
  `id_unidad_venta` int NULL DEFAULT NULL,
  PRIMARY KEY (`id_unidad_compra_venta`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of erp_unidades_compra_venta
-- ----------------------------
INSERT INTO `erp_unidades_compra_venta` VALUES (1, 1, 1);
INSERT INTO `erp_unidades_compra_venta` VALUES (2, 2, 2);
INSERT INTO `erp_unidades_compra_venta` VALUES (3, 2, 3);
INSERT INTO `erp_unidades_compra_venta` VALUES (4, 2, 4);
INSERT INTO `erp_unidades_compra_venta` VALUES (5, 2, 1);

SET FOREIGN_KEY_CHECKS = 1;


/********************************* Ganancia bruta productos  *****************************************/

SELECT
	ecomp.sku,
	ecomp.nombre,
	erpp.proveedor,
	erpplp.costo,
	ecomp.precio_base,
	(ecomp.precio_base-erpplp.costo) as ganancia,
	(((ecomp.precio_base-erpplp.costo)/ecomp.precio_base)*100) as porcentaje_ganancia_bruta
FROM
	ecom_productos ecomp
	LEFT JOIN ecom_productos_proveedores ecompp ON ecompp.id_producto = ecomp.id_producto
	LEFT JOIN erp_proveedores erpp ON erpp.id_proveedor = ecompp.id_proveedor
	INNER JOIN erp_proveedores_listas erppl ON erppl.id_proveedor = erpp.id_proveedor
	INNER JOIN erp_proveedores_listas_productos erpplp ON erpplp.id_lista_proveedor = erppl.id_lista_proveedor
	AND ecomp.sku = erpplp.sku
        
/********************************* Ganancia bruta productos con todo y errores  *****************************************/
        
        SELECT

	ecomp.sku,
	ecomp.nombre,
	erpp.proveedor,
	erpplp.costo,
	ecomp.precio_base,
	(ecomp.precio_base-erpplp.costo) as ganancia,
	(((ecomp.precio_base-erpplp.costo)/ecomp.precio_base)*100) as porcentaje_ganancia_bruta
FROM
	ecom_productos ecomp
	LEFT JOIN ecom_productos_proveedores ecompp ON ecompp.id_producto = ecomp.id_producto
	LEFT JOIN erp_proveedores erpp ON erpp.id_proveedor = ecompp.id_proveedor
	LEFT JOIN erp_proveedores_listas erppl ON erppl.id_proveedor = erpp.id_proveedor
	LEFT JOIN erp_proveedores_listas_productos erpplp ON erpplp.id_lista_proveedor = erppl.id_lista_proveedor
	AND ecomp.sku = erpplp.sku
	ORDER BY porcentaje_ganancia_bruta 