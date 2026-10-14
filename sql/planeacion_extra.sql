-- ==============================================================
-- Módulos nuevos del panel de Planeación
-- Seguro de ejecutar varias veces: solo crea las tablas si NO existen.
-- No borra, no vacía y no modifica ninguna tabla ni columna existente.
-- (En la base local planeacion_municipal_adan estas tablas ya existen.)
-- ==============================================================

CREATE TABLE IF NOT EXISTS `avances_meta` (
  `id_avance` int(11) NOT NULL AUTO_INCREMENT,
  `id_meta` int(11) NOT NULL,
  `valor_avance` decimal(10,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_avance` date NOT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_avance`),
  KEY `id_meta` (`id_meta`),
  KEY `registrado_por` (`registrado_por`),
  CONSTRAINT `fk_av_meta` FOREIGN KEY (`id_meta`) REFERENCES `metas` (`id_meta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_av_usr` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bitacora` (
  `id_bitacora` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_bitacora`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `fk_bit_usr` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
