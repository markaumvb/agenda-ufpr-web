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
        $errors['username'] = 'O e-mail é obrigatório';
    } elseif (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $errors['username'] = 'Digite um e-mail válido (exemplo: usuario@ufpr.br)';
    }
    if (empty($password)) {
        $errors['password'] = 'A senha é obrigatória';
    }
    
    // Se houver erros, redirecionar de volta ao formulário com os parâmetros originais
    if (!empty($errors)) {
        $_SESSION['error_fields'] = $errors;
        $_SESSION['flash_message'] = 'Por favor, corrija os erros no formulário';
        $_SESSION['flash_type'] = 'danger';
        
        $redirectUrl = PUBLIC_URL . "/login";
        header("Location: " . $redirectUrl);
        exit;
    }
    
    // Tentar autenticar o usuário
    require_once __DIR__ . '/../services/RadiusService.php';
    $radiusService = new RadiusService();
    
    $authenticated = $radiusService->authenticate($username, $password);
    
    if (!$authenticated) {
        $_SESSION['flash_message'] = 'Usuário ou senha inválidos';
        $_SESSION['flash_type'] = 'danger';
        
        $redirectUrl = PUBLIC_URL . "/login";
        header("Location: " . $redirectUrl);
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
    
    // Verificar se há um redirecionamento pendente para criação de compromisso
    if (isset($_SESSION['redirect_to_new_compromisso'])) {
        $agendaId = $_SESSION['redirect_to_new_compromisso'];
        unset($_SESSION['redirect_to_new_compromisso']);
        
        header("Location: " . PUBLIC_URL . "/compromissos/new?agenda_id=" . $agendaId . "&public=1");
        exit;
    }
    
    // Redirecionamento padrão
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
        if (!isset($_SESSION['authenticated_username'])) {
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        }
        
        $username = $_SESSION['authenticated_username'];
        
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
        if (!isset($_SESSION['authenticated_username'])) {
            header('Location: ' . PUBLIC_URL . '/login');
            exit;
        }
        
        $username = $_SESSION['authenticated_username'];
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
        
        // CORREÇÃO: Formatar e-mail corretamente
        $email = $this->formatUserEmail($username);
        
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
        unset($_SESSION['authenticated_username']);
        
        // Mensagem de sucesso
        $_SESSION['flash_message'] = 'Cadastro realizado com sucesso!';
        $_SESSION['flash_type'] = 'success';
        
        // Redirecionar para a página de agendas
        header('Location: ' . PUBLIC_URL . '/agendas');
        exit;
    }
    
    /**
     * Formata o e-mail do usuário corretamente
     * 
     * @param string $username Nome de usuário
     * @return string E-mail formatado
     */
    private function formatUserEmail($username) {
        // Se já contém @, usar como está
        if (strpos($username, '@') !== false) {
            return $username;
        }
        
        // Se não contém @, adicionar @ufpr.br
        return $username . '@ufpr.br';
    }
    
    /**
     * Redireciona o usuário após o login quando vindo de uma página específica
     */
    public function redirectFromLogin() {
        // Verificação de segurança - usuário deve estar logado
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . PUBLIC_URL . "/login");
            exit;
        }
        
        DebugHelper::log("Função redirectFromLogin chamada", $_GET);
        
        if (isset($_GET['agenda_hash']) && !empty($_GET['agenda_hash'])) {
            // Buscar agenda pelo hash
            require_once __DIR__ . '/../models/Agenda.php';
            $agendaModel = new Agenda();
            $agenda = $agendaModel->getByPublicHash($_GET['agenda_hash']);
            
            if ($agenda) {
                $redirectUrl = PUBLIC_URL . "/compromissos/new?agenda_id=" . $agenda['id'];
                
                if (isset($_GET['public']) && $_GET['public'] == '1') {
                    $redirectUrl .= "&public=1";
                }
                
                DebugHelper::log("Redirecionando para", $redirectUrl);
                header("Location: " . $redirectUrl);
                exit;
            }
        }
        
        // Redirecionamento padrão
        header("Location: " . PUBLIC_URL . "/agendas");
        exit;
    }
}