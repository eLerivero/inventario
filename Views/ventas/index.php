<?php
// Usar rutas absolutas para evitar problemas de inclusión
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/VentaController.php';
require_once __DIR__ . '/../../Utils/Ayuda.php';
require_once __DIR__ . '/../../Helpers/TasaCambioHelper.php';
require_once __DIR__ . '/../../Utils/Auth.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar acceso específico a ventas
Auth::requireAccessToVentas();

$database = new Database();
$db = $database->getConnection();
$controller = new VentaController($db);

// IDs de tipos de pago que son en USD/DIVISAS
define('TIPOS_PAGO_USD', [2, 7]);

// Manejar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';

// Procesar cambio de estado
if ($action === 'completar' && $id) {
    // Verificar token CSRF
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Token de seguridad inválido";
    } else {
        $result = $controller->actualizarEstado($id, 'completada');
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    }
}

// Obtener ventas
$mostrar_todas = isset($_GET['mostrar_todas']) && $_GET['mostrar_todas'] == '1';
$result = $controller->listar(!$mostrar_todas);

if ($result['success']) {
    $ventas = $result['data'];
    $filtro_activas = $result['filtro_activas'] ?? true;
} else {
    $error_message = $result['message'] ?? 'Error al cargar las ventas';
    $ventas = [];
    $filtro_activas = true;
}

// Estadísticas - Solo para ventas activas
$estadisticas_hoy = $controller->obtenerResumenHoy();
$stats_usd = $estadisticas_hoy['success'] ? $estadisticas_hoy['data'] : [];

$stats_precios_fijos = $controller->obtenerTotalPreciosFijosHoy();
$stats_bs = $stats_precios_fijos['success'] ? $stats_precios_fijos['data'] : [];

// Token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "Gestión de Ventas";
include __DIR__ . '/../layouts/header.php';
?>

