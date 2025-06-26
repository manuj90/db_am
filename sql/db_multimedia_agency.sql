-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 26, 2025 at 04:25 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_multimedia_agency`
--

-- --------------------------------------------------------

--
-- Table structure for table `CALIFICACIONES`
--

CREATE TABLE `CALIFICACIONES` (
  `id_calificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_proyecto` int(11) NOT NULL,
  `estrellas` int(11) NOT NULL CHECK (`estrellas` >= 1 and `estrellas` <= 5),
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `CALIFICACIONES`
--

INSERT INTO `CALIFICACIONES` (`id_calificacion`, `id_usuario`, `id_proyecto`, `estrellas`, `fecha`) VALUES
(16, 18, 10, 5, '2025-06-21 05:05:06'),
(20, 23, 55, 5, '2025-06-25 22:18:22'),
(21, 23, 52, 5, '2025-06-25 22:44:59');

-- --------------------------------------------------------

--
-- Table structure for table `CATEGORIAS_PROYECTO`
--

CREATE TABLE `CATEGORIAS_PROYECTO` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `icono` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `CATEGORIAS_PROYECTO`
--

INSERT INTO `CATEGORIAS_PROYECTO` (`id_categoria`, `nombre`, `descripcion`, `icono`) VALUES
(1, 'Diseño Web', 'Sitios y aplicaciones web', 'web-icon'),
(2, 'Diseño Gráfico', 'Identidad visual, logos, branding', 'graphic-icon'),
(3, 'Animación', 'Animaciones 2D y 3D', 'animation-icon'),
(4, 'Video', 'Producción y edición de video', 'video-icon'),
(5, 'UI/UX', 'Interfaces de usuario y experiencia', 'ui-icon'),
(6, 'Fotografía', 'Sesiones fotográficas profesionales', 'photo-icon');

-- --------------------------------------------------------

--
-- Table structure for table `COMENTARIOS`
--

CREATE TABLE `COMENTARIOS` (
  `id_comentario` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_proyecto` int(11) NOT NULL,
  `contenido` text NOT NULL,
  `fecha` datetime NOT NULL,
  `aprobado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `FAVORITOS`
--

CREATE TABLE `FAVORITOS` (
  `id_favorito` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_proyecto` int(11) NOT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `FAVORITOS`
--

INSERT INTO `FAVORITOS` (`id_favorito`, `id_usuario`, `id_proyecto`, `fecha`) VALUES
(18, 18, 10, '2025-06-21 05:14:54'),
(20, 18, 54, '2025-06-25 22:21:55'),
(21, 23, 52, '2025-06-25 22:45:02');

-- --------------------------------------------------------

--
-- Table structure for table `MEDIOS`
--

