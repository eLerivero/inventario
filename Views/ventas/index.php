<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/VentaController.php';
require_once '../../Utils/Ayuda.php';
require_once '../../Helpers/TasaCambioHelper.php';

$database = new Database();
$db = $database->getConnection();

$controller = new VentaController($db);

// Manejar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';

// Procesar cambio de estado
if ($action === 'completar' && $id) {
    if (!isset($_GET['token']) || $_GET['token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error_message = "Token de seguridad inválido";
    } else {
        $result = $controller->actualizarEstado($id, 'completada');
        if ($result['success']) {
            $success_message = $result['message'];
            header("Refresh: 2; URL=index.php");
        } else {
            $error_message = $result['message'];
        }
    }
}

// Obtener todas las ventas
$result = $controller->listar();
if ($result['success']) {
    $ventas = $result['data'];
} else {
    $error_message = $result['message'];
    $ventas = [];
}

// Obtener estadísticas
$estadisticas = $controller->obtenerEstadisticas();
$stats = $estadisticas['success'] ? $estadisticas['data'] : [];

// Generar token CSRF para seguridad
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<?php
$page_title = "Gestión de Ventas";
include '../layouts/header.php';
?>

<!-- Header con Botón de Nueva Venta -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-shopping-cart me-2"></i>
            Gestión de Ventas
        </h1>
        <p class="text-muted mb-0">Administra las ventas del sistema de inventario</p>
    </div>
    <div>
        <a href="crear.php" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Nueva Venta
        </a>
    </div>
</div>

<!-- Alertas -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <?php if ($action === 'completar'): ?>
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
                        <h5 class="card-title">Total Ventas</h5>
                        <h3><?php echo $stats['total_ventas'] ?? 0; ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-receipt fa-2x"></i>
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
                        <h5 class="card-title">Ingresos Totales</h5>
                        <h3><?php echo $stats['ingresos_totales_formateado'] ?? '$0.00'; ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
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
                        <h5 class="card-title">Ticket Promedio</h5>
                        <h3><?php echo $stats['ticket_promedio_formateado'] ?? '$0.00'; ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x"></i>
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
                        <h5 class="card-title">Clientes Activos</h5>
                        <h3><?php echo $stats['clientes_activos'] ?? 0; ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Ventas -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Ventas
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($ventas)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No hay ventas registradas</h4>
                <p class="text-muted">Comienza creando tu primera venta.</p>
                <a href="crear.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-1"></i> Crear Primera Venta
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tablaVentas">
                    <thead class="table-dark">
                        <tr>
                            <th># Venta</th>
                            <th>Cliente</th>
                            <th>Total USD</th>
                            <th>Total Bs</th>
                            <th>Tasa</th>
                            <th>Tipo Pago</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas as $venta):
                            $estado_badge = [
                                'pendiente' => 'bg-warning',
                                'completada' => 'bg-success',
                                'cancelada' => 'bg-danger'
                            ];
                            $estado_text = [
                                'pendiente' => 'Pendiente',
                                'completada' => 'Completada',
                                'cancelada' => 'Cancelada'
                            ];
                        ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $venta['numero_venta']; ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($venta['cliente_nombre'] ?? 'Cliente no especificado'); ?>
                                </td>
                                <td>
                                    <strong class="text-primary"><?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?></strong>
                                </td>
                                <td>
                                    <strong class="text-success"><?php echo $venta['total_formateado_bs'] ?? TasaCambioHelper::formatearBS($venta['total_bs']); ?></strong>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo $venta['tasa_formateada'] ?? number_format($venta['tasa_cambio_utilizada'], 2); ?> Bs/$</small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($venta['tipo_pago_nombre'] ?? 'No especificado'); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $estado_badge[$venta['estado']] ?? 'bg-secondary'; ?>">
                                        <?php echo $estado_text[$venta['estado']] ?? $venta['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo !empty($venta['fecha_hora']) ? Ayuda::formatDate($venta['fecha_hora']) : Ayuda::formatDate($venta['created_at']); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="ver.php?id=<?php echo $venta['id']; ?>"
                                            class="btn btn-outline-info"
                                            title="Ver detalles"
                                            data-bs-toggle="tooltip">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($venta['estado'] === 'pendiente'): ?>
                                            <button type="button"
                                                class="btn btn-outline-success"
                                                title="Completar venta"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalCompletar"
                                                data-id="<?php echo $venta['id']; ?>"
                                                data-numero="<?php echo $venta['numero_venta']; ?>">
                                                <i class="fas fa-check"></i>
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
            Información sobre Ventas
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">
                    <i class="fas fa-lightbulb me-2"></i>Monedas de Venta:
                </h6>
                <ul class="list-unstyled">
                    <li><span class="badge bg-primary me-2">USD</span> Los precios se manejan en Dólares Americanos</li>
                    <li><span class="badge bg-success me-2">Bs</span> La conversión a Bolívares es automática</li>
                    <li><span class="badge bg-info me-2">Tasa</span> Se usa la tasa de cambio activa del sistema</li>
                    <li><span class="badge bg-warning me-2">Sin IGV</span> El impuesto del 18% ha sido eliminado</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Consideraciones:
                </h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-ban text-danger me-2"></i> Las ventas completadas no se pueden modificar</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> El stock se actualiza al completar la venta</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> Verificar stock antes de completar</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> La tasa de cambio se bloquea al momento de la venta</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Completar Venta -->
<div class="modal fade" id="modalCompletar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Confirmar Completar Venta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas completar la venta <strong id="numeroVenta"></strong>?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Importante:</strong> Al completar la venta, el stock de los productos se actualizará automáticamente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <a href="#" id="btnCompletarConfirmar" class="btn btn-success">
                    <i class="fas fa-check me-1"></i> Completar Venta
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Configurar DataTables
        $('#tablaVentas').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 10,
            order: [
                [0, 'desc']
            ],
            columnDefs: [{
                orderable: false,
                targets: [8]
            }],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control form-control-sm');
                $('.dataTables_length select').addClass('form-control form-control-sm');
            }
        });

        // Configurar modal de completar venta
        const modalCompletar = document.getElementById('modalCompletar');
        modalCompletar.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const numero = button.getAttribute('data-numero');

            document.getElementById('numeroVenta').textContent = '#' + numero;
            document.getElementById('btnCompletarConfirmar').href = `index.php?action=completar&id=${id}&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>`;
        });

        <?php if ($action === 'completar' && !empty($success_message)): ?>
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

<!-- <?php include '../layouts/footer.php'; ?> -->