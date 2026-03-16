<?php
// Views/productos/editar.php
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/CategoriaController.php';
require_once '../../Controllers/TasaCambioController.php';
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

$categorias = $categoriaController->obtenerTodas();
$tasaActual = $tasaController->obtenerTasaActual();

// OBTENER DATOS DEL PRODUCTO A EDITAR
$mensaje = '';
$tipoMensaje = '';
$producto = null;
$stockOriginal = 0;

$producto_id = $_GET['id'] ?? null;

if (!$producto_id) {
    $mensaje = "ID de producto no especificado";
    $tipoMensaje = 'danger';
} else {
    $result = $productoController->obtener($producto_id);
    if ($result['success']) {
        $producto = $result['data'];
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
            'precio_por_kilo_usd' => floatval($_POST['precio_por_kilo_usd'] ?? 0),
            'precio_por_kilo_bs' => floatval($_POST['precio_por_kilo_bs'] ?? 0),
            'precio_costo' => floatval($_POST['precio_costo'] ?? 0),
            'precio_costo_bs' => floatval($_POST['precio_costo_bs'] ?? 0),
            'usar_precio_fijo_bs' => isset($_POST['usar_precio_fijo_bs']) ? true : false,
            'stock_actual' => floatval($_POST['stock_actual'] ?? 0),
            'stock_minimo' => floatval($_POST['stock_minimo'] ?? 0),
            'categoria_id' => $_POST['categoria_id'] ? intval($_POST['categoria_id']) : null,
            'activo' => isset($_POST['activo']) ? true : false
        ];

        // Validaciones básicas
        if (empty($data['nombre'])) {
            throw new Exception("El nombre del producto es obligatorio");
        }

        if ($data['stock_actual'] < 0) {
            throw new Exception("El stock no puede ser negativo");
        }

        // Validaciones según tipo de venta
        $tipo_venta = $producto['tipo_venta'] ?? 'unidad';

        if ($tipo_venta === 'peso') {
            if (isset($data['precio_por_kilo_usd']) && $data['precio_por_kilo_usd'] <= 0) {
                throw new Exception("El precio por kilo en USD debe ser mayor a 0");
            }

            if ($data['usar_precio_fijo_bs'] && isset($data['precio_por_kilo_bs']) && $data['precio_por_kilo_bs'] <= 0) {
                throw new Exception("Para precio fijo, el precio por kilo en Bs debe ser mayor a 0");
            }
        } else {
            if ($data['usar_precio_fijo_bs']) {
                if ($data['precio_bs'] <= 0) {
                    throw new Exception("Para precio fijo, el precio en Bs debe ser mayor a 0");
                }
            } else {
                if ($data['precio'] <= 0) {
                    throw new Exception("El precio en USD debe ser mayor a 0");
                }
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
                $stockOriginal = $producto['stock_actual'];
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

    <?php if ($producto):
        $tipo_venta = $producto['tipo_venta'] ?? 'unidad';
        $unidad_medida = $producto['unidad_medida'] ?? 'kg';
        $es_precio_fijo = isset($producto['usar_precio_fijo_bs']) && $producto['usar_precio_fijo_bs'] == 1;
    ?>
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
                        <p class="mb-2">
                            <?php if ($tipo_venta === 'peso'): ?>
                                <?php echo number_format($producto['stock_actual'], 2); ?> <?php echo $unidad_medida; ?>
                            <?php else: ?>
                                <?php echo intval($producto['stock_actual']); ?> unidades
                            <?php endif; ?>
                        </p>
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
                <div class="row mt-2">
                    <div class="col-md-12">
                        <strong><i class="fas fa-balance-scale me-1"></i>Tipo de Venta:</strong>
                        <?php if ($tipo_venta === 'peso'): ?>
                            <span class="badge bg-info">Por Peso (<?php echo $unidad_medida; ?>)</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Por Unidad</span>
                        <?php endif; ?>

                        <?php if ($es_precio_fijo): ?>
                            <span class="badge bg-warning ms-2"><i class="fas fa-lock me-1"></i>Precio Fijo en Bs</span>
                        <?php endif; ?>
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
                                    maxlength="255">
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
                            maxlength="500"><?php echo htmlspecialchars($_POST['descripcion'] ?? $producto['descripcion']); ?></textarea>
                        <div class="form-text">
                            Máximo 500 caracteres.
                            <span id="charCount" class="char-counter-info"><?php echo strlen($_POST['descripcion'] ?? $producto['descripcion']); ?></span>/500
                        </div>
                    </div>

                    <?php if ($tipo_venta === 'peso'): ?>
                        <!-- SECCIÓN PARA PRODUCTOS POR PESO -->
                        <div class="card mb-3 border-info">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-weight me-2"></i>Configuración para Venta por Peso
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-ruler me-1"></i>Unidad de Medida
                                            </label>
                                            <select class="form-select" name="unidad_medida" id="unidad_medida">
                                                <option value="kg" <?php echo (($producto['unidad_medida'] ?? 'kg') === 'kg') ? 'selected' : ''; ?>>Kilogramos (kg)</option>
                                                <option value="g" <?php echo (($producto['unidad_medida'] ?? '') === 'g') ? 'selected' : ''; ?>>Gramos (g)</option>
                                                <option value="lb" <?php echo (($producto['unidad_medida'] ?? '') === 'lb') ? 'selected' : ''; ?>>Libras (lb)</option>
                                            </select>
                                            <div class="form-text">Unidad base para mostrar en la interfaz</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-dollar-sign me-1"></i>Precio por Kilo (USD) <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number"
                                                    class="form-control"
                                                    name="precio_por_kilo_usd"
                                                    id="precio_por_kilo_usd"
                                                    value="<?php echo number_format($_POST['precio_por_kilo_usd'] ?? ($producto['precio_por_kilo_usd'] ?? $producto['precio']), 2, '.', ''); ?>"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="0.00"
                                                    required
                                                    onchange="calcularPrecioPorKiloBs()">
                                            </div>
                                            <div class="form-text">Precio de venta por kilogramo en USD</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-money-bill-wave me-1"></i>Precio por Kilo (Bs)
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">Bs</span>
                                                <input type="number"
                                                    class="form-control"
                                                    name="precio_por_kilo_bs"
                                                    id="precio_por_kilo_bs"
                                                    value="<?php echo number_format($_POST['precio_por_kilo_bs'] ?? ($producto['precio_por_kilo_bs'] ?? $producto['precio_bs']), 2, '.', ''); ?>"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="0.00"
                                                    <?php echo $es_precio_fijo ? '' : 'readonly'; ?>>
                                            </div>
                                            <small class="text-muted" id="info_precio_kilo_bs">
                                                <?php if ($es_precio_fijo): ?>
                                                    Precio fijo - puedes editarlo manualmente
                                                <?php else: ?>
                                                    Se calcula automáticamente con la tasa de cambio
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- STOCK PARA PRODUCTOS POR PESO -->
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-weight me-1"></i>Stock Actual (<?php echo $unidad_medida; ?>) *
                                            </label>
                                            <div class="input-group">
                                                <input type="number"
                                                    class="form-control"
                                                    id="stock_actual_peso"
                                                    name="stock_actual"
                                                    value="<?php echo number_format($_POST['stock_actual'] ?? $producto['stock_actual'], 3, '.', ''); ?>"
                                                    min="0"
                                                    step="0.001"
                                                    placeholder="0.000"
                                                    required>
                                                <span class="input-group-text"><?php echo $unidad_medida; ?></span>
                                            </div>
                                            <div class="form-text">Cantidad disponible en <?php echo $unidad_medida; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Stock Mínimo (<?php echo $unidad_medida; ?>)
                                            </label>
                                            <div class="input-group">
                                                <input type="number"
                                                    class="form-control"
                                                    id="stock_minimo_peso"
                                                    name="stock_minimo"
                                                    value="<?php echo number_format($_POST['stock_minimo'] ?? $producto['stock_minimo'], 3, '.', ''); ?>"
                                                    min="0"
                                                    step="0.001"
                                                    placeholder="2.000">
                                                <span class="input-group-text"><?php echo $unidad_medida; ?></span>
                                            </div>
                                            <div class="form-text">Alerta cuando el stock llegue a este nivel</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Switch de Precio Fijo -->
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                type="checkbox"
                                                id="usar_precio_fijo_bs"
                                                name="usar_precio_fijo_bs"
                                                value="1"
                                                <?php echo $es_precio_fijo ? 'checked' : ''; ?>
                                                onchange="togglePrecioFijoPeso()">
                                            <label class="form-check-label" for="usar_precio_fijo_bs">
                                                Usar precio fijo en Bolívares para este producto
                                            </label>
                                        </div>
                                        <div class="form-text">
                                            Cuando está activado, el precio por kilo en Bs no cambiará con la tasa de cambio.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- SECCIÓN PARA PRODUCTOS POR UNIDAD (original) -->
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
                                            <?php echo $es_precio_fijo ? 'checked' : ''; ?>
                                            onchange="togglePrecioFijoUnidad()">
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
                                <div class="mb-3" id="precio-fijo-container" style="<?php echo $es_precio_fijo ? 'display: block;' : 'display: none;'; ?>">
                                    <label for="precio_bs" class="form-label">
                                        <i class="fas fa-money-bill-wave me-1"></i>Precio Fijo en Bolívares <span class="text-danger precio-bs-required">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">Bs</span>
                                        <input type="number"
                                            class="form-control"
                                            id="precio_bs"
                                            name="precio_bs"
                                            value="<?php echo number_format($_POST['precio_bs'] ?? $producto['precio_bs'], 2, '.', ''); ?>"
                                            step="0.01"
                                            min="0"
                                            placeholder="Precio fijo en Bs"
                                            onchange="calcularPreciosYMargenes()">
                                    </div>
                                    <div class="form-text">Este precio no se actualizará automáticamente con la tasa de cambio</div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Precios -->
                        <div class="row">
                            <!-- Campo Precio de Venta (USD) -->
                            <div class="col-md-4" id="precio-usd-container" style="<?php echo $es_precio_fijo ? 'display: none;' : 'display: block;'; ?>">
                                <div class="mb-3">
                                    <label for="precio" class="form-label">
                                        <i class="fas fa-dollar-sign me-1"></i>Precio de Venta (USD) <span id="precioRequired" class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number"
                                            class="form-control precio-usd"
                                            id="precio"
                                            name="precio"
                                            value="<?php echo number_format($_POST['precio'] ?? $producto['precio'], 2, '.', ''); ?>"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                            onchange="calcularPreciosYMargenes()">
                                    </div>
                                    <div class="form-text">Precio de venta al público en USD</div>
                                </div>
                            </div>

                            <!-- Campo Precio de Costo (USD) -->
                            <div class="col-md-4" id="precio-costo-usd-container" style="<?php echo $es_precio_fijo ? 'display: none;' : 'display: block;'; ?>">
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
                            <div class="col-md-4" id="precio-costo-bs-container" style="<?php echo $es_precio_fijo ? 'display: block;' : 'display: none;'; ?>">
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
                                                    <strong id="precioBsCalculado" class="text-success">Bs <?php echo number_format($producto['precio_bs'], 2); ?></strong>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Costo:</small><br>
                                                    <strong id="precioCostoBsCalculado" class="text-muted">Bs <?php echo number_format($producto['precio_costo_bs'], 2); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STOCK PARA PRODUCTOS POR UNIDAD -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stock_actual_unidad" class="form-label">
                                        <i class="fas fa-boxes me-1"></i>Stock Actual (unidades) *
                                    </label>
                                    <div class="input-group">
                                        <input type="number"
                                            class="form-control"
                                            id="stock_actual_unidad"
                                            name="stock_actual"
                                            value="<?php echo intval($_POST['stock_actual'] ?? $producto['stock_actual']); ?>"
                                            min="0"
                                            step="1"
                                            placeholder="0"
                                            required>
                                        <span class="input-group-text">und</span>
                                    </div>
                                    <div class="form-text">Cantidad disponible en unidades</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stock_minimo_unidad" class="form-label">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Stock Mínimo
                                    </label>
                                    <div class="input-group">
                                        <input type="number"
                                            class="form-control"
                                            id="stock_minimo_unidad"
                                            name="stock_minimo"
                                            value="<?php echo intval($_POST['stock_minimo'] ?? $producto['stock_minimo']); ?>"
                                            min="0"
                                            step="1"
                                            placeholder="5">
                                        <span class="input-group-text">und</span>
                                    </div>
                                    <div class="form-text">Alerta cuando el stock llegue a este nivel</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Categoría (común para ambos tipos) -->
                    <div class="row mt-3">
                        <div class="col-md-6">
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

                    <!-- Información de Margen (solo para productos por unidad) -->
                    <?php if ($tipo_venta !== 'peso'): ?>
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
                    <?php else: ?>
                        <!-- Información para productos por peso -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Producto por peso:</strong> El margen se calcula por kilogramo en el módulo de ventas.
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Actualizar Producto
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    let tasaActual = <?php echo $tasaActual['success'] ? $tasaActual['data']['tasa_cambio'] : 36.5; ?>;
    let tipoVenta = '<?php echo $tipo_venta ?? 'unidad'; ?>';
    let esPrecioFijo = <?php echo ($es_precio_fijo ?? false) ? 'true' : 'false'; ?>;

    // Función para toggle de precio fijo en productos por peso
    function togglePrecioFijoPeso() {
        const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs').checked;
        const precioKiloBs = document.getElementById('precio_por_kilo_bs');
        const infoPrecio = document.getElementById('info_precio_kilo_bs');

        if (usarPrecioFijo) {
            precioKiloBs.readOnly = false;
            infoPrecio.innerHTML = 'Precio fijo - puedes editarlo manualmente';

            if (!precioKiloBs.value || precioKiloBs.value == '0' || precioKiloBs.value == '0.00') {
                const precioUSD = parseFloat(document.getElementById('precio_por_kilo_usd').value) || 0;
                precioKiloBs.value = (precioUSD * tasaActual).toFixed(2);
            }
        } else {
            precioKiloBs.readOnly = true;
            infoPrecio.innerHTML = 'Se calcula automáticamente con la tasa de cambio';
            calcularPrecioPorKiloBs();
        }
    }

    // Función para toggle de precio fijo en productos por unidad
    function togglePrecioFijoUnidad() {
        const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs').checked;
        const precioBsContainer = document.getElementById('precio-fijo-container');
        const precioUSDContainer = document.getElementById('precio-usd-container');
        const precioCostoUSDContainer = document.getElementById('precio-costo-usd-container');
        const precioCostoBsContainer = document.getElementById('precio-costo-bs-container');
        const precioBsInput = document.getElementById('precio_bs');
        const precioRequiredLabel = document.getElementById('precioRequired');

        if (usarPrecioFijo) {
            precioBsContainer.style.display = 'block';
            precioUSDContainer.style.display = 'none';
            precioCostoUSDContainer.style.display = 'none';
            if (precioCostoBsContainer) precioCostoBsContainer.style.display = 'block';

            if (precioRequiredLabel) {
                precioRequiredLabel.style.display = 'none';
            }

            if (!precioBsInput.value || precioBsInput.value == '0' || precioBsInput.value == '0.00') {
                const precioUSD = parseFloat(document.getElementById('precio').value) || 0;
                precioBsInput.value = (precioUSD * tasaActual).toFixed(2);
            }
        } else {
            precioBsContainer.style.display = 'none';
            precioUSDContainer.style.display = 'block';
            precioCostoUSDContainer.style.display = 'block';
            if (precioCostoBsContainer) precioCostoBsContainer.style.display = 'none';

            if (precioRequiredLabel) {
                precioRequiredLabel.style.display = 'inline';
            }
        }

        calcularPreciosYMargenes();
    }

    function calcularPrecioPorKiloBs() {
        const precioUsd = parseFloat(document.getElementById('precio_por_kilo_usd')?.value) || 0;
        const precioFijo = document.getElementById('usar_precio_fijo_bs')?.checked || false;
        const precioKiloBs = document.getElementById('precio_por_kilo_bs');

        if (!precioFijo && precioKiloBs) {
            precioKiloBs.value = (precioUsd * tasaActual).toFixed(2);
        }
    }

    function calcularPreciosYMargenes() {
        if (tipoVenta === 'unidad') {
            const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs')?.checked || false;
            const precioUSD = parseFloat(document.getElementById('precio')?.value) || 0;
            const precioCostoUSD = parseFloat(document.getElementById('precio_costo')?.value) || 0;

            let precioBsCalculado;
            let precioCostoBsCalculado;

            if (usarPrecioFijo) {
                precioBsCalculado = parseFloat(document.getElementById('precio_bs')?.value) || 0;
                precioCostoBsCalculado = parseFloat(document.getElementById('precio_costo_bs')?.value) || 0;
            } else {
                precioBsCalculado = precioUSD * tasaActual;
                precioCostoBsCalculado = precioCostoUSD * tasaActual;

                const precioBsInput = document.getElementById('precio_bs');
                if (precioBsInput) {
                    precioBsInput.value = precioBsCalculado.toFixed(2);
                }
            }

            document.getElementById('precioBsCalculado').textContent = 'Bs ' + (precioBsCalculado || 0).toFixed(2);
            document.getElementById('precioCostoBsCalculado').textContent = 'Bs ' + (precioCostoBsCalculado || 0).toFixed(2);

            const gananciaUSD = precioUSD - precioCostoUSD;
            const gananciaBS = precioBsCalculado - precioCostoBsCalculado;
            const margen = precioCostoUSD > 0 ? ((gananciaUSD / precioCostoUSD) * 100) : 0;

            document.getElementById('margenGanancia').textContent = margen.toFixed(1) + '%';
            document.getElementById('gananciaNetaUSD').textContent = '$' + gananciaUSD.toFixed(2);
            document.getElementById('gananciaNetaBS').textContent = 'Bs ' + gananciaBS.toFixed(2);

            const margenElement = document.getElementById('margenGanancia');
            if (margen < 10) {
                margenElement.className = 'badge bg-danger';
            } else if (margen < 25) {
                margenElement.className = 'badge bg-warning';
            } else {
                margenElement.className = 'badge bg-success';
            }
        }
    }

    function validarFormulario() {
        const nombre = document.querySelector('input[name="nombre"]').value.trim();

        if (!nombre) {
            alert('El nombre del producto es obligatorio');
            return false;
        }

        <?php if ($tipo_venta === 'peso'): ?>
            const precioKiloUsd = parseFloat(document.getElementById('precio_por_kilo_usd').value);
            if (!precioKiloUsd || precioKiloUsd <= 0) {
                alert('El precio por kilo en USD es obligatorio');
                return false;
            }

            const stockActual = parseFloat(document.getElementById('stock_actual_peso').value);
            if (stockActual < 0) {
                alert('El stock no puede ser negativo');
                return false;
            }

            const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs').checked;
            if (usarPrecioFijo) {
                const precioKiloBs = parseFloat(document.getElementById('precio_por_kilo_bs').value);
                if (!precioKiloBs || precioKiloBs <= 0) {
                    alert('El precio por kilo en Bs es obligatorio cuando usa precio fijo');
                    return false;
                }
            }
        <?php else: ?>
            const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs').checked;

            if (!usarPrecioFijo) {
                const precio = parseFloat(document.getElementById('precio').value);
                if (!precio || precio <= 0) {
                    alert('El precio en USD debe ser mayor a 0 para productos sin precio fijo');
                    return false;
                }
            } else {
                const precioBs = parseFloat(document.getElementById('precio_bs').value);
                if (!precioBs || precioBs <= 0) {
                    alert('El precio en Bs debe ser mayor a 0 para productos con precio fijo');
                    return false;
                }
            }

            const stockActual = parseInt(document.getElementById('stock_actual_unidad').value);
            if (stockActual < 0) {
                alert('El stock no puede ser negativo');
                return false;
            }
        <?php endif; ?>

        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const descripcion = document.getElementById('descripcion');
        const charCount = document.getElementById('charCount');

        if (descripcion) {
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
        }

        <?php if ($tipo_venta === 'peso'): ?>
            const precioKiloUsd = document.getElementById('precio_por_kilo_usd');
            if (precioKiloUsd) {
                precioKiloUsd.addEventListener('input', calcularPrecioPorKiloBs);
            }
        <?php else: ?>
            const precioInput = document.getElementById('precio');
            const precioCosto = document.getElementById('precio_costo');
            if (precioInput) precioInput.addEventListener('input', calcularPreciosYMargenes);
            if (precioCosto) precioCosto.addEventListener('input', calcularPreciosYMargenes);
        <?php endif; ?>

        <?php if ($tipo_venta === 'peso'): ?>
            if (esPrecioFijo) {
                togglePrecioFijoPeso();
            }
        <?php else: ?>
            if (esPrecioFijo) {
                togglePrecioFijoUnidad();
            }
            calcularPreciosYMargenes();
        <?php endif; ?>
    });
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
    }

    .is-valid {
        border-color: #198754 !important;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }
</style>

<!-- <?php require_once '../layouts/footer.php'; ?> -->