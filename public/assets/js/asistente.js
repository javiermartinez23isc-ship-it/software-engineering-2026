/* asistente.js - COMPORTAMIENTO COMPLETO */
function ver(s) {
    document.querySelectorAll('.section').forEach(x => x.classList.remove('active'));
    document.querySelectorAll('.menu-link').forEach(x => x.classList.remove('active'));
    document.getElementById('v-' + s).classList.add('active');
    document.getElementById('m-' + s).classList.add('active');
}

function mostrarTabla() { 
    document.getElementById('schedule-container').style.display = 'block'; 
}

let sel = null;

function seleccionarCelda(td) {
    if(sel) { 
        sel.classList.remove('cell-selected'); 
        sel.innerHTML = ''; 
    }
    sel = td; 
    td.classList.add('cell-selected'); 
    td.innerHTML = '⭐';
    
    document.getElementById('id_horario_input').value = td.getAttribute('data-id');
    document.getElementById('status-text').innerText = 'Horario seleccionado';
    
    const btnCancelar = document.getElementById('btn-cancelar-hora');
    btnCancelar.style.opacity = '1';
    btnCancelar.style.pointerEvents = 'auto';
    
    document.getElementById('btn-submit-cita').disabled = false;
}

function cancelarSeleccion() {
    if(sel) { 
        sel.classList.remove('cell-selected'); 
        sel.innerHTML = ''; 
    }
    sel = null; 
    document.getElementById('id_horario_input').value = '';
    document.getElementById('status-text').innerText = 'Seleccione un horario';
    document.getElementById('btn-submit-cita').disabled = true;
    
    const btnCancelar = document.getElementById('btn-cancelar-hora');
    btnCancelar.style.opacity = '0.4';
    btnCancelar.style.pointerEvents = 'none';
}

function mostrarAlerta() { 
    document.getElementById('alerta-overlay').style.display = 'block'; 
    document.getElementById('alerta-box').style.display = 'block'; 
}

function cerrarAlerta() { 
    document.getElementById('alerta-overlay').style.display = 'none'; 
    document.getElementById('alerta-box').style.display = 'none'; 
}

// Función adicional para el menú móvil si decides activarla
function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar.style.transform === 'translateX(0px)') {
        sidebar.style.transform = 'translateX(-100%)';
    } else {
        sidebar.style.transform = 'translateX(0px)';
    }
}