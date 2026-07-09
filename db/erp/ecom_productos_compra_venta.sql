/*
 Navicat Premium Data Transfer

 Source Server         : beay_server
 Source Server Type    : MySQL
 Source Server Version : 80031
 Source Host           : 137.184.38.241:3306
 Source Schema         : beaydb

 Target Server Type    : MySQL
 Target Server Version : 80031
 File Encoding         : 65001

 Date: 17/02/2023 20:12:29
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ecom_productos_compra_venta
-- ----------------------------
DROP TABLE IF EXISTS `ecom_productos_compra_venta`;
CREATE TABLE `ecom_productos_compra_venta`  (
  `id_producto_compra_venta` int NOT NULL AUTO_INCREMENT,
  `id_unidad_compra` int NOT NULL,
  `id_unidad_venta` int NOT NULL,
  `solo_en_punto_de_venta` tinyint(1) NOT NULL,
  `factor` float(11, 3) NOT NULL,
  PRIMARY KEY (`id_producto_compra_venta`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
