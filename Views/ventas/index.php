<?php
require_once '../layouts/header.php';
require_once '../layouts/sidebar.php';

require_once '../../Controllers/VentaController.php';
require_once '../../Config/Database.php';

$database = new Database();
$db = $database->getConnection();
$ventaController = new VentaController($db);

$ventas = $ventaController->listar();
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-shopping-cart me-2"></i>Gestión de Ventas
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="crear.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Nueva Venta
            </a>
        </div>
    </div>

    <div id="alerts-container"></div>

    <!-- Estadísticas Rápidas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Ventas</h6>
                            <h3><?php echo count($ventas); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-shopping-cart fa-2x"></i>
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
                            <h6 class="card-title">Completadas</h6>
                            <h3>
                                <?php
                                $completadas = array_filter($ventas, function ($v) {
                                    return $v['estado'] === 'completada';
                                });
                                echo count($completadas);
                                ?>
                            </h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
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
                            <h6 class="card-title">Pendientes</h6>
                            <h3>
                                <?php
                                $pendientes = array_filter($ventas, function ($v) {
                                    return $v['estado'] === 'pendiente';
                                });
                                echo count($pendientes);
                                ?>
                            </h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Canceladas</h6>
                            <h3>
                                <?php
                                $canceladas = array_filter($ventas, function ($v) {
                                    return $v['estado'] === 'cancelada';
                                });
                                echo count($canceladas);
                                ?>
                            </h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Ventas -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Historial de Ventas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th># Venta</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Método Pago</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $venta['numero_venta']; ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($venta['cliente_nombre'] ?? 'Cliente General'); ?>
                                    <?php if (!empty($venta['observaciones'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($venta['observaciones'], 0, 30)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-success">
                                    <strong>$<?php echo number_format($venta['total'], 2); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($venta['tipo_pago_nombre'] ?? 'No especificado'); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                                            switch ($venta['estado']) {
                                                                case 'completada':
                                                                    echo 'success';
                                                                    break;
                                                                case 'pendiente':
                                                                    echo 'warning';
                                                                    break;
                                                                case 'cancelada':
                                                                    echo 'danger';
                                                                    break;
                                                                default:
                                                                    echo 'secondary';
                                                            }
                                                            ?>">
                                        <?php echo ucfirst($venta['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($venta['fecha_hora'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="detalle.php?id=<?php echo $venta['id']; ?>"
                                            class="btn btn-outline-primary" title="Ver Detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($venta['estado'] === 'pendiente'): ?>
                                            <a href="?completar=<?php echo $venta['id']; ?>"
                                                class="btn btn-outline-success" title="Completar Venta">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?cancelar=<?php echo $venta['id']; ?>"
                                                class="btn btn-outline-danger" title="Cancelar Venta">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    $(document).ready(function() {
        // Manejar cambios de estado
        $('a[href*="completar"], a[href*="cancelar"]').on('click', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const accion = url.includes('completar') ? 'completar' : 'cancelar';

            if (confirm(`¿Estás seguro de que deseas ${accion} esta venta?`)) {
                showLoading();
                window.location.href = url;
            }
        });

        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            responsive: true,
            order: [
                [5, 'desc']
            ],
            columnDefs: [{
                orderable: false,
                targets: -1
            }]
        });
    });
</script>

<?php require_once '../layouts/footer.php'; ?>