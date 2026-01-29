<?php
// INICIAR SESIÓN PRIMERO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// REQUERIR ACCESO A CIERRE DE CAJA
require_once __DIR__ . '/../../Utils/Auth.php';
Auth::requireAuth();
Auth::requireAccessToCierreCaja();

// Incluir controladores
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/CierreCajaController.php';

$database = new Database();
$db = $database->getConnection();
$cierreController = new CierreCajaController($db);

// Obtener ID o fecha del cierre
$cierre_id = $_GET['id'] ?? null;
$fecha = $_GET['fecha'] ?? null;

if ($cierre_id) {
    $resultado = $cierreController->obtenerDetalleCierre($cierre_id);
} elseif ($fecha) {
    // Buscar cierre por fecha
    require_once __DIR__ . '/../../Models/CierreCaja.php';
    $cierreModel = new CierreCaja($db);
    $cierre = $cierreModel->obtenerPorFecha($fecha);
    
    if ($cierre) {
        $resultado = $cierreController->obtenerDetalleCierre($cierre['id']);
    } else {
        $resultado = ['success' => false, 'message' => 'No se encontró cierre para esta fecha'];
    }
} else {
    $resultado = ['success' => false, 'message' => 'ID o fecha no especificados'];
}

if (!$resultado['success']) {
    $_SESSION['error'] = $resultado['message'];
    header("Location: index.php");
    exit();
}

$data = $resultado['data'];
$cierre = $data['cierre'];
$reporte = $data['reporte'];

// CORRECCIÓN: Verificar si los resúmenes son strings JSON o ya son arrays
$resumen_categorias = $cierre['resumen_categorias'] ?? [];
$resumen_productos = $cierre['resumen_productos'] ?? [];
$resumen_clientes = $cierre['resumen_clientes'] ?? [];

// Si son strings JSON, decodificarlos
if (is_string($resumen_categorias)) {
    $resumen_categorias = json_decode($resumen_categorias, true) ?? [];
}
if (is_string($resumen_productos)) {
    $resumen_productos = json_decode($resumen_productos, true) ?? [];
}
if (is_string($resumen_clientes)) {
    $resumen_clientes = json_decode($resumen_clientes, true) ?? [];
}

// Si están vacíos, intentar obtener del reporte
if (empty($resumen_categorias) && !empty($reporte)) {
    $resumen_categorias = [];
    foreach ($reporte as $item) {
        $categoria = $item['categoria_nombre'];
        if (!isset($resumen_categorias[$categoria])) {
            $resumen_categorias[$categoria] = [
                'unidades' => 0,
                'total_usd' => 0,
                'total_bs' => 0
            ];
        }
        $resumen_categorias[$categoria]['unidades'] += $item['cantidad_vendida'];
        $resumen_categorias[$categoria]['total_usd'] += $item['subtotal_usd'];
        $resumen_categorias[$categoria]['total_bs'] += $item['subtotal_bs'];
    }
}

if (empty($resumen_clientes) && !empty($reporte)) {
    $resumen_clientes = [];
    foreach ($reporte as $item) {
        $cliente = $item['cliente_nombre'];
        if (!isset($resumen_clientes[$cliente])) {
            $resumen_clientes[$cliente] = [
                'nombre' => $cliente,
                'ventas' => 0,
                'total_usd' => 0,
                'total_bs' => 0
            ];
        }
        $resumen_clientes[$cliente]['ventas']++;
        $resumen_clientes[$cliente]['total_usd'] += $item['subtotal_usd'];
        $resumen_clientes[$cliente]['total_bs'] += $item['subtotal_bs'];
    }
    // Convertir a array indexado
    $resumen_clientes = array_values($resumen_clientes);
}

