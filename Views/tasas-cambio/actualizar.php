<?php
// INICIAR SESIÓN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// OBTENER USUARIO ACTUAL
require_once __DIR__ . '/../../Utils/Auth.php';
$current_user = Auth::user();

require_once '../../Controllers/TasaCambioController.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$tasaController = new TasaCambioController($db);

// Obtener tasa actual
$tasaActual = $tasaController->obtenerTasaActual();

// Procesar formulario
$mensaje = '';
$tipoMensaje = '';

if ($_POST && isset($_POST['nueva_tasa'])) {
    try {
        $nuevaTasa = floatval($_POST['nueva_tasa']);
        
        // Usar el usuario de la sesión
        $usuario_id = $current_user ? $current_user['id'] : 1;

        $resultado = $tasaController->actualizarTasa($nuevaTasa, $usuario_id);

        if ($resultado['success']) {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'success';
            echo '<script>setTimeout(() => { window.location.href = "index.php"; }, 2000);</script>';

            // Actualizar tasa actual
            $tasaActual = $tasaController->obtenerTasaActual();
        } else {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'danger';
        }
    } catch (Exception $e) {
        $mensaje = "Error inesperado: " . $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

$page_title = "Actualizar Tasa de Cambio";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-sync-alt me-2"></i>Actualizar Tasa de Cambio
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Listado
            </a>
        </div>
    </div>

    <!-- Información del Usuario -->
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-user-circle fa-2x me-3"></i>
            <div>
                <h5 class="mb-1">Usuario Actual</h5>
                <p class="mb-0">
                    <strong><?php echo htmlspecialchars($current_user['nombre'] ?? 'Usuario'); ?></strong>
                    (<?php echo htmlspecialchars($current_user['username'] ?? 'N/A'); ?>)
                    - Esta acción será registrada con tu usuario.
                </p>
            </div>
        </div>
    </div>

    <!-- Tasa Actual -->
    <?php if ($tasaActual['success']): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>Tasa Actual
                </h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="display-5 mb-3">
                            1 USD = <span class="text-primary"><?php echo number_format($tasaActual['data']['tasa_cambio'], 2); ?></span> Bs
                        </h2>
                        <p class="mb-1">
                            <i class="fas fa-calendar me-1"></i>
                            <strong>Actualizada:</strong> <?php echo date('d/m/Y H:i', strtotime($tasaActual['data']['fecha_actualizacion'])); ?>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-user me-1"></i>
                            <strong>Por:</strong> <?php echo htmlspecialchars($tasaActual['data']['usuario_nombre_completo'] ?? $tasaActual['data']['usuario_nombre'] ?? 'Sistema'); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="bg-light p-3 rounded">
                            <small class="text-muted">ID de la tasa</small>
                            <div class="h5 mb-0"><code><?php echo $tasaActual['data']['id']; ?></code></div>
                        </div>
                    </div>
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

    <!-- Formulario de Actualización -->
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>Nueva Tasa de Cambio
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="formActualizarTasa">
                        <div class="mb-4">
                            <label for="nueva_tasa" class="form-label">
                                <i class="fas fa-calculator me-1"></i>Nueva Tasa (Bs por USD) *
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">1 USD =</span>
                                <input type="number"
                                    class="form-control"
                                    id="nueva_tasa"
                                    name="nueva_tasa"
                                    value="<?php echo $tasaActual['success'] ? $tasaActual['data']['tasa_cambio'] + 0.01 : ''; ?>"
                                    required
                                    min="0.0001"
                                    step="0.0001"
                                    placeholder="36.50">
                                <span class="input-group-text">Bs</span>
                            </div>
                            <div class="form-text">
                                Ingrese la nueva tasa de cambio del día.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-user me-1"></i>Responsable
                            </label>
                            <input type="text"
                                class="form-control"
                                value="<?php echo htmlspecialchars($current_user['nombre'] ?? 'Usuario'); ?>"
                                readonly>
                            <div class="form-text">
                                Esta acción será registrada con tu usuario.
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Importante:</strong> Al actualizar la tasa, se desactivará la tasa anterior y se actualizarán automáticamente todos los precios en Bolívares de los productos.
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-1"></i> Actualizar Tasa de Cambio
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Información Adicional -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Recomendaciones
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Verificar la tasa oficial del día antes de actualizar</li>
                        <li>Actualizar la tasa al inicio de cada día hábil</li>
                        <li>Comunicar el cambio al personal correspondiente</li>
                        <li>Revisar que los precios en Bs se hayan actualizado correctamente</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Últimas Tasas
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $historial = $tasaController->listarHistorial(5);
                    if ($historial['success'] && !empty($historial['data'])):
                    ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($historial['data'] as $item): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <div>
                                        <small><?php echo date('d/m/Y H:i', strtotime($item['fecha_actualizacion'])); ?></small>
                                        <div class="fw-bold"><?php echo number_format($item['tasa_cambio'], 2); ?> Bs</div>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($item['usuario_nombre_completo'] ?? $item['usuario_nombre'] ?? 'Sistema'); ?>
                                        </small>
                                    </div>
                                    <?php if ($item['activa']): ?>
                                        <span class="badge bg-success">Activa</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No hay historial disponible</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formActualizarTasa');
        const tasaInput = document.getElementById('nueva_tasa');

        form.addEventListener('submit', function(e) {
            const tasaVal = parseFloat(tasaInput.value);

            if (!tasaVal || tasaVal <= 0) {
                e.preventDefault();
                alert('Por favor, ingrese una tasa de cambio válida (mayor a 0)');
                tasaInput.focus();
                return false;
            }

            // Confirmar actualización
            if (!confirm('¿Estás seguro de actualizar la tasa de cambio? Esta acción actualizará todos los precios en Bolívares.')) {
                e.preventDefault();
                return false;
            }

            // Mostrar loading
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...';

            return true;
        });

        // Enfocar en el campo de tasa al cargar
        tasaInput.focus();
        tasaInput.select();
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->