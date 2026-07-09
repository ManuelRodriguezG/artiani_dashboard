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

 Date: 05/06/2026 21:06:45
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ecom_clasificaciones
-- ----------------------------
DROP TABLE IF EXISTS `ecom_clasificaciones`;
CREATE TABLE `ecom_clasificaciones`  (
  `id_clasificacion` int NOT NULL AUTO_INCREMENT,
  `clasificacion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `identificador` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `estatus` int NULL DEFAULT NULL,
  `fch_r` datetime NULL DEFAULT NULL,
  `fch_m` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id_clasificacion`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of ecom_clasificaciones
-- ----------------------------
INSERT INTO `ecom_clasificaciones` VALUES (1, 'Acuario', 'acuario', 1, '2023-05-10 23:12:45', NULL);
INSERT INTO `ecom_clasificaciones` VALUES (2, 'Mamíferos', 'mamiferos', 1, '2023-05-10 23:12:45', NULL);
INSERT INTO `ecom_clasificaciones` VALUES (3, 'Perros y gatos', 'perros-y-gatos', 1, '2023-05-10 23:12:45', NULL);
INSERT INTO `ecom_clasificaciones` VALUES (4, 'Reptiles', 'reptiles', 1, '2023-05-10 23:12:45', NULL);
INSERT INTO `ecom_clasificaciones` VALUES (5, 'Aves', 'aves', 1, '2023-05-10 23:12:45', NULL);
INSERT INTO `ecom_clasificaciones` VALUES (6, 'Betta', 'betta', 1, NULL, NULL);
INSERT INTO `ecom_clasificaciones` VALUES (7, 'Hámster', 'hamster', 1, NULL, NULL);
INSERT INTO `ecom_clasificaciones` VALUES (8, 'Iguana', 'iguana', 1, NULL, NULL);
INSERT INTO `ecom_clasificaciones` VALUES (9, 'Cuyo', 'cuyo', 1, NULL, NULL);
INSERT INTO `ecom_clasificaciones` VALUES (10, 'Conejo', 'conejo', 1, NULL, NULL);
INSERT INTO `ecom_clasificaciones` VALUES (11, 'Erizo', 'erizo', 1, NULL, NULL);
INSERT INTO `ecom_clasificaciones` VALUES (12, 'Tortuga', 'tortuga', 1, NULL, NULL);
INSERT INTO `ecom_clasificaciones` VALUES (13, 'Perro', 'perro', 1, NULL, NULL);
INSERT INTO `ecom_clasificaciones` VALUES (14, 'Gato', 'gato', 1, NULL, NULL);
INSERT INTO `ecom_clasificaciones` VALUES (15, 'Chinchilla', 'chinchilla', 1, NULL, NULL);
INSERT INTO `ecom_clasificaciones` VALUES (16, 'Hurón', 'huron', NULL, NULL, NULL);

SET FOREIGN_KEY_CHECKS = 1;
