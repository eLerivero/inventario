<?php
// Archivo: migracion_sistema_cierre.php
require_once 'Config/Database.php';

echo "=== SISTEMA DE REINICIO DE CONTADORES DIARIOS ===\n";
echo "Iniciando migración...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Verificar si existe la columna 'cerrada_en_caja'
    echo "1. Verificando estructura de la tabla 'ventas'...\n";
    $query = "SELECT column_name FROM information_schema.columns 
              WHERE table_name = 'ventas' AND column_name = 'cerrada_en_caja'";
    $stmt = $db->prepare($query);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        echo "   - Agregando columna 'cerrada_en_caja'... ";
        $db->exec("ALTER TABLE ventas ADD COLUMN cerrada_en_caja BOOLEAN DEFAULT FALSE");
        echo "✓ COMPLETADO\n";
    } else {
        echo "   - La columna 'cerrada_en_caja' ya existe ✓\n";
    }

    // 2. Crear índice para mejor performance
    echo "\n2. Creando índices para optimización...\n";
    try {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_ventas_cerrada_fecha ON ventas(cerrada_en_caja, fecha_hora)");
        echo "   - Índice 'idx_ventas_cerrada_fecha' creado ✓\n";
    } catch (Exception $e) {
        echo "   - El índice ya existe o hubo un error: " . $e->getMessage() . "\n";
    }

    // 3. Actualizar todas las ventas existentes como no cerradas
    echo "\n3. Actualizando ventas existentes...\n";
    $stmt = $db->prepare("UPDATE ventas SET cerrada_en_caja = FALSE WHERE cerrada_en_caja IS NULL OR cerrada_en_caja = TRUE");
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "   - $affected ventas actualizadas como 'no cerradas' ✓\n";

    // 4. Verificar si existen cierres de caja previos
    echo "\n4. Verificando cierres de caja existentes...\n";
    $query = "SELECT COUNT(*) as total FROM cierres_caja";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   - Se encontraron {$result['total']} cierres de caja en el sistema ✓\n";

    // 5. Marcar ventas de días con cierre como cerradas
    echo "\n5. Marcando ventas de días con cierre...\n";
    $query = "SELECT DISTINCT fecha FROM cierres_caja WHERE estado = 'completado'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $cierres = $stmt->fetchAll();

    $ventas_marcadas_total = 0;
    foreach ($cierres as $cierre) {
        $query = "UPDATE ventas 
                  SET cerrada_en_caja = TRUE 
                  WHERE DATE(fecha_hora) = :fecha 
                  AND estado = 'completada'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":fecha", $cierre['fecha']);
        $stmt->execute();
        $ventas_marcadas = $stmt->rowCount();
        $ventas_marcadas_total += $ventas_marcadas;
        echo "   - Fecha {$cierre['fecha']}: $ventas_marcadas ventas marcadas como cerradas\n";
    }

    echo "   - TOTAL: $ventas_marcadas_total ventas marcadas como cerradas ✓\n";

    // 6. Crear vistas actualizadas
    echo "\n6. Actualizando vistas del sistema...\n";

    // Vista: vista_ventas_dia_actual (modificada)
    try {
        $db->exec("DROP VIEW IF EXISTS vista_ventas_dia_actual");
        $db->exec("CREATE VIEW vista_ventas_dia_actual AS
            SELECT 
                v.*,
                c.nombre as cliente_nombre,
                c.numero_documento as cliente_documento,
                u.nombre as vendedor_nombre,
                tp.nombre as tipo_pago_nombre,
                COUNT(dv.id) as total_items,
                SUM(dv.cantidad) as total_unidades,
                STRING_AGG(p.nombre, ', ') as productos
            FROM ventas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
            LEFT JOIN detalle_ventas dv ON v.id = dv.venta_id
            LEFT JOIN productos p ON dv.producto_id = p.id
            WHERE v.estado = 'completada' 
            AND DATE(v.fecha_hora) = CURRENT_DATE
            AND v.cerrada_en_caja = FALSE
            GROUP BY v.id, c.nombre, c.numero_documento, u.nombre, tp.nombre
            ORDER BY v.fecha_hora DESC");
        echo "   - Vista 'vista_ventas_dia_actual' actualizada ✓\n";
    } catch (Exception $e) {
        echo "   - Error al crear vista: " . $e->getMessage() . "\n";
    }

    // 7. Verificación final
    echo "\n7. Verificación final del sistema...\n";

    // Contar ventas activas hoy
    $query = "SELECT COUNT(*) as activas_hoy FROM ventas 
              WHERE estado = 'completada' 
              AND DATE(fecha_hora) = CURRENT_DATE
              AND cerrada_en_caja = FALSE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   - Ventas activas hoy: {$result['activas_hoy']} ✓\n";

    // Contar ventas cerradas totales
    $query = "SELECT COUNT(*) as cerradas_total FROM ventas WHERE cerrada_en_caja = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   - Ventas cerradas totales: {$result['cerradas_total']} ✓\n";

    echo "\n========================================\n";
    echo "✅ MIGRACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "========================================\n";
    echo "\nRESUMEN:\n";
    echo "- Se agregó el campo 'cerrada_en_caja' a la tabla ventas\n";
    echo "- Se crearon índices para optimización\n";
    echo "- Se actualizaron todas las ventas existentes\n";
    echo "- Se marcaron ventas de días con cierre como cerradas\n";
    echo "- Se actualizaron las vistas del sistema\n";
    echo "\nEL SISTEMA AHORA SOPORTA REINICIO DE CONTADORES DIARIOS\n";
    echo "Después de cada cierre de caja, las ventas se marcarán como cerradas\n";
    echo "y no aparecerán en los reportes del día siguiente.\n";
} catch (Exception $e) {
    echo "\n❌ ERROR EN LA MIGRACIÓN:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " (Línea: " . $e->getLine() . ")\n";
    echo "\nPor favor, corrija los errores e intente nuevamente.\n";
}
