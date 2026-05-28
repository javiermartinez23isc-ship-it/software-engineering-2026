/* paciente.js */

/* ── Navegación entre secciones ─────────────────────────────────────────── */
function ver(s) {
    document.querySelectorAll('.seccion').forEach(function(v) {
        v.classList.remove('activa');
    });
    var vista = document.getElementById('v-' + s);
    if (vista) vista.classList.add('activa');

    document.querySelectorAll('.sidebar-menu a').forEach(function(l) {
        l.classList.remove('active');
    });
    var menu = document.getElementById('m-' + s);
    if (menu) menu.classList.add('active');

    closeSidebar();
}

function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    if (!sidebar) return;
    var isOpen = sidebar.classList.contains('open');
    sidebar.classList.toggle('open', !isOpen);
    if (overlay) overlay.classList.toggle('open', !isOpen);
}

function closeSidebar() {
    var s = document.getElementById('sidebar');
    var o = document.getElementById('sidebar-overlay');
    if (s) s.classList.remove('open');
    if (o) o.classList.remove('open');
}

/* ── Calendario principal (agendar cita) ────────────────────────────────── */
var _celdaActiva = null;

function seleccionarCelda(td) {
    if (_celdaActiva) {
        _celdaActiva.classList.remove('cell-selected');
        _celdaActiva.innerHTML = '';
    }
    _celdaActiva = td;
    td.classList.add('cell-selected');
    td.innerHTML = '⭐';

    var fecha = td.getAttribute('data-fecha') || '';
    var hora  = td.getAttribute('data-hora')  || '';
    var id    = td.getAttribute('data-id')    || '';

    var btn = document.getElementById('btn-submit-cita');
    if (btn) {
        btn.setAttribute('data-fecha', fecha);
        btn.setAttribute('data-hora',  hora);
        btn.setAttribute('data-id',    id);
        btn.removeAttribute('disabled');
    }

    var hh   = parseInt(hora.split(':')[0], 10);
    var h12  = ((hh % 12) || 12);
    var ampm = hh < 12 ? 'a.m.' : 'p.m.';
    var d    = new Date(fecha + 'T12:00:00');
    var dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
    var label = dias[d.getDay()] + ' ' + d.getDate() + '/' +
                String(d.getMonth() + 1).padStart(2, '0') + ' ' + h12 + ':00 ' + ampm;
    var info = document.getElementById('seleccion-info');
    if (info) info.innerText = '✅ Seleccionado: ' + label;
}

function enviarCita(event) {
    event.preventDefault();
    var btn   = document.getElementById('btn-submit-cita');
    var fecha = btn ? btn.getAttribute('data-fecha') : '';
    var hora  = btn ? btn.getAttribute('data-hora')  : '';
    var id    = btn ? btn.getAttribute('data-id')    : '';

    if (!fecha || !hora) {
        alert('Por favor selecciona un horario en el calendario.');
        return false;
    }

    var form = document.getElementById('form-agendar');
    if (!form) { alert('Error interno: formulario no encontrado.'); return false; }

    ['_inp_id', '_inp_fecha', '_inp_hora'].forEach(function(eid) {
        var el = document.getElementById(eid);
        if (el) el.parentNode.removeChild(el);
    });

    [
        { name: 'id_horario', val: id,    eid: '_inp_id'    },
        { name: 'fecha_slot', val: fecha, eid: '_inp_fecha' },
        { name: 'hora_slot',  val: hora,  eid: '_inp_hora'  }
    ].forEach(function(item) {
        var inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = item.name;
        inp.value = item.val;
        inp.id    = item.eid;
        form.appendChild(inp);
    });

    form.submit();
}

/* ── Modal de Reprogramación ────────────────────────────────────────────── */
var _reproCeldaActiva = null;

