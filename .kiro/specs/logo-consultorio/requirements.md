# Requirements Document

## Introduction

Esta funcionalidad permite al doctor subir, actualizar y eliminar un logo personalizado del consultorio dentro del sistema AgendaVital. El logo del consultorio es independiente del logo del sistema ("Agenda Vital") y se muestra en la barra de navegación de todas las vistas del sistema (doctor, asistente y paciente). Solo el doctor puede gestionar este logo. Si no hay logo configurado, las interfaces muestran un espacio reservado vacío. El logo se almacena en el servidor de archivos y su ruta se persiste en una tabla de configuración en la base de datos.

---

## Glossary

- **Sistema**: La aplicación web AgendaVital en su conjunto.
- **Doctor**: Usuario con `id_tipo_usuario = 1`. Único rol con permisos para gestionar el logo del consultorio.
- **Asistente**: Usuario con `id_tipo_usuario = 2`. Puede visualizar el logo del consultorio pero no modificarlo.
- **Paciente**: Usuario con `id_tipo_usuario = 3`. Puede visualizar el logo del consultorio pero no modificarlo.
- **Logo_Consultorio**: Imagen única del consultorio privado, independiente del logo del sistema "Agenda Vital" (`logo_agenda_vital.png`).
- **Logo_Sistema**: Imagen fija del sistema (`logo_agenda_vital.png`), no modificable por ningún usuario.
- **Gestor_Logo**: Componente PHP encargado de procesar la subida, actualización y eliminación del Logo_Consultorio.
- **Tabla_Configuracion**: Tabla `configuracion_consultorio` en la base de datos `agenda_vital`, que almacena la ruta del Logo_Consultorio bajo la clave `logo_consultorio`.
- **Directorio_Logos**: Carpeta `public/assets/img/logos/` en el servidor, donde se almacenan los archivos del Logo_Consultorio.
- **Navbar**: Barra de navegación superior presente en las vistas de doctor, asistente y paciente.
- **Placeholder**: Espacio reservado en blanco con las mismas dimensiones que el Logo_Consultorio, que se muestra en la Navbar cuando no existe un Logo_Consultorio configurado.

---

## Requirements

### Requisito 1: Almacenamiento persistente del logo del consultorio

**User Story:** Como doctor, quiero que el logo de mi consultorio se guarde de forma persistente en el sistema, para que esté disponible en todas las sesiones y para todos los usuarios.

#### Criterios de Aceptación

1. THE Sistema SHALL almacenar la ruta del Logo_Consultorio en la Tabla_Configuracion de la base de datos `agenda_vital` bajo la clave `logo_consultorio`.
2. WHEN el Doctor sube un Logo_Consultorio por primera vez, THE Sistema SHALL crear un nuevo registro en la Tabla_Configuracion con la ruta del archivo y mostrar confirmación de éxito.
3. THE Sistema SHALL guardar el archivo del Logo_Consultorio en el Directorio_Logos (`public/assets/img/logos/`), con un nombre de archivo único que evite colisiones.
4. WHEN el Doctor sube un nuevo Logo_Consultorio y ya existe un registro en la Tabla_Configuracion, THE Sistema SHALL actualizar ese registro con la nueva ruta del archivo.
5. WHEN el Doctor sube un nuevo Logo_Consultorio y ya existe un archivo previo en el Directorio_Logos, THE Gestor_Logo SHALL intentar eliminar el archivo anterior del servidor antes de guardar el nuevo; IF la eliminación falla, THE Gestor_Logo SHALL registrar el error y continuar guardando el nuevo archivo.
6. IF ocurre un error al guardar el archivo en el servidor, THEN THE Gestor_Logo SHALL mostrar un mensaje de error descriptivo al Doctor y no modificar el registro en la Tabla_Configuracion.

---

### Requisito 2: Subida y actualización del logo por el doctor

**User Story:** Como doctor, quiero poder subir o cambiar el logo de mi consultorio desde mi sección de Configuración, para personalizar la identidad visual del sistema.

#### Criterios de Aceptación

