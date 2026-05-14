```sql
-- Estructura de tabla para `tipo_usuario`
CREATE TABLE `tipo_usuario` (
  `id_tipo_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_tipo` varchar(25) NOT NULL,
  PRIMARY KEY (`id_tipo_usuario`),
  UNIQUE KEY `nombre_tipo` (`nombre_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para `estado_cita`
CREATE TABLE `estado_cita` (
  `id_estado_cita` int(11) NOT NULL AUTO_INCREMENT,
  `estado` varchar(20) NOT NULL,
  PRIMARY KEY (`id_estado_cita`),
  UNIQUE KEY `estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para `horario`
CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `estado` varchar(20) DEFAULT 'disponible',
  PRIMARY KEY (`id_horario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para `usuario`
CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `id_tipo_usuario` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido_paterno` varchar(50) DEFAULT NULL,
  `apellido_materno` varchar(50) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `contrasena_hash` varchar(255) NOT NULL,
  `fecha_alta` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `id_tipo_usuario` (`id_tipo_usuario`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_tipo_usuario`) REFERENCES `tipo_usuario` (`id_tipo_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para `cita`
CREATE TABLE `cita` (
  `id_cita` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_horario` int(11) NOT NULL,
  `id_estado_cita` int(11) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cita`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_horario` (`id_horario`),
  KEY `id_estado_cita` (`id_estado_cita`),
  CONSTRAINT `cita_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`),
  CONSTRAINT `cita_ibfk_2` FOREIGN KEY (`id_horario`) REFERENCES `horario` (`id_horario`),
  CONSTRAINT `cita_ibfk_3` FOREIGN KEY (`id_estado_cita`) REFERENCES `estado_cita` (`id_estado_cita`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para `recordatorio`
CREATE TABLE `recordatorio` (
  `id_recordatorio` int(11) NOT NULL AUTO_INCREMENT,
  `id_cita` int(11) NOT NULL,
  `fecha_envio` datetime NOT NULL,
  `enviado` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_recordatorio`),
  KEY `id_cita` (`id_cita`),
  CONSTRAINT `recordatorio_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `cita` (`id_cita`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

INSERT INTO `tipo_usuario` (`id_tipo_usuario`, `nombre_tipo`) VALUES
(1, 'Doctor'),
(2, 'Asistente'),
(3, 'Paciente');

INSERT INTO `usuario` (`id_usuario`, `id_tipo_usuario`, `nombre`, `apellido_paterno`, `apellido_materno`, `telefono`, `correo`, `contrasena_hash`, `fecha_alta`) VALUES
(1, 1, 'Jesús Fernando', 'Rodríguez', 'Nava', '8710000000', 'doctor@nava.com', 'Nava2026*', '2026-04-26 01:27:30'),
(2, 2, 'Jesahias Fernando', 'Juarez', 'Palacios', '8711112233', 'asistente@vital.com', 'Asistente2026*', '2026-04-26 01:32:50'),
(3, 3, 'Jorge Humberto', 'Esquivel', 'Cuellar', '8714445566', 'paciente1@vital.com', 'Nava2026*', '2026-04-26 01:32:50'),
(4, 3, 'Jesús Javier', 'Martínez', 'Hernández', '8717778899', 'paciente2@vital.com', 'Nava2026*', '2026-04-26 01:32:50'),
(5, 3, 'JONATHAN ALFREDO', NULL, NULL, NULL, 'paciente3@vital.com', 'Nava2026*', '2026-04-26 20:58:46'),
(6, 3, 'hola', 'holaaa', NULL, NULL, 'paciente4@vital.com', 'Nava2026*', '2026-04-26 21:14:47'),
(7, 3, 'Maria contreras', 'lopez', NULL, NULL, 'paciente5@vital.com', 'Nava2026*', '2026-04-29 01:04:55');
```