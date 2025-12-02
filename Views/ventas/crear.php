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
if ($_POST && isset($_POST['cliente_id'])) {
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
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'crear_cliente') {
    header('Content-Type: application/json');

    try {
        $datosCliente = [
            'nombre' => $_POST['nombre'],
            'email' => $_POST['email'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'direccion' => $_POST['direccion'] ?? '',
            'documento_identidad' => $_POST['documento_identidad'] ?? ''
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
?>

<?php
$page_title = "Crear Nueva Venta";
include '../layouts/header.php';
?>

<!-- Header con Botón de Volver -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-cash-register me-2"></i>
            Crear Nueva Venta
        </h1>
        <p class="text-muted mb-0">Registra una nueva venta en el sistema</p>
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
                                        <?php if (!empty($cliente['documento_identidad'])): ?>
                                            (<?php echo htmlspecialchars($cliente['documento_identidad']); ?>)
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

                <!-- Detalles de la Venta -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="mb-3">
                            <i class="fas fa-boxes me-2"></i>
                            Productos de la Venta
                        </h5>

                        <div id="productos-container">
                            <!-- Fila de producto inicial -->
                            <div class="producto-row row mb-3">
                                <div class="col-md-5">
                                    <select class="form-control producto-select" name="productos[]" onchange="actualizarPrecio(this)">
                                        <option value="">Seleccionar producto...</option>
                                        <?php if (!empty($productos_data)): ?>
                                            <?php foreach ($productos_data as $producto): ?>
                                                <option value="<?php echo $producto['id']; ?>"
                                                    data-precio="<?php echo $producto['precio']; ?>"
                                                    data-precio-bs="<?php echo $producto['precio_bs']; ?>"
                                                    data-precio-fijo="<?php echo $producto['usar_precio_fijo_bs'] ? 'true' : 'false'; ?>"
                                                    data-stock="<?php echo $producto['stock_actual']; ?>">
                                                    <?php echo htmlspecialchars($producto['nombre']); ?>
                                                    <?php if (!empty($producto['codigo_sku'])): ?>
                                                        (SKU: <?php echo htmlspecialchars($producto['codigo_sku']); ?>)
                                                    <?php endif; ?>
                                                    - $<?php echo number_format($producto['precio'], 2); ?>
                                                    <?php if ($producto['usar_precio_fijo_bs']): ?>
                                                        <span class="text-success">[Precio fijo: <?php echo number_format($producto['precio_bs'], 2); ?> Bs]</span>
                                                    <?php endif; ?>
                                                    - Stock: <?php echo $producto['stock_actual']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="">No hay productos disponibles</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control cantidad-input" name="cantidades[]"
                                        min="1" step="1" value="1" placeholder="Cantidad" onchange="calcularSubtotal(this)">
                                    <small class="text-muted stock-info"></small>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control precio-input" name="precios[]"
                                        step="0.01" min="0" placeholder="Precio USD" readonly>
                                    <small class="text-muted precio-bs-info"></small>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="form-control subtotal-input" readonly placeholder="Subtotal">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)" disabled>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarProducto()">
                            <i class="fas fa-plus me-1"></i> Agregar Producto
                        </button>

                        <?php if (empty($productos_data)): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay productos disponibles. <a href="../productos/crear.php" class="alert-link">Crear nuevo producto</a>
                            </div>
                        <?php endif; ?>
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
                        <button type="submit" class="btn btn-success" <?php echo (empty($productos_data) || !$tasa_info) ? 'disabled' : ''; ?>>
                            <i class="fas fa-save me-1"></i> Guardar Venta
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="button" class="btn btn-outline-info" onclick="limpiarFormulario()">
                            <i class="fas fa-broom me-1"></i> Limpiar
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
                                    <input type="text" class="form-control" id="nuevo_documento" name="documento_identidad">
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
                Información Importante
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">Sistema de Precios:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-dollar-sign text-primary me-2"></i> <strong>Precio en USD:</strong> Precio base en dólares</li>
                        <li><i class="fas fa-lock text-success me-2"></i> <strong>Precio Fijo en Bs:</strong> Productos marcados mantienen su precio en bolívares</li>
                        <li><i class="fas fa-exchange-alt text-info me-2"></i> <strong>Conversión Automática:</strong> Productos sin precio fijo se convierten automáticamente</li>
                        <li><i class="fas fa-calculator text-warning me-2"></i> <strong>Tasa Actual:</strong> <?php echo $tasa_info ? number_format($tasa_info['tasa_cambio'], 2) : 'N/A'; ?> Bs/USD</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-warning">Consideraciones:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> La venta se crea en estado "Pendiente"</li>
                        <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> El stock se actualiza al completar la venta</li>
                        <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> Verifica el stock antes de completar</li>
                        <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> La tasa de cambio se bloquea al momento de la venta</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
        .precio-fijo-indicator {
            color: #28a745;
            font-weight: bold;
        }
        
        .stock-bajo {
            color: #dc3545;
            font-weight: bold;
        }
        
        .producto-row {
            padding: 10px;
            border-radius: 5px;
            background-color: #f8f9fa;
            margin-bottom: 10px;
        }
        
        .producto-row:hover {
            background-color: #e9ecef;
        }
    </style>

    <script>
        let tasaCambio = <?php echo $tasa_info ? $tasa_info['tasa_cambio'] : 0; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            calcularTotal();

            // Si no hay productos, mostrar alerta
            <?php if (empty($productos_data)): ?>
                showToast('warning', 'No hay productos disponibles. Debes crear productos primero.');
            <?php endif; ?>

            // Si no hay tasa, deshabilitar el formulario
            <?php if (!$tasa_info): ?>
                showToast('error', 'No hay tasa de cambio configurada. Configure la tasa primero.');
            <?php endif; ?>
        });

        // Funciones para gestión de productos
        function agregarProducto() {
            const container = document.getElementById('productos-container');
            const totalRows = document.querySelectorAll('.producto-row').length;
            
            const newRow = document.createElement('div');
            newRow.className = 'producto-row row mb-3';
            newRow.innerHTML = `
                <div class="col-md-5">
                    <select class="form-control producto-select" name="productos[]" onchange="actualizarPrecio(this)">
                        <option value="">Seleccionar producto...</option>
                        <?php if (!empty($productos_data)): ?>
                            <?php foreach ($productos_data as $producto): ?>
                                <option value="<?php echo $producto['id']; ?>"
                                    data-precio="<?php echo $producto['precio']; ?>"
                                    data-precio-bs="<?php echo $producto['precio_bs']; ?>"
                                    data-precio-fijo="<?php echo $producto['usar_precio_fijo_bs'] ? 'true' : 'false'; ?>"
                                    data-stock="<?php echo $producto['stock_actual']; ?>">
                                    <?php echo htmlspecialchars($producto['nombre']); ?>
                                    <?php if (!empty($producto['codigo_sku'])): ?>
                                        (SKU: <?php echo htmlspecialchars($producto['codigo_sku']); ?>)
                                    <?php endif; ?>
                                    - $<?php echo number_format($producto['precio'], 2); ?>
                                    <?php if ($producto['usar_precio_fijo_bs']): ?>
                                        <span class="text-success">[Precio fijo: <?php echo number_format($producto['precio_bs'], 2); ?> Bs]</span>
                                    <?php endif; ?>
                                    - Stock: <?php echo $producto['stock_actual']; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No hay productos disponibles</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control cantidad-input" name="cantidades[]" 
                           min="1" step="1" value="1" placeholder="Cantidad" onchange="calcularSubtotal(this)">
                    <small class="text-muted stock-info"></small>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control precio-input" name="precios[]" 
                           step="0.01" min="0" placeholder="Precio USD" readonly>
                    <small class="text-muted precio-bs-info"></small>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control subtotal-input" readonly placeholder="Subtotal">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(newRow);
            
            // Habilitar botón eliminar si hay más de una fila
            const allRows = document.querySelectorAll('.producto-row');
            if (allRows.length > 1) {
                document.querySelectorAll('.producto-row .btn-danger').forEach(btn => {
                    btn.disabled = false;
                });
            }
        }

        function eliminarFila(button) {
            const row = button.closest('.producto-row');
            const totalRows = document.querySelectorAll('.producto-row').length;
            
            if (totalRows > 1) {
                row.remove();
                calcularTotal();
            } else {
                showToast('warning', 'Debe haber al menos un producto en la venta');
            }
        }

        function actualizarPrecio(select) {
            const row = select.closest('.producto-row');
            const precioInput = row.querySelector('.precio-input');
            const cantidadInput = row.querySelector('.cantidad-input');
            const stockInfo = row.querySelector('.stock-info');
            const precioBsInfo = row.querySelector('.precio-bs-info');

            if (select.value) {
                const precio = parseFloat(select.selectedOptions[0].getAttribute('data-precio'));
                const precioBs = parseFloat(select.selectedOptions[0].getAttribute('data-precio-bs'));
                const precioFijo = select.selectedOptions[0].getAttribute('data-precio-fijo') === 'true';
                const stock = parseInt(select.selectedOptions[0].getAttribute('data-stock'));

                // Guardar datos en el dataset del row
                row.dataset.precioFijo = precioFijo;
                row.dataset.precioBs = precioBs;
                row.dataset.stock = stock;

                precioInput.value = precio.toFixed(2);
                cantidadInput.max = stock;

                // Mostrar información de precio en BS
                if (precioFijo && precioBs > 0) {
                    precioBsInfo.innerHTML = `<span class="precio-fijo-indicator">Bs ${precioBs.toFixed(2)} (Fijo)</span>`;
                } else {
                    const precioBsCalculado = precio * tasaCambio;
                    precioBsInfo.innerHTML = `Bs ${precioBsCalculado.toFixed(2)} (Calculado)`;
                }

                // Mostrar información de stock
                if (stock <= 0) {
                    stockInfo.innerHTML = '<span class="stock-bajo">Sin stock</span>';
                    cantidadInput.disabled = true;
                    showToast('error', 'Producto sin stock disponible');
                } else if (stock <= 10) {
                    stockInfo.innerHTML = `<span class="stock-bajo">Stock bajo: ${stock}</span>`;
                    cantidadInput.disabled = false;
                } else {
                    stockInfo.innerHTML = `Stock: ${stock}`;
                    cantidadInput.disabled = false;
                }

                if (parseInt(cantidadInput.value) > stock && stock > 0) {
                    cantidadInput.value = stock;
                    showToast('warning', 'La cantidad supera el stock disponible. Se ajustó al máximo.');
                }

                calcularSubtotal(cantidadInput);
            } else {
                precioInput.value = '';
                precioBsInfo.innerHTML = '';
                stockInfo.innerHTML = '';
                row.querySelector('.subtotal-input').value = '';
                delete row.dataset.precioFijo;
                delete row.dataset.precioBs;
                delete row.dataset.stock;
            }
        }

        function calcularSubtotal(input) {
            const row = input.closest('.producto-row');
            const precioInput = row.querySelector('.precio-input');
            const subtotalInput = row.querySelector('.subtotal-input');
            const productoId = row.querySelector('.producto-select').value;
            const precioFijo = row.dataset.precioFijo === 'true';
            const precioBs = parseFloat(row.dataset.precioBs) || 0;

            const cantidad = parseFloat(input.value) || 0;
            const precio = parseFloat(precioInput.value) || 0;
            const subtotalUSD = cantidad * precio;
            
            let subtotalBS = 0;
            
            if (precioFijo && precioBs > 0) {
                // Producto con precio fijo
                subtotalBS = cantidad * precioBs;
                subtotalInput.value = `$${subtotalUSD.toFixed(2)} / Bs ${subtotalBS.toFixed(2)} (Fijo)`;
            } else {
                // Producto sin precio fijo
                subtotalBS = subtotalUSD * tasaCambio;
                subtotalInput.value = `$${subtotalUSD.toFixed(2)} / Bs ${subtotalBS.toFixed(2)}`;
            }
            
            calcularTotal();
        }

        function calcularTotal() {
            let subtotalUSD = 0;
            let subtotalBS = 0;

            document.querySelectorAll('.producto-row').forEach(row => {
                const productoSelect = row.querySelector('.producto-select');
                if (!productoSelect.value) return;

                const precioFijo = row.dataset.precioFijo === 'true';
                const precioBs = parseFloat(row.dataset.precioBs) || 0;
                const precioInput = row.querySelector('.precio-input');
                const cantidadInput = row.querySelector('.cantidad-input');

                const cantidad = parseFloat(cantidadInput.value) || 0;
                const precio = parseFloat(precioInput.value) || 0;
                const subtotalItemUSD = cantidad * precio;
                
                let subtotalItemBS = 0;
                
                if (precioFijo && precioBs > 0) {
                    subtotalItemBS = cantidad * precioBs;
                } else {
                    subtotalItemBS = subtotalItemUSD * tasaCambio;
                }

                subtotalUSD += subtotalItemUSD;
                subtotalBS += subtotalItemBS;
            });

            document.getElementById('total-usd').textContent = '$' + subtotalUSD.toFixed(2);
            document.getElementById('total-bs').textContent = 'Bs ' + subtotalBS.toFixed(2);
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

                        actualizarSelectClientes(data.id, formData.get('nombre'), formData.get('documento_identidad'));

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
            if (confirm('¿Estás seguro de que deseas limpiar el formulario? Se perderán todos los datos ingresados.')) {
                document.getElementById('formVenta').reset();
                document.getElementById('productos-container').innerHTML = `
                    <div class="producto-row row mb-3">
                        <div class="col-md-5">
                            <select class="form-control producto-select" name="productos[]" onchange="actualizarPrecio(this)">
                                <option value="">Seleccionar producto...</option>
                                <?php if (!empty($productos_data)): ?>
                                    <?php foreach ($productos_data as $producto): ?>
                                        <option value="<?php echo $producto['id']; ?>"
                                            data-precio="<?php echo $producto['precio']; ?>"
                                            data-precio-bs="<?php echo $producto['precio_bs']; ?>"
                                            data-precio-fijo="<?php echo $producto['usar_precio_fijo_bs'] ? 'true' : 'false'; ?>"
                                            data-stock="<?php echo $producto['stock_actual']; ?>">
                                            <?php echo htmlspecialchars($producto['nombre']); ?>
                                            <?php if (!empty($producto['codigo_sku'])): ?>
                                                (SKU: <?php echo htmlspecialchars($producto['codigo_sku']); ?>)
                                            <?php endif; ?>
                                            - $<?php echo number_format($producto['precio'], 2); ?>
                                            <?php if ($producto['usar_precio_fijo_bs']): ?>
                                                <span class="text-success">[Precio fijo: <?php echo number_format($producto['precio_bs'], 2); ?> Bs]</span>
                                            <?php endif; ?>
                                            - Stock: <?php echo $producto['stock_actual']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No hay productos disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control cantidad-input" name="cantidades[]" 
                                   min="1" step="1" value="1" placeholder="Cantidad" onchange="calcularSubtotal(this)">
                            <small class="text-muted stock-info"></small>
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control precio-input" name="precios[]" 
                                   step="0.01" min="0" placeholder="Precio USD" readonly>
                            <small class="text-muted precio-bs-info"></small>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control subtotal-input" readonly placeholder="Subtotal">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFila(this)" disabled>
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
                calcularTotal();
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

<!--     <?php include '../layouts/footer.php'; ?> -->