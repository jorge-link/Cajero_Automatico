<?php
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redireccionar a la página de inicio con mensaje de cierre
header("Location: index.html?logout=success");
exit;
