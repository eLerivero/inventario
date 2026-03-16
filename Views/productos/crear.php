<?php
// Views/productos/crear.php
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/CategoriaController.php';
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Config/Database.php';
require_once __DIR__ . '/../../Utils/Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
Auth::requireAccessToProductos();

$database = new Database();
$db = $database->getConnection();
$productoController = new ProductoController($db);
$categoriaController = new CategoriaController($db);
$tasaController = new TasaCambioController($db);

$categorias = $categoriaController->obtenerTodas();
$tasaActual = $tasaController->obtenerTasaActual();

$mensaje = '';
$tipoMensaje = '';

if ($_POST) {
    try {
        $data = [
            'codigo_sku' => trim($_POST['codigo_sku'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'tipo_venta' => $_POST['tipo_venta'] ?? 'unidad',
            'unidad_medida' => $_POST['unidad_medida'] ?? 'kg',
            'precio' => floatval($_POST['precio'] ?? 0),
            'precio_bs' => floatval($_POST['precio_bs'] ?? 0),
            'precio_por_kilo_usd' => floatval($_POST['precio_por_kilo_usd'] ?? 0),
            'precio_por_kilo_bs' => floatval($_POST['precio_por_kilo_bs'] ?? 0),
            'precio_costo' => floatval($_POST['precio_costo'] ?? 0),
            'usar_precio_fijo_bs' => isset($_POST['usar_precio_fijo_bs']) ? true : false,
            'stock_actual' => floatval($_POST['stock_actual'] ?? 0),
            'stock_minimo' => floatval($_POST['stock_minimo'] ?? 5),
            'categoria_id' => $_POST['categoria_id'] ? intval($_POST['categoria_id']) : null,
            'activo' => true
        ];

        $resultado = $productoController->crear($data);

        if ($resultado['success']) {
            $mensaje = 'Producto creado exitosamente';
            $tipoMensaje = 'success';
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

$page_title = "Crear Nuevo Producto";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-plus me-2"></i>Crear Nuevo Producto
        </h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <?php if ($tasaActual['success']): ?>
        <div class="alert alert-info mb-4">
            <i class="fas fa-exchange-alt me-2"></i>
            Tasa Actual: <strong>1 USD = <?php echo number_format($tasaActual['data']['tasa_cambio'], 2); ?> Bs</strong>
        </div>
    <?php endif; ?>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Información del Producto</h5>
        </div>
        <div class="card-body">
            <form method="POST" id="formProducto" onsubmit="return validarFormulario()">
                <!-- SKU y Nombre -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-barcode me-1"></i>Código SKU <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                name="codigo_sku"
                                id="codigo_sku"
                                value="<?php echo htmlspecialchars($_POST['codigo_sku'] ?? ''); ?>"
                                required
                                maxlength="50"
                                placeholder="Ej: CAR-001">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-tag me-1"></i>Nombre <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                name="nombre"
                                id="nombre"
                                value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                required
                                maxlength="255"
                                placeholder="Ej: Lomo de Cerdo">
                        </div>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control"
                        name="descripcion"
                        rows="2"
                        maxlength="500"><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                </div>

                <!-- Tipo de Venta -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">
                            <i class="fas fa-balance-scale me-1"></i>Tipo de Venta <span class="text-danger">*</span>
                        </label>
                        <div class="border p-3 rounded bg-light">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="tipo_venta"
                                            id="tipo_unidad"
                                            value="unidad"
                                            <?php echo (!isset($_POST['tipo_venta']) || $_POST['tipo_venta'] === 'unidad') ? 'checked' : ''; ?>
                                            onchange="toggleTipoVenta()">
                                        <label class="form-check-label" for="tipo_unidad">
                                            <i class="fas fa-cube me-1"></i>Por Unidad
                                            <small class="d-block text-muted">Productos que se venden uno a uno</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="tipo_venta"
                                            id="tipo_peso"
                                            value="peso"
                                            <?php echo (isset($_POST['tipo_venta']) && $_POST['tipo_venta'] === 'peso') ? 'checked' : ''; ?>
                                            onchange="toggleTipoVenta()">
                                        <label class="form-check-label" for="tipo_peso">
                                            <i class="fas fa-weight me-1"></i>Por Peso
                                            <small class="d-block text-muted">Carnicería, charcutería, productos a granel</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN PARA PRODUCTOS POR PESO -->
                <div id="configuracion_peso" style="<?php echo (isset($_POST['tipo_venta']) && $_POST['tipo_venta'] === 'peso') ? 'display: block;' : 'display: none;'; ?>">
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
                                            <option value="kg" <?php echo (($_POST['unidad_medida'] ?? 'kg') === 'kg') ? 'selected' : ''; ?>>Kilogramos (kg)</option>
                                            <option value="g" <?php echo (($_POST['unidad_medida'] ?? '') === 'g') ? 'selected' : ''; ?>>Gramos (g)</option>
                                            <option value="lb" <?php echo (($_POST['unidad_medida'] ?? '') === 'lb') ? 'selected' : ''; ?>>Libras (lb)</option>
                                        </select>
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
                                                value="<?php echo htmlspecialchars($_POST['precio_por_kilo_usd'] ?? ''); ?>"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00">
                                        </div>
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
                                                value="<?php echo htmlspecialchars($_POST['precio_por_kilo_bs'] ?? ''); ?>"
                                                min="0"
                                                step="0.01"
                                                placeholder="0.00"
                                                readonly>
                                        </div>
                                        <small class="text-muted" id="info_precio_kilo_bs">
                                            Se calcula automáticamente con la tasa de cambio
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- STOCK PARA PRODUCTOS POR PESO -->
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-weight me-1"></i>Stock Actual (kg) <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number"
                                                class="form-control"
                                                id="stock_actual_peso"
                                                name="stock_actual"
                                                value="<?php echo htmlspecialchars($_POST['stock_actual'] ?? '0'); ?>"
                                                min="0"
                                                step="0.001"
                                                placeholder="0.000"
                                                required>
                                            <span class="input-group-text">kg</span>
                                        </div>
                                        <div class="form-text">Cantidad disponible en kilogramos (ej: 10.500 kg)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Stock Mínimo (kg)
                                        </label>
                                        <div class="input-group">
                                            <input type="number"
                                                class="form-control"
                                                id="stock_minimo_peso"
                                                name="stock_minimo"
                                                value="<?php echo htmlspecialchars($_POST['stock_minimo'] ?? '2'); ?>"
                                                min="0"
                                                step="0.001"
                                                placeholder="2.000">
                                            <span class="input-group-text">kg</span>
                                        </div>
                                        <div class="form-text">Alerta cuando el stock llegue a este nivel</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Switch de Precio Fijo para productos por peso -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                            type="checkbox"
                                            id="usar_precio_fijo_bs_peso"
                                            name="usar_precio_fijo_bs"
                                            value="1"
                                            <?php echo (isset($_POST['usar_precio_fijo_bs']) && $_POST['usar_precio_fijo_bs']) ? 'checked' : ''; ?>
                                            onchange="togglePrecioFijoPeso()">
                                        <label class="form-check-label" for="usar_precio_fijo_bs_peso">
                                            Usar precio fijo en Bolívares para este producto
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Cuando está activado, el precio por kilo en Bs no cambiará con la tasa de cambio.
                                    </div>
                                </div>
                            </div>

                            <!-- Información para productos por peso -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Producto por peso:</strong> El precio se calcula por kilogramo.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN PARA PRODUCTOS POR UNIDAD -->
                <div id="configuracion_unidad" style="<?php echo (!isset($_POST['tipo_venta']) || $_POST['tipo_venta'] === 'unidad') ? 'display: block;' : 'display: none;'; ?>">
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
                                        <?php echo (isset($_POST['usar_precio_fijo_bs']) && $_POST['usar_precio_fijo_bs']) ? 'checked' : ''; ?>
                                        onchange="togglePrecioFijoUnidad()">
                                    <label class="form-check-label" for="usar_precio_fijo_bs">
                                        Usar precio fijo en Bolívares
                                    </label>
                                </div>
                                <div class="form-text">
                                    Cuando está activado, el precio en Bs no cambiará con la tasa de cambio.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3" id="precio-fijo-container" style="display: none;">
                                <label for="precio_bs" class="form-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Precio Fijo en Bolívares <span class="text-danger precio-bs-required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs</span>
                                    <input type="number"
                                        class="form-control"
                                        id="precio_bs"
                                        name="precio_bs"
                                        value="<?php echo htmlspecialchars($_POST['precio_bs'] ?? ''); ?>"
                                        step="0.01"
                                        min="0"
                                        placeholder="Precio fijo en Bs">
                                </div>
                                <div class="form-text">Este precio no se actualizará automáticamente</div>
                            </div>
                        </div>
                    </div>

                    <!-- Precios en USD -->
                    <div class="row">
                        <div class="col-md-4" id="precio-usd-container">
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
                                        value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4" id="precio-costo-usd-container">
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
                                        value="<?php echo htmlspecialchars($_POST['precio_costo'] ?? ''); ?>"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-calculator me-1"></i>Precio en Bolívares
                                </label>
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">Venta:</small><br>
                                                <strong id="precioBsCalculado" class="text-success">Bs 0.00</strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Costo:</small><br>
                                                <strong id="precioCostoBsCalculado" class="text-muted">Bs 0.00</strong>
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
                                    <i class="fas fa-boxes me-1"></i>Stock Actual (unidades) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                        class="form-control"
                                        id="stock_actual_unidad"
                                        name="stock_actual"
                                        value="<?php echo htmlspecialchars($_POST['stock_actual'] ?? '0'); ?>"
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
                                        value="<?php echo htmlspecialchars($_POST['stock_minimo'] ?? '5'); ?>"
                                        min="0"
                                        step="1"
                                        placeholder="5">
                                    <span class="input-group-text">und</span>
                                </div>
                                <div class="form-text">Alerta cuando el stock llegue a este nivel</div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Margen (solo para productos por unidad) -->
                    <div id="margen_info_unidad">
                        <div class="row mt-3">
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
                    </div>
                </div>

                <!-- Categoría (común para ambos tipos) -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="categoria_id" class="form-label">
                                <i class="fas fa-tags me-1"></i>Categoría
                            </label>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">Seleccionar categoría...</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria['id']); ?>"
                                        <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Guardar Producto
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let tasaActual = <?php echo $tasaActual['success'] ? $tasaActual['data']['tasa_cambio'] : 36.5; ?>;

    function toggleTipoVenta() {
        const tipoVenta = document.querySelector('input[name="tipo_venta"]:checked').value;
        const configPeso = document.getElementById('configuracion_peso');
        const configUnidad = document.getElementById('configuracion_unidad');

        if (tipoVenta === 'peso') {
            configPeso.style.display = 'block';
            configUnidad.style.display = 'none';

            // Limpiar campos de unidad si existen
            const precioInput = document.getElementById('precio');
            const precioBsInput = document.getElementById('precio_bs');
            if (precioInput) precioInput.value = '';
            if (precioBsInput) precioBsInput.value = '';

        } else {
            configPeso.style.display = 'none';
            configUnidad.style.display = 'block';

            // Limpiar campos de peso si existen
            const precioKiloUsd = document.getElementById('precio_por_kilo_usd');
            const precioKiloBs = document.getElementById('precio_por_kilo_bs');
            const unidadMedida = document.getElementById('unidad_medida');
            const precioFijoPeso = document.getElementById('usar_precio_fijo_bs_peso');

            if (precioKiloUsd) precioKiloUsd.value = '';
            if (precioKiloBs) precioKiloBs.value = '';
            if (unidadMedida) unidadMedida.value = 'kg';
            if (precioFijoPeso) precioFijoPeso.checked = false;
        }
    }

    function togglePrecioFijoUnidad() {
        const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs').checked;
        const precioBsContainer = document.getElementById('precio-fijo-container');
        const precioUSDContainer = document.getElementById('precio-usd-container');
        const precioCostoUSDContainer = document.getElementById('precio-costo-usd-container');
        const precioBsInput = document.getElementById('precio_bs');
        const precioRequiredLabel = document.getElementById('precioRequired');

        if (usarPrecioFijo) {
            if (precioBsContainer) precioBsContainer.style.display = 'block';
            if (precioUSDContainer) precioUSDContainer.style.display = 'none';
            if (precioCostoUSDContainer) precioCostoUSDContainer.style.display = 'none';

            if (precioRequiredLabel) {
                precioRequiredLabel.style.display = 'none';
            }

            if (precioBsInput && (!precioBsInput.value || precioBsInput.value == '0')) {
                const precioUSD = parseFloat(document.getElementById('precio')?.value) || 0;
                precioBsInput.value = (precioUSD * tasaActual).toFixed(2);
            }
        } else {
            if (precioBsContainer) precioBsContainer.style.display = 'none';
            if (precioUSDContainer) precioUSDContainer.style.display = 'block';
            if (precioCostoUSDContainer) precioCostoUSDContainer.style.display = 'block';

            if (precioRequiredLabel) {
                precioRequiredLabel.style.display = 'inline';
            }
        }

        calcularPreciosYMargenes();
    }

    function togglePrecioFijoPeso() {
        const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs_peso').checked;
        const precioKiloBs = document.getElementById('precio_por_kilo_bs');
        const infoPrecio = document.getElementById('info_precio_kilo_bs');

        if (usarPrecioFijo) {
            if (precioKiloBs) {
                precioKiloBs.readOnly = false;
            }
            if (infoPrecio) {
                infoPrecio.innerHTML = 'Precio fijo - puedes editarlo manualmente';
            }

            if (precioKiloBs && (!precioKiloBs.value || precioKiloBs.value == '0')) {
                const precioUSD = parseFloat(document.getElementById('precio_por_kilo_usd')?.value) || 0;
                precioKiloBs.value = (precioUSD * tasaActual).toFixed(2);
            }
        } else {
            if (precioKiloBs) {
                precioKiloBs.readOnly = true;
            }
            if (infoPrecio) {
                infoPrecio.innerHTML = 'Se calcula automáticamente con la tasa de cambio';
            }
            calcularPrecioPorKiloBs();
        }
    }

    function calcularPrecioPorKiloBs() {
        const precioUsd = parseFloat(document.getElementById('precio_por_kilo_usd')?.value) || 0;
        const precioFijo = document.getElementById('usar_precio_fijo_bs_peso')?.checked || false;
        const precioKiloBs = document.getElementById('precio_por_kilo_bs');

        if (!precioFijo && precioKiloBs) {
            precioKiloBs.value = (precioUsd * tasaActual).toFixed(2);
        }
    }

    function calcularPreciosYMargenes() {
        const tipoVenta = document.querySelector('input[name="tipo_venta"]:checked').value;

        if (tipoVenta === 'unidad') {
            const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs')?.checked || false;
            const precioUSD = parseFloat(document.getElementById('precio')?.value) || 0;
            const precioCostoUSD = parseFloat(document.getElementById('precio_costo')?.value) || 0;

            let precioBsCalculado;

            if (usarPrecioFijo) {
                precioBsCalculado = parseFloat(document.getElementById('precio_bs')?.value) || 0;
            } else {
                precioBsCalculado = precioUSD * tasaActual;
                const precioBsInput = document.getElementById('precio_bs');
                if (precioBsInput) {
                    precioBsInput.value = precioBsCalculado.toFixed(2);
                }
            }

            const precioBsCalculadoElement = document.getElementById('precioBsCalculado');
            if (precioBsCalculadoElement) {
                precioBsCalculadoElement.textContent = 'Bs ' + (precioBsCalculado || 0).toFixed(2);
            }

            const precioCostoBsCalculado = precioCostoUSD * tasaActual;
            const precioCostoBsCalculadoElement = document.getElementById('precioCostoBsCalculado');
            if (precioCostoBsCalculadoElement) {
                precioCostoBsCalculadoElement.textContent = 'Bs ' + (precioCostoBsCalculado || 0).toFixed(2);
            }

            const gananciaUSD = precioUSD - precioCostoUSD;
            const gananciaBS = precioBsCalculado - precioCostoBsCalculado;
            const margen = precioCostoUSD > 0 ? ((gananciaUSD / precioCostoUSD) * 100) : 0;

            const margenElement = document.getElementById('margenGanancia');
            if (margenElement) {
                margenElement.textContent = margen.toFixed(1) + '%';
            }

            const gananciaUSDElement = document.getElementById('gananciaNetaUSD');
            if (gananciaUSDElement) {
                gananciaUSDElement.textContent = '$' + gananciaUSD.toFixed(2);
            }

            const gananciaBSElement = document.getElementById('gananciaNetaBS');
            if (gananciaBSElement) {
                gananciaBSElement.textContent = 'Bs ' + gananciaBS.toFixed(2);
            }

            if (margenElement) {
                if (margen < 10) {
                    margenElement.className = 'badge bg-danger';
                } else if (margen < 25) {
                    margenElement.className = 'badge bg-warning';
                } else {
                    margenElement.className = 'badge bg-success';
                }
            }
        }
    }

    function validarFormulario() {
        const tipoVenta = document.querySelector('input[name="tipo_venta"]:checked').value;
        const codigoSku = document.getElementById('codigo_sku').value.trim();
        const nombre = document.getElementById('nombre').value.trim();

        if (!codigoSku) {
            alert('El código SKU es obligatorio');
            return false;
        }

        if (!nombre) {
            alert('El nombre del producto es obligatorio');
            return false;
        }

        if (tipoVenta === 'peso') {
            // Validaciones para productos por peso
            const precioKiloUsd = parseFloat(document.getElementById('precio_por_kilo_usd').value);
            if (!precioKiloUsd || precioKiloUsd <= 0) {
                alert('El precio por kilo en USD es obligatorio para productos por peso');
                return false;
            }

            const stockActual = parseFloat(document.getElementById('stock_actual_peso').value);
            if (stockActual < 0) {
                alert('El stock no puede ser negativo');
                return false;
            }

            const precioFijo = document.getElementById('usar_precio_fijo_bs_peso')?.checked || false;
            if (precioFijo) {
                const precioKiloBs = parseFloat(document.getElementById('precio_por_kilo_bs').value);
                if (!precioKiloBs || precioKiloBs <= 0) {
                    alert('El precio por kilo en Bs es obligatorio cuando usa precio fijo');
                    return false;
                }
            }
        } else {
            // Validaciones para productos por unidad
            const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs')?.checked || false;

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
        }

        return true;
    }

    // Inicialización al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        // Event listeners para unidad
        const precioInput = document.getElementById('precio');
        const precioCosto = document.getElementById('precio_costo');
        const usarPrecioFijoUnidad = document.getElementById('usar_precio_fijo_bs');

        // Event listeners para peso
        const precioKiloUsd = document.getElementById('precio_por_kilo_usd');
        const usarPrecioFijoPeso = document.getElementById('usar_precio_fijo_bs_peso');

        if (precioInput) precioInput.addEventListener('input', calcularPreciosYMargenes);
        if (precioCosto) precioCosto.addEventListener('input', calcularPreciosYMargenes);
        if (usarPrecioFijoUnidad) usarPrecioFijoUnidad.addEventListener('change', calcularPreciosYMargenes);
        if (precioKiloUsd) precioKiloUsd.addEventListener('input', calcularPrecioPorKiloBs);
        if (usarPrecioFijoPeso) usarPrecioFijoPeso.addEventListener('change', togglePrecioFijoPeso);

        const tipoVentaRadios = document.querySelectorAll('input[name="tipo_venta"]');
        tipoVentaRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'peso') {
                    const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs');
                    if (usarPrecioFijo) {
                        usarPrecioFijo.checked = false;
                    }
                    togglePrecioFijoUnidad();
                }
                calcularPreciosYMargenes();
            });
        });

        // Inicializar estado
        toggleTipoVenta();
        if (document.getElementById('usar_precio_fijo_bs')?.checked) {
            togglePrecioFijoUnidad();
        }
        if (document.getElementById('usar_precio_fijo_bs_peso')?.checked) {
            togglePrecioFijoPeso();
        }
        calcularPreciosYMargenes();
    });
</script>

<style>
    .badge {
        font-size: 0.85em;
    }

    .card {
        margin-bottom: 20px;
    }

    .form-text {
        font-size: 0.85em;
    }

    .input-group-text {
        background-color: #e9ecef;
    }
</style>

<!-- <?php require_once '../layouts/footer.php'; ?> -->