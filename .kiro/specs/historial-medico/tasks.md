# Implementation Plan: Historial Médico - Inserción por Doctor

## Overview
Implementar la funcionalidad completa para que el doctor pueda agregar nuevas entradas al historial médico de un paciente. Se crea el procesador `src/historial/agregar_historial.php` y se modifica `views/roles/historial.php` para mostrar el formulario condicionalmente.

## Tasks

- [x] 1. Crear src/historial/agregar_historial.php
  - Incluir `config/db.php` con ruta `__DIR__ . '/../../config/db.php'`
  - Verificar `$_SESSION['usuario_id']` (sesión activa), redirigir a login si no existe
  - Verificar `$_SESSION['id_tipo_usuario'] == 1` (solo doctor), redirigir al panel correspondiente si no
  - Sanitizar con `mysqli_real_escape_string()`: id_paciente, fecha_consulta, motivo, diagnostico, tratamiento
  - Validar que ningún campo esté vacío; redirigir con `&error=campos_vacios` si falla
  - Ejecutar INSERT en `historial_medico` (id_usuario, fecha_consulta, motivo, diagnostico, tratamiento)
  - Redirigir a `../../views/roles/historial.php?id={id_paciente}` en éxito
  - Redirigir con `&error={mysqli_error_urlencoded}` en fallo de BD

- [x] 2. Modificar views/roles/historial.php para agregar formulario condicional
  - Agregar estilos CSS inline para el formulario (sección `.form-nueva-consulta`, labels, inputs, textarea, botón)
  - Agregar bloque de alerta si `$_GET['error']` está presente (mostrar mensaje de error al doctor)
  - Agregar formulario condicional `<?php if ($_SESSION['id_tipo_usuario'] == 1): ?>` entre el encabezado del paciente y la tabla de historial
  - Formulario con `method="POST"` y `action="../../src/historial/agregar_historial.php"`
  - Campo oculto `id_paciente` con valor `$id_paciente`
  - Campos: fecha_consulta (date), motivo (text maxlength=255), diagnostico (textarea), tratamiento (textarea)
  - Todos los campos con atributo `required`
  - Botón "Guardar Consulta" con estilo consistente al proyecto
  - _Depends on: 1_

## Task Dependency Graph

```
1 (agregar_historial.php) → 2 (historial.php formulario)
```

## Notes
- Seguir el mismo patrón de rutas relativas que `src/appointments/finalizar_cita.php`
- Mantener estilo inline CSS consistente con el resto de `historial.php`
- La validación de rol en servidor es la seguridad real; el formulario oculto en HTML es solo UX
