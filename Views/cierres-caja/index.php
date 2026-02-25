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

// Verificar si hay cierres hoy (puede haber múltiples)
$cierres_hoy_count = $cierreController->existeCierreHoy();
$ultimo_cierre_hoy = $cierreController->obtenerUltimoCierreHoy();

// Obtener resumen de ventas pendientes
$resumen_pendientes = $cierreController->obtenerResumenPendientes();

// Obtener listado de cierres anteriores
$cierres = $cierreController->listarCierres(30);

// Procesar creación de nuevo cierre AUTOMÁTICO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_cierre') {
    $observaciones = $_POST['observaciones'] ?? '';
    $usuario_id = $_SESSION['user_id'];
    
    $resultado = $cierreController->crearCierreAutomatico($usuario_id, $observaciones);
    
    if ($resultado['success']) {
        $_SESSION['success_message'] = $resultado['message'];
        header("Location: ver.php?id=" . $resultado['cierre_id']);
        exit();
    } else {
        $error_message = $resultado['message'];
    }
}

$page_title = "Cierre de Caja Automático";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <!-- Header Section -->
    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-cash-register me-2"></i>Cierre de Caja Automático
            <?php if ($cierres_hoy_count > 0): ?>
            <small class="text-muted">(<?php echo $cierres_hoy_count; ?>
                cierre<?php echo $cierres_hoy_count > 1 ? 's' : ''; ?> hoy)</small>
            <?php endif; ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="historial.php" class="btn btn-outline-secondary">
                <i class="fas fa-history me-2"></i>Historial Completo
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
            <!-- Ventas Pendientes de Cierre -->
            <div class="card mb-4">
                <div
                    class="card-header bg-<?php echo $resumen_pendientes['success'] && $resumen_pendientes['data']['total_ventas'] > 0 ? 'warning' : 'success'; ?> text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Ventas Pendientes de Cierre
                        <?php if ($resumen_pendientes['success'] && $resumen_pendientes['data']['total_ventas'] > 0): ?>
                        <span class="badge bg-light text-dark ms-2">
                            <?php echo $resumen_pendientes['data']['total_ventas']; ?> ventas
                        </span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($resumen_pendientes['success'] && $resumen_pendientes['data']['total_ventas'] > 0): ?>
                    <!-- Resumen de Totales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-info text-white mb-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Ventas Pendientes</h6>
                                    <h3><?php echo $resumen_pendientes['data']['total_ventas']; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white mb-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Unidades</h6>
                                    <h3><?php echo $resumen_pendientes['data']['total_unidades']; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white mb-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Total USD</h6>
                                    <h5><?php echo $resumen_pendientes['data']['total_usd']; ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white mb-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Total BS</h6>
                                    <h5><?php echo $resumen_pendientes['data']['total_bs']; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Ventas Pendientes -->
                    <div class="mb-4">
                        <h6 class="mb-3">Ventas que se incluirán en el próximo cierre:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th># Venta</th>
                                        <th>Fecha/Hora</th>
                                        <th>Cliente</th>
                                        <th class="text-end">Total USD</th>
                                        <th class="text-end">Total BS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumen_pendientes['data']['ventas_lista'] as $venta): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($venta['numero_venta']); ?></td>
                                        <td><?php echo $venta['fecha_hora']; ?></td>
                                        <td><?php echo htmlspecialchars($venta['cliente']); ?></td>
                                        <td class="text-end"><?php echo $venta['total_usd']; ?></td>
                                        <td class="text-end"><?php echo $venta['total_bs']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4 class="text-success">¡Todo al día!</h4>
                        <p class="text-muted">No hay ventas pendientes de cierre.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Panel de Cierre Automático -->
            <div class="card">
                <div
                    class="card-header bg-<?php echo $resumen_pendientes['success'] && $resumen_pendientes['data']['total_ventas'] > 0 ? 'danger' : 'secondary'; ?> text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-robot me-2"></i>
                        Cierre Automático
                        <?php if ($cierres_hoy_count > 0): ?>
                        <small class="text-light">(<?php echo $cierres_hoy_count; ?> hoy)</small>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body text-center">
                    <?php if ($resumen_pendientes['success'] && $resumen_pendientes['data']['total_ventas'] > 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h5>¡Atención!</h5>
                        <p class="mb-0">Hay <?php echo $resumen_pendientes['data']['total_ventas']; ?> ventas pendientes
                            de cierre.</p>
                    </div>

                    <!-- Formulario para cerrar caja automáticamente -->
                    <form method="POST" action="" id="formCierreAutomatico">
                        <input type="hidden" name="action" value="crear_cierre">
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones (opcional):</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                placeholder="Ej: Cierre turno mañana, Cierre especial, etc..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 btn-lg" id="btnCerrarCaja">
                            <i class="fas fa-lock me-2"></i> Realizar Cierre Automático
                        </button>
                    </form>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Puedes realizar múltiples cierres al día. Cada cierre toma TODAS las ventas pendientes.
                        </small>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle fa-2x mb-3"></i>
                        <h5>Sistema Actualizado</h5>
                        <p class="mb-0">No hay ventas pendientes de cierre.</p>
                    </div>
                    <button type="button" class="btn btn-secondary w-100" disabled>
                        <i class="fas fa-lock me-1"></i> No hay ventas para cerrar
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Último Cierre del Día -->
            <?php if ($ultimo_cierre_hoy): ?>
            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Último Cierre Hoy
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <h5><?php echo $ultimo_cierre_hoy['numero_cierre'] ?? 'Cierre #' . $ultimo_cierre_hoy['id']; ?>
                        </h5>
                        <p class="text-muted mb-2">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($ultimo_cierre_hoy['usuario_nombre']); ?>
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date('H:i', strtotime($ultimo_cierre_hoy['created_at'])); ?>
                        </p>
                        <div class="row mt-3">
                            <div class="col-6">
                                <h6>Ventas</h6>
                                <h4 class="text-primary"><?php echo $ultimo_cierre_hoy['total_ventas']; ?></h4>
                            </div>
                            <div class="col-6">
                                <h6>Total BS</h6>
                                <h5 class="text-success">
                                    <?php echo TasaCambioHelper::formatearBS($ultimo_cierre_hoy['total_bs']); ?></h5>
                            </div>
                        </div>
                        <a href="ver.php?id=<?php echo $ultimo_cierre_hoy['id']; ?>"
                            class="btn btn-sm btn-outline-primary mt-3 w-100">
                            <i class="fas fa-eye me-1"></i> Ver Detalles
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
                            <strong>Fecha:</strong> <?php echo date('d/m/Y'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-user text-success me-2"></i>
                            <strong>Usuario:</strong>
                            <?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'Usuario'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock text-warning me-2"></i>
                            <strong>Hora:</strong> <?php echo date('H:i:s'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-shopping-cart text-info me-2"></i>
                            <strong>Ventas pendientes:</strong>
                            <?php if ($resumen_pendientes['success']): ?>
                            <span
                                class="badge bg-<?php echo $resumen_pendientes['data']['total_ventas'] > 0 ? 'warning' : 'success'; ?>">
                                <?php echo $resumen_pendientes['data']['total_ventas']; ?>
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary">0</span>
                            <?php endif; ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-cash-register text-danger me-2"></i>
                            <strong>Cierres hoy:</strong>
                            <span class="badge bg-<?php echo $cierres_hoy_count > 0 ? 'success' : 'secondary'; ?>">
                                <?php echo $cierres_hoy_count; ?>
                            </span>
                        </li>
                    </ul>

                    <!-- Descripción del sistema -->
                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-cogs me-2"></i>¿Cómo funciona?</h6>
                        <p class="mb-0 small">
                            <strong>Ahora puedes realizar múltiples cierres al día.</strong><br>
                            Cada cierre toma automáticamente TODAS las ventas pendientes y genera un reporte
                            independiente.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cierres Recientes (Ahora incluye múltiples del mismo día) -->
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
                            <th># Cierre</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Usuario</th>
                            <th>Ventas</th>
                            <th>Total USD</th>
                            <th>Total Bs</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cierres['data'] as $cierre): ?>
                        <tr>
                            <td>
                                <?php if (isset($cierre['numero_cierre'])): ?>
                                <span class="badge bg-primary"><?php echo $cierre['numero_cierre']; ?></span>
                                <?php else: ?>
                                <span class="badge bg-secondary">#<?php echo $cierre['id']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($cierre['fecha'])); ?></td>
                            <td><?php echo date('H:i', strtotime($cierre['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($cierre['usuario_nombre']); ?></td>
                            <td class="text-center"><?php echo $cierre['total_ventas']; ?></td>
                            <td class="text-end"><?php echo TasaCambioHelper::formatearUSD($cierre['total_usd']); ?>
                            </td>
                            <td class="text-end"><?php echo TasaCambioHelper::formatearBS($cierre['total_bs']); ?></td>
                            <td>
                                <a href="ver.php?id=<?php echo $cierre['id']; ?>" class="btn btn-sm btn-outline-primary"
                                    title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="reporte.php?id=<?php echo $cierre['id']; ?>" target="_blank"
                                    class="btn btn-sm btn-outline-success" title="Descargar Reporte">
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
    document.addEventListener('DOMContentLoaded', function () {
        // Configurar DataTables
        $('#tablaCierres').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            order: [
                [0, 'desc']
            ],
            pageLength: 10,
            responsive: true
        });

        // Confirmar cierre automático
        document.getElementById('formCierreAutomatico').addEventListener('submit', function (e) {
            e.preventDefault();

            const ventasPendientes = < ? php echo $resumen_pendientes['success'] ? $resumen_pendientes[
                'data']['total_ventas'] : 0; ? > ;

            if (ventasPendientes === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No hay ventas pendientes para cerrar'
                });
                return;
            }

            Swal.fire({
                title: '¿Confirmar nuevo cierre automático?',
                html: `<div class="text-start">
                          <p>El sistema <strong>automáticamente</strong> tomará <strong>${ventasPendientes} ventas pendientes</strong> y:</p>
                          <ul>
                            <li>Marcará todas las ventas como cerradas</li>
                            <li>Generará un nuevo reporte de cierre</li>
                            <li>Creará un registro independiente</li>
                            <li>Puedes realizar múltiples cierres hoy</li>
                          </ul>
                          <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, realizar nuevo cierre',
                cancelButtonText: 'Cancelar',
                width: '600px'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar loading
                    const btn = document.getElementById('btnCerrarCaja');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando...';
                    btn.disabled = true;

                    // Enviar formulario
                    this.submit();
                }
            });
        });

        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->
