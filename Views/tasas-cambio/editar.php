<?php
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$tasaController = new TasaCambioController($db);

// Obtener ID de la tasa
$tasa_id = $_GET['id'] ?? null;
$mensaje = '';
$tipoMensaje = '';

if (!$tasa_id) {
    $mensaje = "ID de tasa no especificado";
    $tipoMensaje = 'danger';
} else {
    // Obtener datos de la tasa
    $tasa_result = $tasaController->obtenerTasaPorId($tasa_id);
    $tasa = $tasa_result['success'] ? $tasa_result['data'] : null;

    if (!$tasa) {
        $mensaje = "Tasa de cambio no encontrada";
        $tipoMensaje = 'danger';
    }
}

// Procesar formulario
if ($_POST && $tasa) {
    try {
        $data = [
            'tasa_cambio' => floatval($_POST['tasa_cambio'] ?? 0),
            'usuario_actualizacion' => $_POST['usuario_actualizacion'] ?? 'Sistema',
            'activa' => isset($_POST['activa']) ? 1 : 0
        ];

        $resultado = $tasaController->editarTasa($tasa_id, $data);

        if ($resultado['success']) {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'success';

            // Actualizar datos de la tasa
            $tasa_result = $tasaController->obtenerTasaPorId($tasa_id);
            $tasa = $tasa_result['success'] ? $tasa_result['data'] : $tasa;

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

$page_title = "Editar Tasa de Cambio";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-edit me-2"></i>Editar Tasa de Cambio
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Listado
            </a>
        </div>
    </div>

    <?php if (!$tasa): ?>
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?>">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Información de la tasa -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información de la Tasa
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong><i class="fas fa-hashtag me-1"></i>ID:</strong>
                        <p class="mb-2"><code><?php echo htmlspecialchars($tasa['id']); ?></code></p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-calendar me-1"></i>Creada:</strong>
                        <p class="mb-2"><?php echo date('d/m/Y H:i', strtotime($tasa['created_at'])); ?></p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-sync-alt me-1"></i>Actualizada:</strong>
                        <p class="mb-2"><?php echo date('d/m/Y H:i', strtotime($tasa['updated_at'])); ?></p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-toggle-on me-1"></i>Estado:</strong>
                        <p class="mb-2">
                            <?php if ($tasa['activa']): ?>
                                <span class="badge bg-success">Activa</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactiva</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

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
                            <i class="fas fa-edit me-2"></i>Editar Información
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formTasa" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Moneda Origen</label>
                                    <input type="text"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($tasa['moneda_origen']); ?>"
                                        readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Moneda Destino</label>
                                    <input type="text"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($tasa['moneda_destino']); ?>"
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
                                        value="<?php echo htmlspecialchars($_POST['tasa_cambio'] ?? $tasa['tasa_cambio']); ?>"
                                        required
                                        min="0.0001"
                                        step="0.0001">
                                    <span class="input-group-text">Bs</span>
                                </div>
                                <div class="form-text">Tasa actual: <?php echo number_format($tasa['tasa_cambio'], 4); ?> Bs</div>
                            </div>

                            <div class="mb-3">
                                <label for="usuario_actualizacion" class="form-label">
                                    <i class="fas fa-user me-1"></i>Usuario
                                </label>
                                <input type="text"
                                    class="form-control"
                                    id="usuario_actualizacion"
                                    name="usuario_actualizacion"
                                    value="<?php echo htmlspecialchars($_POST['usuario_actualizacion'] ?? $tasa['usuario_actualizacion']); ?>"
                                    maxlength="100">
                            </div>

                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        id="activa"
                                        name="activa"
                                        <?php echo (isset($_POST['activa']) && $_POST['activa']) || (!isset($_POST['activa']) && $tasa['activa']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activa">
                                        <i class="fas fa-toggle-on me-1"></i>Marcar como tasa activa
                                    </label>
                                </div>
                                <div class="form-text">
                                    Si activas esta tasa, se desactivarán automáticamente todas las otras tasas.
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Actualizar Tasa
                                </button>
                                <a href="index.php?eliminar=<?php echo $tasa_id; ?>"
                                    class="btn btn-danger ms-2"
                                    onclick="return confirm('¿Estás seguro de eliminar esta tasa?')">
                                    <i class="fas fa-trash me-1"></i> Eliminar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
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
                return false;
            }

            // Mostrar loading
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...';

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