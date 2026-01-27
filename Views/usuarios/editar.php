<?php
// Views/usuarios/editar.php

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
$page_title = "Editar Usuario";
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
$formData = [
    'username' => $usuario['username'] ?? '',
    'nombre' => $usuario['nombre'] ?? '',
    'email' => $usuario['email'] ?? '',
    'rol' => $usuario['rol'] ?? 'usuario',
    'activo' => $usuario['activo'] ?? true
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_map('trim', $_POST);
    $formData['activo'] = isset($_POST['activo']) ? 1 : 0;
    
    // Validar datos
    $errors = $controller->validate($formData, true);
    
    // Validar username único (excepto para este usuario)
    if (empty($errors['username']) && $controller->usernameExists($formData['username'], $id)) {
        $errors['username'] = 'El nombre de usuario ya está en uso';
    }
    
    // Validar email único (excepto para este usuario)
    if (empty($errors['email']) && $controller->emailExists($formData['email'], $id)) {
        $errors['email'] = 'El email ya está registrado';
    }
    
    // Si no hay errores, actualizar usuario
    if (empty($errors)) {
        $result = $controller->update($id, $formData);
        
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
        <div class="col-lg-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Editar Usuario</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="../dashboard/index.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>

            <!-- Información actual -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="fas fa-info-circle me-2"></i>Información Actual
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Último Login:</strong> 
                                <?php echo $usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca'; ?>
                            </p>
                            <p><strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Actualizado:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario['updated_at'])); ?></p>
                            <p><strong>Estado:</strong> 
                                <span class="badge bg-<?php echo $usuario['activo'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
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
                        <i class="fas fa-edit me-2"></i>
                        Editar Información
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
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
                                       maxlength="50">
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
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
                                    <div class="invalid-feedback"><?php echo $errors['nombre']; ?></div>
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
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
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
                                    <option value="usuario" <?php echo $formData['rol'] === 'usuario' ? 'selected' : ''; ?>>Usuario Regular</option>
                                    <option value="admin" <?php echo $formData['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                </select>
                                <?php if (isset($errors['rol'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['rol']; ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Estado -->
                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           role="switch" 
                                           id="activo" 
                                           name="activo" 
                                           value="1"
                                           <?php echo $formData['activo'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activo">
                                        Usuario Activo
                                    </label>
                                </div>
                                <div class="form-text">
                                    Los usuarios inactivos no pueden iniciar sesión en el sistema.
                                </div>
                            </div>
                        </div>

                        <!-- Nota sobre contraseña -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-key me-2"></i>Contraseña
                                        </h6>
                                        <p class="mb-0">
                                            Para cambiar la contraseña de este usuario, utiliza la opción 
                                            <strong>"Cambiar Contraseña"</strong> desde la lista de usuarios.
                                            Este formulario solo edita la información básica del usuario.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                            <a href="cambiar-password.php?id=<?php echo $id; ?>" class="btn btn-outline-info">
                                <i class="fas fa-key me-2"></i>Cambiar Contraseña
                            </a>
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

<!-- <?php require_once '../layouts/footer.php'; ?> -->