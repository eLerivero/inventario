<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

require_once '../../Controllers/ProductoController.php';
require_once '../../Controllers/CategoriaController.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$productoController = new ProductoController($db);
$categoriaController = new CategoriaController($db);

$categorias = $categoriaController->obtenerTodas();

// Procesar formulario
$mensaje = '';
$tipoMensaje = '';

if ($_POST) {
    $data = [
        'codigo_sku' => $_POST['codigo_sku'],
        'nombre' => $_POST['nombre'],
        'descripcion' => $_POST['descripcion'],
        'precio' => $_POST['precio'],
        'precio_costo' => $_POST['precio_costo'],
        'stock_actual' => $_POST['stock_actual'],
        'stock_minimo' => $_POST['stock_minimo'],
        'categoria_id' => $_POST['categoria_id']
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
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-plus me-2"></i>Crear Nuevo Producto
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <div id="alerts-container">
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" id="formProducto">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="codigo_sku" class="form-label">Código SKU *</label>
                            <input type="text" class="form-control" id="codigo_sku" name="codigo_sku"
                                value="<?php echo htmlspecialchars($_POST['codigo_sku'] ?? ''); ?>" required>
                            <div class="form-text">Código único de identificación del producto</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Producto *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">