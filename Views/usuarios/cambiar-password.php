<?php
// Views/usuarios/cambiar-password.php

// INICIAR SESIÓN PRIMERO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// REQUERIR ADMINISTRADOR
require_once __DIR__ . '/../../Utils/Auth.php';
Auth::requireAdmin();

// Verificar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ID de usuario inválido';
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$page_title = "Cambiar Contraseña";
require_once '../layouts/header.php';

// Incluir controladores
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/UsuarioController.php';

// Conectar a la base de datos
$database = new Database();
$db = $database->getConnection();
$controller = new UsuarioController($db);

// Obtener usuario
$usuario = $controller->show($id);
if (!$usuario) {
    $_SESSION['error'] = 'Usuario no encontrado';
    header("Location: index.php");
    exit();
}

// Variables
$errors = [];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validar contraseña
    if (empty($password) || strlen($password) < 6) {
        $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Las contraseñas no coinciden';
    }
    
    // Si no hay errores, cambiar contraseña
    if (empty($errors)) {
        $result = $controller->cambiarPassword($id, $password);
        
        if ($result['success']) {
            $_SESSION['message'] = $result['message'];
            header("Location: index.php");
            exit();
        } else {
            $errors['general'] = $result['message'];
        }
    }
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Cambiar Contraseña</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
                            <li class="breadcrumb-item active">Cambiar Contraseña</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>

            <!-- Información del usuario -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-user me-2"></i>Información del Usuario
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario['username']); ?></p>
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                            <p><strong>Rol:</strong> 
                                <span class="badge bg-<?php echo $usuario['rol'] === 'admin' ? 'danger' : 'info'; ?>">
                                    <?php echo $usuario['rol'] === 'admin' ? 'Administrador' : 'Usuario'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensajes de error -->
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $errors['general']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-key me-2"></i>
                        Nueva Contraseña
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-4">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Advertencia
                                </h6>
                                <p class="mb-0">
                                    Al cambiar la contraseña, el usuario no podrá acceder con su contraseña anterior.
                                    Se recomienda notificar al usuario sobre este cambio.
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Nueva Contraseña -->
                            <div class="col-12 mb-3">
                                <label for="password" class="form-label">
                                    Nueva Contraseña <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                           id="password" 
                                           name="password" 
                                           required
                                           minlength="6"
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                <?php else: ?>
                                    <div class="form-text">Mínimo 6 caracteres. Se recomienda usar una combinación segura.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Confirmar Contraseña -->
                            <div class="col-12 mb-3">
                                <label for="confirm_password" class="form-label">
                                    Confirmar Contraseña <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           required
                                           minlength="6"
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                <?php endif; ?>
                                <div class="form-text" id="passwordMatchMessage"></div>
                            </div>
                        </div>

                        <!-- Generador de contraseñas -->
                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="fas fa-wand-magic-sparkles me-2"></i>Generar Contraseña Segura
                                    </h6>
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="generatedPassword" readonly>
                                        </div>
                                        <div class="col-md-4 mt-2 mt-md-0">
                                            <button type="button" class="btn btn-outline-primary w-100" id="generatePassword">
                                                <i class="fas fa-sync-alt me-2"></i>Generar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-success" id="useGeneratedPassword">
                                            <i class="fas fa-check me-1"></i>Usar esta contraseña
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Cambiar Contraseña
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Mostrar/ocultar contraseña
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
        const confirmInput = document.getElementById('confirm_password');
        const icon = this.querySelector('i');
        
        if (confirmInput.type === 'password') {
            confirmInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            confirmInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Validar que las contraseñas coincidan
    function validatePasswords() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const message = document.getElementById('passwordMatchMessage');
        
        if (confirmPassword === '') {
            message.textContent = '';
            message.className = 'form-text';
        } else if (password === confirmPassword) {
            message.textContent = '✓ Las contraseñas coinciden';
            message.className = 'form-text text-success';
        } else {
            message.textContent = '✗ Las contraseñas no coinciden';
            message.className = 'form-text text-danger';
        }
    }

    document.getElementById('confirm_password').addEventListener('input', validatePasswords);
    document.getElementById('password').addEventListener('input', validatePasswords);

    // Generador de contraseñas
    document.getElementById('generatePassword').addEventListener('click', function() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        let password = '';
        
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        document.getElementById('generatedPassword').value = password;
    });

    document.getElementById('useGeneratedPassword').addEventListener('click', function() {
        const generatedPassword = document.getElementById('generatedPassword').value;
        if (generatedPassword) {
            document.getElementById('password').value = generatedPassword;
            document.getElementById('confirm_password').value = generatedPassword;
            validatePasswords();
        }
    });

    // Validación del formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Las contraseñas no coinciden. Por favor, verifica.');
            document.getElementById('confirm_password').focus();
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 6 caracteres.');
            document.getElementById('password').focus();
        }
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->