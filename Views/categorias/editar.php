<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/CategoriaController.php';

$database = new Database();
$db = $database->getConnection();

$controller = new CategoriaController($db);

$error_message = '';
$success_message = '';

// Obtener ID de la categoría
$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: index.php');
    exit();
}

// Obtener datos de la categoría
$result = $controller->obtener($id);
if (!$result['success']) {
    $error_message = $result['message'];
    $categoria = null;
} else {
    $categoria = $result['data'];
}

// Procesar formulario
if ($_POST && $categoria) {
    $result = $controller->actualizar($id, $_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        // Actualizar datos locales
        $categoria['nombre'] = $_POST['nombre'];
        $categoria['descripcion'] = $_POST['descripcion'];
    } else {
        $error_message = $result['message'];
    }
}
?>

<?php 
$page_title = "Editar Categoría";
include '../layouts/header.php'; 
?>

<div class="container-fluid">
    <div class="row">
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-edit me-2"></i>
                    Editar Categoría
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

            <?php if (!$categoria): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No se encontró la categoría solicitada.
                </div>
            <?php else: ?>
                <!-- Formulario -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Editar Información de la Categoría
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
                                               value="<?php echo htmlspecialchars($categoria['nombre']); ?>"
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
                                                  placeholder="Describe brevemente esta categoría..."><?php echo htmlspecialchars($categoria['descripcion']); ?></textarea>
                                        <div class="form-text">Máximo 500 caracteres.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Actualizar Categoría
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información de la Categoría -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Información de la Categoría
                                </h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">ID:</dt>
                                    <dd class="col-sm-8"><?php echo $categoria['id']; ?></dd>

                                    <dt class="col-sm-4">Creado:</dt>
                                    <dd class="col-sm-8"><?php echo Ayuda::formatDate($categoria['created_at'], 'd/m/Y H:i:s'); ?></dd>

                                    <dt class="col-sm-4">Actualizado:</dt>
                                    <dd class="col-sm-8"><?php echo Ayuda::formatDate($categoria['updated_at'] ?? $categoria['created_at'], 'd/m/Y H:i:s'); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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