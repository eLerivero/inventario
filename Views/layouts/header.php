<?php
// Iniciar sesión para manejar mensajes flash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Manejar mensajes flash
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Limpiar mensajes después de mostrarlos
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#1E40AF',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-primary text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-boxes text-2xl"></i>
                    <h1 class="text-xl font-bold"><?php echo SITE_NAME; ?></h1>
                </div>
                <div class="flex space-x-6">
                    <a href="../dashboard/index.php" class="hover:text-blue-200 transition duration-200 flex items-center">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="../productos/index.php" class="hover:text-blue-200 transition duration-200 flex items-center">
                        <i class="fas fa-box mr-2"></i>Productos
                    </a>
                    <a href="../ventas/index.php" class="hover:text-blue-200 transition duration-200 flex items-center">
                        <i class="fas fa-shopping-cart mr-2"></i>Ventas
                    </a>
                    <a href="../clientes/index.php" class="hover:text-blue-200 transition duration-200 flex items-center">
                        <i class="fas fa-users mr-2"></i>Clientes
                    </a>
                    <a href="../categorias/index.php" class="hover:text-blue-200 transition duration-200 flex items-center">
                        <i class="fas fa-tags mr-2"></i>Categorías
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Mostrar mensajes flash -->
        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $success_message; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $error_message; ?>
                </div>
            </div>
        <?php endif; ?>