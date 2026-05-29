| Inicio de Sesión |
| Caso | Inicio de sesión válido |
| Entrada | Correo y contraseña correctos |
| Esperado | Esperado: Acceso permitido al sistema y redirección a la pantalla principal del paciente. |
| Obtenido | Obtenido: El sistema verifica el acceso. El acceso es válido e ingresa al sistema en la pantalla principal. |
| Estado | Correcto |

| Seguridad | 
| Caso | Identificar rol de usuario | 
| Entrada | Iniciar sesión ingresando credenciales válidas de Médico, Asistente y Paciente sucesivamente. | 
| Esperado | Esperado: El sistema identifica el rol y redirige al panel correcto (doctor, asistente, paciente). | 
| Obtenido | Obtenido: El sistema no está identificando correctamente las restricciones del rol. Si un paciente puede ver el panel del doctor, el sistema falla. | 
| Estado | Incorrecto | 

| Seguridad | 
| Caso | Acceso desde ventana de incógnito sin login | 
| Entrada | Copiar la URL pages/doctor.php e intentar entrar desde una ventana de incógnito sin hacer login. | 
| Esperado | Esperado: El sistema bloquea el acceso y redirige automáticamente a la pantalla de login. | 
| Obtenido | Obtenido: Aunque el sistema bloquea el acceso "sin hacer login", falla en proteger la privacidad entre diferentes tipos de usuarios registrados. | 
| Estado | Correcto | 

| Agendamiento | 
| Caso | Registro de cita disponible | 
| Entrada | Entrar como Paciente, seleccionar un horario "disponible" en la tabla y confirmar la cita. | 
| Esperado | Esperado: Muestra alerta de éxito, guarda la cita como "Pendiente" y el horario cambia a "Ocupado". | 
| Obtenido | Obtenido: El sistema agenda la cita de manera correcta y la guarda en la base de datos. | 
| Estado | Correcto | 

| Agendamiento | 
| Caso | Registro de cita con cita pendiente | 
| Entrada | Un Paciente con una cita ya en estado "Pendiente" intenta agendar otra en un horario distinto. | 
| Esperado | Esperado: El sistema muestra la alerta: "Lo sentimos, ya tienes una cita pendiente..." y bloquea la acción. | 
| Obtenido | Obtenido: El sistema muestra el mensaje claro y no permite la creación de otra cita. | 
| Estado | Correcto | 

| Agendamiento | 
| Caso | Colisión al agendar la misma cita | 
| Entrada | Dos usuarios distintos intentan confirmar una cita exactamente en el mismo horario al mismo tiempo. | 
| Esperado | Esperado: Solo el primero lo logra. Al segundo le aparece el error: "Este horario ya fue reservado". | 
| Obtenido | Obtenido: El sistema permite la inserción exitosa solo a un usuario, el cual fue el primero en agendarla. Al otro le muestra el mensaje de error. | 
| Estado | Correcto | 

| Operación | 
| Caso | Registro de nuevo paciente | 
| Entrada | El asistente registra un nuevo paciente llenando el formulario y haciendo clic en registrar. | 
| Esperado | Esperado: El paciente se guarda con rol 3, se asigna la contraseña provisional y redirige a la agenda sin errores. | 
| Obtenido | Obtenido: Se crea con éxito las credenciales del nuevo paciente para su inicio de sesión en el sistema. | 
| Estado | Correcto | 

| Interfaz (UI) | 
| Caso | Diseño responsivo | 
| Entrada | Abrir los paneles y reducir el tamaño de la ventana a formato celular (menos de 768px). | 
| Esperado | Esperado: El menú lateral se oculta, aparece el botón, y las tablas no se desbordan. | 
| Obtenido | Obtenido: No tiene diseño responsivo, al ser más chica la pantalla no aparece ningún botón de menú y se ve todo desordenado. | 
| Estado | Incorrecto | 

| Registro de Cita y Validación de Horario |
| Caso | Registro de cita en horario no disponible |
| Entrada | Selección de un horario no disponible |
| Esperado | Esperado: El sistema bloquea el registro de la cita. |
| Obtenido | Obtenido: El sistema bloquea el horario y no permite crear la cita. |
| Estado | Correcto |

| Registro de Cita y Validación de Horario |
| Caso | Registro de cita en fecha pasada |
| Entrada | Selección de fecha y hora expiradas |
| Esperado | Esperado: El sistema no permite registrar la cita. |
| Obtenido | Obtenido: El sistema permite agendar una cita en fechas expiradas. |
| Estado | Incorrecto |

| Cancelación y Reprogramación |
| Caso | Cancelación de cita |
| Entrada | Cita existente seleccionada |
| Esperado | Esperado: Estado cambia a "Cancelado" y horario disponible nuevamente. |
| Obtenido | Obtenido: La cita se cancela correctamente y el horario vuelve a estar disponible. |
| Estado | Correcto |

| Cancelación y Reprogramación |
| Caso | Reprogramación de cita |
| Entrada | Cita existente seleccionada |
| Esperado | Esperado: Se cancela la cita original y se crea una nueva. |
| Obtenido | Obtenido: No existe función para reprogramar la cita. |
| Estado | Incorrecto |

| Recordatorios |
| Caso | Generación de recordatorio |
| Entrada | Cita registrada |
| Esperado | Esperado: Se genera un recordatorio con fecha de envío. |
| Obtenido | Obtenido: El sistema almacena el recordatorio. |
| Estado | Correcto |

| Recordatorios |
| Caso | Envío de recordatorio |
| Entrada | Recordatorio pendiente |
| Esperado | Esperado: Se envía el recordatorio antes de la cita. |
| Obtenido | Obtenido: No se envía ningún recordatorio. |
| Estado | Incorrecto |

| Confirmación de Cita |
| Caso | Confirmación de cita por paciente |
| Entrada | Respuesta del paciente |
| Esperado | Esperado: Estado cambia de "Pendiente" a "Confirmada". |
| Obtenido | Obtenido: No existe la opcion para confirmar la cita. |
| Estado | Incorrecto |

| Sincronización |
| Caso | Actualización de agenda |
| Entrada | Cambio en cita |
| Esperado | Esperado: Cambios visibles en menos de 5 segundos. |
| Obtenido | Obtenido: Se reflejan en menos de 5 segundos. |
| Estado | Correcto |

| Usabilidad |
| Caso | Registro de cita eficiente |
| Entrada | Flujo completo |
| Esperado | Esperado: No más de 5 interacciones. |
| Obtenido | Obtenido: Se completa en menos de 5 interacciones. |
| Estado | Correcto |

| Restricciones del Sistema |
| Caso | Restricción de datos médicos |
| Entrada | Registro o edición de cita |
| Esperado | Esperado: No permitir diagnósticos médicos. |
| Obtenido | Obtenido: No existe apartado para diagnósticos. |
| Estado | Correcto |
