<?php
class Ayuda
{
    public static function formatCurrency($amount)
    {
        return CURRENCY_SYMBOL . number_format($amount, 2, DECIMAL_SEPARATOR, THOUSANDS_SEPARATOR);
    }

    public static function formatDate($date, $format = DATE_FORMAT)
    {
        if (empty($date)) {
            return '';
        }
        return date($format, strtotime($date));
    }

    public static function formatDateTime($date, $format = DATETIME_FORMAT)
    {
        if (empty($date)) {
            return '';
        }
        return date($format, strtotime($date));
    }

    public static function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
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
        $timestamp = time();
        $random = mt_rand(100, 999);
        return $prefix . '-' . $timestamp . $random;
    }

    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateNumber($number, $min = null, $max = null)
    {
        if (!is_numeric($number)) {
            return false;
        }

        if ($min !== null && $number < $min) {
            return false;
        }

        if ($max !== null && $number > $max) {
            return false;
        }

        return true;
    }

    public static function getMimeType($file)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mime;
    }

    public static function isImage($file)
    {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime = self::getMimeType($file);
        return in_array($mime, $allowed_types);
    }

    public static function uploadFile($file, $destination, $max_size = MAX_FILE_SIZE)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error en la subida del archivo: " . $file['error']);
        }

        if ($file['size'] > $max_size) {
            throw new Exception("El archivo excede el tamaño máximo permitido");
        }

        if (!self::isImage($file['tmp_name'])) {
            throw new Exception("El archivo debe ser una imagen válida");
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $full_path = UPLOAD_DIR . $destination . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $full_path)) {
            throw new Exception("Error al mover el archivo subido");
        }

        return $filename;
    }

    public static function deleteFile($filename, $directory = '')
    {
        $file_path = UPLOAD_DIR . $directory . '/' . $filename;
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return false;
    }
}
