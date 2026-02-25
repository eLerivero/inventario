<?php
class TasaCambioHelper
{
    private static $db = null;
    private static $tasaActual = null;

    /**
     * Obtener la tasa de cambio actual
     */
    public static function obtenerTasaActual($refresh = false)
    {
        if (self::$tasaActual === null || $refresh) {
            try {
                if (self::$db === null) {
                    require_once __DIR__ . '/../Config/Database.php';
                    $database = new Database();
                    self::$db = $database->getConnection();
                }

                $query = "SELECT tasa_cambio FROM tasas_cambio 
                         WHERE activa = TRUE 
                         ORDER BY fecha_actualizacion DESC 
                         LIMIT 1";

                $stmt = self::$db->prepare($query);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    self::$tasaActual = $stmt->fetch(PDO::FETCH_ASSOC)['tasa_cambio'];
                } else {
                    // Tasa por defecto si no hay configurada
                    self::$tasaActual = 36.50;
                }
            } catch (Exception $e) {
                error_log("Error obteniendo tasa de cambio: " . $e->getMessage());
                self::$tasaActual = 36.50;
            }
        }

        return self::$tasaActual;
    }

    /**
     * Convertir de USD a Bs
     */
    public static function convertirUsdABs($montoUsd, $tasa = null)
    {
        if ($tasa === null) {
            $tasa = self::obtenerTasaActual();
        }
        return round($montoUsd * $tasa, 2);
    }

    /**
     * Convertir de Bs a USD
     */
    public static function convertirBsAUsd($montoBs, $tasa = null)
    {
        if ($tasa === null) {
            $tasa = self::obtenerTasaActual();
        }
        return round($montoBs / $tasa, 2);
    }

    /**
     * Formatear moneda con símbolo
     */
    public static function formatearMoneda($monto, $moneda = 'USD', $tasa = null)
    {
        switch (strtoupper($moneda)) {
            case 'USD':
                return '$' . number_format($monto, 2, ',', '.');
            case 'VES':
            case 'BS':
                return 'Bs ' . number_format($monto, 2, ',', '.');
            case 'USD-BS':
                $tasa = $tasa ?? self::obtenerTasaActual();
                $bs = self::convertirUsdABs($monto, $tasa);
                return '$' . number_format($monto, 2, ',', '.') . ' (Bs ' . number_format($bs, 2, ',', '.') . ')';
            default:
                return number_format($monto, 2, ',', '.');
        }
    }

    /**
     * Obtener historial de tasas para gráficos
     */
    public static function obtenerHistorialParaGrafico($dias = 30)
    {
        try {
            if (self::$db === null) {
                require_once __DIR__ . '/../Config/Database.php';
                $database = new Database();
                self::$db = $database->getConnection();
            }

            $query = "SELECT 
                         DATE(fecha_actualizacion) as fecha,
                         tasa_cambio,
                         COUNT(*) as registros
                      FROM tasas_cambio 
                      WHERE fecha_actualizacion >= CURRENT_DATE - INTERVAL '$dias days'
                      GROUP BY DATE(fecha_actualizacion), tasa_cambio
                      ORDER BY fecha DESC";

            $stmt = self::$db->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo historial para gráfico: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si hay tasa configurada
     */
    public static function hayTasaConfigurada()
    {
        try {
            if (self::$db === null) {
                require_once __DIR__ . '/../Config/Database.php';
                $database = new Database();
                self::$db = $database->getConnection();
            }

            $query = "SELECT COUNT(*) as count FROM tasas_cambio WHERE activa = TRUE";
            $stmt = self::$db->prepare($query);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
