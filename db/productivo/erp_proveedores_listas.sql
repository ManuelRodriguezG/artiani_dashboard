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

 Date: 13/06/2026 00:56:01
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for erp_proveedores_listas
-- ----------------------------
DROP TABLE IF EXISTS `erp_proveedores_listas`;
CREATE TABLE `erp_proveedores_listas`  (
  `id_lista_proveedor` int NOT NULL AUTO_INCREMENT,
  `id_proveedor` int NULL DEFAULT NULL,
  `lista` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `estatus` int NULL DEFAULT NULL,
  `fch_r` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id_lista_proveedor`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 11 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of erp_proveedores_listas
-- ----------------------------
INSERT INTO `erp_proveedores_listas` VALUES (1, 7, 'General', 1, '2023-10-24 14:34:46');
INSERT INTO `erp_proveedores_listas` VALUES (2, 22, 'General', 1, '2023-10-24 15:27:07');
INSERT INTO `erp_proveedores_listas` VALUES (3, 9, 'General', 1, '2023-10-24 15:40:50');
INSERT INTO `erp_proveedores_listas` VALUES (4, 1, 'General', 1, '2023-10-24 16:52:27');
INSERT INTO `erp_proveedores_listas` VALUES (5, 8, 'General', 1, '2023-10-26 23:12:18');
INSERT INTO `erp_proveedores_listas` VALUES (6, 3, 'General', 1, '2023-11-25 21:47:07');
INSERT INTO `erp_proveedores_listas` VALUES (7, 11, 'General', 1, '2023-12-15 17:52:22');
INSERT INTO `erp_proveedores_listas` VALUES (8, 23, 'General', 1, '2024-05-03 08:09:55');
INSERT INTO `erp_proveedores_listas` VALUES (9, 13, 'General', 1, '2024-09-24 22:04:01');
INSERT INTO `erp_proveedores_listas` VALUES (10, 12, 'General', 1, '2026-05-25 10:14:19');

SET FOREIGN_KEY_CHECKS = 1;
