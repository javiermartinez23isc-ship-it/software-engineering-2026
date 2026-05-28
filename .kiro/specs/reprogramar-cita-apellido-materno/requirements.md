# Requirements Document

## Introduction

Este documento cubre dos mejoras al sistema **AgendaVital** (PHP + MySQL):

1. **Reprogramar Cita (vista del paciente):** Agregar un botón "Reprogramar Cita" junto al botón "Cancelar Cita" en la sección "Tu Cita Actual" de la vista del paciente. Al activarlo, la cita actual se elimina de la BD (liberando el horario), se muestra el calendario de disponibilidad con el horario anterior bloqueado, y el paciente puede seleccionar y confirmar un nuevo horario.

2. **Campo Apellido Materno en registro de paciente (vista del asistente):** Agregar el campo opcional "Apellido Materno" al formulario "Crear Cuenta de Paciente" en la vista del asistente, con validación y persistencia en la columna `apellido_materno` de la tabla `usuario`.

---

## Glossary

- **Sistema:** La aplicación web AgendaVital (PHP + MySQL).
- **Paciente:** Usuario con `id_tipo_usuario = 3` que accede a `views/roles/paciente.php`.
- **Asistente:** Usuario con `id_tipo_usuario = 2` que accede a `views/roles/asistente.php`.
- **Cita_Activa:** Registro en la tabla `cita` con `id_estado_cita IN (1, 4)` asociado al paciente.
- **Horario:** Registro en la tabla `horario` con campos `id_horario`, `fecha`, `hora_inicio`, `hora_fin`, `disponible`, `estado`.
- **Slot_Anterior:** El `id_horario`, `fecha` y `hora_inicio` de la cita que fue cancelada durante el flujo de reprogramación.
- **Panel_Reprogramar:** Sección de la vista del paciente que muestra el calendario de disponibilidad durante el flujo de reprogramación.
- **Calendario:** Tabla de horarios disponibles que cubre la semana actual y la siguiente (Lun–Vie, 9 a.m.–7 p.m.).
- **Cancelar_Cita:** Script `src/appointments/cancelar_cita.php` que libera el horario y elimina la cita de la BD.
- **Agendar_Cita:** Script `src/appointments/agendar_cita.php` que crea una nueva cita en la BD.
- **Registrar_Paciente:** Script `src/patients/registrar_paciente_asis.php` que inserta un nuevo usuario paciente en la BD.
- **Apellido_Materno:** Valor opcional de texto que representa el segundo apellido del paciente; se almacena en la columna `apellido_materno` de la tabla `usuario`.

---

## Requirements

### Requirement 1: Botón "Reprogramar Cita" en la vista del paciente

**User Story:** Como paciente, quiero poder reprogramar mi cita activa desde la sección "Tu Cita Actual", para cambiar mi horario sin tener que cancelar manualmente y luego buscar uno nuevo.

#### Acceptance Criteria

1. WHEN el Paciente tiene una Cita_Activa, THE Sistema SHALL mostrar el botón "Reprogramar Cita" junto al botón "Cancelar Cita" en la sección "Tu Cita Actual".
2. WHEN el Paciente no tiene una Cita_Activa, THE Sistema SHALL ocultar el botón "Reprogramar Cita".
3. THE Sistema SHALL diferenciar visualmente el botón "Reprogramar Cita" del botón "Cancelar Cita" mediante color distinto (por ejemplo, azul vs. rojo).

---

### Requirement 2: Cancelación inmediata de la cita al iniciar reprogramación

**User Story:** Como paciente, quiero que al hacer clic en "Reprogramar Cita" mi cita actual se elimine de inmediato, para que el horario quede libre mientras elijo uno nuevo.

#### Acceptance Criteria

