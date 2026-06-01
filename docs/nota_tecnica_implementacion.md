# Nota Técnica de Implementación — Agenda Vital

**Documento:** `nota_tecnica_implementacion.md`  
**Implementación / Mejora:** Módulo de Historial Médico — Sistema de Gestión de Citas Médicas  
**Fecha de elaboración:** 28 de Mayo de 2026  
**Estado del sistema:** Desarrollo activo — Sprint final  
**Equipo:** Los Navitas

## 1. Resumen Ejecutivo

El módulo de Historial Médico fue implementado desde cero como una funcionalidad nueva dentro del sistema Agenda Vital. Antes de esta implementación, el sistema únicamente gestionaba citas (agendar, cancelar, confirmar) pero no tenía ningún mecanismo para registrar, consultar, editar ni eliminar el historial clínico de los pacientes.

La implementación cubre el ciclo completo de vida de un registro médico: creación al finalizar una cita, consulta por parte del doctor y del asistente, edición de registros existentes y eliminación con confirmación. Adicionalmente se implementó un flujo obligatorio de registro de historial al momento de finalizar una cita, garantizando que ninguna consulta quede sin documentar.

Durante el desarrollo se identificaron y corrigieron dos defectos críticos de lógica de negocio que impedían el correcto funcionamiento de la edición de registros.

---

## 2. Mejora Implementada

### 2.1 Contexto — Estado previo del sistema

Antes de esta implementación el sistema contaba con:
- Tabla `historial_medico` definida en la base de datos pero sin ninguna interfaz de usuario asociada
- Sin vistas para consultar el historial de un paciente
- Sin endpoints para insertar, editar ni eliminar registros
- Sin flujo que conectara la finalización de una cita con el registro de la consulta médica
- El paciente podía ver su historial en su propio panel pero la tabla siempre aparecía vacía

### 2.2 Alcance de la implementación

Se implementaron los siguientes componentes:

#### A. Vista de Historial Médico (`historial.php`)

Vista compartida entre el doctor y el asistente, accesible mediante `?id={id_paciente}`. Muestra:

- **Encabezado del paciente:** nombre completo, correo y teléfono
- **Formulario de nueva consulta** (solo visible para el doctor): campos fecha, motivo, diagnóstico y tratamiento con validación HTML5
- **Tabla de registros:** fecha, motivo, diagnóstico, tratamiento y columna de acciones (solo doctor)
- **Acciones por registro:** botón Editar (abre modal) y botón Eliminar (con confirmación)
- **Modal de edición:** formulario inline que se abre sobre la misma página sin recargar, precargado con los datos del registro seleccionado
- **Mensajes de retroalimentación:** alertas de éxito y error mediante parámetros GET (`?ok=guardado`, `?ok=editado`, `?ok=eliminado`, `?error=...`)
- **Control de acceso por rol:** el asistente solo puede consultar; el doctor puede agregar, editar y eliminar

#### B. Flujo de Registro Obligatorio al Finalizar Cita (`registrar_historial_cita.php`)

Vista intermedia que se activa cuando el doctor marca una cita como "Finalizada" desde su panel. El flujo es:

```
[doctor.php] → Finalizar Cita → [registrar_historial_cita.php]
→ Formulario obligatorio (motivo, diagnóstico, tratamiento)
→ POST → [agregar_historial.php]
→ INSERT historial_medico
→ redirect doctor.php
```

Características de este flujo:
- La fecha de consulta se toma automáticamente de la cita finalizada (no editable por el doctor)
- El formulario bloquea la salida del navegador si no se ha guardado (`beforeunload` event)
- El aviso en pantalla indica explícitamente que el paso es obligatorio
- Si el INSERT falla, redirige de vuelta al formulario con el mensaje de error

#### C. Endpoints de Backend

**`agregar_historial.php`**
- Acepta POST con: `id_paciente`, `fecha_consulta`, `motivo`, `diagnostico`, `tratamiento`, `id_cita` (opcional)
- Detecta si viene de una cita finalizada (`$desde_cita`) o del formulario del historial
- Redirige al destino correcto según el origen
- Validaciones: campos obligatorios, fecha no futura
- Sanitización: `mysqli_real_escape_string` en todas las entradas

