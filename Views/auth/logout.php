<?php
// Views/auth/logout.php
session_start();

// Incluir configuración y autenticación
require_once __DIR__ . '/../../Config/Config.php';
require_once __DIR__ . '/../../Utils/Auth.php';

// Cerrar sesión
Auth::logout();

// Redirigir al login con mensaje de éxito
$_SESSION['message'] = 'Sesión cerrada correctamente';
header("Location: login.php");
exit();
?>