1. WHEN el Doctor accede a la sección "⚙️ Configuración" en su panel, THE Sistema SHALL mostrar un formulario dedicado para gestionar el Logo_Consultorio, visualmente separado del formulario de perfil personal.
2. WHEN el Doctor selecciona un archivo y envía el formulario de logo, THE Gestor_Logo SHALL validar que el archivo tenga una extensión permitida (JPG, JPEG, PNG, GIF o WEBP) y que su tipo MIME real corresponda a una imagen válida.
3. WHEN el Doctor selecciona un archivo y envía el formulario de logo, THE Gestor_Logo SHALL validar que el tamaño del archivo no supere 2 MB (2,097,152 bytes).
4. IF el archivo enviado supera 2 MB, THEN THE Gestor_Logo SHALL rechazar la subida y mostrar un mensaje de error indicando el límite de tamaño.
5. IF el archivo enviado tiene una extensión no permitida, THEN THE Gestor_Logo SHALL rechazar la subida y mostrar un mensaje de error indicando los formatos aceptados.
6. IF el archivo enviado no es una imagen válida, THEN THE Gestor_Logo SHALL rechazar la subida y mostrar un mensaje de error indicando que el archivo no es una imagen válida.
7. WHEN la subida del Logo_Consultorio es exitosa, THE Gestor_Logo SHALL redirigir al doctor a su panel de Configuración con un indicador de éxito en la URL.
8. WHEN el Doctor regresa a su panel de Configuración tras una subida exitosa, THE Sistema SHALL mostrar un mensaje de confirmación visible indicando que el logo fue actualizado correctamente.
9. IF ocurre un error en el servidor durante la subida (p. ej., fallo al mover el archivo), THEN THE Gestor_Logo SHALL mostrar un mensaje de error descriptivo al Doctor sin modificar el registro existente en la Tabla_Configuracion.

---

### Requisito 3: Restricción de acceso por rol

**User Story:** Como administrador del sistema, quiero que solo el doctor pueda modificar el logo del consultorio, para mantener el control sobre la identidad visual del consultorio.

#### Criterios de Aceptación

1. WHEN cualquier solicitud HTTP llega al Gestor_Logo, THE Gestor_Logo SHALL verificar la sesión activa antes de realizar cualquier otra operación.
2. IF no existe una sesión activa al acceder al Gestor_Logo, THEN THE Gestor_Logo SHALL redirigir la solicitud a `views/auth/login.php` sin procesar ningún archivo ni modificar ningún registro.
3. IF la sesión activa corresponde a un usuario con `id_tipo_usuario` distinto de 1, THEN THE Gestor_Logo SHALL redirigir la solicitud a `views/auth/acceso_denegado.php` sin procesar el archivo ni modificar el registro del logo.
4. WHILE el usuario autenticado tiene `id_tipo_usuario` distinto de 1 (Asistente o Paciente), THE Sistema SHALL no incluir el elemento HTML del formulario de gestión del Logo_Consultorio en el HTML renderizado de su vista (no solo ocultarlo con CSS).

---

### Requisito 4: Visualización del logo en la Navbar de todas las vistas

**User Story:** Como paciente o asistente, quiero ver el logo del consultorio en la barra de navegación, para identificar visualmente el consultorio al que pertenezco.

#### Criterios de Aceptación

1. WHEN el Doctor, el Asistente o el Paciente cargan su vista respectiva, THE Sistema SHALL consultar la Tabla_Configuracion para obtener el valor asociado a la clave `logo_consultorio`.
2. IF la consulta a la Tabla_Configuracion falla, THEN THE Sistema SHALL mostrar el Placeholder en el espacio del Logo_Consultorio y continuar cargando la vista sin interrupciones.
3. IF existe un valor no vacío para `logo_consultorio` en la Tabla_Configuracion, THEN THE Sistema SHALL mostrar el Logo_Consultorio en la Navbar de las vistas del Doctor, Asistente y Paciente.
4. IF no existe un valor para `logo_consultorio` en la Tabla_Configuracion o el valor está vacío, THEN THE Sistema SHALL mostrar el Placeholder en el espacio reservado para el Logo_Consultorio en la Navbar.
5. THE Sistema SHALL mostrar el Logo_Consultorio en la Navbar con una altura máxima de 40px, manteniendo la proporción de aspecto original.
6. THE Sistema SHALL mostrar el Logo_Consultorio y el Logo_Sistema simultáneamente en la Navbar, de forma que ambos sean visibles al mismo tiempo sin que uno reemplace al otro.

