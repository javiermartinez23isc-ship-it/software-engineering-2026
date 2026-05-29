# Reporte de Pruebas - Sprint 5
**Entregable:** `docs/test_report_sprint5.md`

## 1. Caso de prueba y objetivo
Se ejecutaron 15 casos de prueba para validar el sistema de gestión médica:
| Caso | Descripcion | Datos de entrada | Resultado esperado | Estatus |
| :--- | :--- | :--- | :--- | :--- |
|CP-01: Inicio de sesión | Verificar inicio de sesión correcto. | Usuario y contraseña válidos. | Que el usuario pueda entrar al sistema. | Completado. |
|CP-02: Agendar citas | Validar la creación de una nueva cita médica. | Fecha y hora disponibles. | Se agenda la cita para el día y hora establecidos. | Completado. |
|CP-03: Traslape de horarios. | Evitar que se agenden dos citas a la misma hora. | Datos de cita en horario ya ocupado por otro paciente. | El sistema debe evitar el registro y mostrar que el horario no está disponible | Cpmpletado. |
|CP-04: Borrar historial | Verificar que el Personal pueda borrar una cita cancelada. | Selección de cita pendiente y dar clic en borrar. | Poder eliminar una cita de un paciente que haya cancelado.  | Completado |
|CP-05: Cancelación de Citas. | Validar que el paciente pueda liberar un espacio agendado. | ID de cita activa + clic en botón "Cancelar". | Cambio de estatus a "Cancelada" y liberación del horario en la agenda. | Completado. |
|CP-06: Reprogramar cita. | Verificar que el paciente pueda reprogramar una cita sin la necesidad de cancelar la ya creada. | Fecha y hora. | El usuario puede cambiar la fecha y hora de su cita. | Funcion mejorable |
|CP-07: Registro de Paciente Nuevo. | Verificar que el Personal pueda registrar un nuevo paciente. | Nombre, apellido y Correo electrónico. | Creación del perfil del paciente. | Datos inválidos.Los campos de nombre y apellido aceptan caracteres numéricos, lo cual no debería de permitir. |
|CP-08: Iniciar sesión al ser registrado como nuevo usuario. | Que al registrar un paciente este pueda acceder al sistema. | Usuario (correo) y contraseña. | El nuevo usuario puede iniciar sesión en su perfil. | Completado. |
|CP-09: Visualización de Agenda. | Comprobar que el Doctor vea sus citas programadas del día. | Inicio de sesión con cuenta de "Doctor". | Visualizar una lista de citas por atender. | Completado. |
|CP-10: Visualización correcta en dispositivos móviles. | Verificar que la interfaz se adapte correctamente en resoluciones móviles. | Acceso a la URL del sistema desde navegadores y cambio de resoluciones en herramientas de desarrollador (f12). | Resultado esperado: La interfaz mantiene su estructura sin deformaciones. | inompleta |
|CP-11: Acceso al historial médico desde el dashboard del doctor. | Verificar que el médico pueda abrir el historial desde la tabla de citas. | Clic en el botón o ícono de "Historial" dentro de la fila de una cita programada en la tabla de consultas del día. | El historial del paciente se muestra correctamente. | Completa |
|CP-12: Acceso al historial médico desde el asistente. | Verificar que el asistente pueda consultar historiales médicos. | Búsqueda de un paciente por nombre o ID y selección de la opción "Consultar historial médico". | El historial se visualiza correctamente sin errores. | Completado. |
|CP-13: Registro de nueva consulta médica. | Verificar que el doctor pueda guardar información médica en el historial. | Texto en campos de diagnóstico, observaciones médicas y tratamiento en el formulario de atención. | La consulta se almacena correctamente. | Funcion mejorable. |
|CP-14: Validación de campos vacíos. | Verificar que no se permitan registros médicos incompletos. | Intento de guardar una consulta dejando los campos obligatorios (como diagnóstico) sin información. | El sistema muestra mensajes de error y evita guardar datos inválidos. | Funcion mejorable. |
|CP-15: Comprobar funciones de rol. | Verificar que cada rol acceda únicamente a las funciones permitidas. | Credenciales de acceso para los distintos perfiles: Paciente, Doctor y Asistente. | Restricción correcta según permisos. | Completado. |


