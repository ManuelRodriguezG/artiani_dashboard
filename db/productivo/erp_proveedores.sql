/*
 Navicat Premium Dump SQL

 Source Server         : artianiserver
 Source Server Type    : MySQL
 Source Server Version : 100625 (10.6.25-MariaDB-log)
 Source Host           : 201.131.127.234:3306
 Source Schema         : artianicom_artiani

 Target Server Type    : MySQL
 Target Server Version : 100625 (10.6.25-MariaDB-log)
 File Encoding         : 65001

 Date: 13/06/2026 00:55:46
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for erp_proveedores
-- ----------------------------
DROP TABLE IF EXISTS `erp_proveedores`;
CREATE TABLE `erp_proveedores`  (
  `id_proveedor` int NOT NULL AUTO_INCREMENT,
  `proveedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `cuota` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 24 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of erp_proveedores
-- ----------------------------
INSERT INTO `erp_proveedores` VALUES (1, 'SUNNY', '30000');
INSERT INTO `erp_proveedores` VALUES (2, 'AQUAFISH', '0');
INSERT INTO `erp_proveedores` VALUES (3, 'ACUARIO ARBOLEDAS', '35000');
INSERT INTO `erp_proveedores` VALUES (4, 'FIISEA', '2000');
INSERT INTO `erp_proveedores` VALUES (5, 'JAMAY', '1500');
INSERT INTO `erp_proveedores` VALUES (6, 'PETPLANET', '27500');
INSERT INTO `erp_proveedores` VALUES (7, 'AQUAKRILL', '50000');
INSERT INTO `erp_proveedores` VALUES (8, 'OCEAN AQUA', '10000');
INSERT INTO `erp_proveedores` VALUES (9, 'HOBBY PET', '50000');
INSERT INTO `erp_proveedores` VALUES (10, 'PERRUCHES', '20000');
INSERT INTO `erp_proveedores` VALUES (11, 'SAKURA PETTO', '35000');
INSERT INTO `erp_proveedores` VALUES (12, 'PET GLASS', '20000');
INSERT INTO `erp_proveedores` VALUES (13, 'BASES HERRERIA', '15000');
INSERT INTO `erp_proveedores` VALUES (14, 'EL HAMSTER FELIZ', '5000');
INSERT INTO `erp_proveedores` VALUES (15, 'MELVET CONEJINA', '1200');
INSERT INTO `erp_proveedores` VALUES (16, 'KMITA', '15000');
INSERT INTO `erp_proveedores` VALUES (17, 'HENO', '2000');
INSERT INTO `erp_proveedores` VALUES (18, 'ACUAREX', '6000');
INSERT INTO `erp_proveedores` VALUES (19, 'PROPEZ', '6000');
INSERT INTO `erp_proveedores` VALUES (20, 'TRONCOS', '5000');
INSERT INTO `erp_proveedores` VALUES (21, 'RESINA THELEON', '10000');
INSERT INTO `erp_proveedores` VALUES (22, 'TODOANIMAL', '40000');
INSERT INTO `erp_proveedores` VALUES (23, 'MR RABBIT', '2500');

SET FOREIGN_KEY_CHECKS = 1;
