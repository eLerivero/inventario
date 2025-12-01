<?php
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/CategoriaController.php';
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Helpers/TasaCambioHelper.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$productoController = new ProductoController($db);
$categoriaController = new CategoriaController($db);
$tasaController = new TasaCambioController($db);

$categorias = $categoriaController->obtenerTodas();
$tasaActual = TasaCambioHelper::obtenerTasaActual($db);
$tasaInfo = $tasaController->obtenerTasaActual();

// 2. PROCESAR FORMULARIO
$mensaje = '';
$tipoMensaje = '';

if ($_POST) {
    try {
        $data = [
            'codigo_sku' => trim($_POST['codigo_sku'] ?? ''),
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => floatval($_POST['precio'] ?? 0),
            'precio_costo' => floatval($_POST['precio_costo'] ?? 0),
            'stock_actual' => intval($_POST['stock_actual'] ?? 0),
            'stock_minimo' => intval($_POST['stock_minimo'] ?? 0),
            'categoria_id' => $_POST['categoria_id'] ?? null
        ];

        $resultado = $productoController->crear($data);

        if ($resultado['success']) {
            $mensaje = 'Producto creado exitosamente';
            $tipoMensaje = 'success';
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

// 3. DEFINIR TÍTULO Y INCLUIR HEADER
$page_title = "Crear Nuevo Producto";
require_once '../layouts/header.php';
?>

<!-- 4. CONTENIDO PRINCIPAL DE LA VISTA -->
<div class="content-wrapper crear-producto-content">

    <!-- Header de la página -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-plus me-2"></i>Crear Nuevo Producto
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Listado
            </a>
        </div>
    </div>

    <!-- Información de Tasa de Cambio -->
    <?php if ($tasaInfo['success']): ?>
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exchange-alt me-3 fa-lg"></i>
                <div>
                    <strong>Tasa de Cambio Actual:</strong> 1 USD = <?php echo number_format($tasaInfo['data']['tasa_cambio'], 2); ?> Bs
                    <br>
                    <small class="text-muted">Los precios en Bolívares se calcularán automáticamente</small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Alertas del sistema -->
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

    <!-- Formulario de creación -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-edit me-2"></i>
                Información del Producto
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" id="formProducto" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="codigo_sku" class="form-label">
                                <i class="fas fa-barcode me-1"></i>Código SKU *
                            </label>
                            <input type="text"
                                class="form-control <?php echo isset($_POST['codigo_sku']) && empty($_POST['codigo_sku']) ? 'is-invalid' : ''; ?>"
                                id="codigo_sku"
                                name="codigo_sku"
                                value="<?php echo htmlspecialchars($_POST['codigo_sku'] ?? ''); ?>"
                                required
                                maxlength="50"
                                placeholder="Ej: PROD-001">
                            <?php if (isset($_POST['codigo_sku']) && empty($_POST['codigo_sku'])): ?>
                                <div class="invalid-feedback">
                                    El código SKU es obligatorio.
                                </div>
                            <?php endif; ?>
                            <div class="form-text">Código único de identificación del producto</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nombre del Producto *
                            </label>
                            <input type="text"
                                class="form-control <?php echo isset($_POST['nombre']) && empty($_POST['nombre']) ? 'is-invalid' : ''; ?>"
                                id="nombre"
                                name="nombre"
                                value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
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
                        placeholder="Describe las características del producto..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                    <div class="form-text">
                        Máximo 500 caracteres.
                        <span id="charCount" class="char-counter-info">0</span>/500
                    </div>
                </div>

                <!-- Sección de Precios -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="precio" class="form-label">
                                <i class="fas fa-dollar-sign me-1"></i>Precio de Venta (USD) *
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number"
                                    class="form-control <?php echo isset($_POST['precio']) && (empty($_POST['precio']) || $_POST['precio'] <= 0) ? 'is-invalid' : ''; ?>"
                                    id="precio"
                                    name="precio"
                                    value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>"
                                    required
                                    min="0.01"
                                    step="0.01"
                                    placeholder="0.00">
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
                                    class="form-control"
                                    id="precio_costo"
                                    name="precio_costo"
                                    value="<?php echo htmlspecialchars($_POST['precio_costo'] ?? ''); ?>"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00">
                            </div>
                            <div class="form-text">Precio de compra al proveedor en USD</div>
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
                                            <strong id="precioBs" class="text-success">Bs 0.00</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Costo:</small><br>
                                            <strong id="precioCostoBs" class="text-muted">Bs 0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="stock_actual" class="form-label">
                                <i class="fas fa-boxes me-1"></i>Stock Actual
                            </label>
                            <input type="number"
                                class="form-control"
                                id="stock_actual"
                                name="stock_actual"
                                value="<?php echo htmlspecialchars($_POST['stock_actual'] ?? '0'); ?>"
                                min="0"
                                step="1"
                                placeholder="0">
                            <div class="form-text">Cantidad disponible en inventario</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="stock_minimo" class="form-label">
                                <i class="fas fa-exclamation-triangle me-1"></i>Stock Mínimo
                            </label>
                            <input type="number"
                                class="form-control"
                                id="stock_minimo"
                                name="stock_minimo"
                                value="<?php echo htmlspecialchars($_POST['stock_minimo'] ?? '5'); ?>"
                                min="0"
                                step="1"
                                placeholder="5">
                            <div class="form-text">Alerta cuando el stock llegue a este nivel</div>
                        </div>
                    </div>
                    <div class="col-md-4">
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
                            <div class="form-text">Opcional - puedes asignar una categoría después</div>
                        </div>
                    </div>
                </div>

                <!-- Información de Margen -->
                <div class="row">
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

                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Guardar Producto
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
</div>

<script>
    // Calcular precios en Bolívares y márgenes
    function calcularPreciosYMargenes() {
        const tasaActual = <?php echo $tasaActual; ?>;
        const precioUSD = parseFloat(document.getElementById('precio').value) || 0;
        const precioCostoUSD = parseFloat(document.getElementById('precio_costo').value) || 0;

        // Calcular precios en Bs
        const precioBs = precioUSD * tasaActual;
        const precioCostoBs = precioCostoUSD * tasaActual;

        // Calcular márgenes
        const gananciaUSD = precioUSD - precioCostoUSD;
        const gananciaBS = gananciaUSD * tasaActual;
        const margen = precioCostoUSD > 0 ? ((gananciaUSD / precioCostoUSD) * 100) : 0;

        // Actualizar display de precios en Bs
        document.getElementById('precioBs').textContent = 'Bs ' + precioBs.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        document.getElementById('precioCostoBs').textContent = 'Bs ' + precioCostoBs.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

        // Actualizar información de márgenes
        document.getElementById('margenGanancia').textContent = margen.toFixed(1) + '%';
        document.getElementById('gananciaNetaUSD').textContent = '$' + gananciaUSD.toFixed(2);
        document.getElementById('gananciaNetaBS').textContent = 'Bs ' + gananciaBS.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

        // Cambiar color del margen según el valor
        if (margen < 10) {
            document.getElementById('margenGanancia').className = 'badge margen-ganancia-bajo';
        } else if (margen < 25) {
            document.getElementById('margenGanancia').className = 'badge margen-ganancia-medio';
        } else {
            document.getElementById('margenGanancia').className = 'badge margen-ganancia-alto';
        }
    }

    // Contador de caracteres para descripción
    document.addEventListener('DOMContentLoaded', function() {
        const descripcion = document.getElementById('descripcion');
        const charCount = document.getElementById('charCount');

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

        // Inicializar contador
        charCount.textContent = descripcion.value.length;
        if (descripcion.value.length > 450) {
            charCount.className = 'char-counter-warning';
        } else if (descripcion.value.length > 400) {
            charCount.className = 'text-warning';
        }

        // Escuchar cambios en los campos de precio
        document.getElementById('precio').addEventListener('input', calcularPreciosYMargenes);
        document.getElementById('precio_costo').addEventListener('input', calcularPreciosYMargenes);

        // Calcular inicialmente
        calcularPreciosYMargenes();

        // Validación del formulario
        const form = document.getElementById('formProducto');
        const skuInput = document.getElementById('codigo_sku');
        const nombreInput = document.getElementById('nombre');
        const precioInput = document.getElementById('precio');

        form.addEventListener('submit', function(e) {
            const sku = skuInput.value.trim();
            const nombre = nombreInput.value.trim();
            const precioVal = parseFloat(precioInput.value);

            // Validación básica
            let isValid = true;

            if (!sku) {
                e.preventDefault();
                skuInput.classList.add('is-invalid');
                isValid = false;
            }

            if (!nombre) {
                e.preventDefault();
                nombreInput.classList.add('is-invalid');
                isValid = false;
            }

            if (!precioVal || precioVal <= 0) {
                e.preventDefault();
                precioInput.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) {
                showToast('error', 'Por favor, completa todos los campos obligatorios correctamente.');
                return false;
            }

            // Mostrar indicador de carga
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';

            return true;
        });

        // Validación en tiempo real
        skuInput.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });

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
        });
    });

    function limpiarFormulario() {
        if (confirm('¿Estás seguro de que deseas limpiar el formulario? Se perderán todos los datos ingresados.')) {
            document.getElementById('formProducto').reset();
            document.getElementById('charCount').textContent = '0';
            document.getElementById('charCount').className = 'char-counter-info';
            calcularPreciosYMargenes();

            // Remover clases de validación
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.classList.remove('is-valid', 'is-invalid');
            });

            showToast('info', 'Formulario limpiado correctamente.');
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

<!-- <?php require_once '../layouts/footer.php'; ?> -->