**`editar_historial.php`**
- Acepta POST con: `id_historial`, `id_paciente`, `fecha_consulta`, `motivo`, `diagnostico`, `tratamiento`
- Verifica que el registro pertenece al paciente indicado (previene manipulación de IDs)
- Validaciones: campos obligatorios, fecha no futura
- Ejecuta UPDATE con doble condición (`id_historial AND id_usuario`)

**`eliminar_historial.php`**
- Acepta POST con: `id_historial`, `id_paciente`
- Verifica propiedad del registro antes de eliminar
- Ejecuta DELETE con doble condición
- Redirige con confirmación de éxito o mensaje de error

#### D. Correcciones de Defectos Críticos

Durante la implementación se identificaron y corrigieron dos defectos que impedían el funcionamiento correcto:

**Defecto 1 — Inline onclick con datos complejos (modal de edición)**

El botón Editar usaba `onclick="abrirEditar(..., json_encode($motivo), ...)"`. Cuando los campos de motivo, diagnóstico o tratamiento contenían comillas, saltos de línea u otros caracteres especiales, la sintaxis del atributo `onclick` se rompía, causando un error de JavaScript silencioso. El modal nunca se abría.

*Solución:* Se eliminaron los `onclick` inline. Los datos se almacenan en atributos `data-*` con `htmlspecialchars(..., ENT_QUOTES)`. El JavaScript usa event delegation con `document.addEventListener('click', ...)` y `e.target.closest('.btn-editar')` para leer los datos con `getAttribute()`.

**Defecto 2 — Validación de fecha invertida**

Tanto `agregar_historial.php` como `editar_historial.php` rechazaban fechas anteriores a hoy con la condición `$fecha_consulta < date('Y-m-d')`. Un historial médico es por definición un registro de consultas que ya ocurrieron, por lo que rechazar fechas pasadas era un error de lógica de negocio. El campo fecha en el modal también tenía `min="hoy"` que bloqueaba la selección de fechas pasadas en el navegador.

*Solución:* La condición se invirtió a `$fecha_consulta > date('Y-m-d')` (rechazar fechas futuras). El atributo del campo fecha cambió de `min` a `max`.

### 2.3 Flujo completo del módulo

```
DOCTOR
  │
  ├─ Desde panel doctor.php
  │    └─ Clic "👁️ Historial" en tabla de citas
  │         └─ GET historial.php?id={id_paciente}
  │              ├─ Ver tabla de registros
  │              ├─ Agregar nueva consulta → POST agregar_historial.php → redirect ?ok=guardado
  │              ├─ Editar registro → modal inline → POST editar_historial.php → redirect ?ok=editado
  │              └─ Eliminar registro → confirm() → POST eliminar_historial.php → redirect ?ok=eliminado
  │
  └─ Finalizar cita desde doctor.php
       └─ GET registrar_historial_cita.php?id_cita=X&id_paciente=Y
            └─ Formulario obligatorio → POST agregar_historial.php → redirect doctor.php

ASISTENTE
  └─ Clic "👁️ Historial" en tabla de citas
       └─ GET historial.php?id={id_paciente}
            └─ Solo lectura — sin formulario ni acciones

PACIENTE
  └─ Sección "Mi Historial" en paciente.php
       └─ Consulta directa a historial_medico WHERE id_usuario = {sesión}
            └─ Solo lectura — tabla sin acciones
```

---

## 3. Archivos Modificados / Creados

| Archivo | Estado | Descripción del cambio |
|---|---|---|
| `views/roles/historial.php` | **Creado** | Vista principal del historial médico — tabla de registros, formulario de nueva consulta, modal de edición, control de acceso por rol |
| `views/roles/registrar_historial_cita.php` | **Creado** | Vista de registro obligatorio al finalizar una cita — formulario con bloqueo de salida |
| `src/historial/agregar_historial.php` | **Creado** | Endpoint POST para insertar nuevos registros — maneja dos orígenes (historial directo y cita finalizada) |
| `src/historial/editar_historial.php` | **Creado + Corregido** | Endpoint POST para actualizar registros — corrección de validación de fecha |
| `src/historial/eliminar_historial.php` | **Creado** | Endpoint POST para eliminar registros con verificación de propiedad |
| `views/roles/doctor.php` | **Modificado** | Agregado enlace "👁️ Historial" por paciente en la tabla de citas y botón "Finalizar Cita" que redirige a `registrar_historial_cita.php` |
| `views/roles/asistente.php` | **Modificado** | Agregado enlace "👁️ Historial" por paciente en la tabla de citas |
| `views/roles/paciente.php` | **Modificado** | Sección "Mi Historial" conectada a la tabla `historial_medico` real (antes mostraba tabla vacía) |

