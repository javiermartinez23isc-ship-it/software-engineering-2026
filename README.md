Agenda Vital
Sistema de Gestión de Citas Médicas
Agenda Vital es una plataforma orientada a clínicas independientes con el fin de automatizar la programación de consultas médicas. El sistema facilita la interacción entre médicos, asistentes y pacientes, centralizando la información clínica y administrativa para optimizar el flujo de trabajo operativo.

Tecnologías Utilizadas
El proyecto se basa en un stack WAMP, garantizando compatibilidad y un despliegue ágil:

Backend: PHP (Scripts dinámicos).

Base de Datos: MySQL (Gestión de persistencia mediante puerto 3307).

Frontend: HTML5, CSS3 y JavaScript.

Servidor Local: XAMPP.

Arquitectura: Diseño de tres capas (Presentación, Lógica y Datos).

```text
/AGENDAVITAL
├── /config             # Conexión a la BD (db.php)
├── /docs               # Documentación y criterios de aceptación
├── /public/assets      # Recursos estáticos
│   ├── /css            # Estilos por roles (doctor, asistente, paciente)
│   ├── /img            # Logotipos e imágenes del sistema
│   └── /js             # Lógica de validación frontend
├── /src                # Lógica del sistema
│   ├── /appointments   # Gestión de citas (agendar, cancelar, finalizar)
│   ├── /auth           # Scripts de validación y logout
│   └── /patients       # Registro de pacientes
├── /views              # Interfaces de usuario
│   ├── /auth           # Pantalla de Login
│   └── /roles          # Vistas específicas según rol
└── index.php           # Punto de entrada principal
```

Instrucciones de Instalación (Paso a Paso)
1. Preparación de archivos
Descarga e instala XAMPP. https://www.apachefriends.org/download.html

Clona este repositorio y coloca la carpeta dentro de: C:\xampp\htdocs\agenda-vital.

2. Configuración de Puertos (XAMPP)
Para que el sistema funcione correctamente, debemos mover los servicios al puerto 3307.

A. Configurar MySQL:

En el Panel de Control de XAMPP en la fila de MySQL, haz clic en Config > my.ini.

Busca la línea port=3306 y cámbiala por port=3307 (aparece dos veces).

Guarda y cierra.

B. Configurar phpMyAdmin (Acceso a la BD):

En la fila de Apache, haz clic en el botón Config y selecciona phpMyAdmin (config.inc.php).

Busca el bloque de comentarios que dice: /* Bind to the localhost ipv4 address and tcp */.

Justo debajo de ese comentario, pega o edita la siguiente línea:
$cfg['Servers'][$i]['host'] = '127.0.0.1:3307';

Asegúrate de que la línea quede antes del cierre de los servidores para que el gestor reconozca el puerto correctamente.

3. Importar la Base de Datos
Inicia Apache y MySQL en el panel de XAMPP.

Accede a http://localhost/phpmyadmin/.

Crea una base de datos nueva llamada agenda_vital.

Abre el archivo scripts_sql que se encuentra dentro de la carpeta /database del proyecto.

Copia todo el contenido del archivo.

En phpMyAdmin, selecciona la base de datos agenda_vital, ve a la pestaña SQL, pega el código y presiona Continuar para ejecutarlo.

Usuarios de Prueba

Rol,Usuario,Contraseña
Doctor,doctor@nava.com,Nava2026*
Asistente,asistente@vital.com,Asistente2026*
Paciente,paciente1@vital.com,Nava2026*

Notas y Limitaciones
Estado del Proyecto: Actualmente nos encontramos en el Sprint 4.

Ajuste de Alcance: Debido a limitaciones de tiempo, se han eliminado funciones como el cálculo de la tasa de inasistencia.

Notificaciones: El sistema de correos automáticos no está implementado en esta versión.
