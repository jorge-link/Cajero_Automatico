// Variables para control de sesión
let inactivityTimeout;
const TIMEOUT_DURATION = 60000; // 60 segundos (1 minuto)

// Reiniciar el temporizador en cada interacción
function resetTimer() {
    clearTimeout(inactivityTimeout);
    inactivityTimeout = setTimeout(logoutDueToInactivity, TIMEOUT_DURATION);
    
    // Actualizar last_activity en el servidor
    fetch('update_activity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    });
}

// Función para cerrar sesión por inactividad
function logoutDueToInactivity() {
    alert('Su sesión ha expirado por inactividad.');
    window.location.href = 'logout.php';
}

// Eventos para reiniciar el temporizador
document.addEventListener('click', resetTimer);
document.addEventListener('keypress', resetTimer);
document.addEventListener('mousemove', resetTimer);
document.addEventListener('touchstart', resetTimer);

// Iniciar el temporizador cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    resetTimer();
    
    // Verificar el estado de la sesión periódicamente (cada 10 segundos)
    setInterval(function() {
        fetch('check_session.php')
            .then(response => response.json())
            .then(data => {
                if (!data.active) {
                    window.location.href = 'logout.php';
                }
            })
            .catch(error => console.error('Error al verificar la sesión:', error));
    }, 10000);
});
