<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/CategoriaController.php';
require_once '../../Utils/Ayuda.php';
require_once __DIR__ . '/../../Utils/Auth.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Auth::requireAccessToCategorias();

$database = new Database();
$db = $database->getConnection();

$controller = new CategoriaController($db);

$error_message = '';
$success_message = '';

// Obtener ID de la categoría
$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: index.php');
    exit();
}

// Obtener datos de la categoría
$result = $controller->obtener($id);
if (!$result['success']) {
    $error_message = $result['message'];
    $categoria = null;
} else {
    $categoria = $result['data'];
}

// Procesar formulario
if ($_POST && $categoria) {
    $result = $controller->actualizar($id, $_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        // Actualizar datos locales
        $categoria['nombre'] = $_POST['nombre'];
        $categoria['descripcion'] = $_POST['descripcion'];
        $categoria['updated_at'] = date('Y-m-d H:i:s');
    } else {
        $error_message = $result['message'];
    }
}
?>

<?php 
$page_title = "Editar Categoría";
include '../layouts/header.php'; 
?>

<!-- Header con Botón de Volver -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-edit me-2"></i>
            Editar Categoría
        </h1>
        <p class="text-muted mb-0">
            <?php if ($categoria): ?>
                Editando: <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
            <?php else: ?>
                Categoría no encontrada
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Listado
        </a>
    </div>
</div>

<!-- Alertas -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$categoria): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        No se encontró la categoría solicitada.
        <div class="mt-2">
            <a href="index.php" class="btn btn-sm btn-warning">
                <i class="fas fa-arrow-left me-1"></i> Volver al listado
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <!-- Formulario -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Información de la Categoría
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="formCategoria" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="fas fa-tag me-1"></i>Nombre de la Categoría *
                                    </label>
                                    <input type="text" 
                                           class="form-control <?php echo isset($_POST['nombre']) && empty($_POST['nombre']) ? 'is-invalid' : ''; ?>" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="<?php echo htmlspecialchars($categoria['nombre']); ?>"
                                           required
                                           maxlength="100"
                                           placeholder="Ej: Electrónicos, Ropa, Hogar...">
                                    <?php if (isset($_POST['nombre']) && empty($_POST['nombre'])): ?>
                                        <div class="invalid-feedback">
                                            El nombre de la categoría es obligatorio.
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-text">El nombre debe ser único y descriptivo.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>Descripción
                                    </label>
                                    <textarea class="form-control" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              rows="4"
                                              maxlength="500"
                                              placeholder="Describe brevemente esta categoría..."><?php echo htmlspecialchars($categoria['descripcion']); ?></textarea>
                                    <div class="form-text">Máximo 500 caracteres. <span id="charCount"><?php echo strlen($categoria['descripcion']); ?></span>/500</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Actualizar Categoría
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="button" class="btn btn-outline-info" onclick="limpiarFormulario()">
                                    <i class="fas fa-undo me-1"></i> Restaurar Valores
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Información de la Categoría -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información del Registro
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">ID:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-dark">#<?php echo $categoria['id']; ?></span>
                        </dd>

                        <dt class="col-sm-5">Estado:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-<?php echo ($categoria['activo'] ?? 1) ? 'success' : 'secondary'; ?>">
                                <?php echo ($categoria['activo'] ?? 1) ? 'Activa' : 'Inactiva'; ?>
                            </span>
                        </dd>

                        <dt class="col-sm-5">Creado:</dt>
                        <dd class="col-sm-7">
                            <small class="text-muted">
                                <?php echo Ayuda::formatDate($categoria['created_at'], 'd/m/Y H:i:s'); ?>
                            </small>
                        </dd>

                        <dt class="col-sm-5">Actualizado:</dt>
                        <dd class="col-sm-7">
                            <small class="text-muted">
                                <?php 
                                $updated_at = $categoria['updated_at'] ?? $categoria['created_at'];
                                echo Ayuda::formatDate($updated_at, 'd/m/Y H:i:s'); 
                                ?>
                            </small>
                        </dd>

                        <?php if (isset($categoria['total_productos'])): ?>
                            <dt class="col-sm-5">Productos:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-<?php echo $categoria['total_productos'] > 0 ? 'primary' : 'secondary'; ?>">
                                    <?php echo $categoria['total_productos']; ?> productos
                                </span>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Recomendaciones
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <small>Usa nombres claros y descriptivos</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <small>Actualiza la descripción si es necesario</small>
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            <small>Los cambios afectarán a todos los productos asociados</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const descripcion = document.getElementById('descripcion');
    const charCount = document.getElementById('charCount');
    const nombreInput = document.getElementById('nombre');
    const form = document.getElementById('formCategoria');
    
    // Valores originales para restaurar
    const originalValues = {
        nombre: '<?php echo addslashes($categoria['nombre'] ?? ''); ?>',
        descripcion: '<?php echo addslashes($categoria['descripcion'] ?? ''); ?>'
    };

    // Contador de caracteres para descripción
    descripcion.addEventListener('input', function() {
        charCount.textContent = this.value.length;
        
        if (this.value.length > 450) {
            charCount.className = 'text-warning';
        } else {
            charCount.className = 'text-muted';
        }
    });
    
    // Validación del formulario
    form.addEventListener('submit', function(e) {
        const nombre = nombreInput.value.trim();
        
        // Validación básica
        if (!nombre) {
            e.preventDefault();
            nombreInput.classList.add('is-invalid');
            showToast('error', 'Por favor, ingresa el nombre de la categoría.');
            return false;
        }
        
        // Validación de longitud
        if (nombre.length > 100) {
            e.preventDefault();
            nombreInput.classList.add('is-invalid');
            showToast('error', 'El nombre no puede tener más de 100 caracteres.');
            return false;
        }
        
        // Mostrar indicador de carga
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...';
        
        return true;
    });
    
    // Validación en tiempo real
    nombreInput.addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
        }
    });
});

function limpiarFormulario() {
    if (confirm('¿Estás seguro de que deseas restaurar los valores originales? Se perderán los cambios no guardados.')) {
        document.getElementById('nombre').value = '<?php echo addslashes($categoria['nombre'] ?? ''); ?>';
        document.getElementById('descripcion').value = '<?php echo addslashes($categoria['descripcion'] ?? ''); ?>';
        
        // Actualizar contador
        document.getElementById('charCount').textContent = '<?php echo strlen($categoria['descripcion'] ?? ''); ?>';
        document.getElementById('charCount').className = 'text-muted';
        
        // Remover clases de validación
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.classList.remove('is-valid', 'is-invalid');
        });
        
        showToast('info', 'Valores restaurados correctamente.');
    }
}

function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<style>
.form-control.is-valid {
    border-color: #198754;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.toast {
    font-size: 0.875rem;
}
</style>

<?php include '../layouts/footer.php'; ?>