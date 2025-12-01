<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/CategoriaController.php';
require_once '../../Utils/Ayuda.php';

$database = new Database();
$db = $database->getConnection();

$controller = new CategoriaController($db);

// Manejar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';

// Procesar eliminación
if ($action === 'delete' && $id) {
    // Verificar token CSRF
    if (!isset($_GET['token']) || $_GET['token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error_message = "Token de seguridad inválido";
    } else {
        $result = $controller->eliminar($id);
        if ($result['success']) {
            $success_message = $result['message'];
            // Redirigir para evitar reenvío del formulario
            header("Refresh: 2; URL=index.php");
        } else {
            $error_message = $result['message'];
        }
    }
}

// Obtener todas las categorías
$result = $controller->listar();
if ($result['success']) {
    $categorias = $result['data'];
} else {
    $error_message = $result['message'];
    $categorias = [];
}

// Generar token CSRF para seguridad
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<?php 
$page_title = "Gestión de Categorías";
include '../layouts/header.php'; 
?>


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
                        <h5 class="card-title">Total Categorías</h5>
                        <h3><?php echo count($categorias); ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-tags fa-2x"></i>
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
                        <h5 class="card-title">Con Productos</h5>
                        <h3>
                            <?php 
                            $con_productos = array_filter($categorias, function($cat) {
                                $total_productos = isset($cat['total_productos']) ? $cat['total_productos'] : 0;
                                return $total_productos > 0;
                            });
                            echo count($con_productos);
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-box fa-2x"></i>
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
                        <h5 class="card-title">Sin Productos</h5>
                        <h3>
                            <?php 
                            $sin_productos = array_filter($categorias, function($cat) {
                                $total_productos = isset($cat['total_productos']) ? $cat['total_productos'] : 0;
                                return $total_productos == 0;
                            });
                            echo count($sin_productos);
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-box-open fa-2x"></i>
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
                        <h5 class="card-title">Activas</h5>
                        <h3>
                            <?php 
                            $activas = array_filter($categorias, function($cat) {
                                $activo = isset($cat['activo']) ? $cat['activo'] : 1;
                                return $activo == 1;
                            });
                            echo count($activas);
                            ?>
                        </h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Botón de agregar -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-tags me-2"></i>
            Gestión de Categorías
        </h1>
    </div>
    <div>
        <a href="crear.php" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Nueva Categoría
        </a>
    </div>
</div>

<!-- Tabla de Categorías -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Categorías
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($categorias)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No hay categorías registradas</h4>
                <p class="text-muted">Comienza creando tu primera categoría.</p>
                <a href="crear.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-1"></i> Crear Primera Categoría
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tablaCategorias">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Productos</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $index => $categoria): 
                            $activo = isset($categoria['activo']) ? $categoria['activo'] : 1;
                            $total_productos = isset($categoria['total_productos']) ? $categoria['total_productos'] : 0;
                            $descripcion = isset($categoria['descripcion']) ? $categoria['descripcion'] : '';
                            $created_at = isset($categoria['created_at']) ? $categoria['created_at'] : '';
                        ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                                    <?php if ($activo == 0): ?>
                                        <span class="badge bg-secondary ms-1">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if (empty($descripcion)) {
                                        echo '<span class="text-muted">Sin descripción</span>';
                                    } else {
                                        if (strlen($descripcion) > 80) {
                                            echo '<span data-bs-toggle="tooltip" title="' . htmlspecialchars($descripcion) . '">';
                                            echo htmlspecialchars(substr($descripcion, 0, 80)) . '...';
                                            echo '</span>';
                                        } else {
                                            echo htmlspecialchars($descripcion);
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $total_productos > 0 ? 'primary' : 'secondary'; ?>">
                                        <?php echo $total_productos; ?> productos
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $activo ? 'success' : 'secondary'; ?>">
                                        <?php echo $activo ? 'Activa' : 'Inactiva'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo !empty($created_at) ? Ayuda::formatDate($created_at) : 'N/A'; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="editar.php?id=<?php echo $categoria['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Editar categoría"
                                           data-bs-toggle="tooltip">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($total_productos == 0 && $activo == 1): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    title="Eliminar categoría"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEliminar"
                                                    data-id="<?php echo $categoria['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($categoria['nombre']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary" 
                                                    title="<?php echo $total_productos > 0 ? 'No se puede eliminar (tiene productos)' : 'Categoría inactiva'; ?>"
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
            Información sobre Categorías
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
                    <li><i class="fas fa-check text-success me-2"></i> Organiza las categorías de forma lógica</li>
                    <li><i class="fas fa-check text-success me-2"></i> Mantén las descripciones actualizadas</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Limitaciones:
                </h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-ban text-danger me-2"></i> No se pueden eliminar categorías con productos</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> Los nombres deben ser únicos</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> Máximo 100 caracteres para nombres</li>
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
                <p>¿Estás seguro de que deseas eliminar la categoría "<strong id="nombreCategoria"></strong>"?</p>
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
    $('#tablaCategorias').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        pageLength: 10,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [6] },
            { searchable: false, targets: [0, 3, 4, 5, 6] }
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
        
        document.getElementById('nombreCategoria').textContent = nombre;
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

<!-- <?php include '../layouts/footer.php'; ?> -->