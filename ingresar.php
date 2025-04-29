<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

require_once 'db.php';

$error = $success = '';

// Procesar el formulario de ingreso
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['monto'])) {
    $monto = sanitize($_POST['monto']);
    
    // Validar que el monto sea un número positivo
    if (!is_numeric($monto) || $monto <= 0) {
        $error = "Por favor, ingrese un monto válido";
    } else {
        try {
            // Iniciar transacción
            $pdo->beginTransaction();
            
            // Obtener saldo actual
            $stmt = $pdo->prepare("SELECT saldo FROM usuario WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                // Actualizar saldo
                $nuevoSaldo = $usuario['saldo'] + $monto;
                $update = $pdo->prepare("UPDATE usuario SET saldo = :saldo WHERE id = :id");
                $update->execute([
                    'saldo' => $nuevoSaldo,
                    'id' => $_SESSION['user_id']
                ]);
                
                // Registrar movimiento
                $movimiento = $pdo->prepare("INSERT INTO movimiento (id_usuario, tipo, monto) VALUES (:id_usuario, :tipo, :monto)");
                $movimiento->execute([
                    'id_usuario' => $_SESSION['user_id'],
                    'tipo' => 'Ingreso',
                    'monto' => $monto
                ]);
                
                // Actualizar saldo en sesión
                $_SESSION['saldo'] = $nuevoSaldo;
                
                // Confirmar transacción
                $pdo->commit();
                
                $success = "Ingreso de $" . number_format($monto, 2) . " realizado correctamente";
            } else {
                $error = "Error al obtener el saldo actual";
                $pdo->rollBack();
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error en la operación: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar Dinero - Cajero Automático</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="atm-container">
        <div class="atm-header">
            <h1>Banco Virtual</h1>
        </div>
        <div class="atm-screen">
            <div class="screen-content">
                <h2>Ingresar Dinero</h2>
                
                <div class="balance-display">
                    <p>Saldo actual: <span class="balance">$<?php echo number_format($_SESSION['saldo'], 2); ?></span></p>
                </div>
                
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <div class="transaction-animation">
                            <div class="money-deposit"></div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form id="ingreso-form" method="POST">
                    <div class="form-group">
                        <label for="monto">Monto a ingresar:</label>
                        <input type="number" id="monto" name="monto" min="1" step="0.01" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Ingresar</button>
                        <a href="menu.php" class="btn-secondary">Volver al Menú</a>
                    </div>
                </form>
                
                <div class="quick-amounts">
                    <p>Cantidades rápidas:</p>
                    <div class="amount-buttons">
                        <button class="amount-btn" data-amount="100">$100</button>
                        <button class="amount-btn" data-amount="200">$200</button>
                        <button class="amount-btn" data-amount="500">$500</button>
                        <button class="amount-btn" data-amount="1000">$1000</button>
                    </div>
                </div>
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
            const montoInput = document.getElementById('monto');
            const amountButtons = document.querySelectorAll('.amount-btn');
            const keypadButtons = document.querySelectorAll('.keypad-btn');
            
            // Manejar botones de cantidad rápida
            amountButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const amount = this.getAttribute('data-amount');
                    montoInput.value = amount;
                });
            });
            
            // Manejar teclado numérico
            keypadButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const key = this.getAttribute('data-key');
                    
                    if (key === 'clear') {
                        montoInput.value = '';
                    } else if (key === 'enter') {
                        document.getElementById('ingreso-form').submit();
                    } else {
                        montoInput.value += key;
                    }
                });
            });
            
            // Animación al ingresar dinero exitosamente
            <?php if($success): ?>
            setTimeout(() => {
                const moneyDeposit = document.querySelector('.money-deposit');
                if (moneyDeposit) {
                    moneyDeposit.classList.add('depositing');
                }
            }, 500);
            <?php endif; ?>
        });
    </script>
</body>
</html>