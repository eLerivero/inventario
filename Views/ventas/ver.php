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
            <i class="fas fa-receipt me-2"></i>
            Detalles de Venta
        </h1>
        <p class="text-muted mb-0">
            <?php if ($venta): ?>
                Información completa de la venta #<?php echo $venta['numero_venta']; ?>
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
            <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#modalCompletarVenta">
                <i class="fas fa-check-circle me-1"></i> Completar Venta
            </button>
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
    <!-- Tarjeta Resumen Principal -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-invoice me-2"></i>
                        Venta #<?php echo $venta['numero_venta']; ?>
                    </h5>
                    <small class="opacity-75">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo !empty($venta['fecha_hora']) ? Ayuda::formatDate($venta['fecha_hora'], 'd/m/Y H:i:s') : Ayuda::formatDate($venta['created_at'], 'd/m/Y H:i:s'); ?>
                    </small>
                </div>
                <div>
                    <?php
                    $estado_badge = [
                        'pendiente' => ['bg-warning text-dark', 'Pendiente', 'fas fa-clock'],
                        'completada' => ['bg-success', 'Completada', 'fas fa-check-circle'],
                        'cancelada' => ['bg-danger', 'Cancelada', 'fas fa-times-circle']
                    ];
                    $estado_info = $estado_badge[$venta['estado']] ?? ['bg-secondary', $venta['estado'], 'fas fa-question-circle'];
                    ?>
                    <span class="badge <?php echo $estado_info[0]; ?> p-2">
                        <i class="<?php echo $estado_info[2]; ?> me-1"></i>
                        <?php echo $estado_info[1]; ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="d-flex align-items-start mb-3">
                        <div class="bg-light rounded-circle p-3 me-3">
                            <i class="fas fa-user fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted">Cliente</h6>
                            <h5 class="mb-0"><?php echo htmlspecialchars($venta['cliente_nombre']); ?></h5>
                            <?php if (!empty($venta['cliente_documento_identidad'])): ?>
                                <small class="text-muted">DNI: <?php echo htmlspecialchars($venta['cliente_documento_identidad']); ?></small>
                            <?php endif; ?>
                            <?php if (!empty($venta['cliente_telefono'])): ?>
                                <div class="mt-1">
                                    <i class="fas fa-phone text-muted me-1"></i>
                                    <small class="text-muted"><?php echo htmlspecialchars($venta['cliente_telefono']); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start mb-3">
                        <div class="bg-light rounded-circle p-3 me-3">
                            <i class="fas fa-credit-card fa-2x text-success"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted">Método de Pago</h6>
                            <h5 class="mb-0"><?php echo htmlspecialchars($venta['tipo_pago_nombre']); ?></h5>
                            <small class="text-muted">Tasa de cambio fijada</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start mb-3">
                        <div class="bg-light rounded-circle p-3 me-3">
                            <i class="fas fa-exchange-alt fa-2x text-info"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted">Tasa de Cambio</h6>
                            <h5 class="mb-0"><?php echo $venta['tasa_formateada'] ?? number_format($venta['tasa_cambio_utilizada'], 2); ?> Bs/$</h5>
                            <small class="text-muted">Aplicada al momento de la venta</small>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($venta['observaciones'])): ?>
                <div class="alert alert-light mt-3">
                    <h6 class="alert-heading mb-2">
                        <i class="fas fa-sticky-note me-1"></i> Observaciones
                    </h6>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($venta['observaciones'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumen de Totales -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="d-flex flex-column align-items-center">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3 mb-3">
                            <i class="fas fa-dollar-sign fa-2x text-success"></i>
                        </div>
                        <h6 class="text-muted mb-2">Total en Dólares</h6>
                        <h3 class="text-success mb-0"><?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?></h3>
                        <small class="text-muted">Monto total en USD</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <div class="d-flex flex-column align-items-center">
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3 mb-3">
                            <i class="fas fa-bs fa-2x text-warning"></i>
                        </div>
                        <h6 class="text-muted mb-2">Total en Bolívares</h6>
                        <h3 class="text-warning mb-0"><?php echo $venta['total_formateado_bs'] ?? TasaCambioHelper::formatearBS($venta['total_bs']); ?></h3>
                        <small class="text-muted">Monto total en Bs</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <div class="d-flex flex-column align-items-center">
                        <div class="bg-info bg-opacity-10 rounded-circle p-3 mb-3">
                            <i class="fas fa-boxes fa-2x text-info"></i>
                        </div>
                        <h6 class="text-muted mb-2">Productos Vendidos</h6>
                        <h3 class="text-info mb-0"><?php echo count($venta['detalles']); ?></h3>
                        <small class="text-muted">Items en esta venta</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalles de Productos -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-shopping-basket me-2"></i>
                Productos Vendidos
                <span class="badge bg-primary rounded-pill ms-2"><?php echo count($venta['detalles']); ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($venta['detalles'])): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay productos en esta venta</h5>
                    <p class="text-muted mb-0">Esta venta no contiene productos registrados</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">
                                    <i class="fas fa-hashtag"></i>
                                </th>
                                <th width="40%">Producto</th>
                                <th width="10%" class="text-center">Cantidad</th>
                                <th width="20%" class="text-center">Precio Unitario</th>
                                <th width="25%" class="text-center">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $contador = 1;
                            $subtotal_usd = 0;
                            $subtotal_bs = 0;
                            foreach ($venta['detalles'] as $detalle):
                                $subtotal_usd += $detalle['subtotal'];
                                $subtotal_bs += $detalle['subtotal_bs'];
                                $es_precio_fijo = $detalle['es_precio_fijo'] ?? false;
                            ?>
                                <tr>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-light text-dark"><?php echo $contador++; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 me-3">
                                                <i class="fas fa-box text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($detalle['producto_nombre']); ?></h6>
                                                <div class="small text-muted">
                                                    <span class="me-3">
                                                        <i class="fas fa-barcode me-1"></i>
                                                        <?php echo htmlspecialchars($detalle['codigo_sku']); ?>
                                                    </span>
                                                    <?php if ($es_precio_fijo): ?>
                                                        <span class="badge bg-success bg-opacity-10 text-success">
                                                            <i class="fas fa-lock me-1"></i> Precio Fijo
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info bg-opacity-10 text-info">
                                                            <i class="fas fa-calculator me-1"></i> Conversión
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-primary rounded-pill px-3 py-2">
                                            <?php echo $detalle['cantidad']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex flex-column">
                                            <strong class="text-primary">
                                                <?php echo $detalle['precio_unitario_formateado_usd'] ?? '$' . number_format($detalle['precio_unitario'], 2); ?>
                                            </strong>
                                            <small class="text-muted">
                                                <?php echo $detalle['precio_unitario_formateado_bs'] ?? TasaCambioHelper::formatearBS($detalle['precio_unitario_bs']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex flex-column">
                                            <strong class="text-success">
                                                <?php echo $detalle['subtotal_formateado_usd'] ?? '$' . number_format($detalle['subtotal'], 2); ?>
                                            </strong>
                                            <small class="text-muted">
                                                <?php echo $detalle['subtotal_formateado_bs'] ?? TasaCambioHelper::formatearBS($detalle['subtotal_bs']); ?>
                                            </small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end">
                                    <h6 class="mb-0 pt-3">Totales:</h6>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column pt-3">
                                        <strong class="text-primary">
                                            <?php echo '$' . number_format(array_sum(array_column($venta['detalles'], 'precio_unitario')), 2); ?>
                                        </strong>
                                        <small class="text-muted">Precios unitarios</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column pt-3">
                                        <strong class="text-success fs-5">
                                            <?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?>
                                        </strong>
                                        <span class="text-warning">
                                            <?php echo $venta['total_formateado_bs'] ?? TasaCambioHelper::formatearBS($venta['total_bs']); ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-light">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 rounded p-2 me-3">
                            <i class="fas fa-info-circle text-info"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Venta registrada el</small>
                            <small class="text-dark">
                                <?php echo Ayuda::formatDate($venta['created_at'], 'd/m/Y H:i:s'); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        <i class="fas fa-box me-1"></i>
                        <?php echo count($venta['detalles']); ?> productos |
                        <i class="fas fa-dollar-sign me-1 ms-2"></i>
                        <?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de Sistema -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historial de la Venta
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled timeline">
                        <li class="mb-3">
                            <div class="d-flex">
                                <div class="timeline-icon bg-success text-white rounded-circle p-2 me-3">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Venta Creada</h6>
                                    <p class="text-muted mb-0 small">
                                        <?php echo Ayuda::formatDate($venta['created_at'], 'd/m/Y H:i:s'); ?>
                                    </p>
                                </div>
                            </div>
                        </li>
                        <?php if ($venta['estado'] === 'completada'): ?>
                            <li class="mb-3">
                                <div class="d-flex">
                                    <div class="timeline-icon bg-info text-white rounded-circle p-2 me-3">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Venta Completada</h6>
                                        <p class="text-muted mb-0 small">
                                            Stock actualizado y venta finalizada
                                        </p>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($venta['updated_at']) && $venta['updated_at'] !== $venta['created_at']): ?>
                            <li>
                                <div class="d-flex">
                                    <div class="timeline-icon bg-warning text-white rounded-circle p-2 me-3">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Última Actualización</h6>
                                        <p class="text-muted mb-0 small">
                                            <?php echo Ayuda::formatDate($venta['updated_at'], 'd/m/Y H:i:s'); ?>
                                        </p>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Acciones Disponibles
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i> Ver Todas las Ventas
                        </a>
                        <?php if ($venta['estado'] === 'pendiente'): ?>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCompletarVenta">
                                <i class="fas fa-check-circle me-1"></i> Completar Venta
                            </button>
                            <a href="editar.php?id=<?php echo $venta['id']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-edit me-1"></i> Editar Venta
                            </a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-info" onclick="imprimirRecibo()">
                            <i class="fas fa-print me-1"></i> Imprimir Recibo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Completar Venta -->
    <?php if ($venta['estado'] === 'pendiente'): ?>
        <div class="modal fade" id="modalCompletarVenta" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>
                            Completar Venta
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                        </div>
                        <p>¿Estás seguro de que deseas completar esta venta?</p>
                        <ul class="text-muted">
                            <li>El estado cambiará a "Completada"</li>
                            <li>El stock de los productos se actualizará</li>
                            <li>La venta será registrada como finalizada</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <a href="index.php?action=completar&id=<?php echo $venta['id']; ?>&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>"
                            class="btn btn-success">
                            <i class="fas fa-check me-1"></i> Sí, Completar Venta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<style>
    .timeline {
        position: relative;
        padding-left: 0;
    }
    
    .timeline li {
        position: relative;
        padding-left: 0;
    }
    
    .timeline li:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 24px;
        top: 48px;
        bottom: -20px;
        width: 2px;
        background-color: #e9ecef;
    }
    
    .timeline-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }
    
    .badge.bg-opacity-10 {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid #e9ecef;
    }
    
    .card-header {
        border-bottom: 1px solid #e9ecef;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>

<script>
    function imprimirRecibo() {
        // Guardar el contenido original
        const originalContent = document.body.innerHTML;
        
        // Crear contenido para imprimir
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Recibo Venta #<?php echo $venta['numero_venta']; ?></title>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 14px; }
                    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
                    .info { margin-bottom: 20px; }
                    .info-item { margin-bottom: 5px; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f8f9fa; }
                    .total { margin-top: 20px; text-align: right; font-size: 16px; font-weight: bold; }
                    .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
                    .badge { padding: 3px 8px; border-radius: 3px; font-size: 12px; }
                    .badge-pendiente { background-color: #ffc107; color: #000; }
                    .badge-completada { background-color: #198754; color: #fff; }
                    .badge-cancelada { background-color: #dc3545; color: #fff; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>Recibo de Venta</h2>
                    <h3>Venta #<?php echo $venta['numero_venta']; ?></h3>
                    <p><?php echo date('d/m/Y H:i:s'); ?></p>
                </div>
                
                <div class="info">
                    <div class="info-item"><strong>Cliente:</strong> <?php echo htmlspecialchars($venta['cliente_nombre']); ?></div>
                    <?php if (!empty($venta['cliente_documento_identidad'])): ?>
                        <div class="info-item"><strong>DNI:</strong> <?php echo htmlspecialchars($venta['cliente_documento_identidad']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($venta['cliente_telefono'])): ?>
                        <div class="info-item"><strong>Teléfono:</strong> <?php echo htmlspecialchars($venta['cliente_telefono']); ?></div>
                    <?php endif; ?>
                    <div class="info-item"><strong>Estado:</strong> 
                        <span class="badge badge-<?php echo $venta['estado']; ?>">
                            <?php echo ucfirst($venta['estado']); ?>
                        </span>
                    </div>
                    <div class="info-item"><strong>Tipo de Pago:</strong> <?php echo htmlspecialchars($venta['tipo_pago_nombre']); ?></div>
                    <div class="info-item"><strong>Tasa de Cambio:</strong> <?php echo $venta['tasa_formateada'] ?? number_format($venta['tasa_cambio_utilizada'], 2); ?> Bs/$</div>
                    <?php if (!empty($venta['observaciones'])): ?>
                        <div class="info-item"><strong>Observaciones:</strong> <?php echo htmlspecialchars($venta['observaciones']); ?></div>
                    <?php endif; ?>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $contador = 1;
                        foreach ($venta['detalles'] as $detalle):
                        ?>
                            <tr>
                                <td><?php echo $contador++; ?></td>
                                <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?> (<?php echo htmlspecialchars($detalle['codigo_sku']); ?>)</td>
                                <td><?php echo $detalle['cantidad']; ?></td>
                                <td>
                                    <?php echo $detalle['precio_unitario_formateado_usd'] ?? '$' . number_format($detalle['precio_unitario'], 2); ?><br>
                                    <small><?php echo $detalle['precio_unitario_formateado_bs'] ?? TasaCambioHelper::formatearBS($detalle['precio_unitario_bs']); ?></small>
                                </td>
                                <td>
                                    <?php echo $detalle['subtotal_formateado_usd'] ?? '$' . number_format($detalle['subtotal'], 2); ?><br>
                                    <small><?php echo $detalle['subtotal_formateado_bs'] ?? TasaCambioHelper::formatearBS($detalle['subtotal_bs']); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="total">
                    <div><strong>Total USD:</strong> <?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?></div>
                    <div><strong>Total Bs:</strong> <?php echo $venta['total_formateado_bs'] ?? TasaCambioHelper::formatearBS($venta['total_bs']); ?></div>
                </div>
                
                <div class="footer">
                    <p>-----------------------------------</p>
                    <p>Recibo generado el <?php echo date('d/m/Y H:i:s'); ?></p>
                    <p>Sistema de Ventas</p>
                </div>
            </body>
            </html>
        `;
        
        // Abrir ventana de impresión
        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        
        // Esperar a que cargue el contenido
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }
</script>

<!-- <?php include '../layouts/footer.php'; ?> -->