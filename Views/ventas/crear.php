<?php
// Usar rutas absolutas para evitar problemas de inclusión
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/VentaController.php';
require_once __DIR__ . '/../../Controllers/ClienteController.php';
require_once __DIR__ . '/../../Controllers/ProductoController.php';
require_once __DIR__ . '/../../Controllers/TipoPagoController.php';
require_once __DIR__ . '/../../Controllers/TasaCambioController.php';
require_once __DIR__ . '/../../Helpers/TasaCambioHelper.php';

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
$productos = $ventaController->obtenerProductosConInfoCompleta();
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
            foreach ($_POST['productos'] as $index => $producto_id) {
                if (!empty($producto_id) && isset($_POST['cantidades'][$index])) {
                    $cantidad = intval($_POST['cantidades'][$index]);

                    // **OBTENER INFORMACIÓN EXACTA DEL PRODUCTO**
                    $producto_info = null;
                    foreach ($productos_data as $prod) {
                        if ($prod['id'] == $producto_id) {
                            $producto_info = $prod;
                            break;
                        }
                    }

                    if ($producto_info) {
                        // **DETERMINAR SI ES PRECIO FIJO DESDE LA BD**
                        $es_precio_fijo = isset($producto_info['usar_precio_fijo_bs']) &&
                            $producto_info['usar_precio_fijo_bs'] == true;

                        // **PARA PRECIO FIJO: ENVIAR 0 COMO PRECIO USD (EL CONTROLADOR USARÁ precio_bs)**
                        // **PARA PRODUCTO NORMAL: ENVIAR EL PRECIO USD DEL FORMULARIO**
                        $precio_usd = 0;

                        if (!$es_precio_fijo && isset($_POST['precios'][$index])) {
                            $precio_usd = floatval($_POST['precios'][$index]);
                        }

                        // **DEBUG EN EL FORMULARIO**
                        error_log("Formulario - Producto ID: " . $producto_id);
                        error_log("  Nombre: " . $producto_info['nombre']);
                        error_log("  Es precio fijo: " . ($es_precio_fijo ? 'Sí' : 'No'));
                        error_log("  Precio BS en BD: " . ($producto_info['precio_bs'] ?? '0'));
                        error_log("  Precio USD enviado: " . $precio_usd);

                        if ($cantidad > 0) {
                            $datosVenta['detalles'][] = [
                                'producto_id' => $producto_id,
                                'cantidad' => $cantidad,
                                'precio_unitario' => $precio_usd, // 0 para precio fijo
                                'es_precio_fijo' => $es_precio_fijo
                            ];
                        }
                    }
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

                            // Si es precio fijo, mostrar el precio exacto
                            if ($detalle['es_precio_fijo']) {
                                $success_message .= " [Precio fijo exacto: Bs " . number_format($detalle['precio_fijo_original'], 2) . "]";
                            }
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

// Incluir header con rutas corregidas
include __DIR__ . '/../layouts/header.php';
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

<!-- Incluir CSS personalizado -->
<link rel="stylesheet" href="crear-venta.css">

<script>
    // Variables globales que necesitan ser accesibles desde crear-venta.js
    window.tasaCambio = <?php echo $tasa_info ? $tasa_info['tasa_cambio'] : 0; ?>;
    window.productosData = <?php echo json_encode($productos_data); ?>;
    window.tasaInfo = <?php echo json_encode($tasa_info); ?>;
</script>

<!-- Incluir JavaScript personalizado -->
<script src="crear-venta.js"></script>

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

<!-- <?php include __DIR__ . '/../layouts/footer.php'; ?> -->