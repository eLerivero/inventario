<?php
// Views/productos/editar.php
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/CategoriaController.php';
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$productoController = new ProductoController($db);
$categoriaController = new CategoriaController($db);
$tasaController = new TasaCambioController($db);

$categorias = $categoriaController->obtenerTodas();
$tasaActual = $tasaController->obtenerTasaActual();

// OBTENER DATOS DEL PRODUCTO A EDITAR
$mensaje = '';
$tipoMensaje = '';
$producto = null;

$producto_id = $_GET['id'] ?? null;

if (!$producto_id) {
    $mensaje = "ID de producto no especificado";
    $tipoMensaje = 'danger';
} else {
    $result = $productoController->obtener($producto_id);
    if ($result['success']) {
        $producto = $result['data'];
        // Guardar stock original para comparaciones
        $stockOriginal = $producto['stock_actual'];
    } else {
        $mensaje = $result['message'];
        $tipoMensaje = 'danger';
    }
}

// PROCESAR FORMULARIO DE ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $producto) {
    try {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => floatval($_POST['precio'] ?? 0),
            'precio_bs' => floatval($_POST['precio_bs'] ?? 0),
            'precio_costo' => floatval($_POST['precio_costo'] ?? 0),
            'precio_costo_bs' => floatval($_POST['precio_costo_bs'] ?? 0),
            'usar_precio_fijo_bs' => isset($_POST['usar_precio_fijo_bs']) ? true : false,
            'stock_actual' => intval($_POST['stock_actual'] ?? 0),
            'stock_minimo' => intval($_POST['stock_minimo'] ?? 0),
            'categoria_id' => $_POST['categoria_id'] ?? null,
            'activo' => isset($_POST['activo']) ? true : false
        ];

        // Validaciones
        if (empty($data['nombre'])) {
            throw new Exception("El nombre del producto es obligatorio");
        }

        if ($data['stock_actual'] < 0) {
            throw new Exception("El stock no puede ser negativo");
        }

        // Validación de precios según configuración
        if ($data['usar_precio_fijo_bs']) {
            if ($data['precio_bs'] <= 0) {
                throw new Exception("Para precio fijo, el precio en Bs debe ser mayor a 0");
            }
        } else {
            if ($data['precio'] <= 0) {
                throw new Exception("El precio en USD debe ser mayor a 0");
            }
        }

        $resultado = $productoController->actualizar($producto_id, $data);

        if ($resultado['success']) {
            $mensaje = 'Producto actualizado exitosamente';
            $tipoMensaje = 'success';

            // Actualizar los datos del producto en la variable
            $result = $productoController->obtener($producto_id);
            if ($result['success']) {
                $producto = $result['data'];
                $stockOriginal = $producto['stock_actual']; // Actualizar stock original
            }

            // Redirigir después de 2 segundos
            echo '<script>setTimeout(() => { window.location.href = "index.php"; }, 2000);</script>';
        } else {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'danger';
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipoMensaje = 'danger';
    }
}

$page_title = "Editar Producto";
require_once '../layouts/header.php';
?>

