# Scripts SQL — Agenda Vital

Ejecuta los bloques **en orden** en phpMyAdmin sobre la base de datos `agenda_vital`.

---

## 1. Tablas principales

```sql
CREATE TABLE `tipo_usuario` (
  `id_tipo_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_tipo` varchar(25) NOT NULL,
  PRIMARY KEY (`id_tipo_usuario`),
  UNIQUE KEY `nombre_tipo` (`nombre_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `estado_cita` (
  `id_estado_cita` int(11) NOT NULL AUTO_INCREMENT,
  `estado` varchar(20) NOT NULL,
  PRIMARY KEY (`id_estado_cita`),
  UNIQUE KEY `estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `estado` varchar(20) DEFAULT 'disponible',
  PRIMARY KEY (`id_horario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `foto_perfil` varchar(255) DEFAULT NULL,
  `contrasena_provisional` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `correo` (`correo`),
  KEY `id_tipo_usuario` (`id_tipo_usuario`),
  CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_tipo_usuario`) REFERENCES `tipo_usuario` (`id_tipo_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `cita` (
  `id_cita` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_horario` int(11) NOT NULL,
  `id_estado_cita` int(11) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `fecha_cancelacion` datetime DEFAULT NULL,
  `token_confirmacion` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id_cita`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_horario` (`id_horario`),
  KEY `id_estado_cita` (`id_estado_cita`),
  CONSTRAINT `cita_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`),
  CONSTRAINT `cita_ibfk_2` FOREIGN KEY (`id_horario`) REFERENCES `horario` (`id_horario`),
  CONSTRAINT `cita_ibfk_3` FOREIGN KEY (`id_estado_cita`) REFERENCES `estado_cita` (`id_estado_cita`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `recordatorio` (
  `id_recordatorio` int(11) NOT NULL AUTO_INCREMENT,
  `id_cita` int(11) NOT NULL,
  `fecha_envio` datetime NOT NULL,
  `enviado` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_recordatorio`),
  KEY `id_cita` (`id_cita`),
  CONSTRAINT `recordatorio_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `cita` (`id_cita`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `historial_medico` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `fecha_consulta` date NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `diagnostico` text NOT NULL,
  `tratamiento` text NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `configuracion_consultorio` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  PRIMARY KEY (`id_config`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reprogramacion` (
  `id_reprogramacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_cita_nueva` int(11) NOT NULL,
  `fecha_anterior` date NOT NULL,
  `hora_anterior` time NOT NULL,
  `fecha_nueva` date NOT NULL,
  `hora_nueva` time NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_reprogramacion`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_cita_nueva` (`id_cita_nueva`),
  CONSTRAINT `reprogramacion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`),
  CONSTRAINT `reprogramacion_ibfk_2` FOREIGN KEY (`id_cita_nueva`) REFERENCES `cita` (`id_cita`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## 2. Datos iniciales

```sql
INSERT INTO `tipo_usuario` (`id_tipo_usuario`, `nombre_tipo`) VALUES
(1, 'Doctor'),
(2, 'Asistente'),
(3, 'Paciente');

-- Estados de cita (NO modificar los IDs — el código depende de ellos)
INSERT INTO `estado_cita` (`id_estado_cita`, `estado`) VALUES
(1, 'Pendiente'),
(2, 'Finalizada'),
(3, 'Cancelada'),
(4, 'Confirmada'),
(5, 'No asistió');

INSERT IGNORE INTO `configuracion_consultorio` (`clave`, `valor`) VALUES
('nombre_consultorio', 'Consultorio Privado'),
('logo_consultorio', NULL);
```

---

## 3. Usuarios de prueba

> ⚠️ Contraseñas en texto plano — solo para entorno de desarrollo.

```sql
INSERT INTO `usuario` (`id_usuario`, `id_tipo_usuario`, `nombre`, `apellido_paterno`, `apellido_materno`, `telefono`, `correo`, `contrasena_hash`, `contrasena_provisional`) VALUES
(1, 1, 'Jesús Fernando', 'Rodríguez', 'Nava',     '8710000000', 'doctor@nava.com',     'Nava2026*',       0),
(2, 2, 'Jesahias Fernando', 'Juarez', 'Palacios', '8711112233', 'asistente@vital.com', 'Asistente2026*',  0),
(3, 3, 'Jorge Humberto', 'Esquivel', 'Cuellar',   '8714445566', 'paciente1@vital.com', 'Nava2026*',       0),
(4, 3, 'Jesús Javier',  'Martínez', 'Hernández',  '8717778899', 'paciente2@vital.com', 'Nava2026*',       0);
```

---

## Usuarios de acceso

| Rol       | Correo                | Contraseña     |
|-----------|-----------------------|----------------|
| Doctor    | doctor@nava.com       | Nava2026*      |
| Asistente | asistente@vital.com   | Asistente2026* |
| Paciente  | paciente1@vital.com   | Nava2026*      |
| Paciente  | paciente2@vital.com   | Nava2026*      |