---

## 4. Impacto Técnico

### Base de datos

La tabla `historial_medico` ya existía en el esquema con la siguiente estructura:

```sql
CREATE TABLE `historial_medico` (
  `id_historial`   int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario`     int(11) NOT NULL,
  `fecha_consulta` date NOT NULL,
  `motivo`         varchar(255) NOT NULL,
  `diagnostico`    text NOT NULL,
  `tratamiento`    text NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`id_usuario`)
    REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

La clave foránea con `ON DELETE CASCADE` garantiza que si un paciente es eliminado del sistema, sus registros de historial se eliminan automáticamente. No se requirió ninguna migración de esquema para esta implementación.

### Rendimiento

- Las consultas al historial usan `WHERE id_usuario = X ORDER BY fecha_consulta DESC`. El índice en `id_usuario` ya existe, por lo que el rendimiento es adecuado para el volumen esperado de un consultorio independiente.
- La vista `historial.php` ejecuta dos consultas en cada carga: una para los datos del paciente y otra para el historial. Ambas son simples SELECT con filtro por clave primaria o índice.
- No se implementó paginación. Para consultorios con pacientes de larga data (muchos registros históricos), esto podría ser un punto de mejora.

### Seguridad

- Todos los endpoints verifican sesión activa y rol antes de procesar datos.
- `editar_historial.php` y `eliminar_historial.php` verifican que el `id_historial` pertenece al `id_paciente` recibido, previniendo que un usuario manipule IDs en el formulario para acceder a registros de otros pacientes.
- Todas las entradas pasan por `mysqli_real_escape_string` antes de usarse en consultas.
- Los datos mostrados en HTML usan `htmlspecialchars()` para prevenir XSS.
- Los datos en atributos HTML usan `htmlspecialchars(..., ENT_QUOTES)` para prevenir rotura de atributos y XSS.

### JavaScript

- El modal de edición usa event delegation en lugar de `onclick` inline, lo que lo hace robusto ante cualquier contenido en los campos de texto.
- El formulario de `registrar_historial_cita.php` usa el evento `beforeunload` para advertir al doctor si intenta salir sin guardar el historial.
- No se usa ninguna librería externa — todo el comportamiento interactivo está implementado en JavaScript vanilla.

---

## 5. Decisiones de Diseño

| Decisión | Alternativa considerada | Razón de la elección |
|---|---|---|
| Modal inline para edición en `historial.php` | Página separada de edición | Evita una recarga completa de la página y mantiene el contexto del historial visible. Consistente con el patrón de modales usado en el resto del sistema |
| `data-*` attributes + event delegation para el modal | `onclick` inline con `json_encode` | Los inline handlers se rompen con caracteres especiales en los datos (comillas, saltos de línea). `data-*` + `getAttribute()` es el estándar moderno y no tiene ese problema |
| Vista compartida `historial.php` para doctor y asistente | Vistas separadas por rol | Reduce duplicación de código. El control de acceso se maneja con la variable `$es_doctor` que oculta o muestra secciones según el rol |
| Registro de historial obligatorio al finalizar cita | Registro opcional o posterior | Garantiza que ninguna consulta quede sin documentar. El bloqueo de salida con `beforeunload` refuerza este comportamiento |
| Fecha de consulta tomada automáticamente de la cita al finalizar | Permitir al doctor ingresar cualquier fecha | Previene inconsistencias entre la fecha de la cita registrada y la fecha del historial |
| Validación de fecha: rechazar futuras, permitir pasadas | Rechazar pasadas (comportamiento original incorrecto) | Un historial médico es un registro de consultas que ya ocurrieron. Permitir fechas pasadas es correcto para registrar consultas anteriores al sistema |
| `ON DELETE CASCADE` en la FK de historial | Restricción sin cascade | Si un paciente es eliminado, sus registros médicos no tienen sentido sin el paciente. El cascade mantiene la integridad referencial automáticamente |

---

## 6. Riesgos Identificados

### 🟡 Riesgo Medio

