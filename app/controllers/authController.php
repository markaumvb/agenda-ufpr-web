<?php

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
    
/**
 * Processa o formulário de login
 */
public function login() {
    // Verificar se o formulário foi enviado
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['flash_message'] = 'Método inválido';
        $_SESSION['flash_type'] = 'danger';
        header("Location: " . PUBLIC_URL . "/login");
        exit;
    }

    // Validar dados do formulário
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $_SESSION['flash_message'] = 'Por favor, preencha todos os campos';
        $_SESSION['flash_type'] = 'danger';
        $_SESSION['error_fields'] = [];
        
        if (empty($username)) {
            $_SESSION['error_fields']['username'] = 'O campo usuário é obrigatório';
        }
        
        if (empty($password)) {
            $_SESSION['error_fields']['password'] = 'O campo senha é obrigatório';
        }
        
        header("Location: " . PUBLIC_URL . "/login");
        exit;
    }

    try {
        // Autenticar usuário
        $authenticated = false;
        $user = null;

        // Verificar se o usuário existe no sistema
        $userModel = new User();
        $user = $userModel->getByUsername($username);

        if ($user) {
            // Usuário existe, verificar se a senha está correta (para usuários internos)
            if (!empty($user['password'])) {
                if (password_verify($password, $user['password'])) {
                    $authenticated = true;
                }
            } else {
                // Usuário UFPR, autenticar via RADIUS
                $radiusService = new RadiusService();
                if ($radiusService->authenticate($username, $password)) {
                    $authenticated = true;
                }
            }
        } else if (strpos($username, '@ufpr.br') !== false) {
            // Usuário UFPR não cadastrado, autenticar via RADIUS
            $radiusService = new RadiusService();
            if ($radiusService->authenticate($username, $password)) {
                // Autenticação bem sucedida, redirecionar para formulário de registro
                $_SESSION['new_user'] = [
                    'username' => $username
                ];
                
                header("Location: " . PUBLIC_URL . "/register");
                exit;
            }
        }

        if ($authenticated && $user) {
            // Login bem sucedido, criar sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            
            // Atualizar último login
            $userModel->updateLastLogin($user['id']);
            
            // Mensagem de boas vindas
            $_SESSION['flash_message'] = 'Bem-vindo(a), ' . $user['name'] . '!';
            $_SESSION['flash_type'] = 'success';
            
            // VERIFICAR REDIRECIONAMENTO PARA CRIAÇÃO DE COMPROMISSO
            if (isset($_POST['pendingCompromissoAgendaId']) && !empty($_POST['pendingCompromissoAgendaId'])) {
                $agendaId = (int)$_POST['pendingCompromissoAgendaId'];
                
                // Construir URL de destino
                $redirectUrl = PUBLIC_URL . "/compromissos/new?agenda_id=" . $agendaId . "&public=1";
                
                // Redirecionar para a criação de compromisso
                header("Location: " . $redirectUrl);
                exit;
            }
            
            // Verificar se há redirecionamento após login (para compatibilidade)
            if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
                $redirectPath = $_POST['redirect'];
                
                // Se for caminho relativo, adicionar PUBLIC_URL
                if (strpos($redirectPath, 'http') !== 0) {
                    $redirectUrl = PUBLIC_URL . $redirectPath;
                } else {
                    $redirectUrl = $redirectPath;
                }
                
                header("Location: " . $redirectUrl);
                exit;
            }
            
            // Redirecionamento padrão
            header("Location: " . PUBLIC_URL . "/agendas");
            exit;
        } else {
            // Login falhou
            $_SESSION['flash_message'] = 'Credenciais inválidas. Por favor, tente novamente.';
            $_SESSION['flash_type'] = 'danger';
            $_SESSION['error_fields'] = [
                'username' => 'Verifique seu nome de usuário',
                'password' => 'Verifique sua senha'
            ];
            
            header("Location: " . PUBLIC_URL . "/login");
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'Erro ao processar login: ' . $e->getMessage();
        $_SESSION['flash_type'] = 'danger';
        header("Location: " . PUBLIC_URL . "/login");
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
            // Verificar se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new ValidationException(
                    'Método de requisição inválido', 
                    [], 
                    'Método de requisição inválido'
                );
            }
            
            // Verificar se há um usuário temporário
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
            
            // Verificação do email institucional não é mais necessária
            // Removido o bloco de validação de email institucional
            
            // Cadastra o usuário
            $result = $this->userModel->create($data);
            
            if (!$result) {
                // Adicione um log para depuração
                error_log('Falha ao criar usuário: ' . json_encode($data));
                
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
            
            header('Location: ' . BASE_URL );
            exit;
            
        } catch (AppException $e) {
            ExceptionHandler::handle($e);
        } catch (Exception $e) {
            error_log('Erro no registro: ' . $e->getMessage());
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