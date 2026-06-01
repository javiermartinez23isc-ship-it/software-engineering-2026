# Documento de Requerimientos

## Introducción

El sistema AgendaVital requiere un módulo de recordatorios de citas para pacientes. Al registrarse una cita (ya sea por el paciente o por el asistente), el sistema debe crear automáticamente un registro en la tabla `recordatorio` de la base de datos. La interfaz del paciente mostrará un apartado de notificaciones con el detalle de su cita agendada. Adicionalmente, el sistema emitirá una alerta en pantalla una hora antes de la cita, usando la zona horaria de México (America/Mexico_City). Esta funcionalidad es exclusiva de la vista del paciente; los roles de doctor y asistente no la verán.

Todo opera dentro del sistema web (sin notificaciones push externas ni correos electrónicos).

---

## Glosario

- **Sistema**: La aplicación web AgendaVital (PHP + MySQL sobre XAMPP).
- **Paciente**: Usuario con `id_tipo_usuario = 3`, que accede a `views/roles/paciente.php`.
- **Cita**: Registro en la tabla `cita` que vincula un `id_usuario` (paciente) con un `id_horario`.
- **Recordatorio**: Registro en la tabla `recordatorio` que almacena `id_cita`, `fecha_envio` (calculada como la fecha/hora de la cita menos 60 minutos) y el flag `enviado`.
- **Módulo_Recordatorio**: Componente del backend PHP responsable de crear y consultar registros en la tabla `recordatorio`.
- **Panel_Paciente**: La vista `views/roles/paciente.php`, exclusiva para usuarios con rol Paciente.
- **Notificación_Interna**: Elemento visual (recuadro/banner) renderizado dentro del Panel_Paciente, sin uso de servicios externos.
- **Zona_Horaria_México**: Zona horaria `America/Mexico_City` configurada en PHP mediante `date_default_timezone_set('America/Mexico_City')`.

---

## Requerimientos

### Requerimiento 1: Creación automática del recordatorio al agendar una cita

**User Story:** Como paciente o asistente, quiero que al agendar una cita se registre automáticamente un recordatorio, para que el sistema pueda notificarme en el momento oportuno sin que yo tenga que hacer nada adicional.

#### Criterios de Aceptación

1. WHEN una cita es insertada exitosamente en la tabla `cita` (desde cualquier flujo de agendado), THE Módulo_Recordatorio SHALL insertar un registro en la tabla `recordatorio` con `id_cita` igual al ID de la cita recién creada, `fecha_envio` calculada como la fecha y hora de inicio de la cita (campos `fecha` y `hora_inicio` del registro `horario` asociado) menos exactamente 3600 segundos en zona horaria `America/Mexico_City`, y `enviado = 0`.

2. IF la `fecha_envio` calculada resulta ser anterior a la fecha y hora actuales en zona horaria `America/Mexico_City`, THEN THE Módulo_Recordatorio SHALL insertar el registro de recordatorio de todas formas con `enviado = 0`, de modo que el sistema lo trate como un recordatorio ya vencido y no lo muestre como alerta activa.

3. IF la inserción del recordatorio en la tabla `recordatorio` falla por error de base de datos, THEN THE Módulo_Recordatorio SHALL registrar el error en el log del servidor (error_log de PHP) y la respuesta al usuario SHALL confirmar igualmente el agendado de la cita sin mostrar ningún mensaje de error relacionado con el recordatorio.

---

### Requerimiento 2: Visualización del recordatorio en el Panel del Paciente

**User Story:** Como paciente, quiero ver un apartado de notificaciones al ingresar a mi panel, para conocer de inmediato el detalle de mis citas agendadas.

#### Criterios de Aceptación

1. WHEN el Paciente accede al Panel_Paciente, THE Panel_Paciente SHALL ejecutar una consulta que una las tablas `recordatorio`, `cita` y `horario` para obtener las citas con `id_estado_cita IN (1, 4)` y con fecha de horario mayor o igual a la fecha actual en zona horaria `America/Mexico_City`, y SHALL mostrar un recuadro de notificación por cada resultado obtenido.

2. WHEN el recuadro de notificación es renderizado, THE Panel_Paciente SHALL mostrar el texto "Cita Agendada:" seguido de la fecha de la cita en formato `dd/mm/YYYY` y la hora de inicio en formato `HH:MM`.

3. WHILE el Paciente tiene al menos una cita activa futura, THE Panel_Paciente SHALL mantener visible el apartado de notificaciones en la interfaz sin necesidad de acción del usuario.

4. IF el Paciente no tiene citas activas futuras, THEN THE Panel_Paciente SHALL mostrar el mensaje "No tienes citas próximas." en el apartado de notificaciones.

