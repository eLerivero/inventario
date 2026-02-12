<?php
require_once __DIR__ . '/../Models/Venta.php';
require_once __DIR__ . '/../Models/DetalleVenta.php';
require_once __DIR__ . '/../Models/Producto.php';
require_once __DIR__ . '/../Models/TasaCambio.php';
require_once __DIR__ . '/../Helpers/TasaCambioHelper.php';

class VentaController
{
    private $venta;
    private $detalleVenta;
    private $producto;
    private $tasaCambio;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->venta = new Venta($db);
        $this->detalleVenta = new DetalleVenta($db);
        $this->producto = new Producto($db);
        $this->tasaCambio = new TasaCambio($db);
    }

    public function listarold()
    {
        try {
            $stmt = $this->venta->leer();
            $ventas = $stmt->fetchAll();

            // Formatear cada venta para mostrar precios
            foreach ($ventas as &$venta) {
                $venta['total_formateado_usd'] = TasaCambioHelper::formatearUSD($venta['total']);
                $venta['total_formateado_bs'] = TasaCambioHelper::formatearBS($venta['total_bs']);
                $venta['tasa_formateada'] = TasaCambioHelper::formatearBS($venta['tasa_cambio'], false);
            }

            return [
                "success" => true,
                "data" => $ventas
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener las ventas: " . $e->getMessage()
            ];
        }
    }

    public function listar($solo_activas = true)
{
    try {
        // Si solo_activas es true, mostrar solo ventas no cerradas en caja
        if ($solo_activas) {
            $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre
                      FROM ventas v
                      LEFT JOIN clientes c ON v.cliente_id = c.id
                      LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                      WHERE v.cerrada_en_caja = FALSE  -- ← NUEVO FILTRO
                      ORDER BY v.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $ventas = $stmt->fetchAll();
        } else {
            // Código original para mostrar todas las ventas
            $stmt = $this->venta->leer();
            $ventas = $stmt->fetchAll();
        }

        // Formatear cada venta para mostrar precios
        foreach ($ventas as &$venta) {
            $venta['total_formateado_usd'] = TasaCambioHelper::formatearUSD($venta['total']);
            $venta['total_formateado_bs'] = TasaCambioHelper::formatearBS($venta['total_bs']);
            $venta['tasa_formateada'] = TasaCambioHelper::formatearBS($venta['tasa_cambio'], false);
            
            // Añadir indicador de si está cerrada en caja
            $venta['cerrada_en_caja_texto'] = isset($venta['cerrada_en_caja']) && $venta['cerrada_en_caja'] 
                ? 'Cerrada' 
                : 'Activa';
        }

        return [
            "success" => true,
            "data" => $ventas,
            "filtro_activas" => $solo_activas
        ];
    } catch (Exception $e) {
        return [
            "success" => false,
            "message" => "Error al obtener las ventas: " . $e->getMessage()
        ];
    }
}

    public function obtener($id)
    {
        try {
            $venta = $this->venta->obtenerPorId($id);

            if ($venta) {
                $venta['detalles'] = $this->obtenerDetallesCompletos($id);

                // Formatear precios para mostrar
                $venta['total_formateado_usd'] = TasaCambioHelper::formatearUSD($venta['total']);
                $venta['total_formateado_bs'] = TasaCambioHelper::formatearBS($venta['total_bs']);
                $venta['tasa_formateada'] = TasaCambioHelper::formatearBS($venta['tasa_cambio'], false);

                foreach ($venta['detalles'] as &$detalle) {
                    $detalle['precio_unitario_formateado_usd'] = TasaCambioHelper::formatearUSD($detalle['precio_unitario']);
                    $detalle['precio_unitario_formateado_bs'] = TasaCambioHelper::formatearBS($detalle['precio_unitario_bs']);
                    $detalle['subtotal_formateado_usd'] = TasaCambioHelper::formatearUSD($detalle['subtotal']);
                    $detalle['subtotal_formateado_bs'] = TasaCambioHelper::formatearBS($detalle['subtotal_bs']);

                    // Añadir información específica de precio fijo
                    if ($detalle['es_precio_fijo']) {
                        $detalle['precio_fijo_formateado'] = TasaCambioHelper::formatearBS($detalle['precio_unitario_bs']);
                    }
                }

                return [
                    "success" => true,
                    "data" => $venta
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Venta no encontrada"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener la venta: " . $e->getMessage()
            ];
        }
    }

    public function obtenerDetallesCompletos($venta_id)
    {
        try {
            $query = "SELECT 
                dv.*, 
                p.nombre as producto_nombre, 
                p.codigo_sku,
                p.usar_precio_fijo_bs,
                p.precio_bs as precio_fijo_original
              FROM detalle_ventas dv
              JOIN productos p ON dv.producto_id = p.id
              WHERE dv.venta_id = :venta_id";

            // CORRECCIÓN: Cambiar $this->conn por $this->db
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":venta_id", $venta_id);
            $stmt->execute();
            $detalles = $stmt->fetchAll();

            // Marcar qué detalles tienen precio fijo
            foreach ($detalles as &$detalle) {
                $detalle['es_precio_fijo'] = isset($detalle['usar_precio_fijo_bs']) && $detalle['usar_precio_fijo_bs'] && !empty($detalle['precio_fijo_original']);
            }

            return $detalles;
        } catch (Exception $e) {
            error_log("Error al obtener detalles completos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerDetalles($venta_id)
    {
        try {
            $detalles = $this->venta->obtenerDetalles($venta_id);
            return $detalles;
        } catch (Exception $e) {
            return [];
        }
    }

    public function crearOLD($data)
    {
    try {
        $this->db->beginTransaction();

        // Validar datos requeridos
        if (empty($data['cliente_id']) || empty($data['detalles']) || !is_array($data['detalles'])) {
            throw new Exception("Datos incompletos para crear la venta");
        }

        // Obtener tasa de cambio actual
        $tasa_actual = $this->tasaCambio->obtenerTasaActual();
        if (!$tasa_actual) {
            throw new Exception("No hay tasa de cambio configurada. Configure la tasa primero.");
        }
        $tasa_cambio = $tasa_actual['tasa_cambio'];

        // Calcular totales
        $total_usd = 0;
        $total_bs = 0;
        $detalles_procesados = [];

        foreach ($data['detalles'] as $detalle) {
            // Obtener información completa del producto
            $producto = $this->producto->obtenerPorId($detalle['producto_id']);
            if (!$producto) {
                throw new Exception("Producto no encontrado: " . $detalle['producto_id']);
            }

            // Verificar stock (SOLO VALIDACIÓN, SIN DESCONTAR)
            if ($producto['stock_actual'] < $detalle['cantidad']) {
                throw new Exception("Stock insuficiente para el producto: " . $producto['nombre'] .
                    " (Stock disponible: " . $producto['stock_actual'] . ", Solicitado: " . $detalle['cantidad'] . ")");
            }

            // Determinar si es producto con precio fijo en BS
            $es_precio_fijo = isset($producto['usar_precio_fijo_bs']) && $producto['usar_precio_fijo_bs'] == true;

            // Variables para precios
            $precio_unitario_usd = 0;
            $precio_unitario_bs = 0;
            $subtotal_usd = 0;
            $subtotal_bs = 0;

            if ($es_precio_fijo) {
                // PRODUCTO CON PRECIO FIJO EN BS
                if (!empty($producto['precio_bs']) && $producto['precio_bs'] > 0) {
                    $precio_unitario_bs = floatval($producto['precio_bs']);
                    $subtotal_bs = $detalle['cantidad'] * $precio_unitario_bs;

                    if ($tasa_cambio > 0) {
                        $precio_unitario_usd = $precio_unitario_bs / $tasa_cambio;
                        $subtotal_usd = $subtotal_bs / $tasa_cambio;
                    }
                } else {
                    throw new Exception("El producto '{$producto['nombre']}' está marcado como precio fijo pero no tiene precio en BS definido.");
                }
            } else {
                // PRODUCTO SIN PRECIO FIJO
                if (isset($detalle['precio_unitario']) && $detalle['precio_unitario'] > 0) {
                    $precio_unitario_usd = floatval($detalle['precio_unitario']);
                } else if (isset($producto['precio']) && $producto['precio'] > 0) {
                    $precio_unitario_usd = floatval($producto['precio']);
                } else {
                    throw new Exception("El producto '{$producto['nombre']}' no tiene precio definido.");
                }

                $precio_unitario_bs = $precio_unitario_usd * $tasa_cambio;
                $subtotal_usd = $detalle['cantidad'] * $precio_unitario_usd;
                $subtotal_bs = $detalle['cantidad'] * $precio_unitario_bs;
            }

            // Acumular totales
            $total_usd += $subtotal_usd;
            $total_bs += $subtotal_bs;

            // Guardar detalle procesado
            $detalles_procesados[] = [
                'producto_id' => $detalle['producto_id'],
                'cantidad' => $detalle['cantidad'],
                'precio_unitario' => $precio_unitario_usd,
                'precio_unitario_bs' => $precio_unitario_bs,
                'subtotal_usd' => $subtotal_usd,
                'subtotal_bs' => $subtotal_bs,
                'es_precio_fijo' => $es_precio_fijo,
                'producto_nombre' => $producto['nombre'],
                'precio_fijo_original' => $es_precio_fijo ? floatval($producto['precio_bs']) : null
            ];
        }

        // Crear la venta
        $ventaData = [
            'cliente_id' => $data['cliente_id'],
            'tipo_pago_id' => $data['tipo_pago_id'],
            'subtotal' => $total_usd,
            'total' => $total_usd,
            'tasa_cambio_utilizada' => $tasa_cambio,
            'total_bs' => $total_bs,
            'estado' => $data['estado'] ?? 'pendiente',
            'fecha_hora' => $data['fecha_hora'] ?? date('Y-m-d H:i:s'),
            'observaciones' => $data['observaciones'] ?? ''
        ];

        $resultado_venta = $this->venta->crear($ventaData);

        if (!$resultado_venta) {
            throw new Exception("Error al crear la venta");
        }

        $venta_id = $resultado_venta['id'];
        $numero_venta = $resultado_venta['numero_venta'];

        // Crear detalles de venta
        $detallesData = [];
        foreach ($detalles_procesados as $detalle) {
            $detallesData[] = [
                'venta_id' => $venta_id,
                'producto_id' => $detalle['producto_id'],
                'cantidad' => $detalle['cantidad'],
                'precio_unitario' => $detalle['precio_unitario'],
                'precio_unitario_bs' => $detalle['precio_unitario_bs'],
                'subtotal' => $detalle['subtotal_usd'],
                'subtotal_bs' => $detalle['subtotal_bs']
            ];
        }

        if (!$this->venta->crearDetalles($detallesData)) {
            throw new Exception("Error al crear los detalles de venta");
        }

        // ¡IMPORTANTE! NO ACTUALIZAR EL STOCK AQUÍ
        // El stock solo se actualizará cuando la venta se marque como "completada"
        // La base de datos tiene triggers que se encargan de esto automáticamente
        
        $this->db->commit();

        return [
            "success" => true,
            "message" => "Venta creada exitosamente",
            "venta_id" => $venta_id,
            "numero_venta" => $numero_venta,
            "tasa_cambio" => $tasa_cambio,
            "total_usd" => $total_usd,
            "total_bs" => $total_bs,
            "detalles_procesados" => $detalles_procesados,
            "redirect_url" => "index.php"
        ];
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("ERROR en VentaController::crear: " . $e->getMessage());
        return [
            "success" => false,
            "message" => "Error al crear la venta: " . $e->getMessage()
        ];
    }
    }

    public function crear($data)
    {

         // ✅ ESTABLECER ZONA HORARIA CORRECTA
    $this->setTimeZone();
    
    try {
        $this->db->beginTransaction();

        // Validar datos requeridos
        if (empty($data['cliente_id']) || empty($data['detalles']) || !is_array($data['detalles'])) {
            throw new Exception("Datos incompletos para crear la venta");
        }

        // ✅ OBTENER FECHA Y HORA REAL DEL SERVIDOR CON ZONA HORARIA CORRECTA
        // Establecer zona horaria de Venezuela (GMT-4)
        date_default_timezone_set('America/Caracas');
        
        // FORZAR la fecha/hora actual del sistema
        $fecha_hora_actual = date('Y-m-d H:i:s');
        
        // DEBUG: Registrar la fecha que se está usando
        error_log("=== FECHA/HORA DE CREACIÓN DE VENTA ===");
        error_log("Fecha/Hora actual del servidor: " . $fecha_hora_actual);
        error_log("Zona horaria: " . date_default_timezone_get());

        // Obtener tasa de cambio actual
        $tasa_actual = $this->tasaCambio->obtenerTasaActual();
        if (!$tasa_actual) {
            throw new Exception("No hay tasa de cambio configurada. Configure la tasa primero.");
        }
        $tasa_cambio = $tasa_actual['tasa_cambio'];

        // Calcular totales
        $total_usd = 0;
        $total_bs = 0;
        $detalles_procesados = [];

        foreach ($data['detalles'] as $detalle) {
            // Obtener información completa del producto
            $producto = $this->producto->obtenerPorId($detalle['producto_id']);
            if (!$producto) {
                throw new Exception("Producto no encontrado: " . $detalle['producto_id']);
            }

            // Verificar stock
            if ($producto['stock_actual'] < $detalle['cantidad']) {
                throw new Exception("Stock insuficiente para el producto: " . $producto['nombre'] .
                    " (Stock disponible: " . $producto['stock_actual'] . ", Solicitado: " . $detalle['cantidad'] . ")");
            }

            // Determinar si es producto con precio fijo en BS
            $es_precio_fijo = isset($producto['usar_precio_fijo_bs']) && $producto['usar_precio_fijo_bs'] == true;

            // Variables para precios
            $precio_unitario_usd = 0;
            $precio_unitario_bs = 0;
            $subtotal_usd = 0;
            $subtotal_bs = 0;

            if ($es_precio_fijo) {
                // PRODUCTO CON PRECIO FIJO EN BS
                if (!empty($producto['precio_bs']) && $producto['precio_bs'] > 0) {
                    $precio_unitario_bs = floatval($producto['precio_bs']);
                    $subtotal_bs = $detalle['cantidad'] * $precio_unitario_bs;

                    if ($tasa_cambio > 0) {
                        $precio_unitario_usd = $precio_unitario_bs / $tasa_cambio;
                        $subtotal_usd = $subtotal_bs / $tasa_cambio;
                    }
                } else {
                    throw new Exception("El producto '{$producto['nombre']}' está marcado como precio fijo pero no tiene precio en BS definido.");
                }
            } else {
                // PRODUCTO SIN PRECIO FIJO
                if (isset($detalle['precio_unitario']) && $detalle['precio_unitario'] > 0) {
                    $precio_unitario_usd = floatval($detalle['precio_unitario']);
                } else if (isset($producto['precio']) && $producto['precio'] > 0) {
                    $precio_unitario_usd = floatval($producto['precio']);
                } else {
                    throw new Exception("El producto '{$producto['nombre']}' no tiene precio definido.");
                }

                $precio_unitario_bs = $precio_unitario_usd * $tasa_cambio;
                $subtotal_usd = $detalle['cantidad'] * $precio_unitario_usd;
                $subtotal_bs = $detalle['cantidad'] * $precio_unitario_bs;
            }

            // Acumular totales
            $total_usd += $subtotal_usd;
            $total_bs += $subtotal_bs;

            // Guardar detalle procesado
            $detalles_procesados[] = [
                'producto_id' => $detalle['producto_id'],
                'cantidad' => $detalle['cantidad'],
                'precio_unitario' => $precio_unitario_usd,
                'precio_unitario_bs' => $precio_unitario_bs,
                'subtotal_usd' => $subtotal_usd,
                'subtotal_bs' => $subtotal_bs,
                'es_precio_fijo' => $es_precio_fijo,
                'producto_nombre' => $producto['nombre'],
                'precio_fijo_original' => $es_precio_fijo ? floatval($producto['precio_bs']) : null
            ];
        }

        // ✅ CREAR LA VENTA - CON FECHA/HORA FORZADA Y CORRECTA
        $ventaData = [
            'cliente_id' => $data['cliente_id'],
            'tipo_pago_id' => $data['tipo_pago_id'],
            'subtotal' => $total_usd,
            'total' => $total_usd,
            'tasa_cambio_utilizada' => $tasa_cambio,
            'total_bs' => $total_bs,
            'estado' => $data['estado'] ?? 'pendiente',
            'fecha_hora' => $fecha_hora_actual, // ✅ FECHA/HORA ACTUAL FORZADA
            'observaciones' => $data['observaciones'] ?? ''
        ];

        $resultado_venta = $this->venta->crear($ventaData);

        if (!$resultado_venta) {
            throw new Exception("Error al crear la venta");
        }

        $venta_id = $resultado_venta['id'];
        $numero_venta = $resultado_venta['numero_venta'];

        // Crear detalles de venta
        $detallesData = [];
        foreach ($detalles_procesados as $detalle) {
            $detallesData[] = [
                'venta_id' => $venta_id,
                'producto_id' => $detalle['producto_id'],
                'cantidad' => $detalle['cantidad'],
                'precio_unitario' => $detalle['precio_unitario'],
                'precio_unitario_bs' => $detalle['precio_unitario_bs'],
                'subtotal' => $detalle['subtotal_usd'],
                'subtotal_bs' => $detalle['subtotal_bs']
            ];
        }

        if (!$this->venta->crearDetalles($detallesData)) {
            throw new Exception("Error al crear los detalles de venta");
        }

        $this->db->commit();

        return [
            "success" => true,
            "message" => "Venta creada exitosamente",
            "venta_id" => $venta_id,
            "numero_venta" => $numero_venta,
            "tasa_cambio" => $tasa_cambio,
            "total_usd" => $total_usd,
            "total_bs" => $total_bs,
            "detalles_procesados" => $detalles_procesados,
            "fecha_hora" => $fecha_hora_actual, // ✅ DEVOLVER LA FECHA USADA
            "fecha_formateada" => date('d/m/Y H:i:s'), // ✅ FECHA FORMATEADA PARA MOSTRAR
            "redirect_url" => "index.php"
        ];
    } catch (Exception $e) {
        $this->db->rollBack();
        error_log("ERROR en VentaController::crear: " . $e->getMessage());
        return [
            "success" => false,
            "message" => "Error al crear la venta: " . $e->getMessage()
        ];
    }
    }

    /**
     * Establece la zona horaria correcta para toda la aplicación
     */
    private function setTimeZone()
    {
        // Zona horaria de Venezuela
        $timezone = 'America/Caracas';
        
        // Establecer en PHP
        date_default_timezone_set($timezone);
        
        // Establecer en la base de datos (PostgreSQL)
        try {
            $this->db->exec("SET TIME ZONE 'America/Caracas'");
            $this->db->exec("SET datestyle TO 'ISO, DMY'");
        } catch (Exception $e) {
            error_log("Error configurando zona horaria en BD: " . $e->getMessage());
        }
        
        return $timezone;
    }

    public function actualizarEstado($id, $estado)
{
    try {
        $this->db->beginTransaction();

        // Primero, obtener información de la venta
        $venta = $this->venta->obtenerPorId($id);
        if (!$venta) {
            throw new Exception("Venta no encontrada");
        }

        // Actualizar estado en la venta
        $query = "UPDATE ventas SET estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":id", $id);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar estado de la venta");
        }

        // ¡IMPORTANTE! NO ACTUALIZAR EL STOCK AQUÍ
        // La base de datos tiene triggers que se encargan automáticamente de:
        // 1. Descontar stock cuando la venta pasa a 'completada'
        // 2. Reversar stock si una venta 'completada' se cancela
        
        $this->db->commit();

        return [
            "success" => true,
            "message" => "Estado de la venta #{$venta['numero_venta']} actualizado a '{$estado}'"
        ];
    } catch (Exception $e) {
        $this->db->rollBack();
        return [
            "success" => false,
            "message" => "Error al actualizar estado: " . $e->getMessage()
        ];
    }
}
    public function obtenerEstadisticas()
    {
        try {
            $estadisticas = $this->venta->obtenerEstadisticas();

            // Formatear las estadísticas
            if ($estadisticas) {
                $estadisticas['ingresos_totales_formateado'] = TasaCambioHelper::formatearUSD($estadisticas['ingresos_totales'] ?? 0);
                $estadisticas['ticket_promedio_formateado'] = TasaCambioHelper::formatearUSD($estadisticas['ticket_promedio'] ?? 0);
            }

            return [
                "success" => true,
                "data" => $estadisticas
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener estadísticas: " . $e->getMessage()
            ];
        }
    }

    public function obtenerVentasPorMes()
    {
        try {
            $ventas_mes = $this->venta->obtenerVentasPorMes();
            return [
                "success" => true,
                "data" => $ventas_mes
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener ventas por mes: " . $e->getMessage()
            ];
        }
    }

    public function buscar($search)
    {
        try {
            $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre
                      FROM ventas v
                      LEFT JOIN clientes c ON v.cliente_id = c.id
                      LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                      WHERE v.numero_venta::text ILIKE :search 
                         OR c.nombre ILIKE :search
                         OR tp.nombre ILIKE :search
                      ORDER BY v.created_at DESC";

            $stmt = $this->db->prepare($query);
            $searchTerm = "%" . $search . "%";
            $stmt->bindParam(":search", $searchTerm);
            $stmt->execute();
            $ventas = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $ventas
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al buscar ventas: " . $e->getMessage()
            ];
        }
    }

    // Nuevo método para obtener productos con precio fijo
    public function obtenerProductosConInfoCompleta()
    {
        try {
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                      FROM productos p
                      LEFT JOIN categorias c ON p.categoria_id = c.id
                      WHERE p.activo = TRUE
                      ORDER BY p.nombre";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $productos = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $productos
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener productos: " . $e->getMessage()
            ];
        }
    }

    public function obtenerTotalVentasCompletadasBs()
{
    try {
        $query = "SELECT 
                    COUNT(*) as total_ventas,
                    SUM(total) as total_usd,
                    SUM(total_bs) as total_bs
                  FROM ventas 
                  WHERE estado = 'completada'";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            "success" => true,
            "data" => $resultado
        ];
    } catch (Exception $e) {
        return [
            "success" => false,
            "message" => "Error al obtener total de ventas completadas: " . $e->getMessage()
        ];
    }
    }


    // Método para obtener ventas activas del día
    public function obtenerVentasActivasHoy()
    {
        try {
            $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre
                  FROM ventas v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                  WHERE v.estado = 'completada'
                  AND DATE(v.fecha_hora) = CURRENT_DATE
                  AND v.cerrada_en_caja = FALSE
                  ORDER BY v.fecha_hora DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $ventas = $stmt->fetchAll();

            // Formatear cada venta para mostrar precios
            foreach ($ventas as &$venta) {
                $venta['total_formateado_usd'] = TasaCambioHelper::formatearUSD($venta['total']);
                $venta['total_formateado_bs'] = TasaCambioHelper::formatearBS($venta['total_bs']);
                $venta['fecha_formateada'] = date('d/m/Y H:i', strtotime($venta['fecha_hora']));
            }

            return [
                "success" => true,
                "data" => $ventas,
                "total_ventas" => count($ventas)
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener ventas activas: " . $e->getMessage()
            ];
        }
    }


    public function obtenerResumenHoy()
{
    try {
        // IDs de tipos de pago que son en USD/DIVISAS
        $tiposPagoUSD = [2, 7]; // SOLO Efectivo USD y Divisa
        $tiposPagoUSDStr = implode(',', $tiposPagoUSD);
        
        $query = "
            SELECT 
                COUNT(*) as ventas_hoy,
                COUNT(DISTINCT cliente_id) as clientes_hoy,
                MIN(fecha_hora) as primera_venta,
                MAX(fecha_hora) as ultima_venta,
                
                -- 💵 SOLO USD RECIBIDOS (tipos de pago 2 y 7)
                SUM(CASE WHEN tipo_pago_id IN ($tiposPagoUSDStr) THEN total ELSE 0 END) as total_usd_hoy,
                
                -- DESGLOSE DE USD RECIBIDOS
                SUM(CASE WHEN tipo_pago_id = 2 THEN total ELSE 0 END) as total_efectivo_usd,
                SUM(CASE WHEN tipo_pago_id = 7 THEN total ELSE 0 END) as total_divisa,
                
                -- CONTEO DE VENTAS EN USD
                SUM(CASE WHEN tipo_pago_id = 2 THEN 1 ELSE 0 END) as ventas_efectivo_usd,
                SUM(CASE WHEN tipo_pago_id = 7 THEN 1 ELSE 0 END) as ventas_divisa
                
            FROM ventas 
            WHERE estado = 'completada'
            AND DATE(fecha_hora) = CURRENT_DATE
            AND cerrada_en_caja = FALSE
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resumen) {
            // 💵 Formatear USD
            $resumen['total_usd_hoy_formateado'] = TasaCambioHelper::formatearUSD($resumen['total_usd_hoy'] ?? 0);
            $resumen['total_efectivo_usd_formateado'] = TasaCambioHelper::formatearUSD($resumen['total_efectivo_usd'] ?? 0);
            $resumen['total_divisa_formateado'] = TasaCambioHelper::formatearUSD($resumen['total_divisa'] ?? 0);
            
            // ⏰ Formatear horas
            if ($resumen['primera_venta']) {
                $resumen['primera_venta_formateada'] = date('H:i', strtotime($resumen['primera_venta']));
            }
            if ($resumen['ultima_venta']) {
                $resumen['ultima_venta_formateada'] = date('H:i', strtotime($resumen['ultima_venta']));
            }
            
            // ❌ ELIMINAMOS total_bs_hoy de aquí - AHORA SE MANEJA APARTE
            unset($resumen['total_bs_hoy']);
            unset($resumen['total_bs_otros']);
        }

        return [
            "success" => true,
            "data" => $resumen
        ];
    } catch (Exception $e) {
        return [
            "success" => false,
            "message" => "Error al obtener resumen del día: " . $e->getMessage()
        ];
    }
    }

