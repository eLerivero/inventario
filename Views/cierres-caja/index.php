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
require_once __DIR__ . '/../../Helpers/TasaCambioHelper.php';

$database = new Database();
$db = $database->getConnection();
$cierreController = new CierreCajaController($db);

// ============================================
// VARIABLES CON VALORES POR DEFECTO ABSOLUTOS
// ============================================

// 1. Cierres hoy - SIEMPRE un número
$cierres_hoy_count = 0;
try {
    $temp = $cierreController->existeCierreHoy();
    $cierres_hoy_count = is_numeric($temp) ? (int)$temp : 0;
} catch (Exception $e) {
    error_log("Error en existeCierreHoy: " . $e->getMessage());
}

// 2. Último cierre - SIEMPRE un array
$ultimo_cierre_hoy = [];
try {
    $temp = $cierreController->obtenerUltimoCierreHoy();
    $ultimo_cierre_hoy = is_array($temp) ? $temp : [];
} catch (Exception $e) {
    error_log("Error en obtenerUltimoCierreHoy: " . $e->getMessage());
}

// 3. Resumen pendientes - SIEMPRE con estructura completa
$resumen_data = [
    'total_ventas' => 0,
    'total_unidades' => 0,
    'total_usd' => '$0.00',
    'total_bs' => 'Bs 0,00',
    'ventas_lista' => []
];
$total_ventas_pendientes = 0;

