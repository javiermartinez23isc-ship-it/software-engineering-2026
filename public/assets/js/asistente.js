/* asistente.js */
function ver(s) {
    document.querySelectorAll('.section').forEach(x => x.classList.remove('active'));
    document.querySelectorAll('.menu-link').forEach(x => x.classList.remove('active'));
    var v = document.getElementById('v-' + s);
    var m = document.getElementById('m-' + s);
    if (v) v.classList.add('active');
    if (m) m.classList.add('active');
    closeSidebar();
}

function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    var isOpen  = sidebar.classList.contains('open');
    sidebar.classList.toggle('open', !isOpen);
    overlay.classList.toggle('open', !isOpen);
}

function closeSidebar() {
    var s = document.getElementById('sidebar');
    var o = document.getElementById('sidebar-overlay');
    if (s) s.classList.remove('open');
    if (o) o.classList.remove('open');
}

function toggleMenu() { toggleSidebar(); }

function mostrarTabla() {
    var sc = document.getElementById('schedule-container');
    if (sc) { sc.style.display = 'block'; sc.style.visibility = 'visible'; }
}

// Slot seleccionado
var _fecha = '';
var _hora  = '';
var _id    = '';
var _celda = null;

function seleccionarCelda(td) {
    if (_celda) { _celda.classList.remove('cell-selected'); _celda.innerHTML = ''; }
    _celda = td;
    td.classList.add('cell-selected');
    td.innerHTML = '⭐';

    _fecha = td.dataset.fecha || td.getAttribute('data-fecha') || '';
    _hora  = td.dataset.hora  || td.getAttribute('data-hora')  || '';
    _id    = td.dataset.id    || td.getAttribute('data-id')    || '';

    var btn = document.getElementById('btn-submit-cita');
    if (btn) btn.removeAttribute('disabled');

    var hh = parseInt(_hora.split(':')[0], 10);
    var h12 = ((hh % 12) || 12);
    var ampm = hh < 12 ? 'a.m.' : 'p.m.';
    var d = new Date(_fecha + 'T12:00:00');
    var dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
    var label = dias[d.getDay()] + ' ' + d.getDate() + '/' +
                String(d.getMonth()+1).padStart(2,'0') + ' ' + h12 + ':00 ' + ampm;

    var st = document.getElementById('status-text');
    if (st) st.innerText = '✅ ' + label;

    var bc = document.getElementById('btn-cancelar-hora');
    if (bc) { bc.style.opacity = '1'; bc.style.pointerEvents = 'auto'; }
}

function cancelarSeleccion() {
    if (_celda) { _celda.classList.remove('cell-selected'); _celda.innerHTML = ''; }
    _celda = null; _fecha = ''; _hora = ''; _id = '';
    var st = document.getElementById('status-text');
    if (st) st.innerText = 'Ningún horario seleccionado';
    var btn = document.getElementById('btn-submit-cita');
    if (btn) btn.setAttribute('disabled', 'disabled');
    var bc = document.getElementById('btn-cancelar-hora');
    if (bc) { bc.style.opacity = '0.4'; bc.style.pointerEvents = 'none'; }
}

function mostrarAlerta() {
    var o = document.getElementById('alerta-overlay');
    var b = document.getElementById('alerta-box');
    if (o) o.style.display = 'block';
    if (b) b.style.display = 'block';
}

function cerrarAlerta() {
    var o = document.getElementById('alerta-overlay');
    var b = document.getElementById('alerta-box');
    if (o) o.style.display = 'none';
    if (b) b.style.display = 'none';
}

function enviarCitaAsistente(event) {
    event.preventDefault();
    if (!_fecha || !_hora) {
        alert('Por favor selecciona un horario en el calendario.');
        return false;
    }
    var form = document.getElementById('form-nueva-cita');
    if (!form) { alert('Error: formulario no encontrado.'); return false; }

    // Validar que se haya seleccionado un paciente
    var selectPaciente = form.querySelector('select[name="id_usuario"]');
    if (!selectPaciente || !selectPaciente.value) {
        alert('Por favor selecciona un paciente.');
        return false;
    }

    // Limpiar inputs anteriores
    ['_a_id','_a_fecha','_a_hora'].forEach(function(n) {
        var old = document.getElementById(n);
        if (old) old.parentNode.removeChild(old);
    });
    // Crear inputs hidden frescos
    function addHidden(name, val, eid) {
        var inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = name; inp.value = val; inp.id = eid;
        form.appendChild(inp);
    }
    addHidden('id_horario', _id,    '_a_id');
    addHidden('fecha_slot', _fecha, '_a_fecha');
    addHidden('hora_slot',  _hora,  '_a_hora');
    form.submit();
}

document.addEventListener('DOMContentLoaded', function () {
    var params = new URLSearchParams(window.location.search);
    if (params.get('perfil') === 'ok') ver('configuracion');
});
