<?php
// Helpers/TasaCambioHelper.php
class TasaCambioHelper
{
    public static function obtenerTasaActual($db = null)
    {
        if (!$db) {
            require_once __DIR__ . '/../Config/Database.php';
            $database = new Database();
            $db = $database->getConnection();
        }

        $query = "SELECT tasa_cambio FROM tasas_cambio WHERE activa = TRUE ORDER BY fecha_actualizacion DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['tasa_cambio'] : 1;
    }

    public static function formatearUSD($monto)
    {
        return '$' . number_format($monto, 2);
    }

    public static function formatearBS($monto, $incluirSimbolo = true)
    {
        return ($incluirSimbolo ? 'Bs ' : '') . number_format($monto, 2);
    }

    public static function convertirUSDaBS($monto_usd, $db = null)
    {
        $tasa = self::obtenerTasaActual($db);
        return $monto_usd * $tasa;
    }

    public static function convertirBSaUSD($monto_bs, $db = null)
    {
        $tasa = self::obtenerTasaActual($db);
        return $tasa > 0 ? $monto_bs / $tasa : 0;
    }

    public static function calcularMargenGanancia($precio_venta, $precio_costo)
    {
        if ($precio_costo <= 0) {
            return 0;
        }
        return (($precio_venta - $precio_costo) / $precio_costo) * 100;
    }

    public static function obtenerClaseMargen($margen)
    {
        if ($margen < 10) {
            return 'bg-danger';
        } elseif ($margen < 25) {
            return 'bg-warning';
        } else {
            return 'bg-success';
        }
    }

    public static function formatearPrecioProducto($producto, $db = null)
    {
        if (isset($producto['usar_precio_fijo_bs']) && $producto['usar_precio_fijo_bs']) {
            $badge = '<span class="badge bg-warning">Fijo</span> ';
            return $badge . 'Bs ' . number_format($producto['precio_bs'], 2);
        } else {
            if (isset($producto['precio_bs'])) {
                return 'Bs ' . number_format($producto['precio_bs'], 2);
            } else {
                $precio_bs = self::convertirUSDaBS($producto['precio'], $db);
                return 'Bs ' . number_format($precio_bs, 2);
            }
        }
    }

    public static function obtenerPrecioBsProducto($producto, $db = null)
    {
        if (isset($producto['usar_precio_fijo_bs']) && $producto['usar_precio_fijo_bs']) {
            return $producto['precio_bs'];
        } else {
            $tasa = self::obtenerTasaActual($db);
            return $producto['precio'] * $tasa;
        }
    }
}
