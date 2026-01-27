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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-hover {
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .status-success {
            border-left: 4px solid #10b981;
        }
        .status-error {
            border-left: 4px solid #ef4444;
        }
        .status-warning {
            border-left: 4px solid #f59e0b;
        }
        .btn-primary {
            background-color: #374151;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #4b5563;
            transform: translateY(-1px);
        }
        .btn-success {
            background-color: #059669;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            background-color: #047857;
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gray-800 text-white p-8">
            <div class="flex flex-col md:flex-row justify-center items-center text-center md:text-left">
                <i class="fas fa-boxes text-4xl mb-4 md:mb-0 md:mr-6 text-gray-300"></i>
                <div>
                    <h1 class="text-3xl font-bold text-white">Sistema de Inventario</h1>
                    <p class="text-gray-300 mt-2 text-lg">Verificación y Configuración del Sistema</p>
                </div>
            </div>
        </div>

        <div class="p-8">
            <?php if (!empty($errores)): ?>
                <!-- Estado: Errores -->
                <div class="bg-red-50 status-error p-6 rounded-r-lg mb-8 card-hover">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl mr-4 mt-1"></i>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Requisitos del Sistema</h3>
                            <p class="text-gray-700 mb-4">Se encontraron problemas que deben resolverse antes de continuar:</p>
                            <ul class="space-y-3 bg-white rounded-lg p-4 border border-gray-200">
                                <?php foreach ($errores as $error): ?>
                                    <li class="flex items-start text-gray-800">
                                        <i class="fas fa-times-circle text-red-400 mr-3 mt-1"></i>
                                        <span><?php echo htmlspecialchars($error); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50 status-warning p-6 rounded-r-lg mb-6 card-hover">
                    <div class="flex items-start">
                        <i class="fas fa-tools text-amber-500 text-xl mr-4 mt-1"></i>
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Solución de Problemas</h4>
                            <p class="text-gray-700">Corrige los errores listados arriba y luego verifica nuevamente el sistema.</p>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button onclick="location.reload()" class="btn-primary text-white px-8 py-3 rounded-lg font-medium shadow-sm">
                        <i class="fas fa-sync-alt mr-2"></i>Verificar Nuevamente
                    </button>
                </div>

            <?php else: ?>
                <!-- Estado: Éxito -->
                <div class="bg-emerald-50 status-success p-6 rounded-r-lg mb-8 card-hover">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-emerald-500 text-xl mr-4 mt-1"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Sistema Verificado Correctamente</h3>
                            <p class="text-gray-700">Todos los requisitos del sistema están satisfechos y listos para usar.</p>
                        </div>
                    </div>
                </div>

                <!-- Tarjetas de Estado -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 card-hover">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-database text-gray-600 text-xl mr-3"></i>
                            <h3 class="text-md font-semibold text-gray-800">Base de Datos</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-check-circle text-emerald-500 mr-3"></i>
                                <span class="text-sm">PostgreSQL Conectado</span>
                            </div>
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-check-circle text-emerald-500 mr-3"></i>
                                <span class="text-sm">Estructura Verificada</span>
                            </div>
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-check-circle text-emerald-500 mr-3"></i>
                                <span class="text-sm">Tablas Configuradas</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 card-hover">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-server text-gray-600 text-xl mr-3"></i>
                            <h3 class="text-md font-semibold text-gray-800">Servidor</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-check-circle text-emerald-500 mr-3"></i>
                                <span class="text-sm">PHP <?php echo PHP_VERSION; ?></span>
                            </div>
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-check-circle text-emerald-500 mr-3"></i>
                                <span class="text-sm">Extensiones Habilitadas</span>
                            </div>
                            <div class="flex items-center text-gray-700">
                                <i class="fas fa-check-circle text-emerald-500 mr-3"></i>
                                <span class="text-sm">Permisos Configurados</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="bg-blue-50 p-6 rounded-lg border border-blue-200 mb-8 card-hover">
                    <h4 class="text-md font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>Próximos Pasos
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-3">
                            <div class="bg-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-2 shadow-sm">
                                <i class="fas fa-user-cog text-gray-600"></i>
                            </div>
                            <p class="text-sm text-gray-600 font-medium">Configurar usuarios</p>
                        </div>
                        <div class="text-center p-3">
                            <div class="bg-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-2 shadow-sm">
                                <i class="fas fa-box-open text-gray-600"></i>
                            </div>
                            <p class="text-sm text-gray-600 font-medium">Agregar productos</p>
                        </div>
                        <div class="text-center p-3">
                            <div class="bg-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-2 shadow-sm">
                                <i class="fas fa-chart-bar text-gray-600"></i>
                            </div>
                            <p class="text-sm text-gray-600 font-medium">Revisar reportes</p>
                        </div>
                    </div>
                </div>

                <!-- Botón de Inicio -->
                <div class="text-center">
                    <a href="Views/auth/login.php" class="btn-success text-white px-10 py-3 rounded-lg font-semibold shadow-sm inline-block">
                        <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Profesional -->
        <footer class="bg-gray-800 text-gray-300 py-6 border-t border-gray-700">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <!-- Información del sistema -->
                    <div class="mb-4 md:mb-0 text-center md:text-left">
                        <h3 class="text-sm font-semibold text-gray-200 mb-2">Información del Sistema</h3>
                        <div class="flex flex-wrap justify-center md:justify-start gap-4 text-xs">
                            <div class="flex items-center">
                                <i class="fab fa-php mr-1 text-gray-400"></i>
                                <span>PHP <?php echo PHP_VERSION; ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-database mr-1 text-gray-400"></i>
                                <span>PostgreSQL</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-center md:justify-start mt-2 text-xs">
                            <i class="fas fa-code mr-2 text-gray-400"></i>
                            <span>Desarrollado por <span class="text-gray-200">@by Lr</span></span>
                        </div>
                    </div>
                    
                    <!-- Información de contacto -->
                    <div class="text-center md:text-right">
                        <h3 class="text-sm font-semibold text-gray-200 mb-2">Contacto</h3>
                        <div class="flex items-center justify-center md:justify-end text-xs">
                            <i class="fas fa-envelope mr-2 text-gray-400"></i>
                            <span>soluccionesweb@gmail.com</span>
                        </div>
                        <div class="flex justify-center md:justify-end space-x-3 mt-2 text-gray-400">
                            <i class="fab fa-github cursor-pointer hover:text-gray-200 transition-colors"></i>
                            <i class="fab fa-linkedin cursor-pointer hover:text-gray-200 transition-colors"></i>
                            <i class="fab fa-twitter cursor-pointer hover:text-gray-200 transition-colors"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Línea divisoria -->
                <div class="h-px w-full bg-gray-700 my-4"></div>
                
                <!-- Copyright -->
                <div class="text-center text-gray-400 text-xs">
                    &copy; <?php echo date('Y'); ?> Sistema de Inventario. Todos los derechos reservados.
                </div>
            </div>
        </footer>
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

    // Verificar si las tablas principales existen
    $query = "SELECT COUNT(*) as total FROM information_schema.tables 
              WHERE table_schema = 'public' 
              AND table_name IN ('productos', 'categorias', 'ventas', 'clientes', 'usuarios')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] < 5) {
        throw new Exception("La base de datos no está completamente configurada. Ejecuta el script SQL de instalación.");
    }
    
    // Verificar si existe al menos un usuario activo
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE activo = true";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] == 0) {
        // Crear usuario administrador por defecto si no existe
        require_once __DIR__ . '/Utils/Auth.php';
        require_once __DIR__ . '/Models/Usuario.php';
        
        $usuarioModel = new Usuario($db);
        $usuarioModel->username = 'administrador';
        $usuarioModel->password_hash = 'admin123'; // Se hasheará en el método crear
        $usuarioModel->nombre = 'Administrador';
        $usuarioModel->email = 'admin@sistema.com';
        $usuarioModel->rol = 'admin';
        
        if ($usuarioModel->crear()) {
            error_log("Usuario administrador creado automáticamente.");
        }
    }
    
} catch (Exception $e) {
    $errores[] = "Error de base de datos: " . $e->getMessage();
    mostrarPaginaInstalacion($errores);
}

// Si todo está bien y ya hay un usuario logueado, redirigir al dashboard
session_start();
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: Views/dashboard/index.php");
    exit();
}

// Si no está logueado, redirigir al login
header("Location: Views/auth/login.php");
exit();
?>