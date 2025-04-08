<?php
// app/services/RadiusService.php (versão com logs detalhados)

class RadiusService {
    private $server;
    private $secret;
    
    public function __construct() {
        $this->server = defined('RADIUS_SERVER') ? RADIUS_SERVER : '200.17.209.10';
        $this->secret = defined('RADIUS_SECRET') ? RADIUS_SECRET : 'rapadura';
        
        // Log para depuração
        error_log("RADIUS INFO - Servidor: {$this->server}, Secret: [protegido]");
    }
    
    public function authenticate($username, $password) {
        // Log para depuração - informações gerais
        error_log("RADIUS INFO - Tentando autenticar usuário: $username");
        
        // Modo simulação para desenvolvimento
        if (defined('SIMULATE_RADIUS') && SIMULATE_RADIUS) {
            error_log("RADIUS DEBUG - Usando modo de simulação");
            return $password === 'teste123';
        }
        
        // Log para depuração - bibliotecas
        error_log("RADIUS DEBUG - Dapphp\\Radius existe: " . (class_exists('Dapphp\\Radius\\Radius') ? 'SIM' : 'NÃO'));
        
        try {
            // Verificar se a biblioteca foi carregada
            if (!class_exists('Dapphp\\Radius\\Radius')) {
                error_log("RADIUS ERROR - Biblioteca Dapphp\\Radius não encontrada");
                throw new Exception("Biblioteca Dapphp\\Radius não encontrada. Execute 'composer install'");
            }
            
            // Configurar RADIUS
            $radius = new \Dapphp\Radius\Radius();
            $radius->setServer($this->server);
            $radius->setSecret($this->secret);
            
            error_log("RADIUS DEBUG - Configuração concluída, servidor: {$this->server}");
            
            // Tentar autenticação
            error_log("RADIUS DEBUG - Iniciando autenticação PAP para usuário: $username");
            $result = $radius->accessRequest($username, $password);
            
            error_log("RADIUS RESULT - Autenticação para usuário $username: " . ($result ? "SUCESSO" : "FALHA"));
            
            return $result;
        } catch (Exception $e) {
            error_log("RADIUS ERROR - Exceção durante autenticação: " . $e->getMessage());
            error_log("RADIUS ERROR - Arquivo: " . $e->getFile() . ", Linha: " . $e->getLine());
            return false;
        }
    }
}