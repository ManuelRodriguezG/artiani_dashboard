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

 Date: 15/02/2023 18:38:28
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for erp_unidad_venta
-- ----------------------------
DROP TABLE IF EXISTS `erp_unidad_venta`;
CREATE TABLE `erp_unidad_venta`  (
  `id_unidad_venta` int NOT NULL AUTO_INCREMENT,
  `unidad_venta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `abreviatura` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id_unidad_venta`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of erp_unidad_venta
-- ----------------------------
INSERT INTO `erp_unidad_venta` VALUES (1, 'Pieza', 'PZA');
INSERT INTO `erp_unidad_venta` VALUES (2, 'Metros', 'Mts');
INSERT INTO `erp_unidad_venta` VALUES (3, 'Kilogramos', 'Kg');
INSERT INTO `erp_unidad_venta` VALUES (4, 'Litros', 'L');

SET FOREIGN_KEY_CHECKS = 1;
