<?php
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$tasaController = new TasaCambioController($db);

// Obtener tasas de cambio
$tasas_result = $tasaController->listar();
$tasas = $tasas_result['success'] ? $tasas_result['data'] : [];

// Obtener tasa actual
$tasaActual = $tasaController->obtenerTasaActual();

// Manejar eliminación
if (isset($_GET['eliminar'])) {
    $resultado = $tasaController->eliminarTasa($_GET['eliminar']);
    if ($resultado['success']) {
        $success_message = $resultado['message'];
        header("Refresh: 2; URL=index.php");
    } else {
        $error_message = $resultado['message'];
    }
}

// Manejar activación
if (isset($_GET['activar'])) {
    // Primero desactivar todas
    $query = "UPDATE tasas_cambio SET activa = FALSE WHERE activa = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute();

    // Activar la seleccionada
    $query = "UPDATE tasas_cambio SET activa = TRUE, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_GET['activar']);

    if ($stmt->execute()) {
        $success_message = "Tasa activada correctamente";
        header("Refresh: 2; URL=index.php");
    } else {
        $error_message = "Error al activar la tasa";
    }
}

$page_title = "Gestión de Tasas de Cambio";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <!-- Header Section -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-exchange-alt me-2"></i>Gestión de Tasas de Cambio
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="crear.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Nueva Tasa
            </a>
            <a href="actualizar.php" class="btn btn-primary ms-2">
                <i class="fas fa-sync-alt me-2"></i>Actualizar Tasa Actual
            </a>
        </div>
    </div>

    <!-- Tasa Actual -->
    <?php if ($tasaActual['success']): ?>
        <div class="card mb-4 bg-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="card-title mb-1">
                            <i class="fas fa-star me-2"></i>Tasa de Cambio Actual
                        </h4>
                        <h2 class="display-6 mb-0">
                            1 USD = <?php echo number_format($tasaActual['data']['tasa_cambio'], 2); ?> Bs
                        </h2>
                        <p class="mb-0 opacity-75">
                            <i class="fas fa-calendar me-1"></i>
                            Actualizada: <?php echo date('d/m/Y H:i', strtotime($tasaActual['data']['fecha_actualizacion'])); ?>
                            |
                            <i class="fas fa-user me-1"></i>
                            Por: <?php echo htmlspecialchars($tasaActual['data']['usuario_nombre_completo']); ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-grid gap-2 d-md-block">
                            <a href="actualizar.php" class="btn btn-light btn-lg">
                                <i class="fas fa-edit me-1"></i> Actualizar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            No hay tasa de cambio configurada.
            <a href="crear.php" class="alert-link">Configurar tasa de cambio ahora</a>
        </div>
    <?php endif; ?>

    <!-- Alertas -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Tasas</h6>
                            <h4><strong><?php echo count($tasas); ?></strong></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Tasa Activa</h6>
                            <h4><strong>
                                    <?php
                                    $activas = array_filter($tasas, function ($tasa) {
                                        return $tasa['activa'] == 1;
                                    });
                                    echo count($activas);
                                    ?>
                                </strong></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Últimos 7 días</h6>
                            <h4><strong>
                                    <?php
                                    $sieteDias = array_filter($tasas, function ($tasa) {
                                        $fechaTasa = strtotime($tasa['created_at']);
                                        $fechaLimite = strtotime('-7 days');
                                        return $fechaTasa >= $fechaLimite;
                                    });
                                    echo count($sieteDias);
                                    ?>
                                </strong></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-week fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Historial</h6>
                            <h4><strong>
                                    <?php
                                    $historial = $tasaController->listarHistorial(1000);
                                    echo $historial['success'] ? count($historial['data']) : 0;
                                    ?>
                                </strong></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-history fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Tasas -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-table me-2"></i>Historial de Tasas de Cambio
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($tasas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay tasas de cambio registradas</h4>
                    <p class="text-muted">Comienza configurando la tasa de cambio actual.</p>
                    <a href="crear.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-1"></i> Configurar Primera Tasa
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaTasas">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tasa</th>
                                <th>Estado</th>
                                <th>Actualizada</th>
                                <th>Usuario</th>
                                <th>Creada</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasas as $tasa): ?>
                                <tr class="<?php echo $tasa['activa'] ? 'table-success' : ''; ?>">
                                    <td><code><?php echo $tasa['id']; ?></code></td>
                                    <td>
                                        <strong class="fs-5">
                                            1 <?php echo $tasa['moneda_origen']; ?> =
                                            <?php echo number_format($tasa['tasa_cambio'], 2); ?>
                                            <?php echo $tasa['moneda_destino']; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($tasa['activa']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i> Activa
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times-circle me-1"></i> Inactiva
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($tasa['fecha_actualizacion'])); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php
                                            $diferencia = time() - strtotime($tasa['fecha_actualizacion']);
                                            $dias = floor($diferencia / (60 * 60 * 24));
                                            if ($dias == 0) {
                                                echo 'Hoy';
                                            } elseif ($dias == 1) {
                                                echo 'Ayer';
                                            } else {
                                                echo "Hace $dias días";
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($tasa['usuario_nombre_completo'] ?? $tasa['usuario_nombre'] ?? 'Sistema'); ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($tasa['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (!$tasa['activa']): ?>
                                                <a href="index.php?activar=<?php echo $tasa['id']; ?>"
                                                    class="btn btn-outline-success"
                                                    title="Activar esta tasa"
                                                    onclick="return confirm('¿Activar esta tasa de cambio?')">
                                                    <i class="fas fa-toggle-on"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="editar.php?id=<?php echo $tasa['id']; ?>"
                                                class="btn btn-outline-primary"
                                                title="Editar tasa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-outline-danger btn-eliminar"
                                                title="Eliminar tasa"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEliminar"
                                                data-id="<?php echo $tasa['id']; ?>"
                                                data-tasa="<?php echo number_format($tasa['tasa_cambio'], 2); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar la tasa de cambio <strong id="tasaValor"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <a href="#" id="btnEliminarConfirmar" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i> Eliminar
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar DataTables
        $('#tablaTasas').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 10,
            order: [
                [5, 'desc']
            ],
            columnDefs: [{
                orderable: false,
                targets: [6]
            }],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });

        // Configurar modal de eliminación
        const modalEliminar = document.getElementById('modalEliminar');
        modalEliminar.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const tasa = button.getAttribute('data-tasa');

            document.getElementById('tasaValor').textContent = '1 USD = ' + tasa + ' Bs';
            document.getElementById('btnEliminarConfirmar').href = `index.php?eliminar=${id}`;
        });

        // Auto-ocultar alertas
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->