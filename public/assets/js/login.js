/* login.js */
function mostrarRecuperacion(event) {
    event.preventDefault();
    document.getElementById('view-login').style.display = 'none';
    document.getElementById('view-recovery').style.display = 'block';
}

function mostrarLogin(event) {
    if(event) event.preventDefault();
    document.getElementById('view-recovery').style.display = 'none';
    document.getElementById('view-login').style.display = 'block';
    document.getElementById('recoveryEmail').value = '';
}

function enviarRecuperacion() {
    const email = document.getElementById('recoveryEmail').value.trim();
    if (!email || !email.includes('@')) {
        alert("Por favor, ingresa un correo electrónico válido.");
        return;
    }
    alert("Solicitud recibida.\n\nSe han enviado instrucciones al correo proporcionado.");
    mostrarLogin();
}