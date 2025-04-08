<?php
// Arquivo: app/controllers/AuthController.php

/**
 * Controlador para autenticação e registro de usuários
 * Versão aprimorada com tratamento de exceções e validação
 */
class AuthController {
    private $radiusService;
    private $userModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Carregar serviço de autenticação RADIUS
        require_once __DIR__ . '/../services/RadiusService.php';
        $this->radiusService = new RadiusService();
        
        // Carregar modelo de usuário
        require_once __DIR__ . '/../models/Database.php';
        require_once __DIR__ . '/../models/User.php';
        $this->userModel = new User();
        
        // Carregar classes de validação e tratamento de exceções
        require_once __DIR__ . '/../helpers/Validator.php';
        require_once __DIR__ . '/../helpers/ExceptionHandler.php';
    }
    
    /**
     * Exibe o formulário de login
     */
    public function showLoginForm() {
        try {
            // Verifica se usuário já está logado
            if (isset($_SESSION['user_id'])) {
                header('Location: ' . BASE_URL  );
                exit;
            }
            
            require_once __DIR__ . '/../views/shared/header.php';
            require_once __DIR__ . '/../views/auth/login.php';
            require_once __DIR__ . '/../views/shared/footer.php';
        } catch (Exception $e) {
            ExceptionHandler::handle($e);
        }
    }
    
    public function login() {
        try {
            // Verificar se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new ValidationException(
                    'Método de requisição inválido', 
                    [], 
                    'Método de requisição inválido'
                );
            }
            
            // Obter dados do formulário
            $username = htmlspecialchars(filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW) ?? '');
            $password = $_POST['password'] ?? ''; // Não sanitizar a senha
            
            // Validação básica
            if (empty($username) || empty($password)) {
                $_SESSION['flash_message'] = 'Usuário e senha são obrigatórios';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            
            // Tentar autenticar via RADIUS
            $radiusService = new RadiusService();
            $authenticated = $radiusService->authenticate($username, $password);
            
            if (!$authenticated) {
                $_SESSION['flash_message'] = 'Credenciais inválidas. Por favor, verifique seu usuário e senha.';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            
            // A partir daqui a autenticação foi bem sucedida
            // Verificar se o usuário já existe no sistema
            $user = $this->userModel->findByUsername($username);
            
            if (!$user) {
                // Primeiro acesso do usuário - redirecionar para completar cadastro
                $_SESSION['temp_username'] = $username;
                $_SESSION['flash_message'] = 'Primeiro acesso detectado. Por favor, complete seu cadastro.';
                $_SESSION['flash_type'] = 'success';
                
                header('Location: ' . BASE_URL . '/register');
                exit;
            }
            
            // Login bem-sucedido - criar sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            
            // Atualizar última data de login (opcional)
            $this->userModel->updateLastLogin($user['id']);
            
            $_SESSION['flash_message'] = 'Login realizado com sucesso! Bem-vindo(a), ' . $user['name'] . '.';
            $_SESSION['flash_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/public');
            exit;
                
        } catch (Exception $e) {
            // Log do erro
            error_log('Erro na autenticação: ' . $e->getMessage());
            
            // Mensagem amigável para o usuário
            $_SESSION['flash_message'] = 'Erro na autenticação. Por favor, tente novamente.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
    
    /**
     * Exibe o formulário de registro (primeiro acesso)
     */
    public function showRegisterForm() {
        try {
            // Verifica se há um usuário temporário (autenticado no RADIUS mas não cadastrado no sistema)
            if (!isset($_SESSION['temp_username'])) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            
            $username = $_SESSION['temp_username'];
            
            require_once __DIR__ . '/../views/shared/header.php';
            require_once __DIR__ . '/../views/auth/register.php';
            require_once __DIR__ . '/../views/shared/footer.php';
        } catch (Exception $e) {
            ExceptionHandler::handle($e);
        }
    }
    
    /**
     * Processa o registro do usuário (primeiro acesso)
     */
    public function register() {
        try {
            // Verifica se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new ValidationException(
                    'Método de requisição inválido', 
                    [], 
                    'Método de requisição inválido'
                );
            }
            
            // Verifica se há um usuário temporário
            if (!isset($_SESSION['temp_username'])) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            
            $username = $_SESSION['temp_username'];
            
            // Obter dados do formulário
            $data = [
                'username' => $username,
                'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
                'email' => $username . '@mail.ufpr.br' // Gera o email com base no username
            ];
            
            // Guardar dados do formulário em caso de erro
            $_SESSION['form_data'] = $data;
            
            // Validar os campos
            $rules = [
                'name' => 'required|min:3|max:100',
            ];
            
            $validator = new Validator($data, $rules);
            
            if (!$validator->validate()) {
                $_SESSION['validation_errors'] = $validator->getFirstErrors();
                $_SESSION['error_fields'] = $validator->getFirstErrors();
                
                header('Location: ' . BASE_URL . '/register');
                exit;
            }
            
            
            // Verificar se e-mail já está em uso
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser) {
                throw new ValidationException(
                    'E-mail já em uso', 
                    ['email' => 'Este e-mail já está em uso por outro usuário.'],
                    'Este e-mail já está em uso por outro usuário.'
                );
            }
            
            // Cadastra o usuário
            $result = $this->userModel->create($data);
            
            if (!$result) {
                throw new DatabaseException(
                    'Erro ao cadastrar usuário', 
                    'Erro ao cadastrar usuário. Por favor, tente novamente mais tarde.'
                );
            }
            
            // Limpa o usuário temporário e dados de formulário
            unset($_SESSION['temp_username']);
            unset($_SESSION['form_data']);
            
            // Busca o usuário recém-cadastrado
            $user = $this->userModel->findByUsername($username);
            
            // Cria a sessão do usuário
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            
            $_SESSION['flash_message'] = 'Cadastro realizado com sucesso! Bem-vindo(a) ao Sistema de Agendamento UFPR.';
            $_SESSION['flash_type'] = 'success';
            
            header('Location: ' . BASE_URL . '/public');
            exit;
            
        } catch (AppException $e) {
            ExceptionHandler::handle($e);
        } catch (Exception $e) {
            ExceptionHandler::handle($e);
        }
    }
    
    /**
     * Realiza o logout do usuário
     */
    public function logout() {
        try {
            // Destruir todos os dados da sessão
            session_unset();
            session_destroy();
            
            // Iniciar nova sessão para mensagem flash
            session_start();
            
            $_SESSION['flash_message'] = 'Logout realizado com sucesso!';
            $_SESSION['flash_type'] = 'success';
            
            // Redirecionar para a página de login
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        } catch (Exception $e) {
            ExceptionHandler::handle($e);
        }
    }
}