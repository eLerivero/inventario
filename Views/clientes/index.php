<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/DashboardController.php';
require_once '../../Utils/Ayuda.php';

$database = new Database();
$db = $database->getConnection();

$controller = new DashboardController($db);
$data = $controller->obtenerEstadisticasCompletas();

$page_title = "Gestión de clientes";
include '../layouts/header.php';
?>


<?php include '../layouts/footer.php'; ?>