<?php
class TasaCambioHelper
{
    /**
     * Obtiene la tasa de cambio actual
     */
    public static function obtenerTasaActual($db = null)
    {
        if ($db === null) {
            require_once __DIR__ . '/../Config/Database.php';
            $database = new Database();
            $db = $database->getConnection();
        }

        $query = "SELECT tasa_cambio FROM tasas_cambio WHERE activa = TRUE ORDER BY fecha_actualizacion DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['tasa_cambio'];
        }

        return 1; // Tasa por defecto
    }

    /**
     * Convierte USD a Bolívares
     */
    public static function convertirUSDaBS($montoUSD, $db = null)
    {
        $tasa = self::obtenerTasaActual($db);
        return $montoUSD * $tasa;
    }

    /**
     * Convierte Bolívares a USD
     */
    public static function convertirBSaUSD($montoBS, $db = null)
    {
        $tasa = self::obtenerTasaActual($db);
        return $tasa > 0 ? $montoBS / $tasa : 0;
    }

    /**
     * Formatea un precio en Bolívares
     */
    public static function formatearBS($precio, $simbolo = true)
    {
        $formatted = number_format($precio, 2, ',', '.');
        return $simbolo ? "Bs $formatted" : $formatted;
    }

    /**
     * Formatea un precio en USD
     */
    public static function formatearUSD($precio, $simbolo = true)
    {
        $formatted = number_format($precio, 2, ',', '.');
        return $simbolo ? "\$$formatted" : $formatted;
    }

    /**
     * Formatea el precio de un producto mostrando ambas monedas
     */
    public static function formatearPrecioProducto($producto, $db = null)
    {
        if (!isset($producto['precio_bs']) || $producto['precio_bs'] == 0) {
            $tasa = self::obtenerTasaActual($db);
            $precioBS = isset($producto['precio']) ? $producto['precio'] * $tasa : 0;
        } else {
            $precioBS = $producto['precio_bs'];
        }

        return self::formatearBS($precioBS);
    }

    /**
     * Calcula el margen de ganancia
     */
    public static function calcularMargenGanancia($precioVenta, $precioCosto)
    {
        if ($precioCosto == 0) return 0;
        return (($precioVenta - $precioCosto) / $precioCosto) * 100;
    }

    /**
     * Determina la clase CSS para el margen
     */
    public static function obtenerClaseMargen($margen)
    {
        if ($margen < 0) return 'bg-danger';
        if ($margen < 10) return 'bg-warning text-dark';
        if ($margen < 25) return 'bg-info';
        return 'bg-success';
    }

    /**
     * Obtiene la última tasa activa con información completa
     */
    public static function obtenerInformacionTasaActual($db)
    {
        $query = "SELECT * FROM tasas_cambio WHERE activa = TRUE ORDER BY fecha_actualizacion DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    /**
     * Verifica si una tasa está vigente (creada hoy)
     */
    public static function esTasaVigente($fechaActualizacion)
    {
        $hoy = date('Y-m-d');
        $fechaTasa = date('Y-m-d', strtotime($fechaActualizacion));
        return $fechaTasa == $hoy;
    }
}
