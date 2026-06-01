**Inicio de Sesión**<br>
**Caso:** Inicio de sesión válido<br>
**Entrada:** Correo y contraseña correctos<br>
**Esperado:** Acceso permitido al sistema y redirección a la pantalla principal del paciente.<br>
**Obtenido:** El sistema verifica el acceso. El acceso es válido e ingresa al sistema en la pantalla principal.<br>
**Estado:** Correcto<br>
<br>

**Seguridad**<br>
**Caso:** Identificar rol de usuario<br>
**Entrada:** Iniciar sesión ingresando credenciales válidas de Médico, Asistente y Paciente sucesivamente.<br>
**Esperado:** El sistema identifica el rol y redirige al panel correcto (doctor, asistente, paciente).<br>
**Obtenido:** El sistema no está identificando correctamente las restricciones del rol. Si un paciente puede ver el panel del doctor, el sistema falla.<br>
**Estado:** Incorrecto<br>
<br>

**Seguridad**<br>
**Caso:** Acceso desde ventana de incógnito sin login<br>
**Entrada:** Copiar la URL pages/doctor.php e intentar entrar desde una ventana de incógnito sin hacer login.<br>
**Esperado:** El sistema bloquea el acceso y redirige automáticamente a la pantalla de login.<br>
**Obtenido:** Aunque el sistema bloquea el acceso "sin hacer login", falla en proteger la privacidad entre diferentes tipos de usuarios registrados.<br>
**Estado:** Correcto<br>
<br>

**Agendamiento**<br>
**Caso:** Registro de cita disponible<br>
**Entrada:** Entrar como Paciente, seleccionar un horario "disponible" en la tabla y confirmar la cita.<br>
**Esperado:** Muestra alerta de éxito, guarda la cita como "Pendiente" y el horario cambia a "Ocupado".<br>
**Obtenido:** El sistema agenda la cita de manera correcta y la guarda en la base de datos.<br>
**Estado:** Correcto<br>
<br>

**Agendamiento**<br>
**Caso:** Registro de cita con cita pendiente<br>
**Entrada:** Un Paciente con una cita ya en estado "Pendiente" intenta agendar otra en un horario distinto.<br>
**Esperado:** El sistema muestra la alerta: "Lo sentimos, ya tienes una cita pendiente..." y bloquea la acción.<br>
**Obtenido:** El sistema muestra el mensaje claro y no permite la creación de otra cita.<br>
**Estado:** Correcto<br>
<br>

**Agendamiento**<br>
**Caso:** Colisión al agendar la misma cita<br>
**Entrada:** Dos usuarios distintos intentan confirmar una cita exactamente en el mismo horario al mismo tiempo.<br>
**Esperado:** Solo el primero lo logra. Al segundo le aparece el error: "Este horario ya fue reservado".<br>
**Obtenido:** El sistema permite la inserción exitosa solo a un usuario, el cual fue el primero en agendarla. Al otro le muestra el mensaje de error.<br>
**Estado:** Correcto<br>
<br>

**Operación**<br>
**Caso:** Registro de nuevo paciente<br>
**Entrada:** El asistente registra un nuevo paciente llenando el formulario y haciendo clic en registrar.<br>
**Esperado:** El paciente se guarda con rol 3, se asigna la contraseña provisional y redirige a la agenda sin errores.<br>
**Obtenido:** Se crea con éxito las credenciales del nuevo paciente para su inicio de sesión en el sistema.<br>
**Estado:** Correcto<br>
<br>

**Interfaz (UI)**<br>
**Caso:** Diseño responsivo<br>
**Entrada:** Abrir los paneles y reducir el tamaño de la ventana a formato celular (menos de 768px).<br>
**Esperado:** El menú lateral se oculta, aparece el botón, y las tablas no se desbordan.<br>
**Obtenido:** No tiene diseño responsivo, al ser más chica la pantalla no aparece ningún botón de menú y se ve todo desordenado.<br>
**Estado:** Incorrecto<br>
<br>

**Registro de Cita y Validación de Horario**<br>
**Caso:** Registro de cita en horario no disponible<br>
**Entrada:** Selección de un horario no disponible<br>
**Esperado:** El sistema bloquea el registro de la cita.<br>
**Obtenido:** El sistema bloquea el horario y no permite crear la cita.<br>
**Estado:** Correcto<br>
<br>

**Registro de Cita y Validación de Horario**<br>
**Caso:** Registro de cita en fecha pasada<br>
**Entrada:** Selección de fecha y hora expiradas<br>
**Esperado:** El sistema no permite registrar la cita.<br>
**Obtenido:** El sistema permite agendar una cita en fechas expiradas.<br>
**Estado:** Incorrecto<br>
<br>

**Cancelación y Reprogramación**<br>
**Caso:** Cancelación de cita<br>
**Entrada:** Cita existente seleccionada<br>
**Esperado:** Estado cambia a "Cancelado" y horario disponible nuevamente.<br>
**Obtenido:** La cita se cancela correctamente y el horario vuelve a estar disponible.<br>
**Estado:** Correcto<br>
<br>

**Cancelación y Reprogramación**<br>
**Caso:** Reprogramación de cita<br>
**Entrada:** Cita existente seleccionada<br>
**Esperado:** Se cancela la cita original y se crea una nueva.<br>
**Obtenido:** No existe función para reprogramar la cita.<br>
**Estado:** Incorrecto<br>
<br>

**Recordatorios**<br>
**Caso:** Generación de recordatorio<br>
**Entrada:** Cita registrada<br>
**Esperado:** Se genera un recordatorio con fecha de envío.<br>
**Obtenido:** El sistema almacena el recordatorio.<br>
**Estado:** Correcto<br>
<br>

**Recordatorios**<br>
**Caso:** Envío de recordatorio<br>
**Entrada:** Recordatorio pendiente<br>
**Esperado:** Se envía el recordatorio antes de la cita.<br>
**Obtenido:** No se envía ningún recordatorio.<br>
**Estado:** Incorrecto<br>
<br>

**Confirmación de Cita**<br>
**Caso:** Confirmación de cita por paciente<br>
**Entrada:** Respuesta del paciente<br>
**Esperado:** Estado cambia de "Pendiente" a "Confirmada".<br>
**Obtenido:** No existe la opcion para confirmar la cita.<br>
**Estado:** Incorrecto<br>
<br>

**Sincronización**<br>
**Caso:** Actualización de agenda<br>
**Entrada:** Cambio en cita<br>
**Esperado:** Cambios visibles en menos de 5 segundos.<br>
**Obtenido:** Se reflejan en menos de 5 segundos.<br>
**Estado:** Correcto <br>
<br>

**Usabilidad**<br>
**Caso:** Registro de cita eficiente<br>
**Entrada:** Flujo completo<br>
**Esperado:** No más de 5 interacciones.<br>
**Obtenido:** Se completa en menos de 5 interacciones.<br>
**Estado:** Correcto <br>
<br>

**Restricciones del Sistema**<br>
**Caso:** Restricción de datos médicos<br>
**Entrada:** Registro o edición de cita<br>
**Esperado:** No permitir diagnósticos médicos.<br>
**Obtenido:** No existe apartado para diagnósticos.<br>
**Estado:** Correcto<br>