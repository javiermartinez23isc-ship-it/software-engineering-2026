# Agenda Vital
### Sistema de Gestión de Citas Médicas

Agenda Vital es una plataforma web para consultorios independientes que automatiza la programación de consultas médicas. Centraliza citas, historial médico y administración de pacientes con tres roles: Doctor, Asistente y Paciente.

---

## Tecnologías

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.x (sin framework) |
| Base de Datos | MySQL — puerto `3307` |
| Frontend | HTML5, CSS3, JavaScript vanilla |
| Servidor local | XAMPP (Apache + MySQL) |
| Correo | PHPMailer 7.x + Gmail SMTP |

> **PHPMailer ya está incluido** en la carpeta `vendor/` del proyecto. No necesitas instalar Composer ni PHPMailer en el equipo destino — solo copia la carpeta completa `AgendaVital` incluyendo `vendor/`.

---

## Requisitos previos

- [XAMPP](https://www.apachefriends.org/download.html) instalado.
- visual studio code.
- tener instalado git. https://git-scm.com/install
- Navegador web: Cualquier moderno.
---

## Instalación paso a paso

## 1. Clonar el repositorio

Todo el proceso se hace desde **Visual Studio Code**.

### Clonar desde VS Code

1. copia el link del repositorio: https://github.com/javiermartinez23isc-ship-it/software-engineering-2026.git
2. Abre **Visual Studio Code**
3. En la pantalla principal selecciona la opcion "Clone Git Repository".
4. Pega la URL del repositorio anteriormente copiada.
5. Cuando te pida seleccionar una carpeta de destino, navega a:
```
C:\xampp\htdocs\
```
6. Haz clic en **Select as Repository Destination**
7. Espera a que termine la descarga
8. Cuando aparezca el mensaje **"Would you like to open the cloned repository?"**, haz clic en **Open**
9. Una vez clonado cambiaras de la rama "main" a la rama "desarrollo", mira la esquina inferior izquierda de la ventana de VS Code, verás que aparece el nombre de la rama actual (main).
10. Haz clic sobre ese nombre, se abrirá una lista en la parte superior de la pantalla con todas tus ramas locales y remotas.
11. Selecciona la rama "desarrollo" y cierra visual studio code.

12. ahora navega en el explorador de archivos a:
```
C:\xampp\htdocs\
```
13. Dentro de htdocs encontraras una carpeta llamada software-engineering-2026, renombrala a "AgendaVital", especificamente ese nombre y necesitarias cerrar visual para que te deje reenombrarlo.


### 2. Configurar el puerto de MySQL en XAMPP

Este proyecto usa el puerto **3307** en lugar del 3306 por defecto.

**Cambiar puerto en MySQL:**
1. Abre XAMPP → fila MySQL → `Config` → `my.ini` entrar como bloc de notas
2. Busca (para facilitar la busqueda utiliza el comando "ctrl + b") `port=3306` (aparece dos veces) y cámbiala a `port=3307`
3. Guarda y cierra

**Apuntar phpMyAdmin al nuevo puerto:**
1. XAMPP → fila Apache → `Config` → `phpMyAdmin (config.inc.php)` entrar como bloc de notas
2. Busca el bloque `/* Bind to the localhost ipv4 address */`
3. Agrega o edita esta línea:
```php
$cfg['Servers'][$i]['host'] = '127.0.0.1:3307'; (algunas veces solo se tiene que agregar ":3307")
```
4. Guarda y cierra
ahora cierra XAMPP.

### 3. Configurar la zona horaria en PHP

1. Vuelve a abrir **XAMPP Control Panel** como administrador
2. En la fila **Apache** dar clic en el apartado **Config**
3. Buscar la opción **PHP (php.ini)** y entrar como bloc de notas
4. Busca (para facilitar la busqueda utiliza el comando "ctrl + b") `date.timezone=Europe/Berlin` y cámbia a:
```ini
date.timezone=America/Mexico_City
```
5. En ese mismo archivo.
6. Busca (para facilitar la busqueda utiliza el comando "ctrl + b") `;extension=zip` y quítale el punto y coma:
```ini
extension=zip
```
7. En caso de no encontrarse, buscar ";extension=xsl" y  justo debajo de esta linea se debe agregar "extension=zip" sin comillas asi tal cual como se muestra.
8. Guarda el archivo. cierra el archivo.
9. vuelve a abrir XAMPP
10. Inicia los servicios: 
11. En el Panel de XAMPP, haz clic en **Start** en la fila de **Apache**
12. Haz clic en **Start** en la fila de **MySQL**
13. Ambos deben quedar en **verde** con sus puertos mostrados


## 4. Configurar las credenciales de base de datos

1. Dentro de la carpeta del proyecto ve a `config\`
2. Verás el archivo `db.example.php`
3. **Cópialo** y  pegalo dentro de la misma carpeta config y renombra la copia como `db.php` — debe quedar así:
```
config\
    ├── db.example.php   ← archivo original, no tocar
    └── db.php           ← tu copia con tus credenciales
```
4. Abre `db.php` y ajusta los valores según tu instalación:
1. $host = "localhost"; "se queda asi"
2. $port = "3307"; "se deja este puerto obligatoriamente"
3. $user = "root"; "Viene por defecto"
4. $pass = ""; "Viene sin contraseña por defecto"
5. $db   = "agenda_vital"; "Utilizar ese nombre obligatoriamente para la bd"

Si tu MySQL de XAMPP tiene contraseña, escríbela entre las comillas. Si no tiene contraseña (instalación por defecto), déjala vacía:
**Guarda** el archivo.

>  El usuario por defecto de XAMPP es `root` y normalmente no tiene contraseña. Solo cambia `$pass` si configuraste una contraseña diferente.


### 5. Crear la base de datos

1. Inicia Apache y MySQL desde el panel XAMPP 
2. En la fila MySQL selecciona **Admin** que abre la siguiente direción: `http://localhost/phpmyadmin/`
3. Crea una base de datos llamada `agenda_vital` en el panel izquierdo la opcion "Nueva" ahi la crearas.
4. una vez creada Seleccionala `agenda_vital` en el panel izquierdo
5. Ve a la pestaña **SQL**
6. ahora abre el explorador de archivos y Sigue la siguiente ruta en tu explorador de archivos: `C:\XAMPP\htdocs\AgendaVital\database`
7. Dentro de la ruta abre el archivo `base de datos` como bloc de notas
8. Copia todo el contenido y pégalo en el apartado de **SQL** que abriste en el paso 5.
9. Haz clic en **Continuar** y listo.

### 6. Acceder al sistema

Abre el navegador y dirígete a:
```
http://localhost/AgendaVital/views/auth/login.php
```
En dado caso de tener problemas con el puerto **Apache** usar esta dirección:
```
http://localhost:8080/AgendaVital/views/auth/login.php
```

---


## Usuarios de acceso

| Rol | Correo | Contraseña |
|---|---|---|
| Doctor | doctor@nava.com | Nava2026* |
| Asistente | asistente@vital.com | Asistente2026* |
| Paciente | paciente1@vital.com | Nava2026* |
| Paciente | paciente2@vital.com | Nava2026* |

- Podras ingresar con los usarios predeterminados, editar el nombre del doctor y asistente.
- También agregar logo a tu consultorio y su nombre en la configuración del consultorio en el panel de doctor.

---

## Funcionalidades implementadas

### 🩺 Doctor
- Agenda de citas con estado: ⏳ Pendiente / ✅ Confirmada / 🔄 Reprogramada
- Finalizar cita → registro **obligatorio** de historial médico (no se puede omitir)
- Marcar inasistencia
- Bloquear y desbloquear horarios específicos
- Historial médico de pacientes: visualizar, editar y eliminar registros
- Configuración de perfil (nombre, apellidos, teléfono, correo, foto, contraseña)
- Configuración del consultorio (nombre y logo)

### 🗂️ Asistente
- Ver agenda de citas activas con estado
- Registrar citas para pacientes (valida que no tenga cita activa previa)
- Cancelar citas
- Registrar nuevos pacientes con contraseña provisional
- Historial médico de pacientes (solo lectura)
- Configuración de perfil

### 👤 Paciente
- Ver su cita activa con fecha, hora y estado
- Agendar cita mediante calendario interactivo
  - Horarios y días pasados bloqueados automáticamente
  - Horas del día actual que ya pasaron también bloqueadas
- Cancelar cita
- Reprogramar cita (máximo **2 veces por día**)
- **Primer inicio de sesión fuerza cambio de contraseña provisional**
- Historial médico personal (solo lectura)
- Configuración de perfil: teléfono, foto y contraseña
- Campanita 🔔 de recordatorio con contador de tiempo real

### 🔔 Recordatorios automáticos por correo
- Al agendar una cita se crea automáticamente un recordatorio para **1 hora antes**
- El correo se envía al paciente con fecha, hora y estado de la cita
- Incluye botón **"Confirmar mi cita"** que funciona sin iniciar sesión (token único)
- El asunto indica cuándo es la cita: "hoy", "mañana" o "en X días"
- El envío se dispara automáticamente cuando cualquier usuario carga una página del sistema

### 🔐 Seguridad y automatismos
- Guards de sesión y rol en todas las rutas
- Cancelar cita cambia estado a Cancelada(3) — nunca borra el registro
- Auto-marcado de "No asistió"(5) para citas pasadas sin atender al cargar cualquier página
- Zona horaria de MySQL sincronizada con PHP en cada conexión

---

## Estructura del proyecto

```
AgendaVital\
├── .kiro\
│   └── specs\
├── backend\
│   ├── config\
│   ├── database\
│   └── server\
├── config\
│   └── db.php
├── database\
│   ├── models_assistant.sql
│   ├── models_doctor.sql
│   ├── models_home.sql
│   ├── models_patient.sql
│   ├── base de datos.txt
│   └── scripts_sql.md
├── docs\
│   ├── design\
│   ├── reports\
│   ├── requirements\
│   ├── user_manual\
│   └── criterios_aceptacion.md
├── fron\
│   ├── assistant\
│   ├── doctor\
│   ├── home\
│   └── patient\
├── public\
│   └── assets\
├── src\
│   ├── api\
│   ├── appointments\
│   ├── auth\
│   ├── consultorio\
│   ├── historial\
│   ├── notifications\
│   ├── patients\
│   ├── profile\
│   └── schedule\
├── vendor\
│   ├── composer\
│   ├── phpmailer\
│   └── autoload.php
├── views\
│   ├── auth\
│   └── roles\
├── .env
├── .env.example
├── composer.json
├── composer.lock
└── README.md
```

## Equipo de desarrollo

**Los Navitas** — Instituto Tecnológico Superior de San Pedro de Las Colonias / Ingeniería de Software 2026

| Nombre | Rol |
|---|---|
| Jesahias | Analista |
| Javier | Dev Líder |
| Fernando | Tester / QA |
| Jonathan | Diseñador |
| Jorge | Coordinador |
