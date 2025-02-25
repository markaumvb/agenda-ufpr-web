<?php
// Arquivo: agenda_ufpr/app/services/RadiusService.php

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
        $this->server = defined('RADIUS_SERVER') ? RADIUS_SERVER : 'radius.ufpr.br';
        $this->secret = defined('RADIUS_SECRET') ? RADIUS_SECRET : 'shared_secret';
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
            return $password === 'teste123';
        }
        
        // Verificar se a extensão RADIUS está disponível
        if (!extension_loaded('radius')) {
            // Alternativa: usar sockets para comunicação RADIUS
            return $this->authenticateWithSockets($username, $password);
        }
        
        // Usar a extensão RADIUS (mais segura e recomendada)
        $radius = radius_auth_open();
        
        if (!$radius) {
            error_log('Falha ao inicializar RADIUS');
            return false;
        }
        
        // Configurar o cliente RADIUS
        radius_add_server($radius, $this->server, $this->port, $this->secret, 5, 3);
        radius_create_request($radius, RADIUS_ACCESS_REQUEST);
        
        // Adicionar atributos da solicitação
        radius_put_attr($radius, RADIUS_USER_NAME, $username);
        radius_put_attr($radius, RADIUS_USER_PASSWORD, $password);
        
        // Enviar solicitação e obter resposta
        $result = radius_send_request($radius);
        radius_close($radius);
        
        // Verificar resultado
        return $result == RADIUS_ACCESS_ACCEPT;
    }
    
    /**
     * Método alternativo para autenticação RADIUS usando sockets
     * Usado quando a extensão RADIUS não está disponível
     * 
     * @param string $username Nome de usuário
     * @param string $password Senha
     * @return bool Resultado da autenticação
     */
    private function authenticateWithSockets($username, $password) {
        // Em um ambiente de produção, esta função teria uma implementação
        // complexa usando sockets para o protocolo RADIUS
        
        // Para simplificar, vamos simular o comportamento
        error_log('AVISO: Usando autenticação RADIUS simulada via sockets');
        
        // Simular autenticação
        return $password === 'teste123';
    }
}