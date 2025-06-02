<?php

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // Carregar configurações do banco
            $config = require __DIR__ . '/../config/database.php';
            
           
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
    
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']} COLLATE {$config['collation']}"
            ];
            
            // Mesclar com opções do config se existirem
            if (isset($config['options'])) {
                $options = array_merge($options, $config['options']);
            }
            
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $options
            );
            
            // CORREÇÃO: Definir collation para a sessão
            $this->connection->exec("SET collation_connection = {$config['collation']}");
            $this->connection->exec("SET character_set_results = {$config['charset']}");
            
        } catch (PDOException $e) {
            error_log('Erro de conexão com o banco de dados: ' . $e->getMessage());
            throw new Exception('Erro de conexão com o banco de dados');
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevenir clonagem
    private function __clone() {}
    
    // Prevenir deserialização
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}