<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/ClienteController.php';

$database = new Database();
$db = $database->getConnection();

$controller = new ClienteController($db);

$error_message = '';
$success_message = '';

// Procesar formulario
if ($_POST) {
    try {
        $result = $controller->crear($_POST);
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Redirigir después de 2 segundos
            header("Refresh: 2; URL=index.php");
        } else {
            $error_message = $result['message'];
        }
    } catch (Exception $e) {
        $error_message = "Error inesperado: " . $e->getMessage();
    }
}
?>

<?php 
$page_title = "Crear Nuevo Cliente";
include '../layouts/header.php'; 
?>

<!-- Header con Botón de Volver -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-user-plus me-2"></i>
            Crear Nuevo Cliente
        </h1>
        <p class="text-muted mb-0">Agrega un nuevo cliente al sistema de inventario</p>
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
        <div class="mt-2">
            <small>Serás redirigido automáticamente al listado de clientes...</small>
        </div>
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

<!-- Formulario -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-user-edit me-2"></i>
            Información del Cliente
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" id="formCliente" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">
                            <i class="fas fa-user me-1"></i>Nombre Completo *
                        </label>
                        <input type="text" 
                               class="form-control <?php echo isset($_POST['nombre']) && empty($_POST['nombre']) ? 'is-invalid' : ''; ?>" 
                               id="nombre" 
                               name="nombre" 
                               value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                               required
                               maxlength="100"
                               placeholder="Ej: Juan Pérez García">
                        <?php if (isset($_POST['nombre']) && empty($_POST['nombre'])): ?>
                            <div class="invalid-feedback">
                                El nombre del cliente es obligatorio.
                            </div>
                        <?php endif; ?>
                        <div class="form-text">Nombre completo del cliente.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="documento_identidad" class="form-label">
                            <i class="fas fa-id-card me-1"></i>Documento de Identidad
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="documento_identidad" 
                               name="documento_identidad" 
                               value="<?php echo htmlspecialchars($_POST['documento_identidad'] ?? ''); ?>"
                               maxlength="20"
                               placeholder="Ej: 12345678">
                        <div class="form-text">DNI, RUC, u otro documento.</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i>Email
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               maxlength="100"
                               placeholder="ejemplo@correo.com">
                        <div class="form-text">Email válido para notificaciones.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="telefono" class="form-label">
                            <i class="fas fa-phone me-1"></i>Teléfono
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="telefono" 
                               name="telefono" 
                               value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>"
                               maxlength="20"
                               placeholder="Ej: +51 987 654 321">
                        <div class="form-text">Número de contacto del cliente.</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="mb-3">
                        <label for="direccion" class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i>Dirección
                        </label>
                        <textarea class="form-control" 
                                  id="direccion" 
                                  name="direccion" 
                                  rows="3"
                                  maxlength="255"
                                  placeholder="Dirección completa del cliente..."><?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?></textarea>
                        <div class="form-text">Dirección completa para envíos o facturación.</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Guardar Cliente
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="button" class="btn btn-outline-info" onclick="limpiarFormulario()">
                        <i class="fas fa-broom me-1"></i> Limpiar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Información Adicional -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Información Importante
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Beneficios de registrar clientes:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> Historial completo de compras</li>
                    <li><i class="fas fa-check text-success me-2"></i> Mejor servicio al cliente</li>
                    <li><i class="fas fa-check text-success me-2"></i> Comunicación efectiva</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-warning">Consideraciones:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> Solo el nombre es obligatorio</li>
                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> Los emails deben ser únicos</li>
                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> Mantén la información actualizada</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCliente');
    const nombreInput = document.getElementById('nombre');
    
    // Validación del formulario
    form.addEventListener('submit', function(e) {
        const nombre = nombreInput.value.trim();
        
        // Validación básica
        if (!nombre) {
            e.preventDefault();
            nombreInput.classList.add('is-invalid');
            showToast('error', 'Por favor, ingresa el nombre del cliente.');
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
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';
        
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
    if (confirm('¿Estás seguro de que deseas limpiar el formulario? Se perderán todos los datos ingresados.')) {
        document.getElementById('formCliente').reset();
        
        // Remover clases de validación
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.classList.remove('is-valid', 'is-invalid');
        });
        
        showToast('info', 'Formulario limpiado correctamente.');
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
</style>

<!-- <?php include '../layouts/footer.php'; ?> -->