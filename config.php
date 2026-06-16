<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id']) && isset($_SESSION['perfil'])) {
    // Se a sessão for muito antiga (mais de 8 horas), destruir
    if (isset($_SESSION['ultima_atividade']) && (time() - $_SESSION['ultima_atividade'] > 28800)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['ultima_atividade'] = time();
}

$host = 'localhost';
$dbname = 'silab';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

function garantirRoot($pdo) {
    $stmt = $pdo->prepare("SELECT id FROM usuario WHERE perfil = 'root'");
    $stmt->execute();
    $root = $stmt->fetch();
    
    if (!$root) {
        $senha_hash = password_hash('Root@00', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuario (nome_completo, matricula, email, senha_hash, perfil, status) 
                               VALUES ('Root Administrator', '20251SI0000', 'root@silab.com', ?, 'root', 'aprovado')");
        $stmt->execute([$senha_hash]);
        
        $stmt = $pdo->prepare("SELECT id FROM usuario WHERE perfil = 'root'");
        $stmt->execute();
        $root = $stmt->fetch();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM laboratorio");
        $total = $stmt->fetch()['total'];
        
        if ($total == 0 && $root) {
            $stmt = $pdo->prepare("INSERT INTO laboratorio (nome, capacidade, criado_por) VALUES 
                                   ('Lab 24', 30, ?),
                                   ('Lab 25', 30, ?),
                                   ('Lab 27', 30, ?)");
            $stmt->execute([$root['id'], $root['id'], $root['id']]);
        }
    }
    
    return $root['id'] ?? null;
}

garantirRoot($pdo);

function verificarLogin() {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['perfil'])) {
        header('Location: login.php');
        exit();
    }
    
    if ($_SESSION['perfil'] !== 'root') {
        header('Location: login.php');
        exit();
    }
    
    return true;
}

function verificarNaoLogado() {
    if (isset($_SESSION['usuario_id']) && isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'root') {
        header('Location: index.php');
        exit();
    }
}

function registrarLog($pdo, $usuario_id, $acao, $entidade, $entidade_id, $detalhes = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO log_sistema (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$usuario_id, $acao, $entidade, $entidade_id, $detalhes, $ip]);
}
?>