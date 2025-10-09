<<<<<<< HEAD
<?php
class Logger {
    private static $logFile = '../logs/error.log';

    public static function log($message, $type = 'ERROR', $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = "[$timestamp] [$type] $message $contextStr\n";
        
        // Asegurarse de que el directorio de logs existe
        $logDir = dirname(self::$logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Escribir el log
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }

    public static function logDBError($error, $query = '', $params = []) {
        $context = [
            'query' => $query,
            'params' => $params,
            'error_code' => $error->getCode(),
            'error_info' => $error instanceof PDOException ? $error->errorInfo : null,
            'trace' => $error->getTraceAsString()
        ];
        
        self::log($error->getMessage(), 'DATABASE_ERROR', $context);
    }

    public static function logSecurityEvent($event, $userId = null, $details = []) {
        $context = array_merge([
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], $details);
        
        self::log($event, 'SECURITY', $context);
    }
}
=======
<?php
class Logger {
    private static $logFile = '../logs/error.log';

    public static function log($message, $type = 'ERROR', $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = "[$timestamp] [$type] $message $contextStr\n";
        
        // Asegurarse de que el directorio de logs existe
        $logDir = dirname(self::$logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Escribir el log
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }

    public static function logDBError($error, $query = '', $params = []) {
        $context = [
            'query' => $query,
            'params' => $params,
            'error_code' => $error->getCode(),
            'error_info' => $error instanceof PDOException ? $error->errorInfo : null,
            'trace' => $error->getTraceAsString()
        ];
        
        self::log($error->getMessage(), 'DATABASE_ERROR', $context);
    }

    public static function logSecurityEvent($event, $userId = null, $details = []) {
        $context = array_merge([
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], $details);
        
        self::log($event, 'SECURITY', $context);
    }
}
>>>>>>> 41e6a2471d9fdb9e7687c1397ec07e0ab9623e75
?>