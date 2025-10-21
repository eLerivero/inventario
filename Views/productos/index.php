<?php
require_once '../includes/init.php';

// Incluir layout
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

// Incluir controladores específicos
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/CategoriaController.php';

$database = new Database();
$db = $database->getConnection();
$productoController = new ProductoController($db);
$categoriaController = new CategoriaController($db);

$productos = $productoController->listar();
$categorias = $categoriaController->obtenerTodas();

// Manejar eliminación
if (isset($_GET['eliminar'])) {
    $resultado = $productoController->eliminar($_GET['eliminar']);
    if ($resultado['success']) {
        echo '<script>showAlert("Producto eliminado correctamente", "success");</script>';
    } else {
        echo '<script>showAlert("' . htmlspecialchars($resultado['message']) . '", "danger");</script>';
    }
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-box me-2"></i>Gestión de Productos
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="crear.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Nuevo Producto
            </a>
        </div>
    </div>

    <div id="alerts-container"></div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label for="search" class="form-label">Buscar Producto</label>
                    <input type="text" class="form-control" id="search" placeholder="Nombre, SKU o descripción...">
                </div>
                <div class="col-md-4">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo htmlspecialchars($categoria['id']); ?>">
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="stock" class="form-label">Estado de Stock</label>
                    <select class="form-select" id="stock">
                        <option value="">Todos</option>
                        <option value="bajo">Bajo Stock</option>
                        <option value="sin">Sin Stock</option>
                        <option value="normal">Stock Normal</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Productos -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Lista de Productos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($producto['codigo_sku']); ?></code></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                    <?php if (!empty($producto['descripcion'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                                <td class="text-success">
                                    <strong>$<?php echo number_format($producto['precio'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?php
                                                            echo $producto['stock_actual'] == 0 ? 'danger' : ($producto['stock_actual'] <= $producto['stock_minimo'] ? 'warning' : 'success');
                                                            ?>">
                                        <?php echo $producto['stock_actual']; ?>
                                    </span>
                                    / <?php echo $producto['stock_minimo']; ?>
                                </td>
                                <td>
                                    <?php if ($producto['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="editar.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?eliminar=<?php echo $producto['id']; ?>" class="btn btn-outline-danger btn-delete" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="../historial-stock/index.php?producto=<?php echo $producto['id']; ?>" class="btn btn-outline-info" title="Historial">
                                            <i class="fas fa-history"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    $(document).ready(function() {
        // Filtros en tiempo real
        $('#search, #categoria, #stock').on('change keyup', function() {
            const table = $('.datatable').DataTable();
            table.draw();
        });

        // Configuración de DataTables con filtros personalizados
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            responsive: true,
            columnDefs: [{
                orderable: false,
                targets: -1
            }],
            initComplete: function() {
                // Filtro por categoría
                this.api().column(2).every(function() {
                    var column = this;
                    $('#categoria').on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });
                });

                // Filtro por estado de stock
                $('#stock').on('change', function() {
                    const valor = $(this).val();
                    const table = $('.datatable').DataTable();

                    if (valor === 'bajo') {
                        table.column(4).search('\\b[1-9]\\b', true, false).draw();
                    } else if (valor === 'sin') {
                        table.column(4).search('^0$', true, false).draw();
                    } else if (valor === 'normal') {
                        table.column(4).search('^[1-9][0-9]*$', true, false).draw();
                    } else {
                        table.column(4).search('').draw();
                    }
                });

                // Filtro de búsqueda general
                $('#search').on('keyup', function() {
                    const table = $('.datatable').DataTable();
                    table.search(this.value).draw();
                });
            }
        });
    });
</script>

<?php require_once '../layouts/footer.php'; ?>