<?php
// Views/historial-stock/index.php

// 1. INCLUIR CONTROLADORES Y CONFIGURACIÓN
require_once '../../Controllers/HistorialStock.php';
require_once '../../Controllers/ProductoController.php';
require_once '../../Config/Database.php';

require_once __DIR__ . '/../../Utils/Auth.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Auth::requireAccessToHistorialStock();

$database = new Database();
$db = $database->getConnection();
$historialController = new HistorialStockController($db);
$productoController = new ProductoController($db);

// 2. OBTENER PARÁMETROS Y DATOS
$mensaje = '';
$tipoMensaje = '';
$producto_id = $_GET['producto_id'] ?? null;
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Inicializar variables
$movimientos = [];
$producto_info = null;
$stats_data = [];

// Obtener datos según los parámetros
if ($producto_id) {
    $resultado = $historialController->obtenerPorProducto($producto_id);
    if ($resultado['success']) {
        $movimientos = $resultado['data'];
        $producto_info = $resultado['producto'] ?? null;
    } else {
        $mensaje = $resultado['message'];
        $tipoMensaje = 'danger';
    }
} else if ($search) {
    $resultado = $historialController->buscar($search);
    if ($resultado['success']) {
        $movimientos = $resultado['data'];
    } else {
        $mensaje = $resultado['message'];
        $tipoMensaje = 'danger';
    }
} else {
    $resultado = $historialController->listar();
    if ($resultado['success']) {
        $movimientos = $resultado['data'];
    } else {
        $mensaje = $resultado['message'];
        $tipoMensaje = 'danger';
    }
}

// Obtener estadísticas
$estadisticas = $historialController->obtenerEstadisticas();
if ($estadisticas['success']) {
    $stats_data = $estadisticas['data'];
}

// Obtener productos para el filtro
$productos_result = $productoController->obtenerProductosActivos();
$productos = $productos_result['success'] ? $productos_result['data'] : [];

// 3. DEFINIR TÍTULO Y INCLUIR HEADER
$page_title = $producto_id && $producto_info ?
    "Historial de Stock - " . ($producto_info['nombre'] ?? '') :
    "Historial de Stock";

require_once '../layouts/header.php';
?>

