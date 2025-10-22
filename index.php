<?php
// Archivo principal - Punto de entrada de la aplicación

// Cargar configuración con rutas absolutas
$configPath = __DIR__ . '/Config/Config.php';
if (!file_exists($configPath)) {
    die("Error: No se puede encontrar el archivo de configuración en: $configPath");
}
require_once $configPath;

// Verificar requisitos del sistema
function verificarRequisitos()
{
    $errores = [];

    // Verificar versión de PHP
    if (version_compare(PHP_VERSION, '7.4.0') < 0) {
        $errores[] = "Se requiere PHP 7.4.0 o superior. Versión actual: " . PHP_VERSION;
    }

    // Verificar extensiones requeridas
    $extensiones_requeridas = ['pdo', 'pdo_pgsql', 'json', 'mbstring'];
    foreach ($extensiones_requeridas as $ext) {
        if (!extension_loaded($ext)) {
            $errores[] = "Extensión $ext no está habilitada";
        }
    }

    // Verificar permisos de directorios
    $directorios = ['logs', 'uploads'];
    foreach ($directorios as $dir) {
        $path = __DIR__ . '/' . $dir;
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                $errores[] = "No se pudo crear el directorio: $dir";
            }
        } else if (!is_writable($path)) {
            $errores[] = "El directorio $dir no tiene permisos de escritura";
        }
    }

    return $errores;
}

// Página de instalación/verificación
function mostrarPaginaInstalacion($errores = [])
{
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Inventario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <i class="fas fa-boxes text-6xl text-blue-500 mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800">Sistema de Inventario</h1>
            <p class="text-gray-600 mt-2">Verificación del sistema</p>
        </div>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Se encontraron los siguientes errores:</strong>
                </div>
                <ul class="mt-2 list-disc list-inside">
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="text-center">
                <p class="text-gray-600 mb-4">Por favor, corrige los errores y actualiza esta página.</p>
                <button onclick="location.reload()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-redo mr-2"></i>Reintentar
                </button>
            </div>
        <?php else: ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <strong>¡Sistema verificado correctamente!</strong>
                </div>
                <p class="mt-2">Todos los requisitos del sistema están satisfechos.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-database mr-2"></i>Base de Datos
                    </h3>
                    <p class="text-sm text-blue-600">PostgreSQL configurado correctamente</p>
                </div>

                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-green-800 mb-2">
                        <i class="fas fa-server mr-2"></i>Servidor
                    </h3>
                    <p class="text-sm text-green-600">PHP <?php echo PHP_VERSION; ?></p>
                </div>
            </div>

            <div class="text-center">
                <a href="Views/dashboard/index.php" class="bg-green-500 hover:bg-green-600 text-white px-8 py-3 rounded-lg transition duration-200 font-semibold">
                    <i class="fas fa-rocket mr-2"></i>Iniciar Sistema
                </a>
            </div>
        <?php endif; ?>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="text-center text-gray-500 text-sm">
                <p><strong>Información del Sistema:</strong></p>
                <p>PHP <?php echo PHP_VERSION; ?> | PostgreSQL | Desarrollado por @by Lr</p>
            </div>
        </div>
    </div>
</body>
</html>
<?php
    exit();
}

// Verificar si es la primera vez o hay errores
$errores = verificarRequisitos();

// Si hay errores, mostrar página de instalación
if (!empty($errores)) {
    mostrarPaginaInstalacion($errores);
}

// Verificar conexión a la base de datos
try {
    require_once __DIR__ . '/Config/Database.php';
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }

    // Verificar si las tablas existen
    $query = "SELECT COUNT(*) as total FROM information_schema.tables 
              WHERE table_schema = 'public' 
              AND table_name IN ('productos', 'categorias', 'ventas', 'clientes')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] < 4) {
        throw new Exception("La base de datos no está completamente configurada. Ejecuta el script SQL de instalación.");
    }
} catch (Exception $e) {
    $errores[] = "Error de base de datos: " . $e->getMessage();
    mostrarPaginaInstalacion($errores);
}

// Si todo está bien, redirigir al dashboard
header("Location: Views/dashboard/index.php");
exit();
?>