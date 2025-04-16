<?php
// app/helpers/DebugHelper.php

class DebugHelper {
    public static function log($message, $data = null) {
        $logDir = __DIR__ . '/../../logs';
        
        // Criar diretório de logs se não existir
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/redirect_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $logMessage = "[{$timestamp}] {$message}";
        
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $logMessage .= ":\n" . print_r($data, true);
            } else {
                $logMessage .= ": " . $data;
            }
        }
        
        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
    }
}