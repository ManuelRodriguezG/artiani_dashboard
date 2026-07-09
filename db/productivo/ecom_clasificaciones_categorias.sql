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

 Date: 05/06/2026 21:06:54
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ecom_clasificaciones_categorias
-- ----------------------------
DROP TABLE IF EXISTS `ecom_clasificaciones_categorias`;
CREATE TABLE `ecom_clasificaciones_categorias`  (
  `id_clasificacion_categoria` int NOT NULL AUTO_INCREMENT,
  `id_clasificacion` int NULL DEFAULT NULL,
  `id_categoria` int NULL DEFAULT NULL,
  PRIMARY KEY (`id_clasificacion_categoria`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 134 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of ecom_clasificaciones_categorias
-- ----------------------------
INSERT INTO `ecom_clasificaciones_categorias` VALUES (1, 1, 4);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (2, 1, 5);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (3, 1, 6);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (4, 1, 7);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (5, 1, 8);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (6, 1, 9);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (7, 1, 10);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (8, 1, 11);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (9, 1, 12);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (10, 1, 13);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (11, 1, 14);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (12, 2, 19);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (13, 2, 20);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (14, 2, 21);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (15, 2, 22);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (16, 2, 33);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (17, 2, 34);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (18, 3, 23);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (19, 3, 24);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (20, 3, 25);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (21, 3, 26);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (22, 3, 27);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (23, 3, 28);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (24, 3, 29);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (25, 3, 30);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (26, 2, 32);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (27, 5, 31);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (28, 4, 15);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (29, 4, 16);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (30, 4, 17);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (31, 4, 18);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (33, 2, 35);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (34, 2, 36);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (35, 2, 37);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (36, 3, 64);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (37, 2, 65);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (39, 3, 71);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (40, 3, 72);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (41, 3, 73);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (43, 4, 75);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (44, 5, 76);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (45, 1, 75);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (46, 7, 32);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (47, 7, 33);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (48, 7, 34);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (49, 8, 82);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (50, 6, 83);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (51, 11, 65);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (52, 11, 35);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (53, 12, 75);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (54, 12, 12);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (55, 13, 23);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (56, 13, 25);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (57, 13, 27);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (58, 13, 64);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (59, 13, 72);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (60, 13, 73);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (61, 13, 24);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (62, 14, 26);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (63, 14, 28);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (64, 14, 29);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (65, 14, 30);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (66, 14, 71);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (67, 9, 19);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (68, 9, 20);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (69, 10, 21);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (70, 10, 22);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (71, 10, 36);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (72, 4, 86);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (73, 8, 86);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (74, 3, 87);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (75, 14, 87);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (76, 1, 81);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (77, 5, 80);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (78, 2, 79);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (79, 1, 83);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (80, 6, 83);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (81, 4, 82);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (82, 10, 37);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (83, 3, 88);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (84, 3, 89);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (85, 13, 88);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (86, 14, 89);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (87, 3, 90);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (88, 13, 90);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (89, 14, 90);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (90, 1, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (91, 2, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (92, 4, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (93, 5, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (94, 6, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (95, 7, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (96, 8, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (97, 9, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (98, 10, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (99, 11, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (100, 12, 91);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (101, 2, 92);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (102, 9, 92);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (103, 5, 93);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (104, 1, 94);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (105, 2, 101);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (106, 11, 101);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (107, 3, 64);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (108, 3, 71);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (109, 4, 111);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (110, 2, 112);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (111, 3, 114);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (112, 3, 115);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (113, 14, 114);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (114, 13, 115);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (115, 7, 112);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (116, 9, 112);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (117, 10, 112);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (118, 11, 112);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (119, 15, 112);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (120, 16, 112);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (121, 1, 108);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (122, 3, 109);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (123, 14, 109);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (124, 1, 110);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (125, 6, 110);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (126, 3, 116);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (127, 13, 116);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (128, 14, 116);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (129, 9, 116);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (130, 10, 116);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (131, 11, 116);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (132, 15, 116);
INSERT INTO `ecom_clasificaciones_categorias` VALUES (133, 16, 116);

SET FOREIGN_KEY_CHECKS = 1;
