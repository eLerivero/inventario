<?php
// Views/productos/index.php
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/CategoriaController.php';
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Helpers/TasaCambioHelper.php';
require_once '../../Config/Database.php';
require_once __DIR__ . '/../../Utils/Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Verificar acceso específico a productos (solo admin)
Auth::requireAccessToProductos();

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
            <a href="crear.php" class="btn btn-success me-2">
                <i class="fas fa-plus me-2"></i>Nuevo Producto
            </a>
            <a href="../ventas/venta-rapida-por-peso.php" class="btn btn-warning">
                <i class="fas fa-weight me-2"></i>Venta Rápida por Peso
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

    <!-- Filtros Rápidos -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="search" placeholder="SKU, nombre o descripción">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Categoría</label>
                    <select class="form-select" id="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo de Venta</label>
                    <select class="form-select" id="tipo_venta">
                        <option value="">Todos</option>
                        <option value="unidad">Por Unidad</option>
                        <option value="peso">Por Peso</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Stock</label>
                    <select class="form-select" id="stock">
                        <option value="">Todos</option>
                        <option value="bajo">Bajo Stock</option>
                        <option value="sin">Sin Stock</option>
                        <option value="normal">Stock Normal</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary me-2" onclick="exportarExcel()">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </button>
                    <button class="btn btn-outline-secondary" onclick="imprimirLista()">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="row mb-4">
        <div class="col-md-2">
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
        <div class="col-md-2">
            <div class="card bg-success text-white card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Por Unidad</h6>
                            <h4><strong>
                                    <?php
                                    $unidad = array_filter($productos, function ($p) {
                                        return ($p['tipo_venta'] ?? 'unidad') === 'unidad';
                                    });
                                    echo count($unidad);
                                    ?>
                                </strong></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-cube fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Por Peso</h6>
                            <h4><strong>
                                    <?php
                                    $peso = array_filter($productos, function ($p) {
                                        return ($p['tipo_venta'] ?? 'unidad') === 'peso';
                                    });
                                    echo count($peso);
                                    ?>
                                </strong></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-weight fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Bajo Stock</h6>
                            <h4><strong>
                                    <?php
                                    $bajo_stock = array_filter($productos, function ($p) {
                                        $stock = floatval($p['stock_actual'] ?? 0);
                                        $minimo = floatval($p['stock_minimo'] ?? 5);
                                        return $stock > 0 && $stock <= $minimo;
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
        <div class="col-md-2">
            <div class="card bg-danger text-white card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Sin Stock</h6>
                            <h4><strong>
                                    <?php
                                    $sin_stock = array_filter($productos, function ($p) {
                                        return floatval($p['stock_actual'] ?? 0) == 0;
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
        <div class="col-md-2">
            <div class="card bg-secondary text-white card-stat">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Precio Fijo</h6>
                            <h4><strong>
                                    <?php
                                    $precio_fijo = array_filter($productos, function ($p) {
                                        return isset($p['usar_precio_fijo_bs']) && $p['usar_precio_fijo_bs'] == 1;
                                    });
                                    echo count($precio_fijo);
                                    ?>
                                </strong></h4>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-lock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Productos -->
    <div class="card">
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
                                <th>Tipo</th>
                                <th>Precio USD</th>
                                <th>Precio Bs</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto):
                                // Obtener tasa de cambio actual para cálculos
                                $tasa_cambio = $tasaActual['success'] ? $tasaActual['data']['tasa_cambio'] : 1.0;

                                // Determinar tipo de venta
                                $tipo_venta = $producto['tipo_venta'] ?? 'unidad';
                                $unidad_medida = $producto['unidad_medida'] ?? 'kg';
                                $es_precio_fijo = isset($producto['usar_precio_fijo_bs']) && $producto['usar_precio_fijo_bs'] == 1;

                                // Calcular precios según tipo de venta
                                if ($tipo_venta === 'peso') {
                                    // Producto por peso - usar precio por kilo
                                    $precio_usd = $producto['precio_por_kilo_usd'] > 0 ? $producto['precio_por_kilo_usd'] : $producto['precio'];

                                    if ($es_precio_fijo && $producto['precio_por_kilo_bs'] > 0) {
                                        $precio_bs = $producto['precio_por_kilo_bs'];
                                    } else {
                                        $precio_bs = $precio_usd * $tasa_cambio;
                                    }

                                    $precio_label = "/$unidad_medida";
                                    $tipo_texto = 'Por Peso';
                                    $tipo_clase = 'badge bg-info';
                                } else {
                                    // Producto por unidad - precio por unidad
                                    $precio_usd = $producto['precio'] ?? 0;

                                    if ($es_precio_fijo && $producto['precio_bs'] > 0) {
                                        $precio_bs = $producto['precio_bs'];
                                    } else {
                                        $precio_bs = $precio_usd * $tasa_cambio;
                                    }

                                    $precio_label = "/unidad";
                                    $tipo_texto = 'Por Unidad';
                                    $tipo_clase = 'badge bg-secondary';
                                }

                                // Calcular margen
                                $precio_costo = $producto['precio_costo'] ?? 0;
                                $margenData = TasaCambioHelper::calcularMargenGanancia($precio_usd, $precio_costo);
                                $porcentajeMargen = $margenData['porcentaje'];
                                $claseMargen = TasaCambioHelper::obtenerClaseMargen($porcentajeMargen);
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
                                    <td>
                                        <span class="<?php echo $tipo_clase; ?>">
                                            <?php if ($tipo_venta === 'peso'): ?>
                                                <i class="fas fa-weight me-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-cube me-1"></i>
                                            <?php endif; ?>
                                            <?php echo $tipo_texto; ?>
                                        </span>
                                        <?php if ($tipo_venta === 'peso'): ?>
                                            <br>
                                            <small class="text-muted"><?php echo $unidad_medida; ?></small>
                                        <?php endif; ?>
                                        <?php if ($es_precio_fijo): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-lock"></i> Fijo
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="precio-usd">
                                        <strong>$<?php echo number_format($precio_usd, 2); ?></strong><?php echo $precio_label; ?>
                                        <?php if ($precio_costo > 0): ?>
                                            <br>
                                            <small class="text-muted">
                                                Costo: $<?php echo number_format($precio_costo, 2); ?>
                                            </small>
                                            <br>
                                            <span class="badge <?php echo $claseMargen; ?>" title="<?php echo $margenData['mensaje']; ?>">
                                                <?php echo number_format($porcentajeMargen, 1); ?>%
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="precio-bs">
                                        <strong>Bs <?php echo number_format($precio_bs, 2); ?></strong><?php echo $precio_label; ?>
                                        <?php if ($precio_costo > 0): ?>
                                            <br>
                                            <small class="text-muted">
                                                Costo: Bs <?php echo number_format($precio_costo * $tasa_cambio, 2); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $stock_actual = floatval($producto['stock_actual'] ?? 0);
                                        $stock_minimo = floatval($producto['stock_minimo'] ?? 5);

                                        if ($tipo_venta === 'peso') {
                                            $stock_texto = number_format($stock_actual, 2) . ' ' . $unidad_medida;
                                            $minimo_texto = number_format($stock_minimo, 2) . ' ' . $unidad_medida;
                                        } else {
                                            $stock_texto = intval($stock_actual) . ' und';
                                            $minimo_texto = intval($stock_minimo) . ' und';
                                        }

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
                                            <?php echo $stock_texto; ?>
                                        </span>
                                        / <?php echo $minimo_texto; ?>
                                        <?php if ($stock_actual <= $stock_minimo && $stock_actual > 0): ?>
                                            <br>
                                            <small class="text-warning">
                                                <i class="fas fa-exclamation-triangle"></i> Stock bajo
                                            </small>
                                        <?php elseif ($stock_actual == 0): ?>
                                            <br>
                                            <small class="text-danger">
                                                <i class="fas fa-times-circle"></i> Sin stock
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($producto['activo']) && $producto['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-eye-slash"></i> No visible
                                            </small>
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
                                            <?php if ($tipo_venta === 'peso'): ?>
                                                <a href="../ventas/venta-rapida-por-peso.php?producto=<?php echo $producto['id']; ?>"
                                                    class="btn btn-outline-success"
                                                    title="Vender por peso"
                                                    data-bs-toggle="tooltip">
                                                    <i class="fas fa-weight"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button type="button"
                                                class="btn btn-outline-danger btn-eliminar"
                                                title="Eliminar producto"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEliminar"
                                                data-id="<?php echo $producto['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($producto['nombre'] ?? 'Producto'); ?>"
                                                data-sku="<?php echo htmlspecialchars($producto['codigo_sku'] ?? ''); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <a href="../historial-stock/index.php?producto_id=<?php echo $producto['id']; ?>"
                                                class="btn btn-outline-info"
                                                title="Historial de stock"
                                                data-bs-toggle="tooltip">
                                                <i class="fas fa-history"></i>
                                            </a>
                                            <?php if ($es_precio_fijo): ?>
                                                <span class="btn btn-outline-warning disabled" title="Precio fijo en Bs">
                                                    <i class="fas fa-lock"></i>
                                                </span>
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
                <p>¿Estás seguro de que deseas eliminar el siguiente producto?</p>
                <div class="alert alert-warning">
                    <div class="mb-2">
                        <strong>Nombre:</strong> <span id="nombreProducto"></span>
                    </div>
                    <div class="mb-2">
                        <strong>SKU:</strong> <code id="skuProducto"></code>
                    </div>
                    <div>
                        <i class="fas fa-exclamation-circle me-1"></i>
                        <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                    </div>
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
        const table = $('#tablaProductos').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 10,
            order: [
                [1, 'asc']
            ],
            columnDefs: [{
                    orderable: false,
                    targets: [8]
                }, // Deshabilitar ordenación en columna acciones
                {
                    searchable: false,
                    targets: [0, 2, 3, 4, 5, 6, 7, 8]
                }
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
            const sku = button.getAttribute('data-sku');

            document.getElementById('nombreProducto').textContent = nombre;
            document.getElementById('skuProducto').textContent = sku;
            document.getElementById('btnEliminarConfirmar').href = `index.php?eliminar=${id}`;
        });

        // Filtros en tiempo real
        $('#search').on('keyup', function() {
            table.search(this.value).draw();
        });

        $('#categoria').on('change', function() {
            const categoriaId = $(this).val();
            if (categoriaId === '') {
                table.columns(2).search('').draw();
            } else {
                table.columns(2).search(categoriaId).draw();
            }
        });

        $('#tipo_venta').on('change', function() {
            const valor = $(this).val();

            if (valor === '') {
                // Mostrar todos - eliminar filtros
                $.fn.dataTable.ext.search = [];
            } else {
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        const tipoText = $(data[3]).text();
                        return tipoText.includes(valor === 'peso' ? 'Por Peso' : 'Por Unidad');
                    }
                );
            }

            table.draw();
            if (valor !== '') {
                $.fn.dataTable.ext.search.pop();
            }
        });

        $('#stock').on('change', function() {
            const valor = $(this).val();

            $.fn.dataTable.ext.search = [];

            if (valor === 'bajo') {
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        const stockCell = $(data[6]);
                        const stockText = stockCell.find('.badge').text().trim();
                        const stockNumero = parseFloat(stockText.match(/[\d.]+/)[0]);
                        const minimoText = stockCell.text().split('/')[1].trim();
                        const minimoNumero = parseFloat(minimoText.match(/[\d.]+/)[0]);

                        return stockNumero > 0 && stockNumero <= minimoNumero;
                    }
                );
            } else if (valor === 'sin') {
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        const stockCell = $(data[6]);
                        const stockText = stockCell.find('.badge').text().trim();
                        const stockNumero = parseFloat(stockText.match(/[\d.]+/)[0]);
                        return stockNumero === 0;
                    }
                );
            } else if (valor === 'normal') {
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        const stockCell = $(data[6]);
                        const stockText = stockCell.find('.badge').text().trim();
                        const stockNumero = parseFloat(stockText.match(/[\d.]+/)[0]);
                        const minimoText = stockCell.text().split('/')[1].trim();
                        const minimoNumero = parseFloat(minimoText.match(/[\d.]+/)[0]);

                        return stockNumero > minimoNumero;
                    }
                );
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

    function exportarExcel() {
        const table = $('#tablaProductos').DataTable();
        const data = table.rows({
            search: 'applied'
        }).data();

        let csvContent = "SKU,Nombre,Categoría,Tipo,Precio USD,Precio Bs,Stock,Estado\n";

        data.each(function(value) {
            const row = [
                $(value[0]).text().trim(),
                $(value[1]).find('strong').text().trim(),
                $(value[2]).text().trim(),
                $(value[3]).find('.badge').text().trim(),
                $(value[4]).find('strong').text().trim().replace('$', ''),
                $(value[5]).find('strong').text().trim().replace('Bs', '').trim(),
                $(value[6]).find('.badge').text().trim(),
                $(value[7]).text().trim()
            ].map(cell => `"${cell}"`).join(',');

            csvContent += row + '\n';
        });

        const blob = new Blob([csvContent], {
            type: 'text/csv;charset=utf-8;'
        });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "productos_" + new Date().toISOString().slice(0, 10) + ".csv");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function imprimirLista() {
        window.print();
    }
</script>

<style>
    .card-stat {
        transition: transform 0.3s;
    }

    .card-stat:hover {
        transform: translateY(-5px);
        cursor: pointer;
    }

    .table th {
        background-color: #2c3e50;
        color: white;
    }

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .badge {
        font-size: 0.75em;
    }

    .precio-usd,
    .precio-bs {
        white-space: nowrap;
    }

    @media print {

        .btn,
        .btn-group,
        .card-header .btn-group,
        .alert,
        #search,
        #categoria,
        #tipo_venta,
        #stock,
        label[for="search"],
        label[for="categoria"],
        label[for="tipo_venta"],
        label[for="stock"] {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .table-responsive {
            overflow: visible !important;
        }
    }
</style>

<!-- <?php require_once '../layouts/footer.php'; ?> -->