<?php
session_start();
header('Content-Type: application/json');

// Verificar si el usuario está autenticado y si la sesión no ha expirado
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    
    // Verificar si han pasado más de 60 segundos desde la última actividad
    if ($inactive_time > 60) {
        // Sesión expirada
        echo json_encode(['active' => false]);
    } else {
        // Sesión activa
        echo json_encode(['active' => true]);
    }
} else {
    // No hay sesión activa
    echo json_encode(['active' => false]);
}
