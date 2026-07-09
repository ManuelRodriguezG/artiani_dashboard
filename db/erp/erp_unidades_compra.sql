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

 Date: 15/02/2023 18:38:18
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for erp_unidades_compra
-- ----------------------------
DROP TABLE IF EXISTS `erp_unidades_compra`;
CREATE TABLE `erp_unidades_compra`  (
  `id_unidad_compra` int NOT NULL AUTO_INCREMENT,
  `unidad_compra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  `abreviatura` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id_unidad_compra`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of erp_unidades_compra
-- ----------------------------
INSERT INTO `erp_unidades_compra` VALUES (1, 'Pieza', 'PZA');
INSERT INTO `erp_unidades_compra` VALUES (2, 'Caja', 'CAJA');

SET FOREIGN_KEY_CHECKS = 1;