CREATE TABLE `MEDIOS` (
  `id_medio` int(11) NOT NULL,
  `id_proyecto` int(11) NOT NULL,
  `tipo` enum('imagen','video') NOT NULL,
  `url` varchar(255) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `es_principal` tinyint(1) DEFAULT 0,
  `orden` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `MEDIOS`
--

INSERT INTO `MEDIOS` (`id_medio`, `id_proyecto`, `tipo`, `url`, `titulo`, `descripcion`, `es_principal`, `orden`) VALUES
(31, 10, 'imagen', 'proyecto_10_1750444386_0_shk1.png', 'shk1', '', 1, 4),
(39, 12, 'imagen', 'proyecto_12_1750626928_Screenshot_2025-06-22_at_18_15_09.png', 'Screenshot 2025-06-22 at 18.15.09', '', 1, 1),
(40, 12, 'imagen', 'proyecto_12_1750626928_Screenshot_2025-06-22_at_18_14_03.png', 'Screenshot 2025-06-22 at 18.14.03', '', 0, 2),
(41, 12, 'imagen', 'proyecto_12_1750626928_Screenshot_2025-06-22_at_18_14_27.png', 'Screenshot 2025-06-22 at 18.14.27', '', 0, 3),
(43, 10, 'imagen', 'proyecto_10_1750627727_Screenshot_2025-06-22_at_18_28_14.png', 'Screenshot 2025-06-22 at 18.28.14', '', 0, 5),
(44, 10, 'imagen', 'proyecto_10_1750627813_Screenshot_2025-06-22_at_18_30_00.png', 'Screenshot 2025-06-22 at 18.30.00', '', 0, 6),
(45, 10, 'imagen', 'proyecto_10_1750627941_Screenshot_2025-06-22_at_18_31_52.png', 'Screenshot 2025-06-22 at 18.31.52', '', 0, 7),
(48, 12, 'imagen', 'proyecto_12_1750628954_Screenshot_2025-06-22_at_18_24_07.png', 'Screenshot 2025-06-22 at 18.24.07', '', 0, 6),
(49, 13, 'imagen', 'proyecto_13_1750629281_Screenshot_2025-06-22_at_18_50_38.png', 'Screenshot 2025-06-22 at 18.50.38', '', 1, 1),
(50, 13, 'imagen', 'proyecto_13_1750629281_Screenshot_2025-06-22_at_18_50_52.png', 'Screenshot 2025-06-22 at 18.50.52', '', 0, 2),
(51, 13, 'imagen', 'proyecto_13_1750629281_Screenshot_2025-06-22_at_18_50_57.png', 'Screenshot 2025-06-22 at 18.50.57', '', 0, 3),
(52, 14, 'imagen', 'proyecto_14_1750629900_Screenshot_2025-06-22_at_18_58_55.png', 'Screenshot 2025-06-22 at 18.58.55', '', 0, 1),
(53, 14, 'imagen', 'proyecto_14_1750629900_Screenshot_2025-06-22_at_18_58_30.png', 'Screenshot 2025-06-22 at 18.58.30', '', 0, 2),
(54, 14, 'imagen', 'proyecto_14_1750629900_Screenshot_2025-06-22_at_18_57_48.png', 'Screenshot 2025-06-22 at 18.57.48', '', 1, 3),
(55, 47, 'imagen', 'proyecto_47_1750638126_Screenshot_2025-06-22_at_21_21_31.png', 'Screenshot 2025-06-22 at 21.21.31', '', 0, 1),
(56, 47, 'imagen', 'proyecto_47_1750638126_Screenshot_2025-06-22_at_21_21_20.png', 'Screenshot 2025-06-22 at 21.21.20', '', 1, 2),
(58, 47, 'imagen', 'proyecto_47_1750638685_Screenshot_2025-06-22_at_21_27_13.png', 'Screenshot 2025-06-22 at 21.27.13', '', 0, 3),
(59, 50, 'imagen', 'proyecto_50_1750899122_EEAO1.png', 'EEAO1', '', 0, 1),
(60, 50, 'imagen', 'proyecto_50_1750899122_EEAO.png', 'EEAO', '', 1, 2),
(61, 50, 'imagen', 'proyecto_50_1750899215_EEAO2-2.png', 'EEAO2-2', '', 0, 3),
(62, 51, 'imagen', 'proyecto_51_1750899418_LF2.jpg', 'LF2', '', 0, 1),
(63, 51, 'imagen', 'proyecto_51_1750899418_LF.png', 'LF', '', 1, 2),
(64, 52, 'imagen', 'proyecto_52_1750899568_ML.png', 'ML', '', 1, 1),
(65, 52, 'imagen', 'proyecto_52_1750899568_ML1.png', 'ML1', '', 0, 2),
(66, 53, 'imagen', 'proyecto_53_1750899694_PH2.png', 'PH2', '', 0, 1),
(67, 53, 'imagen', 'proyecto_53_1750899694_PH1.png', 'PH1', '', 1, 2),
(68, 54, 'imagen', 'proyecto_54_1750899889_anim.png', 'anim', '', 0, 1),
(69, 54, 'imagen', 'proyecto_54_1750899937_anim2.png', 'anim2', '', 1, 2),
(70, 55, 'video', 'proyecto_55_1750900161_vid.mp4', 'vid', '', 0, 1),
(71, 55, 'imagen', 'proyecto_55_1750900218_vid.png', 'vid', '', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `NIVELES_USUARIO`
--

CREATE TABLE `NIVELES_USUARIO` (
  `id_nivel_usuario` int(11) NOT NULL,
  `nivel` varchar(50) NOT NULL,
  `descripcion` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `NIVELES_USUARIO`
--

INSERT INTO `NIVELES_USUARIO` (`id_nivel_usuario`, `nivel`, `descripcion`) VALUES
(1, 'administrador', 'Personal de la agencia con acceso total'),
(2, 'usuario', 'Usuario común con acceso limitado');

-- --------------------------------------------------------

--
-- Table structure for table `PROYECTOS`
--

CREATE TABLE `PROYECTOS` (
  `id_proyecto` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `cliente` varchar(255) NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_publicacion` datetime DEFAULT NULL,
  `publicado` tinyint(1) DEFAULT 0,
  `vistas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `PROYECTOS`
--

INSERT INTO `PROYECTOS` (`id_proyecto`, `id_usuario`, `id_categoria`, `titulo`, `descripcion`, `cliente`, `fecha_creacion`, `fecha_publicacion`, `publicado`, `vistas`) VALUES
(10, 18, 1, 'Shakira: Las Mujeres Facturan - World Tour', 'This project was a playful and strategic way to explore branding, storytelling, fan engagement, and transmedia design.\r\nWorking with a cultural icon like Shakira — even in a fictionalised context — allowed me to apply narrative techniques to create excitement and emotional connection through visual language and digital interaction.', 'Live Nation', '2023-12-01 10:00:00', '2024-01-15 11:00:00', 1, 1487),
(12, 18, 5, 'Wonder/Art', 'WonderArt es una plataforma digital diseñada para inspirar, guiar y conectar a las personas con experiencias artísticas y culturales de todo el mundo.\r\nCombina tecnología y emoción, ofreciendo contenido curado, eventos y recorridos adaptados para amantes del arte, viajeros, estudiantes y profesionales del sector cultural.', 'WonderArt S.A.', '2025-06-22 18:13:22', '2025-06-22 18:13:22', 1, 2),
(13, 18, 3, 'Purina Gati', 'Gati es un juego de plataformas en 2D protagonizado por un gato astuto y aventurero que emprende una misión para recuperar bocadillos perdidos en un mundo fantástico y lleno de sorpresas. \r\n\r\nLink: https://manuj90.itch.io/gati-game', 'Purina S.A.', '2025-06-22 18:54:24', '2025-06-22 18:54:24', 1, 3),
(14, 18, 5, 'UniMate', 'UNIMATE es un compañero digital para la vida universitaria — la combinación perfecta entre “universidad” y “mate” (tanto en el sentido de amigo o compañero de estudio, como en referencia a la icónica infusión sudamericana).\r\nAcompaña a los estudiantes a lo largo de su trayectoria académica, ofreciendo herramientas potentes y una experiencia de usuario cálida y cercana.\r\n“UNIMATE sabe cuándo tenés examen… y también lo importante que es tener un buen compañero en una noche de estudio.”', 'Colegio Leonardo Da Vinci S.A.E', '2025-06-22 19:04:43', '2025-06-22 19:04:43', 1, 3),
(47, 18, 4, 'Banda Sonora - Cunk On Earth', 'Banda sonora compuesta para el trailer de la serie doblada al español \"Cunk On Earth\" de la BBC, distribuida por Netflix.\r\n\r\nVoz de doblaje: Maria Eugenia Mazza', 'Netflix', '2025-06-22 21:19:48', '2025-06-22 21:19:48', 1, 3),
(50, 22, 6, 'Diseño poster publicitario', 'Una ruptura interdimensional desgarra la realidad, y una heroína improbable (Michelle Yeoh) debe canalizar sus nuevos poderes para enfrentarse a peligros extraños y desconcertantes del multiverso, mientras el destino del mundo pende de un hilo.', 'A24', '2025-06-25 21:51:33', '2025-06-25 21:51:33', 1, 1),
(51, 22, 2, 'Diseño artistico Light Fury', 'La Light Fury fue concebida como el contrapunto etéreo y majestuoso al ya emblemático Night Fury, Toothless. Su diseño fue solicitado específicamente por el equipo creativo de DreamWorks para introducir una figura que combinara misterio, elegancia y una conexión natural con los elementos. La premisa: crear una criatura que se sintiera tanto alienígena como angelical, exótica pero familiar.', 'DreamWorks SKG', '2025-06-25 21:56:49', '2025-06-25 21:56:49', 1, 3),
(52, 22, 2, 'Moonlight poster design', 'Este arte busca encapsular visualmente el corazón de Moonlight: la fragmentación, transformación y reconciliación de la identidad. A través de un tratamiento estilizado en facetas geométricas, se representan las tres etapas del protagonista (Chiron) —niñez, adolescencia y adultez— como un solo rostro dividido, pero finalmente unificado. El uso de polígonos no solo sugiere la construcción identitaria desde múltiples piezas, sino que también transmite una textura emocional cruda, casi quebradiza, como la experiencia del personaje.', 'A24 Films LLC', '2025-06-25 21:59:18', '2025-06-25 21:59:18', 1, 2),
(53, 22, 6, '“Neon Identity” – Campaña visual para colección cápsula', 'LUMINA™ encargó una serie de composiciones visuales que fusionaran estética urbana, identidad afrofuturista y energía neón, para el lanzamiento de su colección cápsula “Electric Archives”. El objetivo era representar al sujeto como figura central en un espacio intervenido por luces y símbolos que evocaran rebeldía, moda y poesía visual. Se requería una pieza de alto impacto, adaptable a entornos editoriales y campañas publicitarias de temporada.', 'LUMINA™ Streetwear', '2025-06-25 22:01:22', '2025-06-25 22:01:22', 1, 0),
(54, 22, 3, 'Inclusión - Motion Graphics', 'Un proyecto de motion graphics emotivo que destaca la inclusión de niños y adultos con autismo. A través de visuales vibrantes, transiciones fluidas y una narrativa cuidadosamente pensada, este proyecto busca generar conciencia y promover la comprensión, resaltando la importancia de crear entornos inclusivos donde todas las personas se sientan vistas y acompañadas.', 'Siempre para adelante', '2025-06-25 22:04:41', '2025-06-25 22:04:41', 1, 1),
(55, 22, 4, 'Charlie XCX Mapping', 'Elementos de color, ritmo, capas abstractas y transiciones sensibles, el video propone un viaje sensorial que captura la dualidad de lo emocional y lo digital, dos mundos que la música de Charli XCX explora constantemente.', 'Vroom Vroom', '2025-06-25 22:09:13', '2025-06-25 22:09:13', 1, 5);

-- --------------------------------------------------------

--
-- Table structure for table `USUARIOS`
--

CREATE TABLE `USUARIOS` (
  `id_usuario` int(11) NOT NULL,
  `id_nivel_usuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `USUARIOS`
--

INSERT INTO `USUARIOS` (`id_usuario`, `id_nivel_usuario`, `nombre`, `apellido`, `email`, `contrasena`, `telefono`, `foto_perfil`, `fecha_registro`, `activo`) VALUES
(18, 1, 'Juan Manuel', 'Scagni', 'juan.scagni@davinci.edu.ar', '$2y$10$z5MAf8TpZDm4nJBC/lmeBOv4dZTlKSlZna3yJsDHNG6Yr52TJ3xeS', '', 'user_18_1750640464_431698411_764862161930691_6650856988108604667_n_he.jpeg', '2025-06-18 22:25:46', 1),
(22, 1, 'Inés', 'Díaz Funes', 'ines.diaz@davinci.edu.ar', '$2y$10$UUjWBRrVDWOI.yyLNgfGYOY/WS1xL7ZtsQHwWsRuWBLP6VDqm5S8G', '', 'user_22_1750639162_409177578_376152554899213_6031733445688055176_n.jpg', '2025-06-22 21:35:22', 1),
(23, 2, 'Antonio Gabriel', 'Rubio', 'antonio.rubio@davinci.edu.ar', '$2y$10$lScp4yyl009JRFEiXQl9X.o0/pxN1hjngj6z/GL3507PDY/OqwEvm', '', 'user_23_1750901835_PFP.png', '2025-06-25 22:15:56', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `CALIFICACIONES`
--
ALTER TABLE `CALIFICACIONES`
  ADD PRIMARY KEY (`id_calificacion`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`,`id_proyecto`),
  ADD KEY `id_proyecto` (`id_proyecto`);

--
-- Indexes for table `CATEGORIAS_PROYECTO`
--
ALTER TABLE `CATEGORIAS_PROYECTO`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indexes for table `COMENTARIOS`
--
ALTER TABLE `COMENTARIOS`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_proyecto` (`id_proyecto`);

--
-- Indexes for table `FAVORITOS`
--
ALTER TABLE `FAVORITOS`
  ADD PRIMARY KEY (`id_favorito`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`,`id_proyecto`),
  ADD KEY `id_proyecto` (`id_proyecto`);

--
-- Indexes for table `MEDIOS`
--
ALTER TABLE `MEDIOS`
  ADD PRIMARY KEY (`id_medio`),
  ADD KEY `id_proyecto` (`id_proyecto`);

--
-- Indexes for table `NIVELES_USUARIO`
--
ALTER TABLE `NIVELES_USUARIO`
  ADD PRIMARY KEY (`id_nivel_usuario`);

--
-- Indexes for table `PROYECTOS`
--
ALTER TABLE `PROYECTOS`
  ADD PRIMARY KEY (`id_proyecto`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indexes for table `USUARIOS`
--
ALTER TABLE `USUARIOS`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id_nivel_usuario` (`id_nivel_usuario`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `CALIFICACIONES`
--
ALTER TABLE `CALIFICACIONES`
  MODIFY `id_calificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `CATEGORIAS_PROYECTO`
--
ALTER TABLE `CATEGORIAS_PROYECTO`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `COMENTARIOS`
--
ALTER TABLE `COMENTARIOS`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `FAVORITOS`
--
ALTER TABLE `FAVORITOS`
  MODIFY `id_favorito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `MEDIOS`
--
ALTER TABLE `MEDIOS`
  MODIFY `id_medio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `NIVELES_USUARIO`
--
ALTER TABLE `NIVELES_USUARIO`
  MODIFY `id_nivel_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `PROYECTOS`
--
ALTER TABLE `PROYECTOS`
  MODIFY `id_proyecto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `USUARIOS`
--
ALTER TABLE `USUARIOS`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `CALIFICACIONES`
--
ALTER TABLE `CALIFICACIONES`
  ADD CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIOS` (`id_usuario`),
  ADD CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`id_proyecto`) REFERENCES `PROYECTOS` (`id_proyecto`);

--
-- Constraints for table `COMENTARIOS`
--
ALTER TABLE `COMENTARIOS`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIOS` (`id_usuario`),
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`id_proyecto`) REFERENCES `PROYECTOS` (`id_proyecto`);

--
-- Constraints for table `FAVORITOS`
--
ALTER TABLE `FAVORITOS`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIOS` (`id_usuario`),
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`id_proyecto`) REFERENCES `PROYECTOS` (`id_proyecto`);

--
-- Constraints for table `MEDIOS`
--
ALTER TABLE `MEDIOS`
  ADD CONSTRAINT `medios_ibfk_1` FOREIGN KEY (`id_proyecto`) REFERENCES `PROYECTOS` (`id_proyecto`);

--
-- Constraints for table `PROYECTOS`
--
ALTER TABLE `PROYECTOS`
  ADD CONSTRAINT `proyectos_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `USUARIOS` (`id_usuario`),
  ADD CONSTRAINT `proyectos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `CATEGORIAS_PROYECTO` (`id_categoria`);

--
-- Constraints for table `USUARIOS`
--
ALTER TABLE `USUARIOS`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_nivel_usuario`) REFERENCES `NIVELES_USUARIO` (`id_nivel_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
