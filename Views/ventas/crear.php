<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/VentaController.php';
require_once '../../Controllers/ClienteController.php';
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/TipoPagoController.php';
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Helpers/TasaCambioHelper.php';

$database = new Database();
$db = $database->getConnection();

$ventaController = new VentaController($db);
$clienteController = new ClienteController($db);
$productoController = new ProductoController($db);
$tipoPagoController = new TipoPagoController($db);
$tasaController = new TasaCambioController($db);

$error_message = '';
$success_message = '';

// Obtener datos para formulario
$clientes = $clienteController->obtenerClientesActivos();
$productos = $ventaController->obtenerProductosConInfoCompleta(); // Usar el nuevo método
$tiposPago = $tipoPagoController->listar();
$tasaActual = $tasaController->obtenerTasaActual();

// Verificar y asignar datos correctamente
$clientes_data = $clientes['success'] ? $clientes['data'] : [];
$productos_data = $productos['success'] ? $productos['data'] : [];
$tiposPago_data = $tiposPago['success'] ? $tiposPago['data'] : [];
$tasa_info = $tasaActual['success'] ? $tasaActual['data'] : null;

// Procesar formulario de venta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_id'])) {
    try {
        // Preparar datos de la venta
        $datosVenta = [
            'cliente_id' => $_POST['cliente_id'],
            'tipo_pago_id' => $_POST['tipo_pago_id'],
            'observaciones' => $_POST['observaciones'] ?? '',
            'estado' => 'pendiente',
            'detalles' => []
        ];

        // Procesar detalles de la venta
        if (isset($_POST['productos']) && is_array($_POST['productos'])) {
            $total_venta = 0;

            foreach ($_POST['productos'] as $index => $producto_id) {
                if (!empty($producto_id) && !empty($_POST['cantidades'][$index]) && !empty($_POST['precios'][$index])) {
                    $cantidad = intval($_POST['cantidades'][$index]);
                    $precio = floatval($_POST['precios'][$index]);
                    
                    $datosVenta['detalles'][] = [
                        'producto_id' => $producto_id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio
                    ];
                }
            }
        }

        if (empty($datosVenta['detalles'])) {
            throw new Exception("Debe agregar al menos un producto a la venta");
        }

        $result = $ventaController->crear($datosVenta);

        if ($result['success']) {
            $success_message = $result['message'];
            // Mostrar resumen de la venta
            $success_message .= "<br><br><strong>Resumen de la Venta:</strong>";
            $success_message .= "<br>Número: " . $result['numero_venta'];
            $success_message .= "<br>Tasa de Cambio: " . number_format($result['tasa_cambio'], 2) . " Bs/USD";
            $success_message .= "<br>Total USD: " . TasaCambioHelper::formatearUSD($result['total_usd']);
            $success_message .= "<br>Total Bs: " . TasaCambioHelper::formatearBS($result['total_bs']);
            
            // Mostrar detalles
            if (isset($result['detalles_procesados'])) {
                $success_message .= "<br><br><strong>Detalles:</strong>";
                foreach ($result['detalles_procesados'] as $detalle) {
                    $tipo_precio = $detalle['es_precio_fijo'] ? " (Precio Fijo BS)" : " (Conversión automática)";
                    $success_message .= "<br>• " . $detalle['producto_nombre'] . 
                                    " - Cant: " . $detalle['cantidad'] . 
                                    " - Precio: $" . number_format($detalle['precio_unitario'], 2) . 
                                    " / Bs " . number_format($detalle['precio_unitario_bs'], 2) . $tipo_precio;
                }
            }
            
            // Redirigir después de 5 segundos
            header("Refresh: 5; URL=index.php");
        } else {
            $error_message = $result['message'];
        }
    } catch (Exception $e) {
        $error_message = "Error inesperado: " . $e->getMessage();
    }
}

// Procesar creación de nuevo cliente desde AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_cliente') {
    header('Content-Type: application/json');

    try {
        $datosCliente = [
            'nombre' => $_POST['nombre'],
            'email' => $_POST['email'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'direccion' => $_POST['direccion'] ?? '',
            'numero_documento' => $_POST['numero_documento'] ?? ''
        ];

        $result = $clienteController->crear($datosCliente);

        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error al crear cliente: " . $e->getMessage()
        ]);
    }
    exit;
}

