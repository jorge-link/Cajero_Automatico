<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

require_once 'db.php';

$error = $success = '';

// Procesar la actualización del PIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pin_actual']) && isset($_POST['pin_nuevo']) && isset($_POST['pin_confirmar'])) {
    $pin_actual = sanitize($_POST['pin_actual']);
    $pin_nuevo = sanitize($_POST['pin_nuevo']);
    $pin_confirmar = sanitize($_POST['pin_confirmar']);
    
    // Validar formato del PIN (4 dígitos)
    if (!preg_match('/^\d{4}$/', $pin_nuevo)) {
        $error = "El nuevo PIN debe contener exactamente 4 dígitos";
    } 
    // Validar que los PINs nuevos coincidan
    elseif ($pin_nuevo !== $pin_confirmar) {
        $error = "Los PINs nuevos no coinciden";
    }
    // Validar que no sea el mismo PIN actual
    elseif ($pin_nuevo === $pin_actual) {
        $error = "El nuevo PIN no puede ser igual al actual";
    }
    // Validar que no sean dígitos repetidos (ej: 1111)
    elseif (preg_match('/^(\d)\1{3}$/', $pin_nuevo)) {
        $error = "El PIN no puede tener todos los dígitos iguales";
    } 
    else {
        try {
            // Verificar PIN actual
            $stmt = $pdo->prepare("SELECT pin FROM usuario WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $usuario = $stmt->fetch();
            
            if ($usuario && $usuario['pin'] === $pin_actual) {
                // Verificar si el PIN ya fue usado antes
                $stmtHistorial = $pdo->prepare("SELECT COUNT(*) FROM historial_pin WHERE id_usuario = :id_usuario AND pin_anterior = :pin_nuevo");
                $stmtHistorial->execute([
                    'id_usuario' => $_SESSION['user_id'],
                    'pin_nuevo' => $pin_nuevo
                ]);
                
                if ($stmtHistorial->fetchColumn() > 0) {
                    $error = "No puedes usar un PIN que ya has utilizado anteriormente";
                } else {
                    // Iniciar transacción
                    $pdo->beginTransaction();
                    
                    // Registrar cambio de PIN (guardando el anterior)
                    $historial = $pdo->prepare("INSERT INTO historial_pin (id_usuario, pin_anterior) VALUES (:id_usuario, :pin_anterior)");
                    $historial->execute([
                        'id_usuario' => $_SESSION['user_id'],
                        'pin_anterior' => $pin_actual
                    ]);
                    
                    // Actualizar PIN
                    $update = $pdo->prepare("UPDATE usuario SET pin = :pin_nuevo WHERE id = :id");
                    $update->execute([
                        'pin_nuevo' => $pin_nuevo,
                        'id' => $_SESSION['user_id']
                    ]);
                    
                    // Confirmar transacción
                    $pdo->commit();
                    
                    $success = "PIN actualizado correctamente";
                    
                    // Reiniciar contador de intentos
                    unset($_SESSION['pin_change_attempts']);
                }
            } else {
                $error = "El PIN actual es incorrecto";
                // Registrar intento fallido
                $_SESSION['pin_change_attempts'] = ($_SESSION['pin_change_attempts'] ?? 0) + 1;
                
                if ($_SESSION['pin_change_attempts'] >= 3) {
                    $error = "Demasiados intentos fallidos. Por seguridad, cierra sesión e inténtalo más tarde";
                }
            }
        } catch (PDOException $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Error en la actualización: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar PIN - Cajero Automático</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="atm-container">
        <div class="atm-header">
            <h1>Banco Virtual</h1>
        </div>
        <div class="atm-screen">
            <div class="screen-content">
                <h2>Cambiar PIN</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form id="pin-form" method="POST">
                    <div class="form-group">
                        <label for="pin_actual">PIN Actual:</label>
                        <input type="password" id="pin_actual" name="pin_actual" maxlength="4" pattern="\d{4}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="pin_nuevo">Nuevo PIN:</label>
                        <input type="password" id="pin_nuevo" name="pin_nuevo" maxlength="4" pattern="\d{4}" required
                               title="4 dígitos diferentes al actual y no usado previamente">
                    </div>
                    
                    <div class="form-group">
                        <label for="pin_confirmar">Confirmar Nuevo PIN:</label>
                        <input type="password" id="pin_confirmar" name="pin_confirmar" maxlength="4" pattern="\d{4}" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Cambiar PIN</button>
                        <a href="menu.php" class="btn-secondary">Volver al Menú</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="atm-keypad">
            <!-- Teclado numérico (igual que en tu versión original) -->
        </div>
    </div>

    <script src="session.js"></script>
    <script>
        // Script para el teclado numérico (igual que en tu versión original)
    </script>
</body>
</html>