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
    
    // Validar formato del PIN
    if (!preg_match('/^\d{4}$/', $pin_nuevo)) {
        $error = "El nuevo PIN debe contener exactamente 4 dígitos";
    } 
    // Validar que los PINs nuevos coincidan
    elseif ($pin_nuevo !== $pin_confirmar) {
        $error = "Los PINs nuevos no coinciden";
    } 
    else {
        try {
            // Verificar PIN actual
            $stmt = $pdo->prepare("SELECT pin FROM usuario WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $usuario = $stmt->fetch();
            
            if ($usuario && $usuario['pin'] === $pin_actual) {
                // Iniciar transacción
                $pdo->beginTransaction();
                
                // Actualizar PIN
                $update = $pdo->prepare("UPDATE usuario SET pin = :pin_nuevo WHERE id = :id");
                $update->execute([
                    'pin_nuevo' => $pin_nuevo,
                    'id' => $_SESSION['user_id']
                ]);
                
                // Registrar cambio de PIN
                $historial = $pdo->prepare("INSERT INTO historial_pin (id_usuario) VALUES (:id_usuario)");
                $historial->execute(['id_usuario' => $_SESSION['user_id']]);
                
                // Confirmar transacción
                $pdo->commit();
                
                $success = "PIN actualizado correctamente";
            } else {
                $error = "El PIN actual es incorrecto";
                if (isset($pdo) && $pdo->inTransaction()) {
                    $pdo->rollBack();
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
                        <input type="password" id="pin_nuevo" name="pin_nuevo" maxlength="4" pattern="\d{4}" required>
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
            <div class="keypad-row">
                <button class="keypad-btn" data-key="1">1</button>
                <button class="keypad-btn" data-key="2">2</button>
                <button class="keypad-btn" data-key="3">3</button>
            </div>
            <div class="keypad-row">
                <button class="keypad-btn" data-key="4">4</button>
                <button class="keypad-btn" data-key="5">5</button>
                <button class="keypad-btn" data-key="6">6</button>
            </div>
            <div class="keypad-row">
                <button class="keypad-btn" data-key="7">7</button>
                <button class="keypad-btn" data-key="8">8</button>
                <button class="keypad-btn" data-key="9">9</button>
            </div>
            <div class="keypad-row">
                <button class="keypad-btn" data-key="clear">C</button>
                <button class="keypad-btn" data-key="0">0</button>
                <button class="keypad-btn" data-key="enter">E</button>
            </div>
        </div>
    </div>

    <script src="session.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pinActualInput = document.getElementById('pin_actual');
            const pinNuevoInput = document.getElementById('pin_nuevo');
            const pinConfirmarInput = document.getElementById('pin_confirmar');
            const keypadButtons = document.querySelectorAll('.keypad-btn');
            
            let activeInput = pinActualInput;
            
            // Establecer el foco en el primer campo
            pinActualInput.focus();
            
            // Cambiar el foco cuando se completa un campo
            pinActualInput.addEventListener('input', function() {
                if(this.value.length === 4) {
                    pinNuevoInput.focus();
                    activeInput = pinNuevoInput;
                }
            });
            
            pinNuevoInput.addEventListener('input', function() {
                if(this.value.length === 4) {
                    pinConfirmarInput.focus();
                    activeInput = pinConfirmarInput;
                }
            });
            
            // Establecer el foco en el campo al hacer clic
            [pinActualInput, pinNuevoInput, pinConfirmarInput].forEach(input => {
                input.addEventListener('focus', function() {
                    activeInput = this;
                });
            });
            
            // Manejar teclado numérico
            keypadButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const key = this.getAttribute('data-key');
                    
                    if (key === 'clear') {
                        activeInput.value = '';
                    } else if (key === 'enter') {
                        document.getElementById('pin-form').submit();
                    } else if (activeInput.value.length < 4) {
                        activeInput.value += key;
                        
                        // Cambiar automáticamente al siguiente campo si se completa
                        if (activeInput.value.length === 4) {
                            if (activeInput === pinActualInput) {
                                pinNuevoInput.focus();
                                activeInput = pinNuevoInput;
                            } else if (activeInput === pinNuevoInput) {
                                pinConfirmarInput.focus();
                                activeInput = pinConfirmarInput;
                            }
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
