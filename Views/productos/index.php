<?php
require_once '../../Config/Database.php';
require_once '../../Models/Producto.php';
require_once '../../Controllers/ProductoController.php';

$database = new Database();
$db = $database->getConnection();

$controller = new ProductoController($db);
$productos = $controller->listar();
$productosBajoStock = $controller->obtenerProductosBajoStock();
$estadisticas = $controller->obtenerEstadisticas();
?>

<?php include '../layouts/header.php'; ?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Gestión de Productos</h2>

    <!-- Estadísticas rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-box text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Productos</p>
                    <p class="text-xl font-bold"><?php echo $estadisticas['total_productos']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-warehouse text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Stock Total</p>
                    <p class="text-xl font-bold"><?php echo $estadisticas['total_stock']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Stock Bajo</p>
                    <p class="text-xl font-bold"><?php echo $estadisticas['productos_bajo_stock']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-dollar-sign text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Precio Promedio</p>
                    <p class="text-xl font-bold">$<?php echo number_format($estadisticas['precio_promedio'], 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de acciones -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
        <div class="flex flex-col md:flex-row gap-4">
            <a href="crear.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200 flex items-center">
                <i class="fas fa-plus mr-2"></i>Nuevo Producto
            </a>

            <!-- Buscador -->
            <div class="relative">
                <input type="text" id="searchInput" placeholder="Buscar productos..."
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
            </div>
        </div>

        <div class="flex space-x-2">
            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm flex items-center">
                <i class="fas fa-check-circle mr-1"></i> Activos: <?php echo count($productos); ?>
            </span>
            <?php if (count($productosBajoStock) > 0): ?>
                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm flex items-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Alertas: <?php echo count($productosBajoStock); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tabla de productos -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div id="searchResults">
        <!-- Los resultados de búsqueda se cargarán aquí -->
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="productTableBody">
                <?php foreach ($productos as $producto): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($producto['codigo_sku']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                            <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($producto['descripcion']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="font-semibold">$<?php echo number_format($producto['precio'], 2); ?></div>
                            <?php if ($producto['precio_costo']): ?>
                                <div class="text-xs text-gray-500">Costo: $<?php echo number_format($producto['precio_costo'], 2); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $producto['stock_actual']; ?> unidades</div>
                            <div class="text-xs text-gray-500">Mín: <?php echo $producto['stock_minimo']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($producto['stock_actual'] == 0): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    <i class="fas fa-times mr-1"></i> Sin Stock
                                </span>
                            <?php elseif ($producto['stock_actual'] <= $producto['stock_minimo']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Stock Bajo
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i> Normal
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="editar.php?id=<?php echo $producto['id']; ?>"
                                    class="text-blue-600 hover:text-blue-900 flex items-center">
                                    <i class="fas fa-edit mr-1"></i> Editar
                                </a>
                                <a href="#"
                                    class="text-red-600 hover:text-red-900 flex items-center"
                                    onclick="return confirm('¿Estás seguro de eliminar este producto?')">
                                    <i class="fas fa-trash mr-1"></i> Eliminar
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (empty($productos)): ?>
            <div class="text-center py-8">
                <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No hay productos registrados</p>
                <a href="crear.php" class="text-blue-500 hover:text-blue-700 mt-2 inline-block">
                    Crear primer producto
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript para búsqueda en tiempo real -->
<script>
    document.getElementById('searchInput').addEventListener('input', function(e) {
        const searchTerm = e.target.value;

        if (searchTerm.length >= 2) {
            fetch('buscar.php?q=' + encodeURIComponent(searchTerm))
                .then(response => response.text())
                .then(data => {
                    document.getElementById('searchResults').innerHTML = data;
                });
        } else if (searchTerm.length === 0) {
            // Recargar tabla completa
            location.reload();
        }
    });
</script>

<?php include '../layouts/footer.php'; ?>