<?php
// INICIAR SESIÓN PRIMERO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// REQUERIR ACCESO A CIERRE DE CAJA (SOLO ADMIN)
require_once __DIR__ . '/../../Utils/Auth.php';
Auth::requireAuth();
Auth::requireAccessToCierreCaja();

// Incluir controladores
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/CierreCajaController.php';

$database = new Database();
$db = $database->getConnection();
$cierreController = new CierreCajaController($db);

// Verificar si ya existe cierre hoy
$cierre_hoy = $cierreController->existeCierreHoy();

// Obtener resumen del día (sin cerrar aún)
$resumen_hoy = $cierreController->obtenerResumenHoy();

// Obtener listado de cierres anteriores
$cierres = $cierreController->listarCierres(30);

// Procesar creación de nuevo cierre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_cierre') {
    $observaciones = $_POST['observaciones'] ?? '';
    $usuario_id = $_SESSION['user_id'];
    
    $resultado = $cierreController->crearCierre($usuario_id, $observaciones);
    
    if ($resultado['success']) {
        $_SESSION['success_message'] = "Cierre de caja realizado exitosamente";
        header("Location: ver.php?id=" . $resultado['cierre_id']);
        exit();
    } else {
        $error_message = $resultado['message'];
    }
}

$page_title = "Cierre de Caja";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <!-- Header Section -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-cash-register me-2"></i>Cierre de Caja
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="historial.php" class="btn btn-outline-secondary">
                <i class="fas fa-history me-2"></i>Historial
            </a>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Panel Principal -->
    <div class="row">
        <div class="col-md-8">
            <!-- Resumen del Día -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Resumen del Día - <?php echo date('d/m/Y'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($resumen_hoy['success'] && $resumen_hoy['data']['total_ventas'] > 0): ?>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-info text-white mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Ventas</h6>
                                        <h3><?php echo $resumen_hoy['data']['total_ventas']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Unidades</h6>
                                        <h3><?php echo $resumen_hoy['data']['total_unidades']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total USD</h6>
                                        <h5><?php echo $resumen_hoy['data']['total_usd']; ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total Bs</h6>
                                        <h5><?php echo $resumen_hoy['data']['total_bs']; ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Totales por tipo de pago -->
                        <div class="mt-4">
                            <h6 class="mb-3">Totales por Tipo de Pago:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tipo de Pago</th>
                                            <th class="text-end">Total USD</th>
                                            <th class="text-end">Total Bs</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resumen_hoy['data']['totales_por_tipo'] as $tipo): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($tipo['tipo']); ?></td>
                                                <td class="text-end"><?php echo $tipo['total_usd']; ?></td>
                                                <td class="text-end"><?php echo $tipo['total_bs']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay ventas hoy</h4>
                            <p class="text-muted">No se han realizado ventas completadas hoy.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Panel de Cierre -->
            <div class="card">
                <div class="card-header bg-<?php echo $cierre_hoy ? 'success' : 'warning'; ?> text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-lock me-2"></i>
                        Estado de Caja
                    </h5>
                </div>
                <div class="card-body text-center">
                    <?php if ($cierre_hoy): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle fa-2x mb-3"></i>
                            <h5>Caja Cerrada</h5>
                            <p class="mb-0">El cierre de caja ya fue realizado hoy.</p>
                        </div>
                        <a href="ver.php?fecha=<?php echo date('Y-m-d'); ?>" class="btn btn-success w-100">
                            <i class="fas fa-eye me-1"></i> Ver Reporte del Día
                        </a>
                    <?php else: ?>
                        <?php if ($resumen_hoy['success'] && $resumen_hoy['data']['total_ventas'] > 0): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <h5>Caja Abierta</h5>
                                <p class="mb-0">Hay ventas pendientes de cierre.</p>
                            </div>
                            
                            <!-- Formulario para cerrar caja -->
                            <form method="POST" action="" onsubmit="return confirm('¿Estás seguro de realizar el cierre de caja? Esta acción no se puede deshacer.')">
                                <input type="hidden" name="action" value="crear_cierre">
                                <div class="mb-3">
                                    <label for="observaciones" class="form-label">Observaciones (opcional):</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" placeholder="Observaciones sobre el cierre..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-lock me-1"></i> Realizar Cierre de Caja
                                </button>
                            </form>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Al cerrar la caja se generará un reporte detallado de todas las ventas del día.
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <h5>Sin Movimientos</h5>
                                <p class="mb-0">No hay ventas para cerrar hoy.</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-calendar-day text-primary me-2"></i>
                            <strong>Fecha actual:</strong> <?php echo date('d/m/Y'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-user text-success me-2"></i>
                            <strong>Usuario:</strong> <?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock text-warning me-2"></i>
                            <strong>Hora:</strong> <?php echo date('H:i:s'); ?>
                        </li>
                        <?php if ($cierre_hoy): ?>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Estado:</strong> <span class="badge bg-success">Cerrado</span>
                            </li>
                        <?php else: ?>
                            <li class="mb-2">
                                <i class="fas fa-unlock text-warning me-2"></i>
                                <strong>Estado:</strong> <span class="badge bg-warning">Abierto</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Cierres Recientes -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-history me-2"></i>
                Cierres Recientes
            </h5>
        </div>
        <div class="card-body">
            <?php if ($cierres['success'] && !empty($cierres['data'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaCierres">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Ventas</th>
                                <th>Unidades</th>
                                <th>Total USD</th>
                                <th>Total Bs</th>
                                <th>Efectivo USD</th>
                                <th>Efectivo Bs</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cierres['data'] as $cierre): ?>
                                <tr>
                                    <td><?php echo $cierre['fecha_formateada']; ?></td>
                                    <td><?php echo htmlspecialchars($cierre['usuario_nombre']); ?></td>
                                    <td class="text-center"><?php echo $cierre['total_ventas']; ?></td>
                                    <td class="text-center"><?php echo $cierre['total_unidades']; ?></td>
                                    <td class="text-end"><?php echo $cierre['total_general_usd']; ?></td>
                                    <td class="text-end"><?php echo $cierre['total_general_bs']; ?></td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cierre['total_efectivo_usd']); ?></td>
                                    <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cierre['total_efectivo_bs']); ?></td>
                                    <td>
                                        <a href="ver.php?id=<?php echo $cierre['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="reporte.php?id=<?php echo $cierre['id']; ?>" target="_blank" class="btn btn-sm btn-outline-success" title="Descargar Reporte">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay cierres de caja registrados</h5>
                    <p class="text-muted">Realiza el primer cierre de caja para comenzar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar DataTables
        $('#tablaCierres').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 10,
            responsive: true
        });

        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->