<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/CategoriaController.php';

$database = new Database();
$db = $database->getConnection();

$controller = new CategoriaController($db);

$error_message = '';
$success_message = '';

// Procesar formulario
if ($_POST) {
    $result = $controller->crear($_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        // Redirigir después de 2 segundos
        header("Refresh: 2; URL=index.php");
    } else {
        $error_message = $result['message'];
    }
}
?>

<?php 
$page_title = "Crear Categoría";
include '../layouts/header.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../layouts/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-plus me-2"></i>
                    Crear Nueva Categoría
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver al Listado
                    </a>
                </div>
            </div>

            <!-- Alertas -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Información de la Categoría
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="formCategoria">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="fas fa-tag me-1"></i>Nombre de la Categoría *
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="<?php echo $_POST['nombre'] ?? ''; ?>"
                                           required
                                           maxlength="100"
                                           placeholder="Ej: Electrónicos, Ropa, Hogar...">
                                    <div class="form-text">El nombre debe ser único y descriptivo.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">
                                        <i class="fas fa-align-left me-1"></i>Descripción
                                    </label>
                                    <textarea class="form-control" 
                                              id="descripcion" 
                                              name="descripcion" 
                                              rows="4"
                                              maxlength="500"
                                              placeholder="Describe brevemente esta categoría..."><?php echo $_POST['descripcion'] ?? ''; ?></textarea>
                                    <div class="form-text">Máximo 500 caracteres.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i> Guardar Categoría
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información Importante
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Las categorías te ayudan a organizar tus productos de manera eficiente.</li>
                        <li>Puedes asignar productos a categorías para facilitar su búsqueda y gestión.</li>
                        <li>Solo se pueden eliminar categorías que no tengan productos asociados.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const form = document.getElementById('formCategoria');
    form.addEventListener('submit', function(e) {
        const nombre = document.getElementById('nombre').value.trim();
        
        if (!nombre) {
            e.preventDefault();
            alert('Por favor, ingresa el nombre de la categoría.');
            return false;
        }
        
        return true;
    });
});
</script>

<?php include '../layouts/footer.php'; ?>