**R1 — Sin paginación en la tabla de historial**  
La tabla de registros carga todos los registros del paciente en una sola consulta. Para pacientes con historial extenso (años de consultas), esto puede resultar en páginas lentas y difíciles de navegar.

*Mitigación sugerida:* Implementar paginación con `LIMIT` y `OFFSET` en la consulta SQL, o un filtro por rango de fechas.

**R2 — Sin filtros en la tabla de historial**  
El criterio de aceptación 3.2 especifica la capacidad de filtrar por fecha y estado. Actualmente la tabla muestra todos los registros sin opción de filtrado.

*Mitigación sugerida:* Agregar un formulario de filtro por rango de fechas y/o motivo sobre la tabla.

**R3 — Contraseñas en texto plano**  
El sistema en general almacena contraseñas como texto plano. Aunque no es específico del módulo de historial, afecta la seguridad global del sistema que contiene datos médicos sensibles.

*Mitigación pendiente:* Migrar a `password_hash()` / `password_verify()` antes de cualquier despliegue en producción.

### 🟢 Riesgo Bajo

**R4 — Compatibilidad de `Element.closest()` en navegadores muy antiguos**  
La función `e.target.closest('.btn-editar')` usada en el event delegation no está disponible en IE11. Para el contexto de uso (consultorio con equipos modernos) esto es aceptable.

**R5 — El campo `tratamiento` en `paciente.php` usa alias incorrecto**  
En la vista del paciente, la consulta al historial usa `$h['treatment'] ?? $h['tratamiento']`. El alias `treatment` no existe en la consulta SQL — siempre cae al fallback `tratamiento`. No causa error visible pero es código muerto que puede confundir en mantenimiento futuro.

*Mitigación:* Eliminar el alias `treatment` de la vista del paciente.

---

## 7. Observaciones de Integración

### Módulo Historial ↔ Módulo Doctor

- El botón "Finalizar Cita" en `doctor.php` redirige a `registrar_historial_cita.php` pasando `id_cita` e `id_paciente` por GET. El endpoint `agregar_historial.php` detecta la presencia de `id_cita` para determinar el flujo y el destino de redirección.
- El enlace "👁️ Historial" en la tabla de citas del doctor pasa el `id_usuario` del paciente (no el `id_cita`), lo que permite acceder al historial completo del paciente independientemente de la cita seleccionada.
- El estado de la cita no cambia automáticamente al registrar el historial desde `registrar_historial_cita.php`. El cambio de estado (a "Finalizada") debe ocurrir en el paso previo desde `doctor.php`.

### Módulo Historial ↔ Módulo Asistente

- El asistente accede al historial con el mismo enlace "👁️ Historial" desde su tabla de citas. La vista `historial.php` detecta el rol y oculta el formulario de nueva consulta y los botones de acción.
- El asistente no puede agregar, editar ni eliminar registros — solo consultar. Esta restricción está implementada tanto en la vista (PHP condicional) como en los endpoints (verificación de `id_tipo_usuario == 1`).

### Módulo Historial ↔ Módulo Paciente

- La sección "Mi Historial" en `paciente.php` consulta directamente la tabla `historial_medico` filtrando por el `id_usuario` de la sesión activa. No usa la vista `historial.php`.
- El paciente solo puede ver sus propios registros y no tiene acceso a ninguna acción de modificación.
- Se identificó un alias incorrecto (`treatment`) en la consulta del paciente (ver Riesgo R5).

### Módulo Historial ↔ Base de Datos

- La tabla `historial_medico` tiene `ON DELETE CASCADE` en la FK hacia `usuario`. Esto significa que eliminar un paciente del sistema elimina automáticamente todo su historial médico. Esta decisión debe ser revisada si en el futuro se requiere conservar registros médicos de pacientes dados de baja.
- No existe una relación directa entre `historial_medico` y `cita` en el esquema de base de datos. El vínculo entre una consulta y su historial es implícito (misma fecha y paciente) pero no está formalizado con una FK. Esto es aceptable para el alcance actual pero limita la trazabilidad en el futuro.

---

## 8. Preparación para Clonación Externa

Para que el módulo de historial médico funcione correctamente en un entorno nuevo, se deben verificar los siguientes puntos además de la instalación general del sistema:

### 8.1 Requisitos del entorno

