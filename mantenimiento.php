<?php
session_start();

// Verificar si el usuario está autenticado y si es el administrador (PIN 9999)
$isAdmin = false;

if (isset($_SESSION['user_id'])) {
    // Incluir conexión a la base de datos
    require_once 'db.php';
    
    try {
        $stmt = $pdo->prepare("SELECT pin FROM usuario WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $usuario = $stmt->fetch();
        
        if ($usuario && $usuario['pin'] === '9999') {
            $isAdmin = true;
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Si no es administrador, redirigir
if (!$isAdmin) {
    header("Location: logout.php");
    exit;
}

$mensaje = $error = '';

// Procesar acciones del modo mantenimiento
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accion'])) {
        $accion = sanitize($_POST['accion']);
        
        try {
            switch ($accion) {
                case 'reiniciar_saldos':
                    // Reiniciar saldos a valor predeterminado
                    $sql = "UPDATE usuario SET saldo = 1000.00 WHERE pin != '9999'";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    $mensaje = "Saldos reiniciados correctamente";
                    break;
                    
                case 'vaciar_historial':
                    // Vaciar historial de movimientos
                    $sql = "TRUNCATE TABLE movimiento";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    $mensaje = "Historial de movimientos vaciado correctamente";
                    break;
                    
                default:
                    $error = "Acción no reconocida";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Obtener historial de cambios de PIN
$historial_pin = [];
try {
    $sql = "
        SELECT h.id, h.id_usuario, h.fecha_hora 
        FROM historial_pin h
        JOIN usuario u ON h.id_usuario = u.id
        ORDER BY h.fecha_hora DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $historial_pin = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error al obtener historial de PIN: " . $e->getMessage();
}

// Obtener lista de usuarios
$usuarios = [];
try {
    $sql = "SELECT id, pin, saldo, fecha_ingreso FROM usuario WHERE pin != '9999'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error al obtener usuarios: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modo Mantenimiento - Cajero Automático</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-panel {
            background-color: #334;
            color: #fff;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .admin-section {
            margin-bottom: 30px;
        }
        
        .admin-actions form {
            display: inline-block;
            margin-right: 10px;
        }
        
        .danger-btn {
            background-color: #d9534f;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .danger-btn:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>
    <div class="atm-container admin-mode">
        <div class="atm-header">
            <h1>Banco Virtual - Modo Mantenimiento</h1>
        </div>
        <div class="atm-screen admin">
            <div class="screen-content">
                <div class="admin-panel">
                    <h2>Panel de Administración</h2>
                    
                    <?php if($mensaje): ?>
                        <div class="alert alert-success"><?php echo $mensaje; ?></div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="admin-section">
                        <h3>Acciones de Mantenimiento</h3>
                        <div class="admin-actions">
                            <form method="POST">
                                <input type="hidden" name="accion" value="reiniciar_saldos">
                                <button type="submit" class="danger-btn">Reiniciar Saldos</button>
                            </form>
                            
                            <form method="POST">
                                <input type="hidden" name="accion" value="vaciar_historial">
                                <button type="submit" class="danger-btn">Vaciar Historial de Movimientos</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="admin-section">
                        <h3>Lista de Usuarios</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>PIN</th>
                                    <th>Saldo</th>
                                    <th>Último Ingreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($usuarios as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo $user['pin']; ?></td>
                                    <td>$<?php echo number_format($user['saldo'], 2); ?></td>
                                    <td>
                                        <?php 
                                        echo $user['fecha_ingreso'] 
                                            ? date('d/m/Y H:i', strtotime($user['fecha_ingreso'])) 
                                            : 'Nunca';
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="admin-section">
                        <h3>Historial de Cambios de PIN</h3>
                        <?php if(count($historial_pin) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Registro</th>
                                    <th>Usuario ID</th>
                                    <th>Fecha y Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($historial_pin as $registro): ?>
                                <tr>
                                    <td><?php echo $registro['id']; ?></td>
                                    <td><?php echo $registro['id_usuario']; ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($registro['fecha_hora'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p>No hay registros de cambios de PIN</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <a href="logout.php" class="btn-primary">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="session.js"></script>
</body>
</html>
