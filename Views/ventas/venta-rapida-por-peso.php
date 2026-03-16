<?php
// Views/ventas/venta-rapida-por-peso.php
require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/TasaCambioController.php';
require_once '../../Config/Database.php';
require_once __DIR__ . '/../../Utils/Auth.php';

session_start();
Auth::requireAccessToVentas();

$database = new Database();
$db = $database->getConnection();
$productoController = new ProductoController($db);
$tasaController = new TasaCambioController($db);

$tasaActual = $tasaController->obtenerTasaActual();
$productosPorPeso = $productoController->obtenerProductosPorPesoParaVenta();

$page_title = "Venta Rápida por Peso";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-weight me-2"></i>Venta Rápida por Peso
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Volver a Ventas
            </a>
            <a href="nueva.php" class="btn btn-primary">
                <i class="fas fa-shopping-cart me-2"></i>Venta Normal
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
                    <small class="text-muted ms-3">Actualizada: <?php echo date('d/m/Y H:i', strtotime($tasaActual['data']['fecha_actualizacion'])); ?></small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Panel de productos (izquierda) -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-drumstick-bite me-2"></i>Seleccionar Producto
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="buscarProducto" placeholder="Buscar producto...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filtroCategoria">
                                <option value="">Todas las categorías</option>
                                <!-- Se llenará con JS -->
                            </select>
                        </div>
                    </div>

                    <div class="row" id="productos-container">
                        <?php if ($productosPorPeso['success'] && !empty($productosPorPeso['data'])): ?>
                            <?php foreach ($productosPorPeso['data'] as $producto): ?>
                                <div class="col-md-4 mb-3 producto-card"
                                    data-id="<?php echo $producto['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                    data-precio-kilo-usd="<?php echo $producto['precio_por_kilo_usd']; ?>"
                                    data-precio-kilo-bs="<?php echo $producto['precio_por_kilo_bs']; ?>"
                                    data-stock="<?php echo $producto['stock_actual_kg']; ?>"
                                    data-categoria="<?php echo htmlspecialchars($producto['categoria_nombre'] ?? ''); ?>"
                                    data-unidad="<?php echo $producto['unidad_medida'] ?? 'kg'; ?>">
                                    <div class="card producto-selector h-100 <?php echo $producto['stock_actual_kg'] <= 0 ? 'bg-light' : 'cursor-pointer'; ?>"
                                        onclick="seleccionarProducto(<?php echo $producto['id']; ?>)"
                                        style="<?php echo $producto['stock_actual_kg'] > 0 ? 'cursor: pointer;' : 'opacity: 0.6;'; ?>">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                                            <p class="card-text">
                                                <span class="badge bg-info">Bs <?php echo number_format($producto['precio_por_kilo_bs'], 2); ?>/kg</span>
                                                <span class="badge bg-secondary">$<?php echo number_format($producto['precio_por_kilo_usd'], 2); ?>/kg</span>
                                            </p>
                                            <p class="card-text">
                                                <small class="<?php echo $producto['stock_actual_kg'] <= 0 ? 'text-danger' : 'text-success'; ?>">
                                                    <i class="fas fa-<?php echo $producto['stock_actual_kg'] <= 0 ? 'times' : 'check'; ?>-circle"></i>
                                                    Stock: <?php echo number_format($producto['stock_actual_kg'], 2); ?> kg
                                                </small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    No hay productos configurados para venta por peso.
                                    <a href="../productos/crear.php" class="alert-link">Crear producto por peso</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de venta actual (derecha) -->
        <div class="col-md-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-basket me-2"></i>Venta Actual
                    </h5>
                </div>
                <div class="card-body">
                    <div id="producto-seleccionado-info" class="mb-3 p-3 border rounded bg-light" style="display: none;">
                        <h6 id="producto-nombre" class="fw-bold"></h6>
                        <p class="mb-1">
                            <span id="producto-precio-kilo" class="badge bg-primary"></span>
                        </p>
                        <p class="mb-0">
                            <small id="producto-stock" class="text-muted"></small>
                        </p>
                    </div>

                    <form id="form-agregar-item">
                        <input type="hidden" id="producto-id" value="">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Peso a vender:</label>
                            <div class="input-group mb-2">
                                <input type="number"
                                    class="form-control form-control-lg text-center"
                                    id="peso-gramos"
                                    placeholder="Gramos"
                                    min="1"
                                    step="1"
                                    value="500"
                                    oninput="calcularPrecio()">
                                <span class="input-group-text">gramos</span>
                            </div>
                            <div class="text-center text-muted">
                                = <span id="peso-kilos">0.500</span> kg
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="row text-center">
                                <div class="col-6">
                                    <small class="text-muted">Precio USD</small>
                                    <h4 id="precio-usd" class="text-primary">$0.00</h4>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Precio Bs</small>
                                    <h4 id="precio-bs" class="text-success">Bs 0,00</h4>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-success w-100 mb-2" onclick="agregarAlCarrito()" id="btn-agregar" disabled>
                            <i class="fas fa-cart-plus me-2"></i>Agregar a Venta
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="limpiarSeleccion()">
                            <i class="fas fa-undo me-2"></i>Limpiar
                        </button>
                    </form>

                    <hr>

                    <div id="carrito-items" style="max-height: 300px; overflow-y: auto;">
                        <!-- Aquí se agregarán los items del carrito -->
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total USD:</span>
                            <span id="total-usd" class="fw-bold text-primary">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total Bs:</span>
                            <span id="total-bs" class="fw-bold text-success">Bs 0,00</span>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary w-100 mt-3" onclick="finalizarVenta()" id="btn-finalizar" disabled>
                        <i class="fas fa-check-circle me-2"></i>Finalizar Venta
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let tasaActual = <?php echo $tasaActual['success'] ? $tasaActual['data']['tasa_cambio'] : 36.5; ?>;
    let carrito = [];
    let productoSeleccionado = null;

    function seleccionarProducto(id) {
        const productoCard = document.querySelector(`.producto-card[data-id="${id}"]`);
        if (!productoCard) return;

        const stock = parseFloat(productoCard.dataset.stock);
        if (stock <= 0) {
            alert('Este producto no tiene stock disponible');
            return;
        }

        // Remover selección anterior
        document.querySelectorAll('.producto-selector').forEach(card => {
            card.classList.remove('border-primary', 'border-3');
        });

        // Marcar selección actual
        const card = productoCard.querySelector('.producto-selector');
        card.classList.add('border-primary', 'border-3');

        productoSeleccionado = {
            id: parseInt(productoCard.dataset.id),
            nombre: productoCard.dataset.nombre,
            precioKiloUsd: parseFloat(productoCard.dataset.precioKiloUsd),
            precioKiloBs: parseFloat(productoCard.dataset.precioKiloBs),
            stock: parseFloat(productoCard.dataset.stock)
        };

        document.getElementById('producto-id').value = productoSeleccionado.id;
        document.getElementById('producto-nombre').textContent = productoSeleccionado.nombre;
        document.getElementById('producto-precio-kilo').textContent =
            `Bs ${productoSeleccionado.precioKiloBs.toFixed(2)}/kg | $${productoSeleccionado.precioKiloUsd.toFixed(2)}/kg`;
        document.getElementById('producto-stock').textContent =
            `Stock disponible: ${productoSeleccionado.stock.toFixed(2)} kg`;

        document.getElementById('producto-seleccionado-info').style.display = 'block';
        document.getElementById('btn-agregar').disabled = false;

        calcularPrecio();
    }

    function calcularPrecio() {
        if (!productoSeleccionado) return;

        const gramos = parseFloat(document.getElementById('peso-gramos').value) || 0;
        const kilos = gramos / 1000;

        document.getElementById('peso-kilos').textContent = kilos.toFixed(3);

        const precioUsd = productoSeleccionado.precioKiloUsd * kilos;
        const precioBs = productoSeleccionado.precioKiloBs * kilos;

        document.getElementById('precio-usd').textContent = `$${precioUsd.toFixed(2)}`;
        document.getElementById('precio-bs').textContent = `Bs ${precioBs.toFixed(2)}`;
    }

    function agregarAlCarrito() {
        if (!productoSeleccionado) {
            alert('Selecciona un producto primero');
            return;
        }

        const gramos = parseFloat(document.getElementById('peso-gramos').value);
        if (!gramos || gramos <= 0) {
            alert('Ingresa un peso válido');
            return;
        }

        const kilos = gramos / 1000;

        // Validar stock
        if (kilos > productoSeleccionado.stock) {
            alert(`Stock insuficiente. Disponible: ${productoSeleccionado.stock.toFixed(2)} kg`);
            return;
        }

        const item = {
            id: productoSeleccionado.id,
            nombre: productoSeleccionado.nombre,
            gramos: gramos,
            kilos: kilos,
            precioKiloUsd: productoSeleccionado.precioKiloUsd,
            precioKiloBs: productoSeleccionado.precioKiloBs,
            precioUsd: productoSeleccionado.precioKiloUsd * kilos,
            precioBs: productoSeleccionado.precioKiloBs * kilos
        };

        carrito.push(item);
        actualizarCarrito();
        limpiarSeleccion();
    }

    function actualizarCarrito() {
        const carritoContainer = document.getElementById('carrito-items');
        let totalUsd = 0;
        let totalBs = 0;

        if (carrito.length === 0) {
            carritoContainer.innerHTML = '<p class="text-muted text-center">No hay items en la venta</p>';
            document.getElementById('btn-finalizar').disabled = true;
        } else {
            let html = '<div class="list-group">';

            carrito.forEach((item, index) => {
                totalUsd += item.precioUsd;
                totalBs += item.precioBs;

                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${item.nombre}</h6>
                                <small class="text-muted">
                                    ${item.gramos}g (${item.kilos.toFixed(3)} kg) 
                                    x Bs ${item.precioKiloBs.toFixed(2)}/kg
                                </small>
                            </div>
                            <div class="text-end">
                                <div><strong>Bs ${item.precioBs.toFixed(2)}</strong></div>
                                <small class="text-muted">$${item.precioUsd.toFixed(2)}</small>
                                <button class="btn btn-sm btn-danger ms-2" onclick="eliminarItem(${index})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            carritoContainer.innerHTML = html;
            document.getElementById('btn-finalizar').disabled = false;
        }

        document.getElementById('total-usd').textContent = `$${totalUsd.toFixed(2)}`;
        document.getElementById('total-bs').textContent = `Bs ${totalBs.toFixed(2)}`;
    }

    function eliminarItem(index) {
        carrito.splice(index, 1);
        actualizarCarrito();
    }

    function limpiarSeleccion() {
        productoSeleccionado = null;
        document.getElementById('producto-id').value = '';
        document.getElementById('producto-seleccionado-info').style.display = 'none';
        document.getElementById('btn-agregar').disabled = true;
        document.getElementById('peso-gramos').value = 500;
        document.getElementById('precio-usd').textContent = '$0.00';
        document.getElementById('precio-bs').textContent = 'Bs 0,00';

        document.querySelectorAll('.producto-selector').forEach(card => {
            card.classList.remove('border-primary', 'border-3');
        });
    }

    function finalizarVenta() {
        if (carrito.length === 0) {
            alert('No hay items en la venta');
            return;
        }

        // Aquí enviarías los datos al backend para procesar la venta
        console.log('Venta a procesar:', carrito);

        if (confirm('¿Confirmar venta?')) {
            alert('Venta procesada exitosamente');
            carrito = [];
            actualizarCarrito();
        }
    }

    // Filtros de búsqueda
    document.getElementById('buscarProducto').addEventListener('keyup', function() {
        const termino = this.value.toLowerCase();
        document.querySelectorAll('.producto-card').forEach(card => {
            const nombre = card.dataset.nombre.toLowerCase();
            if (nombre.includes(termino)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>

<style>
    .cursor-pointer {
        cursor: pointer;
    }

    .producto-selector:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s;
    }

    .border-primary {
        border-color: #0d6efd !important;
    }

    .border-3 {
        border-width: 3px !important;
    }

    .sticky-top {
        z-index: 1020;
    }

    #carrito-items {
        max-height: 300px;
        overflow-y: auto;
    }

    #carrito-items .list-group-item {
        padding: 0.5rem;
    }

    .btn-sm {
        padding: 0.1rem 0.3rem;
        font-size: 0.7rem;
    }
</style>

<!-- <?php require_once '../layouts/footer.php'; ?> -->