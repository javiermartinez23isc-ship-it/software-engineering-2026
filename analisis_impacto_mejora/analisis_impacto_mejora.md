# INSTITUTO TECNOLÓGICO SUPERIOR DE SAN PEDRO DE LAS COLONIAS
![Logo](logo.png)

## INGENIERÍA EN SISTEMAS COMPUTACIONALES

---

# Sprint 5 – Cierre del proyecto
**Grupo:** 6A  
**Materia:** Ingeniería de software  
**Docente:** Ruth Aivi Chávez Rodríguez  

**Equipo “Navitas Team”**  
- Jorge Humberto Esquivel Cuellar  
- Jesús Javier Martínez Hernández  
- Jesús Fernando Rodríguez Nava  
- Jonathan Alfredo Rodríguez Ramírez  
- Jesahias Fernando Juárez Palacios  

---

# Análisis de Impacto de la Mejora

## Introducción
El documento tiene como finalidad analizar el impacto de la mejora implementada en el sistema, específicamente la incorporación del historial médico del paciente y la optimización del diseño responsivo. Este análisis permite identificar las áreas afectadas, los riesgos técnicos y las acciones necesarias para garantizar una integración adecuada y sostenible.

## ¿Qué parte del sistema se modificará?
Se modificarán las pantallas del doctor, asistente y paciente, integrando la funcionalidad de historial clínico.  
- Paciente: visualizará únicamente su historial.  
- Doctor: podrá consultar los historiales de todos los pacientes.  
- Asistente: apoyará en la gestión y seguimiento de citas médicas.  

## ¿Qué requisito se fortalece o ajusta?
Se fortalece la intuitividad de las interfaces y su adaptabilidad a distintos dispositivos, garantizando una experiencia de usuario más clara, accesible y consistente.

## ¿Qué pantallas se verán afectadas?
- Paciente: historial clínico personal.  
- Doctor: consulta de historiales de todos los pacientes.  
- Asistente: gestión de citas y registros médicos.  

## ¿Qué lógica o proceso se ajustará?
Se ajustará la lógica de registro y consulta de información médica, integrando procesos que permitan almacenar, recuperar y visualizar historiales clínicos.  
Además, se optimizará la interfaz para mejorar la respuesta del sistema ante grandes volúmenes de datos.

## ¿La base de datos se modificará?
Sí. Será necesario diseñar y normalizar nuevas estructuras en la base de datos para almacenar los historiales médicos de manera segura y organizada, garantizando integridad y trazabilidad de la información.

## ¿Se necesita agregar, modificar o consultar información?
Sí. La mejora implica la incorporación de nuevos registros médicos, la modificación de estructuras existentes y la consulta de historiales clínicos por parte de los distintos perfiles de usuario.

## ¿Qué riesgo técnico existe?
- Posible incompatibilidad entre los ajustes si no se implementan correctamente.  
- Riesgo de inconsistencia en la base de datos si la nueva estructura no se integra de forma adecuada.  
- Posible disminución del rendimiento al consultar grandes volúmenes de información médica.  

## ¿Qué pruebas deberá realizar QA?
- Usabilidad en dispositivos móviles y de escritorio para la responsividad.  
- Funcionalidad de registro y consulta de historiales médicos en los tres perfiles.  

---

# Validación externa
La validación se realizó mediante la revisión del docente **César Moisés Rosales Ramírez**, quien comprobó que la funcionalidad de historial médico está correctamente implementada en las tres pantallas principales (asistente, doctor y paciente), aprobando su funcionamiento y confirmando la pertinencia de la mejora.

---

# Tabla de impacto

| Área afectada | Impacto identificado | Acción requerida |
|---------------|----------------------|------------------|
| Requisitos    | El alcance del sistema se amplía al incluir la funcionalidad de historial médico | Documentar la ampliación del alcance y actualizar especificaciones |
| Interfaz      | Distorsiones en móviles y necesidad de nuevas secciones | Ajustar maquetación y estilos CSS, realizar pruebas de visualización |
| Lógica        | Nuevos procesos de registro y consulta | Desarrollar módulos adicionales y validaciones |
| Base de datos | Nuevas tablas o campos para historiales médicos | Diseñar y normalizar estructura garantizando integridad |
| Pruebas       | Validar responsividad y funcionalidad del historial médico | Ejecutar pruebas de usabilidad, carga y consistencia |

---

# Documentación
La documentación actual no contempla la nueva funcionalidad de historiales médicos ni los ajustes en la interfaz y la lógica del sistema. Esto genera un vacío en la comprensión técnica y en el manual de usuario.  

**Acción requerida:** Actualizar manuales técnicos y de usuario, reflejando los cambios en la interfaz, la lógica y la base de datos. Incluir diagramas de flujo y ejemplos de uso para facilitar la comprensión.
