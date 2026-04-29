| Inicio de Sesión | <br>| Caso | Inicio de sesión válido |
<br>| Entrada | Correo y contraseña correctos |
<br>| Esperado | Esperado: Acceso permitido al sistema y redirección a la pantalla principal del paciente. |
<br>| Obtenido | Obtenido: El sistema verifica el acceso. El acceso es válido e ingresa al sistema en la pantalla principal. |
<br>| Estado | Correcto |

| Registro de Cita y Validación de Horario | <br>| Caso | Registro de cita válida |
<br>| Entrada | Cita creada, disponible |
<br>| Esperado | Esperado: Se crea una cita exitosamente. El horario cambia a no disponible. |
<br>| Obtenido | Obtenido: La cita es creada correctamente. El horario cambia de disponible a ocupado. |
<br>| Estado | Correcto |

| Registro de Cita y Validación de Horario | <br>| Caso | Registro de cita en horario no disponible |
<br>| Entrada | Selección de un horario no disponible |
<br>| Esperado | Esperado: El sistema bloquea el registro de la cita. |
<br>| Obtenido | Obtenido: El sistema bloquea el horario y no permite crear la cita. |
<br>| Estado | Correcto |

| Registro de Cita y Validación de Horario | <br>| Caso | Registro de cita en fecha pasada |
<br>| Entrada | Selección de fecha y hora expiradas |
<br>| Esperado | Esperado: El sistema no permite registrar la cita. |
<br>| Obtenido | Obtenido: El sistema no interactúa con horarios expirados. |
<br>| Estado | Correcto |

| Cancelación y Reprogramación | <br>| Caso | Cancelación de cita |
<br>| Entrada | Cita existente seleccionada |
<br>| Esperado | Esperado: Estado cambia a "Cancelado" y horario disponible nuevamente. |
<br>| Obtenido | Obtenido: La cita se cancela correctamente y el horario vuelve a estar disponible. |
<br>| Estado | Correcto |

| Cancelación y Reprogramación | <br>| Caso | Reprogramación de cita |
<br>| Entrada | Cita existente seleccionada |
<br>| Esperado | Esperado: Se cancela la cita original y se crea una nueva. |
<br>| Obtenido | Obtenido: No existe función para reprogramar la cita. |
<br>| Estado | Incorrecto |

| Recordatorios | <br>| Caso | Generación de recordatorio |
<br>| Entrada | Cita registrada |
<br>| Esperado | Esperado: Se genera un recordatorio con fecha de envío. |
<br>| Obtenido | Obtenido: El sistema almacena el recordatorio. |
<br>| Estado | Correcto |

| Recordatorios | <br>| Caso | Envío de recordatorio |
<br>| Entrada | Recordatorio pendiente |
<br>| Esperado | Esperado: Se envía el recordatorio antes de la cita. |
<br>| Obtenido | Obtenido: No se envía ningún recordatorio. |
<br>| Estado | Incorrecto |

| Confirmación de Cita | <br>| Caso | Confirmación de cita por paciente |
<br>| Entrada | Respuesta del paciente |
<br>| Esperado | Esperado: Estado cambia de "Pendiente" a "Confirmada". |
<br>| Obtenido | Obtenido: Se confirma la asistencia y el estado cambia correctamente. |
<br>| Estado | Correcto |

| Sincronización | <br>| Caso | Actualización de agenda |
<br>| Entrada | Cambio en cita |
<br>| Esperado | Esperado: Cambios visibles en menos de 5 segundos. |
<br>| Obtenido | Obtenido: Se reflejan en menos de 5 segundos. |
<br>| Estado | Correcto |

| Usabilidad | <br>| Caso | Registro de cita eficiente |
<br>| Entrada | Flujo completo |
<br>| Esperado | Esperado: No más de 5 interacciones. |
<br>| Obtenido | Obtenido: Se completa en menos de 5 interacciones. |
<br>| Estado | Correcto |

| Restricciones del Sistema | <br>| Caso | Restricción de datos médicos |
<br>| Entrada | Registro o edición de cita |
<br>| Esperado | Esperado: No permitir diagnósticos médicos. |
<br>| Obtenido | Obtenido: No existe apartado para diagnósticos. |
<br>| Estado | Correcto |