$page_title = "Reporte de Cierre de Caja - " . date('d/m/Y', strtotime($cierre['fecha']));
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <!-- Header con opciones de impresión -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">
                <i class="fas fa-file-alt me-2"></i>
                Reporte de Cierre de Caja
            </h1>
            <p class="text-muted mb-0">
                Fecha: <?php echo date('d/m/Y', strtotime($cierre['fecha'])); ?> 
                | Usuario: <?php echo htmlspecialchars($cierre['usuario_nombre']); ?>
                | Cierre ID: <?php echo $cierre['id']; ?>
            </p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button onclick="window.print()" class="btn btn-outline-secondary me-2">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
            <a href="index.php" class="btn btn-outline-primary ms-2">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Encabezado del Reporte -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="card-title mb-0">RESUMEN GENERAL</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <div class="border rounded p-3 mb-3">
                        <h6 class="text-muted">VENTAS</h6>
                        <h2 class="text-primary"><?php echo $cierre['total_ventas']; ?></h2>
                    </div>
                </div>
                <div class="col-md-3 text-center">
                    <div class="border rounded p-3 mb-3">
                        <h6 class="text-muted">UNIDADES</h6>
                        <h2 class="text-success"><?php echo $cierre['total_unidades']; ?></h2>
                    </div>
                </div>
                <div class="col-md-3 text-center">
                    <div class="border rounded p-3 mb-3">
                        <h6 class="text-muted">TOTAL USD</h6>
                        <h2 class="text-warning"><?php echo TasaCambioHelper::formatearUSD($cierre['total_usd']); ?></h2>
                    </div>
                </div>
                <div class="col-md-3 text-center">
                    <div class="border rounded p-3 mb-3">
                        <h6 class="text-muted">TOTAL BS</h6>
                        <h2 class="text-danger"><?php echo TasaCambioHelper::formatearBS($cierre['total_bs']); ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Totales por Tipo de Pago -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">TOTALES EN USD POR TIPO DE PAGO</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Efectivo USD</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cierre['efectivo_usd']); ?></td>
                                </tr>
                                <tr>
                                    <td>Efectivo BS</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cierre['efectivo_bs_usd']); ?></td>
                                </tr>
                                <tr>
                                    <td>Transferencia</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cierre['transferencia_usd']); ?></td>
                                </tr>
                                <tr>
                                    <td>Pago Móvil</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cierre['pago_movil_usd']); ?></td>
                                </tr>
                                <tr>
                                    <td>Tarjeta Débito</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cierre['tarjeta_debito_usd']); ?></td>
                                </tr>
                                <tr>
                                    <td>Tarjeta Crédito</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cierre['tarjeta_credito_usd']); ?></td>
                                </tr>
                                <tr>
                                    <td>Divisa</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cierre['divisa_usd']); ?></td>
                                </tr>
                                <tr>
                                    <td>Crédito</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cierre['credito_usd']); ?></td>
                                </tr>
                                <tr class="table-dark">
                                    <td><strong>TOTAL USD</strong></td>
                                    <td class="text-end"><strong><?php echo TasaCambioHelper::formatearUSD($cierre['total_usd']); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">TOTALES EN BS POR TIPO DE PAGO</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Efectivo USD</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cierre['efectivo_bs']); ?></td>
                                </tr>
                                <tr>
                                    <td>Efectivo BS</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cierre['efectivo_bs_bs']); ?></td>
                                </tr>
                                <tr>
                                    <td>Transferencia</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cierre['transferencia_bs']); ?></td>
                                </tr>
                                <tr>
                                    <td>Pago Móvil</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cierre['pago_movil_bs']); ?></td>
                                </tr>
                                <tr>
                                    <td>Tarjeta Débito</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cierre['tarjeta_debito_bs']); ?></td>
                                </tr>
                                <tr>
                                    <td>Tarjeta Crédito</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cierre['tarjeta_credito_bs']); ?></td>
                                </tr>
                                <tr>
                                    <td>Divisa</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cierre['divisa_bs']); ?></td>
                                </tr>
                                <tr>
                                    <td>Crédito</td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cierre['credito_bs']); ?></td>
                                </tr>
                                <tr class="table-dark">
                                    <td><strong>TOTAL BS</strong></td>
                                    <td class="text-end"><strong><?php echo TasaCambioHelper::formatearBS($cierre['total_bs']); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reporte Detallado -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list-alt me-2"></i>
                REPORTE DETALLADO DE VENTAS
            </h5>
        </div>
        <div class="card-body">
            <?php if (!empty($reporte)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="tablaReporte">
                        <thead class="table-dark">
                            <tr>
                                <th>Categoría</th>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Precio Unitario USD</th>
                                <th class="text-end">Subtotal USD</th>
                                <th class="text-end">Precio Unitario BS</th>
                                <th class="text-end">Subtotal BS</th>
                                <th>Tipo de Pago</th>
                                <th>Cliente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_unidades = 0;
                            $total_usd = 0;
                            $total_bs = 0;
                            ?>
                            <?php foreach ($reporte as $item): ?>
                                <?php 
                                $total_unidades += $item['cantidad_vendida'];
                                $total_usd += $item['subtotal_usd'];
                                $total_bs += $item['subtotal_bs'];
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['categoria_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($item['producto_nombre']); ?></td>
                                    <td class="text-center"><?php echo $item['cantidad_vendida']; ?></td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($item['precio_unitario_usd']); ?></td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($item['subtotal_usd']); ?></td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($item['precio_unitario_bs']); ?></td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($item['subtotal_bs']); ?></td>
                                    <td><?php echo htmlspecialchars($item['tipo_pago']); ?></td>
                                    <td><?php echo htmlspecialchars($item['cliente_nombre']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <td colspan="2"><strong>TOTALES</strong></td>
                                <td class="text-center"><strong><?php echo $total_unidades; ?></strong></td>
                                <td colspan="2" class="text-end"><strong><?php echo TasaCambioHelper::formatearUSD($total_usd); ?></strong></td>
                                <td colspan="2" class="text-end"><strong><?php echo TasaCambioHelper::formatearBS($total_bs); ?></strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay detalles de ventas para mostrar</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumen por Categoría -->
    <?php if (!empty($resumen_categorias)): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-tags me-2"></i>
                        RESUMEN POR CATEGORÍA
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th class="text-center">Unidades</th>
                                    <th class="text-end">Total USD</th>
                                    <th class="text-end">Total BS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resumen_categorias as $categoria => $datos): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($categoria); ?></td>
                                        <td class="text-center"><?php echo $datos['unidades']; ?></td>
                                        <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($datos['total_usd']); ?></td>
                                        <td class="text-end"><?php echo TasaCambioHelper::formatearBS($datos['total_bs']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>
                        RESUMEN POR CLIENTE
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th class="text-center">Ventas</th>
                                    <th class="text-end">Total USD</th>
                                    <th class="text-end">Total BS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resumen_clientes as $cliente): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                                        <td class="text-center"><?php echo $cliente['ventas']; ?></td>
                                        <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cliente['total_usd']); ?></td>
                                        <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cliente['total_bs']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Observaciones -->
    <?php if (!empty($cierre['observaciones'])): ?>
    <div class="card">
        <div class="card-header bg-light">
            <h6 class="card-title mb-0">
                <i class="fas fa-sticky-note me-2"></i>
                OBSERVACIONES
            </h6>
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($cierre['observaciones'])); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pie del Reporte -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6>REALIZADO POR</h6>
                    <p class="mb-0">________________________________</p>
                    <p class="mb-0"><?php echo htmlspecialchars($cierre['usuario_nombre']); ?></p>
                    <p class="text-muted">Usuario del Sistema</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6>FECHA Y HORA</h6>
                    <p class="mb-0">________________________________</p>
                    <p class="mb-0"><?php echo date('d/m/Y H:i:s', strtotime($cierre['created_at'])); ?></p>
                    <p class="text-muted">Fecha del Cierre</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar DataTables para el reporte
        $('#tablaReporte').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            pageLength: 20,
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });

        // Estilos para impresión
        const printStyles = `
            @media print {
                .btn, .btn-toolbar, .dataTables_length, .dataTables_filter, 
                .dataTables_info, .dataTables_paginate {
                    display: none !important;
                }
                .card {
                    border: 1px solid #000 !important;
                }
                .table-bordered th, .table-bordered td {
                    border: 1px solid #000 !important;
                }
                .content-wrapper {
                    margin: 0 !important;
                    padding: 0 !important;
                }
            }
        `;
        
        const styleSheet = document.createElement("style");
        styleSheet.innerText = printStyles;
        document.head.appendChild(styleSheet);
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->