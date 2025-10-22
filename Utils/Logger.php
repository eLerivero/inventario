<?php

// Verificar si la clase ya existe para evitar redeclaración
if (!class_exists('Logger')) {
    class Logger
    {
        private static $logFile = __DIR__ . '/../logs/app.log';
        
        public static function appLog($level, $message, $context = [])
        {
            try {
                // Asegurar que el directorio de logs existe
                $logDir = dirname(self::$logFile);
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0755, true);
                }
                
                $timestamp = date('Y-m-d H:i:s');
                $contextStr = !empty($context) ? json_encode($context) : '';
                $logEntry = "[$timestamp] [$level] $message $contextStr" . PHP_EOL;
                
                file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
                
            } catch (Exception $e) {
                // Fallback silencioso para evitar errores en producción
                error_log("Error en logger: " . $e->getMessage());
            }
        }
    }
}

// Verificar si la función ya existe para evitar redeclaración
if (!function_exists('appLog')) {
    function appLog($level, $message, $context = [])
    {
        Logger::appLog($level, $message, $context);
    }
}