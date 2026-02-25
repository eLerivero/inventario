<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/ProductoController.php';

$database = new Database();
$db = $database->getConnection();
$controller = new ProductoController($db);

if (isset($_GET['q'])) {
    $searchTerm = $_GET['q'];
    $productos = $controller->buscar($searchTerm);

    if (!empty($productos)) {
        foreach ($productos as $producto) {
            echo '<tr class="hover:bg-gray-50">';
            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">';
            echo htmlspecialchars($producto['codigo_sku']);
            echo '</td>';
            echo '<td class="px-6 py-4">';
            echo '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($producto['nombre']) . '</div>';
            echo '<div class="text-sm text-gray-500">' . htmlspecialchars($producto['descripcion']) . '</div>';
            echo '</td>';
            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">';
            echo '$' . number_format($producto['precio'], 2);
            echo '</td>';
            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">';
            echo $producto['stock_actual'] . ' unidades';
            echo '</td>';
            echo '<td class="px-6 py-4 whitespace-nowrap">';
            if ($producto['stock_actual'] <= $producto['stock_minimo']) {
                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Stock Bajo</span>';
            } else {
                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Normal</span>';
            }
            echo '</td>';
            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">';
            echo '<a href="editar.php?id=' . $producto['id'] . '" class="text-blue-600 hover:text-blue-900 mr-3">Editar</a>';
            echo '<a href="#" class="text-red-600 hover:text-red-900">Eliminar</a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No se encontraron productos</td></tr>';
    }
}
