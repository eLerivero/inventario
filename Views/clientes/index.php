<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/ClienteController.php';
require_once '../../Utils/Ayuda.php';

$database = new Database();
$db = $database->getConnection();

$controller = new ClienteController($db);

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

// Obtener todos los clientes
$result = $controller->listar();
if ($result['success']) {
    $clientes = $result['data'];
} else {
    $error_message = $result['message'];
    $clientes = [];
}

// Generar token CSRF para seguridad
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<?php 
$page_title = "Gestión de Clientes";
include '../layouts/header.php'; 
?>

<!-- Header con Botón de Nuevo Cliente -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-users me-2"></i>
            Gestión de Clientes
        </h1>
        <p class="text-muted mb-0">Administra los clientes del sistema de inventario</p>
    </div>
    <div>
        <a href="crear.php" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Nuevo Cliente
        </a>
    </div>
</div>

<!-- Alertas -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <?php if ($action === 'delete'): ?>
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
                        <h5 class="card-title">Total Clientes</h5>
                        <h3><?php echo count($clientes); ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
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
                        <h5 class="card-title">Clientes Activos</h5>
                        <h3>
                            <?php 
                            $activos = array_filter($clientes, function($cliente) {
                                $activo = isset($cliente['activo']) ? $cliente['activo'] : true;
                                return $activo;
                            });
                            echo count($activos);
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-check fa-2x"></i>
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
                        <h5 class="card-title">Con Compras</h5>
                        <h3>
                            <?php 
                            $con_compras = array_filter($clientes, function($cliente) {
                                $total_compras = isset($cliente['total_compras']) ? $cliente['total_compras'] : 0;
                                return $total_compras > 0;
                            });
                            echo count($con_compras);
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
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Monto Total</h5>
                        <h3>
                            <?php 
                            $monto_total = array_sum(array_map(function($cliente) {
                                return $cliente['monto_total_compras'] ?? 0;
                            }, $clientes));
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

<!-- Tabla de Clientes -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Clientes
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($clientes)): ?>
            <div class="text-center py-5">
                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No hay clientes registrados</h4>
                <p class="text-muted">Comienza creando tu primer cliente.</p>
                <a href="crear.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-1"></i> Crear Primer Cliente
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tablaClientes">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Compras</th>
                            <th>Monto Total</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $index => $cliente): 
                            $activo = isset($cliente['activo']) ? $cliente['activo'] : true;
                            $total_compras = isset($cliente['total_compras']) ? $cliente['total_compras'] : 0;
                            $monto_total = isset($cliente['monto_total_compras']) ? $cliente['monto_total_compras'] : 0;
                            $created_at = isset($cliente['created_at']) ? $cliente['created_at'] : '';
                        ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($cliente['nombre']); ?></strong>
                                    <?php if (!$activo): ?>
                                        <span class="badge bg-secondary ms-1">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($cliente['email'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?php echo htmlspecialchars($cliente['email']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($cliente['telefono'])): ?>
                                        <i class="fas fa-phone me-1"></i>
                                        <?php echo htmlspecialchars($cliente['telefono']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $total_compras > 0 ? 'primary' : 'secondary'; ?>">
                                        <?php echo $total_compras; ?> compras
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-success">S/ <?php echo number_format($monto_total, 2); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $activo ? 'success' : 'secondary'; ?>">
                                        <?php echo $activo ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo !empty($created_at) ? Ayuda::formatDate($created_at) : 'N/A'; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="editar.php?id=<?php echo $cliente['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Editar cliente"
                                           data-bs-toggle="tooltip">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($total_compras == 0 && $activo): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    title="Eliminar cliente"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEliminar"
                                                    data-id="<?php echo $cliente['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($cliente['nombre']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary" 
                                                    title="<?php echo $total_compras > 0 ? 'No se puede eliminar (tiene compras)' : 'Cliente inactivo'; ?>"
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
            Información sobre Clientes
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">
                    <i class="fas fa-lightbulb me-2"></i>Recomendaciones:
                </h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> Mantén la información de contacto actualizada</li>
                    <li><i class="fas fa-check text-success me-2"></i> Usa emails válidos para notificaciones</li>
                    <li><i class="fas fa-check text-success me-2"></i> Registra todos los datos para mejor servicio</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Limitaciones:
                </h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-ban text-danger me-2"></i> No se pueden eliminar clientes con compras</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> Los emails deben ser únicos</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> Campos obligatorios: Nombre</li>
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
                <p>¿Estás seguro de que deseas eliminar al cliente "<strong id="nombreCliente"></strong>"?</p>
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
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Configurar DataTables
    $('#tablaClientes').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        pageLength: 10,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [0, 8] },
            { searchable: false, targets: [0, 4, 5, 6, 7, 8] }
        ],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        initComplete: function() {
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            $('.dataTables_length select').addClass('form-control form-control-sm');
        }
    });

    // Configurar modal de eliminación
    const modalEliminar = document.getElementById('modalEliminar');
    modalEliminar.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        
        document.getElementById('nombreCliente').textContent = nombre;
        document.getElementById('btnEliminarConfirmar').href = `index.php?action=delete&id=${id}&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>`;
    });

    <?php if ($action === 'delete' && !empty($success_message)): ?>
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