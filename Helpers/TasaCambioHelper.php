<?php
class TasaCambioHelper
{
    /**
     * Formatear cantidad en USD
     */
    public static function formatearUSD($cantidad, $incluirSimbolo = true)
    {
        // Validar que la cantidad no sea nula
        if ($cantidad === null || $cantidad === '') {
            $cantidad = 0;
        }
        $formateado = number_format($cantidad, 2);
        return $incluirSimbolo ? '$' . $formateado : $formateado;
    }
    /**
     * Formatear cantidad en Bolívares
     */
    public static function formatearBS($cantidad, $incluirSimbolo = true)
    {
        // Validar que la cantidad no sea nula
        if ($cantidad === null || $cantidad === '') {
            $cantidad = 0;
        }
        $formateado = number_format($cantidad, 2);
        return $incluirSimbolo ? 'Bs ' . $formateado : $formateado;
    }

    /**
     * Convertir USD a BS usando tasa específica
     */
    public static function convertirUSDaBS($cantidadUSD, $tasaCambio)
    {
        return $cantidadUSD * $tasaCambio;
    }

    /**
     * Convertir BS a USD usando tasa específica
     */
    public static function convertirBSaUSD($cantidadBS, $tasaCambio)
    {
        return $tasaCambio > 0 ? $cantidadBS / $tasaCambio : 0;
    }

    /**
     * Validar si un producto debería usar precio fijo
     */
    public static function debeUsarPrecioFijo($producto)
    {
        return isset($producto['usar_precio_fijo_bs']) &&
            $producto['usar_precio_fijo_bs'] && 
            !empty($producto['precio_bs']) && 
            $producto['precio_bs'] > 0;
    }

    /**
     * Obtener precio en BS para mostrar
     */
    public static function obtenerPrecioBS($producto, $tasaCambio)
    {
        if (self::debeUsarPrecioFijo($producto)) {
            return [
                'precio' => $producto['precio_bs'],
                'tipo' => 'fijo',
                'texto' => 'Precio fijo en Bs'
            ];
        } else {
            return [
                'precio' => $producto['precio'] * $tasaCambio,
                'tipo' => 'calculado',
                'texto' => 'Calculado por tasa'
            ];
        }
    }

    /**
     * Calcular subtotal considerando precio fijo
     */
    public static function calcularSubtotal($producto, $cantidad, $tasaCambio)
    {
        $precioBS = self::obtenerPrecioBS($producto, $tasaCambio);
        
        return [
            'subtotal_usd' => $producto['precio'] * $cantidad,
            'subtotal_bs' => $precioBS['precio'] * $cantidad,
            'tipo_precio' => $precioBS['tipo'],
            'descripcion' => $precioBS['texto']
        ];
    }

    /**
     * Calcular margen de ganancia
     */
    public static function calcularMargenGanancia($precioVenta, $precioCosto)
    {
        if ($precioCosto <= 0) {
            return [
                'porcentaje' => 0,
                'monto' => 0,
                'mensaje' => 'Sin costo'
            ];
        }
        
        $margen = $precioVenta - $precioCosto;
        $porcentaje = ($margen / $precioCosto) * 100;
        
        return [
            'porcentaje' => round($porcentaje, 2),
            'monto' => $margen,
            'mensaje' => $porcentaje > 0 ? number_format($porcentaje, 2) . '%' : 'Sin ganancia'
        ];
    }

    /**
     * Determina la clase CSS para el margen de ganancia
     */
    public static function obtenerClaseMargen($margen)
    {
        if ($margen < 10) {
            return 'bg-danger'; // Muy bajo
        } elseif ($margen < 20) {
            return 'bg-warning'; // Bajo
        } elseif ($margen < 30) {
            return 'bg-info'; // Normal
        } else {
            return 'bg-success'; // Excelente
        }
    }

    /**
     * Formatea el precio del producto en Bolívares
     */
    public static function formatearPrecioProducto($producto, $db)
    {
        $precioUSD = $producto['precio'] ?? 0;
        
        // Obtener tasa de cambio actual
        $tasaCambio = self::obtenerTasaCambioActual($db);
        
        // Convertir a Bs
        $precioBs = $precioUSD * $tasaCambio;
        
        return self::formatearBS($precioBs);
    }

    /**
     * Convierte USD a Bs usando la base de datos
     */
    public static function convertirUSDaBSconDB($precioUSD, $db)
    {
        $tasaCambio = self::obtenerTasaCambioActual($db);
        return self::convertirUSDaBS($precioUSD, $tasaCambio);
    }

    /**
     * Obtiene la tasa de cambio actual desde la base de datos
     */
    private static function obtenerTasaCambioActual($db)
    {
        try {
            $query = "SELECT tasa_cambio FROM tasas_cambio 
                    WHERE activa = 1 
                    ORDER BY fecha_actualizacion DESC 
                    LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row['tasa_cambio'];
            }
            
            // Tasa por defecto si no hay ninguna activa
            return 1.0;
            
        } catch (PDOException $e) {
            error_log("Error obteniendo tasa de cambio: " . $e->getMessage());
            return 1.0; // Tasa por defecto en caso de error
        }
    }

    /**
     * Obtiene el margen como porcentaje simple (para compatibilidad con código existente)
     */
    public static function obtenerPorcentajeMargen($precioVenta, $precioCosto)
    {
        $margenData = self::calcularMargenGanancia($precioVenta, $precioCosto);
        return $margenData['porcentaje'];
    }

    /**
     * Determina si el margen es saludable
     */
    public static function esMargenSaludable($porcentajeMargen)
    {
        return $porcentajeMargen >= 20;
    }

    /**
     * Obtiene recomendación basada en el margen
     */
    public static function obtenerRecomendacionMargen($porcentajeMargen)
    {
        if ($porcentajeMargen < 10) {
            return 'Ajustar precio - Margen muy bajo';
        } elseif ($porcentajeMargen < 20) {
            return 'Revisar costos - Margen bajo';
        } elseif ($porcentajeMargen < 30) {
            return 'Margen aceptable';
        } else {
            return 'Excelente margen';
        }
    }

    /**
     * Formatea el margen para mostrar
     */
    public static function formatearMargen($porcentajeMargen)
    {
        $clase = self::obtenerClaseMargen($porcentajeMargen);
        $recomendacion = self::obtenerRecomendacionMargen($porcentajeMargen);
        
        return [
            'porcentaje' => number_format($porcentajeMargen, 2) . '%',
            'clase' => $clase,
            'recomendacion' => $recomendacion
        ];
    }
}