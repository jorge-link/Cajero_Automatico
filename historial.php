<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

require_once 'db.php';

$error = '';
$movimientos = [];

try {
    // Obtener historial de movimientos
    $stmt = $pdo->prepare("
        SELECT tipo, monto, fecha_hora 
        FROM movimiento 
        WHERE id_usuario = :id_usuario 
        ORDER BY fecha_hora DESC 
        LIMIT 10
    ");
    $stmt->execute(['id_usuario' => $_SESSION['user_id']]);
    $movimientos = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error al obtener el historial: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial - Cajero Automático</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="atm-container">
        <div class="atm-header">
            <h1>Banco Virtual</h1>
        </div>
        <div class="atm-screen">
            <div class="screen-content">
                <h2>Historial de Movimientos</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="balance-display">
                    <p>Saldo actual: <span class="balance">$<?php echo number_format($_SESSION['saldo'], 2); ?></span></p>
                </div>
                
                <div class="transaction-history">
                    <?php if(count($movimientos) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($movimientos as $movimiento): ?>
                            <tr class="<?php echo strtolower($movimiento['tipo']); ?>">
                                <td><?php echo htmlspecialchars($movimiento['tipo']); ?></td>
                                <td>$<?php echo number_format($movimiento['monto'], 2); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($movimiento['fecha_hora'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="no-records">No hay movimientos registrados</p>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <a href="menu.php" class="btn-secondary">Volver al Menú</a>
                </div>
            </div>
        </div>
    </div>

    <script src="session.js"></script>
</body>
</html>
