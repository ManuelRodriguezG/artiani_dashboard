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

 Date: 05/06/2026 21:09:06
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for ecom_categorias
-- ----------------------------
DROP TABLE IF EXISTS `ecom_categorias`;
CREATE TABLE `ecom_categorias`  (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `categoria` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `url_portada` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `url_categoria` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `identificador_categoria` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `estatus` int NULL DEFAULT 1,
  PRIMARY KEY (`id_categoria`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 117 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of ecom_categorias
-- ----------------------------
INSERT INTO `ecom_categorias` VALUES (4, 'Peceras', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/peceras.jpg', '/producto/categoria/peceras', 'peceras', 1);
INSERT INTO `ecom_categorias` VALUES (5, 'Filtración y oxigenación', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/filtracion1.jpg', '/producto/categoria/filtracion_y_oxigenacion', 'filtracion_y_oxigenacion', 1);
INSERT INTO `ecom_categorias` VALUES (6, 'Calefacción para peces', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/calefaccion-peces.png', '/producto/categoria/calefaccion_para_peces', 'calefaccion_para_peces', 1);
INSERT INTO `ecom_categorias` VALUES (7, 'Iluminación para peces', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/iluminacion_peceras.jpg', '/producto/categoria/iluminacion_para_peces', 'iluminacion_para_peces', 1);
INSERT INTO `ecom_categorias` VALUES (8, 'Decoración para peces', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/decoracion_peceras.jpg', '/producto/categoria/decoracion-para-peceras', 'decoracion-para-peceras', 1);
INSERT INTO `ecom_categorias` VALUES (9, 'Alimentos para peces', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/aliemntopeces.png', '/producto/categoria/alimentos_para_peces', 'alimentos_para_peces', 1);
INSERT INTO `ecom_categorias` VALUES (10, 'Bases para peceras', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/bases_peceras.jpg', '/producto/categoria/bases_para_peceras', 'bases_para_peceras', 1);
INSERT INTO `ecom_categorias` VALUES (11, 'Repuestos y aditamentos para peceras', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/aditamentos.png', '/producto/categoria/repuestos_y_aditamentos_para_peceras', 'repuestos_y_aditamentos_para_peceras', 1);
INSERT INTO `ecom_categorias` VALUES (12, 'Tortugueros', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/tortugueros.png', '/producto/categoria/tortugueros', 'tortugueros', 1);
INSERT INTO `ecom_categorias` VALUES (13, 'Plantas naturales para peceras', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/plantas.jpg', '/producto/categoria/plantas_naturales_para_peceras', 'plantas_naturales_para_peceras', 1);
INSERT INTO `ecom_categorias` VALUES (14, 'Peceras equipadas', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/peceras.equipadas.jpg', '/producto/categoria/peceras_equipadas', 'peceras_equipadas', 1);
INSERT INTO `ecom_categorias` VALUES (15, 'Alimentos para reptiles', NULL, 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/74/1692284694.jpg', '/producto/categoria/alimentos_para_reptiles', 'alimentos_para_reptiles', 1);
INSERT INTO `ecom_categorias` VALUES (16, 'Calefacción para reptiles', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/reptiles_calefaccion.png', '/producto/categoria/calefaccion_para_reptiles', 'calefaccion_para_reptiles', 1);
INSERT INTO `ecom_categorias` VALUES (17, 'Terrarios', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/terrario.jpg', '/producto/categoria/terrarios', 'terrarios', 1);
INSERT INTO `ecom_categorias` VALUES (18, 'Decoración y aditamentos para reptiles', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/decoracion-terrarios.jpg', '/producto/categoria/decoracion-y-aditamentos-para-reptiles', 'decoracion-y-aditamentos-para-reptiles', 1);
INSERT INTO `ecom_categorias` VALUES (19, 'Jaulas para cuyos', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/jaulas_cuyos.jpg', '/producto/categoria/jaulas_para_cuyos', 'jaulas_para_cuyos', 1);
INSERT INTO `ecom_categorias` VALUES (20, 'Alimento y alimentadores para cuyos', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/cuyo_alimentos.jpg', '/producto/categoria/alimento-y-alimentadores-para-cuyo', 'alimento-y-alimentadores-para-cuyo', 1);
INSERT INTO `ecom_categorias` VALUES (21, 'Jaulas para conejos', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/jaulas_conejo.png', '/producto/categoria/jaulas_para_conejos', 'jaulas_para_conejos', 1);
INSERT INTO `ecom_categorias` VALUES (22, 'Alimento y alimentadores  para conejos', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/aliemnto-conejo.jpg', '/producto/categoria/alimento-y-alimentadores-para-conejos', 'alimento-y-alimentadores-para-conejos', 1);
INSERT INTO `ecom_categorias` VALUES (23, 'Transportadoras para perros', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/transportadoraperros.jpg', '/producto/categoria/transportadoras_para_perros', 'transportadoras_para_perros', 1);
INSERT INTO `ecom_categorias` VALUES (24, 'Jaulas, corrales y vallas para perros', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/jaulasplegables.jpg', '/producto/categoria/jaulas-corrales-y-vallas-para-perros', 'jaulas-corrales-y-vallas-para-perros', 1);
INSERT INTO `ecom_categorias` VALUES (25, 'Dispensadores de agua y comida', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/dispensadores.jpg', '/producto/categoria/dispensadores_de_agua_y_comida', 'dispensadores_de_agua_y_comida', 1);
INSERT INTO `ecom_categorias` VALUES (26, 'Salud e higiene para gatos', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/collarantipulgas.jpg', '/producto/categoria/salud-e-higiene-para-gatos', 'salud-e-higiene-para-gatos', 1);
INSERT INTO `ecom_categorias` VALUES (27, 'Casas para perros', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/casasperro.jpg', '/producto/categoria/casas_para_perros', 'casas_para_perros', 1);
INSERT INTO `ecom_categorias` VALUES (28, 'Transportadoras para gatos', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/transportadora-gatos.jpg', '/producto/categoria/transportadoras_para_gatos', 'transportadoras_para_gatos', 1);
INSERT INTO `ecom_categorias` VALUES (29, 'Areneros', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/areneros.jpg', '/producto/categoria/areneros', 'areneros', 1);
INSERT INTO `ecom_categorias` VALUES (30, 'Juguetes para gatos', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/juguetes.jpg', '/producto/categoria/juguetes_para_gatos', 'juguetes_para_gatos', 1);
INSERT INTO `ecom_categorias` VALUES (31, 'Jaulas para pájaros', NULL, 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/julas-aves.jpg', '/producto/categoria/jaulas_para_pajaros', 'jaulas_para_pajaros', 1);
INSERT INTO `ecom_categorias` VALUES (32, 'Accesorios y aditamentos para hámsters', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/sustratohamster.png', '/producto/categoria/accesorios_y_aditamentos_para_hamster', 'accesorios_y_aditamentos_para_hamster', 1);
INSERT INTO `ecom_categorias` VALUES (33, 'Jaulas para hámsters', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/jaulashamster.png', '/producto/categoria/jaulas_para_hamsters', 'jaulas_para_hamsters', 1);
INSERT INTO `ecom_categorias` VALUES (34, 'Alimentación para hámsters', NULL, 'https://artiani.com.mx/media/apps/ecommerce/categorias/alimentacion_hamster.jpg', '/producto/categoria/alimentacion_para_hamsters', 'alimentacion_para_hamsters', 1);
INSERT INTO `ecom_categorias` VALUES (35, 'Alimento y alimentadores para erizo', '<p>Encuentra la variedad de alimentos que tenemos para tu mascota</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/35/1691470527.png', '/producto/categoria/alimento-y-alimentadores-para-erizo', 'alimento-y-alimentadores-para-erizo', 1);
INSERT INTO `ecom_categorias` VALUES (36, 'Salud e higiene para conejos', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/36/1691589707.png', '/producto/categoria/salud-e-higiene-para-conejos', 'salud-e-higiene-para-conejos', 1);
INSERT INTO `ecom_categorias` VALUES (37, 'Camas, casas y colchones para conejos', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/37/1692018729.jpg', '/producto/categoria/camas-casas-y-colchones-para-conejos', 'camas-casas-y-colchones-para-conejos', 1);
INSERT INTO `ecom_categorias` VALUES (64, 'Camas, casas y colchones para perros', '<p>Filtro para 200 litros</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/64/1692020680.jpg', '/producto/categoria/camas-casas-y-colchones-para-perros', 'camas-casas-y-colchones-para-perros', 1);
INSERT INTO `ecom_categorias` VALUES (65, 'Jaulas para erizo', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/65/1692191017.jpg', '/producto/categoria/jaulas_para_erizo', 'jaulas_para_erizo', 1);
INSERT INTO `ecom_categorias` VALUES (71, 'Camas, casas y colchones para gatos', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/71/1692283736.jpg', '/producto/categoria/camas_casas_y_colchones_para_gatos', 'camas_casas_y_colchones_para_gatos', 1);
INSERT INTO `ecom_categorias` VALUES (72, 'Salud e higiene para perros', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/72/1692284036.jpg', '/producto/categoria/salud_e_higiene_para_perros', 'salud_e_higiene_para_perros', 1);
INSERT INTO `ecom_categorias` VALUES (73, 'Juguetes para perros', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/73/1692284520.jpg', '/producto/categoria/juguetes_para_perros', 'juguetes_para_perros', 1);
INSERT INTO `ecom_categorias` VALUES (75, 'Alimento para tortugas', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/75/1692285234.jpg', '/producto/categoria/alimento_para_tortugas', 'alimento_para_tortugas', 1);
INSERT INTO `ecom_categorias` VALUES (76, 'Juguetes para pájaros', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/76/1692285513.jpg', '/producto/categoria/juguetes_para_pajaros', 'juguetes_para_pajaros', 1);
INSERT INTO `ecom_categorias` VALUES (79, 'Alimentos para mamíferos roedores', '<p>Alimentos vareados y balanceados para tus pequeñas mascotas</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/79/1699043074.png', '/producto/categoria/alimentos-para-mamiferos-roedores', 'alimentos-para-mamiferos-roedores', 1);
INSERT INTO `ecom_categorias` VALUES (80, 'Alimentos y aditamentos para pájaros', '<p>Alimentos vareados y balanceados para tus aves</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/80/1699043131.png', '/producto/categoria/alimentos-y-aditamentos-para-pajaros', 'alimentos-y-aditamentos-para-pajaros', 1);
INSERT INTO `ecom_categorias` VALUES (81, 'Alimentos de acuario', '<p>Alimentos vareados y balanceados para tus peces</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/81/1699043179.png', '/producto/categoria/alimentos-de-acuario', 'alimentos-de-acuario', 1);
INSERT INTO `ecom_categorias` VALUES (82, 'Alimentos para Iguana', '<p>Alimentos variados y balanceados para iguanas</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/82/1699068340.png', '/producto/categoria/alimentos-para-iguana', 'alimentos-para-iguana', 1);
INSERT INTO `ecom_categorias` VALUES (83, 'Alimentos para pez betta', '<p>Alimentos variados y balanceados para pez betta</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/83/1699068967.png', '/producto/categoria/alimentos-para-pez-betta', 'alimentos-para-pez-betta', 1);
INSERT INTO `ecom_categorias` VALUES (86, 'Alimentos vivos', '<p>Alimentos vivos para diferentes mascotas</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/86/1699147224.png', '/producto/categoria/alimentos-vivos', 'alimentos-vivos', 1);
INSERT INTO `ecom_categorias` VALUES (87, 'Jaulas, vallas y casas para gatos', '<p>Diversidad de jaulas, vallas y casas para gatos</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/87/1699147893.png', '/producto/categoria/jaulas,-vallas-y-casas-para-gatos', 'jaulas,-vallas-y-casas-para-gatos', 1);
INSERT INTO `ecom_categorias` VALUES (88, 'Alimento para perros ', '<p>Alimento para perros chicos, medianos y grandes para todas las edades </p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/88/1701565292.jpg', '/producto/categoria/alimento-para-perros-', 'alimento-para-perros-', 1);
INSERT INTO `ecom_categorias` VALUES (89, 'Alimento para gatos', '<p>Alimento para gatos, pequeños, medianos y grandes para todas las etapas.</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/89/1701565911.jpg', '/producto/categoria/alimento-para-gatos', 'alimento-para-gatos', 1);
INSERT INTO `ecom_categorias` VALUES (90, 'Platos, tazones y contenedores de alimento', '<p>Variedad de platos, tazones y contenedores de alimento para tu mascota</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/90/1706224150.png', '/producto/categoria/platos,-tazones-y-contenedores-de-alimento', 'platos,-tazones-y-contenedores-de-alimento', 1);
INSERT INTO `ecom_categorias` VALUES (91, 'Transportadoras mascoteras de plástico', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/91/1706818985.jpg', '/producto/categoria/transportadoras-mascoteras-de-plastico', 'transportadoras-mascoteras-de-plastico', 1);
INSERT INTO `ecom_categorias` VALUES (92, 'Salud e higiene para cuyo', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/92/1706820774.jpg', '/producto/categoria/salud-e-higiene-para-cuyo', 'salud-e-higiene-para-cuyo', 1);
INSERT INTO `ecom_categorias` VALUES (93, 'Alimento y alimentadores para colibrí', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/93/1706910444.jpg', '/producto/categoria/alimento-y-alimentadores-para-colibri', 'alimento-y-alimentadores-para-colibri', 1);
INSERT INTO `ecom_categorias` VALUES (94, 'Bombas sumergibles ', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/94/1710911645.png', '/producto/categoria/bombas-sumergibles-', 'bombas-sumergibles-', 1);
INSERT INTO `ecom_categorias` VALUES (95, 'Antipulgas para perros y gatos', '<p>Variedad de productos antipulgas</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/95/1710911662.png', '/producto/categoria/antipulgas-para-perros-y-gatos', 'antipulgas-para-perros-y-gatos', 1);
INSERT INTO `ecom_categorias` VALUES (96, 'Hábitat para mamíferos', '<p>Variedad de hábitats para mamíferos</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/96/1710911698.png', '/producto/categoria/habitat-para-mamiferos', 'habitat-para-mamiferos', 1);
INSERT INTO `ecom_categorias` VALUES (99, 'Alimento, premios, snack para perros ', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/99/1714768195.jpg', '/producto/categoria/alimento-premios-snack-para-perros', 'alimento-premios-snack-para-perros', 1);
INSERT INTO `ecom_categorias` VALUES (100, 'Peces de agua dulce ', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/100/1715280412.jpg', '/producto/categoria/peces-de-agua-dulce-', 'peces-de-agua-dulce-', 1);
INSERT INTO `ecom_categorias` VALUES (101, 'Salud e higiene para erizo ', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/101/1723175216.jpg', '/producto/categoria/salud-e-higiene-para-erizo-', 'salud-e-higiene-para-erizo-', 1);
INSERT INTO `ecom_categorias` VALUES (102, 'Salud e higiene para chinchilla', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/102/1723175700.jpg', '/producto/categoria/salud-e-higiene-para-chinchilla', 'salud-e-higiene-para-chinchilla', 1);
INSERT INTO `ecom_categorias` VALUES (103, 'Salud e higiene para hurón', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/103/1723175920.jpg', '/producto/categoria/salud-e-higiene-para-huron', 'salud-e-higiene-para-huron', 1);
INSERT INTO `ecom_categorias` VALUES (104, 'Alimento y alimentadores para chinchilla', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/104/1723181864.jpg', '/producto/categoria/alimento-y-alimentadores-para-chinchilla', 'alimento-y-alimentadores-para-chinchilla', 1);
INSERT INTO `ecom_categorias` VALUES (105, 'Alimento y alimentadores para hurón ', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/105/1723182364.jpg', '/producto/categoria/alimento-y-alimentadores-para-huron-', 'alimento-y-alimentadores-para-huron-', 1);
INSERT INTO `ecom_categorias` VALUES (106, 'Jaulas para chinchilla ', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/106/1723186037.jpg', '/producto/categoria/jaulas-para-chinchilla-', 'jaulas-para-chinchilla-', 1);
INSERT INTO `ecom_categorias` VALUES (107, 'Jaulas para hurón', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/107/1723186339.jpg', '/producto/categoria/jaulas-para-huron', 'jaulas-para-huron', 1);
INSERT INTO `ecom_categorias` VALUES (108, 'Acuarios, nano, micro y megas con y sin mueble para peces ', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/108/1724158477.jpg', '/producto/categoria/acuarios,-nano,-micro-y-megas-con-y-sin-mueble-para-peces-', 'acuarios,-nano,-micro-y-megas-con-y-sin-mueble-para-peces-', 1);
INSERT INTO `ecom_categorias` VALUES (109, 'Rascaderos para gato', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/109/1724773211.jpg', '/producto/categoria/rascaderos-para-gato', 'rascaderos-para-gato', 1);
INSERT INTO `ecom_categorias` VALUES (110, 'Peceras para bettas (beteras)', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/110/1724993358.jpg', '/producto/categoria/peceras-para-bettas-(beteras)', 'peceras-para-bettas-(beteras)', 1);
INSERT INTO `ecom_categorias` VALUES (111, 'Aditamentos y accesorios para tortugueros', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/111/1724994391.jpg', '/producto/categoria/aditamentos-y-accesorios-para-tortugas', 'aditamentos-y-accesorios-para-tortugas', 1);
INSERT INTO `ecom_categorias` VALUES (112, 'Premios, snacks y desgaste de dientes para roedor', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/112/1724998244.jpg', '/producto/categoria/premios,-snacks-y-desgaste-de-dientes-para-roedor', 'premios,-snacks-y-desgaste-de-dientes-para-roedor', 1);
INSERT INTO `ecom_categorias` VALUES (113, 'Accesorios y aditamentos para mamíferos roedor', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/113/1725002983.jpg', '/producto/categoria/accesorios-y-aditamentos-para-mamiferos-roedor', 'accesorios-y-aditamentos-para-mamiferos-roedor', 1);
INSERT INTO `ecom_categorias` VALUES (114, 'Accesorios, entrenamiento, sujetadores para gato', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/114/1725028971.jpg', '/producto/categoria/accesorios,-entrenamiento,-sujetadores-para-gato', 'accesorios,-entrenamiento,-sujetadores-para-gato', 1);
INSERT INTO `ecom_categorias` VALUES (115, 'Accesorios, entrenamiento, sujetadores para perro', '<p><br></p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/115/1725029950.jpg', '/producto/categoria/accesorios,-entrenamiento,-sujetadores-para-perro', 'accesorios,-entrenamiento,-sujetadores-para-perro', 1);
INSERT INTO `ecom_categorias` VALUES (116, 'Mochilas transportadoras', '<p>Gran variedad de modelos y precios en mochilas transportadoras</p>', 'https://panel.artiani.com.mx/media/apps/ecommerce/categorias/116/1748613510.webp', '/producto/categoria/mochilas-transportadoras', 'mochilas-transportadoras', 1);

SET FOREIGN_KEY_CHECKS = 1;
