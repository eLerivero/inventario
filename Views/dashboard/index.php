<?php
// Views/dashboard/index.php

// Definir título de página
$page_title = 'Dashboard';

// Incluir header primero (maneja la autenticación)
require_once __DIR__ . '/../layouts/header.php';

// Incluir controladores necesarios
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/DashboardController.php';
require_once __DIR__ . '/../../Utils/Ayuda.php';
require_once __DIR__ . '/../../Utils/Auth.php';

// Obtener estadísticas del dashboard
try {
    $database = new Database();
    $db = $database->getConnection();
    $controller = new DashboardController($db);
    $data = $controller->obtenerEstadisticasCompletas();
} catch (Exception $e) {
    $data = [
        'resumen' => [
            'total_productos' => 0,
            'ventas_mes' => 0,
            'total_clientes' => 0,
            'productos_bajo_stock' => 0
        ],
        'ventas_recientes' => [],
        'productos_populares' => [],
        'productos_bajo_stock' => []
    ];
}
?>

<!-- Contenido del dashboard -->
<div class="container-fluid">
    <!-- Estadísticas principales -->
    <div class="row mb-4 mt-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title text-muted mb-2">Total Productos</h5>
                            <h2 class="text-primary mb-0"><?php echo $data['resumen']['total_productos']; ?></h2>
                            <small class="text-muted">Activos en inventario</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-box fa-2x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title text-muted mb-2">Ventas del Mes</h5>
                            <h2 class="text-success mb-0"><?php echo $data['resumen']['ventas_mes']; ?></h2>
                            <small class="text-muted">Transacciones completadas</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-shopping-cart fa-2x text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title text-muted mb-2">Total Clientes</h5>
                            <h2 class="text-warning mb-0"><?php echo $data['resumen']['total_clientes']; ?></h2>
                            <small class="text-muted">Clientes registrados</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x text-warning opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title text-muted mb-2">Stock Bajo</h5>
                            <h2 class="text-danger mb-0"><?php echo $data['resumen']['productos_bajo_stock']; ?></h2>
                            <small class="text-muted">Necesitan reabastecimiento</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Ventas Recientes -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Ventas Recientes
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['ventas_recientes'])): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($data['ventas_recientes'] as $venta): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary rounded p-2 me-3">
                                            <i class="fas fa-shopping-cart text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($venta['numero_venta'] ?? 'N/A'); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($venta['cliente_nombre'] ?? 'Cliente no especificado'); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-success d-block"><?php echo Ayuda::formatCurrency($venta['total'] ?? 0); ?></strong>
                                        <small class="text-muted"><?php echo Ayuda::formatDate($venta['created_at'] ?? date('Y-m-d')); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No hay ventas recientes</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white">
                    <a href="../ventas/index.php" class="btn btn-sm btn-outline-primary">
                        Ver todas las ventas
                    </a>
                </div>
            </div>
        </div>

        <!-- Productos Populares -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Productos Más Vendidos
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['productos_populares'])): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($data['productos_populares'] as $producto): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success rounded p-2 me-3">
                                            <i class="fas fa-box text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($producto['nombre'] ?? 'N/A'); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($producto['codigo_sku'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary rounded-pill fs-6">
                                        <?php echo $producto['total_vendido'] ?? 0; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No hay datos de productos vendidos</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white">
                    <a href="../productos/index.php" class="btn btn-sm btn-outline-primary">
                        Ver todos los productos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas de Stock Bajo -->
    <?php if (!empty($data['productos_bajo_stock'])): ?>
        <div class="row">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Alertas de Stock Bajo
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>SKU</th>
                                        <th>Stock Actual</th>
                                        <th>Stock Mínimo</th>
                                        <th>Diferencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['productos_bajo_stock'] as $producto): ?>
                                        <tr class="table-warning">
                                            <td>
                                                <strong><?php echo htmlspecialchars($producto['nombre'] ?? 'N/A'); ?></strong>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($producto['codigo_sku'] ?? ''); ?></code>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger fs-6"><?php echo $producto['stock_actual'] ?? 0; ?></span>
                                            </td>
                                            <td><?php echo $producto['stock_minimo'] ?? 0; ?></td>
                                            <td>
                                                <?php
                                                $diferencia = ($producto['stock_minimo'] ?? 0) - ($producto['stock_actual'] ?? 0);
                                                echo '<span class="badge bg-dark">-' . $diferencia . '</span>';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <a href="../productos/index.php" class="btn btn-sm btn-warning">
                            <i class="fas fa-box me-1"></i> Gestionar Inventario
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Acciones rápidas -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        <?php if (Auth::canAccessProductos()): ?>
                        <div class="col-md-3 col-6">
                            <a href="../productos/crear.php" class="btn btn-outline-primary w-100 h-100 py-3">
                                <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                Nuevo Producto
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if (Auth::canAccessVentas()): ?>
                        <div class="col-md-3 col-6">
                            <a href="../ventas/crear.php" class="btn btn-outline-success w-100 h-100 py-3">
                                <i class="fas fa-cash-register fa-2x mb-2"></i><br>
                                Nueva Venta
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (Auth::canAccessClientes()): ?>
                        <div class="col-md-3 col-6">
                            <a href="../clientes/crear.php" class="btn btn-outline-info w-100 h-100 py-3">
                                <i class="fas fa-user-plus fa-2x mb-2"></i><br>
                                Nuevo Cliente
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if (Auth::canAccessCategorias()): ?>
                        <div class="col-md-3 col-6">
                            <a href="../categorias/crear.php" class="btn btn-outline-warning w-100 h-100 py-3">
                                <i class="fas fa-tag fa-2x mb-2"></i><br>
                                Nueva Categoría
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<?php
// Incluir footer
require_once __DIR__ . '/../layouts/footer.php';
?>