- PHP 7.4 o superior (recomendado 8.x)
- MySQL 5.7 o superior
- Apache con `mod_rewrite` habilitado
- XAMPP (Windows) o LAMP/LEMP (Linux)

### 8.2 Checklist específico del módulo

```
[ ] 1. Verificar que la tabla historial_medico existe en la BD
       (está incluida en scripts_sql.md — bloque principal)
[ ] 2. Verificar que la FK historial_ibfk_1 apunta a usuario(id_usuario)
[ ] 3. Confirmar que la carpeta src/historial/ contiene los 3 archivos:
       - agregar_historial.php
       - editar_historial.php
       - eliminar_historial.php
[ ] 4. Confirmar que views/roles/registrar_historial_cita.php existe
[ ] 5. Confirmar que views/roles/historial.php existe
[ ] 6. Verificar que doctor.php tiene el enlace a historial.php y el botón Finalizar Cita
[ ] 7. Verificar que asistente.php tiene el enlace a historial.php
[ ] 8. Probar flujo completo: login doctor → finalizar cita → registrar historial → ver historial
[ ] 9. Probar edición de un registro con texto que contenga comillas y saltos de línea
[ ] 10. Probar eliminación de un registro y confirmar que redirige con ?ok=eliminado
```

### 8.3 Variables de entorno a revisar

| Archivo | Variable | Valor por defecto | Cambiar si... |
|---|---|---|---|
| `config/db.php` | `$host` | `127.0.0.1` | El servidor MySQL está en otra IP |
| `config/db.php` | `$port` | `3307` | Se usa el puerto estándar 3306 |
| `config/db.php` | `$user` | `root` | Se usa un usuario MySQL diferente |
| `config/db.php` | `$pass` | `""` (vacío) | El usuario MySQL tiene contraseña |
| `config/db.php` | `$db` | `agenda_vital` | La BD tiene otro nombre |

### 8.4 Datos de prueba recomendados

Para verificar el módulo en un entorno nuevo, se recomienda insertar al menos un registro de historial manualmente:

```sql
INSERT INTO historial_medico (id_usuario, fecha_consulta, motivo, diagnostico, tratamiento)
VALUES (3, CURDATE(), 'Revisión general', 'Paciente en buen estado de salud', 'Ninguno');
```

Donde `id_usuario = 3` corresponde al paciente de prueba `paciente1@vital.com`.

---

## 9. Preparación de Liberación Final

### 9.1 Estado actual vs. criterios de aceptación del módulo

| Criterio | Estado |
|---|---|
| Consulta de historial por paciente (doctor y asistente) | ✅ Implementado |
| Registro de nueva consulta desde el historial | ✅ Implementado |
| Registro obligatorio al finalizar una cita | ✅ Implementado |
| Edición de registros existentes | ✅ Implementado y corregido |
| Eliminación de registros con confirmación | ✅ Implementado |
| Control de acceso por rol (solo doctor puede modificar) | ✅ Implementado |
| Paciente puede consultar su propio historial | ✅ Implementado |

### 9.2 Tareas obligatorias antes de liberación a producción

```
[ ] Corregir alias incorrecto 'treatment' en paciente.php (Riesgo R5)
[ ] Revisar si se requiere conservar historial al eliminar pacientes
    (actualmente ON DELETE CASCADE lo elimina automáticamente)
[ ] Migrar contraseñas a password_hash() / password_verify()
[ ] Agregar validación de longitud máxima en campos diagnostico y tratamiento
    (actualmente son TEXT sin límite en BD ni en backend)
[ ] Revisar permisos de acceso: confirmar que ningún endpoint del historial
    es accesible sin sesión activa desde herramientas externas (curl, Postman)
[ ] Prueba de regresión: verificar que el flujo de finalizar cita
    no rompe el estado de la cita si el INSERT del historial falla
```

### 9.3 Funcionalidades opcionales para versiones futuras

- Filtro por rango de fechas y motivo en la tabla de historial
- Paginación de registros para pacientes con historial extenso
- Exportación del historial a PDF
- Relación formal en BD entre `historial_medico` y `cita` (FK `id_cita`)
- Vista de reprogramaciones y cancelaciones vinculadas al historial
- Adjuntar archivos o imágenes a un registro de historial (resultados de laboratorio, radiografías)

---

*Documento generado para uso interno del equipo de desarrollo. Última actualización: 28 de Mayo de 2026.*
