<?php
// Arquivo: agenda_ufpr/app/models/Database.php

/**
 * Classe para gerenciar a conexão com o banco de dados usando PDO
 */
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Construtor privado (padrão Singleton)
     */
    private function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            $this->connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die('Erro de conexão com o banco de dados: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtém a instância única da classe (padrão Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Retorna a conexão PDO ativa
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Impede a clonagem do objeto (padrão Singleton)
     */
    private function __clone() {}
}