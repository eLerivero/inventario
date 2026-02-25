<?php
// 1. INCLUIR CONTROLADORES Y CONFIGURACIÓN
require_once '../../Controllers/TipoPagoController.php';
require_once '../../Config/Database.php';

require_once __DIR__ . '/../../Utils/Auth.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Auth::requireAccessToTiposPagos();



$database = new Database();
$db = $database->getConnection();
$tipoPagoController = new TipoPagoController($db);

// 2. PROCESAR FORMULARIO
$mensaje = '';
$tipoMensaje = '';

if ($_POST) {
    try {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? '')
        ];

        $resultado = $tipoPagoController->crear($data);

        if ($resultado['success']) {
            $mensaje = 'Tipo de pago creado exitosamente';
            $tipoMensaje = 'success';
            // Redirigir después de 2 segundos
            echo '<script>setTimeout(() => { window.location.href = "index.php"; }, 2000);</script>';
        } else {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'danger';
        }
    } catch (Exception $e) {
        $mensaje = "Error inesperado: " . $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

// 3. DEFINIR TÍTULO Y INCLUIR HEADER
$page_title = "Crear Nuevo Tipo de Pago";
require_once '../layouts/header.php';
?>

<!-- 4. CONTENIDO PRINCIPAL DE LA VISTA -->
<div class="content-wrapper crear-tipo-pago-content">
    
    <!-- Header de la página -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-plus me-2"></i>Crear Nuevo Tipo de Pago
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Listado
            </a>
        </div>
    </div>

    <!-- Alertas del sistema -->
    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($mensaje); ?>
            <?php if ($tipoMensaje === 'success'): ?>
                <div class="mt-2">
                    <small>Serás redirigido automáticamente al listado de tipos de pago...</small>
                </div>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario de creación -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-edit me-2"></i>
                Información del Tipo de Pago
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" id="formTipoPago" novalidate>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-credit-card me-1"></i>Nombre del Tipo de Pago *
                            </label>
                            <input type="text" 
                                   class="form-control <?php echo isset($_POST['nombre']) && empty($_POST['nombre']) ? 'is-invalid' : ''; ?>" 
                                   id="nombre" 
                                   name="nombre"
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" 
                                   required
                                   maxlength="50"
                                   placeholder="Ej: Efectivo, Tarjeta de Crédito, Transferencia...">
                            <?php if (isset($_POST['nombre']) && empty($_POST['nombre'])): ?>
                                <div class="invalid-feedback">
                                    El nombre del tipo de pago es obligatorio.
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Nombre único para identificar el método de pago</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">
                        <i class="fas fa-align-left me-1"></i>Descripción
                    </label>
                    <textarea class="form-control" 
                              id="descripcion" 
                              name="descripcion" 
                              rows="3"
                              maxlength="255"
                              placeholder="Describe las características de este método de pago..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                    <div class="form-text">
                        Máximo 255 caracteres. 
                        <span id="charCount" class="char-counter-info">0</span>/255
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="alert alert-info mt-4">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>Información Importante
                    </h6>
                    <ul class="mb-0">
                        <li>Los campos marcados con <span class="text-danger">*</span> son obligatorios</li>
                        <li>El nombre del tipo de pago debe ser único en el sistema</li>
                        <li>Una vez creado, el tipo de pago estará disponible inmediatamente para las ventas</li>
                        <li>Puedes editar esta información posteriormente si es necesario</li>
                    </ul>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Guardar Tipo de Pago
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

</div> 

<!-- End content-wrapper -->

<!-- 5. SCRIPTS ESPECÍFICOS MEJORADOS -->
<script>
// Ajustar dinámicamente el espacio para evitar superposición con footer
function ajustarEspacioFooter() {
    const contentWrapper = document.querySelector('.crear-tipo-pago-content');
    const footer = document.querySelector('.footer-container');
    
    if (contentWrapper && footer) {
        const contentHeight = contentWrapper.scrollHeight;
        const windowHeight = window.innerHeight;
        const headerHeight = document.querySelector('.header-container')?.offsetHeight || 0;
        
        // Si el contenido es más corto que la ventana, agregar padding
        if (contentHeight + headerHeight < windowHeight - 100) {
            contentWrapper.style.minHeight = `calc(100vh - ${headerHeight + 150}px)`;
        } else {
            // Si el contenido es largo, asegurar espacio al final
            contentWrapper.style.paddingBottom = '3rem';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const descripcion = document.getElementById('descripcion');
    const charCount = document.getElementById('charCount');
    
    // Contador de caracteres para descripción
    descripcion.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = length;
        
        if (length > 200) {
            charCount.className = 'char-counter-warning';
        } else if (length > 150) {
            charCount.className = 'text-warning';
        } else {
            charCount.className = 'char-counter-info';
        }
    });
    
    // Inicializar contador
    charCount.textContent = descripcion.value.length;
    if (descripcion.value.length > 200) {
        charCount.className = 'char-counter-warning';
    } else if (descripcion.value.length > 150) {
        charCount.className = 'text-warning';
    }
    
    // Validación del formulario
    const form = document.getElementById('formTipoPago');
    const nombreInput = document.getElementById('nombre');
    
    form.addEventListener('submit', function(e) {
        const nombre = nombreInput.value.trim();
        
        // Validación básica
        let isValid = true;
        
        if (!nombre) {
            e.preventDefault();
            nombreInput.classList.add('is-invalid');
            isValid = false;
        }
        
        if (nombre.length > 50) {
            e.preventDefault();
            showToast('error', 'El nombre no puede exceder los 50 caracteres.');
            nombreInput.focus();
            return false;
        }
        
        if (!isValid) {
            showToast('error', 'Por favor, completa todos los campos obligatorios correctamente.');
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

    // Ajustar espacio del footer
    ajustarEspacioFooter();
});

// Ejecutar ajustes cuando se redimensiona la ventana
window.addEventListener('resize', ajustarEspacioFooter);
window.addEventListener('load', ajustarEspacioFooter);

function limpiarFormulario() {
    if (confirm('¿Estás seguro de que deseas limpiar el formulario? Se perderán todos los datos ingresados.')) {
        document.getElementById('formTipoPago').reset();
        document.getElementById('charCount').textContent = '0';
        document.getElementById('charCount').className = 'char-counter-info';
        
        // Remover clases de validación
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.classList.remove('is-valid', 'is-invalid');
        });
        
        showToast('info', 'Formulario limpiado correctamente.');
    }
}

function showToast(type, message) {
    // Crear toast dinámicamente
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
    
    // Mostrar toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remover toast después de ocultar
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

<!-- 6. INCLUIR FOOTER AL FINAL -->
<?php require_once '../layouts/footer.php'; ?>