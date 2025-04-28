<?php

class AuthController {
    private $userModel;
    
    public function __construct() {
        require_once __DIR__ . '/../models/User.php';
        $this->userModel = new User();
    }
    
    /**
     * Exibe o formulário de login
     */
    public function showLoginForm() {
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/auth/login.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Processa a tentativa de login
     */
    public function login() {
        // Verificar se é um POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        }
        
        // Validar credenciais
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Verificar campos obrigatórios
        $errors = [];
        if (empty($username)) {
            $errors['username'] = 'O nome de usuário é obrigatório';
        }
        if (empty($password)) {
            $errors['password'] = 'A senha é obrigatória';
        }
        
        // Se houver erros, voltar para o formulário
        if (!empty($errors)) {
            $_SESSION['error_fields'] = $errors;
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        }
        
        // Verificar login via RADIUS
        require_once __DIR__ . '/../services/RadiusService.php';
        $radiusService = new RadiusService();
        
        $authenticated = $radiusService->authenticate($username, $password);
        
        if (!$authenticated) {
            $_SESSION['validation_errors'] = ['Credenciais inválidas. Por favor, tente novamente.'];
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        }
        
        // Verificar se o usuário existe no sistema
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            // Redirecionar para registro (primeiro acesso)
            $_SESSION['username'] = $username;
            header('Location: ' . PUBLIC_URL . '/register');
            exit;
        }
        
        // Login bem-sucedido, armazenar dados na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        
        // Verificar se há redirecionamento após login
        if (isset($_POST['redirect_url']) && !empty($_POST['redirect_url'])) {
            $redirectUrl = $_POST['redirect_url'];
            
            // Verificação de segurança para evitar redirecionamento aberto
            if (strpos($redirectUrl, PUBLIC_URL) === 0) {
                header('Location: ' . $redirectUrl);
                exit;
            }
        }
        
        // Verificar se há um ID de agenda pendente
        if (isset($_POST['pending_agenda_id']) && !empty($_POST['pending_agenda_id'])) {
            $agendaId = (int)$_POST['pending_agenda_id'];
            header('Location: ' . PUBLIC_URL . '/compromissos/new?agenda_id=' . $agendaId . '&public=1');
            exit;
        }
        
        // Redirecionamento padrão
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }
    
    /**
     * Processa o logout
     */
    public function logout() {
        // Destruir a sessão
        session_destroy();
        
        // Redirecionar para a página inicial
        header('Location: ' . PUBLIC_URL . '/');
        exit;
    }
    
    /**
     * Exibe o formulário de registro (primeiro acesso)
     */
    public function showRegisterForm() {
        // Verificar se há um nome de usuário na sessão
        if (!isset($_SESSION['username'])) {
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        }
        
        $username = $_SESSION['username'];
        
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/auth/register.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Processa o registro do usuário
     */
    public function register() {
        // Verificar se é um POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '/register');
            exit;
        }
        
        // Verificar se há um nome de usuário na sessão
        if (!isset($_SESSION['username'])) {
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        }
        
        $username = $_SESSION['username'];
        $name = trim($_POST['name']);
        
        // Validar campos
        $errors = [];
        if (empty($name)) {
            $errors['name'] = 'O nome completo é obrigatório';
        }
        
        // Se houver erros, voltar para o formulário
        if (!empty($errors)) {
            $_SESSION['error_fields'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . PUBLIC_URL . '/register');
            exit;
        }
        
        // Obter e-mail a partir do nome de usuário (padrão UFPR)
        $email = $username;
        if (strpos($email, '@') === false) {
            $email .= '@ufpr.br';
        }
        
        // Criar o usuário
        $userData = [
            'username' => $username,
            'name' => $name,
            'email' => $email
        ];
        
        $userId = $this->userModel->create($userData);
        
        if (!$userId) {
            $_SESSION['validation_errors'] = ['Erro ao criar usuário. Por favor, tente novamente.'];
            header('Location: ' . PUBLIC_URL . '/register');
            exit;
        }
        
        // Login automático após registro
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['name'] = $name;
        
        // Limpar dados temporários da sessão
        unset($_SESSION['username']);
        
        // Mensagem de sucesso
        $_SESSION['flash_message'] = 'Cadastro realizado com sucesso!';
        $_SESSION['flash_type'] = 'success';
        
        // Redirecionar para a página de agendas
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }
    
    /**
     * Redireciona o usuário após o login quando vindo de uma página específica
     */
    public function redirectFromLogin() {
        // Verificar se o usuário está logado
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        }
        
        // Verificar parâmetros
        $hash = isset($_GET['hash']) ? $_GET['hash'] : null;
        $agendaId = isset($_GET['agenda_id']) ? (int)$_GET['agenda_id'] : null;
        
        if ($hash) {
            // Redirecionar para a agenda pública
            header('Location: ' . PUBLIC_URL . '/public-agenda/' . $hash);
            exit;
        } else if ($agendaId) {
            // Redirecionar para criar compromisso
            header('Location: ' . PUBLIC_URL . '/compromissos/new?agenda_id=' . $agendaId . '&public=1');
            exit;
        }
        
        // Redirecionamento padrão
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }
}