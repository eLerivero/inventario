<?php
require_once '../../Config/Database.php';
require_once '../../Config/Constants.php';
require_once '../../Controllers/DashboardController.php';

$database = new Database();
$db = $database->getConnection();

$controller = new DashboardController($db);
$data = $controller->obtenerEstadisticasCompletas();
?>

<?php include '../layouts/header.php'; ?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
    <p class="text-gray-600">Resumen general del sistema</p>
</div>

<!-- Estadísticas principales -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-lg">
                <i class="fas fa-box text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Productos</p>
                <p class="text-2xl font-bold"><?php echo $data['resumen']['total_productos']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-lg">
                <i class="fas fa-shopping-cart text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Ventas del Mes</p>
                <p class="text-2xl font-bold"><?php echo $data['resumen']['ventas_mes']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-lg">
                <i class="fas fa-users text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total Clientes</p>
                <p class="text-2xl font-bold"><?php echo $data['resumen']['total_clientes']; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
        <div class="flex items-center">
            <div class="p-3 bg-red-100 rounded-lg">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Stock Bajo</p>
                <p class="text-2xl font-bold"><?php echo $data['resumen']['productos_bajo_stock']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Ventas Recientes -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Ventas Recientes</h2>
        </div>
        <div class="p-6">
            <?php if (!empty($data['ventas_recientes'])): ?>
                <div class="space-y-4">
                    <?php foreach ($data['ventas_recientes'] as $venta): ?>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo $venta['numero_venta']; ?></p>
                                <p class="text-sm text-gray-500"><?php echo $venta['cliente_nombre']; ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-green-600"><?php echo Helpers::formatCurrency($venta['total']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo Helpers::formatDate($venta['created_at']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No hay ventas recientes</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Productos Populares -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Productos Más Vendidos</h2>
        </div>
        <div class="p-6">
            <?php if (!empty($data['productos_populares'])): ?>
                <div class="space-y-4">
                    <?php foreach ($data['productos_populares'] as $producto): ?>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo $producto['nombre']; ?></p>
                                <p class="text-sm text-gray-500"><?php echo $producto['codigo_sku']; ?></p>
                            </div>
                            <div class="text-right">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">
                                    <?php echo $producto['total_vendido']; ?> vendidos
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No hay datos de productos vendidos</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Alertas de Stock Bajo -->
<?php if (!empty($data['productos_bajo_stock'])): ?>
    <div class="mt-8 bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                Alertas de Stock Bajo
            </h2>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Actual</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Mínimo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($data['productos_bajo_stock'] as $producto): ?>
                            <tr class="hover:bg-red-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $producto['nombre']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $producto['codigo_sku']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                    <?php echo $producto['stock_actual']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $producto['stock_minimo']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>