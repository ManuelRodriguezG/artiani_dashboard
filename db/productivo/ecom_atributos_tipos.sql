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

 Date: 04/06/2026 21:53:23
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ecom_atributos_tipos
-- ----------------------------
DROP TABLE IF EXISTS `ecom_atributos_tipos`;
CREATE TABLE `ecom_atributos_tipos`  (
  `id_atributo_tipo` int NOT NULL,
  `tipo_atributo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `fch_r` datetime NULL DEFAULT NULL,
  `fch_m` datetime NULL DEFAULT NULL,
  `fch_e` datetime NULL DEFAULT NULL,
  `usr_r` tinyint NULL DEFAULT NULL,
  `usr_m` tinyint NULL DEFAULT NULL,
  `usr_e` tinyint NULL DEFAULT NULL,
  PRIMARY KEY (`id_atributo_tipo`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of ecom_atributos_tipos
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
