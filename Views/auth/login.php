<?php
// Views/auth/login.php
session_start();

// Habilitar logging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Registrar en logs
function log_message($message) {
    $log_file = __DIR__ . '/../../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: ../dashboard/index.php");
    exit();
}

require_once '../../Config/Database.php';
require_once '../../Controllers/AuthController.php';

$error = '';
$success = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_message("Intento de login - Usuario: " . ($_POST['username'] ?? ''));
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor complete todos los campos';
        log_message("Error: Campos vacíos");
    } else {
        log_message("Intentando autenticar usuario: $username");
        
        $authController = new AuthController();
        
        // Debug: mostrar lo que recibe el controlador
        log_message("Username recibido: $username");
        log_message("Password recibido: " . substr($password, 0, 3) . "...");
        
        $result = $authController->login($username, $password);
        
        log_message("Resultado del login: " . json_encode($result));
        
        if ($result['success']) {
            require_once '../../Utils/Auth.php';
            Auth::login($result['user']);
            
            log_message("Login exitoso para usuario: " . $result['user']['username']);
            
            // Redirigir según el rol
            $redirect = '../dashboard/index.php';
            
            header("Location: $redirect");
            exit();
        } else {
            $error = $result['message'];
            log_message("Login fallido: " . $result['message']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Inventario</title>
    
    <!-- Incluir Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Incluir Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos personalizados -->
    <style>
        .login-container {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .login-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .login-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
        }

        .btn-primary {
            background: #3498db;
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid transparent;
        }

        .alert-danger {
            background-color: rgba(231, 76, 60, 0.1);
            color: #721c24;
            border-left-color: #e74c3c;
        }

        .alert-success {
            background-color: rgba(39, 174, 96, 0.1);
            color: #155724;
            border-left-color: #27ae60;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .input-with-icon {
            padding-left: 3rem;
        }

        .text-muted {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #3498db;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-boxes login-icon"></i>
                <h1 class="text-2xl font-bold">Sistema de Inventario</h1>
                <p class="opacity-90 mt-2">Ingresa a tu cuenta</p>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <i class="fas fa-user input-group-icon"></i>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control input-with-icon" 
                               placeholder="Usuario o Email" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock input-group-icon"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control input-with-icon" 
                               placeholder="Contraseña" 
                               required>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-sign-in-alt mr-2"></i>Ingresar
                        </button>
                    </div>

                    <a href="#" class="forgot-password">
                        <i class="fas fa-key mr-1"></i>¿Olvidaste tu contraseña?
                    </a>
                </form>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <p class="text-muted text-center">
                        <strong>Credenciales de prueba:</strong><br>
                        <small>Usuario: admin | Contraseña: admin123</small>
                    </p>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 text-center">
                <p class="text-sm text-gray-600">
                    <i class="fas fa-code mr-1"></i>
                    Sistema de Inventario v1.0.0
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    &copy; <?php echo date('Y'); ?> - Desarrollado por @by Lr
                </p>
            </div>
        </div>
    </div>

    <!-- Script para mejorar la experiencia de usuario -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enfocar el primer campo
            document.getElementById('username').focus();
            
            // Prevenir reenvío del formulario
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            // Mostrar/ocultar contraseña
            const passwordInput = document.getElementById('password');
            const togglePassword = document.createElement('span');
            togglePassword.innerHTML = '<i class="fas fa-eye"></i>';
            togglePassword.className = 'absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer text-gray-500';
            togglePassword.style.cursor = 'pointer';
            
            passwordInput.parentNode.style.position = 'relative';
            passwordInput.parentNode.appendChild(togglePassword);
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
        });
    </script>
</body>
</html>