---

### Requisito 5: Eliminación del logo del consultorio

**User Story:** Como doctor, quiero poder eliminar el logo del consultorio, para revertir la personalización visual y volver al estado sin logo.

#### Criterios de Aceptación

1. WHEN el Doctor accede a la sección "⚙️ Configuración" y existe un Logo_Consultorio configurado (valor no vacío en Tabla_Configuracion), THE Sistema SHALL mostrar una opción para eliminar el Logo_Consultorio actual.
2. WHEN el Doctor confirma la eliminación del Logo_Consultorio, THE Gestor_Logo SHALL eliminar el registro correspondiente de la Tabla_Configuracion; IF la eliminación del registro falla, THE Gestor_Logo SHALL mostrar un mensaje de error al Doctor y no eliminar el archivo físico.
3. WHEN el Doctor confirma la eliminación del Logo_Consultorio, IF existe un archivo en el Directorio_Logos, THEN THE Gestor_Logo SHALL intentar eliminar el archivo físico del servidor; IF la eliminación del archivo falla, THE Gestor_Logo SHALL continuar y mostrar confirmación de éxito (el registro ya fue eliminado de BD).
4. WHEN la eliminación del Logo_Consultorio es exitosa, THE Gestor_Logo SHALL redirigir al doctor a su panel de Configuración con un indicador de eliminación exitosa en la URL.
5. WHEN el Doctor regresa a su panel de Configuración tras una eliminación exitosa, THE Sistema SHALL mostrar un mensaje de confirmación visible y el espacio del Logo_Consultorio en la Navbar SHALL mostrar el Placeholder.

---

### Requisito 6: Integridad y seguridad en la subida de archivos

**User Story:** Como administrador del sistema, quiero que la subida de imágenes sea segura y no comprometa la integridad del servidor, para proteger el sistema de archivos maliciosos.

#### Criterios de Aceptación

1. WHEN el Gestor_Logo recibe un archivo, THE Gestor_Logo SHALL verificar que el tipo MIME real del archivo corresponde a uno de los tipos permitidos (image/jpeg, image/png, image/gif, image/webp), sin depender únicamente de la extensión declarada por el cliente.
2. IF el tipo MIME real del archivo no corresponde a un tipo de imagen permitido, THEN THE Gestor_Logo SHALL rechazar el archivo y mostrar un mensaje de error al Doctor indicando que el archivo no es una imagen válida.
3. THE Gestor_Logo SHALL sanitizar la ruta del archivo antes de almacenarla en la Tabla_Configuracion, de modo que no contenga caracteres que puedan alterar la consulta SQL.
4. IF el Directorio_Logos no existe en el servidor, THEN THE Gestor_Logo SHALL crearlo con permisos de escritura para el servidor web antes de intentar guardar el archivo.
5. IF el Directorio_Logos no tiene permisos de escritura, THEN THE Gestor_Logo SHALL mostrar un mensaje de error al Doctor indicando que el servidor no puede guardar imágenes, y cancelar la operación sin modificar la Tabla_Configuracion.
6. WHEN el Gestor_Logo recibe un archivo, THE Gestor_Logo SHALL verificar que el tamaño del archivo no supere 2 MB (2,097,152 bytes) antes de intentar moverlo al Directorio_Logos.
7. IF el Gestor_Logo recibe una solicitud con un método HTTP distinto a POST, THEN THE Gestor_Logo SHALL redirigir al panel de inicio correspondiente al rol del usuario autenticado sin procesar ningún archivo.
