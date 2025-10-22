<?php
class Ayuda
{
    public static function sanitizeInput($data)
    {
        if (is_null($data)) return '';
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    public static function formatCurrency($amount, $decimals = 2)
    {
        return CURRENCY_SYMBOL . number_format($amount, $decimals, DECIMAL_SEPARATOR, THOUSANDS_SEPARATOR);
    }

    public static function formatDate($date, $format = DATE_FORMAT)
    {
        if (empty($date)) return '';
        
        $timestamp = strtotime($date);
        return $timestamp !== false ? date($format, $timestamp) : $date;
    }

    public static function generateSKU($name, $category_id = null)
    {
        $prefix = substr(strtoupper(preg_replace('/[^A-Z]/', '', $name)), 0, 3);
        $category_code = $category_id ? str_pad($category_id, 3, '0', STR_PAD_LEFT) : '000';
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        return $prefix . $category_code . $random;
    }

    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function redirect($url, $statusCode = 303)
    {
        header('Location: ' . $url, true, $statusCode);
        exit();
    }
}