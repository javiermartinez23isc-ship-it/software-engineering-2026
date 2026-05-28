# Agenda Vital
### Sistema de Gestión de Citas Médicas

Agenda Vital es una plataforma web orientada a consultorios independientes para automatizar la programación de consultas médicas. Centraliza la información clínica y administrativa, facilitando la interacción entre médicos, asistentes y pacientes para optimizar el flujo de trabajo operativo.

---

## Tecnologías Utilizadas

| Capa | Tecnología |
|---|---|
| Backend | PHP (scripts dinámicos, sin framework) |
| Base de Datos | MySQL — puerto `3307` |
| Frontend | HTML5, CSS3, JavaScript (vanilla) |
| Servidor Local | XAMPP (Apache + MySQL) |
| Arquitectura | Tres capas: Presentación · Lógica · Datos |

---

## Estructura del Proyecto

```text
/AGENDAVITAL
├── /config                  # Conexión a la BD (db.php)
├── /database                # Scripts SQL de creación e inserción
├── /docs                    # Documentación, criterios de aceptación y reportes
├── /public/assets
│   ├── /css                 # Estilos por rol (doctor, asistente, paciente, login)
│   ├── /img                 # Logotipos, fotos de perfil e imágenes del sistema
│   └── /js                  # Lógica de validación y comportamiento frontend
├── /src                     # Lógica del sistema (backend)
│   ├── /appointments        # Agendar, cancelar, reprogramar y finalizar citas
│   ├── /auth                # Login, logout y recuperación de contraseña
│   ├── /consultorio         # Actualización de datos del consultorio
│   ├── /historial           # Agregar, editar y eliminar registros del historial médico
│   ├── /patients            # Registro de pacientes por el asistente
│   ├── /profile             # Actualización de perfil y foto
│   └── /schedule            # Gestión de horarios (bloqueo/desbloqueo por el doctor)
├── /views
│   ├── /auth                # Pantalla de Login
│   └── /roles               # Vistas específicas por rol
│       ├── doctor.php
│       ├── asistente.php
│       ├── paciente.php
│       ├── historial.php
│       └── registrar_historial_cita.php
└── index.php                # Punto de entrada principal
```

---

## Funcionalidades Implementadas

### 🩺 Módulo Doctor
- Visualización de la agenda semanal con calendario dinámico (semana actual + siguiente)
- Bloqueo y desbloqueo de horarios específicos
- Confirmación y finalización de citas
- Acceso al historial médico de cualquier paciente
- Registro de nuevas consultas en el historial
- Edición y eliminación de registros del historial médico
- Configuración de perfil: nombre, apellidos, teléfono, correo, foto y contraseña
- Configuración del consultorio: nombre y logotipo

### 🗂️ Módulo Asistente
- Visualización de todas las citas activas con estado
- Registro de nuevas citas para cualquier paciente
- Cancelación de citas desde la agenda
- Registro de nuevos pacientes con: nombre, apellido paterno, **apellido materno** (opcional), correo, teléfono y contraseña provisional
- Acceso al historial médico de pacientes
- Configuración de perfil personal

### 👤 Módulo Paciente
- Visualización de su cita activa con fecha, hora y estado
- Agendado de citas mediante calendario interactivo
- **Reprogramación de cita**: cancela la cita actual y permite seleccionar un nuevo horario en el mismo paso (el horario anterior queda bloqueado visualmente)
- Cancelación de cita activa
- Consulta de historial médico personal (solo lectura)
- Configuración de perfil: teléfono, foto y contraseña

### 🔐 Autenticación y Seguridad
- Login con validación de rol (doctor, asistente, paciente)
- Guards de sesión en todas las vistas y endpoints
- Protección contra acceso directo a rutas por rol incorrecto
- Contraseñas almacenadas como texto provisional (pendiente de migración a `password_hash`)
- Entradas sanitizadas con `mysqli_real_escape_string` y validaciones con `preg_match`
- Registro de pacientes con verificación de correo duplicado

---

## Instalación Paso a Paso

### 1. Preparar archivos
1. Descarga e instala [XAMPP](https://www.apachefriends.org/download.html)
2. Clona o descarga este repositorio
3. Coloca la carpeta dentro de: `C:\xampp\htdocs\AgendaVital`

### 2. Configurar puertos en XAMPP

**MySQL — cambiar a puerto 3307:**
1. En el Panel de XAMPP, fila MySQL → `Config` → `my.ini`
2. Busca `port=3306` y cámbiala por `port=3307` (aparece dos veces)
3. Guarda y cierra

**phpMyAdmin — apuntar al nuevo puerto:**
1. En la fila Apache → `Config` → `phpMyAdmin (config.inc.php)`
2. Busca el bloque `/* Bind to the localhost ipv4 address and tcp */`
3. Agrega o edita la línea:
   ```php
   $cfg['Servers'][$i]['host'] = '127.0.0.1:3307';
   ```

### 3. Importar la base de datos
1. Inicia Apache y MySQL desde el panel de XAMPP
2. Accede a `http://localhost/phpmyadmin/`
3. Crea una base de datos llamada `agenda_vital`
4. Abre el archivo `/database/scripts_sql.md`
5. Copia el contenido SQL, ve a la pestaña **SQL** en phpMyAdmin, pégalo y ejecuta

### 4. Ejecutar migraciones adicionales
Después del script principal, ejecuta también estos bloques que están al final de `scripts_sql.md`:

```sql
-- Agregar columna foto_perfil
ALTER TABLE `usuario` ADD COLUMN `foto_perfil` varchar(255) DEFAULT NULL AFTER `fecha_alta`;

-- Crear tabla de configuración del consultorio
CREATE TABLE IF NOT EXISTS `configuracion_consultorio` ( ... );
```

### 5. Acceder al sistema
Abre tu navegador y ve a:
```
http://localhost/AgendaVital/
```

---

## Usuarios de Prueba

| Rol | Usuario | Contraseña |
|---|---|---|
| Doctor | doctor@nava.com | Nava2026* |
| Asistente | asistente@vital.com | Asistente2026* |
| Paciente | paciente1@vital.com | Nava2026* |
| Paciente | paciente2@vital.com | Nava2026* |

---

## Estado del Proyecto

El sistema se encuentra en fase de desarrollo activo. Las funcionalidades principales están implementadas y operativas.

### ✅ Implementado
- Sistema de autenticación por roles
- Calendario dinámico de horarios (semana actual + siguiente)
- Gestión completa de citas: agendar, cancelar, reprogramar, confirmar y finalizar
- Historial médico: agregar, editar y eliminar registros
- Registro de pacientes con apellido materno
- Configuración de perfil con foto
- Configuración del consultorio (nombre y logo)
- Diseño responsive (mobile-first)

### 🔄 Pendiente / Mejoras futuras
- Migración de contraseñas a `password_hash` / `password_verify`
- Sistema de recordatorios automáticos por correo
- Recuperación de contraseña funcional vía email
- Cálculo de tasa de inasistencia
- Paginación en tablas con muchos registros
- Aplicación móvil nativa

---

## Equipo de Desarrollo

**Los Ángeles de Ruth** — Universidad / Ingeniería de Software 2026

| Nombre | Rol |
|---|---|
| Jesahias | Coordinador |
| Javier | Analista |
| Fernando | Diseñador |
| Jonathan | Tester / QA |
| Jorge | Desarrollador |