1. WHEN el Paciente hace clic en "Reprogramar Cita", THE Sistema SHALL eliminar el registro de la Cita_Activa de la tabla `cita`.
2. WHEN el Paciente hace clic en "Reprogramar Cita", THE Sistema SHALL actualizar el estado del Horario asociado a `disponible` en la tabla `horario`, siempre que `disponible = 1`.
3. WHEN la eliminación de la cita falla en la BD, THE Sistema SHALL mostrar un mensaje de error al Paciente y mantener la cita sin cambios.

---

### Requirement 3: Mostrar el Panel_Reprogramar con el Slot_Anterior bloqueado

**User Story:** Como paciente, quiero ver el calendario de disponibilidad después de cancelar mi cita, con el horario anterior marcado como no seleccionable, para elegir un horario diferente.

#### Acceptance Criteria

1. WHEN la cita es eliminada exitosamente durante el flujo de reprogramación, THE Sistema SHALL mostrar el Panel_Reprogramar con el Calendario de horarios disponibles.
2. WHEN el Panel_Reprogramar está visible, THE Sistema SHALL renderizar el Slot_Anterior con estilo visual sombreado y sin permitir su selección (no clickeable).
3. WHILE el Panel_Reprogramar está activo, THE Sistema SHALL permitir al Paciente seleccionar cualquier celda disponible distinta al Slot_Anterior.
4. WHILE el Panel_Reprogramar está activo, THE Sistema SHALL mantener bloqueados los días pasados, fines de semana y horarios con `estado = 'ocupado'` o `disponible = 0`.

---

### Requirement 4: Confirmación y creación de la nueva cita

**User Story:** Como paciente, quiero confirmar un nuevo horario en el Panel_Reprogramar para que mi nueva cita quede registrada en el sistema.

#### Acceptance Criteria

1. WHEN el Paciente selecciona un horario disponible en el Panel_Reprogramar y hace clic en "Confirmar nueva cita", THE Sistema SHALL crear un nuevo registro en la tabla `cita` usando el script Agendar_Cita.
2. WHEN la nueva cita es creada exitosamente, THE Sistema SHALL redirigir al Paciente a `views/roles/paciente.php`.
3. WHEN el Paciente no ha seleccionado ningún horario en el Panel_Reprogramar, THE Sistema SHALL mantener el botón "Confirmar nueva cita" deshabilitado.
4. IF el horario seleccionado ya fue ocupado por otro usuario entre la selección y la confirmación, THEN THE Sistema SHALL mostrar un mensaje de error al Paciente e invitarlo a elegir otro horario.

---

### Requirement 5: Campo "Apellido Materno" en el formulario de registro de paciente

**User Story:** Como asistente, quiero poder registrar el apellido materno del paciente al crear su cuenta, para que el expediente del paciente esté completo desde el inicio.

#### Acceptance Criteria

1. THE Sistema SHALL mostrar el campo "Apellido Materno" en el formulario "Crear Cuenta de Paciente" de la vista del Asistente, ubicado después del campo "Apellido Paterno".
2. THE Sistema SHALL tratar el campo "Apellido Materno" como opcional; el formulario SHALL poder enviarse con el campo vacío.
3. WHEN el Asistente ingresa un valor en el campo "Apellido Materno", THE Sistema SHALL validar que el valor cumpla el patrón `/^[\p{L}\s\-]+$/u` (solo letras Unicode, espacios y guiones).
4. IF el valor del campo "Apellido Materno" no cumple el patrón de validación, THEN THE Sistema SHALL mostrar un mensaje de error al Asistente y no registrar al paciente.
5. WHEN el formulario es enviado con un valor válido o vacío en "Apellido Materno", THE Sistema SHALL guardar el valor en la columna `apellido_materno` de la tabla `usuario` usando el script Registrar_Paciente con escape seguro (`mysqli_real_escape_string`).
6. WHEN el formulario es enviado con el campo "Apellido Materno" vacío, THE Sistema SHALL guardar `NULL` o cadena vacía en la columna `apellido_materno`.
