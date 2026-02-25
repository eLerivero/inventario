<?php
// Views/usuarios/crear.php

// INICIAR SESIÓN PRIMERO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// REQUERIR ADMINISTRADOR
require_once __DIR__ . '/../../Utils/Auth.php';

// Verificar que sea administrador usando la nueva función
Auth::requireAdmin();

$page_title = "Crear Nuevo Usuario";
require_once '../layouts/header.php';

// Incluir controladores
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/UsuarioController.php';

// Conectar a la base de datos
$database = new Database();
$db = $database->getConnection();
$controller = new UsuarioController($db);

// Variables
$errors = [];
$formData = [
    'username' => '',
    'nombre' => '',
    'email' => '',
    'rol' => 'usuario',
    'password' => ''
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener y limpiar datos
        $formData = [
            'username' => trim($_POST['username'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'rol' => trim($_POST['rol'] ?? 'usuario'),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];
        
        // Validar datos
        $errors = $controller->validate($formData, false);
        
        // Validar que las contraseñas coincidan
        if (empty($errors['password']) && $formData['password'] !== $formData['confirm_password']) {
            $errors['confirm_password'] = 'Las contraseñas no coinciden';
        }
        
        // Validar username único
        if (empty($errors['username']) && $controller->usernameExists($formData['username'])) {
            $errors['username'] = 'El nombre de usuario ya está en uso';
        }
        
        // Validar email único
        if (empty($errors['email']) && $controller->emailExists($formData['email'])) {
            $errors['email'] = 'El email ya está registrado';
        }
        
        // Si no hay errores, crear usuario
        if (empty($errors)) {
            // Remover confirm_password antes de enviar al controlador
            unset($formData['confirm_password']);
            
            $result = $controller->store($formData);
            
            if ($result['success']) {
                $_SESSION['message'] = $result['message'];
                
                // Redirección con JavaScript como respaldo
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "index.php";
                    }, 1000);
                </script>';
                
                // Redirección PHP
                header("Location: index.php");
                exit();
            } else {
                $errors['general'] = $result['message'];
            }
        }
    } catch (Exception $e) {
        $errors['general'] = "Error inesperado: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Crear Nuevo Usuario</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
                            <li class="breadcrumb-item active">Crear</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>

            <!-- Mensajes de error general -->
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
                        <i class="fas fa-user-plus me-2"></i>
                        Información del Usuario
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="formCrearUsuario">
                        <div class="row">
                            <!-- Username -->
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">
                                    Nombre de Usuario <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                       id="username" 
                                       name="username" 
                                       value="<?php echo htmlspecialchars($formData['username']); ?>"
                                       required
                                       minlength="3"
                                       maxlength="50"
                                       pattern="[a-zA-Z0-9_]+"
                                       title="Solo letras, números y guiones bajos">
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo $errors['username']; ?></div>
                                <?php else: ?>
                                    <div class="form-text">Mínimo 3 caracteres. Solo letras, números y guiones bajos.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Nombre -->
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">
                                    Nombre Completo <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control <?php echo isset($errors['nombre']) ? 'is-invalid' : ''; ?>" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?php echo htmlspecialchars($formData['nombre']); ?>"
                                       required
                                       minlength="2"
                                       maxlength="100">
                                <?php if (isset($errors['nombre'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo $errors['nombre']; ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($formData['email']); ?>"
                                       required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo $errors['email']; ?></div>
                                <?php else: ?>
                                    <div class="form-text">Se usará para notificaciones y recuperación de contraseña.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Rol -->
                            <div class="col-md-6 mb-3">
                                <label for="rol" class="form-label">
                                    Rol <span class="text-danger">*</span>
                                </label>
                                <select class="form-control <?php echo isset($errors['rol']) ? 'is-invalid' : ''; ?>" 
                                        id="rol" 
                                        name="rol" 
                                        required>
                                    <option value="usuario" <?php echo $formData['rol'] === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                                    <option value="admin" <?php echo $formData['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                </select>
                                <?php if (isset($errors['rol'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo $errors['rol']; ?></div>
                                <?php else: ?>
                                    <div class="form-text">Los administradores tienen acceso completo al sistema.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Contraseña -->
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">
                                    Contraseña <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                           id="password" 
                                           name="password" 
                                           value="<?php echo htmlspecialchars($formData['password']); ?>"
                                           required
                                           minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo $errors['password']; ?></div>
                                <?php else: ?>
                                    <div class="form-text">Mínimo 6 caracteres. Se recomienda usar una combinación de letras, números y símbolos.</div>
                                <?php endif; ?>
                            </div>

                            <!-- Confirmar Contraseña -->
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">
                                    Confirmar Contraseña <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           required
                                           minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <div class="invalid-feedback d-block"><?php echo $errors['confirm_password']; ?></div>
                                <?php endif; ?>
                                <div class="form-text" id="passwordMatchMessage"></div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" id="btnSubmit">
                                <i class="fas fa-save me-2"></i>Crear Usuario
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

    // Validar que las contraseñas coincidan en tiempo real
    document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
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
    });

    // Validación del formulario antes de enviar
    document.getElementById('formCrearUsuario').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const username = document.getElementById('username').value;
        
        // Validar contraseñas
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Las contraseñas no coinciden. Por favor, verifica.');
            document.getElementById('confirm_password').focus();
            return false;
        }
        
        if (password.length < 6) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 6 caracteres.');
            document.getElementById('password').focus();
            return false;
        }
        
        // Validar formato de username
        const usernamePattern = /^[a-zA-Z0-9_]+$/;
        if (!usernamePattern.test(username)) {
            e.preventDefault();
            alert('El nombre de usuario solo puede contener letras, números y guiones bajos.');
            document.getElementById('username').focus();
            return false;
        }
        
        // Mostrar loading
        const submitBtn = document.getElementById('btnSubmit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
        
        return true;
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->