<?php
// Incluir configuración al inicio del archivo
require_once '../../Config/Config.php';
require_once '../../Config/Constants.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo defined('SITE_NAME') ? SITE_NAME : 'Sistema de Inventario'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            transition: all 0.3s;
        }

        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 15px 20px;
            border-left: 4px solid transparent;
        }

        .sidebar .nav-link:hover {
            background: #34495e;
            border-left: 4px solid #3498db;
        }

        .sidebar .nav-link.active {
            background: #34495e;
            border-left: 4px solid #3498db;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .navbar-brand {
            font-weight: bold;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }

        .stat-card {
            border-left: 4px solid #3498db;
        }

        .stat-card.warning {
            border-left-color: #f39c12;
        }

        .stat-card.danger {
            border-left-color: #e74c3c;
        }

        .stat-card.success {
            border-left-color: #27ae60;
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">