5. THE Panel_Paciente SHALL mostrar el apartado de notificaciones únicamente a usuarios con `id_tipo_usuario = 3`; los roles Doctor (`id_tipo_usuario = 1`) y Asistente (`id_tipo_usuario = 2`) no verán este apartado.

6. IF la consulta a la base de datos para obtener las notificaciones falla, THEN THE Panel_Paciente SHALL mostrar el mensaje "No tienes citas próximas." en el apartado de notificaciones y SHALL registrar el error en el log del servidor.

---

### Requerimiento 3: Alerta en pantalla una hora antes de la cita

**User Story:** Como paciente, quiero recibir una alerta visual dentro del sistema una hora antes de mi cita, para no olvidar asistir a tiempo.

#### Criterios de Aceptación

1. IF existe un registro en la tabla `recordatorio` con `enviado = 0` y `fecha_envio` menor o igual a la hora actual del servidor en zona horaria `America/Mexico_City`, asociado a una cita del Paciente en sesión, THEN THE Panel_Paciente SHALL mostrar un modal o banner visible con el mensaje "Recordatorio. Queda una hora para tu cita."

2. WHEN el modal o banner de recordatorio es mostrado al Paciente, THE Sistema SHALL actualizar el campo `enviado = 1` en el registro correspondiente de la tabla `recordatorio`, de modo que la alerta no vuelva a mostrarse en recargas o verificaciones posteriores.

3. IF la actualización de `enviado = 1` falla por error de base de datos, THEN THE Sistema SHALL registrar el error en el log del servidor; la alerta ya mostrada al Paciente SHALL permanecer visible en esa sesión y el sistema SHALL reintentar la actualización en el siguiente ciclo de verificación.

4. WHILE el Paciente permanece en el Panel_Paciente con una sesión activa, THE Panel_Paciente SHALL verificar cada 60 segundos mediante una petición AJAX al endpoint `src/reminders/check_recordatorio.php` si existe algún recordatorio pendiente para el paciente en sesión.

5. IF el endpoint de verificación devuelve un código HTTP distinto de 200 o una respuesta no parseable como JSON, THEN THE Panel_Paciente SHALL omitir la alerta en ese ciclo y reintentar la verificación en el siguiente intervalo de 60 segundos sin mostrar ningún error al usuario.

6. THE Panel_Paciente SHALL mostrar la alerta de recordatorio únicamente al Paciente propietario de la cita; el endpoint SHALL filtrar los recordatorios usando exclusivamente el `id_usuario` almacenado en `$_SESSION['usuario_id']`.

---

### Requerimiento 4: Endpoint de consulta de recordatorios pendientes

**User Story:** Como sistema, necesito un endpoint PHP que devuelva si hay recordatorios pendientes para el paciente en sesión, para que el frontend pueda consultarlo periódicamente sin recargar la página.

#### Criterios de Aceptación

1. WHEN el endpoint `src/reminders/check_recordatorio.php` recibe una petición GET con una sesión de Paciente activa (`id_tipo_usuario = 3`), THE Sistema SHALL consultar la tabla `recordatorio` en modo solo lectura buscando registros con `enviado = 0` y `fecha_envio <= NOW()` asociados únicamente a citas cuyo `id_usuario` coincide con `$_SESSION['usuario_id']`.

2. WHEN el endpoint encuentra al menos un recordatorio pendiente, THE Sistema SHALL responder con el JSON `{"pendiente": true}` y código HTTP 200, con cabecera `Content-Type: application/json`.

3. WHEN el endpoint no encuentra recordatorios pendientes, THE Sistema SHALL responder con el JSON `{"pendiente": false}` y código HTTP 200, con cabecera `Content-Type: application/json`.

4. IF la petición al endpoint llega sin sesión activa, o con un usuario cuyo `id_tipo_usuario` es distinto de 3, o mediante un método HTTP distinto de GET, THEN THE Sistema SHALL responder con el JSON `{"error": "No autorizado"}` y código HTTP 401 sin ejecutar ninguna consulta a la base de datos.

5. IF la consulta a la base de datos falla, THEN THE Sistema SHALL responder con el JSON `{"error": "Error interno"}` y código HTTP 500, y SHALL registrar el detalle del error en el log del servidor sin exponer información técnica en la respuesta.

6. THE Sistema SHALL construir la consulta SQL del endpoint usando sentencias preparadas (prepared statements) con el valor de `$_SESSION['usuario_id']` como único parámetro, de modo que no sea posible inyectar un `id_usuario` diferente mediante parámetros de la petición HTTP.
