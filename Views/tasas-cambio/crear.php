<?php
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$tasaController = new TasaCambioController($db);

// Obtener tasa actual para referencia
$tasaActual = $tasaController->obtenerTasaActual();

// Procesar formulario
$mensaje = '';
$tipoMensaje = '';

if ($_POST) {
    try {
        $data = [
            'moneda_origen' => 'USD',
            'moneda_destino' => 'VES',
            'tasa_cambio' => floatval($_POST['tasa_cambio'] ?? 0),
            'usuario_id' => $_POST['usuario_id'] ?? 1,
            'activa' => isset($_POST['activa']) ? 1 : 0
        ];

        $resultado = $tasaController->crearTasa($data);

        if ($resultado['success']) {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'success';
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

$page_title = "Crear Nueva Tasa de Cambio";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-plus-circle me-2"></i>Crear Nueva Tasa de Cambio
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Listado
            </a>
        </div>
    </div>

    <!-- Tasa Actual de Referencia -->
    <?php if ($tasaActual['success']): ?>
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Tasa Actual de Referencia</h5>
                    <p class="mb-0">
                        <strong>1 USD = <?php echo number_format($tasaActual['data']['tasa_cambio'], 2); ?> Bs</strong>
                        <small class="text-muted ms-3">
                            (Actualizada: <?php echo date('d/m/Y H:i', strtotime($tasaActual['data']['fecha_actualizacion'])); ?>)
                        </small>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Alertas -->
    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($mensaje); ?>
            <?php if ($tipoMensaje === 'success'): ?>
                <div class="mt-2">
                    <small>Serás redirigido automáticamente al listado...</small>
                </div>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exchange-alt me-2"></i>Información de la Tasa
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="formTasa" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="moneda_origen" class="form-label">
                                    <i class="fas fa-globe-americas me-1"></i>Moneda Origen
                                </label>
                                <input type="text"
                                    class="form-control"
                                    value="USD"
                                    readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="moneda_destino" class="form-label">
                                    <i class="fas fa-flag me-1"></i>Moneda Destino
                                </label>
                                <input type="text"
                                    class="form-control"
                                    value="VES"
                                    readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tasa_cambio" class="form-label">
                                <i class="fas fa-calculator me-1"></i>Tasa de Cambio *
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">1 USD =</span>
                                <input type="number"
                                    class="form-control <?php echo isset($_POST['tasa_cambio']) && (empty($_POST['tasa_cambio']) || $_POST['tasa_cambio'] <= 0) ? 'is-invalid' : ''; ?>"
                                    id="tasa_cambio"
                                    name="tasa_cambio"
                                    value="<?php echo htmlspecialchars($_POST['tasa_cambio'] ?? ''); ?>"
                                    required
                                    min="0.0001"
                                    step="0.0001"
                                    placeholder="0.0000">
                                <span class="input-group-text">Bs</span>
                            </div>
                            <div class="form-text">Ingrese la tasa de cambio actual (Ej: 36.50)</div>
                        </div>

                        <div class="mb-3">
                            <label for="usuario_id" class="form-label">
                                <i class="fas fa-user me-1"></i>Usuario
                            </label>
                            <input type="text"
                                class="form-control"
                                id="usuario_id"
                                name="usuario_id"
                                value="<?php echo htmlspecialchars($_POST['usuario_id'] ?? 1); ?>"
                                maxlength="100"
                                placeholder="Nombre del usuario">
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                    type="checkbox"
                                    id="activa"
                                    name="activa"
                                    <?php echo isset($_POST['activa']) ? 'checked' : 'checked'; ?>>
                                <label class="form-check-label" for="activa">
                                    <i class="fas fa-toggle-on me-1"></i>Marcar como tasa activa
                                </label>
                            </div>
                            <div class="form-text">
                                Si está activada, esta será la tasa utilizada en el sistema y se actualizarán todos los precios en Bolívares.
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Importante:</strong> Al crear una nueva tasa activa, todas las tasas anteriores se desactivarán automáticamente.
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Guardar Tasa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formTasa');
        const tasaInput = document.getElementById('tasa_cambio');

        form.addEventListener('submit', function(e) {
            const tasaVal = parseFloat(tasaInput.value);

            if (!tasaVal || tasaVal <= 0) {
                e.preventDefault();
                tasaInput.classList.add('is-invalid');

                // Mostrar mensaje
                if (!document.querySelector('.invalid-feedback')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'La tasa de cambio debe ser mayor a 0';
                    tasaInput.parentNode.appendChild(errorDiv);
                }

                return false;
            }

            // Mostrar loading
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';

            return true;
        });

        // Validación en tiempo real
        tasaInput.addEventListener('input', function() {
            const valor = parseFloat(this.value);
            if (valor && valor > 0) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->