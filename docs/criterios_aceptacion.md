# Criterios de Aceptación - Sprint 3: Gestión de Agenda Vital

**Rol:** Analista  
**Proyecto:** Sistema de Gestión de Citas Médicas  
**Estado:** Refinado para MVP (Mínimo Producto Viable) - Versión Final Sprint 3

---

## 1. Gestión de Citas (RF-01, RF-03)

### Criterio 1.1: Registro de Cita y Validación de Horario
- **Descripción:** El sistema debe permitir agendar una cita vinculando un `Usuario` (Paciente) con un `Horario` específico.
- **Validación Técnica:** - Consultar la tabla `Horario` para verificar que `disponible == true` antes de la inserción.
  - El sistema debe bloquear registros en fechas pasadas o fuera de la jornada laboral (RN-05).
- **Resultado Esperado:** - Inserción exitosa en la tabla `Cita` con `id_estado_cita` inicial en "Pendiente".
  - El campo `disponible` en la tabla `Horario` cambia automáticamente a `false`.

### Criterio 1.2: Reprogramación y Cancelación (RN-02, RN-03)
- **Descripción:** Al modificar una cita, el sistema debe gestionar la liberación del tiempo y la actualización del estado.
- **Acción:** - Si se cancela, el `id_estado_cita` cambia a "Cancelado".
  - Si se reprograma, la cita original cambia a "Cancelada por Reprogramación" y se crea una nueva entrada vinculada.
- **Resultado Esperado:** El `id_horario` de la cita original debe volver a `disponible == true` de forma inmediata.

---

## 2. Automatización de Recordatorios (RF-02)

### Criterio 2.1: Disparo y Registro de Notificaciones
- **Descripción:** Gestión de la entidad `Recordatorio` para asegurar el contacto con el paciente.
- **Validación:** Se debe crear un registro en la tabla `Recordatorio` vinculado a la `Cita` con la `fecha_envio` correspondiente.
- **Resultado Esperado:** El estado del recordatorio cambia de `Pendiente` a `Enviado` tras la ejecución del proceso automático.

### Criterio 2.2: Confirmación Automática (RN-01)
- **Descripción:** El estado de la cita debe actualizarse sin intervención manual cuando el paciente confirme desde el recordatorio.
- **Acción:** Al recibir la respuesta del paciente, el `id_estado_cita` en la tabla `Cita` cambia de "Pendiente" a "Confirmada".

---

## 3. Centralización y Alertas (RF-04, RF-05, RN-06)

### Criterio 3.1: Sincronización de Agenda en Tiempo Real
- **Descripción:** El personal médico debe ver la misma información actualizada en la pantalla principal.
- **Métrica:** Cualquier cambio en la base de datos (agendado, cancelación o confirmación) debe reflejarse en la pantalla del usuario en menos de 5 segundos.

### Criterio 3.2: Consulta de Historial de Citas
- **Descripción:** Capacidad de filtrar el registro de citas en la base de datos.
- **Acción:** El sistema debe permitir filtrar la tabla `Cita` por rango de fechas y estado (`Asistida`, `No asistida`, `Cancelada`, `Vencida`).

### Criterio 3.3: Indicador Visual de Citas No Confirmadas (Ajuste RN-06)
- **Descripción:** Identificación de citas que requieren atención manual por falta de respuesta del paciente sin usar pantallas adicionales.
- **Lógica:** Si la cita mantiene el estado "Pendiente" y faltan 12 horas o menos para la hora de inicio (`hora_inicio` en la tabla `Horario`), el sistema debe aplicar un **resaltado visual** (ej. cambio de color de fila o icono de alerta) en la fila correspondiente de la **Pantalla Principal**.
- **Resultado Esperado:** El asistente visualiza la alerta directamente en la agenda actual para proceder con la confirmación manual.

---

## 4. Usabilidad y Seguridad (RNF-01, RNF-02)

### Criterio 4.1: Eficiencia de la Interfaz (RC-01)
- **Métrica:** El flujo completo para registrar una nueva cita no debe superar las **5 interacciones** (clics o selecciones de campo).

### Criterio 4.2: Integridad y Protección de Datos (RD-01)
- **Acción:** La entidad `Usuario` debe manejar la `contrasena_hash` mediante algoritmos de cifrado.
- **Restricción (RN-04):** El sistema no debe permitir campos de texto para diagnósticos médicos complejos en la tabla `Cita` o registros asociados, limitándose a la gestión administrativa.