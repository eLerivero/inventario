<?php
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/CategoriaController.php';
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Helpers/TasaCambioHelper.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$productoController = new ProductoController($db);
$categoriaController = new CategoriaController($db);
$tasaController = new TasaCambioController($db);

// Obtener productos y categorías
$productos_result = $productoController->listar();
$categorias = $categoriaController->obtenerTodas();
$tasaActual = $tasaController->obtenerTasaActual();

// Manejar eliminación
if (isset($_GET['eliminar'])) {
    $resultado = $productoController->eliminar($_GET['eliminar']);
    if ($resultado['success']) {
        $success_message = $resultado['message'];
        // Redirigir para evitar reenvío
        header("Refresh: 2; URL=index.php");
    } else {
        $error_message = $resultado['message'];
    }
}

// Verificar si hay productos
$productos = $productos_result['success'] ? $productos_result['data'] : [];

$page_title = "Gestión de Productos";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <!-- Header Section -->
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

    <!-- Información de Tasa de Cambio -->
    <?php if ($tasaActual['success']): ?>
        <div class="alert alert-info d-flex align-items-center mb-4">
            <i class="fas fa-exchange-alt fa-2x me-3"></i>
            <div>
                <h6 class="mb-1">Tasa de Cambio Actual</h6>
                <p class="mb-0">
                    <strong>1 USD = <?php echo number_format($tasaActual['data']['tasa_cambio'], 2); ?> Bs</strong>
                    <small class="text-muted ms-2">
                        (Actualizada: <?php echo date('d/m/Y H:i', strtotime($tasaActual['data']['fecha_actualizacion'])); ?>)
                    </small>
                </p>
            </div>
            <div class="ms-auto">
                <a href="../tasas-cambio/" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-cog me-1"></i> Gestionar Tasas
                </a>
            </div>
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

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
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

    <!-- Estadísticas Rápidas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Productos</h6>
                            <h4><strong><?php echo count($productos); ?></strong></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-box fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Activos</h6>
                            <h4><strong>
                                    <?php
                                    $activos = array_filter($productos, function ($prod) {
                                        return isset($prod['activo']) && $prod['activo'] == 1;
                                    });
                                    echo count($activos);
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
            <div class="card bg-warning text-white card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Bajo Stock</h6>
                            <h4><strong>
                                    <?php
                                    $bajo_stock = array_filter($productos, function ($prod) {
                                        return isset($prod['stock_actual']) &&
                                            isset($prod['stock_minimo']) &&
                                            $prod['stock_actual'] > 0 &&
                                            $prod['stock_actual'] <= $prod['stock_minimo'];
                                    });
                                    echo count($bajo_stock);
                                    ?>
                                </strong></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Sin Stock</h6>
                            <h4><strong>
                                    <?php
                                    $sin_stock = array_filter($productos, function ($prod) {
                                        return isset($prod['stock_actual']) && $prod['stock_actual'] == 0;
                                    });
                                    echo count($sin_stock);
                                    ?>
                                </strong></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Productos -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Lista de Productos
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($productos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay productos registrados</h4>
                    <p class="text-muted">Comienza creando tu primer producto.</p>
                    <a href="crear.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-1"></i> Crear Primer Producto
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaProductos">
                        <thead class="table-dark">
                            <tr>
                                <th>SKU</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio USD</th>
                                <th>Precio Bs</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto):
                                $margen = TasaCambioHelper::calcularMargenGanancia($producto['precio'], $producto['precio_costo']);
                                $claseMargen = TasaCambioHelper::obtenerClaseMargen($margen);
                            ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($producto['codigo_sku'] ?? 'N/A'); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($producto['nombre'] ?? 'Sin nombre'); ?></strong>
                                        <?php if (!empty($producto['descripcion'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php
                                                $descripcion = $producto['descripcion'] ?? '';
                                                if (strlen($descripcion) > 50) {
                                                    echo htmlspecialchars(substr($descripcion, 0, 50)) . '...';
                                                } else {
                                                    echo htmlspecialchars($descripcion);
                                                }
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $categoria_nombre = $producto['categoria_nombre'] ?? 'Sin categoría';
                                        echo htmlspecialchars($categoria_nombre);
                                        ?>
                                    </td>
                                    <td class="precio-usd">
                                        <strong>$<?php echo number_format($producto['precio'] ?? 0, 2); ?></strong>
                                        <?php if ($producto['precio_costo'] > 0): ?>
                                            <br>
                                            <small class="text-muted">
                                                Costo: $<?php echo number_format($producto['precio_costo'], 2); ?>
                                            </small>
                                            <br>
                                            <span class="badge <?php echo $claseMargen; ?>">
                                                <?php echo number_format($margen, 1); ?>%
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="precio-bs">
                                        <strong>
                                            <?php echo TasaCambioHelper::formatearPrecioProducto($producto, $db); ?>
                                        </strong>
                                        <?php if ($producto['precio_costo'] > 0): ?>
                                            <br>
                                            <small class="text-muted">
                                                Costo: <?php echo TasaCambioHelper::formatearBS($producto['precio_costo_bs'] ?? TasaCambioHelper::convertirUSDaBS($producto['precio_costo'], $db)); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $stock_actual = $producto['stock_actual'] ?? 0;
                                        $stock_minimo = $producto['stock_minimo'] ?? 0;
                                        $badge_class = 'bg-';
                                        if ($stock_actual == 0) {
                                            $badge_class .= 'danger';
                                        } elseif ($stock_actual <= $stock_minimo) {
                                            $badge_class .= 'warning';
                                        } else {
                                            $badge_class .= 'success';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $stock_actual; ?>
                                        </span>
                                        / <?php echo $stock_minimo; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($producto['activo']) && $producto['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="editar.php?id=<?php echo $producto['id']; ?>"
                                                class="btn btn-outline-primary"
                                                title="Editar producto"
                                                data-bs-toggle="tooltip">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-outline-danger btn-eliminar"
                                                title="Eliminar producto"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEliminar"
                                                data-id="<?php echo $producto['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($producto['nombre'] ?? 'Producto'); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <a href="../historial-stock/index.php?producto=<?php echo $producto['id']; ?>"
                                                class="btn btn-outline-info"
                                                title="Historial de stock"
                                                data-bs-toggle="tooltip">
                                                <i class="fas fa-history"></i>
                                            </a>
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
                <p>¿Estás seguro de que deseas eliminar el producto "<strong id="nombreProducto"></strong>"?</p>
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
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Configurar DataTables
        $('#tablaProductos').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 10,
            order: [
                [1, 'asc']
            ],
            columnDefs: [{
                    orderable: false,
                    targets: [7]
                }, // Deshabilitar ordenación en columna acciones
                {
                    searchable: false,
                    targets: [0, 2, 3, 4, 5, 6, 7]
                } // Deshabilitar búsqueda en algunas columnas
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            initComplete: function() {
                // Añadir clase para mejorar el estilo
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

            document.getElementById('nombreProducto').textContent = nombre;
            document.getElementById('btnEliminarConfirmar').href = `index.php?eliminar=${id}`;
        });

        // Filtros en tiempo real
        $('#search').on('keyup', function() {
            const table = $('#tablaProductos').DataTable();
            table.search(this.value).draw();
        });

        $('#categoria').on('change', function() {
            const table = $('#tablaProductos').DataTable();
            table.column(2).search(this.value).draw();
        });

        $('#stock').on('change', function() {
            const valor = $(this).val();
            const table = $('#tablaProductos').DataTable();

            if (valor === 'bajo') {
                // Filtrar productos con stock bajo (stock_actual <= stock_minimo pero > 0)
                table.rows().every(function() {
                    const data = this.data();
                    const stockText = $(data[5]).text();
                    const stockActual = parseInt(stockText.match(/\d+/)[0]); // Extraer número del badge
                    const stockMinimo = parseInt(stockText.split('/')[1]);
                    const visible = stockActual > 0 && stockActual <= stockMinimo;
                    this.nodes().to$().toggle(visible);
                });
            } else if (valor === 'sin') {
                // Filtrar productos sin stock (stock_actual = 0)
                table.rows().every(function() {
                    const data = this.data();
                    const stockText = $(data[5]).text();
                    const stockActual = parseInt(stockText.match(/\d+/)[0]);
                    const visible = stockActual === 0;
                    this.nodes().to$().toggle(visible);
                });
            } else if (valor === 'normal') {
                // Filtrar productos con stock normal (stock_actual > stock_minimo)
                table.rows().every(function() {
                    const data = this.data();
                    const stockText = $(data[5]).text();
                    const stockActual = parseInt(stockText.match(/\d+/)[0]);
                    const stockMinimo = parseInt(stockText.split('/')[1]);
                    const visible = stockActual > stockMinimo;
                    this.nodes().to$().toggle(visible);
                });
            } else {
                // Mostrar todos
                table.rows().every(function() {
                    this.nodes().to$().show();
                });
            }
            table.draw();
        });

        // Auto-ocultar alertas después de 5 segundos
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