-- =====================================================================
-- Sistema de Planeación Municipal — H. Ayuntamiento de Tamazunchale
-- Instalación completa de la base de datos (desde cero).
--
-- Uso:
--   1. Crear la base vacía (utf8mb4), por ejemplo:
--        CREATE DATABASE planeacion_municipal_adan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   2. Importar este archivo en esa base (phpMyAdmin > Importar, o):
--        mysql -u USUARIO -p planeacion_municipal_adan < sql/instalacion.sql
--   3. Entrar con  usuario: admin   contraseña: Cambiar.2026
--      y CAMBIAR esa contraseña de inmediato (Gestión de Usuarios > Editar).
--
-- Es seguro volver a ejecutarlo: no borra tablas ni datos existentes.
-- (sql/planeacion_extra.sql solo sirve para actualizar bases antiguas.)
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrasena` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('administrador','planeacion') COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ejes` (
  `id_eje` int NOT NULL AUTO_INCREMENT,
  `nombre_eje` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_eje`),
  UNIQUE KEY `nombre_eje` (`nombre_eje`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `programas` (
  `id_programa` int NOT NULL AUTO_INCREMENT,
  `id_eje` int NOT NULL,
  `nombre_programa` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `responsable` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_programa`),
  KEY `id_eje` (`id_eje`),
  CONSTRAINT `fk_prog_eje` FOREIGN KEY (`id_eje`) REFERENCES `ejes` (`id_eje`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `metas` (
  `id_meta` int NOT NULL AUTO_INCREMENT,
  `id_programa` int NOT NULL,
  `actividad` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad_medida` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unidades',
  `meta_programada` decimal(10,2) NOT NULL,
  `meta_alcanzada` decimal(10,2) NOT NULL DEFAULT '0.00',
  `presupuesto_asignado` decimal(14,2) DEFAULT NULL,
  `presupuesto_ejercido` decimal(14,2) DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `fecha_inicio` date NOT NULL,
  `fecha_limite` date DEFAULT NULL,
  `estado` enum('Terminado','En Proceso','Pendiente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `creado_por` int DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_meta`),
  KEY `id_programa` (`id_programa`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `fk_meta_prog` FOREIGN KEY (`id_programa`) REFERENCES `programas` (`id_programa`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_meta_usr` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `avances_meta` (
  `id_avance` int NOT NULL AUTO_INCREMENT,
  `id_meta` int NOT NULL,
  `valor_avance` decimal(10,2) NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_avance` date NOT NULL,
  `registrado_por` int DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_avance`),
  KEY `id_meta` (`id_meta`),
  KEY `registrado_por` (`registrado_por`),
  CONSTRAINT `fk_av_meta` FOREIGN KEY (`id_meta`) REFERENCES `metas` (`id_meta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_av_usr` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `observaciones_meta` (
  `id_observacion` int NOT NULL AUTO_INCREMENT,
  `id_meta` int NOT NULL,
  `tipo` enum('Observación','Acuerdo','Problema') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Observación',
  `texto` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_compromiso` date DEFAULT NULL,
  `cumplido` tinyint(1) NOT NULL DEFAULT '0',
  `registrado_por` int DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_observacion`),
  KEY `id_meta` (`id_meta`),
  KEY `registrado_por` (`registrado_por`),
  CONSTRAINT `fk_obs_meta` FOREIGN KEY (`id_meta`) REFERENCES `metas` (`id_meta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_obs_usr` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bitacora` (
  `id_bitacora` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int DEFAULT NULL,
  `accion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bitacora`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_bit_usr` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `indicadores` (
  `id_indicador` int NOT NULL AUTO_INCREMENT,
  `id_programa` int NOT NULL,
  `tipo` enum('fin','proposito','componente','actividad') COLLATE utf8mb4_general_ci NOT NULL,
  `id_padre` int DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `descripcion` text COLLATE utf8mb4_general_ci NOT NULL,
  `unidad_medida` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_indicador`),
  KEY `id_programa` (`id_programa`),
  KEY `id_padre` (`id_padre`),
  CONSTRAINT `fk_indicadores_padre` FOREIGN KEY (`id_padre`) REFERENCES `indicadores` (`id_indicador`) ON DELETE CASCADE,
  CONSTRAINT `fk_indicadores_programa` FOREIGN KEY (`id_programa`) REFERENCES `programas` (`id_programa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `indicador_calendario` (
  `id_calendario` int NOT NULL AUTO_INCREMENT,
  `id_indicador` int NOT NULL,
  `anio` int NOT NULL,
  `mes` tinyint NOT NULL,
  `programado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `realizado` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_calendario`),
  UNIQUE KEY `uq_indicador_anio_mes` (`id_indicador`,`anio`,`mes`),
  CONSTRAINT `fk_calendario_indicador` FOREIGN KEY (`id_indicador`) REFERENCES `indicadores` (`id_indicador`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Usuario administrador inicial (contraseña temporal: Cambiar.2026)
INSERT IGNORE INTO usuarios (nombre, usuario, contrasena, rol, activo)
VALUES ('Administrador del sistema', 'admin', '$2y$10$zNrJ2nwyyef9UkNRWLdbXOwmToXvoWLUBPqzQuGRLMpjviqAETRV6', 'administrador', 1);