## 2. Evidencia:
  * [CP-01: Verificar inicio de sesion correcto.](Evidencia_Imagenes/evidencia1.png)
  * [CP-02: Validar la creacion de una nueva cita medica.](Evidencia_Imagenes/evidencia2.png)
  * [CP-03: Evitar que se agenden dos citas a la misma hora.](Evidencia_Imagenes/evidencia3.png)
  * [CP-04: Verificar que el Personal pueda borrar una cita como..](Evidencia_Imagenes/evidencia4.png)
  * [CP-05: Validar que el paciente pueda liberar un espacio agendado.](Evidencia_Imagenes/evidencia5.png)
  * [CP-06: Verificar que el paciente pueda reprogramar una cita sin la necesidad de cancelar la ya creada.](Evidencia_Imagenes/evidencia6.png)
  * [CP-07: Verificar que el Personal pueda registrar un nuevo paciente.](Evidencia_Imagenes/evidencia7.png)
  * [CP-08: Que al registrar un paciente este pueda acceder al sistema.](Evidencia_Imagenes/evidencia8.png)
  * [CP-09: Comprobar que el Doctor vea sus citas programadas del dia.](Evidencia_Imagenes/evidencia9.png)
  * [CP-10: Verificar que la interfaz se adapte correctamente en resoluciones moviles. ](Evidencia_Imagenes/evidencia10.png)
  * [CP-11: Verificar que el medico pueda abrir el historial desde la tabla de citas.](Evidencia_Imagenes/evidencia11.png)
  * [CP-12: Verificar que el asistente pueda consultar historiales medicos.](Evidencia_Imagenes/evidencia12.png)
  * [CP-13: Verificar que el doctor pueda guardar informacion medica en el historial.](Evidencia_Imagenes/evidencia13.png)
  * [CP-14: Verificar que no se permitan registros medicos incompletos.](Evidencia_Imagenes/evidencia14.png)
  * [CP-15: Verificar que cada rol acceda unicamente a las funciones permitidas.](Evidencia_Imagenes/evidencia15.png)


## 3. Error detectado y corrección aplicada
Se aplicaron correcciones exitosas en los siguientes puntos detectados:
1.	El apartado de configuración: al entrar en configuración y realizar un cambio mínimo y guardar los cambios el sistema muestra una ventanilla de error porque pide obligatoriamente la contraseña, como si el usuario deseara cambiarla.
  **Corrección aplicada.**
2.	 No deja cambiar la foto de perfil.
  **Corrección aplicada.**
3.	Se detectó una falta de restricción de tipos de datos en los campos que deberían ser exclusivamente alfabéticos. 
  **Corrección aplicada.**
4.	El asistente no puede registrar a un nuevo paciente con su número telefónico, se tiene que agregar ese campo a la hora del registro.
	**Corrección aplicada.**
5.	Se debe de restringir la configuración de datos claves del paciente, como nombre, apellidos, correo electrónico. Dejando exclusivamente el cambio de contraseña y número telefónico.
	**Corrección aplicada.**
6.	Se observo que sería más viable relacionar la cita, con el historial médico para que, a la hora de finalizar la cita, se genere el historial médico, ya que se pueden agregar varios historiales médicos sin la necesidad de tener una consulta, provocando discrepancias con la información.
	**Corrección aplicada.**
7.	El sistema permite el registro de fechas anteriores a la fecha actual.
	Corrección aplicada.**
8.	agende una cita el lunes y me la marco como el cuatro y hoy es miércoles
	**Corrección aplicada.**
9.	La funcionalidad de “olvidaste tu contraseña” no funciona.
	**Corrección aplicada.**
10.	No permite modificar el historial médico.
	**Corrección aplicada.**


## 4. Prueba de no regresión
Pruebas de no regresión en inicio de sesión:
1.	El login sigue en manteniendo su estructura, no pide llenar un campo nuevo, mantiene el inicio de sesión con el correo y contraseña del usuario.
2.	Los roles siguen establecidos como paciente, doctor y asistente. 
Pruebas de no regresión en rol Paciente:
3.	El paciente puede registrar sus citas siguiendo el mismo procedimiento ya establecido, sin ningún problema. 
4.	La función de bloqueo al intentar crear una cita nueva al ya contar con una en espera se sigue manteniendo.
5.	El paciente tiene permitido configurar su información.
Pruebas de no regresión en rol Doctor:
6.	Se agrego el historial médico, el cual no interfiere en las funciones antiguas del sistema.
7.	El doctor mantiene la función de marcar una cita como finalizada o marcarla como falta, esta función se actualizo agregando una relación entre una cita con el historial médico, esta nueva implementación no interfiere en el funcionamiento de las demás.
8.	La interfaz del bloqueo de horarios se modificó el diseño de la tabla, pero se mantiene funcionando de la misma manera.
Pruebas de no regresión en rol asistente:
9.	El asistente visualiza el historial de citas que están en espera, la función se mantiene igual, solo se agregó el historial del paciente a la tabla.
10.	Las funciones de agregar paciente y registrar una cita al paciente, siguen manteniendo su funcionalidad igual. La única mejora es restringir el tipo de dato que se puede ingresar en los apartados alfabéticos. Tambien hay que agregar el err


## 5. Conclusión de aptitud para liberación
Tras la ejecución de los 15 casos de prueba del Sprint 5 y la posterior confirmación de la resolución de todos los incidentes reportados, se llegó a la siguiente conclusión:
**Cumplimiento de Objetivos: El sistema ha superado satisfactoriamente el 100% de las pruebas críticas, incluyendo el inicio de sesión, el agendado de citas y la gestión de historiales médicos.** 
**Resolución de Errores: Se confirma que los errores detectados anteriormente han sido corregidos y validados con éxito.** 
**Integridad del Sistema: Las pruebas de no regresión demuestran que las nuevas funcionalidades se integraron sin afectar la estabilidad de los módulos existentes para los roles de Paciente, Doctor y Asistente.**

## Veredicto Final: El software demuestra estabilidad y cumple con los criterios de aceptación definidos. Por lo tanto, el sistema se declara totalmente completado.