try {
    $resumen_pendientes = $cierreController->obtenerResumenPendientes();
    
    if (is_array($resumen_pendientes)) {
        if (isset($resumen_pendientes['success']) && $resumen_pendientes['success'] === true) {
            if (isset($resumen_pendientes['data']) && is_array($resumen_pendientes['data'])) {
                $resumen_data = $resumen_pendientes['data'];
                $total_ventas_pendientes = isset($resumen_data['total_ventas']) ? 
                    (int)$resumen_data['total_ventas'] : 0;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error al obtener resumen pendientes: " . $e->getMessage());
}

// 4. Cierres anteriores - SIEMPRE un array
$cierres_data = [];
$cierres_success = false;
try {
    $cierres = $cierreController->listarCierres(30);
    if (is_array($cierres)) {
        $cierres_success = isset($cierres['success']) ? $cierres['success'] : false;
        $cierres_data = isset($cierres['data']) && is_array($cierres['data']) ? $cierres['data'] : [];
    }
} catch (Exception $e) {
    error_log("Error en listarCierres: " . $e->getMessage());
}

// Procesar creación de nuevo cierre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_cierre') {
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    $usuario_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    
    $resultado = $cierreController->crearCierreAutomatico($usuario_id, $observaciones);
    
    if (is_array($resultado) && isset($resultado['success']) && $resultado['success']) {
        $_SESSION['success_message'] = isset($resultado['message']) ? $resultado['message'] : 'Cierre exitoso';
        $cierre_id = isset($resultado['cierre_id']) ? $resultado['cierre_id'] : 0;
        if ($cierre_id > 0) {
            header("Location: ver.php?id=" . $cierre_id);
            exit();
        } else {
            header("Location: index.php");
            exit();
        }
    } else {
        $error_message = isset($resultado['message']) ? $resultado['message'] : 'Error desconocido';
    }
}

$page_title = "Cierre de Caja Automático";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <!-- Header Section -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
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
        <?php echo htmlspecialchars($_SESSION['success_message']); ?>
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
                <div class="card-header bg-<?php echo $total_ventas_pendientes > 0 ? 'warning' : 'success'; ?> text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Ventas Pendientes de Cierre
                        <?php if ($total_ventas_pendientes > 0): ?>
                            <span class="badge bg-light text-dark ms-2">
                                <?php echo $total_ventas_pendientes; ?> ventas
                            </span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($total_ventas_pendientes > 0): ?>
                        <!-- Resumen de Totales -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-info text-white mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Ventas Pendientes</h6>
                                        <h3><?php echo isset($resumen_data['total_ventas']) ? $resumen_data['total_ventas'] : 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Unidades</h6>
                                        <h3><?php echo isset($resumen_data['total_unidades']) ? $resumen_data['total_unidades'] : 0; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total USD</h6>
                                        <h5><?php echo isset($resumen_data['total_usd']) ? $resumen_data['total_usd'] : '$0.00'; ?></h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total BS</h6>
                                        <h5><?php echo isset($resumen_data['total_bs']) ? $resumen_data['total_bs'] : 'Bs 0,00'; ?></h5>
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
                                        <?php if (!empty($resumen_data['ventas_lista'])): ?>
                                            <?php foreach ($resumen_data['ventas_lista'] as $venta): ?>
                                            <tr>
                                                <td><?php echo isset($venta['numero_venta']) ? htmlspecialchars($venta['numero_venta']) : 'N/A'; ?></td>
                                                <td><?php echo isset($venta['fecha_hora']) ? $venta['fecha_hora'] : 'N/A'; ?></td>
                                                <td><?php echo isset($venta['cliente']) ? htmlspecialchars($venta['cliente']) : 'N/A'; ?></td>
                                                <td class="text-end"><?php echo isset($venta['total_usd']) ? $venta['total_usd'] : '$0.00'; ?></td>
                                                <td class="text-end"><?php echo isset($venta['total_bs']) ? $venta['total_bs'] : 'Bs 0,00'; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No hay ventas pendientes</td>
                                            </tr>
                                        <?php endif; ?>
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
            <div class="card mb-4">
                <div class="card-header bg-<?php echo $total_ventas_pendientes > 0 ? 'danger' : 'secondary'; ?> text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-robot me-2"></i>
                        Cierre Automático
                        <?php if ($cierres_hoy_count > 0): ?>
                        <small class="text-light">(<?php echo $cierres_hoy_count; ?> hoy)</small>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body text-center">
                    <?php if ($total_ventas_pendientes > 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <h5>¡Atención!</h5>
                        <p class="mb-0">Hay <?php echo $total_ventas_pendientes; ?> ventas pendientes de cierre.</p>
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
            <?php if (!empty($ultimo_cierre_hoy)): ?>
            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Último Cierre Hoy
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <h5><?php 
                            $numero = isset($ultimo_cierre_hoy['numero_cierre']) ? $ultimo_cierre_hoy['numero_cierre'] : '';
                            $id = isset($ultimo_cierre_hoy['id']) ? $ultimo_cierre_hoy['id'] : '';
                            echo $numero ?: 'Cierre #' . $id; 
                        ?></h5>
                        <p class="text-muted mb-2">
                            <i class="fas fa-user me-1"></i>
                            <?php echo isset($ultimo_cierre_hoy['usuario_nombre']) ? htmlspecialchars($ultimo_cierre_hoy['usuario_nombre']) : 'Usuario'; ?>
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-clock me-1"></i>
                            <?php 
                            $created = isset($ultimo_cierre_hoy['created_at']) ? $ultimo_cierre_hoy['created_at'] : '';
                            echo $created ? date('H:i', strtotime($created)) : 'N/A'; 
                            ?>
                        </p>
                        <div class="row mt-3">
                            <div class="col-6">
                                <h6>Ventas</h6>
                                <h4 class="text-primary"><?php echo isset($ultimo_cierre_hoy['total_ventas']) ? $ultimo_cierre_hoy['total_ventas'] : 0; ?></h4>
                            </div>
                            <div class="col-6">
                                <h6>Total BS</h6>
                                <h5 class="text-success">
                                    <?php 
                                    $total_bs = isset($ultimo_cierre_hoy['total_bs']) ? $ultimo_cierre_hoy['total_bs'] : 0;
                                    echo TasaCambioHelper::formatearBS($total_bs); 
                                    ?>
                                </h5>
                            </div>
                        </div>
                        <?php if (isset($ultimo_cierre_hoy['id'])): ?>
                        <a href="ver.php?id=<?php echo $ultimo_cierre_hoy['id']; ?>"
                            class="btn btn-sm btn-outline-primary mt-3 w-100">
                            <i class="fas fa-eye me-1"></i> Ver Detalles
                        </a>
                        <?php endif; ?>
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
                            <?php echo isset($_SESSION['user_nombre']) ? htmlspecialchars($_SESSION['user_nombre']) : 'Usuario'; ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock text-warning me-2"></i>
                            <strong>Hora:</strong> <?php echo date('H:i:s'); ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-shopping-cart text-info me-2"></i>
                            <strong>Ventas pendientes:</strong>
                            <span class="badge bg-<?php echo $total_ventas_pendientes > 0 ? 'warning' : 'success'; ?>">
                                <?php echo $total_ventas_pendientes; ?>
                            </span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-cash-register text-danger me-2"></i>
                            <strong>Cierres hoy:</strong>
                            <span class="badge bg-<?php echo $cierres_hoy_count > 0 ? 'success' : 'secondary'; ?>">
                                <?php echo $cierres_hoy_count; ?>
                            </span>
                        </li>
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
            <?php if ($cierres_success && !empty($cierres_data)): ?>
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
                        <?php foreach ($cierres_data as $cierre): ?>
                        <tr>
                            <td>
                                <?php if (isset($cierre['numero_cierre'])): ?>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($cierre['numero_cierre']); ?></span>
                                <?php else: ?>
                                <span class="badge bg-secondary">#<?php echo isset($cierre['id']) ? $cierre['id'] : ''; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo isset($cierre['fecha']) ? date('d/m/Y', strtotime($cierre['fecha'])) : 'N/A'; ?></td>
                            <td><?php echo isset($cierre['created_at']) ? date('H:i', strtotime($cierre['created_at'])) : 'N/A'; ?></td>
                            <td><?php echo isset($cierre['usuario_nombre']) ? htmlspecialchars($cierre['usuario_nombre']) : 'N/A'; ?></td>
                            <td class="text-center"><?php echo isset($cierre['total_ventas']) ? $cierre['total_ventas'] : 0; ?></td>
                            <td class="text-end"><?php echo isset($cierre['total_usd']) ? TasaCambioHelper::formatearUSD($cierre['total_usd']) : '$0.00'; ?></td>
                            <td class="text-end"><?php echo isset($cierre['total_bs']) ? TasaCambioHelper::formatearBS($cierre['total_bs']) : 'Bs 0,00'; ?></td>
                            <td>
                                <?php if (isset($cierre['id'])): ?>
                                <a href="ver.php?id=<?php echo $cierre['id']; ?>" class="btn btn-sm btn-outline-primary"
                                    title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="reporte.php?id=<?php echo $cierre['id']; ?>" target="_blank"
                                    class="btn btn-sm btn-outline-success" title="Descargar Reporte">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <?php endif; ?>
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

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery (para DataTables) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Configurar DataTables si existe la tabla
        if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.DataTable) {
            const tabla = document.getElementById('tablaCierres');
            if (tabla) {
                try {
                    jQuery('#tablaCierres').DataTable({
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                        },
                        order: [[0, 'desc']],
                        pageLength: 10,
                        responsive: true
                    });
                } catch (e) {
                    console.log('Error al inicializar DataTable:', e);
                }
            }
        }

        // Confirmar cierre automático
        const formCierre = document.getElementById('formCierreAutomatico');
        if (formCierre) {
            formCierre.addEventListener('submit', function (e) {
                e.preventDefault();

                const ventasPendientes = <?php echo $total_ventas_pendientes; ?>;

                if (ventasPendientes === 0) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No hay ventas pendientes para cerrar'
                        });
                    } else {
                        alert('No hay ventas pendientes para cerrar');
                    }
                    return;
                }

                if (typeof Swal !== 'undefined') {
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
                            if (btn) {
                                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando...';
                                btn.disabled = true;
                            }
                            // Enviar formulario
                            this.submit();
                        }
                    });
                } else {
                    if (confirm('¿Estás seguro de realizar el cierre automático? Esta acción no se puede deshacer.')) {
                        const btn = document.getElementById('btnCerrarCaja');
                        if (btn) {
                            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando...';
                            btn.disabled = true;
                        }
                        this.submit();
                    }
                }
            });
        }

        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert) {
                    try {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        } else {
                            alert.style.display = 'none';
                        }
                    } catch (e) {
                        alert.style.display = 'none';
                    }
                }
            });
        }, 5000);
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->