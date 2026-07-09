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

 Date: 15/02/2023 18:37:40
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for erp_unidades_compra_venta
-- ----------------------------
DROP TABLE IF EXISTS `erp_unidades_compra_venta`;
CREATE TABLE `erp_unidades_compra_venta`  (
  `id_unidad_compra_venta` int NOT NULL AUTO_INCREMENT,
  `id_unidad_compra` int NULL DEFAULT NULL,
  `id_unidad_venta` int NULL DEFAULT NULL,
  PRIMARY KEY (`id_unidad_compra_venta`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of erp_unidades_compra_venta
-- ----------------------------
INSERT INTO `erp_unidades_compra_venta` VALUES (1, 1, 1);
INSERT INTO `erp_unidades_compra_venta` VALUES (2, 2, 2);
INSERT INTO `erp_unidades_compra_venta` VALUES (3, 2, 3);
INSERT INTO `erp_unidades_compra_venta` VALUES (4, 2, 4);
INSERT INTO `erp_unidades_compra_venta` VALUES (5, 2, 1);

SET FOREIGN_KEY_CHECKS = 1;
