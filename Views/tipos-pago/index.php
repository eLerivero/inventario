<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/TipoPagoController.php';
require_once '../../Utils/Ayuda.php';

$database = new Database();
$db = $database->getConnection();

$controller = new TipoPagoController($db);

// Manejar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';

// Procesar eliminación
if ($action === 'delete' && $id) {
    if (!isset($_GET['token']) || $_GET['token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error_message = "Token de seguridad inválido";
    } else {
        $result = $controller->eliminar($id);
        if ($result['success']) {
            $success_message = $result['message'];
            header("Refresh: 2; URL=index.php");
        } else {
            $error_message = $result['message'];
        }
    }
}

// Procesar cambio de estado
if (($action === 'activar' || $action === 'desactivar') && $id) {
    if (!isset($_GET['token']) || $_GET['token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error_message = "Token de seguridad inválido";
    } else {
        if ($action === 'activar') {
            $result = $controller->activar($id);
        } else {
            $result = $controller->desactivar($id);
        }
        
        if ($result['success']) {
            $success_message = $result['message'];
            header("Refresh: 2; URL=index.php");
        } else {
            $error_message = $result['message'];
        }
    }
}

// Obtener todos los tipos de pago
$result = $controller->listar();
if ($result['success']) {
    $tiposPago = $result['data'];
} else {
    $error_message = $result['message'];
    $tiposPago = [];
}

// Obtener estadísticas de uso
$estadisticas = $controller->obtenerEstadisticasUso();
$stats_data = $estadisticas['success'] ? $estadisticas['data'] : [];

// Generar token CSRF para seguridad
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<?php 
$page_title = "Gestión de Tipos de Pago";
include '../layouts/header.php'; 
?>

<!-- Header con Botón de Nuevo Tipo de Pago -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-credit-card me-2"></i>
            Gestión de Tipos de Pago
        </h1>
        <p class="text-muted mb-0">Administra los métodos de pago del sistema</p>
    </div>
    <div>
        <a href="crear.php" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Nuevo Tipo de Pago
        </a>
    </div>
</div>

<!-- Alertas -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <?php if (in_array($action, ['delete', 'activar', 'desactivar'])): ?>
            <div class="mt-2">
                <small>Serás redirigido automáticamente al listado...</small>
            </div>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Estadísticas Rápidas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Tipos</h5>
                        <h3><?php echo count($tiposPago); ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-credit-card fa-2x"></i>
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
                        <h5 class="card-title">Más Usado</h5>
                        <h3>
                            <?php 
                            $mas_usado = !empty($stats_data) ? $stats_data[0]['nombre'] : 'N/A';
                            echo htmlspecialchars($mas_usado);
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-pie fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Ventas</h5>
                        <h3>
                            <?php 
                            $total_ventas = array_sum(array_map(function($stat) {
                                return $stat['total_ventas'];
                            }, $stats_data));
                            echo $total_ventas;
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-shopping-cart fa-2x"></i>
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
                        <h5 class="card-title">Monto Total</h5>
                        <h3>
                            <?php 
                            $monto_total = array_sum(array_map(function($stat) {
                                return $stat['monto_total'];
                            }, $stats_data));
                            echo 'S/ ' . number_format($monto_total, 2);
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Tipos de Pago -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Tipos de Pago
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($tiposPago)): ?>
            <div class="text-center py-5">
                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No hay tipos de pago registrados</h4>
                <p class="text-muted">Comienza creando tu primer tipo de pago.</p>
                <a href="crear.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-1"></i> Crear Primer Tipo de Pago
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tablaTiposPago">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Ventas</th>
                            <th>Monto Total</th>
                            <th>Porcentaje</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiposPago as $index => $tipo): 
                            // Buscar estadísticas para este tipo de pago
                            $estadistica = array_filter($stats_data, function($stat) use ($tipo) {
                                return $stat['nombre'] === $tipo['nombre'];
                            });
                            $estadistica = !empty($estadistica) ? array_values($estadistica)[0] : null;
                        ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($tipo['nombre']); ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    if (empty($tipo['descripcion'])) {
                                        echo '<span class="text-muted">Sin descripción</span>';
                                    } else {
                                        if (strlen($tipo['descripcion']) > 80) {
                                            echo '<span data-bs-toggle="tooltip" title="' . htmlspecialchars($tipo['descripcion']) . '">';
                                            echo htmlspecialchars(substr($tipo['descripcion'], 0, 80)) . '...';
                                            echo '</span>';
                                        } else {
                                            echo htmlspecialchars($tipo['descripcion']);
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo $estadistica['total_ventas'] ?? 0; ?> ventas
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-success">S/ <?php echo number_format($estadistica['monto_total'] ?? 0, 2); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $estadistica['porcentaje'] ?? 0; ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $tipo['activo'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $tipo['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo !empty($tipo['created_at']) ? Ayuda::formatDate($tipo['created_at']) : 'N/A'; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="editar.php?id=<?php echo $tipo['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Editar tipo de pago"
                                           data-bs-toggle="tooltip">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($tipo['activo']): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-warning" 
                                                    title="Desactivar tipo de pago"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalDesactivar"
                                                    data-id="<?php echo $tipo['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($tipo['nombre']); ?>">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-outline-success" 
                                                    title="Activar tipo de pago"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalActivar"
                                                    data-id="<?php echo $tipo['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($tipo['nombre']); ?>">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (($estadistica['total_ventas'] ?? 0) == 0): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    title="Eliminar tipo de pago"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEliminar"
                                                    data-id="<?php echo $tipo['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($tipo['nombre']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary" 
                                                    title="No se puede eliminar (tiene ventas asociadas)"
                                                    disabled
                                                    data-bs-toggle="tooltip">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
</div>

<!-- Información Adicional -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Información sobre Tipos de Pago
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">
                    <i class="fas fa-lightbulb me-2"></i>Recomendaciones:
                </h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> Usa nombres claros y descriptivos</li>
                    <li><i class="fas fa-check text-success me-2"></i> Mantén solo los métodos de pago activos que uses</li>
                    <li><i class="fas fa-check text-success me-2"></i> Describe brevemente cada método</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Limitaciones:
                </h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-ban text-danger me-2"></i> No se pueden eliminar tipos con ventas asociadas</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> Los nombres deben ser únicos</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> Máximo 50 caracteres para nombres</li>
                </ul>
            </div>
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
                <p>¿Estás seguro de que deseas eliminar el tipo de pago "<strong id="nombreTipoPagoEliminar"></strong>"?</p>
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

<!-- Modal de Desactivación -->
<div class="modal fade" id="modalDesactivar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-pause text-warning me-2"></i>
                    Confirmar Desactivación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas desactivar el tipo de pago "<strong id="nombreTipoPagoDesactivar"></strong>"?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información:</strong> El tipo de pago no estará disponible para nuevas ventas, pero las ventas existentes se mantendrán.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <a href="#" id="btnDesactivarConfirmar" class="btn btn-warning">
                    <i class="fas fa-pause me-1"></i> Desactivar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Activación -->
<div class="modal fade" id="modalActivar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-play text-success me-2"></i>
                    Confirmar Activación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas activar el tipo de pago "<strong id="nombreTipoPagoActivar"></strong>"?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información:</strong> El tipo de pago estará disponible para nuevas ventas.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <a href="#" id="btnActivarConfirmar" class="btn btn-success">
                    <i class="fas fa-play me-1"></i> Activar
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Configurar DataTables
    $('#tablaTiposPago').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        pageLength: 10,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 8] },
            { searchable: false, targets: [0, 3, 4, 5, 6, 7, 8] }
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            $('.dataTables_length select').addClass('form-control form-control-sm');
        }
    });

    // Configurar modales
    configurarModal('Eliminar', 'eliminar');
    configurarModal('Desactivar', 'desactivar');
    configurarModal('Activar', 'activar');

    function configurarModal(accion, tipo) {
        const modal = document.getElementById(`modal${accion}`);
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            
            document.getElementById(`nombreTipoPago${accion}`).textContent = nombre;
            document.getElementById(`btn${accion}Confirmar`).href = `index.php?action=${tipo}&id=${id}&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>`;
        });
    }

    <?php if (in_array($action, ['delete', 'activar', 'desactivar']) && !empty($success_message)): ?>
        setTimeout(() => {
            showToast('success', '<?php echo addslashes($success_message); ?>');
        }, 100);
    <?php endif; ?>

    // Auto-ocultar alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

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

<?php include '../layouts/footer.php'; ?>