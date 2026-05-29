<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
// config.php
// Iniciar sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// FORÇAR VALIDAÇÃO DA SESSÃO - Verificar se os dados são válidos
if (isset($_SESSION['usuario_id']) && isset($_SESSION['perfil'])) {
    // Se a sessão for muito antiga (mais de 8 horas), destruir
    if (isset($_SESSION['ultima_atividade']) && (time() - $_SESSION['ultima_atividade'] > 28800)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['ultima_atividade'] = time();
}

$host = $_ENV['MYSQLHOST'] ?? 'localhost';
$dbname = $_ENV['MYSQLDATABASE'] ?? 'silab';
$username = $_ENV['MYSQLUSER'] ?? 'root';
$password = $_ENV['MYSQLPASSWORD'] ?? '';
$port = $_ENV['MYSQLPORT'] ?? '3306';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Função para garantir que o root existe
function garantirRoot($pdo) {
    $stmt = $pdo->prepare("SELECT id FROM usuario WHERE perfil = 'root'");
    $stmt->execute();
    $root = $stmt->fetch();
    
    if (!$root) {
        $senha_hash = password_hash('Root@00', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuario (nome_completo, matricula, email, senha_hash, perfil, status) 
                               VALUES ('Root Administrator', '20251SI0000', 'root@silab.com', ?, 'root', 'aprovado')");
        $stmt->execute([$senha_hash]);
        
        // Buscar o root_id criado
        $stmt = $pdo->prepare("SELECT id FROM usuario WHERE perfil = 'root'");
        $stmt->execute();
        $root = $stmt->fetch();
        
        // Verificar se existem laboratórios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM laboratorio");
        $total = $stmt->fetch()['total'];
        
        if ($total == 0 && $root) {
            // Criar laboratórios padrão
            $stmt = $pdo->prepare("INSERT INTO laboratorio (nome, capacidade, criado_por) VALUES 
                                   ('Lab 24', 30, ?),
                                   ('Lab 25', 30, ?),
                                   ('Lab 27', 30, ?)");
            $stmt->execute([$root['id'], $root['id'], $root['id']]);
        }
    }
    
    return $root['id'] ?? null;
}

// Garantir root existe
garantirRoot($pdo);

// Função para verificar se usuário está logado (NÃO chama session_start novamente)
function verificarLogin() {
    // Verificar se os dados da sessão existem
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['perfil'])) {
        header('Location: login.php');
        exit();
    }
    
    // Verificar se o perfil é root
    if ($_SESSION['perfil'] !== 'root') {
        header('Location: login.php');
        exit();
    }
    
    return true;
}

// Função para verificar se NÃO está logado (para páginas de login)
function verificarNaoLogado() {
    if (isset($_SESSION['usuario_id']) && isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'root') {
        header('Location: index.php');
        exit();
    }
}

// Função para registrar logs
function registrarLog($pdo, $usuario_id, $acao, $entidade, $entidade_id, $detalhes = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO log_sistema (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $acao, $entidade, $entidade_id, $detalhes, $ip]);
}
?>