<!-- ================================================================== -->
<!-- 🎨 ESTILOS LIMPIOS Y PROFESIONALES -->
<!-- ================================================================== -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    * {
        font-family: 'Inter', sans-serif;
    }

    body {
        background: #f8fafc;
    }

    /* Tarjetas elegantes */
    .card-elegant {
        background: white;
        border-radius: 20px;
        padding: 1.75rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: all 0.2s ease;
        border: 1px solid rgba(203, 213, 225, 0.3);
    }

    .card-elegant:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        border-color: rgba(148, 163, 184, 0.3);
    }

    /* Header limpio */
    .header-limpio {
        background: white;
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem 0;
        margin-bottom: 2rem;
    }

    /* Reloj minimalista */
    .reloj-minimal {
        background: white;
        border-radius: 16px;
        padding: 1.25rem;
        border: 1px solid #e2e8f0;
        display: inline-flex;
        align-items: center;
        gap: 1rem;
    }

    .reloj-minimal i {
        color: #4f46e5;
        font-size: 1.5rem;
    }

    .reloj-minimal .hora {
        font-size: 1.8rem;
        font-weight: 600;
        color: #0f172a;
        line-height: 1;
        letter-spacing: 2px;
    }

    .reloj-minimal .fecha {
        font-size: 0.9rem;
        color: #64748b;
        margin-top: 0.25rem;
    }

    /* Tarjetas de estadísticas */
    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        border-color: #cbd5e1;
        background: #fafcfc;
    }

    .stat-card-ventas {
        border-top: 4px solid #4f46e5;
    }

    .stat-card-usd {
        border-top: 4px solid #0d9488;
    }

    .stat-card-bs {
        border-top: 4px solid #b45309;
    }

    .stat-card-clientes {
        border-top: 4px solid #7c3aed;
    }

    .stat-value {
        font-size: 2.2rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
        margin: 0.75rem 0 0.5rem;
    }

    .stat-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        font-weight: 600;
    }

    .stat-detail {
        font-size: 0.85rem;
        color: #475569;
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    /* Badges limpios */
    .badge-limpio {
        padding: 0.35rem 0.9rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        background: #f1f5f9;
        color: #334155;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .badge-usd {
        background: #e6fffa;
        color: #0d9488;
    }

    .badge-bs {
        background: #fff7ed;
        color: #b45309;
    }

    .badge-success {
        background: #dcfce7;
        color: #059669;
    }

    .badge-warning {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-info {
        background: #e0f2fe;
        color: #0284c7;
    }

    .badge-multiple {
        background: #f3e8ff;
        color: #7e22ce;
    }

    /* Botones limpios */
    .btn-limpio {
        padding: 0.6rem 1.2rem;
        border-radius: 12px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-limpio-primary {
        background: #4f46e5;
        color: white;
    }

    .btn-limpio-primary:hover {
        background: #4338ca;
        color: white;
    }

    .btn-limpio-outline {
        background: transparent;
        border: 1px solid #e2e8f0;
        color: #334155;
    }

    .btn-limpio-outline:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #1e293b;
    }

    .btn-limpio-success {
        background: #0d9488;
        color: white;
    }

    .btn-limpio-success:hover {
        background: #0f766e;
        color: white;
    }

    /* Tabla limpia */
    .table-limpia {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .table-limpia thead th {
        background: #f8fafc;
        color: #334155;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem 1rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .table-limpia tbody tr {
        background: white;
        border-radius: 16px;
        transition: all 0.2s ease;
    }

    .table-limpia tbody tr:hover {
        background: #fafcfc;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }

    .table-limpia td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    .column-usd {
        background: #f0fdfa;
        color: #0d9488;
        font-weight: 600;
    }

    .column-bs {
        background: #fff7ed;
        color: #b45309;
        font-weight: 600;
    }

    /* Footer de tabla */
    .table-footer {
        background: #f8fafc;
        border-top: 2px solid #e2e8f0;
        padding: 1rem;
        border-radius: 12px;
    }

    /* Modal limpio */
    .modal-limpio .modal-content {
        border: none;
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    }

    .modal-header-limpio {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem;
        border-radius: 24px 24px 0 0;
    }

    /* Alertas limpias */
    .alert-limpio {
        border: none;
        border-radius: 16px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        background: white;
        border-left: 4px solid;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .alert-success-limpio {
        border-left-color: #10b981;
        background: #f0fdf4;
    }

    .alert-info-limpio {
        border-left-color: #3b82f6;
        background: #eff6ff;
    }

    .alert-warning-limpio {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }

    /* Filtros */
    .filtros-limpios {
        background: white;
        border-radius: 14px;
        padding: 0.5rem;
        border: 1px solid #e2e8f0;
        display: inline-flex;
    }

    .filtro-btn {
        padding: 0.5rem 1.2rem;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 500;
        color: #64748b;
        transition: all 0.2s ease;
        text-decoration: none;
        cursor: pointer;
    }

    .filtro-btn:hover {
        background: #f1f5f9;
        color: #334155;
    }

    .filtro-btn.active {
        background: #4f46e5;
        color: white;
    }

    .filtro-btn.active:hover {
        background: #4338ca;
        color: white;
    }

    /* Separadores */
    .separator {
        height: 1px;
        background: linear-gradient(to right, transparent, #e2e8f0, transparent);
        margin: 2rem 0;
    }

    /* Paginación de pagos */
    .pagos-mini {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        max-width: 200px;
    }

    .pago-mini-item {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 600;
        cursor: help;
    }

    .pago-mini-usd {
        background: #e6fffa;
        color: #0d9488;
    }

    .pago-mini-bs {
        background: #fff7ed;
        color: #b45309;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .stat-value {
            font-size: 1.8rem;
        }

        .reloj-minimal .hora {
            font-size: 1.4rem;
        }

        .table-responsive {
            overflow-x: auto;
        }
    }
</style>

<!-- ================================================================== -->
<!-- 🕐 BARRA SUPERIOR - FECHA Y HORA MINIMALISTA -->
<!-- ================================================================== -->
<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div class="reloj-minimal">
        <i class="fas fa-clock" style="color: #4f46e5;"></i>
        <div>
            <div class="hora" id="hora-actual"><?php echo date('H:i:s'); ?></div>
            <div class="fecha" id="fecha-actual">
                <?php
                setlocale(LC_TIME, 'spanish');
                echo strftime('%A, %d de %B de %Y');
                ?>
            </div>
        </div>
    </div>

    <!-- Botón de recargar manual -->
    <button class="btn-limpio btn-limpio-outline" onclick="location.reload()">
        <i class="fas fa-sync-alt me-2"></i>
        Actualizar
    </button>
</div>

<!-- ================================================================== -->
<!-- 🎯 HEADER PRINCIPAL -->
<!-- ================================================================== -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="display-6 fw-bold" style="color: #0f172a;">
            <i class="fas fa-shopping-cart me-3" style="color: #4f46e5;"></i>
            Gestión de Ventas
        </h1>
        <p class="text-muted mb-0">
            <i class="fas fa-chart-line me-2" style="color: #64748b;"></i>
            <?php if ($filtro_activas): ?>
                Monitoreo de ventas activas - Pendientes por cierre
            <?php else: ?>
                Historial completo de ventas (activas y cerradas)
            <?php endif; ?>
        </p>
    </div>

    <div class="d-flex gap-3">
        <!-- Filtros -->
        <div class="filtros-limpios">
            <?php if ($filtro_activas): ?>
                <a href="index.php?mostrar_todas=1" class="filtro-btn text-decoration-none">
                    <i class="fas fa-history me-1"></i> Historial
                </a>
                <span class="filtro-btn active">
                    <i class="fas fa-eye me-1"></i> Activas
                </span>
            <?php else: ?>
                <span class="filtro-btn active">
                    <i class="fas fa-history me-1"></i> Todas
                </span>
                <a href="index.php" class="filtro-btn text-decoration-none">
                    <i class="fas fa-eye me-1"></i> Activas
                </a>
            <?php endif; ?>
        </div>

        <!-- Botón Nueva Venta -->
        <a href="crear.php" class="btn-limpio btn-limpio-primary text-decoration-none">
            <i class="fas fa-plus-circle me-2"></i>
            Nueva Venta
        </a>
    </div>
</div>

<!-- ================================================================== -->
<!-- 📢 ALERTAS -->
<!-- ================================================================== -->
<?php if ($mostrar_todas): ?>
    <div class="alert-limpio alert-info-limpio d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle me-3" style="color: #3b82f6;"></i>
            <span><strong>Modo Historial:</strong> Estás viendo todas las ventas (activas y cerradas)</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <div class="alert-limpio alert-success-limpio d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle me-3" style="color: #10b981;"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert-limpio" style="border-left-color: #ef4444; background: #fef2f2;">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-3" style="color: #ef4444;"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- ================================================================== -->
<!-- 📊 ESTADÍSTICAS - SOLO VENTAS ACTIVAS -->
<!-- ================================================================== -->
<?php if ($filtro_activas): ?>
    <div class="row g-4 mb-5">
        <!-- Ventas Hoy -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card stat-card-ventas">
                <div class="stat-label">
                    <i class="fas fa-receipt me-1"></i> VENTAS HOY
                </div>
                <div class="stat-value"><?php echo $stats_usd['ventas_hoy'] ?? 0; ?></div>
                <div class="stat-detail">
                    <span class="badge-limpio">
                        <i class="fas fa-check-circle me-1"></i> Completadas hoy
                    </span>
                </div>
            </div>
        </div>

        <!-- USD Recibidos -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card stat-card-usd">
                <div class="stat-label">
                    <i class="fas fa-dollar-sign me-1"></i> USD RECIBIDOS
                </div>
                <div class="stat-value"><?php echo TasaCambioHelper::formatearUSD($stats_usd['total_usd_hoy'] ?? 0); ?></div>
                <div class="stat-detail">
                    <?php if (($stats_usd['total_efectivo_usd'] ?? 0) > 0): ?>
                        <span class="badge-limpio badge-usd">
                            💵 Efectivo: <?php echo TasaCambioHelper::formatearUSD($stats_usd['total_efectivo_usd']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (($stats_usd['total_divisa'] ?? 0) > 0): ?>
                        <span class="badge-limpio badge-usd">
                            💶 Divisa: <?php echo TasaCambioHelper::formatearUSD($stats_usd['total_divisa']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bs Precio Fijo -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card stat-card-bs">
                <div class="stat-label">
                    <i class="fas fa-lock me-1"></i> BS PRECIO FIJO
                </div>
                <div class="stat-value"><?php echo $stats_bs['total_bs_precio_fijo_formateado'] ?? 'Bs 0,00'; ?></div>
                <div class="stat-detail">
                    <span class="badge-limpio badge-bs">
                        <i class="fas fa-tag me-1"></i>
                        <?php echo $stats_bs['ventas_con_precio_fijo'] ?? 0; ?> ventas
                    </span>
                </div>
            </div>
        </div>

        <!-- Clientes Hoy -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card stat-card-clientes">
                <div class="stat-label">
                    <i class="fas fa-users me-1"></i> CLIENTES HOY
                </div>
                <div class="stat-value"><?php echo $stats_usd['clientes_hoy'] ?? 0; ?></div>
                <div class="stat-detail">
                    <span class="badge-limpio">
                        <i class="fas fa-user-check me-1"></i> Atendidos hoy
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Horario de Ventas -->
    <?php if (!empty($stats_usd['primera_venta_formateada']) || !empty($stats_usd['ultima_venta_formateada'])): ?>
        <div class="card-elegant mb-5">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-4">
                        <div class="bg-light rounded-3 p-3">
                            <i class="fas fa-sun fa-2x" style="color: #4f46e5;"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold">Primera Venta</small>
                            <h3 class="fw-bold mb-0" style="color: #0f172a;">
                                <?php echo $stats_usd['primera_venta_formateada'] ?? '--:--'; ?>
                            </h3>
                            <span class="text-muted small">Inicio de operaciones</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-4">
                        <div class="bg-light rounded-3 p-3">
                            <i class="fas fa-moon fa-2x" style="color: #0d9488;"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-semibold">Última Venta</small>
                            <h3 class="fw-bold mb-0" style="color: #0f172a;">
                                <?php echo $stats_usd['ultima_venta_formateada'] ?? '--:--'; ?>
                            </h3>
                            <span class="text-muted small">Cierre del día</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="separator"></div>

<!-- ================================================================== -->
<!-- 📋 TABLA DE VENTAS - DISEÑO LIMPIO -->
<!-- ================================================================== -->
<div class="card-elegant">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0" style="color: #0f172a;">
            <i class="fas fa-list-ul me-2" style="color: #4f46e5;"></i>
            Listado de Ventas
            <?php if ($filtro_activas): ?>
                <span class="badge-limpio ms-3" style="background: #e6f7e6; color: #059669;">
                    <i class="fas fa-unlock me-1"></i> Activas
                </span>
            <?php else: ?>
                <span class="badge-limpio ms-3" style="background: #f1f5f9; color: #475569;">
                    <i class="fas fa-history me-1"></i> Historial Completo
                </span>
            <?php endif; ?>
        </h4>
        <div class="d-flex gap-2">
            <span class="badge-limpio badge-usd">
                <i class="fas fa-dollar-sign me-1"></i> USD
            </span>
            <span class="badge-limpio badge-bs">
                <i class="fas fa-lock me-1"></i> Bs Fijo
            </span>
            <span class="badge-limpio badge-multiple">
                <i class="fas fa-layer-group me-1"></i> Múltiple
            </span>
        </div>
    </div>

    <?php if (empty($ventas) && $filtro_activas): ?>
        <!-- Estado vacío - Todas cerradas -->
        <div class="text-center py-5">
            <div class="bg-light rounded-circle d-inline-flex p-4 mb-4">
                <i class="fas fa-check-circle fa-4x" style="color: #10b981;"></i>
            </div>
            <h4 class="fw-bold mb-3" style="color: #059669;">¡Todas las ventas han sido cerradas!</h4>
            <p class="text-muted mb-4">No hay ventas activas pendientes de cierre en caja.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="index.php?mostrar_todas=1" class="btn-limpio btn-limpio-outline text-decoration-none">
                    <i class="fas fa-history me-2"></i> Ver Historial
                </a>
                <a href="crear.php" class="btn-limpio btn-limpio-success text-decoration-none">
                    <i class="fas fa-plus-circle me-2"></i> Nueva Venta
                </a>
            </div>
        </div>
    <?php elseif (empty($ventas)): ?>
        <!-- Estado vacío - Sin ventas -->
        <div class="text-center py-5">
            <div class="bg-light rounded-circle d-inline-flex p-4 mb-4">
                <i class="fas fa-shopping-cart fa-4x" style="color: #4f46e5;"></i>
            </div>
            <h4 class="fw-bold mb-3" style="color: #334155;">No hay ventas registradas</h4>
            <p class="text-muted mb-4">Comienza registrando tu primera venta en el sistema.</p>
            <a href="crear.php" class="btn-limpio btn-limpio-primary text-decoration-none">
                <i class="fas fa-plus-circle me-2"></i> Crear Primera Venta
            </a>
        </div>
    <?php else: ?>
        <!-- Tabla -->
        <div class="table-responsive">
            <table class="table table-limpia" id="tablaVentas">
                <thead>
                    <tr>
                        <th># Venta</th>
                        <th>Cliente</th>
                        <th class="text-center">Métodos Pago</th>
                        <th class="text-center">USD</th>
                        <th class="text-center">Bs</th>
                        <th>Tasa</th>
                        <th>Estado</th>
                        <th>Cierre</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_usd_recibido = 0;
                    $total_bs_recibido = 0;
                    $ventasActivas = 0;
                    $ventasCerradas = 0;

                    foreach ($ventas as $venta):
                        $cerrada_en_caja = isset($venta['cerrada_en_caja']) && $venta['cerrada_en_caja'];
                        $cantidad_pagos = $venta['cantidad_pagos'] ?? 1;
                        $es_pago_multiple = $cantidad_pagos > 1;
                        $total_pagado_usd = $venta['total_pagado_usd'] ?? $venta['total'] ?? 0;
                        $total_pagado_bs = $venta['total_pagado_bs'] ?? $venta['total_bs'] ?? 0;

                        // Determinar si tiene pagos en USD
                        $tiene_usd = $es_pago_multiple ? true : in_array($venta['tipo_pago_id'] ?? 0, TIPOS_PAGO_USD);

                        // Calcular total de productos con precio fijo (esto debería venir del controlador)
                        $total_bs_precio_fijo_venta = 0;
                        if (isset($venta['detalles']) && is_array($venta['detalles'])) {
                            foreach ($venta['detalles'] as $detalle) {
                                if (
                                    isset($detalle['precio_unitario_bs']) &&
                                    $detalle['precio_unitario_bs'] > 0 &&
                                    ($detalle['precio_unitario'] * ($venta['tasa_cambio'] ?? 1)) != $detalle['precio_unitario_bs']
                                ) {
                                    $total_bs_precio_fijo_venta += ($detalle['precio_unitario_bs'] * $detalle['cantidad']);
                                }
                            }
                        }

                        $tiene_precio_fijo = $total_bs_precio_fijo_venta > 0;

                        if ($cerrada_en_caja) $ventasCerradas++;
                        else $ventasActivas++;

                        if ($tiene_usd) $total_usd_recibido += $total_pagado_usd;
                        $total_bs_recibido += $total_bs_precio_fijo_venta;
                    ?>
                        <tr style="<?php echo $cerrada_en_caja ? 'opacity: 0.7;' : ''; ?>">
                            <td>
                                <span class="fw-semibold">#<?php echo htmlspecialchars($venta['numero_venta'] ?? 'N/A'); ?></span>
                                <?php if ($es_pago_multiple): ?>
                                    <br><small class="text-muted badge-multiple px-2 py-1 rounded" style="font-size: 0.7rem;">
                                        <i class="fas fa-layer-group"></i> <?php echo $cantidad_pagos; ?> pagos
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle me-2" style="color: #64748b;"></i>
                                    <?php echo htmlspecialchars($venta['cliente_nombre'] ?? 'Cliente no especificado'); ?>
                                </div>
                            </td>

                            <!-- Métodos de Pago -->
                            <td class="text-center">
                                <?php if ($es_pago_multiple): ?>
                                    <span class="badge-limpio badge-multiple" data-bs-toggle="tooltip" title="<?php echo $cantidad_pagos; ?> métodos de pago">
                                        <i class="fas fa-layer-group me-1"></i> Múltiple
                                    </span>
                                <?php else: ?>
                                    <?php if ($tiene_usd): ?>
                                        <span class="badge-limpio badge-usd">
                                            <i class="fas fa-dollar-sign me-1"></i>
                                            <?php echo htmlspecialchars($venta['tipo_pago_nombre'] ?? 'USD'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-limpio">
                                            <?php echo htmlspecialchars($venta['tipo_pago_nombre'] ?? 'N/A'); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <!-- USD -->
                            <td class="text-center column-usd">
                                <?php if ($tiene_usd): ?>
                                    <span class="fw-semibold">
                                        <?php echo TasaCambioHelper::formatearUSD($total_pagado_usd); ?>
                                    </span>
                                    <?php if ($es_pago_multiple): ?>
                                        <br><small class="text-muted">de <?php echo $cantidad_pagos; ?> pagos</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Bs (solo precio fijo) -->
                            <td class="text-center column-bs">
                                <?php if ($tiene_precio_fijo): ?>
                                    <span class="fw-semibold">
                                        <?php echo TasaCambioHelper::formatearBS($total_bs_precio_fijo_venta); ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-lock"></i> Precio fijo
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge-limpio">
                                    <?php
                                    $tasa = $venta['tasa_cambio'] ?? $venta['tasa_cambio_utilizada'] ?? 0;
                                    echo number_format($tasa, 2); ?> Bs/$
                                </span>
                            </td>

                            <td>
                                <?php
                                $estado = $venta['estado'] ?? 'pendiente';
                                if ($estado == 'completada'): ?>
                                    <span class="badge-limpio badge-success">
                                        <i class="fas fa-check-circle me-1"></i> Completada
                                    </span>
                                <?php elseif ($estado == 'pendiente'): ?>
                                    <span class="badge-limpio badge-warning">
                                        <i class="fas fa-clock me-1"></i> Pendiente
                                    </span>
                                <?php else: ?>
                                    <span class="badge-limpio" style="background: #fee2e2; color: #dc2626;">
                                        <i class="fas fa-times-circle me-1"></i> Cancelada
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($cerrada_en_caja): ?>
                                    <span class="badge-limpio" style="background: #f1f5f9; color: #475569;">
                                        <i class="fas fa-lock me-1"></i> Cerrada
                                    </span>
                                <?php else: ?>
                                    <span class="badge-limpio" style="background: #e6f7e6; color: #059669;">
                                        <i class="fas fa-unlock me-1"></i> Activa
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div style="line-height: 1.4;">
                                    <i class="fas fa-calendar-alt me-1" style="color: #64748b;"></i>
                                    <?php
                                    $fecha = !empty($venta['fecha_hora']) ? Ayuda::formatDate($venta['fecha_hora']) : (!empty($venta['created_at']) ? Ayuda::formatDate($venta['created_at']) : 'N/A');
                                    echo $fecha;
                                    ?>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('H:i', strtotime($venta['fecha_hora'] ?? $venta['created_at'] ?? '')); ?>
                                    </small>
                                </div>
                            </td>

                            <td>
                                <div class="btn-group" role="group">
                                    <a href="ver.php?id=<?php echo $venta['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary border-0"
                                        style="padding: 0.5rem 0.8rem;"
                                        data-bs-toggle="tooltip"
                                        title="Ver detalles">
                                        <i class="fas fa-eye" style="color: #4f46e5;"></i>
                                    </a>

                                    <?php if (($venta['estado'] ?? '') === 'pendiente' && !$cerrada_en_caja): ?>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-success border-0"
                                            style="padding: 0.5rem 0.8rem;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalCompletar"
                                            data-id="<?php echo $venta['id']; ?>"
                                            data-numero="<?php echo $venta['numero_venta'] ?? ''; ?>"
                                            data-pagos="<?php echo $cantidad_pagos; ?>"
                                            <?php echo $tiene_precio_fijo ? 'data-precio-fijo="true"' : ''; ?>
                                            data-bs-toggle="tooltip"
                                            title="Completar venta">
                                            <i class="fas fa-check" style="color: #10b981;"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totales -->
        <div class="table-footer mt-4 rounded-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex gap-4 flex-wrap">
                        <div>
                            <small class="text-muted d-block">Total USD Recibidos</small>
                            <span class="fw-bold fs-5" style="color: #0d9488;">
                                <?php echo TasaCambioHelper::formatearUSD($stats_usd['total_usd_hoy'] ?? 0); ?>
                                <?php echo TasaCambioHelper::formatearUSD($total_usd_recibido); ?>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Bs Precio Fijo</small>
                            <span class="fw-bold fs-5" style="color: #b45309;">
                                <?php echo TasaCambioHelper::formatearBS($total_bs_recibido); ?>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Ventas con Múltiples Pagos</small>
                            <span class="fw-bold fs-5" style="color: #7e22ce;">
                                <?php echo count(array_filter($ventas, function ($v) {
                                    return ($v['cantidad_pagos'] ?? 1) > 1;
                                })); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <span class="badge-limpio me-2">
                        <i class="fas fa-unlock me-1" style="color: #059669;"></i>
                        Activas: <strong><?php echo $ventasActivas; ?></strong>
                    </span>
                    <span class="badge-limpio me-2">
                        <i class="fas fa-lock me-1" style="color: #475569;"></i>
                        Cerradas: <strong><?php echo $ventasCerradas; ?></strong>
                    </span>
                    <span class="badge-limpio">
                        <i class="fas fa-shopping-cart me-1" style="color: #4f46e5;"></i>
                        Total: <strong><?php echo count($ventas); ?></strong>
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ================================================================== -->
<!-- 🎯 MODAL COMPLETAR VENTA - ACTUALIZADO PARA PAGOS MÚLTIPLES -->
<!-- ================================================================== -->
<div class="modal fade" id="modalCompletar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-limpio">
            <div class="modal-header modal-header-limpio">
                <h5 class="modal-title fw-bold" style="color: #0f172a;">
                    <i class="fas fa-check-circle me-2" style="color: #10b981;"></i>
                    Completar Venta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="bg-light rounded-circle d-inline-flex p-3 mb-3">
                        <i class="fas fa-shopping-cart fa-3x" style="color: #10b981;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">¿Completar venta <span id="numeroVenta" style="color: #10b981;"></span>?</h5>
                    <p class="text-muted mb-0" id="mensajePagos"></p>
                </div>

                <div class="alert alert-success-limpio d-flex align-items-center p-3">
                    <i class="fas fa-info-circle me-3" style="color: #10b981;"></i>
                    <div>
                        <strong>Proceso automático</strong><br>
                        <small>El stock se actualizará y la venta pasará a estado "Completada"</small>
                    </div>
                </div>

                <div id="precioFijoWarning" class="alert alert-warning-limpio d-flex align-items-center p-3 d-none">
                    <i class="fas fa-lock me-3" style="color: #f59e0b;"></i>
                    <div>
                        <strong>Productos con precio fijo en Bs</strong><br>
                        <small>Esta venta incluye productos con precio fijo en Bolívares</small>
                    </div>
                </div>

                <div id="pagoMultipleInfo" class="alert alert-info-limpio d-flex align-items-center p-3 d-none">
                    <i class="fas fa-layer-group me-3" style="color: #3b82f6;"></i>
                    <div>
                        <strong>Venta con múltiples pagos</strong><br>
                        <small id="pagoMultipleTexto"></small>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn-limpio btn-limpio-outline" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <a href="#" id="btnCompletarConfirmar" class="btn-limpio btn-limpio-success text-decoration-none">
                    <i class="fas fa-check-circle me-2"></i>Sí, completar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================== -->
<!-- 🚀 SCRIPTS -->
<!-- ================================================================== -->
<script>
    // Reloj en tiempo real
    function actualizarReloj() {
        const ahora = new Date();
        const horas = ahora.getHours().toString().padStart(2, '0');
        const minutos = ahora.getMinutes().toString().padStart(2, '0');
        const segundos = ahora.getSeconds().toString().padStart(2, '0');

        const horaElement = document.getElementById('hora-actual');
        if (horaElement) {
            horaElement.textContent = `${horas}:${minutos}:${segundos}`;
        }

        // Actualizar fecha completa
        const fechaElement = document.getElementById('fecha-actual');
        if (fechaElement) {
            const dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
            const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            fechaElement.textContent = `${dias[ahora.getDay()]}, ${ahora.getDate()} de ${meses[ahora.getMonth()]} de ${ahora.getFullYear()}`;
        }
    }

    actualizarReloj();
    setInterval(actualizarReloj, 1000);

    // Inicializar tooltips y DataTables cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => new bootstrap.Tooltip(el));

        // Inicializar DataTables si existe la tabla
        const tablaElement = document.getElementById('tablaVentas');
        if (tablaElement && typeof $.fn !== 'undefined' && $.fn.DataTable) {
            if ($.fn.DataTable.isDataTable('#tablaVentas')) {
                $('#tablaVentas').DataTable().destroy();
            }

            $('#tablaVentas').DataTable({
                language: {
                    processing: "Procesando...",
                    lengthMenu: "Mostrar _MENU_ registros",
                    zeroRecords: "No se encontraron resultados",
                    emptyTable: "No hay datos disponibles",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    search: "Buscar:",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    },
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros"
                },
                pageLength: 10,
                order: [
                    [8, 'desc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: [1, 2, 9]
                }]
            });
        }

        // Modal completar venta
        const modal = document.getElementById('modalCompletar');
        if (modal) {
            modal.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                if (!btn) return;

                const id = btn.dataset.id;
                const numero = btn.dataset.numero;
                const precioFijo = btn.dataset.precioFijo === 'true';
                const cantidadPagos = btn.dataset.pagos || 1;

                const numeroEl = document.getElementById('numeroVenta');
                if (numeroEl) numeroEl.textContent = '#' + (numero || '');

                const mensajePagosEl = document.getElementById('mensajePagos');
                if (mensajePagosEl) {
                    if (cantidadPagos > 1) {
                        mensajePagosEl.textContent = `Esta venta tiene ${cantidadPagos} métodos de pago registrados.`;
                    } else {
                        mensajePagosEl.textContent = 'Esta acción actualizará el stock de productos automáticamente.';
                    }
                }

                const warningEl = document.getElementById('precioFijoWarning');
                if (warningEl) {
                    if (precioFijo) {
                        warningEl.classList.remove('d-none');
                    } else {
                        warningEl.classList.add('d-none');
                    }
                }

                const pagoMultipleInfo = document.getElementById('pagoMultipleInfo');
                const pagoMultipleTexto = document.getElementById('pagoMultipleTexto');
                if (pagoMultipleInfo && pagoMultipleTexto) {
                    if (cantidadPagos > 1) {
                        pagoMultipleInfo.classList.remove('d-none');
                        pagoMultipleTexto.textContent = `La venta fue pagada con ${cantidadPagos} métodos de pago diferentes.`;
                    } else {
                        pagoMultipleInfo.classList.add('d-none');
                    }
                }

                const confirmBtn = document.getElementById('btnCompletarConfirmar');
                if (confirmBtn && id) {
                    confirmBtn.href = `index.php?action=completar&id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`;
                }
            });
        }

        // Auto-refrescar cada 5 minutos (300000 ms)
        setTimeout(() => location.reload(), 300000);
    });

    // Función para cerrar alertas manualmente
    document.querySelectorAll('.alert .btn-close').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.alert').remove();
        });
    });
</script>

<!-- <?php
        include __DIR__ . '/../layouts/footer.php';
        ?> -->