<!-- 4. CONTENIDO PRINCIPAL DE LA VISTA -->
<div class="content-wrapper historial-stock-content">

    <!-- Header de la página -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">
                <i class="fas fa-history me-2"></i>
                <?php echo $producto_id ? 'Historial de Stock' : 'Historial General de Stock'; ?>
            </h1>
            <?php if ($producto_id && $producto_info): ?>
                <p class="text-muted mb-0">
                    Producto: <strong><?php echo htmlspecialchars($producto_info['nombre']); ?></strong>
                    (SKU: <?php echo htmlspecialchars($producto_info['codigo_sku']); ?>)
                    - Stock actual: <span class="badge bg-<?php echo $producto_info['stock_actual'] <= $producto_info['stock_minimo'] ? 'warning' : 'success'; ?>">
                        <?php echo $producto_info['stock_actual']; ?>
                    </span>
                </p>
            <?php else: ?>
                <p class="text-muted mb-0">Registro completo de todos los movimientos de stock</p>
            <?php endif; ?>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?php if ($producto_id): ?>
                <a href="../productos/editar.php?id=<?php echo $producto_id; ?>" class="btn btn-outline-primary me-2">
                    <i class="fas fa-edit me-1"></i>Editar Producto
                </a>
            <?php endif; ?>
            <a href="../productos/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Volver a Productos
            </a>
        </div>
    </div>

    <!-- Alertas del sistema -->
    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filtros y Búsqueda -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2"></i>Filtros y Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <?php if (!$producto_id): ?>
                    <div class="col-md-4">
                        <label for="producto_id" class="form-label">Filtrar por Producto</label>
                        <select class="form-select" id="producto_id" name="producto_id">
                            <option value="">Todos los productos</option>
                            <?php foreach ($productos as $prod): ?>
                                <option value="<?php echo $prod['id']; ?>"
                                    <?php echo ($producto_id == $prod['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prod['nombre']); ?> (<?php echo htmlspecialchars($prod['codigo_sku']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
                <?php endif; ?>

                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                        value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                </div>

                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                        value="<?php echo htmlspecialchars($fecha_fin); ?>">
                </div>

                <div class="col-md-2">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="search" name="search"
                        value="<?php echo htmlspecialchars($search); ?>" placeholder="Término...">
                </div>

                <div class="col-md-12 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Aplicar Filtros
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <?php if (!empty($stats_data)): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Total Movimientos</h5>
                                <h3><?php echo number_format($stats_data['total_movimientos'] ?? 0); ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-exchange-alt fa-2x"></i>
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
                                <h5 class="card-title">Movimientos Mes</h5>
                                <h3><?php echo number_format($stats_data['movimientos_mes'] ?? 0); ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calendar-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Productos con Movimientos</h5>
                                <h3><?php echo count($stats_data['top_productos'] ?? []); ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-boxes fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="card-title">Movimientos Mostrados</h5>
                                <h3><?php echo count($movimientos); ?></h3>
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

    <!-- Tabla de Movimientos -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Movimientos de Stock
                <span class="badge bg-secondary"><?php echo count($movimientos); ?></span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($movimientos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay movimientos de stock</h4>
                    <p class="text-muted">No se encontraron movimientos con los filtros aplicados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaHistorialStock">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <?php if (!$producto_id): ?>
                                    <th>Producto</th>
                                <?php endif; ?>
                                <th>Fecha/Hora</th>
                                <th>Stock Anterior</th>
                                <th>Stock Nuevo</th>
                                <th>Diferencia</th>
                                <th>Tipo Movimiento</th>
                                <th>Referencia</th>
                                <th>Observaciones</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $index => $movimiento): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <?php if (!$producto_id): ?>
                                        <td>
                                            <strong><?php echo htmlspecialchars($movimiento['producto_nombre'] ?? 'N/A'); ?></strong>
                                            <br>
                                            <small class="text-muted">SKU: <?php echo htmlspecialchars($movimiento['codigo_sku'] ?? 'N/A'); ?></small>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <?php
                                        $fecha_hora = $movimiento['fecha_hora'] ?? $movimiento['created_at'] ?? '';
                                        if ($fecha_hora) {
                                            echo date('d/m/Y H:i', strtotime($fecha_hora));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $movimiento['cantidad_anterior'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $movimiento['cantidad_nueva'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $diferencia = $movimiento['diferencia'] ?? 0;
                                        if ($diferencia > 0): ?>
                                            <span class="badge bg-success">+<?php echo $diferencia; ?></span>
                                        <?php elseif ($diferencia < 0): ?>
                                            <span class="badge bg-danger"><?php echo $diferencia; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $tipo_movimiento = $movimiento['tipo_movimiento'] ?? 'sin_cambio';
                                        $tipo_badge = [
                                            'entrada' => 'success',
                                            'salida' => 'danger',
                                            'sin_cambio' => 'secondary',
                                            'ajuste' => 'warning',
                                            'venta' => 'info',
                                            'compra' => 'primary'
                                        ];
                                        $badge_class = $tipo_badge[$tipo_movimiento] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $tipo_movimiento)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php
                                            if (!empty($movimiento['tipo_referencia'])) {
                                                echo ucfirst(str_replace('_', ' ', $movimiento['tipo_referencia']));
                                                if (!empty($movimiento['referencia_id'])) {
                                                    echo ' #' . $movimiento['referencia_id'];
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $observaciones = $movimiento['observaciones'] ?? '';
                                        if (!empty($observaciones)) {
                                            if (strlen($observaciones) > 50) {
                                                echo '<span data-bs-toggle="tooltip" title="' . htmlspecialchars($observaciones) . '">';
                                                echo htmlspecialchars(substr($observaciones, 0, 50)) . '...';
                                                echo '</span>';
                                            } else {
                                                echo htmlspecialchars($observaciones);
                                            }
                                        } else {
                                            echo '<span class="text-muted">Sin observaciones</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php
                                            if (!empty($movimiento['usuario_nombre'])) {
                                                echo htmlspecialchars($movimiento['usuario_nombre']);
                                            } else if (!empty($movimiento['usuario_id'])) {
                                                echo 'Usuario #' . $movimiento['usuario_id'];
                                            } else {
                                                echo 'Sistema';
                                            }
                                            ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información Adicional -->
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h6 class="card-title mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Información sobre Movimientos de Stock
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">
                        <i class="fas fa-lightbulb me-2"></i>Tipos de Movimiento:
                    </h6>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-success me-2">Entrada</span> Incremento en el stock</li>
                        <li><span class="badge bg-danger me-2">Salida</span> Disminución en el stock</li>
                        <li><span class="badge bg-warning me-2">Ajuste</span> Corrección manual</li>
                        <li><span class="badge bg-info me-2">Venta</span> Registrada por sistema de ventas</li>
                        <li><span class="badge bg-primary me-2">Compra</span> Registrada por sistema de compras</li>
                        <li><span class="badge bg-secondary me-2">Sin Cambio</span> Stock sin modificación</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Consideraciones:
                    </h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-database me-2 text-info"></i> El historial se genera automáticamente</li>
                        <li><i class="fas fa-user me-2 text-info"></i> Los ajustes manuales requieren autorización</li>
                        <li><i class="fas fa-clock me-2 text-info"></i> Los registros son permanentes</li>
                        <li><i class="fas fa-chart-bar me-2 text-info"></i> Use los filtros para análisis específicos</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. SCRIPTS ESPECÍFICOS -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar DataTables
        if (document.querySelector('#tablaHistorialStock')) {
            $('#tablaHistorialStock').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                pageLength: 25,
                order: [
                    [2, 'desc']
                ], // Ordenar por fecha descendente
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                initComplete: function() {
                    $('.dataTables_filter input').addClass('form-control form-control-sm');
                    $('.dataTables_length select').addClass('form-control form-control-sm');
                }
            });

            // Inicializar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Validación de fechas
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');

        if (fechaInicio && fechaFin) {
            fechaInicio.addEventListener('change', function() {
                if (this.value && fechaFin.value && this.value > fechaFin.value) {
                    fechaFin.value = this.value;
                }
            });

            fechaFin.addEventListener('change', function() {
                if (this.value && fechaInicio.value && this.value < fechaInicio.value) {
                    this.value = fechaInicio.value;
                }
            });
        }
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->