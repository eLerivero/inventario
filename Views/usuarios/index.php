<?php

// INICIAR SESIÓN PRIMERO - ¡ESTO ES IMPORTANTE!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// REQUERIR ADMINISTRADOR
require_once __DIR__ . '/../../Utils/Auth.php';

Auth::requireAdmin();

$page_title = "Gestión de Usuarios";
require_once '../layouts/header.php';

// Resto del código normal...
// Incluir controladores
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/UsuarioController.php';

// Conectar a la base de datos
$database = new Database();
$db = $database->getConnection();
$controller = new UsuarioController($db);

// Obtener lista de usuarios
$usuarios = $controller->index();

// Obtener estadísticas
$stats = $controller->getStats();

// Procesar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    switch ($action) {
        case 'activate':
            $result = $controller->activate($id);
            if ($result['success']) {
                $_SESSION['message'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header("Location: index.php");
            exit();
            
        case 'toggle_status':
            $usuario = $controller->show($id);
            if ($usuario['activo']) {
                $result = $controller->destroy($id);
            } else {
                $result = $controller->activate($id);
            }
            if ($result['success']) {
                $_SESSION['message'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header("Location: index.php");
            exit();
    }
}

// Mostrar mensajes
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

?>

<div class="container-fluid">
    <!-- Header con estadísticas -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="crear.php" class="btn btn-primary mt-4">
                <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
            </a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Total Usuarios</h6>
                            <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Activos</h6>
                            <h3 class="mb-0"><?php echo $stats['activos']; ?></h3>
                        </div>
                        <i class="fas fa-user-check fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Administradores</h6>
                            <?php 
                            $admins = 0;
                            foreach ($stats['por_rol'] as $rol) {
                                if ($rol['rol'] === 'admin') {
                                    $admins = $rol['cantidad'];
                                }
                            }
                            ?>
                            <h3 class="mb-0"><?php echo $admins; ?></h3>
                        </div>
                        <i class="fas fa-user-shield fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Usuarios Regulares</h6>
                            <?php 
                            $usuarios_regulares = 0;
                            foreach ($stats['por_rol'] as $rol) {
                                if ($rol['rol'] === 'usuario') {
                                    $usuarios_regulares = $rol['cantidad'];
                                }
                            }
                            ?>
                            <h3 class="mb-0"><?php echo $usuarios_regulares; ?></h3>
                        </div>
                        <i class="fas fa-user fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tabla de usuarios -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Lista de Usuarios
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($usuarios)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay usuarios registrados</h5>
                    <p class="text-muted">Comienza creando un nuevo usuario.</p>
                    <a href="crear.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Crear Primer Usuario
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="usuariosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Último Login</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($usuario['username']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $usuario['rol'] === 'admin' ? 'danger' : 'info'; ?>">
                                            <?php echo $usuario['rol'] === 'admin' ? 'Administrador' : 'Usuario'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($usuario['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario['ultimo_login']): ?>
                                            <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="editar.php?id=<?php echo $usuario['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                <form method="post" action="index.php" class="d-inline">
                                                    <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-<?php echo $usuario['activo'] ? 'warning' : 'success'; ?>" 
                                                            title="<?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>"
                                                            onclick="return confirm('¿Estás seguro de que deseas <?php echo $usuario['activo'] ? 'desactivar' : 'activar'; ?> este usuario?')">
                                                        <i class="fas fa-<?php echo $usuario['activo'] ? 'ban' : 'check'; ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <a href="cambiar-password.php?id=<?php echo $usuario['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="Cambiar Contraseña">
                                                    <i class="fas fa-key"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Mostrando <?php echo count($usuarios); ?> usuario(s)
            </small>
        </div>
    </div>
</div>

<script>
    // Inicializar DataTable
    document.addEventListener('DOMContentLoaded', function() {
        $('#usuariosTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 10,
            responsive: true
        });
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->