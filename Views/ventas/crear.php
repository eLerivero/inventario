<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/VentaController.php';
require_once '../../Controllers/ClienteController.php';
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/TipoPagoController.php';

$database = new Database();
$db = $database->getConnection();

$ventaController = new VentaController($db);
$clienteController = new ClienteController($db);
$productoController = new ProductoController($db);
$tipoPagoController = new TipoPagoController($db);

$error_message = '';
$success_message = '';

// Obtener datos para formulario
$clientes = $clienteController->obtenerClientesActivos();
$productos = $productoController->listar();
$tiposPago = $tipoPagoController->listar();

// Verificar y asignar datos correctamente
$clientes_data = $clientes['success'] ? $clientes['data'] : [];
$productos_data = $productos['success'] ? $productos['data'] : [];
$tiposPago_data = $tiposPago['success'] ? $tiposPago['data'] : [];

// Debug: Verificar datos de productos
if (empty($productos_data)) {
    error_log("No se encontraron productos. Respuesta del controlador: " . print_r($productos, true));
}

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
                    $cantidad = floatval($_POST['cantidades'][$index]);
                    $precio = floatval($_POST['precios'][$index]);
                    $subtotal = $cantidad * $precio;
                    
                    $datosVenta['detalles'][] = [
                        'producto_id' => $producto_id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $subtotal
                    ];
                    
                    $total_venta += $subtotal;
                }
            }
            
            $datosVenta['total'] = $total_venta;
        }

        if (empty($datosVenta['detalles'])) {
            throw new Exception("Debe agregar al menos un producto a la venta");
        }

        $result = $ventaController->crear($datosVenta);
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Redirigir después de 2 segundos
            header("Refresh: 2; URL=index.php");
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

<!-- Alertas -->
<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
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
                                                    data-stock="<?php echo $producto['stock_actual']; ?>">
                                                <?php echo htmlspecialchars($producto['nombre']); ?> 
                                                <?php if (!empty($producto['codigo_sku'])): ?>
                                                    (SKU: <?php echo htmlspecialchars($producto['codigo_sku']); ?>)
                                                <?php endif; ?>
                                                - S/ <?php echo number_format($producto['precio'], 2); ?>
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
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control precio-input" name="precios[]" 
                                       step="0.01" min="0" placeholder="Precio" readonly>
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
                                <span>Subtotal:</span>
                                <span id="subtotal-total">S/ 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>IGV (18%):</span>
                                <span id="igv-total">S/ 0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong id="total-venta">S/ 0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-success" <?php echo empty($productos_data) ? 'disabled' : ''; ?>>
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
                <h6 class="text-primary">Proceso de Venta:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> Selecciona cliente existente o crea uno nuevo</li>
                    <li><i class="fas fa-check text-success me-2"></i> Agrega productos con sus cantidades</li>
                    <li><i class="fas fa-check text-success me-2"></i> Verifica precios y stock disponible</li>
                    <li><i class="fas fa-check text-success me-2"></i> Completa la venta desde el listado</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-warning">Consideraciones:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> La venta se crea en estado "Pendiente"</li>
                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> El stock se actualiza al completar la venta</li>
                    <li><i class="fas fa-exclamation-triangle text-warning me-2"></i> Verifica el stock antes de completar</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let productosData = <?php echo json_encode($productos_data); ?>;

document.addEventListener('DOMContentLoaded', function() {
    calcularTotal();
    
    // Si no hay productos, mostrar alerta
    <?php if (empty($productos_data)): ?>
        showToast('warning', 'No hay productos disponibles. Debes crear productos primero.');
    <?php endif; ?>
});

// Funciones para gestión de productos
function agregarProducto() {
    const container = document.getElementById('productos-container');
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
                                data-stock="<?php echo $producto['stock_actual']; ?>">
                            <?php echo htmlspecialchars($producto['nombre']); ?> 
                            <?php if (!empty($producto['codigo_sku'])): ?>
                                (SKU: <?php echo htmlspecialchars($producto['codigo_sku']); ?>)
                            <?php endif; ?>
                            - S/ <?php echo number_format($producto['precio'], 2); ?>
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
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control precio-input" name="precios[]" 
                   step="0.01" min="0" placeholder="Precio" readonly>
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
}

function eliminarFila(button) {
    const row = button.closest('.producto-row');
    // No permitir eliminar la primera fila si es la única
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
    
    if (select.value) {
        const precio = select.selectedOptions[0].getAttribute('data-precio');
        const stock = parseInt(select.selectedOptions[0].getAttribute('data-stock'));
        
        precioInput.value = parseFloat(precio).toFixed(2);
        cantidadInput.max = stock;
        
        if (parseInt(cantidadInput.value) > stock) {
            cantidadInput.value = stock;
            showToast('warning', 'La cantidad supera el stock disponible. Se ajustó al máximo.');
        }
        
        calcularSubtotal(cantidadInput);
    } else {
        precioInput.value = '';
        row.querySelector('.subtotal-input').value = '';
    }
}

function calcularSubtotal(input) {
    const row = input.closest('.producto-row');
    const precioInput = row.querySelector('.precio-input');
    const subtotalInput = row.querySelector('.subtotal-input');
    
    const cantidad = parseFloat(input.value) || 0;
    const precio = parseFloat(precioInput.value) || 0;
    const subtotal = cantidad * precio;
    
    subtotalInput.value = 'S/ ' + subtotal.toFixed(2);
    calcularTotal();
}

function calcularTotal() {
    let subtotal = 0;
    
    document.querySelectorAll('.producto-row').forEach(row => {
        const subtotalInput = row.querySelector('.subtotal-input');
        const valorTexto = subtotalInput.value.replace('S/ ', '').trim();
        const valor = parseFloat(valorTexto) || 0;
        subtotal += valor;
    });
    
    const igv = subtotal * 0.18;
    const total = subtotal + igv;
    
    document.getElementById('subtotal-total').textContent = 'S/ ' + subtotal.toFixed(2);
    document.getElementById('igv-total').textContent = 'S/ ' + igv.toFixed(2);
    document.getElementById('total-venta').textContent = 'S/ ' + total.toFixed(2);
}

// Funciones para gestión de clientes (se mantienen igual)
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
                                        data-stock="<?php echo $producto['stock_actual']; ?>">
                                    <?php echo htmlspecialchars($producto['nombre']); ?> 
                                    <?php if (!empty($producto['codigo_sku'])): ?>
                                        (SKU: <?php echo htmlspecialchars($producto['codigo_sku']); ?>)
                                    <?php endif; ?>
                                    - S/ <?php echo number_format($producto['precio'], 2); ?>
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
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control precio-input" name="precios[]" 
                           step="0.01" min="0" placeholder="Precio" readonly>
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
document.getElementById('modalNuevoCliente').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formNuevoCliente').reset();
    document.getElementById('cliente-message').innerHTML = '';
});
</script>

<!-- <?php include '../layouts/footer.php'; ?> -->