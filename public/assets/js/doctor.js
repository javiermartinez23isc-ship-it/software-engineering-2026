/* doctor.js - COMPORTAMIENTO COMPLETO */
function mostrar(id) {
    document.querySelectorAll('.seccion').forEach(s => s.classList.remove('activa'));
    document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
    const vista = document.getElementById('vista-' + id);
    if (vista) vista.classList.add('activa');
    const menu = document.getElementById('menu-' + id);
    if (menu) menu.classList.add('active');
    // Cerrar sidebar en móvil al seleccionar sección
    closeSidebar();
}

function toggleSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const isOpen   = sidebar.classList.contains('open');
    sidebar.classList.toggle('open', !isOpen);
    overlay.classList.toggle('open', !isOpen);
}

function closeSidebar() {
    document.getElementById('sidebar')?.classList.remove('open');
    document.getElementById('sidebar-overlay')?.classList.remove('open');
}

// Al cargar la página, abrir la sección correcta según parámetros de URL
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    if (params.get('perfil') === 'ok') {
        mostrar('configuracion');
    }
});
