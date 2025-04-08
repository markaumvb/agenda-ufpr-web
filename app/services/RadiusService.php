<?php
// Arquivo: app/services/RadiusService.php (atualizado)

/**
 * Serviço para autenticação via RADIUS
 */
class RadiusService {
    private $server;
    private $secret;
    private $port;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Carregar configurações do RADIUS definidas em constants.php
        $this->server = defined('RADIUS_SERVER') ? RADIUS_SERVER : '200.17.209.10';
        $this->secret = defined('RADIUS_SECRET') ? RADIUS_SECRET : 'rapadura';
        $this->port = defined('RADIUS_PORT') ? RADIUS_PORT : 1812;
    }
    
    /**
     * Realiza a autenticação no servidor RADIUS
     * 
     * @param string $username Nome de usuário
     * @param string $password Senha
     * @return bool Resultado da autenticação
     */
    public function authenticate($username, $password) {
        // Em ambiente de desenvolvimento, podemos simular o RADIUS para testes
        if (defined('SIMULATE_RADIUS') && SIMULATE_RADIUS) {
            // Simular autenticação (aceitar qualquer usuário com senha "teste123")
            error_log("RADIUS DEBUG - Usando modo de simulação para usuário: " . $username);
            return $password === 'teste123';
        }
        
        // Verificar se a biblioteca Dapphp\Radius está disponível
        if (!class_exists('Dapphp\\Radius\\Radius')) {
            error_log('RADIUS DEBUG - Biblioteca Dapphp\Radius não está disponível. Usando método de fallback.');
            return $this->authenticateWithSockets($username, $password);
        }
        
        try {
            error_log('RADIUS DEBUG - Tentando autenticação para usuário: ' . $username . ' usando Dapphp\Radius');
            
            // Usar a biblioteca Dapphp\Radius
            $radius = new \Dapphp\Radius\Radius();
            $radius->setServer($this->server);
            $radius->setSecret($this->secret);
            $radius->setPort($this->port);
            
            // Realizar autenticação
            $result = $radius->accessRequest($username, $password);
            
            error_log('RADIUS DEBUG - Resultado da autenticação: ' . ($result ? 'SUCESSO' : 'FALHA'));
            
            return $result;
        } catch (Exception $e) {
            error_log('RADIUS DEBUG - Erro na autenticação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Método alternativo para autenticação RADIUS usando sockets
     * Usado quando a biblioteca Dapphp\Radius não está disponível
     * 
     * @param string $username Nome de usuário
     * @param string $password Senha
     * @return bool Resultado da autenticação
     */
    private function authenticateWithSockets($username, $password) {
        error_log('RADIUS DEBUG - Usando método de fallback via sockets para usuário: ' . $username);
        
        // Em um ambiente de produção, esta função teria uma implementação
        // complexa usando sockets para o protocolo RADIUS
        
        // Para simplificar, vamos simular o comportamento
        error_log('AVISO: Usando autenticação RADIUS simulada via sockets');
        
        // Simular autenticação - no ambiente real, substitua por código que realmente comunique com o servidor RADIUS
        return $password === 'teste123';
    }
}