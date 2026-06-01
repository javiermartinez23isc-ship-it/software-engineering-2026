# Retrospectiva Final — Agenda Vital
**Proyecto:** Agenda Vital | **Equipo:** Los Navitas  
**Institución:** Instituto Tecnológico Superior de San Pedro de las Colonias  
**Carrera:** Ingeniería en Sistemas Computacionales | **Grupo:** 6A

---

## 1. Integrantes del Equipo
| Rol | Nombre |
| :--- | :--- |
| **Coordinador** | Jorge Humberto Esquivel Cuellar |
| **Dev Líder** | Jesús Javier Martínez Hernández |
| **Diseñador** | Jonathan Alfredo Rodríguez Ramírez |
| **QA / Tester** | Jesús Fernando Rodríguez Nava |
| **Analista** | Jesahias Fernando Juarez Palacios |

**Docente:** Ruth Aivi Chávez Rodríguez

---

## 2. Aprendizajes del Equipo
* **Jorge (Coordinador):** El aprendizaje obtenido en este proyecto es de diferentes habilidades tanto blandas como de conocimientos técnicos, mejore mi desempeño en el trabajo en equipo mejorando la comunicación con mis compañeros, adoptando diferentes roles a lo largo del sprint, un conocimiento técnico.
* **Javier (Dev Líder):** Los aprendizajes que obtuve trabajando en este proyecto tienden en una mejora para mi desarrollo como futuro ingeniero. Los aspectos más resaltantes fueron el trabajo equipo, ya que es muy fundamental el cómo nos desenvolvimos en diferentes roles que fueron cambiando y probandonos individual como colectivamente.
Un aprendizaje indivual el cual es importante es el nuevo manejo de métodos y creación de un proyecto que sea estandarizado y funcional desde una perspectiva con profundo analisis y no solo programar por crear algo, sino que ese algo sea totalmente estructura y rigurosamente funcional. El manejo del repositorio, porque es sin duda un portafolio para un perfil profesional.


---

## 3. Evolución del Proyecto desde el sprint 1

| Sprint | Hitos Alcanzados |
| :--- | :--- |
| **Sprint 1** | : identificamos el problema y como lo solucionaríamos, entonces fue que concluimos con la creación de un sistema web donde en el primer sprint tuvimos que definir que tecnologías utilizar para la creación de nuestro sistema web. |
| **Sprint 2** | : en este sprint fue donde empezamos a crear el diseño de las pantallas, definimos como serían las interfaces del flujo principal de nuestro sistema (agendacion de una cita). |
| **Sprint 3** | : Hubo un retraso por parte del dev líder asignado en este sprint por lo que hubo reasignación de tareas, pero por el tema del tiempo solo se alcanzo a que el sistema lograra cumplir con la agendacion de una cita. |
| **Sprint 4** | : El sistema ingresa a los 3 roles, paciente, doctor, y asistente, pero con algunas funciones no disponibles aún. |
| **Sprint 5** | : Implementación de la mejora externa echa por el docente, aparte se cubrió todas las funciones que no se completaron en el sprint debido (sprint 4) como los recordatorios automáticos por correo real, cambio forzado de contraseña, sincronización en tiempo real, límite de reprogramaciones, diseño responsivo para las pantallas, opción de reprogramar citas y configuraciones funcionales para los diferentes roles. |

---

## 4. Retos Técnicos y Gestión
### Mayor dificultad: Sincronización de Zonas Horarias
El sistema presentaba discrepancias entre la hora de México (PHP) y UTC (MySQL), afectando el envío de recordatorios.
* **Solución:** Forzar `SET time_zone` en cada conexión a la BD y reordenar la ejecución en `db.php` para procesar recordatorios antes de marcar inasistencias.
* **Correos:** Migración de Google Workspace a Gmail personal debido a restricciones del administrador.

### Respuesta al Cambio Externo
Se integró el **Historial Médico por paciente** como requerimiento obligatorio, adaptando el alcance original del sistema con éxito.

---

## 5. Áreas de Mejora
* **Automatización:** Implementar un *cron job* para el envío de recordatorios independiente de la interacción del usuario con la plataforma.

---

### 6. Aportación por rol

**Jesahias Analista:** Convertir la retroalimentación externa en una mejora clara, analizar su impacto y mantener control del alcance.

**Diseñador Jonathan:** Ajustar la interfaz o flujo visual afectado por la mejora externa, manteniendo coherencia con el diseño previo.

**Dev Líder Javier:** Evaluar la factibilidad técnica de la mejora, coordinar su implementación e integrar la versión final del repositorio.

**QA / Tester Fernando:** Probar la mejora implementada y verificar que no rompa lo que ya funcionaba en el sistema.

**Coordinador Jorge:** Organizar la revisión externa, controlar el avance del sprint, dar seguimiento a la implementación y consolidar el cierre del proyecto.

---

## 7. Estado Final de liberación en main.
**Estado:** ✅ Estable — Listo para demostración.

**Funcionalidades Operativas:**
* Gestión integral de citas (agendar, cancelar, reprogramar, confirmar, finalizar).
* Recordatorios automáticos por correo electrónico.
* Historial médico obligatorio post-consulta.
* Registro de pacientes con cambio forzado de contraseña.
* Sincronización en tiempo real entre roles.
* Diseño *responsive* (móvil y escritorio).
* Auto-marcado de inasistencias.
