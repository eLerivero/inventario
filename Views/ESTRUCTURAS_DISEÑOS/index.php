<?php
// 1. INCLUSIÓN DE DEPENDENCIAS
require_once '../../Config/Database.php';
require_once '../../Controllers/[Nombre]Controller.php';
require_once '../../Utils/Ayuda.php'; // Opcional

// 2. CONFIGURACIÓN INICIAL
$database = new Database();
$db = $database->getConnection();
$controller = new [Nombre]Controller($db);

// 3. MANEJO DE ACCIONES
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';

// Ejemplo: Eliminación
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

// 4. OBTENCIÓN DE DATOS
$result = $controller->listar();
if ($result['success']) {
    $datos = $result['data'];
} else {
    $error_message = $result['message'];
    $datos = [];
}

// 5. SEGURIDAD CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<?php 
// 6. CONFIGURACIÓN DE PÁGINA
$page_title = "Gestión de [Módulo]";
include '../layouts/header.php'; 
?>

<!-- 7. CONTENIDO PRINCIPAL -->

<!-- Alertas -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
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

<!-- Estadísticas (Opcional) -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total [Módulo]</h5>
                        <h3><?php echo count($datos); ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-[icono] fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Más tarjetas según necesidades -->
</div>

<!-- Tabla Principal -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de [Módulo]
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($datos)): ?>
            <!-- Estado Vacío -->
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No hay [módulo] registrados</h4>
                <p class="text-muted">Comienza creando tu primer [elemento].</p>
                <a href="crear.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-1"></i> Crear Primer [Elemento]
                </a>
            </div>
        <?php else: ?>
            <!-- Tabla con Datos -->
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tabla[Modulo]">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <!-- Más columnas según entidad -->
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $index => $item): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>
                                    <?php if (isset($item['activo']) && $item['activo'] == 0): ?>
                                        <span class="badge bg-secondary ms-1">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <!-- Más celdas según datos -->
                                <td>
                                    <span class="badge bg-<?php echo ($item['activo'] ?? 1) ? 'success' : 'secondary'; ?>">
                                        <?php echo ($item['activo'] ?? 1) ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo !empty($item['created_at']) ? Ayuda::formatDate($item['created_at']) : 'N/A'; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="editar.php?id=<?php echo $item['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Editar [elemento]"
                                           data-bs-toggle="tooltip">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <!-- Condiciones para eliminar -->
                                        <?php if (($item['total_relaciones'] ?? 0) == 0 && ($item['activo'] ?? 1) == 1): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger" 
                                                    title="Eliminar [elemento]"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEliminar"
                                                    data-id="<?php echo $item['id']; ?>"
                                                    data-nombre="<?php echo htmlspecialchars($item['nombre']); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" 
                                                    class="btn btn-outline-secondary" 
                                                    title="<?php echo ($item['total_relaciones'] ?? 0) > 0 ? 'No se puede eliminar (tiene relaciones)' : 'Elemento inactivo'; ?>"
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

<!-- Información Adicional (Opcional) -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Información sobre [Módulo]
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">
                    <i class="fas fa-lightbulb me-2"></i>Recomendaciones:
                </h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> Recomendación 1</li>
                    <li><i class="fas fa-check text-success me-2"></i> Recomendación 2</li>
                    <li><i class="fas fa-check text-success me-2"></i> Recomendación 3</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Limitaciones:
                </h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-ban text-danger me-2"></i> Limitación 1</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> Limitación 2</li>
                    <li><i class="fas fa-ban text-danger me-2"></i> Limitación 3</li>
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
                <p>¿Estás seguro de que deseas eliminar el [elemento] "<strong id="nombreElemento"></strong>"?</p>
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

<!-- Scripts Específicos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Configurar DataTables
    $('#tabla[Modulo]').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        pageLength: 10,
        order: [[1, 'asc']], // Ordenar por segunda columna (nombre)
        columnDefs: [
            { orderable: false, targets: [0, -1] }, // No ordenar primera y última columna
            { searchable: false, targets: [0, -1] } // No buscar en primera y última columna
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
        
        document.getElementById('nombreElemento').textContent = nombre;
        document.getElementById('btnEliminarConfirmar').href = `index.php?action=delete&id=${id}&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>`;
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

<?php include '../layouts/footer.php'; ?>