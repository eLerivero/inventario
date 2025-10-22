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

// Procesar eliminación
if ($action === 'delete' && $id) {
    $result = $controller->eliminar($id);
    if ($result['success']) {
        $success_message = $result['message'];
    } else {
        $error_message = $result['message'];
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
?>

<?php 
$page_title = "Gestión de Categorías";
include '../layouts/header.php'; 
?>

<div class="container-fluid">
    <div class="row">   
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-tags me-2"></i>
                    Gestión de Categorías
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="crear.php" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> Nueva Categoría
                    </a>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
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
                                            return $cat['total_productos'] > 0;
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
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categorias as $index => $categoria): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                                            </td>
                                            <td>
                                                <?php 
                                                $descripcion = $categoria['descripcion'];
                                                if (empty($descripcion)) {
                                                    echo '<span class="text-muted">Sin descripción</span>';
                                                } else {
                                                    echo htmlspecialchars($descripcion);
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $categoria['total_productos'] > 0 ? 'primary' : 'secondary'; ?>">
                                                    <?php echo $categoria['total_productos']; ?> productos
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo Ayuda::formatDate($categoria['created_at']); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="editar.php?id=<?php echo $categoria['id']; ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Editar categoría">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($categoria['total_productos'] == 0): ?>
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
                                                                title="No se puede eliminar (tiene productos)"
                                                                disabled>
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
        </main>
    </div>
</div>

<!-- Modal de Eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar la categoría "<strong id="nombreCategoria"></strong>"?</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="btnEliminarConfirmar" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i> Eliminar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar DataTables
    $('#tablaCategorias').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        pageLength: 10,
        order: [[1, 'asc']]
    });

    // Configurar modal de eliminación
    const modalEliminar = document.getElementById('modalEliminar');
    modalEliminar.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        
        document.getElementById('nombreCategoria').textContent = nombre;
        document.getElementById('btnEliminarConfirmar').href = `index.php?action=delete&id=${id}`;
    });
});
</script>

<?php include '../layouts/footer.php'; ?>