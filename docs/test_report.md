| Inicio de Sesión                                                                                                  |
| ----------------------- | ------------------------------------------------------------------------------------------------------ |
| **Caso**                | Inicio de sesión válido                                                                                |
| **Entrada**             | Correo y contraseña correctos                                                                          |
| **Esperado y Obtenido** | Esperado: Acceso permitido al sistema y redirección a la pantalla principal del paciente.<br>Obtenido: El sistema verifica el acceso. El acceso es valido e ingresa al sistema en la pantalla principal. |
| **Estado**              | Correcto                                                                                             |

| Registro de Cita y Validación de Horario                                                                                                     |
| ----------------------- | --------------------------------------------------------------------------------------------------------- |
| **Caso**                | Registro de cita válida                                                                                   |
| **Entrada**             | Cita creada. disponible                                                                       |
| **Esperado y Obtenido** | Esperado: Se crea una cita exitosamente. El horario cambia a no disponible.<br>Obtenido:  La cita es creada de forma correcta. El horario seleccionado cambia de disponible a ocupado al momento de crear la cita.   |
| **Estado**              | Correcto                                                                                                |

| Registro de Cita y Validación de Horario                                                           |
| ----------------------- | ----------------------------------------------------------------- |
| **Caso**                | Registro de cita en horario no disponible                         |
| **Entrada**             | Selección de un horario no disponible                    |
| **Esperado y Obtenido** | Esperado: El sistema bloquea el registro de la cita.<br>Obtenido: El sistema bloquea el horario y no permite crear una cita en un horario ocupado. |
| **Estado**              | Correcto                                                        |

| Registro de Cita y Validación de Horario                                                          |
| ----------------------- | --------------------------------------------------------------- |
| **Caso**                | Registro de cita en fecha pasada                                |
| **Entrada**             | Selección de un horario con una fecha y hora expirados.                   |
| **Esperado y Obtenido** | Esperado: El sistema no permite registrar la cita.<br>Obtenido: El sistema no interactua con horarios expirados. |
| **Estado**              | Correcto                                                      |

| Cancelación y Reprogramación.                                                                               |
| ----------------------- | ----------------------------------------------------------------------------------- |
| **Caso**                | Cancelación de cita                                                                 |
| **Entrada**             | Cita existente seleccionada para cancelar.                                           |
| **Esperado y Obtenido** | Esperado: Estado cambia a "Cancelado" y horario disponible nuevamente.<br>Obtenido: Se cancela la cita de forma correcta. En la consulta de horarios, el horario esta disponible después de cancelar la cita.|
| **Estado**              | Correcto                                                                          |

| Cancelación y Reprogramación.                                                                                                  |
| ----------------------- | ------------------------------------------------------------------------------------------------------ |
| **Caso**                | Reprogramación de cita                                                                                 |
| **Entrada**             | Cita existente seleccionada para reprogramar.                                                         |
| **Esperado y Obtenido** | Esperado: La cita original se cancela, queda disponible el horario y se crea una nueva cita.<br>Obtenido: No hay una función para reprogramar la cita.|
| **Estado**              | Incorrecto                                                                                             |

| Recordatorios                                                                       |
| ----------------------- | --------------------------------------------------------------------------- |
| **Caso**                | Generación de recordatorio.                                                  |
| **Entrada**             | Cita registrada.                                                             |
| **Esperado y Obtenido** | Esperado: Se crea registro de un recordatorio con fecha de envío.<br>Obtenido: El sistema almacena el recordatorio y se notifica que existe.|
| **Estado**              | Correcto                                                                  |

| Recordatorios                                                           |
| ----------------------- | ---------------------------------------------------------------- |
| **Caso**                | Envío de recordatorio.                                            |
| **Entrada**             | Recordatorio pendiente.                                           |
| **Esperado y Obtenido** | Esperado: Se envía el recordatorio para que se confirme la asistencia.<br>Obtenido: No se envía ningún recordatorio previo a la cita.|
| **Estado**              | Incorrecto                                                       |

| Confirmación de Cita                                                                      |
| ----------------------- | --------------------------------------------------------------------------- |
| **Caso**                | Confirmación de cita por paciente.                                           |
| **Entrada**             | Respuesta del paciente al recordatorio.                                      |
| **Esperado y Obtenido** | Esperado: Estado de cita cambia de "Pendiente" a "Confirmada".<br>Obtenido: Se confirma la asistencia, la página lo valida y el estado cambia.|
| **Estado**              | Correcto                                                                  |

| Sincronización                                                                          |
| ----------------------- | ------------------------------------------------------------------------------ |
| **Caso**                | Actualización de agenda.                                                        |
| **Entrada**             | Cambio en cita (registro, cancelación o confirmación).                          |
| **Esperado y Obtenido** | Esperado: Cambios visibles en menos de 5 segundos en la interfaz.<br>Obtenido: Al hacer cambios se actualiza la página y se refleja en menos de 5 segundos.|
| **Estado**              | Correcto                                                                     |

| Usabilidad.                                                        |
| ----------------------- | ------------------------------------------------------------ |
| **Caso**                | Registro de cita eficiente                                   |
| **Entrada**             | Flujo completo de agendamiento                               |
| **Esperado y Obtenido** | Esperado: El proceso no supera 5 interacciones.<br>Obtenido: Se crea una cita en menos de 5 interacciones.|
| **Estado**              | Correcto                                                   |

| Limitado para solo agendar citas                                                                           |
| ----------------------- | ------------------------------------------------------------------------------- |
| **Caso**                | Restricción de datos médicos.                                                    |
| **Entrada**             | Registro o edición de cita                                                      |
| **Esperado y Obtenido** | Esperado: No se permiten campos de diagnósticos médicos complejos.<br>Obtenido: No existe ningún apartado para insertar diagnósticos médicos.|
| **Estado**              | Correcto                                                                      |
