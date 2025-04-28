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
 * Processa o login do usuário
 */
public function login() {
    // Verificar se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Redirecionar para o formulário de login se não for POST
        header("Location: " . PUBLIC_URL . "/login");
        exit;
    }
    
    // Obter dados do formulário
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validar campos
    $errors = [];
    if (empty($username)) {
        $errors['username'] = 'O nome de usuário é obrigatório';
    }
    if (empty($password)) {
        $errors['password'] = 'A senha é obrigatória';
    }
    
    // Se houver erros, redirecionar de volta ao formulário
    if (!empty($errors)) {
        $_SESSION['error_fields'] = $errors;
        $_SESSION['flash_message'] = 'Por favor, corrija os erros no formulário';
        $_SESSION['flash_type'] = 'danger';
        header("Location: " . PUBLIC_URL . "/login");
        exit;
    }
    
    // Registrar dados de depuração
    require_once __DIR__ . '/../helpers/DebugHelper.php';
    DebugHelper::log("Tentativa de login", [
        'username' => $username,
        'POST' => $_POST
    ]);
    
    // Tentar autenticar o usuário
    require_once __DIR__ . '/../services/RadiusService.php';
    $radiusService = new RadiusService();
    
    $authenticated = $radiusService->authenticate($username, $password);
    
    if (!$authenticated) {
        $_SESSION['flash_message'] = 'Usuário ou senha inválidos';
        $_SESSION['flash_type'] = 'danger';
        header("Location: " . PUBLIC_URL . "/login");
        exit;
    }
    
    // Verificar se o usuário existe no banco de dados
    require_once __DIR__ . '/../models/User.php';
    $userModel = new User();
    $user = $userModel->findByUsername($username);
    
    if (!$user) {
        // Se o usuário não existe mas autenticou no RADIUS, criar um novo usuário
        $_SESSION['authenticated_username'] = $username;
        header("Location: " . PUBLIC_URL . "/register");
        exit;
    }
    
    // Login bem-sucedido
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name'] = $user['name'];
    
    // Verificar se há redirecionamento pendente de agenda pública
    DebugHelper::log("Verificando parâmetros de redirecionamento", $_POST);
    
    if (isset($_POST['agenda_hash']) && !empty($_POST['agenda_hash']) && 
        isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'compromissos/new') {
        
        DebugHelper::log("Redirecionamento de compromisso detectado", [
            'agenda_hash' => $_POST['agenda_hash'],
            'redirect_to' => $_POST['redirect_to']
        ]);
        
        // Buscar a agenda pelo hash
        require_once __DIR__ . '/../models/Agenda.php';
        $agendaModel = new Agenda();
        $agenda = $agendaModel->getByPublicHash($_POST['agenda_hash']);
        
        if ($agenda) {
            // Redirecionar para a tela de criação de compromisso
            $redirectUrl = PUBLIC_URL . "/compromissos/new?agenda_id=" . $agenda['id'];
            if (isset($_POST['public']) && $_POST['public']) {
                $redirectUrl .= "&public=1";
            }
            
            DebugHelper::log("Redirecionando para criar compromisso", $redirectUrl);
            header("Location: " . $redirectUrl);
            exit;
        }
    }
    
    // Verificar outros parâmetros de redirecionamento
    if (isset($_POST['redirect_url']) && !empty($_POST['redirect_url'])) {
        $redirectUrl = $_POST['redirect_url'];
        DebugHelper::log("Redirecionando para URL especificada", $redirectUrl);
        header("Location: " . $redirectUrl);
        exit;
    }
    
    // Verificar se há ID de agenda pendente
    if (isset($_POST['pending_agenda_id']) && !empty($_POST['pending_agenda_id'])) {
        $redirectUrl = PUBLIC_URL . "/compromissos/new?agenda_id=" . $_POST['pending_agenda_id'];
        if (isset($_POST['public']) && $_POST['public']) {
            $redirectUrl .= "&public=1";
        }
        
        DebugHelper::log("Redirecionando para agenda pendente", $redirectUrl);
        header("Location: " . $redirectUrl);
        exit;
    }
    
    // Redirecionamento padrão se nenhum redirecionamento específico for fornecido
    DebugHelper::log("Redirecionando para página padrão (agendas)");
    header("Location: " . PUBLIC_URL . "/agendas");
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