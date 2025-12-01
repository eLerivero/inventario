<?php
// Views/productos/precios-fijos.php
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$productoController = new ProductoController($db);
$tasaController = new TasaCambioController($db);

// Obtener productos con precio fijo
$result = $productoController->obtenerProductosConPrecioFijo();
$productos = $result['success'] ? $result['data'] : [];
$tasaActual = $tasaController->obtenerTasaActual();

$page_title = "Productos con Precio Fijo en Bolívares";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-lock me-2"></i>Productos con Precio Fijo en Bolívares
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="../productos/crear.php" class="btn btn-success me-2">
                <i class="fas fa-plus me-1"></i>Nuevo Producto
            </a>
            <a href="../productos/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver a Productos
            </a>
        </div>
    </div>

    <!-- Información de Tasa -->
    <?php if ($tasaActual['success']): ?>
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exchange-alt me-3 fa-lg"></i>
                <div>
                    <strong>Tasa de Cambio Actual:</strong> 1 USD = <?php echo number_format($tasaActual['data']['tasa_cambio'], 2); ?> Bs
                    <br>
                    <small class="text-muted">Los productos con precio fijo mantienen su precio en Bs independientemente de la tasa</small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Nota:</strong> Estos productos mantienen un precio fijo en bolívares que no se actualiza automáticamente con la tasa de cambio.
        Si necesitas actualizar sus precios, debes editarlos manualmente.
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Lista de Productos con Precio Fijo
                <span class="badge bg-warning ms-2"><?php echo count($productos); ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($productos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay productos con precio fijo</h4>
                    <p class="text-muted">Puedes marcar productos como precio fijo al crearlos o editarlos.</p>
                    <a href="../productos/crear.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-1"></i> Crear Producto con Precio Fijo
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaPreciosFijos">
                        <thead class="table-dark">
                            <tr>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Precio USD</th>
                                <th>Precio Bs (Fijo)</th>
                                <th>Tasa Equivalente</th>
                                <th>Diferencia con Tasa Actual</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $tasa_cambio_actual = $tasaActual['success'] ? $tasaActual['data']['tasa_cambio'] : 36.5;
                            foreach ($productos as $producto):
                                $tasa_equivalente = $producto['precio'] > 0 ? $producto['precio_bs'] / $producto['precio'] : 0;
                                $diferencia_tasa = $tasa_equivalente - $tasa_cambio_actual;
                                $diferencia_porcentaje = $tasa_cambio_actual > 0 ? ($diferencia_tasa / $tasa_cambio_actual) * 100 : 0;
                            ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($producto['codigo_sku']); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                        <?php if ($producto['descripcion']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 50)); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($producto['precio'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <strong class="text-success">Bs <?php echo number_format($producto['precio_bs'], 2); ?></strong>
                                            <span class="badge bg-warning ms-2">Fijo</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($producto['precio'] > 0): ?>
                                            <small>1 USD = <?php echo number_format($tasa_equivalente, 2); ?> Bs</small>
                                            <br>
                                            <small class="text-muted">Tasa equivalente</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tasa_cambio_actual > 0): ?>
                                            <span class="badge <?php echo $diferencia_porcentaje > 10 ? 'bg-danger' : ($diferencia_porcentaje > 5 ? 'bg-warning' : 'bg-success'); ?>">
                                                <?php echo number_format($diferencia_porcentaje, 1); ?>%
                                            </span>
                                            <br>
                                            <small class="text-muted"><?php echo number_format($diferencia_tasa, 2); ?> Bs</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $producto['stock_actual'] <= $producto['stock_minimo'] ? 'warning' : 'success'; ?>">
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
                                            <a href="../productos/editar.php?id=<?php echo $producto['id']; ?>"
                                                class="btn btn-outline-primary"
                                                title="Editar producto">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../productos/index.php?eliminar=<?php echo $producto['id']; ?>"
                                                class="btn btn-outline-danger"
                                                title="Eliminar producto"
                                                onclick="return confirm('¿Estás seguro de eliminar este producto?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <td colspan="3" class="text-end"><strong>Totales:</strong></td>
                                <td><strong><?php echo count($productos); ?> productos</strong></td>
                                <td colspan="2"></td>
                                <td>
                                    <strong>
                                        <?php
                                        $total_stock = array_sum(array_column($productos, 'stock_actual'));
                                        echo $total_stock;
                                        ?>
                                    </strong>
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estadísticas -->
    <?php if (!empty($productos)): ?>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Valor Inventario (USD)</h5>
                                <h3>
                                    <?php
                                    $valor_usd = 0;
                                    foreach ($productos as $producto) {
                                        $valor_usd += $producto['stock_actual'] * $producto['precio'];
                                    }
                                    echo '$' . number_format($valor_usd, 2);
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Valor Inventario (Bs)</h5>
                                <h3>
                                    <?php
                                    $valor_bs = 0;
                                    foreach ($productos as $producto) {
                                        $valor_bs += $producto['stock_actual'] * $producto['precio_bs'];
                                    }
                                    echo 'Bs ' . number_format($valor_bs, 2);
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
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Precio Promedio (Bs)</h5>
                                <h3>
                                    <?php
                                    $precio_promedio = count($productos) > 0 ? array_sum(array_column($productos, 'precio_bs')) / count($productos) : 0;
                                    echo 'Bs ' . number_format($precio_promedio, 2);
                                    ?>
                                </h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar DataTables
        $('#tablaPreciosFijos').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [
                [0, 'asc']
            ],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control form-control-sm');
                $('.dataTables_length select').addClass('form-control form-control-sm');
            }
        });
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->