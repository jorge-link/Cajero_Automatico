<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

// Incluir conexión a la base de datos para obtener saldo actualizado
require_once 'db.php';

try {
    // Obtener saldo actualizado
    $stmt = $pdo->prepare("SELECT saldo FROM usuario WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        $_SESSION['saldo'] = $usuario['saldo'];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al obtener el saldo: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú - Cajero Automático</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="atm-container">
        <div class="atm-header">
            <h1>Banco Virtual</h1>
        </div>
        <div class="atm-screen">
            <div class="screen-content">
                <h2>Menú Principal</h2>
                <div class="balance-display">
                    <p>Saldo actual: <span class="balance">$<?php echo number_format($_SESSION['saldo'], 2); ?></span></p>
                </div>
                
                <div class="menu-options">
                    <a href="retirar.php" class="menu-btn">Retirar Dinero</a>
                    <a href="ingresar.php" class="menu-btn">Ingresar Dinero</a>
                    <a href="actualizar_pin.php" class="menu-btn">Cambiar PIN</a>
                    <a href="historial.php" class="menu-btn">Consultar Historial</a>
                    <a href="logout.php" class="menu-btn logout">Salir</a>
                </div>
            </div>
        </div>
        <div class="atm-footer">
            <p>Su sesión se cerrará automáticamente después de 1 minuto de inactividad</p>
        </div>
    </div>

    <script src="session.js"></script>
</body>
</html>
