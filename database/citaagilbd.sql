-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: citaagilbd
-- ------------------------------------------------------
-- Server version	8.4.7

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `citas`
--

DROP TABLE IF EXISTS `citas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `citas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `paciente_id` int unsigned NOT NULL,
  `medico_id` int unsigned NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci,
  `estatus` enum('pendiente','confirmada','cancelada','completada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_paciente` (`paciente_id`),
  KEY `idx_medico` (`medico_id`),
  KEY `idx_fecha` (`fecha`),
  CONSTRAINT `fk_cita_medico` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cita_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `citas`
--

LOCK TABLES `citas` WRITE;
/*!40000 ALTER TABLE `citas` DISABLE KEYS */;
INSERT INTO `citas` VALUES (1,7,4,'2026-06-03','10:30:00','prueba','completada','2026-06-03 10:26:57'),(2,7,7,'2026-06-04','15:00:00','me siento mal','completada','2026-06-03 10:27:30'),(3,9,9,'2026-06-03','14:30:00','prueba motivo','completada','2026-06-03 13:08:33');
/*!40000 ALTER TABLE `citas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracion` (
  `clave` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion`
--

LOCK TABLES `configuracion` WRITE;
/*!40000 ALTER TABLE `configuracion` DISABLE KEYS */;
INSERT INTO `configuracion` VALUES ('horario_inicio','08:00'),('horario_fin','18:00'),('duracion_cita','30'),('dias_laborables','1,2,3,4,5');
/*!40000 ALTER TABLE `configuracion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disponibilidad_medico`
--

DROP TABLE IF EXISTS `disponibilidad_medico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `disponibilidad_medico` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `medico_id` int unsigned NOT NULL,
  `dia_semana` tinyint NOT NULL COMMENT '1=Lunes, 7=Domingo',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_medico_dia` (`medico_id`,`dia_semana`),
  CONSTRAINT `fk_disp_medico` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disponibilidad_medico`
--

LOCK TABLES `disponibilidad_medico` WRITE;
/*!40000 ALTER TABLE `disponibilidad_medico` DISABLE KEYS */;
INSERT INTO `disponibilidad_medico` VALUES (1,4,1,'08:00:00','18:00:00',1),(2,4,2,'08:00:00','18:00:00',1),(3,4,3,'08:00:00','18:00:00',1),(4,4,5,'08:00:00','18:00:00',1),(5,5,1,'08:00:00','18:00:00',1),(6,5,3,'08:00:00','18:00:00',1),(7,5,5,'08:00:00','18:00:00',1),(11,6,2,'08:00:00','15:00:00',1),(12,6,3,'08:00:00','18:00:00',1),(13,6,4,'08:00:00','18:00:00',1),(14,6,5,'08:00:00','18:00:00',1),(15,7,1,'08:00:00','11:00:00',1),(16,7,2,'10:00:00','20:00:00',1),(17,7,4,'10:00:00','20:00:00',1),(18,7,5,'08:00:00','14:00:00',1),(19,8,1,'08:00:00','18:00:00',1),(20,8,2,'08:00:00','18:00:00',1),(21,8,3,'08:00:00','18:00:00',1),(22,8,4,'08:00:00','18:00:00',1),(23,8,5,'08:00:00','18:00:00',1),(24,9,1,'08:00:00','18:00:00',1),(25,9,2,'08:00:00','18:00:00',1),(26,9,3,'08:00:00','18:00:00',1),(27,9,4,'10:00:00','18:00:00',1),(28,9,5,'08:00:00','20:00:00',1);
/*!40000 ALTER TABLE `disponibilidad_medico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `especialidades`
--

DROP TABLE IF EXISTS `especialidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `especialidades` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `especialidades`
--

LOCK TABLES `especialidades` WRITE;
/*!40000 ALTER TABLE `especialidades` DISABLE KEYS */;
INSERT INTO `especialidades` VALUES (3,'Cardiología'),(4,'Dermatología'),(5,'Ginecología'),(1,'Medicina General'),(2,'Pediatría');
/*!40000 ALTER TABLE `especialidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `excepciones_disponibilidad`
--

DROP TABLE IF EXISTS `excepciones_disponibilidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `excepciones_disponibilidad` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `medico_id` int unsigned NOT NULL,
  `fecha` date NOT NULL,
  `motivo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_excep_medico` (`medico_id`),
  CONSTRAINT `fk_excep_medico` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `excepciones_disponibilidad`
--

LOCK TABLES `excepciones_disponibilidad` WRITE;
/*!40000 ALTER TABLE `excepciones_disponibilidad` DISABLE KEYS */;
INSERT INTO `excepciones_disponibilidad` VALUES (1,4,'2026-06-04','descanso'),(2,5,'2026-06-04','descanso'),(3,5,'2026-06-09','no disponible'),(4,6,'2026-06-08','no disponible'),(5,9,'2026-06-06','descanso');
/*!40000 ALTER TABLE `excepciones_disponibilidad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medicos`
--

DROP TABLE IF EXISTS `medicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medicos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned NOT NULL,
  `especialidad_id` int unsigned NOT NULL,
  `cedula` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  KEY `fk_medico_especialidad` (`especialidad_id`),
  CONSTRAINT `fk_medico_especialidad` FOREIGN KEY (`especialidad_id`) REFERENCES `especialidades` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_medico_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medicos`
--

LOCK TABLES `medicos` WRITE;
/*!40000 ALTER TABLE `medicos` DISABLE KEYS */;
INSERT INTO `medicos` VALUES (4,2,3,'CED-001'),(5,3,4,'CED-002'),(6,4,5,'CED-003'),(7,5,1,'CED-004'),(8,6,2,'CED-005'),(9,8,1,'CED-008');
/*!40000 ALTER TABLE `medicos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notas_consulta`
--

DROP TABLE IF EXISTS `notas_consulta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notas_consulta` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cita_id` int unsigned NOT NULL,
  `medico_id` int unsigned NOT NULL,
  `paciente_id` int unsigned NOT NULL,
  `nota` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_medico` (`medico_id`),
  KEY `idx_paciente` (`paciente_id`),
  KEY `fk_nota_cita` (`cita_id`),
  CONSTRAINT `fk_nota_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notas_consulta`
--

LOCK TABLES `notas_consulta` WRITE;
/*!40000 ALTER TABLE `notas_consulta` DISABLE KEYS */;
INSERT INTO `notas_consulta` VALUES (1,1,4,7,'lo espero','2026-06-03 10:29:01'),(2,2,7,7,'finalizado','2026-06-03 10:44:28'),(3,3,9,9,'cita terminada','2026-06-03 13:10:13');
/*!40000 ALTER TABLE `notas_consulta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('paciente','medico','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paciente',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`),
  KEY `idx_correo` (`correo`),
  KEY `idx_rol` (`rol`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Admin','Sistema','admin@citaagil.com','8111111111','$2y$10$ZvDTn93m/sZgap/RgNfS4ecl3LSICozHRSR2KsHSfVLXrotsXKiE6','admin',1,'2026-06-02 10:40:30'),(2,'Maria','López','maria.lopez@gmail.com','3333333333','$2y$10$eRzsC4p/nq3PRVmEVMi86OdZ5RfR9CGJDoIkYB1zVvxkIbUmdRXmy','medico',1,'2026-06-03 10:05:52'),(3,'Carlos','Hernandez','carlos@gmail.com','4444444444','$2y$10$h5in0ChsddxGfMUxX0AaY.gdlU/VNI4qH9vI8YXIlflKpYHJFEbmG','medico',1,'2026-06-03 10:06:41'),(4,'Ana','Martinez','ana@gmail.com','5555555555','$2y$10$08//YiZJASQh.XtTFSzl5u0nHR7eTX17WFKMjta0epbVs/ITa8lu6','medico',1,'2026-06-03 10:07:19'),(5,'Luis','Rodriguez','luis@gmail.com','6666666666','$2y$10$H9fYeEjLgYlzclhBMr9NKeO7TxIQxp5.AEUtM.IanxCdIRP4t15dW','medico',1,'2026-06-03 10:07:57'),(6,'Ignacio','Cepeda','ignacio@gmail.com','6666666666','$2y$10$EO0Ot7q7QrH9SeVTyf5M.OWv8PGDKRgJXYJp8kT3BuUqU0XnnPqOO','medico',1,'2026-06-03 10:08:28'),(7,'usuario','sistema','usuario@gmail.com','0000000000','$2y$10$mX43DIkJ2y4OKVbUtYjGVuJTU9kseJVnGvRlzGu4Kx09KiNynWy9O','paciente',1,'2026-06-03 10:10:13'),(8,'Francisco','Cortez','francisco@gmail.com','1111111111','$2y$10$XewSkAY0o/Rx.nDXISm5H.7Lk7G8a0UB1Th2PgdzBr29VpsWGJyO2','medico',1,'2026-06-03 13:05:01'),(9,'usuario2','prueba','usuario2@gmail.com','9809999999','$2y$10$nQy9koGMBBoK1z0l9BFhDOE.EW6IeaU7r3QD.5xXjG5eImCr5aT/a','paciente',1,'2026-06-03 13:07:54');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-04 10:21:44
