<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/VentaController.php';
require_once '../../Utils/Ayuda.php';
require_once '../../Helpers/TasaCambioHelper.php';

$database = new Database();
$db = $database->getConnection();

$controller = new VentaController($db);

// Obtener ID de la venta
$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: index.php');
    exit();
}

// Obtener datos de la venta
$result = $controller->obtener($id);
if (!$result['success']) {
    $error_message = $result['message'];
    $venta = null;
} else {
    $venta = $result['data'];
}
?>

<?php
$page_title = "Detalles de Venta";
include '../layouts/header.php';
?>

<!-- Header con Botón de Volver -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-eye me-2"></i>
            Detalles de Venta
        </h1>
        <p class="text-muted mb-0">
            <?php if ($venta): ?>
                Venta #<?php echo $venta['numero_venta']; ?>
            <?php else: ?>
                Venta no encontrada
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Listado
        </a>
        <?php if ($venta && $venta['estado'] === 'pendiente'): ?>
            <a href="index.php?action=completar&id=<?php echo $venta['id']; ?>&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>"
                class="btn btn-success ms-2"
                onclick="return confirm('¿Estás seguro de que deseas completar esta venta?')">
                <i class="fas fa-check me-1"></i> Completar Venta
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$venta): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        No se encontró la venta solicitada.
        <div class="mt-2">
            <a href="index.php" class="btn btn-sm btn-warning">
                <i class="fas fa-arrow-left me-1"></i> Volver al listado
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <!-- Información Principal -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información de la Venta
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Número:</dt>
                                <dd class="col-sm-8">
                                    <strong>#<?php echo $venta['numero_venta']; ?></strong>
                                </dd>

                                <dt class="col-sm-4">Cliente:</dt>
                                <dd class="col-sm-8">
                                    <?php echo htmlspecialchars($venta['cliente_nombre']); ?>
                                    <?php if (!empty($venta['cliente_email'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($venta['cliente_email']); ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($venta['cliente_telefono'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($venta['cliente_telefono']); ?></small>
                                    <?php endif; ?>
                                </dd>

                                <dt class="col-sm-4">Tipo Pago:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($venta['tipo_pago_nombre']); ?></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Estado:</dt>
                                <dd class="col-sm-8">
                                    <?php
                                    $estado_badge = [
                                        'pendiente' => 'bg-warning',
                                        'completada' => 'bg-success',
                                        'cancelada' => 'bg-danger'
                                    ];
                                    $estado_text = [
                                        'pendiente' => 'Pendiente',
                                        'completada' => 'Completada',
                                        'cancelada' => 'Cancelada'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $estado_badge[$venta['estado']] ?? 'bg-secondary'; ?>">
                                        <?php echo $estado_text[$venta['estado']] ?? $venta['estado']; ?>
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Fecha:</dt>
                                <dd class="col-sm-8">
                                    <?php echo !empty($venta['fecha_hora']) ? Ayuda::formatDate($venta['fecha_hora'], 'd/m/Y H:i:s') : Ayuda::formatDate($venta['created_at'], 'd/m/Y H:i:s'); ?>
                                </dd>

                                <dt class="col-sm-4">Tasa Cambio:</dt>
                                <dd class="col-sm-8">
                                    <small class="text-muted"><?php echo $venta['tasa_formateada'] ?? number_format($venta['tasa_cambio_utilizada'], 2); ?> Bs/$</small>
                                </dd>
                            </dl>
                        </div>
                    </div>

                    <?php if (!empty($venta['observaciones'])): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <dt>Observaciones:</dt>
                                <dd class="text-muted"><?php echo nl2br(htmlspecialchars($venta['observaciones'])); ?></dd>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Detalles de Productos -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-boxes me-2"></i>
                        Productos Vendidos
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($venta['detalles'])): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-box-open fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No hay productos en esta venta.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>SKU</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $subtotal_usd = 0;
                                    $subtotal_bs = 0;
                                    foreach ($venta['detalles'] as $detalle):
                                        $subtotal_usd += $detalle['subtotal'];
                                        $subtotal_bs += $detalle['subtotal_bs'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($detalle['codigo_sku']); ?></td>
                                            <td><?php echo $detalle['cantidad']; ?></td>
                                            <td>
                                                <div class="small"><?php echo $detalle['precio_unitario_formateado_usd'] ?? '$' . number_format($detalle['precio_unitario'], 2); ?></div>
                                                <div class="text-muted small"><?php echo $detalle['precio_unitario_formateado_bs'] ?? TasaCambioHelper::formatearBS($detalle['precio_unitario_bs']); ?></div>
                                            </td>
                                            <td>
                                                <div><strong><?php echo $detalle['subtotal_formateado_usd'] ?? '$' . number_format($detalle['subtotal'], 2); ?></strong></div>
                                                <div class="text-success small"><?php echo $detalle['subtotal_formateado_bs'] ?? TasaCambioHelper::formatearBS($detalle['subtotal_bs']); ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total USD:</strong></td>
                                        <td><strong class="text-primary"><?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total Bs:</strong></td>
                                        <td><strong class="text-success"><?php echo $venta['total_formateado_bs'] ?? TasaCambioHelper::formatearBS($venta['total_bs']); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Resumen de la Venta
                    </h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Número:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-dark">#<?php echo $venta['numero_venta']; ?></span>
                        </dd>

                        <dt class="col-sm-5">Estado:</dt>
                        <dd class="col-sm-7">
                            <span class="badge <?php echo $estado_badge[$venta['estado']] ?? 'bg-secondary'; ?>">
                                <?php echo $estado_text[$venta['estado']] ?? $venta['estado']; ?>
                            </span>
                        </dd>

                        <dt class="col-sm-5">Productos:</dt>
                        <dd class="col-sm-7">
                            <?php echo count($venta['detalles']); ?> items
                        </dd>

                        <dt class="col-sm-5">Tasa Cambio:</dt>
                        <dd class="col-sm-7">
                            <?php echo $venta['tasa_formateada'] ?? number_format($venta['tasa_cambio_utilizada'], 2); ?> Bs/$
                        </dd>

                        <dt class="col-sm-5">Total USD:</dt>
                        <dd class="col-sm-7">
                            <strong class="text-primary"><?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?></strong>
                        </dd>

                        <dt class="col-sm-5">Total Bs:</dt>
                        <dd class="col-sm-7">
                            <strong class="text-success"><?php echo $venta['total_formateado_bs'] ?? TasaCambioHelper::formatearBS($venta['total_bs']); ?></strong>
                        </dd>

                        <dt class="col-sm-5">Registrada:</dt>
                        <dd class="col-sm-7">
                            <small class="text-muted">
                                <?php echo Ayuda::formatDate($venta['created_at'], 'd/m/Y H:i:s'); ?>
                            </small>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Acciones -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Acciones
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver al Listado
                        </a>
                        <?php if ($venta['estado'] === 'pendiente'): ?>
                            <a href="index.php?action=completar&id=<?php echo $venta['id']; ?>&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>"
                                class="btn btn-success"
                                onclick="return confirm('¿Estás seguro de que deseas completar esta venta? El stock se actualizará automáticamente.')">
                                <i class="fas fa-check me-1"></i> Completar Venta
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- <?php include '../layouts/footer.php'; ?> -->