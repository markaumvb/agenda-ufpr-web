<?php
// Arquivo: agenda_ufpr/app/controllers/AuthController.php

/**
 * Controlador para autenticação e registro de usuários
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
    }
    
    /**
     * Exibe o formulário de login
     */
    public function showLoginForm() {
        // Verifica se usuário já está logado
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/auth/login.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Processa o login do usuário
     */
    public function login() {
      // Verifica se é uma requisição POST
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          header('Location: ' . BASE_URL . '/public/login');
          exit;
      }
      
      // Obtém os dados do formulário
      $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
      $password = $_POST['password']; // Não sanitizar a senha para não alterar caracteres especiais
      
      // Valida os campos
      if (empty($username) || empty($password)) {
          $_SESSION['flash_message'] = 'Todos os campos são obrigatórios';
          $_SESSION['flash_type'] = 'danger';
          header('Location: ' . BASE_URL . '/public/login');
          exit;
      }
      
      // Tenta autenticar no RADIUS
      $authenticated = $this->radiusService->authenticate($username, $password);
      
      if (!$authenticated) {
          $_SESSION['flash_message'] = 'Credenciais inválidas';
          $_SESSION['flash_type'] = 'danger';
          header('Location: ' . BASE_URL . '/public/login');
          exit;
      }
      
      // Verifica se o usuário já existe no sistema
      $user = $this->userModel->findByUsername($username);
      
      if (!$user) {
          // Primeiro acesso do usuário - redirecionar para completar cadastro
          $_SESSION['temp_username'] = $username;
          $_SESSION['flash_message'] = 'Primeiro acesso detectado. Por favor, complete seu cadastro.';
          $_SESSION['flash_type'] = 'success';
          header('Location: ' . BASE_URL . '/public/register');
          exit;
      }
      
      // Login bem-sucedido - criar sessão
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['user_name'] = $user['name'];
      
      $_SESSION['flash_message'] = 'Login realizado com sucesso';
      $_SESSION['flash_type'] = 'success';
      header('Location: ' . BASE_URL . '/public');
      exit;
  }
    
    /**
     * Exibe o formulário de registro (primeiro acesso)
     */
    public function showRegisterForm() {
        // Verifica se há um usuário temporário (autenticado no RADIUS mas não cadastrado no sistema)
        if (!isset($_SESSION['temp_username'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $username = $_SESSION['temp_username'];
        
        require_once __DIR__ . '/../views/shared/header.php';
        require_once __DIR__ . '/../views/auth/register.php';
        require_once __DIR__ . '/../views/shared/footer.php';
    }
    
    /**
     * Processa o registro do usuário (primeiro acesso)
     */
    public function register() {
        // Verifica se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/register');
            exit;
        }
        
        // Verifica se há um usuário temporário
        if (!isset($_SESSION['temp_username'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $username = $_SESSION['temp_username'];
        
        // Obtém os dados do formulário
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        // Valida os campos
        if (empty($name) || empty($email)) {
            $_SESSION['flash_message'] = 'Todos os campos são obrigatórios';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/register');
            exit;
        }
        
        // Valida o e-mail
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_message'] = 'E-mail inválido';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/register');
            exit;
        }
        
        // Cadastra o usuário
        $userData = [
            'username' => $username,
            'name' => $name,
            'email' => $email
        ];
        
        $result = $this->userModel->create($userData);
        
        if (!$result) {
            $_SESSION['flash_message'] = 'Erro ao cadastrar usuário';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/register');
            exit;
        }
        
        // Limpa o usuário temporário
        unset($_SESSION['temp_username']);
        
        // Busca o usuário recém-cadastrado
        $user = $this->userModel->findByUsername($username);
        
        // Cria a sessão do usuário
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['name'];
        
        $_SESSION['flash_message'] = 'Cadastro realizado com sucesso';
        $_SESSION['flash_type'] = 'success';
        header('Location: ' . BASE_URL);
        exit;
    }
    
    /**
     * Realiza o logout do usuário
     */
    public function logout() {
        // Destruir todos os dados da sessão
        session_start();
        session_unset();
        session_destroy();
        
        // Redirecionar para a página inicial
        header('Location: ' . PUBLIC_URL . '/login');
        exit;
    }
}