<?php
// Views/productos/editar.php
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/CategoriaController.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$productoController = new ProductoController($db);
$categoriaController = new CategoriaController($db);

$categorias = $categoriaController->obtenerTodas();

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
    } else {
        $mensaje = $result['message'];
        $tipoMensaje = 'danger';
    }
}

// PROCESAR FORMULARIO DE ACTUALIZACIÓN
if ($_POST && $producto) {
    try {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => floatval($_POST['precio'] ?? 0),
            'precio_bs' => floatval($_POST['precio_bs'] ?? 0),
            'precio_costo' => floatval($_POST['precio_costo'] ?? 0),
            'usar_precio_fijo_bs' => isset($_POST['usar_precio_fijo_bs']) ? true : false,
            'stock_minimo' => intval($_POST['stock_minimo'] ?? 0),
            'categoria_id' => $_POST['categoria_id'] ?? null,
            'activo' => isset($_POST['activo']) ? true : false
        ];

        $resultado = $productoController->actualizar($producto_id, $data);

        if ($resultado['success']) {
            $mensaje = 'Producto actualizado exitosamente';
            $tipoMensaje = 'success';

            // Actualizar los datos del producto en la variable
            $result = $productoController->obtener($producto_id);
            if ($result['success']) {
                $producto = $result['data'];
            }

            // Redirigir después de 2 segundos
            echo '<script>setTimeout(() => { window.location.href = "index.php"; }, 2000);</script>';
        } else {
            $mensaje = $resultado['message'];
            $tipoMensaje = 'danger';
        }
    } catch (Exception $e) {
        $mensaje = "Error inesperado: " . $e->getMessage();
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
                        <p class="mb-2">
                            <span class="badge <?php echo $producto['stock_actual'] <= $producto['stock_minimo'] ? 'bg-warning' : 'bg-success'; ?>">
                                <?php echo htmlspecialchars($producto['stock_actual']); ?>
                            </span>
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

                    <!-- Sección de Precios -->
                    <div class="row">
                        <div class="col-md-4">
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
                                        value="<?php echo htmlspecialchars($_POST['precio'] ?? $producto['precio']); ?>"
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
                        <div class="col-md-4">
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
                                        value="<?php echo htmlspecialchars($_POST['precio_costo'] ?? $producto['precio_costo']); ?>"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        onchange="calcularPreciosYMargenes()">
                                </div>
                                <div class="form-text">Precio de compra al proveedor en USD</div>
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
                                    Cuando está activado, el precio en Bs no cambiará con la tasa de cambio.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3" id="precio-fijo-container" style="<?php echo ($producto['usar_precio_fijo_bs'] ? 'display: block;' : 'display: none;'); ?>">
                                <label for="precio_bs" class="form-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Precio Fijo en Bolívares <span class="text-danger precio-bs-required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs</span>
                                    <input type="number"
                                        class="form-control"
                                        id="precio_bs"
                                        name="precio_bs"
                                        value="<?php echo htmlspecialchars($_POST['precio_bs'] ?? $producto['precio_bs']); ?>"
                                        step="0.01"
                                        min="0"
                                        placeholder="Precio fijo en Bs">
                                </div>
                                <div class="form-text">Este precio no se actualizará automáticamente con la tasa de cambio</div>
                            </div>
                        </div>
                    </div>

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
                                <label class="form-label">
                                    <i class="fas fa-boxes me-1"></i>Stock Actual
                                </label>
                                <input type="text"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($producto['stock_actual']); ?>"
                                    readonly
                                    disabled>
                                <div class="form-text">
                                    Para modificar el stock, usa la opción
                                    <a href="../historial-stock/index.php?producto_id=<?php echo $producto_id; ?>" class="text-decoration-none">
                                        Ajustar Stock
                                    </a>
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-chart-line me-1"></i>Margen de Ganancia
                                </label>
                                <div class="form-control-plaintext">
                                    <span id="margenGanancia" class="badge bg-info">0%</span>
                                    <div class="form-text">
                                        <span id="gananciaNeta">$0.00</span> de ganancia por unidad
                                    </div>
                                </div>
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
    let tasaActual = 36.5; // Valor por defecto
    let productoOriginal = <?php echo json_encode($producto ?? []); ?>;

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

        // Configurar toggle de precio fijo
        const usarPrecioFijoCheckbox = document.getElementById('usar_precio_fijo_bs');
        if (usarPrecioFijoCheckbox) {
            usarPrecioFijoCheckbox.addEventListener('change', togglePrecioFijo);
        }

        // Validación del formulario
        const form = document.getElementById('formProducto');
        const nombreInput = document.getElementById('nombre');
        const precioInput = document.getElementById('precio');

        form.addEventListener('submit', function(e) {
            const nombre = nombreInput.value.trim();
            const precioVal = parseFloat(precioInput.value);
            const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs')?.checked || false;
            const precioBsInput = document.getElementById('precio_bs');

            // Validación básica
            let isValid = true;

            if (!nombre) {
                e.preventDefault();
                nombreInput.classList.add('is-invalid');
                isValid = false;
            }

            // Validación según tipo de precio
            if (usarPrecioFijo) {
                const precioBsVal = parseFloat(precioBsInput.value);
                if (!precioBsVal || precioBsVal <= 0) {
                    e.preventDefault();
                    precioBsInput.classList.add('is-invalid');
                    isValid = false;
                    showToast('error', 'Debe ingresar un precio válido en bolívares para precio fijo.');
                }
                // Para precio fijo, el precio USD no es obligatorio
            } else {
                // Para precio NO fijo, el precio USD es obligatorio
                if (!precioVal || precioVal <= 0) {
                    e.preventDefault();
                    precioInput.classList.add('is-invalid');
                    isValid = false;
                    showToast('error', 'El precio en USD debe ser mayor a 0 para productos sin precio fijo.');
                }
            }

            if (!isValid) {
                showToast('error', 'Por favor, completa todos los campos obligatorios correctamente.');
                return false;
            }

            // Mostrar indicador de carga
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...';

            return true;
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
    });

    // Calcular precios en Bolívares y márgenes
    function calcularPreciosYMargenes() {
        const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs')?.checked || false;
        const precioBsInput = document.getElementById('precio_bs');

        let precioBsCalculado, precioCostoBsCalculado;
        let precioUSD, precioCostoUSD;

        if (usarPrecioFijo) {
            // Para precio fijo, usar el valor del campo precio_bs
            precioBsCalculado = parseFloat(precioBsInput.value) || 0;
            precioUSD = parseFloat(document.getElementById('precio').value) || 0;
            precioCostoUSD = parseFloat(document.getElementById('precio_costo').value) || 0;
            precioCostoBsCalculado = precioCostoUSD * tasaActual;
        } else {
            // Para precio NO fijo, calcular basado en tasa
            precioUSD = parseFloat(document.getElementById('precio').value) || 0;
            precioCostoUSD = parseFloat(document.getElementById('precio_costo').value) || 0;
            precioBsCalculado = precioUSD * tasaActual;
            precioCostoBsCalculado = precioCostoUSD * tasaActual;
        }

        // Actualizar campos de visualización
        document.getElementById('precioBsCalculado').textContent = 'Bs ' + precioBsCalculado.toFixed(2);
        document.getElementById('precioCostoBsCalculado').textContent = 'Bs ' + precioCostoBsCalculado.toFixed(2);

        // Calcular márgenes
        const gananciaUSD = precioUSD - precioCostoUSD;
        const margen = precioCostoUSD > 0 ? ((gananciaUSD / precioCostoUSD) * 100) : 0;

        document.getElementById('margenGanancia').textContent = margen.toFixed(2) + '%';
        document.getElementById('gananciaNeta').textContent = '$' + gananciaUSD.toFixed(2);

        // Cambiar color según el margen
        const margenElement = document.getElementById('margenGanancia');
        if (margen < 10) {
            margenElement.className = 'badge bg-danger';
        } else if (margen < 25) {
            margenElement.className = 'badge bg-warning';
        } else {
            margenElement.className = 'badge bg-success';
        }
    }

    function togglePrecioFijo() {
        const usarPrecioFijo = document.getElementById('usar_precio_fijo_bs').checked;
        const precioBsContainer = document.getElementById('precio-fijo-container');
        const precioBsInput = document.getElementById('precio_bs');
        const precioUSDInput = document.getElementById('precio');
        const precioCostoUSDInput = document.getElementById('precio_costo');
        const precioRequiredLabel = document.getElementById('precioRequired');

        if (usarPrecioFijo) {
            precioBsContainer.style.display = 'block';

            // Para precio fijo, los campos en USD no son obligatorios
            precioUSDInput.required = false;
            precioUSDInput.classList.remove('is-valid', 'is-invalid');
            precioCostoUSDInput.required = false;
            precioCostoUSDInput.classList.remove('is-valid', 'is-invalid');

            // Ocultar asterisco rojo en precio USD
            if (precioRequiredLabel) {
                precioRequiredLabel.style.display = 'none';
            }
        } else {
            precioBsContainer.style.display = 'none';

            // Para precio NO fijo, el precio USD es obligatorio
            precioUSDInput.required = true;
            precioCostoUSDInput.required = false; // Costo sigue siendo opcional

            // Mostrar asterisco rojo en precio USD
            if (precioRequiredLabel) {
                precioRequiredLabel.style.display = 'inline';
            }
        }

        // Recalcular márgenes
        calcularPreciosYMargenes();
    }

    function limpiarFormulario() {
        if (confirm('¿Estás seguro de que deseas restaurar los valores originales? Se perderán los cambios no guardados.')) {
            // Restaurar valores originales del producto
            document.getElementById('nombre').value = productoOriginal.nombre || '';
            document.getElementById('descripcion').value = productoOriginal.descripcion || '';
            document.getElementById('precio').value = productoOriginal.precio || '';
            document.getElementById('precio_bs').value = productoOriginal.precio_bs || '';
            document.getElementById('precio_costo').value = productoOriginal.precio_costo || '';
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

            // Recalcular
            calcularPreciosYMargenes();

            showToast('info', 'Valores restaurados correctamente.');
        }
    }

    function confirmarEliminacion() {
        if (confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
            // Redirigir a la función de eliminación
            window.location.href = `index.php?eliminar=<?php echo $producto_id; ?>`;
        }
    }

    function showToast(type, message) {
        // Crear toast dinámicamente
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        const toast = document.createElement('div');

        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
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
</style>

<!-- <?php require_once '../layouts/footer.php'; ?> -->