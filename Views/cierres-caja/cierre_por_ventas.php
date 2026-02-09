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

// Obtener lista de ventas pendientes
$ventas_pendientes = $cierreController->obtenerListaVentasPendientes();

// Procesar creación de nuevo cierre por ventas específicas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_cierre_ventas') {
    $observaciones = $_POST['observaciones'] ?? '';
    $usuario_id = $_SESSION['user_id'];
    
    // Obtener ventas seleccionadas
    $ventas_seleccionadas = $_POST['ventas_seleccionadas'] ?? [];
    
    if (empty($ventas_seleccionadas)) {
        $error_message = "Debe seleccionar al menos una venta para cerrar";
    } else {
        $resultado = $cierreController->crearCierrePorVentas($usuario_id, $ventas_seleccionadas, $observaciones);
        
        if ($resultado['success']) {
            $_SESSION['success_message'] = "Cierre de caja realizado exitosamente. " . 
                                          $resultado['ventas_cerradas'] . " ventas marcadas como cerradas.";
            header("Location: ver.php?id=" . $resultado['cierre_id']);
            exit();
        } else {
            $error_message = $resultado['message'];
        }
    }
}

$page_title = "Cierre de Caja por Ventas";
require_once '../layouts/header.php';
?>

<div class="content-wrapper">
    <!-- Header Section -->
    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-cash-register me-2"></i>Cierre de Caja por Ventas
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Cierre Diario
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
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Ventas Pendientes de Cierre
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($ventas_pendientes['success'] && !empty($ventas_pendientes['data'])): ?>
                    <form method="POST" action="" id="formCierreVentas">
                        <input type="hidden" name="action" value="crear_cierre_ventas">

                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tablaVentas">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="seleccionar_todos">
                                            </div>
                                        </th>
                                        <th># Venta</th>
                                        <th>Fecha/Hora</th>
                                        <th>Cliente</th>
                                        <th>Tipo Pago</th>
                                        <th class="text-end">Total USD</th>
                                        <th class="text-end">Total BS</th>
                                        <th>Items</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $total_usd = 0;
                                        $total_bs = 0;
                                        $total_ventas = 0;
                                        ?>
                                    <?php foreach ($ventas_pendientes['data'] as $venta): ?>
                                    <?php 
                                            $total_usd += $venta['total'];
                                            $total_bs += $venta['total_bs'];
                                            $total_ventas++;
                                            ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input venta-checkbox" type="checkbox"
                                                    name="ventas_seleccionadas[]" value="<?php echo $venta['id']; ?>"
                                                    id="venta_<?php echo $venta['id']; ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <label for="venta_<?php echo $venta['id']; ?>" class="mb-0">
                                                <?php echo htmlspecialchars($venta['numero_venta']); ?>
                                            </label>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_hora'])); ?></td>
                                        <td><?php echo htmlspecialchars($venta['cliente_nombre'] ?? 'Cliente no identificado'); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($venta['tipo_pago_nombre']); ?></td>
                                        <td class="text-end">
                                            <?php echo TasaCambioHelper::formatearUSD($venta['total']); ?></td>
                                        <td class="text-end">
                                            <?php echo TasaCambioHelper::formatearBS($venta['total_bs']); ?></td>
                                        <td class="text-center"><?php echo $venta['items_count']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-dark">
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>TOTALES:</strong></td>
                                        <td class="text-end">
                                            <strong><?php echo TasaCambioHelper::formatearUSD($total_usd); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <strong><?php echo TasaCambioHelper::formatearBS($total_bs); ?></strong>
                                        </td>
                                        <td class="text-center"><strong><?php echo $total_ventas; ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Observaciones -->
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones (opcional):</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                placeholder="Observaciones sobre el cierre..."></textarea>
                        </div>

                        <!-- Botón de Cierre -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg" id="btnCerrarCaja">
                                <i class="fas fa-lock me-2"></i>
                                Cerrar Caja con Ventas Seleccionadas
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4 class="text-success">¡Todo al día!</h4>
                        <p class="text-muted">No hay ventas pendientes de cierre.</p>
                        <a href="index.php" class="btn btn-primary mt-3">
                            <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Panel de Información -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Instrucciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>¿Cómo funciona?</h6>
                        <p class="mb-2">Este sistema permite realizar cierres de caja por ventas específicas.</p>

                        <h6 class="mt-3"><i class="fas fa-list-check me-2"></i>Pasos:</h6>
                        <ol class="mb-0">
                            <li>Selecciona las ventas que deseas incluir en el cierre</li>
                            <li>Agrega observaciones si es necesario</li>
                            <li>Presiona "Cerrar Caja con Ventas Seleccionadas"</li>
                        </ol>
                    </div>

                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Importante:</h6>
                        <ul class="mb-0">
                            <li>Puedes cerrar caja múltiples veces al día</li>
                            <li>Las ventas cerradas no aparecerán en futuros cierres</li>
                            <li>Cada cierre genera un reporte específico</li>
                            <li>Esta acción no se puede deshacer</li>
                        </ul>
                    </div>

                    <!-- Resumen de Selección -->
                    <div class="card mt-3">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Resumen de Selección
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="resumenSeleccion">
                                <p class="text-muted mb-2">Selecciona ventas para ver el resumen</p>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h6>Ventas</h6>
                                        <h4 id="ventasSeleccionadas">0</h4>
                                    </div>
                                    <div class="col-6">
                                        <h6>Total BS</h6>
                                        <h5 id="totalBsSeleccionado">Bs 0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Configurar DataTables
        $('#tablaVentas').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            order: [
                [2, 'desc']
            ],
            pageLength: 10,
            responsive: true,
            columnDefs: [{
                orderable: false,
                targets: 0
            }]
        });

        // Seleccionar/Deseleccionar todos
        document.getElementById('seleccionar_todos').addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.venta-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            actualizarResumen();
        });

        // Actualizar resumen cuando se selecciona/deselecciona una venta
        const checkboxes = document.querySelectorAll('.venta-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', actualizarResumen);
        });

        // Validar formulario antes de enviar
        document.getElementById('formCierreVentas').addEventListener('submit', function (e) {
            const selectedCount = document.querySelectorAll('.venta-checkbox:checked').length;
            if (selectedCount === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debe seleccionar al menos una venta para cerrar caja'
                });
                return false;
            }

            // Confirmar acción
            e.preventDefault();
            Swal.fire({
                title: '¿Confirmar cierre de caja?',
                html: `Se cerrarán <b>${selectedCount}</b> ventas seleccionadas.<br>Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, cerrar caja',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });

        // Función para actualizar el resumen de selección
        function actualizarResumen() {
            const checkboxes = document.querySelectorAll('.venta-checkbox:checked');
            let ventasSeleccionadas = 0;
            let totalBs = 0;

            // Contar ventas seleccionadas
            ventasSeleccionadas = checkboxes.length;

            // Actualizar contadores
            document.getElementById('ventasSeleccionadas').textContent = ventasSeleccionadas;

            // Si no hay selección, mostrar mensaje
            if (ventasSeleccionadas === 0) {
                document.getElementById('totalBsSeleccionado').textContent = 'Bs 0.00';
                document.getElementById('btnCerrarCaja').disabled = true;
                document.getElementById('btnCerrarCaja').innerHTML =
                    '<i class="fas fa-lock me-2"></i>Selecciona ventas para cerrar caja';
            } else {
                // Habilitar botón
                document.getElementById('btnCerrarCaja').disabled = false;
                document.getElementById('btnCerrarCaja').innerHTML =
                    `<i class="fas fa-lock me-2"></i>Cerrar Caja (${ventasSeleccionadas} ventas)`;
            }
        }

        // Inicializar resumen
        actualizarResumen();

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