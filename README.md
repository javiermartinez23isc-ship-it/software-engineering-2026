# Nombre del proyecto: AgendaVital

## Descripción del Proyecto
**AgendaVital** es un sistema web diseñado para resolver el problema de gestión manual en los consultorios independientes. Nuestro objetivo principal es eliminar la desorganización, los procesos tardados y la poca eficiencia, permitiendo una gestión de citas médicas ágil, automatizada y accesible desde cualquier dispositivo con conexión a internet.

---

## Tecnologías Usadas y Arquitectura
* **Frontend:** HTML, CSS y JavaScript (implementando diseño responsivo para correcta visualización en web y móviles).
* **Backend:** PHP.
* **Base de Datos:** MySQL.
* **Entorno de desarrollo:** XAMPP.

### Arquitectura: 
Arquitectura de capas cliente-servidor. El tipo de arquitectura seleccionado para este proyecto es uno conformado por 3 capas de tipo abierto.

* **Capa 1: Presentación.** Aquí el paciente o el personal del consultorio interactúa con la interfaz del sistema. Esta capa se encarga de mostrar la información y permitir la interacción con el sistema.
  * **Ejemplos:** Mostrar formularios para registrar pacientes, permitir agendar, consultar y cancelar citas, mostrar horarios disponibles, validar datos básicos.
  * **Tecnologías:** HTML, CSS, JavaScript.

* **Capa 2: Lógica de negocios.**
En esta capa se procesan las solicitudes del sistema y se aplican las reglas del negocio.
  * **Ejemplos:** Verificar disponibilidad de horarios, evitar citas duplicadas, validar que los datos del paciente sean correctos, gestionar estados de las citas (programada, cancelada, atendida), procesar solicitudes que vienen de la capa de presentación.
  * **Tecnologías:** PHP.

* **Capa 3: Capa de acceso de datos.**
Aquí se gestionan las operaciones CRUD. En esta capa se gestiona la comunicación con la base de datos donde se almacena la información del sistema.
  * **Ejemplos:** Guardar información de pacientes, registrar citas médicas, consultar horarios médicos disponibles, actualizar y eliminar registros, conectarse con la base de datos.
  * **Tecnologías:** MySQL.

---

## Instrucciones para Ejecutar Localmente

Para correr este proyecto en un entorno de desarrollo local, sigue estos pasos:

**Paso 1: Descargar el proyecto**
* Ve a la página principal de este repositorio en GitHub: [https://github.com/javiermartinez23isc-ship-it/software-engineering-2026.git](https://github.com/javiermartinez23isc-ship-it/software-engineering-2026.git).
* Haz clic en el botón verde que dice `<> Code`.
* Selecciona la opción **"Download ZIP"**.

**Paso 2: Extraer los archivos**
* Una vez descargado el archivo `.zip`, búscalo en tu computadora (generalmente en la carpeta de Descargas).
* Haz clic derecho sobre el archivo y selecciona **"Extraer todo"**.
* Esto creará una carpeta normal con todos los archivos del proyecto.

**Paso 3: Abrir el proyecto**
* Simplemente abre la carpeta que acabas de extraer y haz doble clic sobre el archivo `login.html`. Esto abrirá el sistema directamente en tu navegador web predeterminado (Chrome, Edge, Safari, etc.).

**Paso 4: Navegación por el sistema**
Una vez en la pantalla de inicio de sesión (`login.html`), puedes interactuar con el sistema ingresando los siguientes usuarios de prueba:
* Escribe **doctor** en el campo de usuario para acceder a la vista del médico (`doctor.html`).
* Escribe **asistente** para acceder a la vista de recepción (`asistente.html`).
* Escribe **cualquier otro nombre** para acceder a la vista de pacientes (`paciente.html`).

---

## Flujo Principal Implementado
El flujo estructurado para agendar una cita es el siguiente:

1. **Acceso:** El paciente ingresa a la página web del consultorio.
2. **Consulta de disponibilidad:** El sistema carga la agenda y muestra visualmente los días y las horas disponibles del médico.
3. **Reserva:** El paciente selecciona un horario que le convenga y llena un formulario corto para solicitar la cita.
4. **Actualización y Notificación:** El sistema guarda la cita en la base de datos, bloquea ese horario (lo marca como no disponible) y envía un recordatorio automático.
5. **Gestión interna:** El personal del consultorio (doctor o asistente) inicia sesión en su panel, visualiza la cita recién creada y espera a la confirmación de asistencia del paciente.

---

## Estado Actual de Desarrollo
Actualmente, el sistema se encuentra en la fase de desarrollo. Durante este estado, hemos trabajado las pantallas de la interfaz funcionando correctamente y completando el flujo principal del usuario (paciente).
Diseñamos y creamos la base de datos para el sistema y realizamos el codigo necesario en php para el buen funcionamiento del sistema.


---

## Usuarios del Sistema
* **Pacientes:** Personas que buscan agendar y gestionar sus citas de forma remota.
* **Personal del consultorio (Doctor y asistente):** Encargados de administrar la agenda, tiempos y pacientes.

---

## Alcance del Proyecto

**Qué SÍ hace el sistema:**
* Desarrollo de un sistema web accesible.
* Visualizar una agenda mostrando días y horas disponibles para una cita médica.
* Registrar, editar y eliminar citas médicas.
* Confirmar, cancelar y reprogramar citas.
* Enviar recordatorios automáticos a los pacientes.
* Llevar un control y seguimiento del estado de las citas (asistidas, no asistidas, canceladas y reprogramadas).
* Definir roles básicos del sistema para pacientes y personal del consultorio (doctor y asistente).
* Organizar los horarios disponibles y la duración estimada de las consultas.

**Qué NO hace el sistema:**
* Gestión de expedientes clínicos electrónicos.
* Diagnóstico médico o atención clínica.
* Sistemas de emergencias médicas.
* Plataformas hospitalarias complejas.
* Facturación médica avanzada o sistemas financieros completos.
* Redes sociales médicas o comunicación médica especializada.
* Sustitución del criterio profesional del médico.
* Aplicación móvil y de escritorio nativa (es un sistema estrictamente Web).

---

## Equipo y Roles: "Los Navitas"
* **Coordinador:** Fernando.
* **Analista:** Jonathan.
* **Diseñador:** Jorge.
* **Tester/QA:** Javier.
* **Desarrollador:** Jeshaias.

---

##Estado del proyecto: 
**Sprint 3** – Desarrollo.