// Nuevo endpoint para buscar productos
if (isset($_GET['action']) && $_GET['action'] === 'buscar_productos') {
    header('Content-Type: application/json');
    
    $searchTerm = $_GET['q'] ?? '';
    $categoria = $_GET['categoria'] ?? '';
    
    $resultado = $productoController->buscarProductosAvanzado($searchTerm, $categoria);
    
    echo json_encode($resultado);
    exit;
}

// Obtener categorías únicas para el filtro
$categorias = [];
if (!empty($productos_data)) {
    foreach ($productos_data as $producto) {
        if (!empty($producto['categoria_nombre']) && !in_array($producto['categoria_nombre'], $categorias)) {
            $categorias[] = $producto['categoria_nombre'];
        }
    }
    sort($categorias);
}

$page_title = "Crear Nueva Venta - Sistema Avanzado";
include '../layouts/header.php';
?>

<!-- Header con Botón de Volver -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-cash-register me-2"></i>
            Crear Nueva Venta
        </h1>
        <p class="text-muted mb-0">Sistema avanzado de ventas - Búsqueda inteligente de productos</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Listado
        </a>
    </div>
</div>

<!-- Tasa de Cambio Actual -->
<?php if ($tasa_info): ?>
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h6 class="mb-1"><i class="fas fa-exchange-alt me-2"></i>Tasa de Cambio Actual</h6>
                <p class="mb-0">
                    <strong>1 USD = <?php echo number_format($tasa_info['tasa_cambio'], 2); ?> Bs</strong>
                    <small class="text-muted ms-3">
                        (Actualizada: <?php echo date('d/m/Y H:i', strtotime($tasa_info['fecha_actualizacion'])); ?>)
                    </small>
                </p>
            </div>
            <div>
                <a href="../tasas-cambio/actualizar.php" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-sync-alt me-1"></i> Actualizar Tasa
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Error:</strong> No hay tasa de cambio configurada.
        <a href="../tasas-cambio/actualizar.php" class="alert-link">Configure la tasa de cambio primero</a>
    </div>
<?php endif; ?>

