<?php
// Iniciar sesión
session_start();

// Incluir conexión a la base de datos
require_once 'db.php';

// Verificar si se recibió un PIN
if (isset($_POST['pin'])) {
    // Sanitizar el PIN ingresado
    $pin = sanitize($_POST['pin']);

    try {
        // Buscar usuario con el PIN proporcionado
        $stmt = $pdo->prepare("SELECT id, pin, saldo FROM usuario WHERE pin = :pin");
        $stmt->execute(['pin' => $pin]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // Usuario encontrado - Iniciar sesión
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['saldo'] = $usuario['saldo'];
            $_SESSION['last_activity'] = time();
            
            // Registrar fecha y hora de ingreso
            $update = $pdo->prepare("UPDATE usuario SET fecha_ingreso = NOW() WHERE id = :id");
            $update->execute(['id' => $usuario['id']]);

            // Verificar si es un usuario de mantenimiento (PIN 9999)
            if ($pin === '9999') {
                header("Location: mantenimiento.php");
            } else {
                header("Location: menu.php");
            }
            exit;
        } else {
            // PIN incorrecto
            $_SESSION['error'] = "PIN incorrecto. Intente nuevamente.";
            header("Location: index.html");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
        header("Location: index.html");
        exit;
    }
} else {
    // No se recibió un PIN
    $_SESSION['error'] = "Por favor, ingrese su PIN";
    header("Location: index.html");
    exit;
}
