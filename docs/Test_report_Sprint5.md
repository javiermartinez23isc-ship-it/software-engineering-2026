# Reporte de Pruebas - Sprint 5
**Entregable:** `docs/test_report_sprint5.md`

## 1. Caso de prueba y objetivo
Se ejecutaron 16 casos de prueba para validar el sistema de gestión médica:
| Caso | Descripcion | Datos de entrada | Resultado esperado | Estatus |
| :--- | :--- | :--- | :--- | :--- |
|CP-01: Inicio de sesión | Verificar inicio de sesión correcto. | Usuario y contraseña válidos. | Que el usuario pueda entrar al sistema. | Completado. |
|CP-02: | Inicio de sesión, agendado de citas y control de traslapes[cite: 2].
|CP-03: | Inicio de sesión, agendado de citas y control de traslapes[cite: 2].
|CP-04: | Gestión de estatus, cancelación y reprogramación de citas[cite: 2].
|CP-05: | Inicio de sesión, agendado de citas y control de traslapes[cite: 2].
|CP-06: ||Inicio de sesión, agendado de citas y control de traslapes[cite: 2].
|CP-07: | Inicio de sesión, agendado de citas y control de traslapes[cite: 2].
|CP-08: | Registro de pacientes nuevos y validación de sus perfiles[cite: 2].
|CP-09: | Inicio de sesión, agendado de citas y control de traslapes[cite: 2].
|CP-10: | Visualización de agenda del doctor[cite: 2].
|CP-11: | Adaptabilidad en dispositivos móviles[cite: 2].
|CP-12: | Acceso a historiales médicos (Doctor/Asistente), registro de consultas y funciones de rol[cite: 2].
|CP-13: | Inicio de sesión, agendado de citas y control de traslapes[cite: 2].
|CP-14: | Inicio de sesión, agendado de citas y control de traslapes[cite: 2].
|CP-15: | Inicio de sesión, agendado de citas y control de traslapes[cite: 2].
|CP-16: | Inicio de sesión, agendado de citas y control de traslapes[cite: 2].

## 2. Entrada utilizada
| Caso | Datos de entrada |
| :--- | :--- |
| CP-01 | [cite_start]Usuario y contraseña válidos[cite: 2]. |
| CP-02 | [cite_start]Fecha y hora disponibles[cite: 2]. |
| CP-03 | [cite_start]Datos de cita en horario ya ocupado[cite: 2]. |
| CP-08 | [cite_start]Nombre, apellido y correo electrónico[cite: 2]. |
| CP-11 | [cite_start]URL del sistema en navegadores móviles y herramientas F12[cite: 2]. |
| CP-12 | [cite_start]Clic en ícono de "Historial" en tabla de consultas del día[cite: 2]. |
| CP-14 | [cite_start]Texto en campos de diagnóstico y tratamiento[cite: 2]. |
| CP-16 | [cite_start]Credenciales de Paciente, Doctor y Asistente[cite: 2]. |

## 3. Resultado esperado vs. obtenido
* [cite_start]**Esperado**: El sistema debe procesar registros, bloquear traslapes y restringir accesos según el rol[cite: 2].
* [cite_start]**Obtenido**: Se agendaron citas y se validaron roles exitosamente[cite: 2]. [cite_start]Funciones marcadas como "mejorables" originalmente (CP-04, CP-07, CP-14, CP-15) fueron corregidas posteriormente[cite: 2, 77].

## 4. Estatus y evidencia
* [cite_start]**Estatus**: **TOTALMENTE COMPLETADO**[cite: 80].
* **Evidencia**:
  * [cite_start]![CP-01](evidencia1.png) [cite: 5]
  * [cite_start]![CP-02](evidencia2.png) [cite: 7]
  * [cite_start]*(Sigue la secuencia hasta evidencia16.png según tu documento)*[cite: 35].

## 5. Error detectado y corrección aplicada
[cite_start]Se aplicaron correcciones exitosas en los siguientes puntos detectados[cite: 36, 77]:
* [cite_start]**Configuración**: Error al pedir contraseña obligatoria para cambios mínimos[cite: 37, 38].
* [cite_start]**Perfil**: Fallo en cambio de foto y falta de restricción en campos alfabéticos[cite: 39, 40, 41, 42].
* [cite_start]**Registro**: Falta de campo telefónico y restricción de datos claves (nombre/correo)[cite: 43, 44, 45, 47].
* [cite_start]**Historial**: Desfase en fechas de agenda y falta de relación cita-historial[cite: 48, 49, 50, 51, 52, 53].
* [cite_start]**Acceso**: Fallo en "olvidaste tu contraseña" y modificación de historial[cite: 54, 55, 56, 57].

## 6. Prueba de no regresión
* [cite_start]**Login**: Mantiene estructura y roles de Paciente, Doctor y Asistente[cite: 59, 60, 61].
* [cite_start]**Roles**: El paciente sigue agendando con bloqueos de seguridad[cite: 63, 64]. [cite_start]El doctor y asistente integraron el historial médico sin afectar funciones previas[cite: 67, 71, 72].

## 7. Conclusión de aptitud para liberación
[cite_start]Tras resolver el 100% de los incidentes, el sistema demuestra estabilidad y cumple con los criterios de aceptación[cite: 75, 76, 79]. [cite_start]Por lo tanto, se declara **TOTALMENTE COMPLETADO** y apto para liberación[cite: 80].