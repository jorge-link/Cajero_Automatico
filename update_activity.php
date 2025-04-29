<?php
session_start();

// Verificar si el usuario está autenticado
if (isset($_SESSION['user_id'])) {
    // Actualizar timestamp de última actividad
    $_SESSION['last_activity'] = time();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
