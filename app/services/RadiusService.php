<?php
// app/services/RadiusService.php (versão atualizada)

class RadiusService {
    private $server;
    private $secret;
    
    public function __construct() {
        $this->server = defined('RADIUS_SERVER') ? RADIUS_SERVER : '200.17.209.10';
        $this->secret = defined('RADIUS_SECRET') ? RADIUS_SECRET : 'rapadura';
    }
    
    public function authenticate($username, $password) {
        // Modo simulação para desenvolvimento
        if (defined('SIMULATE_RADIUS') && SIMULATE_RADIUS) {
            error_log("RADIUS DEBUG - Usando modo de simulação para usuário: " . $username);
            return $password === 'teste123';
        }
        
        try {
            // Verificar se a biblioteca foi carregada
            if (!class_exists('Dapphp\\Radius\\Radius')) {
                throw new Exception("Biblioteca Dapphp\\Radius não encontrada. Execute 'composer install'");
            }
            
            // Configurar RADIUS usando a API correta
            $radius = new \Dapphp\Radius\Radius();
            $radius->setServer($this->server)
                   ->setSecret($this->secret);
            
            // Tentar autenticação
            $result = $radius->accessRequest($username, $password);
            error_log("RADIUS DEBUG - Autenticação para usuário $username: " . ($result ? "SUCESSO" : "FALHA"));
            
            return $result;
        } catch (Exception $e) {
            error_log("RADIUS ERROR: " . $e->getMessage());
            return false;
        }
    }
}