<!-- Alertas -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success_message; ?>
        <div class="mt-2">
            <small>Serás redirigido automáticamente al listado de ventas...</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Formulario de Venta -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-shopping-cart me-2"></i>
            Información de la Venta
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" id="formVenta" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label">
                            <i class="fas fa-user me-1"></i>Cliente *
                        </label>
                        <div class="input-group">
                            <select class="form-control" id="cliente_id" name="cliente_id" required>
                                <option value="">Seleccionar cliente...</option>
                                <?php foreach ($clientes_data as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>"
                                        <?php echo ($_POST['cliente_id'] ?? '') == $cliente['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cliente['nombre']); ?>
                                        <?php if (!empty($cliente['numero_documento'])): ?>
                                            (<?php echo htmlspecialchars($cliente['numero_documento']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">
                                <i class="fas fa-plus"></i> Nuevo
                            </button>
                        </div>
                        <div class="form-text">Selecciona un cliente existente o crea uno nuevo.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tipo_pago_id" class="form-label">
                            <i class="fas fa-credit-card me-1"></i>Tipo de Pago *
                        </label>
                        <select class="form-control" id="tipo_pago_id" name="tipo_pago_id" required>
                            <option value="">Seleccionar tipo de pago...</option>
                            <?php foreach ($tiposPago_data as $tipoPago): ?>
                                <option value="<?php echo $tipoPago['id']; ?>"
                                    <?php echo ($_POST['tipo_pago_id'] ?? '') == $tipoPago['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipoPago['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Selecciona el método de pago.</div>
                    </div>
                </div>
            </div>

            <!-- Sistema Avanzado de Búsqueda de Productos -->
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="mb-3">
                        <i class="fas fa-boxes me-2"></i>
                        Productos de la Venta - Búsqueda Avanzada
                    </h5>

                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Buscar Producto</label>
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control" 
                                               id="buscar-producto" 
                                               placeholder="Escribe el nombre, SKU o descripción del producto..."
                                               autocomplete="off">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                    </div>
                                    <div class="form-text">
                                        Escribe para buscar productos. Puedes buscar por nombre, SKU o categoría.
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Filtrar por Categoría</label>
                                    <select class="form-control" id="filtro-categoria">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo htmlspecialchars($categoria); ?>">
                                                <?php echo htmlspecialchars($categoria); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-secondary w-100" onclick="limpiarBusqueda()">
                                        <i class="fas fa-times me-1"></i> Limpiar
                                    </button>
                                </div>
                            </div>

                            <!-- Resultados de búsqueda -->
                            <div id="resultados-busqueda" class="mt-3 d-none">
                                <h6 class="mb-2">Resultados de la búsqueda:</h6>
                                <div id="lista-productos" class="list-group" style="max-height: 300px; overflow-y: auto;">
                                    <!-- Los productos aparecerán aquí dinámicamente -->
                                </div>
                            </div>

                            <!-- Contador de resultados -->
                            <div id="contador-resultados" class="mt-2 text-muted small"></div>
                        </div>
                    </div>

                    <!-- Productos seleccionados -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-shopping-basket me-2"></i>
                                Productos Seleccionados
                                <span id="contador-productos" class="badge bg-primary ms-2">0</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="productos-container">
                                <!-- Los productos seleccionados aparecerán aquí dinámicamente -->
                                <div class="text-center text-muted py-4" id="mensaje-sin-productos">
                                    <i class="fas fa-box-open fa-2x mb-2"></i>
                                    <p>No hay productos agregados. Busca y selecciona productos para comenzar.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen de la Venta -->
            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">
                            <i class="fas fa-sticky-note me-1"></i>Observaciones
                        </label>
                        <textarea class="form-control"
                            id="observaciones"
                            name="observaciones"
                            rows="3"
                            placeholder="Observaciones adicionales sobre la venta..."><?php echo htmlspecialchars($_POST['observaciones'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Resumen de Venta</h6>
                            <div class="d-flex justify-content-between">
                                <span>Total USD:</span>
                                <span id="total-usd">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Tasa de Cambio:</span>
                                <span id="tasa-cambio"><?php echo $tasa_info ? number_format($tasa_info['tasa_cambio'], 2) : '0.00'; ?> Bs/$</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <strong>Total Bs:</strong>
                                <strong id="total-bs" class="text-success">Bs 0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Productos:</small>
                                <small id="cantidad-total-productos" class="text-muted">0</small>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Los productos con precio fijo mantienen su valor en Bs
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-success" id="btn-guardar-venta" disabled>
                        <i class="fas fa-save me-1"></i> Guardar Venta
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="button" class="btn btn-outline-info" onclick="limpiarFormulario()">
                        <i class="fas fa-broom me-1"></i> Limpiar Todo
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Nuevo Cliente -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>
                    Crear Nuevo Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevoCliente">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nuevo_nombre" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="nuevo_nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nuevo_documento" class="form-label">Documento de Identidad</label>
                                <input type="text" class="form-control" id="nuevo_documento" name="numero_documento">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nuevo_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="nuevo_email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nuevo_telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="nuevo_telefono" name="telefono">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="nuevo_direccion" class="form-label">Dirección</label>
                                <textarea class="form-control" id="nuevo_direccion" name="direccion" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
                <div id="cliente-message" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="crearCliente()">
                    <i class="fas fa-save me-1"></i> Guardar Cliente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Información Adicional -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Sistema Avanzado de Ventas - Instrucciones
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Búsqueda Avanzada:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-search text-primary me-2"></i> <strong>Búsqueda por texto:</strong> Escribe cualquier palabra del nombre, SKU o descripción</li>
                    <li><i class="fas fa-filter text-info me-2"></i> <strong>Filtro por categoría:</strong> Selecciona una categoría para ver solo esos productos</li>
                    <li><i class="fas fa-bolt text-warning me-2"></i> <strong>Búsqueda en tiempo real:</strong> Los resultados se actualizan automáticamente</li>
                    <li><i class="fas fa-mouse-pointer text-success me-2"></i> <strong>Selección rápida:</strong> Haz clic en un producto para agregarlo</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-success">Gestión de Productos:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-plus-circle text-success me-2"></i> <strong>Agregar rápido:</strong> Click para agregar productos inmediatamente</li>
                    <li><i class="fas fa-sort-amount-up text-info me-2"></i> <strong>Cambiar cantidad:</strong> Usa las flechas o escribe directamente</li>
                    <li><i class="fas fa-trash text-danger me-2"></i> <strong>Eliminar fácil:</strong> Click en la X para quitar un producto</li>
                    <li><i class="fas fa-eye text-warning me-2"></i> <strong>Visualización clara:</strong> Stock, precio y subtotal visibles</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
    .producto-busqueda {
        cursor: pointer;
        transition: all 0.2s;
        border-left: 4px solid transparent;
    }
    
    .producto-busqueda:hover {
        background-color: #f8f9fa;
        border-left-color: #0d6efd;
        transform: translateX(2px);
    }
    
    .producto-busqueda.seleccionado {
        background-color: #e7f1ff;
        border-left-color: #0d6efd;
    }
    
    .producto-seleccionado {
        background-color: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 10px;
        transition: all 0.3s;
        border-left: 4px solid #0d6efd;
    }
    
    .producto-seleccionado:hover {
        background-color: #e9ecef;
    }
    
    .badge-stock {
        font-size: 0.7em;
        padding: 2px 6px;
    }
    
    .precio-fijo-badge {
        background-color: #198754;
    }
    
    .stock-alto {
        background-color: #198754;
    }
    
    .stock-medio {
        background-color: #ffc107;
        color: #000;
    }
    
    .stock-bajo {
        background-color: #dc3545;
    }
    
    #resultados-busqueda {
        border-top: 1px solid #dee2e6;
        padding-top: 15px;
    }
    
    .producto-info {
        font-size: 0.85em;
        color: #6c757d;
    }
    
    .cantidad-input-container {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-cantidad {
        padding: 2px 8px;
        font-size: 0.8em;
    }
</style>

<script>
    let tasaCambio = <?php echo $tasa_info ? $tasa_info['tasa_cambio'] : 0; ?>;
    let productosData = <?php echo json_encode($productos_data); ?>;
    let productosSeleccionados = [];

    document.addEventListener('DOMContentLoaded', function() {
        inicializarBusqueda();
        actualizarContadores();
        
        <?php if (empty($productos_data)): ?>
            showToast('warning', 'No hay productos disponibles. Debes crear productos primero.');
        <?php endif; ?>
        
        <?php if (!$tasa_info): ?>
            showToast('error', 'No hay tasa de cambio configurada. Configure la tasa primero.');
        <?php endif; ?>
    });

    // Sistema de búsqueda avanzada
    function inicializarBusqueda() {
        const buscarInput = document.getElementById('buscar-producto');
        const filtroCategoria = document.getElementById('filtro-categoria');
        
        // Búsqueda en tiempo real
        buscarInput.addEventListener('input', function() {
            buscarProductos();
        });
        
        // Filtro por categoría
        filtroCategoria.addEventListener('change', function() {
            buscarProductos();
        });
        
        // Prevenir envío del formulario al presionar Enter en la búsqueda
        buscarInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarProductos();
            }
        });
    }

    function buscarProductos() {
        const termino = document.getElementById('buscar-producto').value.trim();
        const categoria = document.getElementById('filtro-categoria').value;
        
        // Si no hay término y no hay categoría, ocultar resultados
        if (!termino && !categoria) {
            document.getElementById('resultados-busqueda').classList.add('d-none');
            document.getElementById('contador-resultados').textContent = '';
            return;
        }
        
        // Mostrar loading
        const resultadosDiv = document.getElementById('resultados-busqueda');
        const listaProductos = document.getElementById('lista-productos');
        
        resultadosDiv.classList.remove('d-none');
        listaProductos.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Buscando productos...
            </div>
        `;
        
        // Hacer búsqueda en el servidor
        const url = `crear.php?action=buscar_productos&q=${encodeURIComponent(termino)}&categoria=${encodeURIComponent(categoria)}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                mostrarResultadosBusqueda(data);
            })
            .catch(error => {
                console.error('Error en búsqueda:', error);
                listaProductos.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al buscar productos
                    </div>
                `;
            });
    }

    function mostrarResultadosBusqueda(data) {
        const listaProductos = document.getElementById('lista-productos');
        const contador = document.getElementById('contador-resultados');
        
        if (!data.success || data.data.length === 0) {
            listaProductos.innerHTML = `
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-search me-2"></i>
                    No se encontraron productos que coincidan con la búsqueda.
                </div>
            `;
            contador.textContent = '0 productos encontrados';
            return;
        }
        
        let html = '';
        let contadorProductos = 0;
        
        data.data.forEach(producto => {
            // Verificar si el producto ya está seleccionado
            const yaSeleccionado = productosSeleccionados.some(p => p.id === producto.id);
            
            // Determinar clase de stock
            let stockClase = 'stock-alto';
            if (producto.stock_actual <= 10) stockClase = 'stock-medio';
            if (producto.stock_actual <= 3) stockClase = 'stock-bajo';
            
            html += `
                <div class="list-group-item producto-busqueda ${yaSeleccionado ? 'seleccionado' : ''}" 
                     data-producto-id="${producto.id}"
                     onclick="${!yaSeleccionado ? `seleccionarProducto(${JSON.stringify(producto).replace(/"/g, '&quot;')})` : ''}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-1">
                                <strong>${producto.nombre}</strong>
                                ${producto.usar_precio_fijo_bs ? 
                                    '<span class="badge precio-fijo-badge ms-2">Precio Fijo</span>' : ''}
                                <span class="badge ${stockClase} badge-stock ms-2">
                                    Stock: ${producto.stock_actual}
                                </span>
                            </div>
                            <div class="producto-info">
                                ${producto.codigo_sku ? `<span class="me-3"><i class="fas fa-barcode"></i> ${producto.codigo_sku}</span>` : ''}
                                ${producto.categoria_nombre ? `<span class="me-3"><i class="fas fa-tag"></i> ${producto.categoria_nombre}</span>` : ''}
                                <span><i class="fas fa-dollar-sign"></i> ${parseFloat(producto.precio).toFixed(2)} USD</span>
                                ${producto.usar_precio_fijo_bs ? 
                                    `<span class="ms-3"><i class="fas fa-bolt"></i> ${parseFloat(producto.precio_bs).toFixed(2)} Bs (Fijo)</span>` :
                                    `<span class="ms-3"><i class="fas fa-calculator"></i> ${(parseFloat(producto.precio) * tasaCambio).toFixed(2)} Bs</span>`}
                            </div>
                        </div>
                        <div>
                            ${yaSeleccionado ? 
                                '<span class="badge bg-success"><i class="fas fa-check"></i> Agregado</span>' : 
                                '<button class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Agregar</button>'}
                        </div>
                    </div>
                </div>
            `;
            contadorProductos++;
        });
        
        listaProductos.innerHTML = html;
        contador.textContent = `${contadorProductos} producto${contadorProductos !== 1 ? 's' : ''} encontrado${contadorProductos !== 1 ? 's' : ''}`;
    }

    function seleccionarProducto(producto) {
        // Verificar si ya está seleccionado
        if (productosSeleccionados.some(p => p.id === producto.id)) {
            showToast('info', 'Este producto ya está en la lista');
            return;
        }
        
        // Verificar stock
        if (producto.stock_actual <= 0) {
            showToast('error', 'Producto sin stock disponible');
            return;
        }
        
        // Agregar producto a la lista
        productosSeleccionados.push({
            id: producto.id,
            nombre: producto.nombre,
            precio: parseFloat(producto.precio),
            precio_bs: parseFloat(producto.precio_bs),
            usar_precio_fijo_bs: producto.usar_precio_fijo_bs,
            stock_actual: producto.stock_actual,
            cantidad: 1,
            sku: producto.codigo_sku || '',
            categoria: producto.categoria_nombre || ''
        });
        
        // Actualizar interfaz
        actualizarListaProductos();
        actualizarContadores();
        calcularTotal();
        
        // Mostrar mensaje y actualizar búsqueda
        showToast('success', 'Producto agregado correctamente');
        buscarProductos(); // Para actualizar el estado en la búsqueda
    }

    function actualizarListaProductos() {
        const container = document.getElementById('productos-container');
        const mensajeSinProductos = document.getElementById('mensaje-sin-productos');
        
        if (productosSeleccionados.length === 0) {
            if (!mensajeSinProductos) {
                container.innerHTML = `
                    <div class="text-center text-muted py-4" id="mensaje-sin-productos">
                        <i class="fas fa-box-open fa-2x mb-2"></i>
                        <p>No hay productos agregados. Busca y selecciona productos para comenzar.</p>
                    </div>
                `;
            } else {
                mensajeSinProductos.classList.remove('d-none');
            }
            return;
        }
        
        // Ocultar mensaje si existe
        if (mensajeSinProductos) {
            mensajeSinProductos.classList.add('d-none');
        }
        
        let html = '';
        
        productosSeleccionados.forEach((producto, index) => {
            const subtotalUSD = producto.cantidad * producto.precio;
            let subtotalBS = 0;
            
            if (producto.usar_precio_fijo_bs) {
                subtotalBS = producto.cantidad * producto.precio_bs;
            } else {
                subtotalBS = subtotalUSD * tasaCambio;
            }
            
            html += `
                <div class="producto-seleccionado p-3" data-index="${index}">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <strong>${producto.nombre}</strong>
                            <div class="producto-info">
                                ${producto.sku ? `<div><i class="fas fa-barcode"></i> ${producto.sku}</div>` : ''}
                                ${producto.categoria ? `<div><i class="fas fa-tag"></i> ${producto.categoria}</div>` : ''}
                                <div>
                                    <i class="fas fa-box"></i> Stock: ${producto.stock_actual} |
                                    <i class="fas fa-dollar-sign"></i> ${producto.precio.toFixed(2)} USD
                                    ${producto.usar_precio_fijo_bs ? 
                                        `| <i class="fas fa-lock"></i> ${producto.precio_bs.toFixed(2)} Bs (Fijo)` : 
                                        `| <i class="fas fa-calculator"></i> ${(producto.precio * tasaCambio).toFixed(2)} Bs`}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="cantidad-input-container">
                                <button class="btn btn-sm btn-outline-secondary btn-cantidad" onclick="cambiarCantidad(${index}, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       class="form-control form-control-sm text-center" 
                                       value="${producto.cantidad}" 
                                       min="1" 
                                       max="${producto.stock_actual}"
                                       onchange="actualizarCantidad(${index}, this.value)"
                                       data-producto-index="${index}">
                                <button class="btn btn-sm btn-outline-secondary btn-cantidad" onclick="cambiarCantidad(${index}, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <small class="ms-2 text-muted">max ${producto.stock_actual}</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-end">
                                <div><strong>Subtotal:</strong></div>
                                <div>$${subtotalUSD.toFixed(2)} USD</div>
                                <div><small>Bs ${subtotalBS.toFixed(2)}</small></div>
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <button class="btn btn-sm btn-danger" onclick="eliminarProductoSeleccionado(${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Campos ocultos para el formulario -->
                    <input type="hidden" name="productos[]" value="${producto.id}">
                    <input type="hidden" name="cantidades[]" value="${producto.cantidad}">
                    <input type="hidden" name="precios[]" value="${producto.precio}">
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    function actualizarCantidad(index, nuevaCantidad) {
        nuevaCantidad = parseInt(nuevaCantidad);
        
        if (isNaN(nuevaCantidad) || nuevaCantidad < 1) {
            nuevaCantidad = 1;
        }
        
        // Verificar stock máximo
        const stockMaximo = productosSeleccionados[index].stock_actual;
        if (nuevaCantidad > stockMaximo) {
            nuevaCantidad = stockMaximo;
            showToast('warning', 'No hay suficiente stock disponible');
        }
        
        productosSeleccionados[index].cantidad = nuevaCantidad;
        actualizarListaProductos();
        calcularTotal();
    }

    function cambiarCantidad(index, cambio) {
        const nuevaCantidad = productosSeleccionados[index].cantidad + cambio;
        actualizarCantidad(index, nuevaCantidad);
    }

    function eliminarProductoSeleccionado(index) {
        productosSeleccionados.splice(index, 1);
        actualizarListaProductos();
        actualizarContadores();
        calcularTotal();
        buscarProductos(); // Actualizar búsqueda para quitar "agregado"
        showToast('info', 'Producto eliminado de la lista');
    }

    function actualizarContadores() {
        const contadorProductos = document.getElementById('contador-productos');
        const cantidadTotalProductos = document.getElementById('cantidad-total-productos');
        const btnGuardar = document.getElementById('btn-guardar-venta');
        
        // Contar productos únicos
        contadorProductos.textContent = productosSeleccionados.length;
        
        // Contar cantidad total de productos
        const totalItems = productosSeleccionados.reduce((total, producto) => total + producto.cantidad, 0);
        cantidadTotalProductos.textContent = totalItems;
        
        // Habilitar/deshabilitar botón de guardar
        btnGuardar.disabled = productosSeleccionados.length === 0 || tasaCambio === 0;
    }

    function calcularTotal() {
        let subtotalUSD = 0;
        let subtotalBS = 0;

        productosSeleccionados.forEach(producto => {
            const subtotalItemUSD = producto.cantidad * producto.precio;
            let subtotalItemBS = 0;
            
            if (producto.usar_precio_fijo_bs) {
                subtotalItemBS = producto.cantidad * producto.precio_bs;
            } else {
                subtotalItemBS = subtotalItemUSD * tasaCambio;
            }

            subtotalUSD += subtotalItemUSD;
            subtotalBS += subtotalItemBS;
        });

        document.getElementById('total-usd').textContent = '$' + subtotalUSD.toFixed(2);
        document.getElementById('total-bs').textContent = 'Bs ' + subtotalBS.toFixed(2);
        
        actualizarContadores();
    }

    function limpiarBusqueda() {
        document.getElementById('buscar-producto').value = '';
        document.getElementById('filtro-categoria').value = '';
        document.getElementById('resultados-busqueda').classList.add('d-none');
        document.getElementById('contador-resultados').textContent = '';
    }

    // Funciones para gestión de clientes
    function crearCliente() {
        const form = document.getElementById('formNuevoCliente');
        const formData = new FormData(form);
        formData.append('action', 'crear_cliente');

        // Mostrar loading
        const submitBtn = document.querySelector('#modalNuevoCliente .btn-primary');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';
        submitBtn.disabled = true;

        fetch('crear.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('cliente-message').innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            ${data.message}
                        </div>
                    `;

                    actualizarSelectClientes(data.id, formData.get('nombre'), formData.get('numero_documento'));

                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoCliente'));
                        modal.hide();
                        form.reset();
                        document.getElementById('cliente-message').innerHTML = '';
                    }, 2000);
                } else {
                    document.getElementById('cliente-message').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                document.getElementById('cliente-message').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error de conexión: ${error}
                    </div>
                `;
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
    }

    function actualizarSelectClientes(clienteId, clienteNombre, documento) {
        const select = document.getElementById('cliente_id');
        const option = document.createElement('option');
        option.value = clienteId;
        option.text = clienteNombre + (documento ? ` (${documento})` : '');
        option.selected = true;

        select.appendChild(option);
    }

    function limpiarFormulario() {
        if (confirm('¿Estás seguro de que deseas limpiar todo el formulario? Se perderán todos los datos ingresados.')) {
            document.getElementById('formVenta').reset();
            productosSeleccionados = [];
            actualizarListaProductos();
            actualizarContadores();
            calcularTotal();
            limpiarBusqueda();
            showToast('info', 'Formulario limpiado correctamente.');
        }
    }

    function showToast(type, message) {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        const toast = document.createElement('div');

        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    // Limpiar mensajes del modal cuando se cierre
    document.getElementById('modalNuevoCliente').addEventListener('hidden.bs.modal', function() {
        document.getElementById('formNuevoCliente').reset();
        document.getElementById('cliente-message').innerHTML = '';
    });
</script>

<!-- Modal de confirmación rápida -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5 id="confirmacion-titulo">Producto Agregado</h5>
                <p id="confirmacion-mensaje">El producto ha sido agregado correctamente.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- <?php include '../layouts/footer.php'; ?> -->