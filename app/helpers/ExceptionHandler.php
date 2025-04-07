<?php
// app/helpers/ExceptionHandler.php

/**
 * Exceção base para o sistema de agendamento
 */
class AppException extends Exception {
    protected $userMessage;
    
    /**
     * Construtor
     * 
     * @param string $message Mensagem técnica
     * @param string $userMessage Mensagem amigável para o usuário
     * @param int $code Código de erro
     * @param Throwable $previous Exceção anterior
     */
    public function __construct($message, $userMessage = '', $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->userMessage = $userMessage ?: $message;
    }
    
    /**
     * Retorna a mensagem para o usuário
     * 
     * @return string Mensagem para o usuário
     */
    public function getUserMessage() {
        return $this->userMessage;
    }
}

/**
 * Exceção para autenticação ou autorização
 */
class AuthException extends AppException {
    /**
     * Construtor
     * 
     * @param string $message Mensagem técnica
     * @param string $userMessage Mensagem amigável para o usuário
     * @param int $code Código de erro
     */
    public function __construct($message, $userMessage = '', $code = 403) {
        if (empty($userMessage)) {
            $userMessage = 'Você não tem permissão para acessar este recurso.';
        }
        parent::__construct($message, $userMessage, $code);
    }
}

/**
 * Exceção para recursos não encontrados
 */
class NotFoundException extends AppException {
    /**
     * Construtor
     * 
     * @param string $message Mensagem técnica
     * @param string $userMessage Mensagem amigável para o usuário
     * @param int $code Código de erro
     */
    public function __construct($message, $userMessage = '', $code = 404) {
        if (empty($userMessage)) {
            $userMessage = 'O recurso solicitado não foi encontrado.';
        }
        parent::__construct($message, $userMessage, $code);
    }
}

/**
 * Exceção para validação de dados
 */
class ValidationException extends AppException {
    protected $errors = [];
    
    /**
     * Construtor
     * 
     * @param string $message Mensagem técnica
     * @param array $errors Erros de validação
     * @param string $userMessage Mensagem amigável para o usuário
     * @param int $code Código de erro
     */
    public function __construct($message, $errors = [], $userMessage = '', $code = 422) {
        if (empty($userMessage)) {
            $userMessage = 'Por favor, verifique os dados informados.';
        }
        parent::__construct($message, $userMessage, $code);
        $this->errors = $errors;
    }
    
    /**
     * Retorna os erros de validação
     * 
     * @return array Erros de validação
     */
    public function getErrors() {
        return $this->errors;
    }
}

/**
 * Exceção para erros de banco de dados
 */
class DatabaseException extends AppException {
    /**
     * Construtor
     * 
     * @param string $message Mensagem técnica
     * @param string $userMessage Mensagem amigável para o usuário
     * @param int $code Código de erro
     */
    public function __construct($message, $userMessage = '', $code = 500) {
        if (empty($userMessage)) {
            $userMessage = 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.';
        }
        parent::__construct($message, $userMessage, $code);
    }
}

/**
 * Classe para tratamento centralizado de exceções
 */
class ExceptionHandler {
    /**
     * Trata uma exceção e realiza as ações apropriadas
     * 
     * @param Throwable $exception A exceção a ser tratada
     * @param bool $returnJson Se deve retornar resposta em JSON
     */
    public static function handle($exception, $returnJson = false) {
        // Registrar erro no log
        self::logException($exception);
        
        // Se for uma exceção da aplicação, extrair mensagem para o usuário
        if ($exception instanceof AppException) {
            $userMessage = $exception->getUserMessage();
            $statusCode = $exception->getCode() ?: 500;
            
            // Extrair erros de validação, se for o caso
            $errors = ($exception instanceof ValidationException) ? $exception->getErrors() : [];
        } else {
            // Se for uma exceção genérica
            $userMessage = 'Ocorreu um erro inesperado. Por favor, tente novamente.';
            $statusCode = 500;
            $errors = [];
        }
        
        // Se for para retornar JSON (para requisições AJAX)
        if ($returnJson) {
            self::outputJsonResponse($statusCode, $userMessage, $errors);
            return;
        }
        
        // Salvar mensagem de erro na sessão e redirecionar
        $_SESSION['flash_message'] = $userMessage;
        $_SESSION['flash_type'] = 'danger';
        
        // Se for um erro de validação, também salvar os erros detalhados
        if (!empty($errors)) {
            $_SESSION['validation_errors'] = $errors;
        }
        
        // Redirecionar com base no tipo de erro
        self::redirect($exception, $statusCode);
    }
    
    /**
     * Registra a exceção no log do sistema
     * 
     * @param Throwable $exception A exceção a ser registrada
     */
    private static function logException($exception) {
        // Formatar mensagem detalhada
        $message = sprintf(
            "Exceção: %s\nMensagem: %s\nArquivo: %s\nLinha: %d\nStack trace:\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        // Registrar no log
        error_log($message);
    }
    
    /**
     * Gera resposta JSON para a exceção
     * 
     * @param int $statusCode Código de status HTTP
     * @param string $message Mensagem de erro para o usuário
     * @param array $errors Erros detalhados (opcional)
     */
    private static function outputJsonResponse($statusCode, $message, $errors = []) {
        // Definir cabeçalhos HTTP
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        // Construir resposta
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        // Enviar resposta
        echo json_encode($response);
        exit;
    }
    
    /**
     * Redireciona o usuário com base no tipo de exceção
     * 
     * @param Throwable $exception A exceção ocorrida
     * @param int $statusCode Código de status HTTP
     */
    private static function redirect($exception, $statusCode) {
        // Definir URL padrão para redirecionamento
        $redirectUrl = PUBLIC_URL;
        
        // Personalizar redirecionamento com base no tipo de exceção
        if ($exception instanceof AuthException) {
            // Se for erro de autorização, redirecionar para login
            $redirectUrl = PUBLIC_URL . '/login';
        } elseif ($exception instanceof NotFoundException) {
            // Se for recurso não encontrado, redirecionar para página inicial
            $redirectUrl = PUBLIC_URL;
        } elseif (isset($_SERVER['HTTP_REFERER'])) {
            // Se tiver uma referência, voltar para a página anterior
            $redirectUrl = $_SERVER['HTTP_REFERER'];
        }
        
        // Redirecionar
        header("Location: $redirectUrl");
        exit;
    }
}