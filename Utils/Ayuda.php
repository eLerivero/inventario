<?php
class ayuda
{

    public static function formatCurrency($amount)
    {
        return DEFAULT_CURRENCY . number_format($amount, 2);
    }

    public static function formatDate($date, $format = 'd/m/Y H:i')
    {
        return date($format, strtotime($date));
    }

    public static function sanitizeInput($data)
    {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    public static function redirect($url)
    {
        header("Location: " . $url);
        exit();
    }

    public static function getCurrentDateTime()
    {
        return date('Y-m-d H:i:s');
    }

    public static function generateSKU($name)
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
        $random = mt_rand(1000, 9999);
        return $prefix . '-' . $random;
    }
}
