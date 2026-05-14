/* paciente.js - COMPORTAMIENTO COMPLETO */
function ver(s) {
    // Alternar visibilidad de secciones
    document.querySelectorAll('.seccion').forEach(v => v.classList.remove('activa'));
    const vistaSeleccionada = document.getElementById('v-' + s);
    if (vistaSeleccionada) vistaSeleccionada.classList.add('activa');

    // Alternar estado activo en el menú lateral
    document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
    const menuSeleccionado = document.getElementById('m-' + s);
    if (menuSeleccionado) menuSeleccionado.classList.add('active');
}

let celdaSeleccionada = null;

function seleccionarCelda(celda) {
    // Limpiar selección anterior
    if (celdaSeleccionada) { 
        celdaSeleccionada.classList.remove('cell-selected'); 
        celdaSeleccionada.innerHTML = ''; 
    }

    // Aplicar nueva selección
    celdaSeleccionada = celda;
    celda.classList.add('cell-selected');
    celda.innerHTML = '⭐'; 

    // Actualizar formulario
    const inputId = document.getElementById('input-id-horario');
    const btnSubmit = document.getElementById('btn-submit-cita');
    const statusText = document.getElementById('status-text');

    if (inputId) inputId.value = celda.getAttribute('data-id');
    if (btnSubmit) btnSubmit.disabled = false;
    if (statusText) statusText.innerText = 'Horario seleccionado correctamente.';
}