function abrirReprogramar(idCita, idHorario, fechaActual, horaActual) {
    var overlay = document.getElementById('modal-reprogramar-overlay');
    var modal   = document.getElementById('modal-reprogramar');

    if (!overlay || !modal) {
        alert('Error: no se encontró el panel de reprogramación.');
        return;
    }

    // Rellenar campos ocultos del formulario
    document.getElementById('inp-cita-vieja').value    = idCita;
    document.getElementById('inp-horario-viejo').value = idHorario;

    // Mostrar la cita actual en el aviso
    var hh     = parseInt(String(horaActual).split(':')[0], 10);
    var h12    = ((hh % 12) || 12);
    var ampm   = hh < 12 ? 'a.m.' : 'p.m.';
    var partes = String(fechaActual).split('-');
    var label  = partes[2] + '/' + partes[1] + '/' + partes[0] + ' a las ' + h12 + ':00 ' + ampm;
    var spanCita = document.getElementById('repro-cita-actual');
    if (spanCita) spanCita.textContent = label;

    // Reasignar onclick de celdas libres del modal → seleccionarCeldaRepro
    var cal = document.getElementById('repro-calendario');
    if (cal) {
        cal.querySelectorAll('td.cell-free').forEach(function(td) {
            td.onclick = function() { seleccionarCeldaRepro(this); };
        });

        // Bloquear el slot de la cita anterior (sombreado amarillo)
        var horaNorm = String(horaActual).length === 5 ? horaActual + ':00' : String(horaActual);
        cal.querySelectorAll('td[data-fecha][data-hora]').forEach(function(td) {
            var tdFecha = td.getAttribute('data-fecha');
            var tdHora  = td.getAttribute('data-hora');
            var tdHoraN = tdHora.length === 5 ? tdHora + ':00' : tdHora;
            if (tdFecha === String(fechaActual) && tdHoraN === horaNorm) {
                td.classList.remove('cell-free', 'cell-selected');
                td.classList.add('cell-locked', 'cell-prev-cita');
                td.onclick = null;
                td.innerHTML = '🔒';
                td.title = 'Horario de tu cita anterior';
            }
        });
    }

    // Limpiar selección previa
    _reproCeldaActiva = null;
    var btnSubmit = document.getElementById('btn-repro-submit');
    if (btnSubmit) {
        btnSubmit.setAttribute('disabled', 'disabled');
        btnSubmit.removeAttribute('data-fecha');
        btnSubmit.removeAttribute('data-hora');
        btnSubmit.removeAttribute('data-id');
    }
    var info = document.getElementById('repro-seleccion-info');
    if (info) info.textContent = 'Ningún horario seleccionado.';

    // Mostrar modal
    overlay.style.display = 'block';
    modal.style.display   = 'block';
    document.body.style.overflow = 'hidden';
}

function cerrarReprogramar() {
    var overlay = document.getElementById('modal-reprogramar-overlay');
    var modal   = document.getElementById('modal-reprogramar');
    if (overlay) overlay.style.display = 'none';
    if (modal)   modal.style.display   = 'none';
    document.body.style.overflow = '';
    _reproCeldaActiva = null;
}

function seleccionarCeldaRepro(td) {
    if (_reproCeldaActiva) {
        _reproCeldaActiva.classList.remove('cell-selected');
        _reproCeldaActiva.innerHTML = '';
    }
    _reproCeldaActiva = td;
    td.classList.add('cell-selected');
    td.innerHTML = '⭐';

    var fecha = td.getAttribute('data-fecha') || '';
    var hora  = td.getAttribute('data-hora')  || '';
    var id    = td.getAttribute('data-id')    || '';

    var btn = document.getElementById('btn-repro-submit');
    if (btn) {
        btn.setAttribute('data-fecha', fecha);
        btn.setAttribute('data-hora',  hora);
        btn.setAttribute('data-id',    id);
        btn.removeAttribute('disabled');
    }

    var hh   = parseInt(hora.split(':')[0], 10);
    var h12  = ((hh % 12) || 12);
    var ampm = hh < 12 ? 'a.m.' : 'p.m.';
    var d    = new Date(fecha + 'T12:00:00');
    var dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
    var label = dias[d.getDay()] + ' ' + d.getDate() + '/' +
                String(d.getMonth() + 1).padStart(2, '0') + ' ' + h12 + ':00 ' + ampm;
    var info = document.getElementById('repro-seleccion-info');
    if (info) info.textContent = '✅ Nuevo horario: ' + label;
}

function enviarReprogramar(event) {
    event.preventDefault();
    var btn   = document.getElementById('btn-repro-submit');
    var fecha = btn ? btn.getAttribute('data-fecha') : '';
    var hora  = btn ? btn.getAttribute('data-hora')  : '';
    var id    = btn ? btn.getAttribute('data-id')    : '';

    if (!fecha || !hora) {
        alert('Por favor selecciona un nuevo horario en el calendario.');
        return false;
    }

    var form = document.getElementById('form-reprogramar');
    if (!form) { alert('Error interno: formulario no encontrado.'); return false; }

    ['_repro_id', '_repro_fecha', '_repro_hora'].forEach(function(eid) {
        var el = document.getElementById(eid);
        if (el) el.parentNode.removeChild(el);
    });

    [
        { name: 'id_horario', val: id,    eid: '_repro_id'    },
        { name: 'fecha_slot', val: fecha, eid: '_repro_fecha' },
        { name: 'hora_slot',  val: hora,  eid: '_repro_hora'  }
    ].forEach(function(item) {
        var inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = item.name;
        inp.value = item.val;
        inp.id    = item.eid;
        form.appendChild(inp);
    });

    form.submit();
}

/* ── Init ───────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    var params = new URLSearchParams(window.location.search);
    if (params.get('perfil') === 'ok') ver('configuracion');
});
