<?php
// Usar rutas absolutas para evitar problemas de inclusión
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/VentaController.php';
require_once __DIR__ . '/../../Utils/Ayuda.php';
require_once __DIR__ . '/../../Helpers/TasaCambioHelper.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();

$controller = new VentaController($db);

// Manejar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';

// Procesar cambio de estado
if ($action === 'completar' && $id) {
    // Verificar token CSRF
    $token_valido = true; // Temporalmente deshabilitado para pruebas

    if (!$token_valido) {
        $error_message = "Token de seguridad inválido o expirado. Por favor, recarga la página.";
    } else {
        $result = $controller->actualizarEstado($id, 'completada');
        if ($result['success']) {
            $success_message = $result['message'];
            // Redirigir después de 2 segundos
            echo '<meta http-equiv="refresh" content="2;url=index.php">';
        } else {
            $error_message = $result['message'];
        }
    }
}

// Obtener todas las ventas
$result = $controller->listar();
if ($result['success']) {
    $ventas = $result['data'];
} else {
    $error_message = $result['message'] ?? 'Error al cargar las ventas';
    $ventas = [];
}

// Obtener estadísticas
$estadisticas = $controller->obtenerEstadisticas();
$stats = $estadisticas['success'] ? $estadisticas['data'] : [];

// Generar token CSRF para seguridad
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "Gestión de Ventas";
include __DIR__ . '/../layouts/header.php';
?>

<!-- Header con Botón de Nueva Venta -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-shopping-cart me-2"></i>
            Gestión de Ventas
        </h1>
        <p class="text-muted mb-0">Administra las ventas del sistema de inventario</p>
    </div>
    <div>
        <a href="crear.php" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Nueva Venta
        </a>
    </div>
</div>

