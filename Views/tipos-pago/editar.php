<?php
// =============================================
// 1. INCLUIR CONTROLADORES Y CONFIGURACIÓN
// =============================================
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

// =============================================
// 2. OBTENER DATOS DEL TIPO DE PAGO A EDITAR
// =============================================
$mensaje = '';
$tipoMensaje = '';
$tipoPago = null;

// Obtener ID desde la URL
$id = $_GET['id'] ?? null;

if (!$id) {
    $mensaje = "ID de tipo de pago no especificado";
    $tipoMensaje = 'danger';
} else {
    // Obtener datos del tipo de pago
    $resultado = $tipoPagoController->obtener($id);

    if ($resultado['success']) {
        $tipoPago = $resultado['data'];
    } else {
        $mensaje = $resultado['message'];
        $tipoMensaje = 'danger';
    }
}

// =============================================
// 3. PROCESAR FORMULARIO DE ACTUALIZACIÓN
// =============================================
if ($_POST && $tipoPago) {
    try {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'activo' => isset($_POST['activo']) ? true : false
        ];

        $resultado = $tipoPagoController->actualizar($id, $data);

        if ($resultado['success']) {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'success';

            // Actualizar los datos del tipo de pago en la variable
            $tipoPagoActualizado = $tipoPagoController->obtener($id);
            if ($tipoPagoActualizado['success']) {
                $tipoPago = $tipoPagoActualizado['data'];
            }

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

// =============================================
// 4. DEFINIR TÍTULO Y INCLUIR HEADER
// =============================================
$page_title = "Editar Tipo de Pago";
require_once '../layouts/header.php';
?>

<!-- ============================================= -->
<!-- 5. CONTENIDO PRINCIPAL DE LA VISTA -->
<!-- ============================================= -->
<div class="content-wrapper editar-tipo-pago-content">

    <!-- Header de la página -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-edit me-2"></i>Editar Tipo de Pago
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Listado
            </a>
        </div>
    </div>

    <!-- Alertas del sistema -->
    <?php if ($mensaje && !$tipoPago): ?>
        <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($tipoPago): ?>
        <!-- Alertas de éxito/error después de enviar el formulario -->
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

        <!-- Información del tipo de pago -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información Actual
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong><i class="fas fa-hashtag me-1"></i>ID:</strong>
                        <p class="mb-2"><?php echo htmlspecialchars($tipoPago['id']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <strong><i class="fas fa-calendar me-1"></i>Creado:</strong>
                        <p class="mb-2">
                            <?php 
                            if (!empty($tipoPago['created_at'])) {
                                echo date('d/m/Y H:i', strtotime($tipoPago['created_at'])); 
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <strong><i class="fas fa-sync-alt me-1"></i>Actualizado:</strong>
                        <p class="mb-2">
                            <?php 
                            if (!empty($tipoPago['updated_at'])) {
                                echo date('d/m/Y H:i', strtotime($tipoPago['updated_at'])); 
                            } else {
                                echo 'Nunca';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <strong><i class="fas fa-flag me-1"></i>Estado:</strong>
                        <p class="mb-2">
                            <span class="badge bg-<?php echo $tipoPago['activo'] ? 'success' : 'secondary'; ?>">
                                <?php echo $tipoPago['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de edición -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Editar Información
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formTipoPago" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">
                                    <i class="fas fa-credit-card me-1"></i>Nombre del Tipo de Pago *
                                </label>
                                <input type="text"
                                    class="form-control <?php echo isset($_POST['nombre']) && empty($_POST['nombre']) ? 'is-invalid' : ''; ?>"
                                    id="nombre"
                                    name="nombre"
                                    value="<?php echo htmlspecialchars($_POST['nombre'] ?? $tipoPago['nombre']); ?>"
                                    required
                                    maxlength="50"
                                    placeholder="Ej: Efectivo, Tarjeta de Crédito, Transferencia...">
                                <?php if (isset($_POST['nombre']) && empty($_POST['nombre'])): ?>
                                    <div class="invalid-feedback">
                                        El nombre del tipo de pago es obligatorio.
                                    </div>
                                <?php endif; ?>
                                <div class="form-text">Nombre único para identificar el método de pago (máx. 50 caracteres)</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-flag me-1"></i>Estado
                                </label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="activo" 
                                           name="activo" 
                                           <?php echo ($_POST['activo'] ?? $tipoPago['activo']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activo">
                                        <span id="estadoTexto" class="badge bg-<?php echo ($_POST['activo'] ?? $tipoPago['activo']) ? 'success' : 'secondary'; ?>">
                                            <?php echo ($_POST['activo'] ?? $tipoPago['activo']) ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </label>
                                </div>
                                <div class="form-text">Los tipos de pago inactivos no aparecen en nuevas ventas</div>
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
                            rows="4"
                            maxlength="255"
                            placeholder="Describe las características de este método de pago..."><?php echo htmlspecialchars($_POST['descripcion'] ?? $tipoPago['descripcion']); ?></textarea>
                        <div class="form-text">
                            Máximo 255 caracteres.
                            <span id="charCount" class="char-counter-info"><?php echo strlen($_POST['descripcion'] ?? $tipoPago['descripcion']); ?></span>/255
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
                            <li>Al desactivar un tipo de pago, no estará disponible para nuevas ventas</li>
                            <li>Las ventas existentes con este tipo de pago se mantienen sin cambios</li>
                        </ul>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Actualizar Tipo de Pago
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="button" class="btn btn-outline-info" onclick="limpiarFormulario()">
                                <i class="fas fa-broom me-1"></i> Restaurar Valores
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div> <!-- End content-wrapper -->

<!-- ============================================= -->
<!-- 6. SCRIPTS ESPECÍFICOS -->
<!-- ============================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const descripcion = document.getElementById('descripcion');
    const charCount = document.getElementById('charCount');
    const activoCheckbox = document.getElementById('activo');
    const estadoTexto = document.getElementById('estadoTexto');

    // Contador de caracteres para descripción
    if (descripcion && charCount) {
        descripcion.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;

            if (length > 225) {
                charCount.className = 'text-danger fw-bold';
            } else if (length > 200) {
                charCount.className = 'text-warning';
            } else {
                charCount.className = 'char-counter-info';
            }
        });
    }

    // Cambiar el texto del estado cuando se activa/desactiva el checkbox
    if (activoCheckbox && estadoTexto) {
        activoCheckbox.addEventListener('change', function() {
            if (this.checked) {
                estadoTexto.className = 'badge bg-success';
                estadoTexto.textContent = 'Activo';
            } else {
                estadoTexto.className = 'badge bg-secondary';
                estadoTexto.textContent = 'Inactivo';
            }
        });
    }

    // Validación del formulario
    const form = document.getElementById('formTipoPago');
    const nombreInput = document.getElementById('nombre');

    if (form) {
        form.addEventListener('submit', function(e) {
            const nombre = nombreInput.value.trim();

            // Validación básica
            let isValid = true;

            if (!nombre) {
                e.preventDefault();
                nombreInput.classList.add('is-invalid');
                isValid = false;
            } else if (nombre.length < 3) {
                e.preventDefault();
                showToast('error', 'El nombre debe tener al menos 3 caracteres.');
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
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...';

            return true;
        });
    }

    // Validación en tiempo real
    if (nombreInput) {
        nombreInput.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });
    }

    // Ajustar espacio del footer
    ajustarEspacioFooter();
});

// Ejecutar ajustes cuando se redimensiona la ventana
window.addEventListener('resize', ajustarEspacioFooter);
window.addEventListener('load', ajustarEspacioFooter);

function ajustarEspacioFooter() {
    const contentWrapper = document.querySelector('.editar-tipo-pago-content');
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

function limpiarFormulario() {
    if (confirm('¿Estás seguro de que deseas restaurar los valores originales? Se perderán los cambios no guardados.')) {
        // Recargar la página para restaurar valores originales
        window.location.reload();
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

<!-- ============================================= -->
<!-- 7. INCLUIR FOOTER AL FINAL -->
<!-- ============================================= -->
<!-- <?php require_once '../layouts/footer.php'; ?> -->