<div class="content-wrapper crear-producto-content">

    <!-- Header de la página -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-edit me-2"></i>Editar Producto
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Listado
            </a>
        </div>
    </div>

    <!-- Información de Tasa de Cambio -->
    <?php if ($tasaActual['success']): ?>
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exchange-alt me-3 fa-lg"></i>
                <div>
                    <strong>Tasa de Cambio Actual:</strong> 1 USD = <?php echo number_format($tasaActual['data']['tasa_cambio'], 2); ?> Bs
                    <br>
                    <small class="text-muted">Los precios en Bolívares se calcularán automáticamente a menos que uses precio fijo</small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Alertas del sistema -->
    <?php if ($mensaje && !$producto): ?>
        <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($producto): ?>
        <!-- Alertas de éxito/error después de enviar el formulario -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($mensaje); ?>
                <?php if ($tipoMensaje === 'success'): ?>
                    <div class="mt-2">
                        <small>Serás redirigido automáticamente al listado de productos...</small>
                    </div>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Información del producto -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información Actual del Producto
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong><i class="fas fa-barcode me-1"></i>SKU:</strong>
                        <p class="mb-2"><?php echo htmlspecialchars($producto['codigo_sku']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-boxes me-1"></i>Stock Actual:</strong>
                        <p class="mb-2"><?php echo htmlspecialchars($producto['stock_actual']); ?> unidades</p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-calendar me-1"></i>Creado:</strong>
                        <p class="mb-2"><?php echo date('d/m/Y H:i', strtotime($producto['created_at'])); ?></p>
                    </div>
                    <div class="col-md-3">
                        <strong><i class="fas fa-sync-alt me-1"></i>Actualizado:</strong>
                        <p class="mb-2"><?php echo date('d/m/Y H:i', strtotime($producto['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de edición -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Editar Información del Producto
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formProducto" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Nombre del Producto *
                                </label>
                                <input type="text"
                                    class="form-control <?php echo isset($_POST['nombre']) && empty($_POST['nombre']) ? 'is-invalid' : ''; ?>"
                                    id="nombre"
                                    name="nombre"
                                    value="<?php echo htmlspecialchars($_POST['nombre'] ?? $producto['nombre']); ?>"
                                    required
                                    maxlength="255"
                                    placeholder="Ej: Laptop Dell Inspiron 15">
                                <?php if (isset($_POST['nombre']) && empty($_POST['nombre'])): ?>
                                    <div class="invalid-feedback">
                                        El nombre del producto es obligatorio.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-barcode me-1"></i>Código SKU
                                </label>
                                <input type="text"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($producto['codigo_sku']); ?>"
                                    readonly
                                    disabled>
                                <div class="form-text">El SKU no se puede modificar</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">
                            <i class="fas fa-align-left me-1"></i>Descripción
                        </label>
                        <textarea class="form-control"
                            id="descripcion"
                            name="descripcion"
                            rows="3"
                            maxlength="500"
                            placeholder="Describe las características del producto..."><?php echo htmlspecialchars($_POST['descripcion'] ?? $producto['descripcion']); ?></textarea>
                        <div class="form-text">
                            Máximo 500 caracteres.
                            <span id="charCount" class="char-counter-info"><?php echo strlen($_POST['descripcion'] ?? $producto['descripcion']); ?></span>/500
                        </div>
                    </div>

                    <!-- Configuración de Precio Fijo -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="usar_precio_fijo_bs" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Configuración de Precio en Bolívares
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        id="usar_precio_fijo_bs"
                                        name="usar_precio_fijo_bs"
                                        value="1"
                                        <?php echo (isset($_POST['usar_precio_fijo_bs']) ? $_POST['usar_precio_fijo_bs'] : $producto['usar_precio_fijo_bs']) ? 'checked' : ''; ?>
                                        onchange="togglePrecioFijo()">
                                    <label class="form-check-label" for="usar_precio_fijo_bs">
                                        Usar precio fijo en Bolívares
                                    </label>
                                </div>
                                <div class="form-text">
                                    Cuando está activado, el precio en Bs no cambiará con la tasa de cambio y los campos USD serán opcionales.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3" id="precio-fijo-container" style="<?php echo $producto['usar_precio_fijo_bs'] ? 'display: block;' : 'display: none;'; ?>">
                                <label for="precio_bs" class="form-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Precio Fijo en Bolívares <span class="text-danger precio-bs-required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs</span>
                                    <input type="number"
                                        class="form-control <?php echo isset($_POST['precio_bs']) && (empty($_POST['precio_bs']) || $_POST['precio_bs'] <= 0) ? 'is-invalid' : ''; ?>"
                                        id="precio_bs"
                                        name="precio_bs"
                                        value="<?php echo number_format($_POST['precio_bs'] ?? $producto['precio_bs'], 2, '.', ''); ?>"
                                        step="0.01"
                                        min="0"
                                        placeholder="Precio fijo en Bs"
                                        onchange="calcularPreciosYMargenes()">
                                    <?php if (isset($_POST['precio_bs']) && (empty($_POST['precio_bs']) || $_POST['precio_bs'] <= 0)): ?>
                                        <div class="invalid-feedback">
                                            El precio fijo en Bs debe ser mayor a 0.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">Este precio no se actualizará automáticamente con la tasa de cambio</div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Precios -->
                    <div class="row">
                        <!-- Campo Precio de Venta (USD) -->
                        <div class="col-md-4" id="precio-usd-container" style="<?php echo $producto['usar_precio_fijo_bs'] ? 'display: none;' : 'display: block;'; ?>">
                            <div class="mb-3">
                                <label for="precio" class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Precio de Venta (USD) <span id="precioRequired" class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number"
                                        class="form-control precio-usd <?php echo isset($_POST['precio']) && (empty($_POST['precio']) || $_POST['precio'] <= 0) ? 'is-invalid' : ''; ?>"
                                        id="precio"
                                        name="precio"
                                        value="<?php echo number_format($_POST['precio'] ?? $producto['precio'], 2, '.', ''); ?>"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        onchange="calcularPreciosYMargenes()">
                                    <?php if (isset($_POST['precio']) && (empty($_POST['precio']) || $_POST['precio'] <= 0)): ?>
                                        <div class="invalid-feedback">
                                            El precio debe ser mayor a 0.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-text">Precio de venta al público en USD</div>
                            </div>
                        </div>
                        
                        <!-- Campo Precio de Costo (USD) -->
                        <div class="col-md-4" id="precio-costo-usd-container" style="<?php echo $producto['usar_precio_fijo_bs'] ? 'display: none;' : 'display: block;'; ?>">
                            <div class="mb-3">
                                <label for="precio_costo" class="form-label">
                                    <i class="fas fa-receipt me-1"></i>Precio de Costo (USD)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number"
                                        class="form-control precio-usd"
                                        id="precio_costo"
                                        name="precio_costo"
                                        value="<?php echo number_format($_POST['precio_costo'] ?? $producto['precio_costo'], 2, '.', ''); ?>"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        onchange="calcularPreciosYMargenes()">
                                </div>
                                <div class="form-text">Precio de compra al proveedor en USD</div>
                            </div>
                        </div>
                        
                        <!-- Campo Precio de Costo (Bs) - solo visible para precio fijo -->
                        <div class="col-md-4" id="precio-costo-bs-container" style="<?php echo $producto['usar_precio_fijo_bs'] ? 'display: block;' : 'display: none;'; ?>">
                            <div class="mb-3">
                                <label for="precio_costo_bs" class="form-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Precio de Costo (Bs)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs</span>
                                    <input type="number"
                                        class="form-control"
                                        id="precio_costo_bs"
                                        name="precio_costo_bs"
                                        value="<?php echo number_format($_POST['precio_costo_bs'] ?? $producto['precio_costo_bs'], 2, '.', ''); ?>"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        onchange="calcularPreciosYMargenes()">
                                </div>
                                <div class="form-text">Costo en bolívares (solo para precio fijo)</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-calculator me-1"></i>Precio Actual en Bolívares
                                </label>
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Venta:</small><br>
                                                <strong id="precioBsCalculado" class="text-success">Bs <?php echo number_format($producto['precio_bs'], 2, ',', '.'); ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Costo:</small><br>
                                                <strong id="precioCostoBsCalculado" class="text-muted">Bs <?php echo number_format($producto['precio_costo_bs'], 2, ',', '.'); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Margen -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body py-3">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <small class="text-muted">Margen de Ganancia</small>
                                            <div>
                                                <span id="margenGanancia" class="badge bg-info">0%</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Ganancia por Unidad (USD)</small>
                                            <div>
                                                <strong id="gananciaNetaUSD">$0.00</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">Ganancia por Unidad (Bs)</small>
                                            <div>
                                                <strong id="gananciaNetaBS">Bs 0.00</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Stock -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="stock_minimo" class="form-label">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Stock Mínimo
                                </label>
                                <input type="number"
                                    class="form-control"
                                    id="stock_minimo"
                                    name="stock_minimo"
                                    value="<?php echo htmlspecialchars($_POST['stock_minimo'] ?? $producto['stock_minimo']); ?>"
                                    min="0"
                                    step="1"
                                    placeholder="5">
                                <div class="form-text">Alerta cuando el stock llegue a este nivel</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="stock_actual" class="form-label">
                                    <i class="fas fa-boxes me-1"></i>Stock Actual *
                                </label>
                                <input type="number"
                                    class="form-control <?php echo isset($_POST['stock_actual']) && $_POST['stock_actual'] < 0 ? 'is-invalid' : ''; ?>"
                                    id="stock_actual"
                                    name="stock_actual"
                                    value="<?php echo htmlspecialchars($_POST['stock_actual'] ?? $producto['stock_actual']); ?>"
                                    required
                                    min="0"
                                    step="1"
                                    placeholder="0"
                                    onchange="validarStock()">
                                <?php if (isset($_POST['stock_actual']) && $_POST['stock_actual'] < 0): ?>
                                    <div class="invalid-feedback">
                                        El stock no puede ser negativo.
                                    </div>
                                <?php endif; ?>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Cantidad actual disponible en inventario.
                                    <span id="cambioStockInfo" class="text-muted"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="categoria_id" class="form-label">
                                    <i class="fas fa-tags me-1"></i>Categoría
                                </label>
                                <select class="form-select" id="categoria_id" name="categoria_id">
                                    <option value="">Sin categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo htmlspecialchars($categoria['id']); ?>"
                                            <?php echo ((isset($_POST['categoria_id']) ? $_POST['categoria_id'] : $producto['categoria_id']) == $categoria['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Asigna una categoría al producto</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="activo" class="form-label">
                                    <i class="fas fa-toggle-on me-1"></i>Estado del Producto
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                        type="checkbox"
                                        id="activo"
                                        name="activo"
                                        value="1"
                                        <?php echo (isset($_POST['activo']) ? $_POST['activo'] : $producto['activo']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activo">
                                        Producto Activo
                                    </label>
                                </div>
                                <div class="form-text">Cuando está inactivo, no aparecerá en las listas de ventas</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Actualizar Producto
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="button" class="btn btn-outline-info" onclick="limpiarFormulario()">
                                <i class="fas fa-broom me-1"></i> Restaurar Valores
                            </button>
                            <a href="javascript:void(0)" class="btn btn-outline-danger float-end" onclick="confirmarEliminacion()">
                                <i class="fas fa-trash me-1"></i> Eliminar Producto
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
    // Variables globales
    let tasaActual = <?php echo $tasaActual['success'] ? $tasaActual['data']['tasa_cambio'] : 36.5; ?>;
    let productoOriginal = <?php echo json_encode($producto ?? []); ?>;
    let stockOriginal = <?php echo $stockOriginal ?? 0; ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const descripcion = document.getElementById('descripcion');
        const charCount = document.getElementById('charCount');

        // Contador de caracteres
        descripcion.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;

            if (length > 450) {
                charCount.className = 'char-counter-warning';
            } else if (length > 400) {
                charCount.className = 'text-warning';
            } else {
                charCount.className = 'char-counter-info';
            }
        });

        // Calcular márgenes inicialmente
        calcularPreciosYMargenes();
        
        // Validar stock inicialmente
        validarStock();

        // Configurar toggle de precio fijo
        const usarPrecioFijoCheckbox = document.getElementById('usar_precio_fijo_bs');
        if (usarPrecioFijoCheckbox) {
            usarPrecioFijoCheckbox.addEventListener('change', togglePrecioFijo);
        }

        // Validación del formulario
        const form = document.getElementById('formProducto');
        const nombreInput = document.getElementById('nombre');
        const precioInput = document.getElementById('precio');
        const precioBsInput = document.getElementById('precio_bs');
        const stockInput = document.getElementById('stock_actual');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const nombre = nombreInput.value.trim();
            const precioVal = parseFloat(precioInput.value);
            const stockVal = parseInt(stockInput.value);
            const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs')?.checked || false;
            const precioBsVal = parseFloat(precioBsInput.value);
            
            let isValid = true;
            let errorMessage = '';

            // Limpiar validaciones anteriores
            nombreInput.classList.remove('is-invalid', 'is-valid');
            precioInput.classList.remove('is-invalid', 'is-valid');
            precioBsInput.classList.remove('is-invalid', 'is-valid');
            stockInput.classList.remove('is-invalid', 'is-valid');

            // Validación del nombre
            if (!nombre) {
                nombreInput.classList.add('is-invalid');
                isValid = false;
                errorMessage = 'El nombre del producto es obligatorio.';
            }

            // Validación del stock
            if (isNaN(stockVal) || stockVal < 0) {
                stockInput.classList.add('is-invalid');
                isValid = false;
                errorMessage = errorMessage || 'El stock debe ser un número positivo o cero.';
            }

            // Validación según tipo de precio
            if (usarPrecioFijo) {
                if (isNaN(precioBsVal) || precioBsVal <= 0) {
                    precioBsInput.classList.add('is-invalid');
                    isValid = false;
                    errorMessage = errorMessage || 'Para precio fijo, el precio en Bs debe ser mayor a 0.';
                }
            } else {
                if (isNaN(precioVal) || precioVal <= 0) {
                    precioInput.classList.add('is-invalid');
                    isValid = false;
                    errorMessage = errorMessage || 'El precio en USD debe ser mayor a 0.';
                }
            }

            if (!isValid) {
                showToast('error', errorMessage || 'Por favor, completa todos los campos obligatorios correctamente.');
                return false;
            }

            // Mostrar confirmación
            if (confirm('¿Estás seguro de que deseas actualizar este producto?')) {
                // Mostrar indicador de carga
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...';
                
                // Enviar formulario
                form.submit();
                
                // Restaurar botón después de 3 segundos (por si hay error)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 3000);
            }
        });

        // Validación en tiempo real
        nombreInput.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });

        precioInput.addEventListener('input', function() {
            const valor = parseFloat(this.value);
            if (valor && valor > 0) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
            calcularPreciosYMargenes();
        });

        stockInput.addEventListener('input', function() {
            validarStock();
        });
    });

    function togglePrecioFijo() {
        const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs').checked;
        const precioBsContainer = document.getElementById('precio-fijo-container');
        const precioCostoBsContainer = document.getElementById('precio-costo-bs-container');
        const precioUSDContainer = document.getElementById('precio-usd-container');
        const precioCostoUSDContainer = document.getElementById('precio-costo-usd-container');
        const precioBsInput = document.getElementById('precio_bs');
        const precioCostoBsInput = document.getElementById('precio_costo_bs');
        const precioUSDInput = document.getElementById('precio');
        const precioCostoUSDInput = document.getElementById('precio_costo');
        const precioRequiredLabel = document.getElementById('precioRequired');

        if (usarPrecioFijo) {
            precioBsContainer.style.display = 'block';
            precioCostoBsContainer.style.display = 'block';
            
            // Ocultar campos USD
            precioUSDContainer.style.display = 'none';
            precioCostoUSDContainer.style.display = 'none';
            
            // Para precio fijo, los campos en USD no son obligatorios
            precioUSDInput.required = false;
            precioCostoUSDInput.required = false;
            
            // Mostrar validación para precio_bs
            precioBsInput.required = true;
            precioCostoBsInput.required = false;
            
            // Ocultar asterisco rojo en precio USD
            if (precioRequiredLabel) {
                precioRequiredLabel.style.display = 'none';
            }

            // Si no hay valor en precio_bs, calcular basado en precio USD actual
            if (!precioBsInput.value || precioBsInput.value == '0' || precioBsInput.value == '0.00') {
                const precioUSD = parseFloat(precioUSDInput.value) || 0;
                precioBsInput.value = (precioUSD * tasaActual).toFixed(2);
            }
        } else {
            precioBsContainer.style.display = 'none';
            precioCostoBsContainer.style.display = 'none';
            
            // Mostrar campos USD
            precioUSDContainer.style.display = 'block';
            precioCostoUSDContainer.style.display = 'block';
            
            // Para precio NO fijo, el precio USD es obligatorio
            precioUSDInput.required = true;
            precioCostoUSDInput.required = false;
            
            // Quitar requerido de precio_bs
            precioBsInput.required = false;
            precioCostoBsInput.required = false;

            // Mostrar asterisco rojo en precio USD
            if (precioRequiredLabel) {
                precioRequiredLabel.style.display = 'inline';
            }
        }

        // Recalcular márgenes
        calcularPreciosYMargenes();
    }

    function calcularPreciosYMargenes() {
        const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs')?.checked || false;
        const precioBsInput = document.getElementById('precio_bs');
        const precioCostoBsInput = document.getElementById('precio_costo_bs');
        const precioUSDInput = document.getElementById('precio');
        const precioCostoUSDInput = document.getElementById('precio_costo');

        let precioBsCalculado, precioCostoBsCalculado;
        let precioUSD, precioCostoUSD;

        if (usarPrecioFijo) {
            // Para precio fijo, usar los valores de los campos
            precioBsCalculado = parseFloat(precioBsInput.value) || 0;
            precioCostoBsCalculado = parseFloat(precioCostoBsInput.value) || 0;
            precioUSD = parseFloat(precioUSDInput.value) || 0;
            precioCostoUSD = parseFloat(precioCostoUSDInput.value) || 0;
            
            // Si no se ingresó costo en Bs pero sí en USD, calcular
            if (precioCostoBsCalculado === 0 && precioCostoUSD > 0) {
                precioCostoBsCalculado = precioCostoUSD * tasaActual;
            }
            
            // Si no se ingresó precio en Bs pero sí en USD, calcular
            if (precioBsCalculado === 0 && precioUSD > 0) {
                precioBsCalculado = precioUSD * tasaActual;
                precioBsInput.value = precioBsCalculado.toFixed(2);
            }
        } else {
            // Para precio NO fijo, calcular basado en tasa
            precioUSD = parseFloat(precioUSDInput.value) || 0;
            precioCostoUSD = parseFloat(precioCostoUSDInput.value) || 0;
            precioBsCalculado = precioUSD * tasaActual;
            precioCostoBsCalculado = precioCostoUSD * tasaActual;
            
            // Actualizar campo oculto de precio_bs
            if (precioBsInput) {
                precioBsInput.value = precioBsCalculado.toFixed(2);
            }
            if (precioCostoBsInput) {
                precioCostoBsInput.value = precioCostoBsCalculado.toFixed(2);
            }
        }

        // Actualizar campos de visualización con formato
        document.getElementById('precioBsCalculado').textContent = 'Bs ' + precioBsCalculado.toLocaleString('es-VE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        document.getElementById('precioCostoBsCalculado').textContent = 'Bs ' + precioCostoBsCalculado.toLocaleString('es-VE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Calcular márgenes
        const gananciaUSD = precioUSD - precioCostoUSD;
        const gananciaBS = precioBsCalculado - precioCostoBsCalculado;
        const margen = precioCostoUSD > 0 ? ((gananciaUSD / precioCostoUSD) * 100) : 0;

        // Actualizar información de márgenes
        document.getElementById('margenGanancia').textContent = margen.toFixed(2) + '%';
        document.getElementById('gananciaNetaUSD').textContent = '$' + gananciaUSD.toFixed(2);
        document.getElementById('gananciaNetaBS').textContent = 'Bs ' + gananciaBS.toFixed(2);

        // Cambiar color según el margen
        const margenElement = document.getElementById('margenGanancia');
        if (margen < 0) {
            margenElement.className = 'badge bg-danger';
        } else if (margen < 10) {
            margenElement.className = 'badge bg-warning';
        } else if (margen < 25) {
            margenElement.className = 'badge bg-info';
        } else {
            margenElement.className = 'badge bg-success';
        }
    }

    function validarStock() {
        const stockInput = document.getElementById('stock_actual');
        const stockVal = parseInt(stockInput.value);
        const cambioStockInfo = document.getElementById('cambioStockInfo');
        
        if (isNaN(stockVal) || stockVal < 0) {
            stockInput.classList.add('is-invalid');
            stockInput.classList.remove('is-valid');
            cambioStockInfo.textContent = '';
            return false;
        }
        
        stockInput.classList.remove('is-invalid');
        stockInput.classList.add('is-valid');
        
        // Mostrar información del cambio
        const diferencia = stockVal - stockOriginal;
        if (diferencia > 0) {
            cambioStockInfo.textContent = `(Incremento: +${diferencia} unidades)`;
            cambioStockInfo.className = 'text-success';
        } else if (diferencia < 0) {
            cambioStockInfo.textContent = `(Disminución: ${diferencia} unidades)`;
            cambioStockInfo.className = 'text-danger';
        } else {
            cambioStockInfo.textContent = '(Sin cambios)';
            cambioStockInfo.className = 'text-muted';
        }
        
        return true;
    }

    function limpiarFormulario() {
        if (confirm('¿Estás seguro de que deseas restaurar los valores originales? Se perderán los cambios no guardados.')) {
            // Restaurar valores originales del producto
            document.getElementById('nombre').value = productoOriginal.nombre || '';
            document.getElementById('descripcion').value = productoOriginal.descripcion || '';
            document.getElementById('precio').value = productoOriginal.precio ? parseFloat(productoOriginal.precio).toFixed(2) : '';
            document.getElementById('precio_bs').value = productoOriginal.precio_bs ? parseFloat(productoOriginal.precio_bs).toFixed(2) : '';
            document.getElementById('precio_costo').value = productoOriginal.precio_costo ? parseFloat(productoOriginal.precio_costo).toFixed(2) : '';
            document.getElementById('precio_costo_bs').value = productoOriginal.precio_costo_bs ? parseFloat(productoOriginal.precio_costo_bs).toFixed(2) : '';
            document.getElementById('stock_actual').value = productoOriginal.stock_actual || '0';
            document.getElementById('stock_minimo').value = productoOriginal.stock_minimo || '5';
            document.getElementById('categoria_id').value = productoOriginal.categoria_id || '';
            document.getElementById('activo').checked = productoOriginal.activo || true;

            // Restaurar estado del precio fijo
            const usarPrecioFijoCheckbox = document.getElementById('usar_precio_fijo_bs');
            if (usarPrecioFijoCheckbox) {
                usarPrecioFijoCheckbox.checked = productoOriginal.usar_precio_fijo_bs || false;
                togglePrecioFijo();
            }

            // Actualizar contador de caracteres
            const descripcion = document.getElementById('descripcion');
            const charCount = document.getElementById('charCount');
            charCount.textContent = descripcion.value.length;

            // Recalcular y validar
            calcularPreciosYMargenes();
            validarStock();

            showToast('info', 'Valores restaurados correctamente.');
        }
    }

    function confirmarEliminacion() {
        if (confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
            // Redirigir a la función de eliminación
            window.location.href = `eliminar.php?id=<?php echo $producto_id; ?>`;
        }
    }

    function showToast(type, message) {
        // Crear toast dinámicamente
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        const toast = document.createElement('div');

        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Mostrar toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        // Remover toast después de ocultar
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
</script>

<style>
    .char-counter-info {
        color: #6c757d;
    }

    .char-counter-warning {
        color: #dc3545;
        font-weight: bold;
    }

    .precio-bs-required {
        font-weight: bold;
    }
    
    .crear-producto-content {
        padding: 20px;
    }
    
    .card {
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 1rem 1.25rem;
    }
    
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .badge {
        font-size: 0.85em;
        padding: 0.35em 0.65em;
    }
    
    .input-group-text {
        background-color: #e9ecef;
        border: 1px solid #ced4da;
    }
    
    .is-valid {
        border-color: #198754 !important;
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
    }
</style>

<!-- <?php require_once '../layouts/footer.php'; ?> -->