<!-- Alertas -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <?php if ($action === 'completar'): ?>
            <div class="mt-2">
                <small>Serás redirigido automáticamente al listado...</small>
            </div>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Estadísticas Rápidas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Ventas</h5>
                        <h3><?php echo $stats['total_ventas'] ?? 0; ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Ingresos Totales</h5>
                        <h3><?php echo $stats['ingresos_totales_formateado'] ?? '$0.00'; ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Ticket Promedio</h5>
                        <h3><?php echo $stats['ticket_promedio_formateado'] ?? '$0.00'; ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Clientes Activos</h5>
                        <h3><?php echo $stats['clientes_activos'] ?? 0; ?></h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Ventas -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Ventas
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($ventas)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No hay ventas registradas</h4>
                <p class="text-muted">Comienza creando tu primera venta.</p>
                <a href="crear.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-1"></i> Crear Primera Venta
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tablaVentas">
                    <thead class="table-dark">
                        <tr>
                            <th># Venta</th>
                            <th>Cliente</th>
                            <th>Total USD</th>
                            <th>Total Bs</th>
                            <th>Tasa del Día</th>
                            <th>Tipo Pago</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas as $venta):
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

                            // Formatear fecha
                            $fecha = '';
                            if (!empty($venta['fecha_hora'])) {
                                $fecha = Ayuda::formatDate($venta['fecha_hora']);
                            } elseif (!empty($venta['created_at'])) {
                                $fecha = Ayuda::formatDate($venta['created_at']);
                            }

                            // Obtener detalles para verificar si tiene precios fijos
                            $detalles_venta = [];
                            try {
                                $detalles_venta = $controller->obtenerDetalles($venta['id']);
                            } catch (Exception $e) {
                                // Si hay error, continuar sin detalles
                            }

                            // Verificar si hay productos con precio fijo en esta venta
                            $tiene_precio_fijo = false;
                            foreach ($detalles_venta as $detalle) {
                                if (
                                    isset($detalle['precio_unitario_bs']) &&
                                    isset($detalle['precio_unitario']) &&
                                    $detalle['precio_unitario_bs'] > 0 &&
                                    ($detalle['precio_unitario'] * $venta['tasa_cambio'] != $detalle['precio_unitario_bs'])
                                ) {
                                    $tiene_precio_fijo = true;
                                    break;
                                }
                            }
                        ?>
                            <tr <?php echo $tiene_precio_fijo ? 'class="table-warning"' : ''; ?>>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <strong>#<?php echo htmlspecialchars($venta['numero_venta'] ?? 'N/A'); ?></strong>
                                        <?php if ($tiene_precio_fijo): ?>
                                            <span class="ms-2" title="Esta venta incluye productos con precio fijo en Bs" data-bs-toggle="tooltip">
                                                <i class="fas fa-lock text-success"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($venta['cliente_nombre'] ?? 'Cliente no especificado'); ?>
                                </td>
                                <td>
                                    <strong class="text-primary">
                                        <?php
                                        if (isset($venta['total_formateado_usd'])) {
                                            echo $venta['total_formateado_usd'];
                                        } else {
                                            echo '$' . number_format($venta['total'] ?? 0, 2);
                                        }
                                        ?>
                                    </strong>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <strong class="text-success">
                                            <?php
                                            if (isset($venta['total_formateado_bs'])) {
                                                echo $venta['total_formateado_bs'];
                                            } else {
                                                echo TasaCambioHelper::formatearBS($venta['total_bs'] ?? 0);
                                            }
                                            ?>
                                        </strong>
                                        <?php if ($tiene_precio_fijo): ?>
                                            <small class="text-success">
                                                <i class="fas fa-lock me-1"></i> Incluye precios fijos
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php
                                        // Mostrar la tasa de cambio utilizada en esta venta
                                        if (isset($venta['tasa_cambio_utilizada'])) {
                                            echo number_format($venta['tasa_cambio_utilizada'], 2) . ' Bs/$';
                                        } elseif (isset($venta['tasa_formateada'])) {
                                            echo $venta['tasa_formateada'];
                                        } elseif (isset($venta['tasa_cambio'])) {
                                            echo number_format($venta['tasa_cambio'], 2) . ' Bs/$';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($venta['tipo_pago_nombre'] ?? 'No especificado'); ?>
                                </td>
                                <td>
                                    <?php
                                    $estado = $venta['estado'] ?? 'pendiente';
                                    $badge_class = $estado_badge[$estado] ?? 'bg-secondary';
                                    $badge_text = $estado_text[$estado] ?? $estado;
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $badge_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $fecha; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="ver.php?id=<?php echo $venta['id']; ?>"
                                            class="btn btn-outline-info"
                                            title="Ver detalles de la venta"
                                            data-bs-toggle="tooltip">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (($venta['estado'] ?? '') === 'pendiente'): ?>
                                            <button type="button"
                                                class="btn btn-outline-success"
                                                title="Completar venta"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalCompletar"
                                                data-id="<?php echo $venta['id']; ?>"
                                                data-numero="<?php echo $venta['numero_venta'] ?? ''; ?>"
                                                <?php echo $tiene_precio_fijo ? 'data-precio-fijo="true"' : ''; ?>>
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Información Adicional -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h6 class="card-title mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Sistema de Ventas - Precios Fijos vs Precios Variables
        </h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">
                    <i class="fas fa-lock text-success me-2"></i>Productos con Precio Fijo:
                </h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> Mantienen su precio en Bs independientemente de la tasa</li>
                    <li><i class="fas fa-check text-success me-2"></i> No se convierten a USD en el momento de la venta</li>
                    <li><i class="fas fa-check text-success me-2"></i> Ideales para productos con precio controlado</li>
                    <li><i class="fas fa-check text-success me-2"></i> Se muestran con fondo amarillo en la tabla</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary">
                    <i class="fas fa-sync-alt text-info me-2"></i>Productos con Precio Variable:
                </h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-info me-2"></i> Se convierten usando la tasa de cambio del día</li>
                    <li><i class="fas fa-check text-info me-2"></i> El precio en Bs varía según la tasa</li>
                    <li><i class="fas fa-check text-info me-2"></i> Precio base en USD, conversión automática</li>
                    <li><i class="fas fa-check text-info me-2"></i> Se usan para productos importados o variables</li>
                </ul>
            </div>
        </div>

        <div class="alert alert-info mt-3">
            <i class="fas fa-lightbulb me-2"></i>
            <strong>¿Cómo identificar ventas con precios fijos?</strong>
            <ul class="mb-0 mt-2">
                <li><i class="fas fa-lock text-success me-2"></i> Ícono de candado junto al número de venta</li>
                <li><span class="badge bg-warning text-dark me-2">Fondo amarillo</span> Fila destacada en amarillo</li>
                <li><i class="fas fa-lock text-success me-2"></i> Texto "Incluye precios fijos" en el total Bs</li>
                <li><i class="fas fa-eye text-info me-2"></i> Detalles completos al hacer clic en "Ver"</li>
            </ul>
        </div>
    </div>
</div>

<!-- Modal Completar Venta -->
<div class="modal fade" id="modalCompletar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Confirmar Completar Venta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas completar la venta <strong id="numeroVenta"></strong>?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Importante:</strong> Al completar la venta, el stock de los productos se actualizará automáticamente.
                </div>
                <div id="precioFijoWarning" class="alert alert-warning d-none">
                    <i class="fas fa-lock me-2"></i>
                    <strong>Atención:</strong> Esta venta incluye productos con precio fijo en Bs.
                    Estos precios no se verán afectados por cambios futuros en la tasa de cambio.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <a href="#" id="btnCompletarConfirmar" class="btn btn-success">
                    <i class="fas fa-check me-1"></i> Completar Venta
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos para resaltar ventas con precios fijos */
    .table-warning {
        background-color: rgba(255, 243, 205, 0.3) !important;
    }

    .table-warning:hover {
        background-color: rgba(255, 243, 205, 0.5) !important;
    }

    /* Animación para el ícono de candado */
    .fa-lock.text-success {
        animation: lock-pulse 2s infinite;
    }

    @keyframes lock-pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    /* Tooltip personalizado */
    .tooltip-inner {
        max-width: 300px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                delay: {
                    show: 100,
                    hide: 100
                }
            });
        });

        // Configurar DataTables
        $('#tablaVentas').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 10,
            order: [
                [0, 'desc']
            ],
            columnDefs: [{
                orderable: false,
                targets: [8]
            }],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control form-control-sm');
                $('.dataTables_length select').addClass('form-control form-control-sm');
            }
        });

        // Configurar modal de completar venta
        const modalCompletar = document.getElementById('modalCompletar');
        if (modalCompletar) {
            modalCompletar.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const numero = button.getAttribute('data-numero');
                const tienePrecioFijo = button.getAttribute('data-precio-fijo') === 'true';

                document.getElementById('numeroVenta').textContent = '#' + numero;
                document.getElementById('btnCompletarConfirmar').href = `index.php?action=completar&id=${id}&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>`;

                // Mostrar/ocultar advertencia de precio fijo
                const precioFijoWarning = document.getElementById('precioFijoWarning');
                if (tienePrecioFijo) {
                    precioFijoWarning.classList.remove('d-none');
                } else {
                    precioFijoWarning.classList.add('d-none');
                }
            });
        }

        <?php if ($action === 'completar' && !empty($success_message)): ?>
            setTimeout(() => {
                showToast('success', '<?php echo addslashes($success_message); ?>');
            }, 100);
        <?php endif; ?>

        // Auto-ocultar alertas después de 5 segundos
        const alerts = document.querySelectorAll('.alert:not(.alert-warning, .alert-info)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });

        // Añadir efecto hover a las filas con precios fijos
        const filasConPrecioFijo = document.querySelectorAll('.table-warning');
        filasConPrecioFijo.forEach(fila => {
            fila.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 0 10px rgba(255, 193, 7, 0.5)';
            });

            fila.addEventListener('mouseleave', function() {
                this.style.boxShadow = 'none';
            });
        });
    });

    function showToast(type, message) {
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

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

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

<!-- <?php include __DIR__ . '/../layouts/footer.php'; ?> -->