/**
 * Obtiene el total de ventas en Bs provenientes de productos con precio fijo
 */
    public function obtenerTotalPreciosFijosHoy()
    {
        try {
            $query = "
                SELECT 
        COALESCE(SUM(dv.precio_unitario_bs * dv.cantidad), 0) AS total_bs_precio_fijo,
        COUNT(DISTINCT v.id) AS ventas_con_precio_fijo
    FROM 
        detalle_ventas dv
    INNER JOIN 
        ventas v ON v.id = dv.venta_id
    WHERE 
        v.estado = 'completada'
        AND DATE(v.fecha_hora) = CURRENT_DATE
        AND v.cerrada_en_caja = FALSE
        AND dv.precio_unitario_bs > 0
        AND (dv.precio_unitario * v.tasa_cambio) != dv.precio_unitario_bs;
        ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'total_bs_precio_fijo' => $resultado['total_bs_precio_fijo'] ?? 0,
                    'total_bs_precio_fijo_formateado' => TasaCambioHelper::formatearBS($resultado['total_bs_precio_fijo'] ?? 0),
                    'ventas_con_precio_fijo' => $resultado['ventas_con_precio_fijo'] ?? 0
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener total de precios fijos: ' . $e->getMessage()
            ];
        }
    }


    // NUEVO: Verificar si hay ventas activas
    public function hayVentasActivas()
    {
        try {
            $query = "SELECT COUNT(*) as total 
                  FROM ventas 
                  WHERE estado = 'completada'
                  AND DATE(fecha_hora) = CURRENT_DATE
                  AND cerrada_en_caja = FALSE";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                "success" => true,
                "hay_ventas" => ($resultado['total'] > 0),
                "total_ventas" => $resultado['total']
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al verificar ventas activas: " . $e->getMessage()
            ];
        }
    }

    // Método para obtener ventas cerradas (para historial)
    public function obtenerVentasCerradas($limit = 100)
    {
        try {
            $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre,
                            cc.fecha as fecha_cierre
                    FROM ventas v
                    LEFT JOIN clientes c ON v.cliente_id = c.id
                    LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                    LEFT JOIN cierres_caja cc ON DATE(v.fecha_hora) = cc.fecha
                    WHERE v.cerrada_en_caja = TRUE
                    AND v.estado = 'completada'
                    ORDER BY v.fecha_hora DESC
                    LIMIT :limit";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            $ventas = $stmt->fetchAll();

            // Formatear cada venta para mostrar precios
            foreach ($ventas as &$venta) {
                $venta['total_formateado_usd'] = TasaCambioHelper::formatearUSD($venta['total']);
                $venta['total_formateado_bs'] = TasaCambioHelper::formatearBS($venta['total_bs']);
                $venta['tasa_formateada'] = TasaCambioHelper::formatearBS($venta['tasa_cambio'], false);
            }

            return [
                "success" => true,
                "data" => $ventas,
                "total" => count($ventas)
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener ventas cerradas: " . $e->getMessage()
            ];
        }
    }


}