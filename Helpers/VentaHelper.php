<?php
require_once __DIR__ . '/TasaCambioHelper.php';

class VentaHelper
{
    /**
     * Formatear venta para mostrar en listados
     */
    public static function formatearVentaParaLista($venta)
    {
        return [
            'id' => $venta['id'],
            'numero_venta' => $venta['numero_venta'],
            'cliente_nombre' => $venta['cliente_nombre'] ?? 'Cliente no identificado',
            'tipo_pago_nombre' => $venta['tipo_pago_nombre'] ?? 'No especificado',
            'fecha_hora' => date('d/m/Y H:i', strtotime($venta['fecha_hora'])),
            'total_usd' => TasaCambioHelper::formatearUSD($venta['total'] ?? 0),
            'total_bs' => TasaCambioHelper::formatearBS($venta['total_bs'] ?? 0),
            'estado' => $venta['estado'],
            'estado_color' => self::obtenerColorEstado($venta['estado']),
            'cerrada_en_caja' => isset($venta['cerrada_en_caja']) && $venta['cerrada_en_caja'],
            'es_activa' => !(isset($venta['cerrada_en_caja']) && $venta['cerrada_en_caja'])
        ];
    }

    /**
     * Obtener color según estado de la venta
     */
    public static function obtenerColorEstado($estado)
    {
        $colores = [
            'completada' => 'success',
            'pendiente' => 'warning',
            'cancelada' => 'danger',
            'anulada' => 'secondary',
            'procesando' => 'info'
        ];

        return $colores[$estado] ?? 'secondary';
    }

    /**
     * Verificar si una venta puede modificarse
     */
    public static function puedeModificarse($venta)
    {
        if (!isset($venta['estado'])) {
            return false;
        }

        // No se pueden modificar ventas completadas y cerradas en caja
        if (
            $venta['estado'] == 'completada' &&
            isset($venta['cerrada_en_caja']) &&
            $venta['cerrada_en_caja']
        ) {
            return false;
        }

        // No se pueden modificar ventas canceladas/anuladas
        if (in_array($venta['estado'], ['cancelada', 'anulada'])) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si una venta puede eliminarse
     */
    public static function puedeEliminarse($venta)
    {
        if (!isset($venta['estado'])) {
            return false;
        }

        // Solo se pueden eliminar ventas pendientes
        if ($venta['estado'] != 'pendiente') {
            return false;
        }

        // No se pueden eliminar ventas cerradas en caja
        if (isset($venta['cerrada_en_caja']) && $venta['cerrada_en_caja']) {
            return false;
        }

        return true;
    }

    /**
     * Calcular tiempo transcurrido desde la venta
     */
    public static function calcularTiempoTranscurrido($fecha_hora)
    {
        $fecha_venta = new DateTime($fecha_hora);
        $fecha_actual = new DateTime();
        $diferencia = $fecha_actual->diff($fecha_venta);

        if ($diferencia->d > 0) {
            return $diferencia->d . ' día(s)';
        } elseif ($diferencia->h > 0) {
            return $diferencia->h . ' hora(s)';
        } elseif ($diferencia->i > 0) {
            return $diferencia->i . ' minuto(s)';
        } else {
            return $diferencia->s . ' segundo(s)';
        }
    }

    /**
     * Generar resumen de ventas para dashboard
     */
    public static function generarResumenDashboard($ventas_activas)
    {
        $resumen = [
            'total_ventas' => count($ventas_activas),
            'total_usd' => 0,
            'total_bs' => 0,
            'clientes_unicos' => [],
            'tipos_pago' => [],
            'hora_pico' => [],
            'productos_populares' => []
        ];

        $ventas_por_hora = [];

        foreach ($ventas_activas as $venta) {
            $resumen['total_usd'] += $venta['total'] ?? 0;
            $resumen['total_bs'] += $venta['total_bs'] ?? 0;

            // Clientes únicos
            if (isset($venta['cliente_id'])) {
                $resumen['clientes_unicos'][$venta['cliente_id']] = true;
            }

            // Tipos de pago
            $tipo_pago = $venta['tipo_pago_nombre'] ?? 'No especificado';
            if (!isset($resumen['tipos_pago'][$tipo_pago])) {
                $resumen['tipos_pago'][$tipo_pago] = 0;
            }
            $resumen['tipos_pago'][$tipo_pago] += $venta['total_bs'] ?? 0;

            // Ventas por hora
            $hora = date('H', strtotime($venta['fecha_hora']));
            if (!isset($ventas_por_hora[$hora])) {
                $ventas_por_hora[$hora] = 0;
            }
            $ventas_por_hora[$hora]++;
        }

        // Encontrar hora pico
        if (!empty($ventas_por_hora)) {
            arsort($ventas_por_hora);
            $hora_pico = key($ventas_por_hora);
            $resumen['hora_pico'] = [
                'hora' => $hora_pico . ':00',
                'ventas' => $ventas_por_hora[$hora_pico]
            ];
        }

        $resumen['clientes_unicos'] = count($resumen['clientes_unicos']);

        return $resumen;
    }

    /**
     * Validar datos de venta antes de crear
     */
    public static function validarDatosVenta($data)
    {
        $errores = [];

        // Validar cliente
        if (empty($data['cliente_id']) || $data['cliente_id'] <= 0) {
            $errores[] = 'Cliente no válido';
        }

        // Validar tipo de pago
        if (empty($data['tipo_pago_id']) || $data['tipo_pago_id'] <= 0) {
            $errores[] = 'Tipo de pago no válido';
        }

        // Validar detalles
        if (empty($data['detalles']) || !is_array($data['detalles'])) {
            $errores[] = 'No hay detalles de venta';
        } else {
            foreach ($data['detalles'] as $index => $detalle) {
                if (empty($detalle['producto_id']) || $detalle['producto_id'] <= 0) {
                    $errores[] = "Detalle $index: Producto no válido";
                }
                if (empty($detalle['cantidad']) || $detalle['cantidad'] <= 0) {
                    $errores[] = "Detalle $index: Cantidad no válida";
                }
            }
        }

        return $errores;
    }

    /**
     * Generar número de venta manual (backup)
     */
    public static function generarNumeroVentaManual()
    {
        $prefijo = 'V-' . date('Ymd') . '-';
        $numero = rand(1000, 9999);
        return $prefijo . $numero;
    }

    /**
     * Formatear detalles de venta para mostrar
     */
    public static function formatearDetallesVenta($detalles)
    {
        $formateados = [];

        foreach ($detalles as $detalle) {
            $formateados[] = [
                'producto_nombre' => $detalle['producto_nombre'] ?? 'Producto desconocido',
                'codigo_sku' => $detalle['codigo_sku'] ?? '',
                'cantidad' => $detalle['cantidad'],
                'precio_unitario_usd' => TasaCambioHelper::formatearUSD($detalle['precio_unitario'] ?? 0),
                'precio_unitario_bs' => TasaCambioHelper::formatearBS($detalle['precio_unitario_bs'] ?? 0),
                'subtotal_usd' => TasaCambioHelper::formatearUSD($detalle['subtotal'] ?? 0),
                'subtotal_bs' => TasaCambioHelper::formatearBS($detalle['subtotal_bs'] ?? 0),
                'es_precio_fijo' => isset($detalle['usar_precio_fijo_bs']) && $detalle['usar_precio_fijo_bs'],
                'precio_fijo_original' => isset($detalle['precio_fijo_original']) ?
                    TasaCambioHelper::formatearBS($detalle['precio_fijo_original']) : null
            ];
        }

        return $formateados;
    }
}
