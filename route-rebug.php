<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Depuração Detalhada de Rotas</h1>";

// 1. Verificar ambiente básico
echo "<h2>1. Informações do Servidor</h2>";
echo "<pre>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "</pre>";

// 2. Verificar constantes
echo "<h2>2. Verificação de Constantes</h2>";
echo "<pre>";
if (file_exists(__DIR__ . '/app/config/constants.php')) {
    echo "Arquivo constants.php encontrado.\n";
    require_once __DIR__ . '/app/config/constants.php';
    
    echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'Não definido') . "\n";
    echo "PUBLIC_URL: " . (defined('PUBLIC_URL') ? PUBLIC_URL : 'Não definido') . "\n";
} else {
    echo "ERRO: Arquivo constants.php não encontrado!\n";
    // Listar arquivos do diretório config
    echo "Arquivos em /app/config/:\n";
    if (is_dir(__DIR__ . '/app/config')) {
        $files = scandir(__DIR__ . '/app/config');
        print_r($files);
    } else {
        echo "Diretório /app/config/ não encontrado!\n";
    }
}
echo "</pre>";

// 3. Verificar estrutura de arquivos
echo "<h2>3. Verificação de Estrutura de Arquivos</h2>";
echo "<pre>";

function check_file($path) {
    if (file_exists($path)) {
        echo "$path - ✓ EXISTE\n";
        return true;
    } else {
        echo "$path - ✗ NÃO EXISTE\n";
        return false;
    }
}

// Verificar arquivos essenciais
echo "Verificando arquivos essenciais:\n";
check_file(__DIR__ . '/index.php');
check_file(__DIR__ . '/app/controllers/AuthController.php');
check_file(__DIR__ . '/app/models/User.php');
check_file(__DIR__ . '/app/views/auth/login.php');
check_file(__DIR__ . '/.htaccess');

// Verificar permissões
echo "\nVerificando permissões:\n";
$files_to_check = [
    __DIR__ . '/index.php',
    __DIR__ . '/app/controllers/AuthController.php',
    __DIR__ . '/app/config/constants.php',
    __DIR__ . '/.htaccess'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $owner = posix_getpwuid(fileowner($file));
        $group = posix_getgrgid(filegroup($file));
        printf("%s - Permissões: %o, Dono: %s, Grupo: %s\n", 
               $file, $perms & 0777, $owner['name'], $group['name']);
    }
}
echo "</pre>";

// 4. Testar carregamento de classes
echo "<h2>4. Teste de Carregamento de Classes</h2>";
echo "<pre>";

function test_class_load($className, $filePath) {
    echo "Testando carregamento de $className...\n";
    $fileExists = check_file($filePath);
    
    if ($fileExists) {
        // Verificar conteúdo do arquivo
        $content = file_get_contents($filePath);
        if (empty($content)) {
            echo "AVISO: O arquivo $filePath existe, mas está vazio!\n";
            return false;
        }
        
        // Verificar se a classe existe nele
        if (strpos($content, "class $className") === false) {
            echo "AVISO: O arquivo $filePath não contém a classe $className!\n";
            return false;
        }
        
        try {
            require_once $filePath;
            if (class_exists($className)) {
                echo "Classe $className carregada com sucesso!\n";
                return true;
            } else {
                echo "ERRO: Classe $className não encontrada após carregamento do arquivo!\n";
                return false;
            }
        } catch (Throwable $e) {
            echo "ERRO ao carregar $className: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    return false;
}

test_class_load('AuthController', __DIR__ . '/app/controllers/AuthController.php');
test_class_load('User', __DIR__ . '/app/models/User.php');
test_class_load('Database', __DIR__ . '/app/models/Database.php');

echo "</pre>";

// 5. Simular processamento da rota de login
echo "<h2>5. Simulação da Rota de Login</h2>";
echo "<pre>";

// Simular processamento da rota
echo "Simulando processamento da rota '/login':\n";

// Extrair URI similar ao sistema real
$requestUri = "/agenda_ufpr/login"; // Simula acesso a /login
$pattern = '/\/agenda_ufpr\/?(.*)$/';
preg_match($pattern, $requestUri, $matches);
$uri = isset($matches[1]) ? '/' . $matches[1] : '/';

echo "URI Extraída: '$uri'\n";

// Definir rota de login
$loginRoute = [
    'controller' => 'AuthController',
    'action' => 'showLoginForm',
    'method' => 'GET'
];

echo "Controlador esperado: {$loginRoute['controller']}\n";
echo "Método esperado: {$loginRoute['action']}\n";

// Verificar se o controlador existe
if (class_exists('AuthController')) {
    echo "Controlador AuthController encontrado.\n";
    
    // Verificar se o método existe
    $controller = new AuthController();
    if (method_exists($controller, 'showLoginForm')) {
        echo "Método showLoginForm encontrado.\n";
        echo "A rota de login parece estar configurada corretamente!\n";
    } else {
        echo "ERRO: Método showLoginForm não encontrado no controlador AuthController!\n";
        
        // Mostrar métodos disponíveis
        echo "Métodos disponíveis em AuthController:\n";
        $methods = get_class_methods($controller);
        print_r($methods);
    }
} else {
    echo "ERRO: Classe AuthController não encontrada ou não foi carregada corretamente!\n";
}

echo "</pre>";

// 6. Inspecionar arquivo .htaccess
echo "<h2>6. Verificação do arquivo .htaccess</h2>";
echo "<pre>";

if (file_exists(__DIR__ . '/.htaccess')) {
    echo "Conteúdo do arquivo .htaccess:\n";
    echo htmlspecialchars(file_get_contents(__DIR__ . '/.htaccess')) . "\n";
} else {
    echo "ERRO: Arquivo .htaccess não encontrado na raiz!\n";
}

echo "</pre>";

// 7. Links para testes
echo "<h2>7. Links para Testes</h2>";
if (defined('BASE_URL')) {
    echo "<a href='" . BASE_URL . "/login'>Tentar página de login</a><br>";
    echo "<a href='" . BASE_URL . "/index.php'>Página inicial</a><br>";
} else {
    echo "<p>BASE_URL não está definido. Não é possível gerar links.</p>";
}