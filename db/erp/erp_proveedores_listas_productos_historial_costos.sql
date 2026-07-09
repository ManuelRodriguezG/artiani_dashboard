/*
 Navicat Premium Dump SQL

 Source Server         : db_beay
 Source Server Type    : MySQL
 Source Server Version : 100432 (10.4.32-MariaDB)
 Source Host           : localhost:3306
 Source Schema         : artianilocal

 Target Server Type    : MySQL
 Target Server Version : 100432 (10.4.32-MariaDB)
 File Encoding         : 65001

 Date: 13/05/2026 23:22:59
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for erp_proveedores_listas_productos_historial_costos
-- ----------------------------
DROP TABLE IF EXISTS `erp_proveedores_listas_productos_historial_costos`;
CREATE TABLE `erp_proveedores_listas_productos_historial_costos`  (
  `id_historial` int NOT NULL AUTO_INCREMENT,
  `id_proveedor` int NOT NULL,
  `sku` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `costo_anterior` decimal(11, 2) NULL DEFAULT NULL,
  `costo_nuevo` decimal(11, 2) NULL DEFAULT NULL,
  `precio_sugerido` decimal(11, 2) NULL DEFAULT NULL,
  `precio_actual` decimal(11, 2) NULL DEFAULT NULL,
  `diferencia` decimal(11, 2) NULL DEFAULT NULL,
  `porcentaje_cambio` decimal(11, 2) NULL DEFAULT NULL,
  `requiere_revision` tinyint NULL DEFAULT 1,
  `fch_m` datetime NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
